<?php
/**
 * Once-off library/training migration by URL sub-path — Story 16.6 (FL 16.6).
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Migration;

use Ink\Content\PostTypes;
use Ink\Content\Taxonomies;

defined( 'ABSPATH' ) || exit;

/**
 * Migrates legacy library/training posts onto their CPTs by URL sub-path — a
 * once-off, idempotent DB update (FL 16.6).
 *
 * Content under the `/biblioteek/` prefix becomes `biblioteek_item`; under
 * `/opleiding/` becomes `opleiding_artikel`. The CPTs register their archives at
 * the `biblioteek`/`opleiding` bases, so those high-value prefixes are preserved.
 * The intermediate path segments (between the prefix and the final post slug)
 * become taxonomy terms — `genre` for library, `vaardigheid` for training (their
 * primary taxonomies) — get-or-created and appended (never clobbering existing
 * terms, the {@see \Ink\Challenges\Migration::linkPostsToRound()} convention).
 *
 * Each item's pre-migration permalink is recorded in
 * {@see PostReclassifier::SOURCE_URL_META} before the type change (shared with
 * Story 16.7), and the rewrite rules are flushed afterward.
 *
 * Once-off + guarded ({@see OPTION_DONE}; `--force` re-runs); WP-CLI only
 * (`wp ink migrate-library-training`) — never a web request. Conflation-clean:
 * reads `Content\PostTypes` + `Content\Taxonomies` only; zero `Tiers`/`Entitlement`.
 *
 * Not `final`: the post/term methods are overridable seams so the orchestration
 * is unit-testable without the WordPress post/term API.
 *
 * @package Ink\Core
 */
class LibraryTrainingMigrator {

	/**
	 * The completion flag option — set once the migration has run.
	 *
	 * @var string
	 */
	public const OPTION_DONE = 'ink_migration_library_done';

	/**
	 * The WP-CLI command name (`wp ink migrate-library-training`).
	 *
	 * @var string
	 */
	public const CLI_COMMAND = 'ink migrate-library-training';

	/**
	 * Register the once-off WP-CLI trigger — ONLY under WP-CLI (never a web request).
	 */
	public function register(): void {
		if ( ! ( defined( 'WP_CLI' ) && constant( 'WP_CLI' ) ) ) {
			return;
		}

		if ( ! class_exists( '\WP_CLI' ) ) {
			return;
		}

		\WP_CLI::add_command(
			self::CLI_COMMAND,
			function ( array $args, array $assoc ): void {
				$summary = $this->run( isset( $assoc['force'] ) );
				\WP_CLI::success(
					sprintf(
						'Biblioteek/Opleiding gemigreer: %d biblioteek-items, %d opleiding-artikels, %d terme toegeken%s.',
						(int) $summary['biblioteek'],
						(int) $summary['opleiding'],
						(int) $summary['terms_assigned'],
						! empty( $summary['skipped'] ) ? ' (oorgeslaan — reeds gedoen)' : ''
					)
				);
			}
		);
	}

	/**
	 * The trimmed path segments of a URL (or path). Pure.
	 *
	 * @param string $path A URL or path.
	 * @return list<string>
	 */
	public static function pathSegments( string $path ): array {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.parse_url_parse_url -- pure helper: parses a plain path/URL string, no HTTP; kept WP-free so it is unit-testable without WordPress loaded.
		$only_path = (string) ( parse_url( $path, PHP_URL_PATH ) ?? $path );
		$trimmed   = trim( $only_path, '/' );

		if ( '' === $trimmed ) {
			return array();
		}

		return array_values( array_filter( explode( '/', $trimmed ), static fn ( string $s ): bool => '' !== $s ) );
	}

	/**
	 * The CPT for a content URL by its prefix, or null when it is neither. Pure.
	 *
	 * @param string $path The content URL/path.
	 * @return string|null
	 */
	public static function cptForPath( string $path ): ?string {
		$segments = self::pathSegments( $path );
		$prefix   = strtolower( $segments[0] ?? '' );

		return match ( $prefix ) {
			'biblioteek' => PostTypes::BIBLIOTEEK_ITEM,
			'opleiding'  => PostTypes::OPLEIDING_ARTIKEL,
			default      => null,
		};
	}

	/**
	 * The primary taxonomy for a migrated CPT, or null. Pure.
	 *
	 * @param string $cpt The CPT slug.
	 * @return string|null
	 */
	public static function taxonomyForCpt( string $cpt ): ?string {
		return match ( $cpt ) {
			PostTypes::BIBLIOTEEK_ITEM   => Taxonomies::GENRE,
			PostTypes::OPLEIDING_ARTIKEL => Taxonomies::VAARDIGHEID,
			default                      => null,
		};
	}

	/**
	 * The taxonomy-term slugs encoded in a content URL's sub-path. Pure.
	 *
	 * The segments between the prefix (first) and the post slug (last). A flat
	 * `/biblioteek/slug/` carries no intermediate terms.
	 *
	 * @param string $path The content URL/path.
	 * @return list<string>
	 */
	public static function termSlugsFromPath( string $path ): array {
		$segments = self::pathSegments( $path );

		if ( count( $segments ) <= 2 ) {
			return array();
		}

		return array_values( array_slice( $segments, 1, -1 ) );
	}

	/**
	 * Run the once-off migration. Idempotent unless `$force`.
	 *
	 * @param bool $force Re-run even when already completed.
	 * @return array{skipped:bool, biblioteek:int, opleiding:int, terms_assigned:int}
	 */
	public function run( bool $force = false ): array {
		if ( $this->hasRun() && ! $force ) {
			return array(
				'skipped'        => true,
				'biblioteek'     => 0,
				'opleiding'      => 0,
				'terms_assigned' => 0,
			);
		}

		$biblioteek     = 0;
		$opleiding      = 0;
		$terms_assigned = 0;

		foreach ( $this->legacyContent() as $item ) {
			$post_id = (int) ( $item->id ?? 0 );
			$url     = (string) ( $item->url ?? '' );

			if ( $post_id <= 0 || '' === $url ) {
				continue;
			}

			$cpt = self::cptForPath( $url );

			if ( null === $cpt ) {
				continue; // not library/training — left for Story 16.5.
			}

			$this->recordSourceUrl( $post_id );
			$this->setPostType( $post_id, $cpt );

			if ( PostTypes::BIBLIOTEEK_ITEM === $cpt ) {
				++$biblioteek;
			} else {
				++$opleiding;
			}

			$taxonomy = self::taxonomyForCpt( $cpt );
			$slugs    = self::termSlugsFromPath( $url );

			if ( null !== $taxonomy && array() !== $slugs ) {
				$terms_assigned += $this->assignTerms( $post_id, $taxonomy, $slugs );
			}
		}

		$this->flushRewrites();
		$this->markDone();

		return array(
			'skipped'        => false,
			'biblioteek'     => $biblioteek,
			'opleiding'      => $opleiding,
			'terms_assigned' => $terms_assigned,
		);
	}

	/**
	 * Whether the migration has already completed.
	 */
	public function hasRun(): bool {
		return (bool) get_option( self::OPTION_DONE, false );
	}

	/**
	 * Mark the migration complete (the idempotency flag).
	 */
	protected function markDone(): void {
		update_option( self::OPTION_DONE, true, false );
	}

	/**
	 * The legacy library/training content to migrate. Overridable seam.
	 *
	 * Default: posts whose permalink is under the `/biblioteek/` or `/opleiding/`
	 * prefix, as rows `{id, url}`.
	 *
	 * @return array<int, object>
	 */
	protected function legacyContent(): array {
		$ids = get_posts(
			array(
				'post_type'        => 'post',
				'post_status'      => 'any',
				'numberposts'      => -1,
				'fields'           => 'ids',
				'suppress_filters' => false,
			)
		);

		if ( ! is_array( $ids ) ) {
			return array();
		}

		$items = array();

		foreach ( $ids as $id ) {
			$id  = (int) $id;
			$url = get_permalink( $id );

			if ( ! is_string( $url ) ) {
				continue;
			}

			if ( null === self::cptForPath( $url ) ) {
				continue;
			}

			$items[] = (object) array(
				'id'  => $id,
				'url' => $url,
			);
		}

		return $items;
	}

	/**
	 * Record a post's pre-migration permalink (the 301 source). Overridable seam.
	 *
	 * @param int $post_id The post id.
	 */
	protected function recordSourceUrl( int $post_id ): void {
		$url = get_permalink( $post_id );

		if ( is_string( $url ) && '' !== $url ) {
			update_post_meta( $post_id, PostReclassifier::SOURCE_URL_META, $url );
		}
	}

	/**
	 * Change a post's type. Overridable seam.
	 *
	 * @param int    $post_id The post id.
	 * @param string $type    The target CPT slug.
	 */
	protected function setPostType( int $post_id, string $type ): void {
		set_post_type( $post_id, $type );
	}

	/**
	 * Get-or-create the term slugs and append them to the post. Overridable seam.
	 *
	 * @param int               $post_id  The post id.
	 * @param string            $taxonomy The taxonomy.
	 * @param array<int,string> $slugs    The term slugs from the sub-path.
	 * @return int The number of terms assigned.
	 */
	protected function assignTerms( int $post_id, string $taxonomy, array $slugs ): int {
		$term_ids = array();

		foreach ( $slugs as $slug ) {
			$slug = strtolower( trim( (string) $slug ) );

			if ( '' === $slug ) {
				continue;
			}

			$existing = term_exists( $slug, $taxonomy );

			if ( is_array( $existing ) && isset( $existing['term_id'] ) ) {
				$term_ids[] = (int) $existing['term_id'];
				continue;
			}

			$created = wp_insert_term( $slug, $taxonomy, array( 'slug' => $slug ) );

			if ( ! is_wp_error( $created ) && isset( $created['term_id'] ) ) {
				$term_ids[] = (int) $created['term_id'];
			}
		}

		if ( array() === $term_ids ) {
			return 0;
		}

		wp_set_object_terms( $post_id, $term_ids, $taxonomy, true );

		return count( $term_ids );
	}

	/**
	 * Flush the rewrite rules after migration. Overridable seam.
	 */
	protected function flushRewrites(): void {
		flush_rewrite_rules( false );
	}
}

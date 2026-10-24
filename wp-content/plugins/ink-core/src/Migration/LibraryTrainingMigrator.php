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
	 * @return array{skipped:bool, biblioteek:int, opleiding:int, terms_assigned:int, legacy_skipped:int, containers:int, unmapped_roots:list<string>}
	 */
	public function run( bool $force = false ): array {
		if ( $this->hasRun() && ! $force ) {
			return array(
				'skipped'        => true,
				'biblioteek'     => 0,
				'opleiding'      => 0,
				'terms_assigned' => 0,
				'legacy_skipped' => 0,
				'containers'     => 0,
				'unmapped_roots' => array(),
			);
		}

		$biblioteek     = 0;
		$opleiding      = 0;
		$terms_assigned = 0;
		$legacy_skipped = 0;
		$containers     = 0;
		$roots_seen     = array();

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

		$rows      = array();
		$decisions = array();

		foreach ( $this->legacyCptContent() as $item ) {
			$post_id = (int) ( $item->id ?? 0 );
			$type    = (string) ( $item->legacy_type ?? '' );
			$slugs   = is_array( $item->slugs ?? null ) ? array_values( $item->slugs ) : array();

			if ( $post_id <= 0 || array() === $slugs ) {
				continue;
			}

			$roots_seen[ $type ][] = (string) $slugs[0];
			$decisions[ $post_id ] = LegacyContentMap::decide( $type, $slugs );
			$rows[ $post_id ]      = array(
				'type'   => $type,
				'slugs'  => $slugs,
				'parent' => (int) ( $item->parent ?? 0 ),
			);
		}

		// A container is kept only if some child that stays under it is migrated.
		$kept_containers = array();

		foreach ( $rows as $post_id => $row ) {
			$parent = $row['parent'];

			if ( $parent > 0 && isset( $decisions[ $parent ] ) && LegacyContentMap::CONTAINER === $decisions[ $parent ]['action']
				&& LegacyContentMap::MIGRATE === $decisions[ $post_id ]['action']
				&& $decisions[ $parent ]['cpt'] === $decisions[ $post_id ]['cpt'] ) {
				$kept_containers[ $parent ] = true;
			}
		}

		foreach ( $rows as $post_id => $row ) {
			$decision = $decisions[ $post_id ];

			if ( LegacyContentMap::CONTAINER === $decision['action'] ) {
				if ( ! isset( $kept_containers[ $post_id ] ) ) {
					++$containers;
					continue;
				}
			} elseif ( LegacyContentMap::MIGRATE !== $decision['action'] ) {
				++$legacy_skipped;
				continue;
			}

			if ( null === $decision['cpt'] ) {
				++$legacy_skipped;
				continue;
			}

			$this->recordSourceUrlFor( $post_id, LegacyContentMap::legacyPath( $row['type'], $row['slugs'] ) );
			$this->setPostType( $post_id, $decision['cpt'] );

			// Keep the nested URL when the parent is migrated to the same type; otherwise
			// (moved dictionaries, skipped or dropped parents) detach so the URL stays valid.
			$parent          = $row['parent'];
			$parent_decision = $decisions[ $parent ] ?? null;
			$parent_kept     = null !== $parent_decision
				&& $parent_decision['cpt'] === $decision['cpt']
				&& ( LegacyContentMap::MIGRATE === $parent_decision['action'] || isset( $kept_containers[ $parent ] ) );

			if ( $parent > 0 && ! $parent_kept ) {
				$this->clearParent( $post_id );
			}

			if ( PostTypes::BIBLIOTEEK_ITEM === $decision['cpt'] ) {
				++$biblioteek;
			} else {
				++$opleiding;
			}

			if ( null !== $decision['taxonomy'] && null !== $decision['term'] ) {
				$terms_assigned += $this->assignNamedTerm( $post_id, $decision['taxonomy'], $decision['term'] );
			}
		}

		$unmapped = array();

		foreach ( $roots_seen as $type => $roots ) {
			$unmapped = array_merge( $unmapped, LegacyContentMap::unmappedRoots( (string) $type, $roots ) );
		}

		$this->flushRewrites();
		$this->markDone();

		return array(
			'skipped'        => false,
			'biblioteek'     => $biblioteek,
			'opleiding'      => $opleiding,
			'terms_assigned' => $terms_assigned,
			'legacy_skipped' => $legacy_skipped,
			'containers'     => $containers,
			'unmapped_roots' => array_values( array_unique( $unmapped ) ),
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
	 * The legacy `bibliotekon` / `opleiding` CPT content to migrate. Overridable seam.
	 *
	 * Rows `{id, legacy_type, slugs, parent}` where `slugs` is the chain of post slugs from the
	 * tree root down to the item (via `post_parent`).
	 *
	 * @return array<int, object>
	 */
	protected function legacyCptContent(): array {
		$posts = get_posts(
			array(
				'post_type'        => array( LegacyContentMap::LEGACY_LIBRARY, LegacyContentMap::LEGACY_TRAINING ),
				'post_status'      => 'any',
				'numberposts'      => -1,
				'suppress_filters' => false,
			)
		);

		if ( ! is_array( $posts ) ) {
			return array();
		}

		$by_id = array();

		foreach ( $posts as $post ) {
			$by_id[ (int) $post->ID ] = $post;
		}

		$items = array();

		foreach ( $by_id as $id => $post ) {
			$chain  = array();
			$cursor = $post;
			$guard  = 0;

			while ( null !== $cursor && $guard++ < 20 ) {
				array_unshift( $chain, (string) $cursor->post_name );
				$cursor = $by_id[ (int) $cursor->post_parent ] ?? null;
			}

			$items[] = (object) array(
				'id'          => $id,
				'legacy_type' => (string) $post->post_type,
				'slugs'       => $chain,
				'parent'      => (int) $post->post_parent,
			);
		}

		return $items;
	}

	/**
	 * Detach a migrated item from a parent that is not migrated alongside it.
	 * Overridable seam.
	 *
	 * @param int $post_id The post id.
	 */
	protected function clearParent( int $post_id ): void {
		wp_update_post(
			array(
				'ID'          => $post_id,
				'post_parent' => 0,
			)
		);
	}

	/**
	 * Record a post's pre-migration permalink (the 301 source). Overridable seam.
	 *
	 * @param int $post_id The post id.
	 */
	protected function recordSourceUrl( int $post_id ): void {
		$url = get_permalink( $post_id );

		if ( is_string( $url ) && '' !== $url ) {
			$this->recordSourceUrlFor( $post_id, $url );
		}
	}

	/**
	 * Record an explicit pre-migration URL (the 301 source), write-once. Overridable seam.
	 *
	 * @param int    $post_id The post id.
	 * @param string $url     The legacy permalink.
	 */
	protected function recordSourceUrlFor( int $post_id, string $url ): void {
		// Write-once (R16 review): never clobber an already-recorded source permalink
		// (a `--force` re-run, or PostReclassifier having recorded it first).
		if ( '' !== (string) get_post_meta( $post_id, PostReclassifier::SOURCE_URL_META, true ) ) {
			return;
		}

		if ( '' !== $url ) {
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
	 * Get-or-create a term by display name (slug derived) and append it to the post.
	 * Overridable seam.
	 *
	 * @param int    $post_id  The post id.
	 * @param string $taxonomy The taxonomy.
	 * @param string $name     The term display name.
	 * @return int 1 when a term was assigned, else 0.
	 */
	protected function assignNamedTerm( int $post_id, string $taxonomy, string $name ): int {
		$slug     = sanitize_title( $name );
		$existing = term_exists( $slug, $taxonomy );

		if ( is_array( $existing ) && isset( $existing['term_id'] ) ) {
			$term_id = (int) $existing['term_id'];
		} else {
			$created = wp_insert_term( $name, $taxonomy, array( 'slug' => $slug ) );

			if ( is_wp_error( $created ) || ! isset( $created['term_id'] ) ) {
				return 0;
			}

			$term_id = (int) $created['term_id'];
		}

		wp_set_object_terms( $post_id, array( $term_id ), $taxonomy, true );

		return 1;
	}

	/**
	 * Flush the rewrite rules after migration. Overridable seam.
	 */
	protected function flushRewrites(): void {
		flush_rewrite_rules( false );
	}
}

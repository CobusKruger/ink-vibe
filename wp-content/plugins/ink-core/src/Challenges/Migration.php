<?php
/**
 * Once-off historical challenge migration — Story 12.8 (FL 12.8, §14.6).
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Challenges;

use Ink\Content\ChallengeRound;
use Ink\Content\PostTypes;
use Ink\Content\Taxonomies;
use Ink\Kernel\Scalar;

defined( 'ABSPATH' ) || exit;

/**
 * Converts legacy challenge categories into the INK round model — a once-off,
 * idempotent DB update (FL 12.8).
 *
 * Each legacy challenge category becomes (a) an `uitdaging` post (the round record)
 * and (b) an `uitdagingsrondte` round term keyed to it via {@see ChallengeRound::slugFor()}
 * — the SAME join key live submissions (6.6) and the Library winner linkage (10.5)
 * use. Every piece filed under the legacy category is re-linked to the new round term
 * (append, never clobbering existing terms), preserving each piece's challenge linkage.
 * The uitdaging brief is taken from the legacy category description only where it
 * exists; no deadline is fabricated (legacy categories carry none).
 *
 * Once-off + guarded: a completion option ({@see self::OPTION_DONE}) makes a re-run a
 * no-op (a `--force` re-run is opt-in), and the trigger is WP-CLI only
 * (`wp ink migrate-challenges`) — NEVER auto-run on a web request. Conflation-clean:
 * reads `Ink\Content` + WP core, zero Tiers/Entitlement.
 *
 * Not `final`: the I/O methods are overridable seams so the orchestration is
 * unit-testable without the WordPress term/post API (the {@see ChallengeLinking}
 * precedent).
 *
 * @package Ink\Core
 */
class Migration {

	/**
	 * The completion flag option — set once the migration has run.
	 *
	 * @var string
	 */
	public const OPTION_DONE = 'ink_challenge_migration_done';

	/**
	 * The WP-CLI command name (`wp ink migrate-challenges`).
	 *
	 * @var string
	 */
	public const CLI_COMMAND = 'ink migrate-challenges';

	/**
	 * Meta key linking a migrated uitdaging back to its source legacy category id.
	 * The get-or-create marker that makes a `--force` re-run reconcile (reuse the
	 * existing round) instead of inserting a duplicate uitdaging (R12 review).
	 *
	 * @var string
	 */
	public const SOURCE_CATEGORY_META = 'ink_uitdaging_source_category';

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
						'Uitdagings gemigreer: %d geskep, %d stukke gekoppel%s.',
						(int) $summary['created'],
						(int) $summary['linked'],
						! empty( $summary['skipped'] ) ? ' (oorgeslaan — reeds gedoen)' : ''
					)
				);
			}
		);
	}

	/**
	 * Build the `wp_insert_post` array for the uitdaging from a legacy category. Pure.
	 *
	 * The brief (post content) is the legacy category description where it exists;
	 * empty otherwise ("full brief only where old data exists").
	 *
	 * @param object $category A legacy category row (name + description).
	 * @return array<string, mixed>
	 */
	public static function uitdagingPostArr( object $category ): array {
		return array(
			'post_type'    => PostTypes::UITDAGING,
			'post_title'   => Scalar::asString( $category->name ?? '' ),
			'post_content' => self::briefFrom( Scalar::asString( $category->description ?? '' ) ),
			'post_status'  => 'publish',
		);
	}

	/**
	 * The uitdaging brief from a legacy category description. Pure.
	 *
	 * @param string $description The legacy category description.
	 * @return string
	 */
	public static function briefFrom( string $description ): string {
		return trim( $description );
	}

	/**
	 * Run the once-off migration. Idempotent unless `$force`.
	 *
	 * @param bool $force Re-run even when already completed.
	 * @return array{skipped:bool, created:int, linked:int}
	 */
	public function run( bool $force = false ): array {
		if ( $this->hasRun() && ! $force ) {
			return array(
				'skipped' => true,
				'created' => 0,
				'linked'  => 0,
			);
		}

		$created = 0;
		$linked  = 0;

		foreach ( $this->legacyCategories() as $category ) {
			// Skip a malformed/empty-name category: it would otherwise create an
			// untitled published uitdaging that wp_insert_term then rejects (R12 review).
			if ( '' === trim( Scalar::asString( $category->name ?? '' ) ) ) {
				continue;
			}

			$uitdaging_id = $this->ensureUitdaging( $category );

			if ( $uitdaging_id <= 0 ) {
				continue;
			}

			$term_id = $this->ensureRoundTerm( $uitdaging_id );

			if ( $term_id <= 0 ) {
				continue;
			}

			++$created;

			$post_ids = $this->postsInCategory( (int) ( $category->term_id ?? 0 ) );
			$linked  += $this->linkPostsToRound( $post_ids, $term_id );
		}

		$this->markDone();

		return array(
			'skipped' => false,
			'created' => $created,
			'linked'  => $linked,
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
	 * The legacy challenge categories to migrate. Overridable seam.
	 *
	 * SAFE DEFAULT (R12 review): returns an EMPTY list. The legacy-challenge selection
	 * is site-specific (a configured parent category / a naming convention), and a
	 * blanket "migrate every `category`" default would convert ordinary blog
	 * categories (Nuus, Uncategorized, …) into published uitdagings and mis-tag their
	 * posts as challenge entries. A site MUST override this with the actual challenge
	 * categories before the once-off run; an un-overridden run is a deliberate no-op.
	 *
	 * @return array<int, object>
	 */
	protected function legacyCategories(): array {
		return array();
	}

	/**
	 * Get-or-create the uitdaging post for a legacy category (idempotent). Overridable
	 * seam. Reuses an uitdaging already migrated from this category (matched by the
	 * {@see self::SOURCE_CATEGORY_META} marker) so a `--force` re-run reconciles instead
	 * of inserting a duplicate round (R12 review).
	 *
	 * @param object $category The legacy category row.
	 * @return int The uitdaging id, or 0 on failure.
	 */
	protected function ensureUitdaging( object $category ): int {
		$category_id = (int) ( $category->term_id ?? 0 );

		$existing = $this->findUitdagingForCategory( $category_id );

		if ( $existing > 0 ) {
			return $existing;
		}

		$id = $this->createUitdaging( self::uitdagingPostArr( $category ) );

		if ( $id > 0 && $category_id > 0 ) {
			update_post_meta( $id, self::SOURCE_CATEGORY_META, $category_id );
		}

		return $id;
	}

	/**
	 * The id of an uitdaging already migrated from this legacy category, or 0.
	 * Overridable seam.
	 *
	 * @param int $category_id The legacy category term id.
	 * @return int
	 */
	protected function findUitdagingForCategory( int $category_id ): int {
		if ( $category_id <= 0 ) {
			return 0;
		}

		$ids = get_posts(
			array(
				'post_type'        => PostTypes::UITDAGING,
				'post_status'      => 'any',
				'numberposts'      => 1,
				'fields'           => 'ids',
				'suppress_filters' => false,
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- a once-off CLI migration lookup keyed on the source-category marker, not a request-path query.
				'meta_query'       => array(
					array(
						'key'   => self::SOURCE_CATEGORY_META,
						'value' => $category_id,
					),
				),
			)
		);

		return ( is_array( $ids ) && isset( $ids[0] ) ) ? (int) $ids[0] : 0;
	}

	/**
	 * Create the uitdaging post for a round. Overridable seam.
	 *
	 * @param array<string, mixed> $postarr The `wp_insert_post` args.
	 * @return int The new uitdaging id, or 0 on failure.
	 */
	protected function createUitdaging( array $postarr ): int {
		$id = wp_insert_post( $postarr, true );

		return is_wp_error( $id ) ? 0 : (int) $id;
	}

	/**
	 * Get-or-create the `uitdagingsrondte` round term for a uitdaging. Overridable seam.
	 *
	 * Uses the {@see ChallengeRound::slugFor()} convention so migrated rounds share the
	 * live join key.
	 *
	 * @param int $uitdaging_id The new uitdaging id.
	 * @return int The round term id, or 0 on failure.
	 */
	protected function ensureRoundTerm( int $uitdaging_id ): int {
		$slug     = ChallengeRound::slugFor( $uitdaging_id );
		$existing = get_term_by( 'slug', $slug, Taxonomies::UITDAGINGSRONDTE );

		if ( $existing instanceof \WP_Term ) {
			return (int) $existing->term_id;
		}

		$result = wp_insert_term(
			get_the_title( $uitdaging_id ),
			Taxonomies::UITDAGINGSRONDTE,
			array( 'slug' => $slug )
		);

		if ( is_wp_error( $result ) ) {
			return 0;
		}

		return (int) ( $result['term_id'] ?? 0 );
	}

	/**
	 * The post ids filed under a legacy category. Overridable seam.
	 *
	 * @param int $category_id The legacy category term id.
	 * @return list<int>
	 */
	protected function postsInCategory( int $category_id ): array {
		if ( $category_id <= 0 ) {
			return array();
		}

		$ids = get_posts(
			array(
				'post_type'        => 'any',
				'post_status'      => 'any',
				'numberposts'      => -1,
				'fields'           => 'ids',
				'suppress_filters' => false,
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- a once-off CLI migration over a single legacy category, not a request-path query.
				'tax_query'        => array(
					array(
						'taxonomy' => 'category',
						'field'    => 'term_id',
						'terms'    => $category_id,
					),
				),
			)
		);

		return is_array( $ids ) ? array_map( 'intval', $ids ) : array();
	}

	/**
	 * Append the round term to each piece (never clobbering existing terms).
	 * Overridable seam.
	 *
	 * @param list<int> $post_ids The pieces to re-link.
	 * @param int       $term_id  The round term id.
	 * @return int The number of pieces linked.
	 */
	protected function linkPostsToRound( array $post_ids, int $term_id ): int {
		$linked = 0;

		foreach ( $post_ids as $post_id ) {
			$post_id = (int) $post_id;

			if ( $post_id <= 0 ) {
				continue;
			}

			wp_set_object_terms( $post_id, array( $term_id ), Taxonomies::UITDAGINGSRONDTE, true );
			++$linked;
		}

		return $linked;
	}
}

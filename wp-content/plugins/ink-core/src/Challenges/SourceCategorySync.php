<?php
/**
 * Uitdaging ↔ source-category sync — migration rework.
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Challenges;

use Ink\Content\ChallengeRound;
use Ink\Content\PostTypes;
use Ink\Content\Taxonomies;

defined( 'ABSPATH' ) || exit;

/**
 * Keeps a challenge's entries in step with its source category.
 *
 * On prod a writer enters a challenge by ticking its category (e.g. "Oktober 2026 -
 * Getal 10 projek"). An uitdaging records that category in
 * {@see Migration::SOURCE_CATEGORY_META}; this class makes the link permanent: whenever
 * a post's categories are saved, every uitdaging whose source category is among them
 * gets its round term ({@see ChallengeRound::slugFor()}) appended to the post, so the
 * entry joins the challenge (judge emails, collation, ingestion) with no manual step.
 * It also backs {@see self::sync()}, which an editor or the migration can run to pull in
 * everything already filed under the category.
 *
 * Append-only: it never removes a round term (a moderator may have placed an entry
 * deliberately). Decisions are pure; the term/post I/O sits behind overridable seams.
 *
 * @package Ink\Core
 */
class SourceCategorySync {

	/**
	 * The WP-CLI command name (`wp ink sync-challenge-entries`).
	 *
	 * @var string
	 */
	public const CLI_COMMAND = 'ink sync-challenge-entries';

	/**
	 * Wire the sync to category assignment.
	 */
	public function register(): void {
		add_action( 'set_object_terms', array( $this, 'onSetObjectTerms' ), 10, 4 );

		if ( defined( 'WP_CLI' ) && constant( 'WP_CLI' ) && class_exists( '\WP_CLI' ) ) {
			\WP_CLI::add_command(
				self::CLI_COMMAND,
				function (): void {
					\WP_CLI::success( sprintf( 'Deelnames gesinkroniseer: %d skakel(s) na uitdagingsrondtes.', $this->sync() ) );
				}
			);
		}
	}

	/**
	 * Hook: a post's terms were set. Only `category` changes matter.
	 *
	 * @param int               $object_id The post id.
	 * @param array<int, mixed> $terms     The terms passed (ids or slugs).
	 * @param array<int, int>   $tt_ids    The resulting term-taxonomy ids.
	 * @param string            $taxonomy  The taxonomy.
	 */
	public function onSetObjectTerms( int $object_id, array $terms, array $tt_ids, string $taxonomy ): void {
		if ( 'category' !== $taxonomy ) {
			return;
		}

		$this->linkPostToRounds( $object_id, $this->categoryIdsOf( $object_id ) );
	}

	/**
	 * The uitdaging ids whose source category is among the given categories. Pure.
	 *
	 * @param array<int, int>        $category_ids The post's category ids.
	 * @param array<int, int|string> $source_map   uitdaging id → its source category id.
	 * @return list<int>
	 */
	public static function matchingUitdagings( array $category_ids, array $source_map ): array {
		$wanted = array_map( 'intval', $category_ids );
		$hits   = array();

		foreach ( $source_map as $uitdaging_id => $category_id ) {
			if ( (int) $category_id > 0 && in_array( (int) $category_id, $wanted, true ) ) {
				$hits[] = (int) $uitdaging_id;
			}
		}

		return $hits;
	}

	/**
	 * Pull every post already filed under each uitdaging's source category into its round.
	 *
	 * @return int The number of post↔round links made.
	 */
	public function sync(): int {
		$linked = 0;

		foreach ( $this->sourceMap() as $uitdaging_id => $category_id ) {
			$term_id = $this->roundTermId( (int) $uitdaging_id );

			if ( $term_id <= 0 ) {
				continue;
			}

			foreach ( $this->postsInCategory( (int) $category_id ) as $post_id ) {
				$this->appendRoundTerm( $post_id, $term_id );
				++$linked;
			}
		}

		return $linked;
	}

	/**
	 * Append the round terms of every matching uitdaging to one post.
	 *
	 * @param int             $post_id      The post.
	 * @param array<int, int> $category_ids Its category ids.
	 */
	protected function linkPostToRounds( int $post_id, array $category_ids ): void {
		if ( array() === $category_ids ) {
			return;
		}

		foreach ( self::matchingUitdagings( $category_ids, $this->sourceMap() ) as $uitdaging_id ) {
			$term_id = $this->roundTermId( $uitdaging_id );

			if ( $term_id > 0 ) {
				$this->appendRoundTerm( $post_id, $term_id );
			}
		}
	}

	/**
	 * A post's category ids. Overridable seam.
	 *
	 * @param int $post_id The post.
	 * @return array<int, int>
	 */
	protected function categoryIdsOf( int $post_id ): array {
		$ids = wp_get_object_terms( $post_id, 'category', array( 'fields' => 'ids' ) );

		return is_array( $ids ) ? array_map( 'intval', $ids ) : array();
	}

	/**
	 * Every uitdaging's source category (uitdaging id → category id). Overridable seam.
	 *
	 * @return array<int, int>
	 */
	protected function sourceMap(): array {
		$ids = get_posts(
			array(
				'post_type'        => PostTypes::UITDAGING,
				'post_status'      => 'any',
				'numberposts'      => -1,
				'fields'           => 'ids',
				'suppress_filters' => false,
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- the source-category marker is the join key; a small, bounded set of uitdagings.
				'meta_key'         => Migration::SOURCE_CATEGORY_META,
			)
		);

		$map = array();

		foreach ( is_array( $ids ) ? $ids : array() as $id ) {
			$category = (int) get_post_meta( (int) $id, Migration::SOURCE_CATEGORY_META, true );

			if ( $category > 0 ) {
				$map[ (int) $id ] = $category;
			}
		}

		return $map;
	}

	/**
	 * The round term id for an uitdaging, or 0. Overridable seam.
	 *
	 * @param int $uitdaging_id The uitdaging.
	 */
	protected function roundTermId( int $uitdaging_id ): int {
		$term = get_term_by( 'slug', ChallengeRound::slugFor( $uitdaging_id ), Taxonomies::UITDAGINGSRONDTE );

		return $term instanceof \WP_Term ? (int) $term->term_id : 0;
	}

	/**
	 * Post ids filed under a category, any type/status. Overridable seam.
	 *
	 * @param int $category_id The category.
	 * @return list<int>
	 */
	protected function postsInCategory( int $category_id ): array {
		$ids = get_posts(
			array(
				'post_type'        => 'any',
				'post_status'      => 'any',
				'numberposts'      => -1,
				'fields'           => 'ids',
				'suppress_filters' => false,
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- explicit editor/CLI sync over one category, not a request-path query.
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
	 * Append a round term to a post, never clobbering existing terms. Overridable seam.
	 *
	 * @param int $post_id The post.
	 * @param int $term_id The round term.
	 */
	protected function appendRoundTerm( int $post_id, int $term_id ): void {
		wp_set_object_terms( $post_id, array( $term_id ), Taxonomies::UITDAGINGSRONDTE, true );
	}
}

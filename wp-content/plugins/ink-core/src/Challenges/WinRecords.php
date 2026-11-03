<?php
/**
 * Win records: the interface between new wins and the winner importer's store.
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Challenges;

use Ink\Content\PostTypes;
use Ink\Kernel\Scalar;

defined( 'ABSPATH' ) || exit;

/**
 * One record per win, and the writer's lifetime count (D10, `03-tuis` §6).
 *
 * **Interim store.** `winner-cleanup.md` §7 leaves the choice of store to the winner
 * importer, which isn't built. Until then a record is a row of non-unique meta on the round
 * ({@see META}), which holds a win with no post or no account as readily as one with both.
 * The importer may adopt it or replace the bodies of {@see self::record()} and
 * {@see self::lifetimeCountFor()}; every caller goes through these two.
 *
 * The lifetime count is counted from `ink_entry_placement` rows by author, as `03-tuis` §6
 * says to until the importer lands: its old wins aren't in any store yet, and
 * `ink_tier_win_count` resets on promotion, so it can't serve.
 *
 * @package Ink\Core
 */
final class WinRecords {

	/**
	 * Non-unique post meta on the round, one JSON row per win.
	 *
	 * @var string
	 */
	public const META = 'ink_uitdaging_wen';

	/**
	 * Build a win row. Pure.
	 *
	 * @param int    $uitdaging_id The round.
	 * @param int    $post_id      The winning post, or 0.
	 * @param int    $user_id      The writer's account, or 0: the win stays recorded and promotes nobody.
	 * @param int    $position     The stored position, 1-3.
	 * @param string $category     The category (post type).
	 * @param string $grade        The entry-time Gradering, or ''.
	 * @param string $title        The win's title.
	 * @return array{round_id:int, post_id:int, user_id:int, position:int, category:string, grade:string, title:string}
	 */
	public static function row( int $uitdaging_id, int $post_id, int $user_id, int $position, string $category, string $grade, string $title ): array {
		return array(
			'round_id' => $uitdaging_id,
			'post_id'  => max( 0, $post_id ),
			'user_id'  => max( 0, $user_id ),
			'position' => $position,
			'category' => $category,
			'grade'    => $grade,
			'title'    => $title,
		);
	}

	/**
	 * Add the record for a win the results commit just placed.
	 *
	 * @param int                                         $uitdaging_id The round.
	 * @param array{post_id:int, rank:int, author_id:int} $winner       The winner.
	 * @return bool Whether a record was added.
	 */
	public static function recordEntry( int $uitdaging_id, array $winner ): bool {
		$post_id = (int) ( $winner['post_id'] ?? 0 );

		return self::record(
			self::row(
				$uitdaging_id,
				$post_id,
				(int) ( $winner['author_id'] ?? 0 ),
				(int) ( $winner['rank'] ?? 0 ),
				(string) get_post_type( $post_id ),
				Scalar::asString( get_post_meta( $post_id, Entry::GRADERING_META_KEY, true ) ),
				(string) get_the_title( $post_id )
			)
		);
	}

	/**
	 * Store a win row. Skips one the round already holds, so a retried write can't count twice.
	 *
	 * @param array{round_id:int, post_id:int, user_id:int, position:int, category:string, grade:string, title:string} $row The row.
	 * @return bool Whether a record was added.
	 */
	public static function record( array $row ): bool {
		if ( $row['round_id'] <= 0 || ! Placements::isValidRank( $row['position'] ) ) {
			return false;
		}

		$encoded = (string) wp_json_encode( $row );

		// add_post_meta() strips slashes, so a title with a quote needs the slashed form.
		return in_array( $row, self::forRound( $row['round_id'] ), true )
			? false
			: false !== add_post_meta( $row['round_id'], self::META, wp_slash( $encoded ) );
	}

	/**
	 * A round's stored win rows.
	 *
	 * @param int $uitdaging_id The round.
	 * @return list<array<string, mixed>>
	 */
	public static function forRound( int $uitdaging_id ): array {
		$stored = get_post_meta( $uitdaging_id, self::META, false );
		$rows   = array();

		foreach ( is_array( $stored ) ? $stored : array() as $json ) {
			$row = json_decode( Scalar::asString( $json ), true );

			if ( is_array( $row ) ) {
				$rows[] = $row;
			}
		}

		return $rows;
	}

	/**
	 * A writer's lifetime win count: the one function the importer replaces.
	 *
	 * @param int $user_id The writer.
	 * @return int
	 */
	public static function lifetimeCountFor( int $user_id ): int {
		if ( $user_id <= 0 ) {
			return 0;
		}

		$query = new \WP_Query( self::lifetimeQueryArgs( $user_id ) );

		return (int) $query->found_posts;
	}

	/**
	 * The query behind {@see self::lifetimeCountFor()}: placed, readable posts by this writer. Pure.
	 *
	 * @param int $user_id The writer.
	 * @return array<string, mixed>
	 */
	public static function lifetimeQueryArgs( int $user_id ): array {
		return array(
			'post_type'           => PostTypes::readableTypes(),
			'post_status'         => 'publish',
			'author'              => $user_id,
			'fields'              => 'ids',
			'posts_per_page'      => 1,
			'ignore_sticky_posts' => true,
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- one writer's posts; the author filter bounds it.
			'meta_query'          => array(
				array(
					'key'     => Placements::PLACEMENT_META_KEY,
					'value'   => array( Placements::RANK_FIRST, Placements::MAX_RANK ),
					'compare' => 'BETWEEN',
					'type'    => 'NUMERIC',
				),
			),
		);
	}
}

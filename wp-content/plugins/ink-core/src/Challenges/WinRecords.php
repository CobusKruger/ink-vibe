<?php
/**
 * Win records: the interface between new wins and the winner importer's store.
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Challenges;

use Ink\Kernel\Scalar;

defined( 'ABSPATH' ) || exit;

/**
 * One record per win, and the writer's lifetime count (D10, `03-tuis` §6).
 *
 * **The store** (decided when the winner importer was built): a row of non-unique meta on the
 * round ({@see META}), one JSON row per win. It holds a win with no post or no account as readily
 * as one with both, so a win by a writer who left still counts. The importer
 * ({@see \Ink\Migration\WinnerImporter}) writes the old wins (`source` = `import`); a live results
 * commit ({@see Ingestion}) writes the new ones, through the same {@see self::record()}.
 *
 * The lifetime count is the number of a writer's rows over all rounds. `ink_tier_win_count`
 * resets on promotion, so it cannot serve.
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
	 * @param string $byline       The by-line as printed, for a win with no account; '' to leave it out.
	 * @param string $source       Where the row came from (`import`); '' to leave it out.
	 * @return array{round_id:int, post_id:int, user_id:int, position:int, category:string, grade:string, title:string, byline?:string, source?:string}
	 */
	public static function row( int $uitdaging_id, int $post_id, int $user_id, int $position, string $category, string $grade, string $title, string $byline = '', string $source = '' ): array {
		$row = array(
			'round_id' => $uitdaging_id,
			'post_id'  => max( 0, $post_id ),
			'user_id'  => max( 0, $user_id ),
			'position' => $position,
			'category' => $category,
			'grade'    => $grade,
			'title'    => $title,
		);

		if ( '' !== $byline ) {
			$row['byline'] = $byline;
		}

		if ( '' !== $source ) {
			$row['source'] = $source;
		}

		return $row;
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
	 * @param array{round_id:int, post_id:int, user_id:int, position:int, category:string, grade:string, title:string, byline?:string, source?:string} $row The row.
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
	 * A writer's lifetime win count: every win recorded for the account, in any category, on any round.
	 *
	 * @param int $user_id The writer.
	 * @return int
	 */
	public static function lifetimeCountFor( int $user_id ): int {
		global $wpdb;

		if ( $user_id <= 0 || ! $wpdb instanceof \wpdb ) {
			return 0;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- one indexed-by-key count over a few thousand rows; the rows are JSON in meta.
		return (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key = %s AND meta_value LIKE %s",
				self::META,
				self::userLike( $wpdb->esc_like( '"user_id":' . $user_id . ',' ) )
			)
		);
	}

	/**
	 * A writer's recorded wins, newest round first (by the round's post date, then id).
	 *
	 * Imported and live wins alike: this is the history the public profile shows. Each item is the
	 * stored row plus the round's `round_date` (post date, site time).
	 *
	 * @param int $user_id The writer.
	 * @param int $limit   How many to return at most.
	 * @return list<array<string, mixed>>
	 */
	public static function forUser( int $user_id, int $limit = 3 ): array {
		global $wpdb;

		if ( $user_id <= 0 || $limit <= 0 || ! $wpdb instanceof \wpdb ) {
			return array();
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- one lookup over this module's own meta; the rows are JSON in meta.
		$found = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT m.meta_value, p.post_date FROM {$wpdb->postmeta} m INNER JOIN {$wpdb->posts} p ON p.ID = m.post_id
				WHERE m.meta_key = %s AND m.meta_value LIKE %s
				ORDER BY p.post_date DESC, p.ID DESC, m.meta_id DESC LIMIT %d",
				self::META,
				self::userLike( $wpdb->esc_like( '"user_id":' . $user_id . ',' ) ),
				$limit
			)
		);

		$rows = array();

		foreach ( is_array( $found ) ? $found : array() as $item ) {
			$row = json_decode( Scalar::asString( $item->meta_value ), true );

			if ( is_array( $row ) && (int) ( $row['user_id'] ?? 0 ) === $user_id ) {
				$row['round_date'] = (string) $item->post_date;
				$rows[]            = $row;
			}
		}

		return $rows;
	}

	/**
	 * The writer's win count as of a round: their wins on that round and every earlier one (by the
	 * round's post date, then id). This is what "Nth wen" on a card means, not the lifetime total.
	 * 0 when the round or account is unknown, so the card shows no label rather than a wrong one.
	 *
	 * @param int $user_id      The writer.
	 * @param int $uitdaging_id The round the win belongs to.
	 * @return int
	 */
	public static function countThroughRound( int $user_id, int $uitdaging_id ): int {
		global $wpdb;

		if ( $user_id <= 0 || $uitdaging_id <= 0 || ! $wpdb instanceof \wpdb ) {
			return 0;
		}

		$round = get_post( $uitdaging_id );

		if ( ! $round instanceof \WP_Post ) {
			return 0;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- one count over a few thousand rows; the rows are JSON in meta.
		return (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->postmeta} m INNER JOIN {$wpdb->posts} p ON p.ID = m.post_id
				WHERE m.meta_key = %s AND m.meta_value LIKE %s
				AND ( p.post_date < %s OR ( p.post_date = %s AND p.ID <= %d ) )",
				self::META,
				self::userLike( $wpdb->esc_like( '"user_id":' . $user_id . ',' ) ),
				$round->post_date,
				$round->post_date,
				$uitdaging_id
			)
		);
	}

	/**
	 * The round a winning post won in, or 0 when no win record names it.
	 *
	 * @param int $post_id  The winning work.
	 * @param int $user_id  Its writer.
	 * @return int
	 */
	public static function roundOfWin( int $post_id, int $user_id ): int {
		global $wpdb;

		if ( $post_id <= 0 || $user_id <= 0 || ! $wpdb instanceof \wpdb ) {
			return 0;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- one lookup by key in this module's own meta.
		return (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = %s AND meta_value LIKE %s ORDER BY meta_id DESC LIMIT 1",
				self::META,
				self::userLike( $wpdb->esc_like( '"post_id":' . $post_id . ',"user_id":' . $user_id . ',' ) )
			)
		);
	}

	/**
	 * The LIKE pattern that finds a writer's rows:`"user_id":<id>,` anywhere in the JSON. Pure.
	 *
	 * @param string $escaped The escaped needle.
	 * @return string
	 */
	public static function userLike( string $escaped ): string {
		return '%' . $escaped . '%';
	}

	/**
	 * Delete the rows one source wrote, on every round (a rehearsal re-run of the importer).
	 *
	 * @param string $source The source marker, e.g. `import`.
	 * @return int How many rows went.
	 */
	public static function clearSource( string $source ): int {
		global $wpdb;

		if ( '' === $source || ! $wpdb instanceof \wpdb ) {
			return 0;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- a once-off rehearsal clean-up of this module's own meta.
		$gone = $wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->postmeta} WHERE meta_key = %s AND meta_value LIKE %s",
				self::META,
				'%' . $wpdb->esc_like( '"source":"' . $source . '"' ) . '%'
			)
		);

		wp_cache_flush();

		return is_int( $gone ) ? $gone : 0;
	}
}

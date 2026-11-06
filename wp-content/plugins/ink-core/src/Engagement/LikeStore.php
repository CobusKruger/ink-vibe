<?php
/**
 * Poem hartjies — one per member per work.
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Engagement;

defined( 'ABSPATH' ) || exit;

/**
 * Who has hearted a work, and the work's denormalized hartjie count.
 *
 * A work's hartjies are counted ONCE per member: hearting five lines and then the work
 * itself is still one. A line hartjie ({@see ReactionStore}) implies the work hartjie
 * ({@see self::add()} is idempotent), but un-hearting a line never removes it. The count
 * lives in `ink_reaksie_telling` ({@see ReactionStore::TOTAL_META_KEY}): migration primes it
 * with the old site's likes (which have no member behind them), and this store adjusts it by
 * +1 / -1 only when a member's row is really added / removed. It is never recounted from rows.
 *
 * @package Ink\Core
 */
final class LikeStore {

	/**
	 * Unprefixed table name — also the Kernel `Schema` id.
	 */
	public const TABLE = 'ink_likes';

	/**
	 * The fully-qualified (prefixed) table name.
	 */
	public static function tableName(): string {
		global $wpdb;

		return $wpdb->prefix . self::TABLE;
	}

	/**
	 * The `dbDelta()`-compatible DDL. `UNIQUE KEY user_post` makes a member's like single.
	 */
	public static function schemaSql(): string {
		global $wpdb;

		$table   = self::tableName();
		$collate = $wpdb->get_charset_collate();

		return "CREATE TABLE {$table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			post_id bigint(20) unsigned NOT NULL,
			user_id bigint(20) unsigned NOT NULL,
			created_at datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY user_post (user_id,post_id),
			KEY post_id (post_id)
		) {$collate};";
	}

	/**
	 * Heart a work for a member. Idempotent: a repeat changes nothing, counts nothing.
	 *
	 * @param int $post_id The work.
	 * @param int $user_id The member.
	 * @return bool True when this call added the like (the count went up).
	 */
	public static function add( int $post_id, int $user_id ): bool {
		global $wpdb;

		if ( $post_id <= 0 || $user_id <= 0 ) {
			return false;
		}

		$table = self::tableName();

		// INSERT IGNORE on the UNIQUE (user_id,post_id) key: 1 affected row = newly liked.
		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$affected = $wpdb->query(
			$wpdb->prepare(
				"INSERT IGNORE INTO {$table} (post_id, user_id, created_at) VALUES (%d, %d, %s)",
				$post_id,
				$user_id,
				current_time( 'mysql', true )
			)
		);
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		if ( 1 !== (int) $affected ) {
			return false;
		}

		self::adjustCount( $post_id, 1 );

		return true;
	}

	/**
	 * Un-heart a work for a member.
	 *
	 * @param int $post_id The work.
	 * @param int $user_id The member.
	 * @return bool True when a like was removed (the count went down).
	 */
	public static function remove( int $post_id, int $user_id ): bool {
		global $wpdb;

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$deleted = $wpdb->delete(
			self::tableName(),
			array(
				'post_id' => $post_id,
				'user_id' => $user_id,
			),
			array( '%d', '%d' )
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		if ( 1 !== (int) $deleted ) {
			return false;
		}

		self::adjustCount( $post_id, -1 );

		return true;
	}

	/**
	 * Whether the member has hearted the work.
	 *
	 * @param int $post_id The work.
	 * @param int $user_id The member.
	 */
	public static function has( int $post_id, int $user_id ): bool {
		global $wpdb;

		if ( $post_id <= 0 || $user_id <= 0 ) {
			return false;
		}

		$table = self::tableName();

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$found = $wpdb->get_var(
			$wpdb->prepare( "SELECT id FROM {$table} WHERE post_id = %d AND user_id = %d", $post_id, $user_id )
		);
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		return null !== $found;
	}

	/**
	 * The work's hartjie count (the denormalized field; 0 when never set).
	 *
	 * @param int $post_id The work.
	 */
	public static function count( int $post_id ): int {
		return max( 0, (int) get_post_meta( $post_id, ReactionStore::TOTAL_META_KEY, true ) );
	}

	/**
	 * Move the denormalized count by one step, never below zero. Atomic in SQL so two
	 * simultaneous likes both count.
	 *
	 * @param int $post_id The work.
	 * @param int $delta   +1 or -1.
	 */
	private static function adjustCount( int $post_id, int $delta ): void {
		global $wpdb;

		// The field may be missing (never primed): create it at 0 so the UPDATE has a row.
		if ( '' === (string) get_post_meta( $post_id, ReactionStore::TOTAL_META_KEY, true ) ) {
			update_post_meta( $post_id, ReactionStore::TOTAL_META_KEY, 0 );
		}

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query(
			$wpdb->prepare(
				"UPDATE {$wpdb->postmeta} SET meta_value = GREATEST(0, CAST(meta_value AS SIGNED) + %d) WHERE post_id = %d AND meta_key = %s",
				$delta,
				$post_id,
				ReactionStore::TOTAL_META_KEY
			)
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		wp_cache_delete( $post_id, 'post_meta' );
	}
}

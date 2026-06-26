<?php
/**
 * Follow-graph custom-table store — Story 9.2 (FR-38, AD-5).
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Social;

defined( 'ABSPATH' ) || exit;

/**
 * Owns the `{$wpdb->prefix}ink_follows` custom table (architecture AD-5).
 *
 * The asymmetric, one-way follow graph: `user_id` (the follower) → `followee_id`
 * (the followed skrywer). A custom table (not BuddyPress Friends, not a follow
 * add-on) so the edge is indexed both ways — the member's following list
 * (`KEY user_id`, the 9.3 feed) AND the reverse volgeling count (`KEY
 * followee_id`). A `UNIQUE KEY (user_id, followee_id)` dedups: a repeat follow
 * touches `created_at` only, never a duplicate row. Created at activation via
 * the Kernel {@see \Ink\Kernel\Schema} registry.
 *
 * Asymmetric: `follow(A, B)` creates the A→B edge ONLY — it does not imply B→A
 * (afrikaans-terms.md "Asimmetries; vervang die vorige vriendskapsmodel"). The
 * migration's friendship→two-follows conversion (Epic 16) is a migration
 * concern, not this store's behavior.
 *
 * Conflation-clean: references only `$wpdb`; zero `Ink\Tiers`/`Ink\Entitlement`
 * (following is open to any lid, never entitlement- or tier-gated).
 *
 * @package Ink\Core
 */
final class FollowStore {

	/**
	 * Unprefixed table name — the single source. Also the Kernel `Schema` id.
	 */
	public const TABLE = 'ink_follows';

	/**
	 * The fully-qualified (prefixed) table name.
	 */
	public static function tableName(): string {
		global $wpdb;

		return $wpdb->prefix . self::TABLE;
	}

	/**
	 * The `dbDelta()`-compatible DDL.
	 *
	 * `UNIQUE KEY user_followee` dedups an edge; `KEY followee_id` supports the
	 * reverse volgeling-count / "who follows this writer" query; `KEY user_id`
	 * supports the member's following list (the 9.3 feed). Two spaces after
	 * `PRIMARY KEY` (dbDelta).
	 */
	public static function schemaSql(): string {
		global $wpdb;

		$table   = self::tableName();
		$collate = $wpdb->get_charset_collate();

		return "CREATE TABLE {$table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			user_id bigint(20) unsigned NOT NULL,
			followee_id bigint(20) unsigned NOT NULL,
			created_at datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY user_followee (user_id,followee_id),
			KEY user_id (user_id),
			KEY followee_id (followee_id)
		) {$collate};";
	}

	/**
	 * Follow a skrywer (deduped — a repeat follow is a no-op).
	 *
	 * Self-follow and invalid ids are rejected WITHOUT a write: a user cannot
	 * follow themselves (AC #3), and a non-positive id is never a real user.
	 *
	 * @param int $user_id     The follower.
	 * @param int $followee_id The followed skrywer.
	 * @return bool True when the edge was written; false when rejected.
	 */
	public static function follow( int $user_id, int $followee_id ): bool {
		if ( $user_id <= 0 || $followee_id <= 0 || $user_id === $followee_id ) {
			return false;
		}

		global $wpdb;

		$table = self::tableName();

		// Dedup via the UNIQUE (user_id,followee_id) key: a repeat follow touches
		// created_at only, never a duplicate row. Constant table; values bound.
		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$result = $wpdb->query(
			$wpdb->prepare(
				"INSERT INTO {$table} (user_id, followee_id, created_at)
				VALUES (%d, %d, %s)
				ON DUPLICATE KEY UPDATE created_at = VALUES(created_at)",
				$user_id,
				$followee_id,
				current_time( 'mysql', true )
			)
		);
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		return false !== $result;
	}

	/**
	 * Stop following a skrywer (idempotent).
	 *
	 * @param int $user_id     The follower.
	 * @param int $followee_id The followed skrywer.
	 * @return bool True when the statement ran.
	 */
	public static function unfollow( int $user_id, int $followee_id ): bool {
		global $wpdb;

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$deleted = $wpdb->delete(
			self::tableName(),
			array(
				'user_id'     => $user_id,
				'followee_id' => $followee_id,
			),
			array( '%d', '%d' )
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		return false !== $deleted;
	}

	/**
	 * Whether `$user_id` follows `$followee_id`.
	 *
	 * @param int $user_id     The follower.
	 * @param int $followee_id The followed skrywer.
	 * @return bool
	 */
	public static function isFollowing( int $user_id, int $followee_id ): bool {
		global $wpdb;

		$table = self::tableName();

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$found = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM {$table} WHERE user_id = %d AND followee_id = %d",
				$user_id,
				$followee_id
			)
		);
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		return null !== $found;
	}

	/**
	 * How many lede follow a skrywer (the volgeling count).
	 *
	 * @param int $followee_id The followed skrywer.
	 * @return int
	 */
	public static function followerCount( int $followee_id ): int {
		global $wpdb;

		$table = self::tableName();

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$count = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$table} WHERE followee_id = %d",
				$followee_id
			)
		);
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		return (int) $count;
	}

	/**
	 * How many skrywers a member follows (the following count).
	 *
	 * @param int $user_id The follower.
	 * @return int
	 */
	public static function followingCount( int $user_id ): int {
		global $wpdb;

		$table = self::tableName();

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$count = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$table} WHERE user_id = %d",
				$user_id
			)
		);
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		return (int) $count;
	}

	/**
	 * The skrywer ids a member follows, newest first (for the 9.3 feed).
	 *
	 * @param int $user_id The follower.
	 * @return list<int>
	 */
	public static function followeeIdsFor( int $user_id ): array {
		global $wpdb;

		$table = self::tableName();

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT followee_id FROM {$table} WHERE user_id = %d ORDER BY created_at DESC, id DESC",
				$user_id
			)
		);
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		return is_array( $ids ) ? array_map( 'intval', $ids ) : array();
	}
}

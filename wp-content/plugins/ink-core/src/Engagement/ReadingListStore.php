<?php
/**
 * Leeslys (reading list) custom-table store — Story 7.7 (FR-29, AD-5).
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Engagement;

defined( 'ABSPATH' ) || exit;

/**
 * Owns the `{$wpdb->prefix}ink_reading_list` custom table (architecture AD-5).
 *
 * A member's saved works: (user, post) → saved, deduped by a UNIQUE key. A custom
 * table (not an unbounded serialized user-meta array) so it is indexed for the
 * member's own list AND the reverse "who saved this" query (a later discovery
 * sort). Created at activation via the Kernel {@see \Ink\Kernel\Schema} registry.
 *
 * Conflation-clean: references only `$wpdb`; zero `Ink\Tiers`/`Ink\Entitlement`
 * (the leeslys is open to any lid, never entitlement-gated).
 *
 * @package Ink\Core
 */
final class ReadingListStore {

	/**
	 * Unprefixed table name — the single source. Also the Kernel `Schema` id.
	 */
	public const TABLE = 'ink_reading_list';

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
	 * `UNIQUE KEY user_post` dedups a member's saves; `KEY post_id` supports the
	 * reverse "who saved this" query. Two spaces after `PRIMARY KEY` (dbDelta).
	 */
	public static function schemaSql(): string {
		global $wpdb;

		$table   = self::tableName();
		$collate = $wpdb->get_charset_collate();

		return "CREATE TABLE {$table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			user_id bigint(20) unsigned NOT NULL,
			post_id bigint(20) unsigned NOT NULL,
			created_at datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY user_post (user_id,post_id),
			KEY user_id (user_id),
			KEY post_id (post_id)
		) {$collate};";
	}

	/**
	 * Save a work to a member's leeslys (deduped — a repeat save is a no-op).
	 *
	 * @param int $user_id The member.
	 * @param int $post_id The work.
	 * @return bool True when the statement ran.
	 */
	public static function add( int $user_id, int $post_id ): bool {
		global $wpdb;

		$table = self::tableName();

		// Dedup via the UNIQUE (user_id,post_id) key: a repeat save touches
		// created_at only, never a duplicate row. Constant table name; values bound.
		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$result = $wpdb->query(
			$wpdb->prepare(
				"INSERT INTO {$table} (user_id, post_id, created_at)
				VALUES (%d, %d, %s)
				ON DUPLICATE KEY UPDATE created_at = VALUES(created_at)",
				$user_id,
				$post_id,
				current_time( 'mysql', true )
			)
		);
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		return false !== $result;
	}

	/**
	 * Remove a work from a member's leeslys (idempotent).
	 *
	 * @param int $user_id The member.
	 * @param int $post_id The work.
	 * @return bool True when the statement ran.
	 */
	public static function remove( int $user_id, int $post_id ): bool {
		global $wpdb;

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$deleted = $wpdb->delete(
			self::tableName(),
			array(
				'user_id' => $user_id,
				'post_id' => $post_id,
			),
			array( '%d', '%d' )
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		return false !== $deleted;
	}

	/**
	 * Whether a work is in a member's leeslys.
	 *
	 * @param int $user_id The member.
	 * @param int $post_id The work.
	 * @return bool
	 */
	public static function has( int $user_id, int $post_id ): bool {
		global $wpdb;

		$table = self::tableName();

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$found = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM {$table} WHERE user_id = %d AND post_id = %d",
				$user_id,
				$post_id
			)
		);
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		return null !== $found;
	}

	/**
	 * A member's saved post ids, newest first.
	 *
	 * @param int $user_id The member.
	 * @return list<int>
	 */
	public static function forUser( int $user_id ): array {
		global $wpdb;

		$table = self::tableName();

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT post_id FROM {$table} WHERE user_id = %d ORDER BY created_at DESC, id DESC",
				$user_id
			)
		);
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		return is_array( $ids ) ? array_map( 'intval', $ids ) : array();
	}
}

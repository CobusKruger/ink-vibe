<?php
/**
 * Line-reaction custom table store — Story 7.3 (FR-26).
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Engagement;

use Ink\Kernel\Reaction;

defined( 'ABSPATH' ) || exit;

/**
 * Owns the `{$wpdb->prefix}ink_line_reactions` custom table (architecture AD-5).
 *
 * A line "highlight" always carries a reaction, so the highlight and the reaction
 * are ONE consolidated row: (post, line, user) → reaction. There is deliberately
 * NO text/body column — reactions are encouragement, not commentary; free-form
 * feedback is the structured Gemeenskapsreaksie (Story 7.4), never an inline
 * annotation here. The `UNIQUE KEY (post_id, line_index, user_id)` enforces
 * one-reaction-per-user-per-line; {@see self::set()} upserts so reacting again
 * changes the reaction.
 *
 * Created at activation via the Kernel {@see \Ink\Kernel\Schema} registry (the
 * provider is registered at plugin include time in `ink-core.php`). Conflation-
 * clean: references only the Kernel `Reaction` enum + `$wpdb`; zero `Ink\Tiers` /
 * `Ink\Entitlement` (engagement is open to any lid, never entitlement-gated).
 *
 * @package Ink\Core
 */
final class ReactionStore {

	/**
	 * Unprefixed table name — the single source. Also the Kernel `Schema` id.
	 */
	public const TABLE = 'ink_line_reactions';

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
	 * Whitespace matters to dbDelta: two spaces after `PRIMARY KEY`, one space
	 * between `KEY`/`UNIQUE KEY` and the index name, lowercase types. No text
	 * column — reactions-only (Story 7.3, AC #2).
	 */
	public static function schemaSql(): string {
		global $wpdb;

		$table   = self::tableName();
		$collate = $wpdb->get_charset_collate();

		return "CREATE TABLE {$table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			post_id bigint(20) unsigned NOT NULL,
			line_index int(10) unsigned NOT NULL,
			user_id bigint(20) unsigned NOT NULL,
			reaction varchar(20) NOT NULL DEFAULT '',
			created_at datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY user_line (post_id,line_index,user_id),
			KEY post_id (post_id)
		) {$collate};";
	}

	/**
	 * Upsert a member's reaction to one line (one row per user per line).
	 *
	 * Reacting again changes the reaction (the UNIQUE key drives the
	 * `ON DUPLICATE KEY UPDATE`). The `created_at` is refreshed on change.
	 *
	 * @param int      $post_id    The work.
	 * @param int      $line_index The 0-based physical-line index (7.2 contract).
	 * @param int      $user_id    The reacting member.
	 * @param Reaction $reaction   The reaction value.
	 * @return bool True when the row was written.
	 */
	public static function set( int $post_id, int $line_index, int $user_id, Reaction $reaction ): bool {
		global $wpdb;

		$table = self::tableName();

		// Upsert via a prepared statement: the UNIQUE (post_id,line_index,user_id)
		// key makes a repeat reaction an UPDATE, never a duplicate row. Table name
		// is a constant; every value is bound. A write needs no cache.
		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$result = $wpdb->query(
			$wpdb->prepare(
				"INSERT INTO {$table} (post_id, line_index, user_id, reaction, created_at)
				VALUES (%d, %d, %d, %s, %s)
				ON DUPLICATE KEY UPDATE reaction = VALUES(reaction), created_at = VALUES(created_at)",
				$post_id,
				$line_index,
				$user_id,
				$reaction->value,
				current_time( 'mysql', true )
			)
		);
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		return false !== $result;
	}

	/**
	 * Remove a member's reaction from one line (toggle off).
	 *
	 * @param int $post_id    The work.
	 * @param int $line_index The 0-based physical-line index.
	 * @param int $user_id    The member.
	 * @return bool True when a row was deleted.
	 */
	public static function remove( int $post_id, int $line_index, int $user_id ): bool {
		global $wpdb;

		// Safe $wpdb->delete() API (explicit format array, no interpolation).
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$deleted = $wpdb->delete(
			self::tableName(),
			array(
				'post_id'    => $post_id,
				'line_index' => $line_index,
				'user_id'    => $user_id,
			),
			array( '%d', '%d', '%d' )
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		return ! empty( $deleted );
	}

	/**
	 * The member's current reaction to one line, or null when none.
	 *
	 * @param int $post_id    The work.
	 * @param int $line_index The 0-based physical-line index.
	 * @param int $user_id    The member.
	 * @return Reaction|null
	 */
	public static function userReaction( int $post_id, int $line_index, int $user_id ): ?Reaction {
		global $wpdb;

		$table = self::tableName();

		// Constant table name; all filters bound through prepare().
		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$value = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT reaction FROM {$table} WHERE post_id = %d AND line_index = %d AND user_id = %d",
				$post_id,
				$line_index,
				$user_id
			)
		);
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		return is_string( $value ) ? Reaction::tryFrom( $value ) : null;
	}

	/**
	 * All reaction rows for a work (used by the count surfaces, Story 7.8).
	 *
	 * @param int $post_id The work.
	 * @return list<object> Raw rows (id, post_id, line_index, user_id, reaction, created_at).
	 */
	public static function forPost( int $post_id ): array {
		global $wpdb;

		$table = self::tableName();

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE post_id = %d ORDER BY line_index ASC, id ASC",
				$post_id
			)
		);
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		return is_array( $rows ) ? array_values( $rows ) : array();
	}
}

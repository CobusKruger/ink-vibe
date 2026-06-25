<?php
/**
 * Gradering audit log (graderingsgeskiedenis) custom table.
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Tiers;

use Ink\Kernel\Tier;

defined( 'ABSPATH' ) || exit;

/**
 * The append-only writer-Gradering audit log (Story 5.3, FR-12).
 *
 * Owns the `{$wpdb->prefix}ink_tier_history` custom table (architecture line
 * 879): one row per committed Gradering change, recording actor (0 = the
 * automatic engine), date, reason, from→to grade, and an optional challenge
 * link. The table is created at activation through the Kernel {@see \Ink\Kernel\Schema}
 * registry (the provider is registered at plugin include time in `ink-core.php`
 * — see that file for the activation-timing reason).
 *
 * This story supplies the typed APPEND ({@see self::record()}) and the typed
 * per-writer history READ ({@see self::forUser()}); the `Tiers::promote()`
 * orchestration that calls `record()` (with the `ink_writer_tier` write) lands
 * in Story 5.2 (manual) / 5.8 (auto). No update/delete API exists — it is an
 * audit log.
 *
 * THE conflation rule (AD-1): references only the Kernel `Tier` + WordPress
 * `$wpdb`; zero `Ink\Entitlement`. Deptrac `Tiers: [Kernel]` holds.
 *
 * @package Ink\Core
 */
final class PromotionLog {

	/**
	 * Unprefixed table name — the single source (`{$wpdb->prefix}` is added by
	 * {@see self::tableName()}). Also the Kernel `Schema` registry id.
	 */
	public const TABLE = 'ink_tier_history';

	/**
	 * The fully-qualified (prefixed) table name.
	 */
	public static function tableName(): string {
		global $wpdb;

		return $wpdb->prefix . self::TABLE;
	}

	/**
	 * The `dbDelta()`-compatible DDL for the audit table.
	 *
	 * Whitespace matters to dbDelta: two spaces after `PRIMARY KEY`, one space
	 * between `KEY` and the index name, lowercase types.
	 */
	public static function schemaSql(): string {
		global $wpdb;

		$table   = self::tableName();
		$collate = $wpdb->get_charset_collate();

		return "CREATE TABLE {$table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			user_id bigint(20) unsigned NOT NULL,
			from_tier varchar(20) NOT NULL DEFAULT '',
			to_tier varchar(20) NOT NULL DEFAULT '',
			actor_id bigint(20) unsigned NOT NULL DEFAULT 0,
			reason text NULL,
			challenge_id bigint(20) unsigned NOT NULL DEFAULT 0,
			created_at datetime NOT NULL,
			PRIMARY KEY  (id),
			KEY user_id (user_id),
			KEY created_at (created_at)
		) {$collate};";
	}

	/**
	 * Append one audit record for a committed Gradering change.
	 *
	 * @param int    $user_id      The writer whose grade changed.
	 * @param Tier   $from         The grade before the change.
	 * @param Tier   $to           The grade after the change.
	 * @param int    $actor_id     The staff user id, or 0 for the automatic engine.
	 * @param string $reason       The staff-entered (or system) reason.
	 * @param int    $challenge_id Optional linked challenge id (0 = none).
	 * @return bool True when the row was inserted.
	 */
	public static function record(
		int $user_id,
		Tier $from,
		Tier $to,
		int $actor_id = 0,
		string $reason = '',
		int $challenge_id = 0
	): bool {
		global $wpdb;

		// Append-only audit write to a custom table via the safe $wpdb->insert()
		// API (explicit format array, no interpolation); a write needs no cache.
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$inserted = $wpdb->insert(
			self::tableName(),
			array(
				'user_id'      => $user_id,
				'from_tier'    => $from->value,
				'to_tier'      => $to->value,
				'actor_id'     => $actor_id,
				'reason'       => $reason,
				'challenge_id' => $challenge_id,
				'created_at'   => current_time( 'mysql', true ),
			),
			array( '%d', '%s', '%s', '%d', '%s', '%d', '%s' )
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		return false !== $inserted;
	}

	/**
	 * The full Gradering history for one writer, newest first.
	 *
	 * The `user_id` filter is bound through `$wpdb->prepare()`; the table name is
	 * a constant (never user input).
	 *
	 * @param int $user_id The writer.
	 * @return list<PromotionLogEntry>
	 */
	public static function forUser( int $user_id ): array {
		global $wpdb;

		$table = self::tableName();

		// The table name is a class constant (never user input); the user_id
		// filter is bound through prepare(). An audit-log read is intentionally
		// uncached.
		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE user_id = %d ORDER BY created_at DESC, id DESC",
				$user_id
			)
		);
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		if ( ! is_array( $rows ) ) {
			return array();
		}

		return array_map(
			static fn ( object $row ): PromotionLogEntry => PromotionLogEntry::fromRow( $row ),
			$rows
		);
	}
}

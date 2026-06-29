<?php
/**
 * Content-report custom table store — Story 18.4 (§8).
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Forms;

defined( 'ABSPATH' ) || exit;

/**
 * Owns the `{$wpdb->prefix}ink_reports` custom table (Story 18.4).
 *
 * A content report is one row: (object_type, object_id, reporter_id) → reason +
 * optional detail + status. New reports default to {@see STATUS_OPEN}; a future
 * moderation surface flips them to {@see STATUS_RESOLVED}. Created at activation
 * via the Kernel {@see \Ink\Kernel\Schema} registry (the provider is registered at
 * plugin include time in `ink-core.php`, mirroring {@see \Ink\Engagement\ReactionStore}).
 *
 * Conflation-clean: references only the report enums + `$wpdb`; zero Tiers/Entitlement
 * (reporting is open to any lid, never entitlement-gated).
 *
 * @package Ink\Core
 */
final class ReportStore {

	/**
	 * Unprefixed table name — the single source. Also the Kernel `Schema` id.
	 */
	public const TABLE = 'ink_reports';

	/**
	 * Report status values (Afrikaans persisted strings).
	 */
	public const STATUS_OPEN     = 'oop';
	public const STATUS_RESOLVED = 'afgehandel';

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
	 * between `KEY` and the index name, lowercase types.
	 */
	public static function schemaSql(): string {
		global $wpdb;

		$table   = self::tableName();
		$collate = $wpdb->get_charset_collate();

		return "CREATE TABLE {$table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			object_type varchar(20) NOT NULL DEFAULT '',
			object_id bigint(20) unsigned NOT NULL,
			reporter_id bigint(20) unsigned NOT NULL,
			reason varchar(20) NOT NULL DEFAULT '',
			detail text NOT NULL,
			status varchar(20) NOT NULL DEFAULT 'oop',
			created_at datetime NOT NULL,
			PRIMARY KEY  (id),
			KEY object (object_type,object_id),
			KEY status (status)
		) {$collate};";
	}

	/**
	 * Record a new report (status = open). Returns the new row id, or 0 on failure.
	 *
	 * @param ReportTarget $target   What is being reported.
	 * @param int          $object_id The reported object's id.
	 * @param int          $reporter_id The reporting lid's user id.
	 * @param ReportReason $reason   The reason.
	 * @param string       $detail   Optional free-text detail (already sanitised).
	 * @return int
	 */
	public static function record( ReportTarget $target, int $object_id, int $reporter_id, ReportReason $reason, string $detail = '' ): int {
		global $wpdb;

		// Safe $wpdb->insert() API (explicit format array, no interpolation).
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$ok = $wpdb->insert(
			self::tableName(),
			array(
				'object_type' => $target->value,
				'object_id'   => $object_id,
				'reporter_id' => $reporter_id,
				'reason'      => $reason->value,
				'detail'      => $detail,
				'status'      => self::STATUS_OPEN,
				'created_at'  => current_time( 'mysql', true ),
			),
			array( '%s', '%d', '%d', '%s', '%s', '%s', '%s' )
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		return false === $ok ? 0 : (int) $wpdb->insert_id;
	}
}

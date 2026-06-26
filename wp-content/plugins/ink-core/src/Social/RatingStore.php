<?php
/**
 * Reader ratings & reviews custom-table store — Story 9.6 (FR-42, AD-5).
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Social;

defined( 'ABSPATH' ) || exit;

/**
 * Owns the `{$wpdb->prefix}ink_ratings` custom table (architecture AD-5).
 *
 * A reader's rating (1–5) + optional written review of a skrywer, with a
 * moderation {@see RatingStatus}. `UNIQUE (user_id, skrywer_id)` keeps it to one
 * rating per rater per writer (a re-submit upserts and returns to `hangend`);
 * `KEY (skrywer_id, status)` serves the public "approved ratings for this
 * writer" aggregate/reviews query.
 *
 * Held-for-moderation: a submitted rating is `hangend` and is NEVER public. The
 * public reads ({@see self::aggregate()}, {@see self::approvedReviews()}) filter
 * `goedgekeur` ONLY — so an unmoderated review can never surface (POPIA), and
 * with no approval path yet (pre-18.4) the public surface is simply empty.
 *
 * Conflation-clean: references only `$wpdb`; zero `Ink\Tiers`/`Ink\Entitlement`
 * (a reader rating is not the writer's Gradering, and rating is open to any lid).
 *
 * @package Ink\Core
 */
final class RatingStore {

	/**
	 * Unprefixed table name — the single source. Also the Kernel `Schema` id.
	 */
	public const TABLE = 'ink_ratings';

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
	 * `UNIQUE KEY user_skrywer` keeps one rating per rater per writer (dedup +
	 * upsert); `KEY skrywer_status` serves the public approved-ratings query. Two
	 * spaces after `PRIMARY KEY` (dbDelta).
	 */
	public static function schemaSql(): string {
		global $wpdb;

		$table   = self::tableName();
		$collate = $wpdb->get_charset_collate();

		return "CREATE TABLE {$table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			user_id bigint(20) unsigned NOT NULL,
			skrywer_id bigint(20) unsigned NOT NULL,
			score tinyint(3) unsigned NOT NULL,
			resensie text NOT NULL,
			status varchar(20) NOT NULL,
			created_at datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY user_skrywer (user_id,skrywer_id),
			KEY skrywer_status (skrywer_id,status)
		) {$collate};";
	}

	/**
	 * Submit (or update) a reader's rating of a writer — held for moderation.
	 *
	 * Upserts via the UNIQUE (user_id, skrywer_id) key and forces the status to
	 * `hangend`: a changed review must be re-moderated and is never auto-public.
	 *
	 * @param int    $user_id    The rater.
	 * @param int    $skrywer_id The rated writer.
	 * @param int    $score      The rating (1–5; caller validates).
	 * @param string $review     The optional written review (caller sanitizes).
	 * @return bool True when the statement ran.
	 */
	public static function rate( int $user_id, int $skrywer_id, int $score, string $review ): bool {
		global $wpdb;

		$table = self::tableName();

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$result = $wpdb->query(
			$wpdb->prepare(
				"INSERT INTO {$table} (user_id, skrywer_id, score, resensie, status, created_at)
				VALUES (%d, %d, %d, %s, %s, %s)
				ON DUPLICATE KEY UPDATE score = VALUES(score), resensie = VALUES(resensie), status = VALUES(status), created_at = VALUES(created_at)",
				$user_id,
				$skrywer_id,
				$score,
				$review,
				RatingStatus::Hangend->value,
				current_time( 'mysql', true )
			)
		);
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		return false !== $result;
	}

	/**
	 * The PUBLIC aggregate for a writer — over APPROVED ratings only.
	 *
	 * @param int $skrywer_id The rated writer.
	 * @return array{count:int, average:float}
	 */
	public static function aggregate( int $skrywer_id ): array {
		global $wpdb;

		$table = self::tableName();

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT COUNT(*) AS n, AVG(score) AS gem FROM {$table} WHERE skrywer_id = %d AND status = %s",
				$skrywer_id,
				RatingStatus::Goedgekeur->value
			),
			ARRAY_A
		);
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		$count = is_array( $row ) ? (int) ( $row['n'] ?? 0 ) : 0;
		$avg   = is_array( $row ) && null !== ( $row['gem'] ?? null ) ? (float) $row['gem'] : 0.0;

		return array(
			'count'   => $count,
			'average' => $avg,
		);
	}

	/**
	 * The PUBLIC approved written reviews for a writer (non-empty review text).
	 *
	 * @param int $skrywer_id The rated writer.
	 * @return list<array{user_id:int, score:int, resensie:string}>
	 */
	public static function approvedReviews( int $skrywer_id ): array {
		global $wpdb;

		$table = self::tableName();

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT user_id, score, resensie FROM {$table}
				WHERE skrywer_id = %d AND status = %s AND resensie <> ''
				ORDER BY created_at DESC",
				$skrywer_id,
				RatingStatus::Goedgekeur->value
			),
			ARRAY_A
		);
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		if ( ! is_array( $rows ) ) {
			return array();
		}

		return array_map(
			static fn ( array $row ): array => array(
				'user_id'  => (int) $row['user_id'],
				'score'    => (int) $row['score'],
				'resensie' => (string) $row['resensie'],
			),
			$rows
		);
	}

	/**
	 * Whether a lid has already rated a writer.
	 *
	 * @param int $user_id    The rater.
	 * @param int $skrywer_id The rated writer.
	 * @return bool
	 */
	public static function hasRated( int $user_id, int $skrywer_id ): bool {
		global $wpdb;

		$table = self::tableName();

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$found = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM {$table} WHERE user_id = %d AND skrywer_id = %d",
				$user_id,
				$skrywer_id
			)
		);
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		return null !== $found;
	}
}

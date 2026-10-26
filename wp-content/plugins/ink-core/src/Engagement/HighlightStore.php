<?php
/**
 * A member's saved text highlights on a story or article.
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Engagement;

defined( 'ABSPATH' ) || exit;

/**
 * Storage for the passages a signed-in member highlights while reading a storie or
 * artikel. A highlight is a character range inside one paragraph (paragraph index,
 * start and end offset into that paragraph's text), plus the highlighted text so the
 * client can tell when the piece was edited and the range no longer fits.
 *
 * Private to the member: nothing here is read for anyone but the owner.
 *
 * @package Ink\Core
 */
final class HighlightStore {

	/**
	 * The table (without the WordPress prefix).
	 */
	public const TABLE = 'ink_highlights';

	/**
	 * The most highlights one member can keep on one piece.
	 */
	public const MAX_PER_POST = 100;

	/**
	 * The longest quote kept.
	 */
	public const MAX_QUOTE = 500;

	/**
	 * The prefixed table name.
	 */
	public static function tableName(): string {
		global $wpdb;

		return $wpdb->prefix . self::TABLE;
	}

	/**
	 * The `dbDelta` DDL, registered with the Kernel Schema.
	 */
	public static function schemaSql(): string {
		global $wpdb;

		$table   = self::tableName();
		$collate = $wpdb->get_charset_collate();

		return "CREATE TABLE {$table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			user_id bigint(20) unsigned NOT NULL,
			post_id bigint(20) unsigned NOT NULL,
			para_index int(10) unsigned NOT NULL,
			start_offset int(10) unsigned NOT NULL,
			end_offset int(10) unsigned NOT NULL,
			quote text NOT NULL,
			created_at datetime NOT NULL,
			PRIMARY KEY  (id),
			KEY user_post (user_id,post_id)
		) {$collate};";
	}

	/**
	 * Validate and normalise a highlight submission. Pure.
	 *
	 * @param int    $para  The paragraph index.
	 * @param int    $start Start offset in the paragraph's text.
	 * @param int    $end   End offset (exclusive).
	 * @param string $quote The highlighted text.
	 * @return array{para:int, start:int, end:int, quote:string}|null Null when it cannot be a real selection.
	 */
	public static function normalise( int $para, int $start, int $end, string $quote ): ?array {
		$quote = trim( $quote );

		if ( $para < 0 || $start < 0 || $end <= $start || '' === $quote ) {
			return null;
		}

		if ( $end - $start > 20000 ) {
			return null;
		}

		return array(
			'para'  => $para,
			'start' => $start,
			'end'   => $end,
			'quote' => mb_substr( $quote, 0, self::MAX_QUOTE ),
		);
	}

	/**
	 * A member's highlights on one piece, oldest first.
	 *
	 * @param int $user_id The member.
	 * @param int $post_id The piece.
	 * @return list<array{id:int, para:int, start:int, end:int, quote:string}>
	 */
	public static function forUserPost( int $user_id, int $post_id ): array {
		global $wpdb;

		if ( $user_id <= 0 || $post_id <= 0 ) {
			return array();
		}

		$table = self::tableName();

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT id, para_index, start_offset, end_offset, quote FROM {$table} WHERE user_id = %d AND post_id = %d ORDER BY id ASC",
				$user_id,
				$post_id
			)
		);
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		$out = array();

		foreach ( is_array( $rows ) ? $rows : array() as $row ) {
			$out[] = array(
				'id'    => (int) $row->id,
				'para'  => (int) $row->para_index,
				'start' => (int) $row->start_offset,
				'end'   => (int) $row->end_offset,
				'quote' => (string) $row->quote,
			);
		}

		return $out;
	}

	/**
	 * Save a highlight, within the per-piece cap.
	 *
	 * @param int                                               $user_id The member.
	 * @param int                                               $post_id The piece.
	 * @param array{para:int, start:int, end:int, quote:string} $h       A {@see self::normalise()} result.
	 * @return int The new row id, or 0 when the cap is reached or the insert failed.
	 */
	public static function add( int $user_id, int $post_id, array $h ): int {
		global $wpdb;

		if ( count( self::forUserPost( $user_id, $post_id ) ) >= self::MAX_PER_POST ) {
			return 0;
		}

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$inserted = $wpdb->insert(
			self::tableName(),
			array(
				'user_id'      => $user_id,
				'post_id'      => $post_id,
				'para_index'   => $h['para'],
				'start_offset' => $h['start'],
				'end_offset'   => $h['end'],
				'quote'        => $h['quote'],
				'created_at'   => current_time( 'mysql', true ),
			),
			array( '%d', '%d', '%d', '%d', '%d', '%s', '%s' )
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		return false === $inserted ? 0 : (int) $wpdb->insert_id;
	}

	/**
	 * Delete one of a member's highlights.
	 *
	 * @param int $user_id The member (only their own rows are deletable).
	 * @param int $id      The highlight id.
	 * @return bool Whether a row was removed.
	 */
	public static function remove( int $user_id, int $id ): bool {
		global $wpdb;

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$deleted = $wpdb->delete(
			self::tableName(),
			array(
				'id'      => $id,
				'user_id' => $user_id,
			),
			array( '%d', '%d' )
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		return is_int( $deleted ) && $deleted > 0;
	}
}

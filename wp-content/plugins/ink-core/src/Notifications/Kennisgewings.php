<?php
/**
 * In-app kennisgewing emitter + mark-all-read boundary — Story 9.9 (FR-44, AD-5).
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Notifications;

defined( 'ABSPATH' ) || exit;

/**
 * Creates INK kennisgewings in the BuddyPress notifications store and owns the
 * timestamp-boundary "merk alles as gelees" logic.
 *
 * Per AD-5 there is NO parallel notifications table — INK registers a single
 * `ink` BP component with a typed {@see NotificationType} action. Every write
 * routes through {@see self::add()}, which is guarded so the whole module is a
 * clean no-op when BuddyPress is absent (the unit/CI repo) — the LifecycleEmails
 * / Action-Scheduler graceful-degradation precedent.
 *
 * "Merk alles as gelees" is a TIMESTAMP BOUNDARY, not a per-row flip: marking
 * stores a per-user GMT boundary; a kennisgewing is unread iff its created time
 * is strictly after the boundary. A notification arriving DURING the mark-all
 * operation (created after the boundary) stays unread — never wrongly cleared
 * (the phantom-unread bug a per-row mark-all causes). The pure boundary helpers
 * unit-test without BuddyPress.
 *
 * @package Ink\Core
 */
final class Kennisgewings {

	/**
	 * Per-user GMT "marked all read at" boundary (user-meta key).
	 */
	public const MARK_META = 'ink_kennisgewings_gelees_op';

	/**
	 * Create a kennisgewing for a user (guarded BP write; no-op without BP).
	 *
	 * Never notifies the actor about their own action, and never writes for a
	 * non-positive user id.
	 *
	 * @param int              $user_id  The recipient.
	 * @param NotificationType $type     The kennisgewing category.
	 * @param int              $item_id  The primary subject id (post/comment/etc).
	 * @param int              $actor_id The user who triggered it (0 = system).
	 * @return bool True when a notification was written.
	 */
	public static function add( int $user_id, NotificationType $type, int $item_id, int $actor_id = 0 ): bool {
		if ( $user_id <= 0 || ( $actor_id > 0 && $user_id === $actor_id ) ) {
			return false;
		}

		if ( ! function_exists( 'bp_notifications_add_notification' ) ) {
			return false;
		}

		$result = bp_notifications_add_notification(
			array(
				'user_id'           => $user_id,
				'item_id'           => $item_id,
				'secondary_item_id' => $actor_id,
				'component_name'    => NotificationType::COMPONENT,
				'component_action'  => $type->value,
			)
		);

		return false !== $result && null !== $result;
	}

	/**
	 * Mark every kennisgewing read for a user — by moving the boundary to now.
	 *
	 * Stores the GMT boundary (the race-free no-phantom-unread mechanism) and,
	 * when BuddyPress is present, also flips BP's own flags for tidiness.
	 *
	 * @param int $user_id The user.
	 */
	public static function markAllRead( int $user_id ): void {
		if ( $user_id <= 0 ) {
			return;
		}

		update_user_meta( $user_id, self::MARK_META, current_time( 'mysql', true ) );

		if ( function_exists( 'bp_notifications_mark_all_notifications_by_type' ) ) {
			// Tidy BP's own state; the boundary is the source of truth for unread.
			bp_notifications_mark_all_notifications_by_type( $user_id, NotificationType::COMPONENT );
		}
	}

	/**
	 * The user's current "marked all read at" GMT boundary ('' when never marked).
	 *
	 * @param int $user_id The user.
	 * @return string
	 */
	public static function boundaryFor( int $user_id ): string {
		$value = get_user_meta( $user_id, self::MARK_META, true );

		return is_string( $value ) ? $value : '';
	}

	/**
	 * Pure: is a kennisgewing (created at $created_gmt) unread relative to the
	 * boundary? Unread iff created strictly AFTER the boundary. An empty boundary
	 * (never marked all read) means everything is unread.
	 *
	 * MySQL `Y-m-d H:i:s` strings compare correctly lexicographically.
	 *
	 * @param string $created_gmt  The kennisgewing's GMT create time.
	 * @param string $boundary_gmt The user's mark-all-read boundary.
	 * @return bool
	 */
	public static function isUnread( string $created_gmt, string $boundary_gmt ): bool {
		if ( '' === $boundary_gmt ) {
			return true;
		}

		return $created_gmt > $boundary_gmt;
	}

	/**
	 * Pure: count unread kennisgewings from their create times + the boundary.
	 *
	 * @param list<string> $created_gmts The create times.
	 * @param string       $boundary_gmt The boundary.
	 * @return int
	 */
	public static function countUnread( array $created_gmts, string $boundary_gmt ): int {
		$n = 0;

		foreach ( $created_gmts as $created ) {
			if ( self::isUnread( (string) $created, $boundary_gmt ) ) {
				++$n;
			}
		}

		return $n;
	}
}

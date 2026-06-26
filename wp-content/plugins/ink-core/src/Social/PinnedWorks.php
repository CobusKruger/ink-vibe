<?php
/**
 * Pinned / selected works store — Story 9.5 (FR-41).
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Social;

defined( 'ABSPATH' ) || exit;

/**
 * Owns a skrywer's pinned-works list (user-meta `ink_vasgespelde_werke`).
 *
 * A small, ordered, capped list of the writer's own published bydraes, surfaced
 * "best work first" on the public Skrywerprofiel. Stored as user-meta — NOT a
 * custom table — because it is a bounded per-writer list with no reverse "who
 * pinned this" query (the leeslys needed a table precisely for its reverse
 * query; pins do not). Public-ish meta → `ink_` prefix without a leading
 * underscore (the pins are shown publicly).
 *
 * The pure list transforms ({@see self::addPin()} / {@see self::removePin()}) are
 * split from the persistence so the cap + dedup + order rules unit-test without
 * `update_user_meta`. Conflation-clean: zero `Ink\Tiers`/`Ink\Entitlement`
 * (pinning your own work is open to any writing lid).
 *
 * @package Ink\Core
 */
final class PinnedWorks {

	/**
	 * The user-meta key — the single source.
	 */
	public const META = 'ink_vasgespelde_werke';

	/**
	 * The maximum number of pinned works.
	 */
	public const MAX = 6;

	/**
	 * A writer's pinned post ids, in pin order (capped, deduped, sanitized).
	 *
	 * @param int $user_id The writer.
	 * @return list<int>
	 */
	public static function forUser( int $user_id ): array {
		$stored = get_user_meta( $user_id, self::META, true );

		return self::normalize( is_array( $stored ) ? $stored : array() );
	}

	/**
	 * Whether a post is in the writer's pinned list.
	 *
	 * @param int $user_id The writer.
	 * @param int $post_id The work.
	 * @return bool
	 */
	public static function isPinned( int $user_id, int $post_id ): bool {
		return in_array( $post_id, self::forUser( $user_id ), true );
	}

	/**
	 * Pin a work (append; capped; deduped). Persists.
	 *
	 * @param int $user_id The writer.
	 * @param int $post_id The work.
	 * @return bool True when the list now contains the pin (added or already present).
	 */
	public static function pin( int $user_id, int $post_id ): bool {
		$current = self::forUser( $user_id );
		$next    = self::addPin( $current, $post_id );

		if ( $next === $current ) {
			// No change: either already pinned, or at cap (rejected).
			return in_array( $post_id, $current, true );
		}

		update_user_meta( $user_id, self::META, $next );

		return true;
	}

	/**
	 * Unpin a work (idempotent). Persists.
	 *
	 * @param int $user_id The writer.
	 * @param int $post_id The work.
	 * @return bool True (the post is not pinned afterwards).
	 */
	public static function unpin( int $user_id, int $post_id ): bool {
		$current = self::forUser( $user_id );
		$next    = self::removePin( $current, $post_id );

		if ( $next !== $current ) {
			update_user_meta( $user_id, self::META, $next );
		}

		return true;
	}

	/**
	 * Pure: append a pin if absent and under the cap. Returns the list unchanged
	 * when already present OR at the cap (the writer must unpin first — no silent
	 * eviction of an existing pin).
	 *
	 * @param list<int> $pins    The current pins (already normalized).
	 * @param int       $post_id The work to pin.
	 * @return list<int>
	 */
	public static function addPin( array $pins, int $post_id ): array {
		if ( $post_id <= 0 || in_array( $post_id, $pins, true ) || count( $pins ) >= self::MAX ) {
			return $pins;
		}

		$pins[] = $post_id;

		return $pins;
	}

	/**
	 * Pure: remove a pin (idempotent), preserving order + reindexing.
	 *
	 * @param list<int> $pins    The current pins.
	 * @param int       $post_id The work to unpin.
	 * @return list<int>
	 */
	public static function removePin( array $pins, int $post_id ): array {
		return array_values(
			array_filter(
				$pins,
				static fn ( int $id ): bool => $id !== $post_id
			)
		);
	}

	/**
	 * Pure: sanitize a stored list to a deduped, capped, positive-int list in order.
	 *
	 * @param array<int|string, mixed> $stored The raw stored value.
	 * @return list<int>
	 */
	private static function normalize( array $stored ): array {
		$out = array();

		foreach ( $stored as $value ) {
			$id = (int) $value;

			if ( $id > 0 && ! in_array( $id, $out, true ) ) {
				$out[] = $id;
			}

			if ( count( $out ) >= self::MAX ) {
				break;
			}
		}

		return $out;
	}
}

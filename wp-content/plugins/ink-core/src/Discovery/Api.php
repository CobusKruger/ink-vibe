<?php
/**
 * Discovery module public facade (reserved).
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Discovery;

defined( 'ABSPATH' ) || exit;

/**
 * Discovery module facade.
 *
 * The sole public cross-module surface for Discovery (Epic 8). Other modules
 * reach Discovery only through this facade (AD-1). My Profiel rebuild §5.4:
 * the Bydraes-tab unified per-post card ({@see \Ink\Social\BydraesSurface})
 * reads the per-work read count + its "leser"/"lesers" label through here —
 * a real, non-circular cross-module Api-facade dependency (Discovery depends
 * only on Kernel/Content/Engagement/Tiers, never back on Social), mirroring
 * every other `*\Api` facade edge already accepted in `deptrac.yaml`.
 *
 * @package Ink\Core
 */
final class Api {

	/**
	 * A work's denormalized read count (AD-7, {@see ReadCount::READ_COUNT_META}).
	 *
	 * @param int $post_id The work.
	 * @return int
	 */
	public static function readCountFor( int $post_id ): int {
		return (int) get_post_meta( $post_id, ReadCount::READ_COUNT_META, true );
	}

	/**
	 * The verb-less, plural-correct "leser"/"lesers" read-count label
	 * (My Profiel rebuild §2 item 2 — readers, not lectures).
	 *
	 * @param int $n The read count.
	 * @return string
	 */
	public static function readerLabel( int $n ): string {
		return ReadCountSurface::countLabel( $n );
	}
}

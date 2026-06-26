<?php
/**
 * Automatic Gradering promotion engine.
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Tiers;

use Ink\Kernel\Tier;

defined( 'ABSPATH' ) || exit;

/**
 * The automatic writer-Gradering promotion engine (Story 5.8, FR-12a / R3).
 *
 * A *win* is any top-3 placement at the writer's current Gradering (each counts;
 * the R2 ingestion — Story 12A.3, not yet built — calls this with the win count).
 * The engine accumulates wins ({@see Api::recordWin()}) and promotes on the
 * thresholds: **Brons → Silwer at 5**, **Silwer → Goud at 15**. **Goud** has no
 * auto-threshold (terminal for auto) and **Meester** is manual-only — a win for
 * either never auto-promotes. The win count is reset to 0 by the promotion (the
 * {@see Api::promote()} reset, Story 5.7), so a single call promotes at most one
 * step.
 *
 * THE conflation rule (AD-1): Gradering advancement is competition-driven, never
 * entitlement-driven — this engine references only the Kernel `Tier` + this
 * module's `Api`, never `Ink\Entitlement`. A lapsed-membership Goud writer is
 * unaffected. Promotions go through the sole {@see Api::promote()} write path
 * with `actor_id = 0` (the automatic engine), which logs the change and fires
 * `ink/tier_promoted` (the Story 5.10 email seam).
 *
 * @package Ink\Core
 */
final class PromotionEngine {

	/**
	 * The single-source auto-promotion thresholds: current grade → wins needed +
	 * the next grade. Grades absent from the map (Goud, Meester) have no
	 * auto-threshold — this mirrors {@see Tier::isAutoPromotable()}.
	 *
	 * @var array<string, array{wins: int, next: Tier}>
	 */
	private const THRESHOLDS = array(
		Tier::Brons->value  => array(
			'wins' => 5,
			'next' => Tier::Silwer,
		),
		Tier::Silwer->value => array(
			'wins' => 15,
			'next' => Tier::Goud,
		),
	);

	/**
	 * Record top-3 win(s) for a writer and auto-promote when a threshold is met.
	 *
	 * @param int $user_id      The writer.
	 * @param int $wins         The number of top-3 wins to award (default 1).
	 * @param int $challenge_id Optional linked challenge id recorded on a promotion.
	 * @return Tier|null The new grade if promoted; null if no promotion occurred.
	 */
	public static function award( int $user_id, int $wins = 1, int $challenge_id = 0 ): ?Tier {
		$current = Api::forUser( $user_id );

		if ( ! isset( self::THRESHOLDS[ $current->value ] ) ) {
			// Goud / Meester — no auto-threshold, and the counter is never reset
			// for a terminal grade (only promote() resets it, and these never
			// promote). Skip accumulation so ink_tier_win_count cannot grow
			// unbounded with wins that can never count toward anything.
			return null;
		}

		// Accumulate the wins at the (auto-promotable) current Gradering.
		$total = Api::recordWin( $user_id, $wins );

		$rule = self::THRESHOLDS[ $current->value ];

		if ( $total < $rule['wins'] ) {
			return null;
		}

		$next = $rule['next'];

		// The promotion resets the win count to 0 (Story 5.7), so one award
		// promotes at most one step.
		return Api::promote( $user_id, $next, 0, __( 'Outomatiese bevordering', 'ink-core' ), $challenge_id )
			? $next
			: null;
	}

	/**
	 * The progress toward the next Gradering for a grade + current win count
	 * (Story 5.9) — reads the SAME single-source threshold map as {@see self::award()}.
	 *
	 * @param Tier $current The writer's current grade.
	 * @param int  $count   The current accumulated win count.
	 * @return array{needed: int, next: Tier}|null Wins still needed + the next
	 *         grade, or null for a terminal grade (Goud/Meester — no auto-threshold).
	 */
	public static function progressFor( Tier $current, int $count ): ?array {
		if ( ! isset( self::THRESHOLDS[ $current->value ] ) ) {
			return null;
		}

		$rule = self::THRESHOLDS[ $current->value ];

		return array(
			// Clamped to ≥1: the counter resets on promotion, so in practice the
			// count is always below the threshold; the clamp is defensive.
			'needed' => max( 1, $rule['wins'] - $count ),
			'next'   => $rule['next'],
		);
	}
}

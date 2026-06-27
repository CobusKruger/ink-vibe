<?php
/**
 * Biblioteek auto-update on win — reserved hook stub (Story 10.6, R4).
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Library;

defined( 'ABSPATH' ) || exit;

/**
 * The reserved Biblioteek auto-update event seam (R4 — P0 stub).
 *
 * When challenge winners are committed by R2 ingestion (Story 12A.3), that flow
 * calls {@see triggerForWinner()} (via {@see Api::notifyWinnerCommitted()}), which
 * fires the {@see self::HOOK} action carrying the producing uitdaging + the winning
 * post ids. The hook EXISTS and is INVOKED at P0 so 12A.3 can wire to it later
 * without rework (R4) — but its BODY is deferred: {@see onWinnerCommitted()} is a
 * documented no-op until the broader biblioteek-organisation analysis (§9.4) decides
 * how a winning piece becomes / updates a `biblioteek_item`.
 *
 * Mirrors the Tiers `ink/tier_promoted` event seam (do_action + a HOOK constant +
 * an add_action listener). Conflation-clean: the payload is challenge/post ids
 * only — zero `Ink\Tiers`/`Ink\Entitlement`. Becoming a library item is an
 * editorial/organisational outcome, never a tier/subscription gate.
 *
 * @package Ink\Core
 */
final class AutoUpdate {

	/**
	 * The reserved Biblioteek auto-update action hook (INK `ink/…` convention).
	 *
	 * @var string
	 */
	public const HOOK = 'ink/biblioteek_wen_bywerking';

	/**
	 * Wire the deferred-body listener to the reserved hook.
	 *
	 * The listener is attached at P0 so the seam is end-to-end (invoker → action →
	 * listener); its body is deferred (§9.4). Dispatched once by the Kernel on `init`.
	 */
	public function register(): void {
		add_action( self::HOOK, array( $this, 'onWinnerCommitted' ), 10, 2 );
	}

	/**
	 * Invoke the reserved hook when winners are committed. Fail-safe.
	 *
	 * The R2-ingestion (Story 12A.3) seam: fires {@see self::HOOK} with the
	 * producing uitdaging + the winning post ids. A non-positive `$uitdaging_id`
	 * does not fire (a tampered / unresolved commit is silently ignored).
	 *
	 * @param int       $uitdaging_id    The producing uitdaging post id.
	 * @param list<int> $winner_post_ids The winning bydrae/post ids (optional at the seam).
	 */
	public static function triggerForWinner( int $uitdaging_id, array $winner_post_ids = array() ): void {
		if ( $uitdaging_id <= 0 ) {
			return;
		}

		// phpcs:ignore WordPress.NamingConventions.ValidHookName.UseUnderscores -- INK ink/... event-surface convention (AD).
		do_action( self::HOOK, $uitdaging_id, $winner_post_ids );
	}

	/**
	 * Deferred hook body — the future biblioteek_item create/update on a win.
	 *
	 * P0 STUB: intentionally a no-op. The actual logic (create/update a
	 * `biblioteek_item` from each winning post, link it to the producing challenge
	 * via the Story 10.5 `uitdagingsrondte` round term, etc.) is held with the §9.4
	 * biblioteek-organisation analysis. This method is the documented landing spot
	 * so 12A.3 can fire the hook today and the body lands here later without rework.
	 *
	 * @param int       $uitdaging_id    The producing uitdaging post id.
	 * @param list<int> $winner_post_ids The winning bydrae/post ids.
	 */
	public function onWinnerCommitted( int $uitdaging_id, array $winner_post_ids ): void {
		// Deferred (§9.4) — no biblioteek content is written at P0. See class docblock.
	}
}

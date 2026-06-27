<?php
/**
 * Library module public facade.
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Library;

defined( 'ABSPATH' ) || exit;

/**
 * Library module facade — the sole public cross-module surface (AD-1).
 *
 * Other modules reach Library through this facade, never into its internals. At
 * 10.6 it exposes the reserved Biblioteek auto-update seam: Story 12A.3 (R2
 * winner ingestion, a different module) calls {@see notifyWinnerCommitted()} when
 * winners are committed, so the biblioteek auto-update can wire to it later
 * without rework (R4).
 *
 * @package Ink\Core
 */
final class Api {

	/**
	 * The reserved Biblioteek auto-update hook name (single source).
	 *
	 * @return string
	 */
	public static function winnerCommittedHook(): string {
		return AutoUpdate::HOOK;
	}

	/**
	 * Notify the Biblioteek that challenge winners were committed (R4 seam).
	 *
	 * Fires the reserved {@see AutoUpdate::HOOK} for a positive uitdaging id. The
	 * R2-ingestion (Story 12A.3) calls this; the hook body is deferred (§9.4).
	 *
	 * @param int       $uitdaging_id    The producing uitdaging post id.
	 * @param list<int> $winner_post_ids The winning bydrae/post ids (optional).
	 */
	public static function notifyWinnerCommitted( int $uitdaging_id, array $winner_post_ids = array() ): void {
		AutoUpdate::triggerForWinner( $uitdaging_id, $winner_post_ids );
	}
}

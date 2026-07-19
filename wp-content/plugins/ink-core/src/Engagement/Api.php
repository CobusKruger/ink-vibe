<?php
/**
 * Engagement module public facade (reserved).
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Engagement;

use Ink\Kernel\Reaction;

defined( 'ABSPATH' ) || exit;

/**
 * Engagement module facade.
 *
 * The sole public cross-module surface for Engagement (Epic 7). Other modules
 * reach Engagement only through this facade (AD-1).
 *
 * @package Ink\Core
 */
final class Api {

	/**
	 * The post-meta key holding a work's denormalized total reaction count.
	 *
	 * The cross-module contract for the "Mees geliefd" discovery sort (Story 8.2,
	 * AD-7): Discovery orders by this meta and reads the key through this facade
	 * rather than reaching into {@see ReactionStore} or duplicating the literal.
	 *
	 * @return string
	 */
	public static function reactionTotalMetaKey(): string {
		return ReactionStore::TOTAL_META_KEY;
	}

	/**
	 * A work's hartjie (Heart) reaction count — Story 19.4 (§6 home featured cards).
	 *
	 * The home "Die redakteur se keuse" cards show the Heart count as the work's
	 * hartjie total (the Heart glyph == the hartjie reaction). Sourced from the
	 * existing {@see ReactionStore} aggregation rather than re-counting — Discovery
	 * reads it through this facade (AD-1), never reaching into the store directly.
	 *
	 * @param int $post_id The work.
	 * @return int The hartjie count (0 when none).
	 */
	public static function hartjieCountForPost( int $post_id ): int {
		$counts = ReactionStore::countsForPost( $post_id );

		return (int) ( $counts[ Reaction::Hartjie->value ] ?? 0 );
	}

	/**
	 * The verb-less, plural-correct hartjie-count label — Story 19.4 (§6).
	 *
	 * Delegates to the single-source {@see ReactionCounts::label()} so the home card
	 * reads identically to every other reaction surface ("342 hartjies" / "1 hartjie").
	 *
	 * @param int $n The hartjie count.
	 * @return string
	 */
	public static function hartjieCountLabel( int $n ): string {
		return ReactionCounts::label( Reaction::Hartjie, $n );
	}

	/**
	 * A work's Gemeenskapsreaksie (MessageCircle) response count — Story 19.4 (§6).
	 *
	 * The MessageCircle count on the home featured cards is the structured-response
	 * (Gemeenskapsreaksie) total — the filtered `ink_reaksie` count from
	 * {@see ResponseStore::countForPost()} (AD-5a), NOT WordPress's `comment_count`.
	 * Discovery reads it through this facade (AD-1).
	 *
	 * @param int $post_id The work.
	 * @return int The response count (0 when none).
	 */
	public static function responseCountForPost( int $post_id ): int {
		return ResponseStore::countForPost( $post_id );
	}
}

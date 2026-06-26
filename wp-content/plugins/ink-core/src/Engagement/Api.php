<?php
/**
 * Engagement module public facade (reserved).
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Engagement;

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
}

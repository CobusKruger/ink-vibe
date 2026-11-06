<?php
/**
 * Challenges module public facade.
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Challenges;

defined( 'ABSPATH' ) || exit;

/**
 * Challenges module facade.
 *
 * The sole public cross-module surface for Challenges (Epic 12); will expose
 * `enter()` (called by Submission at the publish moment). Other modules reach
 * Challenges only through this facade (AD-1). Today it serves the Ontdek "Wenners"
 * view, which lists the algehele wenners and shows them as the Tuis winner cards.
 *
 * @package Ink\Core
 */
final class Api {

	/**
	 * The `meta_query` clause that selects the algehele wenners (placement rank 1). Pure.
	 *
	 * @return array{key:string, value:int, compare:string, type:string}
	 */
	public static function winnerMetaQuery(): array {
		return array(
			'key'     => Placements::PLACEMENT_META_KEY,
			'value'   => Placements::RANK_FIRST,
			'compare' => '=',
			'type'    => 'NUMERIC',
		);
	}

	/**
	 * A winning work as the Tuis winner card. '' for a work with no card (not a bydrae type).
	 *
	 * @param int $post_id The winning work.
	 * @return string
	 */
	public static function winnerCardHtml( int $post_id ): string {
		return FeaturedWinners::cardForPost( $post_id );
	}
}

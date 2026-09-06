<?php
/**
 * Shared per-post "work card" facts — extracted so a second card-rendering
 * surface never re-derives this by hand.
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Social;

use Ink\Engagement\Api as EngagementApi;

defined( 'ABSPATH' ) || exit;

/**
 * The days-ago label and engagement (hartjie/gemeenskap) counts a "work card"
 * needs, factored out of {@see SkrywerProfiel::pinnedCards()} (the first place
 * this shape was computed) so {@see FollowingFeed} — a second, sibling Social
 * surface needing the identical per-post facts — reuses it instead of
 * re-deriving the same `EngagementApi` reads and days-ago arithmetic by hand.
 *
 * Deliberately a small, dependency-free helper (not a base class or a trait)
 * rather than coupling `FollowingFeed` directly to `SkrywerProfiel`: neither
 * block is "the" owner of this logic, both are equally callers.
 *
 * @package Ink\Core
 */
final class WorkCardFacts {

	/**
	 * The "[N] dae/dag gelede" timestamp label for a work. Pure formatting.
	 *
	 * @param int $timestamp_gmt A GMT unix timestamp.
	 * @return string
	 */
	public static function daysAgoLabel( int $timestamp_gmt ): string {
		$days = max( 0, (int) floor( ( time() - $timestamp_gmt ) / DAY_IN_SECONDS ) );

		/* translators: %s: the number of days since publication. */
		$format = _n( '%s dag gelede', '%s dae gelede', $days, 'ink-core' );

		return sprintf( $format, number_format_i18n( $days ) );
	}

	/**
	 * The days-ago label for a post, resolved from its GMT publish timestamp.
	 * '' when the timestamp can't be resolved (mirrors the pre-existing
	 * `pinnedCards()` guard).
	 *
	 * @param int $post_id The work.
	 * @return string
	 */
	public static function daysAgoLabelForPost( int $post_id ): string {
		$timestamp = get_post_time( 'U', true, $post_id );

		return is_int( $timestamp ) ? self::daysAgoLabel( $timestamp ) : '';
	}

	/**
	 * The hartjie (like) count + label and gemeenskap (response) count for a
	 * post — display-only reads of `Engagement\Api`, degrading to zeros/''
	 * when Engagement is unavailable (the same guard `pinnedCards()` used).
	 *
	 * @param int $post_id The work.
	 * @return array{hartjies:int, hartjieLabel:string, gemeenskap:int}
	 */
	public static function engagement( int $post_id ): array {
		$has_engagement = class_exists( EngagementApi::class );
		$hartjies       = $has_engagement ? EngagementApi::hartjieCountForPost( $post_id ) : 0;

		return array(
			'hartjies'     => $hartjies,
			'hartjieLabel' => $has_engagement ? EngagementApi::hartjieCountLabel( $hartjies ) : '',
			'gemeenskap'   => $has_engagement ? EngagementApi::responseCountForPost( $post_id ) : 0,
		);
	}
}

<?php
/**
 * Social module public facade (reserved).
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Social;

defined( 'ABSPATH' ) || exit;

/**
 * Social module facade.
 *
 * The sole public cross-module surface for Social (Epic 9). Other modules reach
 * Social only through this facade (AD-1) — the 9.3 following-feed reads
 * {@see self::followeeIdsFor()}, the 9.4 Skrywerprofiel reads
 * {@see self::followerCount()} / {@see self::isFollowing()}. No module touches
 * {@see FollowStore} directly.
 *
 * @package Ink\Core
 */
final class Api {

	/**
	 * Whether `$user_id` follows `$skrywer_id`.
	 *
	 * @param int $user_id    The follower.
	 * @param int $skrywer_id The followed skrywer.
	 * @return bool
	 */
	public static function isFollowing( int $user_id, int $skrywer_id ): bool {
		return FollowStore::isFollowing( $user_id, $skrywer_id );
	}

	/**
	 * The volgeling count for a skrywer (how many lede follow them).
	 *
	 * @param int $skrywer_id The followed skrywer.
	 * @return int
	 */
	public static function followerCount( int $skrywer_id ): int {
		return FollowStore::followerCount( $skrywer_id );
	}

	/**
	 * How many skrywers a member follows.
	 *
	 * @param int $user_id The follower.
	 * @return int
	 */
	public static function followingCount( int $user_id ): int {
		return FollowStore::followingCount( $user_id );
	}

	/**
	 * The skrywer ids a member follows, newest first (the 9.3 feed source).
	 *
	 * @param int $user_id The follower.
	 * @return list<int>
	 */
	public static function followeeIdsFor( int $user_id ): array {
		return FollowStore::followeeIdsFor( $user_id );
	}

	/**
	 * The follower ids of a skrywer (the 9.9 new-work fan-out source).
	 *
	 * @param int $skrywer_id The followed skrywer.
	 * @return list<int>
	 */
	public static function followerIdsFor( int $skrywer_id ): array {
		return FollowStore::followerIdsFor( $skrywer_id );
	}

	/**
	 * The verb-less volgeling-count label (e.g. "12 volgelinge").
	 *
	 * @param int $n The follower count.
	 * @return string
	 */
	public static function volgelingLabel( int $n ): string {
		return FollowCounts::volgelingLabel( $n );
	}

	/**
	 * A writer's pinned (vasgespelde) work ids, in display order (Story 9.5).
	 *
	 * @param int $user_id The writer.
	 * @return list<int>
	 */
	public static function pinnedWorksFor( int $user_id ): array {
		return PinnedWorks::forUser( $user_id );
	}

	/**
	 * The PUBLIC reader-rating aggregate for a writer (approved only, Story 9.6).
	 *
	 * @param int $skrywer_id The rated writer.
	 * @return array{count:int, average:float}
	 */
	public static function ratingAggregateFor( int $skrywer_id ): array {
		return RatingStore::aggregate( $skrywer_id );
	}

	/**
	 * The PUBLIC approved written reviews for a writer (Story 9.6).
	 *
	 * @param int $skrywer_id The rated writer.
	 * @return list<array{user_id:int, score:int, resensie:string}>
	 */
	public static function approvedReviewsFor( int $skrywer_id ): array {
		return RatingStore::approvedReviews( $skrywer_id );
	}

	/**
	 * The verb-less leseroordeel-count label (e.g. "12 leseroordele").
	 *
	 * @param int $n The (approved) review count.
	 * @return string
	 */
	public static function leseroordeelLabel( int $n ): string {
		/* translators: %s: the number of leseroordele (reader reviews). */
		$format = _n( '%s leseroordeel', '%s leseroordele', $n, 'ink-core' );

		return sprintf( $format, number_format_i18n( $n ) );
	}

	/**
	 * Whether a lid has already rated a writer (Story 9.6).
	 *
	 * @param int $user_id    The rater.
	 * @param int $skrywer_id The rated writer.
	 * @return bool
	 */
	public static function hasRated( int $user_id, int $skrywer_id ): bool {
		return RatingStore::hasRated( $user_id, $skrywer_id );
	}
}

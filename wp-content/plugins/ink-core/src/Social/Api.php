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
	 * The verb-less volgeling-count label (e.g. "12 volgelinge").
	 *
	 * @param int $n The follower count.
	 * @return string
	 */
	public static function volgelingLabel( int $n ): string {
		return FollowCounts::volgelingLabel( $n );
	}
}

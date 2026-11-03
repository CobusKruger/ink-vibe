<?php
/**
 * Gemeenskapsreaksie upvote store on the WP-comment substrate (owner-approved 2026-11-02).
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Engagement;

defined( 'ABSPATH' ) || exit;

/**
 * Stores which members upvoted a Gemeenskapsreaksie.
 *
 * Reuses the response substrate (AD-5a, {@see ResponseStore}): a response is a WP
 * comment, and each upvote is one comment-meta row `ink_upvote_user` holding the
 * voter's user id. No table and no column, so old responses start at 0 with no
 * migration. {@see self::add()} refuses a repeat, so a member counts once per
 * response; {@see self::remove()} withdraws it (the toggle). The count is the number
 * of distinct voters.
 *
 * Conflation-clean: engagement is open to any lid, never entitlement-gated.
 *
 * @package Ink\Core
 */
final class ResponseUpvotes {

	/**
	 * The comment-meta key holding one voter's user id per row.
	 */
	public const META_VOTER = 'ink_upvote_user';

	/**
	 * The distinct voter ids of one response.
	 *
	 * @param int $comment_id The response (comment) id.
	 * @return list<int>
	 */
	public static function voters( int $comment_id ): array {
		if ( $comment_id <= 0 ) {
			return array();
		}

		$rows = get_comment_meta( $comment_id, self::META_VOTER, false );

		return self::distinctIds( is_array( $rows ) ? $rows : array() );
	}

	/**
	 * Normalise stored voter rows to distinct positive integer ids. Pure.
	 *
	 * @param array<int|string, mixed> $rows The raw meta values.
	 * @return list<int>
	 */
	public static function distinctIds( array $rows ): array {
		$ids = array();

		foreach ( $rows as $row ) {
			$id = (int) ( is_scalar( $row ) ? $row : 0 );

			if ( $id > 0 ) {
				$ids[ $id ] = $id;
			}
		}

		return array_values( $ids );
	}

	/**
	 * The upvote count of one response.
	 *
	 * @param int $comment_id The response id.
	 * @return int
	 */
	public static function count( int $comment_id ): int {
		return count( self::voters( $comment_id ) );
	}

	/**
	 * Whether a member upvoted a response.
	 *
	 * @param int $comment_id The response id.
	 * @param int $user_id    The member.
	 * @return bool
	 */
	public static function has( int $comment_id, int $user_id ): bool {
		return $user_id > 0 && in_array( $user_id, self::voters( $comment_id ), true );
	}

	/**
	 * Record a member's upvote. A repeat is a no-op, so one member is one vote.
	 *
	 * @param int $comment_id The response id.
	 * @param int $user_id    The member.
	 * @return bool True when a vote was added.
	 */
	public static function add( int $comment_id, int $user_id ): bool {
		if ( $comment_id <= 0 || $user_id <= 0 || self::has( $comment_id, $user_id ) ) {
			return false;
		}

		return false !== add_comment_meta( $comment_id, self::META_VOTER, $user_id, false );
	}

	/**
	 * Withdraw a member's upvote.
	 *
	 * @param int $comment_id The response id.
	 * @param int $user_id    The member.
	 * @return bool True when a vote was removed.
	 */
	public static function remove( int $comment_id, int $user_id ): bool {
		if ( $comment_id <= 0 || $user_id <= 0 ) {
			return false;
		}

		return (bool) delete_comment_meta( $comment_id, self::META_VOTER, $user_id );
	}

	/**
	 * Count and the viewer's own vote for a set of responses.
	 *
	 * @param list<int> $comment_ids The response ids.
	 * @param int       $viewer_id   The viewing member, or 0.
	 * @return array<int, array{count:int, voted:bool}>
	 */
	public static function summaryFor( array $comment_ids, int $viewer_id ): array {
		$summary = array();

		foreach ( $comment_ids as $comment_id ) {
			$voters = self::voters( $comment_id );

			$summary[ $comment_id ] = array(
				'count' => count( $voters ),
				'voted' => $viewer_id > 0 && in_array( $viewer_id, $voters, true ),
			);
		}

		return $summary;
	}
}

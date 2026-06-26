<?php
/**
 * Gemeenskapsreaksie store on the WP-comment substrate — Story 7.4 (FR-27, AD-5a).
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Engagement;

use Ink\Kernel\ResponseType;

defined( 'ABSPATH' ) || exit;

/**
 * Stores structured community responses (Gemeenskapsreaksies) as WP comments.
 *
 * Per AD-5a, a Gemeenskapsreaksie reuses the WordPress comment infrastructure: a
 * single sanctioned custom `comment_type = 'ink_reaksie'` plus comment-meta
 * `ink_response_type ∈ {lof, insig, voorstel}` (the {@see ResponseType} enum) —
 * NOT three comment types, NOT a custom table. Rows are written ONLY
 * programmatically via `wp_insert_comment` (the public comment form stays disabled
 * site-wide by {@see Comments}); this does not re-enable native WP comments.
 *
 * Each response MUST carry a type — {@see self::forPost()} skips any row whose
 * stored type is missing/invalid, so no untyped Gemeenskapsreaksie ever surfaces.
 * The displayed response count is the filtered `ink_reaksie` count
 * ({@see self::countForPost()}), managed independently of WordPress's default
 * `comment_count` (AD-5a guardrail). Flat, not threaded (v1).
 *
 * Conflation-clean: references only the Kernel `ResponseType` + WP comment APIs;
 * zero `Ink\Tiers` / `Ink\Entitlement` (engagement is open to any lid).
 *
 * @package Ink\Core
 */
final class ResponseStore {

	/**
	 * The sanctioned custom comment type (AD-5a). Single source.
	 */
	public const COMMENT_TYPE = 'ink_reaksie';

	/**
	 * The comment-meta key holding the response type enum value.
	 */
	public const META_TYPE = 'ink_response_type';

	/**
	 * Insert a typed Gemeenskapsreaksie for a work.
	 *
	 * @param int          $post_id The work.
	 * @param int          $user_id The responding member.
	 * @param ResponseType $type    The response type (lof / insig / voorstel).
	 * @param string       $content The (already-sanitised) response text.
	 * @return int The new comment id, or 0 on failure.
	 */
	public static function add( int $post_id, int $user_id, ResponseType $type, string $content ): int {
		$user = get_userdata( $user_id );

		$comment_id = wp_insert_comment(
			array(
				'comment_post_ID'      => $post_id,
				'user_id'              => $user_id,
				'comment_author'       => $user instanceof \WP_User ? $user->display_name : '',
				'comment_author_email' => $user instanceof \WP_User ? $user->user_email : '',
				'comment_content'      => $content,
				'comment_type'         => self::COMMENT_TYPE,
				'comment_approved'     => 1,
			)
		);

		if ( ! is_int( $comment_id ) || 0 === $comment_id ) {
			return 0;
		}

		add_comment_meta( $comment_id, self::META_TYPE, $type->value, true );

		return $comment_id;
	}

	/**
	 * The approved Gemeenskapsreaksies for a work, oldest first.
	 *
	 * A row whose stored type is missing or not a valid {@see ResponseType} is
	 * SKIPPED — every surfaced response carries a type (AC #4).
	 *
	 * @param int $post_id The work.
	 * @return list<array{id:int, type:ResponseType, content:string, author:string, date:string}>
	 */
	public static function forPost( int $post_id ): array {
		$comments = get_comments(
			array(
				'post_id' => $post_id,
				'type'    => self::COMMENT_TYPE,
				'status'  => 'approve',
				'order'   => 'ASC',
			)
		);

		$responses = array();

		foreach ( (array) $comments as $comment ) {
			$type = ResponseType::tryFrom( (string) get_comment_meta( (int) $comment->comment_ID, self::META_TYPE, true ) );

			if ( null === $type ) {
				continue; // No untyped Gemeenskapsreaksie surfaces.
			}

			$responses[] = array(
				'id'      => (int) $comment->comment_ID,
				'type'    => $type,
				'content' => (string) $comment->comment_content,
				'author'  => (string) $comment->comment_author,
				'date'    => (string) $comment->comment_date,
			);
		}

		return $responses;
	}

	/**
	 * The filtered Gemeenskapsreaksie count for a work (AD-5a).
	 *
	 * A count of `comment_type='ink_reaksie'` only — NOT WordPress's default
	 * `comment_count`, so the custom-type rows never inflate a displayed comment
	 * total. Counts ONLY rows carrying a valid `ink_response_type`, so the count
	 * matches exactly what {@see self::forPost()} renders (a row without a valid
	 * type never surfaces, and must not be counted either).
	 *
	 * @param int $post_id The work.
	 * @return int
	 */
	public static function countForPost( int $post_id ): int {
		return (int) get_comments(
			array(
				'post_id'    => $post_id,
				'type'       => self::COMMENT_TYPE,
				'status'     => 'approve',
				'count'      => true,
				'meta_query' => array(
					array(
						'key'     => self::META_TYPE,
						'value'   => ResponseType::values(),
						'compare' => 'IN',
					),
				),
			)
		);
	}
}

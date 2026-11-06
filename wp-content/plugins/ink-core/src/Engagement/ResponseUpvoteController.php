<?php
/**
 * Gemeenskapsreaksie upvote REST write path (owner-approved 2026-11-02).
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Engagement;

use Ink\Kernel\ResponseType;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

defined( 'ABSPATH' ) || exit;

/**
 * The `ink/v1/gemeenskapsreaksie/<id>/opstem` REST endpoint: toggles the signed-in
 * member's upvote on one response and returns the new count.
 *
 * Gated like every other engagement write (AD-6 §2): `is_user_logged_in()` plus the
 * REST nonce, which core verifies for cookie auth (a request without a valid
 * `X-WP-Nonce` is treated as logged out and the permission fails). Never
 * entitlement-gated. The target must be an approved, typed `ink_reaksie` on a
 * published work, and a member cannot upvote their own response.
 *
 * Validation is pure ({@see self::validate()}); the callback is thin WP glue.
 *
 * @package Ink\Core
 */
final class ResponseUpvoteController {

	private const NAMESPACE = 'ink/v1';
	private const ROUTE     = '/gemeenskapsreaksie/(?P<id>\d+)/opstem';

	/**
	 * Register the REST route on `rest_api_init`.
	 */
	public function register(): void {
		add_action( 'rest_api_init', array( $this, 'registerRoutes' ) );
	}

	/**
	 * Register `POST ink/v1/gemeenskapsreaksie/<id>/opstem`.
	 */
	public function registerRoutes(): void {
		register_rest_route(
			self::NAMESPACE,
			self::ROUTE,
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'handleToggle' ),
				'permission_callback' => array( $this, 'permission' ),
				'args'                => array(
					'id' => array(
						'type'     => 'integer',
						'required' => true,
					),
				),
			)
		);
	}

	/**
	 * Permission: any logged-in lid (engagement is NOT entitlement-gated, AD-6 §2).
	 *
	 * @return bool
	 */
	public function permission(): bool {
		return is_user_logged_in();
	}

	/**
	 * POST handler: toggle the member's upvote on a response.
	 *
	 * @param WP_REST_Request $request The REST request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function handleToggle( WP_REST_Request $request ) {
		$comment_id = absint( $request->get_param( 'id' ) );
		$user_id    = get_current_user_id();
		$comment    = $comment_id > 0 ? get_comment( $comment_id ) : null;

		$is_response = $comment instanceof \WP_Comment
			&& ResponseStore::COMMENT_TYPE === $comment->comment_type
			&& '1' === (string) $comment->comment_approved
			&& null !== ResponseType::tryFrom( (string) get_comment_meta( $comment_id, ResponseStore::META_TYPE, true ) );

		$post_id = $comment instanceof \WP_Comment ? (int) $comment->comment_post_ID : 0;
		$author  = $comment instanceof \WP_Comment ? (int) $comment->user_id : 0;

		$error = self::validate( $is_response, Readable::isBydrae( $post_id ), $author, $user_id );
		if ( $error instanceof WP_Error ) {
			return $error;
		}

		$voted = ! ResponseUpvotes::has( $comment_id, $user_id );

		if ( $voted ) {
			ResponseUpvotes::add( $comment_id, $user_id );
		} else {
			ResponseUpvotes::remove( $comment_id, $user_id );
		}

		return new WP_REST_Response(
			array(
				'id'    => $comment_id,
				'voted' => $voted,
				'count' => ResponseUpvotes::count( $comment_id ),
			)
		);
	}

	/**
	 * Validate an upvote toggle. Pure: no WordPress state, no DB.
	 *
	 * @param bool $isResponse   Whether the target is an approved, typed response.
	 * @param bool $postReadable Whether its work exists and is published.
	 * @param int  $authorId     The response author's user id.
	 * @param int  $userId       The voting member.
	 * @return WP_Error|null A coded error, or null when valid.
	 */
	public static function validate( bool $isResponse, bool $postReadable, int $authorId, int $userId ): ?WP_Error {
		if ( $userId <= 0 ) {
			return new WP_Error( 'ink_opstem_not_logged_in', self::errorMessage(), array( 'status' => 401 ) );
		}

		if ( ! $isResponse || ! $postReadable ) {
			return new WP_Error( 'ink_opstem_invalid_response', self::errorMessage(), array( 'status' => 404 ) );
		}

		if ( $authorId === $userId ) {
			return new WP_Error( 'ink_opstem_own_response', self::errorMessage(), array( 'status' => 403 ) );
		}

		return null;
	}

	/**
	 * The one error text for a refused upvote. The page never offers a control that
	 * could trigger it (logged-out visitors and the author get a disabled button),
	 * so it only reaches a hand-made request.
	 *
	 * @return string
	 */
	private static function errorMessage(): string {
		return __( 'Jammer, dit het nie gewerk nie', 'ink-core' );
	}
}

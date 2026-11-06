<?php
/**
 * REST: heart / un-heart a work — `ink/v1/hartjie`.
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Engagement;

use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

defined( 'ABSPATH' ) || exit;

/**
 * The reading page's bottom heart: `POST` hearts the work, `DELETE` un-hearts it. Any
 * logged-in member (engagement is not entitlement-gated). One like per member
 * ({@see LikeStore}); the response carries the new state and count so the page can show it.
 *
 * @package Ink\Core
 */
final class LikeController {

	private const NAMESPACE = 'ink/v1';
	private const ROUTE     = '/hartjie';

	/**
	 * Register the routes on `rest_api_init`.
	 */
	public function register(): void {
		add_action( 'rest_api_init', array( $this, 'registerRoutes' ) );
	}

	/**
	 * Register `POST` (heart) and `DELETE` (un-heart).
	 */
	public function registerRoutes(): void {
		$args = array(
			'post_id' => array(
				'type'     => 'integer',
				'required' => true,
			),
		);

		register_rest_route(
			self::NAMESPACE,
			self::ROUTE,
			array(
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'handleLike' ),
					'permission_callback' => array( $this, 'permission' ),
					'args'                => $args,
				),
				array(
					'methods'             => 'DELETE',
					'callback'            => array( $this, 'handleUnlike' ),
					'permission_callback' => array( $this, 'permission' ),
					'args'                => $args,
				),
			)
		);
	}

	/**
	 * Any logged-in member.
	 */
	public function permission(): bool {
		return is_user_logged_in();
	}

	/**
	 * Heart the work (idempotent).
	 *
	 * @param WP_REST_Request $request The request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function handleLike( WP_REST_Request $request ) {
		$post_id = absint( $request->get_param( 'post_id' ) );

		if ( ! Readable::isBydrae( $post_id ) ) {
			return new WP_Error( 'ink_hartjie_invalid_post', 'Hierdie werk is nie beskikbaar vir hartjies nie.', array( 'status' => 400 ) );
		}

		LikeStore::add( $post_id, get_current_user_id() );

		return self::state( $post_id );
	}

	/**
	 * Un-heart the work.
	 *
	 * @param WP_REST_Request $request The request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function handleUnlike( WP_REST_Request $request ) {
		$post_id = absint( $request->get_param( 'post_id' ) );

		if ( ! Readable::isBydrae( $post_id ) ) {
			return new WP_Error( 'ink_hartjie_invalid_post', 'Hierdie werk is nie beskikbaar vir hartjies nie.', array( 'status' => 400 ) );
		}

		LikeStore::remove( $post_id, get_current_user_id() );

		return self::state( $post_id );
	}

	/**
	 * The work's hartjie state for the current member. Shared with {@see ReactionController}
	 * so a line hartjie answers with the same shape.
	 *
	 * @param int $post_id The work.
	 */
	public static function state( int $post_id ): WP_REST_Response {
		return new WP_REST_Response(
			array(
				'liked' => LikeStore::has( $post_id, get_current_user_id() ),
				'count' => LikeStore::count( $post_id ),
			)
		);
	}
}

<?php
/**
 * Follow REST write path — Story 9.2 (FR-38, AD-6).
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Social;

use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

defined( 'ABSPATH' ) || exit;

/**
 * The `ink/v1/volg` REST endpoint — follow / unfollow a skrywer.
 *
 * A logged-in lid follows (POST) or unfollows (DELETE) a skrywer. Per AD-6 §2
 * gated by `is_user_logged_in()` + the REST nonce ONLY — never entitlement- or
 * tier-gated (THE conflation rule: following is open to any lid; a gratis lid
 * may follow). Validation returns an Afrikaans `WP_Error`. The dedup, self-follow
 * guard and idempotency live in {@see FollowStore}; this is the thin write
 * surface.
 *
 * @package Ink\Core
 */
final class FollowController {

	private const NAMESPACE = 'ink/v1';
	private const ROUTE     = '/volg';

	/**
	 * Register the REST routes on `rest_api_init`.
	 */
	public function register(): void {
		add_action( 'rest_api_init', array( $this, 'registerRoutes' ) );
	}

	/**
	 * Register `POST` (follow) + `DELETE` (unfollow) on `ink/v1/volg`.
	 */
	public function registerRoutes(): void {
		$args = array(
			'followee_id' => array(
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
					'callback'            => array( $this, 'handleFollow' ),
					'permission_callback' => array( $this, 'permission' ),
					'args'                => $args,
				),
				array(
					'methods'             => 'DELETE',
					'callback'            => array( $this, 'handleUnfollow' ),
					'permission_callback' => array( $this, 'permission' ),
					'args'                => $args,
				),
			)
		);
	}

	/**
	 * Permission: any logged-in lid (following is NOT entitlement-gated, AD-6 §2).
	 *
	 * @return bool
	 */
	public function permission(): bool {
		return is_user_logged_in();
	}

	/**
	 * POST: follow a skrywer.
	 *
	 * @param WP_REST_Request $request The REST request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function handleFollow( WP_REST_Request $request ) {
		$followee_id = absint( $request->get_param( 'followee_id' ) );
		$user_id     = get_current_user_id();

		$error = self::validate(
			false !== get_userdata( $followee_id ),
			$followee_id === $user_id
		);
		if ( $error instanceof WP_Error ) {
			return $error;
		}

		FollowStore::follow( $user_id, $followee_id );

		return new WP_REST_Response(
			array(
				'followee_id' => $followee_id,
				'following'   => true,
			)
		);
	}

	/**
	 * DELETE: stop following a skrywer.
	 *
	 * @param WP_REST_Request $request The REST request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function handleUnfollow( WP_REST_Request $request ) {
		$followee_id = absint( $request->get_param( 'followee_id' ) );
		$user_id     = get_current_user_id();

		$error = self::validate(
			false !== get_userdata( $followee_id ),
			$followee_id === $user_id
		);
		if ( $error instanceof WP_Error ) {
			return $error;
		}

		FollowStore::unfollow( $user_id, $followee_id );

		return new WP_REST_Response(
			array(
				'followee_id' => $followee_id,
				'following'   => false,
			)
		);
	}

	/**
	 * Validate a follow write. Pure — no WordPress state, no DB.
	 *
	 * @param bool $targetIsUser Whether the followee is a real user.
	 * @param bool $isSelf       Whether the followee is the current user.
	 * @return WP_Error|null
	 */
	public static function validate( bool $targetIsUser, bool $isSelf ): ?WP_Error {
		if ( $isSelf ) {
			return new WP_Error( 'ink_volg_self', 'Jy kan nie jouself volg nie.' );
		}

		if ( ! $targetIsUser ) {
			return new WP_Error( 'ink_volg_invalid_target', 'Hierdie skrywer is nie beskikbaar nie.' );
		}

		return null;
	}
}

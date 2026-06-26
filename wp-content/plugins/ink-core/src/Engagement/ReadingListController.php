<?php
/**
 * Leeslys REST write path — Story 7.7 (FR-29, AD-6).
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
 * The `ink/v1/leeslys` REST endpoint — save / remove a work from the leeslys.
 *
 * A logged-in lid saves (POST) or removes (DELETE) a work. Per AD-6 §2 gated by
 * `is_user_logged_in()` + the REST nonce ONLY — never entitlement-gated
 * (conflation-clean). Validation returns an Afrikaans `WP_Error`. The dedup +
 * idempotency live in {@see ReadingListStore}; this is the thin write surface.
 *
 * @package Ink\Core
 */
final class ReadingListController {

	private const NAMESPACE = 'ink/v1';
	private const ROUTE     = '/leeslys';

	/**
	 * Register the REST routes on `rest_api_init`.
	 */
	public function register(): void {
		add_action( 'rest_api_init', array( $this, 'registerRoutes' ) );
	}

	/**
	 * Register `POST` (save) + `DELETE` (remove) on `ink/v1/leeslys`.
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
					'callback'            => array( $this, 'handleSave' ),
					'permission_callback' => array( $this, 'permission' ),
					'args'                => $args,
				),
				array(
					'methods'             => 'DELETE',
					'callback'            => array( $this, 'handleRemove' ),
					'permission_callback' => array( $this, 'permission' ),
					'args'                => $args,
				),
			)
		);
	}

	/**
	 * Permission: any logged-in lid (the leeslys is NOT entitlement-gated, AD-6 §2).
	 *
	 * @return bool
	 */
	public function permission(): bool {
		return is_user_logged_in();
	}

	/**
	 * POST: save a work to the member's leeslys.
	 *
	 * @param WP_REST_Request $request The REST request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function handleSave( WP_REST_Request $request ) {
		$post_id = absint( $request->get_param( 'post_id' ) );

		$error = self::validate( self::isReadable( $post_id ) );
		if ( $error instanceof WP_Error ) {
			return $error;
		}

		ReadingListStore::add( get_current_user_id(), $post_id );

		return new WP_REST_Response(
			array(
				'post_id' => $post_id,
				'saved'   => true,
			)
		);
	}

	/**
	 * DELETE: remove a work from the member's leeslys.
	 *
	 * @param WP_REST_Request $request The REST request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function handleRemove( WP_REST_Request $request ) {
		$post_id = absint( $request->get_param( 'post_id' ) );

		$error = self::validate( self::isReadable( $post_id ) );
		if ( $error instanceof WP_Error ) {
			return $error;
		}

		ReadingListStore::remove( get_current_user_id(), $post_id );

		return new WP_REST_Response(
			array(
				'post_id' => $post_id,
				'saved'   => false,
			)
		);
	}

	/**
	 * Validate a leeslys write. Pure — no WordPress state, no DB.
	 *
	 * @param bool $postReadable Whether the target post exists and is published.
	 * @return WP_Error|null
	 */
	public static function validate( bool $postReadable ): ?WP_Error {
		if ( ! $postReadable ) {
			return new WP_Error( 'ink_leeslys_invalid_post', 'Hierdie werk is nie beskikbaar nie.' );
		}

		return null;
	}

	/**
	 * Whether a post exists and is published (a readable work).
	 *
	 * @param int $post_id The post.
	 * @return bool
	 */
	private static function isReadable( int $post_id ): bool {
		return $post_id > 0 && 'publish' === get_post_status( $post_id );
	}
}

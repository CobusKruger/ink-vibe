<?php
/**
 * Pinned-works REST write path — Story 9.5 (FR-41, AD-6).
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Social;

use Ink\Content\PostTypes;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

defined( 'ABSPATH' ) || exit;

/**
 * The `ink/v1/vasgespel` REST endpoint — pin / unpin one of your OWN works.
 *
 * A logged-in skrywer pins (POST) or unpins (DELETE) a work on their profile.
 * Per AD-6 §2 gated by `is_user_logged_in()` + the REST nonce; the authorisation
 * is OWNERSHIP — a writer may only pin their own published readable bydrae (not
 * another author's work, not a draft/page/attachment, not the `skryfwerk`
 * bucket). Never entitlement- or tier-gated. The cap/dedup/order live in
 * {@see PinnedWorks}; this is the thin write surface.
 *
 * @package Ink\Core
 */
final class PinnedWorksController {

	private const NAMESPACE = 'ink/v1';
	private const ROUTE     = '/vasgespel';

	/**
	 * Register the REST routes on `rest_api_init`.
	 */
	public function register(): void {
		add_action( 'rest_api_init', array( $this, 'registerRoutes' ) );
	}

	/**
	 * Register `POST` (pin) + `DELETE` (unpin) on `ink/v1/vasgespel`.
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
					'callback'            => array( $this, 'handlePin' ),
					'permission_callback' => array( $this, 'permission' ),
					'args'                => $args,
				),
				array(
					'methods'             => 'DELETE',
					'callback'            => array( $this, 'handleUnpin' ),
					'permission_callback' => array( $this, 'permission' ),
					'args'                => $args,
				),
			)
		);
	}

	/**
	 * Permission: any logged-in lid (ownership is enforced in the handler).
	 *
	 * @return bool
	 */
	public function permission(): bool {
		return is_user_logged_in();
	}

	/**
	 * POST: pin one of the writer's own published works.
	 *
	 * @param WP_REST_Request $request The REST request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function handlePin( WP_REST_Request $request ) {
		$post_id = absint( $request->get_param( 'post_id' ) );
		$user_id = get_current_user_id();

		$error = self::validate( self::isOwnReadableBydrae( $post_id, $user_id ) );
		if ( $error instanceof WP_Error ) {
			return $error;
		}

		PinnedWorks::pin( $user_id, $post_id );

		return new WP_REST_Response(
			array(
				'post_id' => $post_id,
				'pinned'  => true,
			)
		);
	}

	/**
	 * DELETE: unpin one of the writer's own works.
	 *
	 * @param WP_REST_Request $request The REST request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function handleUnpin( WP_REST_Request $request ) {
		$post_id = absint( $request->get_param( 'post_id' ) );
		$user_id = get_current_user_id();

		$error = self::validate( self::isOwnReadableBydrae( $post_id, $user_id ) );
		if ( $error instanceof WP_Error ) {
			return $error;
		}

		PinnedWorks::unpin( $user_id, $post_id );

		return new WP_REST_Response(
			array(
				'post_id' => $post_id,
				'pinned'  => false,
			)
		);
	}

	/**
	 * Whether the post is the user's OWN published readable bydrae.
	 *
	 * The own-work authorisation: author match + published + a readable type
	 * (gedig/storie/artikel — the `skryfwerk` bucket and non-bydrae are excluded).
	 *
	 * @param int $post_id The work.
	 * @param int $user_id The writer.
	 * @return bool
	 */
	private static function isOwnReadableBydrae( int $post_id, int $user_id ): bool {
		if ( $post_id <= 0 || $user_id <= 0 ) {
			return false;
		}

		return (int) get_post_field( 'post_author', $post_id ) === $user_id
			&& 'publish' === get_post_status( $post_id )
			&& in_array( get_post_type( $post_id ), PostTypes::readableTypes(), true );
	}

	/**
	 * Validate a pin write. Pure — no WordPress state, no DB.
	 *
	 * @param bool $isOwnReadableBydrae Whether the target is the writer's own published bydrae.
	 * @return WP_Error|null
	 */
	public static function validate( bool $isOwnReadableBydrae ): ?WP_Error {
		if ( ! $isOwnReadableBydrae ) {
			return new WP_Error( 'ink_vasgespel_invalid', 'Jy kan net jou eie gepubliseerde werk vasspeld.' );
		}

		return null;
	}
}

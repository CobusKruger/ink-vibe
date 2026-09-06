<?php
/**
 * Kennisgewings REST read + mark-all-read path — My Profiel rebuild §5.8.
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Notifications;

use WP_REST_Request;
use WP_REST_Response;

defined( 'ABSPATH' ) || exit;

/**
 * The `ink/v1/kennisgewings` REST endpoint — list the current user's
 * kennisgewings (GET) and "merk alles as gelees" (POST).
 *
 * Mirrors {@see \Ink\Social\FollowController}/{@see \Ink\Social\PinnedWorksController}'s
 * exact shape: a `final class`, `NAMESPACE`/`ROUTE` constants, `register()`
 * hooked to `rest_api_init`, a single `permission()` gate. Unlike those two
 * (which authorise a WRITE against a target id — a followee, a work — and so
 * need a `validate()`), both handlers here act ONLY on
 * `get_current_user_id()`: there is no target to own or validate, so per AD-6
 * §2 `is_user_logged_in()` alone is the complete gate — seeing and clearing
 * your OWN kennisgewings is open to any lid, never entitlement- or tier-gated.
 *
 * The GET list honours an optional `limit` request param so the Kennisgewings
 * tab (wants everything: omit `limit`) and the Oorsig "Onlangse aktiwiteit"
 * card (wants {@see KennisgewingsSurface::RECENT_LIMIT}) can share this ONE
 * route rather than each growing its own endpoint (My Profiel rebuild
 * strategy §5.8 point 3).
 *
 * @package Ink\Core
 */
final class KennisgewingsController {

	private const NAMESPACE = 'ink/v1';
	private const ROUTE     = '/kennisgewings';

	/**
	 * Register the REST routes on `rest_api_init`.
	 */
	public function register(): void {
		add_action( 'rest_api_init', array( $this, 'registerRoutes' ) );
	}

	/**
	 * Register `GET` (list) + `POST` (mark all read) on `ink/v1/kennisgewings`.
	 */
	public function registerRoutes(): void {
		register_rest_route(
			self::NAMESPACE,
			self::ROUTE,
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'handleList' ),
					'permission_callback' => array( $this, 'permission' ),
					'args'                => array(
						'limit' => array(
							'type'     => 'integer',
							'required' => false,
						),
					),
				),
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'handleMarkAllRead' ),
					'permission_callback' => array( $this, 'permission' ),
				),
			)
		);
	}

	/**
	 * Permission: any logged-in lid. Both handlers only ever act on
	 * `get_current_user_id()`, so there is no per-request ownership check to
	 * add beyond being logged in.
	 *
	 * @return bool
	 */
	public function permission(): bool {
		return is_user_logged_in();
	}

	/**
	 * GET: the current user's kennisgewings, newest-first, plus the shared
	 * unread count (§5.8 point 3 — computed once, not once per surface).
	 *
	 * @param WP_REST_Request $request The REST request.
	 * @return WP_REST_Response
	 */
	public function handleList( WP_REST_Request $request ) {
		$limit   = absint( $request->get_param( 'limit' ) );
		$user_id = get_current_user_id();

		$items = $limit > 0
			? KennisgewingsSurface::recent( $user_id, $limit )
			: KennisgewingsSurface::rows( $user_id );

		return new WP_REST_Response(
			array(
				'items'        => $items,
				'unread_count' => KennisgewingsSurface::unreadCount( $user_id ),
			)
		);
	}

	/**
	 * POST: "merk alles as gelees" — moves the read boundary to now
	 * ({@see Kennisgewings::markAllRead()} via the {@see Api} facade).
	 *
	 * @param WP_REST_Request $request The REST request (unused — no body params).
	 * @return WP_REST_Response
	 */
	public function handleMarkAllRead( WP_REST_Request $request ) {
		unset( $request );

		Api::markAllRead( get_current_user_id() );

		return new WP_REST_Response( array( 'marked_all_read' => true ) );
	}
}

<?php
/**
 * REST routes for a member's saved text highlights.
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Engagement;

use Ink\Content\PostTypes;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

defined( 'ABSPATH' ) || exit;

/**
 * `POST ink/v1/hooglig` saves a highlight, `DELETE ink/v1/hooglig/<id>` removes one.
 * Signed-in members only; a member can only ever touch their own rows (the REST
 * nonce is verified by core for cookie auth).
 *
 * @package Ink\Core
 */
final class HighlightController {

	/**
	 * The REST namespace.
	 */
	public const NAMESPACE = 'ink/v1';

	/**
	 * The route base.
	 */
	public const ROUTE = '/hooglig';

	/**
	 * Register the hooks.
	 */
	public function register(): void {
		add_action( 'rest_api_init', array( $this, 'registerRoutes' ) );
	}

	/**
	 * Register the routes.
	 */
	public function registerRoutes(): void {
		register_rest_route(
			self::NAMESPACE,
			self::ROUTE,
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'handleAdd' ),
				'permission_callback' => array( $this, 'permission' ),
				'args'                => array(
					'post_id' => array(
						'type'     => 'integer',
						'required' => true,
					),
					'para'    => array(
						'type'     => 'integer',
						'required' => true,
					),
					'start'   => array(
						'type'     => 'integer',
						'required' => true,
					),
					'end'     => array(
						'type'     => 'integer',
						'required' => true,
					),
					'quote'   => array(
						'type'     => 'string',
						'required' => true,
					),
				),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			self::ROUTE . '/(?P<id>\d+)',
			array(
				'methods'             => 'DELETE',
				'callback'            => array( $this, 'handleRemove' ),
				'permission_callback' => array( $this, 'permission' ),
			)
		);
	}

	/**
	 * Any signed-in member.
	 */
	public function permission(): bool {
		return is_user_logged_in();
	}

	/**
	 * Whether a post type carries text highlights (stories and articles; poems have line hearts).
	 *
	 * @param string $post_type The post type.
	 */
	public static function isHighlightable( string $post_type ): bool {
		return in_array( $post_type, array( PostTypes::STORIE, PostTypes::ARTIKEL ), true );
	}

	/**
	 * Save a highlight.
	 *
	 * @param WP_REST_Request $request The request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function handleAdd( WP_REST_Request $request ) {
		$post_id = absint( $request->get_param( 'post_id' ) );

		if ( 'publish' !== get_post_status( $post_id ) || ! self::isHighlightable( (string) get_post_type( $post_id ) ) ) {
			return new WP_Error( 'ink_hooglig_ongeldig', __( 'Hierdie stuk kan nie uitgelig word nie.', 'ink-core' ), array( 'status' => 400 ) );
		}

		$h = HighlightStore::normalise(
			(int) $request->get_param( 'para' ),
			(int) $request->get_param( 'start' ),
			(int) $request->get_param( 'end' ),
			sanitize_textarea_field( (string) $request->get_param( 'quote' ) )
		);

		if ( null === $h ) {
			return new WP_Error( 'ink_hooglig_ongeldig', __( 'Ongeldige keuse.', 'ink-core' ), array( 'status' => 400 ) );
		}

		$id = HighlightStore::add( get_current_user_id(), $post_id, $h );

		if ( $id <= 0 ) {
			return new WP_Error( 'ink_hooglig_vol', __( 'Jy het te veel gedeeltes in hierdie stuk uitgelig.', 'ink-core' ), array( 'status' => 409 ) );
		}

		return new WP_REST_Response( array( 'id' => $id ), 201 );
	}

	/**
	 * Remove one of the member's own highlights.
	 *
	 * @param WP_REST_Request $request The request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function handleRemove( WP_REST_Request $request ) {
		$removed = HighlightStore::remove( get_current_user_id(), absint( $request->get_param( 'id' ) ) );

		if ( ! $removed ) {
			return new WP_Error( 'ink_hooglig_nie_gevind', __( 'Hooglig nie gevind nie.', 'ink-core' ), array( 'status' => 404 ) );
		}

		return new WP_REST_Response( array( 'removed' => true ), 200 );
	}
}

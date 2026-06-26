<?php
/**
 * Gemeenskapsreaksie REST write path — Story 7.4 (FR-27, AD-6).
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
 * The `ink/v1/gemeenskapsreaksie` REST endpoint — the ONLY feedback path.
 *
 * A logged-in lid posts a typed Gemeenskapsreaksie (lof / insig / voorstel) on a
 * work. Per AD-6 §2 the write is gated by `is_user_logged_in()` + the REST nonce
 * ONLY — never entitlement-gated (conflation-clean). Each response MUST carry a
 * type: an unknown/missing type is rejected, so no untyped Gemeenskapsreaksie can
 * be created. Validation returns `WP_Error` with `ink_gemeenskapsreaksie_*`-coded
 * Afrikaans messages.
 *
 * Validation is pure ({@see self::validate()}); the callback is thin WP glue.
 *
 * @package Ink\Core
 */
final class ResponseController {

	private const NAMESPACE = 'ink/v1';
	private const ROUTE     = '/gemeenskapsreaksie';

	/**
	 * Register the REST route on `rest_api_init`.
	 */
	public function register(): void {
		add_action( 'rest_api_init', array( $this, 'registerRoutes' ) );
	}

	/**
	 * Register `POST ink/v1/gemeenskapsreaksie`.
	 */
	public function registerRoutes(): void {
		register_rest_route(
			self::NAMESPACE,
			self::ROUTE,
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'handlePost' ),
				'permission_callback' => array( $this, 'permission' ),
				'args'                => array(
					'post_id' => array(
						'type'     => 'integer',
						'required' => true,
					),
					'type'    => array(
						'type'     => 'string',
						'required' => true,
						'enum'     => ResponseType::values(),
					),
					'content' => array(
						'type'     => 'string',
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
	 * POST handler: create a typed Gemeenskapsreaksie.
	 *
	 * @param WP_REST_Request $request The REST request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function handlePost( WP_REST_Request $request ) {
		$post_id = absint( $request->get_param( 'post_id' ) );
		$type    = sanitize_key( (string) $request->get_param( 'type' ) );
		$content = sanitize_textarea_field( wp_unslash( (string) $request->get_param( 'content' ) ) );

		$error = self::validate( $type, $content, self::isReadable( $post_id ) );
		if ( $error instanceof WP_Error ) {
			return $error;
		}

		$id = ResponseStore::add( $post_id, get_current_user_id(), ResponseType::from( $type ), $content );

		return new WP_REST_Response(
			array(
				'id'   => $id,
				'type' => $type,
			)
		);
	}

	/**
	 * Validate a Gemeenskapsreaksie write. Pure — no WordPress state, no DB.
	 *
	 * @param string $typeRaw      The submitted type value (already sanitised).
	 * @param string $content      The submitted, sanitised response text.
	 * @param bool   $postReadable Whether the target post exists and is published.
	 * @return WP_Error|null A coded Afrikaans error, or null when valid.
	 */
	public static function validate( string $typeRaw, string $content, bool $postReadable ): ?WP_Error {
		if ( ! $postReadable ) {
			return new WP_Error( 'ink_gemeenskapsreaksie_invalid_post', 'Hierdie werk is nie beskikbaar vir reaksies nie.' );
		}

		if ( null === ResponseType::tryFrom( $typeRaw ) ) {
			return new WP_Error( 'ink_gemeenskapsreaksie_invalid_type', 'Kies \'n geldige tipe: lof, insig of voorstel.' );
		}

		if ( '' === trim( $content ) ) {
			return new WP_Error( 'ink_gemeenskapsreaksie_empty', 'Skryf asseblief \'n reaksie.' );
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

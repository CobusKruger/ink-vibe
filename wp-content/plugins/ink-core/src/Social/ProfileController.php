<?php
/**
 * My Profiel identity-strip edit REST write path — My Profiel rebuild (§5.1).
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
 * The `ink/v1/profiel` REST endpoint — the real inline edit-profile modal's
 * persistence layer (name / tagline / bio, each independently optional).
 *
 * Unlike Lovable's demo-only "Edit profile" modal, this is a real write path.
 * Per AD-6 §2 gated by `is_user_logged_in()` + the REST nonce. There is
 * deliberately NO target-user parameter at all — the route always writes to
 * `get_current_user_id()` — so "you can only edit your own profile" is a
 * structural guarantee (no request shape can even express editing someone
 * else's profile), not a runtime equality check that could be forgotten.
 *
 * Writes, each fired only when its param is present in the request (partial
 * updates — a caller may send just one field):
 * - `display_name` → `wp_update_user()` (core WP field).
 * - `tagline`       → {@see Tagline::set()} (the new My Profiel identity-strip
 *   meta, `ink_skrywer_leuse` — distinct from the bio).
 * - `bio`           → the `description` user-meta (the existing "Oor my" bio
 *   source read by {@see SkrywerProfiel::render()} / {@see FollowingList::render()}
 *   via `get_the_author_meta( 'description', … )`).
 *
 * @package Ink\Core
 */
final class ProfileController {

	private const NAMESPACE = 'ink/v1';
	private const ROUTE     = '/profiel';

	/**
	 * Register the REST route on `rest_api_init`.
	 */
	public function register(): void {
		add_action( 'rest_api_init', array( $this, 'registerRoutes' ) );
	}

	/**
	 * Register `POST` on `ink/v1/profiel`.
	 */
	public function registerRoutes(): void {
		register_rest_route(
			self::NAMESPACE,
			self::ROUTE,
			array(
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'handleUpdate' ),
					'permission_callback' => array( $this, 'permission' ),
					'args'                => array(
						'display_name' => array(
							'type'     => 'string',
							'required' => false,
						),
						'tagline'      => array(
							'type'     => 'string',
							'required' => false,
						),
						'bio'          => array(
							'type'     => 'string',
							'required' => false,
						),
					),
				),
			)
		);
	}

	/**
	 * Permission: any logged-in lid. Ownership needs no separate check here —
	 * the handler never reads a target-user param, it only ever writes to
	 * `get_current_user_id()`.
	 *
	 * @return bool
	 */
	public function permission(): bool {
		return is_user_logged_in();
	}

	/**
	 * POST: update the current user's own display name / tagline / bio.
	 *
	 * Each field is written only when the request actually carries it — a
	 * caller may submit just the field(s) the modal's form changed.
	 *
	 * @param WP_REST_Request $request The REST request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function handleUpdate( WP_REST_Request $request ) {
		$user_id = get_current_user_id();

		$has_name    = null !== $request->get_param( 'display_name' );
		$has_tagline = null !== $request->get_param( 'tagline' );
		$has_bio     = null !== $request->get_param( 'bio' );

		$error = self::validate( $has_name || $has_tagline || $has_bio );
		if ( $error instanceof WP_Error ) {
			return $error;
		}

		$updated = array();

		if ( $has_name ) {
			$name = sanitize_text_field( wp_unslash( (string) $request->get_param( 'display_name' ) ) );

			wp_update_user(
				array(
					'ID'           => $user_id,
					'display_name' => $name,
				)
			);

			$updated['display_name'] = $name;
		}

		if ( $has_tagline ) {
			$tagline = sanitize_text_field( wp_unslash( (string) $request->get_param( 'tagline' ) ) );

			Tagline::set( $user_id, $tagline );

			$updated['tagline'] = Tagline::sanitize( $tagline );
		}

		if ( $has_bio ) {
			// `sanitize_textarea_field` mirrors the codebase's existing convention
			// for free-text longer than a single line (RatingController's
			// `resensie`, ContactForm/ReportForm's message fields) — never
			// `wp_kses_post`, which no free-text save in this codebase uses.
			$bio = sanitize_textarea_field( wp_unslash( (string) $request->get_param( 'bio' ) ) );

			update_user_meta( $user_id, 'description', $bio );

			$updated['bio'] = $bio;
		}

		return new WP_REST_Response( $updated );
	}

	/**
	 * Validate a profile-edit submission. Pure — no WordPress state, no DB.
	 *
	 * @param bool $hasAnyField Whether at least one editable field was submitted.
	 * @return WP_Error|null
	 */
	public static function validate( bool $hasAnyField ): ?WP_Error {
		if ( ! $hasAnyField ) {
			return new WP_Error( 'ink_profiel_leeg', 'Niks is ingedien om te stoor nie.' );
		}

		return null;
	}
}

<?php
/**
 * Reader-rating REST write path — Story 9.6 (FR-42, AD-6).
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
 * The `ink/v1/leseroordeel` REST endpoint — submit/update a rating of a skrywer.
 *
 * A logged-in lid rates (1–5) + optionally reviews a writer. Per AD-6 §2 gated
 * by `is_user_logged_in()` + the REST nonce ONLY — never entitlement- or
 * tier-gated (rating is open to any lid). The submission is stored `hangend`
 * (held for moderation, Story 18.4); it is never auto-public. Validation returns
 * an Afrikaans `WP_Error`.
 *
 * @package Ink\Core
 */
final class RatingController {

	private const NAMESPACE = 'ink/v1';
	private const ROUTE     = '/leseroordeel';

	private const MIN_SCORE = 1;
	private const MAX_SCORE = 5;

	/**
	 * Register the REST route on `rest_api_init`.
	 */
	public function register(): void {
		add_action( 'rest_api_init', array( $this, 'registerRoutes' ) );
	}

	/**
	 * Register `POST` on `ink/v1/leseroordeel`.
	 */
	public function registerRoutes(): void {
		register_rest_route(
			self::NAMESPACE,
			self::ROUTE,
			array(
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'handle' ),
					'permission_callback' => array( $this, 'permission' ),
					'args'                => array(
						'skrywer_id' => array(
							'type'     => 'integer',
							'required' => true,
						),
						'score'      => array(
							'type'     => 'integer',
							'required' => true,
						),
						'resensie'   => array(
							'type'     => 'string',
							'required' => false,
						),
					),
				),
			)
		);
	}

	/**
	 * Permission: any logged-in lid (rating is NOT entitlement-gated, AD-6 §2).
	 *
	 * @return bool
	 */
	public function permission(): bool {
		return is_user_logged_in();
	}

	/**
	 * POST: submit/update a held rating of a writer.
	 *
	 * @param WP_REST_Request $request The REST request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function handle( WP_REST_Request $request ) {
		$skrywer_id = absint( $request->get_param( 'skrywer_id' ) );
		$score      = (int) $request->get_param( 'score' );
		$review     = sanitize_textarea_field( (string) $request->get_param( 'resensie' ) );
		$user_id    = get_current_user_id();

		$error = self::validate(
			false !== get_userdata( $skrywer_id ),
			$skrywer_id === $user_id,
			$score
		);
		if ( $error instanceof WP_Error ) {
			return $error;
		}

		RatingStore::rate( $user_id, $skrywer_id, $score, $review );

		return new WP_REST_Response(
			array(
				'skrywer_id' => $skrywer_id,
				'status'     => RatingStatus::Hangend->value,
			)
		);
	}

	/**
	 * Validate a rating submission. Pure — no WordPress state, no DB.
	 *
	 * @param bool $targetIsUser Whether the writer is a real user.
	 * @param bool $isSelf       Whether the writer is the current user.
	 * @param int  $score        The submitted score.
	 * @return WP_Error|null
	 */
	public static function validate( bool $targetIsUser, bool $isSelf, int $score ): ?WP_Error {
		if ( $isSelf ) {
			return new WP_Error( 'ink_oordeel_self', 'Jy kan nie jouself beoordeel nie.' );
		}

		if ( ! $targetIsUser ) {
			return new WP_Error( 'ink_oordeel_invalid_target', 'Hierdie skrywer is nie beskikbaar nie.' );
		}

		if ( $score < self::MIN_SCORE || $score > self::MAX_SCORE ) {
			return new WP_Error( 'ink_oordeel_score', 'Kies asseblief \'n gradering van 1 tot 5.' );
		}

		return null;
	}
}

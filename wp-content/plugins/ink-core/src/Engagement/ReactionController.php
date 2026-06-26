<?php
/**
 * Line-reaction REST write path — Story 7.3 (FR-26, AD-6).
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Engagement;

use Ink\Kernel\Reaction;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

defined( 'ABSPATH' ) || exit;

/**
 * The `ink/v1/reaksie` REST endpoint — the FIRST `ink/v1` write path (AD-6 §1).
 *
 * A logged-in lid attaches / changes / removes a reaksie on one content line of a
 * work. Per AD-6 §2 engagement is gated by `is_user_logged_in()` + the REST nonce
 * ONLY — never entitlement-gated (conflation-clean: no `Ink\Tiers`/`Ink\Entitlement`
 * reference). Validation returns `WP_Error` with `ink_reaksie_*`-coded Afrikaans
 * messages (NFR-1 — error strings are a leak vector).
 *
 * Reads stay server-rendered (the gedig block); this is writes only. Reactions are
 * encouragement, not commentary — the payload carries NO free-form text (Story 7.4
 * owns structured feedback). The submitted line is validated against the post's
 * stored body via {@see GedigBody::tokenize()} so a reaction can only ever land on
 * a real CONTENT line, never a blank separator (the 7.2 contract enforced at the
 * write layer, not just in the display).
 *
 * The validation + toggle decision are pure ({@see self::validate()},
 * {@see self::decideRemoval()}); the callbacks are thin WP glue over them.
 *
 * @package Ink\Core
 */
final class ReactionController {

	private const NAMESPACE = 'ink/v1';
	private const ROUTE     = '/reaksie';

	/**
	 * Register the REST routes on `rest_api_init`.
	 */
	public function register(): void {
		add_action( 'rest_api_init', array( $this, 'registerRoutes' ) );
	}

	/**
	 * Register `POST` (set / toggle) and `DELETE` (remove) on `ink/v1/reaksie`.
	 */
	public function registerRoutes(): void {
		register_rest_route(
			self::NAMESPACE,
			self::ROUTE,
			array(
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'handleSet' ),
					'permission_callback' => array( $this, 'permission' ),
					'args'                => array(
						'post_id'  => array(
							'type'     => 'integer',
							'required' => true,
						),
						'line'     => array(
							'type'     => 'integer',
							'required' => true,
						),
						'reaction' => array(
							'type'     => 'string',
							'required' => true,
							'enum'     => Reaction::values(),
						),
					),
				),
				array(
					'methods'             => 'DELETE',
					'callback'            => array( $this, 'handleRemove' ),
					'permission_callback' => array( $this, 'permission' ),
					'args'                => array(
						'post_id' => array(
							'type'     => 'integer',
							'required' => true,
						),
						'line'    => array(
							'type'     => 'integer',
							'required' => true,
						),
					),
				),
			)
		);
	}

	/**
	 * Permission: any logged-in lid (engagement is NOT entitlement-gated, AD-6 §2).
	 * The REST nonce (`X-WP-Nonce`) is verified by core for cookie auth.
	 *
	 * @return bool
	 */
	public function permission(): bool {
		return is_user_logged_in();
	}

	/**
	 * POST handler: set, change, or toggle-off a reaction on a content line.
	 *
	 * @param WP_REST_Request $request The REST request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function handleSet( WP_REST_Request $request ) {
		$post_id  = absint( $request->get_param( 'post_id' ) );
		$line     = absint( $request->get_param( 'line' ) );
		$reaction = sanitize_key( (string) $request->get_param( 'reaction' ) );
		$user_id  = get_current_user_id();

		$error = self::validate( $line, $reaction, Readable::isBydrae( $post_id ), self::bodyOf( $post_id ) );
		if ( $error instanceof WP_Error ) {
			return $error;
		}

		$requested = Reaction::from( $reaction );
		$current   = ReactionStore::userReaction( $post_id, $line, $user_id );

		if ( self::decideRemoval( $current, $requested ) ) {
			ReactionStore::remove( $post_id, $line, $user_id );

			return new WP_REST_Response(
				array(
					'line'     => $line,
					'reaction' => null,
					'removed'  => true,
				)
			);
		}

		ReactionStore::set( $post_id, $line, $user_id, $requested );

		return new WP_REST_Response(
			array(
				'line'     => $line,
				'reaction' => $requested->value,
				'removed'  => false,
			)
		);
	}

	/**
	 * DELETE handler: remove the member's reaction on a content line.
	 *
	 * @param WP_REST_Request $request The REST request.
	 * @return WP_REST_Response
	 */
	public function handleRemove( WP_REST_Request $request ): WP_REST_Response {
		$post_id = absint( $request->get_param( 'post_id' ) );
		$line    = absint( $request->get_param( 'line' ) );

		ReactionStore::remove( $post_id, $line, get_current_user_id() );

		return new WP_REST_Response(
			array(
				'line'     => $line,
				'reaction' => null,
				'removed'  => true,
			)
		);
	}

	/**
	 * Validate a reaction write. Pure — no WordPress state, no DB.
	 *
	 * @param int    $line         The submitted line index.
	 * @param string $reactionRaw  The submitted reaction value (already sanitised).
	 * @param bool   $postReadable Whether the target post exists and is published.
	 * @param string $postContent  The target post's raw stored body.
	 * @return WP_Error|null A coded Afrikaans error, or null when valid.
	 */
	public static function validate( int $line, string $reactionRaw, bool $postReadable, string $postContent ): ?WP_Error {
		if ( ! $postReadable ) {
			return new WP_Error( 'ink_reaksie_invalid_post', 'Hierdie werk is nie beskikbaar vir reaksies nie.' );
		}

		if ( null === Reaction::tryFrom( $reactionRaw ) ) {
			return new WP_Error( 'ink_reaksie_invalid_reaction', 'Onbekende reaksie.' );
		}

		if ( ! self::isContentLine( $line, $postContent ) ) {
			return new WP_Error( 'ink_reaksie_invalid_line', 'Mens kan net op \'n inhoudsreël reageer, nie op \'n leë reël nie.' );
		}

		return null;
	}

	/**
	 * Whether re-selecting `$requested` should toggle the existing reaction off.
	 * Pure: removal happens only when the member's current reaction equals the
	 * requested one; otherwise it is a set/change.
	 *
	 * @param Reaction|null $current   The member's current reaction (or null).
	 * @param Reaction      $requested The requested reaction.
	 * @return bool True → remove; false → set/change.
	 */
	public static function decideRemoval( ?Reaction $current, Reaction $requested ): bool {
		return $current === $requested;
	}

	/**
	 * Whether `$line` is a CONTENT-line index of the body (not blank/out-of-range).
	 * Reuses the 7.2 tokeniser so the resonance-anchor contract is enforced here.
	 *
	 * @param int    $line The submitted line index.
	 * @param string $body The raw stored body.
	 * @return bool
	 */
	private static function isContentLine( int $line, string $body ): bool {
		foreach ( GedigBody::tokenize( $body ) as $token ) {
			if ( 'line' === $token['type'] && isset( $token['index'] ) && $token['index'] === $line ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * The raw stored body of a post, or '' when absent.
	 *
	 * @param int $post_id The post.
	 * @return string
	 */
	private static function bodyOf( int $post_id ): string {
		$post = $post_id > 0 ? get_post( $post_id ) : null;

		return $post instanceof \WP_Post ? $post->post_content : '';
	}
}

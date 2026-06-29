<?php
/**
 * The custom front-end content-report form + handler — Story 18.4 (§8).
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Forms;

use WP_Error;

defined( 'ABSPATH' ) || exit;

/**
 * Owns the custom Rapporteer (content-report) form — a custom `ink-core` form, NOT
 * the retired "Report Content" plugin (project-context). A logged-in lid reports a
 * work, review or community response for abuse; this collaborator nonce-verifies,
 * sanitises, validates, drops bot submissions via a honeypot, persists the report
 * to {@see ReportStore} and fires `ink/content_reported` for moderation consumers.
 *
 * Logged-in only: unlike {@see ContactForm} (which binds `nopriv` for anonymous
 * visitors), reporting binds ONLY `admin_post_ink_rapporteer` — a report must be
 * attributable to a lid (so it is moderatable + rate-limitable).
 *
 * THE conflation rule (AD-1): ZERO reference to `Ink\Tiers` / `Ink\Entitlement` —
 * reporting is open to any lid, never gated on membership tier or Gradering.
 *
 * House style: thin {@see render()} (supplies nonce + URL + current target) + pure
 * {@see toHtml()} (escaping + gettext only). Copy is concrete Afrikaans.
 *
 * @package Ink\Core
 */
final class ReportForm {

	/**
	 * The server block name (single source for the renderer + the theme embed).
	 */
	public const BLOCK = 'ink/rapporteer-vorm';

	/**
	 * Nonce action + field name for the report round-trip.
	 */
	public const NONCE_ACTION = 'ink_rapporteer';
	public const NONCE_NAME   = 'ink_rapporteer_nonce';

	/**
	 * The `admin-post` action the report form posts to (logged-in only).
	 */
	public const POST_ACTION = 'ink_rapporteer';

	/**
	 * Form field names (single source for the renderer + the handler).
	 */
	public const FIELD_TARGET = 'ink_rapporteer_tipe';
	public const FIELD_OBJECT = 'ink_rapporteer_objek';
	public const FIELD_REASON = 'ink_rapporteer_rede';
	public const FIELD_DETAIL = 'ink_rapporteer_besonderhede';

	/**
	 * The honeypot field — a real lid leaves it empty; a bot fills it.
	 */
	public const FIELD_HONEYPOT = 'ink_rapporteer_webwerf';

	/**
	 * The query-arg notice slug used to message the lid after a round-trip.
	 */
	public const NOTICE_ARG     = 'ink_rapporteer';
	public const NOTICE_DONE    = 'ontvang';
	public const NOTICE_INVALID = 'fout';

	/**
	 * The action fired on a successful report (moderation-queue consumption seam).
	 */
	public const HOOK_REPORTED = 'ink/content_reported';

	/**
	 * Register the report hooks. Logged-in only (no `nopriv`).
	 */
	public function register(): void {
		add_action( 'admin_post_' . self::POST_ACTION, array( $this, 'handlePost' ) );
		add_action( 'init', array( self::class, 'registerBlock' ) );
	}

	/**
	 * Register the `ink/rapporteer-vorm` dynamic block.
	 */
	public static function registerBlock(): void {
		if ( ! function_exists( 'register_block_type' ) ) {
			return;
		}

		register_block_type(
			self::BLOCK,
			array(
				'render_callback' => array( self::class, 'render' ),
				'attributes'      => array(
					'objekTipe' => array(
						'type'    => 'string',
						'default' => ReportTarget::Werk->value,
					),
					'objekId'   => array(
						'type'    => 'number',
						'default' => 0,
					),
				),
			)
		);
	}

	/**
	 * Block render callback. Only renders for a logged-in lid; supplies the nonce,
	 * the admin-post URL, the target kind + id (defaulting to the current work) and
	 * the current notice to the pure renderer.
	 *
	 * @param array<string, mixed> $attributes Block attributes (objekTipe/objekId).
	 * @return string
	 */
	public static function render( array $attributes = array() ): string {
		if ( ! is_user_logged_in() ) {
			return '';
		}

		$raw_target = isset( $attributes['objekTipe'] ) && is_string( $attributes['objekTipe'] ) ? $attributes['objekTipe'] : '';
		$target     = ( ReportTarget::tryFrom( $raw_target ) ?? ReportTarget::Werk )->value;

		$object_id = isset( $attributes['objekId'] ) ? (int) $attributes['objekId'] : 0;

		if ( $object_id <= 0 ) {
			$object_id = (int) get_the_ID();
		}

		$nonce_field = wp_nonce_field( self::NONCE_ACTION, self::NONCE_NAME, true, false );
		$action_url  = admin_url( 'admin-post.php' );

		$notice = '';
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only notice slug for display only; no state change here.
		if ( isset( $_GET[ self::NOTICE_ARG ] ) && is_scalar( $_GET[ self::NOTICE_ARG ] ) ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- as above.
			$notice = sanitize_key( wp_unslash( $_GET[ self::NOTICE_ARG ] ) );
		}

		return self::toHtml( (string) $nonce_field, (string) $action_url, $target, $object_id, $notice );
	}

	/**
	 * Build the report-form HTML. Pure (escaping + gettext; nonce/URL passed in).
	 *
	 * @param string $nonce_field The pre-rendered `wp_nonce_field` markup.
	 * @param string $action_url  The `admin-post.php` URL to POST to.
	 * @param string $target      The target kind value ({@see ReportTarget}).
	 * @param int    $object_id   The reported object's id.
	 * @param string $notice      Optional result notice slug.
	 * @return string
	 */
	public static function toHtml( string $nonce_field, string $action_url, string $target, int $object_id, string $notice = '' ): string {
		$html = '<form class="ink-rapporteer-vorm" method="post" action="' . esc_url( $action_url ) . '">';

		if ( self::NOTICE_DONE === $notice ) {
			$html .= '<p class="ink-rapporteer-vorm__notice ink-rapporteer-vorm__notice--ok" role="status">'
				. esc_html__( 'Dankie, jou verslag is ontvang en sal nagegaan word.', 'ink-core' ) . '</p>';
		} elseif ( self::NOTICE_INVALID === $notice ) {
			$html .= '<p class="ink-rapporteer-vorm__notice ink-rapporteer-vorm__notice--fout" role="alert">'
				. esc_html__( 'Kies asseblief ’n rede vir die verslag.', 'ink-core' ) . '</p>';
		}

		$html .= $nonce_field
			. '<input type="hidden" name="action" value="' . esc_attr( self::POST_ACTION ) . '" />'
			. '<input type="hidden" name="' . esc_attr( self::FIELD_TARGET ) . '" value="' . esc_attr( $target ) . '" />'
			. '<input type="hidden" name="' . esc_attr( self::FIELD_OBJECT ) . '" value="' . esc_attr( (string) $object_id ) . '" />'
			. '<p class="ink-rapporteer-vorm__veld"><label for="' . esc_attr( self::FIELD_REASON ) . '">'
				. esc_html__( 'Rede', 'ink-core' ) . '</label>'
				. '<select id="' . esc_attr( self::FIELD_REASON ) . '" name="' . esc_attr( self::FIELD_REASON ) . '" required="required">';

		foreach ( ReportReason::cases() as $reason ) {
			$html .= '<option value="' . esc_attr( $reason->value ) . '">' . esc_html( $reason->label() ) . '</option>';
		}

		$html .= '</select></p>'
			. '<p class="ink-rapporteer-vorm__veld"><label for="' . esc_attr( self::FIELD_DETAIL ) . '">'
				. esc_html__( 'Besonderhede (opsioneel)', 'ink-core' ) . '</label>'
				. '<textarea id="' . esc_attr( self::FIELD_DETAIL ) . '" name="' . esc_attr( self::FIELD_DETAIL ) . '" rows="4"></textarea></p>'
			// Honeypot: visually hidden, must stay empty.
			. '<p class="ink-rapporteer-vorm__hp" aria-hidden="true" style="position:absolute;left:-9999px;">'
				. '<label for="' . esc_attr( self::FIELD_HONEYPOT ) . '">' . esc_html__( 'Los hierdie veld leeg', 'ink-core' ) . '</label>'
				. '<input type="text" id="' . esc_attr( self::FIELD_HONEYPOT ) . '" name="' . esc_attr( self::FIELD_HONEYPOT ) . '" tabindex="-1" autocomplete="off" /></p>'
			. '<p class="ink-rapporteer-vorm__stuur"><button type="submit" class="wp-element-button">'
				. esc_html__( 'Rapporteer', 'ink-core' ) . '</button></p>';

		return $html . '</form>';
	}

	/**
	 * Validate the submitted report. Pure, fail-safe.
	 *
	 * @param int    $reporter_id The reporting lid's user id (0 = anonymous).
	 * @param string $target      The target kind value.
	 * @param int    $object_id   The reported object id.
	 * @param string $reason      The reason value.
	 * @return true|WP_Error
	 */
	public function validate( int $reporter_id, string $target, int $object_id, string $reason ) {
		if ( $reporter_id <= 0 ) {
			return new WP_Error( 'ink_rapporteer_anoniem', 'Slegs aangemelde lede kan rapporteer.' );
		}

		if ( null === ReportTarget::tryFrom( $target ) ) {
			return new WP_Error( 'ink_rapporteer_ongeldige_tipe', 'Ongeldige rapporteer-tipe.' );
		}

		if ( $object_id <= 0 ) {
			return new WP_Error( 'ink_rapporteer_ongeldige_objek', 'Ongeldige objek.' );
		}

		if ( null === ReportReason::tryFrom( $reason ) ) {
			return new WP_Error( 'ink_rapporteer_ongeldige_rede', 'Ongeldige rede.' );
		}

		return true;
	}

	/**
	 * Handle the nonce-protected report POST (logged-in lid only).
	 */
	public function handlePost(): void {
		if ( ! isset( $_POST[ self::NONCE_NAME ] ) || ! is_scalar( $_POST[ self::NONCE_NAME ] ) ) {
			$this->redirect( $this->backUrl() );
			return;
		}

		$nonce = sanitize_text_field( wp_unslash( $_POST[ self::NONCE_NAME ] ) );

		if ( ! wp_verify_nonce( $nonce, self::NONCE_ACTION ) ) {
			$this->redirect( $this->backUrl() );
			return;
		}

		// Honeypot: a filled value is a bot. Drop silently with a done-looking redirect.
		$honeypot = isset( $_POST[ self::FIELD_HONEYPOT ] ) && is_scalar( $_POST[ self::FIELD_HONEYPOT ] )
			? trim( sanitize_text_field( wp_unslash( $_POST[ self::FIELD_HONEYPOT ] ) ) )
			: '';

		if ( '' !== $honeypot ) {
			$this->redirect( $this->backUrl( self::NOTICE_DONE ) );
			return;
		}

		$target = isset( $_POST[ self::FIELD_TARGET ] ) && is_scalar( $_POST[ self::FIELD_TARGET ] )
			? sanitize_key( wp_unslash( $_POST[ self::FIELD_TARGET ] ) )
			: '';

		$object_id = isset( $_POST[ self::FIELD_OBJECT ] ) && is_scalar( $_POST[ self::FIELD_OBJECT ] )
			? absint( wp_unslash( $_POST[ self::FIELD_OBJECT ] ) )
			: 0;

		$reason = isset( $_POST[ self::FIELD_REASON ] ) && is_scalar( $_POST[ self::FIELD_REASON ] )
			? sanitize_key( wp_unslash( $_POST[ self::FIELD_REASON ] ) )
			: '';

		$detail = isset( $_POST[ self::FIELD_DETAIL ] ) && is_scalar( $_POST[ self::FIELD_DETAIL ] )
			? sanitize_textarea_field( wp_unslash( $_POST[ self::FIELD_DETAIL ] ) )
			: '';

		$reporter_id = $this->currentUserId();

		$valid = $this->validate( $reporter_id, $target, $object_id, $reason );

		if ( is_wp_error( $valid ) ) {
			$this->redirect( $this->backUrl( self::NOTICE_INVALID ) );
			return;
		}

		$report_id = $this->persist(
			ReportTarget::from( $target ),
			$object_id,
			$reporter_id,
			ReportReason::from( $reason ),
			$detail
		);

		if ( $report_id > 0 ) {
			do_action(
				self::HOOK_REPORTED,
				array(
					'id'          => $report_id,
					'object_type' => $target,
					'object_id'   => $object_id,
					'reporter_id' => $reporter_id,
					'reason'      => $reason,
				)
			);
		}

		$this->redirect( $this->backUrl( self::NOTICE_DONE ) );
	}

	/**
	 * Persist the report. Overridable seam for tests.
	 *
	 * @param ReportTarget $target      What is being reported.
	 * @param int          $object_id   The reported object id.
	 * @param int          $reporter_id The reporting lid.
	 * @param ReportReason $reason      The reason.
	 * @param string       $detail      Optional detail.
	 * @return int The new report id (0 on failure).
	 */
	protected function persist( ReportTarget $target, int $object_id, int $reporter_id, ReportReason $reason, string $detail ): int {
		return ReportStore::record( $target, $object_id, $reporter_id, $reason, $detail );
	}

	/**
	 * The current user's id. Overridable seam.
	 */
	protected function currentUserId(): int {
		return get_current_user_id();
	}

	/**
	 * The URL to return to after a report (the referring work), with optional notice.
	 *
	 * @param string $notice Optional notice slug.
	 * @return string
	 */
	protected function backUrl( string $notice = '' ): string {
		$referer = wp_get_referer();
		$url     = is_string( $referer ) && '' !== $referer ? $referer : home_url( '/' );

		return '' === $notice ? $url : add_query_arg( self::NOTICE_ARG, $notice, $url );
	}

	/**
	 * Redirect to a local URL and end the request. Seam: tests override {@see halt()}.
	 *
	 * @param string $url Target URL.
	 */
	protected function redirect( string $url ): void {
		wp_safe_redirect( $url );
		$this->halt();
	}

	/**
	 * End the request after a redirect. Overridable seam for tests.
	 */
	protected function halt(): void {
		exit;
	}
}

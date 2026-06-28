<?php
/**
 * The custom front-end Kontak (contact) form handler + block — Story 15.4 (FR-61).
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Forms;

use WP_Error;

defined( 'ABSPATH' ) || exit;

/**
 * Owns the custom Kontak contact form (FR-61) — a custom `ink-core` form, NOT
 * Contact Form 7 / Fluent Forms (OQ-8 resolved). A besoeker (anonymous or logged-in)
 * enters a name, e-mail, optional subject and a message; this collaborator
 * nonce-verifies, sanitises, validates, drops bot submissions via a honeypot, and
 * (behind a filterable send toggle) e-mails the site's admin address.
 *
 * Public path: unlike the logged-in-only Skryf form ({@see \Ink\Submission\SubmissionForm}),
 * the contact form registers BOTH `admin_post_` and `admin_post_nopriv_` so a visitor
 * who has not registered can still reach INK. The write seam is the sanctioned
 * `admin-post` path: nonce-verified, raw `$_POST` guarded inline with `is_scalar()`
 * before any sanitiser, then a safe redirect with a notice.
 *
 * THE conflation rule (AD-1): ZERO reference to `Ink\Tiers` / `Ink\Entitlement` — a
 * contact form is never gated on membership or the writer Gradering.
 *
 * House style: thin {@see render()} (supplies the nonce + admin-post URL + notice from
 * WordPress) + pure {@see toHtml()} (escaping only). Copy is Afrikaans-as-source via
 * gettext; the not-yet-curated Kontak microcopy carries the standing human-copy-pending
 * marker (the unauthored-copy workflow; see docs/afrikaans-copy-worklist.md).
 *
 * @package Ink\Core
 */
final class ContactForm {

	/**
	 * The server block name (single source for the renderer + the theme embed).
	 */
	public const BLOCK = 'ink/kontak-vorm';

	/**
	 * Nonce action + field name for the contact round-trip.
	 */
	public const NONCE_ACTION = 'ink_kontak';
	public const NONCE_NAME   = 'ink_kontak_nonce';

	/**
	 * The `admin-post` action the Kontak form posts to (priv + nopriv).
	 */
	public const POST_ACTION = 'ink_kontak';

	/**
	 * Form field names (single source for the renderer + the handler).
	 */
	public const FIELD_NAME    = 'ink_kontak_naam';
	public const FIELD_EMAIL   = 'ink_kontak_epos';
	public const FIELD_SUBJECT = 'ink_kontak_onderwerp';
	public const FIELD_MESSAGE = 'ink_kontak_boodskap';

	/**
	 * The honeypot field — a real visitor leaves it empty; a bot fills it. A
	 * non-empty value silently drops the submission.
	 */
	public const FIELD_HONEYPOT = 'ink_kontak_webwerf';

	/**
	 * The query-arg notice slug used to message the visitor after a round-trip.
	 */
	public const NOTICE_ARG       = 'ink_kontak';
	public const NOTICE_SENT      = 'gestuur';
	public const NOTICE_INVALID   = 'fout';
	public const NOTICE_SEND_FAIL = 'stuur-fout';

	/**
	 * Register the Kontak hooks. Invoked once from {@see Module::register()}.
	 *
	 * Both `admin_post_` (logged-in) and `admin_post_nopriv_` (anonymous) are bound —
	 * a contact form must be reachable by any besoeker.
	 */
	public function register(): void {
		add_action( 'admin_post_' . self::POST_ACTION, array( $this, 'handlePost' ) );
		add_action( 'admin_post_nopriv_' . self::POST_ACTION, array( $this, 'handlePost' ) );
		add_action( 'init', array( self::class, 'registerBlock' ) );
	}

	/**
	 * Register the `ink/kontak-vorm` dynamic block.
	 */
	public static function registerBlock(): void {
		if ( ! function_exists( 'register_block_type' ) ) {
			return;
		}

		register_block_type(
			self::BLOCK,
			array(
				'render_callback' => array( self::class, 'render' ),
			)
		);
	}

	/**
	 * Block render callback. Supplies the nonce field, the admin-post URL and the
	 * current notice (from the query string) to the pure renderer.
	 *
	 * @return string
	 */
	public static function render(): string {
		$nonce_field = wp_nonce_field( self::NONCE_ACTION, self::NONCE_NAME, true, false );
		$action_url  = admin_url( 'admin-post.php' );

		$notice = '';
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only notice slug for display only; no state changes here.
		if ( isset( $_GET[ self::NOTICE_ARG ] ) && is_scalar( $_GET[ self::NOTICE_ARG ] ) ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- as above.
			$notice = sanitize_key( wp_unslash( $_GET[ self::NOTICE_ARG ] ) );
		}

		return self::toHtml( (string) $nonce_field, (string) $action_url, $notice );
	}

	/**
	 * Build the contact-form HTML. Pure (escaping + gettext only; the nonce + URL are
	 * passed in). Posts to the `admin-post` action; carries the four labelled fields,
	 * the honeypot, and a submit button. An optional notice banner messages the result.
	 *
	 * @param string $nonce_field The pre-rendered `wp_nonce_field` markup.
	 * @param string $action_url  The `admin-post.php` URL to POST to.
	 * @param string $notice      Optional result notice slug ({@see NOTICE_SENT}/{@see NOTICE_INVALID}).
	 * @return string
	 */
	public static function toHtml( string $nonce_field, string $action_url, string $notice = '' ): string {
		$html = '<form class="ink-kontak-vorm" method="post" action="' . esc_url( $action_url ) . '">';

		if ( self::NOTICE_SENT === $notice ) {
			$html .= '<p class="ink-kontak-vorm__notice ink-kontak-vorm__notice--ok" role="status">'
				. esc_html__( 'Dankie, jou boodskap is gestuur.', 'ink-core' ) . '</p>';
		} elseif ( self::NOTICE_INVALID === $notice ) {
			$html .= '<p class="ink-kontak-vorm__notice ink-kontak-vorm__notice--fout" role="alert">'
				. esc_html__( 'Maak seker jou naam, e-pos en boodskap is ingevul.', 'ink-core' ) . '</p>';
		} elseif ( self::NOTICE_SEND_FAIL === $notice ) {
			$html .= '<p class="ink-kontak-vorm__notice ink-kontak-vorm__notice--fout" role="alert">'
				. esc_html__( 'Ons kon nie jou boodskap stuur nie. Probeer asseblief weer.', 'ink-core' ) . '</p>';
		}

		$html .= $nonce_field
			. '<input type="hidden" name="action" value="' . esc_attr( self::POST_ACTION ) . '" />'
			. '<p class="ink-kontak-vorm__veld"><label for="' . esc_attr( self::FIELD_NAME ) . '">'
				. esc_html__( 'Naam', 'ink-core' ) . '</label>'
				. '<input type="text" id="' . esc_attr( self::FIELD_NAME ) . '" name="' . esc_attr( self::FIELD_NAME ) . '" required="required" /></p>'
			. '<p class="ink-kontak-vorm__veld"><label for="' . esc_attr( self::FIELD_EMAIL ) . '">'
				. esc_html__( 'E-pos', 'ink-core' ) . '</label>'
				. '<input type="email" id="' . esc_attr( self::FIELD_EMAIL ) . '" name="' . esc_attr( self::FIELD_EMAIL ) . '" required="required" /></p>'
			. '<p class="ink-kontak-vorm__veld"><label for="' . esc_attr( self::FIELD_SUBJECT ) . '">'
				. esc_html__( 'Onderwerp', 'ink-core' ) . '</label>'
				. '<input type="text" id="' . esc_attr( self::FIELD_SUBJECT ) . '" name="' . esc_attr( self::FIELD_SUBJECT ) . '" /></p>'
			. '<p class="ink-kontak-vorm__veld"><label for="' . esc_attr( self::FIELD_MESSAGE ) . '">'
				. esc_html__( 'Boodskap', 'ink-core' ) . '</label>'
				. '<textarea id="' . esc_attr( self::FIELD_MESSAGE ) . '" name="' . esc_attr( self::FIELD_MESSAGE ) . '" rows="6" required="required"></textarea></p>'
			// Honeypot: visually hidden, must stay empty. A filled value is a bot.
			. '<p class="ink-kontak-vorm__hp" aria-hidden="true" style="position:absolute;left:-9999px;">'
				. '<label for="' . esc_attr( self::FIELD_HONEYPOT ) . '">' . esc_html__( 'Los hierdie veld leeg', 'ink-core' ) . '</label>'
				. '<input type="text" id="' . esc_attr( self::FIELD_HONEYPOT ) . '" name="' . esc_attr( self::FIELD_HONEYPOT ) . '" tabindex="-1" autocomplete="off" /></p>'
			. '<p class="ink-kontak-vorm__stuur"><button type="submit" class="wp-element-button">'
				. esc_html__( 'Stuur boodskap', 'ink-core' ) . '</button></p>'
			// Standing copy-debt marker: the Kontak micro/validation/success copy is not
			// yet curated in ui-copy-translations.md (see docs/afrikaans-copy-worklist.md).
			. '<span class="ink-needs-human-af" hidden>[NEEDS HUMAN AFRIKAANS] — Kontak form labels / validation / success copy not yet authored in ui-copy-translations.md.</span>';

		return $html . '</form>';
	}

	/**
	 * Validate the submitted fields. Pure, fail-safe (no WordPress state beyond the
	 * mockable `is_email`): name and message must be non-empty after trimming, and the
	 * e-mail must be a valid address. On any failure a {@see WP_Error} is returned and
	 * the caller sends NOTHING — the visitor is returned to the form.
	 *
	 * @param string $name    The visitor's name (already sanitised).
	 * @param string $email   The visitor's e-mail (already sanitised).
	 * @param string $subject The optional subject (already sanitised).
	 * @param string $message The message body (already sanitised).
	 * @return true|WP_Error True when valid, else the first validation error.
	 */
	public function validate( string $name, string $email, string $subject, string $message ) {
		if ( '' === trim( $name ) ) {
			return new WP_Error( 'ink_kontak_missing_name', 'Naam ontbreek.' );
		}

		if ( '' === trim( $email ) || ! is_email( $email ) ) {
			return new WP_Error( 'ink_kontak_invalid_email', 'Ongeldige e-pos.' );
		}

		if ( '' === trim( $message ) ) {
			return new WP_Error( 'ink_kontak_missing_message', 'Boodskap ontbreek.' );
		}

		return true;
	}

	/**
	 * Handle the nonce-protected Kontak POST (anonymous or logged-in).
	 *
	 * Sanctioned path: nonce verify → honeypot drop → inline `is_scalar` guards +
	 * `wp_unslash` + sanitise → {@see validate()} → {@see send()} → safe redirect with
	 * a notice. Every failure degrades gracefully back to the form; no raw superglobal
	 * reaches a sanitiser un-guarded.
	 */
	public function handlePost(): void {
		if ( ! isset( $_POST[ self::NONCE_NAME ] ) || ! is_scalar( $_POST[ self::NONCE_NAME ] ) ) {
			$this->redirect( $this->formUrl() );
			return;
		}

		$nonce = sanitize_text_field( wp_unslash( $_POST[ self::NONCE_NAME ] ) );

		if ( ! wp_verify_nonce( $nonce, self::NONCE_ACTION ) ) {
			$this->redirect( $this->formUrl() );
			return;
		}

		// Honeypot: a filled value is a bot. Drop silently with a success-looking
		// redirect so the bot learns nothing.
		$honeypot = isset( $_POST[ self::FIELD_HONEYPOT ] ) && is_scalar( $_POST[ self::FIELD_HONEYPOT ] )
			? trim( sanitize_text_field( wp_unslash( $_POST[ self::FIELD_HONEYPOT ] ) ) )
			: '';

		if ( '' !== $honeypot ) {
			$this->redirect( $this->formUrl( self::NOTICE_SENT ) );
			return;
		}

		$name = isset( $_POST[ self::FIELD_NAME ] ) && is_scalar( $_POST[ self::FIELD_NAME ] )
			? sanitize_text_field( wp_unslash( $_POST[ self::FIELD_NAME ] ) )
			: '';

		$email = isset( $_POST[ self::FIELD_EMAIL ] ) && is_scalar( $_POST[ self::FIELD_EMAIL ] )
			? sanitize_email( wp_unslash( $_POST[ self::FIELD_EMAIL ] ) )
			: '';

		$subject = isset( $_POST[ self::FIELD_SUBJECT ] ) && is_scalar( $_POST[ self::FIELD_SUBJECT ] )
			? sanitize_text_field( wp_unslash( $_POST[ self::FIELD_SUBJECT ] ) )
			: '';

		$message = isset( $_POST[ self::FIELD_MESSAGE ] ) && is_scalar( $_POST[ self::FIELD_MESSAGE ] )
			? sanitize_textarea_field( wp_unslash( $_POST[ self::FIELD_MESSAGE ] ) )
			: '';

		$valid = $this->validate( $name, $email, $subject, $message );

		if ( is_wp_error( $valid ) ) {
			$this->redirect( $this->formUrl( self::NOTICE_INVALID ) );
			return;
		}

		// Report the truth: only claim "gestuur" when the message was actually
		// accepted for delivery. A disabled toggle, an empty admin_email, or a
		// wp_mail transport failure must NOT show success (the message would be
		// silently lost). The honeypot path above is the deliberate exception.
		$sent = $this->send( $name, $email, $subject, $message );

		$this->redirect( $this->formUrl( $sent ? self::NOTICE_SENT : self::NOTICE_SEND_FAIL ) );
	}

	/**
	 * E-mail the contact submission to the site's admin address (Story 15.4).
	 *
	 * Behind the filterable `ink_kontak_send_enabled` toggle (default ON — a contact
	 * form is meant to deliver; an operator can disable it). The recipient defaults to
	 * `admin_email` and is filterable via `ink_kontak_recipient`. Reply-To is set to the
	 * visitor's e-mail so staff can reply directly. Overridable seam for tests.
	 *
	 * @param string $name    The visitor's (sanitised) name.
	 * @param string $email   The visitor's (sanitised) e-mail.
	 * @param string $subject The (sanitised) subject (may be empty).
	 * @param string $message The (sanitised) message body.
	 * @return bool Whether the mail was accepted for delivery (false when disabled).
	 */
	protected function send( string $name, string $email, string $subject, string $message ): bool {
		if ( ! apply_filters( 'ink_kontak_send_enabled', true ) ) {
			return false;
		}

		$recipient = (string) apply_filters( 'ink_kontak_recipient', get_option( 'admin_email' ) );

		if ( '' === $recipient ) {
			return false;
		}

		$line = '' === trim( $subject ) ? __( 'Kontakboodskap van INK', 'ink-core' ) : $subject;
		$body = $name . ' <' . $email . '>' . "\n\n" . $message;

		// RFC 5322 quoted display-name: the sanitisers already strip CR/LF (no header
		// injection), but a name with commas / angle brackets / quotes would otherwise
		// form a malformed Reply-To. Quote it and strip any residual double-quotes.
		$header = 'Reply-To: "' . str_replace( '"', '', $name ) . '" <' . $email . '>';

		return (bool) wp_mail( $recipient, $line, $body, array( $header ) );
	}

	/**
	 * The Kontak page URL, with an optional notice marker.
	 *
	 * @param string $notice Optional `ink_kontak` notice slug.
	 * @return string The local Kontak URL.
	 */
	protected function formUrl( string $notice = '' ): string {
		$url = home_url( '/kontak' );

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

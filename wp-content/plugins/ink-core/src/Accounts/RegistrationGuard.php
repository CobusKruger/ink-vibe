<?php
/**
 * Registration anti-spam baseline + hardening — Story 18.10 (FR-3a, R6).
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Accounts;

use WP_Error;

defined( 'ABSPATH' ) || exit;

/**
 * The always-on registration anti-abuse surface (Story 18.10).
 *
 * The Story-3.4 spike decided the baseline layers — honeypot + submission-timing +
 * a challenge (Cloudflare Turnstile) — and the hardening around the optional
 * pending-approval state ({@see Approval}, Story 3.6): edge rate-limiting +
 * blocked-attempt analytics. This collaborator builds them on the registration
 * endpoint:
 *
 *  - **Honeypot** — a hidden field a human leaves empty; a filled value blocks.
 *  - **Timing** — a render timestamp; a submission faster than {@see MIN_SECONDS}
 *    is a bot.
 *  - **Challenge** — a Cloudflare Turnstile verdict via the
 *    `ink_registration_challenge_passed` filter (default pass when no provider is
 *    wired, so the gate never blocks legitimate signups before Turnstile is set up).
 *  - **Rate-limit** — at most {@see MAX_ATTEMPTS} registration attempts per IP per
 *    window (the count + record are overridable seams over a transient).
 *  - **Analytics** — `do_action( 'ink/registration_blocked', … )` so blocked attempts
 *    are observable (the security stack's blocked-attempt surface).
 *
 * The decision ({@see evaluate()}) is pure over primitives so it unit-tests without
 * WordPress; the request reads (honeypot value, timestamps, IP, attempt count, the
 * challenge verdict) are overridable seams. Conflation-clean: zero Tiers/Entitlement.
 *
 * @package Ink\Core
 */
class RegistrationGuard {

	/**
	 * Hidden honeypot field — a human leaves it empty; a bot fills it.
	 */
	public const FIELD_HONEYPOT = 'ink_reg_webwerf';

	/**
	 * Hidden render-timestamp field (epoch seconds) — drives the timing check.
	 */
	public const FIELD_RENDERED_AT = 'ink_reg_t';

	/**
	 * Minimum plausible human fill time, in seconds. Faster ⇒ a bot.
	 */
	public const MIN_SECONDS = 3;

	/**
	 * Max registration attempts per IP per window.
	 */
	public const MAX_ATTEMPTS = 5;

	/**
	 * The rate-limit window, in seconds (15 minutes).
	 */
	public const WINDOW_SECONDS = 900;

	/**
	 * The block-reason codes (also the WP_Error codes).
	 */
	public const REASON_HONEYPOT  = 'ink_reg_honeypot';
	public const REASON_TOO_FAST  = 'ink_reg_too_fast';
	public const REASON_CHALLENGE = 'ink_reg_challenge';
	public const REASON_RATE      = 'ink_reg_rate';

	/**
	 * The action fired on a blocked attempt (the analytics seam).
	 */
	public const HOOK_BLOCKED = 'ink/registration_blocked';

	/**
	 * Wire the guard: emit the hidden fields on the registration form, and validate
	 * on submit via the WP-native `registration_errors` filter.
	 */
	public function register(): void {
		add_action( 'register_form', array( $this, 'renderFields' ) );
		add_filter( 'registration_errors', array( $this, 'guard' ), 10, 1 );
	}

	/**
	 * Decide whether a registration attempt is blocked, returning a reason code or
	 * null when it passes. Pure — evaluated in honeypot → timing → challenge →
	 * rate order (the cheapest, most certain signals first).
	 *
	 * @param array{honeypot_filled:bool, elapsed_seconds:int, challenge_passed:bool, attempts:int} $signals Request signals.
	 * @return string|null The block reason, or null when the attempt passes.
	 */
	public static function evaluate( array $signals ): ?string {
		if ( true === ( $signals['honeypot_filled'] ?? false ) ) {
			return self::REASON_HONEYPOT;
		}

		if ( ( $signals['elapsed_seconds'] ?? PHP_INT_MAX ) < self::MIN_SECONDS ) {
			return self::REASON_TOO_FAST;
		}

		if ( true !== ( $signals['challenge_passed'] ?? true ) ) {
			return self::REASON_CHALLENGE;
		}

		if ( ( $signals['attempts'] ?? 0 ) >= self::MAX_ATTEMPTS ) {
			return self::REASON_RATE;
		}

		return null;
	}

	/**
	 * The Afrikaans member-facing message for a block reason.
	 *
	 * @param string $reason A REASON_* code.
	 */
	public static function messageFor( string $reason ): string {
		return match ( $reason ) {
			self::REASON_RATE => __( 'Te veel registrasiepogings. Probeer asseblief later weer.', 'ink-core' ),
			default           => __( 'Ons kon nie jou registrasie verifieer nie. Probeer asseblief weer.', 'ink-core' ),
		};
	}

	/**
	 * Render the hidden honeypot + timestamp fields on the registration form.
	 */
	public function renderFields(): void {
		printf(
			'<p class="ink-reg-hp" aria-hidden="true" style="position:absolute;left:-9999px;">'
				. '<label for="%1$s">%2$s</label>'
				. '<input type="text" name="%1$s" id="%1$s" tabindex="-1" autocomplete="off" value="" /></p>'
				. '<input type="hidden" name="%3$s" value="%4$d" />',
			esc_attr( self::FIELD_HONEYPOT ),
			esc_html__( 'Los hierdie veld leeg', 'ink-core' ),
			esc_attr( self::FIELD_RENDERED_AT ),
			(int) $this->now()
		);
	}

	/**
	 * Validate the registration attempt; add a WP_Error + fire analytics on a block.
	 *
	 * @param WP_Error $errors The accumulating registration errors.
	 * @return WP_Error
	 */
	public function guard( WP_Error $errors ): WP_Error {
		$signals = array(
			'honeypot_filled'  => '' !== trim( $this->honeypotValue() ),
			'elapsed_seconds'  => $this->now() - $this->renderedAt(),
			'challenge_passed' => $this->challengePassed(),
			'attempts'         => $this->attemptCount(),
		);

		$this->recordAttempt();

		$reason = self::evaluate( $signals );

		if ( null === $reason ) {
			return $errors;
		}

		/**
		 * Fires when a registration attempt is blocked (the blocked-attempt analytics
		 * surface). The reason is a REASON_* code; the IP is the rate-limit key.
		 *
		 * @param string $reason The block reason code.
		 * @param string $ip     The requester IP.
		 */
		do_action( self::HOOK_BLOCKED, $reason, $this->requesterIp() ); // phpcs:ignore WordPress.NamingConventions.ValidHookName.UseUnderscores -- INK ink/... event-surface convention (AD).

		$errors->add( $reason, self::messageFor( $reason ) );

		return $errors;
	}

	/**
	 * The submitted honeypot value. Overridable seam.
	 */
	protected function honeypotValue(): string {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- WP-core registration nonce is verified by WordPress before registration_errors; this only reads the honeypot for a bot check.
		if ( ! isset( $_POST[ self::FIELD_HONEYPOT ] ) || ! is_scalar( $_POST[ self::FIELD_HONEYPOT ] ) ) {
			return '';
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- as above.
		return sanitize_text_field( wp_unslash( $_POST[ self::FIELD_HONEYPOT ] ) );
	}

	/**
	 * The submitted render timestamp (epoch). Overridable seam.
	 */
	protected function renderedAt(): int {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- WP-core registration nonce verified upstream; this only reads the timing field.
		if ( ! isset( $_POST[ self::FIELD_RENDERED_AT ] ) || ! is_scalar( $_POST[ self::FIELD_RENDERED_AT ] ) ) {
			return 0;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- as above.
		return absint( wp_unslash( $_POST[ self::FIELD_RENDERED_AT ] ) );
	}

	/**
	 * The current epoch time. Overridable seam.
	 */
	protected function now(): int {
		return time();
	}

	/**
	 * Whether the challenge (Turnstile) verdict passed. Filter seam — defaults to
	 * pass so signups are not blocked before a provider is wired.
	 */
	protected function challengePassed(): bool {
		return (bool) apply_filters( 'ink_registration_challenge_passed', true );
	}

	/**
	 * The requester IP (the rate-limit key). Overridable seam.
	 */
	protected function requesterIp(): string {
		if ( ! isset( $_SERVER['REMOTE_ADDR'] ) || ! is_scalar( $_SERVER['REMOTE_ADDR'] ) ) {
			return '';
		}

		// Read-only rate-limit key; behind Cloudflare the trusted client IP is resolved at the edge.
		return sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) );
	}

	/**
	 * The recent attempt count for this requester. Overridable seam (transient).
	 */
	protected function attemptCount(): int {
		$count = get_transient( $this->rateKey() );

		return is_numeric( $count ) ? (int) $count : 0;
	}

	/**
	 * Record this attempt against the rate-limit window. Overridable seam.
	 */
	protected function recordAttempt(): void {
		$key = $this->rateKey();
		set_transient( $key, $this->attemptCount() + 1, self::WINDOW_SECONDS );
	}

	/**
	 * The transient key for this requester's rate-limit window.
	 */
	protected function rateKey(): string {
		return 'ink_reg_attempts_' . md5( $this->requesterIp() );
	}
}

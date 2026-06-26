<?php
/**
 * Post-signup onboarding state (the one-time, skippable onboarding flag).
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Accounts;

use Ink\Kernel\Scalar;

defined( 'ABSPATH' ) || exit;

/**
 * Owns INK's post-signup onboarding STATE (Epic 3, Story 3.3).
 *
 * The account already exists at Brons / gratis lid ({@see Registration} stamps
 * `ink_writer_tier` on `user_register`). This collaborator picks up AFTER that:
 * it registers a single one-bit user-meta flag (`ink_onboarding_complete`) on the
 * Story-2.3 `register_meta('user', …)` substrate, and persists it from the
 * nonce-protected skip/complete write ({@see completeViaPost}). The flag drives
 * AC-1's behaviour — onboarding shows when it is unset/false; completing OR
 * skipping sets it true; a returning, already-onboarded/dismissed lid is never
 * re-nagged. The flag NEVER blocks any account capability.
 *
 * Unlike the staff-gated `ink_writer_tier`, this is SELF-SET / OWN-RECORD state:
 * the lid sets their own onboarding flag, so the `auth_callback` authorizes the
 * own-record case only.
 *
 * THE conflation rule (AD-1): this collaborator writes ONLY the onboarding flag —
 * no lidmaatskap, no entitlement, no `ink_writer_tier` re-write, no reader/writer
 * intent flag (Story 3.2 removed it). `src/Accounts/` carries ZERO reference to
 * `Ink\Entitlement`.
 *
 * Scope discipline (Story 3.3 out-of-scope list): the first-action prompt is a
 * PRESENTATION seam in the theme that degrades gracefully — this module builds
 * NO follow graph (Story 9.2 / `Ink\Social`), NO leeslys `ink_reading_list`
 * (Story 7.7 / `Ink\Engagement`), NO `/ink/v1/volg` or `/ink/v1/leeslys` write.
 *
 * @package Ink\Core
 */
final class Onboarding {

	/**
	 * The one-bit onboarding-state user-meta key (single source), `ink_`-prefixed.
	 *
	 * Unset/false ⇒ the lid has not yet completed/dismissed onboarding (show it);
	 * true ⇒ completed or skipped (never re-nag).
	 */
	public const ONBOARDING_COMPLETE = 'ink_onboarding_complete';

	/**
	 * Nonce action + field name for the skip/complete write round-trip.
	 */
	private const NONCE_ACTION = 'ink_accounts_onboarding_complete';
	private const NONCE_NAME   = 'ink_accounts_onboarding_nonce';

	/**
	 * The admin-post action name the skip/complete form posts to (logged-in only).
	 */
	private const POST_ACTION = 'ink_onboarding_complete';

	/**
	 * Register the onboarding hooks. Invoked once from {@see Module::register()}
	 * (dispatched by the Kernel on `init`).
	 *
	 * Wires: the onboarding-state user-meta registration, and the
	 * logged-in-only `admin-post` handler for the nonce-protected skip/complete
	 * write. No follow/leeslys subsystem is touched here.
	 */
	public function register(): void {
		add_action( 'init', array( $this, 'registerMeta' ) );

		// The skip/complete write is a logged-in own-record toggle. `admin-post`
		// (logged-in variant only — no `nopriv`) gives a nonce-protected,
		// capability-checked POST seam for a one-bit flag without a bespoke REST
		// route (AD-7 sanctions a simple toggle for client state of this size).
		add_action( 'admin_post_' . self::POST_ACTION, array( $this, 'completeViaPost' ) );
	}

	/**
	 * Register the onboarding-state user meta on the Story-2.3 substrate.
	 *
	 * Mirrors {@see \Ink\Content\UserMeta} house style: `single`, a typed value, a
	 * sane registered default (`false` = "not yet onboarded"), a
	 * `sanitize_callback` guarded by the shared {@see Scalar}::safe() helper (a non-scalar
	 * payload must never reach a scalar sanitiser — the Epic-2 recurring bug
	 * class), and an `auth_callback` that authorizes the OWN-RECORD case only
	 * (self-set onboarding state, unlike the staff-gated tier).
	 */
	public function registerMeta(): void {
		register_meta(
			'user',
			self::ONBOARDING_COMPLETE,
			array(
				'single'            => true,
				'type'              => 'boolean',
				'show_in_rest'      => true,
				'default'           => false, // Unset ⇒ not yet onboarded ⇒ show onboarding.
				'sanitize_callback' => array( self::class, 'sanitizeFlag' ),
				'auth_callback'     => array( self::class, 'authOwnRecord' ),
			)
		);
	}

	/**
	 * Coerce any incoming value to the boolean onboarding flag.
	 *
	 * The shared {@see Scalar}::safe() guard (Epic-2 recurring bug class): a non-scalar
	 * payload (array/object from a malformed REST/POST body) falls back to the
	 * `false` default rather than reaching a scalar coercion. A scalar is
	 * normalised through `rest_sanitize_boolean` so "0"/"false"/"" read as false.
	 *
	 * @param mixed $value Incoming meta value.
	 * @return bool The normalised flag.
	 */
	public static function sanitizeFlag( $value ): bool {
		if ( ! Scalar::safe( $value ) ) {
			return false;
		}

		return (bool) rest_sanitize_boolean( $value );
	}

	/**
	 * Authorize a write to the onboarding flag for the OWN record only.
	 *
	 * Onboarding state is self-set: a logged-in lid may set their own flag. The
	 * `auth_callback` receives the meta context; we authorize only when the
	 * current user is the subject. Staff (who may edit other users) are allowed via
	 * `edit_user`. This is NOT the staff-only gate the tier uses.
	 *
	 * @param bool   $allowed   The incoming WP decision (ignored — we decide).
	 * @param string $meta_key  The meta key being authorized.
	 * @param int    $object_id The user ID the meta belongs to.
	 * @return bool Whether the write is authorized.
	 */
	public static function authOwnRecord( $allowed, $meta_key, $object_id ): bool { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundBeforeLastUsed -- WP passes the incoming decision + key; we re-derive from the object_id.
		$current = get_current_user_id();

		if ( 0 === $current ) {
			return false;
		}

		if ( (int) $object_id === $current ) {
			return true;
		}

		return current_user_can( 'edit_user', (int) $object_id );
	}

	/**
	 * Whether a lid has completed (or dismissed) onboarding.
	 *
	 * The one read surface the theme/template gate consumes (through the bridge in
	 * `functions.php`) to decide whether to show onboarding — driving the one-time
	 * / no-re-nag behaviour (AC-1). Reads only the flag; references no entitlement.
	 *
	 * @param int $user_id The lid.
	 * @return bool True once onboarding is complete or dismissed.
	 */
	public static function hasCompleted( int $user_id ): bool {
		if ( $user_id <= 0 ) {
			return false;
		}

		return self::sanitizeFlag( get_user_meta( $user_id, self::ONBOARDING_COMPLETE, true ) );
	}

	/**
	 * Mark onboarding complete/dismissed for a lid (the single write path).
	 *
	 * Writes ONLY the onboarding flag — no lidmaatskap, no entitlement, no tier
	 * re-stamp, no intent flag (THE conflation rule). Idempotent: a returning lid
	 * who already finished is simply re-set to the same true value.
	 *
	 * @param int $user_id The lid completing/skipping onboarding.
	 */
	public static function markComplete( int $user_id ): void {
		if ( $user_id <= 0 ) {
			return;
		}

		update_user_meta( $user_id, self::ONBOARDING_COMPLETE, true );
	}

	/**
	 * The nonce action used by the skip/complete form (test/template surface).
	 */
	public static function nonceAction(): string {
		return self::NONCE_ACTION;
	}

	/**
	 * The nonce field name used by the skip/complete form (test/template surface).
	 */
	public static function nonceName(): string {
		return self::NONCE_NAME;
	}

	/**
	 * The `admin-post` action the skip/complete form targets (test/template surface).
	 */
	public static function postAction(): string {
		return self::POST_ACTION;
	}

	/**
	 * Handle the nonce-protected skip/complete POST (logged-in own-record only).
	 *
	 * The sanctioned (never raw) `$_POST` path: nonce verify → logged-in own-record
	 * check → write only the flag → redirect back. NO raw superglobal reaches a
	 * sanitiser without the {@see Scalar}::safe() guard. Errors degrade gracefully — a
	 * failed flag-write must never block the lid from using the account, so the
	 * handler always returns the lid to the site rather than fatalling.
	 */
	public function completeViaPost(): void {
		$user_id = get_current_user_id();

		// Logged-in own-record only: a logged-out request cannot set the flag.
		if ( 0 === $user_id ) {
			$this->safeRedirect( home_url( '/' ) );
			return;
		}

		// Nonce verify (state-changing path — AD-6).
		if ( ! isset( $_POST[ self::NONCE_NAME ] ) || ! is_scalar( $_POST[ self::NONCE_NAME ] ) ) {
			$this->safeRedirect( home_url( '/' ) );
			return;
		}

		$nonce = sanitize_text_field( wp_unslash( $_POST[ self::NONCE_NAME ] ) );

		if ( ! wp_verify_nonce( $nonce, self::NONCE_ACTION ) ) {
			$this->safeRedirect( home_url( '/' ) );
			return;
		}

		// Write ONLY the onboarding flag (own record).
		self::markComplete( $user_id );

		// Honour an optional sanitised return target; default home.
		$redirect = home_url( '/' );

		if ( isset( $_POST['ink_redirect_to'] ) && is_scalar( $_POST['ink_redirect_to'] ) ) {
			$candidate = esc_url_raw( wp_unslash( $_POST['ink_redirect_to'] ) );

			if ( '' !== $candidate ) {
				$redirect = $candidate;
			}
		}

		$this->safeRedirect( $redirect );
	}

	/**
	 * Redirect helper kept to local URLs (graceful, never blocks).
	 *
	 * @param string $url Target URL.
	 */
	private function safeRedirect( string $url ): void {
		wp_safe_redirect( $url );
		exit;
	}
}

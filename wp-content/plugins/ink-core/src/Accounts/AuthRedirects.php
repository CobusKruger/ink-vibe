<?php
/**
 * Keeps the login / registration / lost-password round-trip on INK's own
 * styled pages instead of WordPress core's raw admin-styled `wp-login.php`
 * screens (Epic 19 auth fidelity pass).
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Accounts;

use WP_Error;

defined( 'ABSPATH' ) || exit;

/**
 * `patterns/auth-login.php` / `auth-register.php` / `auth-forgot-password.php`
 * (`ink-foundation`) POST straight to `wp-login.php` — "auth is USED, never
 * reimplemented" — which is correct for the HAPPY path (WordPress's own
 * `redirect_to` field already lands a successful login/reset back wherever
 * the theme points it). The GAP this class closes is what WordPress core does
 * on its OWN, without a hook: a FAILED login/lost-password submission falls
 * through to `login_header()` — core's raw, un-translated, unbranded admin
 * login-screen chrome — and a registration POST (success OR failure) is
 * additionally intercepted earlier still by BuddyPress's `login_form_register`
 * handler (`bp_core_screen_signup()`, live whenever the `members` component is
 * scoped on — see {@see \Ink\Social\BuddyPress::SCOPED_ON}), which renders its
 * own separate un-translated screen rather than ever reaching WordPress
 * core's own `register_new_user()` call.
 *
 * Each method here hooks the earliest point WordPress (or BuddyPress) offers
 * for exactly this purpose and redirects back to the theme's own page with a
 * notice-slug query arg — the same `?slug=notice` + fixed-message convention
 * already established by `Ink\Forms\ContactForm`. No credential/session logic
 * is touched: {@see registerFailure()} still calls WordPress's OWN
 * `register_new_user()`; it only runs EARLIER (priority 1 on the same action
 * BuddyPress hooks) so it — not BuddyPress's screen — decides where the
 * visitor lands.
 */
class AuthRedirects {

	public const LOGIN_URL_PATH    = '/meld-aan/';
	public const REGISTER_URL_PATH = '/registreer/';
	public const LOST_URL_PATH     = '/wagwoord-herstel/';

	public const LOGIN_NOTICE_ARG    = 'meld_aan';
	public const REGISTER_NOTICE_ARG = 'registreer';
	public const LOST_NOTICE_ARG     = 'wagwoord';

	public const NOTICE_FAILED   = 'fout';
	public const NOTICE_COMPLETE = 'voltooi';

	/**
	 * Register the redirect hooks. Invoked from {@see Module::register()}.
	 */
	public function register(): void {
		add_action( 'wp_login_failed', array( $this, 'loginFailed' ), 10, 1 );
		add_action( 'lost_password', array( $this, 'lostPasswordFailed' ), 10, 1 );
		// Priority 1: must run before BuddyPress's own `login_form_register`
		// handler (default priority 10) so ITS screen never gets a chance to
		// render for a POST — see the class docblock.
		add_action( 'login_form_register', array( $this, 'registerSubmission' ), 1 );
	}

	/**
	 * A failed login attempt (`wp_login_failed`) — redirect back to
	 * `/meld-aan/` with a notice, preserving the attempted username so the
	 * visitor does not have to retype it (WordPress core's own raw screen does
	 * the same).
	 *
	 * @param string $username The attempted (unsanitised) username/e-mail.
	 */
	public function loginFailed( string $username ): void {
		$url = add_query_arg( self::LOGIN_NOTICE_ARG, self::NOTICE_FAILED, home_url( self::LOGIN_URL_PATH ) );
		$url = add_query_arg( 'log', rawurlencode( $username ), $url );

		$this->redirect( $url );
	}

	/**
	 * A failed lost-password submission (`lost_password` fires with the
	 * resulting `WP_Error`, empty when the submission was valid — see
	 * `wp-login.php`'s own `lostpassword`/`retrievepassword` case). The
	 * SUCCESS path needs no hook here: the theme's own hidden `redirect_to`
	 * field already lands it back on `/wagwoord-herstel/` via WordPress
	 * core's own `redirect_to`-respecting behaviour.
	 *
	 * @param WP_Error $errors The lost-password errors (possibly empty).
	 */
	public function lostPasswordFailed( WP_Error $errors ): void {
		if ( ! $errors->has_errors() ) {
			return;
		}

		$this->redirect(
			add_query_arg( self::LOST_NOTICE_ARG, self::NOTICE_FAILED, home_url( self::LOST_URL_PATH ) )
		);
	}

	/**
	 * Pre-empt BuddyPress's `login_form_register` handler on a POST: call
	 * WordPress core's OWN `register_new_user()` (still "auth is used, never
	 * reimplemented" — {@see \Ink\Accounts\RegistrationGuard}'s
	 * `registration_errors` filter still runs, since that filter fires INSIDE
	 * `register_new_user()` regardless of the caller) and redirect to
	 * `/registreer/` with a notice either way. A GET request (the page simply
	 * loading) is left alone — BuddyPress's own directory-page hijack for
	 * THAT case is already closed at the routing level, see
	 * `Ink\Social\AuthPageRelease`.
	 */
	public function registerSubmission(): void {
		if ( 'POST' !== ( $_SERVER['REQUEST_METHOD'] ?? '' ) ) {
			return;
		}

		$user_login = isset( $_POST['user_login'] ) && is_string( $_POST['user_login'] ) ? wp_unslash( $_POST['user_login'] ) : '';
		$user_email = isset( $_POST['user_email'] ) && is_string( $_POST['user_email'] ) ? wp_unslash( $_POST['user_email'] ) : '';

		$errors = register_new_user( $user_login, $user_email );

		if ( is_wp_error( $errors ) ) {
			$this->redirect(
				add_query_arg( self::REGISTER_NOTICE_ARG, self::NOTICE_FAILED, home_url( self::REGISTER_URL_PATH ) )
			);
			return;
		}

		$redirect_to = ! empty( $_POST['redirect_to'] ) && is_string( $_POST['redirect_to'] )
			? wp_unslash( $_POST['redirect_to'] )
			: add_query_arg( self::REGISTER_NOTICE_ARG, self::NOTICE_COMPLETE, home_url( self::REGISTER_URL_PATH ) );

		$this->redirect( $redirect_to );
	}

	/**
	 * Redirect to a local URL and end the request. Seam: tests override
	 * {@see halt()} (mirrors `Ink\Forms\ContactForm`'s own redirect/halt seam).
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

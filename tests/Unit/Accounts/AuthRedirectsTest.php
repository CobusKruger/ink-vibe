<?php
/**
 * Unit tests for the auth-flow redirect glue (Epic 19 auth fidelity pass).
 *
 * Target: {@see \Ink\Accounts\AuthRedirects}. Confirms each hook redirects to
 * the theme's own page with the right notice slug (or is a clean no-op on the
 * WordPress-native happy path), and that {@see AuthRedirects::registerSubmission()}
 * pre-empts BuddyPress's `login_form_register` handler correctly: it still
 * calls WordPress core's OWN `register_new_user()` — auth stays used, never
 * reimplemented — but decides the redirect itself.
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Accounts;

use Ink\Accounts\AuthRedirects;
use Brain\Monkey;
use Brain\Monkey\Functions;
use WP_Error;

beforeEach( function (): void {
	Monkey\setUp();
	Functions\when( 'home_url' )->alias( static fn ( string $path = '' ): string => 'https://ink.test' . $path );
	Functions\when( 'add_query_arg' )->alias(
		static function ( string $key, string $value, string $url ): string {
			$separator = false === strpos( $url, '?' ) ? '?' : '&';
			return $url . $separator . $key . '=' . $value;
		}
	);
	Functions\when( 'wp_unslash' )->returnArg( 1 );
} );

afterEach( function (): void {
	Monkey\tearDown();
} );

/**
 * A halt-overridden subclass so a redirect doesn't actually `exit` the test
 * process — mirrors the seam-override testing shape used elsewhere for
 * redirect/halt collaborators in this codebase.
 */
function ink_auth_redirects_testable(): AuthRedirects {
	return new class() extends AuthRedirects {
		public bool $halted = false;

		protected function halt(): void {
			$this->halted = true;
		}
	};
}

// --- loginFailed() ---

test( 'loginFailed redirects to meld-aan with the failed notice and preserves the username', function (): void {
	Functions\expect( 'wp_safe_redirect' )
		->once()
		->with( 'https://ink.test/meld-aan/?meld_aan=fout&log=toets%40ink.test' );

	$sut = ink_auth_redirects_testable();
	$sut->loginFailed( 'toets@ink.test' );

	expect( $sut->halted )->toBeTrue();
} );

// --- lostPasswordFailed() ---

test( 'lostPasswordFailed redirects to wagwoord-herstel with the failed notice when there are real errors', function (): void {
	Functions\expect( 'wp_safe_redirect' )
		->once()
		->with( 'https://ink.test/wagwoord-herstel/?wagwoord=fout' );

	$errors = new WP_Error( 'invalid_email', 'Unknown email address.' );

	$sut = ink_auth_redirects_testable();
	$sut->lostPasswordFailed( $errors );

	expect( $sut->halted )->toBeTrue();
} );

test( 'lostPasswordFailed is a no-op (no redirect) when the WP_Error carries no actual errors', function (): void {
	Functions\expect( 'wp_safe_redirect' )->never();

	$sut = ink_auth_redirects_testable();
	$sut->lostPasswordFailed( new WP_Error() );

	expect( $sut->halted )->toBeFalse();
} );

// --- registerSubmission() ---

test( 'registerSubmission is a no-op on a GET request (BuddyPress/WP handle page rendering normally)', function (): void {
	$_SERVER['REQUEST_METHOD'] = 'GET';

	Functions\expect( 'register_new_user' )->never();
	Functions\expect( 'wp_safe_redirect' )->never();

	$sut = ink_auth_redirects_testable();
	$sut->registerSubmission();

	expect( $sut->halted )->toBeFalse();
} );

test( 'registerSubmission redirects to registreer with the failed notice when register_new_user() errors', function (): void {
	$_SERVER['REQUEST_METHOD'] = 'POST';
	$_POST['user_login']       = 'nuwelid';
	$_POST['user_email']       = 'nuwelid@ink.test';

	Functions\expect( 'register_new_user' )
		->once()
		->with( 'nuwelid', 'nuwelid@ink.test' )
		->andReturn( new WP_Error( 'existing_user_login', 'Sorry, that username already exists!' ) );

	Functions\expect( 'wp_safe_redirect' )
		->once()
		->with( 'https://ink.test/registreer/?registreer=fout' );

	$sut = ink_auth_redirects_testable();
	$sut->registerSubmission();

	expect( $sut->halted )->toBeTrue();

	unset( $_POST['user_login'], $_POST['user_email'] );
} );

test( 'registerSubmission redirects to registreer with the complete notice on success, when no redirect_to was posted', function (): void {
	$_SERVER['REQUEST_METHOD'] = 'POST';
	$_POST['user_login']       = 'nuwelid';
	$_POST['user_email']       = 'nuwelid@ink.test';

	Functions\expect( 'register_new_user' )->once()->andReturn( 42 );

	Functions\expect( 'wp_safe_redirect' )
		->once()
		->with( 'https://ink.test/registreer/?registreer=voltooi' );

	$sut = ink_auth_redirects_testable();
	$sut->registerSubmission();

	expect( $sut->halted )->toBeTrue();

	unset( $_POST['user_login'], $_POST['user_email'] );
} );

test( 'registerSubmission honours a posted redirect_to on success (non-vacuous — differs from the default)', function (): void {
	$_SERVER['REQUEST_METHOD'] = 'POST';
	$_POST['user_login']       = 'nuwelid';
	$_POST['user_email']       = 'nuwelid@ink.test';
	$_POST['redirect_to']      = 'https://ink.test/welkom/';

	Functions\expect( 'register_new_user' )->once()->andReturn( 42 );

	Functions\expect( 'wp_safe_redirect' )
		->once()
		->with( 'https://ink.test/welkom/' );

	$sut = ink_auth_redirects_testable();
	$sut->registerSubmission();

	expect( $sut->halted )->toBeTrue();

	unset( $_POST['user_login'], $_POST['user_email'], $_POST['redirect_to'] );
} );

<?php
/**
 * Unit tests for the admin-language forcing mechanism (§14.14).
 *
 * Target: {@see \Ink\Kernel\I18n::forceStaffAdminLocale()} (Story 1.10).
 *
 * WIRED IN STORY 1.11. Authored ready-to-run by Story 1.10; Story 1.11 stood up
 * the foundational harness (Pest function API + Brain Monkey/WP_Mock, the
 * `tests/bootstrap.php` Brain Monkey lifecycle + `WP_User` double, `phpunit.xml`
 * Unit testsuite) and relocated this file to the repo-root `tests/` tree
 * (architecture.md lines 851, 963-966 — tests live at the repo root; this
 * supersedes the placeholder plugin-local `tests/` location Story 1.10 used
 * while the harness did not yet exist). Mirrors the 1.8 CommentsTest precedent.
 *
 * Harness assumptions (provided by tests/bootstrap.php):
 *  - Brain\Monkey is set up/torn down per test (beforeEach -> Monkey\setUp()).
 *  - `is_admin()`, `get_userdata()`, `user_can()` are stubbed per test via
 *    Brain\Monkey\Functions\when()/expect().
 *  - The `WP_User` class is available (a light test double registered in
 *    tests/bootstrap.php / tests/stubs/class-wp-user.php), so
 *    `Mockery::mock( \WP_User::class )` resolves without loading WordPress.
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Kernel;

use Ink\Kernel\I18n;
use Brain\Monkey;
use Brain\Monkey\Functions;

beforeEach( function (): void {
	Monkey\setUp();
} );

afterEach( function (): void {
	Monkey\tearDown();
} );

/**
 * AC-2: an editor/administrator (has `edit_others_posts`) in wp-admin is forced
 * to the English admin locale (`en_US`).
 */
test( 'forceStaffAdminLocale returns en_US for staff in admin context', function (): void {
	Functions\when( 'is_admin' )->justReturn( true );

	$staff = \Mockery::mock( \WP_User::class );
	Functions\when( 'get_userdata' )->justReturn( $staff );
	Functions\when( 'user_can' )->justReturn( true ); // edit_others_posts === staff

	expect( I18n::forceStaffAdminLocale( 'af', 7 ) )->toBe( I18n::ADMIN_LOCALE );
	expect( I18n::ADMIN_LOCALE )->toBe( 'en_US' );
} );

/**
 * AC-2: a non-staff user (member/subscriber — lacks `edit_others_posts`) in
 * wp-admin keeps the incoming locale (`af`) — only staff are forced to English.
 */
test( 'forceStaffAdminLocale leaves non-staff users unchanged in admin context', function (): void {
	Functions\when( 'is_admin' )->justReturn( true );

	$member = \Mockery::mock( \WP_User::class );
	Functions\when( 'get_userdata' )->justReturn( $member );
	Functions\when( 'user_can' )->justReturn( false ); // not staff

	expect( I18n::forceStaffAdminLocale( 'af', 42 ) )->toBe( 'af' );
} );

/**
 * AC-2 (load-bearing): OUTSIDE wp-admin the filter is a strict no-op — the
 * front-end/site locale (`af`) is returned unchanged for EVERY user, staff
 * included. This is the guarantee that the front end stays Afrikaans regardless
 * of a staff member's forced-English admin locale.
 */
test( 'forceStaffAdminLocale is a no-op on the front end (front end stays af)', function ( int|\WP_User $user ): void {
	Functions\when( 'is_admin' )->justReturn( false );

	// get_userdata/user_can must not even be reached on the front end, but stub
	// them defensively so the test is robust to refactors.
	Functions\when( 'get_userdata' )->justReturn( false );
	Functions\when( 'user_can' )->justReturn( true );

	expect( I18n::forceStaffAdminLocale( 'af', $user ) )->toBe( 'af' );
} )->with( [
	'staff user id'     => [ 1 ],
	'member user id'    => [ 42 ],
	'anonymous (id 0)'  => [ 0 ],
] );

/**
 * Edge: in admin context with an unresolvable user (`get_userdata` returns
 * false), the incoming locale is preserved rather than forced — no crash, no
 * accidental forcing of an unknown principal.
 */
test( 'forceStaffAdminLocale preserves locale when the user cannot be resolved', function (): void {
	Functions\when( 'is_admin' )->justReturn( true );
	Functions\when( 'get_userdata' )->justReturn( false );

	expect( I18n::forceStaffAdminLocale( 'af', 999 ) )->toBe( 'af' );
} );

<?php
/**
 * Unit tests for the INK social-login availability seam (R6, Story 3.5).
 *
 * Target: {@see \Ink\Accounts\SocialLogin} — the read-only, filter-driven seam
 * the theme bridge consumes to decide whether to render the social-login
 * section. R6 social login is a vetted-plugin seam (architecture.md line
 * 650-652): this class implements NO OAuth and stores no tokens, so the seam's
 * only behaviour is its graceful-degradation default and the filter override.
 *
 * Also re-asserts (AC-4) that a socially-registered account inherits INK's
 * Brons / gratis-lid defaults through the EXISTING `user_register` path
 * ({@see \Ink\Accounts\Registration}) — there is no parallel social setter.
 *
 * Brain Monkey, no WordPress/DB.
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Accounts;

use Ink\Accounts\Registration;
use Ink\Accounts\SocialLogin;
use Ink\Content\UserMeta;
use Ink\Kernel\Tier;
use Brain\Monkey;
use Brain\Monkey\Filters;
use Brain\Monkey\Functions;

beforeEach( function (): void {
	Monkey\setUp();
} );

afterEach( function (): void {
	Monkey\tearDown();
} );

/**
 * AC-1/AC-5: the seam contract names are the exact `ink_`-prefixed hooks.
 */
test( 'the seam filter and action constants are the exact prefixed hooks', function (): void {
	expect( SocialLogin::AVAILABLE_FILTER )->toBe( 'ink_social_login_available' );
	expect( SocialLogin::BUTTONS_ACTION )->toBe( 'ink_social_login_buttons' );
} );

/**
 * AC-1: graceful degradation — with no plugin (no filter override) social login
 * is unavailable, so the auth surface renders no social section and never errors.
 */
test( 'isAvailable defaults to false (graceful degradation, no plugin)', function (): void {
	// Brain Monkey's default apply_filters returns the passed default (false).
	expect( SocialLogin::isAvailable() )->toBeFalse();
} );

/**
 * AC-1: a vetted plugin (or deploy-time glue) flips the filter true to announce
 * availability — then the seam reports true.
 */
test( 'isAvailable returns true when the ink_social_login_available filter is set', function (): void {
	Filters\expectApplied( SocialLogin::AVAILABLE_FILTER )
		->once()
		->andReturn( true );

	expect( SocialLogin::isAvailable() )->toBeTrue();
} );

/**
 * AC-4/AC-5 + THE conflation rule: a social signup inherits INK's Brons /
 * gratis-lid default through the EXISTING `Registration::applyDefaults` path —
 * there is no parallel social setter, and the default write touches ONLY the
 * writer tier (no entitlement / membership / intent).
 */
test( 'a social signup inherits Brons via the existing default path, nothing else', function (): void {
	$written = array();

	Functions\when( 'get_user_meta' )->justReturn( '' );
	Functions\when( 'update_user_meta' )->alias(
		function ( $user_id, $key, $value ) use ( &$written ): bool {
			$written[ $key ] = $value;
			return true;
		}
	);

	// A socially-registered user flows through the same user_register reaction.
	( new Registration() )->applyDefaults( 88 );

	expect( $written )->toBe( array( UserMeta::WRITER_TIER => Tier::Brons->value ) );
	expect( $written )->not->toHaveKey( 'ink_writer_intent' );
	expect( $written )->not->toHaveKey( 'ink_membership' );
	expect( $written )->not->toHaveKey( 'ink_entitlement' );
} );

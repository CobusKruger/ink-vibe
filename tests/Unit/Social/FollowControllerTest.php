<?php
/**
 * Unit tests for the follow REST controller (Story 9.2, FR-38, AD-6).
 *
 * Pure `validate()` + `permission()` plus the conflation guardrail over the
 * follow controller, store, toggle and counts (no Tiers/Entitlement — following
 * is open to any lid, never entitlement- or tier-gated).
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Social;

use Ink\Social\FollowController;
use Ink\Tests\Support\CodeScan;
use Brain\Monkey;
use Brain\Monkey\Functions;

beforeEach( function (): void {
	Monkey\setUp();
} );

afterEach( function (): void {
	Monkey\tearDown();
} );

test( 'permission allows any logged-in lid (not entitlement-gated)', function (): void {
	Functions\when( 'is_user_logged_in' )->justReturn( true );
	expect( ( new FollowController() )->permission() )->toBeTrue();

	Functions\when( 'is_user_logged_in' )->justReturn( false );
	expect( ( new FollowController() )->permission() )->toBeFalse();
} );

test( 'validate rejects self-follow with the self code', function (): void {
	$error = FollowController::validate( true, true );
	expect( $error )->toBeInstanceOf( \WP_Error::class );
	expect( $error->get_error_code() )->toBe( 'ink_volg_self' );
} );

test( 'validate rejects a non-user target with the invalid-target code', function (): void {
	$error = FollowController::validate( false, false );
	expect( $error )->toBeInstanceOf( \WP_Error::class );
	expect( $error->get_error_code() )->toBe( 'ink_volg_invalid_target' );
} );

test( 'validate passes a real, distinct target', function (): void {
	expect( FollowController::validate( true, false ) )->toBeNull();
} );

test( 'the follow controller, store, toggle and counts are conflation-clean (no Tiers / Entitlement)', function (): void {
	$root  = dirname( __DIR__, 3 ) . '/wp-content/plugins/ink-core/src/Social/';
	$files = array(
		$root . 'FollowController.php',
		$root . 'FollowStore.php',
		$root . 'FollowToggle.php',
		$root . 'FollowCounts.php',
		$root . 'FollowingFeed.php',
	);

	$scanned = 0;
	foreach ( $files as $file ) {
		$code = CodeScan::withoutComments( $file );
		expect( $code )->toContain( 'class ' );
		++$scanned;
		expect( $code )->not->toContain( 'Ink\\Tiers' );
		expect( $code )->not->toContain( 'Ink\\Entitlement' );
	}

	expect( $scanned )->toBe( 5 );
} );

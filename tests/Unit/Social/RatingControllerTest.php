<?php
/**
 * Unit tests for the reader-rating REST controller (Story 9.6, FR-42, AD-6).
 *
 * Pure `validate()` + `permission()` — self / non-user / out-of-range score
 * rejection, valid third-party 1–5 pass.
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Social;

use Ink\Social\RatingController;
use Brain\Monkey;
use Brain\Monkey\Functions;

beforeEach( function (): void {
	Monkey\setUp();
} );

afterEach( function (): void {
	Monkey\tearDown();
} );

test( 'permission allows any logged-in lid', function (): void {
	Functions\when( 'is_user_logged_in' )->justReturn( true );
	expect( ( new RatingController() )->permission() )->toBeTrue();

	Functions\when( 'is_user_logged_in' )->justReturn( false );
	expect( ( new RatingController() )->permission() )->toBeFalse();
} );

test( 'validate rejects rating yourself', function (): void {
	$error = RatingController::validate( true, true, 5 );
	expect( $error )->toBeInstanceOf( \WP_Error::class );
	expect( $error->get_error_code() )->toBe( 'ink_oordeel_self' );
} );

test( 'validate rejects a non-user target', function (): void {
	$error = RatingController::validate( false, false, 5 );
	expect( $error )->toBeInstanceOf( \WP_Error::class );
	expect( $error->get_error_code() )->toBe( 'ink_oordeel_invalid_target' );
} );

test( 'validate rejects a score outside 1-5', function (): void {
	expect( RatingController::validate( true, false, 0 )->get_error_code() )->toBe( 'ink_oordeel_score' );
	expect( RatingController::validate( true, false, 6 )->get_error_code() )->toBe( 'ink_oordeel_score' );
} );

test( 'validate passes a valid 1-5 third-party rating', function (): void {
	expect( RatingController::validate( true, false, 1 ) )->toBeNull();
	expect( RatingController::validate( true, false, 5 ) )->toBeNull();
} );

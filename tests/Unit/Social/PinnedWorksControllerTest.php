<?php
/**
 * Unit tests for the pinned-works REST controller (Story 9.5, FR-41, AD-6).
 *
 * Pure `validate()` + `permission()`. The ownership authorisation (own +
 * published + readable bydrae) is the gate.
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Social;

use Ink\Social\PinnedWorksController;
use Brain\Monkey;
use Brain\Monkey\Functions;

beforeEach( function (): void {
	Monkey\setUp();
} );

afterEach( function (): void {
	Monkey\tearDown();
} );

test( 'permission allows any logged-in lid (ownership enforced in the handler)', function (): void {
	Functions\when( 'is_user_logged_in' )->justReturn( true );
	expect( ( new PinnedWorksController() )->permission() )->toBeTrue();

	Functions\when( 'is_user_logged_in' )->justReturn( false );
	expect( ( new PinnedWorksController() )->permission() )->toBeFalse();
} );

test( 'validate rejects a work that is not the writer own published bydrae', function (): void {
	$error = PinnedWorksController::validate( false );
	expect( $error )->toBeInstanceOf( \WP_Error::class );
	expect( $error->get_error_code() )->toBe( 'ink_vasgespel_invalid' );
} );

test( 'validate passes the writer own published readable bydrae', function (): void {
	expect( PinnedWorksController::validate( true ) )->toBeNull();
} );

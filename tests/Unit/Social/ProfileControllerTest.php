<?php
/**
 * Unit tests for the My Profiel identity-strip edit REST controller (§5.1).
 *
 * Target: {@see \Ink\Social\ProfileController}. `permission()` is a trivial
 * logged-in check — "can't edit someone else's profile" is a STRUCTURAL
 * guarantee here (no request shape carries a target-user param at all; the
 * handler only ever writes to `get_current_user_id()`), verified below by
 * asserting every write always targets the current user regardless of what a
 * caller submits. `validate()` is pure; `handleUpdate()`'s per-field update
 * paths and partial updates are exercised directly via the `WP_REST_Request`
 * unit-suite stub.
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Social;

use Ink\Social\ProfileController;
use Brain\Monkey;
use Brain\Monkey\Functions;

beforeEach( function (): void {
	Monkey\setUp();
	Functions\when( 'sanitize_text_field' )->returnArg( 1 );
	Functions\when( 'sanitize_textarea_field' )->returnArg( 1 );
	Functions\when( 'wp_unslash' )->returnArg( 1 );
	Functions\when( 'get_current_user_id' )->justReturn( 7 );
} );

afterEach( function (): void {
	Monkey\tearDown();
} );

test( 'permission allows any logged-in lid', function (): void {
	Functions\when( 'is_user_logged_in' )->justReturn( true );
	expect( ( new ProfileController() )->permission() )->toBeTrue();

	Functions\when( 'is_user_logged_in' )->justReturn( false );
	expect( ( new ProfileController() )->permission() )->toBeFalse();
} );

test( 'validate rejects a submission with no editable field at all', function (): void {
	$error = ProfileController::validate( false );
	expect( $error )->toBeInstanceOf( \WP_Error::class );
	expect( $error->get_error_code() )->toBe( 'ink_profiel_leeg' );
} );

test( 'validate passes a submission with at least one editable field', function (): void {
	expect( ProfileController::validate( true ) )->toBeNull();
} );

test( 'handleUpdate updates only display_name when it is the only field submitted', function (): void {
	$captured_user_update = null;
	Functions\when( 'wp_update_user' )->alias(
		function ( array $userdata ) use ( &$captured_user_update ) {
			$captured_user_update = $userdata;
		}
	);
	$meta_calls = array();
	Functions\when( 'update_user_meta' )->alias(
		function ( int $user_id, string $key, $value ) use ( &$meta_calls ): bool {
			$meta_calls[] = array( $user_id, $key, $value );

			return true;
		}
	);

	$response = ( new ProfileController() )->handleUpdate( new \WP_REST_Request( array( 'display_name' => 'Nuwe Naam' ) ) );

	expect( $captured_user_update )->toBe(
		array(
			'ID'           => 7,
			'display_name' => 'Nuwe Naam',
		)
	);
	expect( $meta_calls )->toBe( array() ); // tagline + bio untouched.
	expect( $response->get_data() )->toBe( array( 'display_name' => 'Nuwe Naam' ) );
} );

test( 'handleUpdate updates only tagline when it is the only field submitted', function (): void {
	Functions\when( 'wp_update_user' )->alias(
		function () {
			throw new \RuntimeException( 'display_name must not be touched' );
		}
	);
	$meta_calls = array();
	Functions\when( 'update_user_meta' )->alias(
		function ( int $user_id, string $key, $value ) use ( &$meta_calls ): bool {
			$meta_calls[] = array( $user_id, $key, $value );

			return true;
		}
	);

	$response = ( new ProfileController() )->handleUpdate( new \WP_REST_Request( array( 'tagline' => 'Skryf om te lewe.' ) ) );

	expect( $meta_calls )->toBe( array( array( 7, 'ink_skrywer_leuse', 'Skryf om te lewe.' ) ) );
	expect( $response->get_data() )->toBe( array( 'tagline' => 'Skryf om te lewe.' ) );
} );

test( 'handleUpdate updates only bio when it is the only field submitted', function (): void {
	Functions\when( 'wp_update_user' )->alias(
		function () {
			throw new \RuntimeException( 'display_name must not be touched' );
		}
	);
	$meta_calls = array();
	Functions\when( 'update_user_meta' )->alias(
		function ( int $user_id, string $key, $value ) use ( &$meta_calls ): bool {
			$meta_calls[] = array( $user_id, $key, $value );

			return true;
		}
	);

	$response = ( new ProfileController() )->handleUpdate( new \WP_REST_Request( array( 'bio' => 'Oor my as skrywer.' ) ) );

	expect( $meta_calls )->toBe( array( array( 7, 'description', 'Oor my as skrywer.' ) ) );
	expect( $response->get_data() )->toBe( array( 'bio' => 'Oor my as skrywer.' ) );
} );

test( 'handleUpdate updates all three fields when all are submitted, always against the current user', function (): void {
	Functions\when( 'get_current_user_id' )->justReturn( 99 );

	$captured_user_update = null;
	Functions\when( 'wp_update_user' )->alias(
		function ( array $userdata ) use ( &$captured_user_update ) {
			$captured_user_update = $userdata;
		}
	);
	$meta_calls = array();
	Functions\when( 'update_user_meta' )->alias(
		function ( int $user_id, string $key, $value ) use ( &$meta_calls ): bool {
			$meta_calls[] = array( $user_id, $key, $value );

			return true;
		}
	);

	$response = ( new ProfileController() )->handleUpdate(
		new \WP_REST_Request(
			array(
				'display_name' => 'Nuwe Naam',
				'tagline'      => 'Leuse',
				'bio'          => 'Bio',
			)
		)
	);

	// Every write targets the CURRENT user (99) — there is no request field that
	// could ever redirect a write to a different user id.
	expect( $captured_user_update['ID'] )->toBe( 99 );
	expect( $meta_calls )->toBe(
		array(
			array( 99, 'ink_skrywer_leuse', 'Leuse' ),
			array( 99, 'description', 'Bio' ),
		)
	);
	expect( $response->get_data() )->toBe(
		array(
			'display_name' => 'Nuwe Naam',
			'tagline'      => 'Leuse',
			'bio'          => 'Bio',
		)
	);
} );

test( 'handleUpdate returns a WP_Error and writes nothing when no field is submitted', function (): void {
	Functions\when( 'wp_update_user' )->alias(
		function () {
			throw new \RuntimeException( 'must not be called' );
		}
	);
	Functions\when( 'update_user_meta' )->alias(
		function () {
			throw new \RuntimeException( 'must not be called' );
		}
	);

	$response = ( new ProfileController() )->handleUpdate( new \WP_REST_Request( array() ) );

	expect( $response )->toBeInstanceOf( \WP_Error::class );
	expect( $response->get_error_code() )->toBe( 'ink_profiel_leeg' );
} );

<?php
/**
 * Unit tests for the My Profiel identity-strip tagline user meta (§5.1).
 *
 * Target: {@see \Ink\Social\Tagline}. `register()` wires the meta directly
 * (no nested `add_action('init', …)`); `get()`/`set()`/`sanitize()` are the
 * thin read/write + pure sanitize surface.
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Social;

use Ink\Social\Tagline;
use Brain\Monkey;
use Brain\Monkey\Functions;

beforeEach( function (): void {
	Monkey\setUp();
} );

afterEach( function (): void {
	Monkey\tearDown();
} );

test( 'register() registers the user meta with the expected shape', function (): void {
	$captured = array();

	Functions\when( 'register_meta' )->alias(
		function ( string $object_type, string $key, array $args ) use ( &$captured ): void {
			$captured['object_type'] = $object_type;
			$captured['key']         = $key;
			$captured['args']        = $args;
		}
	);

	( new Tagline() )->register();

	expect( $captured['object_type'] )->toBe( 'user' );
	expect( $captured['key'] )->toBe( Tagline::META );
	expect( $captured['args']['type'] )->toBe( 'string' );
	expect( $captured['args']['default'] )->toBe( '' );
	expect( $captured['args']['sanitize_callback'] )->toBe( 'sanitize_text_field' );
} );

test( 'get() returns the stored tagline', function (): void {
	Functions\when( 'get_user_meta' )->justReturn( 'Skryf om te lewe.' );

	expect( Tagline::get( 42 ) )->toBe( 'Skryf om te lewe.' );
} );

test( 'get() returns an empty string when unset', function (): void {
	Functions\when( 'get_user_meta' )->justReturn( '' );

	expect( Tagline::get( 42 ) )->toBe( '' );
} );

test( 'set() sanitizes and persists the tagline', function (): void {
	Functions\when( 'sanitize_text_field' )->alias( static fn ( string $s ): string => trim( $s ) );

	$captured = array();
	Functions\when( 'update_user_meta' )->alias(
		function ( int $user_id, string $key, $value ) use ( &$captured ): bool {
			$captured = array( $user_id, $key, $value );

			return true;
		}
	);

	expect( Tagline::set( 42, '  Skryf om te lewe.  ' ) )->toBeTrue();
	expect( $captured )->toBe( array( 42, Tagline::META, 'Skryf om te lewe.' ) );
} );

test( 'set() returns false when the underlying update fails', function (): void {
	Functions\when( 'sanitize_text_field' )->returnArg( 1 );
	Functions\when( 'update_user_meta' )->justReturn( false );

	expect( Tagline::set( 42, 'x' ) )->toBeFalse();
} );

test( 'sanitize() trims via sanitize_text_field and caps at MAX_LENGTH', function (): void {
	Functions\when( 'sanitize_text_field' )->returnArg( 1 );

	$long = str_repeat( 'a', Tagline::MAX_LENGTH + 20 );

	expect( Tagline::sanitize( $long ) )->toHaveLength( Tagline::MAX_LENGTH );
	expect( Tagline::sanitize( 'kort' ) )->toBe( 'kort' );
} );

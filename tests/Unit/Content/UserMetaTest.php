<?php
/**
 * Unit tests for the INK writer-tier user-meta registrar (AD-1, AD-5, AD-6).
 *
 * Target: {@see \Ink\Content\UserMeta} and the {@see \Ink\Content\Api} facade
 * (Story 2.3).
 *
 * Authored ready-to-run; the runner (Pest function API + Brain Monkey, the
 * `tests/bootstrap.php` lifecycle, `phpunit.xml` Unit testsuite) is the 1.11
 * scaffold built out in the 18.8 CI buildout. Mirrors the 2.1/2.2 precedents.
 *
 * Harness assumptions (provided by tests/bootstrap.php):
 *  - Brain\Monkey is set up/torn down per test.
 *  - `register_meta()` is aliased to capture every (object_type, key, args) call
 *    so the registration can be asserted without WordPress loaded.
 *  - The real `Ink\Kernel\Tier` enum is autoloaded, so the captured
 *    `sanitize_callback` runs for real (no WP needed for the default/coercion).
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Content;

use Ink\Content\Api;
use Ink\Content\UserMeta;
use Brain\Monkey;
use Brain\Monkey\Functions;

/**
 * Register every meta key with `register_meta` captured, returning the
 * key => [object_type, args] map the registrar produced.
 *
 * @return array<string, array{object_type: string, args: array<string, mixed>}>
 */
function ink_capture_registered_user_meta(): array {
	$captured = array();

	Functions\when( 'register_meta' )->alias(
		function ( string $object_type, string $key, array $args ) use ( &$captured ): void {
			$captured[ $key ] = array(
				'object_type' => $object_type,
				'args'        => $args,
			);
		}
	);

	( new UserMeta() )->register();

	return $captured;
}

beforeEach( function (): void {
	Monkey\setUp();
} );

afterEach( function (): void {
	Monkey\tearDown();
} );

/**
 * AC-1: exactly the two writer-tier keys register; `ink_tier_win_count` is NOT
 * registered here (it is Story 5.7).
 */
test( 'keys() exposes exactly the two writer-tier meta keys', function (): void {
	expect( UserMeta::keys() )->toBe( array(
		'ink_writer_tier',
		'ink_tier_promoted_at',
	) );
	expect( UserMeta::keys() )->not->toContain( 'ink_tier_win_count' );
} );

/**
 * AC-1: the key constants carry the exact `ink_`-prefixed IDs.
 */
test( 'the meta-key constants are the exact prefixed IDs', function (): void {
	expect( UserMeta::WRITER_TIER )->toBe( 'ink_writer_tier' );
	expect( UserMeta::TIER_PROMOTED_AT )->toBe( 'ink_tier_promoted_at' );
} );

/**
 * AC-1/AC-2: both keys register against the `user` object type, win_count absent.
 */
test( 'register() registers both keys against the user object type', function (): void {
	$registered = ink_capture_registered_user_meta();

	expect( array_keys( $registered ) )->toBe( UserMeta::keys() );
	expect( $registered )->not->toHaveKey( 'ink_tier_win_count' );

	foreach ( $registered as $key => $entry ) {
		expect( $entry['object_type'] )->toBe( 'user' );
	}
} );

/**
 * AC-1: `ink_writer_tier` defaults to `brons` (from the Kernel Tier enum).
 */
test( 'ink_writer_tier defaults to brons', function (): void {
	$registered = ink_capture_registered_user_meta();

	expect( $registered['ink_writer_tier']['args']['default'] )->toBe( 'brons' );
	expect( $registered['ink_tier_promoted_at']['args']['default'] )->toBe( '' );
} );

/**
 * AC-3: both keys are single + REST-aware.
 */
test( 'both keys are single and show_in_rest', function (): void {
	$registered = ink_capture_registered_user_meta();

	foreach ( $registered as $key => $entry ) {
		expect( $entry['args']['single'] )->toBeTrue();
		expect( $entry['args']['show_in_rest'] )->toBeTrue();
		expect( $entry['args']['type'] )->toBe( 'string' );
		expect( $entry['args'] )->toHaveKey( 'auth_callback' );
		expect( $entry['args'] )->toHaveKey( 'sanitize_callback' );
	}
} );

/**
 * AC-3: the `ink_writer_tier` sanitize_callback coerces any junk value back to the
 * `brons` default and lets a valid grade through — an invalid grade cannot persist.
 */
test( 'the writer-tier sanitize callback coerces to a valid grade', function (): void {
	$registered = ink_capture_registered_user_meta();
	$sanitize   = $registered['ink_writer_tier']['args']['sanitize_callback'];

	expect( $sanitize( 'goud' ) )->toBe( 'goud' );
	expect( $sanitize( 'meester' ) )->toBe( 'meester' );
	expect( $sanitize( 'rubbish' ) )->toBe( 'brons' );
	expect( $sanitize( '' ) )->toBe( 'brons' );
} );

/**
 * AC-4: the Content facade exposes the meta-key surface, delegating to UserMeta.
 */
test( 'Api facade exposes the user-meta key surface', function (): void {
	expect( Api::userMetaKeys() )->toBe( UserMeta::keys() );
} );

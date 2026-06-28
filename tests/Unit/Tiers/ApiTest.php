<?php
/**
 * Unit tests for the Tiers module facade typed accessor (Story 5.1).
 *
 * Target: {@see \Ink\Tiers\Api::forUser()} — the typed, default-safe read path
 * that guarantees the Brons default for unset/empty/junk users (closes the
 * Epic-2 review deferral on Story 2.3: a raw `get_user_meta` of an unset user
 * returns `''`, not `brons`).
 *
 * Harness: Brain Monkey mocks `get_user_meta`; the real `Ink\Kernel\Tier` enum
 * is autoloaded so the coercion runs for real. Mirrors the UserMetaTest style.
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Tiers;

use Ink\Kernel\Tier;
use Ink\Tiers\Api;
use Brain\Monkey;
use Brain\Monkey\Functions;

beforeEach( function (): void {
	Monkey\setUp();
} );

afterEach( function (): void {
	Monkey\tearDown();
} );

/**
 * AC-2: an unset writer (raw `get_user_meta` returns `''`) reads as Brons —
 * the default the raw meta read does NOT provide.
 */
test( 'forUser() returns Brons when the meta is unset (empty string)', function (): void {
	Functions\when( 'get_user_meta' )->justReturn( '' );

	expect( Api::forUser( 42 ) )->toBe( Tier::Brons );
} );

/**
 * AC-2: a non-scalar / falsy meta value (e.g. WP returns `false` or array) also
 * falls back to the default — never `null`, never a raw value.
 */
test( 'forUser() returns Brons for a non-scalar meta value', function (): void {
	Functions\when( 'get_user_meta' )->justReturn( array() );

	expect( Api::forUser( 42 ) )->toBe( Tier::Brons );
} );

/**
 * AC-2: an unrecognised stored string (typo / stale value) is coerced back to
 * the Brons default — an invalid grade can never surface.
 */
test( 'forUser() coerces an unrecognised grade back to Brons', function (): void {
	Functions\when( 'get_user_meta' )->justReturn( 'platinum' );

	expect( Api::forUser( 42 ) )->toBe( Tier::Brons );
} );

/**
 * AC-2: each valid stored grade round-trips to its enum case, incl. the
 * manual-only Meester.
 */
test( 'forUser() returns the matching case for each valid stored grade', function ( string $stored, Tier $expected ): void {
	Functions\when( 'get_user_meta' )->justReturn( $stored );

	expect( Api::forUser( 7 ) )->toBe( $expected );
} )->with( array(
	'brons'   => array( 'brons', Tier::Brons ),
	'silwer'  => array( 'silwer', Tier::Silwer ),
	'goud'    => array( 'goud', Tier::Goud ),
	'meester' => array( 'meester', Tier::Meester ),
) );

/**
 * AC-3: the accessor reads the Kernel-owned single-source key (single, by user id).
 */
test( 'forUser() reads the single ink_writer_tier meta by user id', function (): void {
	Functions\expect( 'get_user_meta' )
		->once()
		->with( 7, Tier::META_KEY, true )
		->andReturn( 'goud' );

	expect( Api::forUser( 7 ) )->toBe( Tier::Goud );
} );

/**
 * Story 16.3: the migration baseline-set writes ONLY the grade meta — no
 * promoted_at, no win-count reset, no audit log, no event (a baseline is not a
 * promotion). The sanctioned Tiers-owned tier-write the CSV import routes through.
 */
test( 'importBaselineGrade writes only the grade meta (no promoted_at/win-count/log/event)', function (): void {
	Functions\expect( 'update_user_meta' )
		->once()
		->with( 55, Tier::META_KEY, 'silwer' );

	// Exactly one meta write — promoted_at + win-count are NEVER touched.
	Functions\expect( 'update_user_meta' )
		->never()
		->with( 55, Tier::PROMOTED_AT_META_KEY, \Mockery::any() );
	Functions\expect( 'update_user_meta' )
		->never()
		->with( 55, Tier::WIN_COUNT_META_KEY, \Mockery::any() );

	Api::importBaselineGrade( 55, Tier::Silwer );

	// No promotion event fired (a migration baseline is not an achievement).
	expect( Monkey\Actions\did( 'ink/tier_promoted' ) )->toBe( 0 );
} );

<?php
/**
 * Unit tests for the win-count counter (Story 5.7).
 *
 * Target: {@see \Ink\Tiers\Api::winCountForUser()} + {@see \Ink\Tiers\Api::recordWin()}
 * — the typed read + the dumb accumulator (no threshold logic; that is Story 5.8).
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Tiers;

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
 * AC-2: an unset/junk counter reads as 0.
 */
test( 'winCountForUser is 0 when unset or non-scalar', function (): void {
	Functions\when( 'get_user_meta' )->justReturn( '' );
	expect( Api::winCountForUser( 42 ) )->toBe( 0 );

	Functions\when( 'get_user_meta' )->justReturn( array() );
	expect( Api::winCountForUser( 42 ) )->toBe( 0 );
} );

/**
 * AC-2: a stored value reads as the int.
 */
test( 'winCountForUser returns the stored count as an int', function (): void {
	Functions\when( 'get_user_meta' )->justReturn( '4' );
	expect( Api::winCountForUser( 42 ) )->toBe( 4 );
} );

/**
 * AC-1/AC-2: recordWin accumulates by 1 (default) and returns the new total.
 */
test( 'recordWin adds one by default and returns the new total', function (): void {
	Functions\when( 'get_user_meta' )->justReturn( '4' );
	Functions\expect( 'update_user_meta' )->once()->with( 42, 'ink_tier_win_count', 5 );

	expect( Api::recordWin( 42 ) )->toBe( 5 );
} );

/**
 * AC-1: recordWin can add several wins at once.
 */
test( 'recordWin can add several wins at once', function (): void {
	Functions\when( 'get_user_meta' )->justReturn( '2' );
	Functions\expect( 'update_user_meta' )->once()->with( 7, 'ink_tier_win_count', 5 );

	expect( Api::recordWin( 7, 3 ) )->toBe( 5 );
} );

/**
 * AC-1: a non-positive count never decreases the counter.
 */
test( 'recordWin never decreases the counter', function (): void {
	Functions\when( 'get_user_meta' )->justReturn( '5' );
	Functions\expect( 'update_user_meta' )->once()->with( 7, 'ink_tier_win_count', 5 );

	expect( Api::recordWin( 7, -3 ) )->toBe( 5 );
} );

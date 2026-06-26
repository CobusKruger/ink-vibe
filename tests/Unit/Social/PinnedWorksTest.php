<?php
/**
 * Unit tests for the pinned-works store (Story 9.5, FR-41).
 *
 * Target: {@see \Ink\Social\PinnedWorks}. The pure list transforms (addPin /
 * removePin) carry the cap + dedup + order rules; tested without user-meta.
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Social;

use Ink\Social\PinnedWorks;
use Brain\Monkey;

beforeEach( function (): void {
	Monkey\setUp();
} );

afterEach( function (): void {
	Monkey\tearDown();
} );

test( 'addPin appends a new pin in order', function (): void {
	expect( PinnedWorks::addPin( array( 7 ), 42 ) )->toBe( array( 7, 42 ) );
} );

test( 'addPin dedups an already-pinned work (no change)', function (): void {
	expect( PinnedWorks::addPin( array( 7, 42 ), 42 ) )->toBe( array( 7, 42 ) );
} );

test( 'addPin rejects a pin at the cap rather than evicting (non-vacuous — under cap DOES add)', function (): void {
	$full = range( 1, PinnedWorks::MAX ); // exactly MAX pins

	expect( PinnedWorks::addPin( $full, 999 ) )->toBe( $full ); // rejected at cap

	$under = array( 1, 2 );
	expect( PinnedWorks::addPin( $under, 3 ) )->toBe( array( 1, 2, 3 ) ); // under cap adds
} );

test( 'addPin ignores a non-positive id', function (): void {
	expect( PinnedWorks::addPin( array( 7 ), 0 ) )->toBe( array( 7 ) );
} );

test( 'removePin removes the pin and reindexes (idempotent)', function (): void {
	expect( PinnedWorks::removePin( array( 7, 42, 9 ), 42 ) )->toBe( array( 7, 9 ) );
	expect( PinnedWorks::removePin( array( 7, 9 ), 42 ) )->toBe( array( 7, 9 ) ); // idempotent
} );

test( 'forUser sanitizes, dedups and caps the stored value', function (): void {
	Monkey\Functions\when( 'get_user_meta' )->justReturn( array( '7', '7', '0', 42, '13', 13 ) );

	expect( PinnedWorks::forUser( 5 ) )->toBe( array( 7, 42, 13 ) );
} );

test( 'forUser returns an empty list when nothing is pinned', function (): void {
	Monkey\Functions\when( 'get_user_meta' )->justReturn( '' );

	expect( PinnedWorks::forUser( 5 ) )->toBe( array() );
} );

<?php
/**
 * Unit tests for the monthly challenge cadence helper (Story 12.3, FR-47).
 *
 * Target: {@see \Ink\Challenges\Cadence} — monthly round period derivation
 * (periodKey / periodLabel / monthName, Afrikaans) + the judging-freeze boundary.
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Challenges;

use Ink\Challenges\Cadence;
use Brain\Monkey;

beforeEach( function (): void {
	Monkey\setUp();
} );

afterEach( function (): void {
	Monkey\tearDown();
} );

test( 'monthName returns the Afrikaans month for 1-12 and empty for out-of-range', function (): void {
	expect( Cadence::monthName( 1 ) )->toBe( 'Januarie' );
	expect( Cadence::monthName( 10 ) )->toBe( 'Oktober' );
	expect( Cadence::monthName( 12 ) )->toBe( 'Desember' );
	expect( Cadence::monthName( 0 ) )->toBe( '' );
	expect( Cadence::monthName( 13 ) )->toBe( '' );
} );

test( 'periodKey is the YYYY-MM of the deadline in SAST', function (): void {
	$deadline = new \DateTimeImmutable( '2026-10-31 12:00:00', new \DateTimeZone( 'Africa/Johannesburg' ) );
	expect( Cadence::periodKey( $deadline ) )->toBe( '2026-10' );
} );

test( 'periodKey reads the SAST calendar month, not the UTC one, near midnight', function (): void {
	// 2026-10-31 23:30 SAST == 2026-10-31 21:30 UTC; still October in SAST.
	$deadline = new \DateTimeImmutable( '2026-10-31 21:30:00', new \DateTimeZone( 'UTC' ) );
	expect( Cadence::periodKey( $deadline ) )->toBe( '2026-10' );

	// 2026-10-31 22:30 UTC == 2026-11-01 00:30 SAST; rolls into November in SAST.
	$rolled = new \DateTimeImmutable( '2026-10-31 22:30:00', new \DateTimeZone( 'UTC' ) );
	expect( Cadence::periodKey( $rolled ) )->toBe( '2026-11' );
} );

test( 'periodLabel is the Afrikaans month and year', function (): void {
	$deadline = new \DateTimeImmutable( '2026-10-15 09:00:00', new \DateTimeZone( 'Africa/Johannesburg' ) );
	expect( Cadence::periodLabel( $deadline ) )->toBe( 'Oktober 2026' );
} );

test( 'entriesFrozen is false through the inclusive deadline and true after', function (): void {
	$sast     = new \DateTimeZone( 'Africa/Johannesburg' );
	$deadline = new \DateTimeImmutable( '2026-10-31 00:00:00', $sast );

	$open   = new \DateTimeImmutable( '2026-10-31 23:59:59', $sast );
	$closed = new \DateTimeImmutable( '2026-11-01 00:00:00', $sast );

	expect( Cadence::entriesFrozen( $deadline, $open ) )->toBeFalse();
	expect( Cadence::entriesFrozen( $deadline, $closed ) )->toBeTrue();
} );

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
use Ink\Kernel\CadenceType;
use Ink\Content\FieldSets;
use Ink\Tiers\Api as TiersApi;
use Ink\Kernel\Tier;
use Brain\Monkey;
use Brain\Monkey\Functions;

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

// --- Story 12B.1: annual cadence (R9) ---------------------------------------

test( 'periodKey is the SAST year for the annual cadence', function (): void {
	$deadline = new \DateTimeImmutable( '2026-12-31 12:00:00', new \DateTimeZone( 'Africa/Johannesburg' ) );
	expect( Cadence::periodKey( $deadline, CadenceType::Jaarliks ) )->toBe( '2026' );
} );

test( 'periodKey reads the SAST calendar year, not the UTC one, near year-end midnight', function (): void {
	// 2026-12-31 22:30 UTC == 2027-01-01 00:30 SAST; rolls into 2027 in SAST.
	$rolled = new \DateTimeImmutable( '2026-12-31 22:30:00', new \DateTimeZone( 'UTC' ) );
	expect( Cadence::periodKey( $rolled, CadenceType::Jaarliks ) )->toBe( '2027' );
} );

test( 'periodLabel is the bare year for the annual cadence (no new copy)', function (): void {
	$deadline = new \DateTimeImmutable( '2026-12-15 09:00:00', new \DateTimeZone( 'Africa/Johannesburg' ) );
	expect( Cadence::periodLabel( $deadline, CadenceType::Jaarliks ) )->toBe( '2026' );
} );

test( 'the cadence argument changes the period (non-vacuous: monthly != annual for one deadline)', function (): void {
	$deadline = new \DateTimeImmutable( '2026-10-31 12:00:00', new \DateTimeZone( 'Africa/Johannesburg' ) );

	// Monthly is the default arg; annual is explicit. They must differ.
	expect( Cadence::periodKey( $deadline ) )->toBe( '2026-10' );
	expect( Cadence::periodKey( $deadline, CadenceType::Jaarliks ) )->toBe( '2026' );
	expect( Cadence::periodLabel( $deadline ) )->toBe( 'Oktober 2026' );
	expect( Cadence::periodLabel( $deadline, CadenceType::Jaarliks ) )->toBe( '2026' );
} );

test( 'forUitdaging resolves the cadence from the uitdaging meta, defaulting to monthly', function (): void {
	// Round 7 is configured annual; round 9 has no cadence meta (legacy/absent).
	Functions\when( 'get_post_meta' )->alias(
		fn ( $id, string $key, bool $single ) => ( FieldSets::UITDAGING_CADENCE === $key && 7 === $id ) ? 'jaarliks' : ''
	);

	expect( Cadence::forUitdaging( 7 ) )->toBe( CadenceType::Jaarliks );
	expect( Cadence::forUitdaging( 9 ) )->toBe( CadenceType::Maandeliks ); // absent/legacy meta
} );

test( 'periodKeyFor / periodLabelFor resolve the round cadence then derive the period', function (): void {
	$deadline = new \DateTimeImmutable( '2026-12-31 12:00:00', new \DateTimeZone( 'Africa/Johannesburg' ) );

	// Rounds 7 and 8 are annual; round 9 is monthly (no cadence meta).
	Functions\when( 'get_post_meta' )->alias(
		fn ( $id, string $key, bool $single ) => ( FieldSets::UITDAGING_CADENCE === $key && in_array( $id, array( 7, 8 ), true ) ) ? 'jaarliks' : ''
	);

	expect( Cadence::periodKeyFor( 7, $deadline ) )->toBe( '2026' );
	expect( Cadence::periodLabelFor( 8, $deadline ) )->toBe( '2026' );
	expect( Cadence::periodLabelFor( 9, $deadline ) )->toBe( 'Desember 2026' ); // monthly default
} );

test( 'the annual period flows unchanged through the winner-label machinery', function (): void {
	// __ stubbed so Terms::label returns its glossary literal (Goud / wenner).
	Functions\when( '__' )->returnArg( 1 );

	$deadline = new \DateTimeImmutable( '2026-12-31 12:00:00', new \DateTimeZone( 'Africa/Johannesburg' ) );
	$period   = Cadence::periodLabel( $deadline, CadenceType::Jaarliks );

	// The winners machinery (Tiers\Api::winnerLabel) is reused verbatim — only the
	// period differs from the monthly cadence ("Desember 2026 Goud-wenner").
	expect( TiersApi::winnerLabel( Tier::Goud, $period ) )->toBe( '2026 Goud-wenner' );
} );

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

// The production-path proof that the annual cadence is actually reused by the winners
// machinery lives in WinnersPostTest ('generate carries the round cadence period into
// the announcement title') — `WinnersPost::roundPeriod()` calls `periodLabelFor()`.

// --- A monthly round's close derives from its month (owner, 2026-11-01). ---

test( 'monthOf takes the month from the title; real round titles', function (): void {
	expect( Cadence::monthOf( 'Oktober 2026 - Iets projek', '2026-10-01 08:00:00' ) )->toBe( array( 10, 2026 ) );
	expect( Cadence::monthOf( 'MEI 2026 - OOP PROJEK', '2026-05-05' ) )->toBe( array( 5, 2026 ) );
	expect( Cadence::monthOf( 'Desember 2016 tot 22 Januarie 2017 Projek: INK-gedenkbundel', '2016-12-01' ) )->toBe( array( 12, 2016 ) );
	expect( Cadence::monthOf( 'Projek - Teenstellings (1 Mei 2016 - 31 Mei 2016)', '2016-05-02' ) )->toBe( array( 5, 2016 ) );
} );

test( 'monthOf: a round posted shortly before month-end belongs to the month in its title', function (): void {
	expect( Cadence::monthOf( 'November 2026 - Oop projek', '2026-10-30 09:00:00' ) )->toBe( array( 11, 2026 ) );
	expect( Cadence::monthOf( 'Januarie 2027 - Oop projek', '2026-12-30' ) )->toBe( array( 1, 2027 ) );
} );

test( 'monthOf: the post date settles a title with no year, also across new year', function (): void {
	expect( Cadence::monthOf( 'September - Lente projek', '2017-09-01' ) )->toBe( array( 9, 2017 ) );
	expect( Cadence::monthOf( 'Januarie projek', '2026-12-30' ) )->toBe( array( 1, 2027 ) );
	expect( Cadence::monthOf( 'Desember projek', '2027-01-02' ) )->toBe( array( 12, 2026 ) );
} );

test( 'monthOf: the post date corrects a year that is a whole year out', function (): void {
	// The one real case among the 128 rounds in challenges.csv.
	expect( Cadence::monthOf( 'Junie 2016 - FAK projek', '2017-06-01' ) )->toBe( array( 6, 2017 ) );
} );

test( 'monthOf: a month in the title beats a post date in another month', function (): void {
	expect( Cadence::monthOf( 'Mei 2026 - Laat projek', '2026-07-15' ) )->toBe( array( 5, 2026 ) );
} );

test( 'monthOf: a title with no month falls to the post date; late in a month means the next', function (): void {
	expect( Cadence::monthOf( 'Herfs', '2016-03-16' ) )->toBe( array( 3, 2016 ) );
	expect( Cadence::monthOf( '“Carpe Diem” (Gryp die oomblik) - Sonnet', '2016-03-30' ) )->toBe( array( 4, 2016 ) );
	expect( Cadence::monthOf( 'Sonder maand', '2025-12-28' ) )->toBe( array( 1, 2026 ) );
} );

test( 'monthOf does not take a month out of the letters of a word', function (): void {
	expect( Cadence::monthOf( 'Meisies projek', '2026-02-02' ) )->toBe( array( 2, 2026 ) );
} );

test( 'monthOf reads an entity-encoded title and copes with no usable date', function (): void {
	expect( Cadence::monthOf( 'Mei 2026 &#8211; Oop', '' ) )->toBe( array( 5, 2026 ) );
	expect( Cadence::monthOf( 'Mei sonder jaar', 'nonsens' ) )->toBeNull();
	expect( Cadence::monthOf( 'Niks', '' ) )->toBeNull();
} );

test( 'monthlyClose is the last day of the month: October 31, a leap February 29', function (): void {
	expect( Cadence::monthlyClose( 'Oktober 2026 - X', '2026-10-01' )->format( 'Y-m-d' ) )->toBe( '2026-10-31' );
	expect( Cadence::monthlyClose( 'November 2026 - X', '2026-10-30' )->format( 'Y-m-d' ) )->toBe( '2026-11-30' );
	expect( Cadence::monthlyClose( 'Februarie 2028 - X', '2028-02-01' )->format( 'Y-m-d' ) )->toBe( '2028-02-29' );
	expect( Cadence::monthlyClose( 'Niks', '' ) )->toBeNull();
} );

test( 'closeFor derives a monthly round and ignores a stored deadline', function (): void {
	Functions\when( 'get_post_meta' )->alias(
		fn ( int $id, string $key ) => FieldSets::UITDAGING_CADENCE === $key ? 'maandeliks' : '2020-01-01'
	);
	Functions\when( 'get_post_field' )->alias(
		fn ( string $field ) => 'post_title' === $field ? 'Oktober 2026 - Oop projek' : '2026-10-01 08:00:00'
	);

	expect( Cadence::closeFor( 5 )->format( 'Y-m-d' ) )->toBe( '2026-10-31' );
} );

test( 'closeFor keeps the stored value on an annual round, whatever its title says', function (): void {
	Functions\when( 'get_post_meta' )->alias(
		fn ( int $id, string $key ) => FieldSets::UITDAGING_CADENCE === $key ? 'jaarliks' : '2026-06-15'
	);
	Functions\expect( 'get_post_field' )->never();

	expect( Cadence::closeFor( 5 )->format( 'Y-m-d' ) )->toBe( '2026-06-15' );
} );

test( 'closeFor is null for an annual round with nothing stored', function (): void {
	Functions\when( 'get_post_meta' )->alias(
		fn ( int $id, string $key ) => FieldSets::UITDAGING_CADENCE === $key ? 'jaarliks' : ''
	);

	expect( Cadence::closeFor( 5 ) )->toBeNull();
} );

test( 'closeString is the filter answer for modules with no Challenges edge', function (): void {
	Functions\when( 'get_post_meta' )->justReturn( '' );
	Functions\when( 'get_post_field' )->alias(
		fn ( string $field ) => 'post_title' === $field ? 'Oktober 2026' : '2026-10-01'
	);

	expect( Cadence::closeString( '', 5 ) )->toBe( '2026-10-31' );
} );

test( 'hasStarted: a monthly round starts on the first; an annual one has only a close', function (): void {
	$sast  = new \DateTimeZone( 'Africa/Johannesburg' );
	$close = new \DateTimeImmutable( '2026-11-30', $sast );

	expect( Cadence::hasStarted( $close, CadenceType::Maandeliks, new \DateTimeImmutable( '2026-10-31 23:59:59', $sast ) ) )->toBeFalse();
	expect( Cadence::hasStarted( $close, CadenceType::Maandeliks, new \DateTimeImmutable( '2026-11-01 00:00:00', $sast ) ) )->toBeTrue();
	expect( Cadence::hasStarted( $close, CadenceType::Jaarliks, new \DateTimeImmutable( '2026-01-01', $sast ) ) )->toBeTrue();
} );

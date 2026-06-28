<?php
/**
 * Unit tests for the sponsor campaign scheduler (Story 14.2, FR-58).
 *
 * Target: {@see \Ink\Sponsors\Campaign} — the campaign-window + rotation logic. The
 * pure layers (isActive window matrix, parseDate, dayIndex, rotate, activeFrom,
 * queryArgs) are unit-testable with `now` injected; the WP-touching wrappers
 * (activeSponsors/featured) are covered via their pure pieces (house style — no
 * WP_Query mock).
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Sponsors;

use Ink\Sponsors\Campaign;
use Ink\Sponsors\Sponsor;
use Ink\Content\PostTypes;
use Brain\Monkey;

beforeEach( function (): void {
	Monkey\setUp();
} );

afterEach( function (): void {
	Monkey\tearDown();
} );

/**
 * Build a Sponsor with the given campaign dates (other fields irrelevant here).
 */
function ink_sponsor_with_window( string $start, string $end, int $id = 1, string $name = 'P' ): Sponsor {
	return new Sponsor( $id, $name, '', '', $start, $end, '' );
}

function ink_sast_now( string $ymdhis ): \DateTimeImmutable {
	return new \DateTimeImmutable( $ymdhis, new \DateTimeZone( 'Africa/Johannesburg' ) );
}

// --- parseDate ---

test( 'parseDate parses a Y-m-d borg date as a SAST instant', function (): void {
	$dt = Campaign::parseDate( '2026-06-22' );
	expect( $dt )->toBeInstanceOf( \DateTimeImmutable::class );
	expect( $dt->setTimezone( new \DateTimeZone( 'Africa/Johannesburg' ) )->format( 'Y-m-d' ) )->toBe( '2026-06-22' );
} );

test( 'parseDate returns null for an empty or unparseable date', function (): void {
	expect( Campaign::parseDate( '' ) )->toBeNull();
	expect( Campaign::parseDate( 'not-a-date' ) )->toBeNull();
} );

// --- isActive window matrix ---

test( 'isActive is true within an inclusive window and false outside it', function (): void {
	$sponsor = ink_sponsor_with_window( '2026-06-01', '2026-06-30' );

	expect( Campaign::isActive( $sponsor, ink_sast_now( '2026-06-15 12:00:00' ) ) )->toBeTrue();
	// Inclusive start and end days.
	expect( Campaign::isActive( $sponsor, ink_sast_now( '2026-06-01 00:00:00' ) ) )->toBeTrue();
	expect( Campaign::isActive( $sponsor, ink_sast_now( '2026-06-30 23:59:59' ) ) )->toBeTrue();
	// Just outside.
	expect( Campaign::isActive( $sponsor, ink_sast_now( '2026-05-31 23:59:59' ) ) )->toBeFalse();
	expect( Campaign::isActive( $sponsor, ink_sast_now( '2026-07-01 00:00:00' ) ) )->toBeFalse();
} );

test( 'isActive treats a single-day campaign (start==end) as that whole day', function (): void {
	$sponsor = ink_sponsor_with_window( '2026-06-22', '2026-06-22' );

	expect( Campaign::isActive( $sponsor, ink_sast_now( '2026-06-22 00:00:00' ) ) )->toBeTrue();
	expect( Campaign::isActive( $sponsor, ink_sast_now( '2026-06-22 23:59:59' ) ) )->toBeTrue();
	expect( Campaign::isActive( $sponsor, ink_sast_now( '2026-06-21 23:59:59' ) ) )->toBeFalse();
	expect( Campaign::isActive( $sponsor, ink_sast_now( '2026-06-23 00:00:00' ) ) )->toBeFalse();
} );

test( 'isActive honours open bounds: open-start, open-end, and evergreen (both empty)', function (): void {
	$now = ink_sast_now( '2026-06-22 12:00:00' );

	// Open start (active up to the end).
	expect( Campaign::isActive( ink_sponsor_with_window( '', '2026-12-31' ), $now ) )->toBeTrue();
	expect( Campaign::isActive( ink_sponsor_with_window( '', '2026-01-01' ), $now ) )->toBeFalse();
	// Open end (active from the start onward).
	expect( Campaign::isActive( ink_sponsor_with_window( '2026-01-01', '' ), $now ) )->toBeTrue();
	expect( Campaign::isActive( ink_sponsor_with_window( '2026-12-01', '' ), $now ) )->toBeFalse();
	// Evergreen: no dates at all → always active.
	expect( Campaign::isActive( ink_sponsor_with_window( '', '' ), $now ) )->toBeTrue();
} );

// --- activeFrom ---

test( 'activeFrom filters to the active sponsors and re-indexes the list', function (): void {
	$now = ink_sast_now( '2026-06-22 12:00:00' );

	$in1  = ink_sponsor_with_window( '2026-06-01', '2026-06-30', 1 );
	$out  = ink_sponsor_with_window( '2026-01-01', '2026-01-31', 2 );
	$in2  = ink_sponsor_with_window( '', '', 3 );

	$active = Campaign::activeFrom( array( $in1, $out, $in2 ), $now );

	expect( $active )->toHaveCount( 2 );
	expect( $active[0]->postId )->toBe( 1 );
	expect( $active[1]->postId )->toBe( 3 ); // re-indexed (gap from the filtered-out #2 closed).
} );

// --- dayIndex + rotate ---

test( 'dayIndex advances by exactly one per calendar day', function (): void {
	$d0 = Campaign::dayIndex( ink_sast_now( '2026-06-22 00:00:00' ) );
	$d1 = Campaign::dayIndex( ink_sast_now( '2026-06-23 00:00:00' ) );
	$sameDay = Campaign::dayIndex( ink_sast_now( '2026-06-22 23:00:00' ) );

	expect( $d1 - $d0 )->toBe( 1 );
	expect( $sameDay )->toBe( $d0 );
} );

test( 'rotate cycles through the active set by the day index, stable within a day', function (): void {
	$a = ink_sponsor_with_window( '', '', 1 );
	$b = ink_sponsor_with_window( '', '', 2 );
	$c = ink_sponsor_with_window( '', '', 3 );
	$set = array( $a, $b, $c );

	expect( Campaign::rotate( $set, 0 )->postId )->toBe( 1 );
	expect( Campaign::rotate( $set, 1 )->postId )->toBe( 2 );
	expect( Campaign::rotate( $set, 2 )->postId )->toBe( 3 );
	expect( Campaign::rotate( $set, 3 )->postId )->toBe( 1 ); // wraps.
} );

test( 'rotate returns null for an empty set and is negative-safe', function (): void {
	expect( Campaign::rotate( array(), 5 ) )->toBeNull();

	$set = array( ink_sponsor_with_window( '', '', 1 ), ink_sponsor_with_window( '', '', 2 ) );
	// A defensive negative index must not throw or pick a negative offset.
	expect( Campaign::rotate( $set, -1 )->postId )->toBe( 2 );
} );

// --- queryArgs ---

test( 'queryArgs targets published borg posts newest-first, bounded', function (): void {
	$args = Campaign::queryArgs();

	expect( $args['post_type'] )->toBe( PostTypes::BORG );
	expect( $args['post_status'] )->toBe( 'publish' );
	expect( $args['orderby'] )->toBe( 'date' );
	expect( $args['order'] )->toBe( 'DESC' );
	expect( $args['posts_per_page'] )->toBeGreaterThan( 0 );
	expect( $args['ignore_sticky_posts'] )->toBeTrue();
} );

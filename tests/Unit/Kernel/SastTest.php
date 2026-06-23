<?php
/**
 * Unit tests for the single reusable SAST end-of-day boundary helper (Story 4.3, AD-2).
 *
 * Target: {@see \Ink\Kernel\Sast} — the ONE source of truth for "end of day SAST"
 * (Africa/Johannesburg, UTC+2, no DST). AD-2 says the same helper serves BOTH the
 * 4.3 entitlement gate (valid through 23:59:59 SAST on the lidmaatskap end date) and
 * the AD-3 challenge deadline / entry-freeze (FR-47, inclusive 23:59:59 SAST). These
 * tests pin "now" so the boundary maths is deterministic and assert the SAST offset
 * (UTC+2) is load-bearing — the boundary is computed in SAST, NOT UTC.
 *
 * Brain Monkey, no WordPress/DB loaded. The helper is pure PHP `DateTimeImmutable`
 * maths, so the only WP seam is the optional `current_datetime()` "now" source, which
 * these tests bypass by passing an explicit `$now`.
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Kernel;

use Ink\Kernel\Sast;
use Brain\Monkey;
use Brain\Monkey\Functions;

beforeEach( function (): void {
	Monkey\setUp();
} );

afterEach( function (): void {
	Monkey\tearDown();
} );

/**
 * The SAST timezone identifier is the exact single source (Africa/Johannesburg).
 */
test( 'the SAST timezone constant is the exact single source', function (): void {
	expect( Sast::TIMEZONE )->toBe( 'Africa/Johannesburg' );
} );

/**
 * AD-2 granularity: end of day is 23:59:59 SAST = 21:59:59 UTC on the date's SAST
 * calendar day — NOT UTC midnight-based. We assert both the SAST wall-clock and the
 * underlying UTC instant, so a regression to UTC-based maths is caught.
 */
test( 'endOfDay returns 23:59:59 SAST = 21:59:59 UTC on the SAST calendar day', function (): void {
	$date = new \DateTimeImmutable( '2026-06-22 09:00:00', new \DateTimeZone( 'Africa/Johannesburg' ) );

	$eod = Sast::endOfDay( $date );

	// Wall-clock in SAST: 23:59:59 on the same calendar day.
	$sast = $eod->setTimezone( new \DateTimeZone( 'Africa/Johannesburg' ) );
	expect( $sast->format( 'Y-m-d H:i:s' ) )->toBe( '2026-06-22 23:59:59' );

	// The same instant in UTC: 21:59:59 (UTC+2 offset, no DST).
	$utc = $eod->setTimezone( new \DateTimeZone( 'UTC' ) );
	expect( $utc->format( 'Y-m-d H:i:s' ) )->toBe( '2026-06-22 21:59:59' );
} );

/**
 * AD-2: the calendar day is resolved in SAST, not UTC. An instant that is still the
 * 22nd in UTC but already the 23rd in SAST (e.g. 22:30 UTC = 00:30 SAST next day)
 * must yield the 23rd's end-of-day SAST. This proves the UTC+2 offset is load-bearing
 * (UTC-based maths would wrongly return the 22nd).
 */
test( 'endOfDay resolves the calendar day in SAST not UTC (offset is load-bearing)', function (): void {
	// 2026-06-22 22:30:00 UTC == 2026-06-23 00:30:00 SAST.
	$instant = new \DateTimeImmutable( '2026-06-22 22:30:00', new \DateTimeZone( 'UTC' ) );

	$eod  = Sast::endOfDay( $instant );
	$sast = $eod->setTimezone( new \DateTimeZone( 'Africa/Johannesburg' ) );

	// The SAST day is the 23rd, so end-of-day is the 23rd at 23:59:59 SAST.
	expect( $sast->format( 'Y-m-d H:i:s' ) )->toBe( '2026-06-23 23:59:59' );
} );

/**
 * AD-2: valid THROUGH end of day SAST — just before 23:59:59 SAST on the expiry day
 * is still within the window (true); just after the boundary is out (false). The
 * boundary instant itself is inclusive.
 */
test( 'isThroughEndOfDay is true just before, true at, and false just after the SAST boundary', function (): void {
	$sast    = new \DateTimeZone( 'Africa/Johannesburg' );
	$endDate = new \DateTimeImmutable( '2026-06-22 00:00:00', $sast );

	$justBefore = new \DateTimeImmutable( '2026-06-22 23:59:58', $sast );
	$atBoundary = new \DateTimeImmutable( '2026-06-22 23:59:59', $sast );
	$justAfter  = new \DateTimeImmutable( '2026-06-23 00:00:00', $sast );

	expect( Sast::isThroughEndOfDay( $endDate, $justBefore ) )->toBeTrue();
	expect( Sast::isThroughEndOfDay( $endDate, $atBoundary ) )->toBeTrue();
	expect( Sast::isThroughEndOfDay( $endDate, $justAfter ) )->toBeFalse();
} );

/**
 * AD-2 lag case framing: an end date in the FUTURE is comfortably within the window,
 * and an end date in the PAST (a full day earlier) is out — regardless of wall-clock
 * time-of-day. The end DATE (its SAST calendar day), not the time, drives the window.
 */
test( 'isThroughEndOfDay honours a future end date and rejects a past one', function (): void {
	$sast = new \DateTimeZone( 'Africa/Johannesburg' );
	$now  = new \DateTimeImmutable( '2026-06-22 12:00:00', $sast );

	$future = new \DateTimeImmutable( '2026-06-25 00:00:00', $sast );
	$past   = new \DateTimeImmutable( '2026-06-21 00:00:00', $sast );
	$today  = new \DateTimeImmutable( '2026-06-22 06:00:00', $sast ); // same SAST day, earlier wall time.

	expect( Sast::isThroughEndOfDay( $future, $now ) )->toBeTrue();
	expect( Sast::isThroughEndOfDay( $past, $now ) )->toBeFalse();
	// End date today (SAST) but "now" is later in the day → still valid through EOD.
	expect( Sast::isThroughEndOfDay( $today, $now ) )->toBeTrue();
} );

/**
 * The "now" source is injectable: passing an explicit `$now` bypasses WordPress's
 * `current_datetime()` entirely, so the maths is deterministic in the mocked suite.
 * (The default `now()` path is integration-covered, Story 18.8.)
 */
test( 'isThroughEndOfDay uses the injected now, not the wall clock', function (): void {
	$sast    = new \DateTimeZone( 'Africa/Johannesburg' );
	$endDate = new \DateTimeImmutable( '2020-01-01 00:00:00', $sast ); // long past.

	// Inject a "now" BEFORE that long-past end date → the injected now wins (true),
	// proving the helper does not fall back to the real clock.
	$injectedNow = new \DateTimeImmutable( '2019-12-31 10:00:00', $sast );

	expect( Sast::isThroughEndOfDay( $endDate, $injectedNow ) )->toBeTrue();
} );

/**
 * The production `now()` clock path (cheap assertion — Review [Note] on its prior
 * test-thinness): when WordPress is loaded, `now()` prefers `current_datetime()`. Mock
 * that WP seam and assert the returned instant is exactly it, so the default-clock
 * branch (not just the injected-now branch) carries a unit assertion. (The fully WP-
 * native path remains integration-covered, Story 18.8.)
 */
test( 'now prefers the WordPress current_datetime clock when WordPress is loaded', function (): void {
	$wpNow = new \DateTimeImmutable( '2026-06-22 08:30:00', new \DateTimeZone( 'Africa/Johannesburg' ) );

	Functions\when( 'current_datetime' )->justReturn( $wpNow );

	expect( Sast::now()->format( \DateTimeInterface::ATOM ) )->toBe( $wpNow->format( \DateTimeInterface::ATOM ) );
} );

<?php
/**
 * The single reusable SAST end-of-day boundary helper (Story 4.3, AD-2/AD-3).
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Kernel;

defined( 'ABSPATH' ) || exit;

/**
 * The ONE source of truth for "end of day SAST" — South African Standard Time
 * (`Africa/Johannesburg`, UTC+2, no daylight saving).
 *
 * AD-2 decides the policy: a date is valid THROUGH end of day SAST, i.e. its SAST
 * calendar day is valid through **23:59:59 SAST = 21:59:59 UTC**. Times are stored
 * in UTC and compared as instants; only the BOUNDARY is computed in SAST. AD-2 also
 * mandates a SINGLE helper, reused — not two implementations:
 *
 *  - **AD-2 (this story)** — the entitlement gate ({@see \Ink\Entitlement\SubmissionGate})
 *    treats a lidmaatskap's end date as valid through end of day SAST, so a member is
 *    entitled until 23:59:59 SAST on the expiry day (cron-independent; it never trusts
 *    WooCommerce's lagging `expired` status flag).
 *  - **AD-3 (later)** — the challenge deadline / entry-freeze (FR-47, "inclusive
 *    23:59:59 SAST") reuses the SAME boundary: "round is open" = `now <=
 *    endOfDay( deadline )`. One source of truth for end-of-day SAST.
 *
 * Lives in the Kernel (the shared base every module already depends on, and which
 * depends on nothing — deptrac.yaml) so BOTH `Ink\Entitlement` and `Ink\Challenges`
 * reuse it with zero new cross-module edges. It is a pure value/utility — no business
 * logic, no WordPress state — exactly like the Kernel enums ({@see Reaction} /
 * {@see ResponseType}).
 *
 * @package Ink\Core
 */
final class Sast {

	/**
	 * The SAST timezone identifier — the single source (UTC+2, no DST).
	 *
	 * `Africa/Johannesburg` observes no daylight saving, so the offset is a constant
	 * +02:00 year-round; the boundary maths never has to reason about a DST shift.
	 */
	public const TIMEZONE = 'Africa/Johannesburg';

	/**
	 * The end-of-day SAST instant for a given date's SAST calendar day.
	 *
	 * Resolves the date's calendar day **in SAST** (NOT UTC — an instant late in the
	 * UTC day may already be the next SAST day, given the +2 offset) and returns
	 * `23:59:59` on that SAST day as a UTC-normalised immutable instant. Comparable
	 * directly against any other instant.
	 *
	 * @param \DateTimeInterface $date Any instant; only its SAST calendar day matters.
	 * @return \DateTimeImmutable The 23:59:59-SAST instant of that SAST day.
	 */
	public static function endOfDay( \DateTimeInterface $date ): \DateTimeImmutable {
		$sast = new \DateTimeZone( self::TIMEZONE );

		// Reinterpret the instant in SAST so the calendar day is the SAST day, then
		// pin the wall clock to 23:59:59 on that day (still in SAST).
		$inSast = \DateTimeImmutable::createFromInterface( $date )->setTimezone( $sast );

		return $inSast->setTime( 23, 59, 59 );
	}

	/**
	 * The current instant, timezone-aware.
	 *
	 * Prefers WordPress's `current_datetime()` (site-timezone-aware) when WordPress is
	 * loaded; falls back to a UTC "now" otherwise (the unit suite injects an explicit
	 * `$now` and never reaches this fallback). The returned instant is timezone-bearing,
	 * so the comparison in {@see isThroughEndOfDay()} is correct regardless of source.
	 *
	 * @return \DateTimeImmutable The current instant.
	 */
	public static function now(): \DateTimeImmutable {
		if ( function_exists( 'current_datetime' ) ) {
			return \DateTimeImmutable::createFromInterface( current_datetime() );
		}

		return new \DateTimeImmutable( 'now', new \DateTimeZone( 'UTC' ) );
	}

	/**
	 * Whether "now" is still within the date's end-of-day-SAST window (inclusive).
	 *
	 * `true` iff `now <= endOfDay( $endDate )` — the date is valid THROUGH 23:59:59
	 * SAST on its SAST calendar day (AD-2). The boundary instant itself is inclusive.
	 * Both operands are compared as absolute instants, so a mix of UTC- and SAST-typed
	 * inputs compares correctly.
	 *
	 * @param \DateTimeInterface      $endDate The date whose end-of-day-SAST boundary applies.
	 * @param \DateTimeInterface|null $now     The instant to test; defaults to {@see now()}.
	 * @return bool True when within the window (inclusive of the boundary).
	 */
	public static function isThroughEndOfDay( \DateTimeInterface $endDate, ?\DateTimeInterface $now = null ): bool {
		$now ??= self::now();

		return $now <= self::endOfDay( $endDate );
	}
}

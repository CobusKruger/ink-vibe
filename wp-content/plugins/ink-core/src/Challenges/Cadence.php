<?php
/**
 * Monthly challenge cadence helper — Story 12.3 (FR-47).
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Challenges;

use Ink\Kernel\Sast;

defined( 'ABSPATH' ) || exit;

/**
 * The single source for a challenge's MONTHLY cadence + the judging-freeze boundary.
 *
 * INK uitdagings run on a monthly cadence (FR-47): one round per calendar month, all
 * times SAST. The round's period is derived from the stored `challenge_deadline` —
 * {@see self::periodKey()} (`YYYY-MM`, the stable grouping key) and
 * {@see self::periodLabel()} (the Afrikaans display label, e.g. "Oktober 2026"). The
 * Afrikaans month names live here as the single source the period label and the Tiers
 * winner label ("Oktober Goud-wenner", {@see \Ink\Tiers\Api::winnerLabel()}) consume —
 * standard month nouns, not contested INK copy.
 *
 * {@see self::entriesFrozen()} names the judging freeze: once `now` is past the
 * inclusive end-of-day-SAST deadline, entries are frozen for judging. It delegates to
 * {@see Sast::isThroughEndOfDay()} (AD-2/AD-3 — the real single source for the
 * boundary); this class only gives the freeze its domain name. Conflation-clean:
 * reads only `Ink\Kernel` (no Content/Tiers/Entitlement edge).
 *
 * @package Ink\Core
 */
final class Cadence {

	/**
	 * The Afrikaans calendar month names, indexed 1–12. The single source.
	 *
	 * @var array<int, string>
	 */
	private const MONTHS = array(
		1  => 'Januarie',
		2  => 'Februarie',
		3  => 'Maart',
		4  => 'April',
		5  => 'Mei',
		6  => 'Junie',
		7  => 'Julie',
		8  => 'Augustus',
		9  => 'September',
		10 => 'Oktober',
		11 => 'November',
		12 => 'Desember',
	);

	/**
	 * The Afrikaans month name for a 1–12 month number; '' for out-of-range. Pure.
	 *
	 * @param int $month The calendar month number.
	 * @return string
	 */
	public static function monthName( int $month ): string {
		return self::MONTHS[ $month ] ?? '';
	}

	/**
	 * The stable monthly period key (`YYYY-MM`) for a deadline, in SAST. Pure.
	 *
	 * Resolves the deadline's SAST calendar month (an instant late in the UTC day may
	 * already be the next SAST day, given the +2 offset), so the round groups by the
	 * SAST month rather than the UTC one.
	 *
	 * @param \DateTimeInterface $deadline The round deadline instant.
	 * @return string
	 */
	public static function periodKey( \DateTimeInterface $deadline ): string {
		return self::inSast( $deadline )->format( 'Y-m' );
	}

	/**
	 * The Afrikaans monthly period label (e.g. "Oktober 2026") for a deadline. Pure.
	 *
	 * @param \DateTimeInterface $deadline The round deadline instant.
	 * @return string
	 */
	public static function periodLabel( \DateTimeInterface $deadline ): string {
		$sast = self::inSast( $deadline );

		return self::monthName( (int) $sast->format( 'n' ) ) . ' ' . $sast->format( 'Y' );
	}

	/**
	 * Whether entries are frozen for judging — `now` is past the inclusive
	 * end-of-day-SAST deadline (the inverse of {@see Sast::isThroughEndOfDay()}). Pure
	 * delegate to the single boundary source.
	 *
	 * @param \DateTimeInterface      $deadline The round deadline.
	 * @param \DateTimeInterface|null $now      The instant to test (defaults to now).
	 * @return bool
	 */
	public static function entriesFrozen( \DateTimeInterface $deadline, ?\DateTimeInterface $now = null ): bool {
		return ! Sast::isThroughEndOfDay( $deadline, $now );
	}

	/**
	 * Reinterpret an instant in the SAST calendar. Pure.
	 *
	 * @param \DateTimeInterface $date Any instant.
	 * @return \DateTimeImmutable
	 */
	private static function inSast( \DateTimeInterface $date ): \DateTimeImmutable {
		return \DateTimeImmutable::createFromInterface( $date )->setTimezone( new \DateTimeZone( Sast::TIMEZONE ) );
	}
}

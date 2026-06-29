<?php
/**
 * Monthly challenge cadence helper — Story 12.3 (FR-47).
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Challenges;

use Ink\Content\FieldSets;
use Ink\Kernel\CadenceType;
use Ink\Kernel\Sast;
use Ink\Kernel\Scalar;

defined( 'ABSPATH' ) || exit;

/**
 * The single source for a challenge's cadence period + the judging-freeze boundary.
 *
 * INK uitdagings run monthly by default (FR-47): one round per calendar month, all
 * times SAST. Story 12B.1 (R9) adds an ANNUAL cadence for the yearly competition —
 * the same adjudication pipeline (collation, ingestion + coverage, winners post /
 * banner / featuring, auto-promotion) reused at an annual cadence, with no new core
 * mechanics. A cadence is configuration only: the pipeline never branches on it; the
 * one cadence-specific fact is the round's PERIOD, which this class owns.
 *
 * The period is derived from the stored `challenge_deadline` and the round's
 * {@see CadenceType} (resolved per-`uitdaging` from {@see FieldSets::UITDAGING_CADENCE}
 * via {@see self::forUitdaging()}, defaulting to monthly so every legacy round is
 * unchanged):
 *   - MONTHLY: {@see self::periodKey()} ⇒ `YYYY-MM` (stable grouping key);
 *     {@see self::periodLabel()} ⇒ the Afrikaans label "Oktober 2026".
 *   - ANNUAL:  period key ⇒ `YYYY`; period label ⇒ the bare year "2026" — which keeps
 *     {@see \Ink\Tiers\Api::winnerLabel()} composing "2026 Goud-wenner" with no new
 *     copy (a numeral). {@see self::periodKeyFor()} / {@see self::periodLabelFor()}
 *     resolve the cadence then derive, so a caller needs only the round id + deadline.
 * The Afrikaans month names live here as the single source the monthly label and the
 * Tiers winner label ("Oktober Goud-wenner") consume — standard month nouns, not
 * contested INK copy.
 *
 * {@see self::entriesFrozen()} names the judging freeze: once `now` is past the
 * inclusive end-of-day-SAST deadline, entries are frozen for judging. It delegates to
 * {@see Sast::isThroughEndOfDay()} (AD-2/AD-3 — the real single source for the
 * boundary); this class only gives the freeze its domain name. The freeze is
 * cadence-INDEPENDENT: the stored deadline instant is the boundary whether the round
 * is monthly or annual, so it takes no {@see CadenceType}. Conflation-clean: reads
 * only `Ink\Kernel` + `Ink\Content` (the deadline/cadence meta keys — already an
 * allowed Challenges→Content edge); no Tiers/Entitlement edge.
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
	 * The stable period key for a deadline, in SAST. Pure.
	 *
	 * MONTHLY (default) ⇒ `YYYY-MM`; ANNUAL ⇒ `YYYY`. Resolves the deadline's SAST
	 * calendar month/year (an instant late in the UTC day may already be the next
	 * SAST day, given the +2 offset), so the round groups by the SAST month/year
	 * rather than the UTC one. The optional cadence defaults to monthly, so every
	 * existing 1-argument caller is unchanged.
	 *
	 * @param \DateTimeInterface $deadline The round deadline instant.
	 * @param CadenceType        $cadence  The round cadence (defaults to monthly).
	 * @return string
	 */
	public static function periodKey( \DateTimeInterface $deadline, CadenceType $cadence = CadenceType::Maandeliks ): string {
		$sast = self::inSast( $deadline );

		return CadenceType::Jaarliks === $cadence ? $sast->format( 'Y' ) : $sast->format( 'Y-m' );
	}

	/**
	 * The Afrikaans period label for a deadline. Pure.
	 *
	 * MONTHLY (default) ⇒ "Oktober 2026"; ANNUAL ⇒ the bare year "2026" (a numeral —
	 * so {@see \Ink\Tiers\Api::winnerLabel()} composes "2026 Goud-wenner" with no new
	 * authored copy). The optional cadence defaults to monthly.
	 *
	 * @param \DateTimeInterface $deadline The round deadline instant.
	 * @param CadenceType        $cadence  The round cadence (defaults to monthly).
	 * @return string
	 */
	public static function periodLabel( \DateTimeInterface $deadline, CadenceType $cadence = CadenceType::Maandeliks ): string {
		$sast = self::inSast( $deadline );

		if ( CadenceType::Jaarliks === $cadence ) {
			return $sast->format( 'Y' );
		}

		return self::monthName( (int) $sast->format( 'n' ) ) . ' ' . $sast->format( 'Y' );
	}

	/**
	 * The cadence configured for a `uitdaging` round, defaulting to monthly. Pure
	 * read of {@see FieldSets::UITDAGING_CADENCE} via {@see CadenceType::fromMeta()};
	 * an absent/legacy/invalid meta resolves to monthly so no round is accidentally
	 * treated as annual.
	 *
	 * @param int $uitdaging_id The producing uitdaging post id.
	 * @return CadenceType
	 */
	public static function forUitdaging( int $uitdaging_id ): CadenceType {
		return CadenceType::fromMeta(
			Scalar::asString( get_post_meta( $uitdaging_id, FieldSets::UITDAGING_CADENCE, true ) )
		);
	}

	/**
	 * The period key for a round, resolving its cadence from the `uitdaging` then
	 * deriving the key. The single entry point a cadence-agnostic caller uses.
	 *
	 * @param int                $uitdaging_id The producing uitdaging post id.
	 * @param \DateTimeInterface $deadline     The round deadline instant.
	 * @return string
	 */
	public static function periodKeyFor( int $uitdaging_id, \DateTimeInterface $deadline ): string {
		return self::periodKey( $deadline, self::forUitdaging( $uitdaging_id ) );
	}

	/**
	 * The period label for a round, resolving its cadence from the `uitdaging` then
	 * deriving the label. The single entry point a cadence-agnostic caller uses.
	 *
	 * @param int                $uitdaging_id The producing uitdaging post id.
	 * @param \DateTimeInterface $deadline     The round deadline instant.
	 * @return string
	 */
	public static function periodLabelFor( int $uitdaging_id, \DateTimeInterface $deadline ): string {
		return self::periodLabel( $deadline, self::forUitdaging( $uitdaging_id ) );
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

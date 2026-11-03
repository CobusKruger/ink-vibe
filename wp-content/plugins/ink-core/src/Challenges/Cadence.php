<?php
/**
 * Monthly challenge cadence helper — Story 12.3 (FR-47).
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Challenges;

use Ink\Content\ChallengeResultTitle;
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
 * **When a round closes ({@see self::closeFor()}, the one reader; owner, 2026-11-01).** A
 * monthly round has no separate deadline: it runs from the first to the last day of its
 * month, and the month comes from its title, verified against its post date
 * ({@see self::monthOf()}). Nothing is stored or entered per round. An annual round keeps
 * its stored {@see FieldSets::UITDAGING_DEADLINE}. Modules that may not depend on Challenges
 * read the same answer through {@see self::CLOSE_FILTER}.
 *
 * The period is derived from a round's close and its
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
	 * Filter giving a round's close as a `Y-m-d` string ('' when it has none), for
	 * modules with no Challenges edge. The firer uses the literal; this class owns the
	 * constant and listens (the `ink/uitdaging_entry_linked` convention).
	 *
	 * @var string
	 */
	public const CLOSE_FILTER = 'ink_uitdaging_sluiting';

	/**
	 * From this day of a month, a post date points at the NEXT month's round: rounds are
	 * created in the first days of their month, with a few shortly before month-end (owner).
	 *
	 * @var int
	 */
	private const LATE_POST_DAY = 25;

	/**
	 * How many months a title's year may sit from the post date before it counts as a typo.
	 * One real case: "Junie 2016 - FAK projek" was posted on 2017-06-01.
	 *
	 * @var int
	 */
	private const MAX_YEAR_DRIFT_MONTHS = 11;

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
	 * Listen on {@see self::CLOSE_FILTER}. Invoked from {@see Module::register()}.
	 */
	public static function register(): void {
		add_filter( self::CLOSE_FILTER, array( self::class, 'closeString' ), 10, 2 );
	}

	/**
	 * The (month, year) a monthly round belongs to. Pure.
	 *
	 * The title decides the month. The post date verifies it: it settles a title with no
	 * year, corrects a year that is a whole year or more out, and names the month when the
	 * title has none (on or after {@see self::LATE_POST_DAY} that is the next month).
	 *
	 * @param string $title     The round's title.
	 * @param string $post_date The round's post date, `Y-m-d[ H:i:s]`.
	 * @return array{0:int,1:int}|null [month 1-12, year]; null when neither gives a month.
	 */
	public static function monthOf( string $title, string $post_date ): ?array {
		$month  = ChallengeResultTitle::monthNumber( $title );
		$year   = ChallengeResultTitle::year( $title );
		$posted = 1 === preg_match( '/^(\d{4})-(\d{2})-(\d{2})/', $post_date, $m ) && checkdate( (int) $m[2], (int) $m[3], (int) $m[1] )
			? array( (int) $m[1], (int) $m[2], (int) $m[3] )
			: null;

		if ( null === $posted ) {
			return null !== $month && null !== $year ? array( $month, $year ) : null;
		}

		list( $posted_year, $posted_month, $posted_day ) = $posted;

		if ( null === $month ) {
			if ( $posted_day < self::LATE_POST_DAY ) {
				return array( $posted_month, $posted_year );
			}

			return 12 === $posted_month ? array( 1, $posted_year + 1 ) : array( $posted_month + 1, $posted_year );
		}

		$posted_index = $posted_year * 12 + $posted_month;

		if ( null !== $year && abs( $year * 12 + $month - $posted_index ) <= self::MAX_YEAR_DRIFT_MONTHS ) {
			return array( $month, $year );
		}

		// No year, or one the post date rules out: the year that puts this month nearest the post date.
		$best = $posted_year;

		foreach ( array( $posted_year - 1, $posted_year + 1 ) as $candidate ) {
			if ( abs( $candidate * 12 + $month - $posted_index ) < abs( $best * 12 + $month - $posted_index ) ) {
				$best = $candidate;
			}
		}

		return array( $month, $best );
	}

	/**
	 * The last day of a monthly round's month, as a SAST date. Pure.
	 *
	 * @param string $title     The round's title.
	 * @param string $post_date The round's post date.
	 * @return \DateTimeImmutable|null
	 */
	public static function monthlyClose( string $title, string $post_date ): ?\DateTimeImmutable {
		$month = self::monthOf( $title, $post_date );

		if ( null === $month ) {
			return null;
		}

		return Deadline::parse( sprintf( '%04d-%02d-01', $month[1], $month[0] ) )?->modify( 'last day of this month' );
	}

	/**
	 * When a round closes: the one place every reader asks. Reads post data.
	 *
	 * @param int $uitdaging_id The round.
	 * @return \DateTimeImmutable|null Null only for an annual round with no stored deadline.
	 */
	public static function closeFor( int $uitdaging_id ): ?\DateTimeImmutable {
		if ( CadenceType::Jaarliks === self::forUitdaging( $uitdaging_id ) ) {
			return Deadline::parse( Scalar::asString( get_post_meta( $uitdaging_id, FieldSets::UITDAGING_DEADLINE, true ) ) );
		}

		return self::monthlyClose(
			Scalar::asString( get_post_field( 'post_title', $uitdaging_id ) ),
			Scalar::asString( get_post_field( 'post_date', $uitdaging_id ) )
		);
	}

	/**
	 * The {@see self::CLOSE_FILTER} callback: a round's close as `Y-m-d`, or ''.
	 *
	 * @param mixed $value        The incoming value (ignored).
	 * @param int   $uitdaging_id The round.
	 * @return string
	 */
	public static function closeString( $value, int $uitdaging_id ): string {
		unset( $value );

		$close = self::closeFor( $uitdaging_id );

		return null === $close ? '' : self::inSast( $close )->format( 'Y-m-d' );
	}

	/**
	 * Whether a round's period has begun: a monthly round starts on the first of its month,
	 * so a round posted a day early doesn't take over from the one still running. An annual
	 * round has only a close. Pure.
	 *
	 * @param \DateTimeInterface $close   The round's close.
	 * @param CadenceType        $cadence The round's cadence.
	 * @param \DateTimeInterface $now     The instant to test.
	 * @return bool
	 */
	public static function hasStarted( \DateTimeInterface $close, CadenceType $cadence, \DateTimeInterface $now ): bool {
		if ( CadenceType::Jaarliks === $cadence ) {
			return true;
		}

		return self::inSast( $now )->format( 'Y-m' ) >= self::inSast( $close )->format( 'Y-m' );
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

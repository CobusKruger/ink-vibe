<?php
/**
 * Read-time from word count — Story 19.4 (§6 featured bydraes).
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Discovery;

defined( 'ABSPATH' ) || exit;

/**
 * Computes a reading-time estimate from a body's word count (§6, owner decision).
 *
 * The read-time shown on the home featured cards is computed HERE, in `ink-core`,
 * never in the theme (three-layer separation — the theme performs no computation).
 * The estimate is words ÷ {@see self::WORDS_PER_MINUTE}, rounded UP, floored at one
 * minute so even a two-line gedig reads as "1 min" rather than "0 min".
 *
 * Word counting mirrors the {@see \Ink\Submission\Counters::words()} rule (UTF-8
 * non-whitespace tokens, so an Afrikaans diacritic stays part of its word) — kept as
 * a local, dependency-free copy so Discovery needs no cross-module edge to Submission
 * for a one-line regex; the two definitions are intentionally the same rule.
 *
 * Pure value logic, no WordPress state beyond the `_n()` label formatter. Conflation-
 * clean — no `Ink\Tiers`/`Ink\Entitlement`.
 *
 * @package Ink\Core
 */
final class ReadingTime {

	/**
	 * Assumed reading pace (words per minute) for the estimate.
	 *
	 * @var int
	 */
	public const WORDS_PER_MINUTE = 200;

	/**
	 * Count words (UTF-8 non-whitespace tokens) in a body. Pure.
	 *
	 * @param string $text The body text (may contain markup — stripped by the caller).
	 * @return int The word count (0 for empty / whitespace-only).
	 */
	public static function words( string $text ): int {
		return (int) preg_match_all( '/\S+/u', $text );
	}

	/**
	 * Reading-time minutes for a given word count. Pure.
	 *
	 * Rounded up, floored at 1 (a non-empty body is always at least "1 min"); an
	 * empty body (zero words) yields 0 so the caller can omit the read-time entirely.
	 *
	 * @param int $words The word count.
	 * @return int Whole minutes (0 only when there are no words).
	 */
	public static function minutesFromWords( int $words ): int {
		if ( $words <= 0 ) {
			return 0;
		}

		return max( 1, (int) ceil( $words / self::WORDS_PER_MINUTE ) );
	}

	/**
	 * Reading-time minutes for a body of text. Pure.
	 *
	 * @param string $text The body text.
	 * @return int Whole minutes (0 for an empty body).
	 */
	public static function minutesFromText( string $text ): int {
		return self::minutesFromWords( self::words( $text ) );
	}

	/**
	 * The Afrikaans read-time label for a minute count. Pure (formatter only).
	 *
	 * The authored ui-copy form is the invariant abbreviation "[X] min"
	 * (ui-copy-translations.md, FeaturedWorks §6) — the abbreviation does not inflect,
	 * so the `_n()` singular and plural are deliberately identical (mirrors the
	 * invariant "duim op" count in {@see \Ink\Engagement\ReactionCounts}).
	 *
	 * @param int $minutes The whole-minute estimate.
	 * @return string e.g. "8 min". Empty string when there is no read-time (0 min).
	 */
	public static function label( int $minutes ): string {
		if ( $minutes <= 0 ) {
			return '';
		}

		/* translators: %d: the estimated reading time in whole minutes. */
		return sprintf( _n( '%d min', '%d min', $minutes, 'ink-core' ), $minutes );
	}
}

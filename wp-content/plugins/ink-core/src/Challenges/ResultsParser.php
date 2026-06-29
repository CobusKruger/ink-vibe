<?php
/**
 * Judges' results plain-text parser — Story 12A.3 (FR-50-R2, R2).
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Challenges;

defined( 'ABSPATH' ) || exit;

/**
 * Parses the judges' results, pasted as PLAIN TEXT, into a structured winners block +
 * per-entry commentary (Story 12A.3, R2). No `.docx` upload — plain text only (owner
 * decision; removes the PhpWord/XXE/zip-bomb surface). Pure: no WP, no side effects.
 *
 * Expected (forgiving, documented) format — two sections, headed case-insensitively:
 *
 *   WENNERS
 *   Brons Gedigte
 *   1ste: Gedig 3
 *   2de: Gedig 1
 *   3de: Geen
 *   Silwer Stories
 *   1: Storie 2
 *
 *   KOMMENTAAR
 *   Gedig 3: Maanlig
 *   <commentary text, any number of lines>
 *
 *   Gedig 1: Nag
 *   <commentary text>
 *
 * A **pool header** line names the Gradering (brons/silwer/goud) and optionally the
 * category (gedig/storie/artikel); it sets the context for the rank lines beneath it.
 * A **winner** line carries a rank token (1/2/3, optionally 1ste/2de/3de/.) and either an
 * **EntryID** ("Gedig 3") or **"Geen"** (no placement — omitted from the output). The
 * EntryID's own type word is authoritative for the category; the pool header's grade
 * supplies the Gradering. In KOMMENTAAR, a line beginning with an EntryID starts a
 * commentary block (the rest of the line after ":" is the title); following lines are
 * its text until the next EntryID or section.
 *
 * The coverage report ({@see Coverage}) + the explicit confirm gate are the safety net
 * for an imperfect parse — nothing is committed on the parse alone.
 *
 * @package Ink\Core
 */
final class ResultsParser {

	/**
	 * Matches an EntryID token: a bydrae type word + a number (e.g. "Gedig 3"). The
	 * type words mirror the {@see \Ink\I18n\Terms} singular labels (Gedig/Storie/Artikel).
	 *
	 * @var string
	 */
	private const ENTRY_ID_RE = '/\b(Gedig|Storie|Artikel)\s+(\d+)\b/i';

	/**
	 * The Gradering words a pool header may carry.
	 *
	 * @var string
	 */
	private const GRADE_RE = '/\b(brons|silwer|goud)\b/i';

	/**
	 * Parse pasted results text into winners + commentary. Pure.
	 *
	 * @param string $text The pasted plain-text results.
	 * @return array{winners:list<array{grade:string, type:string, rank:int, entry_id:string}>, commentary:list<array{entry_id:string, title:string, text:string}>}
	 */
	public static function parse( string $text ): array {
		$lines = preg_split( '/\R/', $text );

		if ( false === $lines ) {
			$lines = array();
		}

		$section    = '';
		$pool_grade = '';
		$winners    = array();
		$commentary = array();
		$current    = null; // The open commentary block.

		foreach ( $lines as $raw ) {
			$line = trim( $raw );

			if ( '' === $line ) {
				continue;
			}

			if ( 1 === preg_match( '/^wenners\b/i', $line ) ) {
				$section = 'wenners';
				$current = null;
				continue;
			}

			if ( 1 === preg_match( '/^kommentaar\b/i', $line ) ) {
				$section = 'kommentaar';
				$current = null;
				continue;
			}

			if ( 'wenners' === $section ) {
				self::parseWinnerLine( $line, $pool_grade, $winners );
				continue;
			}

			if ( 'kommentaar' === $section ) {
				self::parseCommentaryLine( $line, $current, $commentary );
			}
		}

		self::flushCommentary( $current, $commentary );

		return array(
			'winners'    => $winners,
			'commentary' => $commentary,
		);
	}

	/**
	 * Parse one WENNERS-section line (pool header or rank line). Mutates the running
	 * grade context + the winners list.
	 *
	 * @param string                                                            $line       The trimmed line.
	 * @param string                                                            $pool_grade The running Gradering context (by ref).
	 * @param list<array{grade:string, type:string, rank:int, entry_id:string}> $winners   The winners accumulator (by ref).
	 */
	private static function parseWinnerLine( string $line, string &$pool_grade, array &$winners ): void {
		$has_grade = 1 === preg_match( self::GRADE_RE, $line, $gm );
		$has_entry = 1 === preg_match( self::ENTRY_ID_RE, $line, $em );
		$rank      = self::rankIn( $line );

		// A pool header sets the grade context (a line naming a grade but no winner).
		if ( $has_grade && ( 0 === $rank || ! $has_entry ) ) {
			$pool_grade = strtolower( $gm[1] );
			return;
		}

		// A rank line: needs a rank AND an EntryID (a "Geen" line is intentionally dropped).
		if ( $rank > 0 && $has_entry ) {
			// A grade on the same line overrides the running context.
			if ( $has_grade ) {
				$pool_grade = strtolower( $gm[1] );
			}

			$winners[] = array(
				'grade'    => $pool_grade,
				'type'     => strtolower( $em[1] ),
				'rank'     => $rank,
				'entry_id' => self::normaliseEntryId( $em[1], (int) $em[2] ),
			);
		}
	}

	/**
	 * Parse one KOMMENTAAR-section line (block header or body). Mutates the open block.
	 *
	 * @param string                                                  $line       The trimmed line.
	 * @param array{entry_id:string, title:string, text:string}|null  $current    The open block (by ref).
	 * @param list<array{entry_id:string, title:string, text:string}> $commentary The commentary accumulator (by ref).
	 */
	private static function parseCommentaryLine( string $line, ?array &$current, array &$commentary ): void {
		// A line beginning with an EntryID starts a new commentary block.
		if ( 1 === preg_match( '/^(Gedig|Storie|Artikel)\s+(\d+)\s*[:\-–]?\s*(.*)$/i', $line, $m ) ) {
			self::flushCommentary( $current, $commentary );

			$current = array(
				'entry_id' => self::normaliseEntryId( $m[1], (int) $m[2] ),
				'title'    => trim( $m[3] ),
				'text'     => '',
			);

			return;
		}

		if ( null !== $current ) {
			$current['text'] = '' === $current['text'] ? $line : $current['text'] . "\n" . $line;
		}
	}

	/**
	 * Append the open commentary block (if any) to the accumulator. The caller owns the
	 * `$current` reset (it either reassigns a new block or discards it at end of parse),
	 * so this takes `$current` by value — no by-ref out type to reason about.
	 *
	 * @param array{entry_id:string, title:string, text:string}|null  $current    The open block (or null).
	 * @param list<array{entry_id:string, title:string, text:string}> $commentary The accumulator (by ref).
	 */
	private static function flushCommentary( ?array $current, array &$commentary ): void {
		if ( null !== $current ) {
			$current['text'] = trim( $current['text'] );
			$commentary[]    = $current;
		}
	}

	/**
	 * The placement rank named in a line (1/2/3), or 0 if none. Pure.
	 *
	 * Accepts "1", "1:", "1.", "1ste", "2de", "3de" near the line start.
	 *
	 * @param string $line The line.
	 * @return int
	 */
	private static function rankIn( string $line ): int {
		// Accept "1:" / "1." / "1)" (punctuation) OR an ordinal form "1ste" / "2de" /
		// "3de" even WITHOUT trailing punctuation (R12A review — "1ste Gedig 3" was
		// silently dropped). A bare "1 Gedig" (no ordinal, no punctuation) is still NOT a
		// rank line, so a prose line like "3 gedigte is ingedien" never false-matches.
		if ( 1 === preg_match( '/^\s*([123])(?:(?:ste|de|e)\s*[:.\)]?|\s*[:.\)])/i', $line, $m ) ) {
			return (int) $m[1];
		}

		return 0;
	}

	/**
	 * Normalise an EntryID to its canonical "{Ucfirst type} {number}" form. Pure.
	 *
	 * @param string $type_word The matched type word (any case).
	 * @param int    $number    The matched number.
	 * @return string
	 */
	private static function normaliseEntryId( string $type_word, int $number ): string {
		return ucfirst( strtolower( $type_word ) ) . ' ' . $number;
	}
}

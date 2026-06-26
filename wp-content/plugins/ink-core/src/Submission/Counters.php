<?php
/**
 * The single source for Skryf body line/word counting — Story 6.2.
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Submission;

defined( 'ABSPATH' ) || exit;

/**
 * Counts lines and words in a Skryf body, the ONE definition of those rules (FR-17).
 *
 * The Skryf counter feedback ("[N] reëls · [N] woorde" for a gedig, "[N] woorde"
 * for prose) is computed here so the rule lives once: the browser counter is a
 * thin progressive-enhancement mirror of these methods, never a second definition.
 *
 *  - **words**: non-whitespace tokens, UTF-8 aware (so an Afrikaans diacritic is
 *    part of its word, not a separator).
 *  - **lines**: non-blank lines. Blank lines separate stanzas — they are
 *    structure, not verse lines, so they do not inflate the gedig line count.
 *    (The blank-line structure itself is preserved verbatim by the 6.3 editor.)
 *
 * Pure value logic, no WordPress state. Conflation-clean — no `Ink\Tiers`.
 *
 * @package Ink\Core
 */
final class Counters {

	/**
	 * Count words (UTF-8 non-whitespace tokens) in a body.
	 *
	 * @param string $text The body text.
	 * @return int The word count (0 for empty / whitespace-only).
	 */
	public static function words( string $text ): int {
		return (int) preg_match_all( '/\S+/u', $text );
	}

	/**
	 * Count non-blank lines in a body.
	 *
	 * @param string $text The body text.
	 * @return int The non-blank line count.
	 */
	public static function lines( string $text ): int {
		$parts = preg_split( '/\R/u', $text );

		if ( false === $parts ) {
			return 0;
		}

		$count = 0;

		foreach ( $parts as $line ) {
			if ( '' !== trim( $line ) ) {
				++$count;
			}
		}

		return $count;
	}

	/**
	 * The type-aware counter result for a body.
	 *
	 * @param string $type The bydrae CPT slug.
	 * @param string $text The body text.
	 * @return array{lines:int|null, words:int} `lines` is null for prose types.
	 */
	public static function forType( string $type, string $text ): array {
		return array(
			'lines' => ContentType::countsLines( $type ) ? self::lines( $text ) : null,
			'words' => self::words( $text ),
		);
	}
}

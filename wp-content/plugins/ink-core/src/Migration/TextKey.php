<?php
/**
 * Comparison keys for Afrikaans titles and names as people really type them.
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Migration;

defined( 'ABSPATH' ) || exit;

/**
 * The one place that decides whether two typed strings are "the same". Pure.
 *
 * Accents are hard to type on most keyboards, so writers drop or swap them, even in their own
 * names. The article 'n has no key at all, so it arrives as ŉ, ñ, ń, 'n, ’n, `n, ´n, n or N.
 * A key folds all of that away, along with case, spacing and punctuation. Keys are for
 * comparing only, never for display.
 */
final class TextKey {

	/**
	 * Accented letters and their plain forms. ŉ also ends a word: "ŉHond" is "'n hond".
	 *
	 * @var array<string, string>
	 */
	private const FOLD = array(
		'à' => 'a', 'á' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a', 'å' => 'a', 'ā' => 'a', 'ă' => 'a', 'ą' => 'a', // phpcs:ignore
		'ç' => 'c', 'ć' => 'c', 'č' => 'c', // phpcs:ignore
		'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e', 'ē' => 'e', 'ĕ' => 'e', 'ė' => 'e', 'ę' => 'e', 'ě' => 'e', // phpcs:ignore
		'ì' => 'i', 'í' => 'i', 'î' => 'i', 'ï' => 'i', 'ĩ' => 'i', 'ī' => 'i', 'į' => 'i', // phpcs:ignore
		'ñ' => 'n', 'ń' => 'n', 'ň' => 'n', 'ņ' => 'n', 'ǹ' => 'n', 'ŉ' => 'n ', // phpcs:ignore
		'ò' => 'o', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o', 'ö' => 'o', 'ø' => 'o', 'ō' => 'o', 'ő' => 'o', // phpcs:ignore
		'š' => 's', 'ś' => 's', 'ß' => 'ss', // phpcs:ignore
		'ù' => 'u', 'ú' => 'u', 'û' => 'u', 'ü' => 'u', 'ũ' => 'u', 'ū' => 'u', 'ű' => 'u', // phpcs:ignore
		'ý' => 'y', 'ÿ' => 'y', 'ž' => 'z', 'ź' => 'z', 'ż' => 'z', // phpcs:ignore
	);

	/**
	 * Old posts hold double-encoded entities and quotes that were saved in the wrong encoding.
	 *
	 * @param string $text The typed text.
	 * @return string The text as it was meant.
	 */
	private static function decoded( string $text ): string {
		$text = html_entity_decode( html_entity_decode( $text, ENT_QUOTES | ENT_HTML5, 'UTF-8' ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );

		return str_replace( array( "\u{00E2}\u{20AC}\u{2122}", "\u{00E2}\u{20AC}\u{02DC}", "\u{00C5}\u{2030}" ), "'", $text );
	}

	/**
	 * Lowercase, entity-free, accent-free words. Everything else builds on this.
	 *
	 * @param string $text The typed text.
	 * @return list<string> The words.
	 */
	private static function words( string $text ): array {
		$text = self::decoded( $text );
		$text = (string) preg_replace( '/\p{Mn}+/u', '', mb_strtolower( $text ) );
		$text = strtr( $text, self::FOLD );

		// Apostrophes join nothing: "'n", "se'" and "n" are all just letters once they go.
		$text = (string) preg_replace( "/['\x{2018}\x{2019}\x{201A}\x{201B}\x{02BC}\x{02BB}\x{0060}\x{00B4}\x{2032}\x{FF07}]+/u", '', $text );
		$text = (string) preg_replace( '/[^\p{L}\p{N}]+/u', ' ', $text );

		return array_values( array_filter( explode( ' ', $text ), static fn ( string $w ): bool => '' !== $w ) );
	}

	/**
	 * Plain text with accents, case and punctuation folded, for comparing any two strings.
	 *
	 * @param string $text The typed text.
	 * @return string The key, without spaces.
	 */
	public static function text( string $text ): string {
		return implode( '', self::words( $text ) );
	}

	/**
	 * A title's key: as {@see text()}, without a leading 'n, which writers add and drop.
	 *
	 * @param string $title The typed title.
	 * @return string The key, without spaces.
	 */
	public static function title( string $title ): string {
		$words = self::words( self::withoutMarkers( $title ) );

		if ( count( $words ) > 1 && 'n' === $words[0] ) {
			array_shift( $words );
		}

		return array() === $words ? self::text( $title ) : implode( '', $words );
	}

	/**
	 * A title without the grade, class, word count and project that writers add to it.
	 *
	 * The forms are the ones found in the titles on prod: "(Silwer gradering)", "[brons]",
	 * "{silwer}", "Goudklas", "bronsgradering", "Katergorie - Goud", "(silwer 490 woorde)",
	 * "Silwer- Limiriek", "Stille waters Silwer 350 Woorde April projek". A grade word inside
	 * a title stays: "Drie silwer bangles", "Goudgeel lande".
	 *
	 * @param string $title The typed title.
	 * @return string The title without markers; empty if nothing else was in it.
	 */
	public static function withoutMarkers( string $title ): string {
		$grade  = '(?:goud|silwer|silver|brons|bronze|gold)';
		$scale  = '(?:st(?:e|re)r)?gradering';
		$group  = '(?:' . $scale . '|klas|kategorie|katergorie|afdeling)';
		$month  = '(?:januarie|februarie|maart|april|mei|junie|julie|augustus|september|oktober|november|desember)';
		$months = $month . '(?:\s*\/\s*' . $month . ')?';
		$edge   = '[\s\-\x{2013}\x{2014}:;,.!]*';

		$text = self::decoded( $title );
		$text = strtr( mb_strtolower( $text ), self::FOLD );

		do {
			$before = $text;
			$text   = (string) preg_replace(
				array(
					'/[\(\[\{][^\)\]\}]*\b(?:' . $grade . '(?:klas|gradering)?|goue|' . $scale . ')\b[^\)\]\}]*[\)\]\}]/u',
					'/\b' . $grade . '(?:klas|gradering)\b/u',
					'/\b' . $group . '\s*[=:\-\x{2013}\x{2014}]*\s*' . $grade . '\b/u',
					'/\b' . $grade . '\s*[\-\x{2013}\x{2014}]*\s*' . $group . '\b/u',
					'/\b(?:st(?:e|re)r)gradering\b\s*[=:\-\x{2013}\x{2014}]*/u',
					'/\(?\b\d+\s*(?:woorde?|woode|words?|w)\b\)?/u',
					'/\b(?:' . $months . '\s*)?(?:20\d\d\s*)?(?:oop\s+)?(?:maandtema|projek|uitdaging)\b/u',
					'/[\(\[\{]\s*[gsb]\s*[\)\]\}]\s*$/u',
				),
				' ',
				$text
			);
			if ( $text !== $before ) {
				$text = (string) preg_replace( '/' . $edge . '\b' . $months . '(?:\s*20\d\d)?' . $edge . '$/u', ' ', $text );
			}
			$text = (string) preg_replace( '/' . $edge . '\b' . $grade . '\b' . $edge . '$/u', ' ', $text );
			$text = (string) preg_replace( '/^\s*' . $grade . '\s*[\-\x{2013}\x{2014}:]+\s*/u', ' ', $text );
		} while ( $text !== $before );

		return trim( $text );
	}

	/**
	 * A writer's key: as {@see text()}, without anything in brackets ("Carma Shaw (Viooltjie)").
	 *
	 * @param string $name The typed name.
	 * @return string The key, without spaces.
	 */
	public static function name( string $name ): string {
		return self::text( (string) preg_replace( '/\(.*?\)/u', '', $name ) );
	}
}

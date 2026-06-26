<?php
/**
 * Diacritic folding for accent-insensitive search — Story 8.4 (FR-35, AD-7).
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Discovery;

defined( 'ABSPATH' ) || exit;

/**
 * Folds text to a lowercased, accent-stripped form for diacritic-insensitive search.
 *
 * AD-7's `[BUILD]` fallback: rather than rely on the (unverified) cloned-DB
 * collation to fold accents, both the stored search index and the query term pass
 * through {@see self::fold()}, so `reën`/`reen` and `café`/`cafe` match in both
 * directions regardless of collation. Pure — no WordPress dependency.
 *
 * @package Ink\Core
 */
final class Diacritics {

	/**
	 * Accented character → base letter (lowercased input; covers the Afrikaans
	 * set ê ë é è / î ï í ì / ô ö ó ò / û ü ú ù / â ä á à and ç ñ for safety).
	 *
	 * @return array<string, string>
	 */
	private static function map(): array {
		return array(
			'à' => 'a',
			'á' => 'a',
			'â' => 'a',
			'ä' => 'a',
			'ã' => 'a',
			'å' => 'a',
			'è' => 'e',
			'é' => 'e',
			'ê' => 'e',
			'ë' => 'e',
			'ì' => 'i',
			'í' => 'i',
			'î' => 'i',
			'ï' => 'i',
			'ò' => 'o',
			'ó' => 'o',
			'ô' => 'o',
			'ö' => 'o',
			'õ' => 'o',
			'ù' => 'u',
			'ú' => 'u',
			'û' => 'u',
			'ü' => 'u',
			'ý' => 'y',
			'ÿ' => 'y',
			'ç' => 'c',
			'ñ' => 'n',
		);
	}

	/**
	 * Fold text to its lowercased, diacritic-stripped, whitespace-collapsed form.
	 *
	 * @param string $text The input text.
	 * @return string
	 */
	public static function fold( string $text ): string {
		$lower  = function_exists( 'mb_strtolower' ) ? mb_strtolower( $text, 'UTF-8' ) : strtolower( $text );
		$folded = strtr( $lower, self::map() );

		// Collapse any run of whitespace to a single space and trim.
		$collapsed = preg_replace( '/\s+/u', ' ', $folded );

		return trim( null === $collapsed ? $folded : $collapsed );
	}
}

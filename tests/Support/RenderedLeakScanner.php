<?php
/**
 * Live English-leak detector (NFR-1 Layer 2) — Story 18.8.
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Support;

/**
 * The runtime counterpart to the static `composer copy:scan` placeholder ratchet:
 * scans RENDERED front-end HTML for suspected-English visible text on an
 * Afrikaans-first site (Quality Gate D / NFR-1).
 *
 * The static scan catches unauthored-copy placeholders in source; this catches
 * actual English that slips through at render time — plugin-composed sentences,
 * un-translated third-party strings, post-update regressions — the §12 leak vectors
 * a page-crawl exposes. The detection is a pure function over an HTML string so it
 * unit-tests here; the crawl orchestration (fetch the key pages, feed each page's
 * HTML in, plus `wp i18n` untranslated counts) needs a running site and runs in
 * CI/cron (see tools/leak-scan/scan-rendered.php + docs/test-pyramid-plan.md).
 *
 * Heuristic: tokenise the VISIBLE text (tags/script/style stripped) and flag tokens
 * that are common English function/UI words which should never appear on an
 * Afrikaans front end, minus an allowlist of brand names / shared loanwords. It is a
 * tripwire (high-signal English markers), not a dictionary — a flagged page is
 * triaged by a human, never auto-translated (AI Afrikaans is forbidden).
 *
 * @package Ink\Tests
 */
final class RenderedLeakScanner {

	/**
	 * High-signal English marker words that must not appear in Afrikaans front-end
	 * copy. Deliberately small + unambiguous (these are not Afrikaans words and are
	 * not shared loanwords): function words + the most common WP/Woo/BP UI verbs.
	 *
	 * @var list<string>
	 */
	public const ENGLISH_MARKERS = array(
		'the', 'your', 'you', 'and', 'with', 'please', 'welcome', 'login', 'log',
		'logout', 'sign', 'submit', 'password', 'username', 'register', 'account',
		'search', 'read', 'more', 'comment', 'reply', 'subscribe', 'cart', 'checkout',
		'add', 'remove', 'settings', 'profile', 'follow', 'following', 'message',
		'sent', 'failed', 'required', 'email',
	);

	/**
	 * Tokens that LOOK English but are legitimate on the INK front end (brand names,
	 * shared loanwords, proper nouns). Lower-cased.
	 *
	 * @var list<string>
	 */
	public const ALLOWLIST = array(
		'ink', 'inkpols', 'payfast', 'facebook', 'instagram', 'youtube', 'pdf',
		'epos', // Afrikaans for e-mail; never flag.
	);

	/**
	 * The English-marker tokens found in a rendered page's visible text. Pure.
	 *
	 * @param string       $html      The rendered HTML.
	 * @param list<string> $allowlist Extra allowlisted tokens (merged with {@see ALLOWLIST}).
	 * @return list<string> The distinct offending tokens, in first-seen order.
	 */
	public static function candidates( string $html, array $allowlist = array() ): array {
		$text = self::visibleText( $html );

		$allow   = array_map( 'strtolower', array_merge( self::ALLOWLIST, $allowlist ) );
		$markers = self::ENGLISH_MARKERS;

		$found = array();

		foreach ( self::tokens( $text ) as $token ) {
			if ( in_array( $token, $allow, true ) ) {
				continue;
			}

			if ( in_array( $token, $markers, true ) && ! in_array( $token, $found, true ) ) {
				$found[] = $token;
			}
		}

		return $found;
	}

	/**
	 * Whether a rendered page is clean (no English markers). Pure.
	 *
	 * @param string       $html      The rendered HTML.
	 * @param list<string> $allowlist Extra allowlisted tokens.
	 */
	public static function isClean( string $html, array $allowlist = array() ): bool {
		return array() === self::candidates( $html, $allowlist );
	}

	/**
	 * The visible text of an HTML document (script/style/tags removed). Pure.
	 *
	 * @param string $html The HTML.
	 * @return string
	 */
	public static function visibleText( string $html ): string {
		// Drop script/style bodies entirely, then replace remaining tags with a
		// SPACE (not nothing) so adjacent block elements don't merge their text into
		// one token (e.g. "</h1><p>" must not glue "account" to "Please"). Native +
		// WordPress-free so the detector unit-tests without WP loaded.
		$stripped = (string) preg_replace( '#<(script|style)\b[^>]*>.*?</\1>#is', ' ', $html );
		$stripped = (string) preg_replace( '/<[^>]+>/', ' ', $stripped );

		return trim( (string) preg_replace( '/\s+/', ' ', $stripped ) );
	}

	/**
	 * Lower-cased alphabetic tokens of a text. Pure.
	 *
	 * @param string $text The visible text.
	 * @return list<string>
	 */
	private static function tokens( string $text ): array {
		preg_match_all( '/[A-Za-z]+/', $text, $matches );

		return array_map( 'strtolower', $matches[0] );
	}
}

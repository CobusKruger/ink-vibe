<?php
/**
 * The strict structure-preserving body sanitiser for the light editor — Story 6.3.
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Submission;

defined( 'ABSPATH' ) || exit;

/**
 * Sanitises a Skryf body to a deliberately tiny inline allowlist (FR-18).
 *
 * The light editor exists so shaped / concrete poetry survives: the ONLY marks
 * that may persist are bold, italic, and hard line breaks. Everything else —
 * headings, tables, inline images, embeds, lists, blockquotes, links, iframes,
 * and ALL attributes (so no inline `style`/`class` → no font / colour / size
 * controls) — is stripped, keeping only the text content.
 *
 * Crucially this preserves line structure VERBATIM: it delegates tag-stripping to
 * the battle-tested {@see wp_kses()} (which never touches text nodes), and adds NO
 * `trim()` or whitespace normalisation of its own, so newlines, blank stanza
 * separators, and leading whitespace (indentation) are stored exactly as written.
 * The reading-side stanza-aware rendering is Story 7.2; this guarantees the STORED
 * body is faithful.
 *
 * Pure allowlist policy, no business state. Conflation-clean — no `Ink\Tiers`.
 *
 * @package Ink\Core
 */
final class ProseSanitizer {

	/**
	 * The strict inline allowlist: bold, italic, hard break — NO attributes.
	 *
	 * Empty attribute arrays mean any `style` / `class` / `color` / `size` on an
	 * allowed tag is dropped (closing "no font/colour/size controls"); any tag NOT
	 * listed (heading, table, img, span, a, ul, blockquote, iframe, …) is reduced
	 * to its text content by {@see wp_kses()}.
	 *
	 * @return array<string, array<string, mixed>> The `wp_kses` allowed-HTML map.
	 */
	public static function allowedTags(): array {
		return array(
			'strong' => array(),
			'b'      => array(),
			'em'     => array(),
			'i'      => array(),
			'br'     => array(),
		);
	}

	/**
	 * Sanitise a raw body to the strict allowlist, preserving structure verbatim.
	 *
	 * @param string $raw The unslashed raw body.
	 * @return string The sanitised body — only bold/italic/break, structure intact.
	 */
	public static function sanitize( string $raw ): string {
		return wp_kses( $raw, self::allowedTags() );
	}
}

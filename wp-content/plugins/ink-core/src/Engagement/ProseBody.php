<?php
/**
 * The prose paragraph tokeniser — text-highlight reactions (post-Epic-19, FR-24/26).
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Engagement;

defined( 'ABSPATH' ) || exit;

/**
 * Splits a storie/artikel body into paragraph tokens (the write-path anchor unit).
 *
 * Storie/artikel already render through the core `wp:post-content` block (Story
 * 7.1) — that stays as-is, no display change here. The theme numbers the SAME
 * rendered `<p>` elements client-side (`data-ink-para`, 0-based, DOM order) for
 * the reading surface; this class is the SERVER-side mirror of that numbering,
 * used ONLY to validate a submitted anchor on the `ink/v1/reaksie` write path
 * ({@see ReactionController::validate()}) — the "enforce the module-owned
 * guarantee on every path, not just the display" durability rule Story 7.3
 * established for {@see GedigBody::tokenize()}.
 *
 * The 6.3 light editor + {@see \Ink\Submission\ProseSanitizer} store a bydrae
 * body as plain text + a tiny inline allowlist (bold/italic/break) with real
 * blank-line paragraph breaks (the same verbatim-newline convention gedig
 * relies on) — `wp:post-content`'s `wpautop` pass turns each blank-line-
 * delimited block into one `<p>`. For that restricted input domain, splitting
 * on runs of blank lines reproduces `wpautop`'s paragraph count/order exactly,
 * so the client's DOM-order numbering and this tokeniser never drift apart.
 *
 * @package Ink\Core
 */
final class ProseBody {

	/**
	 * Split a stored body into 0-based paragraph tokens.
	 *
	 * A "paragraph" is a run of non-blank physical lines bounded by one or more
	 * blank lines (or the start/end of the body) — mirrors `wpautop`'s own
	 * blank-line paragraph-break rule for this plain-text-plus-inline-marks
	 * input domain. Purely a string→array mapping; no WordPress calls.
	 *
	 * @param string $body The raw stored body.
	 * @return array<int, array{type:string, index:int, text:string}>
	 */
	public static function tokenize( string $body ): array {
		$normalized = str_replace( array( "\r\n", "\r" ), "\n", $body );
		$blocks     = preg_split( '/\n[ \t]*\n+/', trim( $normalized ) );
		$blocks     = false === $blocks ? array() : $blocks;

		$tokens = array();
		$index  = 0;

		foreach ( $blocks as $block ) {
			if ( '' === trim( $block ) ) {
				continue;
			}

			$tokens[] = array(
				'type'  => 'paragraph',
				'index' => $index,
				'text'  => $block,
			);
			++$index;
		}

		return $tokens;
	}

	/**
	 * The first real paragraph's 0-based index, or `null` for an empty body.
	 * Story 7.8 follow-up — the floating single-heart "enkel" total
	 * ({@see ReactionTotals::toHtmlEnkel()}) needs one stable, always-valid
	 * anchor to react against. Pure.
	 *
	 * @param string $body The raw stored body.
	 * @return int|null
	 */
	public static function firstParagraphIndex( string $body ): ?int {
		$tokens = self::tokenize( $body );

		return array() !== $tokens ? (int) $tokens[0]['index'] : null;
	}

	/**
	 * Whether `$index` is a real paragraph index of the body (not out-of-range).
	 *
	 * @param int    $index The submitted paragraph index.
	 * @param string $body  The raw stored body.
	 * @return bool
	 */
	public static function isParagraphIndex( int $index, string $body ): bool {
		foreach ( self::tokenize( $body ) as $token ) {
			if ( $token['index'] === $index ) {
				return true;
			}
		}

		return false;
	}
}

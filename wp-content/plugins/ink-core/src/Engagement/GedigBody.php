<?php
/**
 * The stanza-aware gedig body renderer — Story 7.2 (FR-25).
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Engagement;

use Ink\Kernel\ProseFormat;

defined( 'ABSPATH' ) || exit;

/**
 * Renders a poem's verbatim body as a stanza-aware, resonance-anchored layout.
 *
 * The 6.3 light editor stores a gedig body with its line structure intact: literal
 * `\n` newlines, blank stanza separators, and leading whitespace (indentation for
 * shaped / concrete poetry), with only the strict inline marks (`strong/b/em/i/br`)
 * surviving. The default `the_content` path would run `wpautop` over that and
 * collapse the structure — so this renders through a dedicated server block
 * (`ink/gedig-body`, AD-7) that reads the RAW body and rebuilds it faithfully:
 *
 *   - line breaks + blank-line / stanza spacing preserved verbatim (every blank
 *     physical line becomes a real gap);
 *   - leading whitespace preserved (the theme styles each line `white-space:
 *     pre-wrap`, never an HTML whitespace collapse);
 *   - author-entered Roman-numeral stanza markers flagged for styling;
 *   - every CONTENT line carries a stable `data-ink-line` resonance anchor (the
 *     0-based physical-line index) — the contract Story 7.3 consumes; blank
 *     separators carry none and are not resonance-able.
 *
 * Three-layer: this owns the INK line model + structural HTML (dynamic, INK-tied);
 * the theme owns the CSS. The inline allowlist comes from {@see ProseFormat} (the
 * same set the write-time sanitiser uses) — Engagement depends only on the Kernel.
 *
 * @package Ink\Core
 */
final class GedigBody {

	/**
	 * The block name (single source for the renderer + the theme pattern embed).
	 *
	 * @var string
	 */
	public const BLOCK = 'ink/gedig-body';

	/**
	 * Non-whitespace sentinel standing in for "a stanza break belongs here"
	 * during {@see self::normalizeLegacyMarkup()} — never a real newline until
	 * that method's final step, so intermediate whitespace-eating regexes in
	 * the same pass can never accidentally consume it. `\x00` bytes cannot
	 * occur in real stored post content, so collision is not a concern.
	 *
	 * @var string
	 */
	private const STANZA_PLACEHOLDER = "\x00INK_STANZA\x00";

	/**
	 * Sentinel standing in for "a line break belongs here" — see
	 * {@see self::STANZA_PLACEHOLDER}.
	 *
	 * @var string
	 */
	private const LINE_PLACEHOLDER = "\x00INK_LINE\x00";

	/**
	 * Register the server-rendered block.
	 *
	 * Invoked from {@see Module::register()}, which the Kernel already dispatches
	 * on `init` — so `registerBlock()` is called DIRECTLY here rather than nesting
	 * a second `add_action( 'init', … )` from within the running `init` hook.
	 */
	public function register(): void {
		self::registerBlock();
	}

	/**
	 * Register the `ink/gedig-body` dynamic block.
	 *
	 * Render-only server block (no editor UI) — used inside the locked
	 * `single-gedig` reading pattern, so PHP registration with a `render_callback`
	 * is sufficient; no block.json is needed.
	 */
	public static function registerBlock(): void {
		if ( ! function_exists( 'register_block_type' ) ) {
			return;
		}

		register_block_type(
			self::BLOCK,
			array(
				'render_callback' => array( self::class, 'render' ),
			)
		);
	}

	/**
	 * Block render callback: render the current post's raw body as a poem.
	 *
	 * Reads the RAW `post_content` (never `the_content`/`wpautop`, which would
	 * collapse the verbatim structure) for the post in the current loop.
	 *
	 * @return string The stanza-aware HTML, or an empty container when no post.
	 */
	public static function render(): string {
		$post = function_exists( 'get_post' ) ? get_post() : null;

		if ( ! $post instanceof \WP_Post ) {
			return self::toHtml( '' );
		}

		return self::toHtml( $post->post_content );
	}

	/**
	 * Split a stored body into physical-line tokens.
	 *
	 * Each `\n`-delimited physical line becomes either a `blank` token (the line is
	 * empty or whitespace-only — a stanza separator) or a `line` token carrying its
	 * 0-based physical-line `index`, its verbatim `text` (leading whitespace + inline
	 * marks intact — NOT trimmed), and whether it is a Roman-numeral `marker`.
	 *
	 * `$body` is run through {@see self::normalizeLegacyMarkup()} FIRST (a no-op for
	 * the majority raw-`\n` format) so both real historical HTML-markup storage
	 * formats and the raw format tokenize identically from here on — see that
	 * method's docblock for why (product-owner correction: this is real historical
	 * data in a valid legacy format, not corruption to rewrite).
	 *
	 * @param string $body The raw stored body.
	 * @return array<int, array{type:string, index?:int, text?:string, marker?:bool}>
	 */
	public static function tokenize( string $body ): array {
		$lines  = explode( "\n", self::normalizeLegacyMarkup( $body ) );
		$tokens = array();

		foreach ( $lines as $index => $line ) {
			if ( '' === trim( $line ) ) {
				$tokens[] = array( 'type' => 'blank' );
				continue;
			}

			$tokens[] = array(
				'type'   => 'line',
				'index'  => $index,
				'text'   => $line,
				'marker' => self::isRomanNumeralMarker( trim( $line ) ),
			);
		}

		return $tokens;
	}

	/**
	 * Normalise a legacy HTML-markup body to the plain `\n`-per-line format
	 * {@see self::tokenize()} expects — a NO-OP for the majority raw format.
	 *
	 * Product-owner correction: the Gutenberg-wrapped / `<br>`-joined content on
	 * some `gedig` posts is real historical data, captured verbatim at migration
	 * time from an old theme's storage format — it was never corrupted, and must
	 * never be rewritten to suit this parser. `tokenize()` has to learn to READ
	 * it instead. This method is that read-time adapter.
	 *
	 * Evidence, not guesswork: a full-database scan (2026-09, this pass) found
	 * ~5,585 `gedig` posts sharing SOME HTML-markup shape, sampled in depth
	 * before this was written. Five distinct real shapes turned up:
	 *
	 *  1. Genuine raw `\n`-per-line (the majority, unaffected — no `<p>`, `<div>`,
	 *     `<br>`, or `<!-- wp: -->` anywhere in the body at all; this method's
	 *     first line is a fast, zero-risk no-op return for exactly this case).
	 *  2. Gutenberg-wrapped, one `<p>` for the WHOLE body, `<br>`-joined lines
	 *     inside (2 posts confirmed directly — 67912, 67904 — restored to this
	 *     exact original form after an earlier, incorrect "fix the data" pass).
	 *  3. The DOMINANT legacy shape (~5,578 of the ~5,585): NO Gutenberg wrapper
	 *     at all, one bare `<p>…</p>` per STANZA, `<br>`/`<br />` joining that
	 *     stanza's lines inside it — pre-Gutenberg (classic-editor-era) HTML,
	 *     migrated/imported as raw markup.
	 *  4. A Facebook-paste shape (7 posts): nested `<div class="html-div …">` /
	 *     `<span class="…">` wrappers (Meta's own atomic CSS class names) with
	 *     `<br class="html-br" />` line breaks; a bare EMPTY `<div></div>` pair
	 *     is Meta's own paragraph-spacer and is the real stanza-break signal —
	 *     ordinary (non-empty) `</div><div>` transitions are just the next LINE
	 *     of the same stanza, not a stanza break.
	 *  5. A "one `<p>` per LINE" shape (2 posts): no `<br>` anywhere; each line is
	 *     its own `<p>…</p>`, and a blank/`&nbsp;`-only `<p>` is the stanza-break
	 *     signal — an ordinary `</p><p>` transition here is just the next line,
	 *     the OPPOSITE convention from shape 3's `</p><p>` (there, a stanza
	 *     break). Distinguished from shape 3 by the one reliable per-post signal
	 *     that actually differs between them: whether `<br>` appears anywhere.
	 *
	 * Approach: convert every shape's real structural boundaries into one of two
	 * non-whitespace placeholder tokens (`self::STANZA_PLACEHOLDER` /
	 * `self::LINE_PLACEHOLDER`) — NOT directly into `\n`/`\n\n` — because later
	 * clean-up steps in this same pass consume incidental pretty-printing
	 * whitespace around remaining tags (`\s*<tag>\s*` → `''`); if the meaningful
	 * breaks were literal newlines at that point, that clean-up would eat them
	 * right back out. Real newlines are materialised from the placeholders only
	 * as the very last step, once no further whitespace-eating regex will run.
	 *
	 * Known, disclosed imperfection (7 Facebook-paste posts only): a stanza
	 * boundary can pick up one extra blank separator (an adjacent ordinary
	 * `</div><div>` transition firing alongside the dedicated empty-div stanza
	 * marker) — a harmless slightly-larger visual gap, not glued/lost content;
	 * not worth a full nested-HTML parser for 7 posts.
	 *
	 * @param string $body The raw stored body, either format.
	 * @return string Plain `\n`-per-line text, ready for `explode( "\n", … )`.
	 */
	private static function normalizeLegacyMarkup( string $body ): string {
		if ( ! preg_match( '/<p\b|<div\b|<br\b|<!--\s*wp:/i', $body ) ) {
			return $body; // The raw \n format — the majority of posts — untouched.
		}

		$stanza = self::STANZA_PLACEHOLDER;
		$line   = self::LINE_PLACEHOLDER;

		// 1. Any HTML comment is pure noise here — Gutenberg block comments
		// (the expected case), but evidence also turned up third-party-app
		// export artifacts (e.g. a Samsung Notes clipboard-metadata comment
		// glued onto a signature line by a copy-paste import) that are just as
		// much noise; a poem never legitimately contains an HTML comment as
		// real content. Left unstripped, such a comment would otherwise become
		// its OWN bare, still-interactive resonance line once rendered (the
		// exact "bare comment-only token" symptom this whole fix targets) —
		// {@see wp_kses()} would already strip it at render time regardless,
		// so stripping it here just avoids minting a spurious empty line/anchor
		// for it in the first place.
		$body = (string) preg_replace( '/\s*<!--.*?-->\s*/s', '', $body );

		// 2. A bare, empty <div></div> pair (Meta's own paragraph-spacer, shape 4)
		// is an explicit stanza break — consumed BEFORE the generic </div><div>
		// line-boundary rule below can see (and misread) it as just a line.
		$body = (string) preg_replace( '/\s*<div>\s*<\/div>\s*/i', $stanza, $body );

		// 3. Shape 3 vs shape 5 both use <p>, with OPPOSITE </p><p> conventions —
		// the one reliable signal that differs is whether <br> appears anywhere.
		$has_br            = 1 === preg_match( '/<br\b/i', $body );
		$paragraph_boundary = $has_br ? $stanza : $line;

		// 4. A blank/&nbsp;-only <p> is shape 5's explicit stanza-break signal
		// (harmless no-op for shape 3, which never produces one in practice).
		$body = (string) preg_replace( '/\s*<p[^>]*>\s*(?:&nbsp;|\xC2\xA0)?\s*<\/p>\s*/i', $stanza, $body );

		// 5. <br> / <br/> / <br /> / <br class="…" /> (any attributes) is always
		// a line break — its own trailing pretty-print whitespace is consumed in
		// the SAME match so it can never double up with a following real break.
		$body = (string) preg_replace( '/<br\b[^>]*>\s*/i', $line, $body );

		// 6. </p> immediately followed by <p …> — shape-dependent (step 3).
		$body = (string) preg_replace( '/<\/p>\s*<p\b[^>]*>/i', $paragraph_boundary, $body );

		// 7 & 8. </div><div> / </span><span> — always just the next line (shape
		// 4's only stanza signal, the empty-div pair, is already consumed above).
		$body = (string) preg_replace( '/<\/div>\s*<div\b[^>]*>/i', $line, $body );
		$body = (string) preg_replace( '/<\/span>\s*<span\b[^>]*>/i', $line, $body );

		// 9. Whatever p/div/span tags remain (the very first opening tag, the
		// very last closing tag, wrappers that never bordered another wrapper
		// tag) are pure structural noise now — strip fully, whitespace included.
		$body = (string) preg_replace( '/\s*<\/?(?:p|div|span)\b[^>]*>\s*/i', '', $body );

		// 10. Materialise the placeholders — only now, once nothing further in
		// this pass will eat whitespace and risk consuming a real newline.
		return str_replace( array( $stanza, $line ), array( "\n\n", "\n" ), $body );
	}

	/**
	 * Whether a trimmed line is an author-entered Roman-numeral stanza marker.
	 *
	 * Heuristic: a non-empty line made only of uppercase Roman letters
	 * (`I V X L C D M`) with an optional trailing period — e.g. `I`, `II`, `IV`, `I.`.
	 * A standalone English/Afrikaans word never matches the Roman-letter-only set;
	 * Arabic numerals do not match either.
	 *
	 * @param string $trimmed The already-trimmed line text.
	 * @return bool True when the line is a Roman-numeral marker.
	 */
	public static function isRomanNumeralMarker( string $trimmed ): bool {
		return '' !== $trimmed && 1 === preg_match( '/^[IVXLCDM]+\.?$/', $trimmed );
	}

	/**
	 * Build the stanza-aware HTML for a stored body.
	 *
	 * Consecutive content lines are grouped into `ink-gedig__stanza` containers;
	 * each blank physical line emits an `ink-gedig__sep` element so the original
	 * blank-line spacing is reproduced verbatim. Content lines are re-sanitised on
	 * output through the shared inline allowlist (preserves text nodes incl. leading
	 * spaces; strips/escapes anything else) and carry their resonance anchor.
	 *
	 * @param string $body The raw stored body.
	 * @return string The stanza-aware HTML.
	 */
	public static function toHtml( string $body ): string {
		$tokens  = self::tokenize( $body );
		$allowed = ProseFormat::allowedInlineTags();

		$html        = '<div class="ink-gedig">';
		$stanza_open = false;

		foreach ( $tokens as $token ) {
			if ( 'blank' === $token['type'] ) {
				if ( $stanza_open ) {
					$html       .= '</div>';
					$stanza_open = false;
				}
				$html .= '<div class="ink-gedig__sep" aria-hidden="true"></div>';
				continue;
			}

			// Sanitize BEFORE deciding whether this token has any real content.
			// tokenize()'s own blank-check runs on the RAW pre-sanitization text
			// (`'' === trim( $line )`) and rightly stays that way — it can't know
			// what the inline allowlist will do. But a line whose raw text is
			// disallowed markup with nothing else (e.g. a stray legacy `<ul>`
			// footnote artifact survived by {@see self::normalizeLegacyMarkup()})
			// is NOT whitespace-only raw text, so it tokenizes as `line`, yet
			// sanitizes down to nothing visible — rendering it as-is would mint a
			// genuinely empty `<p data-ink-line>` that `line-reactions.js` still
			// treats as fully interactive (heart + hover highlight over blank
			// space). Checking the RENDERED result (not `trim($token['text'])`
			// again, which is exactly the raw check that already missed this)
			// catches that: `strip_tags()` here strips even the ALLOWED inline
			// tags too (an empty `<em></em>` or a bare `<br>` carries no visible
			// text either), so only genuinely-nothing-left counts as empty — a
			// line that's just punctuation, a single word, an em dash, etc. still
			// has real text and stays interactive.
			$rendered = wp_kses( (string) $token['text'], $allowed );

			if ( '' === trim( strip_tags( $rendered ) ) ) {
				if ( $stanza_open ) {
					$html       .= '</div>';
					$stanza_open = false;
				}
				$html .= '<div class="ink-gedig__sep" aria-hidden="true"></div>';
				continue;
			}

			if ( ! $stanza_open ) {
				$html       .= '<div class="ink-gedig__stanza">';
				$stanza_open = true;
			}

			$classes = 'ink-gedig__line';
			if ( ! empty( $token['marker'] ) ) {
				$classes .= ' ink-gedig__line--marker';
			}

			// The text is wrapped in an inner `.ink-gedig__line-text` span (shrink-
			// to-fit, `display:inline-block`) rather than positioned straight off
			// the full-width `<p>` — this is the anchor the theme's per-line heart
			// toggle (Story 7.3 post-Epic-19 fidelity pass) positions itself
			// against, so it sits right after each line's own text (Lovable's
			// `PoetryReader.tsx` wraps its line text in an identical
			// `relative inline-block` span for the same reason), not pinned to a
			// fixed column unrelated to line length.
			$html .= '<p class="' . $classes . '" data-ink-line="' . esc_attr( (string) $token['index'] ) . '" data-audit-id="gedig-stanza-line">'
				. '<span class="ink-gedig__line-text">' . $rendered . '</span>'
				. '</p>';
		}

		if ( $stanza_open ) {
			$html .= '</div>';
		}

		$html .= '</div>';

		return $html;
	}
}

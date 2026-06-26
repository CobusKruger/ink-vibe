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
	 * Register the server-rendered block on `init`.
	 */
	public function register(): void {
		add_action( 'init', array( self::class, 'registerBlock' ) );
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
	 * @param string $body The raw stored body.
	 * @return array<int, array{type:string, index?:int, text?:string, marker?:bool}>
	 */
	public static function tokenize( string $body ): array {
		$lines  = explode( "\n", $body );
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

			if ( ! $stanza_open ) {
				$html       .= '<div class="ink-gedig__stanza">';
				$stanza_open = true;
			}

			$classes = 'ink-gedig__line';
			if ( ! empty( $token['marker'] ) ) {
				$classes .= ' ink-gedig__line--marker';
			}

			$html .= '<p class="' . $classes . '" data-ink-line="' . esc_attr( (string) $token['index'] ) . '">'
				. wp_kses( (string) $token['text'], $allowed )
				. '</p>';
		}

		if ( $stanza_open ) {
			$html .= '</div>';
		}

		$html .= '</div>';

		return $html;
	}
}

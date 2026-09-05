<?php
/**
 * Reaction totals (resonance counts) server block — Story 7.8 (FR-28).
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Engagement;

use Ink\Kernel\Reaction;

defined( 'ABSPATH' ) || exit;

/**
 * Renders the `ink/reaksie-tellers` block: a work's verb-less resonance counts.
 *
 * Shows each reaction's total (icon + `_n()`-formatted count) without vanity
 * framing — the icon does the verb (FR-28). All counts pass through the single-
 * source {@see ReactionCounts::label()} so every surface reads identically.
 *
 * @package Ink\Core
 */
final class ReactionTotals {

	/**
	 * The block name.
	 *
	 * @var string
	 */
	public const BLOCK = 'ink/reaksie-tellers';

	/**
	 * Decorative glyph per reaction (the verb-replacing icon).
	 *
	 * @return array<string, string>
	 */
	private static function glyphs(): array {
		return array(
			Reaction::Hartjie->value => '♥',
			Reaction::DuimOp->value  => '👍',
			Reaction::Wow->value     => '✨',
		);
	}

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
	 * Register the `ink/reaksie-tellers` dynamic block.
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
	 * Block render callback for the current work.
	 *
	 * @param array<string, mixed> $attributes Parsed block attributes (e.g. `variant`).
	 * @return string
	 */
	public static function render( array $attributes = array() ): string {
		$post_id = function_exists( 'get_the_ID' ) ? (int) get_the_ID() : 0;

		if ( $post_id <= 0 ) {
			return '';
		}

		$variant = isset( $attributes['variant'] ) && is_string( $attributes['variant'] ) ? $attributes['variant'] : 'volledig';

		return self::toHtml( ReactionStore::countsForPost( $post_id ), $variant );
	}

	/**
	 * Build the verb-less totals HTML. Pure — formatter + escaping only.
	 *
	 * @param array<string, int> $counts  Reaction value → total.
	 * @param string             $variant `'volledig'` (default, all 3 reactions —
	 *                                    storie/artikel) or `'enkel'` (single
	 *                                    hartjie count only — lees-gedig, matching
	 *                                    Lovable's single heart+count floating-bar
	 *                                    button; theme-fidelity re-audit, page 3,
	 *                                    product-owner decision).
	 * @return string
	 */
	public static function toHtml( array $counts, string $variant = 'volledig' ): string {
		if ( 'enkel' === $variant ) {
			return self::toHtmlEnkel( $counts );
		}

		$glyphs = self::glyphs();

		$html = '<div class="ink-reaksie-tellers">';

		foreach ( Reaction::cases() as $reaction ) {
			$n     = isset( $counts[ $reaction->value ] ) ? (int) $counts[ $reaction->value ] : 0;
			$glyph = $glyphs[ $reaction->value ] ?? '';

			$html .= '<span class="ink-reaksie-tellers__item ink-reaksie-tellers--' . esc_attr( $reaction->value ) . '">'
				. '<span class="ink-reaksie-tellers__glyph" aria-hidden="true">' . esc_html( $glyph ) . '</span> '
				. esc_html( ReactionCounts::label( $reaction, $n ) )
				. '</span>';
		}

		$html .= '</div>';

		return $html;
	}

	/**
	 * The `'enkel'` variant: a single hartjie glyph + bare count, no per-reaction
	 * label text — the reading page's floating action bar "like" affordance
	 * (Lovable's `ReadStory.tsx` floating bar renders `<Heart/><span>{likeCount}</span>`
	 * with no words, unconditionally for both poetry and prose — it is shared
	 * code, not gedig-specific). Still a truthful READ of the same underlying
	 * reaction totals (AD-5a) — collapsing the DISPLAY to one number is a
	 * presentation decision, not a new reaction type or a new store. Product-
	 * owner decision originated on lees-gedig (theme-fidelity third pass) and
	 * was confirmed to extend to lees-storie, which shares the same Lovable
	 * component — reading-artikel is NOT included (kept at the `'volledig'`
	 * default, untouched).
	 *
	 * @param array<string, int> $counts Reaction value → total.
	 * @return string
	 */
	private static function toHtmlEnkel( array $counts ): string {
		$n = isset( $counts[ Reaction::Hartjie->value ] ) ? (int) $counts[ Reaction::Hartjie->value ] : 0;

		$audit_id = self::enkelAuditId();

		return '<div class="ink-reaksie-tellers ink-reaksie-tellers--enkel"'
			. ( null !== $audit_id ? ' data-audit-id="' . esc_attr( $audit_id ) . '"' : '' )
			. ' aria-label="' . esc_attr( ReactionCounts::label( Reaction::Hartjie, $n ) ) . '">'
			. '<span class="ink-reaksie-tellers__hart" aria-hidden="true">' . self::heartOutlineSvg() . '</span>'
			. '<span class="ink-reaksie-tellers__telling">' . esc_html( (string) $n ) . '</span>'
			. '</div>';
	}

	/**
	 * The `data-audit-id` for the `'enkel'` variant — page-specific (gedig vs
	 * storie), resolved from the current singular post type so both reading
	 * pages' Tier-1 measurement anchors stay distinct. `null` off either page
	 * (e.g. a preview context) — no attribute is printed at all rather than a
	 * misleading default.
	 *
	 * @return string|null
	 */
	private static function enkelAuditId(): ?string {
		if ( ! function_exists( 'is_singular' ) ) {
			return null;
		}

		if ( is_singular( 'gedig' ) ) {
			return 'gedig-reaksie-tellers';
		}

		if ( is_singular( 'storie' ) ) {
			return 'storie-reaksie-tellers';
		}

		return null;
	}

	/**
	 * The outline heart glyph for the `'enkel'` variant — the same lucide `Heart`
	 * path already hand-authored inline elsewhere in this pass (e.g.
	 * `reading-gedig.php`'s hint pill), reused here for one visual heart across
	 * the page rather than a second, slightly-different hand-drawn copy.
	 *
	 * @return string
	 */
	private static function heartOutlineSvg(): string {
		return '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" focusable="false"><path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.29 1.51 4.04 3 5.5l7 7Z"/></svg>';
	}
}

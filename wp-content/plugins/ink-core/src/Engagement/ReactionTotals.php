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
	 * Register the server-rendered block on `init`.
	 */
	public function register(): void {
		add_action( 'init', array( self::class, 'registerBlock' ) );
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
	 * @return string
	 */
	public static function render(): string {
		$post_id = function_exists( 'get_the_ID' ) ? (int) get_the_ID() : 0;

		if ( $post_id <= 0 ) {
			return '';
		}

		return self::toHtml( ReactionStore::countsForPost( $post_id ) );
	}

	/**
	 * Build the verb-less totals HTML. Pure — formatter + escaping only.
	 *
	 * @param array<string, int> $counts Reaction value → total.
	 * @return string
	 */
	public static function toHtml( array $counts ): string {
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
}

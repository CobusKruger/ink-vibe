<?php
/**
 * Response (Gemeenskapsreaksie) count server block — post-Epic-19 fidelity pass.
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Engagement;

defined( 'ABSPATH' ) || exit;

/**
 * Renders the `ink/kommentaar-telling` block: a verb-less comment-count anchor
 * for the reading page's floating engagement bar (`.ink-reaksie-bar`).
 *
 * Lovable's `ReadStory.tsx` floating bar shows `<MessageCircle/> {work.comments}`
 * as a link to the responses section, alongside the like count and the bookmark
 * toggle. This reuses {@see ResponseStore::countForPost()} — the SAME filtered
 * `ink_reaksie` count already rendered as the "N Gemeenskapsreaksies" heading by
 * {@see ResponsesList} — rather than a new query (there is deliberately no
 * separate comment-count store; this is a second, terser DISPLAY of the same
 * truthful read, matching this codebase's `ink/reaksie-tellers` "enkel" precedent
 * of collapsing a display without inventing new counted state).
 *
 * @package Ink\Core
 */
final class ResponseCount {

	/**
	 * The block name.
	 *
	 * @var string
	 */
	public const BLOCK = 'ink/kommentaar-telling';

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
	 * Register the `ink/kommentaar-telling` dynamic block.
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

		return self::toHtml( ResponseStore::countForPost( $post_id ) );
	}

	/**
	 * Build the comment-count anchor HTML. Pure — escaping only.
	 *
	 * Links to `#kommentaar` (the Gemeenskapsreaksies section's anchor id, matching
	 * Lovable's `href="#critiques"`), letting a click jump straight to the existing
	 * responses.
	 *
	 * @param int $count The filtered response count.
	 * @return string
	 */
	public static function toHtml( int $count ): string {
		return '<a href="#kommentaar" class="ink-kommentaar-telling" aria-label="' . esc_attr( (string) $count . ' kommentaar' ) . '">'
			. '<span class="ink-kommentaar-telling__ikoon" aria-hidden="true">' . self::messageIconSvg() . '</span>'
			. '<span class="ink-kommentaar-telling__getal">' . esc_html( (string) $count ) . '</span>'
			. '</a>';
	}

	/**
	 * The outline message-circle glyph — the same lucide `MessageCircle` path
	 * already hand-authored inline elsewhere in this codebase
	 * (`Ink\Discovery\FeaturedStream`, `Ink\Discovery\WorksArchive`), reused here
	 * rather than a second, slightly-different hand-drawn copy.
	 *
	 * @return string
	 */
	private static function messageIconSvg(): string {
		return '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" focusable="false"><path d="M7.9 20A9 9 0 1 0 4 16.1L2 22Z"/></svg>';
	}
}

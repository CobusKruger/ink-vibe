<?php
/**
 * Leeslys save-toggle server block — Story 7.7 (FR-29).
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Engagement;

use Ink\I18n\Terms;

defined( 'ABSPATH' ) || exit;

/**
 * Renders the `ink/leeslys-knoppie` save toggle on a reading surface.
 *
 * Server-renders the button in its correct initial state (`has()` for the current
 * member) so there is no client-side flash; the enqueued client flips it through
 * the `ink/v1/leeslys` endpoint and shows the authored toast.
 *
 * Post-Epic-19 fidelity follow-up (product-owner decision): Lovable's floating
 * bar shows the bookmark icon unconditionally, logged in or not — guests see it
 * outline/unsaved and clicking sends them to sign in, rather than the icon being
 * absent entirely. The button therefore ALWAYS renders now; only the write
 * (`ReadingListStore::has()`, `ink/v1/leeslys`) stays gated on a real member —
 * {@see render()} never calls the store with a guest/`0` id, and the markup
 * carries a `data-ink-guest` flag so the client (`leeslys.js`) redirects to
 * `/meld-aan/` instead of posting for a guest click.
 *
 * @package Ink\Core
 */
final class ReadingListToggle {

	/**
	 * The block name.
	 *
	 * @var string
	 */
	public const BLOCK = 'ink/leeslys-knoppie';

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
	 * Register the `ink/leeslys-knoppie` dynamic block.
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
	 * Renders for every visitor now (guest included, per product-owner decision
	 * — see the class docblock); a guest's `saved` is always `false` and
	 * {@see ReadingListStore::has()} is never called with a guest/`0` user id
	 * (that store method expects a real member).
	 *
	 * @return string
	 */
	public static function render(): string {
		$post_id = function_exists( 'get_the_ID' ) ? (int) get_the_ID() : 0;

		if ( $post_id <= 0 ) {
			return '';
		}

		$logged_in = is_user_logged_in();
		$saved     = $logged_in && ReadingListStore::has( get_current_user_id(), $post_id );

		return self::toHtml( $post_id, $saved, ! $logged_in );
	}

	/**
	 * Build the toggle button HTML. Pure — Terms + escaping only.
	 *
	 * Icon-only (post-Epic-19 fidelity pass): Lovable's floating-bar equivalent is
	 * a plain `<Bookmark/>` — no visible text, no count, filled (`fill-sage`) when
	 * saved, outline otherwise — not this theme's generic outlined text button
	 * (the "big outlined text button" flagged in product-owner review). The label
	 * survives as the accessible name (`aria-label`, plus a visually-hidden span
	 * for assistive tech that ignores `aria-label` on unusual node structures) —
	 * it is only the VISIBLE text that is cut, matching Lovable exactly. Reuses
	 * the same lucide `Bookmark` path already hand-authored inline elsewhere in
	 * this theme (`patterns/gemeenskap.php`'s "Bou jou leeslys" list item).
	 *
	 * @param int  $post_id The work.
	 * @param bool $saved   Whether the member has already saved it. Always
	 *                      `false` for a guest.
	 * @param bool $guest   Whether the current visitor is logged out — emits
	 *                      `data-ink-guest="1"` so the client redirects to
	 *                      sign-in on click instead of posting to the REST
	 *                      endpoint.
	 * @return string
	 */
	public static function toHtml( int $post_id, bool $saved, bool $guest = false ): string {
		$classes = 'ink-leeslys-knoppie' . ( $saved ? ' is-saved' : '' );
		$label   = Terms::label( 'leeslys' );

		return '<button type="button" class="' . esc_attr( $classes ) . '"'
			. ' data-ink-post="' . esc_attr( (string) $post_id ) . '"'
			. ( $guest ? ' data-ink-guest="1"' : '' )
			. ' aria-pressed="' . ( $saved ? 'true' : 'false' ) . '"'
			. ' aria-label="' . esc_attr( $label ) . '">'
			. '<span class="ink-leeslys-knoppie__ikoon" aria-hidden="true">' . self::bookmarkSvg() . '</span>'
			. '<span class="ink-visually-hidden">' . esc_html( $label ) . '</span>'
			. '</button>';
	}

	/**
	 * The lucide `Bookmark` glyph — solid when the current toggle state renders
	 * `.is-saved` (CSS fills it via `.ink-leeslys-knoppie.is-saved svg{fill:...}`,
	 * not a second hand-drawn "filled" variant here).
	 *
	 * @return string
	 */
	private static function bookmarkSvg(): string {
		return '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" focusable="false"><path d="m19 21-7-4-7 4V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/></svg>';
	}
}

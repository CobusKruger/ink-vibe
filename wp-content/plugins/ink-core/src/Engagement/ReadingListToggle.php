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
 * the `ink/v1/leeslys` endpoint and shows the authored toast. Shown only to logged-
 * in members (saving requires an account); logged-out readers see nothing here.
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
	 * Register the server-rendered block on `init`.
	 */
	public function register(): void {
		add_action( 'init', array( self::class, 'registerBlock' ) );
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
	 * Block render callback for the current work (logged-in members only).
	 *
	 * @return string
	 */
	public static function render(): string {
		if ( ! is_user_logged_in() ) {
			return '';
		}

		$post_id = function_exists( 'get_the_ID' ) ? (int) get_the_ID() : 0;

		if ( $post_id <= 0 ) {
			return '';
		}

		return self::toHtml( $post_id, ReadingListStore::has( get_current_user_id(), $post_id ) );
	}

	/**
	 * Build the toggle button HTML. Pure — Terms + escaping only.
	 *
	 * @param int  $post_id The work.
	 * @param bool $saved   Whether the member has already saved it.
	 * @return string
	 */
	public static function toHtml( int $post_id, bool $saved ): string {
		$classes = 'ink-leeslys-knoppie' . ( $saved ? ' is-saved' : '' );

		return '<button type="button" class="' . esc_attr( $classes ) . '"'
			. ' data-ink-post="' . esc_attr( (string) $post_id ) . '"'
			. ' aria-pressed="' . ( $saved ? 'true' : 'false' ) . '">'
			. esc_html( Terms::label( 'leeslys' ) )
			. '</button>';
	}
}

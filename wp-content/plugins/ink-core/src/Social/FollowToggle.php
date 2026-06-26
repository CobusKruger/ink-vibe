<?php
/**
 * Volg / Volg tans toggle server block — Story 9.2 (FR-38).
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Social;

use Ink\I18n\Terms;

defined( 'ABSPATH' ) || exit;

/**
 * Renders the `ink/volg-knoppie` follow toggle for a target skrywer.
 *
 * Server-renders the button in its correct initial state (`isFollowing()` for
 * the current viewer) so there is no client-side flash; the enqueued client
 * flips it through the `ink/v1/volg` endpoint. Shown only to a logged-in lid who
 * is NOT the target skrywer — a logged-out reader and the skrywer viewing their
 * own profile see nothing here (you cannot follow yourself).
 *
 * The block takes a `skrywerId` attribute so Story 9.4 (Skrywerprofiel) and the
 * skrywer cards can place it; with no attribute it falls back to the queried
 * author (the author of the current archive/profile context).
 *
 * @package Ink\Core
 */
final class FollowToggle {

	/**
	 * The block name.
	 *
	 * @var string
	 */
	public const BLOCK = 'ink/volg-knoppie';

	/**
	 * Register the server-rendered block on `init`.
	 */
	public function register(): void {
		add_action( 'init', array( self::class, 'registerBlock' ) );
	}

	/**
	 * Register the `ink/volg-knoppie` dynamic block.
	 */
	public static function registerBlock(): void {
		if ( ! function_exists( 'register_block_type' ) ) {
			return;
		}

		register_block_type(
			self::BLOCK,
			array(
				'attributes'      => array(
					'skrywerId' => array(
						'type'    => 'integer',
						'default' => 0,
					),
				),
				'render_callback' => array( self::class, 'render' ),
			)
		);
	}

	/**
	 * Block render callback (logged-in lede only; never on your own profile).
	 *
	 * @param array<string,mixed> $attributes Block attributes (`skrywerId`).
	 * @return string
	 */
	public static function render( array $attributes = array() ): string {
		if ( ! is_user_logged_in() ) {
			return '';
		}

		$skrywer_id = isset( $attributes['skrywerId'] ) ? (int) $attributes['skrywerId'] : 0;

		if ( $skrywer_id <= 0 ) {
			$skrywer_id = (int) get_query_var( 'author' );
		}

		if ( $skrywer_id <= 0 || get_current_user_id() === $skrywer_id ) {
			return '';
		}

		return self::toHtml( $skrywer_id, FollowStore::isFollowing( get_current_user_id(), $skrywer_id ) );
	}

	/**
	 * Build the toggle button HTML. Pure — Terms + escaping only.
	 *
	 * @param int  $skrywer_id The target skrywer.
	 * @param bool $following  Whether the viewer already follows them.
	 * @return string
	 */
	public static function toHtml( int $skrywer_id, bool $following ): string {
		$classes = 'ink-volg-knoppie' . ( $following ? ' is-following' : '' );
		$label   = $following ? Terms::label( 'volg_tans' ) : Terms::label( 'volg' );

		return '<button type="button" class="' . esc_attr( $classes ) . '"'
			. ' data-ink-skrywer="' . esc_attr( (string) $skrywer_id ) . '"'
			. ' aria-pressed="' . ( $following ? 'true' : 'false' ) . '">'
			. esc_html( $label )
			. '</button>';
	}
}

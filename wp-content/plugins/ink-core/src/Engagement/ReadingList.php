<?php
/**
 * Leeslys profile list server block — Story 7.7 (FR-29).
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Engagement;

use Ink\I18n\Terms;

defined( 'ABSPATH' ) || exit;

/**
 * Renders the `ink/leeslys` block: the member's saved works, surfaced on profile.
 *
 * Lists the current member's leeslys (the dedicated My Profiel page is Epic 9;
 * v1 lists the logged-in member's own saves). Reads stay server-rendered (AD-7).
 * Logged-out → nothing.
 *
 * @package Ink\Core
 */
final class ReadingList {

	/**
	 * The block name.
	 *
	 * @var string
	 */
	public const BLOCK = 'ink/leeslys';

	/**
	 * Register the server-rendered block on `init`.
	 */
	public function register(): void {
		add_action( 'init', array( self::class, 'registerBlock' ) );
	}

	/**
	 * Register the `ink/leeslys` dynamic block.
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
	 * Block render callback: the current member's saved works.
	 *
	 * @return string
	 */
	public static function render(): string {
		if ( ! is_user_logged_in() ) {
			return '';
		}

		$cards = array();

		foreach ( ReadingListStore::forUser( get_current_user_id() ) as $post_id ) {
			$post = get_post( $post_id );

			if ( ! $post instanceof \WP_Post || 'publish' !== $post->post_status ) {
				continue;
			}

			$cards[] = array(
				'title'     => get_the_title( $post ),
				'permalink' => (string) get_permalink( $post ),
				'type'      => $post->post_type,
			);
		}

		return self::toHtml( $cards );
	}

	/**
	 * Build the leeslys list HTML. Pure — Terms + escaping only.
	 *
	 * @param list<array{title:string, permalink:string, type:string}> $cards The saved works.
	 * @return string
	 */
	public static function toHtml( array $cards ): string {
		$html = '<section class="ink-leeslys"><h2 class="ink-leeslys__heading">' . esc_html( Terms::label( 'leeslys' ) ) . '</h2>';

		if ( array() === $cards ) {
			$html .= '<ul class="ink-leeslys__list"></ul></section>';

			return $html;
		}

		$html .= '<ul class="ink-leeslys__list">';
		foreach ( $cards as $card ) {
			$html .= '<li class="ink-leeslys__item">'
				. '<span class="ink-leeslys__type">' . esc_html( Terms::label( $card['type'] ) ) . '</span>'
				. '<a class="ink-leeslys__title" href="' . esc_url( $card['permalink'] ) . '">' . esc_html( $card['title'] ) . '</a>'
				. '</li>';
		}
		$html .= '</ul></section>';

		return $html;
	}
}

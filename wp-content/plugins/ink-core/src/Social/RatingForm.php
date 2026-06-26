<?php
/**
 * Reader-rating submission form block — Story 9.6 (FR-42).
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Social;

defined( 'ABSPATH' ) || exit;

/**
 * Renders the `ink/leseroordeel-vorm` rating form on the public Skrywerprofiel.
 *
 * A 1–5 star select + optional review textarea, shown only to a logged-in lid
 * who is NOT the writer (you cannot rate yourself). The enqueued client posts to
 * `ink/v1/leseroordeel` (the leeslys/volg client pattern); the submission is
 * held for moderation (Story 18.4) — the form copy says so. A logged-out / self
 * viewer sees nothing.
 *
 * Conflation-clean: no `Ink\Tiers`/`Ink\Entitlement` (rating is open to any lid).
 *
 * @package Ink\Core
 */
final class RatingForm {

	/**
	 * The block name.
	 *
	 * @var string
	 */
	public const BLOCK = 'ink/leseroordeel-vorm';

	/**
	 * Register the server-rendered block on `init`.
	 */
	public function register(): void {
		add_action( 'init', array( self::class, 'registerBlock' ) );
	}

	/**
	 * Register the `ink/leseroordeel-vorm` dynamic block.
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

		return self::toHtml( $skrywer_id );
	}

	/**
	 * Build the rating-form HTML. Pure — escaping only.
	 *
	 * @param int $skrywer_id The rated writer.
	 * @return string
	 */
	public static function toHtml( int $skrywer_id ): string {
		$html = '<form class="ink-leseroordeel-vorm" data-ink-skrywer="' . esc_attr( (string) $skrywer_id ) . '">'
			. '<h2 class="ink-leseroordeel-vorm__titel">' . esc_html__( 'Beoordeel hierdie skrywer', 'ink-core' ) . '</h2>'
			. '<fieldset class="ink-leseroordeel-vorm__sterre">'
			. '<legend>' . esc_html__( 'Jou gradering', 'ink-core' ) . '</legend>';

		for ( $score = 1; $score <= 5; $score++ ) {
			$html .= '<label class="ink-leseroordeel-vorm__ster">'
				. '<input type="radio" name="ink-oordeel-score" value="' . esc_attr( (string) $score ) . '" />'
				. '<span>' . esc_html( (string) $score ) . '</span>'
				. '</label>';
		}

		$html .= '</fieldset>'
			. '<label class="ink-leseroordeel-vorm__resensie-etiket" for="ink-oordeel-resensie">'
			. esc_html__( 'Jou resensie (opsioneel)', 'ink-core' ) . '</label>'
			. '<textarea id="ink-oordeel-resensie" name="ink-oordeel-resensie" class="ink-leseroordeel-vorm__resensie"></textarea>'
			. '<button type="submit" class="ink-leseroordeel-vorm__stuur">' . esc_html__( 'Stuur oordeel', 'ink-core' ) . '</button>'
			. '<p class="ink-leseroordeel-vorm__nota">' . esc_html__( 'Jou oordeel word eers gemodereer voordat dit gewys word.', 'ink-core' ) . '</p>'
			. '</form>';

		return $html;
	}
}

<?php
/**
 * Oor INK sponsor recognition section server block — Story 14.4 (FR-58).
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Sponsors;

use Ink\I18n\Terms;

defined( 'ABSPATH' ) || exit;

/**
 * Renders the `ink/borg-erkenning` block: the full sponsor recognition section for
 * Oor INK — Story 14.4 (FR-58).
 *
 * An evergreen "thank you + become a sponsor" panel: an eyebrow ("Ons borge"), a
 * heading ("Moontlik gemaak deur"), a thank-you description, a grid of ALL currently
 * active sponsor logos (linked, via the shared {@see SponsorLink}), and a "Word 'n
 * borg" CTA to the contact page. Unlike the 14.3 homepage strip (which shows the
 * single rotated pick and fully collapses), this section shows EVERY active sponsor
 * and always renders — only the logo grid is omitted when there are none.
 *
 * Business logic (which sponsors are active) stays in `ink-core` ({@see Campaign});
 * the theme only embeds the block. All copy comes from the {@see Terms} registry
 * (no bare literals). House-style split: thin {@see render()} + pure {@see toHtml()}.
 * Conflation-clean: references only `Ink\Sponsors` + `Ink\I18n\Terms` + WP core.
 *
 * @package Ink\Core
 */
final class RecognitionSection {

	/**
	 * The block name (single source for the renderer + the theme pattern embed).
	 *
	 * @var string
	 */
	public const BLOCK = 'ink/borg-erkenning';

	/**
	 * Register the server-rendered block on `init`.
	 */
	public function register(): void {
		add_action( 'init', array( self::class, 'registerBlock' ) );
	}

	/**
	 * Register the `ink/borg-erkenning` dynamic block.
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
	 * Block render callback. Reads all active sponsors, renders the section.
	 *
	 * @return string
	 */
	public static function render(): string {
		return self::toHtml( Campaign::activeSponsors() );
	}

	/**
	 * Build the recognition-section HTML. Pure (Terms + escaping + SponsorLink only).
	 *
	 * Always renders the eyebrow, heading, description and CTA (the evergreen
	 * acknowledgement + invitation); the logo grid is included only when there are
	 * active sponsors (no empty grid chrome).
	 *
	 * @param list<Sponsor> $sponsors The active sponsors.
	 * @return string
	 */
	public static function toHtml( array $sponsors ): string {
		$eyebrow     = Terms::label( 'borge_blad_titel' );
		$heading     = Terms::label( 'borge_afdeling_titel' );
		$description = Terms::label( 'borge_beskrywing' );
		$cta_label   = Terms::label( 'word_borg' );

		$html = '<section class="ink-borg-erkenning" aria-label="' . esc_attr( $eyebrow ) . '">'
			. '<p class="ink-borg-erkenning__etiket">' . esc_html( $eyebrow ) . '</p>'
			. '<h2 class="ink-borg-erkenning__titel">' . esc_html( $heading ) . '</h2>'
			. '<p class="ink-borg-erkenning__beskrywing">' . esc_html( $description ) . '</p>';

		if ( array() !== $sponsors ) {
			$html .= '<ul class="ink-borg-erkenning__rooster">';

			foreach ( $sponsors as $sponsor ) {
				$html .= '<li class="ink-borg-erkenning__item">'
					. SponsorLink::html(
						$sponsor,
						'ink-borg-erkenning__logo',
						'ink-borg-erkenning__naam',
						'ink-borg-erkenning__skakel'
					)
					. '</li>';
			}

			$html .= '</ul>';
		}

		$html .= '<a class="ink-borg-erkenning__cta" href="' . esc_url( self::contactUrl() ) . '">'
			. esc_html( $cta_label ) . '</a>';

		return $html . '</section>';
	}

	/**
	 * The contact-page URL for the "Word 'n borg" CTA. Guarded for the unit suite.
	 *
	 * A site-relative page link built via `home_url()` (not a hardcoded asset URL);
	 * targets the Epic-15.4 Kontak page. Falls back to the bare path when WordPress is
	 * not loaded.
	 *
	 * @return string
	 */
	private static function contactUrl(): string {
		if ( function_exists( 'home_url' ) ) {
			$url = home_url( '/kontak' );

			if ( is_string( $url ) && '' !== $url ) {
				return $url;
			}
		}

		return '/kontak';
	}
}

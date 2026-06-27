<?php
/**
 * Community contribution CTA — Story 11.5 (FR-56).
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Training;

defined( 'ABSPATH' ) || exit;

/**
 * Renders the `ink/opleiding-bydra` block: the closing "Plaas 'n stuk" call to
 * action on the Opleiding hub (FR-56).
 *
 * The Opleiding section is community-written; this CTA invites a skrywer to
 * contribute a craft essay or guide. The button targets a FILTERABLE URL
 * (`ink/opleiding_bydra_url`) defaulting to the Epic-6 Skryf flow (`/skryf/`) — a
 * single retarget point as the guide-submission type is wired. Deliberately reads
 * NO `Ink\Submission` (the URL is `home_url` + a filter) so `Training` stays
 * `Kernel + Content`; conflation-clean, server-rendered.
 *
 * @package Ink\Core
 */
final class ContributionCta {

	/**
	 * The block name (single source for the renderer + the theme pattern embed).
	 *
	 * @var string
	 */
	public const BLOCK = 'ink/opleiding-bydra';

	/**
	 * The filter that retargets the contribution destination.
	 *
	 * @var string
	 */
	public const URL_FILTER = 'ink/opleiding_bydra_url';

	/**
	 * Register the server-rendered block on `init`.
	 */
	public function register(): void {
		add_action( 'init', array( self::class, 'registerBlock' ) );
	}

	/**
	 * Register the `ink/opleiding-bydra` dynamic block.
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
	 * The contribution destination — the Skryf flow by default, filterable.
	 *
	 * The single retarget point for when `opleiding_artikel` becomes a first-class
	 * Skryf submittable type (the documented follow-on). No `Ink\Submission`
	 * dependency — `home_url` + a filter keep `Training` at `Kernel + Content`.
	 *
	 * @return string
	 */
	public static function contributionUrl(): string {
		$default  = home_url( '/skryf/' );
		$filtered = apply_filters( self::URL_FILTER, $default );

		// A misbehaving filter (null / non-string / empty) must never yield a broken
		// or fatal href — fall back to the Skryf default.
		return ( is_string( $filtered ) && '' !== $filtered ) ? $filtered : $default;
	}

	/**
	 * Block render callback. Resolves the contribution URL, renders.
	 *
	 * @return string
	 */
	public static function render(): string {
		return self::toHtml( self::contributionUrl() );
	}

	/**
	 * Build the CTA HTML. Pure — escaping only.
	 *
	 * @param string $url The contribution destination href.
	 * @return string
	 */
	public static function toHtml( string $url ): string {
		return '<aside class="ink-opleiding-bydra">'
			. '<h2 class="ink-opleiding-bydra__titel">' . esc_html__( 'Het jy iets om te deel?', 'ink-core' ) . '</h2>'
			. '<p class="ink-opleiding-bydra__teks">'
			. esc_html__( 'Die opleidingsafdeling word deur ons gemeenskap geskryf. As jy \'n skryfkunsessay of \'n gids wil bydra, sal ons dit graag wil lees.', 'ink-core' )
			. '</p>'
			. '<a class="ink-opleiding-bydra__knoppie" href="' . esc_url( $url ) . '">' . esc_html__( 'Plaas \'n stuk', 'ink-core' ) . '</a>'
			. '</aside>';
	}
}

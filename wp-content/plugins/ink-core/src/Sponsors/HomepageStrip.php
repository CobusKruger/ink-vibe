<?php
/**
 * Homepage sponsor strip server block — Story 14.3 (FR-58); §7 chips — Story 19.5.
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Sponsors;

use Ink\I18n\Terms;

defined( 'ABSPATH' ) || exit;

/**
 * Renders the `ink/borg-strook` block: the homepage sponsor section —
 * Story 14.3 (FR-58), restyled to visual fidelity in Story 19.5 (§7).
 *
 * Shows the CURRENTLY ACTIVE sponsors (their campaign window is open — see
 * {@see Campaign::activeSponsors()}) as per-tier chips: a centred eyebrow ("Ons borge",
 * from the {@see Terms} registry) + a serif heading + an intro paragraph + one chip per
 * active sponsor (its tier drives the `ink-borg-strook__chip--{goud|silwer|brons}`
 * modifier class the theme styles) + a "Word 'n borg" CTA. With NO active sponsor the
 * block COLLAPSES (renders the empty string — no chrome, no orphan heading).
 *
 * Three-layer separation: ALL business logic (which sponsors are active, the tier
 * classification) stays in `ink-core`; the theme only embeds the block and paints its
 * `.ink-borg-strook*` markup. House-style split: thin {@see render()} + pure
 * {@see toHtml()}. Conflation-clean: references only `Ink\Sponsors` + `Ink\I18n\Terms`
 * + WP core (sponsoring is editorial, never gated on tier/entitlement).
 *
 * @package Ink\Core
 */
final class HomepageStrip {

	/**
	 * The block name (single source for the renderer + the theme pattern embed).
	 *
	 * @var string
	 */
	public const BLOCK = 'ink/borg-strook';

	/**
	 * The canonical sponsor tiers, in descending prominence. The single source for the
	 * chip-modifier classification: a stored `borgvlak` (free-text {@see FieldSets::BORG_TIER})
	 * is matched case-insensitively against these; anything else degrades to `brons`
	 * (the neutral default chip, mirroring the Lovable design's fall-through).
	 *
	 * @var list<string>
	 */
	private const TIERS = array( 'goud', 'silwer', 'brons' );

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
	 * Register the `ink/borg-strook` dynamic block.
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
	 * Block render callback. Reads the active sponsors, renders the section.
	 *
	 * @return string
	 */
	public static function render(): string {
		return self::toHtml( Campaign::activeSponsors() );
	}

	/**
	 * Normalise a stored `borgvlak` to a canonical tier slug. Pure.
	 *
	 * Matches the free-text tier value case-insensitively against {@see TIERS}
	 * (so "Goud"/"goud"/" GOUD " all map to `goud`); anything unrecognised — including
	 * an empty tier — degrades to `brons` (the neutral default chip).
	 *
	 * @param string $tier The stored tier label.
	 * @return string One of {@see TIERS}.
	 */
	public static function tierSlug( string $tier ): string {
		$needle = strtolower( trim( $tier ) );

		foreach ( self::TIERS as $slug ) {
			if ( str_contains( $needle, $slug ) ) {
				return $slug;
			}
		}

		return 'brons';
	}

	/**
	 * Build the borg-section HTML for the active sponsors — '' when there are none.
	 * Pure (Terms + escaping + SponsorLink only).
	 *
	 * Collapse contract: an empty list returns '' (no heading, no orphan chrome).
	 * Otherwise: a centred eyebrow + serif heading + intro + one per-tier chip per
	 * sponsor (the sponsor's linked logo/name via the shared {@see SponsorLink}, with
	 * alt text = the sponsor name) + a "Word 'n borg" CTA to the contact page.
	 *
	 * @param list<Sponsor> $sponsors The active sponsors.
	 * @return string
	 */
	public static function toHtml( array $sponsors ): string {
		if ( array() === $sponsors ) {
			return '';
		}

		$eyebrow     = Terms::label( 'borge_blad_titel' );
		$heading     = Terms::label( 'borge_afdeling_titel' );
		$description = Terms::label( 'borge_beskrywing' );
		$cta_label   = Terms::label( 'word_borg' );

		$chips = '';
		foreach ( $sponsors as $sponsor ) {
			$slug   = self::tierSlug( $sponsor->tier );
			$chips .= '<li class="ink-borg-strook__chip ink-borg-strook__chip--' . esc_attr( $slug ) . ' ink-hover-scale">'
				. SponsorLink::html(
					$sponsor,
					'ink-borg-strook__logo',
					'ink-borg-strook__naam',
					'ink-borg-strook__skakel'
				)
				. '</li>';
		}

		// Lucide "Heart" (stroke), decorative — the visible CTA label carries the meaning.
		$heart = '<svg class="ink-borg-strook__cta-ikoon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"/></svg>';

		return '<section class="ink-borg-strook" aria-label="' . esc_attr( $eyebrow ) . '">'
			. '<p class="ink-borg-strook__etiket">' . esc_html( $eyebrow ) . '</p>'
			. '<h2 class="ink-borg-strook__titel">' . esc_html( $heading ) . '</h2>'
			. '<p class="ink-borg-strook__beskrywing">' . esc_html( $description ) . '</p>'
			. '<ul class="ink-borg-strook__rooster">' . $chips . '</ul>'
			. '<a class="ink-borg-strook__cta" href="' . esc_url( self::contactUrl() ) . '">'
			. $heart . esc_html( $cta_label ) . '</a>'
			. '</section>';
	}

	/**
	 * The contact-page URL for the "Word 'n borg" CTA. Guarded for the unit suite.
	 *
	 * A site-relative page link built via `home_url()` (not a hardcoded asset URL);
	 * targets the Epic-15.4 Kontak page. Falls back to the bare path when WordPress is
	 * not loaded. Mirrors {@see RecognitionSection::contactUrl()}.
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

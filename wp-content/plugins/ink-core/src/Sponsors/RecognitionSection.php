<?php
/**
 * Oor INK sponsor recognition section server block — Story 14.4 (FR-58).
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Sponsors;

use Ink\I18n\Terms;
use Ink\Kernel\QaFixture;

defined( 'ABSPATH' ) || exit;

/**
 * Renders the `ink/borg-erkenning` block: the full sponsor recognition section for
 * Oor INK — Story 14.4 (FR-58); tier chips + fixture gating brought to parity with
 * the homepage strip during the Epic-19 theme-fidelity re-audit (page 14, oor-ink).
 *
 * An evergreen "thank you + become a sponsor" panel: an eyebrow ("Ons borge"), a
 * heading ("Moontlik gemaak deur"), a thank-you description, a grid of ALL currently
 * active sponsor logos (linked, via the shared {@see SponsorLink}, each tinted by
 * its {@see HomepageStrip::tierSlug()} the same way the 14.3 strip's chips are), and
 * a "Word 'n borg" CTA to the contact page. Unlike the 14.3 homepage strip (which
 * shows the single rotated pick and fully collapses), this section shows EVERY
 * active sponsor and always renders — only the logo grid is omitted when there are
 * none.
 *
 * Business logic (which sponsors are active) stays in `ink-core` ({@see Campaign});
 * the theme only embeds the block. All copy comes from the {@see Terms} registry
 * (no bare literals). House-style split: thin {@see render()} + pure {@see toHtml()}.
 * Conflation-clean: references only `Ink\Sponsors` + `Ink\I18n\Terms` +
 * `Ink\Kernel\QaFixture` (the shared QA-fixture predicate, mirroring
 * {@see HomepageStrip}'s own use of it) + WP core.
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
	 * Overridable seam: when a filter callback returns true, QA-fixture-titled
	 * sponsors (see {@see \Ink\Kernel\QaFixture}) are INCLUDED instead of excluded
	 * (the default). Mirrors {@see HomepageStrip::INCLUDE_FIXTURES_FILTER} exactly —
	 * {@see Campaign::activeSponsors()} has no data-seam filter of its own to hook,
	 * so without this seam a real seeded `QA FIXTURE — ` `borg` post leaks onto the
	 * real Oor INK page (confirmed live during the Epic-19 theme-fidelity re-audit,
	 * page 14 — this block had NO exclusion at all before, unlike the homepage strip
	 * which already had it from the tuisblad pass). The theme gates its own override
	 * to the QA/component gallery page only.
	 *
	 * @var string
	 */
	public const INCLUDE_FIXTURES_FILTER = 'ink_borg_erkenning_include_fixtures';

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
		return self::toHtml( self::activeSponsors() );
	}

	/**
	 * The active sponsors to render, with QA-fixture-titled ones excluded unless
	 * {@see INCLUDE_FIXTURES_FILTER} is overridden true. Impure (WP_Query + filter).
	 * Mirrors {@see HomepageStrip::activeSponsors()} exactly.
	 *
	 * @return list<Sponsor>
	 */
	private static function activeSponsors(): array {
		$sponsors = Campaign::activeSponsors();

		if ( (bool) apply_filters( self::INCLUDE_FIXTURES_FILTER, false ) ) {
			return $sponsors;
		}

		return array_values(
			array_filter(
				$sponsors,
				static fn ( Sponsor $sponsor ): bool => ! QaFixture::isFixtureTitle( $sponsor->name )
			)
		);
	}

	/**
	 * Build the recognition-section HTML. Pure (Terms + escaping + SponsorLink only).
	 *
	 * Always renders the eyebrow, heading, description and CTA (the evergreen
	 * acknowledgement + invitation); the logo grid is included only when there are
	 * active sponsors (no empty grid chrome). Each grid item carries a
	 * {@see HomepageStrip::tierSlug()} modifier class (`ink-borg-erkenning__item--
	 * {goud|silwer|brons}`) so the recognition grid reads with the same per-tier
	 * visual weight as the 14.3 homepage strip's chips, instead of a flat list.
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
				$slug  = HomepageStrip::tierSlug( $sponsor->tier );
				$html .= '<li class="ink-borg-erkenning__item ink-borg-erkenning__item--' . esc_attr( $slug ) . '">'
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
			. self::heartIcon() . esc_html( $cta_label ) . '</a>';

		return $html . '</section>';
	}

	/**
	 * The decorative Lucide "Heart" (stroke) icon prefixing the CTA — the visible
	 * label carries the meaning, so it is `aria-hidden`. Matches Lovable's
	 * `SponsorsSection.tsx` CTA (`<Heart className="mr-2 h-4 w-4" />`) and the
	 * identical icon already inlined in {@see HomepageStrip::toHtml()}; kept as its
	 * own copy here (not shared) to hold its own `ink-borg-erkenning__cta-ikoon`
	 * class, per the file's own conflation-clean convention. Pure.
	 *
	 * @return string
	 */
	private static function heartIcon(): string {
		return '<svg class="ink-borg-erkenning__cta-ikoon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"/></svg>';
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

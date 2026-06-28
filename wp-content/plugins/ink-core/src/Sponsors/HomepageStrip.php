<?php
/**
 * Homepage sponsor strip server block — Story 14.3 (FR-58).
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Sponsors;

use Ink\I18n\Terms;

defined( 'ABSPATH' ) || exit;

/**
 * Renders the `ink/borg-strook` block: the subtle homepage sponsor strip —
 * Story 14.3 (FR-58).
 *
 * Shows ONE sponsor — the daily-rotated active pick from {@see Campaign::featured()}
 * (14.2) — as a small eyebrow ("Ons borge", from the {@see Terms} registry) + the
 * sponsor logo (linked). With no active sponsor the block COLLAPSES (renders the
 * empty string — no chrome). With several active it shows today's rotation slot.
 *
 * "No logo dumps on content pages": the strip is a single block embedded in a single
 * homepage pattern — it is NOT hooked into `the_content` or any global render, so
 * single content templates never carry it. Business logic stays in `ink-core`
 * (which sponsor, rotation); the theme only embeds the block (three-layer
 * separation). House-style split: thin {@see render()} + pure {@see toHtml()}.
 * Conflation-clean: references only `Ink\Sponsors` + `Ink\I18n\Terms` + WP core.
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
	 * Register the server-rendered block on `init`.
	 */
	public function register(): void {
		add_action( 'init', array( self::class, 'registerBlock' ) );
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
	 * Block render callback. Reads the featured sponsor, renders the strip.
	 *
	 * @return string
	 */
	public static function render(): string {
		return self::toHtml( Campaign::featured() );
	}

	/**
	 * Build the strip HTML for the featured sponsor — '' when there is none. Pure
	 * (Terms + escaping + guarded permalink only).
	 *
	 * Collapse contract: a null sponsor returns '' (no heading, no orphan chrome).
	 * Otherwise: an eyebrow label + the linked logo (or the name when no logo). The
	 * link targets the sponsor's external URL when set (`target=_blank`,
	 * `rel="noopener sponsored"`), else its permalink, else no anchor.
	 *
	 * @param Sponsor|null $sponsor The featured sponsor, or null.
	 * @return string
	 */
	public static function toHtml( ?Sponsor $sponsor ): string {
		if ( null === $sponsor ) {
			return '';
		}

		$label = Terms::label( 'borge_blad_titel' );

		// The linked logo/name is the shared 14.4 SponsorLink piece (single source for
		// the link-target decision); this surface keeps its own BEM namespace.
		$inner = SponsorLink::html(
			$sponsor,
			'ink-borg-strook__logo',
			'ink-borg-strook__naam',
			'ink-borg-strook__skakel'
		);

		return '<aside class="ink-borg-strook" aria-label="' . esc_attr( $label ) . '">'
			. '<p class="ink-borg-strook__etiket">' . esc_html( $label ) . '</p>'
			. $inner
			. '</aside>';
	}
}

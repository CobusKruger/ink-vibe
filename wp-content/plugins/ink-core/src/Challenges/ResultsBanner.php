<?php
/**
 * The results banner — WP3 (N5; `03-tuis` §5) and WP1 (E8; `01-uitslae` §12).
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Challenges;

use Ink\Content\PostTypes;

defined( 'ABSPATH' ) || exit;

/**
 * The `ink/uitslae-banier` block: a band whose whole panel links to a round's results page.
 *
 *  - `konteks` = `tuis` (default): the latest announcement's round, on Tuis.
 *  - `konteks` = `rondte`: the current round, on its own page, only when it has results.
 *
 * DIVERGENCE (accepted, owner 2026-11-01, `owner-rulings.md`; `maps/uitslae.csv`
 * status `divergence`): the banner on the round page. Lovable's `Challenge.tsx` has no link
 * to the results. It takes no eyebrow there: the round page already states the month, and the
 * ruling names recipes GAP-03-01, -02, -03, -05, -06 only. Its ids are prefixed `round-` so
 * nothing pretends to pair with Lovable.
 *
 * @package Ink\Core
 */
final class ResultsBanner {

	/**
	 * The block name.
	 *
	 * @var string
	 */
	public const BLOCK = 'ink/uitslae-banier';

	/**
	 * The Tuis context.
	 *
	 * @var string
	 */
	public const CONTEXT_HOME = 'tuis';

	/**
	 * The round-page context.
	 *
	 * @var string
	 */
	public const CONTEXT_ROUND = 'rondte';

	private const ICON_CROWN = '<path d="M11.562 3.266a.5.5 0 0 1 .876 0L15.39 8.87a1 1 0 0 0 1.516.294L21.183 5.5a.5.5 0 0 1 .798.519l-2.834 10.246a1 1 0 0 1-.956.734H5.81a1 1 0 0 1-.957-.734L2.02 6.02a.5.5 0 0 1 .798-.519l4.276 3.664a1 1 0 0 0 1.516-.294z"/><path d="M5 21h14"/>';
	private const ICON_ARROW = '<path d="M5 12h14"/><path d="m12 5 7 7-7 7"/>';

	/**
	 * Register the block. Runs inside the module's `init` dispatch.
	 */
	public function register(): void {
		if ( ! function_exists( 'register_block_type' ) ) {
			return;
		}

		register_block_type(
			self::BLOCK,
			array(
				'render_callback' => array( self::class, 'render' ),
				'attributes'      => array(
					'konteks' => array(
						'type'    => 'string',
						'default' => self::CONTEXT_HOME,
					),
				),
			)
		);
	}

	/**
	 * Block render callback.
	 *
	 * @param array<string, mixed> $attributes Block attributes.
	 * @return string
	 */
	public static function render( array $attributes = array() ): string {
		$context = self::CONTEXT_ROUND === ( $attributes['konteks'] ?? '' ) ? self::CONTEXT_ROUND : self::CONTEXT_HOME;
		$round   = self::CONTEXT_ROUND === $context ? (int) get_queried_object_id() : self::latestRound();

		if ( $round <= 0 || PostTypes::UITDAGING !== get_post_type( $round ) || ! RoundResults::hasResults( $round ) ) {
			return '';
		}

		return self::toHtml( ResultsRoute::urlFor( $round ), RoundResults::themeFor( $round ), RoundResults::periodFor( $round ), $context );
	}

	/**
	 * The round of the latest announcement; 0 with none.
	 *
	 * @return int
	 */
	private static function latestRound(): int {
		$announcement = ( new WinnersPost() )->latestAnnouncement();

		return $announcement > 0 ? (int) get_post_meta( $announcement, WinnersPost::UITDAGING_META, true ) : 0;
	}

	/**
	 * The banner. Pure.
	 *
	 * @param string $url     The round's results URL.
	 * @param string $theme   The round's theme.
	 * @param string $period  The round's period, e.g. "Oktober 2026".
	 * @param string $context One of the CONTEXT_ constants.
	 * @return string
	 */
	public static function toHtml( string $url, string $theme, string $period, string $context = self::CONTEXT_HOME ): string {
		$on_round = self::CONTEXT_ROUND === $context;
		$prefix   = $on_round ? 'round-results-banner' : 'results-banner';

		$eyebrow = $on_round ? '' : '<p class="ink-eyebrow ink-eyebrow--caps ink-eyebrow--gold" data-audit-id="results-banner-eyebrow">'
			. esc_html( __( 'Uitslae aangekondig', 'ink-core' ) . ( '' !== $period ? ' · ' . $period : '' ) ) . '</p>';

		return '<section class="ink-band ink-band--banner alignfull" data-audit-id="' . esc_attr( $prefix ) . '">'
			. '<div class="ink-container" data-audit-id="' . esc_attr( $prefix . '-container' ) . '">'
			. '<a class="ink-card--banner" data-audit-id="' . esc_attr( $prefix . '-link' ) . '" href="' . esc_url( $url ) . '">'
			. '<span class="ink-icon-tile--circle-md ink-icon-tile--gold" data-audit-id="' . esc_attr( $prefix . '-icon' ) . '">' . self::icon( self::ICON_CROWN, $prefix . '-icon-svg' ) . '</span>'
			. '<div class="ink-card--banner__body" data-audit-id="' . esc_attr( $prefix . '-body' ) . '">'
			. $eyebrow
			. '<p class="ink-heading-banner" data-audit-id="' . esc_attr( $prefix . '-title' ) . '">“' . esc_html( $theme ) . '” — ' . esc_html__( '[NEEDS HUMAN AFRIKAANS] TUIS-UITSLAE-TITEL', 'ink-core' ) . '</p>'
			. '</div>'
			. '<span class="ink-link--ink" data-audit-id="' . esc_attr( $prefix . '-cta' ) . '">' . esc_html__( '[NEEDS HUMAN AFRIKAANS] TUIS-UITSLAE-CTA', 'ink-core' ) . ' ' . self::icon( self::ICON_ARROW, $prefix . '-cta-icon' ) . '</span>'
			. '</a></div></section>';
	}

	/**
	 * A decorative inline Lucide icon. `$paths` is a class-internal SVG literal. Pure.
	 *
	 * @param string $paths The inner SVG markup.
	 * @param string $id    The audit id.
	 * @return string
	 */
	private static function icon( string $paths, string $id ): string {
		return '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" '
			. 'class="ink-icon" data-audit-id="' . esc_attr( $id ) . '" aria-hidden="true" focusable="false">' . $paths . '</svg>';
	}
}

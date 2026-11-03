<?php
/**
 * The InkPols section on Tuis — WP3 (N6; `03-tuis` §5, contract `04-inkpols` §3).
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\InkPols;

defined( 'ABSPATH' ) || exit;

/**
 * The `ink/inkpols-tuis` block: the band and container around the shared current-issue
 * renderer ({@see CurrentIssue::toHtml()}, context `home`). It is the only route to InkPols
 * (D1). With no published issue it renders nothing, which hides the section.
 *
 * DIVERGENCE (accepted, handoff permitted #4, #6; `04-inkpols` §11, disclosed where the
 * renderer and `ink-cover` live): A4 cover, no download button, naam for issue and season.
 *
 * @package Ink\Core
 */
final class HomeSection {

	/**
	 * The block name.
	 *
	 * @var string
	 */
	public const BLOCK = 'ink/inkpols-tuis';

	/**
	 * Register the block. Runs inside the module's `init` dispatch.
	 */
	public function register(): void {
		if ( ! function_exists( 'register_block_type' ) ) {
			return;
		}

		register_block_type( self::BLOCK, array( 'render_callback' => array( self::class, 'render' ) ) );
	}

	/**
	 * Block render callback.
	 *
	 * @return string
	 */
	public static function render(): string {
		$issue = Api::latestPublishedIssue();

		return null === $issue ? '' : self::wrap( CurrentIssue::toHtml( $issue, 'home' ) );
	}

	/**
	 * The band around the renderer's grid; '' when the grid is empty. Pure.
	 *
	 * @param string $grid The renderer's grid.
	 * @return string
	 */
	public static function wrap( string $grid ): string {
		if ( '' === $grid ) {
			return '';
		}

		return '<section class="ink-band ink-band--poem ink-band--cream alignfull" data-audit-id="magazine">'
			. '<div class="ink-container" data-audit-id="magazine-container">' . $grid . '</div>'
			. '</section>';
	}
}

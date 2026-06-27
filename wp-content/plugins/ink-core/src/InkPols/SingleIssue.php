<?php
/**
 * InkPols single-issue metadata server block — Story 13.2 (FR-57).
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\InkPols;

defined( 'ABSPATH' ) || exit;

/**
 * Renders the `ink/inkpols-besonderhede` block: the robust single-issue page
 * metadata (Story 13.2, FR-57).
 *
 * On a single `inkpols_uitgawe`, renders the issue metadata from the 13.1
 * {@see Issue} read-model — cover image, localised issue date, volume, teaser —
 * gracefully omitting any field that is absent (no malformed/empty rows). The
 * editorial body surfaces through core `post-content` in the theme pattern. The
 * PDF flipbook viewer + direct-PDF a11y fallback are Story 13.3 (wired into the
 * same single-page pattern).
 *
 * Mirrors {@see \Ink\Challenges\SinglePage} (the `ink/uitdaging-besonderhede`
 * single-page block). Conflation-clean: reads only `Ink\InkPols` + the `Terms`
 * registry + WP core — zero `Ink\Tiers`/`Ink\Entitlement` (reading a published
 * issue is open).
 *
 * @package Ink\Core
 */
final class SingleIssue {

	/**
	 * The block name (single source for the renderer + the theme pattern embed).
	 *
	 * @var string
	 */
	public const BLOCK = 'ink/inkpols-besonderhede';

	/**
	 * Register the server-rendered block on `init`.
	 */
	public function register(): void {
		add_action( 'init', array( self::class, 'registerBlock' ) );
	}

	/**
	 * Register the `ink/inkpols-besonderhede` dynamic block.
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
	 * Build the metadata block for an issue. Pure (Terms + escaping only).
	 *
	 * Each row is omitted when its field is empty; the whole block is '' when the
	 * issue carries no metadata at all (so a bare issue renders no empty shell).
	 *
	 * @param Issue $issue The issue read-model.
	 * @return string
	 */
	public static function metaHtml( Issue $issue ): string {
		$cover_url = $issue->coverUrl();

		$cover = '' !== $cover_url
			? '<img class="ink-inkpols-besonderhede__omslag" src="' . esc_url( $cover_url ) . '" alt="' . esc_attr( $issue->title ) . '" />'
			: '';

		$date = '' !== $issue->displayDate()
			? '<p class="ink-inkpols-besonderhede__datum">' . esc_html( $issue->displayDate() ) . '</p>'
			: '';

		$volume = '' !== $issue->volume
			? '<p class="ink-inkpols-besonderhede__volume">' . esc_html( $issue->volume ) . '</p>'
			: '';

		$teaser = '' !== $issue->teaser
			? '<p class="ink-inkpols-besonderhede__voorskou">' . esc_html( $issue->teaser ) . '</p>'
			: '';

		$body = $cover . $date . $volume . $teaser;

		if ( '' === $body ) {
			return '';
		}

		return '<div class="ink-inkpols-besonderhede">' . $body . '</div>';
	}

	/**
	 * Block render callback. Resolves the current issue, type-guards, composes.
	 *
	 * @return string
	 */
	public static function render(): string {
		$id    = function_exists( 'get_the_ID' ) ? (int) get_the_ID() : 0;
		$issue = Api::issueFor( $id );

		if ( null === $issue ) {
			return '';
		}

		return self::metaHtml( $issue );
	}
}

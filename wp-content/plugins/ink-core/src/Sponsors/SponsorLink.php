<?php
/**
 * Shared sponsor logo/name link renderer — Story 14.4 (FR-58).
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Sponsors;

defined( 'ABSPATH' ) || exit;

/**
 * Renders a single sponsor as a linked logo (or name fallback) — the piece shared
 * by the homepage strip (14.3 {@see HomepageStrip}) and the recognition section
 * (14.4 {@see RecognitionSection}).
 *
 * The link-target decision is the single source for BOTH surfaces: the sponsor's
 * external `link` wins (`target=_blank rel="noopener sponsored"`), else the sponsor's
 * own permalink, else no anchor (the logo/name renders bare). The visible content is
 * the logo when one resolves, else the sponsor name (never a broken `<img>`). Every
 * value is escaped at output. The BEM class names are passed in so each surface keeps
 * its own namespace (`ink-borg-strook__*` vs `ink-borg-erkenning__*`).
 *
 * @package Ink\Core
 */
final class SponsorLink {

	/**
	 * Build the linked logo/name markup for a sponsor. Pure (escaping + guarded
	 * permalink only).
	 *
	 * @param Sponsor $sponsor   The sponsor read-model.
	 * @param string  $logoClass CSS class for the `<img>` logo.
	 * @param string  $nameClass CSS class for the name fallback `<span>`.
	 * @param string  $linkClass CSS class for the `<a>` anchor.
	 * @param string  $logoSize  The registered image size for the logo (default `medium`).
	 * @return string
	 */
	public static function html( Sponsor $sponsor, string $logoClass, string $nameClass, string $linkClass, string $logoSize = 'medium' ): string {
		$logo = $sponsor->logoUrl( $logoSize );

		$inner = '' !== $logo
			? '<img class="' . esc_attr( $logoClass ) . '" src="' . esc_url( $logo ) . '" alt="' . esc_attr( $sponsor->name ) . '" />'
			: '<span class="' . esc_attr( $nameClass ) . '">' . esc_html( $sponsor->name ) . '</span>';

		if ( '' !== $sponsor->link ) {
			return '<a class="' . esc_attr( $linkClass ) . '" href="' . esc_url( $sponsor->link )
				. '" target="_blank" rel="noopener sponsored">' . $inner . '</a>';
		}

		$permalink = self::permalink( $sponsor->postId );

		if ( '' !== $permalink ) {
			return '<a class="' . esc_attr( $linkClass ) . '" href="' . esc_url( $permalink ) . '">' . $inner . '</a>';
		}

		return $inner;
	}

	/**
	 * The sponsor's own permalink, or '' when unavailable. Guarded for the unit suite.
	 *
	 * @param int $post_id The sponsor post id.
	 * @return string
	 */
	private static function permalink( int $post_id ): string {
		if ( $post_id <= 0 || ! function_exists( 'get_permalink' ) ) {
			return '';
		}

		$url = get_permalink( $post_id );

		return is_string( $url ) ? $url : '';
	}
}

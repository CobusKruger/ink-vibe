<?php
/**
 * Winner banner — per-rank / per-tier variants — Story 12A.6 (FR-50-R2, C9).
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Challenges;

use Ink\Kernel\Scalar;

defined( 'ABSPATH' ) || exit;

/**
 * Renders the winner banner with per-rank (algehele wenner vs wenner) and per-tier
 * (Brons/Silwer/Goud/Meester) variants (Story 12A.6, C9).
 *
 * A11y (no colour-only encoding): the banner pairs its colour with a real TEXT label and
 * an `aria-hidden` mark glyph, so the placement is conveyed by text — not colour alone —
 * mirroring the Story-5.4 gradering badge. Colour reuses the established
 * `.ink-gradering--{tier}` convention (theme.json maps `--meester` to `primary #EA4015`);
 * Brons/Silwer/Goud are class hooks pending their design-handoff swatches.
 *
 * The placement flag already lives on the entry as `ink_entry_placement` (Story 12.6);
 * this presenter READS it. Conflation-clean: placement + gradering snapshot only, zero
 * `Ink\Entitlement`.
 *
 * @package Ink\Core
 */
final class WinnerBanner {

	/**
	 * The banner's per-rank variant modifier: 'algehele' (1st) / 'wenner' (2nd-3rd) /
	 * '' (not a placement). Pure.
	 *
	 * @param int $rank The placement rank.
	 * @return string
	 */
	public static function variant( int $rank ): string {
		if ( ! Placements::isValidRank( $rank ) ) {
			return '';
		}

		return Placements::isAlgeheleWenner( $rank ) ? 'algehele' : 'wenner';
	}

	/**
	 * The winner-banner markup for a rank + tier + label. Pure (escaping only).
	 *
	 * Returns '' for an invalid rank (no banner on a non-placed entry). The mark is
	 * `aria-hidden`; the label is real text — colour is never the only signal (a11y).
	 *
	 * @param int    $rank  The placement rank (1/2/3).
	 * @param string $grade The entry-time Gradering snapshot (brons/silwer/goud/meester).
	 * @param string $label The placement label text (e.g. "algehele wenner").
	 * @return string
	 */
	public static function toHtml( int $rank, string $grade, string $label ): string {
		$variant = self::variant( $rank );

		if ( '' === $variant ) {
			return '';
		}

		$grade   = trim( $grade );
		$classes = 'ink-wenner-banier ink-wenner-banier--' . $variant;

		if ( '' !== $grade ) {
			$classes .= ' ink-gradering--' . $grade;
		}

		return '<span class="' . esc_attr( $classes ) . '">'
			. '<span class="ink-wenner-banier__merk" aria-hidden="true">&#9733;</span>'
			. '<span class="ink-wenner-banier__teks">' . esc_html( $label ) . '</span>'
			. '</span>';
	}

	/**
	 * The winner banner for a (placed) work — reads its placement + entry-time gradering.
	 *
	 * Returns '' when the work has no placement (so a non-winning work shows no banner).
	 *
	 * @param int $post_id The entry (bydrae) post id.
	 * @return string
	 */
	public static function forPost( int $post_id ): string {
		if ( $post_id <= 0 ) {
			return '';
		}

		$rank = Placements::placementFor( $post_id );

		if ( ! Placements::isValidRank( $rank ) ) {
			return '';
		}

		$grade = Scalar::asString( get_post_meta( $post_id, Entry::GRADERING_META_KEY, true ) );

		return self::toHtml( $rank, $grade, Placements::placementLabel( $rank ) );
	}
}

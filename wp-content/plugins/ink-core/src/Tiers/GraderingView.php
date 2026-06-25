<?php
/**
 * Gradering display view (presenter output).
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Tiers;

use Ink\Kernel\Tier;

defined( 'ABSPATH' ) || exit;

/**
 * A typed, presentation-ready view of a writer's Gradering (Story 5.4, FR-14).
 *
 * The single place the "Meester is special" display rule is encoded: Meester
 * renders in the brand `primary` token (#EA4015), NOT `danger` and NOT the
 * gold/silver/bronze grade colours. The theme bridge ({@see \ink_foundation_gradering_badge()})
 * renders the accessible badge from this view — text label always present
 * (a11y, never colour-only) + a decorative mark.
 *
 * THE conflation rule (AD-1): built from the Kernel `Tier` + the `Ink\I18n\Terms`
 * label registry only; never `Ink\Entitlement`. A displayed grade is independent
 * of membership.
 *
 * @package Ink\Core
 */
final class GraderingView {

	/**
	 * Build a presentation-ready Gradering view.
	 *
	 * @param Tier   $tier      The writer's grade.
	 * @param string $label     The glossary display label (from the Terms registry).
	 * @param bool   $isMeester Whether the grade is the manual-only Meester.
	 */
	public function __construct(
		public readonly Tier $tier,
		public readonly string $label,
		public readonly bool $isMeester,
	) {}

	/**
	 * The single-source CSS modifier for the badge (the grade backing value),
	 * e.g. `meester` → `.ink-gradering--meester`.
	 */
	public function cssModifier(): string {
		return $this->tier->value;
	}

	/**
	 * The theme colour-token name for this grade: `primary` for Meester (#EA4015),
	 * otherwise the grade value (the per-grade gold/silver/bronze token).
	 */
	public function colorToken(): string {
		return $this->isMeester ? 'primary' : $this->tier->value;
	}
}

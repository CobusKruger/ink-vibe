<?php
/**
 * Lidmaatskap plan presentation read-model (Story 4.4, FR-7).
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Entitlement;

defined( 'ABSPATH' ) || exit;

/**
 * A thin READ-MODEL that shapes the three lidmaatskap plan slots into
 * presentation-ready rows for the Lidmaatskap page (Story 4.4) — the seam that
 * lets a static FSE block pattern surface dynamic plan data WITHOUT any business
 * logic living in the theme (project-context.md: "No business logic in the
 * theme").
 *
 * It introduces NO new business rule: every value is delegated to {@see Api}
 * (the 4.1 plan registry + the 4.2 purchase hand-off) —
 *  - the term LABEL via {@see LidmaatskapTerm::label()} (the AD-10 terminology
 *    registry, never an inline literal);
 *  - the PRICE via {@see Api::priceFor()} (resolved from the WooCommerce product
 *    at runtime — `null` when WooCommerce is absent / the plan is unmapped /
 *    retired / misconfigured; there is NO price literal here);
 *  - the SELLABILITY flag via {@see Api::isAvailable()};
 *  - the purchase URL via {@see Api::purchaseUrl()} (the WC checkout / PayFast
 *    hand-off — `null` when the plan cannot be offered).
 *
 * The theme reads these rows through the `class_exists`-guarded
 * `ink_foundation_membership_plans()` bridge and renders them with token-only,
 * structurally-locked core blocks — it iterates rows it is HANDED and computes
 * nothing. No vanity discount/savings field is exposed (AC-3 / Stories 4.9–4.11
 * are post-launch).
 *
 * THE conflation rule (AD-1): lidmaatskap-only — zero reference to writer
 * Gradering (`Ink\Tiers`).
 *
 * @package Ink\Core
 */
final class PlanPresenter {

	/**
	 * The presentation rows for the three launch plan slots, in ascending term order.
	 *
	 * One row per {@see LidmaatskapTerm} (the closed 1/6/12-month set). Each row is
	 * a flat, escape-at-render-ready shape the theme renders directly:
	 *  - `months`        (int)         the fixed term length (1/6/12);
	 *  - `term_label`    (string)      the registry-resolved Afrikaans term label;
	 *  - `price`         (string|null) the WooCommerce runtime price (raw), or null;
	 *  - `price_display` (string|null) the consistently-formatted ZAR string the
	 *    theme echoes (e.g. `R60.00`), or null when there is no live price;
	 *  - `is_available`  (bool)        whether the slot is sellable right now;
	 *  - `purchase_url`  (string|null) the WC/PayFast checkout URL, or null.
	 *
	 * @return list<array{months:int, term_label:string, price:string|null, price_display:string|null, is_available:bool, purchase_url:string|null}>
	 */
	public function rows(): array {
		$rows = array();

		foreach ( LidmaatskapTerm::cases() as $term ) {
			$price = Api::priceFor( $term );

			$rows[] = array(
				'months'        => $term->months(),
				'term_label'    => $term->label(),
				'price'         => $price,
				'price_display' => $this->priceDisplay( $price ),
				'is_available'  => Api::isAvailable( $term ),
				'purchase_url'  => Api::purchaseUrl( $term ),
			);
		}

		return $rows;
	}

	/**
	 * Format a raw WooCommerce price into a consistent, plain ZAR display string.
	 *
	 * Presentation shaping belongs in the read-model, NOT the theme: the theme used
	 * to echo `'R' . $price` raw, so `60` / `300.00` / `1200.5` rendered as the
	 * inconsistent `R60` / `R300.00` / `R1200.5`. This normalises every price to the
	 * same `R<amount>.<2 decimals>` shape. There is NO price literal here — only the
	 * `R` currency symbol and a decimal/separator FORMAT.
	 *
	 * WooCommerce's own locale config is honoured when present
	 * ({@see wc_get_price_decimals()} / {@see wc_get_price_decimal_separator()} /
	 * {@see wc_get_price_thousand_separator()}), each `function_exists()`-guarded so
	 * the seam degrades gracefully to a deterministic, locale-free default
	 * (`number_format( $amount, 2 )`) when WooCommerce is absent. Returns a plain
	 * string (no HTML) so the theme can `esc_html()` it. Null in, null out — the
	 * pattern keeps its "Prys binnekort beskikbaar" degrade.
	 *
	 * @param string|null $price The raw WooCommerce price, or null.
	 * @return string|null The formatted ZAR string, or null when there is no price.
	 */
	private function priceDisplay( ?string $price ): ?string {
		if ( null === $price || '' === $price || ! is_numeric( $price ) ) {
			return null;
		}

		$amount = (float) $price;

		$decimals  = function_exists( 'wc_get_price_decimals' ) ? (int) wc_get_price_decimals() : 2;
		$dec_point = function_exists( 'wc_get_price_decimal_separator' ) ? (string) wc_get_price_decimal_separator() : '.';
		$thousands = function_exists( 'wc_get_price_thousand_separator' ) ? (string) wc_get_price_thousand_separator() : '';

		return 'R' . number_format( $amount, $decimals, $dec_point, $thousands );
	}
}

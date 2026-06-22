<?php
/**
 * Entitlement module public facade.
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Entitlement;

defined( 'ABSPATH' ) || exit;

/**
 * Entitlement module facade — the sole public cross-module surface (AD-1).
 *
 * Other `ink-core` modules reach the lidmaatskap plan registry ONLY through this
 * facade (never into {@see MembershipPlans} internals): the 4.4 Lidmaatskap page
 * (`Api::plans()`), the 4.5 renewal UI (`Api::terms()` / `Api::priceFor()`), and
 * the 4.3 entitlement gate (which will reuse the same membership concept). Mirrors
 * {@see \Ink\Notifications\Api}'s static-facade shape.
 *
 * Scope (Story 4.1): this facade exposes the PLAN REGISTRY only. The
 * `can_submit()` entitlement gate (AD-2) stays RESERVED for Story 4.3 and is NOT
 * added here.
 *
 * THE conflation rule (AD-1): lidmaatskap entitlement ⟂ writer Gradering — this
 * facade carries no reference to `Ink\Tiers`.
 *
 * @package Ink\Core
 */
final class Api {

	/**
	 * The shared plan registry (lazily built; stateless, so a fresh instance is fine).
	 */
	private static ?MembershipPlans $plans = null;

	/**
	 * The shared purchase/activation seam (Story 4.2; lazily built, stateless).
	 */
	private static ?PurchaseActivation $purchase = null;

	/**
	 * The three launch lidmaatskap plan slots (one per fixed term).
	 *
	 * @return list<MembershipPlan>
	 */
	public static function plans(): array {
		return self::registry()->plans();
	}

	/**
	 * The fixed set of lidmaatskap terms offered (the closed enum set).
	 *
	 * @return list<LidmaatskapTerm>
	 */
	public static function terms(): array {
		return MembershipPlans::terms();
	}

	/**
	 * The WooCommerce-resolved price of a term's plan (runtime; null when unknown).
	 *
	 * @param LidmaatskapTerm $term The fixed term.
	 * @return string|null The admin-configured price, or null.
	 */
	public static function priceFor( LidmaatskapTerm $term ): ?string {
		return self::registry()->priceFor( $term );
	}

	/**
	 * Whether a term's plan is SELLABLE right now (published product, valid price).
	 *
	 * The explicit availability signal (FIX-B): downstream stories (the 4.4 page,
	 * the 4.5 renewal UI) decide whether to OFFER a slot via this, NOT by inferring
	 * sellability from a null {@see priceFor()} (which conflates WooCommerce-absent
	 * / unmapped / retired / misconfigured).
	 *
	 * @param LidmaatskapTerm $term The fixed term.
	 * @return bool True when the plan can be sold.
	 */
	public static function isAvailable( LidmaatskapTerm $term ): bool {
		return self::registry()->isAvailable( $term );
	}

	/**
	 * The subset of fixed terms whose plan is currently sellable.
	 *
	 * A convenience over {@see isAvailable()} for the 4.4 page / 4.5 renewal UI:
	 * the closed term set filtered to the slots that resolve to a published
	 * WooCommerce product with a valid positive price.
	 *
	 * @return list<LidmaatskapTerm>
	 */
	public static function availableTerms(): array {
		return array_values(
			array_filter(
				MembershipPlans::terms(),
				static fn ( LidmaatskapTerm $term ): bool => self::registry()->isAvailable( $term )
			)
		);
	}

	/**
	 * The WooCommerce checkout URL that starts the off-site PayFast purchase of a
	 * term's plan (Story 4.2) — or null when it cannot be offered.
	 *
	 * The cross-module surface the 4.4 Lidmaatskap page consumes to render a "buy"
	 * CTA: it hands off to the WooCommerce checkout / WC PayFast gateway for the
	 * mapped 4.1 product, building no card form and referencing no PayFast
	 * credential. Null when WooCommerce is absent or the plan is not sellable.
	 *
	 * @param LidmaatskapTerm $term The fixed term to purchase.
	 * @return string|null The checkout URL, or null when unavailable.
	 */
	public static function purchaseUrl( LidmaatskapTerm $term ): ?string {
		return self::purchase()->purchaseUrl( $term );
	}

	/**
	 * The shared registry instance.
	 */
	private static function registry(): MembershipPlans {
		return self::$plans ??= new MembershipPlans();
	}

	/**
	 * The shared purchase/activation seam (stateless, so a fresh instance is fine).
	 */
	private static function purchase(): PurchaseActivation {
		return self::$purchase ??= new PurchaseActivation();
	}
}

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
 * (`Api::planRows()`), the 4.5 renewal UI (`Api::renewalRows()`), and the 4.3
 * entitlement gate (`Api::can_submit()`). Mirrors
 * {@see \Ink\Notifications\Api}'s static-facade shape.
 *
 * Scope (Story 4.3): this facade now ALSO exposes the submission-entitlement gate —
 * {@see can_submit()} (facading {@see SubmissionGate}, AD-2): "may this user plaas
 * right now?", evaluated against the WooCommerce Membership END DATE in SAST (NOT
 * the cron-flipped status flag). It is the reusable evaluation the publish point
 * (Story 6.8, `Ink\Submission`) and AD-3 challenge entry consume; this story does
 * NOT wire the enforcement point into a submission flow (deferred to 6.8 — that
 * module + the front-end form do not exist yet; AD-6 decision 2 / FR-19 -> 6.8).
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
	 * The shared submission-entitlement gate (Story 4.3; lazily built, stateless).
	 */
	private static ?SubmissionGate $gate = null;

	/**
	 * The shared plan presentation read-model (Story 4.4; lazily built, stateless).
	 */
	private static ?PlanPresenter $presenter = null;

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
	 * Whether the user may plaas (submit/publish) right now — the entitlement gate.
	 *
	 * The reusable runtime evaluation (Story 4.3, AD-2/AD-6) the publish point
	 * (Story 6.8, `Ink\Submission`) and AD-3 challenge entry consume: `true` iff the
	 * user has an ACTIVE INK lidmaatskap membership whose END DATE is still valid
	 * through end of day SAST — evaluated against the membership end date in SAST, NOT
	 * the lagging WooCommerce `expired` status flag. Fail-safe deny for a null /
	 * logged-out / non-member user or when WooCommerce Memberships is unavailable.
	 *
	 * THE conflation rule (AD-1): computed ONLY from membership state — reads no
	 * `ink_writer_tier`, references no `Ink\Tiers`.
	 *
	 * @param int|\WP_User|null $user The user id, WP_User, or null/logged-out.
	 * @return bool True when the user may submit; false otherwise (fail-safe).
	 */
	public static function can_submit( int|\WP_User|null $user ): bool {
		return self::gate()->canSubmit( $user );
	}

	/**
	 * The presentation-ready rows for the Lidmaatskap page (Story 4.4, FR-7).
	 *
	 * The single cross-module surface the theme's `ink_foundation_membership_plans()`
	 * bridge consumes (AD-1: the facade is the only public surface): one flat row per
	 * fixed term (1/6/12), each carrying the registry-resolved term label, the
	 * WooCommerce runtime price (or null), the sellability flag, and the WC/PayFast
	 * purchase URL (or null). The theme renders these rows with token-only locked
	 * blocks and computes nothing — keeping all plan shaping in `ink-core`
	 * (project-context.md three-layer rule). Delegates to {@see PlanPresenter}.
	 *
	 * @return list<array{months:int, term_label:string, price:string|null, is_available:bool, purchase_url:string|null}>
	 */
	public static function planRows(): array {
		return self::presenter()->rows();
	}

	/**
	 * The presentation-ready rows for the My Profiel renewal section (Story 4.5, FR-8).
	 *
	 * The single cross-module surface the theme's `ink_foundation_renewal_plans()` bridge
	 * consumes (AD-1: the facade is the only public surface): one flat row per fixed term
	 * (1/6/12) for the renewal UI. At launch the renewal rows are IDENTICAL to the 4.4
	 * plan rows (term label, the WooCommerce runtime price + ZAR `price_display`, the
	 * sellability flag, and the WC/PayFast `purchase_url` — the RENEW CTA target), so this
	 * delegates straight to the shared {@see PlanPresenter} ({@see planRows()}) — no
	 * separate read-model is needed (a renewal-only class would be a verbatim pass-through).
	 * Renewal at launch IS the manual fixed-term purchase flow (renew = buy another fixed
	 * term via {@see purchaseUrl()}); there is no auto-renew/recurring concept and no
	 * discount/savings field (Stories 4.9–4.11 are post-launch). The named surface is kept
	 * distinct from {@see planRows()} so a future story can let renewal rows diverge
	 * (renewal-specific framing) without re-threading the theme bridge.
	 *
	 * @return list<array{months:int, term_label:string, price:string|null, price_display:string|null, is_available:bool, purchase_url:string|null}>
	 */
	public static function renewalRows(): array {
		return self::presenter()->rows();
	}

	/**
	 * The shared registry instance.
	 */
	private static function registry(): MembershipPlans {
		return self::$plans ??= new MembershipPlans();
	}

	/**
	 * The shared plan presentation read-model (stateless, so a fresh instance is fine).
	 */
	private static function presenter(): PlanPresenter {
		return self::$presenter ??= new PlanPresenter();
	}

	/**
	 * The shared purchase/activation seam (stateless, so a fresh instance is fine).
	 */
	private static function purchase(): PurchaseActivation {
		return self::$purchase ??= new PurchaseActivation();
	}

	/**
	 * The shared submission-entitlement gate (stateless, so a fresh instance is fine).
	 */
	private static function gate(): SubmissionGate {
		return self::$gate ??= new SubmissionGate();
	}
}

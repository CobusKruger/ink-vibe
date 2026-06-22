<?php
/**
 * Lidmaatskap plan registry (Story 4.1, FR-4).
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Entitlement;

defined( 'ABSPATH' ) || exit;

/**
 * The config-driven, single-source registry of the three launch lidmaatskap
 * plan slots (FR-4 / Story 4.1) — the `ink-core` business SEAM over the
 * WooCommerce / WooCommerce Memberships store.
 *
 * WooCommerce + WooCommerce Memberships own the sellable products and the price
 * (admin-editable); this class does NOT reimplement them (project-context.md:
 * "do not reimplement"). It DESCRIBES the three plan slots — one per fixed
 * {@see LidmaatskapTerm} (1/6/12 months) — and resolves the price from the
 * WooCommerce product at RUNTIME. The consumers (the 4.3 entitlement gate, the
 * 4.4 Lidmaatskap page, the 4.5 renewal UI) reach this only through
 * {@see Api}, the module's facade.
 *
 * The anti-hardcode rule (AC-2 / FR-4 "configurable price & term"):
 *  - The term LENGTHS are the fixed value set ({@see LidmaatskapTerm}); the AC
 *    says they stay 1/6/12, so they are INK-held.
 *  - The PRICE is read from the WooCommerce product via `wc_get_product()` (a
 *    `function_exists()`-guarded runtime lookup) — there is NO `60`/`300`/`600`
 *    literal in this class. A redakteur changing a price/term in WooCommerce
 *    admin needs no `ink-core` edit.
 *  - The product MAPPING is config: a single `ink_`-prefixed option
 *    ({@see OPTION_PRODUCTS}) holding a `term-months => product_id` map,
 *    fail-safe empty (an unset/garbled value yields no mapping, never a fatal).
 *
 * No auto-renew, no discount at launch (AC-1 / AC-3): the slot value object
 * ({@see MembershipPlan}) is non-recurring and exposes no discount/savings
 * field — Stories 4.9–4.11 (recurring + genuine discount) are post-launch.
 *
 * THE conflation rule (AD-1): this seam is lidmaatskap-only — zero reference to
 * writer Gradering (`Ink\Tiers`).
 *
 * @package Ink\Core
 */
class MembershipPlans {

	/**
	 * The single `ink_`-prefixed option holding the plan→product mapping.
	 *
	 * Shape: `array<int $term_months, int $wc_product_id>`. Admin/migration sets
	 * it; this class never hardcodes a product id (only the fixed term set is
	 * INK-held). Fail-safe empty when unset or non-array.
	 */
	public const OPTION_PRODUCTS = 'ink_membership_plan_products';

	/**
	 * The `ink_`-prefixed filter that layers over the {@see OPTION_PRODUCTS} option.
	 *
	 * The "no producer" gap (FIX-D): nothing else writes the option yet, so the
	 * registry would always return null in practice. This filter lets site config
	 * (or a later admin UI / the Lidmaatskap admin surface) SUPPLY or OVERRIDE the
	 * `term-months => product_id` map without an `ink-core` code change — the
	 * configurable-mapping seam FR-4 / AC-2 promises. The option-derived (and
	 * key-normalised) map is the filter's default value; a hook may add or replace
	 * rows. Single-source const so the filter name is never an inline bare literal.
	 */
	public const FILTER_PRODUCTS = 'ink_membership_plan_products';

	/**
	 * The three launch plan slots — one per fixed term, in ascending term order.
	 *
	 * The term set is the single source ({@see LidmaatskapTerm}); each slot binds
	 * its term to the mapped WooCommerce product id (nullable when unmapped). No
	 * price is stored on the slot — see {@see priceFor()}.
	 *
	 * @return list<MembershipPlan>
	 */
	public function plans(): array {
		$plans = array();

		foreach ( LidmaatskapTerm::cases() as $term ) {
			$plans[] = new MembershipPlan( $term, $this->productIdFor( $term ) );
		}

		return $plans;
	}

	/**
	 * The fixed set of terms the registry offers (the closed enum set).
	 *
	 * @return list<LidmaatskapTerm>
	 */
	public static function terms(): array {
		return LidmaatskapTerm::cases();
	}

	/**
	 * The WooCommerce product id mapped to a term, or null when unmapped.
	 *
	 * Reads the config option fail-safe: a non-array (unset/garbled) value yields
	 * null; a missing/zero row yields null. Never hardcodes a product id.
	 *
	 * @param LidmaatskapTerm $term The fixed term.
	 * @return int|null The mapped product id, or null when unmapped.
	 */
	public function productIdFor( LidmaatskapTerm $term ): ?int {
		$map = $this->productMap();

		$product_id = $map[ $term->months() ] ?? null;

		if ( ! is_scalar( $product_id ) ) {
			return null;
		}

		$product_id = (int) $product_id;

		return $product_id > 0 ? $product_id : null;
	}

	/**
	 * The resolved `term-months => product_id` map (option + filter, int-keyed).
	 *
	 * Reads the {@see OPTION_PRODUCTS} option fail-safe (a non-array unset/garbled
	 * value collapses to an empty map), then:
	 *  - NORMALISES the keys to `int` (FIX-C): a migration/admin write could store
	 *    string keys (`'1'`, `'01'`, `' 1'`) that don't all PHP-normalise to the
	 *    int the lookup uses, so each key is cast via `(int) trim()` into a rebuilt
	 *    map — a plausible string-keyed write still resolves;
	 *  - layers the {@see FILTER_PRODUCTS} filter over it (FIX-D): site config / a
	 *    later admin UI can SUPPLY or OVERRIDE rows without an `ink-core` edit
	 *    (closing the "no producer" gap). The filtered result is normalised again
	 *    and re-guarded so a filter returning a non-array or string keys is
	 *    fail-safe.
	 *
	 * @return array<int, mixed> The int-keyed product map (possibly empty).
	 */
	private function productMap(): array {
		$option_map = get_option( self::OPTION_PRODUCTS, array() );

		$map = apply_filters( self::FILTER_PRODUCTS, $this->normaliseKeys( $option_map ) );

		return $this->normaliseKeys( $map );
	}

	/**
	 * Rebuild a map with each key cast to a trimmed `int` (fail-safe for non-arrays).
	 *
	 * A non-array input yields an empty map (the fail-safe-empty discipline). Each
	 * key is `(int) trim( (string) $key )` so a string-keyed write (`'1'`/`' 1'`)
	 * resolves to the same int key the term lookup uses ({@see LidmaatskapTerm::months()}).
	 *
	 * @param mixed $map The raw option/filter value.
	 * @return array<int, mixed> The int-keyed map.
	 */
	private function normaliseKeys( mixed $map ): array {
		if ( ! is_array( $map ) ) {
			return array();
		}

		$normalised = array();

		foreach ( $map as $key => $value ) {
			$normalised[ (int) trim( (string) $key ) ] = $value;
		}

		return $normalised;
	}

	/**
	 * The price of a term's plan, resolved from the WooCommerce product at RUNTIME.
	 *
	 * The anti-hardcode boundary (AC-2): the amount is read from the live
	 * WooCommerce product (`wc_get_product()->get_price()`), behind a
	 * `function_exists()` guard so the seam degrades gracefully when WooCommerce
	 * is absent. Returns null when WooCommerce is unavailable, the term is
	 * unmapped, or the product is missing — `ink-core` holds NO fallback price
	 * literal.
	 *
	 * @param LidmaatskapTerm $term The fixed term.
	 * @return string|null The WooCommerce-configured price as a string, or null.
	 */
	public function priceFor( LidmaatskapTerm $term ): ?string {
		if ( ! $this->isWooCommerceAvailable() ) {
			return null;
		}

		$product_id = $this->productIdFor( $term );

		if ( null === $product_id ) {
			return null;
		}

		$product = wc_get_product( $product_id );

		if ( ! is_object( $product ) || ! method_exists( $product, 'get_price' ) ) {
			return null;
		}

		// A retired plan stays sellable-looking unless we check status (FIX-B): a
		// trashed/draft/private product is still a truthy object, so a price read
		// would surface it. AC-2 explicitly lets a redakteur RETIRE a plan — treat
		// any non-`publish` product as unavailable. Guard `get_status()`' presence
		// so the seam stays mock-friendly (a bare price stub need not define it).
		if ( method_exists( $product, 'get_status' ) && 'publish' !== $product->get_status() ) {
			return null;
		}

		$price = $product->get_price();

		// A lidmaatskap price must be a POSITIVE number (FIX-A): a misconfigured /
		// free product can return '' (unset), null, a non-numeric value ('abc'), 0,
		// or a negative amount — none is a valid paid price, so all collapse to
		// "unknown/unavailable" (null), the same fail-safe discipline
		// `productIdFor()` applies to ids. ink-core holds NO fallback price literal.
		if ( ! is_numeric( $price ) || (float) $price <= 0.0 ) {
			return null;
		}

		return (string) $price;
	}

	/**
	 * Whether a term's plan is SELLABLE right now — a public availability signal.
	 *
	 * The downstream-inference gap (FIX-B): a consumer (the 4.4 Lidmaatskap page,
	 * the 4.5 renewal UI) should NOT have to infer "is this slot sellable?" from a
	 * null price (which conflates "WooCommerce absent", "unmapped", "retired" and
	 * "misconfigured"). This is the explicit seam: true IFF the term is mapped to a
	 * PUBLISHED WooCommerce product with a valid POSITIVE price. It reuses
	 * {@see priceFor()} (which already enforces published-status + positive-price),
	 * so the availability rule lives in one place.
	 *
	 * @param LidmaatskapTerm $term The fixed term.
	 * @return bool True when the plan can be sold (published product, valid price).
	 */
	public function isAvailable( LidmaatskapTerm $term ): bool {
		return null !== $this->priceFor( $term );
	}

	/**
	 * Whether WooCommerce's product API is available in this request.
	 *
	 * The "do not reimplement" boundary (project-context.md): when WooCommerce is
	 * inactive the seam degrades gracefully (price resolution returns null) rather
	 * than fatalling on a missing `wc_get_product()`. A `protected` seam (not an
	 * inline `function_exists()`) so the unavailable branch is deterministically
	 * unit-testable without mocking a PHP internal — Brain Monkey-defined function
	 * symbols persist within a process, making an inline `function_exists` guard
	 * untestable for the "absent" case.
	 *
	 * @return bool True when `wc_get_product()` can be called.
	 */
	protected function isWooCommerceAvailable(): bool {
		return function_exists( 'wc_get_product' );
	}
}

<?php
/**
 * WooCommerce storefront-UI suppression seam (Story 4.6, FR-10).
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Entitlement;

defined( 'ABSPATH' ) || exit;

/**
 * Hides the general WooCommerce STOREFRONT so the site reads as a community, not a
 * shop (FR-10 / Story 4.6) — WHILE keeping the Story 4.2/4.4/4.5 lidmaatskap purchase
 * & renewal flow working end-to-end.
 *
 * "HOOK, DON'T EDIT" (project-context.md). WooCommerce + WooCommerce Memberships own
 * the cart / catalog / checkout; INK sells ONLY lidmaatskap (membership) through the
 * controlled 4.x flow. This collaborator does NOT reimplement any WooCommerce
 * behaviour and edits NO WooCommerce file — it closes the storefront surfaces purely
 * through documented WooCommerce / WordPress hooks and filters, mirroring the
 * {@see \Ink\Engagement\Comments} suppression precedent (force-close via late hooks,
 * remove affordances, prune chrome, with a documented re-open filter seam).
 *
 * THE HOOKS USED AND WHAT EACH SUPPRESSES:
 *
 *  - `template_redirect` ({@see onTemplateRedirect()}) — redirects the SHOP page
 *    ({@see HOOK_IS_SHOP}), PRODUCT archives / single products
 *    ({@see HOOK_IS_PRODUCT} / `is_post_type_archive('product')`), and the CART page
 *    ({@see HOOK_IS_CART}) away to the home URL. These are the "this is a shop"
 *    surfaces. CARVE-OUT: the CHECKOUT page ({@see HOOK_IS_CHECKOUT}) is NEVER
 *    redirected, and an add-to-cart request for a configured lidmaatskap product is
 *    NEVER redirected — so the purchase flow's `wc_get_checkout_url()` +
 *    `?add-to-cart=<lidmaatskap product_id>` (Story 4.2 {@see PurchaseActivation::purchaseUrl()})
 *    passes through untouched. The checkout exemption additionally covers the PayFast-
 *    return endpoints `order-received` / `order-pay` ({@see isCheckout()}), and the cart
 *    is also exempt when it ALREADY contains a lidmaatskap product
 *    ({@see cartContainsLidmaatskap()}) — so WooCommerce's "redirect to cart after add"
 *    setting / a "view cart" click never bounces a mid-purchase lid off the cart.
 *  - `pre_get_posts` ({@see filterSearchQuery()}) — excludes the `product` post type from
 *    FRONT-END search results so the site reads as a community, not a shop.
 *  - `woocommerce_is_purchasable` ({@see filterIsPurchasable()}) — makes any product
 *    that is NOT a configured lidmaatskap product UN-purchasable, so a stray generic
 *    "add to cart" affordance is inert. The lidmaatskap products STAY purchasable.
 *  - `woocommerce_loop_add_to_cart_link` ({@see filterLoopAddToCartLink()}) — removes
 *    the "add to cart" link rendered on generic catalog loop items for non-lidmaatskap
 *    products (a removal, no new copy).
 *  - `woocommerce_account_menu_items` ({@see filterAccountMenuItems()}) — prunes the
 *    My-Account storefront tabs that do not apply to a community membership site
 *    ({@see REMOVED_ACCOUNT_TABS}: `downloads`). Renewal lives on My Profiel
 *    (Story 4.5 / Epic 9.4), not the WooCommerce account storefront.
 *
 * THE CARVE-OUT (the crux, AC-2). Whatever is suppressed has an explicit exception so
 * the lidmaatskap checkout keeps working. The distinction is:
 *   • SUPPRESSED — the shop/catalog page, product archives, and the cart page.
 *   • ALLOWED    — the CHECKOUT page, and an add-to-cart for a LIDMAATSKAP PRODUCT.
 * A "lidmaatskap product" is identified by its WooCommerce product id being in the
 * configured Story-4.1 `ink_membership_plan_products` set — resolved through
 * {@see MembershipPlans} ({@see lidmaatskapProductIds()}), the SAME distinguisher
 * {@see SubmissionGate::inkProductIds()} already uses. Identifying the allowed flow by
 * (a) the checkout endpoint and (b) the lidmaatskap product id keeps general-store
 * suppression and the membership flow cleanly separated.
 *
 * Every suppress decision passes through the {@see FILTER_SUPPRESS_EXCEPTION} re-open
 * seam (default: suppress) so a later story can exempt a narrow context without an
 * `ink-core` edit — the {@see \Ink\Engagement\Comments::FILTER_OPEN_EXCEPTION} pattern.
 *
 * WOOCOMMERCE-ABSENT = TOTAL NO-OP. {@see register()} wires ZERO hooks when WooCommerce
 * is inactive ({@see isWooCommerceActive()} → `class_exists( 'WooCommerce' )`), so the
 * seam never fatals on a missing `is_shop()` / `wc_*` symbol. Each handler additionally
 * guards every WC conditional behind `function_exists()` for a partial/loading WC.
 *
 * THE conflation rule (AD-1, FR-13): storefront suppression is unrelated to writer
 * Gradering — this class carries ZERO reference to `Ink\Tiers` and never reads/writes
 * `ink_writer_tier`. Deptrac enforces `Entitlement ⟂ Tiers`.
 *
 * Non-`final` for deliberate testability seams (the 4.1 {@see MembershipPlans} / 4.2
 * {@see PurchaseActivation} / 4.3 {@see SubmissionGate} precedent): the WC-active check,
 * the page-conditional reads, the redirect, and the lidmaatskap-product resolution are
 * `protected` so the unit suite can drive each branch deterministically without a live
 * WooCommerce (Brain Monkey-defined symbols persist within a process).
 *
 * @package Ink\Core
 */
class StorefrontSuppression {

	/**
	 * The single documented re-open seam: a context may filter this true to be EXEMPT
	 * from suppression for one decision (the carve-out extension point).
	 *
	 * `apply_filters( self::FILTER_SUPPRESS_EXCEPTION, false, $context, $detail )` —
	 * returns false everywhere by default (suppression applies). Mirrors
	 * {@see \Ink\Engagement\Comments::FILTER_OPEN_EXCEPTION}. The built-in checkout +
	 * lidmaatskap-product carve-outs do NOT rely on this filter; it is for future,
	 * narrower exemptions.
	 */
	public const FILTER_SUPPRESS_EXCEPTION = 'ink_store_suppress_exception';

	/** Documented WP hook the storefront pages also render through (redirect point). */
	public const HOOK_TEMPLATE_REDIRECT = 'template_redirect';

	/** Documented WC filter: whether a product can be purchased (add-to-cart guard). */
	public const HOOK_IS_PURCHASABLE = 'woocommerce_is_purchasable';

	/** Documented WC filter: the "add to cart" link on a catalog loop item. */
	public const HOOK_LOOP_ADD_TO_CART = 'woocommerce_loop_add_to_cart_link';

	/** Documented WC filter: the My-Account navigation tabs. */
	public const HOOK_ACCOUNT_MENU_ITEMS = 'woocommerce_account_menu_items';

	/** Documented WP-core hook: alter the main query before posts are fetched. */
	public const HOOK_PRE_GET_POSTS = 'pre_get_posts';

	/** The WooCommerce product post type (excluded from front-end search). */
	public const POST_TYPE_PRODUCT = 'product';

	/** WooCommerce conditional-tag function names (the platform's own — guarded). */
	public const FN_IS_SHOP     = 'is_shop';
	public const FN_IS_PRODUCT  = 'is_product';
	public const FN_IS_CART     = 'is_cart';
	public const FN_IS_CHECKOUT = 'is_checkout';

	/**
	 * The WooCommerce endpoint-URL conditional + the PayFast-return endpoints.
	 *
	 * After an off-site PayFast payment WooCommerce returns the visitor to one of its
	 * checkout endpoints — `order-received` (the thank-you page) and `order-pay` (the
	 * pay-for-an-order page). `is_checkout()` is not guaranteed truthy on every theme /
	 * WC version for these endpoint URLs, so the checkout carve-out exempts them
	 * EXPLICITLY via `is_wc_endpoint_url()` (function_exists-guarded). These are the
	 * PayFast-return surfaces — they must NEVER be redirected (AC-2).
	 */
	public const FN_IS_WC_ENDPOINT_URL   = 'is_wc_endpoint_url';
	public const ENDPOINT_ORDER_RECEIVED = 'order-received';
	public const ENDPOINT_ORDER_PAY      = 'order-pay';

	/** WooCommerce product-taxonomy conditional-tag function names (guarded). */
	public const FN_IS_PRODUCT_CATEGORY = 'is_product_category';
	public const FN_IS_PRODUCT_TAG      = 'is_product_tag';
	public const FN_IS_TAX              = 'is_tax';
	public const TAX_PRODUCT_CAT        = 'product_cat';
	public const TAX_PRODUCT_TAG        = 'product_tag';

	/**
	 * The add-to-cart request key WooCommerce reads to add a product to the cart.
	 *
	 * The Story-4.2 purchase flow builds `<checkout-url>?add-to-cart=<product_id>`; the
	 * carve-out reads this key (sanitised to int, never a raw superglobal) to decide
	 * whether a redirected page is actually a lidmaatskap purchase in progress.
	 */
	public const ADD_TO_CART_ARG = 'add-to-cart';

	/**
	 * The My-Account storefront tabs pruned on a community membership site.
	 *
	 * `downloads` is a digital-store tab with no meaning for a membership-only site.
	 * `orders` / `payment-methods` / `edit-account` / `dashboard` are KEPT (a lid may
	 * review a lidmaatskap order or manage their account). WooCommerce's own slugs
	 * (no `ink_` prefix — the platform's vocabulary).
	 *
	 * @var list<string>
	 */
	private const REMOVED_ACCOUNT_TABS = array( 'downloads' );

	/**
	 * Register the suppression hooks — ONLY when WooCommerce is active.
	 *
	 * Invoked once from {@see Module::register()} (dispatched by the Kernel on `init`).
	 * When WooCommerce is inactive the method returns early having wired NOTHING, so the
	 * seam is a complete no-op and never fatals on a missing WC conditional/function
	 * (AC-1). When active, each hook is wired via a first-class-callable method
	 * reference (the {@see \Ink\Engagement\Comments::register()} shape).
	 */
	public function register(): void {
		if ( ! $this->isWooCommerceActive() ) {
			return; // WooCommerce absent ⇒ zero hooks, graceful no-op.
		}

		// Redirect the shop/catalog/cart pages away — carve out checkout + lidmaatskap.
		add_action( self::HOOK_TEMPLATE_REDIRECT, array( $this, 'onTemplateRedirect' ) );

		// Make non-lidmaatskap products un-purchasable + strip their add-to-cart link.
		add_filter( self::HOOK_IS_PURCHASABLE, array( $this, 'filterIsPurchasable' ), 10, 2 );
		add_filter( self::HOOK_LOOP_ADD_TO_CART, array( $this, 'filterLoopAddToCartLink' ), 10, 2 );

		// Prune the inapplicable My-Account storefront tabs.
		add_filter( self::HOOK_ACCOUNT_MENU_ITEMS, array( $this, 'filterAccountMenuItems' ) );

		// Exclude the `product` post type from FRONT-END search (reads as a community,
		// not a shop — no stray product surfaces in search results).
		add_action( self::HOOK_PRE_GET_POSTS, array( $this, 'filterSearchQuery' ) );
	}

	/**
	 * Redirect the shop / product-archive / cart pages away — carving out the checkout
	 * and any lidmaatskap-product add-to-cart in progress.
	 *
	 * SUPPRESSED: `is_shop()`, `is_product()` / `is_post_type_archive('product')`,
	 * `is_cart()`. ALLOWED (no redirect): `is_checkout()` and a request carrying
	 * `?add-to-cart=<lidmaatskap product_id>` (the Story-4.2 purchase hand-off). The
	 * decision additionally passes through {@see FILTER_SUPPRESS_EXCEPTION} so a future
	 * context can exempt itself. Every WC conditional is `function_exists()`-guarded so a
	 * partial/loading WooCommerce never fatals.
	 */
	public function onTemplateRedirect(): void {
		// CARVE-OUT 1: the checkout page + the PayFast-return endpoints (order-received /
		// order-pay) are the purchase/return surfaces — never redirect them (AC-2).
		if ( $this->isCheckout() ) {
			return;
		}

		// CARVE-OUT 2: a lidmaatskap-product add-to-cart in progress — let it reach the
		// cart/checkout (this is the 4.2 purchase flow, not general browsing). When NO
		// lidmaatskap product is configured yet (empty map), {@see isLidmaatskapAddToCart()}
		// treats ANY add-to-cart as a potential membership purchase and exempts it — better
		// to under-suppress the generic store than risk blocking the membership flow.
		if ( $this->isLidmaatskapAddToCart() ) {
			return;
		}

		// CARVE-OUT 3 (FIX-1): a lid mid-purchase who lands on the CART page WITHOUT an
		// add-to-cart param — WooCommerce's "redirect to cart after add" setting and any
		// "view cart" click do this. If the cart holds a lidmaatskap product, the purchase
		// is in progress: do NOT bounce them off the cart (would break checkout).
		if ( $this->cartContainsLidmaatskap() ) {
			return;
		}

		// Only the general-store surfaces are suppressed.
		if ( ! $this->isSuppressedStorePage() ) {
			return;
		}

		// Re-open seam: a context may exempt itself (default false ⇒ suppress).
		if ( (bool) apply_filters( self::FILTER_SUPPRESS_EXCEPTION, false, 'template_redirect', '' ) ) {
			return;
		}

		$this->redirectAway();
	}

	/**
	 * Whether a product may be purchased — true ONLY for a configured lidmaatskap product.
	 *
	 * The carve-out as a filter: a lidmaatskap product STAYS purchasable (the purchase
	 * flow works); any other product becomes un-purchasable, so a stray generic
	 * "add to cart" is inert. A context may re-open via {@see FILTER_SUPPRESS_EXCEPTION}.
	 *
	 * @param bool  $purchasable Incoming purchasable flag from WooCommerce.
	 * @param mixed $product     The WC_Product (read behind a `method_exists` guard).
	 * @return bool True only for a lidmaatskap product (or an exempted context).
	 */
	public function filterIsPurchasable( bool $purchasable, mixed $product ): bool {
		// FIX-5: this WC filter also fires in admin / REST / Store-API contexts (admin
		// order creation, REST product reads). Suppression is a STOREFRONT concern — never
		// leak un-purchasability into those contexts. Return the value unchanged off the
		// front end.
		if ( ! $this->isFrontEndStorefront() ) {
			return $purchasable;
		}

		// FIX-2: fail OPEN when NO lidmaatskap product is configured. Until an admin maps
		// products the set is empty; forcing everything un-purchasable would make the
		// would-be membership product un-purchasable too and break checkout. Suppress
		// nothing when the set is empty — under-suppress the generic store rather than
		// risk blocking the membership flow.
		if ( array() === $this->lidmaatskapProductIds() ) {
			return $purchasable;
		}

		$product_id = $this->productId( $product );

		if ( $product_id > 0 && $this->isLidmaatskapProduct( $product_id ) ) {
			return $purchasable; // Lidmaatskap product ⇒ leave WooCommerce's decision intact.
		}

		if ( (bool) apply_filters( self::FILTER_SUPPRESS_EXCEPTION, false, 'is_purchasable', (string) $product_id ) ) {
			return $purchasable; // Exempted context ⇒ unchanged.
		}

		return false; // Non-lidmaatskap product ⇒ not purchasable (inert add-to-cart).
	}

	/**
	 * Remove the catalog-loop "add to cart" link for non-lidmaatskap products.
	 *
	 * A removal (no new copy): returns an empty string for any non-lidmaatskap product,
	 * so generic loop items show no add-to-cart affordance. A lidmaatskap product keeps
	 * its link untouched.
	 *
	 * @param string $link    The add-to-cart link HTML from WooCommerce.
	 * @param mixed  $product The WC_Product (read behind a `method_exists` guard).
	 * @return string The link for a lidmaatskap product; empty string otherwise.
	 */
	public function filterLoopAddToCartLink( string $link, mixed $product ): string {
		// FIX-5: front-end-only — the loop link filter can also fire in admin / REST.
		if ( ! $this->isFrontEndStorefront() ) {
			return $link;
		}

		// FIX-2: fail OPEN with an empty map — keep WooCommerce's link when no lidmaatskap
		// product is configured (suppress nothing rather than risk the membership flow).
		if ( array() === $this->lidmaatskapProductIds() ) {
			return $link;
		}

		$product_id = $this->productId( $product );

		if ( $product_id > 0 && $this->isLidmaatskapProduct( $product_id ) ) {
			return $link; // Lidmaatskap product ⇒ keep its add-to-cart link.
		}

		if ( (bool) apply_filters( self::FILTER_SUPPRESS_EXCEPTION, false, 'loop_add_to_cart', (string) $product_id ) ) {
			return $link;
		}

		return ''; // Non-lidmaatskap ⇒ no add-to-cart affordance.
	}

	/**
	 * Prune the inapplicable My-Account storefront tabs.
	 *
	 * Removes the {@see REMOVED_ACCOUNT_TABS} (e.g. `downloads`) that have no meaning on a
	 * community membership site; keeps the rest (orders / payment-methods / account) so a
	 * lid can review a lidmaatskap order. FAIL-SAFE (FIX-6): a non-array input is returned
	 * UNTOUCHED — blanking the whole account menu (returning `array()`) would be
	 * fail-destructive; if WooCommerce hands us an unexpected shape we leave it alone.
	 *
	 * @param mixed $items The WC account-menu items (slug => label).
	 * @return mixed The pruned menu (array), or the original input unchanged when non-array.
	 */
	public function filterAccountMenuItems( mixed $items ): mixed {
		if ( ! is_array( $items ) ) {
			return $items; // Fail-safe: never blank the whole menu on an unexpected shape.
		}

		foreach ( self::REMOVED_ACCOUNT_TABS as $slug ) {
			unset( $items[ $slug ] );
		}

		return $items;
	}

	/**
	 * Whether the current request is one of the suppressed general-store pages.
	 *
	 * True for the shop page, a product single/archive, a product TAXONOMY archive
	 * (category / tag — FIX-6, matching the docblock/AC-1 claim), or the cart page — each
	 * WC conditional `function_exists()`-guarded so a partial WooCommerce never fatals.
	 *
	 * @return bool True when the request is a suppressed store page.
	 */
	protected function isSuppressedStorePage(): bool {
		if ( function_exists( self::FN_IS_SHOP ) && is_shop() ) {
			return true;
		}

		if ( function_exists( self::FN_IS_PRODUCT ) && is_product() ) {
			return true;
		}

		if ( function_exists( 'is_post_type_archive' ) && is_post_type_archive( self::POST_TYPE_PRODUCT ) ) {
			return true;
		}

		if ( $this->isProductTaxonomyArchive() ) {
			return true;
		}

		if ( function_exists( self::FN_IS_CART ) && is_cart() ) {
			return true;
		}

		return false;
	}

	/**
	 * Whether the request is a product taxonomy archive (category / tag) — FIX-6.
	 *
	 * AC-1 / the class docblock claim product taxonomy archives are suppressed; this makes
	 * the code match the claim. Checks `is_product_category()` / `is_product_tag()` and the
	 * generic `is_tax('product_cat'|'product_tag')` (covering custom product taxonomies),
	 * each `function_exists()`-guarded so a partial WooCommerce never fatals.
	 *
	 * @return bool True on a product category / tag archive.
	 */
	protected function isProductTaxonomyArchive(): bool {
		if ( function_exists( self::FN_IS_PRODUCT_CATEGORY ) && is_product_category() ) {
			return true;
		}

		if ( function_exists( self::FN_IS_PRODUCT_TAG ) && is_product_tag() ) {
			return true;
		}

		if ( function_exists( self::FN_IS_TAX )
			&& ( is_tax( self::TAX_PRODUCT_CAT ) || is_tax( self::TAX_PRODUCT_TAG ) ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Whether the current request is the checkout surface (carve-out — never redirected).
	 *
	 * Exempts (a) the checkout page (`is_checkout()`) AND (b) the WooCommerce checkout
	 * ENDPOINTS PayFast returns to — `order-received` (thank-you) and `order-pay` (FIX-3).
	 * `is_checkout()` alone is unverified for these endpoint URLs across themes / WC
	 * versions, so the PayFast-return surfaces are exempted explicitly via
	 * `is_wc_endpoint_url()`. All guards behind `function_exists()` (partial WC = no fatal).
	 *
	 * @return bool True on the checkout page or a PayFast-return checkout endpoint.
	 */
	protected function isCheckout(): bool {
		if ( function_exists( self::FN_IS_CHECKOUT ) && is_checkout() ) {
			return true;
		}

		if ( function_exists( self::FN_IS_WC_ENDPOINT_URL )
			&& ( is_wc_endpoint_url( self::ENDPOINT_ORDER_RECEIVED )
				|| is_wc_endpoint_url( self::ENDPOINT_ORDER_PAY ) ) ) {
			return true; // PayFast-return endpoint — must stay reachable (AC-2).
		}

		return false;
	}

	/**
	 * Whether the request carries an add-to-cart for a configured lidmaatskap product.
	 *
	 * The Story-4.2 purchase hand-off is `?add-to-cart=<lidmaatskap product_id>`; this is
	 * the carve-out signal that a lidmaatskap purchase is in progress (so the cart/checkout
	 * must be reachable). Reads the requested id SET via {@see addToCartProductIds()}
	 * (handles single / comma-multi / array forms — FIX-4; never a raw superglobal) and
	 * carves out if ANY requested id is a lidmaatskap product.
	 *
	 * FIX-2 (fail OPEN): when NO lidmaatskap product is configured (empty map) ANY
	 * add-to-cart request is treated as a potential membership purchase and exempted — the
	 * map is empty until an admin maps products, and a wrongly-redirected add-to-cart would
	 * break the membership flow. Better to under-suppress the generic store.
	 *
	 * @return bool True when a lidmaatskap (or, with an empty map, any) product is added.
	 */
	protected function isLidmaatskapAddToCart(): bool {
		$requested = $this->addToCartProductIds();

		if ( array() === $requested ) {
			return false; // No add-to-cart in progress.
		}

		// Empty map ⇒ nothing is classified yet ⇒ treat any add-to-cart as the membership
		// flow (fail OPEN — never bounce a potential purchase).
		if ( array() === $this->lidmaatskapProductIds() ) {
			return true;
		}

		foreach ( $requested as $product_id ) {
			if ( $this->isLidmaatskapProduct( $product_id ) ) {
				return true; // ANY requested id is a lidmaatskap product ⇒ carve out.
			}
		}

		return false;
	}

	/**
	 * The SET of product ids in the `add-to-cart` request (single / multi / array forms).
	 *
	 * WooCommerce accepts three add-to-cart shapes; a single-id read would MISS a
	 * lidmaatskap product inside a multi-add (FIX-4):
	 *  - single:        `?add-to-cart=12`        → [12]
	 *  - comma-multi:   `?add-to-cart=12,34`     → [12, 34]
	 *  - array form:    `?add-to-cart[]=12&...`  → [12, 34]
	 *
	 * Reads the raw request value via `filter_input( …, FILTER_UNSAFE_RAW )` (NOT a raw
	 * `$_GET`/`$_POST` superglobal — the security rule), then sanitises EACH id to a
	 * positive int itself (a comma-list / array can't be coerced with a scalar int filter).
	 * Garbage (`abc`, empty) yields no ids (treated as no lidmaatskap add-to-cart). A
	 * `protected` seam so the unit suite can pin it deterministically.
	 *
	 * @return list<int> The requested product ids (positive ints only; possibly empty).
	 */
	protected function addToCartProductIds(): array {
		$raw = filter_input( INPUT_GET, self::ADD_TO_CART_ARG, FILTER_UNSAFE_RAW, FILTER_REQUIRE_ARRAY );

		if ( null === $raw || false === $raw ) {
			$raw = filter_input( INPUT_GET, self::ADD_TO_CART_ARG, FILTER_UNSAFE_RAW );
		}

		if ( null === $raw || false === $raw ) {
			$raw = filter_input( INPUT_POST, self::ADD_TO_CART_ARG, FILTER_UNSAFE_RAW, FILTER_REQUIRE_ARRAY );
		}

		if ( null === $raw || false === $raw ) {
			$raw = filter_input( INPUT_POST, self::ADD_TO_CART_ARG, FILTER_UNSAFE_RAW );
		}

		return $this->parseProductIds( $raw );
	}

	/**
	 * Parse a raw add-to-cart value (scalar / comma-list / array) into a positive-int set.
	 *
	 * Each candidate is sanitised independently: an array is flattened, a string is split
	 * on commas, and every piece is coerced to a positive int (non-numeric / zero / negative
	 * dropped). A `protected` seam so {@see addToCartProductIds()} can be driven in tests
	 * without touching `filter_input`.
	 *
	 * @param mixed $raw The raw request value (string, array, or null/false).
	 * @return list<int> The positive product ids (possibly empty).
	 */
	protected function parseProductIds( mixed $raw ): array {
		if ( null === $raw || false === $raw ) {
			return array();
		}

		$candidates = is_array( $raw ) ? $raw : array( $raw );
		$ids        = array();

		foreach ( $candidates as $candidate ) {
			if ( ! is_scalar( $candidate ) ) {
				continue;
			}

			foreach ( explode( ',', (string) $candidate ) as $piece ) {
				$piece = trim( $piece );

				if ( '' === $piece || ! ctype_digit( $piece ) ) {
					continue; // Non-numeric / garbage ⇒ no id (graceful, no fatal).
				}

				$product_id = (int) $piece;

				if ( $product_id > 0 ) {
					$ids[] = $product_id;
				}
			}
		}

		return array_values( array_unique( $ids ) );
	}

	/**
	 * The WC product id off a WC_Product object, behind a `method_exists` guard (0 = none).
	 *
	 * Never assumes WooCommerce internal structure — reads only the documented `get_id()`
	 * getter. A non-object / malformed product yields 0 (graceful).
	 *
	 * @param mixed $product The WC_Product (or anything).
	 * @return int The product id, or 0.
	 */
	protected function productId( mixed $product ): int {
		if ( is_object( $product ) && method_exists( $product, 'get_id' ) ) {
			return (int) $product->get_id();
		}

		return 0;
	}

	/**
	 * Whether a product id is a configured Story-4.1 lidmaatskap product (the carve-out).
	 *
	 * @param int $product_id The WooCommerce product id.
	 * @return bool True when the id is a configured lidmaatskap product.
	 */
	protected function isLidmaatskapProduct( int $product_id ): bool {
		return in_array( $product_id, $this->lidmaatskapProductIds(), true );
	}

	/**
	 * The configured Story-4.1 lidmaatskap product ids (the carve-out distinguisher).
	 *
	 * Resolved through the 4.1 {@see MembershipPlans} registry (never reimplemented) — the
	 * product id mapped to each fixed term in `ink_membership_plan_products`. This is the
	 * SAME set {@see SubmissionGate::inkProductIds()} uses, so "is this a lidmaatskap
	 * product?" has one source of truth. A `protected` seam so the unit suite can pin the
	 * set without building the option/filter chain.
	 *
	 * @return list<int> The configured lidmaatskap product ids (possibly empty).
	 */
	protected function lidmaatskapProductIds(): array {
		$plans       = new MembershipPlans();
		$product_ids = array();

		foreach ( MembershipPlans::terms() as $term ) {
			$product_id = $plans->productIdFor( $term );

			if ( null !== $product_id ) {
				$product_ids[] = $product_id;
			}
		}

		return array_values( array_unique( $product_ids ) );
	}

	/**
	 * Whether WooCommerce's cart currently holds a configured lidmaatskap product (FIX-1).
	 *
	 * WooCommerce's "redirect to cart after add" setting and any "view cart" click land a
	 * mid-purchase lid on the CART page WITHOUT an `add-to-cart` param — the add-to-cart
	 * carve-out would miss it and the cart would be redirected away, breaking checkout. So
	 * the cart is also exempt when it ALREADY contains a lidmaatskap product.
	 *
	 * Reads `WC()->cart->get_cart()` behind full availability guards (`function_exists('WC')`,
	 * `is_object`, `method_exists`/`is_callable`, `is_array`) so a partial/absent WooCommerce
	 * — or a request before the cart is built — degrades to `false`, never a fatal. Reads
	 * only the documented `product_id` / `variation_id` cart-item keys (no internal-structure
	 * assumption). A `protected` seam so the unit suite can pin it.
	 *
	 * @return bool True when any cart item is a configured lidmaatskap product.
	 */
	protected function cartContainsLidmaatskap(): bool {
		$lidmaatskap_ids = $this->lidmaatskapProductIds();

		if ( array() === $lidmaatskap_ids ) {
			return false; // Nothing classified yet — the empty-map fail-open is handled
							// by the add-to-cart carve-out; here a false is safe.
		}

		$items = $this->cartItems();

		foreach ( $items as $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}

			$product_id   = isset( $item['product_id'] ) && is_scalar( $item['product_id'] ) ? (int) $item['product_id'] : 0;
			$variation_id = isset( $item['variation_id'] ) && is_scalar( $item['variation_id'] ) ? (int) $item['variation_id'] : 0;

			if ( ( $product_id > 0 && $this->isLidmaatskapProduct( $product_id ) )
				|| ( $variation_id > 0 && $this->isLidmaatskapProduct( $variation_id ) ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * The current WooCommerce cart items, behind full availability guards (FIX-1).
	 *
	 * Reads `WC()->cart->get_cart()` only when `WC()`, its `->cart`, and a callable
	 * `get_cart()` are all present — otherwise an empty array (graceful no-op). A
	 * `protected` seam so {@see cartContainsLidmaatskap()} is unit-testable without a live
	 * WooCommerce.
	 *
	 * @return array<mixed> The cart items (possibly empty).
	 */
	protected function cartItems(): array {
		if ( ! function_exists( 'WC' ) ) {
			return array();
		}

		$wc = WC();

		if ( ! is_object( $wc ) || ! isset( $wc->cart ) || ! is_object( $wc->cart ) ) {
			return array();
		}

		if ( ! is_callable( array( $wc->cart, 'get_cart' ) ) ) {
			return array();
		}

		$items = $wc->cart->get_cart();

		return is_array( $items ) ? $items : array();
	}

	/**
	 * Whether the current request is a FRONT-END storefront request (FIX-5).
	 *
	 * `woocommerce_is_purchasable` / `woocommerce_loop_add_to_cart_link` also fire in
	 * wp-admin, REST, and Store-API contexts (admin order creation, REST product reads).
	 * Suppression is a storefront concern — it must NOT force products un-purchasable in
	 * those contexts. Returns false for `is_admin()` and for a REST request
	 * (`REST_REQUEST`), so the suppression filters return their value unchanged there. A
	 * `protected` seam so the unit suite can pin the context.
	 *
	 * @return bool True only for a front-end storefront request.
	 */
	protected function isFrontEndStorefront(): bool {
		if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
			return false; // REST / Store-API ⇒ not a storefront page render.
		}

		if ( function_exists( 'is_admin' ) && is_admin() ) {
			return false; // wp-admin (incl. admin-ajax order creation) ⇒ not storefront.
		}

		return true;
	}

	/**
	 * Exclude the `product` post type from FRONT-END search results (FIX-6).
	 *
	 * A "reads as a community, not a shop" measure: products must not surface in the site
	 * search. Acts ONLY on the FRONT-END main search query — guarded on
	 * `! is_admin()`, `$query->is_main_query()`, and `$query->is_search()` so it never
	 * touches an admin search, a secondary query, or a non-search query. Narrows the
	 * searched post types to those WordPress would otherwise search, minus `product`.
	 *
	 * @param mixed $query The `WP_Query` passed by `pre_get_posts`.
	 * @return void
	 */
	public function filterSearchQuery( mixed $query ): void {
		if ( ! is_object( $query )
			|| ! method_exists( $query, 'is_main_query' )
			|| ! method_exists( $query, 'is_search' )
			|| ! method_exists( $query, 'get' )
			|| ! method_exists( $query, 'set' ) ) {
			return; // Not a usable WP_Query ⇒ graceful no-op.
		}

		if ( function_exists( 'is_admin' ) && is_admin() ) {
			return; // Admin search is unaffected (FIX-6 is front-end only).
		}

		if ( ! $query->is_main_query() || ! $query->is_search() ) {
			return; // Only the front-end main search query.
		}

		$post_types = $query->get( 'post_type' );

		if ( empty( $post_types ) ) {
			// Default WP search is `post`-only; replace with the searchable set minus
			// `product` so the search reads as a community, not a shop.
			$searchable = function_exists( 'get_post_types' )
				? array_keys( (array) get_post_types( array( 'exclude_from_search' => false ) ) )
				: array( 'post' );
			$post_types = $searchable;
		} else {
			$post_types = (array) $post_types;
		}

		$post_types = array_values(
			array_filter(
				$post_types,
				static fn( $type ): bool => self::POST_TYPE_PRODUCT !== $type
			)
		);

		$query->set( 'post_type', $post_types );
	}

	/**
	 * Redirect the visitor away from a suppressed store page.
	 *
	 * Default target is the site home (`home_url()`) — the site reads as a community, so a
	 * shop/cart URL lands the visitor back on the community front, NOT a store. A
	 * `protected` seam so the unit suite asserts the redirect+exit without a real WP
	 * runtime. Uses `wp_safe_redirect()` (local-only) and stops execution.
	 */
	protected function redirectAway(): void {
		wp_safe_redirect( home_url( '/' ) );
		exit;
	}

	/**
	 * Whether WooCommerce is active in this request.
	 *
	 * The "do not reimplement" / no-op-when-absent boundary (project-context.md): when
	 * WooCommerce is inactive {@see register()} wires no hooks rather than fatalling on a
	 * missing conditional/function. A `protected` seam (not an inline `class_exists()`) so
	 * the "absent" branch is deterministically unit-testable via a test subclass — the
	 * 4.1/4.2/4.3 precedent (Brain Monkey-defined symbols persist within a process).
	 *
	 * @return bool True when the `WooCommerce` class is loaded.
	 */
	protected function isWooCommerceActive(): bool {
		return class_exists( 'WooCommerce' );
	}
}

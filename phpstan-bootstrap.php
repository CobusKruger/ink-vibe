<?php
/**
 * PHPStan-only bootstrap.
 *
 * Declares the `ink-core` plugin constants that are defined at runtime in the
 * plugin's main file (`wp-content/plugins/ink-core/ink-core.php`) — which sits
 * OUTSIDE the analysed `src/` path. Without these declarations PHPStan reports
 * `INK_CORE_PATH` / `INK_CORE_FILE` / `INK_CORE_VERSION` / `INK_CORE_URL` as
 * undefined (`constant.notFound`) wherever `src/` references them.
 *
 * It also declares the signatures of OPTIONAL platform-plugin functions that
 * `ink-core` calls behind a `function_exists()` guard but that are NOT in the
 * WordPress-core stubs — currently WooCommerce's `wc_get_product()` (Story 4.1,
 * `Ink\Entitlement\MembershipPlans`) and `wc_get_checkout_url()` (Story 4.2,
 * `Ink\Entitlement\PurchaseActivation`). WooCommerce is a platform plugin assembled
 * at build time (AD-4) and is not a Composer stub dependency, so PHPStan reports
 * `function.notFound` for its symbols. Declaring the signature here (analysis-
 * only) resolves it without an `@phpstan-ignore` suppression; the runtime call
 * is `function_exists()`-guarded so it never fatals when WooCommerce is inactive.
 *
 * This file is loaded only via `bootstrapFiles` in `phpstan.neon`. It is never
 * shipped or loaded at runtime — WordPress loads `ink-core.php`, which defines
 * the real values. The values here exist purely so static analysis resolves the
 * symbols and their (string) types.
 *
 * @package Ink\Core
 */

declare(strict_types=1);

defined( 'INK_CORE_VERSION' ) || define( 'INK_CORE_VERSION', '0.1.0' );
defined( 'INK_CORE_FILE' ) || define( 'INK_CORE_FILE', __DIR__ . '/wp-content/plugins/ink-core/ink-core.php' );
defined( 'INK_CORE_PATH' ) || define( 'INK_CORE_PATH', __DIR__ . '/wp-content/plugins/ink-core/' );
defined( 'INK_CORE_URL' ) || define( 'INK_CORE_URL', 'https://example.test/wp-content/plugins/ink-core/' );

if ( ! function_exists( 'wc_get_product' ) ) {
	/**
	 * Analysis-only signature for WooCommerce's product accessor (platform plugin).
	 *
	 * Real implementation ships with WooCommerce. `ink-core` calls it only behind a
	 * `function_exists()` guard ({@see \Ink\Entitlement\MembershipPlans}).
	 *
	 * @param int|\WP_Post|bool $the_product Product id / post / object.
	 * @return object|false The WooCommerce product object, or false when not found.
	 */
	function wc_get_product( $the_product = false ) { // phpcs:ignore
		return false;
	}
}

if ( ! function_exists( 'wc_get_checkout_url' ) ) {
	/**
	 * Analysis-only signature for WooCommerce's checkout-URL accessor (platform plugin).
	 *
	 * Real implementation ships with WooCommerce. `ink-core` calls it only behind a
	 * `function_exists()` guard ({@see \Ink\Entitlement\PurchaseActivation}) to hand
	 * the off-site PayFast purchase off to the WooCommerce checkout.
	 *
	 * @return string The WooCommerce checkout page URL.
	 */
	function wc_get_checkout_url() { // phpcs:ignore
		return '';
	}
}

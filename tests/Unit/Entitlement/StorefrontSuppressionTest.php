<?php
/**
 * Unit tests for the WooCommerce storefront-UI suppression seam (Story 4.6, FR-10).
 *
 * Target: {@see \Ink\Entitlement\StorefrontSuppression} — hides the general
 * WooCommerce storefront (shop/catalog/cart, generic add-to-cart, inapplicable
 * My-Account tabs) via documented WooCommerce/WP hooks, while CARVING OUT the
 * lidmaatskap purchase flow (the checkout page + an add-to-cart for a configured
 * Story-4.1 lidmaatskap product).
 *
 * Brain Monkey, no WordPress/DB, NO WooCommerce loaded — every WC conditional /
 * function / hook is mocked. The WC-active gate, the page-conditional reads, the
 * redirect, the add-to-cart product id, and the lidmaatskap-product set are
 * `protected` seams overridden via anonymous test subclasses (the 4.1/4.2/4.3
 * precedent — Brain Monkey-defined symbols persist within a process, so an inline
 * guard can't be simulated as "absent" once a sibling test defines it).
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Entitlement;

use Ink\Entitlement\StorefrontSuppression;
use Brain\Monkey;
use Brain\Monkey\Filters;
use Brain\Monkey\Functions;

beforeEach( function (): void {
	Monkey\setUp();
	// `StorefrontSuppression::isStorefrontRequest()` guards with
	// `function_exists( 'is_admin' ) && is_admin()`. Once ANY earlier test in the
	// process defines `is_admin` (Brain Monkey can't un-define it), that guard
	// stops short-circuiting and calls `is_admin()` — so stub it explicitly to the
	// normal front-end value rather than relying on it being undefined.
	Functions\when( 'is_admin' )->justReturn( false );
} );

/**
 * Default the FILTER_SUPPRESS_EXCEPTION seam to its passthrough (returns its second arg
 * ⇒ false ⇒ suppress). Tests that need the seam to EXEMPT use `Filters\expectApplied`
 * instead and do NOT call this (Brain Monkey forbids a `when()` stub + an
 * `expectApplied()` expectation on the same hook in one test).
 */
function ink_suppress_seam_default(): void {
	Filters\expectApplied( StorefrontSuppression::FILTER_SUPPRESS_EXCEPTION )
		->andReturnFirstArg();
}

afterEach( function (): void {
	Monkey\tearDown();
} );

/**
 * A NAMED WC-active double for the register()-wiring assertions. Brain Monkey's
 * `has_action()` callback introspection rejects an ANONYMOUS class name, so the wiring
 * tests need a concrete class; behaviour tests can still use the anonymous double.
 */
final class InkStorefrontSuppressionWcActive extends StorefrontSuppression {
	protected function isWooCommerceActive(): bool {
		return true;
	}
}

/**
 * A NAMED WC-absent double for the no-op wiring assertion (same introspection reason).
 */
final class InkStorefrontSuppressionWcAbsent extends StorefrontSuppression {
	protected function isWooCommerceActive(): bool {
		return false;
	}
}

/**
 * A test double exposing every suppression branch deterministically: WC active/absent,
 * which store page the request is, whether it is checkout, the add-to-cart product id,
 * and the configured lidmaatskap product set — plus a capturing redirect (no real exit).
 */
function ink_suppression( array $opts = array() ): StorefrontSuppression {
	return new class( $opts ) extends StorefrontSuppression {
		public bool $redirected = false;

		public function __construct( private array $opts ) {}

		protected function isWooCommerceActive(): bool {
			return (bool) ( $this->opts['wc_active'] ?? true );
		}

		protected function isSuppressedStorePage(): bool {
			return (bool) ( $this->opts['store_page'] ?? false );
		}

		protected function isCheckout(): bool {
			return (bool) ( $this->opts['checkout'] ?? false );
		}

		protected function addToCartProductIds(): array {
			$value = $this->opts['add_to_cart'] ?? 0;

			if ( is_array( $value ) ) {
				return array_values( array_map( 'intval', $value ) );
			}

			return 0 === (int) $value ? array() : array( (int) $value );
		}

		protected function lidmaatskapProductIds(): array {
			return (array) ( $this->opts['lidmaatskap_ids'] ?? array() );
		}

		protected function cartContainsLidmaatskap(): bool {
			return (bool) ( $this->opts['cart_has_lid'] ?? false );
		}

		protected function isFrontEndStorefront(): bool {
			return (bool) ( $this->opts['front_end'] ?? true );
		}

		// Capture the redirect instead of exiting, so onTemplateRedirect() is testable.
		protected function redirectAway(): void {
			$this->redirected = true;
		}
	};
}

/** A minimal WC_Product double exposing only the documented get_id() getter. */
function ink_product( int $id ): object {
	return new class( $id ) {
		public function __construct( private int $id ) {}
		public function get_id(): int {
			return $this->id;
		}
	};
}

/**
 * AC-1/AC-3: the hook + arg keys are the exact single-source constants — never
 * scattered literals.
 */
test( 'the hook and arg keys are the exact single-source constants', function (): void {
	expect( StorefrontSuppression::HOOK_TEMPLATE_REDIRECT )->toBe( 'template_redirect' );
	expect( StorefrontSuppression::HOOK_IS_PURCHASABLE )->toBe( 'woocommerce_is_purchasable' );
	expect( StorefrontSuppression::HOOK_LOOP_ADD_TO_CART )->toBe( 'woocommerce_loop_add_to_cart_link' );
	expect( StorefrontSuppression::HOOK_ACCOUNT_MENU_ITEMS )->toBe( 'woocommerce_account_menu_items' );
	expect( StorefrontSuppression::ADD_TO_CART_ARG )->toBe( 'add-to-cart' );
	expect( StorefrontSuppression::FILTER_SUPPRESS_EXCEPTION )->toBe( 'ink_store_suppress_exception' );
} );

/**
 * AC-1/AC-4: register() wires the suppression hooks when WooCommerce is ACTIVE.
 */
test( 'register() wires the suppression hooks when WooCommerce is active', function (): void {
	$suppression = new InkStorefrontSuppressionWcActive();
	$suppression->register();

	expect( has_action( StorefrontSuppression::HOOK_TEMPLATE_REDIRECT, array( $suppression, 'onTemplateRedirect' ) ) )->not->toBeFalse();
	expect( has_filter( StorefrontSuppression::HOOK_IS_PURCHASABLE, array( $suppression, 'filterIsPurchasable' ) ) )->not->toBeFalse();
	expect( has_filter( StorefrontSuppression::HOOK_LOOP_ADD_TO_CART, array( $suppression, 'filterLoopAddToCartLink' ) ) )->not->toBeFalse();
	expect( has_filter( StorefrontSuppression::HOOK_ACCOUNT_MENU_ITEMS, array( $suppression, 'filterAccountMenuItems' ) ) )->not->toBeFalse();
} );

/**
 * AC-1 (the no-op guarantee): register() wires ZERO hooks when WooCommerce is ABSENT —
 * no fatal, no hooks.
 */
test( 'register() wires nothing when WooCommerce is absent (graceful no-op)', function (): void {
	$suppression = new InkStorefrontSuppressionWcAbsent();
	$suppression->register();

	expect( has_action( StorefrontSuppression::HOOK_TEMPLATE_REDIRECT, array( $suppression, 'onTemplateRedirect' ) ) )->toBeFalse();
	expect( has_filter( StorefrontSuppression::HOOK_IS_PURCHASABLE, array( $suppression, 'filterIsPurchasable' ) ) )->toBeFalse();
	expect( has_filter( StorefrontSuppression::HOOK_LOOP_ADD_TO_CART, array( $suppression, 'filterLoopAddToCartLink' ) ) )->toBeFalse();
	expect( has_filter( StorefrontSuppression::HOOK_ACCOUNT_MENU_ITEMS, array( $suppression, 'filterAccountMenuItems' ) ) )->toBeFalse();
} );

/**
 * AC-1: a generic store page (shop/catalog/cart), not carved out, is redirected away.
 */
test( 'a generic store page is redirected away', function (): void {
	ink_suppress_seam_default();

	$suppression = ink_suppression(
		array(
			'store_page'  => true,
			'checkout'    => false,
			'add_to_cart' => 0,
		)
	);

	$suppression->onTemplateRedirect();

	expect( $suppression->redirected )->toBeTrue();
} );

/**
 * AC-2 (CARVE-OUT — checkout): the checkout page is NEVER redirected, so the purchase
 * endpoint stays reachable.
 */
test( 'the checkout page is never redirected (carve-out)', function (): void {
	$suppression = ink_suppression(
		array(
			'store_page' => true, // even if conditionals would say "store", checkout wins.
			'checkout'   => true,
		)
	);

	$suppression->onTemplateRedirect();

	expect( $suppression->redirected )->toBeFalse();
} );

/**
 * AC-2 (CARVE-OUT — lidmaatskap add-to-cart): a request adding a configured lidmaatskap
 * product to the cart is NOT redirected — the Story-4.2 `?add-to-cart=<lidmaatskap id>`
 * hand-off passes through even on a cart page.
 */
test( 'a lidmaatskap-product add-to-cart request is not redirected (carve-out)', function (): void {
	$suppression = ink_suppression(
		array(
			'store_page'      => true,
			'checkout'        => false,
			'add_to_cart'     => 106,          // the lidmaatskap product being purchased.
			'lidmaatskap_ids' => array( 106 ), // configured 4.1 product set.
		)
	);

	$suppression->onTemplateRedirect();

	expect( $suppression->redirected )->toBeFalse();
} );

/**
 * AC-2: an add-to-cart for a NON-lidmaatskap product on a store page is still
 * suppressed (only the lidmaatskap products are carved out).
 */
test( 'a non-lidmaatskap add-to-cart on a store page is still suppressed', function (): void {
	ink_suppress_seam_default();

	$suppression = ink_suppression(
		array(
			'store_page'      => true,
			'add_to_cart'     => 999,          // some other product.
			'lidmaatskap_ids' => array( 106 ),
		)
	);

	$suppression->onTemplateRedirect();

	expect( $suppression->redirected )->toBeTrue();
} );

/**
 * AC-1: a non-store page (e.g. a community page) is left alone — no redirect.
 */
test( 'a non-store page is not redirected', function (): void {
	$suppression = ink_suppression( array( 'store_page' => false ) );

	$suppression->onTemplateRedirect();

	expect( $suppression->redirected )->toBeFalse();
} );

/**
 * AC-2 (re-open seam): a context filtering FILTER_SUPPRESS_EXCEPTION true is EXEMPT —
 * the store page is not redirected.
 */
test( 'the FILTER_SUPPRESS_EXCEPTION seam exempts a store page when filtered true', function (): void {
	Filters\expectApplied( StorefrontSuppression::FILTER_SUPPRESS_EXCEPTION )
		->andReturn( true );

	$suppression = ink_suppression( array( 'store_page' => true ) );

	$suppression->onTemplateRedirect();

	expect( $suppression->redirected )->toBeFalse();
} );

/**
 * AC-2 (CARVE-OUT — purchasability): a configured lidmaatskap product STAYS purchasable;
 * any other product becomes un-purchasable (inert generic add-to-cart).
 */
test( 'is_purchasable keeps lidmaatskap products purchasable and blocks others', function (): void {
	// The non-lidmaatskap branch consults the seam (default passthrough ⇒ suppress).
	Filters\expectApplied( StorefrontSuppression::FILTER_SUPPRESS_EXCEPTION )
		->andReturnFirstArg();

	$suppression = new class() extends StorefrontSuppression {
		protected function lidmaatskapProductIds(): array {
			return array( 106 );
		}
	};

	// Lidmaatskap product ⇒ WooCommerce's incoming decision (true) is preserved
	// (returns BEFORE the seam, so the seam is consulted only for the 999 product).
	expect( $suppression->filterIsPurchasable( true, ink_product( 106 ) ) )->toBeTrue();

	// Non-lidmaatskap product ⇒ forced un-purchasable.
	expect( $suppression->filterIsPurchasable( true, ink_product( 999 ) ) )->toBeFalse();
} );

/**
 * AC-2 (re-open seam): a context may re-open purchasability for a non-lidmaatskap
 * product via FILTER_SUPPRESS_EXCEPTION.
 */
test( 'is_purchasable honours the exception seam for a non-lidmaatskap product', function (): void {
	Filters\expectApplied( StorefrontSuppression::FILTER_SUPPRESS_EXCEPTION )
		->andReturn( true );

	$suppression = new class() extends StorefrontSuppression {
		protected function lidmaatskapProductIds(): array {
			return array( 106 );
		}
	};

	expect( $suppression->filterIsPurchasable( true, ink_product( 999 ) ) )->toBeTrue();
} );

/**
 * AC-1/AC-2: the loop add-to-cart link is removed for non-lidmaatskap products and kept
 * for lidmaatskap products (a removal, no new copy).
 */
test( 'the loop add-to-cart link is stripped for non-lidmaatskap and kept for lidmaatskap', function (): void {
	// Only the non-lidmaatskap (999) branch consults the seam (passthrough ⇒ strip).
	Filters\expectApplied( StorefrontSuppression::FILTER_SUPPRESS_EXCEPTION )
		->andReturnFirstArg();

	$suppression = new class() extends StorefrontSuppression {
		protected function lidmaatskapProductIds(): array {
			return array( 106 );
		}
	};

	expect( $suppression->filterLoopAddToCartLink( '<a>koop</a>', ink_product( 106 ) ) )->toBe( '<a>koop</a>' );
	expect( $suppression->filterLoopAddToCartLink( '<a>koop</a>', ink_product( 999 ) ) )->toBe( '' );
} );

/**
 * AC-1: the inapplicable My-Account tab(s) are pruned; the rest are kept; a non-array is
 * fail-safe empty.
 */
test( 'the account menu prunes downloads and keeps the rest', function (): void {
	$suppression = new StorefrontSuppression();

	$menu = array(
		'dashboard'       => 'Dashboard',
		'orders'          => 'Orders',
		'downloads'       => 'Downloads',
		'payment-methods' => 'Payment methods',
		'edit-account'    => 'Account details',
	);

	$pruned = $suppression->filterAccountMenuItems( $menu );

	expect( $pruned )->not->toHaveKey( 'downloads' );
	expect( $pruned )->toHaveKey( 'orders' );
	expect( $pruned )->toHaveKey( 'payment-methods' );
} );

/**
 * FIX-6 (fail-safe): a non-array account-menu input is returned UNTOUCHED — blanking the
 * whole menu (returning `array()`) would be fail-destructive. WooCommerce's filter must
 * never have the entire account navigation wiped by an unexpected shape.
 */
test( 'the account menu returns a non-array input unchanged (fail-safe, not blanked)', function (): void {
	$suppression = new StorefrontSuppression();

	expect( $suppression->filterAccountMenuItems( 'not-an-array' ) )->toBe( 'not-an-array' );
	expect( $suppression->filterAccountMenuItems( null ) )->toBeNull();
} );

/**
 * The lidmaatskap product set is resolved through the real Story-4.1 MembershipPlans
 * registry (the SAME carve-out distinguisher SubmissionGate uses) — proving one source
 * of truth, not a reinvented mapping. A 6-month term mapped to product 106 surfaces.
 */
test( 'lidmaatskap product ids resolve through the real 4.1 MembershipPlans registry', function (): void {
	Functions\when( 'get_option' )->justReturn( array( 6 => 106 ) );
	Functions\when( 'apply_filters' )->returnArg( 2 );

	// A subclass that exposes the protected resolver for assertion.
	$suppression = new class() extends StorefrontSuppression {
		public function exposeIds(): array {
			return $this->lidmaatskapProductIds();
		}
		public function exposeIsLidmaatskap( int $id ): bool {
			return $this->isLidmaatskapProduct( $id );
		}
	};

	expect( $suppression->exposeIds() )->toContain( 106 );
	expect( $suppression->exposeIsLidmaatskap( 106 ) )->toBeTrue();
	expect( $suppression->exposeIsLidmaatskap( 999 ) )->toBeFalse();
} );

/**
 * Strip PHP comments + docblocks, leaving only executable CODE — so the static scans
 * below assert against logic, not explanatory prose (the 4.1/4.2/3.6 precedent).
 *
 * @param string $file Absolute path to a PHP source file.
 * @return string The concatenated code tokens (no comments).
 */
function ink_ss_code_only( string $file ): string {
	$code = '';

	foreach ( token_get_all( (string) file_get_contents( $file ) ) as $token ) {
		if ( is_array( $token ) ) {
			if ( in_array( $token[0], array( T_COMMENT, T_DOC_COMMENT ), true ) ) {
				continue;
			}
			$code .= $token[1];
			continue;
		}

		$code .= $token;
	}

	return $code;
}

/**
 * AC-4 (THE conflation rule): the suppression CODE carries ZERO reference to
 * `Ink\Tiers` and never writes `ink_writer_tier` — store suppression is unrelated to
 * writer Gradering. Scans the comment-stripped source so the conflation-rule DOC prose
 * is not a false positive.
 */
test( 'the suppression code has no Ink\\Tiers coupling (conflation rule)', function (): void {
	$file = dirname( __DIR__, 3 ) . '/wp-content/plugins/ink-core/src/Entitlement/StorefrontSuppression.php';
	expect( is_file( $file ) )->toBeTrue();

	$code = ink_ss_code_only( $file );

	expect( $code )->not->toContain( 'use Ink\Tiers' );
	expect( $code )->not->toContain( 'Ink\Tiers\\' );
	expect( $code )->not->toContain( 'ink_writer_tier' );
} );

/**
 * AC-3/AC-4 (security + hook-not-edit): the code reads NO raw superglobal (`$_GET` /
 * `$_POST` / `$_REQUEST`) — the add-to-cart id is read via `filter_input()` — and
 * never references a WooCommerce plugin file path (it only uses documented hook /
 * conditional names).
 */
test( 'the suppression code uses no raw superglobal and edits no WooCommerce file', function (): void {
	$file = dirname( __DIR__, 3 ) . '/wp-content/plugins/ink-core/src/Entitlement/StorefrontSuppression.php';
	$code = ink_ss_code_only( $file );

	expect( $code )->not->toContain( '$_GET' );
	expect( $code )->not->toContain( '$_POST' );
	expect( $code )->not->toContain( '$_REQUEST' );
	// No require/include of a plugin file (hook, don't edit).
	expect( $code )->not->toContain( 'plugins/woocommerce' );
} );

// ---------------------------------------------------------------------------------------
// Review fixes 1–6 (Story 4.6 code review, 2026-06-23).
// ---------------------------------------------------------------------------------------

/**
 * A double exposing the FIX-1 cart helper: drive the cart contents + the lidmaatskap set
 * without a live WooCommerce, and expose `cartContainsLidmaatskap()` for assertion.
 */
function ink_cart_suppression( array $items, array $lidmaatskap_ids ): object {
	return new class( $items, $lidmaatskap_ids ) extends StorefrontSuppression {
		public function __construct( private array $items, private array $lidmaatskap_ids ) {}

		protected function cartItems(): array {
			return $this->items;
		}

		protected function lidmaatskapProductIds(): array {
			return $this->lidmaatskap_ids;
		}

		public function exposeCartContainsLidmaatskap(): bool {
			return $this->cartContainsLidmaatskap();
		}
	};
}

/**
 * FIX-1 (BLOCKER): a cart holding a lidmaatskap product is NOT redirected — WooCommerce's
 * "redirect to cart after add" / a "view cart" click lands a mid-purchase lid on the cart
 * page WITHOUT an add-to-cart param; bouncing them would break the membership checkout.
 */
test( 'FIX-1: a cart containing a lidmaatskap product is not redirected', function (): void {
	$suppression = ink_suppression(
		array(
			'store_page'   => true, // the cart page.
			'checkout'     => false,
			'add_to_cart'  => 0,    // no add-to-cart param (the redirect-to-cart case).
			'cart_has_lid' => true, // but the cart holds a lidmaatskap product.
		)
	);

	$suppression->onTemplateRedirect();

	expect( $suppression->redirected )->toBeFalse();
} );

/**
 * FIX-1: a cart with only a non-lidmaatskap item (or empty) on a store page IS redirected
 * (the carve-out is specific to lidmaatskap items).
 */
test( 'FIX-1: a cart without a lidmaatskap product is still redirected', function (): void {
	ink_suppress_seam_default();

	$suppression = ink_suppression(
		array(
			'store_page'   => true,
			'add_to_cart'  => 0,
			'cart_has_lid' => false,
		)
	);

	$suppression->onTemplateRedirect();

	expect( $suppression->redirected )->toBeTrue();
} );

/**
 * FIX-1: `cartContainsLidmaatskap()` matches a lidmaatskap product by `product_id` OR
 * `variation_id`, and is false for a non-lidmaatskap cart / empty set / malformed item.
 */
test( 'FIX-1: cartContainsLidmaatskap matches product_id or variation_id', function (): void {
	// A lidmaatskap simple product in the cart.
	expect(
		ink_cart_suppression( array( array( 'product_id' => 106 ) ), array( 106 ) )
			->exposeCartContainsLidmaatskap()
	)->toBeTrue();

	// A lidmaatskap variation in the cart (variation_id matches).
	expect(
		ink_cart_suppression( array( array( 'product_id' => 50, 'variation_id' => 106 ) ), array( 106 ) )
			->exposeCartContainsLidmaatskap()
	)->toBeTrue();

	// A non-lidmaatskap cart.
	expect(
		ink_cart_suppression( array( array( 'product_id' => 999 ) ), array( 106 ) )
			->exposeCartContainsLidmaatskap()
	)->toBeFalse();

	// An empty lidmaatskap set ⇒ false (the empty-map fail-open is handled elsewhere).
	expect(
		ink_cart_suppression( array( array( 'product_id' => 106 ) ), array() )
			->exposeCartContainsLidmaatskap()
	)->toBeFalse();

	// A malformed cart item ⇒ graceful (no fatal), no match.
	expect(
		ink_cart_suppression( array( 'not-an-item', array() ), array( 106 ) )
			->exposeCartContainsLidmaatskap()
	)->toBeFalse();
} );

/**
 * FIX-1 (graceful WC-absent): `cartItems()` returns an empty array — and
 * `cartContainsLidmaatskap()` false — when `WC()` is unavailable (the default real method
 * with no `WC()` function defined). No fatal.
 */
test( 'FIX-1: cartItems is a graceful no-op when WC() is unavailable', function (): void {
	$suppression = new class() extends StorefrontSuppression {
		public function exposeCartItems(): array {
			return $this->cartItems();
		}
	};

	expect( $suppression->exposeCartItems() )->toBe( array() );
} );

/**
 * FIX-2 (HIGH — fail OPEN): with an EMPTY lidmaatskap map, a non-lidmaatskap product stays
 * purchasable — forcing everything un-purchasable would make the would-be membership
 * product un-purchasable too and break checkout. Suppress nothing when the set is empty.
 */
test( 'FIX-2: empty lidmaatskap map leaves products purchasable (fail open)', function (): void {
	$suppression = new class() extends StorefrontSuppression {
		protected function lidmaatskapProductIds(): array {
			return array(); // no products mapped yet (the real launch state).
		}
	};

	// No suppression filter is consulted on the empty-map fast path.
	expect( $suppression->filterIsPurchasable( true, ink_product( 999 ) ) )->toBeTrue();
	// The loop link is likewise kept.
	expect( $suppression->filterLoopAddToCartLink( '<a>koop</a>', ink_product( 999 ) ) )->toBe( '<a>koop</a>' );
} );

/**
 * FIX-2: a POPULATED map still forces a non-lidmaatskap product un-purchasable (the
 * suppression still works once products are mapped).
 */
test( 'FIX-2: populated map still suppresses a non-lidmaatskap product', function (): void {
	Filters\expectApplied( StorefrontSuppression::FILTER_SUPPRESS_EXCEPTION )
		->andReturnFirstArg();

	$suppression = new class() extends StorefrontSuppression {
		protected function lidmaatskapProductIds(): array {
			return array( 106 );
		}
	};

	expect( $suppression->filterIsPurchasable( true, ink_product( 999 ) ) )->toBeFalse();
} );

/**
 * FIX-2: with an empty map, an add-to-cart of ANY product is treated as a potential
 * membership purchase and is NOT redirected (never bounce a possible purchase).
 */
test( 'FIX-2: empty map exempts any add-to-cart (potential membership flow)', function (): void {
	$suppression = ink_suppression(
		array(
			'store_page'      => true,
			'add_to_cart'     => 999,    // some product.
			'lidmaatskap_ids' => array(), // empty map.
		)
	);

	$suppression->onTemplateRedirect();

	expect( $suppression->redirected )->toBeFalse();
} );

/**
 * A double exposing the FIX-3 endpoint carve-out: pin `is_checkout()` and the WC endpoint
 * conditionals via Brain Monkey-defined functions, and assert `onTemplateRedirect()`.
 */
function ink_endpoint_suppression( bool $order_received, bool $order_pay ): StorefrontSuppression {
	return new class( $order_received, $order_pay ) extends StorefrontSuppression {
		public bool $redirected = false;

		public function __construct( public bool $order_received, public bool $order_pay ) {}

		protected function isSuppressedStorePage(): bool {
			return true; // even if conditionals would say "store", the endpoint carve-out wins.
		}

		// Re-implement isCheckout() using only the endpoint conditionals (is_checkout()
		// false here) so the FIX-3 endpoint exemption is exercised in isolation.
		protected function isCheckout(): bool {
			if ( $this->order_received ) {
				return true;
			}
			if ( $this->order_pay ) {
				return true;
			}
			return false;
		}

		protected function redirectAway(): void {
			$this->redirected = true;
		}
	};
}

/**
 * FIX-3 (HIGH — PayFast-return): an `order-received` (thank-you) endpoint request is NOT
 * redirected. This is the surface PayFast returns to after payment.
 */
test( 'FIX-3: an order-received (PayFast-return) endpoint is not redirected', function (): void {
	$suppression = ink_endpoint_suppression( true, false );

	$suppression->onTemplateRedirect();

	expect( $suppression->redirected )->toBeFalse();
} );

/**
 * FIX-3: an `order-pay` endpoint request is likewise not redirected.
 */
test( 'FIX-3: an order-pay endpoint is not redirected', function (): void {
	$suppression = ink_endpoint_suppression( false, true );

	$suppression->onTemplateRedirect();

	expect( $suppression->redirected )->toBeFalse();
} );

/**
 * FIX-3 (real isCheckout): the explicit endpoint conditionals are honoured by the REAL
 * `isCheckout()` — with `is_checkout()` false but `is_wc_endpoint_url('order-received')`
 * true, the request is treated as the checkout surface.
 */
test( 'FIX-3: real isCheckout exempts a WC checkout endpoint via is_wc_endpoint_url', function (): void {
	Functions\when( 'is_checkout' )->justReturn( false );
	Functions\when( 'is_wc_endpoint_url' )->alias(
		static fn( string $endpoint ): bool => StorefrontSuppression::ENDPOINT_ORDER_RECEIVED === $endpoint
	);

	$suppression = new class() extends StorefrontSuppression {
		public function exposeIsCheckout(): bool {
			return $this->isCheckout();
		}
	};

	expect( $suppression->exposeIsCheckout() )->toBeTrue();
} );

/**
 * A double exposing the FIX-4 parser so the multi/array add-to-cart forms can be asserted
 * directly (without `filter_input`, which the unit suite cannot populate).
 */
function ink_parse_suppression(): object {
	return new class() extends StorefrontSuppression {
		public function exposeParse( mixed $raw ): array {
			return $this->parseProductIds( $raw );
		}
	};
}

/**
 * FIX-4 (MED — multi/array add-to-cart): the parser returns the SET of ids for the single,
 * comma-multi, and array forms; garbage yields no ids (graceful).
 */
test( 'FIX-4: parseProductIds handles single, comma-multi, array, and garbage forms', function (): void {
	$p = ink_parse_suppression();

	expect( $p->exposeParse( '12' ) )->toBe( array( 12 ) );
	expect( $p->exposeParse( '12,34' ) )->toBe( array( 12, 34 ) );        // comma-multi.
	expect( $p->exposeParse( array( '12', '34' ) ) )->toBe( array( 12, 34 ) ); // array form.
	expect( $p->exposeParse( 'abc' ) )->toBe( array() );                  // garbage ⇒ no ids.
	expect( $p->exposeParse( '12,abc,34' ) )->toBe( array( 12, 34 ) );    // partial garbage.
	expect( $p->exposeParse( null ) )->toBe( array() );                   // absent.
	expect( $p->exposeParse( '0' ) )->toBe( array() );                    // non-positive dropped.
} );

/**
 * FIX-4 (carve-out): a comma-multi add-to-cart that CONTAINS a lidmaatskap id is carved
 * out (the purchase flow inside a multi-add still passes through).
 */
test( 'FIX-4: a multi-add containing a lidmaatskap id is not redirected', function (): void {
	$suppression = ink_suppression(
		array(
			'store_page'      => true,
			'add_to_cart'     => array( 12, 106 ), // 106 is the lidmaatskap product.
			'lidmaatskap_ids' => array( 106 ),
		)
	);

	$suppression->onTemplateRedirect();

	expect( $suppression->redirected )->toBeFalse();
} );

/**
 * FIX-5 (MED — front-end guard): in an ADMIN context the purchasability filter returns the
 * value unchanged — suppression must not leak into admin order creation / REST.
 */
test( 'FIX-5: in an admin context the purchasability filter returns the value unchanged', function (): void {
	$suppression = new class() extends StorefrontSuppression {
		protected function isFrontEndStorefront(): bool {
			return false; // admin / REST context.
		}
		protected function lidmaatskapProductIds(): array {
			return array( 106 ); // populated — proving the front-end guard, not the empty-map path.
		}
	};

	// A non-lidmaatskap product would be forced un-purchasable on the front end; off the
	// front end the incoming value is preserved.
	expect( $suppression->filterIsPurchasable( true, ink_product( 999 ) ) )->toBeTrue();
	expect( $suppression->filterLoopAddToCartLink( '<a>koop</a>', ink_product( 999 ) ) )->toBe( '<a>koop</a>' );
} );

/**
 * FIX-6 (product taxonomy archives): a product CATEGORY archive is a suppressed store page
 * (matching the AC-1 / docblock claim).
 */
test( 'FIX-6: a product category archive is a suppressed store page', function (): void {
	Functions\when( 'is_shop' )->justReturn( false );
	Functions\when( 'is_product' )->justReturn( false );
	Functions\when( 'is_post_type_archive' )->justReturn( false );
	Functions\when( 'is_cart' )->justReturn( false );
	Functions\when( 'is_product_category' )->justReturn( true );
	Functions\when( 'is_product_tag' )->justReturn( false );

	$suppression = new class() extends StorefrontSuppression {
		public function exposeIsSuppressed(): bool {
			return $this->isSuppressedStorePage();
		}
	};

	expect( $suppression->exposeIsSuppressed() )->toBeTrue();
} );

/**
 * FIX-6 (search): `filterSearchQuery()` removes the `product` post type from a front-end
 * main search query, and leaves the rest in place.
 */
test( 'FIX-6: the front-end search query excludes the product post type', function (): void {
	Functions\when( 'is_admin' )->justReturn( false );

	$query = new class() {
		public mixed $post_type = array( 'post', 'product', 'gedig' );
		public function is_main_query(): bool {
			return true;
		}
		public function is_search(): bool {
			return true;
		}
		public function get( string $key ): mixed {
			return 'post_type' === $key ? $this->post_type : null;
		}
		public function set( string $key, mixed $value ): void {
			if ( 'post_type' === $key ) {
				$this->post_type = $value;
			}
		}
	};

	( new StorefrontSuppression() )->filterSearchQuery( $query );

	expect( $query->post_type )->toBe( array( 'post', 'gedig' ) );
	expect( $query->post_type )->not->toContain( 'product' );
} );

/**
 * FIX-6 (search guard): a NON-search query (or admin / secondary query) is untouched.
 */
test( 'FIX-6: a non-search query is left untouched', function (): void {
	Functions\when( 'is_admin' )->justReturn( false );

	$query = new class() {
		public mixed $post_type = array( 'post', 'product' );
		public function is_main_query(): bool {
			return true;
		}
		public function is_search(): bool {
			return false; // not a search ⇒ no change.
		}
		public function get( string $key ): mixed {
			return $this->post_type;
		}
		public function set( string $key, mixed $value ): void {
			$this->post_type = $value;
		}
	};

	( new StorefrontSuppression() )->filterSearchQuery( $query );

	expect( $query->post_type )->toBe( array( 'post', 'product' ) ); // unchanged.
} );

/**
 * FIX-6 (search): the `pre_get_posts` hook is wired by `register()` when WooCommerce is
 * active.
 */
test( 'FIX-6: register() wires the pre_get_posts search filter when WC is active', function (): void {
	$suppression = new InkStorefrontSuppressionWcActive();
	$suppression->register();

	expect( has_action( StorefrontSuppression::HOOK_PRE_GET_POSTS, array( $suppression, 'filterSearchQuery' ) ) )->not->toBeFalse();
} );

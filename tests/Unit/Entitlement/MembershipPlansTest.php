<?php
/**
 * Unit tests for the lidmaatskap plan registry (Story 4.1, FR-4).
 *
 * Target: {@see \Ink\Entitlement\MembershipPlans} — the config-driven, single-
 * source registry of the three launch lidmaatskap plan slots. Each slot is a
 * fixed {@see \Ink\Entitlement\LidmaatskapTerm} mapped to a WooCommerce product;
 * the PRICE is resolved from WooCommerce at runtime (admin-editable, NEVER a PHP
 * literal), and a redakteur changes price/term/plan in WooCommerce admin with no
 * `ink-core` edit. No auto-renew, no discount/savings framing at launch.
 *
 * Brain Monkey, no WordPress/DB and no WooCommerce loaded — `wc_get_product` and
 * `function_exists` are mocked. The real `Ink\Entitlement\LidmaatskapTerm` enum
 * is autoloaded so the asserted term set is the genuine single source.
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Entitlement;

use Ink\Entitlement\LidmaatskapTerm;
use Ink\Entitlement\MembershipPlans;
use Ink\Entitlement\MembershipPlan;
use Brain\Monkey;
use Brain\Monkey\Functions;

beforeEach( function (): void {
	Monkey\setUp();

	Functions\when( '__' )->returnArg( 1 );
} );

afterEach( function (): void {
	Monkey\tearDown();
} );

/**
 * The product-ID config option key is the exact `ink_`-prefixed single source.
 */
test( 'the config option key is the exact prefixed single source', function (): void {
	expect( MembershipPlans::OPTION_PRODUCTS )->toBe( 'ink_membership_plan_products' );
} );

/**
 * AC-1/AC-5: the registry exposes exactly three plan slots, one per fixed term
 * (1/6/12 months) — the "three fixed-term lidmaatskap products" guarantee.
 */
test( 'plans exposes exactly three slots, one per fixed term', function (): void {
	Functions\when( 'get_option' )->justReturn( array() );

	$plans = ( new MembershipPlans() )->plans();

	expect( $plans )->toHaveCount( 3 );

	$terms = array_map( static fn ( MembershipPlan $p ): int => $p->term->value, $plans );
	expect( $terms )->toBe( array( 1, 6, 12 ) );
} );

/**
 * AC-1: the set of terms the registry offers is exactly the closed enum set.
 */
test( 'terms returns exactly the fixed LidmaatskapTerm set', function (): void {
	expect( MembershipPlans::terms() )->toBe( LidmaatskapTerm::cases() );
} );

/**
 * AC-2 (the core anti-hardcode guarantee): the price is resolved from the
 * WooCommerce product at RUNTIME — never a PHP literal. A mocked product returns
 * an admin-set price; the registry surfaces exactly that value.
 */
test( 'priceFor resolves the price from the WooCommerce product at runtime', function (): void {
	$product_map = array(
		1  => 101,
		6  => 106,
		12 => 112,
	);

	Functions\when( 'get_option' )->justReturn( $product_map );

	// A WooCommerce-admin-set price (NOT known to ink-core as a literal). We map
	// each product id to whatever the store admin configured.
	$store_prices = array(
		101 => '60',
		106 => '300',
		112 => '600',
	);

	Functions\when( 'wc_get_product' )->alias(
		function ( int $id ) use ( &$store_prices ) {
			if ( ! isset( $store_prices[ $id ] ) ) {
				return false;
			}
			return new class( $store_prices[ $id ] ) {
				public function __construct( private string $price ) {}
				public function get_price(): string {
					return $this->price;
				}
			};
		}
	);

	$plans = new MembershipPlans();

	// The prices come from the mocked store, proving runtime resolution.
	expect( $plans->priceFor( LidmaatskapTerm::OneMonth ) )->toBe( '60' );
	expect( $plans->priceFor( LidmaatskapTerm::SixMonths ) )->toBe( '300' );
	expect( $plans->priceFor( LidmaatskapTerm::TwelveMonths ) )->toBe( '600' );

	// Change the admin price → the registry follows, with no ink-core code change.
	$store_prices[101] = '75';
	expect( $plans->priceFor( LidmaatskapTerm::OneMonth ) )->toBe( '75' );
} );

/**
 * AC-2: when WooCommerce is absent, price resolution degrades gracefully to null
 * — ink-core holds NO fallback price literal. The WC-availability seam is forced
 * OFF via a test subclass (Brain Monkey-defined function symbols persist within a
 * process, so an inline `function_exists` mock can't deterministically simulate
 * "absent"; the protected seam makes the branch testable cleanly).
 */
test( 'priceFor returns null when WooCommerce is unavailable', function (): void {
	Functions\when( 'get_option' )->justReturn( array( 1 => 101 ) );

	$plans = new class() extends MembershipPlans {
		protected function isWooCommerceAvailable(): bool {
			return false; // simulate WooCommerce inactive.
		}
	};

	expect( $plans->priceFor( LidmaatskapTerm::OneMonth ) )->toBeNull();
} );

/**
 * AC-2: when a term is not mapped to a product (or the product is missing),
 * price resolution returns null — never an invented amount. WooCommerce IS
 * available here (Brain Monkey defines `wc_get_product`, so the real
 * `function_exists` guard passes) but the product lookup yields nothing.
 */
test( 'priceFor returns null for an unmapped or missing product', function (): void {
	Functions\when( 'get_option' )->justReturn( array() ); // no mapping.
	Functions\when( 'wc_get_product' )->justReturn( false );

	$plans = new MembershipPlans();

	expect( $plans->priceFor( LidmaatskapTerm::SixMonths ) )->toBeNull();
} );

/**
 * AC-2: the product-ID config map is fail-safe empty — an unset or garbled
 * (non-array) option yields no mapping rather than a fatal.
 */
test( 'the product map is fail-safe empty for unset or garbled config', function (): void {
	Functions\when( 'get_option' )->justReturn( false ); // unset.
	expect( ( new MembershipPlans() )->productIdFor( LidmaatskapTerm::OneMonth ) )->toBeNull();

	Functions\when( 'get_option' )->justReturn( 'corrupt' ); // garbled scalar.
	expect( ( new MembershipPlans() )->productIdFor( LidmaatskapTerm::OneMonth ) )->toBeNull();

	Functions\when( 'get_option' )->justReturn( array( 6 => 106 ) ); // valid row.
	expect( ( new MembershipPlans() )->productIdFor( LidmaatskapTerm::SixMonths ) )->toBe( 106 );
	expect( ( new MembershipPlans() )->productIdFor( LidmaatskapTerm::OneMonth ) )->toBeNull();
} );

/**
 * FIX-A (review): a price that is not a positive number is "unknown" → null. A
 * misconfigured / free product (`''`, `'0'`, `0`, a negative, or a non-numeric
 * value) must NEVER surface as a valid paid lidmaatskap price — same fail-safe
 * discipline `productIdFor()` applies to ids. A valid positive price is returned.
 */
test( 'priceFor treats a non-positive or non-numeric price as unknown (null)', function (): void {
	Functions\when( 'get_option' )->justReturn( array( 1 => 101 ) );

	$current_price = null; // closure-mutable so one test exercises every case.

	Functions\when( 'wc_get_product' )->alias(
		function ( int $id ) use ( &$current_price ) {
			return new class( $current_price ) {
				public function __construct( private mixed $price ) {}
				public function get_price(): mixed {
					return $this->price;
				}
			};
		}
	);

	$plans = new MembershipPlans();

	// Invalid → null (fail-safe).
	$current_price = 'abc';
	expect( $plans->priceFor( LidmaatskapTerm::OneMonth ) )->toBeNull();

	$current_price = '0';
	expect( $plans->priceFor( LidmaatskapTerm::OneMonth ) )->toBeNull();

	$current_price = 0;
	expect( $plans->priceFor( LidmaatskapTerm::OneMonth ) )->toBeNull();

	$current_price = '-50';
	expect( $plans->priceFor( LidmaatskapTerm::OneMonth ) )->toBeNull();

	$current_price = '';
	expect( $plans->priceFor( LidmaatskapTerm::OneMonth ) )->toBeNull();

	// Valid positive → returned verbatim (as a string).
	$current_price = '60';
	expect( $plans->priceFor( LidmaatskapTerm::OneMonth ) )->toBe( '60' );

	$current_price = 60.0;
	expect( $plans->priceFor( LidmaatskapTerm::OneMonth ) )->toBe( '60' );
} );

/**
 * FIX-B (review): a retired plan (a non-`publish` WooCommerce product — trashed /
 * draft / private — which `wc_get_product()` still returns as a truthy object)
 * is treated as UNAVAILABLE: priceFor → null AND isAvailable → false. AC-2
 * explicitly lets a redakteur retire a plan. A published, valid-priced product
 * IS available.
 */
test( 'a retired (non-publish) product is unavailable; a published valid one is available', function (): void {
	Functions\when( 'get_option' )->justReturn(
		array(
			1 => 101,
			6 => 106,
		)
	);

	Functions\when( 'wc_get_product' )->alias(
		function ( int $id ) {
			// 101 = retired (draft); 106 = published, valid price.
			$status = 101 === $id ? 'draft' : 'publish';
			return new class( $status ) {
				public function __construct( private string $status ) {}
				public function get_status(): string {
					return $this->status;
				}
				public function get_price(): string {
					return '300';
				}
			};
		}
	);

	$plans = new MembershipPlans();

	// Retired → not sellable, no price (even though wc_get_product returns an object).
	expect( $plans->priceFor( LidmaatskapTerm::OneMonth ) )->toBeNull();
	expect( $plans->isAvailable( LidmaatskapTerm::OneMonth ) )->toBeFalse();

	// Published + valid price → available.
	expect( $plans->priceFor( LidmaatskapTerm::SixMonths ) )->toBe( '300' );
	expect( $plans->isAvailable( LidmaatskapTerm::SixMonths ) )->toBeTrue();
} );

/**
 * FIX-B: when WooCommerce is absent, the slot is not available (no inference from
 * a null price needed — the signal is explicit).
 */
test( 'isAvailable is false when WooCommerce is unavailable', function (): void {
	Functions\when( 'get_option' )->justReturn( array( 1 => 101 ) );

	$plans = new class() extends MembershipPlans {
		protected function isWooCommerceAvailable(): bool {
			return false;
		}
	};

	expect( $plans->isAvailable( LidmaatskapTerm::OneMonth ) )->toBeFalse();
} );

/**
 * FIX-C (review): the product-ID map keys are normalised to int before lookup, so
 * a migration/admin write that stored STRING keys (`'1'`, `'06'`, `' 12'`) — which
 * do not all PHP-normalise to the int the lookup uses — still resolves.
 */
test( 'productIdFor resolves a string-keyed option map (key normalisation)', function (): void {
	Functions\when( 'get_option' )->justReturn(
		array(
			'1'   => 101,
			'06'  => 106,
			' 12' => 112,
		)
	);

	$plans = new MembershipPlans();

	expect( $plans->productIdFor( LidmaatskapTerm::OneMonth ) )->toBe( 101 );
	expect( $plans->productIdFor( LidmaatskapTerm::SixMonths ) )->toBe( 106 );
	expect( $plans->productIdFor( LidmaatskapTerm::TwelveMonths ) )->toBe( 112 );
} );

/**
 * FIX-D (review): the `ink_membership_plan_products` FILTER layers over the option
 * — site config / a later admin UI can SUPPLY or OVERRIDE the mapping without an
 * `ink-core` edit (closing the "no producer" gap). The filter wins over the
 * option-derived map and can ADD rows the option lacks.
 */
test( 'the product filter overrides and augments the option-derived map', function (): void {
	// Option supplies only the 1-month row, with the to-be-overridden id 999.
	Functions\when( 'get_option' )->justReturn( array( 1 => 999 ) );

	// A site-config filter overrides 1-month and ADDS the 6/12-month rows.
	Functions\when( 'apply_filters' )->alias(
		function ( string $hook, $value ) {
			expect( $hook )->toBe( MembershipPlans::FILTER_PRODUCTS );
			// $value is the option-derived (normalised) map; the filter rewrites it.
			return array(
				1  => 101,
				6  => 106,
				12 => 112,
			);
		}
	);

	$plans = new MembershipPlans();

	expect( $plans->productIdFor( LidmaatskapTerm::OneMonth ) )->toBe( 101 ); // overridden.
	expect( $plans->productIdFor( LidmaatskapTerm::SixMonths ) )->toBe( 106 ); // augmented.
	expect( $plans->productIdFor( LidmaatskapTerm::TwelveMonths ) )->toBe( 112 ); // augmented.
} );

/**
 * FIX-D: the filter name is the exact `ink_`-prefixed single source.
 */
test( 'the product filter name is the exact prefixed single source', function (): void {
	expect( MembershipPlans::FILTER_PRODUCTS )->toBe( 'ink_membership_plan_products' );
} );

/**
 * AC-1/AC-3: no auto-renew and no discount/savings framing at launch. Every
 * plan slot is non-recurring and the value object carries NO discount field.
 */
test( 'every plan is non-recurring with no discount field at launch', function (): void {
	Functions\when( 'get_option' )->justReturn( array() );

	$plans = ( new MembershipPlans() )->plans();

	foreach ( $plans as $plan ) {
		expect( $plan->isRecurring() )->toBeFalse();
	}

	// The value object MUST NOT expose any discount/savings surface (AC-3).
	expect( method_exists( MembershipPlan::class, 'discount' ) )->toBeFalse();
	expect( method_exists( MembershipPlan::class, 'savings' ) )->toBeFalse();
	expect( property_exists( MembershipPlan::class, 'discount' ) )->toBeFalse();
	expect( property_exists( MembershipPlan::class, 'savings' ) )->toBeFalse();
} );

/**
 * Strip PHP comments + docblocks from a source file, leaving only executable
 * CODE — so the static scans below assert against logic, not explanatory prose
 * (a doc-comment may legitimately mention "R60" or the conflation-rule
 * "`Ink\Tiers`" while the code itself does neither — the 3.6-review precedent).
 *
 * @param string $file Absolute path to a PHP source file.
 * @return string The concatenated code tokens (no comments).
 */
function ink_code_only( string $file ): string {
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
 * AC-3/AC-2: NO price literal (60/300/600) is baked into the registry/value-object
 * CODE — the price lives only in WooCommerce, resolved at runtime. Static scan
 * over the comment-stripped source (logic only).
 */
test( 'no price literal is hardcoded in the entitlement plan code', function (): void {
	$dir   = dirname( __DIR__, 3 ) . '/wp-content/plugins/ink-core/src/Entitlement';
	$files = array(
		$dir . '/MembershipPlans.php',
		$dir . '/MembershipPlan.php',
		$dir . '/LidmaatskapTerm.php',
	);

	foreach ( $files as $file ) {
		expect( is_file( $file ) )->toBeTrue();
		// The launch prices must never appear as literals in logic.
		expect( ink_code_only( $file ) )->not->toMatch( '/\b(60|300|600)\b/' );
	}
} );

/**
 * AC-5 (THE conflation rule): the Entitlement plan CODE carries ZERO reference
 * to `Ink\Tiers` — lidmaatskap entitlement is independent of writer Gradering.
 * Scans the comment-stripped source so the conflation-rule DOC prose (which must
 * name `Ink\Tiers` to state the rule) is not a false positive.
 */
test( 'the entitlement plan code has no Ink\\Tiers coupling (conflation rule)', function (): void {
	$dir   = dirname( __DIR__, 3 ) . '/wp-content/plugins/ink-core/src/Entitlement';
	$files = glob( $dir . '/*.php' );

	expect( $files )->not->toBeFalse();

	foreach ( (array) $files as $file ) {
		$code = ink_code_only( $file );
		expect( $code )->not->toContain( 'use Ink\Tiers' );
		expect( $code )->not->toContain( 'Ink\Tiers\\' );
	}
} );

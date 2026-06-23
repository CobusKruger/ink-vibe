<?php
/**
 * Unit tests for the lidmaatskap plan presentation read-model (Story 4.4, FR-7).
 *
 * Target: {@see \Ink\Entitlement\PlanPresenter} — the thin read-model that shapes
 * the three plan slots into presentation rows for the Lidmaatskap page, so a
 * static FSE block pattern can surface dynamic plan data WITHOUT any business
 * logic in the theme. It introduces no rule: it delegates wholly to the
 * {@see \Ink\Entitlement\Api} facade (the 4.1 registry + the 4.2 purchase
 * hand-off). These tests assert the ROW SHAPE / count / ordering / label
 * resolution and the WooCommerce-runtime price + purchase-URL pass-through
 * (mocked) — the null-degrade branches are already covered by MembershipPlansTest.
 *
 * Brain Monkey, no WordPress/DB. WooCommerce is simulated via Brain Monkey
 * function aliases (`wc_get_product` / `wc_get_checkout_url` / `add_query_arg`);
 * the real `Ink\I18n\Terms` registry is autoloaded so the asserted term labels
 * are the genuine single-source values (no literals duplicated in the test).
 *
 * NOTE on the static facade cache: {@see Api} caches its registry / purchase /
 * presenter collaborators in private statics. Each test rebuilds the function
 * mocks in `beforeEach`, and the collaborators are stateless (they read
 * `get_option` / `wc_get_product` fresh per call), so a cached instance still
 * resolves against the current test's mocks.
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Entitlement;

use Ink\Entitlement\Api;
use Ink\Entitlement\LidmaatskapTerm;
use Ink\Entitlement\PlanPresenter;
use Ink\I18n\Terms;
use Brain\Monkey;
use Brain\Monkey\Functions;

beforeEach( function (): void {
	Monkey\setUp();

	// `__()` is identity in unit context (no .mo) so Afrikaans source renders.
	Functions\when( '__' )->returnArg( 1 );

	// The product mapping (4.1 config): all three terms → a WooCommerce product id.
	Functions\when( 'get_option' )->justReturn(
		array(
			1  => 101,
			6  => 106,
			12 => 112,
		)
	);

	// No filter override layered over the option map.
	Functions\when( 'apply_filters' )->returnArg( 2 );

	// WooCommerce present: each product id resolves to a published product whose
	// price is its id as a decimal string (deterministic, NOT a real R-value — the
	// read-model holds no price literal; it passes through whatever WC returns).
	Functions\when( 'wc_get_product' )->alias(
		static function ( int $id ) {
			return new class( $id ) {
				public function __construct( private int $id ) {}
				public function get_status(): string {
					return 'publish';
				}
				public function get_price(): string {
					return (string) $this->id;
				}
			};
		}
	);

	// WooCommerce checkout (4.2 purchase hand-off).
	Functions\when( 'wc_get_checkout_url' )->justReturn( 'https://ink.test/kassa' );
	Functions\when( 'add_query_arg' )->alias(
		static fn ( string $key, mixed $value, string $url ): string => $url . '?' . $key . '=' . (string) $value
	);
} );

afterEach( function (): void {
	Monkey\tearDown();
} );

/**
 * FIX-1 helper: resolve the `price_display` the read-model produces for a given raw
 * WooCommerce price string. Re-points `wc_get_product` so every term resolves to a
 * published product at the supplied price, then returns the first row's display
 * value. The WC price-format helpers (`wc_get_price_decimals` etc.) are intentionally
 * left undefined so the deterministic, locale-free fallback (`R` + 2-decimal
 * `number_format`) is exercised.
 */
function ink_test_price_display_for( string $raw_price ): ?string {
	Functions\when( 'wc_get_product' )->alias(
		static function () use ( $raw_price ) {
			return new class( $raw_price ) {
				public function __construct( private string $price ) {}
				public function get_status(): string {
					return 'publish';
				}
				public function get_price(): string {
					return $this->price;
				}
			};
		}
	);

	$rows = ( new PlanPresenter() )->rows();

	return $rows[0]['price_display'];
}

/**
 * AC-1/AC-3/AC-7: exactly three rows, one per fixed term, in ascending order — the
 * read-model never invents a fourth slot and never reorders the closed term set.
 */
test( 'rows returns exactly three plan rows, one per fixed term, ascending', function (): void {
	$rows = ( new PlanPresenter() )->rows();

	expect( $rows )->toHaveCount( 3 );

	$months = array_map( static fn ( array $row ): int => $row['months'], $rows );
	expect( $months )->toBe( array( 1, 6, 12 ) );
} );

/**
 * AC-7: every row carries exactly the presentation contract keys (the flat shape
 * the theme renders) — no leaked internals, no discount/savings field (AC-5).
 */
test( 'each row exposes the presentation contract keys and no savings field', function (): void {
	$rows = ( new PlanPresenter() )->rows();

	foreach ( $rows as $row ) {
		expect( array_keys( $row ) )
			->toBe( array( 'months', 'term_label', 'price', 'price_display', 'is_available', 'purchase_url' ) );

		// No vanity discount/savings framing surface at launch (AC-3 / AC-5).
		expect( $row )->not->toHaveKey( 'discount' );
		expect( $row )->not->toHaveKey( 'savings' );
		expect( $row )->not->toHaveKey( 'percent_off' );
	}
} );

/**
 * AC-4: the term label resolves through the terminology registry — never an inline
 * literal. The asserted strings are the genuine single-source registry values.
 */
test( 'row term labels resolve via the terminology registry', function (): void {
	$rows = ( new PlanPresenter() )->rows();

	expect( $rows[0]['term_label'] )->toBe( Terms::label( 'term_1_month' ) );
	expect( $rows[1]['term_label'] )->toBe( Terms::label( 'term_6_months' ) );
	expect( $rows[2]['term_label'] )->toBe( Terms::label( 'term_12_months' ) );

	// Sanity: Afrikaans month words, no English leakage.
	expect( $rows[0]['term_label'] )->toBe( '1 maand' );
	expect( $rows[2]['term_label'] )->toBe( '12 maande' );
} );

/**
 * AC-3: the price is passed through from the WooCommerce runtime resolution
 * (`Api::priceFor()`), not invented — the read-model holds no price literal.
 */
test( 'row price is the WooCommerce runtime price passed through from the Api', function (): void {
	$rows = ( new PlanPresenter() )->rows();

	// The mocked WC product returns its id as the price string — so the row price
	// equals exactly what Api::priceFor() resolves (pure pass-through, no literal).
	expect( $rows[0]['price'] )->toBe( Api::priceFor( LidmaatskapTerm::OneMonth ) );
	expect( $rows[1]['price'] )->toBe( Api::priceFor( LidmaatskapTerm::SixMonths ) );
	expect( $rows[2]['price'] )->toBe( Api::priceFor( LidmaatskapTerm::TwelveMonths ) );

	expect( $rows[0]['price'] )->toBe( '101' );
	expect( $rows[1]['price'] )->toBe( '106' );
	expect( $rows[2]['price'] )->toBe( '112' );
} );

/**
 * AC-3: sellability + purchase URL are passed through from the Api seam (the 4.1
 * availability signal + the 4.2 WC/PayFast checkout hand-off).
 */
test( 'row availability and purchase url are passed through from the Api seam', function (): void {
	$rows = ( new PlanPresenter() )->rows();

	foreach ( $rows as $i => $row ) {
		$term = LidmaatskapTerm::cases()[ $i ];

		expect( $row['is_available'] )->toBe( Api::isAvailable( $term ) );
		expect( $row['is_available'] )->toBeTrue();

		expect( $row['purchase_url'] )->toBe( Api::purchaseUrl( $term ) );
		expect( $row['purchase_url'] )->toContain( 'https://ink.test/kassa' );
		expect( $row['purchase_url'] )->toContain( 'add-to-cart=' );
	}
} );

/**
 * FIX-1 (HIGH): each row carries a consistent ZAR `price_display` string alongside
 * the raw `price` — the presentation-shaping that USED to live (inconsistently) in
 * the theme (`'R' . $price`) now lives in the read-model. The raw `price` field is
 * preserved unchanged (pure WC pass-through).
 */
test( 'each row exposes a price_display key alongside the raw price', function (): void {
	$rows = ( new PlanPresenter() )->rows();

	foreach ( $rows as $row ) {
		expect( array_keys( $row ) )
			->toBe( array( 'months', 'term_label', 'price', 'price_display', 'is_available', 'purchase_url' ) );
	}

	// Raw price stays the untouched WC pass-through (the id-as-price mock).
	expect( $rows[0]['price'] )->toBe( '101' );
} );

/**
 * FIX-1 (HIGH): the consistent ZAR format is `R<thousands>.<2 decimals>` — an
 * integer-ish raw price renders with 2 decimals, never bare. `60` -> `R60.00`.
 */
test( 'price_display formats an integer price to a consistent R + 2-decimal string', function (): void {
	$display = ink_test_price_display_for( '60' );

	expect( $display )->toBe( 'R60.00' );
} );

/**
 * FIX-1 (HIGH): an already-2-decimal raw price renders identically — `300.00` ->
 * `R300.00` (no double-formatting, no stray zeros).
 */
test( 'price_display keeps a 2-decimal price consistent', function (): void {
	$display = ink_test_price_display_for( '300.00' );

	expect( $display )->toBe( 'R300.00' );
} );

/**
 * FIX-1 (HIGH): a 1-decimal raw price is normalised to 2 decimals — `1200.5` ->
 * `R1200.50` (the inconsistent `R1200.5` the raw echo produced is gone). No
 * thousands separator is injected (deterministic, locale-free default).
 */
test( 'price_display normalises a ragged-decimal price to 2 decimals', function (): void {
	$display = ink_test_price_display_for( '1200.5' );

	expect( $display )->toBe( 'R1200.50' );
} );

/**
 * FIX-1 (HIGH): a null / unavailable price yields a null `price_display` — the
 * pattern keeps its "Prys binnekort beskikbaar" degrade (no `R` prefix on nothing).
 */
test( 'price_display is null when the price is null (graceful degrade)', function (): void {
	// WooCommerce absent: priceFor() resolves null for every term.
	Functions\when( 'wc_get_product' )->justReturn( null );

	$rows = ( new PlanPresenter() )->rows();

	foreach ( $rows as $row ) {
		expect( $row['price'] )->toBeNull();
		expect( $row['price_display'] )->toBeNull();
	}
} );

/**
 * AC-7 (conflation rule): the read-model file references no `Ink\Tiers` — verified
 * by a comment-stripped source scan (the 4.1 discipline). Lidmaatskap ⟂ Gradering.
 */
test( 'the PlanPresenter source references no Ink\\Tiers (conflation-clean)', function (): void {
	$source = (string) file_get_contents(
		dirname( __DIR__, 3 ) . '/wp-content/plugins/ink-core/src/Entitlement/PlanPresenter.php'
	);

	// Strip comments so doc-prose mentions never produce a false positive.
	$stripped = preg_replace( '#/\*.*?\*/#s', '', $source );
	$stripped = preg_replace( '#//.*#', '', (string) $stripped );

	expect( $stripped )->not->toContain( 'Ink\\Tiers' );
	expect( $stripped )->not->toContain( 'use Ink\Tiers' );
} );

/**
 * AC-3: the read-model holds NO price literal — the price comes only from the
 * WooCommerce runtime (comment-stripped scan, mirroring the 4.1 MembershipPlans
 * discipline). Guards against a regression that bakes R60/R300/R600 into the theme
 * seam.
 */
test( 'the PlanPresenter source holds no price literal', function (): void {
	$source = (string) file_get_contents(
		dirname( __DIR__, 3 ) . '/wp-content/plugins/ink-core/src/Entitlement/PlanPresenter.php'
	);

	$stripped = preg_replace( '#/\*.*?\*/#s', '', $source );
	$stripped = preg_replace( '#//.*#', '', (string) $stripped );

	expect( $stripped )->not->toMatch( '/\b(?:60|300|600)\b/' );
} );

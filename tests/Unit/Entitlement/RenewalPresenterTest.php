<?php
/**
 * Unit tests for the lidmaatskap renewal surface (Story 4.5, FR-8).
 *
 * Target: {@see \Ink\Entitlement\Api::renewalRows()} — the single cross-module surface
 * the theme's `ink_foundation_renewal_plans()` bridge consumes to shape the three
 * fixed-term plan slots into RENEWAL rows for the My Profiel Lidmaatskap-tab renewal
 * section, so a static FSE block pattern can surface dynamic renewal data WITHOUT any
 * business logic in the theme.
 *
 * At launch the renewal rows are IDENTICAL to the 4.4 plan rows: `renewalRows()` is a
 * direct delegate to the shared {@see \Ink\Entitlement\PlanPresenter} (the 4.1 registry
 * + the 4.2 purchase hand-off). No separate renewal read-model exists — a renewal-only
 * class would be a verbatim pass-through; the named surface is kept distinct only so a
 * future story can let renewal rows diverge without re-threading the theme bridge. These
 * tests therefore assert on the renewal-SPECIFIC contract (rows === plan rows; the
 * closed 1/6/12 set; the renew CTA == the 4.2 purchase URL; no savings field); the
 * PlanPresenter-internal guardrails (no `Ink\Tiers` source ref, no price literal) live
 * in {@see PlanPresenterTest} and are not duplicated here.
 *
 * "Renew" at launch IS the manual fixed-term purchase flow: each row's `purchase_url`
 * is the 4.2 WC/PayFast checkout URL for that term — there is NO auto-renew / recurring
 * mechanism (Stories 4.9–4.11 are post-launch). NO discount/savings/percent_off field is
 * exposed (AC-3, the standing Epic-4 launch ban on vanity savings framing).
 *
 * Brain Monkey, no WordPress/DB. WooCommerce is simulated via Brain Monkey function
 * aliases; the real `Ink\I18n\Terms` registry is autoloaded so the asserted term labels
 * are the genuine single-source values (no literals duplicated in the test).
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Entitlement;

use Ink\Entitlement\Api;
use Ink\Entitlement\LidmaatskapTerm;
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

	// WooCommerce checkout (4.2 purchase hand-off) — the renew CTA target.
	Functions\when( 'wc_get_checkout_url' )->justReturn( 'https://ink.test/kassa' );
	Functions\when( 'add_query_arg' )->alias(
		static fn ( string $key, mixed $value, string $url ): string => $url . '?' . $key . '=' . (string) $value
	);
} );

afterEach( function (): void {
	Monkey\tearDown();
} );

/**
 * AC-1/AC-7: exactly three renewal rows, one per fixed term, in ascending order — the
 * renewal surface never invents a fourth slot and never reorders the closed 1/6/12 set.
 */
test( 'renewalRows returns exactly three renewal rows, one per fixed term, ascending', function (): void {
	$rows = Api::renewalRows();

	expect( $rows )->toHaveCount( 3 );

	$months = array_map( static fn ( array $row ): int => $row['months'], $rows );
	expect( $months )->toBe( array( 1, 6, 12 ) );
} );

/**
 * AC-1: the renewal rows REUSE the 4.4 plan-row shaping — at launch they ARE the plan
 * rows (a renewal-only read-model would be a verbatim pass-through). This is the contract
 * that justifies collapsing the indirection: `renewalRows()` === `planRows()`.
 */
test( 'renewal rows are identical to the 4.4 plan rows (reuse, not duplication)', function (): void {
	expect( Api::renewalRows() )->toBe( Api::planRows() );
} );

/**
 * AC-3/AC-7: every renewal row carries exactly the presentation contract keys (the flat
 * shape the renewal section renders) — and NO discount/savings field (the standing ban).
 */
test( 'each renewal row exposes the presentation contract keys and no savings field', function (): void {
	$rows = Api::renewalRows();

	foreach ( $rows as $row ) {
		expect( array_keys( $row ) )
			->toBe( array( 'months', 'term_label', 'price', 'price_display', 'is_available', 'purchase_url' ) );

		// No vanity discount/savings framing surface at launch (AC-3).
		expect( $row )->not->toHaveKey( 'discount' );
		expect( $row )->not->toHaveKey( 'savings' );
		expect( $row )->not->toHaveKey( 'percent_off' );
	}
} );

/**
 * AC-6: each term label resolves through the terminology registry — never an inline
 * literal. The asserted strings are the genuine single-source registry values.
 */
test( 'renewal row term labels resolve via the terminology registry', function (): void {
	$rows = Api::renewalRows();

	expect( $rows[0]['term_label'] )->toBe( Terms::label( 'term_1_month' ) );
	expect( $rows[1]['term_label'] )->toBe( Terms::label( 'term_6_months' ) );
	expect( $rows[2]['term_label'] )->toBe( Terms::label( 'term_12_months' ) );

	// Sanity: Afrikaans month words, no English leakage.
	expect( $rows[0]['term_label'] )->toBe( '1 maand' );
	expect( $rows[2]['term_label'] )->toBe( '12 maande' );
} );

/**
 * AC-2: "renew" IS the manual fixed-term purchase — each row's renew CTA target equals
 * the 4.2 WC/PayFast purchase URL for that term (Api::purchaseUrl). There is no separate
 * auto-renew/recurring URL; renew = buy another fixed term.
 */
test( 'renewal row purchase url is the 4.2 PayFast purchase url for that term', function (): void {
	$rows = Api::renewalRows();

	foreach ( $rows as $i => $row ) {
		$term = LidmaatskapTerm::cases()[ $i ];

		expect( $row['purchase_url'] )->toBe( Api::purchaseUrl( $term ) );
		expect( $row['purchase_url'] )->toContain( 'https://ink.test/kassa' );
		expect( $row['purchase_url'] )->toContain( 'add-to-cart=' );

		expect( $row['is_available'] )->toBe( Api::isAvailable( $term ) );
		expect( $row['is_available'] )->toBeTrue();
	}
} );

/**
 * AC-2 (graceful degrade): when a plan is not sellable (WooCommerce absent), the renewal
 * row carries a null purchase_url and price — the section then degrades to an inert
 * "Binnekort beskikbaar" CTA (no invented endpoint).
 */
test( 'renewal rows degrade to null price and purchase url when WooCommerce is absent', function (): void {
	Functions\when( 'wc_get_product' )->justReturn( null );

	$rows = Api::renewalRows();

	expect( $rows )->toHaveCount( 3 );
	foreach ( $rows as $row ) {
		expect( $row['price'] )->toBeNull();
		expect( $row['price_display'] )->toBeNull();
		expect( $row['purchase_url'] )->toBeNull();
		expect( $row['is_available'] )->toBeFalse();
		// Term labels still resolve (the only INK-held copy that needs no WooCommerce).
		expect( $row['term_label'] )->not->toBe( '' );
	}
} );

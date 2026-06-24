<?php
/**
 * Unit tests for the fixed lidmaatskap term enum (Story 4.1, FR-4).
 *
 * Target: {@see \Ink\Entitlement\LidmaatskapTerm} — the closed value set of
 * lidmaatskap term lengths (1 / 6 / 12 months). The backing int is the term
 * length in months (the part the AC says "stays 1/6/12"); the price is NOT
 * modelled here (it is owned by the WooCommerce product, admin-editable).
 *
 * Brain Monkey, no WordPress/DB. The real `Ink\I18n\Terms` registry is
 * autoloaded so the asserted display labels are the genuine single-source
 * values (no literals duplicated in the test).
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Entitlement;

use Ink\Entitlement\LidmaatskapTerm;
use Ink\I18n\Terms;
use Brain\Monkey;
use Brain\Monkey\Functions;

beforeEach( function (): void {
	Monkey\setUp();

	// `__()` is identity in unit context (no .mo) so Afrikaans source renders.
	Functions\when( '__' )->returnArg( 1 );
} );

afterEach( function (): void {
	Monkey\tearDown();
} );

/**
 * AC-1/AC-5: the enum is exactly the three fixed terms, backed by the month
 * count (1/6/12) — the term-set integrity guarantee ("terms stay 1/6/12").
 */
test( 'the enum has exactly the three fixed terms backed by month counts', function (): void {
	$cases = LidmaatskapTerm::cases();

	expect( $cases )->toHaveCount( 3 );

	$values = array_map( static fn ( LidmaatskapTerm $t ): int => $t->value, $cases );
	expect( $values )->toBe( array( 1, 6, 12 ) );

	expect( LidmaatskapTerm::OneMonth->value )->toBe( 1 );
	expect( LidmaatskapTerm::SixMonths->value )->toBe( 6 );
	expect( LidmaatskapTerm::TwelveMonths->value )->toBe( 12 );
} );

/**
 * AC-1: `months()` returns the term length in months (the backing int).
 */
test( 'months returns the term length in months', function (): void {
	expect( LidmaatskapTerm::OneMonth->months() )->toBe( 1 );
	expect( LidmaatskapTerm::SixMonths->months() )->toBe( 6 );
	expect( LidmaatskapTerm::TwelveMonths->months() )->toBe( 12 );
} );

/**
 * AC-4: the display label resolves through the terminology registry — never an
 * inline bare literal. The asserted strings are the genuine registry values.
 */
test( 'label resolves the Afrikaans display label via the terminology registry', function (): void {
	expect( LidmaatskapTerm::OneMonth->label() )->toBe( Terms::label( 'term_1_month' ) );
	expect( LidmaatskapTerm::SixMonths->label() )->toBe( Terms::label( 'term_6_months' ) );
	expect( LidmaatskapTerm::TwelveMonths->label() )->toBe( Terms::label( 'term_12_months' ) );

	// Sanity: the registry returns Afrikaans month words (no English leakage).
	expect( LidmaatskapTerm::OneMonth->label() )->toBe( '1 maand' );
	expect( LidmaatskapTerm::SixMonths->label() )->toBe( '6 maande' );
	expect( LidmaatskapTerm::TwelveMonths->label() )->toBe( '12 maande' );
} );

/**
 * AC-1: a term can be resolved from its month count (the persisted value),
 * and an out-of-set value is rejected (no silent fourth term).
 */
test( 'tryFrom resolves a valid month count and rejects an out-of-set value', function (): void {
	expect( LidmaatskapTerm::tryFrom( 6 ) )->toBe( LidmaatskapTerm::SixMonths );
	expect( LidmaatskapTerm::tryFrom( 3 ) )->toBeNull();
	expect( LidmaatskapTerm::tryFrom( 0 ) )->toBeNull();
} );

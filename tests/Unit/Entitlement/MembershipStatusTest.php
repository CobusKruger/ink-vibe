<?php
/**
 * Unit tests for the lidmaatskap access-state enum (Story 4.7, FR-9).
 *
 * Target: {@see \Ink\Entitlement\MembershipStatus} — the closed value set of the
 * four access states (active / expired / access-denied / payment-failed), each
 * mapping to its lid-family Afrikaans status message via the terminology registry.
 *
 * Brain Monkey, no WordPress/DB. The real `Ink\I18n\Terms` registry is autoloaded so
 * the asserted messages are the genuine single-source values (no literals duplicated
 * in the test).
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Entitlement;

use Ink\Entitlement\MembershipStatus;
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
 * AC-3: the enum is exactly the four AC states, backed by their stable state ids.
 */
test( 'the enum has exactly the four access states', function (): void {
	$cases = MembershipStatus::cases();

	expect( $cases )->toHaveCount( 4 );

	$values = array_map( static fn ( MembershipStatus $s ): string => $s->value, $cases );
	expect( $values )->toBe( array( 'active', 'expired', 'access-denied', 'payment-failed' ) );
} );

/**
 * AC-3: each case maps to its terminology-registry message key.
 */
test( 'each state maps to its registry message key', function (): void {
	expect( MembershipStatus::Active->messageKey() )->toBe( 'status_active' );
	expect( MembershipStatus::Expired->messageKey() )->toBe( 'status_expired' );
	expect( MembershipStatus::AccessDenied->messageKey() )->toBe( 'status_access_denied' );
	expect( MembershipStatus::PaymentFailed->messageKey() )->toBe( 'status_payment_failed' );
} );

/**
 * AC-1/AC-3: each state resolves to its Afrikaans message via the registry — never an
 * inline literal (the asserted strings are the genuine registry values).
 */
test( 'each state resolves its Afrikaans message via the registry', function (): void {
	expect( MembershipStatus::Active->message() )->toBe( Terms::label( 'status_active' ) );
	expect( MembershipStatus::Expired->message() )->toBe( Terms::label( 'status_expired' ) );
	expect( MembershipStatus::AccessDenied->message() )->toBe( Terms::label( 'status_access_denied' ) );
	expect( MembershipStatus::PaymentFailed->message() )->toBe( Terms::label( 'status_payment_failed' ) );

	// Sanity: the genuine approved Afrikaans copy (afrikaans-terms.md Deel 3).
	expect( MembershipStatus::Active->message() )->toBe( 'Jou lidmaatskap is aktief. Jy kan nou werk plaas.' );
	expect( MembershipStatus::Expired->message() )->toBe( 'Jou lidmaatskap het verval. Hernu om werk te plaas.' );
	expect( MembershipStatus::PaymentFailed->message() )->toBe( 'Jou betaling het misluk of is gekanselleer.' );
} );

/**
 * Conflation (AD-1, FR-13): the enum source references no `Ink\Tiers` /
 * `ink_writer_tier` — status messaging is a lidmaatskap-state concept only.
 */
test( 'the enum carries no Gradering / Tiers coupling', function (): void {
	$source = (string) file_get_contents(
		dirname( __DIR__, 3 ) . '/wp-content/plugins/ink-core/src/Entitlement/MembershipStatus.php'
	);

	// No CODE coupling: no `use Ink\Tiers…` import, no `ink_writer_tier` meta key.
	// (The conflation rule is named in prose docblocks, which is fine — guard the
	// actual coupling surfaces, not the documentation that asserts their absence.)
	expect( $source )->not->toMatch( '/\buse\s+Ink\\\\Tiers/' );
	expect( $source )->not->toContain( 'ink_writer_tier' );
} );

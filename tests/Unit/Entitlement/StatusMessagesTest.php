<?php
/**
 * Unit tests for the lidmaatskap status-message resolver (Story 4.7, FR-9).
 *
 * Target: {@see \Ink\Entitlement\StatusMessages} — the state → Afrikaans-message
 * resolver. It introduces no business rule (the 4.3 gate stays the entitlement
 * authority); it only maps a known state (typed enum, or a WooCommerce Memberships
 * status string) to the right lid-family Afrikaans message.
 *
 * Brain Monkey, no WordPress/DB. The real `Ink\I18n\Terms` registry is autoloaded so
 * the asserted messages are the genuine single-source values.
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Entitlement;

use Ink\Entitlement\MembershipStatus;
use Ink\Entitlement\StatusMessages;
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
 * AC-3/AC-4: a typed state resolves to its registry message.
 */
test( 'messageFor resolves a typed state to its Afrikaans message', function (): void {
	$resolver = new StatusMessages();

	expect( $resolver->messageFor( MembershipStatus::Active ) )->toBe( Terms::label( 'status_active' ) );
	expect( $resolver->messageFor( MembershipStatus::Expired ) )->toBe( Terms::label( 'status_expired' ) );
	expect( $resolver->messageFor( MembershipStatus::AccessDenied ) )->toBe( Terms::label( 'status_access_denied' ) );
	expect( $resolver->messageFor( MembershipStatus::PaymentFailed ) )->toBe( Terms::label( 'status_payment_failed' ) );
} );

/**
 * AC-3: the WC entitled / time statuses map to Active / Expired.
 */
test( 'fromWcStatus maps the entitled and time statuses', function (): void {
	$resolver = new StatusMessages();

	foreach ( array( 'active', 'complimentary', 'free', 'free_trial' ) as $wc ) {
		expect( $resolver->fromWcStatus( $wc ) )->toBe( MembershipStatus::Active );
	}

	expect( $resolver->fromWcStatus( 'expired' ) )->toBe( MembershipStatus::Expired );
} );

/**
 * AC-3: the administrative-revocation statuses (and any unrecognised status) fail safe
 * to AccessDenied — never an over-permissive Active.
 */
test( 'fromWcStatus fails safe to AccessDenied for revoked or unknown statuses', function (): void {
	$resolver = new StatusMessages();

	foreach ( array( 'cancelled', 'paused', 'pending', 'pending_cancellation', 'wat-ook-al', '' ) as $wc ) {
		expect( $resolver->fromWcStatus( $wc ) )->toBe( MembershipStatus::AccessDenied );
	}
} );

/**
 * AC-3 (hardening): fromWcStatus normalises casing / surrounding whitespace before
 * matching, so a stray-cased or padded status still maps to the correct enum case
 * rather than fail-safe-denying an entitled member (a false denial).
 */
test( 'fromWcStatus normalises casing and whitespace', function (): void {
	$resolver = new StatusMessages();

	expect( $resolver->fromWcStatus( 'Active' ) )->toBe( MembershipStatus::Active );
	expect( $resolver->fromWcStatus( ' active ' ) )->toBe( MembershipStatus::Active );
	expect( $resolver->fromWcStatus( 'EXPIRED' ) )->toBe( MembershipStatus::Expired );
	expect( $resolver->fromWcStatus( ' Expired ' ) )->toBe( MembershipStatus::Expired );
} );

/**
 * AC-3: fromWcStatus NEVER returns PaymentFailed — payment-failed is a PayFast-return
 * state, not a WooCommerce membership status.
 */
test( 'fromWcStatus never returns PaymentFailed', function (): void {
	$resolver = new StatusMessages();

	$statuses = array(
		'active',
		'complimentary',
		'free',
		'free_trial',
		'expired',
		'cancelled',
		'paused',
		'pending',
		'pending_cancellation',
		'unknown',
	);

	foreach ( $statuses as $wc ) {
		expect( $resolver->fromWcStatus( $wc ) )->not->toBe( MembershipStatus::PaymentFailed );
	}
} );

/**
 * AC-4: the convenience WC-status → message path returns the correct messages.
 */
test( 'messageForWcStatus returns the right Afrikaans message', function (): void {
	$resolver = new StatusMessages();

	expect( $resolver->messageForWcStatus( 'active' ) )->toBe( Terms::label( 'status_active' ) );
	expect( $resolver->messageForWcStatus( 'expired' ) )->toBe( Terms::label( 'status_expired' ) );
	expect( $resolver->messageForWcStatus( 'cancelled' ) )->toBe( Terms::label( 'status_access_denied' ) );
} );

/**
 * Conflation (AD-1, FR-13): the resolver source references no `Ink\Tiers` /
 * `ink_writer_tier`.
 */
test( 'the resolver carries no Gradering / Tiers coupling', function (): void {
	$source = (string) file_get_contents(
		dirname( __DIR__, 3 ) . '/wp-content/plugins/ink-core/src/Entitlement/StatusMessages.php'
	);

	// No CODE coupling: no `use Ink\Tiers…` import, no `ink_writer_tier` meta key.
	// (The conflation rule is named in prose docblocks, which is fine — guard the
	// actual coupling surfaces, not the documentation that asserts their absence.)
	expect( $source )->not->toMatch( '/\buse\s+Ink\\\\Tiers/' );
	expect( $source )->not->toContain( 'ink_writer_tier' );
} );

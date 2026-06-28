<?php
/**
 * Unit tests for the read-only subscription verification (Story 16.4).
 *
 * Target: {@see \Ink\Migration\SubscriptionVerifier} — confirms the cloned
 * WooCommerce Memberships data before cutover (no import). Tests the INK-owned
 * report shape + flagging (the "test the OUTCOME, not WC internals" rule), never
 * WooCommerce call shapes.
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Migration;

use Ink\Migration\SubscriptionVerifier;
use Brain\Monkey;

beforeEach( function (): void {
	Monkey\setUp();
} );

afterEach( function (): void {
	Monkey\tearDown();
} );

// --- pure report shaping ---

test( 'knownStatuses lists the recognised WC Memberships statuses', function (): void {
	$statuses = SubscriptionVerifier::knownStatuses();

	expect( $statuses )->toContain( 'active' );
	expect( $statuses )->toContain( 'expired' );
	expect( $statuses )->toContain( 'cancelled' );
	expect( $statuses )->toContain( 'paused' );
} );

test( 'summarise groups by status, counts plans, and splits active expiry vs unlimited', function (): void {
	$report = SubscriptionVerifier::summarise(
		array(
			array( 'user_id' => 1, 'plan_id' => 10, 'status' => 'active', 'end_date' => '2027-01-01' ),
			array( 'user_id' => 2, 'plan_id' => 10, 'status' => 'active', 'end_date' => '' ), // unlimited
			array( 'user_id' => 3, 'plan_id' => 20, 'status' => 'expired', 'end_date' => '2024-01-01' ),
		)
	);

	expect( $report['total'] )->toBe( 3 );
	expect( $report['by_status'] )->toBe( array( 'active' => 2, 'expired' => 1 ) );
	expect( $report['plans'] )->toBe( array( 10 => 2, 20 => 1 ) );
	expect( $report['active_with_expiry'] )->toBe( 1 );
	expect( $report['active_unlimited'] )->toBe( 1 );
	expect( $report['flagged'] )->toBe( array() ); // all healthy
} );

test( 'summarise flags a membership with no plan and one with an unknown status (and not healthy ones)', function (): void {
	$report = SubscriptionVerifier::summarise(
		array(
			array( 'user_id' => 1, 'plan_id' => 10, 'status' => 'active', 'end_date' => '2027-01-01' ), // healthy
			array( 'user_id' => 2, 'plan_id' => 0, 'status' => 'active', 'end_date' => '' ),            // no plan
			array( 'user_id' => 3, 'plan_id' => 30, 'status' => 'zombie', 'end_date' => '' ),           // unknown status
		)
	);

	expect( $report['flagged'] )->toHaveCount( 2 );

	$flagged_users = array_column( $report['flagged'], 'user_id' );
	expect( $flagged_users )->toBe( array( 2, 3 ) );    // the healthy user 1 is NOT flagged
	expect( $report['flagged'][0]['reasons'] )->toContain( 'geen-plan' );
	expect( $report['flagged'][1]['reasons'] )->toContain( 'onbekende-status' );
} );

// --- verify() over the WC seam ---

test( 'verify reports not-available and an empty report when WC Memberships is absent', function (): void {
	$verifier = new class() extends SubscriptionVerifier {
		protected function wooMembershipsAvailable(): bool {
			return false;
		}
		protected function membershipRecords(): array {
			throw new \RuntimeException( 'must not read records when WC is unavailable' );
		}
	};

	$report = $verifier->verify();

	expect( $report['available'] )->toBeFalse();
	expect( $report['total'] )->toBe( 0 );
	expect( $report['flagged'] )->toBe( array() );
} );

test( 'verify summarises the records from the seam when WC is available', function (): void {
	$verifier = new class() extends SubscriptionVerifier {
		protected function wooMembershipsAvailable(): bool {
			return true;
		}
		protected function membershipRecords(): array {
			return array(
				array( 'user_id' => 1, 'plan_id' => 10, 'status' => 'active', 'end_date' => '2027-01-01' ),
				array( 'user_id' => 2, 'plan_id' => 0, 'status' => 'active', 'end_date' => '' ),
			);
		}
	};

	$report = $verifier->verify();

	expect( $report['available'] )->toBeTrue();
	expect( $report['total'] )->toBe( 2 );
	expect( $report['flagged'] )->toHaveCount( 1 ); // the no-plan record
} );

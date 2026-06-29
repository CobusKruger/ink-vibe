<?php
/**
 * Integration test (real WP) — the submission-entitlement gate (Story 18.8, NFR-9).
 *
 * Seam: `Ink\Entitlement\Api::can_submit()` — THE conflation gate (AD-2/FR-19).
 * The DENY path is fully exercisable in the ink-core-only wp-env (no WooCommerce
 * Memberships → fail-safe deny): a logged-out user and a plain member without an
 * active lidmaatskap both resolve `false`. The active-member ⇒ `true` path requires
 * WooCommerce Memberships + a PayFast-sandbox purchase and is covered by the E2E
 * critical-journey spec (tests/e2e/critical-journey.spec.js).
 *
 * Runs INSIDE wp-env (real WP+DB), NOT a mocked unit test.
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Integration\Entitlement;

use Ink\Entitlement\Api;

test( 'a logged-out user cannot submit (fail-safe deny)', function (): void {
	expect( Api::can_submit( null ) )->toBeFalse();
} );

test( 'a member without an active lidmaatskap cannot submit (conflation deny path)', function (): void {
	$user_id = wp_create_user( 'ink_lid_18_8', 'sterk-wagwoord-18-8', 'ink_lid_18_8@ink.test' );

	expect( $user_id )->toBeInt();

	// No active WooCommerce Membership → fail-safe deny. This also pins the
	// conflation rule: the gate reads membership only, never ink_writer_tier.
	expect( Api::can_submit( (int) $user_id ) )->toBeFalse();
} );

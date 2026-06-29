<?php
/**
 * Integration test (real WP) — the tier write seam (Story 18.8, NFR-9).
 *
 * Seam: `Ink\Tiers\Api::promote()` writes `ink_writer_tier` user-meta AND appends a
 * row to the `ink_tier_history` custom table (FR-12). The unit suite proves the
 * orchestration with mocks; this is its real-WP+DB counterpart — the meta actually
 * persists and the PromotionLog row is actually written to the custom table created
 * at activation via the Kernel Schema registry.
 *
 * Fully exercisable in the ink-core-only wp-env (no WooCommerce needed — tier is
 * Gradering, not lidmaatskap; THE conflation rule). Runs INSIDE wp-env, NOT mocked.
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Integration\Tiers;

use Ink\Tiers\Api;
use Ink\Tiers\PromotionLog;
use Ink\Kernel\Tier;

test( 'promote() writes the writer-tier meta and appends a PromotionLog row', function (): void {
	$user_id = wp_create_user( 'ink_skrywer_18_8', 'sterk-wagwoord-18-8', 'ink_skrywer_18_8@ink.test' );

	expect( $user_id )->toBeInt();
	$user_id = (int) $user_id;

	// Baseline: a fresh writer defaults to Brons.
	expect( Api::forUser( $user_id ) )->toBe( Tier::Brons );

	$promoted = Api::promote( $user_id, Tier::Silwer, 0, 'Integrasie-toets bevordering' );

	expect( $promoted )->toBeTrue();

	// Meta actually persisted to the DB.
	expect( get_user_meta( $user_id, Tier::META_KEY, true ) )->toBe( Tier::Silwer->value );
	expect( Api::forUser( $user_id ) )->toBe( Tier::Silwer );

	// The audit row actually landed in the custom table.
	$history = PromotionLog::forUser( $user_id );
	expect( $history )->not->toBe( array() );
} );

test( 'promote() to the same grade is a no-op (no spurious history row)', function (): void {
	$user_id = (int) wp_create_user( 'ink_skrywer2_18_8', 'sterk-wagwoord-18-8', 'ink_skrywer2_18_8@ink.test' );

	// Brons → Brons: no change.
	expect( Api::promote( $user_id, Tier::Brons ) )->toBeFalse();
	expect( PromotionLog::forUser( $user_id ) )->toBe( array() );
} );

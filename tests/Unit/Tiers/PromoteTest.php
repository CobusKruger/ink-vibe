<?php
/**
 * Unit tests for the sole Gradering write path (Story 5.2).
 *
 * Target: {@see \Ink\Tiers\Api::promote()} — writes `ink_writer_tier` +
 * normalised `ink_tier_promoted_at`, appends the audit log, fires the
 * `ink/tier_promoted` event, and is a no-op on an unchanged grade.
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Tiers;

use Ink\Kernel\Tier;
use Ink\Tiers\Api;
use Brain\Monkey;
use Brain\Monkey\Functions;
use Brain\Monkey\Actions;
use Mockery;

beforeEach( function (): void {
	Monkey\setUp();

	$wpdb            = Mockery::mock();
	$wpdb->prefix    = 'wp_';
	$GLOBALS['wpdb'] = $wpdb;

	Functions\when( 'current_time' )->justReturn( '2026-06-26 07:30:00' );
} );

afterEach( function (): void {
	unset( $GLOBALS['wpdb'] );
	Monkey\tearDown();
} );

/**
 * AC-2: an actual change writes both meta keys, appends the log, fires the
 * event, and returns true.
 */
test( 'promote writes the grade, stamps promoted_at, logs, and fires the event', function (): void {
	Functions\when( 'get_user_meta' )->justReturn( 'brons' ); // current grade.

	Functions\expect( 'update_user_meta' )->once()->with( 42, 'ink_writer_tier', 'silwer' );
	Functions\expect( 'update_user_meta' )->once()->with( 42, 'ink_tier_promoted_at', '2026-06-26 07:30:00' );
	Functions\expect( 'update_user_meta' )->once()->with( 42, 'ink_tier_win_count', 0 ); // Story 5.7 reset.

	$GLOBALS['wpdb']->shouldReceive( 'insert' )->once()->andReturn( 1 );

	Actions\expectDone( 'ink/tier_promoted' )->once()->with( 42, Tier::Brons, Tier::Silwer, 3, 0 );

	expect( Api::promote( 42, Tier::Silwer, 3, 'Bevordering', 0 ) )->toBeTrue();
} );

/**
 * AC-2: a no-op change (from === to) writes nothing, logs nothing, fires
 * nothing, and returns false.
 */
test( 'promote is a no-op when the grade is unchanged', function (): void {
	Functions\when( 'get_user_meta' )->justReturn( 'goud' );

	Functions\expect( 'update_user_meta' )->never();
	$GLOBALS['wpdb']->shouldReceive( 'insert' )->never();
	Actions\expectDone( 'ink/tier_promoted' )->never();

	expect( Api::promote( 7, Tier::Goud, 3, 'geen verandering' ) )->toBeFalse();
} );

/**
 * AC-1: any direction works, including a downward correction and a manual set to
 * Meester.
 */
test( 'promote supports a downward correction', function (): void {
	Functions\when( 'get_user_meta' )->justReturn( 'goud' );
	Functions\expect( 'update_user_meta' )->once()->with( 9, 'ink_writer_tier', 'silwer' );
	Functions\expect( 'update_user_meta' )->once()->with( 9, 'ink_tier_promoted_at', Mockery::type( 'string' ) );
	Functions\expect( 'update_user_meta' )->once()->with( 9, 'ink_tier_win_count', 0 );
	$GLOBALS['wpdb']->shouldReceive( 'insert' )->once()->andReturn( 1 );
	Actions\expectDone( 'ink/tier_promoted' )->once();

	expect( Api::promote( 9, Tier::Silwer, 3, 'Regstelling' ) )->toBeTrue();
} );

test( 'promote supports a manual set to Meester', function (): void {
	Functions\when( 'get_user_meta' )->justReturn( 'goud' );
	Functions\expect( 'update_user_meta' )->once()->with( 9, 'ink_writer_tier', 'meester' );
	Functions\expect( 'update_user_meta' )->once()->with( 9, 'ink_tier_promoted_at', Mockery::type( 'string' ) );
	Functions\expect( 'update_user_meta' )->once()->with( 9, 'ink_tier_win_count', 0 );
	$GLOBALS['wpdb']->shouldReceive( 'insert' )->once()->andReturn( 1 );
	Actions\expectDone( 'ink/tier_promoted' )->once();

	expect( Api::promote( 9, Tier::Meester, 3, 'Meestertoekenning' ) )->toBeTrue();
} );

/**
 * Audit durability: when the append-only log row fails to persist (e.g. the
 * table is missing on an un-upgraded install), the promotion still stands but
 * the loss is surfaced via the ink/tier_promotion_log_failed monitoring seam
 * rather than being dropped silently.
 */
test( 'promote fires the audit-failure seam when the log row does not persist', function (): void {
	Functions\when( 'get_user_meta' )->justReturn( 'brons' );
	Functions\when( 'update_user_meta' )->justReturn( true );
	Functions\when( 'wp_trigger_error' )->justReturn( null );
	$GLOBALS['wpdb']->shouldReceive( 'insert' )->once()->andReturn( false ); // audit insert fails.

	Actions\expectDone( 'ink/tier_promoted' )->once();
	Actions\expectDone( 'ink/tier_promotion_log_failed' )->once()->with( 42, Tier::Brons, Tier::Silwer, 3, 0 );

	// The promotion itself still stands.
	expect( Api::promote( 42, Tier::Silwer, 3, 'Bevordering', 0 ) )->toBeTrue();
} );

/**
 * AC-2: actor_id defaults to 0 (the automatic engine).
 */
test( 'promote defaults the actor to 0 (system)', function (): void {
	Functions\when( 'get_user_meta' )->justReturn( 'brons' );
	Functions\when( 'update_user_meta' )->justReturn( true );
	$GLOBALS['wpdb']->shouldReceive( 'insert' )->once()->andReturn( 1 );
	Actions\expectDone( 'ink/tier_promoted' )->once()->with( 5, Tier::Brons, Tier::Silwer, 0, 0 );

	expect( Api::promote( 5, Tier::Silwer ) )->toBeTrue();
} );

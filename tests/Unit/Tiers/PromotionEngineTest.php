<?php
/**
 * Unit tests for the automatic promotion engine (Story 5.8).
 *
 * Target: {@see \Ink\Tiers\PromotionEngine::award()} (+ the {@see \Ink\Tiers\Api::awardWins()}
 * facade) — the 5/15 thresholds, Goud/Meester terminal, system actor, the
 * `ink/tier_promoted` event.
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Tiers;

use Ink\Kernel\Tier;
use Ink\Tiers\Api;
use Ink\Tiers\PromotionEngine;
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
	Functions\when( 'update_user_meta' )->justReturn( true );
	Functions\when( '__' )->returnArg( 1 );
} );

afterEach( function (): void {
	unset( $GLOBALS['wpdb'] );
	Monkey\tearDown();
} );

/**
 * Stub `get_user_meta` to return the grade for the tier key and a fixed win
 * count for the win-count key.
 */
function ink_stub_tier_meta( string $grade, int $wins ): void {
	Functions\when( 'get_user_meta' )->alias(
		static function ( int $user_id, string $key, bool $single ) use ( $grade, $wins ) {
			return 'ink_writer_tier' === $key ? $grade : (string) $wins;
		}
	);
}

/**
 * AC-1: Brons reaching 5 wins promotes to Silwer (system actor, event fired).
 */
test( 'Brons promotes to Silwer at the 5th win', function (): void {
	ink_stub_tier_meta( 'brons', 4 );
	$GLOBALS['wpdb']->shouldReceive( 'insert' )->once()->andReturn( 1 );
	Actions\expectDone( 'ink/tier_promoted' )->once()->with( 42, Tier::Brons, Tier::Silwer, 0, 0 );

	expect( PromotionEngine::award( 42, 1 ) )->toBe( Tier::Silwer );
} );

/**
 * AC-1: below the threshold, no promotion (null, no event).
 */
test( 'Brons below 5 wins does not promote', function (): void {
	ink_stub_tier_meta( 'brons', 2 );
	$GLOBALS['wpdb']->shouldReceive( 'insert' )->never();
	Actions\expectDone( 'ink/tier_promoted' )->never();

	expect( PromotionEngine::award( 42, 1 ) )->toBeNull();
} );

/**
 * AC-1: Silwer reaching 15 wins promotes to Goud.
 */
test( 'Silwer promotes to Goud at the 15th win', function (): void {
	ink_stub_tier_meta( 'silwer', 14 );
	$GLOBALS['wpdb']->shouldReceive( 'insert' )->once()->andReturn( 1 );
	Actions\expectDone( 'ink/tier_promoted' )->once()->with( 7, Tier::Silwer, Tier::Goud, 0, 0 );

	expect( PromotionEngine::award( 7, 1 ) )->toBe( Tier::Goud );
} );

/**
 * AC-1: Silwer below 15 does not promote.
 */
test( 'Silwer below 15 wins does not promote', function (): void {
	ink_stub_tier_meta( 'silwer', 13 );
	$GLOBALS['wpdb']->shouldReceive( 'insert' )->never();
	Actions\expectDone( 'ink/tier_promoted' )->never();

	expect( PromotionEngine::award( 7, 1 ) )->toBeNull();
} );

/**
 * AC-1: Goud has no auto-threshold — a win never promotes (Meester likewise).
 */
test( 'Goud never auto-promotes', function (): void {
	ink_stub_tier_meta( 'goud', 99 );
	$GLOBALS['wpdb']->shouldReceive( 'insert' )->never();
	Actions\expectDone( 'ink/tier_promoted' )->never();

	expect( PromotionEngine::award( 7, 1 ) )->toBeNull();
} );

/**
 * AC-1: multiple wins in one call can cross the threshold and promote once.
 */
test( 'multiple wins in one call cross the threshold', function (): void {
	ink_stub_tier_meta( 'brons', 3 ); // +3 = 6 >= 5.
	$GLOBALS['wpdb']->shouldReceive( 'insert' )->once()->andReturn( 1 );
	Actions\expectDone( 'ink/tier_promoted' )->once()->with( 7, Tier::Brons, Tier::Silwer, 0, 0 );

	expect( PromotionEngine::award( 7, 3 ) )->toBe( Tier::Silwer );
} );

/**
 * AC-2: the linked challenge id reaches the promotion event/log.
 */
test( 'the challenge id is carried into the promotion', function (): void {
	ink_stub_tier_meta( 'brons', 4 );
	$GLOBALS['wpdb']->shouldReceive( 'insert' )->once()->andReturn( 1 );
	Actions\expectDone( 'ink/tier_promoted' )->once()->with( 42, Tier::Brons, Tier::Silwer, 0, 11 );

	expect( PromotionEngine::award( 42, 1, 11 ) )->toBe( Tier::Silwer );
} );

/**
 * AC-2: the Api facade delegates to the engine.
 */
test( 'Api::awardWins delegates to the engine', function (): void {
	ink_stub_tier_meta( 'brons', 4 );
	$GLOBALS['wpdb']->shouldReceive( 'insert' )->once()->andReturn( 1 );
	Actions\expectDone( 'ink/tier_promoted' )->once();

	expect( Api::awardWins( 42, 1 ) )->toBe( Tier::Silwer );
} );

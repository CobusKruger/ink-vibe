<?php
/**
 * Unit tests for the trending score (Story 8.2, FR-33, AD-7).
 *
 * Target: {@see \Ink\Discovery\TrendingScore}. The heart is the pure `compute()`
 * gravity function (monotonic in reactions, decaying with age) and the pure
 * `ageInDays()` helper. The Action Scheduler wiring + `recomputeAll()` are thin
 * WP glue over these.
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Discovery;

use Ink\Discovery\TrendingScore;
use Ink\Content\PostTypes;
use Brain\Monkey;

beforeEach( function (): void {
	Monkey\setUp();
	if ( ! defined( 'DAY_IN_SECONDS' ) ) {
		define( 'DAY_IN_SECONDS', 86400 );
	}
} );

afterEach( function (): void {
	Monkey\tearDown();
} );

test( 'scorableTypes are the three readable bydrae types', function (): void {
	expect( TrendingScore::scorableTypes() )->toBe( array( PostTypes::GEDIG, PostTypes::STORIE, PostTypes::ARTIKEL ) );
} );

test( 'compute is monotonic in the reaction total at a fixed age', function (): void {
	$age = 5;
	expect( TrendingScore::compute( 10, $age ) )->toBeGreaterThan( TrendingScore::compute( 2, $age ) );
	expect( TrendingScore::compute( 100, $age ) )->toBeGreaterThan( TrendingScore::compute( 10, $age ) );
} );

test( 'compute decays with age at a fixed reaction total', function (): void {
	$reactions = 20;
	expect( TrendingScore::compute( $reactions, 1 ) )->toBeGreaterThan( TrendingScore::compute( $reactions, 30 ) );
	expect( TrendingScore::compute( $reactions, 30 ) )->toBeGreaterThan( TrendingScore::compute( $reactions, 365 ) );
} );

test( 'a fresh, modestly-reacted work can out-rank an old, heavily-stale one', function (): void {
	$fresh = TrendingScore::compute( 8, 1 );    // 8 reactions, yesterday
	$stale = TrendingScore::compute( 80, 400 ); // 80 reactions, over a year old

	expect( $fresh )->toBeGreaterThan( $stale );
} );

test( 'compute clamps a negative age and negative reaction total', function (): void {
	// Negative age behaves as age 0; negative reactions behave as 0.
	expect( TrendingScore::compute( -5, 3 ) )->toBe( TrendingScore::compute( 0, 3 ) );
	expect( TrendingScore::compute( 5, -10 ) )->toBe( TrendingScore::compute( 5, 0 ) );
	expect( TrendingScore::compute( 0, 0 ) )->toBeGreaterThan( 0.0 );
} );

test( 'ageInDays is whole days, never negative, and 0 for an invalid timestamp', function (): void {
	$now = 1_000 * DAY_IN_SECONDS;

	expect( TrendingScore::ageInDays( $now - ( 3 * DAY_IN_SECONDS ), $now ) )->toBe( 3 );
	expect( TrendingScore::ageInDays( $now - 100, $now ) )->toBe( 0 );        // < 1 day
	expect( TrendingScore::ageInDays( $now + ( 5 * DAY_IN_SECONDS ), $now ) )->toBe( 0 ); // future → clamped
	expect( TrendingScore::ageInDays( false, $now ) )->toBe( 0 );             // invalid
	expect( TrendingScore::ageInDays( 0, $now ) )->toBe( 0 );
} );

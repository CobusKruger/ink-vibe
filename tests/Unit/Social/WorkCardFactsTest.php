<?php
/**
 * Unit tests for the shared per-post "work card" facts helper.
 *
 * Target: {@see \Ink\Social\WorkCardFacts}, extracted from
 * {@see \Ink\Social\SkrywerProfiel::pinnedCards()} so {@see \Ink\Social\FollowingFeed}
 * reuses the identical days-ago + engagement-count computation rather than
 * re-deriving it. Covers the pure `daysAgoLabel()`/`daysAgoLabelForPost()`
 * formatting and `engagement()`'s real read-through to `Engagement\Api`.
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Social;

use Ink\Social\WorkCardFacts;
use Brain\Monkey;
use Brain\Monkey\Functions;

if ( ! defined( 'DAY_IN_SECONDS' ) ) {
	define( 'DAY_IN_SECONDS', 86400 );
}

beforeEach( function (): void {
	Monkey\setUp();
	Functions\when( '_n' )->alias( static fn ( string $s, string $p, int $n ): string => 1 === $n ? $s : $p );
	Functions\when( 'number_format_i18n' )->alias( static fn ( $n, $d = 0 ): string => number_format( (float) $n, (int) $d ) );
} );

afterEach( function (): void {
	Monkey\tearDown();
	unset( $GLOBALS['wpdb'] );
} );

test( 'daysAgoLabel formats a plural days-ago label', function (): void {
	$label = WorkCardFacts::daysAgoLabel( time() - ( 3 * DAY_IN_SECONDS ) );

	expect( $label )->toBe( '3 dae gelede' );
} );

test( 'daysAgoLabel formats the singular "1 dag gelede" form', function (): void {
	$label = WorkCardFacts::daysAgoLabel( time() - DAY_IN_SECONDS );

	expect( $label )->toBe( '1 dag gelede' );
} );

test( 'daysAgoLabel floors negative age (future timestamp) to zero, never a negative day count', function (): void {
	$label = WorkCardFacts::daysAgoLabel( time() + DAY_IN_SECONDS );

	expect( $label )->toBe( '0 dae gelede' );
} );

test( 'daysAgoLabelForPost resolves the post\'s GMT publish timestamp', function (): void {
	Functions\when( 'get_post_time' )->justReturn( time() - ( 2 * DAY_IN_SECONDS ) );

	expect( WorkCardFacts::daysAgoLabelForPost( 42 ) )->toBe( '2 dae gelede' );
} );

test( 'daysAgoLabelForPost returns \'\' when the timestamp can\'t be resolved', function (): void {
	Functions\when( 'get_post_time' )->justReturn( false );

	expect( WorkCardFacts::daysAgoLabelForPost( 42 ) )->toBe( '' );
} );

test( 'engagement resolves the hartjie count/label and gemeenskap count via Engagement\\Api', function (): void {
	$wpdb            = \Mockery::mock();
	$wpdb->prefix    = 'wp_';
	$GLOBALS['wpdb'] = $wpdb;
	$wpdb->shouldReceive( 'prepare' )->andReturn( 'PREPARED' );
	$wpdb->shouldReceive( 'get_results' )->andReturn(
		array( (object) array( 'reaction' => 'hartjie', 'total' => '5' ) )
	);

	Functions\when( 'get_comments' )->justReturn( 7 );

	$facts = WorkCardFacts::engagement( 99 );

	expect( $facts['hartjies'] )->toBe( 5 );
	expect( $facts['hartjieLabel'] )->toBe( '5 hartjies' );
	expect( $facts['gemeenskap'] )->toBe( 7 );
} );

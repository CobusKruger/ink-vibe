<?php
/**
 * Unit tests for the Gradering audit-log table (Story 5.3).
 *
 * Target: {@see \Ink\Tiers\PromotionLog} — the `ink_tier_history` custom table:
 * the dbDelta schema, the typed append, and the per-writer history read.
 *
 * Establishes the reusable `$wpdb` Mockery double pattern for ink-core (the
 * first custom table). A Mockery mock is assigned to `global $wpdb` per test.
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Tiers;

use Ink\Kernel\Tier;
use Ink\Tiers\PromotionLog;
use Ink\Tiers\PromotionLogEntry;
use Brain\Monkey;
use Brain\Monkey\Functions;
use Mockery;

beforeEach( function (): void {
	Monkey\setUp();

	$wpdb         = Mockery::mock();
	$wpdb->prefix = 'wp_';
	$GLOBALS['wpdb'] = $wpdb;
} );

afterEach( function (): void {
	unset( $GLOBALS['wpdb'] );
	Monkey\tearDown();
} );

/**
 * AC-1: the table name is the prefixed single-source constant.
 */
test( 'tableName prefixes the single-source table constant', function (): void {
	expect( PromotionLog::TABLE )->toBe( 'ink_tier_history' );
	expect( PromotionLog::tableName() )->toBe( 'wp_ink_tier_history' );
} );

/**
 * AC-1/AC-3: the dbDelta DDL carries the table, every column, and the keys.
 */
test( 'schemaSql is dbDelta-compatible with all columns and keys', function (): void {
	$GLOBALS['wpdb']->shouldReceive( 'get_charset_collate' )->andReturn( 'DEFAULT CHARSET=utf8mb4' );

	$sql = PromotionLog::schemaSql();

	expect( $sql )->toContain( 'CREATE TABLE wp_ink_tier_history' );
	foreach ( array( 'id', 'user_id', 'from_tier', 'to_tier', 'actor_id', 'reason', 'challenge_id', 'created_at' ) as $column ) {
		expect( $sql )->toContain( $column );
	}
	expect( $sql )->toContain( 'PRIMARY KEY  (id)' ); // dbDelta requires two spaces.
	expect( $sql )->toContain( 'KEY user_id (user_id)' );
	expect( $sql )->toContain( 'KEY created_at (created_at)' );
	expect( $sql )->toContain( 'DEFAULT CHARSET=utf8mb4' );
} );

/**
 * AC-2/AC-3: record() inserts the typed row with grades as backing strings, the
 * GMT timestamp, and an explicit format array.
 */
test( 'record inserts the row with grades as backing strings and a format array', function (): void {
	Functions\when( 'current_time' )->justReturn( '2026-06-25 07:30:00' );

	$GLOBALS['wpdb']->shouldReceive( 'insert' )
		->once()
		->with(
			'wp_ink_tier_history',
			array(
				'user_id'      => 42,
				'from_tier'    => 'brons',
				'to_tier'      => 'silwer',
				'actor_id'     => 3,
				'reason'       => 'Bevordering',
				'challenge_id' => 11,
				'created_at'   => '2026-06-25 07:30:00',
			),
			array( '%d', '%s', '%s', '%d', '%s', '%d', '%s' )
		)
		->andReturn( 1 );

	$ok = PromotionLog::record( 42, Tier::Brons, Tier::Silwer, 3, 'Bevordering', 11 );

	expect( $ok )->toBeTrue();
} );

/**
 * AC-1: a system (automatic) change defaults actor_id to 0 and challenge to 0.
 */
test( 'record defaults to a system actor with no challenge link', function (): void {
	Functions\when( 'current_time' )->justReturn( '2026-06-25 07:30:00' );

	$GLOBALS['wpdb']->shouldReceive( 'insert' )
		->once()
		->with(
			'wp_ink_tier_history',
			Mockery::on( static fn ( array $data ): bool => 0 === $data['actor_id'] && 0 === $data['challenge_id'] && 'goud' === $data['to_tier'] ),
			Mockery::type( 'array' )
		)
		->andReturn( 1 );

	expect( PromotionLog::record( 9, Tier::Silwer, Tier::Goud ) )->toBeTrue();
} );

/**
 * AC-2/AC-3: a failed insert returns false.
 */
test( 'record returns false when the insert fails', function (): void {
	Functions\when( 'current_time' )->justReturn( '2026-06-25 07:30:00' );
	$GLOBALS['wpdb']->shouldReceive( 'insert' )->andReturn( false );

	expect( PromotionLog::record( 1, Tier::Brons, Tier::Silwer ) )->toBeFalse();
} );

/**
 * AC-2/AC-3: forUser binds the user filter via prepare() and maps rows newest-first.
 */
test( 'forUser runs a prepared query and maps rows to typed entries', function (): void {
	$GLOBALS['wpdb']->shouldReceive( 'prepare' )
		->once()
		->with( Mockery::pattern( '/WHERE user_id = %d ORDER BY created_at DESC/' ), 42 )
		->andReturn( 'PREPARED' );

	$GLOBALS['wpdb']->shouldReceive( 'get_results' )
		->once()
		->with( 'PREPARED' )
		->andReturn( array(
			ink_tier_history_row( array( 'id' => '2', 'to_tier' => 'goud' ) ),
			ink_tier_history_row( array( 'id' => '1', 'to_tier' => 'silwer' ) ),
		) );

	$history = PromotionLog::forUser( 42 );

	expect( $history )->toHaveCount( 2 );
	expect( $history[0] )->toBeInstanceOf( PromotionLogEntry::class );
	expect( $history[0]->to )->toBe( Tier::Goud );
	expect( $history[1]->to )->toBe( Tier::Silwer );
} );

/**
 * AC-2: a writer with no history returns an empty list (not null).
 */
test( 'forUser returns an empty list when there is no history', function (): void {
	$GLOBALS['wpdb']->shouldReceive( 'prepare' )->andReturn( 'PREPARED' );
	$GLOBALS['wpdb']->shouldReceive( 'get_results' )->andReturn( null );

	expect( PromotionLog::forUser( 999 ) )->toBe( array() );
} );

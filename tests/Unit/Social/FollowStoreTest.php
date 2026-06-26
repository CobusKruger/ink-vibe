<?php
/**
 * Unit tests for the follow-graph custom-table store (Story 9.2, FR-38, AD-5).
 *
 * Target: {@see \Ink\Social\FollowStore}. Mockery `$wpdb`. The asymmetric edge,
 * the self-follow guard, the dedup (UNIQUE user_followee) and the reverse-query
 * indexes (KEY followee_id / user_id) are asserted.
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Social;

use Ink\Social\FollowStore;
use Brain\Monkey;
use Brain\Monkey\Functions;
use Mockery;

beforeEach( function (): void {
	Monkey\setUp();

	$wpdb            = Mockery::mock();
	$wpdb->prefix    = 'wp_';
	$GLOBALS['wpdb'] = $wpdb;
} );

afterEach( function (): void {
	unset( $GLOBALS['wpdb'] );
	Monkey\tearDown();
} );

test( 'tableName prefixes the single-source table constant', function (): void {
	expect( FollowStore::TABLE )->toBe( 'ink_follows' );
	expect( FollowStore::tableName() )->toBe( 'wp_ink_follows' );
} );

test( 'schemaSql dedups the edge and indexes both directions', function (): void {
	$GLOBALS['wpdb']->shouldReceive( 'get_charset_collate' )->andReturn( 'DEFAULT CHARSET=utf8mb4' );

	$sql = FollowStore::schemaSql();

	expect( $sql )->toContain( 'CREATE TABLE wp_ink_follows' );
	foreach ( array( 'id', 'user_id', 'followee_id', 'created_at' ) as $column ) {
		expect( $sql )->toContain( $column );
	}
	expect( $sql )->toContain( 'PRIMARY KEY  (id)' );
	expect( $sql )->toContain( 'UNIQUE KEY user_followee (user_id,followee_id)' ); // dedup
	expect( $sql )->toContain( 'KEY followee_id (followee_id)' );                  // reverse volgeling count
	expect( $sql )->toContain( 'KEY user_id (user_id)' );                          // following list / feed
} );

test( 'follow upserts a valid asymmetric edge (dedup no-op on repeat follow)', function (): void {
	Functions\expect( 'current_time' )->once()->with( 'mysql', true )->andReturn( '2026-06-26 12:00:00' );

	$GLOBALS['wpdb']->shouldReceive( 'prepare' )
		->once()
		->with(
			Mockery::pattern( '/INSERT INTO wp_ink_follows.*ON DUPLICATE KEY UPDATE created_at = VALUES\(created_at\)/s' ),
			7,
			42,
			'2026-06-26 12:00:00'
		)
		->andReturn( 'PREPARED' );

	$GLOBALS['wpdb']->shouldReceive( 'query' )->once()->with( 'PREPARED' )->andReturn( 1 );

	expect( FollowStore::follow( 7, 42 ) )->toBeTrue();
} );

test( 'follow rejects self-follow without writing (non-vacuous — a distinct edge DOES write)', function (): void {
	// No prepare/query expectation set: if follow() tried to write, Mockery would
	// fail on the unexpected call. The guard must short-circuit.
	$GLOBALS['wpdb']->shouldNotReceive( 'query' );

	expect( FollowStore::follow( 7, 7 ) )->toBeFalse();
} );

test( 'follow rejects non-positive ids without writing', function (): void {
	$GLOBALS['wpdb']->shouldNotReceive( 'query' );

	expect( FollowStore::follow( 0, 42 ) )->toBeFalse();
	expect( FollowStore::follow( 7, 0 ) )->toBeFalse();
} );

test( 'unfollow deletes the asymmetric edge (idempotent)', function (): void {
	$GLOBALS['wpdb']->shouldReceive( 'delete' )
		->once()
		->with( 'wp_ink_follows', array( 'user_id' => 7, 'followee_id' => 42 ), array( '%d', '%d' ) )
		->andReturn( 1 );

	expect( FollowStore::unfollow( 7, 42 ) )->toBeTrue();
} );

test( 'isFollowing is true only when the edge exists', function (): void {
	$GLOBALS['wpdb']->shouldReceive( 'prepare' )->andReturn( 'PREPARED' );
	$GLOBALS['wpdb']->shouldReceive( 'get_var' )->once()->andReturn( '3' );
	expect( FollowStore::isFollowing( 7, 42 ) )->toBeTrue();
} );

test( 'isFollowing is false when there is no edge', function (): void {
	$GLOBALS['wpdb']->shouldReceive( 'prepare' )->andReturn( 'PREPARED' );
	$GLOBALS['wpdb']->shouldReceive( 'get_var' )->once()->andReturn( null );
	expect( FollowStore::isFollowing( 7, 42 ) )->toBeFalse();
} );

test( 'followerCount counts over the followee_id column', function (): void {
	$GLOBALS['wpdb']->shouldReceive( 'prepare' )
		->once()
		->with( Mockery::pattern( '/COUNT\(\*\).*WHERE followee_id = %d/s' ), 42 )
		->andReturn( 'PREPARED' );
	$GLOBALS['wpdb']->shouldReceive( 'get_var' )->once()->with( 'PREPARED' )->andReturn( '12' );

	expect( FollowStore::followerCount( 42 ) )->toBe( 12 );
} );

test( 'followingCount counts over the user_id column', function (): void {
	$GLOBALS['wpdb']->shouldReceive( 'prepare' )
		->once()
		->with( Mockery::pattern( '/COUNT\(\*\).*WHERE user_id = %d/s' ), 7 )
		->andReturn( 'PREPARED' );
	$GLOBALS['wpdb']->shouldReceive( 'get_var' )->once()->with( 'PREPARED' )->andReturn( '5' );

	expect( FollowStore::followingCount( 7 ) )->toBe( 5 );
} );

test( 'followerIdsFor queries the followee_id column (who follows this writer)', function (): void {
	$GLOBALS['wpdb']->shouldReceive( 'prepare' )
		->once()
		->with( Mockery::pattern( '/SELECT user_id.*WHERE followee_id = %d/s' ), 42 )
		->andReturn( 'PREPARED' );
	$GLOBALS['wpdb']->shouldReceive( 'get_col' )->once()->andReturn( array( '7', '9' ) );

	expect( FollowStore::followerIdsFor( 42 ) )->toBe( array( 7, 9 ) );
} );

test( 'followeeIdsFor returns the followed ids newest first, empty when none', function (): void {
	$GLOBALS['wpdb']->shouldReceive( 'prepare' )
		->with( Mockery::pattern( '/SELECT followee_id.*WHERE user_id = %d ORDER BY created_at DESC/s' ), 7 )
		->andReturn( 'PREPARED' );
	$GLOBALS['wpdb']->shouldReceive( 'get_col' )->once()->andReturn( array( '42', '7' ) );

	expect( FollowStore::followeeIdsFor( 7 ) )->toBe( array( 42, 7 ) );
} );

test( 'followeeIdsFor returns an empty list when the member follows nobody', function (): void {
	$GLOBALS['wpdb']->shouldReceive( 'prepare' )->andReturn( 'PREPARED' );
	$GLOBALS['wpdb']->shouldReceive( 'get_col' )->once()->andReturn( null );

	expect( FollowStore::followeeIdsFor( 7 ) )->toBe( array() );
} );

<?php
/**
 * Unit tests for the leeslys custom-table store (Story 7.7, FR-29, AD-5).
 *
 * Target: {@see \Ink\Engagement\ReadingListStore}. Mockery `$wpdb`. The dedup
 * (UNIQUE user_post) and the reverse-query index (KEY post_id) are asserted in
 * the schema.
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Engagement;

use Ink\Engagement\ReadingListStore;
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
	expect( ReadingListStore::TABLE )->toBe( 'ink_reading_list' );
	expect( ReadingListStore::tableName() )->toBe( 'wp_ink_reading_list' );
} );

test( 'schemaSql dedups per member and indexes the reverse who-saved-this query', function (): void {
	$GLOBALS['wpdb']->shouldReceive( 'get_charset_collate' )->andReturn( 'DEFAULT CHARSET=utf8mb4' );

	$sql = ReadingListStore::schemaSql();

	expect( $sql )->toContain( 'CREATE TABLE wp_ink_reading_list' );
	foreach ( array( 'id', 'user_id', 'post_id', 'created_at' ) as $column ) {
		expect( $sql )->toContain( $column );
	}
	expect( $sql )->toContain( 'PRIMARY KEY  (id)' );
	expect( $sql )->toContain( 'UNIQUE KEY user_post (user_id,post_id)' ); // dedup
	expect( $sql )->toContain( 'KEY post_id (post_id)' );                  // reverse query
} );

test( 'add upserts (dedup no-op on repeat save)', function (): void {
	Functions\expect( 'current_time' )->once()->with( 'mysql', true )->andReturn( '2026-06-26 12:00:00' );

	$GLOBALS['wpdb']->shouldReceive( 'prepare' )
		->once()
		->with(
			Mockery::pattern( '/INSERT INTO wp_ink_reading_list.*ON DUPLICATE KEY UPDATE created_at = VALUES\(created_at\)/s' ),
			7,
			42,
			'2026-06-26 12:00:00'
		)
		->andReturn( 'PREPARED' );

	$GLOBALS['wpdb']->shouldReceive( 'query' )->once()->with( 'PREPARED' )->andReturn( 1 );

	expect( ReadingListStore::add( 7, 42 ) )->toBeTrue();
} );

test( 'remove deletes the member/post row', function (): void {
	$GLOBALS['wpdb']->shouldReceive( 'delete' )
		->once()
		->with( 'wp_ink_reading_list', array( 'user_id' => 7, 'post_id' => 42 ), array( '%d', '%d' ) )
		->andReturn( 1 );

	expect( ReadingListStore::remove( 7, 42 ) )->toBeTrue();
} );

test( 'has is true only when a row exists', function (): void {
	$GLOBALS['wpdb']->shouldReceive( 'prepare' )->andReturn( 'PREPARED' );
	$GLOBALS['wpdb']->shouldReceive( 'get_var' )->once()->andReturn( '3' );
	expect( ReadingListStore::has( 7, 42 ) )->toBeTrue();
} );

test( 'has is false when there is no row', function (): void {
	$GLOBALS['wpdb']->shouldReceive( 'prepare' )->andReturn( 'PREPARED' );
	$GLOBALS['wpdb']->shouldReceive( 'get_var' )->once()->andReturn( null );
	expect( ReadingListStore::has( 7, 42 ) )->toBeFalse();
} );

test( 'forUser returns saved post ids newest first, empty when none', function (): void {
	$GLOBALS['wpdb']->shouldReceive( 'prepare' )->andReturn( 'PREPARED' );
	$GLOBALS['wpdb']->shouldReceive( 'get_col' )->once()->andReturn( array( '42', '7' ) );

	expect( ReadingListStore::forUser( 7 ) )->toBe( array( 42, 7 ) );
} );

test( 'forUser returns an empty list when nothing is saved', function (): void {
	$GLOBALS['wpdb']->shouldReceive( 'prepare' )->andReturn( 'PREPARED' );
	$GLOBALS['wpdb']->shouldReceive( 'get_col' )->once()->andReturn( null );

	expect( ReadingListStore::forUser( 7 ) )->toBe( array() );
} );

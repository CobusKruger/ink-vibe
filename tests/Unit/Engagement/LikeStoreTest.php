<?php
/**
 * Unit tests for the poem-hartjie store: one like per member per work.
 *
 * Target: {@see \Ink\Engagement\LikeStore}. `$wpdb` is a Mockery mock on the global.
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Engagement;

use Ink\Engagement\LikeStore;
use Brain\Monkey;
use Brain\Monkey\Functions;
use Mockery;

beforeEach( function (): void {
	Monkey\setUp();

	$wpdb             = Mockery::mock();
	$wpdb->prefix     = 'wp_';
	$wpdb->postmeta   = 'wp_postmeta';
	$GLOBALS['wpdb']  = $wpdb;
} );

afterEach( function (): void {
	Monkey\tearDown();
	Mockery::close();
	unset( $GLOBALS['wpdb'] );
} );

test( 'the table keeps one like per member per work', function (): void {
	$GLOBALS['wpdb']->shouldReceive( 'get_charset_collate' )->andReturn( '' );

	expect( LikeStore::tableName() )->toBe( 'wp_ink_likes' );
	expect( LikeStore::schemaSql() )->toContain( 'UNIQUE KEY user_post (user_id,post_id)' );
} );

test( 'add raises the count by one only when the like is new', function (): void {
	Functions\when( 'current_time' )->justReturn( '2026-11-06 10:00:00' );
	Functions\when( 'get_post_meta' )->justReturn( '5' );
	Functions\when( 'wp_cache_delete' )->justReturn( true );

	$GLOBALS['wpdb']->shouldReceive( 'prepare' )->andReturn( 'SQL' );
	// First INSERT IGNORE adds a row (1), then the UPDATE; a repeat adds none (0) and no UPDATE.
	$GLOBALS['wpdb']->shouldReceive( 'query' )->with( 'SQL' )->times( 3 )->andReturn( 1, 1, 0 );

	expect( LikeStore::add( 42, 7 ) )->toBeTrue();
	expect( LikeStore::add( 42, 7 ) )->toBeFalse();
} );

test( 'add and remove ignore a missing work or member', function (): void {
	expect( LikeStore::add( 0, 7 ) )->toBeFalse();
	expect( LikeStore::add( 42, 0 ) )->toBeFalse();
	expect( LikeStore::has( 0, 7 ) )->toBeFalse();
} );

test( 'remove lowers the count only when a like was really removed', function (): void {
	Functions\when( 'get_post_meta' )->justReturn( '5' );
	Functions\when( 'wp_cache_delete' )->justReturn( true );

	$GLOBALS['wpdb']->shouldReceive( 'delete' )->andReturn( 0, 1 );
	$GLOBALS['wpdb']->shouldReceive( 'prepare' )->andReturn( 'SQL' );
	$GLOBALS['wpdb']->shouldReceive( 'query' )->with( 'SQL' )->once()->andReturn( 1 );

	expect( LikeStore::remove( 42, 7 ) )->toBeFalse();
	expect( LikeStore::remove( 42, 7 ) )->toBeTrue();
} );

test( 'count reads the denormalized field and never goes below zero', function (): void {
	Functions\when( 'get_post_meta' )->justReturn( '-3' );

	expect( LikeStore::count( 42 ) )->toBe( 0 );
} );

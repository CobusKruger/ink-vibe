<?php
/**
 * Unit tests for the line-reaction custom-table store (Story 7.3, FR-26).
 *
 * Target: {@see \Ink\Engagement\ReactionStore}. `$wpdb` is a Mockery mock on the
 * global, mirroring the PromotionLog test pattern. The reactions-only guarantee
 * (AC #2) is guarded non-vacuously: the schema is asserted to carry the known
 * columns AND to carry NO text/body/comment column.
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Engagement;

use Ink\Engagement\ReactionStore;
use Ink\Kernel\Reaction;
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
	expect( ReactionStore::TABLE )->toBe( 'ink_line_reactions' );
	expect( ReactionStore::tableName() )->toBe( 'wp_ink_line_reactions' );
} );

test( 'schemaSql is dbDelta-compatible, enforces one-per-user-per-line, and has NO text column', function (): void {
	$GLOBALS['wpdb']->shouldReceive( 'get_charset_collate' )->andReturn( 'DEFAULT CHARSET=utf8mb4' );

	$sql = ReactionStore::schemaSql();

	expect( $sql )->toContain( 'CREATE TABLE wp_ink_line_reactions' );
	foreach ( array( 'id', 'post_id', 'line_index', 'user_id', 'reaction', 'created_at' ) as $column ) {
		expect( $sql )->toContain( $column );
	}
	expect( $sql )->toContain( 'PRIMARY KEY  (id)' );                            // two spaces (dbDelta)
	expect( $sql )->toContain( 'UNIQUE KEY user_line (post_id,line_index,user_id)' ); // one-per-user-per-line
	expect( $sql )->toContain( 'DEFAULT CHARSET=utf8mb4' );

	// Reactions-only (AC #2): there is NO free-form text/body/comment column.
	foreach ( array( ' text ', 'body', 'comment', 'annotation' ) as $forbidden ) {
		expect( $sql )->not->toContain( $forbidden );
	}
} );

test( 'set upserts one row per user per line (ON DUPLICATE KEY UPDATE)', function (): void {
	Functions\expect( 'current_time' )->once()->with( 'mysql', true )->andReturn( '2026-06-26 10:00:00' );

	$GLOBALS['wpdb']->shouldReceive( 'prepare' )
		->once()
		->with(
			Mockery::pattern( '/INSERT INTO wp_ink_line_reactions.*ON DUPLICATE KEY UPDATE reaction = VALUES\(reaction\)/s' ),
			42,
			3,
			7,
			'hartjie',
			'2026-06-26 10:00:00'
		)
		->andReturn( 'PREPARED' );

	$GLOBALS['wpdb']->shouldReceive( 'query' )->once()->with( 'PREPARED' )->andReturn( 1 );

	expect( ReactionStore::set( 42, 3, 7, Reaction::Hartjie ) )->toBeTrue();
} );

test( 'remove deletes the member row for that line', function (): void {
	$GLOBALS['wpdb']->shouldReceive( 'delete' )
		->once()
		->with(
			'wp_ink_line_reactions',
			array(
				'post_id'    => 42,
				'line_index' => 3,
				'user_id'    => 7,
			),
			array( '%d', '%d', '%d' )
		)
		->andReturn( 1 );

	expect( ReactionStore::remove( 42, 3, 7 ) )->toBeTrue();
} );

test( 'userReaction maps a stored value to the enum, null when none', function (): void {
	$GLOBALS['wpdb']->shouldReceive( 'prepare' )->andReturn( 'PREPARED' );
	$GLOBALS['wpdb']->shouldReceive( 'get_var' )->once()->andReturn( 'wow' );

	expect( ReactionStore::userReaction( 42, 3, 7 ) )->toBe( Reaction::Wow );
} );

test( 'userReaction returns null when the member has not reacted', function (): void {
	$GLOBALS['wpdb']->shouldReceive( 'prepare' )->andReturn( 'PREPARED' );
	$GLOBALS['wpdb']->shouldReceive( 'get_var' )->once()->andReturn( null );

	expect( ReactionStore::userReaction( 42, 3, 7 ) )->toBeNull();
} );

test( 'forPost returns an empty list when there are no reactions', function (): void {
	$GLOBALS['wpdb']->shouldReceive( 'prepare' )->andReturn( 'PREPARED' );
	$GLOBALS['wpdb']->shouldReceive( 'get_results' )->andReturn( null );

	expect( ReactionStore::forPost( 999 ) )->toBe( array() );
} );

test( 'countsForPost aggregates per reaction and normalises every reaction to a count', function (): void {
	$GLOBALS['wpdb']->shouldReceive( 'prepare' )
		->once()
		->with( Mockery::pattern( '/SELECT reaction, COUNT\(\*\) AS total FROM wp_ink_line_reactions WHERE post_id = %d GROUP BY reaction/' ), 42 )
		->andReturn( 'PREPARED' );

	// Only hartjie + wow have rows; duim_op has none.
	$GLOBALS['wpdb']->shouldReceive( 'get_results' )->once()->with( 'PREPARED' )->andReturn(
		array(
			(object) array( 'reaction' => 'hartjie', 'total' => '342' ),
			(object) array( 'reaction' => 'wow', 'total' => '5' ),
		)
	);

	$counts = ReactionStore::countsForPost( 42 );

	expect( $counts )->toBe( array( 'hartjie' => 342, 'duim_op' => 0, 'wow' => 5 ) ); // normalised, duim_op → 0
} );

test( 'countsForPost returns all-zero counts when there are no reactions', function (): void {
	$GLOBALS['wpdb']->shouldReceive( 'prepare' )->andReturn( 'PREPARED' );
	$GLOBALS['wpdb']->shouldReceive( 'get_results' )->andReturn( null );

	expect( ReactionStore::countsForPost( 999 ) )->toBe( array( 'hartjie' => 0, 'duim_op' => 0, 'wow' => 0 ) );
} );

<?php
/**
 * Unit tests for the reader-ratings store (Story 9.6, FR-42, AD-5).
 *
 * Target: {@see \Ink\Social\RatingStore}. The load-bearing assertion: the public
 * aggregate + reviews queries filter `goedgekeur` ONLY — a held (`hangend`)
 * review can never surface (POPIA / held-for-moderation).
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Social;

use Ink\Social\RatingStore;
use Ink\Social\RatingStatus;
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

test( 'schemaSql keeps one rating per rater/writer and indexes the approved query', function (): void {
	$GLOBALS['wpdb']->shouldReceive( 'get_charset_collate' )->andReturn( 'DEFAULT CHARSET=utf8mb4' );

	$sql = RatingStore::schemaSql();

	expect( $sql )->toContain( 'CREATE TABLE wp_ink_ratings' );
	foreach ( array( 'user_id', 'skrywer_id', 'score', 'resensie', 'status', 'created_at' ) as $column ) {
		expect( $sql )->toContain( $column );
	}
	expect( $sql )->toContain( 'UNIQUE KEY user_skrywer (user_id,skrywer_id)' );
	expect( $sql )->toContain( 'KEY skrywer_status (skrywer_id,status)' );
} );

test( 'rate upserts the rating held (hangend), never auto-public', function (): void {
	Functions\expect( 'current_time' )->once()->with( 'mysql', true )->andReturn( '2026-06-26 12:00:00' );

	$GLOBALS['wpdb']->shouldReceive( 'prepare' )
		->once()
		->with(
			Mockery::pattern( '/INSERT INTO wp_ink_ratings.*ON DUPLICATE KEY UPDATE/s' ),
			7,
			42,
			5,
			'Pragtig.',
			'hangend', // RatingStatus::Hangend — held, not public
			'2026-06-26 12:00:00'
		)
		->andReturn( 'PREPARED' );
	$GLOBALS['wpdb']->shouldReceive( 'query' )->once()->with( 'PREPARED' )->andReturn( 1 );

	expect( RatingStore::rate( 7, 42, 5, 'Pragtig.' ) )->toBeTrue();
} );

test( 'aggregate counts APPROVED ratings only (held reviews never count)', function (): void {
	$GLOBALS['wpdb']->shouldReceive( 'prepare' )
		->once()
		->with(
			Mockery::pattern( '/AVG\(score\).*WHERE skrywer_id = %d AND status = %s/s' ),
			42,
			'goedgekeur' // the decisive filter — NOT hangend
		)
		->andReturn( 'PREPARED' );
	$GLOBALS['wpdb']->shouldReceive( 'get_row' )->once()->with( 'PREPARED', ARRAY_A )->andReturn( array( 'n' => '3', 'gem' => '4.5' ) );

	expect( RatingStore::aggregate( 42 ) )->toBe( array( 'count' => 3, 'average' => 4.5 ) );
} );

test( 'aggregate is zero when nothing is approved (pre-18.4 held state)', function (): void {
	$GLOBALS['wpdb']->shouldReceive( 'prepare' )->andReturn( 'PREPARED' );
	$GLOBALS['wpdb']->shouldReceive( 'get_row' )->once()->andReturn( array( 'n' => '0', 'gem' => null ) );

	expect( RatingStore::aggregate( 42 ) )->toBe( array( 'count' => 0, 'average' => 0.0 ) );
} );

test( 'approvedReviews binds the goedgekeur status and non-empty review filter', function (): void {
	$GLOBALS['wpdb']->shouldReceive( 'prepare' )
		->once()
		->with(
			Mockery::pattern( "/status = %s AND resensie <> ''/s" ),
			42,
			'goedgekeur'
		)
		->andReturn( 'PREPARED' );
	$GLOBALS['wpdb']->shouldReceive( 'get_results' )->once()->with( 'PREPARED', ARRAY_A )->andReturn(
		array( array( 'user_id' => '7', 'score' => '5', 'resensie' => 'Pragtig.' ) )
	);

	$reviews = RatingStore::approvedReviews( 42 );

	expect( $reviews )->toBe( array( array( 'user_id' => 7, 'score' => 5, 'resensie' => 'Pragtig.' ) ) );
} );

test( 'the held status value is hangend (never auto-public)', function (): void {
	expect( RatingStatus::Hangend->value )->toBe( 'hangend' );
	expect( RatingStatus::Goedgekeur->value )->toBe( 'goedgekeur' );
	expect( RatingStatus::Verwerp->value )->toBe( 'verwerp' );
} );

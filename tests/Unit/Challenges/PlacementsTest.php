<?php
/**
 * Unit tests for structured placement records (Story 12.6, FR-50).
 *
 * Target: {@see \Ink\Challenges\Placements} — records 1st/2nd/3rd per Gradering per
 * round on the authoritative entry, distinguishing algehele wenner (1st) from wenner
 * (2nd/3rd). Pure layers + the validated record write.
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Challenges;

use Ink\Challenges\Placements;
use Brain\Monkey;
use Brain\Monkey\Functions;

beforeEach( function (): void {
	Monkey\setUp();
	Functions\when( '__' )->returnArg( 1 );
} );

afterEach( function (): void {
	Monkey\tearDown();
} );

test( 'isValidRank accepts 1-3 and rejects everything else', function (): void {
	expect( Placements::isValidRank( 1 ) )->toBeTrue();
	expect( Placements::isValidRank( 3 ) )->toBeTrue();
	expect( Placements::isValidRank( 0 ) )->toBeFalse();
	expect( Placements::isValidRank( 4 ) )->toBeFalse();
	expect( Placements::isValidRank( -1 ) )->toBeFalse();
} );

test( 'isAlgeheleWenner is true only for the 1st placement', function (): void {
	expect( Placements::isAlgeheleWenner( 1 ) )->toBeTrue();
	expect( Placements::isAlgeheleWenner( 2 ) )->toBeFalse();
	expect( Placements::isAlgeheleWenner( 3 ) )->toBeFalse();
} );

test( 'placementLabel distinguishes algehele wenner (1) from wenner (2/3)', function (): void {
	expect( Placements::placementLabel( 1 ) )->toBe( 'algehele wenner' );
	expect( Placements::placementLabel( 2 ) )->toBe( 'wenner' );
	expect( Placements::placementLabel( 3 ) )->toBe( 'wenner' );
	expect( Placements::placementLabel( 0 ) )->toBe( '' );
} );

test( 'record writes a valid rank to the entry placement meta', function (): void {
	Functions\expect( 'update_post_meta' )->once()->with( 42, Placements::PLACEMENT_META_KEY, 2 );

	expect( Placements::record( 42, 2 ) )->toBeTrue();
} );

test( 'record rejects an invalid rank or entry without writing', function (): void {
	Functions\expect( 'update_post_meta' )->never();

	expect( Placements::record( 42, 4 ) )->toBeFalse();
	expect( Placements::record( 0, 1 ) )->toBeFalse();
} );

test( 'arrange groups placed entries per pool, sorted by rank, ignoring the unplaced', function (): void {
	$placed = array(
		array( 'id' => 10, 'gradering' => 'goud', 'rank' => 2 ),
		array( 'id' => 11, 'gradering' => 'goud', 'rank' => 1 ),
		array( 'id' => 12, 'gradering' => 'brons', 'rank' => 1 ),
		array( 'id' => 13, 'gradering' => 'goud', 'rank' => 0 ), // unplaced — excluded
		array( 'id' => 14, 'gradering' => 'goud', 'rank' => 3 ),
	);

	$by_pool = Placements::arrange( $placed );

	// Goud pool: ranks 1,2,3 in order, by entry id.
	expect( array_column( $by_pool['goud'], 'id' ) )->toBe( array( 11, 10, 14 ) );
	expect( array_column( $by_pool['goud'], 'rank' ) )->toBe( array( 1, 2, 3 ) );
	expect( $by_pool['goud'][0]['is_algehele_wenner'] )->toBeTrue();
	expect( $by_pool['goud'][1]['is_algehele_wenner'] )->toBeFalse();

	// Brons pool: the single 1st place.
	expect( array_column( $by_pool['brons'], 'id' ) )->toBe( array( 12 ) );

	// The unplaced entry (rank 0) is absent.
	$all_ids = array_merge( array_column( $by_pool['goud'], 'id' ), array_column( $by_pool['brons'], 'id' ) );
	expect( $all_ids )->not->toContain( 13 );
} );

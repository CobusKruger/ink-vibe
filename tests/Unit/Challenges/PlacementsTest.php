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

test( 'arrange collapses a duplicated rank to one entry (lowest id wins) — no two algehele wenners (R12)', function (): void {
	$placed = array(
		array( 'id' => 30, 'gradering' => 'silwer', 'rank' => 1 ),
		array( 'id' => 20, 'gradering' => 'silwer', 'rank' => 1 ), // duplicate rank 1 — dirty data
		array( 'id' => 40, 'gradering' => 'silwer', 'rank' => 2 ),
	);

	$silwer = Placements::arrange( $placed )['silwer'];

	// Only ONE rank-1 survives, and it is the lowest id (deterministic).
	$first_places = array_filter( $silwer, static fn ( array $r ): bool => 1 === $r['rank'] );
	expect( $first_places )->toHaveCount( 1 );
	expect( array_column( $silwer, 'id' ) )->toBe( array( 20, 40 ) );
	expect( array_column( $silwer, 'rank' ) )->toBe( array( 1, 2 ) );
} );

test( 'arrange keeps BOTH category rank-1s of one Gradering — the D1 read-collapse regression', function (): void {
	// Goud has a Gedig algehele wenner AND a Storie algehele wenner. Before the D1 fix the
	// pool was keyed on gradering alone, so the two LEGITIMATE rank-1s collided and one was
	// silently dropped. They must now BOTH survive — in separate (Gradering × category) pools.
	$placed = array(
		array( 'id' => 10, 'gradering' => 'goud', 'category' => 'gedig', 'rank' => 1 ),  // Goud-Gedig algehele wenner
		array( 'id' => 11, 'gradering' => 'goud', 'category' => 'storie', 'rank' => 1 ), // Goud-Storie algehele wenner
		array( 'id' => 12, 'gradering' => 'goud', 'category' => 'gedig', 'rank' => 2 ),  // Goud-Gedig wenner
	);

	$by_pool = Placements::arrange( $placed );

	// Two distinct Goud pools — one per category — each keyed via Pools::poolKey.
	expect( $by_pool )->toHaveKey( 'goud|gedig' );
	expect( $by_pool )->toHaveKey( 'goud|storie' );

	// BOTH algehele wenners survive (the regression that proves D1 is fixed).
	expect( array_column( $by_pool['goud|gedig'], 'id' ) )->toBe( array( 10, 12 ) );
	expect( array_column( $by_pool['goud|gedig'], 'rank' ) )->toBe( array( 1, 2 ) );
	expect( $by_pool['goud|gedig'][0]['is_algehele_wenner'] )->toBeTrue();

	expect( array_column( $by_pool['goud|storie'], 'id' ) )->toBe( array( 11 ) );
	expect( $by_pool['goud|storie'][0]['is_algehele_wenner'] )->toBeTrue();

	// Across both categories there are TWO algehele wenners, not one (non-vacuous).
	$algehele = array_merge(
		array_filter( $by_pool['goud|gedig'], static fn ( array $r ): bool => $r['is_algehele_wenner'] ),
		array_filter( $by_pool['goud|storie'], static fn ( array $r ): bool => $r['is_algehele_wenner'] )
	);
	expect( $algehele )->toHaveCount( 2 );
} );

test( 'arrange STILL collapses two rank-1s in the SAME (Gradering × category) — the guard is preserved', function (): void {
	// Two rank-1s in the SAME category of the same Gradering is dirty data — the defensive
	// one-per-rank guard must still collapse it (lowest id wins), even category-scoped.
	$placed = array(
		array( 'id' => 30, 'gradering' => 'goud', 'category' => 'gedig', 'rank' => 1 ),
		array( 'id' => 20, 'gradering' => 'goud', 'category' => 'gedig', 'rank' => 1 ), // duplicate within same pool
		array( 'id' => 40, 'gradering' => 'goud', 'category' => 'gedig', 'rank' => 2 ),
	);

	$pool = Placements::arrange( $placed )['goud|gedig'];

	$first_places = array_filter( $pool, static fn ( array $r ): bool => 1 === $r['rank'] );
	expect( $first_places )->toHaveCount( 1 );
	expect( array_column( $pool, 'id' ) )->toBe( array( 20, 40 ) ); // lowest-id rank-1 wins
} );

<?php
/**
 * Unit tests for Gradering-based competition pools (Story 12.5, FR-49/UJ-4).
 *
 * Target: {@see \Ink\Challenges\Pools} — groups a round's entries into per-Gradering
 * pools (Brons/Silwer/Goud) by their entry-time snapshot. Pure layers only.
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Challenges;

use Ink\Challenges\Pools;
use Ink\Kernel\Tier;
use Brain\Monkey;
use Brain\Monkey\Functions;

beforeEach( function (): void {
	Monkey\setUp();
	Functions\when( '__' )->returnArg( 1 );
} );

afterEach( function (): void {
	Monkey\tearDown();
} );

test( 'competingTiers is Brons/Silwer/Goud — never the manual-only Meester', function (): void {
	$values = array_map( static fn ( Tier $t ): string => $t->value, Pools::competingTiers() );

	expect( $values )->toBe( array( 'brons', 'silwer', 'goud' ) );
	expect( $values )->not->toContain( 'meester' );
} );

test( 'group buckets entries by their entry-time gradering, multiple per pool', function (): void {
	$entries = array(
		array( 'id' => 1, 'gradering' => 'brons' ),
		array( 'id' => 2, 'gradering' => 'goud' ),
		array( 'id' => 3, 'gradering' => 'brons' ),
		array( 'id' => 4, 'gradering' => 'silwer' ),
	);

	$pools = Pools::group( $entries );

	expect( $pools['brons'] )->toBe( array( 1, 3 ) );
	expect( $pools['silwer'] )->toBe( array( 4 ) );
	expect( $pools['goud'] )->toBe( array( 2 ) );
} );

test( 'group folds a Meester entry into the Goud pool; empty/junk form no pool', function (): void {
	$entries = array(
		array( 'id' => 1, 'gradering' => 'brons' ),
		array( 'id' => 2, 'gradering' => 'meester' ), // elevated Goud member → Goud pool
		array( 'id' => 5, 'gradering' => 'goud' ),     // a real Goud entry shares the pool
		array( 'id' => 3, 'gradering' => '' ),         // unsnapshotted — excluded
		array( 'id' => 4, 'gradering' => 'rubbish' ),  // junk — excluded
	);

	$pools = Pools::group( $entries );

	expect( $pools['brons'] )->toBe( array( 1 ) );
	// Meester competes in Goud: it shares the goud pool with the real Goud entry
	// (insertion follows entry-array order within the matching tier: id 2 then id 5).
	expect( $pools['goud'] )->toBe( array( 2, 5 ) );
	expect( $pools )->not->toHaveKey( 'meester' );
	expect( $pools )->not->toHaveKey( '' );
	expect( $pools )->not->toHaveKey( 'rubbish' );
} );

test( 'group folds a Meester entry into the (Goud × category) pool — D1 interaction', function (): void {
	$entries = array(
		array( 'id' => 1, 'gradering' => 'goud', 'category' => 'gedig' ),
		array( 'id' => 2, 'gradering' => 'meester', 'category' => 'gedig' ),  // → goud|gedig
		array( 'id' => 3, 'gradering' => 'meester', 'category' => 'storie' ), // → goud|storie
	);

	$pools = Pools::group( $entries );

	expect( $pools['goud|gedig'] )->toBe( array( 1, 2 ) );
	expect( $pools['goud|storie'] )->toBe( array( 3 ) );
	expect( $pools )->not->toHaveKey( 'meester|gedig' );
} );

test( 'group keys on (Gradering × category) when entries carry a category — the D1 fix', function (): void {
	// One Goud Gedig + one Goud Storie + one Goud Gedig: the two categories form two
	// distinct pools within Goud, so a per-category podium is never collapsed away.
	$entries = array(
		array( 'id' => 1, 'gradering' => 'goud', 'category' => 'gedig' ),
		array( 'id' => 2, 'gradering' => 'goud', 'category' => 'storie' ),
		array( 'id' => 3, 'gradering' => 'goud', 'category' => 'gedig' ),
		array( 'id' => 4, 'gradering' => 'brons', 'category' => 'gedig' ),
	);

	$pools = Pools::group( $entries );

	expect( $pools['goud|gedig'] )->toBe( array( 1, 3 ) );
	expect( $pools['goud|storie'] )->toBe( array( 2 ) );
	expect( $pools['brons|gedig'] )->toBe( array( 4 ) );
	// The collapsed Gradering-only key must NOT exist when a category is present.
	expect( $pools )->not->toHaveKey( 'goud' );
} );

test( 'poolKey / splitPoolKey round-trip (Gradering × category, and category-less)', function (): void {
	expect( Pools::poolKey( 'goud', 'gedig' ) )->toBe( 'goud|gedig' );
	expect( Pools::poolKey( 'goud' ) )->toBe( 'goud' ); // category-less degrades to Gradering-only

	expect( Pools::splitPoolKey( 'goud|gedig' ) )->toBe( array( 'goud', 'gedig' ) );
	expect( Pools::splitPoolKey( 'goud' ) )->toBe( array( 'goud', '' ) );
} );

test( 'poolLabel returns the Gradering label for a tier', function (): void {
	expect( Pools::poolLabel( Tier::Goud ) )->toBe( 'Goud' );
	expect( Pools::poolLabel( Tier::Brons ) )->toBe( 'Brons' );
} );

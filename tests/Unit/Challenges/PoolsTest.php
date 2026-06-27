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

test( 'group excludes entries with an empty or non-competing gradering (e.g. meester)', function (): void {
	$entries = array(
		array( 'id' => 1, 'gradering' => 'brons' ),
		array( 'id' => 2, 'gradering' => 'meester' ), // terminal/manual — no pool
		array( 'id' => 3, 'gradering' => '' ),         // unsnapshotted — excluded
		array( 'id' => 4, 'gradering' => 'rubbish' ),  // junk — excluded
	);

	$pools = Pools::group( $entries );

	expect( $pools['brons'] )->toBe( array( 1 ) );
	expect( $pools )->not->toHaveKey( 'meester' );
	expect( $pools )->not->toHaveKey( '' );
	expect( $pools )->not->toHaveKey( 'rubbish' );
} );

test( 'poolLabel returns the Gradering label for a tier', function (): void {
	expect( Pools::poolLabel( Tier::Goud ) )->toBe( 'Goud' );
	expect( Pools::poolLabel( Tier::Brons ) )->toBe( 'Brons' );
} );

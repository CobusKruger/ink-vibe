<?php
/**
 * Unit tests for the Gradering discovery + winner-label primitives (Story 5.5).
 *
 * Target: {@see \Ink\Tiers\Api::usersByGrade()} + {@see \Ink\Tiers\Api::winnerLabel()}
 * — the filter/segmentation primitive (Epic-8 Ontdek) and the winner label
 * (Epic-12 winners). Plus the `wenner` term added to the registry.
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Tiers;

use Ink\Kernel\Tier;
use Ink\Tiers\Api;
use Ink\I18n\Terms;
use Brain\Monkey;
use Brain\Monkey\Functions;

beforeEach( function (): void {
	Monkey\setUp();
	Functions\when( '__' )->returnArg( 1 );
} );

afterEach( function (): void {
	Monkey\tearDown();
} );

/**
 * AC-2: usersByGrade filters get_users by the ink_writer_tier meta and returns
 * int IDs.
 */
test( 'usersByGrade filters by the writer-tier meta and returns int ids', function (): void {
	Functions\expect( 'get_users' )
		->once()
		->with( array( 'meta_key' => 'ink_writer_tier', 'meta_value' => 'goud', 'fields' => 'ID' ) )
		->andReturn( array( '3', '7', '9' ) );

	expect( Api::usersByGrade( Tier::Goud ) )->toBe( array( 3, 7, 9 ) );
} );

/**
 * AC-2: caller args merge through to get_users.
 */
test( 'usersByGrade merges caller args', function (): void {
	$captured = array();
	Functions\when( 'get_users' )->alias(
		function ( array $args ) use ( &$captured ): array {
			$captured = $args;
			return array();
		}
	);

	Api::usersByGrade( Tier::Silwer, array( 'number' => 10, 'paged' => 2 ) );

	expect( $captured['meta_key'] )->toBe( 'ink_writer_tier' );
	expect( $captured['meta_value'] )->toBe( 'silwer' );
	expect( $captured['number'] )->toBe( 10 );
	expect( $captured['paged'] )->toBe( 2 );
} );

/**
 * Review patch (Group C): caller args cannot override the grade filter or fields —
 * the grade filter + `fields => 'ID'` are authoritative.
 */
test( 'usersByGrade does not let caller args override the grade filter', function (): void {
	$captured = array();
	Functions\when( 'get_users' )->alias(
		function ( array $args ) use ( &$captured ): array {
			$captured = $args;
			return array();
		}
	);

	Api::usersByGrade( Tier::Goud, array( 'meta_key' => 'evil', 'meta_value' => 'silwer', 'fields' => 'all' ) );

	expect( $captured['meta_key'] )->toBe( 'ink_writer_tier' );
	expect( $captured['meta_value'] )->toBe( 'goud' );
	expect( $captured['fields'] )->toBe( 'ID' );
} );

/**
 * Review patch (Group C): non-positive / empty ids are dropped from the result.
 */
test( 'usersByGrade drops non-positive ids', function (): void {
	Functions\when( 'get_users' )->justReturn( array( '0', '5', '', '12' ) );

	expect( Api::usersByGrade( Tier::Goud ) )->toBe( array( 5, 12 ) );
} );

/**
 * AC-1: winnerLabel composes "{period} {Grade}-wenner".
 */
test( 'winnerLabel composes the period, grade and wenner', function (): void {
	expect( Api::winnerLabel( Tier::Goud, 'Oktober' ) )->toBe( 'Oktober Goud-wenner' );
	expect( Api::winnerLabel( Tier::Silwer, 'Maart' ) )->toBe( 'Maart Silwer-wenner' );
} );

/**
 * AC-1/AC-2: the `wenner` term is registered in the Terms registry.
 */
test( 'the wenner term is registered', function (): void {
	expect( Terms::has( 'wenner' ) )->toBeTrue();
	expect( Terms::label( 'wenner' ) )->toBe( 'wenner' );
} );

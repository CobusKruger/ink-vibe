<?php
/**
 * Unit tests for the single-source reaction-count formatter (Story 7.8, FR-28).
 *
 * Target: {@see \Ink\Engagement\ReactionCounts::label()}. `_n` is aliased to the
 * real WordPress plural rule (singular at n==1, else plural) so the tests assert
 * the actual rendered strings: verb-less, correct singular/plural, AND n=0 → the
 * plural form ("0 hartjies").
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Engagement;

use Ink\Engagement\ReactionCounts;
use Ink\Kernel\Reaction;
use Brain\Monkey;
use Brain\Monkey\Functions;

beforeEach( function (): void {
	Monkey\setUp();
	// Mirror WP's _n(): singular when n == 1, plural otherwise.
	Functions\when( '_n' )->alias(
		static fn ( string $single, string $plural, int $number ): string => 1 === $number ? $single : $plural
	);
} );

afterEach( function (): void {
	Monkey\tearDown();
} );

test( 'hartjie count is verb-less with correct singular/plural', function (): void {
	expect( ReactionCounts::label( Reaction::Hartjie, 1 ) )->toBe( '1 hartjie' );
	expect( ReactionCounts::label( Reaction::Hartjie, 342 ) )->toBe( '342 hartjies' );
} );

test( 'n=0 renders the plural form (0 hartjies)', function (): void {
	expect( ReactionCounts::label( Reaction::Hartjie, 0 ) )->toBe( '0 hartjies' );
} );

test( 'duim op count is the invariant phrase; wow pluralises to wows', function (): void {
	expect( ReactionCounts::label( Reaction::DuimOp, 1 ) )->toBe( '1 duim op' );
	expect( ReactionCounts::label( Reaction::DuimOp, 18 ) )->toBe( '18 duim op' );

	expect( ReactionCounts::label( Reaction::Wow, 1 ) )->toBe( '1 wow' );
	expect( ReactionCounts::label( Reaction::Wow, 5 ) )->toBe( '5 wows' );
} );

test( 'no count format carries a vanity verb', function (): void {
	// Verb-less guarantee: the icon does the verb (FR-28); no "het", "gehou", "stem".
	foreach ( Reaction::cases() as $reaction ) {
		$label = strtolower( ReactionCounts::label( $reaction, 3 ) );
		foreach ( array( 'het ', 'gehou', 'stem', 'like' ) as $verb ) {
			expect( $label )->not->toContain( $verb );
		}
	}
} );

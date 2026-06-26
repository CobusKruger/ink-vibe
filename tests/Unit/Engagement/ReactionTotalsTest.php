<?php
/**
 * Unit tests for the reaction-totals block (Story 7.8, FR-28).
 *
 * Target: {@see \Ink\Engagement\ReactionTotals::toHtml()} — pure. Renders a
 * verb-less count per reaction via the single-source formatter. `_n` aliased to
 * the real plural rule; `esc_*` identity.
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Engagement;

use Ink\Engagement\ReactionTotals;
use Brain\Monkey;
use Brain\Monkey\Functions;

beforeEach( function (): void {
	Monkey\setUp();
	Functions\when( '_n' )->alias(
		static fn ( string $single, string $plural, int $number ): string => 1 === $number ? $single : $plural
	);
	Functions\when( 'esc_html' )->returnArg( 1 );
	Functions\when( 'esc_attr' )->returnArg( 1 );
} );

afterEach( function (): void {
	Monkey\tearDown();
} );

test( 'toHtml renders a verb-less count for every reaction via the single-source formatter', function (): void {
	$html = ReactionTotals::toHtml( array( 'hartjie' => 342, 'duim_op' => 0, 'wow' => 5 ) );

	expect( $html )->toContain( '342 hartjies' );
	expect( $html )->toContain( '0 duim op' );  // n=0 handled
	expect( $html )->toContain( '5 wows' );
	expect( $html )->toContain( 'ink-reaksie-tellers--hartjie' );
} );

test( 'toHtml treats a missing reaction key as zero', function (): void {
	$html = ReactionTotals::toHtml( array( 'hartjie' => 7 ) ); // duim_op / wow absent

	expect( $html )->toContain( '7 hartjies' );
	expect( $html )->toContain( '0 duim op' );
	expect( $html )->toContain( '0 wows' );
} );

<?php
/**
 * Unit tests for the winner banner variants (Story 12A.6, C9).
 *
 * Target: {@see \Ink\Challenges\WinnerBanner} — per-rank (algehele/wenner) + per-tier
 * banner markup with a11y (text + aria-hidden mark, never colour-only).
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Challenges;

use Ink\Challenges\WinnerBanner;
use Brain\Monkey;
use Brain\Monkey\Functions;

beforeEach( function (): void {
	Monkey\setUp();
	Functions\when( '__' )->returnArg( 1 );
	Functions\when( 'esc_attr' )->returnArg( 1 );
	Functions\when( 'esc_html' )->returnArg( 1 );
} );

afterEach( function (): void {
	Monkey\tearDown();
} );

test( 'variant is algehele for 1st, wenner for 2nd/3rd, empty otherwise', function (): void {
	expect( WinnerBanner::variant( 1 ) )->toBe( 'algehele' );
	expect( WinnerBanner::variant( 2 ) )->toBe( 'wenner' );
	expect( WinnerBanner::variant( 3 ) )->toBe( 'wenner' );
	expect( WinnerBanner::variant( 0 ) )->toBe( '' );
	expect( WinnerBanner::variant( 4 ) )->toBe( '' );
} );

test( 'toHtml emits the variant + tier classes, a text label, and an aria-hidden mark (no colour-only)', function (): void {
	$html = WinnerBanner::toHtml( 1, 'goud', 'algehele wenner' );

	expect( $html )->toContain( 'ink-wenner-banier--algehele' );
	expect( $html )->toContain( 'ink-gradering--goud' ); // reuses the 5.4 colour convention
	expect( $html )->toContain( 'aria-hidden="true"' );  // mark is decorative
	expect( $html )->toContain( 'algehele wenner' );     // the placement is TEXT, not colour-only
} );

test( 'toHtml maps Meester to the gradering colour convention (primary via --meester)', function (): void {
	expect( WinnerBanner::toHtml( 1, 'meester', 'algehele wenner' ) )->toContain( 'ink-gradering--meester' );
} );

test( 'toHtml omits the tier class when the grade is empty but still renders the banner', function (): void {
	$html = WinnerBanner::toHtml( 2, '', 'wenner' );

	expect( $html )->toContain( 'ink-wenner-banier--wenner' );
	expect( $html )->not->toContain( 'ink-gradering--' );
} );

test( 'toHtml returns empty for an invalid rank (no banner on a non-placed entry)', function (): void {
	expect( WinnerBanner::toHtml( 0, 'goud', 'x' ) )->toBe( '' );
	expect( WinnerBanner::toHtml( 4, 'goud', 'x' ) )->toBe( '' );
} );

test( 'forPost reads the placement + gradering snapshot and renders the banner', function (): void {
	Functions\when( 'get_post_meta' )->alias(
		function ( $id, $key ) {
			if ( 'ink_entry_placement' === $key ) {
				return '1';
			}
			if ( 'ink_entry_gradering' === $key ) {
				return 'silwer';
			}
			return '';
		}
	);

	$html = WinnerBanner::forPost( 42 );

	expect( $html )->toContain( 'ink-wenner-banier--algehele' );
	expect( $html )->toContain( 'ink-gradering--silwer' );
} );

test( 'forPost returns empty for a work with no placement', function (): void {
	Functions\when( 'get_post_meta' )->justReturn( '' ); // placementFor → 0

	expect( WinnerBanner::forPost( 42 ) )->toBe( '' );
} );

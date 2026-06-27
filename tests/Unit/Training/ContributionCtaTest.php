<?php
/**
 * Unit tests for the community contribution CTA (Story 11.5, FR-56).
 *
 * Target: {@see \Ink\Training\ContributionCta}. The pure `toHtml()` (heading +
 * description + "Plaas 'n stuk" button) and the filterable `contributionUrl()`
 * (the `/skryf/` default + the `ink/opleiding_bydra_url` retarget) are testable
 * with WP mocked.
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Training;

use Ink\Training\ContributionCta;
use Brain\Monkey;
use Brain\Monkey\Functions;

beforeEach( function (): void {
	Monkey\setUp();
} );

afterEach( function (): void {
	Monkey\tearDown();
} );

// --- toHtml ---

test( 'toHtml renders the heading, description and the Plaas \'n stuk button with the supplied href', function (): void {
	Functions\when( 'esc_html__' )->returnArg( 1 );
	Functions\when( 'esc_url' )->returnArg( 1 );

	$html = ContributionCta::toHtml( '/skryf/' );

	expect( $html )->toContain( 'ink-opleiding-bydra' );
	expect( $html )->toContain( 'Het jy iets om te deel?' );
	expect( $html )->toContain( 'word deur ons gemeenskap geskryf' );
	expect( $html )->toContain( 'Plaas \'n stuk' );
	expect( $html )->toContain( 'href="/skryf/"' );
} );

test( 'toHtml escapes the contribution href through esc_url', function (): void {
	Functions\when( 'esc_html__' )->returnArg( 1 );
	Functions\expect( 'esc_url' )->once()->with( '/raw' )->andReturn( '/escaped' );

	$html = ContributionCta::toHtml( '/raw' );
	expect( $html )->toContain( 'href="/escaped"' );
} );

// --- contributionUrl ---

test( 'contributionUrl defaults to the Skryf flow and is filterable', function (): void {
	Functions\when( 'home_url' )->alias( static fn ( string $path = '' ): string => 'https://ink.test' . $path );
	// Default: the filter passes the value through unchanged.
	Functions\when( 'apply_filters' )->returnArg( 2 );

	expect( ContributionCta::contributionUrl() )->toBe( 'https://ink.test/skryf/' );
} );

test( 'contributionUrl honours the ink/opleiding_bydra_url filter (the single retarget point)', function (): void {
	Functions\when( 'home_url' )->alias( static fn ( string $path = '' ): string => 'https://ink.test' . $path );
	Functions\expect( 'apply_filters' )
		->once()
		->with( ContributionCta::URL_FILTER, 'https://ink.test/skryf/' )
		->andReturn( 'https://ink.test/dra-by/' );

	expect( ContributionCta::contributionUrl() )->toBe( 'https://ink.test/dra-by/' );
} );

test( 'contributionUrl falls back to the Skryf default when a misbehaving filter returns a non-string/empty (R11)', function (): void {
	Functions\when( 'home_url' )->alias( static fn ( string $path = '' ): string => 'https://ink.test' . $path );

	// null → fallback.
	Functions\when( 'apply_filters' )->justReturn( null );
	expect( ContributionCta::contributionUrl() )->toBe( 'https://ink.test/skryf/' );

	// empty string → fallback (no broken href).
	Functions\when( 'apply_filters' )->justReturn( '' );
	expect( ContributionCta::contributionUrl() )->toBe( 'https://ink.test/skryf/' );
} );

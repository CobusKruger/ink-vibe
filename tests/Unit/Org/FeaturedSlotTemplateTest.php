<?php
/**
 * Home featured-slot embed guardrail (Story 15.6, FR-50-R2).
 *
 * The Tuisblad hosts the winners announcement in a featured slot — the server-rendered
 * `ink/wenner-kollig` block (Ink\Challenges\FeaturedWinners) — between the Uitdaging
 * teaser and Uitgesoekte werke (page-map). Read off disk; no WordPress runtime needed.
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Org;

$ink_theme = static fn (): string => dirname( __DIR__, 3 ) . '/wp-content/themes/ink-foundation';

test( 'the Tuisblad embeds the winners featured slot before the featured-works grid', function () use ( $ink_theme ): void {
	$path = $ink_theme() . '/templates/front-page.html';
	expect( file_exists( $path ) )->toBeTrue();
	$markup = (string) file_get_contents( $path );

	// The featured-slot block is embedded.
	expect( $markup )->toContain( 'wp:ink/wenner-kollig' );

	// Page-map position: after the challenge teaser, before Uitgesoekte werke.
	$uitdaging = strpos( $markup, 'ink-foundation/huidige-uitdaging' );
	$slot      = strpos( $markup, 'wp:ink/wenner-kollig' );
	$featured  = strpos( $markup, 'ink-foundation/featured-grid' );

	expect( $uitdaging )->toBeLessThan( $slot );
	expect( $slot )->toBeLessThan( $featured );
} );

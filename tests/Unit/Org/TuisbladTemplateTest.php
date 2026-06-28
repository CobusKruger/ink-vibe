<?php
/**
 * Tuisblad (home page) structural guardrails (Story 15.1, FR-59).
 *
 * The Tuisblad is the FSE `front-page.html` template assembled from theme patterns:
 * an inline hero spotlight, then the `huidige-uitdaging`, `featured-grid`,
 * `borg-strook` and `cta-band` patterns within locked header/footer chrome. These
 * are read off disk and asserted on their block markup — no WordPress runtime needed.
 *
 * Non-vacuous: positive structural markers (chrome, the hero, real block content) are
 * asserted first, so a blank/missing file fails loudly rather than passing the embed
 * and ordering checks on emptiness.
 *
 * Presentation-only guard: the home challenge teaser is a STATIC entry-point (AC #2/#5),
 * so it must NOT embed a server-rendered `ink/` business-logic block.
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Org;

$ink_theme = static fn (): string => dirname( __DIR__, 3 ) . '/wp-content/themes/ink-foundation';

$ink_read = static function ( string $relative ) use ( $ink_theme ): string {
	$path = $ink_theme() . '/' . ltrim( $relative, '/' );
	expect( file_exists( $path ) )->toBeTrue( "missing file: {$relative}" );

	return (string) file_get_contents( $path );
};

test( 'the Tuisblad assembles hero, challenge, featured works, sponsors and CTA within locked chrome', function () use ( $ink_read ): void {
	$markup = $ink_read( 'templates/front-page.html' );

	// Non-vacuous: header/footer chrome + the inline hero spotlight are really present.
	expect( $markup )->toContain( 'wp:template-part' );                  // header/footer chrome
	expect( $markup )->toContain( 'wp:heading {"level":1' );             // hero <h1> spotlight

	// All four assembled sections are referenced (AC #1, #6).
	foreach ( array(
		'ink-foundation/huidige-uitdaging',
		'ink-foundation/featured-grid',
		'ink-foundation/borg-strook',
		'ink-foundation/cta-band',
	) as $slug ) {
		expect( $markup )->toContain( $slug );
	}
} );

test( 'the Tuisblad sections render in the required order: challenge -> featured -> sponsors -> CTA', function () use ( $ink_read ): void {
	$markup = $ink_read( 'templates/front-page.html' );

	$uitdaging = strpos( $markup, 'ink-foundation/huidige-uitdaging' );
	$featured  = strpos( $markup, 'ink-foundation/featured-grid' );
	$borge     = strpos( $markup, 'ink-foundation/borg-strook' );
	$cta       = strpos( $markup, 'ink-foundation/cta-band' );

	expect( $uitdaging )->toBeLessThan( $featured );
	expect( $featured )->toBeLessThan( $borge );
	expect( $borge )->toBeLessThan( $cta );
} );

test( 'the home challenge teaser is a static entry-point, not a business-logic surface', function () use ( $ink_read ): void {
	$markup = $ink_read( 'patterns/huidige-uitdaging.php' );

	// Non-vacuous: the teaser is real (a heading + a button to the archive).
	expect( $markup )->toContain( 'wp:heading' );
	expect( $markup )->toContain( '/uitdagings' );

	// Presentation-only (three-layer): NO server-rendered ink-core block, NO WP_Query.
	expect( $markup )->not->toContain( 'wp:ink/' );
	expect( $markup )->not->toContain( 'WP_Query' );
} );

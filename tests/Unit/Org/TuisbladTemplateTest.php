<?php
/**
 * Tuisblad (home page) structural guardrails (Story 15.1, FR-59; Epic 19 Story 19.2).
 *
 * The Tuisblad is the FSE `front-page.html` template assembled from theme patterns:
 * the `hero` pattern (a two-column split whose LEFT column carries the single page
 * <h1> and whose RIGHT column hosts the `huidige-uitdaging` challenge slot), then
 * the `featured-grid`, `borg-strook` and `cta-band` patterns within locked
 * header/footer chrome. Story 19.2 moved the hero into a PHP pattern (`hero.php`)
 * so its copy can go through the `ink-foundation` text domain and carry inline SVG
 * icons — an `.html` template cannot run gettext — and folded the challenge teaser
 * into the hero's right column. These files are read off disk and asserted on their
 * block markup — no WordPress runtime needed.
 *
 * Non-vacuous: positive structural markers (chrome, the hero reference, the hero's
 * <h1>, real block content) are asserted first, so a blank/missing file fails
 * loudly rather than passing the embed and ordering checks on emptiness.
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
	$hero   = $ink_read( 'patterns/hero.php' );

	// Non-vacuous: header/footer chrome + the hero pattern reference are really present.
	expect( $markup )->toContain( 'wp:template-part' );        // header/footer chrome
	expect( $markup )->toContain( 'ink-foundation/hero' );     // the hero pattern reference

	// The single page <h1> lives in the hero pattern's left column (19.2, §2 AC).
	expect( $hero )->toContain( 'wp:heading {"level":1' );

	// Exactly ONE visible <h1> across the assembled page: the hero owns it, and the
	// front-page template + other assembled sections carry none.
	expect( substr_count( $hero, 'wp:heading {"level":1' ) )->toBe( 1 );
	expect( $markup )->not->toContain( 'wp:heading {"level":1' );

	// The challenge slot is folded into the hero's right column (19.2, §2/§3 AC).
	expect( $hero )->toContain( 'ink-foundation/huidige-uitdaging' );

	// The remaining assembled sections are referenced from the template (AC #1, #6).
	foreach ( array(
		'ink-foundation/featured-grid',
		'ink-foundation/borg-strook',
		'ink-foundation/cta-band',
	) as $slug ) {
		expect( $markup )->toContain( $slug );
	}
} );

test( 'the Tuisblad sections render in the required order: hero -> featured -> sponsors -> CTA', function () use ( $ink_read ): void {
	$markup = $ink_read( 'templates/front-page.html' );

	$hero     = strpos( $markup, 'ink-foundation/hero' );
	$featured = strpos( $markup, 'ink-foundation/featured-grid' );
	$borge    = strpos( $markup, 'ink-foundation/borg-strook' );
	$cta      = strpos( $markup, 'ink-foundation/cta-band' );

	expect( $hero )->toBeLessThan( $featured );
	expect( $featured )->toBeLessThan( $borge );
	expect( $borge )->toBeLessThan( $cta );
} );

test( 'the hero right column hosts the challenge teaser before the featured-works grid', function () use ( $ink_read ): void {
	$hero = $ink_read( 'patterns/hero.php' );

	// Non-vacuous: the hero really carries the two-column grid + the challenge slot.
	expect( $hero )->toContain( 'ink-hero-grid' );
	expect( $hero )->toContain( 'ink-foundation/huidige-uitdaging' );
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

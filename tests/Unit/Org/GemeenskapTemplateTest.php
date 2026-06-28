<?php
/**
 * Gemeenskap (community marketing page) structural guardrails (Story 15.2, FR-60).
 *
 * The Gemeenskap page is the slug-based FSE `page-gemeenskap.html` template (thin
 * wrapper) embedding the `gemeenskap.php` content pattern within locked header/footer
 * chrome. Read off disk and asserted on block markup — no WordPress runtime needed.
 *
 * Non-vacuous: chrome + real section headings are asserted first, so a blank/missing
 * file fails loudly rather than passing the embed and content checks on emptiness.
 *
 * Presentation-only guard: a static conversion page must NOT embed a server-rendered
 * `ink/` business-logic block or a WP_Query (the live-stats / spotlight surfaces are
 * deferred, AC #3).
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

test( 'the Gemeenskap template embeds the content pattern within locked chrome', function () use ( $ink_read ): void {
	$markup = $ink_read( 'templates/page-gemeenskap.html' );

	expect( $markup )->toContain( 'wp:template-part' );        // header/footer chrome
	expect( $markup )->toContain( 'ink-foundation/gemeenskap' ); // references the content pattern
} );

test( 'the Gemeenskap pattern presents value props, how-it-works, principles and CTAs', function () use ( $ink_read ): void {
	$markup = $ink_read( 'patterns/gemeenskap.php' );

	// Non-vacuous: the hero spotlight is really present.
	expect( $markup )->toContain( 'wp:heading {"level":1' );

	// The four AC sections (FR-60: value props, principles, how-it-works, CTAs).
	expect( $markup )->toContain( 'Vir skrywers' );             // value props
	expect( $markup )->toContain( 'Vir lesers' );               // value props
	expect( $markup )->toContain( 'Hoe INK werk' );             // how-it-works
	expect( $markup )->toContain( 'Gemeenskapsbeginsels' );     // principles
	expect( $markup )->toContain( 'Gereed om by INK aan te sluit?' ); // closing CTA

	// CTAs route to real pages.
	expect( $markup )->toContain( '/registreer' );
} );

test( 'the Gemeenskap page is presentation-only (no business logic)', function () use ( $ink_read ): void {
	$markup = $ink_read( 'patterns/gemeenskap.php' );

	expect( $markup )->not->toContain( 'wp:ink/' );   // no server-rendered block
	expect( $markup )->not->toContain( 'WP_Query' );  // no query in the theme
} );

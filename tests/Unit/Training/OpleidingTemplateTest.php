<?php
/**
 * Opleiding archive/single structural guardrails (Story 11.1, FR-54).
 *
 * The Opleiding surfaces are FSE `archive-opleiding_artikel.html` /
 * `single-opleiding_artikel.html` templates + `opleiding.php` / `reading-opleiding.php`
 * patterns that embed the server-rendered `ink/opleiding-argief` block and the core
 * reading blocks. These are read off disk and asserted on their block markup — no
 * WordPress runtime needed.
 *
 * Non-vacuous: positive structural markers (header/footer parts, the pattern
 * references) are asserted first, so a blank/missing file fails loudly rather than
 * passing the embed check on emptiness.
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Training;

$ink_theme = static fn (): string => dirname( __DIR__, 3 ) . '/wp-content/themes/ink-foundation';

$ink_read = static function ( string $relative ) use ( $ink_theme ): string {
	$path = $ink_theme() . '/' . ltrim( $relative, '/' );
	expect( file_exists( $path ) )->toBeTrue( "missing file: {$relative}" );

	return (string) file_get_contents( $path );
};

test( 'the Opleiding archive template embeds the opleiding pattern within locked chrome', function () use ( $ink_read ): void {
	$markup = $ink_read( 'templates/archive-opleiding_artikel.html' );

	expect( $markup )->toContain( 'wp:template-part' );          // header/footer chrome
	expect( $markup )->toContain( 'ink-foundation/opleiding' );   // references the hub pattern
} );

test( 'the Opleiding pattern embeds the server-rendered hub block and routes its label through the bridge', function () use ( $ink_read ): void {
	$markup = $ink_read( 'patterns/opleiding.php' );

	// Label read from the terminology registry bridge (single-source, not a bare literal).
	expect( $markup )->toContain( "ink_foundation_term( 'opleiding'" );
	// The hub itself is the server-rendered ink-core block.
	expect( $markup )->toContain( 'wp:ink/opleiding-argief' );
} );

test( 'the single opleiding_artikel template embeds the reading pattern within locked chrome', function () use ( $ink_read ): void {
	$markup = $ink_read( 'templates/single-opleiding_artikel.html' );

	expect( $markup )->toContain( 'wp:template-part' );                  // header/footer chrome
	expect( $markup )->toContain( 'ink-foundation/reading-opleiding' );   // references the reading pattern
} );

test( 'the reading pattern carries the eyebrow label via the bridge plus title/author/content blocks', function () use ( $ink_read ): void {
	$markup = $ink_read( 'patterns/reading-opleiding.php' );

	// Eyebrow label from the terminology registry bridge (single-source).
	expect( $markup )->toContain( "ink_foundation_term( 'opleiding_artikel'" );
	// Core reading blocks.
	expect( $markup )->toContain( 'wp:post-title' );
	expect( $markup )->toContain( 'wp:post-content' );
} );

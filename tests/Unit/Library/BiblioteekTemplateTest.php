<?php
/**
 * Biblioteek archive/single structural guardrails (Story 10.1, FR-52).
 *
 * The Biblioteek surfaces are FSE `archive-biblioteek_item.html` /
 * `single-biblioteek_item.html` templates + `biblioteek.php` / `reading-biblioteek.php`
 * patterns that embed the server-rendered `ink/biblioteek-argief` block and the
 * core reading blocks. These are read off disk and asserted on their block markup —
 * no WordPress runtime needed.
 *
 * Non-vacuous: positive structural markers (header/footer parts, the pattern
 * references) are asserted first, so a blank/missing file fails loudly rather than
 * passing the embed check on emptiness.
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Library;

$ink_theme = static fn (): string => dirname( __DIR__, 3 ) . '/wp-content/themes/ink-foundation';

$ink_read = static function ( string $relative ) use ( $ink_theme ): string {
	$path = $ink_theme() . '/' . ltrim( $relative, '/' );
	expect( file_exists( $path ) )->toBeTrue( "missing file: {$relative}" );

	return (string) file_get_contents( $path );
};

test( 'the Biblioteek archive template embeds the biblioteek pattern within locked chrome', function () use ( $ink_read ): void {
	$markup = $ink_read( 'templates/archive-biblioteek_item.html' );

	expect( $markup )->toContain( 'wp:template-part' );          // header/footer chrome
	expect( $markup )->toContain( 'ink-foundation/biblioteek' );  // references the archive pattern
} );

test( 'the Biblioteek pattern embeds the server-rendered archive block and routes its label through the bridge', function () use ( $ink_read ): void {
	$markup = $ink_read( 'patterns/biblioteek.php' );

	// Label read from the terminology registry bridge (single-source, not a bare literal).
	expect( $markup )->toContain( "ink_foundation_term( 'biblioteek'" );
	// The archive itself is the server-rendered ink-core block.
	expect( $markup )->toContain( 'wp:ink/biblioteek-argief' );
} );

test( 'the single biblioteek_item template embeds the reading pattern within locked chrome', function () use ( $ink_read ): void {
	$markup = $ink_read( 'templates/single-biblioteek_item.html' );

	expect( $markup )->toContain( 'wp:template-part' );                   // header/footer chrome
	expect( $markup )->toContain( 'ink-foundation/reading-biblioteek' );   // references the reading pattern
} );

test( 'the reading pattern carries the eyebrow label via the bridge plus title/author/content blocks', function () use ( $ink_read ): void {
	$markup = $ink_read( 'patterns/reading-biblioteek.php' );

	// Eyebrow label from the terminology registry bridge (single-source).
	expect( $markup )->toContain( "ink_foundation_term( 'biblioteek_item'" );
	// Core reading blocks.
	expect( $markup )->toContain( 'wp:post-title' );
	expect( $markup )->toContain( 'wp:post-content' );
} );

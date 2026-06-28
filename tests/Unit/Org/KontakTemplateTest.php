<?php
/**
 * Kontak (contact page) structural guardrails (Story 15.4, FR-61).
 *
 * The Kontak page is the slug-based FSE `page-kontak.html` template (thin wrapper)
 * embedding the `kontak.php` content pattern, which in turn embeds the server-rendered
 * `ink/kontak-vorm` block (Ink\Forms\ContactForm — the custom ink-core form, NOT
 * CF7/Fluent). Read off disk and asserted on block markup — no WordPress runtime.
 *
 * Non-vacuous: chrome + the hero are asserted first, so a blank/missing file fails
 * loudly rather than passing the embed check on emptiness.
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

test( 'the Kontak template embeds the content pattern within locked chrome', function () use ( $ink_read ): void {
	$markup = $ink_read( 'templates/page-kontak.html' );

	expect( $markup )->toContain( 'wp:template-part' );      // header/footer chrome
	expect( $markup )->toContain( 'ink-foundation/kontak' );  // references the content pattern
} );

test( 'the Kontak pattern embeds the custom ink-core form block (the three-layer seam)', function () use ( $ink_read ): void {
	$markup = $ink_read( 'patterns/kontak.php' );

	// Non-vacuous: the hero is really present.
	expect( $markup )->toContain( 'wp:heading {"level":1' );

	// The custom ink-core form block — NOT a third-party form plugin.
	expect( $markup )->toContain( 'wp:ink/kontak-vorm' );
} );

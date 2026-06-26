<?php
/**
 * Ontdek hub structural guardrails (Story 8.1, FR-32).
 *
 * The hub is an FSE `page-ontdek.html` template + an `ontdek.php` pattern that
 * embeds the server-rendered `ink/ontdek-werke` works-archive block. These are
 * read off disk and asserted on their block markup — no WordPress runtime needed.
 *
 * Non-vacuous: positive structural markers (header/footer parts, the archive
 * pattern reference) are asserted first, so a blank/missing file fails loudly
 * rather than passing the embed check on emptiness.
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Discovery;

$ink_theme = static fn (): string => dirname( __DIR__, 3 ) . '/wp-content/themes/ink-foundation';

$ink_read = static function ( string $relative ) use ( $ink_theme ): string {
	$path = $ink_theme() . '/' . ltrim( $relative, '/' );
	expect( file_exists( $path ) )->toBeTrue( "missing file: {$relative}" );

	return (string) file_get_contents( $path );
};

test( 'the Ontdek page template embeds the ontdek pattern within locked chrome', function () use ( $ink_read ): void {
	$markup = $ink_read( 'templates/page-ontdek.html' );

	expect( $markup )->toContain( 'wp:template-part' );            // header/footer chrome
	expect( $markup )->toContain( 'ink-foundation/ontdek' );        // references the hub pattern
} );

test( 'the Ontdek pattern carries the Bydraes/Skrywers tab scaffold and embeds the works-archive block', function () use ( $ink_read ): void {
	$markup = $ink_read( 'patterns/ontdek.php' );

	// Positive structural markers (make the embed check non-vacuous).
	expect( $markup )->toContain( 'ink-foundation/archive-intro' ); // hub intro
	expect( $markup )->toContain( 'is-style-pill' );                 // tab scaffold

	// Tab labels read from the terminology registry bridge (single-source, not bare literals).
	expect( $markup )->toContain( "ink_foundation_term( 'bydrae_plural'" );
	expect( $markup )->toContain( "ink_foundation_term( 'skrywer_plural'" );

	// The works archive is the server-rendered ink-core block.
	expect( $markup )->toContain( 'wp:ink/ontdek-werke' );
	// Story 8.3: the Skrywers tab embeds the skrywers server block.
	expect( $markup )->toContain( 'wp:ink/ontdek-skrywers' );
	// Story 8.4: the search block sits at the top of the hub.
	expect( $markup )->toContain( 'wp:ink/ontdek-soek' );
	// Story 8.5: the personalised discovery surfaces.
	expect( $markup )->toContain( 'wp:ink/ontdek-vlakke' );
} );

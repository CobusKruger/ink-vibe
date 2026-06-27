<?php
/**
 * Uitdaging single-page structural guardrails (Story 12.1, FR-45).
 *
 * The challenge single surface is the FSE `single-uitdaging.html` template +
 * `reading-uitdaging.php` pattern that embeds the server-rendered
 * `ink/uitdaging-besonderhede` block and core reading blocks. Read off disk and
 * asserted on block markup — no WordPress runtime needed. Mirrors the Library
 * BiblioteekTemplateTest precedent.
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Challenges;

$ink_theme = static fn (): string => dirname( __DIR__, 3 ) . '/wp-content/themes/ink-foundation';

$ink_read = static function ( string $relative ) use ( $ink_theme ): string {
	$path = $ink_theme() . '/' . ltrim( $relative, '/' );
	expect( file_exists( $path ) )->toBeTrue( "missing file: {$relative}" );

	return (string) file_get_contents( $path );
};

test( 'the single uitdaging template embeds the reading pattern within locked chrome', function () use ( $ink_read ): void {
	$markup = $ink_read( 'templates/single-uitdaging.html' );

	expect( $markup )->toContain( 'wp:template-part' );                 // header/footer chrome
	expect( $markup )->toContain( 'ink-foundation/reading-uitdaging' ); // references the reading pattern
} );

test( 'the reading-uitdaging pattern carries the eyebrow label via the bridge plus title/content and the besonderhede block', function () use ( $ink_read ): void {
	$markup = $ink_read( 'patterns/reading-uitdaging.php' );

	// Eyebrow label from the terminology registry bridge (single-source, not a bare literal).
	expect( $markup )->toContain( "ink_foundation_term( 'uitdaging'" );
	// Core reading blocks (the editorial brief: prompt, devices, rules, prize, resources).
	expect( $markup )->toContain( 'wp:post-title' );
	expect( $markup )->toContain( 'wp:post-content' );
	// The server-rendered deadline/status + entries-list block.
	expect( $markup )->toContain( 'wp:ink/uitdaging-besonderhede' );
} );

test( 'the uitdaging archive template embeds the list pattern within locked chrome', function () use ( $ink_read ): void {
	$markup = $ink_read( 'templates/archive-uitdaging.html' );

	expect( $markup )->toContain( 'wp:template-part' );        // header/footer chrome
	expect( $markup )->toContain( 'ink-foundation/uitdaging' ); // references the archive pattern
} );

test( 'the uitdaging archive pattern routes its heading via the bridge and embeds the server-rendered list block', function () use ( $ink_read ): void {
	$markup = $ink_read( 'patterns/uitdaging.php' );

	// Heading from the terminology registry bridge (single-source, plural form).
	expect( $markup )->toContain( "ink_foundation_term( 'uitdaging_plural'" );
	// The list itself is the server-rendered ink-core block.
	expect( $markup )->toContain( 'wp:ink/uitdaging-argief' );
} );

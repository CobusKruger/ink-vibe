<?php
/**
 * InkPols archive + single-issue structural guardrails (Story 13.2, FR-57).
 *
 * The InkPols surfaces are the FSE `archive-inkpols_uitgawe.html` /
 * `single-inkpols_uitgawe.html` templates + the `inkpols.php` / `reading-inkpols.php`
 * patterns that embed the server-rendered `ink/inkpols-argief` and
 * `ink/inkpols-besonderhede` blocks. Read off disk and asserted on block markup —
 * no WordPress runtime needed. Mirrors the Challenges UitdagingTemplateTest precedent.
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\InkPols;

$ink_theme = static fn (): string => dirname( __DIR__, 3 ) . '/wp-content/themes/ink-foundation';

$ink_read = static function ( string $relative ) use ( $ink_theme ): string {
	$path = $ink_theme() . '/' . ltrim( $relative, '/' );
	expect( file_exists( $path ) )->toBeTrue( "missing file: {$relative}" );

	return (string) file_get_contents( $path );
};

test( 'the inkpols archive template embeds the archive pattern within locked chrome', function () use ( $ink_read ): void {
	$markup = $ink_read( 'templates/archive-inkpols_uitgawe.html' );

	expect( $markup )->toContain( 'wp:template-part' );      // header/footer chrome
	expect( $markup )->toContain( 'ink-foundation/inkpols' ); // references the archive pattern
} );

test( 'the inkpols archive pattern routes its heading via the bridge and embeds the server-rendered list block', function () use ( $ink_read ): void {
	$markup = $ink_read( 'patterns/inkpols.php' );

	// Heading from the terminology registry bridge (single-source brand label).
	expect( $markup )->toContain( "ink_foundation_term( 'inkpols'" );
	// The archive itself is the server-rendered ink-core block.
	expect( $markup )->toContain( 'wp:ink/inkpols-argief' );
} );

test( 'the single inkpols template embeds the reading pattern within locked chrome', function () use ( $ink_read ): void {
	$markup = $ink_read( 'templates/single-inkpols_uitgawe.html' );

	expect( $markup )->toContain( 'wp:template-part' );
	expect( $markup )->toContain( 'ink-foundation/reading-inkpols' );
} );

test( 'the reading-inkpols pattern carries the eyebrow label via the bridge plus title/content and the besonderhede block', function () use ( $ink_read ): void {
	$markup = $ink_read( 'patterns/reading-inkpols.php' );

	// Eyebrow label from the terminology registry bridge (single-source, not a bare literal).
	expect( $markup )->toContain( "ink_foundation_term( 'inkpols_uitgawe'" );
	// Core reading blocks.
	expect( $markup )->toContain( 'wp:post-title' );
	expect( $markup )->toContain( 'wp:post-content' );
	// The server-rendered issue-metadata block.
	expect( $markup )->toContain( 'wp:ink/inkpols-besonderhede' );
} );

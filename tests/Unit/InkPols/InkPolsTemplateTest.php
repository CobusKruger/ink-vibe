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

test( 'the inkpols archive pattern embeds the server-rendered block, which emits its own bands', function () use ( $ink_read ): void {
	expect( $ink_read( 'patterns/inkpols.php' ) )->toContain( 'wp:ink/inkpols-argief' );
} );

test( 'the single inkpols template embeds the reading pattern within locked chrome', function () use ( $ink_read ): void {
	$markup = $ink_read( 'templates/single-inkpols_uitgawe.html' );

	expect( $markup )->toContain( 'wp:template-part' );
	expect( $markup )->toContain( 'ink-foundation/reading-inkpols' );
} );

test( 'the reading-inkpols pattern has bar, flipbook and contents, and no body section', function () use ( $ink_read ): void {
	$markup = $ink_read( 'patterns/reading-inkpols.php' );

	expect( strpos( $markup, 'wp:ink/inkpols-besonderhede' ) )->toBeLessThan( strpos( $markup, 'wp:ink/inkpols-leser' ) );
	expect( strpos( $markup, 'wp:ink/inkpols-leser' ) )->toBeLessThan( strpos( $markup, 'wp:ink/inkpols-inhoud' ) );
	expect( $markup )->not->toContain( 'wp:post-content' );
} );

test( 'theme.json holds no ink-inkpols rules any more', function () use ( $ink_read ): void {
	expect( $ink_read( 'theme.json' ) )->not->toContain( 'ink-inkpols' );
} );

test( 'the page sheet gate covers the archive, the single and the front page', function () use ( $ink_read ): void {
	$functions = $ink_read( 'functions.php' );

	expect( $functions )->toContain( "is_post_type_archive( 'inkpols_uitgawe' )" );
	expect( $functions )->toContain( "! is_singular( 'inkpols_uitgawe' ) && ! is_front_page()" );
} );

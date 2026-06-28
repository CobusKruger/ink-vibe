<?php
/**
 * Footer / social-links structural guardrails (Story 15.5, FR-62).
 *
 * The footer template part (`template-parts/footer.html`) embeds the
 * `ink-foundation/footer-main` pattern. This story adds a theme-native social-links
 * section using the WordPress core `social-links` / `social-link` blocks — the
 * sanctioned replacement for the retired "Ultimate Social Media Icons" plugin. Read
 * off disk and asserted on block markup — no WordPress runtime needed.
 *
 * Non-vacuous: the footer's real existing content is asserted first, so a blank/missing
 * file fails loudly rather than passing the social-block checks on emptiness.
 *
 * Guard: the footer must NOT reference any legacy social-icon plugin handle.
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

test( 'the footer part embeds the footer-main pattern', function () use ( $ink_read ): void {
	$markup = $ink_read( 'template-parts/footer.html' );

	expect( $markup )->toContain( 'ink-foundation/footer-main' );
} );

test( 'the footer renders theme-native core social links (replacing the legacy plugin)', function () use ( $ink_read ): void {
	$markup = $ink_read( 'patterns/footer-main.php' );

	// Non-vacuous: the real footer content is present.
	expect( $markup )->toContain( 'Ondersteun ons' );          // an existing link column
	expect( $markup )->toContain( 'Volg ons' );                // the new social heading

	// WordPress core social blocks — theme-native.
	expect( $markup )->toContain( 'wp:social-links' );
	expect( $markup )->toContain( '"service":"facebook"' );
	expect( $markup )->toContain( '"service":"instagram"' );
	expect( $markup )->toContain( '"service":"x"' );
} );

test( 'the footer does not reuse a legacy social-icon plugin', function () use ( $ink_read ): void {
	$markup = $ink_read( 'patterns/footer-main.php' );

	// The retired "Ultimate Social Media Icons" plugin shortcode/handle must be gone.
	expect( strtolower( $markup ) )->not->toContain( 'ultimate' );
	expect( $markup )->not->toContain( '[ssba' );        // legacy social-icon shortcode family
	expect( $markup )->not->toContain( 'usm_premium' );  // legacy plugin handle
} );

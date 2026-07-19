<?php
/**
 * Footer structural guardrails (Story 15.5 social links, FR-62; Story 19.5 §10 rework).
 *
 * The footer template part (`parts/footer.html` — renamed from the legacy
 * `template-parts/` tree) embeds the `ink-foundation/footer-main` pattern. Story 19.5
 * reworked that pattern to the §10 4-column layout (brand+blurb / Ontdek / Gemeenskap /
 * Ondersteun ons) with a bottom bar, while KEEPING the Story-15.5 theme-native
 * social-links section (WordPress core `social-links` / `social-link` blocks — the
 * sanctioned replacement for the retired "Ultimate Social Media Icons" plugin). Read
 * off disk and asserted on block markup — no WordPress runtime needed.
 *
 * Non-vacuous: the footer's real 4-column content is asserted first, so a blank/missing
 * file fails loudly rather than passing the social-block / org-copy checks on emptiness.
 *
 * Guards: the footer must NOT reference any legacy social-icon plugin handle, and its
 * org copy must use Afrikaans placeholders — never US "501(c)(3)" wording.
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
	$markup = $ink_read( 'parts/footer.html' );

	expect( $markup )->toContain( 'ink-foundation/footer-main' );
} );

test( 'the footer is a 4-column layout with the brand column and the three link groups (§10)', function () use ( $ink_read ): void {
	$markup = $ink_read( 'patterns/footer-main.php' );

	// Non-vacuous: the real 4-column footer content is present.
	expect( $markup )->toContain( 'is-style-ink-footer' );        // the site-wide footer treatment.
	expect( $markup )->toContain( 'ink-footer-kolomme' );         // the 4-column grid container.
	expect( $markup )->toContain( 'ink-footer-handelsmerk' );     // the brand + blurb column.
	expect( $markup )->toContain( 'Ontdek' );                     // link group 1.
	expect( $markup )->toContain( 'Gemeenskap' );                 // link group 2.
	expect( $markup )->toContain( 'Ondersteun ons' );             // link group 3.
	expect( $markup )->toContain( 'ink-footer-onderbalk' );       // the bottom bar.
	expect( $markup )->toContain( 'ink-footer-hart__ikoon' );     // the filled-terracotta heart.
} );

test( 'the footer renders theme-native core social links (replacing the legacy plugin)', function () use ( $ink_read ): void {
	$markup = $ink_read( 'patterns/footer-main.php' );

	// Non-vacuous: the real footer content is present.
	expect( $markup )->toContain( 'Ondersteun ons' );          // an existing link column.

	// WordPress core social blocks — theme-native.
	expect( $markup )->toContain( 'wp:social-links' );
	expect( $markup )->toContain( '"service":"facebook"' );
	expect( $markup )->toContain( '"service":"instagram"' );
	expect( $markup )->toContain( '"service":"x"' );
} );

test( 'the footer org copy uses Afrikaans placeholders — never US 501(c)(3) wording', function () use ( $ink_read ): void {
	$markup = $ink_read( 'patterns/footer-main.php' );

	// Non-vacuous: the Afrikaans org copy + the stigtingsjaar placeholder are present.
	expect( $markup )->toContain( 'Niewinsgerigte gemeenskapsorganisasie' );
	expect( $markup )->toContain( '[stigtingsjaar]' );

	// Guard: no US non-profit wording reaches the front end.
	expect( $markup )->not->toContain( '501(c)(3)' );
	expect( $markup )->not->toContain( '501(c)' );
} );

test( 'the footer does not reuse a legacy social-icon plugin', function () use ( $ink_read ): void {
	$markup = $ink_read( 'patterns/footer-main.php' );

	// The retired "Ultimate Social Media Icons" plugin shortcode/handle must be gone.
	expect( strtolower( $markup ) )->not->toContain( 'ultimate' );
	expect( $markup )->not->toContain( '[ssba' );        // legacy social-icon shortcode family
	expect( $markup )->not->toContain( 'usm_premium' );  // legacy plugin handle
} );

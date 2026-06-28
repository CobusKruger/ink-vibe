<?php
/**
 * Oor INK (about page) structural guardrails (Story 15.3, FR-60).
 *
 * The Oor INK page is the slug-based FSE `page-oor-ink.html` template (thin wrapper)
 * embedding the `oor-ink.php` content pattern within locked header/footer chrome. It
 * assembles static mission/about prose, a contact CTA, the already-built
 * `borg-erkenning` sponsor section, and org-page links. Read off disk and asserted on
 * block markup — no WordPress runtime needed.
 *
 * Non-vacuous: chrome + the mission H1 are asserted first, so a blank/missing file
 * fails loudly rather than passing the embed and content guards on emptiness.
 *
 * Org-detail guard (Story 17.1, AC #2): the org placeholders are RESOLVED — founding
 * year 2018 is applied, the legal status uses the confirmed generic non-profit framing
 * with no legal-registration detail, the `[stigtingsjaar]`/`[regstatus]` markers are
 * gone, and no US "501(c)(3)" wording leaks.
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

test( 'the Oor INK template embeds the content pattern within locked chrome', function () use ( $ink_read ): void {
	$markup = $ink_read( 'templates/page-oor-ink.html' );

	expect( $markup )->toContain( 'wp:template-part' );      // header/footer chrome
	expect( $markup )->toContain( 'ink-foundation/oor-ink' ); // references the content pattern
} );

test( 'the Oor INK pattern shows mission, contact, sponsors and org pages', function () use ( $ink_read ): void {
	$markup = $ink_read( 'patterns/oor-ink.php' );

	// Non-vacuous: the mission spotlight is really present.
	expect( $markup )->toContain( 'wp:heading {"level":1' );
	expect( $markup )->toContain( 'Ons missie' );                 // mission

	// Contact (AC #1 + #5: CTA resolves to the 15.4 Kontak page).
	expect( $markup )->toContain( '/kontak' );

	// Sponsors: embeds the already-built borg-erkenning section (the three-layer seam).
	expect( $markup )->toContain( 'ink-foundation/borg-erkenning' );

	// Org pages links.
	expect( $markup )->toContain( '/gemeenskap' );
} );

test( 'the Oor INK page has resolved org details and never US legal wording (Story 17.1)', function () use ( $ink_read ): void {
	$markup = $ink_read( 'patterns/oor-ink.php' );

	// Org placeholders are RESOLVED, not present (AC #2).
	expect( $markup )->not->toContain( '[stigtingsjaar]' );
	expect( $markup )->not->toContain( '[regstatus]' );

	// Founding year 2018 is applied, with the generic non-profit framing.
	expect( $markup )->toContain( '2018' );
	expect( $markup )->toContain( 'niewinsgerigte gemeenskapsorganisasie' );

	// No legal-registration detail leaks; US nonprofit wording must NEVER appear.
	expect( $markup )->not->toContain( 'Regstatus:' );
	expect( $markup )->not->toContain( '501(c)(3)' );
	expect( $markup )->not->toContain( '501(c)' );
} );

<?php
/**
 * Unit tests for the homepage sponsor strip block (Story 14.3, FR-58; §7 chips 19.5).
 *
 * Target: {@see \Ink\Sponsors\HomepageStrip} — the `ink/borg-strook` server block.
 * The pure `toHtml()` renderer is unit-testable with WordPress mocked: it must
 * COLLAPSE (return '') with no active sponsor, and otherwise render the eyebrow +
 * heading + intro + CTA and ONE per-tier chip per active sponsor (the tier drives the
 * `ink-borg-strook__chip--{goud|silwer|brons}` modifier), preferring the external link,
 * carrying alt text on logos, and falling back to the name when there is no logo.
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Sponsors;

use Ink\Sponsors\HomepageStrip;
use Ink\Sponsors\Sponsor;
use Brain\Monkey;
use Brain\Monkey\Functions;

beforeEach( function (): void {
	Monkey\setUp();
	// The eyebrow/heading/intro/CTA come from the Terms registry; __() returns its arg.
	Functions\when( '__' )->returnArg( 1 );
	Functions\when( 'esc_url' )->returnArg( 1 );
	Functions\when( 'esc_attr' )->returnArg( 1 );
	Functions\when( 'esc_html' )->returnArg( 1 );
	Functions\when( 'home_url' )->alias( static fn ( string $path = '' ) => 'https://ink.test' . $path );
} );

afterEach( function (): void {
	Monkey\tearDown();
} );

test( 'the block name is the single-source constant', function (): void {
	expect( HomepageStrip::BLOCK )->toBe( 'ink/borg-strook' );
} );

// --- tier classification (pure, ink-core-owned) ---

test( 'tierSlug maps the stored borgvlak to a canonical tier slug (case-insensitive)', function (): void {
	expect( HomepageStrip::tierSlug( 'Goud' ) )->toBe( 'goud' );
	expect( HomepageStrip::tierSlug( 'silwer' ) )->toBe( 'silwer' );
	expect( HomepageStrip::tierSlug( ' BRONS ' ) )->toBe( 'brons' );
} );

test( 'tierSlug degrades an unknown or empty tier to brons (the neutral default chip)', function (): void {
	expect( HomepageStrip::tierSlug( '' ) )->toBe( 'brons' );
	expect( HomepageStrip::tierSlug( 'platinum' ) )->toBe( 'brons' );
} );

// --- collapse ---

test( 'toHtml COLLAPSES to an empty string when there is no active sponsor', function (): void {
	expect( HomepageStrip::toHtml( array() ) )->toBe( '' );
} );

// --- copy renders when there are sponsors ---

test( 'toHtml renders the eyebrow, heading, intro and CTA (to the contact page) when sponsors are active', function (): void {
	Functions\when( 'get_post_thumbnail_id' )->justReturn( 0 );

	$html = HomepageStrip::toHtml( array( new Sponsor( 1, 'Protea', 'https://protea.test', 'goud', '', '', '' ) ) );

	expect( $html )->toContain( 'Ons borge' );                       // eyebrow.
	expect( $html )->toContain( 'Moontlik gemaak deur' );            // heading.
	expect( $html )->toContain( 'gulhartigheid van ons borge' );     // intro (fragment).
	expect( $html )->toContain( "Word 'n borg" );                    // CTA label.
	expect( $html )->toContain( 'href="https://ink.test/kontak"' );  // CTA → contact page.
} );

// --- one per-tier chip per active sponsor ---

test( 'toHtml renders one per-tier chip per active sponsor with the tier modifier class', function (): void {
	Functions\when( 'get_post_thumbnail_id' )->justReturn( 99 );
	Functions\when( 'wp_get_attachment_image_url' )->justReturn( 'https://ink.test/logo.png' );

	$sponsors = array(
		new Sponsor( 1, 'Protea', 'https://protea.test', 'Goud', '', '', '' ),
		new Sponsor( 2, 'NB', 'https://nb.test', 'Silwer', '', '', '' ),
		new Sponsor( 3, 'Kwela', 'https://kwela.test', '', '', '', '' ),
	);

	$html = HomepageStrip::toHtml( $sponsors );

	expect( $html )->toContain( 'ink-borg-strook__rooster' );
	expect( substr_count( $html, 'ink-borg-strook__chip ' ) )->toBe( 3 );
	expect( $html )->toContain( 'ink-borg-strook__chip--goud' );
	expect( $html )->toContain( 'ink-borg-strook__chip--silwer' );
	expect( $html )->toContain( 'ink-borg-strook__chip--brons' ); // untiered Kwela → brons.
	// hover:scale-105 is applied via the reduced-motion-safe helper class.
	expect( substr_count( $html, 'ink-hover-scale' ) )->toBe( 3 );
	// External links preferred, opened safely.
	expect( $html )->toContain( 'href="https://protea.test"' );
	expect( $html )->toContain( 'rel="noopener sponsored"' );
} );

// --- logo alt text + name fallback ---

test( 'toHtml gives each logo alt text (the sponsor name) and falls back to the name when there is no logo', function (): void {
	// The thumbnail id mirrors the post id so each sponsor resolves independently.
	Functions\when( 'get_post_thumbnail_id' )->alias( static fn ( int $post_id ) => $post_id );
	Functions\when( 'wp_get_attachment_image_url' )->alias(
		static fn ( int $id ) => 1 === $id ? 'https://ink.test/protea.png' : ''
	);
	Functions\when( 'get_permalink' )->justReturn( false );

	$sponsors = array(
		new Sponsor( 1, 'Protea', '', 'goud', '', '', '' ),   // has logo.
		new Sponsor( 2, 'Kwela', '', 'brons', '', '', '' ),   // no logo → name.
	);

	$html = HomepageStrip::toHtml( $sponsors );

	expect( $html )->toContain( 'src="https://ink.test/protea.png"' );
	expect( $html )->toContain( 'alt="Protea"' );   // logo alt = sponsor name (a11y).
	expect( $html )->toContain( 'Kwela' );          // name fallback (never a broken img).
} );

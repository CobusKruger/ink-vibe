<?php
/**
 * Unit tests for the homepage sponsor strip block (Story 14.3, FR-58).
 *
 * Target: {@see \Ink\Sponsors\HomepageStrip} — the `ink/borg-strook` server block.
 * The pure `toHtml()` renderer is unit-testable with WordPress mocked: it must
 * COLLAPSE (return '') with no sponsor, render an eyebrow + linked logo for a
 * sponsor, prefer the external link over the permalink, and fall back to the name
 * when there is no logo.
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
	// The eyebrow label comes from the Terms registry; __() returns its arg in tests.
	Functions\when( '__' )->returnArg( 1 );
} );

afterEach( function (): void {
	Monkey\tearDown();
} );

/**
 * Pass-through escapers so the rendered markup is assertable in the unit suite.
 */
function ink_strook_stub_escapers(): void {
	Functions\when( 'esc_url' )->returnArg( 1 );
	Functions\when( 'esc_attr' )->returnArg( 1 );
	Functions\when( 'esc_html' )->returnArg( 1 );
}

test( 'the block name is the single-source constant', function (): void {
	expect( HomepageStrip::BLOCK )->toBe( 'ink/borg-strook' );
} );

// --- collapse ---

test( 'toHtml COLLAPSES to an empty string when there is no active sponsor', function (): void {
	expect( HomepageStrip::toHtml( null ) )->toBe( '' );
} );

// --- render with logo + external link ---

test( 'toHtml renders the eyebrow + a logo linked to the external sponsor link (new tab, rel sponsored)', function (): void {
	ink_strook_stub_escapers();
	Functions\when( 'get_post_thumbnail_id' )->justReturn( 99 );
	Functions\when( 'wp_get_attachment_image_url' )->justReturn( 'https://ink.test/logo.png' );

	$sponsor = new Sponsor( 7, 'Uitgewery Protea', 'https://protea.test', '', '', '', '' );
	$html    = HomepageStrip::toHtml( $sponsor );

	expect( $html )->toContain( 'Ons borge' );                       // eyebrow from the registry.
	expect( $html )->toContain( 'src="https://ink.test/logo.png"' ); // the logo.
	expect( $html )->toContain( 'alt="Uitgewery Protea"' );          // logo alt = name.
	expect( $html )->toContain( 'href="https://protea.test"' );      // the external link.
	expect( $html )->toContain( 'target="_blank"' );
	expect( $html )->toContain( 'rel="noopener sponsored"' );
} );

// --- permalink fallback when no external link ---

test( 'toHtml falls back to the sponsor permalink when there is no external link (no new tab)', function (): void {
	ink_strook_stub_escapers();
	Functions\when( 'get_post_thumbnail_id' )->justReturn( 99 );
	Functions\when( 'wp_get_attachment_image_url' )->justReturn( 'https://ink.test/logo.png' );
	Functions\when( 'get_permalink' )->justReturn( 'https://ink.test/borg/protea' );

	$sponsor = new Sponsor( 7, 'Protea', '', '', '', '', '' );
	$html    = HomepageStrip::toHtml( $sponsor );

	expect( $html )->toContain( 'href="https://ink.test/borg/protea"' );
	expect( $html )->not->toContain( 'target="_blank"' ); // internal link → same tab.
} );

// --- name fallback when no logo ---

test( 'toHtml falls back to the sponsor name when there is no logo (never a broken img)', function (): void {
	ink_strook_stub_escapers();
	Functions\when( 'get_post_thumbnail_id' )->justReturn( 0 ); // no featured image.

	$sponsor = new Sponsor( 7, 'Protea', 'https://protea.test', '', '', '', '' );
	$html    = HomepageStrip::toHtml( $sponsor );

	expect( $html )->not->toContain( '<img' );
	expect( $html )->toContain( 'Protea' );
	expect( $html )->toContain( 'href="https://protea.test"' );
} );

// --- no-anchor when neither link nor permalink resolves ---

test( 'toHtml renders the logo without an anchor when neither link nor permalink resolves', function (): void {
	ink_strook_stub_escapers();
	Functions\when( 'get_post_thumbnail_id' )->justReturn( 99 );
	Functions\when( 'wp_get_attachment_image_url' )->justReturn( 'https://ink.test/logo.png' );
	Functions\when( 'get_permalink' )->justReturn( false ); // permalink unavailable.

	$sponsor = new Sponsor( 7, 'Protea', '', '', '', '', '' );
	$html    = HomepageStrip::toHtml( $sponsor );

	expect( $html )->toContain( 'src="https://ink.test/logo.png"' );
	expect( $html )->not->toContain( '<a ' );
} );

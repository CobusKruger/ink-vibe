<?php
/**
 * Unit tests for the Oor INK sponsor recognition section block (Story 14.4, FR-58).
 *
 * Target: {@see \Ink\Sponsors\RecognitionSection} — the `ink/borg-erkenning` server
 * block. The pure `toHtml()` renderer must ALWAYS render the eyebrow/heading/
 * description/CTA, include the logo grid only when there are active sponsors, link
 * each logo (external link → permalink → none), and fall back to the name when there
 * is no logo.
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Sponsors;

use Ink\Sponsors\RecognitionSection;
use Ink\Sponsors\Sponsor;
use Brain\Monkey;
use Brain\Monkey\Functions;

beforeEach( function (): void {
	Monkey\setUp();
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
	expect( RecognitionSection::BLOCK )->toBe( 'ink/borg-erkenning' );
} );

// --- copy renders always ---

test( 'toHtml ALWAYS renders the eyebrow, heading, description and CTA (even with no sponsors)', function (): void {
	$html = RecognitionSection::toHtml( array() );

	expect( $html )->toContain( 'Ons borge' );                  // eyebrow.
	expect( $html )->toContain( 'Moontlik gemaak deur' );       // heading.
	expect( $html )->toContain( 'gulhartigheid van ons borge' ); // description (fragment).
	expect( $html )->toContain( "Word 'n borg" );               // CTA label.
	expect( $html )->toContain( 'href="https://ink.test/kontak"' ); // CTA → contact page.
} );

// --- grid degrades when empty, present with sponsors ---

test( 'toHtml omits the logo grid when there are no active sponsors (no empty grid chrome)', function (): void {
	$html = RecognitionSection::toHtml( array() );

	expect( $html )->not->toContain( 'ink-borg-erkenning__rooster' );
	expect( $html )->not->toContain( '<li' );
} );

test( 'toHtml renders a logo grid item per active sponsor', function (): void {
	Functions\when( 'get_post_thumbnail_id' )->justReturn( 99 );
	Functions\when( 'wp_get_attachment_image_url' )->justReturn( 'https://ink.test/logo.png' );

	$sponsors = array(
		new Sponsor( 1, 'Protea', 'https://protea.test', '', '', '', '' ),
		new Sponsor( 2, 'NB', 'https://nb.test', '', '', '', '' ),
	);

	$html = RecognitionSection::toHtml( $sponsors );

	expect( $html )->toContain( 'ink-borg-erkenning__rooster' );
	// Trailing space distinguishes the base class from its tier-modifier siblings
	// (which start with the same substring) — mirrors HomepageStripTest's technique.
	expect( substr_count( $html, 'ink-borg-erkenning__item ' ) )->toBe( 2 );
	expect( $html )->toContain( 'href="https://protea.test"' );
	expect( $html )->toContain( 'href="https://nb.test"' );
	expect( $html )->toContain( 'rel="noopener sponsored"' );
} );

// --- one per-tier modifier class per active sponsor (parity with HomepageStrip) ---

test( 'toHtml gives each grid item a tier modifier class, degrading unknown/empty tiers to brons', function (): void {
	Functions\when( 'get_post_thumbnail_id' )->justReturn( 0 );

	// postId 0 (not a real seeded post) with no external link deliberately keeps
	// SponsorLink out of its `get_permalink()` fallback branch (`$post_id <= 0`
	// short-circuits it) — this test only cares about the tier modifier class, not
	// the link target, so it has no reason to touch `get_permalink` at all.
	$sponsors = array(
		new Sponsor( 0, 'Protea', '', 'Goud', '', '', '' ),
		new Sponsor( 0, 'NB', '', 'Silwer', '', '', '' ),
		new Sponsor( 0, 'Kwela', '', '', '', '', '' ),
	);

	$html = RecognitionSection::toHtml( $sponsors );

	expect( $html )->toContain( 'ink-borg-erkenning__item--goud' );
	expect( $html )->toContain( 'ink-borg-erkenning__item--silwer' );
	expect( $html )->toContain( 'ink-borg-erkenning__item--brons' ); // untiered Kwela → brons.
} );

// --- CTA carries the decorative heart icon (Lovable SponsorsSection.tsx parity) ---

test( 'toHtml prefixes the CTA with the decorative heart icon', function (): void {
	$html = RecognitionSection::toHtml( array() );

	expect( $html )->toContain( 'ink-borg-erkenning__cta-ikoon' );
	expect( $html )->toContain( 'aria-hidden="true"' );
} );

// --- name fallback when no logo ---

test( 'toHtml falls back to the sponsor name when a sponsor has no logo (never a broken img)', function (): void {
	Functions\when( 'get_post_thumbnail_id' )->justReturn( 0 );

	$html = RecognitionSection::toHtml( array( new Sponsor( 1, 'Protea', 'https://protea.test', '', '', '', '' ) ) );

	expect( $html )->not->toContain( '<img' );
	expect( $html )->toContain( 'Protea' );
} );

// --- permalink fallback for the grid link ---

test( 'toHtml links a logo to the sponsor permalink when there is no external link', function (): void {
	Functions\when( 'get_post_thumbnail_id' )->justReturn( 99 );
	Functions\when( 'wp_get_attachment_image_url' )->justReturn( 'https://ink.test/logo.png' );
	Functions\when( 'get_permalink' )->justReturn( 'https://ink.test/borg/protea' );

	$html = RecognitionSection::toHtml( array( new Sponsor( 1, 'Protea', '', '', '', '', '' ) ) );

	expect( $html )->toContain( 'href="https://ink.test/borg/protea"' );
} );

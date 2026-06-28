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
	expect( substr_count( $html, 'ink-borg-erkenning__item' ) )->toBe( 2 );
	expect( $html )->toContain( 'href="https://protea.test"' );
	expect( $html )->toContain( 'href="https://nb.test"' );
	expect( $html )->toContain( 'rel="noopener sponsored"' );
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

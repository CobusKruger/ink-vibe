<?php
/**
 * Unit tests for the InkPols PDF flipbook viewer (Story 13.3, FR-57).
 *
 * Target: {@see \Ink\InkPols\Viewer}. The pure `shortcodeFor()` (Real3D Flipbook
 * shortcode for a PDF URL) and `embedHtml()` (flipbook wrapper when the plugin is
 * available; the `Lees die uitgawe` direct-PDF fallback when not; '' for no PDF)
 * are unit-testable without WordPress or the plugin present.
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\InkPols;

use Ink\InkPols\Viewer;
use Brain\Monkey;
use Brain\Monkey\Functions;

beforeEach( function (): void {
	Monkey\setUp();
	Functions\when( '__' )->returnArg( 1 );
	Functions\when( 'esc_html' )->returnArg( 1 );
	Functions\when( 'esc_url' )->returnArg( 1 );
} );

afterEach( function (): void {
	Monkey\tearDown();
} );

test( 'shortcodeFor builds the Real3D Flipbook shortcode with the pdf attribute', function (): void {
	$shortcode = Viewer::shortcodeFor( 'https://ink.test/inkpols-7.pdf' );

	expect( $shortcode )->toBe( '[' . Viewer::SHORTCODE_TAG . ' pdf="https://ink.test/inkpols-7.pdf"]' );
} );

test( 'embedHtml wraps the expanded shortcode output when the flipbook is available', function (): void {
	$html = Viewer::embedHtml( 'https://ink.test/x.pdf', true, '<div id="flipbook">…</div>' );

	expect( $html )->toContain( 'ink-inkpols-leser' );
	expect( $html )->toContain( '<div id="flipbook">' );
	// The available branch does NOT render the fallback link.
	expect( $html )->not->toContain( 'ink-inkpols-leser__skakel' );
} );

test( 'embedHtml degrades to the Lees die uitgawe direct-PDF link when the plugin is inactive', function (): void {
	$html = Viewer::embedHtml( 'https://ink.test/x.pdf', false, '' );

	expect( $html )->toContain( 'ink-inkpols-leser--terugval' );
	expect( $html )->toContain( 'ink-inkpols-leser__skakel' );
	expect( $html )->toContain( 'https://ink.test/x.pdf' );
	expect( $html )->toContain( 'Lees die uitgawe' );
} );

test( 'embedHtml renders nothing when there is no PDF URL', function (): void {
	expect( Viewer::embedHtml( '', true, '<div>x</div>' ) )->toBe( '' );
	expect( Viewer::embedHtml( '', false, '' ) )->toBe( '' );
} );

<?php
/**
 * Unit tests for the live English-leak detector (Story 18.8, NFR-1 Layer 2).
 *
 * Target: {@see \Ink\Tests\Support\RenderedLeakScanner} — the pure rendered-HTML
 * detection that backs the CI/cron page-crawl leak gate. No WordPress.
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Support;

use Ink\Tests\Support\RenderedLeakScanner;

test( 'a clean Afrikaans page has no English candidates', function (): void {
	$html = '<html><body><h1>Welkom by INK</h1><p>Lees gedigte en stories van ons skrywers.</p></body></html>';

	expect( RenderedLeakScanner::candidates( $html ) )->toBe( array() );
	expect( RenderedLeakScanner::isClean( $html ) )->toBeTrue();
} );

test( 'an English-leaking page is flagged with the offending tokens', function (): void {
	$html = '<html><body><h1>Welcome to your account</h1><p>Please login with your password.</p></body></html>';

	$found = RenderedLeakScanner::candidates( $html );

	expect( $found )->toContain( 'welcome' );
	expect( $found )->toContain( 'your' );
	expect( $found )->toContain( 'account' );
	expect( $found )->toContain( 'please' );
	expect( $found )->toContain( 'login' );
	expect( $found )->toContain( 'password' );
	expect( RenderedLeakScanner::isClean( $html ) )->toBeFalse();
} );

test( 'script and style bodies are ignored (only visible text is scanned)', function (): void {
	$html = '<html><head><style>.x{content:"please login"}</style>'
		. '<script>var t = "welcome your account";</script></head>'
		. '<body><p>Meld aan by jou rekening.</p></body></html>';

	expect( RenderedLeakScanner::candidates( $html ) )->toBe( array() );
} );

test( 'brand names and loanwords on the allowlist are never flagged', function (): void {
	$html = '<body><p>INK · InkPols · betaal met PayFast · volg ons op Facebook · laai die PDF af</p></body>';

	expect( RenderedLeakScanner::candidates( $html ) )->toBe( array() );
} );

test( 'epos (Afrikaans e-mail) is allowlisted, the English email is flagged', function (): void {
	$af = '<body><p>Voer jou epos in.</p></body>';
	$en = '<body><p>Enter your email.</p></body>';

	expect( RenderedLeakScanner::candidates( $af ) )->toBe( array() );
	expect( RenderedLeakScanner::candidates( $en ) )->toContain( 'email' );
} );

test( 'a caller-supplied allowlist suppresses a token', function (): void {
	$html = '<body><p>Read more</p></body>';

	// Without the extra allowlist, "read" + "more" flag; with it, suppressed.
	expect( RenderedLeakScanner::candidates( $html ) )->toContain( 'read' );
	expect( RenderedLeakScanner::candidates( $html, array( 'read', 'more' ) ) )->toBe( array() );
} );

test( 'candidates are distinct and in first-seen order', function (): void {
	$html = '<body><p>your your you the the</p></body>';

	expect( RenderedLeakScanner::candidates( $html ) )->toBe( array( 'your', 'you', 'the' ) );
} );

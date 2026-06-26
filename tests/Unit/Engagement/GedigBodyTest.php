<?php
/**
 * Unit tests for the gedig stanza-aware renderer (Story 7.2, FR-25).
 *
 * Target: {@see \Ink\Engagement\GedigBody} — the server-rendered `ink/gedig-body`
 * block that renders the 6.3-stored verbatim poem body (line breaks, blank-line /
 * stanza spacing, leading whitespace) with author Roman-numeral markers and
 * per-line resonance anchors (the contract Story 7.3 consumes).
 *
 * `tokenize` / `isRomanNumeralMarker` are pure and tested directly. `toHtml` is
 * tested with `wp_kses` mocked as identity and `esc_attr` as arg-passthrough, so
 * the assertions are about OUR structure, not WordPress.
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Engagement;

use Ink\Engagement\GedigBody;
use Brain\Monkey;
use Brain\Monkey\Functions;

beforeEach( function (): void {
	Monkey\setUp();
} );

afterEach( function (): void {
	Monkey\tearDown();
} );

test( 'tokenize splits physical lines, flags blanks, and keeps leading whitespace verbatim', function (): void {
	$body   = "  ingekeepte reël\n\n  tweede strofe";
	$tokens = GedigBody::tokenize( $body );

	expect( $tokens )->toHaveCount( 3 );

	expect( $tokens[0]['type'] )->toBe( 'line' );
	expect( $tokens[0]['index'] )->toBe( 0 );
	expect( $tokens[0]['text'] )->toBe( '  ingekeepte reël' ); // leading spaces kept

	expect( $tokens[1]['type'] )->toBe( 'blank' );

	expect( $tokens[2]['type'] )->toBe( 'line' );
	expect( $tokens[2]['index'] )->toBe( 2 ); // physical-line index, NOT content ordinal
	expect( $tokens[2]['text'] )->toBe( '  tweede strofe' );
} );

test( 'tokenize treats whitespace-only lines as blank separators', function (): void {
	$tokens = GedigBody::tokenize( "reël een\n   \nreël twee" );

	expect( $tokens[1]['type'] )->toBe( 'blank' );
	expect( $tokens[0]['type'] )->toBe( 'line' );
	expect( $tokens[2]['type'] )->toBe( 'line' );
} );

test( 'isRomanNumeralMarker recognises author Roman markers and rejects words/numbers', function (): void {
	foreach ( array( 'I', 'II', 'III', 'IV', 'V', 'X', 'I.', 'IV.' ) as $marker ) {
		expect( GedigBody::isRomanNumeralMarker( $marker ) )->toBeTrue( "should match: {$marker}" );
	}

	foreach ( array( 'Iets', 'Hallo', '1', '', 'reël', 'die' ) as $notMarker ) {
		expect( GedigBody::isRomanNumeralMarker( $notMarker ) )->toBeFalse( "should not match: {$notMarker}" );
	}
} );

test( 'tokenize flags a standalone Roman numeral line as a marker', function (): void {
	$tokens = GedigBody::tokenize( "II\nkort vers" );

	expect( $tokens[0]['type'] )->toBe( 'line' );
	expect( $tokens[0]['marker'] )->toBeTrue();
	expect( $tokens[1]['marker'] )->toBeFalse();
} );

test( 'toHtml emits resonance anchors on content lines but never on blank separators', function (): void {
	Functions\when( 'wp_kses' )->returnArg( 1 );
	Functions\when( 'esc_attr' )->returnArg( 1 );

	$html = GedigBody::toHtml( "reël een\n\nreël twee" );

	// Non-vacuous: a content line DOES carry the anchor...
	expect( $html )->toContain( 'data-ink-line="0"' );
	expect( $html )->toContain( 'data-ink-line="2"' );
	// ...the blank separator is rendered but is NOT resonance-able.
	expect( $html )->toContain( 'ink-gedig__sep' );
	expect( substr_count( $html, 'data-ink-line' ) )->toBe( 2 ); // exactly the two content lines
	expect( $html )->toContain( 'ink-gedig__stanza' );
	expect( $html )->toContain( 'ink-gedig__line' );
} );

test( 'toHtml marks Roman-numeral lines, preserves inline marks and leading whitespace', function (): void {
	Functions\when( 'wp_kses' )->returnArg( 1 );
	Functions\when( 'esc_attr' )->returnArg( 1 );

	$html = GedigBody::toHtml( "I\n  <strong>vet</strong> woord" );

	expect( $html )->toContain( 'ink-gedig__line--marker' );      // the "I" marker
	expect( $html )->toContain( '<strong>vet</strong>' );          // inline mark preserved
	expect( $html )->toContain( '  <strong>vet</strong> woord' );  // leading whitespace preserved verbatim
} );

test( 'toHtml on an empty body renders an empty container gracefully', function (): void {
	Functions\when( 'wp_kses' )->returnArg( 1 );
	Functions\when( 'esc_attr' )->returnArg( 1 );

	$html = GedigBody::toHtml( '' );

	expect( $html )->toContain( 'ink-gedig' );
	expect( $html )->not->toContain( 'data-ink-line' );
} );

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

// Legacy-format support (post-Epic-19 correction): tokenize() must parse real
// historical HTML-markup storage formats as first-class input, NOT have that
// content rewritten to suit the parser. Each format below is a real shape
// confirmed via a full-database sample of ~5,585 gedig posts, not a guess.

test( 'tokenize treats a plain \n body with no markup exactly as before (no regression)', function (): void {
	$tokens = GedigBody::tokenize( "reël een\n\nreël twee" );

	expect( $tokens )->toHaveCount( 3 );
	expect( $tokens[0]['text'] )->toBe( 'reël een' );
	expect( $tokens[1]['type'] )->toBe( 'blank' );
	expect( $tokens[2]['text'] )->toBe( 'reël twee' );
} );

test( 'tokenize parses a Gutenberg-wrapped single <p>, <br>-joined body (67912/67904 shape)', function (): void {
	$body = "<!-- wp:paragraph -->\n<p>sy hande praat waar haar mond nie wil nie<br>'n kop knik, 'n oomblik lank stil<br>die kamer onthou elke asem<br>wat nooit woorde geword het nie</p>\n<!-- /wp:paragraph -->";

	$tokens      = GedigBody::tokenize( $body );
	$line_tokens = array_values( array_filter( $tokens, static fn( $t ) => 'line' === $t['type'] ) );

	expect( $line_tokens )->toHaveCount( 4 );
	expect( $line_tokens[0]['text'] )->toBe( 'sy hande praat waar haar mond nie wil nie' );
	expect( $line_tokens[3]['text'] )->toBe( 'wat nooit woorde geword het nie' );
	expect( $tokens )->toHaveCount( 4 ); // no bare-comment/blank artifacts — exactly the 4 real lines
} );

test( 'tokenize parses the dominant legacy shape: one bare <p> per stanza, <br>-joined lines inside', function (): void {
	$body = "<p>reël een<br />\nreël twee<br />\nreël drie</p>\n<p>strofe twee reël een<br />\nstrofe twee reël twee</p>";

	$tokens      = GedigBody::tokenize( $body );
	$line_tokens = array_values( array_filter( $tokens, static fn( $t ) => 'line' === $t['type'] ) );
	$blank_count = count( $tokens ) - count( $line_tokens );

	expect( $line_tokens )->toHaveCount( 5 );
	expect( $blank_count )->toBeGreaterThanOrEqual( 1 ); // a stanza break between the two <p> blocks
	expect( $line_tokens[0]['text'] )->toBe( 'reël een' );
	expect( $line_tokens[4]['text'] )->toBe( 'strofe twee reël twee' );
} );

test( 'tokenize parses a bare <br>-only body (no <p> at all), double <br> as a stanza break', function (): void {
	$body = "reël een<br>\nreël twee<br>\n<br>\nreël drie";

	$tokens      = GedigBody::tokenize( $body );
	$line_tokens = array_values( array_filter( $tokens, static fn( $t ) => 'line' === $t['type'] ) );
	$blank_count = count( $tokens ) - count( $line_tokens );

	expect( $line_tokens )->toHaveCount( 3 );
	expect( $blank_count )->toBe( 1 );
} );

test( 'tokenize parses the "one <p> per LINE" legacy shape (no <br> at all), &nbsp;-only <p> as the stanza break', function (): void {
	$body = "<p>reël een</p>\n<p>reël twee</p>\n<p>&nbsp;</p>\n<p>strofe twee</p>";

	$tokens      = GedigBody::tokenize( $body );
	$line_tokens = array_values( array_filter( $tokens, static fn( $t ) => 'line' === $t['type'] ) );
	$blank_count = count( $tokens ) - count( $line_tokens );

	expect( $line_tokens )->toHaveCount( 3 );
	expect( $blank_count )->toBe( 1 );
	expect( $line_tokens[0]['text'] )->toBe( 'reël een' );
	expect( $line_tokens[1]['text'] )->toBe( 'reël twee' );
	expect( $line_tokens[2]['text'] )->toBe( 'strofe twee' );
} );

test( 'tokenize strips a non-wp HTML comment rather than minting a bare interactive line for it', function (): void {
	$body = '<p>©Skrywer<br><!--/data/user/0/com.samsung.android.app.notes/clipdata.sdocx--></p>';

	$tokens      = GedigBody::tokenize( $body );
	$line_tokens = array_values( array_filter( $tokens, static fn( $t ) => 'line' === $t['type'] ) );

	expect( $line_tokens )->toHaveCount( 1 );
	expect( $line_tokens[0]['text'] )->toBe( '©Skrywer' );
} );

// A line whose RAW text is non-blank (so tokenize() correctly classifies it as
// `line`, not `blank`) but is disallowed markup that sanitizes down to nothing
// visible — e.g. a stray legacy `<ul>` footnote-artifact fragment surviving
// normalizeLegacyMarkup() as its own physical line (post 58508, live) — must
// not render as a genuinely-empty, still-interactive `<p data-ink-line>` (a
// heart + hover highlight over blank space). toHtml() re-checks the RENDERED
// (post-wp_kses) result, not the raw text tokenize() already checked.

test( 'toHtml treats a line that sanitizes down to nothing visible as a separator, never an empty interactive line', function (): void {
	Functions\when( 'wp_kses' )->returnArg( 1 );
	Functions\when( 'esc_attr' )->returnArg( 1 );

	$html = GedigBody::toHtml( "eerste reël\n<ul>\ntweede reël" );

	expect( $html )->toContain( 'data-ink-line="0"' );
	expect( $html )->toContain( 'data-ink-line="2"' );
	expect( $html )->not->toContain( 'data-ink-line="1"' ); // sanitizes to nothing — never interactive
	expect( substr_count( $html, 'ink-gedig__sep' ) )->toBe( 1 ); // rendered as a plain separator instead
} );

test( 'toHtml does not over-trigger: a line with only an empty allowed tag (e.g. <em></em>) is also treated as empty', function (): void {
	Functions\when( 'wp_kses' )->returnArg( 1 );
	Functions\when( 'esc_attr' )->returnArg( 1 );

	$html = GedigBody::toHtml( "eerste reël\n<em></em>\ntweede reël" );

	expect( $html )->not->toContain( 'data-ink-line="1"' );
} );

test( 'toHtml keeps short-but-real lines (single word, punctuation-only) fully interactive — does not over-trigger', function (): void {
	Functions\when( 'wp_kses' )->returnArg( 1 );
	Functions\when( 'esc_attr' )->returnArg( 1 );

	$html = GedigBody::toHtml( "Ja.\n—\n..." );

	expect( $html )->toContain( 'data-ink-line="0"' );
	expect( $html )->toContain( 'data-ink-line="1"' );
	expect( $html )->toContain( 'data-ink-line="2"' );
	expect( $html )->not->toContain( 'ink-gedig__sep' );
} );

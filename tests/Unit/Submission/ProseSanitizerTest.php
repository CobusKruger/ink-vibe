<?php
/**
 * Unit tests for the light-editor body sanitiser (Story 6.3, FR-18).
 *
 * Target: {@see \Ink\Submission\ProseSanitizer} — the strict inline allowlist that
 * lets shaped/concrete poetry survive. The security-relevant guard here is the
 * allowlist CONTENT: exactly bold/italic/break, NO attributes, and NOTHING else
 * (headings/tables/img/span/links/lists/iframes absent) — a future loosening must
 * fail this test. We also prove `sanitize()` adds no `trim()`/whitespace collapse
 * of its own (it delegates stripping to `wp_kses`, which is mocked as identity so
 * the assertion is about OUR code, not WordPress's).
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Submission;

use Ink\Submission\ProseSanitizer;
use Brain\Monkey;
use Brain\Monkey\Functions;

beforeEach( function (): void {
	Monkey\setUp();
} );

afterEach( function (): void {
	Monkey\tearDown();
} );

/**
 * The allowlist is EXACTLY bold/italic/break, each with no attributes.
 */
test( 'allowedTags is exactly the inline marks with no attributes', function (): void {
	$tags = ProseSanitizer::allowedTags();

	expect( array_keys( $tags ) )->toBe( array( 'strong', 'b', 'em', 'i', 'br' ) );

	foreach ( $tags as $attributes ) {
		expect( $attributes )->toBe( array() ); // no style/class/color/size attributes
	}
} );

/**
 * Block / media / link / control tags are NOT allowed (regression guard).
 */
test( 'allowedTags excludes headings, tables, media, links and controls', function (): void {
	$tags = ProseSanitizer::allowedTags();

	foreach ( array( 'h1', 'h2', 'h3', 'table', 'tr', 'td', 'img', 'span', 'a', 'ul', 'ol', 'li', 'blockquote', 'iframe', 'font', 'div', 'p', 'style' ) as $forbidden ) {
		expect( $tags )->not->toHaveKey( $forbidden );
	}
} );

/**
 * sanitize() delegates to wp_kses with the strict allowlist and adds no trimming
 * or whitespace collapse — leading whitespace + blank stanza lines pass through.
 */
test( 'sanitize preserves structure verbatim and uses the strict allowlist', function (): void {
	$body = "    ingekeepte reël\n\n  tweede strofe";

	Functions\expect( 'wp_kses' )
		->once()
		->with( $body, ProseSanitizer::allowedTags() )
		->andReturnUsing( static fn( string $string ): string => $string );

	$result = ProseSanitizer::sanitize( $body );

	// No trim / collapse of OUR own — leading spaces and the blank line survive.
	expect( $result )->toBe( $body );
} );

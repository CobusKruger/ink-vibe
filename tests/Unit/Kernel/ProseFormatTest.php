<?php
/**
 * Unit tests for the shared inline-prose allowlist (Story 7.2, FR-25 / FR-18).
 *
 * Target: {@see \Ink\Kernel\ProseFormat} — the single-source inline allowlist that
 * BOTH the write-time light-editor sanitiser ({@see \Ink\Submission\ProseSanitizer},
 * 6.3) and the read-time gedig renderer ({@see \Ink\Engagement\GedigBody}, 7.2)
 * consume, so the two can never drift. The security-relevant guard is the allowlist
 * CONTENT: exactly bold/italic/break, NO attributes, nothing else — a future
 * loosening must fail this test.
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Kernel;

use Ink\Kernel\ProseFormat;

test( 'allowedInlineTags is exactly the inline marks with no attributes', function (): void {
	$tags = ProseFormat::allowedInlineTags();

	expect( array_keys( $tags ) )->toBe( array( 'strong', 'b', 'em', 'i', 'br' ) );

	foreach ( $tags as $attributes ) {
		expect( $attributes )->toBe( array() ); // no style/class/color/size attributes
	}
} );

test( 'allowedInlineTags excludes headings, tables, media, links and controls', function (): void {
	$tags = ProseFormat::allowedInlineTags();

	foreach ( array( 'h1', 'h2', 'table', 'img', 'span', 'a', 'ul', 'ol', 'li', 'blockquote', 'iframe', 'font', 'div', 'p', 'style' ) as $forbidden ) {
		expect( $tags )->not->toHaveKey( $forbidden );
	}
} );

test( 'ProseSanitizer delegates to the shared Kernel allowlist (single-source)', function (): void {
	expect( \Ink\Submission\ProseSanitizer::allowedTags() )->toBe( ProseFormat::allowedInlineTags() );
} );

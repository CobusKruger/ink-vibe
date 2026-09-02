<?php
/**
 * Unit tests for the prose paragraph tokeniser (post-Epic-19 storie fidelity pass).
 *
 * Target: {@see \Ink\Engagement\ProseBody} — the write-path anchor tokeniser for
 * the storie/artikel text-highlight-reaction feature, mirroring
 * {@see \Ink\Engagement\GedigBody::tokenize()}'s per-line contract at
 * paragraph granularity instead.
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Engagement;

use Ink\Engagement\ProseBody;

test( 'tokenize splits blank-line-delimited blocks into 0-based paragraphs', function (): void {
	$tokens = ProseBody::tokenize( "Eerste paragraaf.\n\nTweede paragraaf." );

	expect( $tokens )->toHaveCount( 2 );
	expect( $tokens[0]['type'] )->toBe( 'paragraph' );
	expect( $tokens[0]['index'] )->toBe( 0 );
	expect( $tokens[0]['text'] )->toBe( 'Eerste paragraaf.' );
	expect( $tokens[1]['index'] )->toBe( 1 );
	expect( $tokens[1]['text'] )->toBe( 'Tweede paragraaf.' );
} );

test( 'tokenize keeps multiple physical lines of the same paragraph as ONE token', function (): void {
	// No blank line between the two physical lines — same paragraph, unlike
	// GedigBody which would anchor each physical line individually.
	$tokens = ProseBody::tokenize( "reël een\nreël twee (steeds eerste paragraaf)\n\ntweede paragraaf" );

	expect( $tokens )->toHaveCount( 2 );
	expect( $tokens[0]['text'] )->toBe( "reël een\nreël twee (steeds eerste paragraaf)" );
	expect( $tokens[1]['text'] )->toBe( 'tweede paragraaf' );
} );

test( 'tokenize collapses runs of multiple blank lines to one paragraph break', function (): void {
	$tokens = ProseBody::tokenize( "een\n\n\n\ntwee" );

	expect( $tokens )->toHaveCount( 2 );
	expect( $tokens[0]['index'] )->toBe( 0 );
	expect( $tokens[1]['index'] )->toBe( 1 );
} );

test( 'tokenize trims leading/trailing blank lines without emitting empty paragraphs', function (): void {
	$tokens = ProseBody::tokenize( "\n\neen\n\ntwee\n\n" );

	expect( $tokens )->toHaveCount( 2 );
	foreach ( $tokens as $token ) {
		expect( trim( $token['text'] ) )->not->toBe( '' );
	}
} );

test( 'tokenize on an empty body yields no paragraphs', function (): void {
	expect( ProseBody::tokenize( '' ) )->toBe( array() );
	expect( ProseBody::tokenize( "   \n\n  " ) )->toBe( array() );
} );

test( 'isParagraphIndex is true only for a real paragraph index, false for blank/out-of-range', function (): void {
	$body = "een\n\ntwee";

	expect( ProseBody::isParagraphIndex( 0, $body ) )->toBeTrue();
	expect( ProseBody::isParagraphIndex( 1, $body ) )->toBeTrue();
	expect( ProseBody::isParagraphIndex( 2, $body ) )->toBeFalse(); // out of range
	expect( ProseBody::isParagraphIndex( -1, $body ) )->toBeFalse();
} );

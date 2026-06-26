<?php
/**
 * Unit tests for the Skryf line/word counting single source (Story 6.2, FR-17).
 *
 * Target: {@see \Ink\Submission\Counters} — the ONE definition of "[N] reëls ·
 * [N] woorde". Pins UTF-8 word counting (Afrikaans diacritics are part of a word),
 * non-blank line counting (blank stanza separators do not inflate the verse-line
 * count), and the type-aware `forType` result (lines null for prose). Pure PHP, no
 * WordPress — no Brain Monkey needed.
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Submission;

use Ink\Submission\Counters;

test( 'words counts UTF-8 non-whitespace tokens', function (): void {
	expect( Counters::words( 'een twee drie' ) )->toBe( 3 );
	expect( Counters::words( '' ) )->toBe( 0 );
	expect( Counters::words( "  \n\t " ) )->toBe( 0 );
	expect( Counters::words( 'wéreld môre súbtiel' ) )->toBe( 3 ); // diacritics stay in-word
	expect( Counters::words( "een   twee\tdrie\nvier" ) )->toBe( 4 );
} );

test( 'lines counts non-blank lines only', function (): void {
	expect( Counters::lines( "een\ntwee\ndrie" ) )->toBe( 3 );
	expect( Counters::lines( "een\n\ndrie" ) )->toBe( 2 );      // blank stanza separator skipped
	expect( Counters::lines( "een\n   \ndrie" ) )->toBe( 2 );   // whitespace-only line skipped
	expect( Counters::lines( '' ) )->toBe( 0 );
	expect( Counters::lines( "een\n" ) )->toBe( 1 );            // trailing newline
	expect( Counters::lines( "een\r\ntwee" ) )->toBe( 2 );      // CRLF
} );

test( 'forType includes lines for a gedig and omits them for prose', function (): void {
	$verse = Counters::forType( 'gedig', "reël een\n\nreël twee" );
	expect( $verse )->toBe( array( 'lines' => 2, 'words' => 4 ) );

	$prose = Counters::forType( 'storie', "Dit is prosa met woorde." );
	expect( $prose['lines'] )->toBeNull();
	expect( $prose['words'] )->toBe( 5 );

	expect( Counters::forType( 'artikel', 'een twee' )['lines'] )->toBeNull();
} );

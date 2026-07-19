<?php
/**
 * Unit tests for the read-time helper (Story 19.4, §6).
 *
 * Target: {@see \Ink\Discovery\ReadingTime} — the `ink-core`-owned read-time
 * computation (owner decision: read-time is computed from word count in ink-core,
 * NEVER in the theme). We test the INK-owned OUTCOMES: the word-count rule, the
 * minutes math (round-up, floored at 1, zero-for-empty), and the Afrikaans label.
 * Brain-Monkey-mocked — no WordPress/DB.
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Discovery;

use Ink\Discovery\ReadingTime;
use Brain\Monkey;
use Brain\Monkey\Functions;

beforeEach( function (): void {
	Monkey\setUp();
	Functions\when( '_n' )->alias(
		static fn ( string $single, string $plural, int $number ): string => 1 === $number ? $single : $plural
	);
} );

afterEach( function (): void {
	Monkey\tearDown();
} );

test( 'the words-per-minute pace is the single-source constant', function (): void {
	expect( ReadingTime::WORDS_PER_MINUTE )->toBe( 200 );
} );

// --- words(): UTF-8 non-whitespace tokens ---

test( 'words counts UTF-8 non-whitespace tokens (Afrikaans diacritics stay in-word)', function (): void {
	expect( ReadingTime::words( 'die môre-son sê niks' ) )->toBe( 4 );
	expect( ReadingTime::words( '   ' ) )->toBe( 0 );
	expect( ReadingTime::words( '' ) )->toBe( 0 );
	expect( ReadingTime::words( "een\ntwee\tdrie" ) )->toBe( 3 );
} );

// --- minutesFromWords(): round up, floor at 1, zero for empty ---

test( 'minutesFromWords rounds UP to whole minutes at the 200 wpm pace', function (): void {
	expect( ReadingTime::minutesFromWords( 200 ) )->toBe( 1 );
	expect( ReadingTime::minutesFromWords( 201 ) )->toBe( 2 );
	expect( ReadingTime::minutesFromWords( 400 ) )->toBe( 2 );
	expect( ReadingTime::minutesFromWords( 401 ) )->toBe( 3 );
	expect( ReadingTime::minutesFromWords( 1600 ) )->toBe( 8 );
} );

test( 'minutesFromWords floors a short-but-nonempty body at 1 minute', function (): void {
	expect( ReadingTime::minutesFromWords( 1 ) )->toBe( 1 );
	expect( ReadingTime::minutesFromWords( 30 ) )->toBe( 1 );
} );

test( 'minutesFromWords returns 0 for an empty body (so the caller can omit read-time)', function (): void {
	expect( ReadingTime::minutesFromWords( 0 ) )->toBe( 0 );
	expect( ReadingTime::minutesFromWords( -5 ) )->toBe( 0 );
} );

test( 'minutesFromText counts words then converts to minutes', function (): void {
	$text = implode( ' ', array_fill( 0, 450, 'woord' ) );

	expect( ReadingTime::minutesFromText( $text ) )->toBe( 3 );
	expect( ReadingTime::minutesFromText( '   ' ) )->toBe( 0 );
} );

// --- label(): authored "N min", empty for zero ---

test( 'label renders the authored "N min" form via _n', function (): void {
	expect( ReadingTime::label( 8 ) )->toBe( '8 min' );
	expect( ReadingTime::label( 1 ) )->toBe( '1 min' );
} );

test( 'label is empty when there is no read-time (0 min), so no "0 min" ever surfaces', function (): void {
	expect( ReadingTime::label( 0 ) )->toBe( '' );
	expect( ReadingTime::label( -3 ) )->toBe( '' );
} );

<?php
/**
 * Unit tests for diacritic folding (Story 8.4, FR-35, AD-7).
 *
 * Target: {@see \Ink\Discovery\Diacritics::fold()} — the pure accent-stripping
 * that makes search diacritic-insensitive in both directions.
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Discovery;

use Ink\Discovery\Diacritics;
use Brain\Monkey;

beforeEach( function (): void {
	Monkey\setUp();
} );

afterEach( function (): void {
	Monkey\tearDown();
} );

test( 'fold strips the Afrikaans diacritics to their base letters and lowercases', function (): void {
	expect( Diacritics::fold( 'reën' ) )->toBe( 'reen' );      // ë → e
	expect( Diacritics::fold( 'môre' ) )->toBe( 'more' );      // ô → o
	expect( Diacritics::fold( 'wîe' ) )->toBe( 'wie' );        // î → i
	expect( Diacritics::fold( 'Café' ) )->toBe( 'cafe' );      // é → e + lowercase
	expect( Diacritics::fold( 'Eugène' ) )->toBe( 'eugene' );  // è → e
	expect( Diacritics::fold( 'oübliek' ) )->toBe( 'oubliek' ); // ü → u
} );

test( 'fold matches in both directions (accented query <-> unaccented content)', function (): void {
	// The folded forms are equal whichever side carries the accent.
	expect( Diacritics::fold( 'reën' ) )->toBe( Diacritics::fold( 'reen' ) );
	expect( Diacritics::fold( 'REËN' ) )->toBe( Diacritics::fold( 'reen' ) );
	expect( Diacritics::fold( 'Môre' ) )->toBe( Diacritics::fold( 'more' ) );
} );

test( 'fold collapses whitespace and trims', function (): void {
	expect( Diacritics::fold( "  die   blou\tlug  " ) )->toBe( 'die blou lug' );
} );

test( 'fold leaves plain ASCII unchanged apart from case (non-vacuous)', function (): void {
	expect( Diacritics::fold( 'Storie' ) )->toBe( 'storie' );
	expect( Diacritics::fold( 'gedig' ) )->toBe( 'gedig' );
} );

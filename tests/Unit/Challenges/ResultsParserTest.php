<?php
/**
 * Unit tests for the judges' results parser (Story 12A.3, FR-50-R2).
 *
 * Target: {@see \Ink\Challenges\ResultsParser} — extracts the winners block (top-3 per
 * Gradering × category, "Geen" omitted) + per-entry commentary from pasted plain text.
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Challenges;

use Ink\Challenges\ResultsParser;
use Brain\Monkey;

beforeEach( function (): void {
	Monkey\setUp();
} );

afterEach( function (): void {
	Monkey\tearDown();
} );

test( 'parses winners by pool header, rank token, and EntryID — omitting Geen', function (): void {
	$text = <<<TXT
WENNERS
Brons Gedigte
1ste: Gedig 3
2de: Gedig 1
3de: Geen
Silwer Stories
1: Storie 2
TXT;

	$parsed = ResultsParser::parse( $text );

	expect( $parsed['winners'] )->toHaveCount( 3 );
	expect( $parsed['winners'][0] )->toBe( array( 'grade' => 'brons', 'type' => 'gedig', 'rank' => 1, 'entry_id' => 'Gedig 3' ) );
	expect( $parsed['winners'][1] )->toBe( array( 'grade' => 'brons', 'type' => 'gedig', 'rank' => 2, 'entry_id' => 'Gedig 1' ) );
	// The "Geen" line is intentionally dropped; Silwer carries over the grade context.
	expect( $parsed['winners'][2] )->toBe( array( 'grade' => 'silwer', 'type' => 'storie', 'rank' => 1, 'entry_id' => 'Storie 2' ) );
} );

test( 'parses per-entry commentary keyed by EntryID + title, multi-line text', function (): void {
	$text = <<<TXT
KOMMENTAAR
Gedig 3: Maanlig
Pragtige beeldspraak.
Sterk slotreël.

Gedig 1: Nag
Goeie ritme.
TXT;

	$parsed = ResultsParser::parse( $text );

	expect( $parsed['commentary'] )->toHaveCount( 2 );
	expect( $parsed['commentary'][0]['entry_id'] )->toBe( 'Gedig 3' );
	expect( $parsed['commentary'][0]['title'] )->toBe( 'Maanlig' );
	expect( $parsed['commentary'][0]['text'] )->toBe( "Pragtige beeldspraak.\nSterk slotreël." );
	expect( $parsed['commentary'][1]['entry_id'] )->toBe( 'Gedig 1' );
	expect( $parsed['commentary'][1]['title'] )->toBe( 'Nag' );
	expect( $parsed['commentary'][1]['text'] )->toBe( 'Goeie ritme.' );
} );

test( 'parses a full document with both sections', function (): void {
	$text = <<<TXT
WENNERS
Goud Artikels
1ste: Artikel 2

KOMMENTAAR
Artikel 2: Die see
Insiggewend.
TXT;

	$parsed = ResultsParser::parse( $text );

	expect( $parsed['winners'] )->toHaveCount( 1 );
	expect( $parsed['winners'][0]['entry_id'] )->toBe( 'Artikel 2' );
	expect( $parsed['commentary'] )->toHaveCount( 1 );
	expect( $parsed['commentary'][0]['entry_id'] )->toBe( 'Artikel 2' );
} );

test( 'normalises EntryID case and is forgiving of mixed-case type words', function (): void {
	$parsed = ResultsParser::parse( "WENNERS\nBrons Gedigte\n1: gedig 7" );

	expect( $parsed['winners'][0]['entry_id'] )->toBe( 'Gedig 7' );
	expect( $parsed['winners'][0]['type'] )->toBe( 'gedig' );
} );

test( 'empty / section-less text yields empty winners and commentary', function (): void {
	$parsed = ResultsParser::parse( '' );

	expect( $parsed['winners'] )->toBe( array() );
	expect( $parsed['commentary'] )->toBe( array() );
} );

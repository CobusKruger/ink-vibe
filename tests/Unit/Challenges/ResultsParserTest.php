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

/**
 * Compact "grade type rank entry" strings for a parse's winners.
 *
 * @return list<string>
 */
function winnersOf( string $text ): array {
	return array_map(
		static fn ( array $w ): string => "{$w['grade']} {$w['type']} #{$w['rank']} {$w['entry_id']}",
		ResultsParser::parse( $text )['winners']
	);
}

test( 'a tie on one line keeps BOTH entries at the shared rank, so the gate blocks loudly instead of losing one', function (): void {
	expect( winnersOf( "WENNERS\nBrons Gedigte\n1ste: Gedig 3 & Gedig 5\n2de: Gedig 1" ) )->toBe(
		array( 'brons gedig #1 Gedig 3', 'brons gedig #1 Gedig 5', 'brons gedig #2 Gedig 1' )
	);
} );

test( 'an unnumbered entry line right after a rank line shares that rank', function (): void {
	expect( winnersOf( "WENNERS\nBrons Gedigte\n1ste: Gedig 3\nGedig 5\n2de: Gedig 1" ) )->toBe(
		array( 'brons gedig #1 Gedig 3', 'brons gedig #1 Gedig 5', 'brons gedig #2 Gedig 1' )
	);
} );

test( 'honourable mentions are not winners and do not inherit a rank', function (): void {
	expect( winnersOf( "WENNERS\nBrons Gedigte\n1ste: Gedig 3\nEervolle vermelding: Gedig 9\nGedig 10\nSilwer Gedigte\n1: Gedig 4" ) )->toBe(
		array( 'brons gedig #1 Gedig 3', 'silwer gedig #1 Gedig 4' )
	);
} );

test( 'Wenner, spaced ordinals and bracketed ranks all count as ranks', function (): void {
	expect( winnersOf( "WENNERS\nBrons Gedigte\nWenner: Gedig 3\n2de plek: Gedig 1\n3 DE PLEK : Gedig 2" ) )->toBe(
		array( 'brons gedig #1 Gedig 3', 'brons gedig #2 Gedig 1', 'brons gedig #3 Gedig 2' )
	);
	expect( winnersOf( "WENNERS\nBrons Gedigte\n(1) Gedig 3\n(2) Gedig 1" ) )->toBe(
		array( 'brons gedig #1 Gedig 3', 'brons gedig #2 Gedig 1' )
	);
} );

test( 'a Geen line closes a tie so the next entry line is not swallowed into it', function (): void {
	expect( winnersOf( "WENNERS\nBrons Gedigte\n1ste: Gedig 3\n2de: Geen\nGedig 7" ) )->toBe( array( 'brons gedig #1 Gedig 3' ) );
} );

test( 'the mentions come back next to the winners, and never become winners', function (): void {
	$parsed = ResultsParser::parse( "WENNERS\nBrons Gedigte\n1ste: Gedig 3\nEervolle vermelding: Gedig 9\nGedig 10\nSilwer Gedigte\n1: Gedig 4\nKOMMENTAAR\nGedig 3: T\nTeks" );

	expect( array_column( $parsed['winners'], 'entry_id' ) )->toBe( array( 'Gedig 3', 'Gedig 4' ) );
	expect( $parsed['mentions'] )->toBe(
		array(
			array( 'entry_id' => 'Gedig 9', 'text' => '' ),
			array( 'entry_id' => 'Gedig 10', 'text' => '' ),
		)
	);
} );

test( 'a plain-text mention is kept as printed, list marker dropped, even with a grade word in its title', function (): void {
	$parsed = ResultsParser::parse( "WENNERS\nBrons Gedigte\n1ste: Gedig 3\nEervolle vermeldings\n- Lig op water|Anna Smit\nGoud in die grond | Piet Pompies\nSilwer Gedigte\n1: Gedig 4" );

	expect( array_column( $parsed['mentions'], 'text' ) )->toBe( array( 'Lig op water | Anna Smit', 'Goud in die grond | Piet Pompies' ) );
	// The title's grade word opened no pool: Gedig 4 still sits under Silwer, from its real header.
	expect( $parsed['winners'][1]['grade'] )->toBe( 'silwer' );
} );

test( 'a paste with no mention heading has no mentions', function (): void {
	expect( ResultsParser::parse( "WENNERS\nBrons Gedigte\n1ste: Gedig 3" )['mentions'] )->toBe( array() );
} );

test( 'a pool header ends the mention list', function (): void {
	$parsed = ResultsParser::parse( "WENNERS\nEervolle vermelding\nGedig 9\nSilwer Gedigte\nGedig 10" );

	expect( array_column( $parsed['mentions'], 'entry_id' ) )->toBe( array( 'Gedig 9' ) );
} );

test( 'the judges\' own layout: a winners line per category, commentary under bare headings', function (): void {
	$text = "INK SIA AUGUSTUS 2026 KOMMENTAAR EN UITSLAE\nWENNERS\nBRONS: (1) No 9 (2) No 12 (3) Geen\nGOUD: (1) No 4\nPROSA: (1) No 2 (2) No 1\nVERHALE: (1) No 1\n\nBRONS\n1. Gee die hond wind \nGeen aanhaling nie.\n9. Geloof, Hoop en Liefde\nMooi.\n\nGOUD\n\n4. Die wêreld\nSterk.\n\nPROSA\nTussen die lyne\nDie skrywer gebruik Gandhi.\nPadkos vir die hart\nUitstaande.\n\nVERHALE\n\nDie een se dood\nHumoristies.\n";

	$parsed = ResultsParser::parse( $text );

	expect( array_map( fn( $w ) => $w['grade'] . '|' . $w['type'] . '|' . $w['rank'] . '|' . $w['entry_id'], $parsed['winners'] ) )->toBe(
		array( 'brons|gedig|1|Gedig 9', 'brons|gedig|2|Gedig 12', 'goud|gedig|1|Gedig 4', '|artikel|1|Artikel 2', '|artikel|2|Artikel 1', '|storie|1|Storie 1' )
	);
	// "Geen" places nobody. Gedigte carry their own number, Prosa and Verhale are numbered by position.
	expect( array_map( fn( $c ) => $c['type'] . '|' . $c['grade'] . '|' . $c['number'] . '|' . $c['title'], $parsed['commentary'] ) )->toBe(
		array(
			'gedig|brons|1|Gee die hond wind',
			'gedig|brons|9|Geloof, Hoop en Liefde',
			'gedig|goud|4|Die wêreld',
			'artikel||1|Tussen die lyne',
			'artikel||2|Padkos vir die hart',
			'storie||1|Die een se dood',
		)
	);
	expect( $parsed['commentary'][3]['text'] )->toBe( 'Die skrywer gebruik Gandhi.' );
	expect( $parsed['mentions'] )->toBe( array() );
} );

test( 'in the judges\' layout a tie takes the rank for both, and a title ending in an ellipsis stays a title', function (): void {
	$parsed = ResultsParser::parse( "WENNERS\nSILWER: (1) No 8 & No 9 (2) Geen\n\nSILWER\n8. …Waar die storms gaan lê\nGoed.\n" );

	expect( array_column( $parsed['winners'], 'entry_id' ) )->toBe( array( 'Gedig 8', 'Gedig 9' ) );
	expect( array_column( $parsed['winners'], 'rank' ) )->toBe( array( 1, 1 ) );

	$prose = ResultsParser::parse( "WENNERS\nPROSA: (1) No 1\n\nPROSA\nBewerk en bewaak ...\nEen paragraaf.\nTweede titel\nNog een.\n" );

	expect( array_column( $prose['commentary'], 'title' ) )->toBe( array( 'Bewerk en bewaak ...', 'Tweede titel' ) );
} );

test( 'in the judges\' layout a comment of several lines stays one comment', function (): void {
	$parsed = ResultsParser::parse( "WENNERS\nPROSA: (1) No 1\n\nPROSA\nTitel een\nEerste reël van die kommentaar is lank genoeg om duidelik 'n sin te wees en eindig met 'n punt.\nTweede reël, wat ook 'n sin is.\n" );

	expect( count( $parsed['commentary'] ) )->toBe( 1 );
	expect( $parsed['commentary'][0]['text'] )->toContain( "punt.\nTweede reël" );
} );

test( 'the judges\' layout, typed by hand: no colon, "No." and a stray full stop all read the same', function (): void {
	$parsed = ResultsParser::parse( "Kommentaar en uitslae September 2026\nWENNERS\nBRONS (1) No. 9 (2) No. 14 (3) No. 13\nGOUD (1) No. 4 (2) No. 6. (3) No. 12\nVERHALE (1) No. 7\n\nBRONS\n9. gety\nMooi.\n" );

	expect( array_column( $parsed['winners'], 'entry_id' ) )->toBe( array( 'Gedig 9', 'Gedig 14', 'Gedig 13', 'Gedig 4', 'Gedig 6', 'Gedig 12', 'Storie 7' ) );
	expect( array_column( $parsed['winners'], 'grade' ) )->toBe( array( 'brons', 'brons', 'brons', 'goud', 'goud', 'goud', '' ) );
} );

test( 'a category on a line of its own with its ranks on the next is read', function (): void {
	$parsed = ResultsParser::parse( "WENNERS\nBRONS\n(1) No 9 (2) No 12\nPROSA\n(1) No 2\n\nBRONS\n9. Titel\nTekst.\n" );

	expect( array_map( fn( $w ) => $w['grade'] . '|' . $w['type'] . '|' . $w['rank'] . '|' . $w['entry_id'], $parsed['winners'] ) )->toBe(
		array( 'brons|gedig|1|Gedig 9', 'brons|gedig|2|Gedig 12', '|artikel|1|Artikel 2' )
	);
	expect( $parsed['commentary'][0]['title'] )->toBe( 'Titel' );
} );

test( 'a poem with no number among numbered ones has no number, and is flagged', function (): void {
	$parsed = ResultsParser::parse( "WENNERS\nGOUD: (1) No 4\n\nGOUD\n\nDeur my oë (Dialoog)\nKommentaar een.\n4. Al wat sy kon red\nKommentaar twee.\n" );

	expect( array_column( $parsed['commentary'], 'number' ) )->toBe( array( 0, 4 ) );
	expect( $parsed['problems'] )->toHaveCount( 1 );
	expect( $parsed['problems'][0] )->toContain( 'Deur my oë (Dialoog)' )->toContain( 'geen nommer' );
} );

test( 'a file the parser cannot read says what to change, not what it skipped', function (): void {
	expect( ResultsParser::parse( "Gedig 3 was mooi\nGedig 5 ook\n" )['problems'][0] )->toContain( 'WENNERS' );

	$no_lines = ResultsParser::parse( "WENNERS\nDie wenners is Gedig 3 en 5\n" )['problems'][0];
	expect( $no_lines )->toContain( 'Reël 2' )->toContain( 'BRONS: (1) No 9' );

	$none_under = ResultsParser::parse( "WENNERS\n" )['problems'][0];
	expect( $none_under )->toContain( 'niks onder WENNERS' );
} );

test( 'winners with no category heading over the commentary say so, naming the line', function (): void {
	$problems = ResultsParser::parse( "WENNERS\nBRONS: (1) No 9\n\n9. Titel\nTekst.\n" )['problems'];

	expect( $problems[0] )->toContain( 'Reël 4' )->toContain( 'BRONS, SILWER, GOUD, PROSA of VERHALE' );
	expect( $problems[1] )->toContain( 'geen kommentaar onder ’n opskrif BRONS' );
} );

test( 'a title with no comment, a duplicated number and a category with no winners line are each named', function (): void {
	$problems = ResultsParser::parse( "WENNERS\nBRONS: (1) No 1\n\nBRONS\n1. Een\nTekst.\n1. Nog een\nTekst.\n2. Leeg\n\nPROSA\nStuk\nTekst.\n" )['problems'];
	$all      = implode( "\n", $problems );

	expect( $all )->toContain( 'Nommer 1 kom twee keer voor' );
	expect( $all )->toContain( '“Leeg” geen kommentaar' );
	expect( $all )->toContain( 'kommentaar onder PROSA, maar geen wenners-reël' );
} );

test( 'a clean file in the judges\' layout has no problems', function (): void {
	expect( ResultsParser::parse( "WENNERS\nBRONS: (1) No 1\n\nBRONS\n1. Een\nTekst.\n" )['problems'] )->toBe( array() );
} );

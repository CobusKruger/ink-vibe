<?php
/**
 * Unit tests for the legacy result-post winners parser.
 *
 * Target: {@see \Ink\Migration\LegacyResultsParser}. Layouts are real prod wordings (2018-2026).
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Migration;

use Ink\Migration\LegacyResultsParser as P;

/**
 * Compact "category/grading/position writer - title" strings for a post body.
 *
 * @param list<string> $known Known writer names.
 * @return list<string>
 */
function parsed( string $html, array $known = array() ): array {
	return array_map(
		static fn ( array $r ): string => "{$r['category']}/{$r['grading']}/{$r['position']} {$r['writer']} - {$r['title']}",
		P::parse( $html, $known )['rows']
	);
}

test( 'modern layout: "(1) No. 19 Writer met Title" under grade and category headings', function (): void {
	$html = "<strong>WENNERS:</strong>\n<strong>BRONS:</strong>\n(1) No. 19 Johan J van Rensburg met Ouma se kombuis\n(2) No. 7    Pierce van Tonder met In arendsklou\n"
		. "<strong>GOUD:</strong>\n(1) No. 21 Elmarie Nienaber van Kampen met Doodstyding\n<strong>PROSA:</strong>\n(1) No. 6 Hester Steenkamp met Eensaamheid\n"
		. "<strong>KOMMENTAAR</strong>\nBRONS:\n(1) No. 3 Iemand met Prosa wat nie 'n wenner is nie";

	expect( parsed( $html ) )->toBe(
		array(
			'Gedigte/Brons/1 Johan J van Rensburg - Ouma se kombuis',
			'Gedigte/Brons/2 Pierce van Tonder - In arendsklou',
			'Gedigte/Goud/1 Elmarie Nienaber van Kampen - Doodstyding',
			'Prosa//1 Hester Steenkamp - Eensaamheid',
		)
	);
} );

test( 'only text before the commentary is read, and the heading line carrying "Kommentaar" is not the boundary', function (): void {
	$html = "KOMMENTAAR EN UITSLAE JUNIE 2024\nGEDIGTE\nBrons:\n17.Retha van Deventer (Piketti) met Hersinskim\nKOMMENTAAR\nGEDIGTE\nBrons:\n1.Skyn met Skyn";

	expect( parsed( $html ) )->toBe( array( 'Gedigte/Brons/1 Retha van Deventer (Piketti) - Hersinskim' ) );
} );

test( 'a short heading ending in "Kommentaar" ends the winners region (Nov 2018)', function (): void {
	$html = "GEDIGTE WENNERS:\n<strong>BRONS:</strong>\n(1) Moria: Charleine Collins\n(3) Ontvlugting: See-Jay-El\n<strong>GEDIGTE KOMMENTAAR:</strong>\n"
		. "1. Woordeloos verloof: Marieta McGrath\n3. Die onwerklike mooi: Palet\nVERHALE BESPREKINGS\n(1) No 2 Iemand met Iets";
	$known = array( 'Charleine Collins', 'See-Jay-El', 'Marieta McGrath', 'Palet' );

	expect( parsed( $html, $known ) )->toBe(
		array(
			'Gedigte/Brons/1 Charleine Collins - Moria',
			'Gedigte/Brons/3 See-Jay-El - Ontvlugting',
		)
	);
} );

test( 'two-digit bracket numbers in the commentary do not turn the winners\' (1)-(3) into entry numbers', function (): void {
	$html = "GEDIGTE WENNERS:\nGOUD:\n(1) Strandfontein: Carma Shaw\n(2) Sag soen die Nylstroomson: Anzé Bezuidenhout\nGEDIGTE KOMMENTAAR:\n(10) Moria: Charleine Collins";
	$known = array( 'Carma Shaw', 'Anzé Bezuidenhout', 'Charleine Collins' );

	expect( parsed( $html, $known ) )->toBe(
		array(
			'Gedigte/Goud/1 Carma Shaw - Strandfontein',
			'Gedigte/Goud/2 Anzé Bezuidenhout - Sag soen die Nylstroomson',
		)
	);
} );

test( 'a title above the lists that ends in "kommentaar" does not end the region', function (): void {
	$html = "GEDIGTE\nUitslae en kommentaar\nBRONS\n(1) No 3 Iemand met Titel";

	expect( parsed( $html ) )->toBe( array( 'Gedigte/Brons/1 Iemand - Titel' ) );
} );

test( 'with a position marker, an unknown writer must still be shaped like a name', function (): void {
	$html = "GEDIGTE\nBRONS\n1. Annamarie vd Merwe met Hartsdief\n2. Retha van Deventer (Piketti) met Hersinskim\n3. Karoo in daardie konteks met ‘n hoofletter.";

	expect( parsed( $html ) )->toBe(
		array(
			'Gedigte/Brons/1 Annamarie vd Merwe - Hartsdief',
			'Gedigte/Brons/2 Retha van Deventer (Piketti) - Hersinskim',
		)
	);
} );

test( 'a glued entry number is not a position, so order inside the block decides', function (): void {
	$html = "GEDIGTE\nBrons:\n17.Retha van Deventer met Hersinskim\n27.Anne-Marie Kotzee met 'n Nuwe normaal\n20.Cobus Kruger met Wintersoggend";

	expect( parsed( $html ) )->toBe(
		array(
			'Gedigte/Brons/1 Retha van Deventer - Hersinskim',
			'Gedigte/Brons/2 Anne-Marie Kotzee - \'n Nuwe normaal',
			'Gedigte/Brons/3 Cobus Kruger - Wintersoggend',
		)
	);
} );

test( 'bracketed numbers are entry numbers when any of them is two digits and carries no "No."', function (): void {
	$html = "GEDIGTE:\nBRONS:\n(11) Christa Diederiks met Akwakooi\n(12) Aniki Joseph met Moedertaal\nGeen\nPROSA:\n(8) Marlene Erasmus met Wanneer die aarde sug\n(3) Ano met Dag zero";

	expect( parsed( $html ) )->toBe(
		array(
			'Gedigte/Brons/1 Christa Diederiks - Akwakooi',
			'Gedigte/Brons/2 Aniki Joseph - Moedertaal',
			'Prosa//1 Marlene Erasmus - Wanneer die aarde sug',
			'Prosa//2 Ano - Dag zero',
		)
	);
} );

test( '"Geen" consumes a position but yields no row', function (): void {
	$html = "GEDIGTE\nBRONS\nGEEN\nNR 3 Iemand met Een\nSILWER\nNR 1 Ander met Twee";

	expect( parsed( $html ) )->toBe( array( 'Gedigte/Brons/2 Iemand - Een', 'Gedigte/Silwer/1 Ander - Twee' ) );
} );

test( 'explicit positions, "Wenner:" and spaced "3 DE PLEK :" markers', function (): void {
	$html = "GEDIGTE\nGOUD:\nWENNER: No 25 – Carma Shaw met Boer van de Aar\n2 DE PLEK: No 18 – Carma Shaw met Meisie Lotter\n3 DE PLEK Anzé Bezuidenhout met Piet Pompies";

	expect( parsed( $html ) )->toBe(
		array(
			'Gedigte/Goud/1 Carma Shaw - Boer van de Aar',
			'Gedigte/Goud/2 Carma Shaw - Meisie Lotter',
			'Gedigte/Goud/3 Anzé Bezuidenhout - Piet Pompies',
		)
	);
} );

test( 'a header may carry its first entry, and a header typo within one letter still counts', function (): void {
	$html = "Gedigte:\nGOUD: 1ste. Jou voetspore bepaal – Anzé Bezuidenhout\n2de. Hunkering – Carma Shaw\nARTKELS\nNO 1 Ano met Musiek vir beter gesondheid";

	expect( parsed( $html, array( 'Anzé Bezuidenhout', 'Carma Shaw' ) ) )->toBe(
		array(
			'Gedigte/Goud/1 Anzé Bezuidenhout - Jou voetspore bepaal',
			'Gedigte/Goud/2 Carma Shaw - Hunkering',
			'Artikels//1 Ano - Musiek vir beter gesondheid',
		)
	);
} );

test( 'writer-last layouts are only split when exactly one side is a known writer', function (): void {
	$html = "GEDIGTE\nGoud:\nWenner: Carma Shaw - ’n Storie uit Donkerbos\n2de: Roete kodes - Anzé Besuidenhout\n3de: Niemand Bekend - Net 'n Titel";
	$known = array( 'Carma Shaw', 'Anzé Besuidenhout' );

	$result = P::parse( $html, $known );

	expect( count( $result['rows'] ) )->toBe( 2 );
	expect( $result['rows'][0]['writer'] )->toBe( 'Carma Shaw' );
	expect( $result['rows'][1]['writer'] )->toBe( 'Anzé Besuidenhout' );
	expect( $result['unparsed'] )->not->toBe( array() ); // the unknown pair is reported, not guessed.
} );

test( 'a title that contains "met" is accepted only when the writer before the first "met" is known', function (): void {
	$html = "GEDIGTE\nGOUD\n(1) NO 19 Marlene Erasmus met Husse met lang ore";

	expect( parsed( $html, array( 'Marlene Erasmus' ) ) )->toBe( array( 'Gedigte/Goud/1 Marlene Erasmus - Husse met lang ore' ) );
	expect( parsed( $html ) )->toBe( array() );
} );

test( 'two winners sharing a position on one line produce one row each', function (): void {
	$html = "GEDIGTE\nSILWER\n2. No. 14 & 15 Barbara Smit met Liefde & Amanda De Meillon met My land, my lewe, my liefde";

	expect( parsed( $html ) )->toBe(
		array(
			'Gedigte/Silwer/2 Barbara Smit - Liefde',
			'Gedigte/Silwer/2 Amanda De Meillon - My land, my lewe, my liefde',
		)
	);
} );

test( 'prose in the commentary is never read as an entry', function (): void {
	$html = "GEDIGTE\nBRONS\n(1) No 3 Iemand met Titel\nDit is ‘n menslike gedig met nostalgie en verlange, maar jy het ongelukkig nie die opdrag nagekom nie.";

	expect( parsed( $html ) )->toBe( array( 'Gedigte/Brons/1 Iemand - Titel' ) );
} );

test( 'honourable mentions are not winners, and a repeated heading ends the winners region', function (): void {
	$html = "GEDIGTE\nBRONS\n(1) No 3 Iemand met Titel\nEervolle vermelding: No 28 Ander met Iets\nGEDIGTE\nBRONS\n(1) No 4 Derde met Ander";

	expect( parsed( $html ) )->toBe( array( 'Gedigte/Brons/1 Iemand - Titel' ) );
} );

test( 'entries listed under an honourable-mention heading are not winners, up to the next heading', function (): void {
	$html = "GEDIGTE\nGoud\n1: no 16; Carma Shaw met Haikoe\nBrons\n1: No. 18; Madzi met Winterskets\nDie volgende gedigte het almal 'n bogemiddelde persentasie gekry:\nEervolle vermeldings in goud, silwer en brons afdelings\nNo.5; Callie Vermeulen met Tsubaki\nVERHALE:\n1: No. 3 (69%); Wilma met Sun en Moon";

	expect( parsed( $html ) )->toBe(
		array(
			'Gedigte/Goud/1 Carma Shaw - Haikoe',
			'Gedigte/Brons/1 Madzi - Winterskets',
			'Verhale//1 Wilma - Sun en Moon',
		)
	);
} );

test( 'January 2021 layout: "no 28 / Title Writer", split at a known trailing writer', function (): void {
	$html = "Gedigte:\nBrons:\nno 26 / Vergeet en vergeef Reinette Thiart\nno 14/ Troudag Andries Fourie (Seirdna)\nno 1 / Oordenking (skrywer onbekend)\n";

	expect( parsed( $html, array( 'Reinette Thiart', 'Andries Fourie' ) ) )->toBe(
		array(
			'Gedigte/Brons/1 Reinette Thiart - Vergeet en vergeef',
			'Gedigte/Brons/2 Andries Fourie (Seirdna) - Troudag',
		)
	);
} );

test( 'January 2021 layout: a writer with no account still keeps their place in the order', function (): void {
	$html = "Gedigte:\nBrons:\nno 28 / Alexanderbaai Annemarie vd Merwe\nno 26 / Vergeet en vergeef Reinette Thiart\nno 10 / Reen Helena Mellet\n";

	expect( parsed( $html, array( 'Helena Mellet' ) ) )->toBe(
		array(
			'Gedigte/Brons/1 Annemarie vd Merwe - Alexanderbaai',
			'Gedigte/Brons/2 Reinette Thiart - Vergeet en vergeef',
			'Gedigte/Brons/3 Helena Mellet - Reen',
		)
	);
} );

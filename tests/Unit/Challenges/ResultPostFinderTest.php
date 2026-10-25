<?php
/**
 * Unit tests for the challenge result-post finder.
 *
 * Target: {@see \Ink\Challenges\ResultPostFinder}. Titles are real prod wordings.
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Challenges;

use Ink\Challenges\ResultPostFinder;

test( 'result titles of every era are recognised', function (): void {
	foreach (
		array(
			'Wenners: Winter nostalgie projek',
			'WENNERS: - LIEFDE -PROJEK: FEBRUARIE 2017',
			'MAART 2018 PROJEK: UITSLAE EN KOMMENTAAR',
			'APRIL PROJEK: Kommentare en uitslae',
			'Januarie 2021 - Verwagting/e-projek Uitslag en kommentaar',
			'Maart 2026 - Herfs/Kas projek - Komentaar en uitslae',
			'November 2024 - OOP projek - uitslae en kommentaar',
			'Wenners van die GrysKraG kompetisie',
		) as $title
	) {
		expect( ResultPostFinder::isResultTitle( $title ) )->toBeTrue( $title );
	}
} );

test( 'ordinary writing is not mistaken for a result post', function (): void {
	foreach ( array( 'Oue Duitsland', 'naweek;  DIE WENNER', 'My projek vir skool', 'Uitslae van die rugbywedstryd' ) as $title ) {
		expect( ResultPostFinder::isResultTitle( $title ) )->toBeFalse( $title );
	}
} );

test( 'titleMarker reads the hand-typed grade and ignores anything else', function (): void {
	expect( ResultPostFinder::titleMarker( 'onder die akkerboom (GOUD)' ) )->toBe( 'goud' );
	expect( ResultPostFinder::titleMarker( 'skrywersblok (brons)' ) )->toBe( 'brons' );
	expect( ResultPostFinder::titleMarker( 'Goud en silwer' ) )->toBeNull();
} );

test( 'monthYear reads the first month and year, or null when there is no year', function (): void {
	expect( ResultPostFinder::monthYear( 'Oktober 2026 - Getal 10 projek' ) )->toBe( array( 10, 2026 ) );
	expect( ResultPostFinder::monthYear( 'November & Desember 2025 - OOP projek - Kommentaar en uitslae' ) )->toBe( array( 11, 2025 ) );
	expect( ResultPostFinder::monthYear( 'APRIL 2022 - EN TOE... PROJEK - Kommentaar en uitslae' ) )->toBe( array( 4, 2022 ) );
	expect( ResultPostFinder::monthYear( 'APRIL PROJEK: Kommentare en uitslae' ) )->toBeNull();
	expect( ResultPostFinder::monthYear( 'Wenners van die Amakeia projek' ) )->toBeNull();
} );

test( 'matchUitdaging links only when exactly one uitdaging has that month and year', function (): void {
	$uit = array(
		1 => 'Maart 2023 - OOP projek',
		2 => 'April 2023 - Klug projek',
		3 => 'Mei 2023 - OOP projek',
		4 => 'Mei 2023 - Tweede projek',
	);

	expect( ResultPostFinder::matchUitdaging( array( 5, 2023 ), $uit ) )->toBeNull(); // ambiguous
	expect( ResultPostFinder::matchUitdaging( array( 4, 2023 ), $uit ) )->toBe( 2 );
	expect( ResultPostFinder::matchUitdaging( array( 6, 2023 ), $uit ) )->toBeNull(); // none
	expect( ResultPostFinder::matchUitdaging( null, $uit ) )->toBeNull();
} );

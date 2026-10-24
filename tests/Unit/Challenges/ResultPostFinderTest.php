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

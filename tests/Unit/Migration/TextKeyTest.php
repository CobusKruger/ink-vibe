<?php
/**
 * Unit tests for the comparison keys for typed Afrikaans text.
 *
 * Target: {@see \Ink\Migration\TextKey}.
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Migration;

use Ink\Migration\TextKey;

test( 'every way of typing the article \'n gives one key', function ( string $typed ): void {
	expect( TextKey::text( $typed ) )->toBe( 'watnramp' );
} )->with(
	array(
		"Wat 'n ramp",
		"Wat \u{2019}n ramp",
		"Wat \u{2018}n ramp",
		"Wat \u{2018}\u{2019}n ramp",
		"Wat \u{02BC}n ramp",
		'Wat `n ramp',
		"Wat \u{00B4}n ramp",
		"Wat \u{2032}n ramp",
		"Wat \u{0149} ramp",
		"Wat \u{0149}ramp",
		"Wat \u{00F1} ramp",
		"Wat \u{00D1} ramp",
		"Wat \u{0144} ramp",
		"Wat \u{0143} ramp",
		'Wat n ramp',
		'Wat N ramp',
		"WAT 'N RAMP",
		'Wat &#8217;n ramp',
		'Wat &amp;#8217;n ramp',
		"Wat \u{00E2}\u{20AC}\u{2122}n ramp",
	)
);

test( 'accents, case, spacing and punctuation do not matter', function ( string $a, string $b ): void {
	expect( TextKey::text( $a ) )->toBe( TextKey::text( $b ) );
} )->with(
	array(
		array( 'Wat is jóú voorgee?', 'wat is jou voorgee' ),
		array( 'Reën in die Groot Karoo', 'Reen in die groot karoo' ),
		array( 'Spieëlhand', 'SPIEELHAND' ),
		array( 'Seëpoort', 'Seepoort' ),
		array( "e\u{0301}en", 'een' ),
		array( 'Lente-kotiljons', 'Lente kotiljons' ),
		array( 'Lankmoe ( r )digheid', 'Lankmoe(r)digheid' ),
		array( "Die seun se' pa", "Die seun se pa" ),
	)
);

test( 'a title may gain or lose a leading \'n, whichever way it was typed', function ( string $typed ): void {
	expect( TextKey::title( $typed ) )->toBe( 'nuwebegin' );
} )->with(
	array( 'Nuwe begin', "'n Nuwe begin", "\u{2018}n Nuwe begin", "\u{0149} Nuwe begin", "\u{0149}Nuwe begin", "\u{00F1} Nuwe begin", "\u{0144} Nuwe begin", 'n Nuwe begin', 'N nuwe begin' )
);

test( 'a title that is only the letter stays, and a word starting with n is left alone', function (): void {
	expect( TextKey::title( 'N' ) )->toBe( 'n' );
	expect( TextKey::title( 'Nag' ) )->toBe( 'nag' );
	expect( TextKey::title( 'Nag en dag' ) )->toBe( 'nagendag' );
} );

test( 'names fold accents and ignore brackets, and keep a leading initial', function (): void {
	expect( TextKey::name( 'Anzé Bezuidenhout' ) )->toBe( 'anzebezuidenhout' );
	expect( TextKey::name( 'Anzè Bezuidenhout' ) )->toBe( 'anzebezuidenhout' );
	expect( TextKey::name( 'Anze Bezuidenhout' ) )->toBe( 'anzebezuidenhout' );
	expect( TextKey::name( 'Carma Shaw (Viooltjie)' ) )->toBe( 'carmashaw' );
	expect( TextKey::name( 'N Smit' ) )->toBe( 'nsmit' );
} );

test( 'a grade added to a title goes, in every form found on prod', function ( string $typed, string $bare ): void {
	expect( TextKey::title( $typed ) )->toBe( TextKey::text( $bare ) );
} )->with(
	array(
		array( 'Die dorpsmuseum (Brons)', 'Die dorpsmuseum' ),
		array( 'Die dorpsmuseum ( brons)', 'Die dorpsmuseum' ),
		array( 'Die dorpsmuseum [BRONS]', 'Die dorpsmuseum' ),
		array( '[BRONS] Die dorpsmuseum', 'Die dorpsmuseum' ),
		array( 'Die dorpsmuseum {silwer}', 'Die dorpsmuseum' ),
		array( 'Die dorpsmuseum (Silver)', 'Die dorpsmuseum' ),
		array( 'Haikoe 1 (Silwer gradering)', 'Haikoe 1' ),
		array( 'Holte in my hart (stergradering Silwer)', 'Holte in my hart' ),
		array( 'asem                  STERGRADERING - BRONS', 'asem' ),
		array( 'Haikoe (2) (STRERGRADERING = BRONS)', 'Haikoe (2)' ),
		array( 'In my hande (brons afdeling)', 'In my hande' ),
		array( 'In my hande (brons kategorie)', 'In my hande' ),
		array( 'In my hande (silwer klas)', 'In my hande' ),
		array( 'Kos vir die siel (brons of silver, nie seker)', 'Kos vir die siel' ),
		array( 'Die melkweg baar Goudklas', 'Die melkweg baar' ),
		array( 'Sinkretisme bronsklas', 'Sinkretisme' ),
		array( 'Wintertaal bronsgradering', 'Wintertaal' ),
		array( 'Ek skilder jou - Katergorie - Goud', 'Ek skilder jou' ),
		array( 'Stille waters (silwer 490 woorde)', 'Stille waters' ),
		array( 'Stille waters (silver – 391 woorde)', 'Stille waters' ),
		array( 'Stille waters (brons 574 woode)', 'Stille waters' ),
		array( 'Stille waters     Silwer 350 Woorde April projek', 'Stille waters' ),
		array( 'Oupa en die Canabis   1598 woorde Silwer Januarie 2022 projek', 'Oupa en die Canabis' ),
		array( 'Adam     Goud     November projek', 'Adam' ),
		array( 'Onthou                (Klas silwer: Desember oop projek)', 'Onthou' ),
		array( 'Repliek    Oop projek klas silwer November/Desember', 'Repliek' ),
		array( 'Tjailatyd        Silwer', 'Tjailatyd' ),
		array( 'Silwer- Limiriek', 'Limiriek' ),
		array( 'Goud: Vlam', 'Vlam' ),
		array( 'Gamkaskloof se kronkelpad. Goudklas', 'Gamkaskloof se kronkelpad' ),
		array( 'Bronsink (BRONS)', 'Bronsink' ),
		array( 'Stoepsit-liasering - (Silwer)', 'Stoepsit-liasering' ),
	)
);

test( 'a grade word that belongs to the title stays', function ( string $title ): void {
	expect( TextKey::title( $title ) )->toBe( TextKey::text( $title ) );
} )->with(
	array( 'Drie silwer bangles', 'Goudgeel lande van stof', 'Rooigoud Skadu', 'Silwerblink trane', 'Goue stervisse', 'Goud', 'Silwer', 'Bron van Vreugde', 'Die 25 klas', 'Skakering van liefde', '2 Julie 2018', 'Winde van verandering' )
);

test( 'a title that is nothing but a marker keeps its text', function (): void {
	expect( TextKey::title( '(Goud)' ) )->toBe( 'goud' );
	expect( TextKey::withoutMarkers( '(Goud)' ) )->toBe( '' );
} );

<?php
/**
 * Unit tests for finding the post that announced a challenge round.
 *
 * Target: {@see \Ink\Content\ChallengeAnnouncement}.
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Content;

use Ink\Content\ChallengeAnnouncement;

test( 'the earliest moderator post of the round is its announcement, whatever its date order in the list', function (): void {
	$id = ChallengeAnnouncement::pick(
		array(
			array( 'id' => 12, 'date' => '2026-10-20 10:00:00', 'title' => 'Oktober 2026 - Getal 10 projek: nuus' ),
			array( 'id' => 5, 'date' => '2026-10-01 15:17:32', 'title' => 'Oktober 2026 - Getal 10 projek' ),
		),
		'Oktober 2026 - Getal 10 projek'
	);

	expect( $id )->toBe( 5 );
} );

test( 'a typo or different punctuation in the title does not matter', function (): void {
	expect(
		ChallengeAnnouncement::pick(
			array( array( 'id' => 7, 'date' => '2026-07-01 14:20:39', 'title' => 'Julle 2026 DIS NOG NIKS... LAAT EK JOU VERTEL projek' ) ),
			'Julie 2026 - Dis nog niks, laat ek jou vertel projek'
		)
	)->toBe( 7 );

	expect(
		ChallengeAnnouncement::pick(
			array( array( 'id' => 8, 'date' => '2026-06-02 09:34:10', 'title' => 'JUNIE 2026 - STREEK/OMGEWING PROJEK' ) ),
			'JUNIE 2026 - STREEK PROJEK'
		)
	)->toBe( 8 );
} );

test( 'a later news post is not mistaken for the announcement of a round that had none', function (): void {
	expect(
		ChallengeAnnouncement::pick(
			array( array( 'id' => 9, 'date' => '2017-04-29 12:40:39', 'title' => 'Kompetisie nuus!!!!! GROOT pryse op die spel SLUIT 30 April 2017' ) ),
			'SA Vryskutskrywer kompetisie'
		)
	)->toBeNull();
} );

test( 'no candidates, no announcement', function (): void {
	expect( ChallengeAnnouncement::pick( array(), 'Enige ronde' ) )->toBeNull();
} );

test( 'normalise ignores case, entities, tags and punctuation', function (): void {
	expect( ChallengeAnnouncement::normalise( 'Oktober 2026 &#8211; <b>Getal</b> 10 projek!' ) )->toBe( 'oktober 2026 getal 10 projek' );
} );

test( 'monthLabel gives the Afrikaans month and year of a date, whatever language WordPress runs in', function (): void {
	expect( \Ink\Content\ChallengeResultTitle::monthLabel( '2026-10-01 15:17:32' ) )->toBe( 'Oktober 2026' )
		->and( \Ink\Content\ChallengeResultTitle::monthLabel( '2017-03-03' ) )->toBe( 'Maart 2017' )
		->and( \Ink\Content\ChallengeResultTitle::monthLabel( 'nie-\'n-datum' ) )->toBe( '' )
		->and( \Ink\Content\ChallengeResultTitle::monthLabel( '2026-13-01 00:00:00' ) )->toBe( '' );
} );

test( 'year reads the first 20xx year in a round name', function (): void {
	expect( \Ink\Content\ChallengeResultTitle::year( 'INK Dig- en skryfkompetisie 2026' ) )->toBe( 2026 )
		->and( \Ink\Content\ChallengeResultTitle::year( 'Groot skryf kompetisie' ) )->toBeNull();
} );

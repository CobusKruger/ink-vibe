<?php
/**
 * Unit tests for the winner importer's matching rules.
 *
 * Target: {@see \Ink\Migration\WinnerMatcher}. Pure.
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Migration;

use Ink\Migration\WinnerMatcher;

/**
 * A candidate post.
 *
 * @param int       $id     The id.
 * @param string    $title  The title.
 * @param string    $date   The date.
 * @param list<int> $rounds The rounds it is an entry of.
 * @return array{id:int, title:string, date:string, rounds:list<int>}
 */
function winner_post( int $id, string $title, string $date = '2020-03-10 10:00:00', array $rounds = array() ): array {
	return array(
		'id'     => $id,
		'title'  => $title,
		'date'   => $date,
		'rounds' => $rounds,
	);
}

test( 'a title matches through the text key: accents, the article, case and a grade added to the title do not matter', function (): void {
	$posts = array(
		winner_post( 1, 'Die sprokie' ),
		winner_post( 2, "'n Perd van 'n ander kleur (Silwer gradering)" ),
		winner_post( 3, 'Heeltemal anders' ),
	);

	expect( array_column( WinnerMatcher::matches( 'DIE SPROKIE', $posts ), 'id' ) )->toBe( array( 1 ) );
	expect( array_column( WinnerMatcher::matches( 'ŉ Perd van ŉ ander kleur', $posts ), 'id' ) )->toBe( array( 2 ) );
	expect( WinnerMatcher::matches( '', $posts ) )->toBe( array() );
} );

test( 'a post may add to the title or hold it, but a short title does not match by containment', function (): void {
	$posts = array(
		winner_post( 1, 'Die onderhoud (Geskiedenisfiksie)' ),
		winner_post( 2, 'Villanelle: Na jou asem weg is' ),
		winner_post( 3, 'Reen en wind en meer' ),
	);

	$added = WinnerMatcher::matches( 'Die onderhoud', $posts );
	$held  = WinnerMatcher::matches( 'Na jou asem weg is', $posts );

	expect( array_column( $added, 'id' ) )->toBe( array( 1 ) );
	expect( $added[0]['score'] )->toBe( WinnerMatcher::CONTAINED );
	expect( array_column( $held, 'id' ) )->toBe( array( 2 ) );
	expect( WinnerMatcher::matches( 'Reen', $posts ) )->toBe( array() ); // four letters: too short to hold
} );

test( 'pick prefers a post filed under the round, then the better match, then the window, then the nearest date', function (): void {
	$result = '2020-04-05 09:00:00';

	$filed   = winner_post( 10, 'Reen op die dak (Brons)', '2019-01-01 00:00:00', array( 7 ) ); // contained, but in the round
	$exact   = winner_post( 11, 'Reen op die dak', '2020-03-20 00:00:00' );
	$repost  = winner_post( 12, 'Reen op die dak', '2021-06-01 00:00:00' );
	$matches = WinnerMatcher::matches( 'Reen op die dak', array( $filed, $exact, $repost ) );

	expect( WinnerMatcher::pick( $matches, 7, $result ) )->toBe( 10 ); // the round decides
	expect( WinnerMatcher::pick( $matches, 0, $result ) )->toBe( 11 ); // else the exact one inside the window
	expect( WinnerMatcher::pick( array( $matches[2] ), 0, $result ) )->toBe( 12 ); // a lone repost still identifies the post
	expect( WinnerMatcher::pick( array(), 7, $result ) )->toBeNull();
} );

test( 'pick breaks a tie by the lowest id', function (): void {
	$matches = WinnerMatcher::matches( 'Dieselfde titel', array( winner_post( 9, 'Dieselfde titel' ), winner_post( 4, 'Dieselfde titel' ) ) );

	expect( WinnerMatcher::pick( $matches, 0, '2020-04-05 00:00:00' ) )->toBe( 4 );
} );

test( 'a result post with no link takes the round that holds most of its winners, if one clearly leads', function (): void {
	$dates = array(
		5 => '2020-02-27 00:00:00',
		6 => '2020-03-27 00:00:00',
		7 => '2018-01-01 00:00:00',
	);

	expect( WinnerMatcher::chooseRound( array( 5 => 3, 6 => 1 ), $dates, '2020-04-10 00:00:00' ) )->toBe( 5 );
	expect( WinnerMatcher::chooseRound( array( 5 => 2, 6 => 2 ), $dates, '2020-04-10 00:00:00' ) )->toBe( 0 ); // a tie
	expect( WinnerMatcher::chooseRound( array( 7 => 4, 6 => 1 ), $dates, '2020-04-10 00:00:00' ) )->toBe( 6 ); // 7 started two years before
	expect( WinnerMatcher::chooseRound( array( 6 => 1 ), $dates, '2020-03-01 00:00:00' ) )->toBe( 0 ); // 6 had not started
	expect( WinnerMatcher::chooseRound( array(), $dates, '2020-04-10 00:00:00' ) )->toBe( 0 );
} );

test( 'a near match (a typo) counts only for a post filed under the round, and never when the numbers differ', function (): void {
	$typo   = winner_post( 1, 'Kintsugi', '2020-03-10 10:00:00', array( 7 ) );
	$other  = winner_post( 2, 'Kintsugi', '2020-03-10 10:00:00', array() );
	$tanka  = winner_post( 3, 'Tanka 1', '2020-03-10 10:00:00', array( 7 ) );
	$bigger = winner_post( 4, 'Die prys van die liefde. (BRONS)', '2020-03-10 10:00:00', array( 7 ) );

	$matches = WinnerMatcher::matches( 'Kuntsugi', array( $typo, $other ) );

	expect( array_column( $matches, 'score' ) )->toBe( array( WinnerMatcher::CLOSE, WinnerMatcher::CLOSE ) );
	expect( WinnerMatcher::pick( $matches, 7, '2020-04-05 00:00:00' ) )->toBe( 1 );   // filed under the round
	expect( WinnerMatcher::pick( $matches, 8, '2020-04-05 00:00:00' ) )->toBeNull();  // another round: too weak
	expect( WinnerMatcher::pick( $matches, 0, '2020-04-05 00:00:00' ) )->toBeNull();  // no round known: too weak

	expect( WinnerMatcher::matches( 'Tanka 2', array( $tanka ) ) )->toBe( array() );  // the numbers differ
	expect( array_column( WinnerMatcher::matches( 'Die prys van liefde', array( $bigger ) ), 'id' ) )->toBe( array( 4 ) );
	expect( WinnerMatcher::isClose( 'kort', 'kors' ) )->toBeFalse();                  // too short to judge
} );

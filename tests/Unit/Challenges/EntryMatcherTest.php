<?php
/**
 * Unit tests for {@see \Ink\Challenges\EntryMatcher}.
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Challenges;

use Ink\Challenges\EntryMatcher;

/** A round entry. */
function ink_m_entry( int $id, string $type, string $pool, int $number, string $title ): array {
	return array( 'id' => $id, 'type' => $type, 'pool' => $pool, 'number' => $number, 'title' => $title );
}

/** What the judges wrote about. */
function ink_m_want( string $type, string $grade, int $number, string $title ): array {
	return array( 'type' => $type, 'grade' => $grade, 'number' => $number, 'title' => $title );
}

test( 'a title matches through the project comparison: accents, case, a leading \'n and a grade marker count for nothing', function (): void {
	$entries = array(
		ink_m_entry( 1, 'gedig', 'goud', 0, 'leefreël – (Goud)' ),
		ink_m_entry( 2, 'gedig', 'goud', 0, '… waar die storms gaan lê (SILWER)' ),
		ink_m_entry( 3, 'artikel', 'brons', 0, 'Tussen die lyne       790 woorde Goud' ),
	);

	$found = EntryMatcher::match(
		array(
			ink_m_want( 'gedig', 'goud', 11, 'leefreel' ),
			ink_m_want( 'gedig', 'goud', 8, '…Waar die storms gaan lê' ),
			ink_m_want( 'artikel', '', 1, 'Tussen die lyne' ),
		),
		$entries
	);

	expect( $found )->toBe(
		array(
			0 => array( 'post_id' => 1, 'how' => 'titel' ),
			1 => array( 'post_id' => 2, 'how' => 'titel' ),
			2 => array( 'post_id' => 3, 'how' => 'titel' ),
		)
	);
} );

test( 'a slip in a title still finds its entry within the pool, and is marked to check', function (): void {
	$found = EntryMatcher::match(
		array(
			ink_m_want( 'gedig', 'silwer', 2, 'Que Vadis' ),
			ink_m_want( 'gedig', 'goud', 4, 'Die wêreld is ’n sandkorrel' ),
		),
		array(
			ink_m_entry( 1, 'gedig', 'silwer', 0, 'Quo Vadis' ),
			ink_m_entry( 2, 'gedig', 'goud', 0, '’n Wêreld in ’n sandkorrel' ),
			ink_m_entry( 3, 'gedig', 'goud', 0, 'leefreël' ),
		)
	);

	expect( $found )->toBe(
		array(
			0 => array( 'post_id' => 1, 'how' => 'ooreenkoms' ),
			1 => array( 'post_id' => 2, 'how' => 'ooreenkoms' ),
		)
	);
} );

test( 'an unlike title is no match, even when the number is the same', function (): void {
	$found = EntryMatcher::match(
		array( ink_m_want( 'gedig', 'brons', 1, 'Nag oor die dorp' ) ),
		array( ink_m_entry( 1, 'gedig', 'brons', 1, 'Maanlig' ) )
	);

	expect( $found )->toBe( array() );
} );

test( 'the pool is tried first; the same title in another pool matches but is marked doubtful', function (): void {
	$found = EntryMatcher::match(
		array( ink_m_want( 'gedig', 'brons', 1, 'Nag' ), ink_m_want( 'gedig', 'silwer', 2, 'Nag' ) ),
		array( ink_m_entry( 1, 'gedig', 'silwer', 0, 'Nag' ), ink_m_entry( 2, 'gedig', 'brons', 0, 'Nag' ) )
	);

	expect( $found[0] )->toBe( array( 'post_id' => 2, 'how' => 'titel' ) );
	expect( $found[1] )->toBe( array( 'post_id' => 1, 'how' => 'titel' ) );

	$other = EntryMatcher::match( array( ink_m_want( 'gedig', 'brons', 1, 'Nag' ) ), array( ink_m_entry( 1, 'gedig', 'goud', 0, 'Nag' ) ) );

	expect( $other[0] )->toBe( array( 'post_id' => 1, 'how' => 'titel_ander_poel' ) );
} );

test( 'a type is never crossed, and one entry is never claimed twice', function (): void {
	$found = EntryMatcher::match(
		array( ink_m_want( 'storie', '', 1, 'Nag' ), ink_m_want( 'gedig', 'brons', 2, 'Nag' ) ),
		array( ink_m_entry( 1, 'gedig', 'brons', 0, 'Nag' ) )
	);

	expect( $found )->toBe( array( 1 => array( 'post_id' => 1, 'how' => 'titel' ) ) );
} );

test( 'with no title in the document a number finds the entry carrying it', function (): void {
	$found = EntryMatcher::match(
		array( ink_m_want( 'gedig', 'brons', 2, '' ), ink_m_want( 'gedig', 'brons', 9, '' ) ),
		array( ink_m_entry( 1, 'gedig', 'brons', 1, 'A' ), ink_m_entry( 2, 'gedig', 'brons', 2, 'B' ) )
	);

	expect( $found )->toBe( array( 0 => array( 'post_id' => 2, 'how' => 'nommer' ) ) );
} );

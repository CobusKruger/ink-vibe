<?php
/**
 * Unit tests for the results coverage report (Story 12A.3, FR-50-R2).
 *
 * Target: {@see \Ink\Challenges\Coverage} — reconciles parsed winners + commentary
 * against the stored EntryIDs and decides whether a hard gap blocks the commit.
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Challenges;

use Ink\Challenges\Coverage;
use Brain\Monkey;

beforeEach( function (): void {
	Monkey\setUp();
} );

afterEach( function (): void {
	Monkey\tearDown();
} );

test( 'report matches winners + commentary against stored ids and flags the gaps', function (): void {
	$winners = array(
		array( 'grade' => 'brons', 'type' => 'gedig', 'rank' => 1, 'entry_id' => 'Gedig 1' ),
		array( 'grade' => 'brons', 'type' => 'gedig', 'rank' => 2, 'entry_id' => 'Gedig 9' ), // unknown
	);
	$commentary = array(
		array( 'entry_id' => 'Gedig 1', 'title' => 'A', 'text' => 'x' ),
	);
	$stored = array( 'Gedig 1', 'Gedig 2' );

	$report = Coverage::report( $winners, $commentary, $stored );

	expect( $report['matched_winners'] )->toBe( array( 'Gedig 1' ) );
	expect( $report['unknown_winners'] )->toBe( array( 'Gedig 9' ) );
	expect( $report['entries_without_commentary'] )->toBe( array( 'Gedig 2' ) );
	expect( $report['all_winners_identified'] )->toBeFalse();
	expect( $report['all_commentary_resolved'] )->toBeFalse();
} );

test( 'report is all-clear when every winner matches and every entry has commentary', function (): void {
	$winners    = array( array( 'grade' => 'brons', 'type' => 'gedig', 'rank' => 1, 'entry_id' => 'Gedig 1' ) );
	$commentary = array(
		array( 'entry_id' => 'Gedig 1', 'title' => 'A', 'text' => 'x' ),
		array( 'entry_id' => 'Gedig 2', 'title' => 'B', 'text' => 'y' ),
	);
	$stored = array( 'Gedig 1', 'Gedig 2' );

	$report = Coverage::report( $winners, $commentary, $stored );

	expect( $report['all_winners_identified'] )->toBeTrue();
	expect( $report['all_commentary_resolved'] )->toBeTrue();
	expect( Coverage::blocksCommit( $report ) )->toBeFalse();
} );

test( 'blocksCommit is true on an unknown winner EntryID', function (): void {
	$report = Coverage::report(
		array( array( 'grade' => 'brons', 'type' => 'gedig', 'rank' => 1, 'entry_id' => 'Gedig 9' ) ),
		array(),
		array( 'Gedig 1' )
	);

	expect( $report['unknown_winners'] )->toBe( array( 'Gedig 9' ) );
	expect( Coverage::blocksCommit( $report ) )->toBeTrue();
} );

test( 'blocksCommit is true on a duplicate rank in a pool (no two algehele wenners) — flag #1', function (): void {
	// Two rank-1s in the brons×gedig pool: the authoritative rank-uniqueness invariant.
	$winners = array(
		array( 'grade' => 'brons', 'type' => 'gedig', 'rank' => 1, 'entry_id' => 'Gedig 1' ),
		array( 'grade' => 'brons', 'type' => 'gedig', 'rank' => 1, 'entry_id' => 'Gedig 2' ),
	);

	$report = Coverage::report( $winners, array(), array( 'Gedig 1', 'Gedig 2' ) );

	expect( $report['duplicates'] )->toContain( 'brons|gedig|1' );
	expect( Coverage::blocksCommit( $report ) )->toBeTrue();
} );

test( 'a per-category split does NOT collide: rank-1 gedig and rank-1 storie in the same grade are both allowed', function (): void {
	// The pool is (grade × category), so a 1st gedig and a 1st storie in Brons are
	// distinct slots — not a duplicate.
	$winners = array(
		array( 'grade' => 'brons', 'type' => 'gedig', 'rank' => 1, 'entry_id' => 'Gedig 1' ),
		array( 'grade' => 'brons', 'type' => 'storie', 'rank' => 1, 'entry_id' => 'Storie 1' ),
	);

	$report = Coverage::report( $winners, array(), array( 'Gedig 1', 'Storie 1' ) );

	expect( $report['duplicates'] )->toBe( array() );
	expect( Coverage::blocksCommit( $report ) )->toBeFalse();
} );

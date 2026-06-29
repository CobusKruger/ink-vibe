<?php
/**
 * Unit tests for the judge-email collation logic (Story 12A.2, FR-50-R1).
 *
 * Target: {@see \Ink\Challenges\Collation} — the pure ordering / per-type numbering /
 * anonymisation / preview composition behind the judge-email tool, plus the
 * {@see \Ink\Challenges\Collation::assignRound()} persistence and the
 * {@see \Ink\Challenges\CollationPage::collateRound()} integration over its seams.
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Challenges;

use Ink\Challenges\Collation;
use Ink\Challenges\CollationPage;
use Ink\Challenges\EntryId;
use Brain\Monkey;
use Brain\Monkey\Functions;

beforeEach( function (): void {
	Monkey\setUp();
	Functions\when( '__' )->returnArg( 1 );
} );

afterEach( function (): void {
	Monkey\tearDown();
} );

test( 'sortForAssignment orders by type then Gradering then id, dropping Meester and empty', function (): void {
	$entries = array(
		array( 'id' => 50, 'type' => 'storie', 'gradering' => 'brons' ),
		array( 'id' => 10, 'type' => 'gedig', 'gradering' => 'goud' ),
		array( 'id' => 11, 'type' => 'gedig', 'gradering' => 'brons' ),
		array( 'id' => 12, 'type' => 'gedig', 'gradering' => 'brons' ),
		array( 'id' => 99, 'type' => 'gedig', 'gradering' => 'meester' ), // non-competing — dropped
		array( 'id' => 98, 'type' => 'gedig', 'gradering' => '' ),        // no snapshot — dropped
	);

	$sorted = Collation::sortForAssignment( $entries );
	$ids    = array_column( $sorted, 'id' );

	// gedig (brons 11, brons 12, goud 10) before storie (brons 50); Meester/empty absent.
	expect( $ids )->toBe( array( 11, 12, 10, 50 ) );
	expect( $ids )->not->toContain( 99 );
	expect( $ids )->not->toContain( 98 );
} );

test( 'computeAssignments numbers each type from 1 in sort order', function (): void {
	$sorted = array(
		array( 'id' => 11, 'type' => 'gedig', 'gradering' => 'brons' ),
		array( 'id' => 12, 'type' => 'gedig', 'gradering' => 'brons' ),
		array( 'id' => 10, 'type' => 'gedig', 'gradering' => 'goud' ),
		array( 'id' => 50, 'type' => 'storie', 'gradering' => 'brons' ),
	);

	$a = Collation::computeAssignments( $sorted, array() );

	expect( $a )->toBe( array( 11 => 1, 12 => 2, 10 => 3, 50 => 1 ) );
} );

test( 'computeAssignments is idempotent — keeps existing numbers and continues the per-type sequence', function (): void {
	$sorted = array(
		array( 'id' => 11, 'type' => 'gedig', 'gradering' => 'brons' ),
		array( 'id' => 12, 'type' => 'gedig', 'gradering' => 'brons' ),
		array( 'id' => 13, 'type' => 'gedig', 'gradering' => 'goud' ), // NEW (late entry)
	);

	// 11 and 12 were numbered in a prior collation; 13 is new.
	$existing = array( 11 => 1, 12 => 2 );

	$a = Collation::computeAssignments( $sorted, $existing );

	// Existing kept; the new entry continues from 3 (no renumber, no burn).
	expect( $a )->toBe( array( 11 => 1, 12 => 2, 13 => 3 ) );
} );

test( 'stripIdentity removes the author name and copyright lines but keeps the work', function (): void {
	$content = "Deur Jan Smit\nDie maan is vol vanaand.\nKopiereg 2026 Jan Smit\n© Jan Smit";

	$stripped = Collation::stripIdentity( $content, 'Jan Smit' );

	// Non-vacuous: the name WAS present in the input.
	expect( $content )->toContain( 'Jan Smit' );
	// ...and is gone, along with the copyright lines, after stripping.
	expect( $stripped )->not->toContain( 'Jan Smit' );
	expect( $stripped )->not->toContain( 'Kopiereg' );
	expect( $stripped )->not->toContain( '©' );
	// The work survives.
	expect( $stripped )->toContain( 'Die maan is vol vanaand.' );
} );

test( 'buildPreviewBody composes the challenge body then per-entry heading + text in order', function (): void {
	$body = 'Skryf oor die maan.';

	$entries = array(
		array( 'entry_id' => 'Gedig 1', 'title' => 'Maanlig', 'text' => 'Die maan skyn.' ),
		array( 'entry_id' => 'Gedig 2', 'title' => 'Nag', 'text' => 'Dis donker.' ),
	);

	$preview = Collation::buildPreviewBody( $body, $entries );

	expect( $preview )->toContain( 'Skryf oor die maan.' );
	expect( $preview )->toContain( 'Gedig 1: Maanlig' );
	expect( $preview )->toContain( 'Die maan skyn.' );
	// Order: the body and Gedig 1 precede Gedig 2.
	expect( strpos( $preview, 'Gedig 1' ) )->toBeLessThan( strpos( $preview, 'Gedig 2' ) );
	expect( strpos( $preview, 'Skryf oor die maan.' ) )->toBeLessThan( strpos( $preview, 'Gedig 1' ) );
} );

test( 'parseRecipients keeps valid, drops invalid, and dedupes', function (): void {
	Functions\when( 'sanitize_email' )->returnArg( 1 );
	Functions\when( 'is_email' )->alias(
		static fn ( $email ) => str_contains( (string) $email, '@' ) ? $email : false
	);

	$valid = Collation::parseRecipients( "jan@ink.test, piet@ink.test\nnonsense\njan@ink.test" );

	expect( $valid )->toBe( array( 'jan@ink.test', 'piet@ink.test' ) );
} );

test( 'assignRound persists each entry number via EntryId (first-wins)', function (): void {
	// Unassigned entries: numberFor reads 0.
	Functions\when( 'get_post_meta' )->justReturn( '' );
	Functions\expect( 'update_post_meta' )->times( 4 ); // 2 entries × (type + number)

	$count = Collation::assignRound(
		array(
			array( 'id' => 11, 'type' => 'gedig', 'number' => 1 ),
			array( 'id' => 12, 'type' => 'gedig', 'number' => 2 ),
		)
	);

	expect( $count )->toBe( 2 );
} );

test( 'collateRound integrates the seams: composes the preview and assigns EntryIDs', function (): void {
	Functions\when( 'get_post_meta' )->justReturn( '' ); // all unassigned
	Functions\when( 'update_post_meta' )->justReturn( true );

	$page = new class() extends CollationPage {
		protected function entriesFor( int $uitdaging_id ): array {
			return array(
				array( 'id' => 11, 'type' => 'gedig', 'gradering' => 'brons', 'title' => 'Maanlig', 'content' => 'Deur Jan\nDie maan skyn.', 'author_name' => 'Jan' ),
				array( 'id' => 10, 'type' => 'gedig', 'gradering' => 'goud', 'title' => 'Nag', 'content' => 'Dis donker.', 'author_name' => 'Piet' ),
			);
		}

		protected function challengeBodyFor( int $uitdaging_id ): string {
			return 'Skryf oor die maan.';
		}
	};

	$result = $page->collateRound( 7 );

	expect( $result['empty'] )->toBeFalse();
	// brons (11) numbered Gedig 1 before goud (10) numbered Gedig 2.
	expect( $result['ordered'][0]['entry_id'] )->toBe( 'Gedig 1' );
	expect( $result['ordered'][0]['id'] )->toBe( 11 );
	expect( $result['ordered'][1]['entry_id'] )->toBe( 'Gedig 2' );
	// Preview is anonymised (author name stripped) and ordered.
	expect( $result['preview'] )->toContain( 'Skryf oor die maan.' );
	expect( $result['preview'] )->toContain( 'Gedig 1: Maanlig' );
	expect( $result['preview'] )->not->toContain( 'Jan' );
} );

test( 'collateRound reports empty for a round with no competing entries', function (): void {
	Functions\when( 'get_post_meta' )->justReturn( '' );

	$page = new class() extends CollationPage {
		protected function entriesFor( int $uitdaging_id ): array {
			return array(
				array( 'id' => 99, 'type' => 'gedig', 'gradering' => 'meester', 'title' => 'X', 'content' => 'Y', 'author_name' => 'Z' ),
			);
		}

		protected function challengeBodyFor( int $uitdaging_id ): string {
			return 'Brief.';
		}
	};

	expect( $page->collateRound( 7 )['empty'] )->toBeTrue();
} );

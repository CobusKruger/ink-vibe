<?php
/**
 * Unit tests for the results commit pipeline (Story 12A.3, FR-50-R2).
 *
 * Target: {@see \Ink\Challenges\Ingestion} — the idempotent AC-5 commit pipeline (with
 * the side-effects behind overridable seams), and {@see \Ink\Challenges\IngestionPage::analyse()}.
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Challenges;

use Ink\Challenges\Ingestion;
use Ink\Challenges\IngestionPage;
use Brain\Monkey;
use Brain\Monkey\Functions;

beforeEach( function (): void {
	Monkey\setUp();
	Functions\when( '__' )->returnArg( 1 );
} );

afterEach( function (): void {
	Monkey\tearDown();
} );

/**
 * An Ingestion test double: in-memory commit marker + captured seam calls, so the
 * pipeline order + idempotency are asserted without WP or the real Placements/Tiers.
 */
function ink_test_ingestion(): Ingestion {
	return new class() extends Ingestion {
		public bool $committed_flag      = false;
		/** @var list<array{int,int}> */
		public array $placements         = array();
		/** @var array<int,int> */
		public array $awarded            = array();
		/** @var list<int> */
		public array $biblioteek_ids     = array();
		public int $winners_post_calls   = 0;
		public int $feedback_calls       = 0;

		public function isCommitted( int $uitdaging_id ): bool {
			return $this->committed_flag;
		}

		protected function markCommitted( int $uitdaging_id ): void {
			$this->committed_flag = true;
		}

		protected function commitWinnersPost( int $uitdaging_id, array $winners ): int {
			++$this->winners_post_calls;
			return 555; // pretend a post was created
		}

		protected function commitModeratorFeedback( int $uitdaging_id, array $commentary ): int {
			++$this->feedback_calls;
			return count( $commentary );
		}

		protected function fireBiblioteek( int $uitdaging_id, array $winner_post_ids ): void {
			$this->biblioteek_ids = $winner_post_ids;
		}

		protected function recordPlacement( int $post_id, int $rank ): bool {
			$this->placements[] = array( $post_id, $rank );
			return true;
		}

		protected function awardWins( int $author_id, int $count, int $uitdaging_id ) {
			$this->awarded[ $author_id ] = $count;
			return null; // not promoted (threshold not reached) — the count is what matters
		}
	};
}

test( 'commit runs the AC-5 steps: winners post, feedback, biblioteek, placements, promotions', function (): void {
	$ing = ink_test_ingestion();

	$winners = array(
		array( 'post_id' => 10, 'rank' => 1, 'author_id' => 100 ),
		array( 'post_id' => 11, 'rank' => 2, 'author_id' => 100 ), // same author — two wins
		array( 'post_id' => 12, 'rank' => 1, 'author_id' => 200 ),
	);
	$commentary = array(
		array( 'post_id' => 10, 'title' => 'A', 'text' => 'x' ),
	);

	$result = $ing->commit( 7, $winners, $commentary );

	expect( $result['committed'] )->toBeTrue();
	expect( $ing->winners_post_calls )->toBe( 1 );
	expect( $result['feedback'] )->toBe( 1 );
	expect( $ing->biblioteek_ids )->toBe( array( 10, 11, 12 ) );
	expect( $ing->placements )->toBe( array( array( 10, 1 ), array( 11, 2 ), array( 12, 1 ) ) );
	expect( $result['placed'] )->toBe( 3 );
	// Author 100 earned two top-3 wins this round; author 200 earned one.
	expect( $ing->awarded )->toBe( array( 100 => 2, 200 => 1 ) );
} );

test( 'commit is idempotent — a second commit on a committed round writes nothing (non-vacuous)', function (): void {
	$ing = ink_test_ingestion();

	$winners = array( array( 'post_id' => 10, 'rank' => 1, 'author_id' => 100 ) );

	// First commit WROTE (non-vacuous): it recorded a placement + marked committed.
	$first = $ing->commit( 7, $winners, array() );
	expect( $first['committed'] )->toBeTrue();
	expect( $ing->placements )->toHaveCount( 1 );

	// Second commit no-ops: no new placement, no new promotion, distinct reason.
	$second = $ing->commit( 7, $winners, array() );
	expect( $second['committed'] )->toBeFalse();
	expect( $second['reason'] )->toBe( 'reeds_gepleeg' );
	expect( $ing->placements )->toHaveCount( 1 ); // still one — no double-write
	expect( $ing->winners_post_calls )->toBe( 1 ); // not re-posted
} );

test( 'commit rejects a non-positive uitdaging id', function (): void {
	$result = ink_test_ingestion()->commit( 0, array(), array() );

	expect( $result['committed'] )->toBeFalse();
	expect( $result['reason'] )->toBe( 'ongeldige_uitdaging' );
} );

test( 'IngestionPage::analyse resolves matched EntryIDs to post ids and reports coverage', function (): void {
	$page = new class() extends IngestionPage {
		protected function entriesFor( int $uitdaging_id ): array {
			return array(
				array( 'id' => 10, 'entry_id' => 'Gedig 1', 'author_id' => 100, 'title' => 'Maanlig' ),
				array( 'id' => 11, 'entry_id' => 'Gedig 2', 'author_id' => 200, 'title' => 'Nag' ),
			);
		}
	};

	$text = "WENNERS\nBrons Gedigte\n1ste: Gedig 1\nKOMMENTAAR\nGedig 1: Maanlig\nMooi.";

	$analysis = $page->analyse( 7, $text );

	// The winner resolved to its post id + author.
	expect( $analysis['winners'] )->toBe( array( array( 'post_id' => 10, 'rank' => 1, 'author_id' => 100 ) ) );
	// Commentary resolved to its post id.
	expect( $analysis['commentary'][0]['post_id'] )->toBe( 10 );
	// Coverage: Gedig 2 has no commentary.
	expect( $analysis['report']['entries_without_commentary'] )->toBe( array( 'Gedig 2' ) );
	expect( $analysis['report']['all_winners_identified'] )->toBeTrue();
} );

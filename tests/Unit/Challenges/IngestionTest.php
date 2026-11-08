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
	Functions\when( 'get_the_author_meta' )->justReturn( '' );
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
		public array $wins               = array();
		public array $mentions           = array();
		/** @var list<array{int,int}> */
		public array $placements         = array();
		/** @var array<int,int> */
		public array $awarded            = array();
		/** @var list<int> Post ids that are not poems (Storie, Artikel). */
		public array $prose_ids          = array();
		/** @var list<int> */
		public array $biblioteek_ids     = array();
		public int $winners_post_calls   = 0;
		public int $feedback_calls       = 0;
		/** @var array<int,int> Gedig wins per writer an earlier import already counted for the round. */
		public array $imported           = array();
		public int $taken_over_calls     = 0;

		protected function takeOverImported( int $uitdaging_id ): array {
			++$this->taken_over_calls;
			return $this->imported;
		}

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

		protected function promotesWriter( int $post_id ): bool {
			return ! in_array( $post_id, $this->prose_ids, true );
		}

		protected function recordWin( int $uitdaging_id, array $winner ): bool {
			$this->wins[] = array( $uitdaging_id, $winner['post_id'] );
			return true;
		}

		protected function commitMentions( int $uitdaging_id, array $mentions ): int {
			$this->mentions = $mentions;
			return count( $mentions );
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

test( 'commit refuses an empty-winner commit and leaves the round re-runnable (R12A)', function (): void {
	$ing = ink_test_ingestion();

	$result = $ing->commit( 7, array(), array() );

	expect( $result['committed'] )->toBeFalse();
	expect( $result['reason'] )->toBe( 'geen_wenners' );
	// Nothing written, NOT marked committed — the round can still be committed later.
	expect( $ing->committed_flag )->toBeFalse();
	expect( $ing->placements )->toBe( array() );
} );

test( 'commit dedupes a duplicated post_id so an entry places + is awarded once (R12A)', function (): void {
	$ing = ink_test_ingestion();

	// Same entry (post 10) appears twice — a dirty/direct payload.
	$winners = array(
		array( 'post_id' => 10, 'rank' => 1, 'author_id' => 100 ),
		array( 'post_id' => 10, 'rank' => 2, 'author_id' => 100 ),
	);

	$result = $ing->commit( 7, $winners, array() );

	expect( $result['placed'] )->toBe( 1 );               // one placement, not two
	expect( $ing->placements )->toBe( array( array( 10, 1 ) ) ); // first occurrence wins
	expect( $ing->awarded )->toBe( array( 100 => 1 ) );   // one win, not two
} );

test( 'analyse keys tie detection on the STORED gradering, not the pasted header (flag #1, R12A)', function (): void {
	$page = new class() extends IngestionPage {
		protected function entriesFor( int $uitdaging_id ): array {
			// BOTH entries are really in the Brons pool (stored gradering = brons).
			return array(
				array( 'id' => 10, 'entry_id' => 'Gedig 1', 'author_id' => 100, 'title' => 'A', 'gradering' => 'brons' ),
				array( 'id' => 11, 'entry_id' => 'Gedig 2', 'author_id' => 200, 'title' => 'B', 'gradering' => 'brons' ),
			);
		}
	};

	// The paste mis-states the second winner's pool ("Silwer Gedigte") — a typo'd header.
	// Both are rank-1 Gedigte in the SAME real (Brons) pool: one tie, not two pools.
	$text = "WENNERS\nBrons Gedigte\n1ste: Gedig 1\nSilwer Gedigte\n1ste: Gedig 2";

	$analysis = $page->analyse( 7, $text );

	// Authoritative grade (brons for both) puts them in ONE slot: reported as a tie (both win),
	// not blocked. Keyed on the pasted header they would have looked like two separate pools.
	expect( $analysis['report']['ties'] )->toBe( array( 'brons|gedig|1' ) );
	expect( \Ink\Challenges\Coverage::blocksCommit( $analysis['report'] ) )->toBeFalse();
} );

test( 'commit adds a win record for each new win, and none for an unplaced one', function (): void {
	$ing = ink_test_ingestion();

	$result = $ing->commit(
		7,
		array(
			array( 'post_id' => 10, 'rank' => 1, 'author_id' => 100 ),
			array( 'post_id' => 11, 'rank' => 2, 'author_id' => 0 ), // No account: recorded all the same.
		),
		array()
	);

	expect( $ing->wins )->toBe( array( array( 7, 10 ), array( 7, 11 ) ) );
	expect( $result['wins'] )->toBe( 2 );
} );

test( 'commit writes the mentions inside the commit-done guard', function (): void {
	$ing     = ink_test_ingestion();
	$winners = array( array( 'post_id' => 10, 'rank' => 1, 'author_id' => 100 ) );

	$first = $ing->commit( 7, $winners, array(), array( 'Lig | Anna Smit' ) );

	expect( $first['mentions'] )->toBe( 1 );
	expect( $ing->mentions )->toBe( array( 'Lig | Anna Smit' ) );

	// A re-run on a committed round writes neither records nor mentions.
	$ing->mentions = array();
	$ing->wins     = array();
	$second        = $ing->commit( 7, $winners, array(), array( 'Iets anders | X' ) );

	expect( $second['reason'] )->toBe( 'reeds_gepleeg' );
	expect( $ing->mentions )->toBe( array() );
	expect( $ing->wins )->toBe( array() );
} );

test( 'commitMentions sanitises the lines and leaves the field alone when there are none', function (): void {
	\Brain\Monkey\Functions\when( 'sanitize_textarea_field' )->returnArg( 1 );
	\Brain\Monkey\Functions\expect( 'update_post_meta' )->once()->with( 7, 'ink_uitdaging_vermeldings', "Lig | Anna\nGedig | Piet" );

	$ing = new class() extends Ingestion {
		public function write( array $mentions ): int {
			return $this->commitMentions( 7, $mentions );
		}
	};

	expect( $ing->write( array() ) )->toBe( 0 );
	expect( $ing->write( array( 'Lig | Anna', 'Gedig | Piet' ) ) )->toBe( 2 );
} );

test( 'analyse resolves mention EntryIDs to `Titel | Skrywer` and keeps plain-text mentions', function (): void {
	$page = new class() extends IngestionPage {
		protected function entriesFor( int $uitdaging_id ): array {
			return array(
				array( 'id' => 10, 'entry_id' => 'Gedig 1', 'author_id' => 100, 'title' => 'Wenner', 'gradering' => 'brons' ),
				array( 'id' => 11, 'entry_id' => 'Gedig 7', 'author_id' => 101, 'title' => 'Nag', 'gradering' => 'brons' ),
			);
		}

		protected function authorName( int $author_id ): string {
			return 'Skrywer ' . $author_id;
		}
	};

	$analysis = $page->analyse( 7, "WENNERS\nBrons Gedigte\n1ste: Gedig 1\nEervolle vermeldings\nGedig 7\nGedig 99\nLig op water | Anna Smit\n" );

	// Gedig 99 has no entry in this round, so it is left out, as unmatched commentary is.
	expect( $analysis['mentions'] )->toBe( array( 'Nag | Skrywer 101', 'Lig op water | Anna Smit' ) );
	expect( array_column( $analysis['winners'], 'post_id' ) )->toBe( array( 10 ) );
} );

test( 'a Storie or Artikel win is recorded and placed but promotes nobody: Gradering is Gedigte-only', function (): void {
	$ing            = ink_test_ingestion();
	$ing->prose_ids = array( 11, 12 );

	$result = $ing->commit(
		7,
		array(
			array( 'post_id' => 10, 'rank' => 1, 'author_id' => 100 ), // a Gedig
			array( 'post_id' => 11, 'rank' => 1, 'author_id' => 100 ), // a Storie by the same writer
			array( 'post_id' => 12, 'rank' => 2, 'author_id' => 200 ), // a Storie only
		),
		array()
	);

	expect( $result['placed'] )->toBe( 3 );
	expect( $result['wins'] )->toBe( 3 );
	expect( $ing->awarded )->toBe( array( 100 => 1 ) );
} );

test( 'only Gedigte wins reach the promotion engine', function (): void {
	$ing            = ink_test_ingestion();
	$ing->prose_ids = array( 11, 12 );

	$ing->commit(
		8,
		array(
			array( 'post_id' => 10, 'rank' => 1, 'author_id' => 100 ),
			array( 'post_id' => 11, 'rank' => 2, 'author_id' => 100 ),
			array( 'post_id' => 12, 'rank' => 1, 'author_id' => 200 ),
		),
		array()
	);

	expect( $ing->awarded )->toBe( array( 100 => 1 ) );
} );

/**
 * An IngestionPage over a fixed round: three poems (two Brons, one Silwer) and two articles.
 * Only two poems carry an EntryID, and the numbers are NOT the judges' (a round judged from an older list).
 */
function ink_test_page(): IngestionPage {
	return new class() extends IngestionPage {
		protected function entriesFor( int $uitdaging_id ): array {
			return array(
				array( 'id' => 10, 'entry_id' => 'Gedig 1', 'type' => 'gedig', 'author_id' => 100, 'title' => 'Maanlig', 'gradering' => 'brons' ),
				array( 'id' => 11, 'entry_id' => 'Gedig 2', 'type' => 'gedig', 'author_id' => 200, 'title' => '’n Wêreld in ’n sandkorrel', 'gradering' => 'brons' ),
				array( 'id' => 12, 'entry_id' => '', 'type' => 'gedig', 'author_id' => 300, 'title' => 'Quo Vadis', 'gradering' => 'silwer' ),
				array( 'id' => 20, 'entry_id' => '', 'type' => 'artikel', 'author_id' => 400, 'title' => 'Halfvol in Italië', 'gradering' => 'brons' ),
				array( 'id' => 21, 'entry_id' => '', 'type' => 'artikel', 'author_id' => 500, 'title' => 'Padkos vir die hart', 'gradering' => 'goud' ),
			);
		}

		protected function authorName( int $author_id ): string {
			return 'Skrywer ' . $author_id;
		}
	};
}

/** The judges' own layout, numbered differently from the site. */
const INK_TEST_JUDGES = "INK SIA 2026 KOMMENTAAR EN UITSLAE\nWENNERS\nBRONS: (1) No 5 (2) No 3 (3) Geen\nSILWER: (1) No 9\nPROSA: (1) No 2 (2) No 1\n\nBRONS\n3. Maanlig\nMooi.\n5. Die wêreld is ’n sandkorrel\nSterk.\n\nSILWER\n9. Que Vadis\nGoed.\n\nPROSA\nHalfvol Italie\nTe kort.\nPadkos vir die hart\nUitstaande.\n";

test( 'analyse matches the judges\' entries by title, not by their numbers', function (): void {
	$analysis = ink_test_page()->analyse( 7, INK_TEST_JUDGES );

	// No 3 is "Maanlig" on the judges' list, which the site numbers Gedig 1; the title decides.
	expect( array_column( $analysis['doc_rows'], 'post_id' ) )->toBe( array( 10, 11, 12, 20, 21 ) );
	expect( array_column( $analysis['doc_rows'], 'status' ) )->toBe( array( 'gepas', 'ooreenkoms', 'ooreenkoms', 'ooreenkoms', 'gepas' ) );
	// Winners follow their entry: Brons 1st No 5 = the Wêreld poem, 2nd No 3 = Maanlig; Prosa 1st No 2 = Padkos.
	expect( $analysis['winners'] )->toBe(
		array(
			array( 'post_id' => 11, 'rank' => 1, 'author_id' => 200 ),
			array( 'post_id' => 10, 'rank' => 2, 'author_id' => 100 ),
			array( 'post_id' => 12, 'rank' => 1, 'author_id' => 300 ),
			array( 'post_id' => 21, 'rank' => 1, 'author_id' => 500 ),
			array( 'post_id' => 20, 'rank' => 2, 'author_id' => 400 ),
		)
	);
	expect( \Ink\Challenges\Coverage::blocksCommit( $analysis['report'] ) )->toBeFalse();
	expect( array_column( $analysis['commentary'], 'post_id' ) )->toBe( array( 10, 11, 12, 20, 21 ) );
	expect( $analysis['without'] )->toBe( array() );
	// The dropdown lists every entry, numbered or not, by type, pool and title.
	expect( array_values( $analysis['options'] ) )->toBe(
		array(
			'Artikel — Halfvol in Italië (Skrywer 400)',
			'Artikel — Padkos vir die hart (Skrywer 500)',
			'Gedig Brons — Maanlig (Skrywer 100)',
			'Gedig Brons — ’n Wêreld in ’n sandkorrel (Skrywer 200)',
			'Gedig Silwer — Quo Vadis (Skrywer 300)',
		)
	);
} );

test( 'an entry nothing matches blocks the commit until the editor picks one', function (): void {
	$text = "WENNERS\nBRONS: (1) No 7\n\nBRONS\n7. Iets heeltemal anders\nGoed.\n";

	$analysis = ink_test_page()->analyse( 7, $text );

	expect( $analysis['doc_rows'][0]['status'] )->toBe( 'ongepas' );
	expect( $analysis['winner_rows'][0]['status'] )->toBe( 'ongepas' );
	expect( $analysis['winners'] )->toBe( array() );
	expect( \Ink\Challenges\Coverage::blocksCommit( $analysis['report'] ) )->toBeTrue();

	$fixed = ink_test_page()->analyse( 7, $text, array( 0 => 10 ) );

	expect( $fixed['doc_rows'][0]['status'] )->toBe( 'gewysig' );
	expect( $fixed['winners'] )->toBe( array( array( 'post_id' => 10, 'rank' => 1, 'author_id' => 100 ) ) );
	expect( array_column( $fixed['commentary'], 'post_id' ) )->toBe( array( 10 ) );
	expect( \Ink\Challenges\Coverage::blocksCommit( $fixed['report'] ) )->toBeFalse();
} );

test( 'a stale EntryID on the site never stands in for a title: the same number, another poem, is no match', function (): void {
	// The site\'s Gedig 1 is "Maanlig"; the judges\' No 1 is a poem the round does not hold.
	$analysis = ink_test_page()->analyse( 7, "WENNERS\nBRONS: (1) No 1\n\nBRONS\n1. Nag oor die dorp\nGoed.\n" );

	expect( $analysis['doc_rows'][0]['status'] )->toBe( 'ongepas' );
	expect( $analysis['winners'] )->toBe( array() );
} );

test( 'a correction can leave an entry out, or move a match onto an entry already placed (blocked)', function (): void {
	$left_out = ink_test_page()->analyse( 7, INK_TEST_JUDGES, array( 3 => -1 ) );
	expect( $left_out['doc_rows'][3]['status'] )->toBe( 'uitgelaat' );
	expect( array_column( $left_out['winners'], 'post_id' ) )->not->toContain( 20 );
	expect( \Ink\Challenges\Coverage::blocksCommit( $left_out['report'] ) )->toBeFalse();

	// Both prosa winners now point at the same article.
	$twice = ink_test_page()->analyse( 7, INK_TEST_JUDGES, array( 3 => 21 ) );
	expect( \Ink\Challenges\Coverage::blocksCommit( $twice['report'] ) )->toBeTrue();
	expect( $twice['doc_rows'][3]['shared'] )->toBeTrue();

	// A post id outside the round is never accepted: it counts as nothing chosen.
	$foreign = ink_test_page()->analyse( 7, INK_TEST_JUDGES, array( 3 => 999 ) );
	expect( $foreign['doc_rows'][3]['status'] )->toBe( 'ongepas' );
	expect( \Ink\Challenges\Coverage::blocksCommit( $foreign['report'] ) )->toBeTrue();
} );

test( 'entries without a comment are listed, and a winner with no comment is matched on its number', function (): void {
	$page = new class() extends IngestionPage {
		protected function entriesFor( int $uitdaging_id ): array {
			return array(
				array( 'id' => 10, 'entry_id' => 'Gedig 1', 'author_id' => 100, 'title' => 'Maanlig', 'gradering' => 'brons' ),
				array( 'id' => 11, 'entry_id' => 'Gedig 2', 'author_id' => 200, 'title' => 'Nag', 'gradering' => 'brons' ),
			);
		}
	};

	$analysis = $page->analyse( 7, "WENNERS\nBrons Gedigte\n1ste: Gedig 1" );

	expect( $analysis['doc_rows'][0]['status'] )->toBe( 'nommer' );
	expect( $analysis['winners'] )->toBe( array( array( 'post_id' => 10, 'rank' => 1, 'author_id' => 100 ) ) );
	// No comment was read at all, so "every entry lacks one" is left to the problems, not listed.
	expect( $analysis['without'] )->toBe( array() );

	$with = $page->analyse( 7, "WENNERS
Brons Gedigte
1ste: Gedig 1
KOMMENTAAR
Gedig 1: Maanlig
Mooi." );

	expect( $with['without'] )->toBe( array( 'Nag' ) );
} );

test( 'a file with nothing readable returns the problems and no rows', function (): void {
	$analysis = ink_test_page()->analyse( 7, "Gedig 3 was mooi
" );

	expect( $analysis['winner_rows'] )->toBe( array() );
	expect( $analysis['doc_rows'] )->toBe( array() );
	expect( $analysis['without'] )->toBe( array() );
	expect( $analysis['problems'][0] )->toContain( 'WENNERS' );
} );

test( 'rounds are ordered by close date, newest first, and the default is the newest closed round without results', function (): void {
	$rounds = IngestionPage::sortByClose(
		array(
			array( 'id' => 1, 'title' => 'Aug', 'close' => '2026-08-31', 'committed' => true ),
			array( 'id' => 2, 'title' => 'Nov', 'close' => '2026-11-30', 'committed' => false ),
			array( 'id' => 3, 'title' => 'Jaar', 'close' => '', 'committed' => false ),
			array( 'id' => 4, 'title' => 'Okt', 'close' => '2026-10-31', 'committed' => false ),
			array( 'id' => 5, 'title' => 'Sep', 'close' => '2026-09-30', 'committed' => true ),
		)
	);

	expect( array_column( $rounds, 'id' ) )->toBe( array( 2, 4, 5, 1, 3 ) );
	expect( IngestionPage::defaultRound( $rounds, '2026-11-07' ) )->toBe( 4 );  // Nov has not closed.
	expect( IngestionPage::defaultRound( $rounds, '2026-05-01' ) )->toBe( 2 );  // None closed: the newest.
	expect( IngestionPage::defaultRound( array(), '2026-11-07' ) )->toBe( 0 );
} );

test( 'a round whose wins were imported is taken over: only wins the writers do not already have are awarded', function (): void {
	$ing           = ink_test_ingestion();
	$ing->imported = array( 100 => 1, 200 => 1 ); // The import already counted one Gedig win each.

	$result = $ing->commit(
		7,
		array(
			array( 'post_id' => 10, 'rank' => 1, 'author_id' => 100 ), // Same win as imported: not awarded again.
			array( 'post_id' => 11, 'rank' => 2, 'author_id' => 300 ), // A writer the import did not have: awarded.
		),
		array()
	);

	expect( $ing->taken_over_calls )->toBe( 1 );
	expect( $ing->awarded )->toBe( array( 300 => 1 ) );
	expect( $result['placed'] )->toBe( 2 );
} );

test( 'a writer with more wins this round than the import counted is awarded the difference', function (): void {
	$ing           = ink_test_ingestion();
	$ing->imported = array( 100 => 1 );

	$ing->commit(
		7,
		array(
			array( 'post_id' => 10, 'rank' => 1, 'author_id' => 100 ),
			array( 'post_id' => 11, 'rank' => 2, 'author_id' => 100 ),
		),
		array()
	);

	expect( $ing->awarded )->toBe( array( 100 => 1 ) );
} );

test( 'an unclassified entry (skryfwerk) can be matched by title and is flagged, so a winner is not stranded', function (): void {
	$page = new class() extends IngestionPage {
		protected function entriesFor( int $uitdaging_id ): array {
			return array(
				array( 'id' => 10, 'entry_id' => '', 'type' => 'skryfwerk', 'author_id' => 100, 'title' => 'Lente in die skoolgange', 'gradering' => 'brons' ),
			);
		}
	};

	$analysis = $page->analyse( 7, "WENNERS\nBRONS: (1) No 14\n\nBRONS\n14. Lente in die skoolgange\nMooi.\n" );

	expect( $analysis['doc_rows'][0]['post_id'] )->toBe( 10 );
	expect( $analysis['doc_rows'][0]['unsorted'] )->toBeTrue();
	expect( $analysis['winners'] )->toBe( array( array( 'post_id' => 10, 'rank' => 1, 'author_id' => 100 ) ) );
	expect( array_values( $analysis['options'] ) )->toBe( array( 'Skryfwerk Brons — Lente in die skoolgange' ) );
} );

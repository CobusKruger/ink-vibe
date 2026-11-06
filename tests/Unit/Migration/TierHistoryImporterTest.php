<?php
/**
 * Unit tests for the Gradering-history backfill.
 *
 * Target: {@see \Ink\Migration\TierHistoryImporter}. The I/O seams are stubbed; the decisions are real.
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Migration;

use Brain\Monkey;
use Brain\Monkey\Functions;
use Ink\Kernel\Tier;
use Ink\Migration\MigrateAll;
use Ink\Migration\TierHistoryImporter;
use Ink\Tiers\PromotionEngine;

beforeEach( function (): void {
	Monkey\setUp();
	Functions\when( 'get_option' )->justReturn( false );
	Functions\when( 'update_option' )->justReturn( true );
} );

afterEach( function (): void {
	Monkey\tearDown();
} );

/**
 * A win row as `TierDeriver` lists it.
 *
 * @param int    $post     The result post.
 * @param string $date     The result date.
 * @param int    $user     The account, or 0.
 * @param string $category The category label.
 * @return array<string, mixed>
 */
function history_win( int $post, string $date, int $user, string $category = 'Gedigte' ): array {
	return array(
		'result_post' => $post,
		'result_date' => $date,
		'writer'      => 'X',
		'user'        => $user,
		'title'       => 'T' . $post,
		'position'    => 1,
		'category'    => $category,
		'grading'     => 'Brons',
	);
}

/**
 * `$n` Gedigte wins for a user, one per month from 2019-01.
 *
 * @param int $user The account.
 * @param int $n    How many wins.
 * @return list<array<string, mixed>>
 */
function history_wins( int $user, int $n ): array {
	$rows = array();

	for ( $i = 0; $i < $n; $i++ ) {
		$rows[] = history_win( 100 + $i, gmdate( 'Y-m-d H:i:s', strtotime( '2019-01-05 09:00:00 +' . $i . ' months' ) ), $user );
	}

	return $rows;
}

test( 'the replay ends at exactly the grade the baseline derivation gives', function (): void {
	foreach ( array( 0, 4, 5, 6, 19, 20, 21, 40 ) as $total ) {
		$steps = PromotionEngine::promotionsForTotalWins( $total );
		$last  = array() === $steps ? Tier::default() : $steps[ count( $steps ) - 1 ]['to'];

		expect( $last )->toBe( PromotionEngine::standingForTotalWins( $total )['tier'] );
	}
} );

test( 'the 5th win promotes to Silwer and the 20th to Goud', function (): void {
	$steps = PromotionEngine::promotionsForTotalWins( 20 );

	expect( $steps )->toHaveCount( 2 );
	expect( $steps[0]['to'] )->toBe( Tier::Silwer );
	expect( $steps[0]['win'] )->toBe( 5 );
	expect( $steps[1]['from'] )->toBe( Tier::Silwer );
	expect( $steps[1]['to'] )->toBe( Tier::Goud );
	expect( $steps[1]['win'] )->toBe( 20 );
	expect( PromotionEngine::promotionsForTotalWins( 4 ) )->toBe( array() );
} );

test( 'the plan dates each promotion by the win that triggered it', function (): void {
	$plan = TierHistoryImporter::plan( history_wins( 7, 6 ) );

	expect( $plan )->toHaveCount( 1 );
	expect( $plan[0]['user'] )->toBe( 7 );
	expect( $plan[0]['to'] )->toBe( Tier::Silwer );
	expect( $plan[0]['result_post'] )->toBe( 104 ); // The 5th win, not the 6th.
	expect( $plan[0]['result_date'] )->toBe( '2019-05-05 09:00:00' );
} );

test( 'the plan counts Gedigte wins with an account only, oldest first whatever the input order', function (): void {
	$rows   = array_reverse( history_wins( 7, 5 ) );
	$rows[] = history_win( 900, '2018-01-01 00:00:00', 7, 'Storie' ); // Not poetry: no part in Gradering.
	$rows[] = history_win( 901, '2018-01-01 00:00:00', 0 );          // No account: promotes nobody.

	$plan = TierHistoryImporter::plan( $rows );

	expect( $plan )->toHaveCount( 1 );
	expect( $plan[0]['result_post'] )->toBe( 104 );
} );

test( 'four wins is no promotion and each writer is planned separately', function (): void {
	$rows = array_merge( history_wins( 1, 4 ), history_wins( 2, 5 ) );
	$plan = TierHistoryImporter::plan( $rows );

	expect( $plan )->toHaveCount( 1 );
	expect( $plan[0]['user'] )->toBe( 2 );
} );

/**
 * An importer over canned rows, recording what it would write.
 *
 * @param list<array<string, mixed>> $rows   The parsed wins.
 * @param array<int, int>            $rounds Result post => round.
 */
function history_importer( array $rows, array $rounds = array() ): object {
	return new class( $rows, $rounds ) extends TierHistoryImporter {
		/** @var list<array{0:int,1:string,2:string,3:string,4:int}> */
		public array $written = array();
		public bool $cleared  = false;

		/**
		 * @param list<array<string, mixed>> $rows   Wins.
		 * @param array<int, int>            $rounds Rounds.
		 */
		public function __construct( private array $rows, private array $rounds ) {}

		protected function winRows(): array {
			return $this->rows;
		}

		protected function roundOf( int $result_post ): int {
			return $this->rounds[ $result_post ] ?? 0;
		}

		protected function writePromotion( int $user_id, Tier $from, Tier $to, string $result_date, int $round ): bool {
			$this->written[] = array( $user_id, $from->value, $to->value, $result_date, $round );
			return true;
		}

		protected function clearImported(): void {
			$this->cleared = true;
		}
	};
}

test( 'run writes the promotions with the round of the triggering result post', function (): void {
	$importer = history_importer( history_wins( 7, 20 ), array( 104 => 55, 119 => 66 ) );
	$summary  = $importer->run();

	expect( $importer->written )->toBe(
		array(
			array( 7, 'brons', 'silwer', '2019-05-05 09:00:00', 55 ),
			array( 7, 'silwer', 'goud', '2020-08-05 09:00:00', 66 ),
		)
	);
	expect( $summary['promotions'] )->toBe( 2 );
	expect( $summary['writers'] )->toBe( 1 );
	expect( $summary['written'] )->toBe( 2 );
	expect( $summary['no_round'] )->toBe( 0 );
} );

test( 'a result post with no round still gets its promotion, counted as unlinked', function (): void {
	$importer = history_importer( history_wins( 7, 5 ) );
	$summary  = $importer->run();

	expect( $importer->written )->toHaveCount( 1 );
	expect( $importer->written[0][4] )->toBe( 0 );
	expect( $summary['no_round'] )->toBe( 1 );
} );

test( 'run is skipped once done, and --force clears its own rows first', function (): void {
	Functions\when( 'get_option' )->justReturn( true );
	$skipped = history_importer( history_wins( 7, 5 ) );

	expect( $skipped->run()['skipped'] )->toBeTrue();
	expect( $skipped->written )->toBe( array() );

	$forced = history_importer( history_wins( 7, 5 ) );
	$forced->run( true );

	expect( $forced->cleared )->toBeTrue();
	expect( $forced->written )->toHaveCount( 1 );
} );

test( 'the step runs after the winners step and is judged by its summary line', function (): void {
	$ids = array_map( static fn ( array $s ): string => $s[0], MigrateAll::steps() );

	expect( array_search( 'tier-history', $ids, true ) )->toBeGreaterThan( array_search( 'winners', $ids, true ) );
	expect( MigrateAll::verdict( 'tier-history', TierHistoryImporter::summaryLine( array( 'skipped' => false, 'writers' => 3, 'promotions' => 4, 'written' => 4, 'no_round' => 0 ) ) ) )->toBe( MigrateAll::VERDICT_OK );
	expect( MigrateAll::verdict( 'tier-history', 'Success: ' . TierHistoryImporter::summaryLine( array( 'skipped' => false, 'writers' => 0, 'promotions' => 0, 'written' => 0, 'no_round' => 0 ) ) ) )->toBe( MigrateAll::VERDICT_ALL_ZERO );
} );

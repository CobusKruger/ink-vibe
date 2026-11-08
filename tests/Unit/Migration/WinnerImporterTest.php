<?php
/**
 * Unit tests for the winner importer.
 *
 * Target: {@see \Ink\Migration\WinnerImporter}. The I/O seams are stubbed; the decisions are real.
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Migration;

use Brain\Monkey;
use Brain\Monkey\Functions;
use Ink\Migration\MigrateAll;
use Ink\Migration\WinnerImporter;

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
 * @param int    $result_post The result post.
 * @param string $writer      The by-line.
 * @param int    $user        The account, or 0.
 * @param string $title       The title.
 * @param int    $position    The position.
 * @param string $category    The category label.
 * @param string $grading     The grade heading.
 * @return array<string, mixed>
 */
function winner_row( int $result_post, string $writer, int $user, string $title, int $position = 1, string $category = 'Gedigte', string $grading = 'Goud' ): array {
	return array(
		'result_post' => $result_post,
		'result_date' => '2020-04-05 09:00:00',
		'writer'      => $writer,
		'user'        => $user,
		'title'       => $title,
		'position'    => $position,
		'category'    => $category,
		'grading'     => $grading,
	);
}

/**
 * An importer over canned rows, recording what it would write.
 *
 * @param list<array<string, mixed>>             $rows  The parsed wins.
 * @param array<int, list<array<string, mixed>>> $posts Posts per writer.
 * @param array<int, int>                        $links Stored result-post links.
 */
function winner_importer( array $rows, array $posts, array $links = array() ): object {
	return new class( $rows, $posts, $links ) extends WinnerImporter {
		/** @var list<array<string, mixed>> */
		public array $records = array();
		/** @var list<array{0:int,1:int,2:string}> */
		public array $placements = array();
		/** @var array<int, int> */
		public array $linked = array();
		public bool $cleared = false;

		public function __construct( private array $rows, private array $posts, private array $links ) {}

		protected function winRows(): array {
			return $this->rows;
		}
		protected function roundDates(): array {
			return array(
				5 => '2020-02-27 00:00:00',
				6 => '2019-01-01 00:00:00',
			);
		}
		protected function userPosts( int $user_id ): array {
			return $this->posts[ $user_id ] ?? array();
		}
		protected function existingLink( int $post_id ): int {
			return $this->links[ $post_id ] ?? 0;
		}
		protected function link( int $post_id, int $uitdaging_id ): void {
			$this->linked[ $post_id ] = $uitdaging_id;
		}
		protected function postTitle( int $post_id ): string {
			return 'Uitslae ' . $post_id;
		}
		protected function recordPlacement( int $post_id, int $position, string $grade ): void {
			$this->placements[] = array( $post_id, $position, $grade );
		}
		protected function recordWin( array $row ): bool {
			$this->records[] = $row;
			return true;
		}
		protected function clearImported(): void {
			$this->cleared = true;
		}
	};
}

test( 'a win is recorded with its post, placement and grade; a Gedig carries the grade it won under', function (): void {
	$importer = winner_importer(
		array( winner_row( 100, 'Carma Shaw', 3, 'Reen op die dak' ) ),
		array( 3 => array( array( 'id' => 40, 'title' => 'Reen op die dak', 'date' => '2020-03-20 00:00:00', 'rounds' => array( 5 ) ) ) ),
		array( 100 => 5 )
	);

	$summary = $importer->run();

	expect( $summary['recorded'] )->toBe( 1 );
	expect( $summary['matched'] )->toBe( 1 );
	expect( $importer->placements )->toBe( array( array( 40, 1, 'goud' ) ) );
	expect( $importer->records[0] )->toBe(
		array(
			'round_id' => 5,
			'post_id'  => 40,
			'user_id'  => 3,
			'position' => 1,
			'category' => 'gedig',
			'grade'    => 'goud',
			'title'    => 'Reen op die dak',
			'source'   => 'import',
		)
	);
} );

test( 'a win by a writer with no account (a deleted user) is counted but not recorded', function (): void {
	$importer = winner_importer(
		array( winner_row( 100, 'Agie', 0, 'Verlore woorde', 2, 'Verhale', '' ) ),
		array(),
		array( 100 => 5 )
	);

	$summary = $importer->run();

	expect( $summary['recorded'] )->toBe( 0 );
	expect( $summary['matched'] )->toBe( 0 );
	expect( $summary['no_account'] )->toBe( 1 );
	expect( $importer->placements )->toBe( array() );
	expect( $importer->records )->toBe( array() );
} );

test( 'a win with an account but no post is still recorded', function (): void {
	$importer = winner_importer(
		array( winner_row( 100, 'Carma Shaw', 3, 'Verlore woorde', 2, 'Verhale', '' ) ),
		array(),
		array( 100 => 5 )
	);

	$summary = $importer->run();

	expect( $summary['recorded'] )->toBe( 1 );
	expect( $summary['matched'] )->toBe( 0 );
	expect( $importer->records[0] )->toMatchArray(
		array(
			'post_id'  => 0,
			'user_id'  => 3,
			'category' => 'storie',
		)
	);
} );

test( 'only a Gedig keeps a grade: a Storie under a grade heading records none', function (): void {
	$importer = winner_importer(
		array( winner_row( 100, 'Carma Shaw', 3, 'Die storie', 1, 'Verhale', 'Silwer' ) ),
		array( 3 => array( array( 'id' => 41, 'title' => 'Die storie', 'date' => '2020-03-20 00:00:00', 'rounds' => array( 5 ) ) ) ),
		array( 100 => 5 )
	);

	$importer->run();

	expect( $importer->records[0]['grade'] )->toBe( '' );
	expect( $importer->placements )->toBe( array( array( 41, 1, '' ) ) );
} );

test( 'a result post with no link gets the round that holds most of its winning posts, and the link is stored', function (): void {
	$importer = winner_importer(
		array(
			winner_row( 100, 'Carma Shaw', 3, 'Reen op die dak' ),
			winner_row( 100, 'Anzé', 5, 'Wind in die riet', 2 ),
		),
		array(
			3 => array( array( 'id' => 40, 'title' => 'Reen op die dak', 'date' => '2020-03-20 00:00:00', 'rounds' => array( 5 ) ) ),
			5 => array( array( 'id' => 41, 'title' => 'Wind in die riet', 'date' => '2020-03-21 00:00:00', 'rounds' => array( 5 ) ) ),
		)
	);

	$summary = $importer->run();

	expect( $importer->linked )->toBe( array( 100 => 5 ) );
	expect( $summary['linked'] )->toBe( 1 );
	expect( $summary['recorded'] )->toBe( 2 );
	expect( array_column( $importer->records, 'round_id' ) )->toBe( array( 5, 5 ) );
} );

test( 'a result post whose round cannot be found is reported and its wins are not imported', function (): void {
	$importer = winner_importer( array( winner_row( 100, 'Agie', 0, 'Verlore woorde' ) ), array() );

	$summary = $importer->run();

	expect( $summary['no_round'] )->toBe( 1 );
	expect( $summary['recorded'] )->toBe( 0 );
	expect( $summary['problems'][0] )->toContain( 'Uitslae 100' );
} );

test( 'a position outside 1 to 3 is reported, not recorded', function (): void {
	$importer = winner_importer( array( winner_row( 100, 'Carma Shaw', 3, 'Een', 4 ) ), array(), array( 100 => 5 ) );

	$summary = $importer->run();

	expect( $summary['recorded'] )->toBe( 0 );
	expect( $summary['problems'][0] )->toContain( 'posisie 4' );
} );

test( 'it is once-off: a second run does nothing, and force starts again from a clean slate', function (): void {
	$rows = array( winner_row( 100, 'Carma Shaw', 3, 'Een' ) );

	$importer = winner_importer( $rows, array(), array( 100 => 5 ) );

	Functions\when( 'get_option' )->justReturn( true );
	expect( $importer->run()['skipped'] )->toBeTrue();
	expect( $importer->records )->toBe( array() );

	$forced = $importer->run( true );

	expect( $forced['skipped'] )->toBeFalse();
	expect( $importer->cleared )->toBeTrue();
	expect( $forced['recorded'] )->toBe( 1 );
} );

test( 'votes count a round once per winning row; the grade heading maps to a stored grade', function (): void {
	$post = static fn ( array $rounds ): array => array(
		'id'     => 1,
		'title'  => 't',
		'date'   => 'd',
		'rounds' => $rounds,
		'score'  => 3,
	);

	expect( WinnerImporter::votes( array( array( $post( array( 5, 6 ) ), $post( array( 5 ) ) ), array( $post( array( 5 ) ) ) ) ) )->toBe( array( 5 => 2, 6 => 1 ) );
	expect( WinnerImporter::gradeValue( ' Goud ' ) )->toBe( 'goud' );
	expect( WinnerImporter::gradeValue( 'Meester' ) )->toBe( '' );
	expect( WinnerImporter::gradeValue( '' ) )->toBe( '' );
} );

test( 'the step runs in migrate-all after the result posts are linked', function (): void {
	$ids = array_column( array_map( static fn ( array $s ): array => array( 'id' => $s[0], 'cmd' => $s[1] ), MigrateAll::steps() ), 'cmd', 'id' );

	expect( $ids['winners'] )->toBe( 'ink import-winners' );

	$order = array_keys( $ids );

	expect( array_search( 'winners', $order, true ) )->toBeGreaterThan( array_search( 'result-posts', $order, true ) );
	expect( array_search( 'winners', $order, true ) )->toBeGreaterThan( array_search( 'challenge-entries', $order, true ) );
} );

test( 'the summary line reads as one result for migrate-all', function (): void {
	$line = WinnerImporter::summaryLine(
		array(
			'rows'         => 10,
			'result_posts' => 2,
			'recorded'     => 9,
			'matched'      => 7,
			'no_account'   => 1,
			'no_round'     => 1,
			'linked'       => 0,
			'skipped'      => false,
		)
	);

	expect( $line )->toContain( '10 wenne' );
	expect( $line )->toContain( '9 aangeteken' );
} );

<?php
/**
 * Unit tests for the CSV-driven InkPols back-catalogue migration (WP4, `inkpols.md` §3).
 *
 * Target: {@see \Ink\InkPols\Migration} — one `inkpols_uitgawe` per row of
 * `inkpols-uitgawes.csv`, attachments matched by upload path, drafts for
 * rows that lack a required field, 301 sources recorded, old Pages trashed. Pure
 * parsers/builders + the idempotency-guarded orchestration over an in-memory fake.
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\InkPols;

use Ink\InkPols\Migration;
use Ink\Content\FieldSets;
use Ink\Content\PostTypes;
use Ink\Migration\MigrateAll;
use Ink\Migration\PostReclassifier;
use Brain\Monkey;

beforeEach( function (): void {
	Monkey\setUp();
} );

afterEach( function (): void {
	Monkey\tearDown();
} );

const INKPOLS_HEADER = 'slug,naam,titel,beskrywing,datum,pdf,voorblad,ou_url,gedig_titel,gedig_skrywer,storie_titel,storie_skrywer,artikel_titel,artikel_skrywer';

/**
 * The CSV with its owner columns blank, as the step reads it.
 */
function inkpols_fixture_csv(): string {
	return (string) file_get_contents( __DIR__ . '/inkpols-uitgawes.fixture.txt' );
}

/**
 * A complete row (every required field filled).
 *
 * @param string $slug The slug.
 * @param string $old  The old Page path.
 */
function inkpols_complete_row( string $slug = 'julie-augustus-2026', string $old = '/inkpols-2026-07/' ): string {
	return $slug . ',Julie/Augustus 2026,Die winteruitgawe,"Gedigte, stories en meer",2026-07-01,2026/09/a.pdf,2026/09/a.jpeg,' . $old . ',Reën,Anna,Die brug,Ben,Oor taal,Carla';
}

/**
 * A migration over an in-memory site: posts, meta, attachments and Pages.
 */
function inkpols_fake( ?string $csv, array $attachments = array(), array $pages = array(), bool $done = false ): Migration {
	return new class( $csv, $attachments, $pages, $done ) extends Migration {
		/** @var array<int, array<string, mixed>> */
		public array $posts = array();
		/** @var array<int, array<string, int|string>> */
		public array $meta = array();
		/** @var array<int, int> */
		public array $thumbnails = array();
		/** @var list<int> */
		public array $trashed = array();
		public bool $marked = false;
		private int $next = 900;

		/**
		 * @param array<string, int> $attachments Upload path → id.
		 * @param array<string, int> $pages       Page path → id.
		 */
		public function __construct( private ?string $csv, private array $attachments, private array $pages, private bool $done ) {
		}
		public function hasRun(): bool {
			return $this->done;
		}
		protected function markDone(): void {
			$this->marked = true;
			$this->done   = true;
		}
		protected function csvPath(): string {
			return '/data/inkpols-uitgawes.csv';
		}
		protected function readCsv(): ?string {
			return $this->csv;
		}
		protected function attachmentIdFor( string $path ): int {
			return $this->attachments[ $path ] ?? 0;
		}
		protected function findIssueForSlug( string $slug ): int {
			foreach ( $this->meta as $id => $meta ) {
				if ( ( $meta[ Migration::SOURCE_SLUG_META ] ?? '' ) === $slug ) {
					return $id;
				}
			}
			return 0;
		}
		protected function createIssue( array $postarr ): int {
			$id                 = ++$this->next;
			$this->posts[ $id ] = $postarr;
			return $id;
		}
		protected function updateIssue( array $postarr ): bool {
			$this->posts[ (int) $postarr['ID'] ] = $postarr;
			return true;
		}
		protected function setIssueMeta( int $issue_id, string $key, int|string $value ): void {
			$this->meta[ $issue_id ][ $key ] = $value;
		}
		protected function setCover( int $issue_id, int $cover_id ): void {
			$this->thumbnails[ $issue_id ] = $cover_id;
		}
		protected function pageIdForPath( string $path ): int {
			$id = $this->pages[ $path ] ?? 0;
			return in_array( $id, $this->trashed, true ) ? 0 : $id;
		}
		protected function trashPage( int $page_id ): bool {
			$this->trashed[] = $page_id;
			return true;
		}
		protected function homeUrl( string $path ): string {
			return 'https://ink.test' . $path;
		}
	};
}

// --- parsing ---

test( 'parseCsv reads the committed CSV: seven rows, prefilled columns set, owner columns blank', function (): void {
	$parsed = Migration::parseCsv( inkpols_fixture_csv() );

	expect( $parsed['error'] )->toBe( '' );
	expect( $parsed['problems'] )->toBe( array() );
	expect( $parsed['rows'] )->toHaveCount( 7 );

	$first = $parsed['rows'][0];
	expect( $first['slug'] )->toBe( 'november-2025' );
	expect( $first['naam'] )->toBe( 'November 2025' );
	expect( $first['datum'] )->toBe( '2025-11-01' );
	expect( $first['pdf'] )->toBe( '2025/12/Inkpols_Nov_2025.pdf' );
	expect( $first['ou_url'] )->toBe( '/inkpols-2025-11/' );
	expect( $first['titel'] )->toBe( '' );
	expect( $first['gedig_titel'] )->toBe( '' );

	expect( Migration::validRows( $parsed['rows'] )['rows'] )->toHaveCount( 7 );
} );

test( 'parseCsv strips a BOM, trims cells, keeps quoted commas and line breaks, and skips blank lines', function (): void {
	$csv = "\xEF\xBB\xBF" . INKPOLS_HEADER . "\n\n"
		. 'mei-2026, Mei 2026 ,,"Een, twee' . "\n" . 'drie",2026-05-01,,,,,,,,,' . "\n";

	$parsed = Migration::parseCsv( $csv );

	expect( $parsed['error'] )->toBe( '' );
	expect( $parsed['rows'] )->toHaveCount( 1 );
	expect( $parsed['rows'][0]['slug'] )->toBe( 'mei-2026' );
	expect( $parsed['rows'][0]['naam'] )->toBe( 'Mei 2026' );
	expect( $parsed['rows'][0]['beskrywing'] )->toBe( "Een, twee\ndrie" );
} );

test( 'parseCsv reports a row with the wrong number of cells and keeps the rest', function (): void {
	$csv = INKPOLS_HEADER . "\nkort,Kort\n" . inkpols_complete_row() . "\n";

	$parsed = Migration::parseCsv( $csv );

	expect( $parsed['rows'] )->toHaveCount( 1 );
	expect( $parsed['problems'] )->toHaveCount( 1 );
	expect( $parsed['problems'][0] )->toContain( 'Ry 2' );
} );

test( 'parseCsv fails on a header that lacks a column (every row would be misread)', function (): void {
	$parsed = Migration::parseCsv( "slug,naam,datum\nx,X,2026-01-01\n" );

	expect( $parsed['rows'] )->toBe( array() );
	expect( $parsed['error'] )->toContain( 'Kolomme ontbreek' );
	expect( $parsed['error'] )->toContain( 'artikel_skrywer' );
} );

test( 'parseCsv fails on an empty file', function (): void {
	expect( Migration::parseCsv( '' )['error'] )->not->toBe( '' );
} );

// --- row checks ---

test( 'validRows skips a bad slug, an impossible datum and a repeated slug, but not owner blanks', function (): void {
	$base = array_fill_keys( Migration::COLUMNS, '' );
	$rows = array(
		array_merge( $base, array( 'slug' => 'mei-2026', 'datum' => '2026-05-01' ) ),
		array_merge( $base, array( 'slug' => 'Mei 2026', 'datum' => '2026-05-01' ) ),
		array_merge( $base, array( 'slug' => 'feb-2026', 'datum' => '2026-02-30' ) ),
		array_merge( $base, array( 'slug' => 'mei-2026', 'datum' => '2026-06-01' ) ),
	);

	$valid = Migration::validRows( $rows );

	expect( $valid['rows'] )->toHaveCount( 1 );
	expect( $valid['rows'][0]['slug'] )->toBe( 'mei-2026' );
	expect( $valid['problems'] )->toHaveCount( 3 );
	expect( $valid['problems'][1] )->toContain( '2026-02-30' );
	expect( $valid['problems'][2] )->toContain( 'reeds vroeër' );
} );

test( 'uploadPath normalises a CSV cell to the _wp_attached_file shape', function (): void {
	expect( Migration::uploadPath( '2025/12/a.pdf' ) )->toBe( '2025/12/a.pdf' );
	expect( Migration::uploadPath( ' /2025/12/a.pdf ' ) )->toBe( '2025/12/a.pdf' );
	expect( Migration::uploadPath( 'wp-content/uploads/2025/12/a.pdf' ) )->toBe( '2025/12/a.pdf' );
	expect( Migration::uploadPath( 'https://ink.org.za/wp-content/uploads/2025/12/a.pdf' ) )->toBe( '2025/12/a.pdf' );
	expect( Migration::uploadPath( '2025\\12\\a.pdf' ) )->toBe( '2025/12/a.pdf' );
} );

test( 'oldPath reduces ou_url to a page path', function (): void {
	expect( Migration::oldPath( '/inkpols-2025-11/' ) )->toBe( 'inkpols-2025-11' );
	expect( Migration::oldPath( 'https://ink.org.za/inkpols-2025-11/' ) )->toBe( 'inkpols-2025-11' );
	expect( Migration::oldPath( '' ) )->toBe( '' );
	expect( Migration::oldPath( '/' ) )->toBe( '' );
} );

test( 'missingRequired lists today\'s owner blanks when the attachments match', function (): void {
	$row = Migration::parseCsv( inkpols_fixture_csv() )['rows'][0];

	expect( Migration::missingRequired( $row, 11, 12 ) )->toBe(
		array( 'titel', 'beskrywing', 'gedig_titel', 'gedig_skrywer', 'storie_titel', 'storie_skrywer', 'artikel_titel', 'artikel_skrywer' )
	);
} );

test( 'missingRequired reports a named file with no attachment by its path', function (): void {
	$row = Migration::parseCsv( INKPOLS_HEADER . "\n" . inkpols_complete_row() )['rows'][0];

	expect( Migration::missingRequired( $row, 11, 12 ) )->toBe( array() );
	expect( Migration::missingRequired( $row, 0, 12 ) )->toBe( array( 'pdf (geen aanhegsel by 2026/09/a.pdf nie)' ) );

	$row['voorblad'] = '';
	expect( Migration::missingRequired( $row, 11, 0 ) )->toBe( array( 'voorblad' ) );
} );

// --- builders ---

test( 'issuePostArr sets the naam as title, the CSV slug, the status and datum as the publish date', function (): void {
	$row = Migration::parseCsv( INKPOLS_HEADER . "\n" . inkpols_complete_row() )['rows'][0];
	$arr = Migration::issuePostArr( $row, 'draft' );

	expect( $arr['post_type'] )->toBe( PostTypes::INKPOLS_UITGAWE );
	expect( $arr['post_title'] )->toBe( 'Julie/Augustus 2026' );
	expect( $arr['post_name'] )->toBe( 'julie-augustus-2026' );
	expect( $arr['post_status'] )->toBe( 'draft' );
	expect( $arr['post_date'] )->toBe( '2026-07-01 00:00:00' );
	expect( $arr['edit_date'] )->toBeTrue();
} );

test( 'issueMeta maps the CSV to the FieldSets keys and the slug marker', function (): void {
	$row  = Migration::parseCsv( INKPOLS_HEADER . "\n" . inkpols_complete_row() )['rows'][0];
	$meta = Migration::issueMeta( $row, 11, 12 );

	expect( $meta[ FieldSets::INKPOLS_ISSUE_DATE ] )->toBe( '2026-07-01' );
	expect( $meta[ FieldSets::INKPOLS_PDF_ID ] )->toBe( 11 );
	expect( $meta[ FieldSets::INKPOLS_COVER_ID ] )->toBe( 12 );
	expect( $meta[ FieldSets::INKPOLS_TITEL ] )->toBe( 'Die winteruitgawe' );
	expect( $meta[ FieldSets::INKPOLS_TEASER ] )->toBe( 'Gedigte, stories en meer' );
	expect( $meta[ FieldSets::INKPOLS_GEDIG_TITEL ] )->toBe( 'Reën' );
	expect( $meta[ FieldSets::INKPOLS_ARTIKEL_SKRYWER ] )->toBe( 'Carla' );
	expect( $meta[ Migration::SOURCE_SLUG_META ] )->toBe( 'julie-augustus-2026' );
} );

test( 'the old-URL key is the one generate-redirects reads', function (): void {
	expect( Migration::SOURCE_URL_META )->toBe( PostReclassifier::SOURCE_URL_META );
} );

// --- orchestration ---

test( 'run publishes a complete row, drafts an incomplete one, records 301 sources and trashes the right Pages', function (): void {
	$csv = INKPOLS_HEADER . "\n"
		. inkpols_complete_row() . "\n"
		. "mei-junie-2026,Mei/Junie 2026,,,2026-05-01,2026/07/b.pdf,2026/07/b.jpeg,/inkpols-2026-05/,,,,,,\n";

	$migration = inkpols_fake(
		$csv,
		array( '2026/09/a.pdf' => 11, '2026/09/a.jpeg' => 12, '2026/07/b.pdf' => 21, '2026/07/b.jpeg' => 22 ),
		array( 'inkpols-2026-07' => 68227, 'inkpols-2026-05' => 67687, 'inkpols' => 65591 )
	);

	$summary = $migration->run();

	expect( $summary['error'] )->toBe( '' );
	expect( $summary['created'] )->toBe( 2 );
	expect( $summary['published'] )->toBe( 1 );
	expect( $summary['drafts'] )->toBe( 1 );
	expect( $summary['incomplete'] )->toHaveKey( 'mei-junie-2026' );
	expect( $summary['incomplete'] )->not->toHaveKey( 'julie-augustus-2026' );
	expect( $migration->marked )->toBeTrue();

	$statuses = array_column( $migration->posts, 'post_status', 'post_name' );
	expect( $statuses )->toBe( array( 'julie-augustus-2026' => 'publish', 'mei-junie-2026' => 'draft' ) );

	// Both issues carry their attachments, cover-as-thumbnail and 301 source, draft too.
	expect( $migration->meta[901][ FieldSets::INKPOLS_PDF_ID ] )->toBe( 11 );
	expect( $migration->meta[902][ FieldSets::INKPOLS_COVER_ID ] )->toBe( 22 );
	expect( $migration->thumbnails )->toBe( array( 901 => 12, 902 => 22 ) );
	expect( $migration->meta[901][ Migration::SOURCE_URL_META ] )->toBe( 'https://ink.test/inkpols-2026-07/' );
	expect( $migration->meta[902][ Migration::SOURCE_URL_META ] )->toBe( 'https://ink.test/inkpols-2026-05/' );

	// The published issue's Page and the index go; the draft's Page stays live.
	expect( $migration->trashed )->toBe( array( 68227, 65591 ) );
	expect( $summary['retired'] )->toBe( 2 );
	expect( $summary['kept'] )->toBe( 1 );
} );

test( 'a --force re-run reconciles in place: no duplicates, completed rows get published', function (): void {
	$draft     = "julie-augustus-2026,Julie/Augustus 2026,,,2026-07-01,2026/09/a.pdf,2026/09/a.jpeg,/inkpols-2026-07/,,,,,,\n";
	$migration = inkpols_fake(
		INKPOLS_HEADER . "\n" . $draft,
		array( '2026/09/a.pdf' => 11, '2026/09/a.jpeg' => 12 ),
		array( 'inkpols-2026-07' => 68227, 'inkpols' => 65591 )
	);

	$first = $migration->run();
	expect( $first['created'] )->toBe( 1 );
	expect( $first['drafts'] )->toBe( 1 );
	expect( $migration->trashed )->toBe( array( 65591 ) );

	// Without --force the completed run is a no-op.
	expect( $migration->run()['skipped'] )->toBeTrue();

	// The owner fills the row in; the CSV changes.
	( function ( string $csv ): void {
		$this->csv = $csv;
	} )->call( $migration, INKPOLS_HEADER . "\n" . inkpols_complete_row() . "\n" );

	$second = $migration->run( true );

	expect( $second['created'] )->toBe( 0 );
	expect( $second['updated'] )->toBe( 1 );
	expect( $second['published'] )->toBe( 1 );
	expect( $migration->posts )->toHaveCount( 1 );
	expect( $migration->posts[901]['ID'] )->toBe( 901 );
	expect( $migration->posts[901]['post_status'] )->toBe( 'publish' );
	expect( $migration->meta[901][ FieldSets::INKPOLS_TITEL ] )->toBe( 'Die winteruitgawe' );
	expect( $migration->trashed )->toBe( array( 65591, 68227 ) );

	// A third forced run changes nothing more.
	$third = $migration->run( true );
	expect( $third['updated'] )->toBe( 1 );
	expect( $third['retired'] )->toBe( 0 );
	expect( $migration->posts )->toHaveCount( 1 );
} );

test( 'run on today\'s committed CSV creates seven drafts, keeps their Pages, and is not judged all-zero', function (): void {
	$rows        = Migration::parseCsv( inkpols_fixture_csv() )['rows'];
	$attachments = array();
	$pages       = array( 'inkpols' => 65591 );

	foreach ( $rows as $i => $row ) {
		$attachments[ $row['pdf'] ]      = 100 + $i;
		$attachments[ $row['voorblad'] ] = 200 + $i;
		$pages[ Migration::oldPath( $row['ou_url'] ) ] = 300 + $i;
	}

	$migration = inkpols_fake( inkpols_fixture_csv(), $attachments, $pages );
	$summary   = $migration->run();

	expect( $summary['created'] )->toBe( 7 );
	expect( $summary['published'] )->toBe( 0 );
	expect( $summary['drafts'] )->toBe( 7 );
	expect( $summary['kept'] )->toBe( 7 );
	expect( $summary['retired'] )->toBe( 1 );
	expect( $summary['incomplete'] )->toHaveCount( 7 );
	expect( $summary['incomplete']['november-2025'] )->toContain( 'titel' );

	expect( MigrateAll::verdict( 'inkpols', Migration::summaryLine( $summary ) ) )->toBe( MigrateAll::VERDICT_OK );
} );

test( 'a skipped re-run is judged ok by migrate-all', function (): void {
	$summary = inkpols_fake( null, array(), array(), true )->run();

	expect( $summary['skipped'] )->toBeTrue();
	expect( MigrateAll::verdict( 'inkpols', Migration::summaryLine( $summary ) ) )->toBe( MigrateAll::VERDICT_OK );
} );

test( 'a missing CSV is an error and leaves the step not done', function (): void {
	$migration = inkpols_fake( null );
	$summary   = $migration->run();

	expect( $summary['error'] )->toContain( '/data/inkpols-uitgawes.csv' );
	expect( $migration->marked )->toBeFalse();
	expect( $migration->posts )->toBe( array() );
} );

test( 'a CSV missing a column is an error and touches nothing, not even the index Page', function (): void {
	$migration = inkpols_fake( "slug,naam\nx,X\n", array(), array( 'inkpols' => 65591 ) );
	$summary   = $migration->run();

	expect( $summary['error'] )->toContain( 'Kolomme ontbreek' );
	expect( $migration->trashed )->toBe( array() );
	expect( $migration->marked )->toBeFalse();
} );

test( 'a bad row is reported and skipped while the others are still migrated', function (): void {
	$csv       = INKPOLS_HEADER . "\n"
		. "sleg,Sleg,,,2026-13-01,,,,,,,,,\n"
		. inkpols_complete_row() . "\n";
	$migration = inkpols_fake( $csv, array( '2026/09/a.pdf' => 11, '2026/09/a.jpeg' => 12 ) );

	$summary = $migration->run();

	expect( $summary['created'] )->toBe( 1 );
	expect( $summary['problems'] )->toHaveCount( 1 );
	expect( $summary['problems'][0] )->toContain( 'sleg' );
	expect( Migration::summaryLine( $summary ) )->toContain( '1 rye oorgeslaan' );
} );

test( 'an issue that cannot be saved is reported, not counted', function (): void {
	$migration = new class() extends Migration {
		public function hasRun(): bool {
			return false;
		}
		protected function markDone(): void {}
		protected function readCsv(): ?string {
			return INKPOLS_HEADER . "\n" . inkpols_complete_row() . "\n";
		}
		protected function attachmentIdFor( string $path ): int {
			return 5;
		}
		protected function findIssueForSlug( string $slug ): int {
			return 0;
		}
		protected function createIssue( array $postarr ): int {
			return 0;
		}
		protected function pageIdForPath( string $path ): int {
			return 0;
		}
	};

	$summary = $migration->run();

	expect( $summary['created'] )->toBe( 0 );
	expect( $summary['problems'] )->toBe( array( 'Uitgawe "julie-augustus-2026" kon nie gestoor word nie.' ) );
} );

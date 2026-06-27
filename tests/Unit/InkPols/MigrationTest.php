<?php
/**
 * Unit tests for the once-off InkPols back-catalogue migration (Story 13.4).
 *
 * Target: {@see \Ink\InkPols\Migration} — legacy issues → inkpols_uitgawe records
 * with re-linked PDFs and month/year naming normalised to date+volume meta. Pure
 * builders + the idempotency-guarded orchestration over overridable seams.
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\InkPols;

use Ink\InkPols\Migration;
use Ink\Content\FieldSets;
use Ink\Content\PostTypes;
use Brain\Monkey;

beforeEach( function (): void {
	Monkey\setUp();
} );

afterEach( function (): void {
	Monkey\tearDown();
} );

// --- pure builders ---

test( 'issueDateFromName parses Afrikaans Maand JJJJ to a normalised Y-m-01', function (): void {
	expect( Migration::issueDateFromName( 'Mei 2018' ) )->toBe( '2018-05-01' );
	expect( Migration::issueDateFromName( 'Desember 2020' ) )->toBe( '2020-12-01' );
	expect( Migration::issueDateFromName( '2019 Januarie' ) )->toBe( '2019-01-01' );
} );

test( 'issueDateFromName parses numeric YYYY-MM and MM/YYYY shapes', function (): void {
	expect( Migration::issueDateFromName( '2018-05' ) )->toBe( '2018-05-01' );
	expect( Migration::issueDateFromName( '05/2018' ) )->toBe( '2018-05-01' );
	expect( Migration::issueDateFromName( '2020/3' ) )->toBe( '2020-03-01' );
} );

test( 'issueDateFromName returns empty string for an unparseable name (no fabricated date)', function (): void {
	expect( Migration::issueDateFromName( 'Lente-uitgawe' ) )->toBe( '' );
	expect( Migration::issueDateFromName( '' ) )->toBe( '' );
	expect( Migration::issueDateFromName( '2018-13' ) )->toBe( '' ); // month out of range
} );

test( 'issueDateFromName requires a PLAUSIBLE year — a volume/issue number is not a year (R13 review)', function (): void {
	// "1234" is not a 19xx/20xx year, so this must NOT fabricate 1234-05-01.
	expect( Migration::issueDateFromName( 'Mei uitgawe 1234' ) )->toBe( '' );
	expect( Migration::issueDateFromName( 'Mei 2018' ) )->toBe( '2018-05-01' ); // real year still parses
} );

test( 'issueDateFromName matches a month only as a whole word, not a substring (R13 review)', function (): void {
	// No standalone month word → no match (the year alone is not enough).
	expect( Migration::issueDateFromName( 'Bundel 2018, redakteursnota' ) )->toBe( '' );
} );

test( 'issueDateFromName picks the FIRST month by string position for a multi-month span (R13 review)', function (): void {
	// "Junie" appears before "Julie" in the string; the earlier-in-array order
	// must not win — position decides.
	expect( Migration::issueDateFromName( 'Junie-Julie 2019' ) )->toBe( '2019-06-01' );
	expect( Migration::issueDateFromName( 'Julie-Augustus 2019' ) )->toBe( '2019-07-01' );
} );

test( 'issuePostArr maps a legacy issue to a published inkpols_uitgawe', function (): void {
	$arr = Migration::issuePostArr( (object) array( 'id' => 5, 'name' => 'Mei 2018' ) );

	expect( $arr['post_type'] )->toBe( PostTypes::INKPOLS_UITGAWE );
	expect( $arr['post_title'] )->toBe( 'Mei 2018' );
	expect( $arr['post_status'] )->toBe( 'publish' );
} );

// --- orchestration over seams ---

test( 'run is a no-op when the migration has already completed (idempotent)', function (): void {
	$migration = new class() extends Migration {
		public bool $ensured = false;
		public function hasRun(): bool {
			return true;
		}
		protected function legacyIssues(): array {
			return array( (object) array( 'id' => 1, 'name' => 'Mei 2018' ) );
		}
		protected function ensureIssue( object $issue ): array {
			$this->ensured = true;
			return array( 'id' => 99, 'created' => true );
		}
	};

	$summary = $migration->run();

	expect( $summary['skipped'] )->toBeTrue();
	expect( $summary['created'] )->toBe( 0 );
	expect( $migration->ensured )->toBeFalse();
} );

test( 'run re-links PDFs, writes date+volume meta, and marks done', function (): void {
	$migration = new class() extends Migration {
		public bool $marked = false;
		/** @var list<array{0:int,1:string,2:int|string}> */
		public array $meta = array();

		public function hasRun(): bool {
			return false;
		}
		protected function legacyIssues(): array {
			return array(
				(object) array( 'id' => 1, 'name' => 'Mei 2018', 'pdf_id' => 501, 'volume' => 'Jaargang 3' ),
				(object) array( 'id' => 2, 'name' => 'Junie 2018', 'pdf_id' => 0 ), // no PDF
			);
		}
		protected function ensureIssue( object $issue ): array {
			return array( 'id' => 1 === (int) $issue->id ? 101 : 102, 'created' => true );
		}
		protected function setIssueMeta( int $issue_id, string $key, int|string $value ): void {
			$this->meta[] = array( $issue_id, $key, $value );
		}
		protected function markDone(): void {
			$this->marked = true;
		}
	};

	$summary = $migration->run();

	expect( $summary['skipped'] )->toBeFalse();
	expect( $summary['created'] )->toBe( 2 );
	expect( $summary['reconciled'] )->toBe( 0 );
	expect( $summary['relinked'] )->toBe( 1 ); // only issue 1 had a PDF
	expect( $migration->marked )->toBeTrue();

	// Issue 1: date + volume + pdf written.
	expect( $migration->meta )->toContain( array( 101, FieldSets::INKPOLS_ISSUE_DATE, '2018-05-01' ) );
	expect( $migration->meta )->toContain( array( 101, FieldSets::INKPOLS_VOLUME, 'Jaargang 3' ) );
	expect( $migration->meta )->toContain( array( 101, FieldSets::INKPOLS_PDF_ID, 501 ) );
	// Issue 2: date written, NO pdf meta (pdf_id 0).
	expect( $migration->meta )->toContain( array( 102, FieldSets::INKPOLS_ISSUE_DATE, '2018-06-01' ) );
	$pdf_for_2 = array_filter(
		$migration->meta,
		static fn ( array $row ): bool => 102 === $row[0] && FieldSets::INKPOLS_PDF_ID === $row[1]
	);
	expect( $pdf_for_2 )->toBe( array() );
} );

test( 'run skips a malformed empty-name legacy issue (no untitled uitgawe)', function (): void {
	$migration = new class() extends Migration {
		public int $ensured = 0;
		public function hasRun(): bool {
			return false;
		}
		protected function legacyIssues(): array {
			return array( (object) array( 'id' => 1, 'name' => '   ' ) );
		}
		protected function ensureIssue( object $issue ): array {
			++$this->ensured;
			return array( 'id' => 50, 'created' => true );
		}
		protected function markDone(): void {}
	};

	$summary = $migration->run();

	expect( $summary['created'] )->toBe( 0 );
	expect( $migration->ensured )->toBe( 0 );
} );

test( 'ensureIssue reuses an existing migrated issue (--force reconciles, no duplicate)', function (): void {
	$migration = new class() extends Migration {
		public int $createdCount = 0;
		public function hasRun(): bool {
			return true; // already done
		}
		protected function legacyIssues(): array {
			return array( (object) array( 'id' => 7, 'name' => 'Mei 2018', 'pdf_id' => 0 ) );
		}
		protected function findIssueForLegacy( int $legacy_id ): int {
			return 7 === $legacy_id ? 555 : 0; // already migrated to issue 555
		}
		protected function createIssue( array $postarr ): int {
			++$this->createdCount;
			return 999;
		}
		protected function setIssueMeta( int $issue_id, string $key, int|string $value ): void {}
		protected function markDone(): void {}
	};

	$summary = $migration->run( true ); // --force

	expect( $summary['skipped'] )->toBeFalse();
	// R13 review: a reconcile is NOT counted as a create — created 0, reconciled 1.
	expect( $summary['created'] )->toBe( 0 );
	expect( $summary['reconciled'] )->toBe( 1 );
	expect( $migration->createdCount )->toBe( 0 ); // reused 555, did NOT insert a duplicate
} );

test( 'volumeFor defaults to the legacy name when no explicit volume exists', function (): void {
	$migration = new class() extends Migration {
		public function publicVolumeFor( object $issue ): string {
			return $this->volumeFor( $issue );
		}
	};

	expect( $migration->publicVolumeFor( (object) array( 'name' => 'Mei 2018' ) ) )->toBe( 'Mei 2018' );
	expect( $migration->publicVolumeFor( (object) array( 'name' => 'Mei 2018', 'volume' => 'Jaargang 9' ) ) )->toBe( 'Jaargang 9' );
} );

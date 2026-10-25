<?php
/**
 * Unit tests for the once-off historical challenge migration (Story 12.8).
 *
 * Target: {@see \Ink\Challenges\Migration} — legacy challenge categories →
 * uitdaging records + uitdagingsrondte round terms, re-linking each piece. Pure
 * builders + the idempotency-guarded orchestration over overridable seams.
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Challenges;

use Ink\Challenges\Migration;
use Ink\Content\PostTypes;
use Brain\Monkey;
use Brain\Monkey\Functions;

beforeEach( function (): void {
	Monkey\setUp();
} );

afterEach( function (): void {
	Monkey\tearDown();
} );

test( 'uitdagingPostArr maps a legacy category to a published uitdaging with its brief', function (): void {
	$category = (object) array(
		'term_id'     => 5,
		'name'        => 'Oktober 2024',
		'description' => 'Skryf oor herfs.',
	);

	$arr = Migration::uitdagingPostArr( $category );

	expect( $arr['post_type'] )->toBe( PostTypes::UITDAGING );
	expect( $arr['post_title'] )->toBe( 'Oktober 2024' );
	expect( $arr['post_content'] )->toBe( 'Skryf oor herfs.' );
	expect( $arr['post_status'] )->toBe( 'publish' );
} );

test( 'uitdagingPostArr leaves the brief empty when the legacy category has no description', function (): void {
	$arr = Migration::uitdagingPostArr( (object) array( 'term_id' => 5, 'name' => 'Junie 2024', 'description' => '' ) );

	expect( $arr['post_content'] )->toBe( '' );
	expect( $arr['post_title'] )->toBe( 'Junie 2024' );
} );

test( 'run is a no-op when the migration has already completed (idempotent)', function (): void {
	$migration = new class() extends Migration {
		public bool $created = false;
		public function hasRun(): bool {
			return true;
		}
		protected function legacyCategories(): array {
			return array( (object) array( 'term_id' => 1, 'name' => 'X', 'description' => '' ) );
		}
		protected function ensureUitdaging( object $category ): int {
			$this->created = true;
			return 99;
		}
	};

	$summary = $migration->run();

	expect( $summary['skipped'] )->toBeTrue();
	expect( $summary['created'] )->toBe( 0 );
	expect( $migration->created )->toBeFalse(); // nothing was created
} );

test( 'run creates a round per legacy category, links the pieces, and marks done', function (): void {
	$migration = new class() extends Migration {
		public bool $marked = false;
		/** @var list<array{0:array<int>,1:int}> */
		public array $links = array();

		public function hasRun(): bool {
			return false;
		}
		protected function legacyCategories(): array {
			return array(
				(object) array( 'term_id' => 1, 'name' => 'Oktober', 'description' => 'A' ),
				(object) array( 'term_id' => 2, 'name' => 'November', 'description' => '' ),
			);
		}
		protected function ensureUitdaging( object $category ): int {
			return 'Oktober' === $category->name ? 101 : 102;
		}
		protected function ensureRoundTerm( int $uitdaging_id ): int {
			return 900 + $uitdaging_id;
		}
		protected function postsInCategory( int $category_id ): array {
			return 1 === $category_id ? array( 11, 12 ) : array( 21 );
		}
		protected function linkPostsToRound( array $post_ids, int $term_id ): int {
			$this->links[] = array( $post_ids, $term_id );
			return count( $post_ids );
		}
		protected function announcementDate( int $category_id ): ?string {
			return null;
		}
		protected function earliestEntryDate( array $entry_ids ): ?string {
			return null;
		}
		protected function markDone(): void {
			$this->marked = true;
		}
	};

	$summary = $migration->run();

	expect( $summary['skipped'] )->toBeFalse();
	expect( $summary['created'] )->toBe( 2 );
	expect( $summary['linked'] )->toBe( 3 ); // 2 + 1 pieces
	expect( $migration->marked )->toBeTrue();
	// Each round term was the slug-keyed term for its new uitdaging id.
	expect( $migration->links )->toBe( array( array( array( 11, 12 ), 1001 ), array( array( 21 ), 1002 ) ) );
} );

test( 'run skips a malformed empty-name category (no untitled uitdaging)', function (): void {
	$migration = new class() extends Migration {
		public int $ensured = 0;
		public function hasRun(): bool {
			return false;
		}
		protected function legacyCategories(): array {
			return array( (object) array( 'term_id' => 1, 'name' => '   ', 'description' => '' ) );
		}
		protected function ensureUitdaging( object $category ): int {
			++$this->ensured;
			return 50;
		}
		protected function markDone(): void {}
	};

	$summary = $migration->run();

	expect( $summary['created'] )->toBe( 0 );
	expect( $migration->ensured )->toBe( 0 ); // empty-name category never reaches ensureUitdaging
} );

test( 'ensureUitdaging reuses an existing migrated uitdaging (R12: --force reconciles, no duplicate)', function (): void {
	$migration = new class() extends Migration {
		public int $createdCount = 0;
		public function hasRun(): bool {
			return true; // already done
		}
		protected function legacyCategories(): array {
			return array( (object) array( 'term_id' => 7, 'name' => 'Oktober', 'description' => '' ) );
		}
		protected function findUitdagingForCategory( int $category_id ): int {
			return 7 === $category_id ? 555 : 0; // already migrated to uitdaging 555
		}
		protected function createUitdaging( array $postarr ): int {
			++$this->createdCount;
			return 999;
		}
		protected function ensureRoundTerm( int $uitdaging_id ): int {
			return $uitdaging_id;
		}
		protected function postsInCategory( int $category_id ): array {
			return array();
		}
		protected function linkPostsToRound( array $post_ids, int $term_id ): int {
			return 0;
		}
		protected function announcementDate( int $category_id ): ?string {
			return null;
		}
		protected function setPostDate( int $post_id, string $date ): void {}
		protected function markDone(): void {}
	};

	$summary = $migration->run( true ); // --force

	expect( $summary['skipped'] )->toBeFalse();
	expect( $summary['created'] )->toBe( 1 );
	expect( $migration->createdCount )->toBe( 0 ); // reused 555, did NOT insert a duplicate
} );

test( 'a round takes the date it was announced, else the month in its name, else its first entry, else keeps the import date', function (): void {
	$migration = new class() extends Migration {
		/** @var array<int, string> */
		public array $dated = array();

		public function hasRun(): bool {
			return false;
		}
		protected function legacyCategories(): array {
			return array(
				(object) array( 'term_id' => 1, 'name' => 'Announced', 'description' => '' ),
				(object) array( 'term_id' => 2, 'name' => 'Entries only', 'description' => '' ),
				(object) array( 'term_id' => 3, 'name' => 'Empty', 'description' => '' ),
				(object) array( 'term_id' => 4, 'name' => '4de Skryfkompetisie (1 Desember 2017 tot 31 Maart 2018)', 'description' => '' ),
			);
		}
		protected function ensureUitdaging( object $category ): int {
			return 100 + (int) $category->term_id;
		}
		protected function ensureRoundTerm( int $uitdaging_id ): int {
			return $uitdaging_id;
		}
		protected function postsInCategory( int $category_id ): array {
			return 3 === $category_id ? array() : array( 10 * $category_id );
		}
		protected function linkPostsToRound( array $post_ids, int $term_id ): int {
			return count( $post_ids );
		}
		protected function announcementDate( int $category_id ): ?string {
			return 1 === $category_id ? '2016-03-16 11:41:23' : null;
		}
		protected function earliestEntryDate( array $entry_ids ): ?string {
			return array() === $entry_ids ? null : '2016-05-02 15:51:35';
		}
		protected function setPostDate( int $post_id, string $date ): void {
			$this->dated[ $post_id ] = $date;
		}
		protected function markDone(): void {}
	};

	$migration->run();

	expect( $migration->dated )->toBe( array( 101 => '2016-03-16 11:41:23', 102 => '2016-05-02 15:51:35', 104 => '2017-12-01 00:00:00' ) );
} );

test( 'only writing counts as an entry: result posts and announcements are core posts', function (): void {
	expect( Migration::ENTRY_TYPES )->toBe( array( 'gedig', 'storie', 'artikel', 'skryfwerk' ) )
		->and( Migration::ENTRY_TYPES )->not->toContain( 'post' );
} );

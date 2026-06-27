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
		protected function createUitdaging( array $postarr ): int {
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
		protected function createUitdaging( array $postarr ): int {
			return 'Oktober' === $postarr['post_title'] ? 101 : 102;
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

test( 'run with force re-runs even when already completed', function (): void {
	$migration = new class() extends Migration {
		public int $createdCount = 0;
		public function hasRun(): bool {
			return true; // already done
		}
		protected function legacyCategories(): array {
			return array( (object) array( 'term_id' => 1, 'name' => 'X', 'description' => '' ) );
		}
		protected function createUitdaging( array $postarr ): int {
			++$this->createdCount;
			return 50;
		}
		protected function ensureRoundTerm( int $uitdaging_id ): int {
			return 1;
		}
		protected function postsInCategory( int $category_id ): array {
			return array();
		}
		protected function linkPostsToRound( array $post_ids, int $term_id ): int {
			return 0;
		}
		protected function markDone(): void {}
	};

	$summary = $migration->run( true );

	expect( $summary['skipped'] )->toBeFalse();
	expect( $migration->createdCount )->toBe( 1 );
} );

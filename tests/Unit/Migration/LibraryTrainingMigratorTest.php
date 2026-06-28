<?php
/**
 * Unit tests for the once-off library/training sub-path migration (Story 16.6).
 *
 * Target: {@see \Ink\Migration\LibraryTrainingMigrator} — maps `/biblioteek/` and
 * `/opleiding/` content onto their CPTs and turns sub-path segments into
 * genre/vaardigheid terms, keeping the prefixes. Pure path/CPT/taxonomy helpers
 * + the idempotency-guarded orchestration over overridable seams.
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Migration;

use Ink\Migration\LibraryTrainingMigrator;
use Ink\Content\PostTypes;
use Ink\Content\Taxonomies;
use Brain\Monkey;

beforeEach( function (): void {
	Monkey\setUp();
} );

afterEach( function (): void {
	Monkey\tearDown();
} );

// --- pure path → CPT/taxonomy helpers ---

test( 'cptForPath maps the biblioteek + opleiding prefixes and skips others', function (): void {
	expect( LibraryTrainingMigrator::cptForPath( '/biblioteek/projek-wenners/my-werk/' ) )->toBe( PostTypes::BIBLIOTEEK_ITEM );
	expect( LibraryTrainingMigrator::cptForPath( 'https://ink.test/opleiding/skryfkuns/les/' ) )->toBe( PostTypes::OPLEIDING_ARTIKEL );
	expect( LibraryTrainingMigrator::cptForPath( '/nuus/iets/' ) )->toBeNull();
	expect( LibraryTrainingMigrator::cptForPath( '/' ) )->toBeNull();
} );

test( 'taxonomyForCpt is genre for library and vaardigheid for training', function (): void {
	expect( LibraryTrainingMigrator::taxonomyForCpt( PostTypes::BIBLIOTEEK_ITEM ) )->toBe( Taxonomies::GENRE );
	expect( LibraryTrainingMigrator::taxonomyForCpt( PostTypes::OPLEIDING_ARTIKEL ) )->toBe( Taxonomies::VAARDIGHEID );
	expect( LibraryTrainingMigrator::taxonomyForCpt( PostTypes::GEDIG ) )->toBeNull();
} );

test( 'termSlugsFromPath keeps the middle segments and drops the prefix + post slug', function (): void {
	// prefix=biblioteek, post=my-gedig, terms=projek-wenners + wen-gedigte
	expect( LibraryTrainingMigrator::termSlugsFromPath( '/biblioteek/projek-wenners/wen-gedigte/my-gedig/' ) )
		->toBe( array( 'projek-wenners', 'wen-gedigte' ) );
	// flat /opleiding/les/ → prefix + post only → no terms
	expect( LibraryTrainingMigrator::termSlugsFromPath( '/opleiding/les/' ) )->toBe( array() );
	expect( LibraryTrainingMigrator::termSlugsFromPath( '/opleiding/skryfkuns/les/' ) )->toBe( array( 'skryfkuns' ) );
} );

// --- orchestration over seams ---

test( 'run is a no-op when the migration has already completed (idempotent)', function (): void {
	$migration = new class() extends LibraryTrainingMigrator {
		public bool $touched = false;
		public function hasRun(): bool {
			return true;
		}
		protected function legacyContent(): array {
			return array( (object) array( 'id' => 1, 'url' => '/biblioteek/wenners/x/' ) );
		}
		protected function setPostType( int $post_id, string $type ): void {
			$this->touched = true;
		}
		protected function flushRewrites(): void {
			$this->touched = true;
		}
		protected function markDone(): void {
			$this->touched = true;
		}
	};

	$summary = $migration->run();

	expect( $summary['skipped'] )->toBeTrue();
	expect( $migration->touched )->toBeFalse();
} );

test( 'run maps each item to its CPT, assigns sub-path terms, records source URLs, flushes once', function (): void {
	$migration = new class() extends LibraryTrainingMigrator {
		/** @var list<array{0:int,1:string}> */
		public array $set = array();
		/** @var list<array{0:int,1:string,2:array<int,string>}> */
		public array $terms = array();
		/** @var list<int> */
		public array $recorded = array();
		public int $flushes    = 0;
		public bool $marked    = false;

		public function hasRun(): bool {
			return false;
		}
		protected function legacyContent(): array {
			return array(
				(object) array( 'id' => 1, 'url' => '/biblioteek/projek-wenners/wen-gedigte/g1/' ),
				(object) array( 'id' => 2, 'url' => '/opleiding/skryfkuns/les1/' ),
				(object) array( 'id' => 3, 'url' => '/biblioteek/los-werk/' ), // no middle term
			);
		}
		protected function recordSourceUrl( int $post_id ): void {
			$this->recorded[] = $post_id;
		}
		protected function setPostType( int $post_id, string $type ): void {
			$this->set[] = array( $post_id, $type );
		}
		protected function assignTerms( int $post_id, string $taxonomy, array $slugs ): int {
			$this->terms[] = array( $post_id, $taxonomy, $slugs );
			return count( $slugs );
		}
		protected function flushRewrites(): void {
			++$this->flushes;
		}
		protected function markDone(): void {
			$this->marked = true;
		}
	};

	$summary = $migration->run();

	expect( $summary['biblioteek'] )->toBe( 2 );  // items 1 + 3
	expect( $summary['opleiding'] )->toBe( 1 );   // item 2
	expect( $summary['terms_assigned'] )->toBe( 3 ); // 2 (item1) + 1 (item2) + 0 (item3)

	expect( $migration->set )->toBe(
		array(
			array( 1, PostTypes::BIBLIOTEEK_ITEM ),
			array( 2, PostTypes::OPLEIDING_ARTIKEL ),
			array( 3, PostTypes::BIBLIOTEEK_ITEM ),
		)
	);
	// Terms land in the right taxonomy; the flat item (3) assigns none.
	expect( $migration->terms )->toBe(
		array(
			array( 1, Taxonomies::GENRE, array( 'projek-wenners', 'wen-gedigte' ) ),
			array( 2, Taxonomies::VAARDIGHEID, array( 'skryfkuns' ) ),
		)
	);
	expect( $migration->recorded )->toBe( array( 1, 2, 3 ) );
	expect( $migration->flushes )->toBe( 1 );
	expect( $migration->marked )->toBeTrue();
} );

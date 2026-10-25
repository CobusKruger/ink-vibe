<?php
/**
 * Unit tests for the once-off post → CPT reclassification (Story 16.5).
 *
 * Target: {@see \Ink\Migration\PostReclassifier} — category-driven CPT mapping
 * (unclassifiable/conflicting → skryfwerk), the inkpols→inkpols_uitgawe rename,
 * the monthly_challenge skip, source-URL recording for the 16.7 301, and the
 * slug-collision guard. Pure mapping helpers + the idempotency-guarded
 * orchestration over overridable seams.
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Migration;

use Ink\Migration\PostReclassifier;
use Ink\Content\PostTypes;
use Brain\Monkey;

beforeEach( function (): void {
	Monkey\setUp();
} );

afterEach( function (): void {
	Monkey\tearDown();
} );

// --- pure category → CPT mapping (the "never hand-classify" invariant) ---

test( 'cptForCategorySlugs maps a single recognised content-type category (incl. verhaal→storie)', function (): void {
	expect( PostReclassifier::cptForCategorySlugs( array( 'gedig' ) ) )->toBe( PostTypes::GEDIG );
	expect( PostReclassifier::cptForCategorySlugs( array( 'verhaal' ) ) )->toBe( PostTypes::STORIE ); // legacy rename
	expect( PostReclassifier::cptForCategorySlugs( array( 'kortverhaal' ) ) )->toBe( PostTypes::STORIE );
	expect( PostReclassifier::cptForCategorySlugs( array( 'artikel' ) ) )->toBe( PostTypes::ARTIKEL );
	// A content-type category alongside an unrecognised topic/round category still maps.
	expect( PostReclassifier::cptForCategorySlugs( array( 'gedig', 'oktober-2025-projek' ) ) )->toBe( PostTypes::GEDIG );
} );

test( 'cptForCategorySlugs maps the real prod category tree (poems, story subtree, articles)', function (): void {
	foreach ( array( 'gedigte', 'musiek-liriek' ) as $slug ) {
		expect( PostReclassifier::cptForCategorySlugs( array( $slug ) ) )->toBe( PostTypes::GEDIG );
	}
	foreach ( array( 'algemeen-verhale', 'algemeen', 'verhale-liefde', 'verhale-wetenskapfiksie', 'verhale-kortverhale' ) as $slug ) {
		expect( PostReclassifier::cptForCategorySlugs( array( $slug ) ) )->toBe( PostTypes::STORIE );
	}
	foreach ( array( 'artikels', 'algemeengorrel', 'rubrieke', 'prosa', 'prosa-lede-bydraes' ) as $slug ) {
		expect( PostReclassifier::cptForCategorySlugs( array( $slug ) ) )->toBe( PostTypes::ARTIKEL );
	}
} );

test( 'cptForCategorySlugs: no match falls back to skryfwerk, conflicts resolve gedig > storie > artikel', function (): void {
	expect( PostReclassifier::cptForCategorySlugs( array() ) )->toBe( PostTypes::SKRYFWERK );
	expect( PostReclassifier::cptForCategorySlugs( array( 'nuus', 'lede-bydraes' ) ) )->toBe( PostTypes::SKRYFWERK );
	expect( PostReclassifier::cptForCategorySlugs( array( 'gedig', 'artikel' ) ) )->toBe( PostTypes::GEDIG );
	expect( PostReclassifier::cptForCategorySlugs( array( 'verhaal', 'gedig' ) ) )->toBe( PostTypes::GEDIG );
	expect( PostReclassifier::cptForCategorySlugs( array( 'algemeengorrel', 'verhale-reis' ) ) )->toBe( PostTypes::STORIE );
} );

test( 'genresForCategorySlugs yields the backend genre for musiek-liriek only', function (): void {
	expect( PostReclassifier::genresForCategorySlugs( array( 'musiek-liriek', 'gedigte' ) ) )->toBe( array( 'Musiek & Liriek' ) );
	expect( PostReclassifier::genresForCategorySlugs( array( 'gedigte' ) ) )->toBe( array() );
} );

test( 'renamedPostType renames inkpols, and leaves others alone', function (): void {
	expect( PostReclassifier::renamedPostType( 'inkpols' ) )->toBe( PostTypes::INKPOLS_UITGAWE );
	expect( PostReclassifier::renamedPostType( 'post' ) )->toBeNull();
	expect( PostReclassifier::renamedPostType( 'gedig' ) )->toBeNull();
} );

test( 'isSkippedType skips monthly_challenge only', function (): void {
	expect( PostReclassifier::isSkippedType( 'monthly_challenge' ) )->toBeTrue();
	expect( PostReclassifier::isSkippedType( 'post' ) )->toBeFalse();
} );

test( 'slugCollisions finds an archive↔page clash and is empty when none', function (): void {
	expect( PostReclassifier::slugCollisions( array( 'biblioteek', 'oor-ink' ) ) )->toBe( array( 'biblioteek' ) );
	expect( PostReclassifier::slugCollisions( array( 'oor-ink', 'kontak', 'gemeenskap' ) ) )->toBe( array() );
} );

// --- orchestration over seams ---

test( 'run is a no-op when the reclassification has already completed (idempotent)', function (): void {
	$migration = new class() extends PostReclassifier {
		public bool $touched = false;
		public function hasRun(): bool {
			return true;
		}
		protected function legacyPosts(): array {
			return array( (object) array( 'id' => 1, 'post_type' => 'post', 'category_slugs' => array( 'gedig' ) ) );
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

	expect( $summary['already_done'] )->toBeTrue();
	expect( $summary['reassigned'] )->toBe( 0 );
	expect( $migration->touched )->toBeFalse();
} );

test( 'run maps posts, renames inkpols, skips monthly_challenge, records source URLs, flushes once', function (): void {
	$migration = new class() extends PostReclassifier {
		/** @var list<array{0:int,1:string}> */
		public array $set = array();
		/** @var list<int> */
		public array $recorded = array();
		public int $flushes    = 0;
		public bool $marked    = false;

		public function hasRun(): bool {
			return false;
		}
		protected function legacyPosts(): array {
			return array(
				(object) array( 'id' => 1, 'post_type' => 'post', 'category_slugs' => array( 'gedig' ) ),         // → gedig
				(object) array( 'id' => 2, 'post_type' => 'post', 'category_slugs' => array( 'nuus' ) ),          // → skryfwerk
				(object) array( 'id' => 3, 'post_type' => 'post', 'category_slugs' => array( 'nuus', 'lede-bydraes' ) ), // unrecognised → skryfwerk
				(object) array( 'id' => 4, 'post_type' => 'inkpols', 'category_slugs' => array() ),               // rename
				(object) array( 'id' => 5, 'post_type' => 'monthly_challenge', 'category_slugs' => array() ),     // skipped
			);
		}
		protected function categorySlugsFor( object $post ): array {
			return (array) ( $post->category_slugs ?? array() );
		}
		protected function recordSourceUrl( int $post_id ): void {
			$this->recorded[] = $post_id;
		}
		protected function setPostType( int $post_id, string $type ): void {
			$this->set[] = array( $post_id, $type );
		}
		protected function existingPageSlugs(): array {
			return array( 'oor-ink' );
		}
		protected function flushRewrites(): void {
			++$this->flushes;
		}
		protected function markDone(): void {
			$this->marked = true;
		}
	};

	$summary = $migration->run();

	expect( $summary['reassigned'] )->toBe( 1 );    // post 1 → gedig
	expect( $summary['to_skryfwerk'] )->toBe( 2 );  // posts 2 + 3
	expect( $summary['renamed'] )->toBe( 1 );       // post 4 inkpols → inkpols_uitgawe
	expect( $summary['skipped'] )->toBe( 1 );       // post 5 monthly_challenge

	// monthly_challenge (5) is NEVER reassigned; its source URL is NOT recorded.
	expect( $migration->recorded )->toBe( array( 1, 2, 3, 4 ) );
	expect( $migration->set )->toBe(
		array(
			array( 1, PostTypes::GEDIG ),
			array( 2, PostTypes::SKRYFWERK ),
			array( 3, PostTypes::SKRYFWERK ),
			array( 4, PostTypes::INKPOLS_UITGAWE ),
		)
	);
	expect( $migration->flushes )->toBe( 1 ); // flushed exactly once, after the loop
	expect( $migration->marked )->toBeTrue();
} );

test( 'run leaves /biblioteek/ and /opleiding/ flat posts to Story 16.6 (R16 — order-independent, no 301 loss)', function (): void {
	$migration = new class() extends PostReclassifier {
		/** @var list<int> */
		public array $set = array();
		/** @var list<int> */
		public array $recorded = array();

		public function hasRun(): bool {
			return false;
		}
		protected function legacyPosts(): array {
			return array(
				(object) array( 'id' => 1, 'post_type' => 'post', 'category_slugs' => array( 'gedig' ), 'url' => 'https://ink.test/my-gedig/' ),
				(object) array( 'id' => 2, 'post_type' => 'post', 'category_slugs' => array( 'gedig' ), 'url' => 'https://ink.test/biblioteek/wen/g/' ), // library → skip
				(object) array( 'id' => 3, 'post_type' => 'post', 'category_slugs' => array( 'artikel' ), 'url' => 'https://ink.test/opleiding/skryf/les/' ), // training → skip
			);
		}
		protected function categorySlugsFor( object $post ): array {
			return (array) ( $post->category_slugs ?? array() );
		}
		protected function recordSourceUrl( int $post_id ): void {
			$this->recorded[] = $post_id;
		}
		protected function setPostType( int $post_id, string $type ): void {
			$this->set[] = $post_id;
		}
		protected function existingPageSlugs(): array {
			return array();
		}
		protected function flushRewrites(): void {}
		protected function markDone(): void {}
	};

	$summary = $migration->run();

	// Only the non-prefixed post 1 is reassigned; the library/training posts are skipped.
	expect( $summary['reassigned'] )->toBe( 1 );
	expect( $summary['skipped'] )->toBe( 2 );
	expect( $migration->set )->toBe( array( 1 ) );
	// Their SOURCE_URL_META is NOT overwritten with a post-rename permalink here.
	expect( $migration->recorded )->toBe( array( 1 ) );
} );

test( 'run keeps staff result posts as core posts, trashes the committed duplicate, and still classifies member writing', function (): void {
	$migration = new class() extends PostReclassifier {
		/** @var list<array{0:int,1:string}> */
		public array $set = array();
		/** @var list<int> */
		public array $trashed = array();

		public function hasRun(): bool {
			return false;
		}
		protected function legacyPosts(): array {
			return array(
				// Staff-authored result post: stays a `post`.
				(object) array( 'id' => 1, 'post_type' => 'post', 'category_slugs' => array( 'projekte' ), 'title' => 'Maart 2023 - OOP projek: Kommentaar en uitslae', 'staff' => true ),
				// Same wording from a member: ordinary writing, falls back to skryfwerk.
				(object) array( 'id' => 2, 'post_type' => 'post', 'category_slugs' => array( 'projekte' ), 'title' => 'Maart 2023 - OOP projek: Kommentaar en uitslae', 'staff' => false ),
				// The committed duplicate (35127).
				(object) array( 'id' => 35127, 'post_type' => 'post', 'category_slugs' => array(), 'title' => 'PLAGIAAT', 'staff' => false ),
				(object) array( 'id' => 3, 'post_type' => 'post', 'category_slugs' => array( 'gedigte' ), 'title' => 'Môre', 'staff' => false ),
			);
		}
		protected function categorySlugsFor( object $post ): array {
			return (array) ( $post->category_slugs ?? array() );
		}
		protected function recordSourceUrl( int $post_id ): void {}
		protected function setPostType( int $post_id, string $type ): void {
			$this->set[] = array( $post_id, $type );
		}
		protected function trashPost( int $post_id ): void {
			$this->trashed[] = $post_id;
		}
		protected function assignGenre( int $post_id, string $genre ): void {}
		protected function existingPageSlugs(): array {
			return array();
		}
		protected function flushRewrites(): void {}
		protected function markDone(): void {}
	};

	$summary = $migration->run();

	expect( $summary['results'] )->toBe( 1 );
	expect( $summary['dropped'] )->toBe( 1 );
	expect( $migration->trashed )->toBe( array( 35127 ) );
	expect( $migration->set )->toBe( array( array( 2, PostTypes::SKRYFWERK ), array( 3, PostTypes::GEDIG ) ) );
} );

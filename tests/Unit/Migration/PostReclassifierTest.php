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

test( 'cptForCategorySlugs falls through to skryfwerk for BOTH no-match AND conflicting categories (non-vacuous)', function (): void {
	// Zero recognised content-type categories → skryfwerk.
	expect( PostReclassifier::cptForCategorySlugs( array() ) )->toBe( PostTypes::SKRYFWERK );
	expect( PostReclassifier::cptForCategorySlugs( array( 'nuus', 'redaksioneel' ) ) )->toBe( PostTypes::SKRYFWERK );
	// CONFLICTING content-type categories (2+ distinct) → skryfwerk, never a guess.
	expect( PostReclassifier::cptForCategorySlugs( array( 'gedig', 'artikel' ) ) )->toBe( PostTypes::SKRYFWERK );
	expect( PostReclassifier::cptForCategorySlugs( array( 'verhaal', 'gedig' ) ) )->toBe( PostTypes::SKRYFWERK );
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
				(object) array( 'id' => 3, 'post_type' => 'post', 'category_slugs' => array( 'gedig', 'artikel' ) ), // conflict → skryfwerk
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

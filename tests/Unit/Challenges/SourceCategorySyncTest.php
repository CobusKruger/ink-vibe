<?php
/**
 * Unit tests for the uitdaging ↔ source-category sync.
 *
 * Target: {@see \Ink\Challenges\SourceCategorySync}. Seams stubbed; no WordPress.
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Challenges;

use Ink\Challenges\SourceCategorySync;

/**
 * Build a sync whose I/O is recorded rather than performed.
 *
 * @param array<int,int>        $source     uitdaging id → category id.
 * @param array<int,list<int>>  $categories post id → category ids.
 * @param array<int,list<int>>  $filed      category id → post ids.
 */
function syncDouble( array $source, array $categories = array(), array $filed = array() ): object {
	return new class( $source, $categories, $filed ) extends SourceCategorySync {
		/** @var list<array{0:int,1:int}> */
		public array $appended = array();

		public function __construct( private array $source, private array $categories, private array $filed ) {}

		protected function sourceMap(): array {
			return $this->source;
		}
		protected function categoryIdsOf( int $post_id ): array {
			return $this->categories[ $post_id ] ?? array();
		}
		protected function roundTermId( int $uitdaging_id ): int {
			return $uitdaging_id * 10;
		}
		protected function postsInCategory( int $category_id ): array {
			return $this->filed[ $category_id ] ?? array();
		}
		protected function appendRoundTerm( int $post_id, int $term_id ): void {
			$this->appended[] = array( $post_id, $term_id );
		}
	};
}

test( 'matchingUitdagings returns only uitdagings whose source category is on the post', function (): void {
	$map = array( 5 => 100, 6 => 200, 7 => 0 );

	expect( SourceCategorySync::matchingUitdagings( array( 100, 300 ), $map ) )->toBe( array( 5 ) );
	expect( SourceCategorySync::matchingUitdagings( array( 100, 200 ), $map ) )->toBe( array( 5, 6 ) );
	expect( SourceCategorySync::matchingUitdagings( array( 999 ), $map ) )->toBe( array() );
	// An uitdaging with no source category (0) never matches.
	expect( SourceCategorySync::matchingUitdagings( array( 0 ), $map ) )->toBe( array() );
} );

test( 'ticking a source category appends that challenge round to the post', function (): void {
	$sync = syncDouble( array( 5 => 100 ), array( 42 => array( 100, 9 ) ) );

	$sync->onSetObjectTerms( 42, array( 100 ), array(), 'category' );

	expect( $sync->appended )->toBe( array( array( 42, 50 ) ) );
} );

test( 'other taxonomies and unrelated categories do not create entries', function (): void {
	$sync = syncDouble( array( 5 => 100 ), array( 42 => array( 9 ) ) );

	$sync->onSetObjectTerms( 42, array( 9 ), array(), 'category' );
	$sync->onSetObjectTerms( 42, array( 100 ), array(), 'post_tag' );

	expect( $sync->appended )->toBe( array() );
} );

test( 'sync pulls every post already filed under each source category into its round', function (): void {
	$sync = syncDouble( array( 5 => 100, 6 => 200 ), array(), array( 100 => array( 1, 2 ), 200 => array( 3 ) ) );

	expect( $sync->sync() )->toBe( 3 );
	expect( $sync->appended )->toBe( array( array( 1, 50 ), array( 2, 50 ), array( 3, 60 ) ) );
} );

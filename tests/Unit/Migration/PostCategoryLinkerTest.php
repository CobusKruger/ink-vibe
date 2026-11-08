<?php
/**
 * Unit tests for the hand-sorted post category linker.
 *
 * Target: {@see \Ink\Migration\PostCategoryLinker}. WordPress calls stubbed.
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Migration;

use Ink\Migration\PostCategoryLinker;

test( 'every mapped category is one the reclassifier recognises', function (): void {
	expect( PostCategoryLinker::unmappedSlugs() )->toBe( array() );
} );

test( 'no post id is mapped twice', function (): void {
	$ids = PostCategoryLinker::allIds();

	expect( $ids )->toHaveCount( 151 );
	expect( array_unique( $ids ) )->toHaveCount( count( $ids ) );
} );

test( 'it links plain posts, keeps what is linked and reports the rest', function (): void {
	$linker = new class() extends PostCategoryLinker {
		/** @var list<string> */
		public array $added = array();

		protected function categoryId( string $slug ): int {
			return 'artikels' === $slug ? 0 : 7;
		}
		protected function postType( int $post_id ): string {
			return match ( $post_id ) {
				51747   => 'gedig', // Already reclassified: skipped.
				1041    => 'post',
				default => 'post',
			};
		}
		protected function hasCategory( int $post_id, int $term_id ): bool {
			return 1179 === $post_id;
		}
		protected function addCategory( int $post_id, int $term_id ): void {
			$this->added[] = $post_id . ':' . $term_id;
		}
	};

	$summary = $linker->run();

	expect( $linker->added )->toContain( '1041:7' );
	expect( $linker->added )->not->toContain( '51747:7' );
	expect( $linker->added )->not->toContain( '1179:7' );
	expect( $summary['already'] )->toBe( 1 );
	// 125 + 23 candidates, minus 1 non-post, minus 1 already linked; the missing category's 3 are skipped.
	expect( $summary['linked'] )->toBe( 146 );
	expect( implode( ' ', $summary['problems'] ) )->toContain( 'artikels' );
} );

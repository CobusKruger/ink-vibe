<?php
/**
 * Unit tests for the migration 301 redirect layer (Story 16.7).
 *
 * Target: {@see \Ink\Migration\RedirectGenerator} — builds the old-path → new-URL
 * map from recorded source URLs (only when the path changed) and serves 301s at
 * runtime. Pure path/map helpers + the build + serve orchestration over seams.
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Migration;

use Ink\Migration\RedirectGenerator;
use Brain\Monkey;

beforeEach( function (): void {
	Monkey\setUp();
} );

afterEach( function (): void {
	Monkey\tearDown();
} );

// --- pure path + map helpers ---

test( 'normalisePath reduces URLs/paths to a leading-slashed, trailing-slash-free path', function (): void {
	expect( RedirectGenerator::normalisePath( 'https://ink.test/gedig/foo/' ) )->toBe( '/gedig/foo' );
	expect( RedirectGenerator::normalisePath( '/gedig/foo' ) )->toBe( '/gedig/foo' );
	expect( RedirectGenerator::normalisePath( '/' ) )->toBe( '/' );
	expect( RedirectGenerator::normalisePath( '' ) )->toBe( '/' );
} );

test( 'buildRedirectMap emits an entry for a CHANGED url and NONE for an unchanged one (non-vacuous)', function (): void {
	$map = RedirectGenerator::buildRedirectMap(
		array(
			// flat post → typed CPT: path changed → 301.
			array( 'old' => 'https://ink.test/my-gedig/', 'new' => 'https://ink.test/gedig/my-gedig/' ),
			// /biblioteek/ single, prefix kept: path unchanged → NO redirect.
			array( 'old' => 'https://ink.test/biblioteek/wen/', 'new' => 'https://ink.test/biblioteek/wen/' ),
			// trailing-slash-only difference is NOT a change.
			array( 'old' => 'https://ink.test/opleiding/les', 'new' => 'https://ink.test/opleiding/les/' ),
		)
	);

	expect( $map )->toBe( array( '/my-gedig' => 'https://ink.test/gedig/my-gedig/' ) );
} );

test( 'buildRedirectMap skips records missing an old or new URL', function (): void {
	$map = RedirectGenerator::buildRedirectMap(
		array(
			array( 'old' => '', 'new' => 'https://ink.test/gedig/x/' ),
			array( 'old' => 'https://ink.test/x/', 'new' => '' ),
		)
	);

	expect( $map )->toBe( array() );
} );

test( 'redirectTargetFor matches on the normalised path (slash-insensitive) or returns null', function (): void {
	$map = array( '/my-gedig' => 'https://ink.test/gedig/my-gedig/' );

	expect( RedirectGenerator::redirectTargetFor( '/my-gedig/', $map ) )->toBe( 'https://ink.test/gedig/my-gedig/' );
	expect( RedirectGenerator::redirectTargetFor( '/unknown/', $map ) )->toBeNull();
} );

// --- build over seams ---

test( 'build is a no-op when already completed (idempotent)', function (): void {
	$gen = new class() extends RedirectGenerator {
		public bool $stored = false;
		public function hasRun(): bool {
			return true;
		}
		protected function recordedRedirectSources(): array {
			return array( (object) array( 'id' => 1, 'old' => '/x/' ) );
		}
		protected function storeMap( array $map ): void {
			$this->stored = true;
		}
		protected function markDone(): void {}
	};

	$summary = $gen->build();

	expect( $summary['skipped'] )->toBeTrue();
	expect( $summary['count'] )->toBe( 0 );
	expect( $gen->stored )->toBeFalse();
} );

test( 'build stores the changed-URL map and reports the count', function (): void {
	$gen = new class() extends RedirectGenerator {
		/** @var array<string,string> */
		public array $map = array();
		public bool $marked = false;

		public function hasRun(): bool {
			return false;
		}
		protected function recordedRedirectSources(): array {
			return array(
				(object) array( 'id' => 1, 'old' => 'https://ink.test/my-gedig/' ),
				(object) array( 'id' => 2, 'old' => 'https://ink.test/biblioteek/wen/' ), // unchanged
			);
		}
		protected function currentPermalink( int $post_id ): string {
			return 1 === $post_id
				? 'https://ink.test/gedig/my-gedig/'
				: 'https://ink.test/biblioteek/wen/';
		}
		protected function storeMap( array $map ): void {
			$this->map = $map;
		}
		protected function markDone(): void {
			$this->marked = true;
		}
	};

	$summary = $gen->build();

	expect( $summary['count'] )->toBe( 1 ); // only the changed one
	expect( $gen->map )->toBe( array( '/my-gedig' => 'https://ink.test/gedig/my-gedig/' ) );
	expect( $gen->marked )->toBeTrue();
} );

// --- serve over seams ---

test( 'maybeRedirect issues a 301 to the mapped target on a match', function (): void {
	$gen = new class() extends RedirectGenerator {
		public ?string $redirected = null;
		protected function loadMap(): array {
			return array( '/my-gedig' => 'https://ink.test/gedig/my-gedig/' );
		}
		protected function requestPath(): string {
			return '/my-gedig/';
		}
		protected function doRedirect( string $target ): void {
			$this->redirected = $target;
		}
	};

	$gen->maybeRedirect();

	expect( $gen->redirected )->toBe( 'https://ink.test/gedig/my-gedig/' );
} );

test( 'maybeRedirect is a no-op on a miss, an empty map, or a self-target (loop guard)', function (): void {
	$miss = new class() extends RedirectGenerator {
		public bool $redirected = false;
		protected function loadMap(): array {
			return array( '/a' => 'https://ink.test/b/' );
		}
		protected function requestPath(): string {
			return '/elsewhere/';
		}
		protected function doRedirect( string $target ): void {
			$this->redirected = true;
		}
	};
	$miss->maybeRedirect();
	expect( $miss->redirected )->toBeFalse();

	$empty = new class() extends RedirectGenerator {
		public bool $redirected = false;
		protected function loadMap(): array {
			return array();
		}
		protected function requestPath(): string {
			return '/a/';
		}
		protected function doRedirect( string $target ): void {
			$this->redirected = true;
		}
	};
	$empty->maybeRedirect();
	expect( $empty->redirected )->toBeFalse();

	$loop = new class() extends RedirectGenerator {
		public bool $redirected = false;
		protected function loadMap(): array {
			return array( '/a' => 'https://ink.test/a/' ); // target path == request path
		}
		protected function requestPath(): string {
			return '/a/';
		}
		protected function doRedirect( string $target ): void {
			$this->redirected = true;
		}
	};
	$loop->maybeRedirect();
	expect( $loop->redirected )->toBeFalse();
} );

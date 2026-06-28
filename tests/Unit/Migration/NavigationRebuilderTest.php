<?php
/**
 * Unit tests for the once-off fresh navigation rebuild (Story 16.8).
 *
 * Target: {@see \Ink\Migration\NavigationRebuilder} — builds a canonical
 * `wp_navigation` entity from the new IA. Pure item/markup helpers + the
 * idempotency-guarded get-or-create orchestration over overridable seams.
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Migration;

use Ink\Migration\NavigationRebuilder;
use Brain\Monkey;

beforeEach( function (): void {
	Monkey\setUp();
} );

afterEach( function (): void {
	Monkey\tearDown();
} );

// --- pure IA + markup helpers ---

test( 'navItems is the ordered new IA at canonical routes (incl. the Epic-15 org pages)', function (): void {
	$items  = NavigationRebuilder::navItems();
	$labels = array_column( $items, 'label' );
	$urls   = array_column( $items, 'url' );

	expect( $labels )->toBe(
		array( 'Tuis', 'Ontdek', 'Biblioteek', 'Opleiding', 'Uitdagings', 'InkPols', 'Gemeenskap', 'Oor INK', 'Kontak' )
	);
	// Canonical routes, including the Epic-15 org pages.
	expect( $urls )->toContain( '/oor-ink' );
	expect( $urls )->toContain( '/kontak' );
	expect( $urls )->toContain( '/uitdagings' );
} );

test( 'toNavigationMarkup wraps one navigation-link per item in a navigation block', function (): void {
	$markup = NavigationRebuilder::toNavigationMarkup(
		array(
			array( 'label' => 'Tuis', 'url' => '/' ),
			array( 'label' => 'Oor INK', 'url' => '/oor-ink' ),
		)
	);

	expect( $markup )->toStartWith( '<!-- wp:navigation -->' );
	expect( $markup )->toEndWith( '<!-- /wp:navigation -->' );
	expect( substr_count( $markup, 'wp:navigation-link' ) )->toBe( 2 );
	expect( $markup )->toContain( '"label":"Tuis"' );
	expect( $markup )->toContain( '"url":"/oor-ink"' );
} );

// --- orchestration over seams ---

test( 'run is a no-op when the rebuild has already completed (idempotent)', function (): void {
	$rebuilder = new class() extends NavigationRebuilder {
		public bool $touched = false;
		public function hasRun(): bool {
			return true;
		}
		protected function existingNavId(): int {
			$this->touched = true;
			return 0;
		}
		protected function markDone(): void {
			$this->touched = true;
		}
	};

	$summary = $rebuilder->run();

	expect( $summary['skipped'] )->toBeTrue();
	expect( $rebuilder->touched )->toBeFalse();
} );

test( 'run creates a navigation entity when none exists', function (): void {
	$rebuilder = new class() extends NavigationRebuilder {
		public ?string $created_content = null;
		public bool $updated            = false;

		public function hasRun(): bool {
			return false;
		}
		protected function existingNavId(): int {
			return 0;
		}
		protected function createNav( string $title, string $content ): int {
			$this->created_content = $content;
			return 77;
		}
		protected function updateNav( int $id, string $content ): void {
			$this->updated = true;
		}
		protected function markDone(): void {}
	};

	$summary = $rebuilder->run();

	expect( $summary['created'] )->toBeTrue();
	expect( $summary['nav_id'] )->toBe( 77 );
	expect( $summary['items'] )->toBe( count( NavigationRebuilder::navItems() ) );
	expect( $rebuilder->updated )->toBeFalse();
	expect( $rebuilder->created_content )->toContain( 'wp:navigation-link' );
} );

test( 'run updates the existing navigation entity rather than creating a duplicate', function (): void {
	$rebuilder = new class() extends NavigationRebuilder {
		public int $updated_id = 0;
		public bool $created    = false;

		public function hasRun(): bool {
			return false;
		}
		protected function existingNavId(): int {
			return 42;
		}
		protected function createNav( string $title, string $content ): int {
			$this->created = true;
			return 0;
		}
		protected function updateNav( int $id, string $content ): void {
			$this->updated_id = $id;
		}
		protected function markDone(): void {}
	};

	$summary = $rebuilder->run();

	expect( $summary['created'] )->toBeFalse();
	expect( $summary['nav_id'] )->toBe( 42 );
	expect( $rebuilder->updated_id )->toBe( 42 );
	expect( $rebuilder->created )->toBeFalse();
} );

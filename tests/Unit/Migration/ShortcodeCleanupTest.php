<?php
/**
 * Unit tests for the once-off WPBakery shortcode cleanup (Story 16.12).
 *
 * Target: {@see \Ink\Migration\ShortcodeCleanup} — strips `[vc_*]` tags while
 * keeping inner content and leaving non-`vc_` shortcodes intact. Pure strip
 * helper + the idempotency-guarded orchestration over overridable seams.
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Migration;

use Ink\Migration\ShortcodeCleanup;
use Brain\Monkey;

beforeEach( function (): void {
	Monkey\setUp();
} );

afterEach( function (): void {
	Monkey\tearDown();
} );

// --- pure strip (the "no raw [vc_*]" invariant) ---

test( 'stripVcShortcodes removes vc tags but keeps the inner content', function (): void {
	expect( ShortcodeCleanup::stripVcShortcodes( '[vc_column_text]Hallo wêreld[/vc_column_text]' ) )
		->toBe( 'Hallo wêreld' );

	$rich = '[vc_row][vc_column][vc_column_text]Paragraaf een.[/vc_column_text][/vc_column][/vc_row]';
	expect( ShortcodeCleanup::stripVcShortcodes( $rich ) )->toBe( 'Paragraaf een.' );

	// self-closing vc tag
	expect( ShortcodeCleanup::stripVcShortcodes( 'Voor[vc_separator]Na' ) )->toBe( 'VoorNa' );

	// vc tag with attributes
	expect( ShortcodeCleanup::stripVcShortcodes( '[vc_row css=".vc_custom_1{}"]X[/vc_row]' ) )->toBe( 'X' );
} );

test( 'stripVcShortcodes leaves non-vc shortcodes and plain content untouched (non-vacuous)', function (): void {
	// Core/other shortcodes must survive — only the WPBakery prefix is targeted.
	expect( ShortcodeCleanup::stripVcShortcodes( '[gallery ids="1,2,3"]' ) )->toBe( '[gallery ids="1,2,3"]' );
	expect( ShortcodeCleanup::stripVcShortcodes( '[caption]Foto[/caption]' ) )->toBe( '[caption]Foto[/caption]' );
	expect( ShortcodeCleanup::stripVcShortcodes( 'Gewone teks, geen kortkode.' ) )->toBe( 'Gewone teks, geen kortkode.' );

	// Mixed: vc stripped, gallery kept.
	expect( ShortcodeCleanup::stripVcShortcodes( '[vc_column_text][gallery ids="9"][/vc_column_text]' ) )
		->toBe( '[gallery ids="9"]' );
} );

// --- orchestration over seams ---

test( 'run is a no-op when the cleanup has already completed (idempotent)', function (): void {
	$migration = new class() extends ShortcodeCleanup {
		public bool $touched = false;
		public function hasRun(): bool {
			return true;
		}
		protected function contentRecords(): array {
			return array( (object) array( 'id' => 1, 'content' => '[vc_row]X[/vc_row]' ) );
		}
		protected function updatePostContent( int $post_id, string $content ): void {
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

test( 'run rewrites ONLY the posts whose content changed and counts them', function (): void {
	$migration = new class() extends ShortcodeCleanup {
		/** @var list<array{0:int,1:string}> */
		public array $written = array();
		public bool $marked   = false;

		public function hasRun(): bool {
			return false;
		}
		protected function contentRecords(): array {
			return array(
				(object) array( 'id' => 1, 'content' => '[vc_column_text]Een[/vc_column_text]' ), // changed
				(object) array( 'id' => 2, 'content' => 'Geen kortkode hier' ),                   // unchanged → no write
				(object) array( 'id' => 3, 'content' => '[vc_separator]Twee' ),                   // changed
			);
		}
		protected function updatePostContent( int $post_id, string $content ): void {
			$this->written[] = array( $post_id, $content );
		}
		protected function markDone(): void {
			$this->marked = true;
		}
	};

	$summary = $migration->run();

	expect( $summary['cleaned'] )->toBe( 2 ); // posts 1 + 3 only
	expect( $migration->written )->toBe(
		array(
			array( 1, 'Een' ),
			array( 3, 'Twee' ),
		)
	);
	expect( $migration->marked )->toBeTrue();
} );

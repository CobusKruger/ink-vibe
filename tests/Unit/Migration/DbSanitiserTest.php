<?php
/**
 * Unit tests for the once-off DB clone sanitiser (Story 16.1).
 *
 * Target: {@see \Ink\Migration\DbSanitiser} — strips transients + finished
 * Action Scheduler logs to a clean migration baseline while preserving members,
 * subscriptions, content, and media. Pure target-set helpers + the idempotency-
 * guarded orchestration over overridable I/O seams.
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Migration;

use Ink\Migration\DbSanitiser;
use Brain\Monkey;

beforeEach( function (): void {
	Monkey\setUp();
} );

afterEach( function (): void {
	Monkey\tearDown();
} );

// --- pure target-set helpers (the preservation invariant) ---

test( 'transientLikePrefixes targets ONLY transient namespaces — never a non-transient option (preservation guard)', function (): void {
	$prefixes = DbSanitiser::transientLikePrefixes();

	expect( $prefixes )->not->toBeEmpty();

	foreach ( $prefixes as $prefix ) {
		// Non-vacuous: each prefix must be a concrete transient namespace, so the
		// LIKE can never broaden to ordinary options (siteurl, blogname, the Woo
		// Memberships rows, …). A bare '%' / '' would match everything — forbidden.
		expect( $prefix )->not->toBe( '' );
		expect( $prefix )->not->toContain( '%' );
		expect(
			str_starts_with( $prefix, '_transient_' ) || str_starts_with( $prefix, '_site_transient_' )
		)->toBeTrue();
	}

	// The four canonical transient option-name namespaces (AC #4).
	expect( $prefixes )->toContain( '_transient_' );
	expect( $prefixes )->toContain( '_transient_timeout_' );
	expect( $prefixes )->toContain( '_site_transient_' );
	expect( $prefixes )->toContain( '_site_transient_timeout_' );
} );

test( 'purgeableActionStatuses purges finished logs but PRESERVES pending/in-progress work', function (): void {
	$statuses = DbSanitiser::purgeableActionStatuses();

	expect( $statuses )->toContain( 'complete' );
	expect( $statuses )->toContain( 'failed' );
	expect( $statuses )->toContain( 'canceled' );
	// The load-bearing exclusion: live scheduled work is never dropped.
	expect( $statuses )->not->toContain( 'pending' );
	expect( $statuses )->not->toContain( 'in-progress' );
} );

// --- orchestration over seams ---

test( 'run is a no-op when the sanitise has already completed (idempotent)', function (): void {
	$sanitiser = new class() extends DbSanitiser {
		public bool $touched = false;
		public function hasRun(): bool {
			return true;
		}
		protected function deleteTransients(): int {
			$this->touched = true;
			return 5;
		}
		protected function markDone(): void {
			$this->touched = true;
		}
	};

	$summary = $sanitiser->run();

	expect( $summary['skipped'] )->toBeTrue();
	expect( $summary['transients'] )->toBe( 0 );
	expect( $summary['actions'] )->toBe( 0 );
	expect( $summary['logs'] )->toBe( 0 );
	expect( $sanitiser->touched )->toBeFalse();
} );

test( 'run aggregates the per-target deletes and marks done', function (): void {
	$sanitiser = new class() extends DbSanitiser {
		public bool $marked = false;
		public function hasRun(): bool {
			return false;
		}
		protected function deleteTransients(): int {
			return 42;
		}
		protected function deleteFinishedActions(): int {
			return 7;
		}
		protected function deleteOrphanLogs(): int {
			return 3;
		}
		protected function markDone(): void {
			$this->marked = true;
		}
	};

	$summary = $sanitiser->run();

	expect( $summary['skipped'] )->toBeFalse();
	expect( $summary['transients'] )->toBe( 42 );
	expect( $summary['actions'] )->toBe( 7 );
	expect( $summary['logs'] )->toBe( 3 );
	expect( $sanitiser->marked )->toBeTrue();
} );

test( 'a completed sanitise still re-runs under --force', function (): void {
	$sanitiser = new class() extends DbSanitiser {
		public function hasRun(): bool {
			return true;
		}
		protected function deleteTransients(): int {
			return 1;
		}
		protected function deleteFinishedActions(): int {
			return 0;
		}
		protected function deleteOrphanLogs(): int {
			return 0;
		}
		protected function markDone(): void {}
	};

	$summary = $sanitiser->run( true );

	expect( $summary['skipped'] )->toBeFalse();
	expect( $summary['transients'] )->toBe( 1 );
} );

// --- $wpdb deleting seams (house Mockery pattern) ---

test( 'deleteTransients escapes each prefix with esc_like and deletes via prepared LIKE', function (): void {
	$wpdb            = \Mockery::mock();
	$wpdb->options   = 'wp_options';
	$wpdb->prefix    = 'wp_';
	$GLOBALS['wpdb'] = $wpdb;

	// esc_like keeps the literal underscores literal (so they are not wildcards).
	$wpdb->shouldReceive( 'esc_like' )->andReturnUsing(
		static fn ( string $s ): string => str_replace( '_', '\\_', $s )
	);
	$wpdb->shouldReceive( 'prepare' )->andReturnUsing(
		static fn ( string $sql, $arg ): string => "PREPARED:{$arg}"
	);
	// Capture every query so we can assert the ESCAPED LIKE actually reaches it
	// (non-vacuous: the test would fail if esc_like were dropped or the wrong
	// prefix used), not merely that the affected-rows sum is right.
	$queried = array();
	$wpdb->shouldReceive( 'query' )->times( 4 )->andReturnUsing(
		static function ( string $sql ) use ( &$queried ): int {
			$queried[] = $sql;
			return array( 10, 0, 2, 0 )[ count( $queried ) - 1 ] ?? 0;
		}
	);

	$sanitiser = new class() extends DbSanitiser {
		public function publicDeleteTransients(): int {
			return $this->deleteTransients();
		}
	};

	expect( $sanitiser->publicDeleteTransients() )->toBe( 12 );

	// The first DELETE bound the esc_like-escaped `_transient_` prefix (literal
	// underscores, trailing %), confining the LIKE to the transient namespace.
	expect( $queried[0] )->toContain( '\\_transient\\_%' );
	expect( $queried )->toHaveCount( 4 );

	unset( $GLOBALS['wpdb'] );
} );

test( 'deleteFinishedActions is a no-op when the actions table is absent on the clone', function (): void {
	$wpdb            = \Mockery::mock();
	$wpdb->prefix    = 'wp_';
	$GLOBALS['wpdb'] = $wpdb;
	$wpdb->shouldNotReceive( 'query' );

	$sanitiser = new class() extends DbSanitiser {
		protected function tableExists( string $table ): bool {
			return false;
		}
		public function publicDeleteFinishedActions(): int {
			return $this->deleteFinishedActions();
		}
	};

	expect( $sanitiser->publicDeleteFinishedActions() )->toBe( 0 );

	unset( $GLOBALS['wpdb'] );
} );

<?php
/**
 * Unit tests for INK dynamic-surface cache bypass (Story 18.5, NFR-3).
 *
 * Target: {@see \Ink\Cache\CacheControl} — the pure shouldBypassCache() decision
 * + the bypass-emission orchestration via overridable seams. Brain-Monkey, no
 * WordPress.
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Cache;

use Ink\Cache\CacheControl;
use Brain\Monkey;
use Brain\Monkey\Functions;

beforeEach( function (): void {
	Monkey\setUp();
} );

afterEach( function (): void {
	Monkey\tearDown();
} );

// --- pure decision ---

test( 'shouldBypassCache bypasses an INK admin-post request', function (): void {
	expect( ( new CacheControl() )->shouldBypassCache( array( 'ink_admin_post' => true ) ) )->toBeTrue();
} );

test( 'shouldBypassCache bypasses a filter-opted-out request', function (): void {
	expect( ( new CacheControl() )->shouldBypassCache( array( 'filtered_bypass' => true ) ) )->toBeTrue();
} );

test( 'shouldBypassCache does NOT bypass an anonymous normal page', function (): void {
	expect( ( new CacheControl() )->shouldBypassCache( array( 'ink_admin_post' => false, 'filtered_bypass' => false ) ) )->toBeFalse();
	// empty context (no signals) → no bypass.
	expect( ( new CacheControl() )->shouldBypassCache( array() ) )->toBeFalse();
} );

// --- single sources ---

test( 'the admin-post prefix is the generic INK signal', function (): void {
	expect( CacheControl::ADMIN_POST_PREFIX )->toBe( 'ink_' );
} );

test( 'the bypass filter name is the opt-out seam', function (): void {
	expect( CacheControl::BYPASS_FILTER )->toBe( 'ink_cache_bypass' );
} );

// --- maybeBypass(): fires the emission only on a private surface (non-vacuous) ---

test( 'maybeBypass triggers the no-cache emission for an INK admin-post request', function (): void {
	Functions\when( 'apply_filters' )->alias( static fn ( string $hook, $value ) => $value );

	$control = new class() extends CacheControl {
		public bool $bypassed = false;
		protected function isInkAdminPost(): bool {
			return true;
		}
		protected function bypass(): void {
			$this->bypassed = true;
		}
	};

	$control->maybeBypass();

	expect( $control->bypassed )->toBeTrue();
} );

test( 'maybeBypass does NOT emit no-cache for a normal anonymous page', function (): void {
	Functions\when( 'apply_filters' )->alias( static fn ( string $hook, $value ) => $value );

	$control = new class() extends CacheControl {
		public bool $bypassed = false;
		protected function isInkAdminPost(): bool {
			return false;
		}
		protected function bypass(): void {
			$this->bypassed = true;
		}
	};

	$control->maybeBypass();

	expect( $control->bypassed )->toBeFalse();
} );

test( 'maybeBypass honours the ink_cache_bypass filter even on a normal page', function (): void {
	Functions\when( 'apply_filters' )->alias(
		static fn ( string $hook, $value ) => CacheControl::BYPASS_FILTER === $hook ? true : $value
	);

	$control = new class() extends CacheControl {
		public bool $bypassed = false;
		protected function isInkAdminPost(): bool {
			return false;
		}
		protected function bypass(): void {
			$this->bypassed = true;
		}
	};

	$control->maybeBypass();

	expect( $control->bypassed )->toBeTrue();
} );

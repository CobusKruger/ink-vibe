<?php
/**
 * Unit tests for the Sponsors module bootstrap (Story 14.3/14.4 review patch, FR-58).
 *
 * Target: {@see \Ink\Sponsors\Module}. The load-bearing guarantee is the 14.3 AC-5
 * invariant — "no logo dumps on content pages": the sponsor surfaces are blocks
 * embedded only where wanted, NEVER injected via a global content hook. This test
 * asserts `register()` wires only block-init hooks and binds nothing to
 * `the_content`/`the_excerpt`/`loop_end` (the channels that would auto-dump sponsor
 * markup onto every single content page).
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Sponsors;

use Ink\Sponsors\Module;
use Brain\Monkey;
use Brain\Monkey\Functions;

beforeEach( function (): void {
	Monkey\setUp();
} );

afterEach( function (): void {
	Monkey\tearDown();
} );

test( 'the Sponsors module registers NO content-injection hook (14.3 AC-5: no logo dumps on content pages)', function (): void {
	$hooks = array();

	Functions\when( 'add_action' )->alias(
		static function ( string $hook ) use ( &$hooks ): bool {
			$hooks[] = $hook;
			return true;
		}
	);
	Functions\when( 'add_filter' )->alias(
		static function ( string $hook ) use ( &$hooks ): bool {
			$hooks[] = $hook;
			return true;
		}
	);

	( new Module() )->register();

	// The strip + recognition blocks register on `init` (proves register() ran — non-vacuous).
	expect( $hooks )->toContain( 'init' );

	// And NOTHING binds to a content-injection channel — the surfaces are blocks,
	// embedded only on the homepage / Oor INK, never auto-appended to content.
	expect( $hooks )->not->toContain( 'the_content' );
	expect( $hooks )->not->toContain( 'the_excerpt' );
	expect( $hooks )->not->toContain( 'loop_end' );
	expect( $hooks )->not->toContain( 'wp_footer' );
} );

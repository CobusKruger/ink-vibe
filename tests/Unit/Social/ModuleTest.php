<?php
/**
 * Unit tests for the Social module bootstrap (Story 9.1, FR-37).
 *
 * Target: {@see \Ink\Social\Module}. `register()` wires the BuddyPress
 * `bp_active_components` scope filter only when BuddyPress is present, and is a
 * clean no-op otherwise.
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Social;

use Ink\Social\Module;
use Brain\Monkey;

beforeEach( function (): void {
	Monkey\setUp();
} );

afterEach( function (): void {
	Monkey\tearDown();
} );

/**
 * Build a Module with the BuddyPress-presence seam pinned, so neither case
 * depends on a process-wide `buddypress()` stub leaking between tests.
 */
function ink_social_module( bool $bp_active ): Module {
	return new class( $bp_active ) extends Module {
		public function __construct( private bool $bp_active ) {}

		protected function buddyPressActive(): bool {
			return $this->bp_active;
		}
	};
}

test( 'register() wires the bp_active_components scope filter when BuddyPress is present', function (): void {
	ink_social_module( true )->register();

	expect(
		has_filter( 'bp_active_components', 'Ink\Social\BuddyPress::scopeComponents' )
	)->not->toBeFalse();
} );

test( 'register() is a clean no-op when BuddyPress is absent', function (): void {
	ink_social_module( false )->register();

	expect( has_filter( 'bp_active_components' ) )->toBeFalse();
} );

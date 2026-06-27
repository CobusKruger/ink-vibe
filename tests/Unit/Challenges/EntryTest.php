<?php
/**
 * Unit tests for the entry-time Gradering snapshot (Story 12.4, FR-48/UJ-4).
 *
 * Target: {@see \Ink\Challenges\Entry} — records the writer's entry-time Gradering
 * pool onto the entry when `ink/uitdaging_entry_linked` fires. The Tiers read is an
 * overridable seam (`gradingValueFor`) so the snapshot logic is unit-testable without
 * the Tiers Api.
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Challenges;

use Ink\Challenges\Entry;
use Brain\Monkey;
use Brain\Monkey\Functions;

beforeEach( function (): void {
	Monkey\setUp();
} );

afterEach( function (): void {
	Monkey\tearDown();
} );

/**
 * An Entry double with the Tiers read stubbed to a fixed grade.
 */
function ink_entry_with_grade( string $grade ): Entry {
	return new class( $grade ) extends Entry {
		public function __construct( private string $grade ) {}

		protected function gradingValueFor( int $user_id ): string {
			return $this->grade;
		}
	};
}

test( 'onEntryLinked snapshots the author entry-time grade onto the entry meta', function (): void {
	Functions\when( 'get_post_meta' )->justReturn( '' );        // not yet snapshotted
	Functions\when( 'get_post_field' )->justReturn( 7 );        // author id

	Functions\expect( 'update_post_meta' )->once()->with( 42, Entry::GRADERING_META_KEY, 'goud' );

	ink_entry_with_grade( 'goud' )->onEntryLinked( 42, array( 5 ) );

	expect( true )->toBeTrue();
} );

test( 'onEntryLinked is idempotent — an already-snapshotted entry keeps its pool', function (): void {
	Functions\when( 'get_post_meta' )->justReturn( 'brons' ); // already snapshotted

	Functions\expect( 'update_post_meta' )->never();

	ink_entry_with_grade( 'goud' )->onEntryLinked( 42, array( 5 ) );

	expect( true )->toBeTrue();
} );

test( 'onEntryLinked ignores a non-positive post id', function (): void {
	Functions\expect( 'update_post_meta' )->never();

	ink_entry_with_grade( 'goud' )->onEntryLinked( 0, array( 5 ) );

	expect( true )->toBeTrue();
} );

test( 'onEntryLinked ignores an entry with no resolvable author', function (): void {
	Functions\when( 'get_post_meta' )->justReturn( '' );
	Functions\when( 'get_post_field' )->justReturn( 0 ); // no author

	Functions\expect( 'update_post_meta' )->never();

	ink_entry_with_grade( 'goud' )->onEntryLinked( 42, array( 5 ) );

	expect( true )->toBeTrue();
} );

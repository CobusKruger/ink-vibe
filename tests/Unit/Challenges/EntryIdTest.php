<?php
/**
 * Unit tests for the per-type EntryID data model (Story 12A.1, FR-50-R1).
 *
 * Target: {@see \Ink\Challenges\EntryId} — the per-type EntryID number persisted on the
 * authoritative entry post at collation, the idempotent first-wins assign primitive, and
 * the canonical "{type} {number}" string. Pure layers + the validated meta write.
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Challenges;

use Ink\Challenges\EntryId;
use Brain\Monkey;
use Brain\Monkey\Functions;

beforeEach( function (): void {
	Monkey\setUp();
	Functions\when( '__' )->returnArg( 1 );
} );

afterEach( function (): void {
	Monkey\tearDown();
} );

test( 'format composes "{type label} {number}" for a valid number', function (): void {
	expect( EntryId::format( 'Gedig', 1 ) )->toBe( 'Gedig 1' );
	expect( EntryId::format( 'Storie', 12 ) )->toBe( 'Storie 12' );
} );

test( 'format returns empty for a non-positive number or empty label', function (): void {
	expect( EntryId::format( 'Gedig', 0 ) )->toBe( '' );
	expect( EntryId::format( 'Gedig', -1 ) )->toBe( '' );
	expect( EntryId::format( '', 3 ) )->toBe( '' );
	expect( EntryId::format( '   ', 3 ) )->toBe( '' );
} );

test( 'assign writes BOTH the type and number meta for an unassigned entry', function (): void {
	// Unassigned: numberFor reads 0.
	Functions\when( 'get_post_meta' )->justReturn( '' );
	Functions\expect( 'update_post_meta' )->once()->with( 42, EntryId::TYPE_META_KEY, 'gedig' );
	Functions\expect( 'update_post_meta' )->once()->with( 42, EntryId::NUMBER_META_KEY, 3 );

	expect( EntryId::assign( 42, 'gedig', 3 ) )->toBeTrue();
} );

test( 'assign is idempotent — an already-numbered entry is NOT renumbered (no burn)', function (): void {
	// Non-vacuous: the SAME inputs WOULD write when unassigned (asserted in the test
	// above). Here the entry already carries number 7, so assign must refuse to write.
	Functions\when( 'get_post_meta' )->justReturn( '7' );
	Functions\expect( 'update_post_meta' )->never();

	expect( EntryId::assign( 42, 'gedig', 3 ) )->toBeFalse();
} );

test( 'assign rejects junk (non-positive id, empty type, non-positive number) without writing', function (): void {
	Functions\when( 'get_post_meta' )->justReturn( '' );
	Functions\expect( 'update_post_meta' )->never();

	expect( EntryId::assign( 0, 'gedig', 3 ) )->toBeFalse();
	expect( EntryId::assign( 42, '', 3 ) )->toBeFalse();
	expect( EntryId::assign( 42, '   ', 3 ) )->toBeFalse();
	expect( EntryId::assign( 42, 'gedig', 0 ) )->toBeFalse();
	expect( EntryId::assign( 42, 'gedig', -2 ) )->toBeFalse();
} );

test( 'numberFor / typeFor read the stored meta (and floor junk numbers to 0)', function (): void {
	Functions\when( 'get_post_meta' )->alias(
		function ( $id, $key ) {
			if ( EntryId::NUMBER_META_KEY === $key ) {
				return '5';
			}
			if ( EntryId::TYPE_META_KEY === $key ) {
				return 'storie';
			}
			return '';
		}
	);

	expect( EntryId::numberFor( 9 ) )->toBe( 5 );
	expect( EntryId::typeFor( 9 ) )->toBe( 'storie' );
	expect( EntryId::isAssigned( 9 ) )->toBeTrue();

	// Non-positive id never reads.
	expect( EntryId::numberFor( 0 ) )->toBe( 0 );
	expect( EntryId::typeFor( 0 ) )->toBe( '' );
	expect( EntryId::isAssigned( 0 ) )->toBeFalse();
} );

test( 'isAssigned is false for an unassigned entry', function (): void {
	Functions\when( 'get_post_meta' )->justReturn( '' );

	expect( EntryId::isAssigned( 42 ) )->toBeFalse();
} );

test( 'entryIdFor composes the canonical string from the stored type + number via Terms', function (): void {
	Functions\when( 'get_post_meta' )->alias(
		function ( $id, $key ) {
			if ( EntryId::NUMBER_META_KEY === $key ) {
				return '2';
			}
			if ( EntryId::TYPE_META_KEY === $key ) {
				return 'gedig';
			}
			return '';
		}
	);

	// Terms::label('gedig') resolves to the literal 'Gedig' (the __ mock returns arg 1).
	expect( EntryId::entryIdFor( 9 ) )->toBe( 'Gedig 2' );
} );

test( 'entryIdFor returns empty for an unassigned entry', function (): void {
	Functions\when( 'get_post_meta' )->justReturn( '' );

	expect( EntryId::entryIdFor( 42 ) )->toBe( '' );
} );

<?php
/**
 * Unit tests for the once-off user role reassignment (Story 16.2).
 *
 * Target: {@see \Ink\Migration\UserReclassifier} — collapses non-staff accounts
 * onto the single member base role (FR-2), preserves staff, and cleans configured
 * legacy profile meta. Pure staff-classification helpers + the idempotency-guarded
 * orchestration over overridable I/O seams.
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Migration;

use Ink\Migration\UserReclassifier;
use Brain\Monkey;

beforeEach( function (): void {
	Monkey\setUp();
} );

afterEach( function (): void {
	Monkey\tearDown();
} );

// --- pure staff-classification (the preservation invariant) ---

test( 'isStaff protects administrator AND editor, and only those (preservation guard)', function (): void {
	// Non-vacuous: both protected roles must be recognised so staff are never demoted.
	expect( UserReclassifier::isStaff( array( 'administrator' ) ) )->toBeTrue();
	expect( UserReclassifier::isStaff( array( 'editor' ) ) )->toBeTrue();
	expect( UserReclassifier::isStaff( array( 'subscriber', 'editor' ) ) )->toBeTrue();

	// Member / legacy roles are NOT staff — they collapse onto the base role.
	expect( UserReclassifier::isStaff( array( 'subscriber' ) ) )->toBeFalse();
	expect( UserReclassifier::isStaff( array( 'reader' ) ) )->toBeFalse();
	expect( UserReclassifier::isStaff( array( 'writer' ) ) )->toBeFalse();
	expect( UserReclassifier::isStaff( array( 'youzify_member' ) ) )->toBeFalse();
	expect( UserReclassifier::isStaff( array() ) )->toBeFalse();
} );

test( 'preservedRoles is exactly administrator + editor', function (): void {
	expect( UserReclassifier::preservedRoles() )->toBe( array( 'administrator', 'editor' ) );
} );

test( 'the base role is the single member role subscriber', function (): void {
	expect( UserReclassifier::BASE_ROLE )->toBe( 'subscriber' );
} );

// --- orchestration over seams ---

test( 'run is a no-op when the reassignment has already completed (idempotent)', function (): void {
	$migration = new class() extends UserReclassifier {
		public bool $touched = false;
		public function hasRun(): bool {
			return true;
		}
		protected function legacyUserIds(): array {
			return array( 1, 2 );
		}
		protected function reassignToBaseRole( int $user_id ): void {
			$this->touched = true;
		}
		protected function markDone(): void {
			$this->touched = true;
		}
	};

	$summary = $migration->run();

	expect( $summary['skipped'] )->toBeTrue();
	expect( $summary['reassigned'] )->toBe( 0 );
	expect( $migration->touched )->toBeFalse();
} );

test( 'run reassigns non-staff, preserves staff, and never demotes an editor', function (): void {
	$migration = new class() extends UserReclassifier {
		/** @var list<int> */
		public array $reassigned = array();
		public bool $marked      = false;

		public function hasRun(): bool {
			return false;
		}
		protected function legacyUserIds(): array {
			return array( 10, 11, 12, 13 );
		}
		protected function userRoles( int $user_id ): array {
			return match ( $user_id ) {
				10 => array( 'reader' ),        // legacy member → base role
				11 => array( 'editor' ),        // staff → preserved
				12 => array( 'writer' ),        // legacy member → base role
				13 => array( 'administrator' ), // staff → preserved
				default => array(),
			};
		}
		protected function reassignToBaseRole( int $user_id ): void {
			$this->reassigned[] = $user_id;
		}
		protected function markDone(): void {
			$this->marked = true;
		}
	};

	$summary = $migration->run();

	expect( $summary['skipped'] )->toBeFalse();
	expect( $summary['reassigned'] )->toBe( 2 );
	expect( $summary['staff_preserved'] )->toBe( 2 );
	expect( $migration->reassigned )->toBe( array( 10, 12 ) ); // staff 11/13 untouched
	expect( $migration->marked )->toBeTrue();
} );

test( 'run cleans only the configured legacy meta keys (destructive op is opt-in)', function (): void {
	$migration = new class() extends UserReclassifier {
		/** @var list<array{0:int,1:string}> */
		public array $deleted = array();

		public function hasRun(): bool {
			return false;
		}
		protected function legacyUserIds(): array {
			return array( 20 );
		}
		protected function userRoles( int $user_id ): array {
			return array( 'subscriber' );
		}
		protected function reassignToBaseRole( int $user_id ): void {}
		protected function legacyMetaKeys(): array {
			return array( 'youzify_cover', 'bp_field_99', '' ); // '' is skipped
		}
		protected function cleanLegacyMeta( int $user_id ): int {
			// Exercise the real default body via the parent (delete_user_meta mocked).
			return parent::cleanLegacyMeta( $user_id );
		}
		protected function markDone(): void {}
	};

	Monkey\Functions\expect( 'delete_user_meta' )->twice()->andReturn( true );

	$summary = $migration->run();

	expect( $summary['meta_cleaned'] )->toBe( 2 ); // the empty key is skipped
} );

test( 'the default legacyMetaKeys is empty — an un-configured run cleans no meta', function (): void {
	$migration = new class() extends UserReclassifier {
		public function exposeKeys(): array {
			return $this->legacyMetaKeys();
		}
	};

	expect( $migration->exposeKeys() )->toBe( array() );
} );

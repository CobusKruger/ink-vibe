<?php
/**
 * Unit tests for the once-off BuddyPress friendship → follow migration (Story 16.9).
 *
 * Target: {@see \Ink\Migration\FollowGraphMigration} — confirmed friendships
 * become two mutual follow records (pending skipped, orphaned skipped, deduped);
 * old activity trimmed. Pure pairing/cutoff helpers + the idempotency-guarded
 * orchestration over overridable seams.
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Migration;

use Ink\Migration\FollowGraphMigration;
use Brain\Monkey;

beforeEach( function (): void {
	Monkey\setUp();
} );

afterEach( function (): void {
	Monkey\tearDown();
} );

// --- pure pairing (the MR-8 invariant) ---

test( 'followPairsFromFriendships turns a confirmed friendship into BOTH directed pairs', function (): void {
	$pairs = FollowGraphMigration::followPairsFromFriendships(
		array(
			array( 'initiator_user_id' => 3, 'friend_user_id' => 7, 'is_confirmed' => 1 ),
		)
	);

	expect( $pairs )->toBe( array( array( 3, 7 ), array( 7, 3 ) ) );
} );

test( 'followPairsFromFriendships NEVER converts a pending friendship (non-vacuous)', function (): void {
	$pairs = FollowGraphMigration::followPairsFromFriendships(
		array(
			array( 'initiator_user_id' => 3, 'friend_user_id' => 7, 'is_confirmed' => 0 ), // pending
			array( 'initiator_user_id' => 4, 'friend_user_id' => 8, 'is_confirmed' => 1 ), // confirmed
		)
	);

	// Only the confirmed pair's two directions — the pending one contributes nothing.
	expect( $pairs )->toBe( array( array( 4, 8 ), array( 8, 4 ) ) );
} );

test( 'followPairsFromFriendships skips self-edges + non-positive ids and dedups reciprocals', function (): void {
	$pairs = FollowGraphMigration::followPairsFromFriendships(
		array(
			array( 'initiator_user_id' => 5, 'friend_user_id' => 5, 'is_confirmed' => 1 ), // self → skip
			array( 'initiator_user_id' => 0, 'friend_user_id' => 9, 'is_confirmed' => 1 ), // bad id → skip
			array( 'initiator_user_id' => 1, 'friend_user_id' => 2, 'is_confirmed' => 1 ),
			array( 'initiator_user_id' => 2, 'friend_user_id' => 1, 'is_confirmed' => 1 ), // reciprocal dup
		)
	);

	expect( $pairs )->toBe( array( array( 1, 2 ), array( 2, 1 ) ) ); // deduped to two directed edges
} );

test( 'cutoffDate subtracts the retention window (2 years)', function (): void {
	expect( FollowGraphMigration::cutoffDate( '2026-06-28 12:00:00' ) )->toBe( '2024-06-28 12:00:00' );
} );

// --- orchestration over seams ---

test( 'run is a no-op when the migration has already completed (idempotent)', function (): void {
	$migration = new class() extends FollowGraphMigration {
		public bool $touched = false;
		public function hasRun(): bool {
			return true;
		}
		protected function friendships(): array {
			return array( array( 'initiator_user_id' => 1, 'friend_user_id' => 2, 'is_confirmed' => 1 ) );
		}
		protected function recordFollow( int $follower, int $followee ): bool {
			$this->touched = true;
			return true;
		}
		protected function markDone(): void {
			$this->touched = true;
		}
	};

	$summary = $migration->run();

	expect( $summary['skipped'] )->toBeTrue();
	expect( $migration->touched )->toBeFalse();
} );

test( 'run records two follows per confirmed friendship, skips pending + orphaned, trims activity', function (): void {
	$migration = new class() extends FollowGraphMigration {
		/** @var list<array{0:int,1:int}> */
		public array $followed = array();
		public bool $marked    = false;

		public function hasRun(): bool {
			return false;
		}
		protected function friendships(): array {
			return array(
				array( 'initiator_user_id' => 1, 'friend_user_id' => 2, 'is_confirmed' => 1 ), // both valid
				array( 'initiator_user_id' => 3, 'friend_user_id' => 99, 'is_confirmed' => 1 ), // 99 orphaned
				array( 'initiator_user_id' => 4, 'friend_user_id' => 5, 'is_confirmed' => 0 ),  // pending
			);
		}
		protected function validUser( int $user_id ): bool {
			return 99 !== $user_id; // 99 is not an imported account
		}
		protected function recordFollow( int $follower, int $followee ): bool {
			$this->followed[] = array( $follower, $followee );
			return true;
		}
		protected function trimOldActivity( string $cutoff ): int {
			return 12;
		}
		protected function now(): string {
			return '2026-06-28 00:00:00';
		}
		protected function markDone(): void {
			$this->marked = true;
		}
	};

	$summary = $migration->run();

	expect( $summary['follows_created'] )->toBe( 2 );   // friendship (1,2) → 2 records
	expect( $summary['pending_skipped'] )->toBe( 1 );   // friendship (4,5)
	expect( $summary['orphaned_skipped'] )->toBe( 2 );  // both directions of (3,99)
	expect( $summary['activity_trimmed'] )->toBe( 12 );

	expect( $migration->followed )->toBe( array( array( 1, 2 ), array( 2, 1 ) ) );
	expect( $migration->marked )->toBeTrue();
} );

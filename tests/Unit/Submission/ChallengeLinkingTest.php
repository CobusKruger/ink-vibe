<?php
/**
 * Unit tests for challenge linking at submission (Story 6.6, FR-22/UJ-4, AD-3).
 *
 * Target: {@see \Ink\Submission\ChallengeLinking} — tick an OPEN uitdaging at
 * submission → write its `uitdagingsrondte` term; "open" is the inclusive SAST
 * deadline (real {@see \Ink\Kernel\Sast} maths, pinned "now"). Pins: isOpen across
 * open/past/missing-deadline/wrong-type/draft; link() links only open ticked ids
 * (dedupe + skip ≤0); openChallenges filters to open published uitdagings.
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Submission;

use Ink\Submission\ChallengeLinking;
use Brain\Monkey;
use Brain\Monkey\Functions;

beforeEach( function (): void {
	Monkey\setUp();
} );

afterEach( function (): void {
	Monkey\tearDown();
} );

/**
 * A fixed "now" in SAST for deterministic deadline maths.
 */
function ink_now(): \DateTimeImmutable {
	return new \DateTimeImmutable( '2026-06-26 12:00:00', new \DateTimeZone( 'Africa/Johannesburg' ) );
}

test( 'isOpen is true for a published uitdaging before its deadline', function (): void {
	Functions\when( 'get_post_type' )->justReturn( 'uitdaging' );
	Functions\when( 'get_post_status' )->justReturn( 'publish' );
	Functions\when( 'get_post_meta' )->justReturn( '2026-12-31' );

	expect( ( new ChallengeLinking() )->isOpen( 7, ink_now() ) )->toBeTrue();
} );

test( 'isOpen is false after the deadline has passed', function (): void {
	Functions\when( 'get_post_type' )->justReturn( 'uitdaging' );
	Functions\when( 'get_post_status' )->justReturn( 'publish' );
	Functions\when( 'get_post_meta' )->justReturn( '2020-01-01' );

	expect( ( new ChallengeLinking() )->isOpen( 7, ink_now() ) )->toBeFalse();
} );

test( 'isOpen is false (fail-safe) when no deadline is set', function (): void {
	Functions\when( 'get_post_type' )->justReturn( 'uitdaging' );
	Functions\when( 'get_post_status' )->justReturn( 'publish' );
	Functions\when( 'get_post_meta' )->justReturn( '' );

	expect( ( new ChallengeLinking() )->isOpen( 7, ink_now() ) )->toBeFalse();
} );

test( 'isOpen is false for a non-uitdaging or unpublished post', function (): void {
	Functions\when( 'get_post_status' )->justReturn( 'publish' );
	Functions\when( 'get_post_meta' )->justReturn( '2026-12-31' );

	Functions\when( 'get_post_type' )->justReturn( 'gedig' );
	expect( ( new ChallengeLinking() )->isOpen( 7, ink_now() ) )->toBeFalse();

	Functions\when( 'get_post_type' )->justReturn( 'uitdaging' );
	Functions\when( 'get_post_status' )->justReturn( 'draft' );
	expect( ( new ChallengeLinking() )->isOpen( 7, ink_now() ) )->toBeFalse();
} );

test( 'link writes the round term only for open ticked challenges, deduped', function (): void {
	$linker = new class() extends ChallengeLinking {
		/** @var list<array{0:int,1:int}> */
		public array $assigned = array();
		/** @var array<int, bool> */
		public array $openMap = array();

		public function isOpen( int $uitdaging_id, ?\DateTimeInterface $now = null ): bool {
			return $this->openMap[ $uitdaging_id ] ?? false;
		}

		protected function resolveRoundTerm( int $uitdaging_id ): int {
			return 1000 + $uitdaging_id;
		}

		protected function assign( int $post_id, int $term_id ): void {
			$this->assigned[] = array( $post_id, $term_id );
		}

		protected function entryCountFor( int $post_id, int $uitdaging_id ): int {
			return 0; // no prior entries — the per-type cap is exercised separately.
		}
	};

	$linker->openMap = array(
		11 => true,
		22 => false, // closed — must be skipped
		33 => true,
	);

	$linked = $linker->link( 5, array( 11, 22, 33, 33, 0, -1 ) );

	expect( $linked )->toBe( array( 11, 33 ) ); // closed/dupe/invalid dropped
	expect( $linker->assigned )->toBe( array( array( 5, 1011 ), array( 5, 1033 ) ) );
} );

test( 'withinCap allows up to three entries of a type and refuses the fourth', function (): void {
	expect( ChallengeLinking::withinCap( 0 ) )->toBeTrue();
	expect( ChallengeLinking::withinCap( 2 ) )->toBeTrue();
	expect( ChallengeLinking::withinCap( 3 ) )->toBeFalse(); // already at the cap
	expect( ChallengeLinking::withinCap( 4 ) )->toBeFalse();
} );

test( 'link refuses to link a 4th entry of a type already at the per-type cap', function (): void {
	$linker = new class() extends ChallengeLinking {
		/** @var list<array{0:int,1:int}> */
		public array $assigned = array();
		/** @var array<int, int> */
		public array $counts = array();

		public function isOpen( int $uitdaging_id, ?\DateTimeInterface $now = null ): bool {
			return true;
		}

		protected function resolveRoundTerm( int $uitdaging_id ): int {
			return 1000 + $uitdaging_id;
		}

		protected function assign( int $post_id, int $term_id ): void {
			$this->assigned[] = array( $post_id, $term_id );
		}

		protected function entryCountFor( int $post_id, int $uitdaging_id ): int {
			return $this->counts[ $uitdaging_id ] ?? 0;
		}
	};

	$linker->counts = array(
		11 => 3, // already at the cap — the new entry must NOT be linked
		33 => 2, // room for one more — linked
	);

	$linked = $linker->link( 5, array( 11, 33 ) );

	expect( $linked )->toBe( array( 33 ) );
	expect( $linker->assigned )->toBe( array( array( 5, 1033 ) ) );
} );

test( 'openChallenges returns only the open published uitdagings', function (): void {
	Functions\when( 'get_the_title' )->alias( static fn( int $id ): string => 'Uitdaging ' . $id );

	$linker = new class() extends ChallengeLinking {
		/** @var array<int, bool> */
		public array $openMap = array();

		protected function publishedChallenges(): array {
			return array( (object) array( 'ID' => 11 ), (object) array( 'ID' => 22 ) );
		}

		public function isOpen( int $uitdaging_id, ?\DateTimeInterface $now = null ): bool {
			return $this->openMap[ $uitdaging_id ] ?? false;
		}
	};

	$linker->openMap = array(
		11 => true,
		22 => false,
	);

	expect( $linker->openChallenges() )->toBe( array( array( 'id' => 11, 'title' => 'Uitdaging 11' ) ) );
} );

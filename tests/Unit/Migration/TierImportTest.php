<?php
/**
 * Unit tests for the once-off writer-tier CSV import (Story 16.3).
 *
 * Target: {@see \Ink\Migration\TierImport} — sets `ink_writer_tier` from a CSV
 * keyed on email; a missing/ambiguous tier defaults to `brons` + a review flag,
 * NEVER a guessed higher grade. Pure parse/column helpers + the idempotency-
 * guarded orchestration over overridable I/O seams.
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Migration;

use Ink\Migration\TierImport;
use Ink\Kernel\Tier;
use Brain\Monkey;

beforeEach( function (): void {
	Monkey\setUp();
} );

afterEach( function (): void {
	Monkey\tearDown();
} );

// --- pure parse helper (the "never guess" invariant) ---

test( 'parseTier accepts the four canonical grades, any case/whitespace', function (): void {
	expect( TierImport::parseTier( 'brons' ) )->toBe( Tier::Brons );
	expect( TierImport::parseTier( ' Silwer ' ) )->toBe( Tier::Silwer );
	expect( TierImport::parseTier( 'GOUD' ) )->toBe( Tier::Goud );
	expect( TierImport::parseTier( 'Meester' ) )->toBe( Tier::Meester );
} );

test( 'parseTier returns null for empty/unrecognised values — it never guesses a higher grade', function (): void {
	// Non-vacuous: anything that is not an exact canonical grade is unrecognised,
	// so the caller defaults to brons + flag rather than guessing.
	expect( TierImport::parseTier( '' ) )->toBeNull();
	expect( TierImport::parseTier( '   ' ) )->toBeNull();
	expect( TierImport::parseTier( 'bronze' ) )->toBeNull();  // English ≠ canonical
	expect( TierImport::parseTier( 'gold' ) )->toBeNull();
	expect( TierImport::parseTier( 'platinum' ) )->toBeNull();
	expect( TierImport::parseTier( 'silwer-ish' ) )->toBeNull();
} );

test( 'columnIndexes detects email + tier columns by header fragment, order-independent', function (): void {
	expect( TierImport::columnIndexes( array( 'E-pos', 'Gradering' ) ) )->toBe( array( 'email' => 0, 'tier' => 1 ) );
	expect( TierImport::columnIndexes( array( 'Tier', 'Naam', 'Email' ) ) )->toBe( array( 'email' => 2, 'tier' => 0 ) );
	expect( TierImport::columnIndexes( array( 'naam', 'van' ) ) )->toBe( array( 'email' => null, 'tier' => null ) );
} );

// --- orchestration over seams ---

test( 'run is a no-op when the import has already completed (idempotent)', function (): void {
	$import = new class() extends TierImport {
		public bool $touched = false;
		public function hasRun(): bool {
			return true;
		}
		protected function readRows( string $path ): array {
			return array( array( 'email' => 'a@b.co', 'tier' => 'goud' ) );
		}
		protected function setTier( int $user_id, Tier $tier ): void {
			$this->touched = true;
		}
		protected function markDone(): void {
			$this->touched = true;
		}
	};

	$summary = $import->run( 'x.csv' );

	expect( $summary['skipped'] )->toBeTrue();
	expect( $summary['set'] )->toBe( 0 );
	expect( $import->touched )->toBeFalse();
} );

test( 'run sets recognised grades, defaults+flags ambiguous ones, and counts no-account rows', function (): void {
	$import = new class() extends TierImport {
		/** @var list<array{0:int,1:string}> */
		public array $set = array();
		/** @var list<int> */
		public array $flagged = array();
		public bool $marked   = false;

		public function hasRun(): bool {
			return false;
		}
		protected function readRows( string $path ): array {
			return array(
				array( 'email' => 'goud@ink.co', 'tier' => 'Goud' ),     // recognised
				array( 'email' => 'huh@ink.co', 'tier' => 'platinum' ),  // ambiguous → brons + flag
				array( 'email' => 'blank@ink.co', 'tier' => '' ),        // missing → brons + flag
				array( 'email' => 'ghost@ink.co', 'tier' => 'silwer' ),  // no account
			);
		}
		protected function userIdForEmail( string $email ): int {
			return match ( $email ) {
				'goud@ink.co'  => 11,
				'huh@ink.co'   => 12,
				'blank@ink.co' => 13,
				default        => 0, // ghost has no account
			};
		}
		protected function setTier( int $user_id, Tier $tier ): void {
			$this->set[] = array( $user_id, $tier->value );
		}
		protected function flagForReview( int $user_id, string $reason ): void {
			$this->flagged[] = $user_id;
		}
		protected function markDone(): void {
			$this->marked = true;
		}
	};

	$summary = $import->run( 'tiers.csv' );

	expect( $summary['set'] )->toBe( 1 );
	expect( $summary['defaulted'] )->toBe( 2 );
	expect( $summary['no_account'] )->toBe( 1 );

	// The recognised row keeps its grade; the two ambiguous rows became brons + flag,
	// and NEVER a guessed silwer/goud/meester.
	expect( $import->set )->toBe(
		array(
			array( 11, 'goud' ),
			array( 12, 'brons' ),
			array( 13, 'brons' ),
		)
	);
	expect( $import->flagged )->toBe( array( 12, 13 ) );
	expect( $import->marked )->toBeTrue();
} );

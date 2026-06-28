<?php
/**
 * Unit tests for the once-off selective options carry-forward (Story 16.11).
 *
 * Target: {@see \Ink\Migration\OptionsCarryForward} — carries only the
 * allowlisted deliberate options (site URL/name + forced `af` locale), drops
 * everything else (SEO is fresh in Rank Math). Pure allowlist/filter logic + the
 * idempotency-guarded orchestration over overridable seams.
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Migration;

use Ink\Migration\OptionsCarryForward;
use Brain\Monkey;

beforeEach( function (): void {
	Monkey\setUp();
} );

afterEach( function (): void {
	Monkey\tearDown();
} );

// --- pure allowlist + filter (the "don't clone wholesale" invariant) ---

test( 'allowedOptions is the deliberate carry-forward set', function (): void {
	$allowed = OptionsCarryForward::allowedOptions();

	expect( $allowed )->toContain( 'siteurl' );
	expect( $allowed )->toContain( 'home' );
	expect( $allowed )->toContain( 'blogname' );
	expect( $allowed )->toContain( 'WPLANG' );
	// SEO / plugin config is NOT deliberate carry-forward.
	expect( $allowed )->not->toContain( 'wpseo' );
	expect( $allowed )->not->toContain( 'rank_math' );
} );

test( 'filterCarryForward keeps allowlisted keys, DROPS SEO/plugin keys, and FORCES locale af (non-vacuous)', function (): void {
	$carry = OptionsCarryForward::filterCarryForward(
		array(
			'siteurl'         => 'https://ink.test',
			'blogname'        => 'INK',
			'wpseo'           => array( 'lots' => 'of seo config' ), // SEO → dropped
			'rank_math_xxx'   => 'fresh in Rank Math',               // SEO → dropped
			'youzify_settings' => 'retired plugin',                  // retired → dropped
			'WPLANG'          => 'en_US',                            // wrong locale → forced to af
		)
	);

	expect( $carry['siteurl'] )->toBe( 'https://ink.test' );
	expect( $carry['blogname'] )->toBe( 'INK' );
	expect( $carry['WPLANG'] )->toBe( 'af' ); // forced, overriding the legacy en_US

	// The non-allowlisted keys never appear.
	expect( $carry )->not->toHaveKey( 'wpseo' );
	expect( $carry )->not->toHaveKey( 'rank_math_xxx' );
	expect( $carry )->not->toHaveKey( 'youzify_settings' );
} );

test( 'filterCarryForward forces the af locale even when legacy carries no WPLANG', function (): void {
	$carry = OptionsCarryForward::filterCarryForward( array( 'blogname' => 'INK' ) );

	expect( $carry['WPLANG'] )->toBe( 'af' );
} );

test( 'filterCarryForward drops a non-scalar legacy value rather than casting it to "Array" (R16)', function (): void {
	$carry = OptionsCarryForward::filterCarryForward(
		array( 'blogname' => array( 'unexpected', 'array' ) ) // junk non-scalar value
	);

	expect( $carry )->not->toHaveKey( 'blogname' ); // dropped, not cast to "Array"
	expect( $carry['WPLANG'] )->toBe( 'af' );       // locale still forced
} );

// --- orchestration over seams ---

test( 'run is a no-op when the carry-forward has already completed (idempotent)', function (): void {
	$migration = new class() extends OptionsCarryForward {
		public bool $touched = false;
		public function hasRun(): bool {
			return true;
		}
		protected function legacyOptions(): array {
			return array( 'siteurl' => 'https://ink.test' );
		}
		protected function applyOption( string $key, string $value ): void {
			$this->touched = true;
		}
		protected function markDone(): void {
			$this->touched = true;
		}
	};

	$summary = $migration->run();

	expect( $summary['skipped'] )->toBeTrue();
	expect( $migration->touched )->toBeFalse();
} );

test( 'run applies the filtered carry-forward set (allowlisted + forced locale) over the seam', function (): void {
	$migration = new class() extends OptionsCarryForward {
		/** @var array<string,string> */
		public array $applied = array();
		public bool $marked   = false;

		public function hasRun(): bool {
			return false;
		}
		protected function legacyOptions(): array {
			return array(
				'siteurl' => 'https://ink.test',
				'blogname' => 'INK',
				'wpseo'   => 'seo', // dropped
			);
		}
		protected function applyOption( string $key, string $value ): void {
			$this->applied[ $key ] = $value;
		}
		protected function markDone(): void {
			$this->marked = true;
		}
	};

	$summary = $migration->run();

	expect( $summary['applied'] )->toBe( 3 );  // siteurl, blogname, WPLANG (forced)
	expect( $summary['locale'] )->toBe( 'af' );
	expect( $migration->applied )->toBe(
		array(
			'siteurl' => 'https://ink.test',
			'blogname' => 'INK',
			'WPLANG'  => 'af',
		)
	);
	expect( $migration->applied )->not->toHaveKey( 'wpseo' );
	expect( $migration->marked )->toBeTrue();
} );

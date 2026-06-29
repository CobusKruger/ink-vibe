<?php
/**
 * Unit tests for the migration redirect-integrity audit/flatten (Story 18.2, NFR-4).
 *
 * Target: {@see \Ink\Migration\RedirectIntegrity} — the pure audit() over the
 * Story-16.7 map (chains/loops/empty) and flatten() that collapses chains to the
 * final target without following cycles. INK-owned outcomes only; Brain-Monkey,
 * no WordPress.
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Migration;

use Ink\Migration\RedirectIntegrity;
use Brain\Monkey;

beforeEach( function (): void {
	Monkey\setUp();
} );

afterEach( function (): void {
	Monkey\tearDown();
} );

// --- audit(): a clean map is ok ---

test( 'audit reports ok for a clean map (no chains, loops or empty targets)', function (): void {
	$report = RedirectIntegrity::audit(
		array(
			'/my-gedig'  => 'https://ink.test/gedig/my-gedig/',
			'/my-storie' => 'https://ink.test/storie/my-storie/',
		)
	);

	expect( $report['ok'] )->toBeTrue();
	expect( $report['count'] )->toBe( 2 );
	expect( $report['chains'] )->toBe( array() );
	expect( $report['loops'] )->toBe( array() );
	expect( $report['empty'] )->toBe( array() );
} );

// --- audit(): detects a chain (301 -> 301) ---

test( 'audit detects a chain — a target whose path is itself a redirect key', function (): void {
	// /a -> /b (which is itself a key) -> /gedig/final
	$report = RedirectIntegrity::audit(
		array(
			'/a' => 'https://ink.test/b/',
			'/b' => 'https://ink.test/gedig/final/',
		)
	);

	expect( $report['ok'] )->toBeFalse();
	expect( $report['chains'] )->toBe( array( '/a' ) );
} );

// --- audit(): detects a loop ---

test( 'audit detects a loop — a target that normalises back to its own key', function (): void {
	$report = RedirectIntegrity::audit(
		array( '/loop' => 'https://ink.test/loop/' )
	);

	expect( $report['loops'] )->toBe( array( '/loop' ) );
	expect( $report['ok'] )->toBeFalse();
} );

// --- audit(): flags an empty target ---

test( 'audit flags an empty/blank target', function (): void {
	$report = RedirectIntegrity::audit(
		array(
			'/has-target' => 'https://ink.test/gedig/x/',
			'/no-target'  => '',
			'/blank'      => '   ',
		)
	);

	expect( $report['empty'] )->toBe( array( '/no-target', '/blank' ) );
	expect( $report['ok'] )->toBeFalse();
} );

// --- flatten(): collapses a chain to the final target ---

test( 'flatten collapses a chain so the first key points directly at the final target', function (): void {
	$flat = RedirectIntegrity::flatten(
		array(
			'/a' => 'https://ink.test/b/',
			'/b' => 'https://ink.test/gedig/final/',
		)
	);

	expect( $flat['/a'] )->toBe( 'https://ink.test/gedig/final/' );
	expect( $flat['/b'] )->toBe( 'https://ink.test/gedig/final/' );
	// And the flattened map now audits clean.
	expect( RedirectIntegrity::audit( $flat )['ok'] )->toBeTrue();
} );

test( 'flatten resolves a three-hop chain to the terminal destination', function (): void {
	$flat = RedirectIntegrity::flatten(
		array(
			'/a' => 'https://ink.test/b/',
			'/b' => 'https://ink.test/c/',
			'/c' => 'https://ink.test/storie/end/',
		)
	);

	expect( $flat['/a'] )->toBe( 'https://ink.test/storie/end/' );
	expect( $flat['/b'] )->toBe( 'https://ink.test/storie/end/' );
} );

// --- flatten(): does NOT follow a cycle ---

test( 'flatten leaves a cyclic entry on its original target (never loops forever)', function (): void {
	$map = array(
		'/x' => 'https://ink.test/y/',
		'/y' => 'https://ink.test/x/',
	);

	$flat = RedirectIntegrity::flatten( $map );

	// Originals preserved (no invented destination); audit still flags the cycle.
	expect( $flat['/x'] )->toBe( 'https://ink.test/y/' );
	expect( $flat['/y'] )->toBe( 'https://ink.test/x/' );
	expect( RedirectIntegrity::audit( $flat )['chains'] )->not->toBe( array() );
} );

// --- flatten(): drops empty targets ---

test( 'flatten drops entries with an empty target', function (): void {
	$flat = RedirectIntegrity::flatten(
		array(
			'/keep' => 'https://ink.test/gedig/x/',
			'/drop' => '',
		)
	);

	expect( $flat )->toHaveKey( '/keep' );
	expect( $flat )->not->toHaveKey( '/drop' );
} );

// --- flatten(): idempotent ---

test( 'flatten is idempotent — a flattened map flattens to itself', function (): void {
	$map = array(
		'/a' => 'https://ink.test/b/',
		'/b' => 'https://ink.test/gedig/final/',
	);

	$once  = RedirectIntegrity::flatten( $map );
	$twice = RedirectIntegrity::flatten( $once );

	expect( $twice )->toBe( $once );
} );

<?php
/**
 * Unit tests for the private read-count surface (Story 9.12, FR-44b, R8).
 *
 * Target: {@see \Ink\Discovery\ReadCountSurface}. The verb-less `_n()` count
 * label + the pure `toHtml()` (per-work rows, empty state) + the logged-out gate.
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Discovery;

use Ink\Discovery\ReadCountSurface;
use Brain\Monkey;
use Brain\Monkey\Functions;

beforeEach( function (): void {
	Monkey\setUp();
	Functions\when( '__' )->returnArg( 1 );
	Functions\when( 'esc_html' )->returnArg( 1 );
	Functions\when( 'esc_html__' )->returnArg( 1 );
	Functions\when( 'number_format_i18n' )->alias( static fn ( $n ): string => (string) $n );
	Functions\when( '_n' )->alias( static fn ( string $s, string $p, int $n ): string => 1 === $n ? $s : $p );
} );

afterEach( function (): void {
	Monkey\tearDown();
} );

test( 'countLabel is verb-less: singular at one, plural at zero and many, never "gelees"', function (): void {
	expect( ReadCountSurface::countLabel( 1 ) )->toBe( '1 lesing' );
	expect( ReadCountSurface::countLabel( 0 ) )->toBe( '0 lesings' );
	expect( ReadCountSurface::countLabel( 12 ) )->toBe( '12 lesings' );
	expect( ReadCountSurface::countLabel( 5 ) )->not->toContain( 'gelees' ); // verb-less
} );

test( 'toHtml renders a row per own work with its read count', function (): void {
	$rows = array(
		array( 'title' => 'Vlerke', 'count' => 12 ),
		array( 'title' => 'Brug', 'count' => 1 ),
	);

	$html = ReadCountSurface::toHtml( $rows );

	expect( $html )->toContain( 'ink-leesgetalle__lys' );
	expect( $html )->toContain( 'Vlerke' );
	expect( $html )->toContain( '12 lesings' );
	expect( $html )->toContain( 'Brug' );
	expect( $html )->toContain( '1 lesing' );
} );

test( 'toHtml shows 0 lesings for a work that was never read (graceful, R8)', function (): void {
	$html = ReadCountSurface::toHtml( array( array( 'title' => 'Stil', 'count' => 0 ) ) );

	expect( $html )->toContain( '0 lesings' );
} );

test( 'toHtml renders the empty state when the writer has no published works', function (): void {
	$html = ReadCountSurface::toHtml( array() );

	expect( $html )->toContain( 'ink-leesgetalle__leeg' );
	expect( $html )->not->toContain( '<ul' );
} );

test( 'render returns nothing for a logged-out visitor (private surface)', function (): void {
	Functions\when( 'is_user_logged_in' )->justReturn( false );

	expect( ReadCountSurface::render() )->toBe( '' );
} );

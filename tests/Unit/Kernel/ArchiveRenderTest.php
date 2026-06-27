<?php
/**
 * Unit tests for the shared archive-render primitives (Story 11.1).
 *
 * Target: {@see \Ink\Kernel\ArchiveRender}. The pure `pill()` (active marking +
 * URL self-escape) and `pagination()` (prev/next gating + CSS prefix / paged var)
 * are unit-testable without WordPress; the request-reads are covered by the
 * archives that consume them (they need WP query-var state).
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Kernel;

use Ink\Kernel\ArchiveRender;
use Brain\Monkey;
use Brain\Monkey\Functions;

beforeEach( function (): void {
	Monkey\setUp();
} );

afterEach( function (): void {
	Monkey\tearDown();
} );

/**
 * Register the escaping + URL-builder stubs the render path needs.
 */
function ink_archiverender_stubs(): void {
	Functions\when( 'esc_html__' )->returnArg( 1 );
	Functions\when( 'esc_html' )->returnArg( 1 );
	Functions\when( 'esc_attr' )->returnArg( 1 );
	Functions\when( 'esc_url' )->returnArg( 1 );
	Functions\when( 'add_query_arg' )->alias(
		static fn ( string $key, $value = '', $url = '' ): string => '/hub?' . $key . '=' . $value
	);
}

// --- pill ---

test( 'pill marks the active option with is-active + aria-current and escapes the href', function (): void {
	ink_archiverender_stubs();

	$active = ArchiveRender::pill( '/hub?genre=poesie', 'Poësie', true, 'ink-x__knoppie' );
	expect( $active )->toContain( 'is-active' );
	expect( $active )->toContain( 'aria-current="true"' );
	expect( $active )->toContain( 'Poësie' );
	expect( $active )->toContain( 'href="/hub?genre=poesie"' );

	$inactive = ArchiveRender::pill( '/hub', 'Alles', false, 'ink-x__knoppie' );
	expect( $inactive )->not->toContain( 'is-active' );
	expect( $inactive )->not->toContain( 'aria-current' );
} );

test( 'pill routes the href through esc_url itself (the single escape point)', function (): void {
	// esc_url is the ONLY thing that touches the url — assert it is actually called,
	// proving a caller no longer has to (the Epic-10 pre-escape hardening).
	Functions\when( 'esc_html' )->returnArg( 1 );
	Functions\when( 'esc_attr' )->returnArg( 1 );
	Functions\expect( 'esc_url' )->once()->with( '/raw?x=1' )->andReturn( '/escaped' );

	$html = ArchiveRender::pill( '/raw?x=1', 'Label', false, 'base' );
	expect( $html )->toContain( 'href="/escaped"' );
} );

// --- pagination ---

test( 'pagination renders prev/next only when there is more than one page', function (): void {
	ink_archiverender_stubs();

	$single = ArchiveRender::pagination( 1, 1, 'ink-opleiding', 'opleiding_bladsy' );
	expect( $single )->toBe( '' );

	$none = ArchiveRender::pagination( 1, 0, 'ink-opleiding', 'opleiding_bladsy' );
	expect( $none )->toBe( '' );
} );

test( 'pagination on a middle page links both prev and next, prefixed and on the paged var', function (): void {
	ink_archiverender_stubs();

	$html = ArchiveRender::pagination( 2, 3, 'ink-opleiding', 'opleiding_bladsy' );
	expect( $html )->toContain( 'ink-opleiding__blaai' );
	expect( $html )->toContain( 'ink-opleiding__vorige' );
	expect( $html )->toContain( 'ink-opleiding__volgende' );
	expect( $html )->toContain( 'Vorige' );
	expect( $html )->toContain( 'Volgende' );
	// Prev/next move along the supplied paged var.
	expect( $html )->toContain( 'opleiding_bladsy=1' );
	expect( $html )->toContain( 'opleiding_bladsy=3' );
} );

test( 'pagination on the first page omits prev, and on the last page omits next', function (): void {
	ink_archiverender_stubs();

	$first = ArchiveRender::pagination( 1, 3, 'ink-opleiding', 'opleiding_bladsy' );
	expect( $first )->toContain( 'ink-opleiding__volgende' );
	expect( $first )->not->toContain( 'ink-opleiding__vorige' );

	$last = ArchiveRender::pagination( 3, 3, 'ink-opleiding', 'opleiding_bladsy' );
	expect( $last )->toContain( 'ink-opleiding__vorige' );
	expect( $last )->not->toContain( 'ink-opleiding__volgende' );
} );

test( 'pagination clamps a non-positive current page to 1', function (): void {
	ink_archiverender_stubs();

	$html = ArchiveRender::pagination( 0, 3, 'ink-opleiding', 'opleiding_bladsy' );
	// Clamped to page 1 → no prev, next points at page 2.
	expect( $html )->not->toContain( 'ink-opleiding__vorige' );
	expect( $html )->toContain( 'opleiding_bladsy=2' );
} );

test( 'pagination clamps an out-of-range page down to the last page (no link beyond max) (R11)', function (): void {
	ink_archiverender_stubs();

	$html = ArchiveRender::pagination( 999, 3, 'ink-opleiding', 'opleiding_bladsy' );
	// Treated as the last page: prev to 2, never a next beyond max.
	expect( $html )->toContain( 'ink-opleiding__vorige' );
	expect( $html )->toContain( 'opleiding_bladsy=2' );
	expect( $html )->not->toContain( 'ink-opleiding__volgende' );
} );

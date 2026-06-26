<?php
/**
 * Unit tests for the Ontdek works archive (Story 8.1, FR-32).
 *
 * Target: {@see \Ink\Discovery\WorksArchive}. The heart of the archive is the
 * pure `queryArgs()` (newest-first published bydraes + defensive date browse) and
 * the pure `toHtml()` (card list, pagination, empty-state). Both are pure and
 * unit-testable without WordPress.
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Discovery;

use Ink\Discovery\WorksArchive;
use Ink\Content\PostTypes;
use Brain\Monkey;
use Brain\Monkey\Functions;

beforeEach( function (): void {
	Monkey\setUp();
} );

afterEach( function (): void {
	Monkey\tearDown();
} );

test( 'readableTypes are the three bydrae types, excluding the skryfwerk bucket', function (): void {
	$types = WorksArchive::readableTypes();

	expect( $types )->toBe( array( PostTypes::GEDIG, PostTypes::STORIE, PostTypes::ARTIKEL ) );
	expect( $types )->not->toContain( PostTypes::SKRYFWERK );
} );

test( 'queryArgs lists published bydraes newest-first, paged and per-page', function (): void {
	$args = WorksArchive::queryArgs( 3, 12, null, null );

	expect( $args['post_type'] )->toBe( array( 'gedig', 'storie', 'artikel' ) );
	expect( $args['post_status'] )->toBe( 'publish' );
	expect( $args['posts_per_page'] )->toBe( 12 );
	expect( $args['paged'] )->toBe( 3 );
	expect( $args['orderby'] )->toBe( 'date' );
	expect( $args['order'] )->toBe( 'DESC' );
	expect( $args['ignore_sticky_posts'] )->toBeTrue();
	// No date browse → no date_query.
	expect( $args )->not->toHaveKey( 'date_query' );
} );

test( 'queryArgs clamps a non-positive paged to 1', function (): void {
	expect( WorksArchive::queryArgs( 0, 12, null, null )['paged'] )->toBe( 1 );
	expect( WorksArchive::queryArgs( -5, 12, null, null )['paged'] )->toBe( 1 );
} );

test( 'queryArgs adds a date_query for a valid year and carries the month only for 1-12', function (): void {
	// Valid year + valid month → both present.
	$with_month = WorksArchive::queryArgs( 1, 12, 2026, 6 );
	expect( $with_month )->toHaveKey( 'date_query' );
	expect( $with_month['date_query'][0] )->toBe( array( 'year' => 2026, 'month' => 6 ) );

	// Valid year, no month → year only.
	$year_only = WorksArchive::queryArgs( 1, 12, 2026, null );
	expect( $year_only['date_query'][0] )->toBe( array( 'year' => 2026 ) );

	// Valid year, out-of-range month → month dropped (degrade, don't break).
	$bad_month = WorksArchive::queryArgs( 1, 12, 2026, 13 );
	expect( $bad_month['date_query'][0] )->toBe( array( 'year' => 2026 ) );
} );

test( 'queryArgs ignores a garbage year (degrades to the unfiltered listing)', function (): void {
	// Non-4-digit / out-of-range years carry no date_query at all.
	foreach ( array( 0, 50, 999, 10000 ) as $bad_year ) {
		expect( WorksArchive::queryArgs( 1, 12, $bad_year, 6 ) )->not->toHaveKey( 'date_query' );
	}
} );

test( 'toHtml renders the heading and a card per work, escaping every value', function (): void {
	Functions\when( '__' )->returnArg( 1 );
	Functions\when( 'esc_html__' )->returnArg( 1 );
	Functions\when( 'esc_html' )->returnArg( 1 );
	Functions\when( 'esc_url' )->returnArg( 1 );

	$cards = array(
		array( 'title' => 'Herfsblare', 'permalink' => '/gedig/herfsblare', 'type' => 'gedig', 'author' => 'Lid Een' ),
		array( 'title' => 'Die brug', 'permalink' => '/storie/die-brug', 'type' => 'storie', 'author' => 'Lid Twee' ),
	);

	$html = WorksArchive::toHtml( $cards, array( 'paged' => 1, 'max_pages' => 1 ) );

	// Heading is the registry plural ("Bydraes"); a card per work.
	expect( $html )->toContain( 'Bydraes' );
	expect( $html )->toContain( 'Herfsblare' );
	expect( $html )->toContain( '/gedig/herfsblare' );
	expect( $html )->toContain( 'Lid Een' );
	expect( $html )->toContain( 'Die brug' );
	expect( $html )->toContain( 'Lid Twee' );
	// Type badge sourced from the Terms registry (Gedig / Storie).
	expect( $html )->toContain( 'Gedig' );
	expect( $html )->toContain( 'Storie' );
	// Single page → no pagination nav.
	expect( $html )->not->toContain( 'ink-ontdek-werke__blaai' );
} );

test( 'toHtml renders prev/next only when there is more than one page', function (): void {
	Functions\when( '__' )->returnArg( 1 );
	Functions\when( 'esc_html__' )->returnArg( 1 );
	Functions\when( 'esc_html' )->returnArg( 1 );
	Functions\when( 'esc_url' )->returnArg( 1 );
	Functions\when( 'add_query_arg' )->alias( static fn ( string $k, int $v ): string => "?{$k}={$v}" );

	$cards = array(
		array( 'title' => 'Herfsblare', 'permalink' => '/gedig/herfsblare', 'type' => 'gedig', 'author' => 'Lid Een' ),
	);

	// Page 2 of 3 → both prev and next.
	$html = WorksArchive::toHtml( $cards, array( 'paged' => 2, 'max_pages' => 3 ) );
	expect( $html )->toContain( 'ink-ontdek-werke__blaai' );
	expect( $html )->toContain( 'Vorige' );
	expect( $html )->toContain( 'Volgende' );

	// First page → next only, no prev.
	$first = WorksArchive::toHtml( $cards, array( 'paged' => 1, 'max_pages' => 3 ) );
	expect( $first )->toContain( 'Volgende' );
	expect( $first )->not->toContain( 'ink-ontdek-werke__vorige' );
} );

test( 'toHtml shows the empty-state line (not a blank section) when there are no works', function (): void {
	Functions\when( '__' )->returnArg( 1 );
	Functions\when( 'esc_html' )->returnArg( 1 );

	$html = WorksArchive::toHtml( array(), array( 'paged' => 1, 'max_pages' => 0 ) );

	// Non-vacuous: heading still renders, plus the composed empty-state line.
	expect( $html )->toContain( 'Bydraes' );
	expect( $html )->toContain( 'Geen' );
	expect( $html )->toContain( 'ink-ontdek-werke__leeg' );
	// Not a blank section.
	expect( $html )->not->toBe( '' );
	expect( $html )->not->toContain( 'ink-ontdek-werke__list' );
} );

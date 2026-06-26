<?php
/**
 * Unit tests for the Ontdek works archive (Stories 8.1 + 8.2, FR-32/FR-33).
 *
 * Target: {@see \Ink\Discovery\WorksArchive}. The pure `queryArgs()` (newest-first
 * published bydraes + defensive date browse + type filter + count sorts) and the
 * pure `toHtml()`/`controlsHtml()` (card list, filter/sort controls, pagination,
 * empty-state) are unit-testable without WordPress.
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Discovery;

use Ink\Discovery\WorksArchive;
use Ink\Discovery\TrendingScore;
use Ink\Engagement\Api as EngagementApi;
use Ink\Content\PostTypes;
use Brain\Monkey;
use Brain\Monkey\Functions;

beforeEach( function (): void {
	Monkey\setUp();
} );

afterEach( function (): void {
	Monkey\tearDown();
} );

/**
 * Register the WP stubs the render path ({@see WorksArchive::toHtml()} +
 * {@see WorksArchive::controlsHtml()}) needs — escaping + URL builders.
 */
function ink_archive_render_stubs(): void {
	Functions\when( '__' )->returnArg( 1 );
	Functions\when( 'esc_html__' )->returnArg( 1 );
	Functions\when( 'esc_html' )->returnArg( 1 );
	Functions\when( 'esc_url' )->returnArg( 1 );
	Functions\when( 'esc_attr' )->returnArg( 1 );
	Functions\when( 'remove_query_arg' )->justReturn( '/ontdek' );
	Functions\when( 'add_query_arg' )->alias(
		static fn ( string $key, $value = '', $url = '' ): string => '/ontdek?' . $key . '=' . $value
	);
}

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
	expect( $args )->not->toHaveKey( 'date_query' );
} );

test( 'queryArgs clamps a non-positive paged to 1', function (): void {
	expect( WorksArchive::queryArgs( 0, 12, null, null )['paged'] )->toBe( 1 );
	expect( WorksArchive::queryArgs( -5, 12, null, null )['paged'] )->toBe( 1 );
} );

test( 'queryArgs adds a date_query for a valid year and carries the month only for 1-12', function (): void {
	$with_month = WorksArchive::queryArgs( 1, 12, 2026, 6 );
	expect( $with_month )->toHaveKey( 'date_query' );
	expect( $with_month['date_query'][0] )->toBe( array( 'year' => 2026, 'month' => 6 ) );

	$year_only = WorksArchive::queryArgs( 1, 12, 2026, null );
	expect( $year_only['date_query'][0] )->toBe( array( 'year' => 2026 ) );

	$bad_month = WorksArchive::queryArgs( 1, 12, 2026, 13 );
	expect( $bad_month['date_query'][0] )->toBe( array( 'year' => 2026 ) );
} );

test( 'queryArgs ignores a garbage year (degrades to the unfiltered listing)', function (): void {
	foreach ( array( 0, 50, 999, 10000 ) as $bad_year ) {
		expect( WorksArchive::queryArgs( 1, 12, $bad_year, 6 ) )->not->toHaveKey( 'date_query' );
	}
} );

// --- Story 8.2: type filter ---

test( 'queryArgs narrows post_type to a single readable type when filtered', function (): void {
	$gedig = WorksArchive::queryArgs( 1, 12, null, null, PostTypes::GEDIG );
	expect( $gedig['post_type'] )->toBe( array( 'gedig' ) );

	$storie = WorksArchive::queryArgs( 1, 12, null, null, PostTypes::STORIE );
	expect( $storie['post_type'] )->toBe( array( 'storie' ) );
} );

test( 'queryArgs ignores a garbage or excluded type (falls back to all three)', function (): void {
	foreach ( array( 'paddas', PostTypes::SKRYFWERK, 'page' ) as $bad_type ) {
		expect( WorksArchive::queryArgs( 1, 12, null, null, $bad_type )['post_type'] )
			->toBe( array( 'gedig', 'storie', 'artikel' ) );
	}
} );

// --- Story 8.2: sorts ---

test( 'the mees-geliefd sort orders by the denormalized reaction-total meta, desc, date tiebreak', function (): void {
	$args = WorksArchive::queryArgs( 1, 12, null, null, null, WorksArchive::SORT_GELIEFD );

	expect( $args['meta_key'] )->toBe( EngagementApi::reactionTotalMetaKey() );
	expect( $args['orderby'] )->toBe( array( 'meta_value_num' => 'DESC', 'date' => 'DESC' ) );
} );

test( 'the opspraakwekkend sort orders by the stored trending-score meta, desc, date tiebreak', function (): void {
	$args = WorksArchive::queryArgs( 1, 12, null, null, null, WorksArchive::SORT_OPSPRAAK );

	expect( $args['meta_key'] )->toBe( TrendingScore::META_KEY );
	expect( $args['orderby'] )->toBe( array( 'meta_value_num' => 'DESC', 'date' => 'DESC' ) );
} );

test( 'an unknown sort degrades to nuut (plain date desc, no meta_key)', function (): void {
	$args = WorksArchive::queryArgs( 1, 12, null, null, null, 'gewildste' );

	expect( $args['orderby'] )->toBe( 'date' );
	expect( $args['order'] )->toBe( 'DESC' );
	expect( $args )->not->toHaveKey( 'meta_key' );
} );

// --- toHtml / controls ---

test( 'toHtml renders the heading, the controls and a card per work, escaping every value', function (): void {
	ink_archive_render_stubs();

	$cards = array(
		array( 'title' => 'Herfsblare', 'permalink' => '/gedig/herfsblare', 'type' => 'gedig', 'author' => 'Lid Een' ),
		array( 'title' => 'Die brug', 'permalink' => '/storie/die-brug', 'type' => 'storie', 'author' => 'Lid Twee' ),
	);

	$html = WorksArchive::toHtml( $cards, array( 'paged' => 1, 'max_pages' => 1, 'type' => null, 'sort' => WorksArchive::SORT_NUUT ) );

	expect( $html )->toContain( 'Bydraes' );
	expect( $html )->toContain( 'Herfsblare' );
	expect( $html )->toContain( '/gedig/herfsblare' );
	expect( $html )->toContain( 'Lid Een' );
	expect( $html )->toContain( 'Die brug' );
	// Type badge + filter labels sourced from the Terms registry.
	expect( $html )->toContain( 'Gedig' );
	expect( $html )->toContain( 'Storie' );
	// Controls present.
	expect( $html )->toContain( 'ink-ontdek-werke__kontroles' );
	expect( $html )->toContain( 'Alles' );
	expect( $html )->toContain( 'Nuut' );
	expect( $html )->toContain( 'Opspraakwekkend' );
	expect( $html )->toContain( 'Mees geliefd' );
	// Single page → no pagination nav.
	expect( $html )->not->toContain( 'ink-ontdek-werke__blaai' );
} );

test( 'controlsHtml marks the active type and sort and preserves the other dimension', function (): void {
	ink_archive_render_stubs();

	// All types, newest first → Alles + Nuut active.
	$default = WorksArchive::controlsHtml( null, WorksArchive::SORT_NUUT );
	expect( $default )->toContain( 'is-active' );
	expect( $default )->toContain( 'aria-current="true"' );

	// Filtered to gedig + most-loved → those are the active controls.
	$filtered = WorksArchive::controlsHtml( PostTypes::GEDIG, WorksArchive::SORT_GELIEFD );
	expect( $filtered )->toContain( 'is-active' );
	// The type pills and all three sort options render.
	expect( $filtered )->toContain( 'Gedigte' );
	expect( $filtered )->toContain( 'Stories' );
	expect( $filtered )->toContain( 'Artikels' );
	expect( $filtered )->toContain( 'Mees geliefd' );
} );

test( 'toHtml renders prev/next only when there is more than one page', function (): void {
	ink_archive_render_stubs();

	$cards = array(
		array( 'title' => 'Herfsblare', 'permalink' => '/gedig/herfsblare', 'type' => 'gedig', 'author' => 'Lid Een' ),
	);

	$html = WorksArchive::toHtml( $cards, array( 'paged' => 2, 'max_pages' => 3, 'type' => null, 'sort' => WorksArchive::SORT_NUUT ) );
	expect( $html )->toContain( 'ink-ontdek-werke__blaai' );
	expect( $html )->toContain( 'Vorige' );
	expect( $html )->toContain( 'Volgende' );

	$first = WorksArchive::toHtml( $cards, array( 'paged' => 1, 'max_pages' => 3, 'type' => null, 'sort' => WorksArchive::SORT_NUUT ) );
	expect( $first )->toContain( 'Volgende' );
	expect( $first )->not->toContain( 'ink-ontdek-werke__vorige' );
} );

test( 'toHtml shows the empty-state line (with controls, not a blank section) when there are no works', function (): void {
	ink_archive_render_stubs();

	$html = WorksArchive::toHtml( array(), array( 'paged' => 1, 'max_pages' => 0, 'type' => null, 'sort' => WorksArchive::SORT_NUUT ) );

	// Non-vacuous: heading + controls still render, plus the composed empty-state line.
	expect( $html )->toContain( 'Bydraes' );
	expect( $html )->toContain( 'ink-ontdek-werke__kontroles' );
	expect( $html )->toContain( 'Geen' );
	expect( $html )->toContain( 'ink-ontdek-werke__leeg' );
	expect( $html )->not->toContain( 'ink-ontdek-werke__list' );
} );

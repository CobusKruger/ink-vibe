<?php
/**
 * Unit tests for the Biblioteek archive (Story 10.1, FR-52).
 *
 * Target: {@see \Ink\Library\Archive}. The pure `queryArgs()` (newest-first
 * published biblioteek_items + defensive genre filter + keyword search),
 * `featuredArgs()`, and the pure `toHtml()`/`featuredHtml()`/`filterHtml()`/
 * `searchHtml()` (card grid, featured strip, genre pills, search form,
 * pagination, empty-state) are unit-testable without WordPress.
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Library;

use Ink\Library\Archive;
use Ink\Content\PostTypes;
use Ink\Content\Taxonomies;
use Brain\Monkey;
use Brain\Monkey\Functions;

beforeEach( function (): void {
	Monkey\setUp();
} );

afterEach( function (): void {
	Monkey\tearDown();
} );

/**
 * Register the WP stubs the render path needs — escaping + URL builders.
 */
function ink_biblioteek_render_stubs(): void {
	Functions\when( '__' )->returnArg( 1 );
	Functions\when( 'esc_html__' )->returnArg( 1 );
	Functions\when( 'esc_attr__' )->returnArg( 1 );
	Functions\when( 'esc_html' )->returnArg( 1 );
	Functions\when( 'esc_url' )->returnArg( 1 );
	Functions\when( 'esc_attr' )->returnArg( 1 );
	Functions\when( 'remove_query_arg' )->justReturn( '/biblioteek' );
	Functions\when( 'add_query_arg' )->alias(
		static fn ( string $key, $value = '', $url = '' ): string => '/biblioteek?' . $key . '=' . $value
	);
}

// --- queryArgs ---

test( 'queryArgs lists published biblioteek_items newest-first, paged and per-page', function (): void {
	$args = Archive::queryArgs( 2, 12 );

	expect( $args['post_type'] )->toBe( PostTypes::BIBLIOTEEK_ITEM );
	expect( $args['post_status'] )->toBe( 'publish' );
	expect( $args['posts_per_page'] )->toBe( 12 );
	expect( $args['paged'] )->toBe( 2 );
	expect( $args['orderby'] )->toBe( 'date' );
	expect( $args['order'] )->toBe( 'DESC' );
	expect( $args['ignore_sticky_posts'] )->toBeTrue();
	expect( $args )->not->toHaveKey( 'tax_query' );
	expect( $args )->not->toHaveKey( 's' );
} );

test( 'queryArgs clamps a non-positive paged to 1', function (): void {
	expect( Archive::queryArgs( 0, 12 )['paged'] )->toBe( 1 );
	expect( Archive::queryArgs( -7, 12 )['paged'] )->toBe( 1 );
} );

test( 'queryArgs adds a genre tax_query only for a real slug', function (): void {
	$filtered = Archive::queryArgs( 1, 12, 'poesie' );
	expect( $filtered )->toHaveKey( 'tax_query' );
	expect( $filtered['tax_query'][0] )->toBe(
		array(
			'taxonomy' => Taxonomies::GENRE,
			'field'    => 'slug',
			'terms'    => 'poesie',
		)
	);

	expect( Archive::queryArgs( 1, 12, '' ) )->not->toHaveKey( 'tax_query' );
	expect( Archive::queryArgs( 1, 12, null ) )->not->toHaveKey( 'tax_query' );
} );

test( 'queryArgs adds the s keyword only for a non-empty trimmed term', function (): void {
	expect( Archive::queryArgs( 1, 12, null, 'herfs' )['s'] )->toBe( 'herfs' );
	// Whitespace-only degrades to no search.
	expect( Archive::queryArgs( 1, 12, null, '   ' ) )->not->toHaveKey( 's' );
	expect( Archive::queryArgs( 1, 12, null, '' ) )->not->toHaveKey( 's' );
	// A padded term is trimmed.
	expect( Archive::queryArgs( 1, 12, null, '  brug  ' )['s'] )->toBe( 'brug' );
} );

test( 'featuredArgs requests the most-recent published items with no_found_rows', function (): void {
	$args = Archive::featuredArgs( 3 );

	expect( $args['post_type'] )->toBe( PostTypes::BIBLIOTEEK_ITEM );
	expect( $args['post_status'] )->toBe( 'publish' );
	expect( $args['posts_per_page'] )->toBe( 3 );
	expect( $args['orderby'] )->toBe( 'date' );
	expect( $args['no_found_rows'] )->toBeTrue();
	// A non-positive count is floored to at least one.
	expect( Archive::featuredArgs( 0 )['posts_per_page'] )->toBe( 1 );
} );

// --- toHtml / controls ---

test( 'toHtml renders the heading, controls and a card per item, escaping every value', function (): void {
	ink_biblioteek_render_stubs();

	$cards = array(
		array( 'title' => 'Versamelde gedigte', 'permalink' => '/biblioteek/versamelde', 'author' => 'Lid Een' ),
		array( 'title' => 'Die handboek', 'permalink' => '/biblioteek/handboek', 'author' => 'Lid Twee' ),
	);
	$genres = array(
		array( 'slug' => 'poesie', 'name' => 'Poësie' ),
		array( 'slug' => 'prosa', 'name' => 'Prosa' ),
	);

	$html = Archive::toHtml( $cards, array(), $genres, array( 'paged' => 1, 'max_pages' => 1, 'genre' => null, 'search' => '' ) );

	expect( $html )->toContain( 'Biblioteek' );
	expect( $html )->toContain( 'Versamelde gedigte' );
	expect( $html )->toContain( '/biblioteek/versamelde' );
	expect( $html )->toContain( 'Lid Een' );
	expect( $html )->toContain( 'Die handboek' );
	// Genre filter pills + Alles.
	expect( $html )->toContain( 'ink-biblioteek__filter' );
	expect( $html )->toContain( 'Alles' );
	expect( $html )->toContain( 'Poësie' );
	expect( $html )->toContain( 'Prosa' );
	// Search form.
	expect( $html )->toContain( 'ink-biblioteek__soek' );
	// Single page → no pagination nav.
	expect( $html )->not->toContain( 'ink-biblioteek__blaai' );
} );

test( 'featuredHtml renders the Uitgelig strip with a card per featured item, and nothing when empty', function (): void {
	ink_biblioteek_render_stubs();

	$featured = array(
		array( 'title' => 'Nuwe bundel', 'permalink' => '/biblioteek/nuwe', 'author' => 'Lid Drie' ),
	);

	$html = Archive::featuredHtml( $featured );
	expect( $html )->toContain( 'ink-biblioteek__uitgelig' );
	expect( $html )->toContain( 'Uitgelig' );
	expect( $html )->toContain( 'Nuwe bundel' );

	expect( Archive::featuredHtml( array() ) )->toBe( '' );
} );

test( 'filterHtml marks the active genre and renders nothing without terms', function (): void {
	ink_biblioteek_render_stubs();

	$genres = array(
		array( 'slug' => 'poesie', 'name' => 'Poësie' ),
		array( 'slug' => 'prosa', 'name' => 'Prosa' ),
	);

	$active = Archive::filterHtml( $genres, 'poesie' );
	expect( $active )->toContain( 'is-active' );
	expect( $active )->toContain( 'aria-current="true"' );
	expect( $active )->toContain( 'Poësie' );
	expect( $active )->toContain( 'Prosa' );

	// No terms → no filter row at all.
	expect( Archive::filterHtml( array(), null ) )->toBe( '' );
} );

test( 'searchHtml echoes the current term into the input value, escaped', function (): void {
	ink_biblioteek_render_stubs();

	$html = Archive::searchHtml( 'handboek' );
	expect( $html )->toContain( 'ink-biblioteek__soek' );
	expect( $html )->toContain( 'value="handboek"' );
	expect( $html )->toContain( 'Soek' );
} );

test( 'toHtml renders prev/next only when there is more than one page', function (): void {
	ink_biblioteek_render_stubs();

	$cards = array(
		array( 'title' => 'Versamelde gedigte', 'permalink' => '/biblioteek/versamelde', 'author' => 'Lid Een' ),
	);

	$multi = Archive::toHtml( $cards, array(), array(), array( 'paged' => 2, 'max_pages' => 3, 'genre' => null, 'search' => '' ) );
	expect( $multi )->toContain( 'ink-biblioteek__blaai' );
	expect( $multi )->toContain( 'Vorige' );
	expect( $multi )->toContain( 'Volgende' );

	$first = Archive::toHtml( $cards, array(), array(), array( 'paged' => 1, 'max_pages' => 3, 'genre' => null, 'search' => '' ) );
	expect( $first )->toContain( 'Volgende' );
	expect( $first )->not->toContain( 'ink-biblioteek__vorige' );
} );

test( 'toHtml shows the empty-state line (with controls, not a blank section) when nothing matches', function (): void {
	ink_biblioteek_render_stubs();

	$genres = array( array( 'slug' => 'poesie', 'name' => 'Poësie' ) );

	$html = Archive::toHtml( array(), array(), $genres, array( 'paged' => 1, 'max_pages' => 0, 'genre' => 'poesie', 'search' => '' ) );

	// Non-vacuous: heading + filter still render, plus the composed empty-state line.
	expect( $html )->toContain( 'Biblioteek' );
	expect( $html )->toContain( 'ink-biblioteek__filter' );
	expect( $html )->toContain( 'Geen' );
	expect( $html )->toContain( 'ink-biblioteek__leeg' );
	expect( $html )->not->toContain( 'ink-biblioteek__list' );
} );

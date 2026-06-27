<?php
/**
 * Unit tests for the Opleiding hub (Story 11.1, FR-54).
 *
 * Target: {@see \Ink\Training\Hub}. The pure `queryArgs()` (newest-first published
 * opleiding_artikels + defensive keyword search), `featuredArgs()`, and the pure
 * `toHtml()`/`featuredHtml()`/`searchHtml()` (card grid, featured strip, search
 * form, pagination, empty-state) are unit-testable without WordPress.
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Training;

use Ink\Training\Hub;
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
function ink_opleiding_render_stubs(): void {
	Functions\when( '__' )->returnArg( 1 );
	Functions\when( 'esc_html__' )->returnArg( 1 );
	Functions\when( 'esc_attr__' )->returnArg( 1 );
	Functions\when( 'esc_html' )->returnArg( 1 );
	Functions\when( 'esc_url' )->returnArg( 1 );
	Functions\when( 'esc_attr' )->returnArg( 1 );
	Functions\when( 'remove_query_arg' )->justReturn( '/opleiding' );
	Functions\when( 'add_query_arg' )->alias(
		static fn ( string $key, $value = '', $url = '' ): string => '/opleiding?' . $key . '=' . $value
	);
}

// --- queryArgs ---

test( 'queryArgs lists published opleiding_artikels newest-first, paged and per-page', function (): void {
	$args = Hub::queryArgs( 2, 12 );

	expect( $args['post_type'] )->toBe( PostTypes::OPLEIDING_ARTIKEL );
	expect( $args['post_status'] )->toBe( 'publish' );
	expect( $args['posts_per_page'] )->toBe( 12 );
	expect( $args['paged'] )->toBe( 2 );
	expect( $args['orderby'] )->toBe( 'date' );
	expect( $args['order'] )->toBe( 'DESC' );
	expect( $args['ignore_sticky_posts'] )->toBeTrue();
	expect( $args )->not->toHaveKey( 's' );
} );

test( 'queryArgs clamps a non-positive paged to 1', function (): void {
	expect( Hub::queryArgs( 0, 12 )['paged'] )->toBe( 1 );
	expect( Hub::queryArgs( -7, 12 )['paged'] )->toBe( 1 );
} );

test( 'queryArgs adds the s keyword only for a non-empty trimmed term', function (): void {
	expect( Hub::queryArgs( 1, 12, null, 'rym' )['s'] )->toBe( 'rym' );
	// Whitespace-only degrades to no search.
	expect( Hub::queryArgs( 1, 12, null, '   ' ) )->not->toHaveKey( 's' );
	expect( Hub::queryArgs( 1, 12, null, '' ) )->not->toHaveKey( 's' );
	// A padded term is trimmed.
	expect( Hub::queryArgs( 1, 12, null, '  metafoor  ' )['s'] )->toBe( 'metafoor' );
} );

test( 'queryArgs adds a vaardigheid tax_query only for a real facet slug', function (): void {
	$filtered = Hub::queryArgs( 1, 12, 'digkuns' );
	expect( $filtered )->toHaveKey( 'tax_query' );
	expect( $filtered['tax_query'][0] )->toBe(
		array(
			'taxonomy' => Taxonomies::VAARDIGHEID,
			'field'    => 'slug',
			'terms'    => 'digkuns',
		)
	);

	// Garbage/absent facet degrades to the unfiltered listing.
	expect( Hub::queryArgs( 1, 12, '' ) )->not->toHaveKey( 'tax_query' );
	expect( Hub::queryArgs( 1, 12, null ) )->not->toHaveKey( 'tax_query' );
} );

test( 'queryArgs never carries an LMS-style ordering — it is a resource hub', function (): void {
	$args = Hub::queryArgs( 1, 12 );
	// Recency only; no menu_order / progress / lesson sequencing keys.
	expect( $args['orderby'] )->toBe( 'date' );
	expect( $args )->not->toHaveKey( 'meta_key' );
} );

test( 'featuredArgs requests the most-recent published items with no_found_rows', function (): void {
	$args = Hub::featuredArgs( 3 );

	expect( $args['post_type'] )->toBe( PostTypes::OPLEIDING_ARTIKEL );
	expect( $args['post_status'] )->toBe( 'publish' );
	expect( $args['posts_per_page'] )->toBe( 3 );
	expect( $args['orderby'] )->toBe( 'date' );
	expect( $args['order'] )->toBe( 'DESC' ); // most-recent — guards against an ASC regression.
	expect( $args['no_found_rows'] )->toBeTrue();
	// A non-positive count is floored to at least one.
	expect( Hub::featuredArgs( 0 )['posts_per_page'] )->toBe( 1 );
} );

// --- toHtml / controls ---

test( 'toHtml renders the heading, search and a card per item, escaping every value', function (): void {
	ink_opleiding_render_stubs();

	$cards = array(
		array( 'title' => 'Hoe om te begin', 'permalink' => '/opleiding/begin', 'author' => 'Redakteur' ),
		array( 'title' => 'Oor metafore', 'permalink' => '/opleiding/metafore', 'author' => 'Mentor' ),
	);

	$html = Hub::toHtml( $cards, array(), array(), array( 'paged' => 1, 'max_pages' => 1, 'vaardigheid' => null, 'search' => '' ) );

	expect( $html )->toContain( 'Opleiding' );
	expect( $html )->toContain( 'Hoe om te begin' );
	expect( $html )->toContain( '/opleiding/begin' );
	expect( $html )->toContain( 'Redakteur' );
	expect( $html )->toContain( 'Oor metafore' );
	expect( $html )->toContain( 'ink-opleiding__list' );
	// Search form.
	expect( $html )->toContain( 'ink-opleiding__soek' );
	// Single page → no pagination nav.
	expect( $html )->not->toContain( 'ink-opleiding__blaai' );
} );

test( 'featuredHtml renders the Uitgelig strip with a card per featured item, and nothing when empty', function (): void {
	ink_opleiding_render_stubs();

	$featured = array(
		array( 'title' => 'Begin hier', 'permalink' => '/opleiding/begin-hier', 'author' => 'Redakteur' ),
	);

	$html = Hub::featuredHtml( $featured );
	expect( $html )->toContain( 'ink-opleiding__uitgelig' );
	expect( $html )->toContain( 'Uitgelig' );
	expect( $html )->toContain( 'Begin hier' );

	expect( Hub::featuredHtml( array() ) )->toBe( '' );
} );

test( 'searchHtml echoes the current term into the input value, escaped', function (): void {
	ink_opleiding_render_stubs();

	$html = Hub::searchHtml( 'rym' );
	expect( $html )->toContain( 'ink-opleiding__soek' );
	expect( $html )->toContain( 'value="rym"' );
	expect( $html )->toContain( 'Soek' );
	expect( $html )->toContain( 'name="opleiding_soek"' );
	// No active facet → no hidden facet field.
	expect( $html )->not->toContain( 'type="hidden"' );
} );

test( 'searchHtml carries the active vaardigheid facet forward in a hidden field (GET form would otherwise drop it)', function (): void {
	ink_opleiding_render_stubs();

	$html = Hub::searchHtml( 'rym', 'digkuns' );
	expect( $html )->toContain( 'type="hidden"' );
	expect( $html )->toContain( 'name="opleiding_vaardigheid"' );
	expect( $html )->toContain( 'value="digkuns"' );
} );

test( 'filterHtml renders the canonical facets, marks the active one, and renders nothing without terms', function (): void {
	ink_opleiding_render_stubs();

	$facets = array(
		array( 'slug' => 'begin-hier', 'name' => 'Begin hier' ),
		array( 'slug' => 'skryfkuns', 'name' => 'Skryfkuns' ),
		array( 'slug' => 'digkuns', 'name' => 'Digkuns' ),
	);

	$active = Hub::filterHtml( $facets, 'digkuns' );
	expect( $active )->toContain( 'ink-opleiding__filter' );
	expect( $active )->toContain( 'Alles' );
	expect( $active )->toContain( 'Begin hier' );
	expect( $active )->toContain( 'Skryfkuns' );
	expect( $active )->toContain( 'Digkuns' );
	expect( $active )->toContain( 'is-active' );
	expect( $active )->toContain( 'aria-current="true"' );

	// No terms → no filter row at all.
	expect( Hub::filterHtml( array(), null ) )->toBe( '' );
} );

test( 'toHtml integrates the vaardigheid filter row when facets are supplied', function (): void {
	ink_opleiding_render_stubs();

	$cards  = array( array( 'title' => 'Oor rym', 'permalink' => '/opleiding/rym', 'author' => 'Mentor' ) );
	$facets = array( array( 'slug' => 'digkuns', 'name' => 'Digkuns' ) );

	$html = Hub::toHtml( $cards, array(), $facets, array( 'paged' => 1, 'max_pages' => 1, 'vaardigheid' => 'digkuns', 'search' => '' ) );
	expect( $html )->toContain( 'ink-opleiding__filter' );
	expect( $html )->toContain( 'Digkuns' );
	// The active facet is preserved into the search form's hidden field.
	expect( $html )->toContain( 'name="opleiding_vaardigheid"' );
} );

test( 'toHtml renders prev/next only when there is more than one page', function (): void {
	ink_opleiding_render_stubs();

	$cards = array(
		array( 'title' => 'Hoe om te begin', 'permalink' => '/opleiding/begin', 'author' => 'Redakteur' ),
	);

	$multi = Hub::toHtml( $cards, array(), array(), array( 'paged' => 2, 'max_pages' => 3, 'vaardigheid' => null, 'search' => '' ) );
	expect( $multi )->toContain( 'ink-opleiding__blaai' );
	expect( $multi )->toContain( 'Vorige' );
	expect( $multi )->toContain( 'Volgende' );

	$first = Hub::toHtml( $cards, array(), array(), array( 'paged' => 1, 'max_pages' => 3, 'vaardigheid' => null, 'search' => '' ) );
	expect( $first )->toContain( 'Volgende' );
	expect( $first )->not->toContain( 'ink-opleiding__vorige' );
} );

test( 'toHtml shows the empty-state line (with controls, not a blank section) when nothing matches', function (): void {
	ink_opleiding_render_stubs();

	$html = Hub::toHtml( array(), array(), array(), array( 'paged' => 1, 'max_pages' => 0, 'vaardigheid' => null, 'search' => 'niksgevind' ) );

	// Non-vacuous: heading + search still render, plus the composed empty-state line.
	expect( $html )->toContain( 'Opleiding' );
	expect( $html )->toContain( 'ink-opleiding__soek' );
	expect( $html )->toContain( 'Geen' );
	expect( $html )->toContain( 'ink-opleiding__leeg' );
	expect( $html )->not->toContain( 'ink-opleiding__list' );
} );

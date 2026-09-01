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
	// The read-time label's invariant "%d min" abbreviation (see Archive::readTimeLabel()).
	Functions\when( '_n' )->justReturn( '%d min' );
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
	expect( $args['order'] )->toBe( 'DESC' ); // most-recent — guards against an ASC regression.
	expect( $args['no_found_rows'] )->toBeTrue();
	// A non-positive count is floored to at least one.
	expect( Archive::featuredArgs( 0 )['posts_per_page'] )->toBe( 1 );
} );

// --- toHtml / controls ---

test( 'toHtml renders the heading, controls and a card per item, escaping every value', function (): void {
	ink_biblioteek_render_stubs();

	$cards = array(
		array( 'title' => 'Versamelde gedigte', 'permalink' => '/biblioteek/versamelde', 'author' => 'Lid Een', 'genre' => 'Poësie' ),
		array( 'title' => 'Die handboek', 'permalink' => '/biblioteek/handboek', 'author' => 'Lid Twee', 'genre' => 'Prosa' ),
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
	// Per-card genre badge (AC: title -> permalink, genre badge, author).
	expect( $html )->toContain( 'ink-biblioteek__genre' );
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

test( 'a card without a genre omits the badge rather than rendering an empty one', function (): void {
	ink_biblioteek_render_stubs();

	$cards = array(
		array( 'title' => 'Sonder genre', 'permalink' => '/biblioteek/sonder', 'author' => 'Lid Een', 'genre' => '' ),
	);

	$html = Archive::toHtml( $cards, array(), array(), array( 'paged' => 1, 'max_pages' => 1, 'genre' => null, 'search' => '' ) );

	expect( $html )->toContain( 'Sonder genre' );
	expect( $html )->not->toContain( 'ink-biblioteek__genre' );
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
	// No active genre → no hidden genre field.
	expect( $html )->not->toContain( 'type="hidden"' );
} );

test( 'searchHtml carries the active genre forward in a hidden field (GET form would otherwise drop it)', function (): void {
	ink_biblioteek_render_stubs();

	$html = Archive::searchHtml( 'handboek', 'poesie' );
	expect( $html )->toContain( 'type="hidden"' );
	expect( $html )->toContain( 'name="biblioteek_genre"' );
	expect( $html )->toContain( 'value="poesie"' );
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
	// A genre filter is active → a "clear filters" affordance is offered.
	expect( $html )->toContain( 'Vee filters uit' );
} );

test( 'an unfiltered empty library shows the empty-state line without a clear-filters affordance', function (): void {
	ink_biblioteek_render_stubs();

	$html = Archive::toHtml( array(), array(), array(), array( 'paged' => 1, 'max_pages' => 0, 'genre' => null, 'search' => '' ) );

	expect( $html )->toContain( 'Biblioteek' );
	expect( $html )->toContain( 'Geen' );
	expect( $html )->toContain( 'ink-biblioteek__leeg' );
	expect( $html )->not->toContain( 'ink-biblioteek__list' );
	expect( $html )->not->toContain( 'Vee filters uit' );
} );

test( 'toHtml renders the alignwide class on the section — the wrapping pattern alone is not enough (Post-Epic-19 width fix)', function (): void {
	ink_biblioteek_render_stubs();

	$cards = array( array( 'title' => 'Iets', 'permalink' => '/biblioteek/iets', 'author' => 'Lid Een' ) );

	$html = Archive::toHtml( $cards, array(), array(), array( 'paged' => 1, 'max_pages' => 1, 'genre' => null, 'search' => '' ) );
	expect( $html )->toContain( 'class="ink-biblioteek alignwide"' );
} );

test( 'a card renders its excerpt, read-time and cover image, omitting each when absent', function (): void {
	ink_biblioteek_render_stubs();

	$with = array(
		'title'        => 'Met alles',
		'permalink'    => '/biblioteek/met-alles',
		'author'       => 'Lid Een',
		'excerpt'      => 'n Kort uittreksel.',
		'read_minutes' => 4,
		'image'        => '<img src="cover.jpg" alt="Omslag" />',
	);

	$html = Archive::toHtml( array( $with ), array(), array(), array( 'paged' => 1, 'max_pages' => 1, 'genre' => null, 'search' => '' ) );
	expect( $html )->toContain( 'ink-biblioteek__item-uittreksel' );
	expect( $html )->toContain( 'n Kort uittreksel.' );
	expect( $html )->toContain( 'ink-biblioteek__item-leestyd' );
	expect( $html )->toContain( '4 min' );
	expect( $html )->toContain( 'ink-biblioteek__item-beeld' );
	expect( $html )->toContain( '<img src="cover.jpg" alt="Omslag" />' );

	$without = array( 'title' => 'Sonder ekstras', 'permalink' => '/biblioteek/sonder-ekstras', 'author' => 'Lid Twee' );

	$bare = Archive::toHtml( array( $without ), array(), array(), array( 'paged' => 1, 'max_pages' => 1, 'genre' => null, 'search' => '' ) );
	expect( $bare )->not->toContain( 'ink-biblioteek__item-uittreksel' );
	expect( $bare )->not->toContain( 'ink-biblioteek__item-leestyd' );
	expect( $bare )->not->toContain( 'ink-biblioteek__item-beeld' );
} );

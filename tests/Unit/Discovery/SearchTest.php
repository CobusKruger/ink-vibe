<?php
/**
 * Unit tests for the Ontdek search block (Story 8.4, FR-35, AD-7).
 *
 * Target: {@see \Ink\Discovery\Search}. The pure `worksQueryArgs()` /
 * `skrywersQueryArgs()` (the folded-index LIKE shape) and the pure `toHtml()`
 * (form + result groups + empty-state) are unit-testable without WordPress.
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Discovery;

use Ink\Discovery\Search;
use Ink\Discovery\SearchIndex;
use Ink\Content\PostTypes;
use Brain\Monkey;
use Brain\Monkey\Functions;

beforeEach( function (): void {
	Monkey\setUp();
} );

afterEach( function (): void {
	Monkey\tearDown();
} );

function ink_search_render_stubs(): void {
	Functions\when( '__' )->returnArg( 1 );
	Functions\when( 'esc_html__' )->returnArg( 1 );
	Functions\when( 'esc_attr__' )->returnArg( 1 );
	Functions\when( 'esc_html' )->returnArg( 1 );
	Functions\when( 'esc_attr' )->returnArg( 1 );
	Functions\when( 'esc_url' )->returnArg( 1 );
}

test( 'worksQueryArgs matches the folded term LIKE against the works index, published bydraes only', function (): void {
	$args = Search::worksQueryArgs( 'reen', 12 );

	expect( $args['post_type'] )->toBe( array( PostTypes::GEDIG, PostTypes::STORIE, PostTypes::ARTIKEL ) );
	expect( $args['post_status'] )->toBe( 'publish' );
	expect( $args['posts_per_page'] )->toBe( 12 );
	// Bare term — WP_Meta_Query wraps the LIKE value with `%…%` + esc_like itself.
	expect( $args['meta_query'][0] )->toBe(
		array( 'key' => SearchIndex::WORKS_META, 'value' => 'reen', 'compare' => 'LIKE' )
	);
} );

test( 'skrywersQueryArgs matches the folded term LIKE against the skrywer index (ids only)', function (): void {
	$args = Search::skrywersQueryArgs( 'anna', 12 );

	expect( $args['fields'] )->toBe( 'ID' );
	expect( $args['number'] )->toBe( 12 );
	expect( $args['meta_query'][0] )->toBe(
		array( 'key' => SearchIndex::SKRYWER_META, 'value' => 'anna', 'compare' => 'LIKE' )
	);
} );

test( 'toHtml with no query renders the search form only (no results section)', function (): void {
	ink_search_render_stubs();

	$html = Search::toHtml( '', array(), array() );

	expect( $html )->toContain( 'ink-ontdek-soek__form' );
	expect( $html )->toContain( 'Vind stories, gedigte of skrywers...' ); // placeholder
	expect( $html )->not->toContain( 'ink-ontdek-soek__groep' );
	expect( $html )->not->toContain( 'ink-ontdek-soek__leeg' );
} );

test( 'toHtml renders both result groups and keeps the raw query in the input', function (): void {
	ink_search_render_stubs();

	$works    = array( array( 'title' => 'Reën', 'permalink' => '/gedig/reen', 'type' => 'gedig', 'author' => 'Anna' ) );
	$skrywers = array( array( 'name' => 'Anna Visser', 'profile_url' => '/skrywer/anna', 'gradering' => 'Silwer' ) );

	$html = Search::toHtml( 'reën', $works, $skrywers );

	expect( $html )->toContain( 'value="reën"' ); // raw (unfolded) query echoed back
	expect( $html )->toContain( 'Bydraes' );       // works group heading
	expect( $html )->toContain( 'Reën' );
	expect( $html )->toContain( '/gedig/reen' );
	expect( $html )->toContain( 'Skrywers' );      // skrywers group heading
	expect( $html )->toContain( 'Anna Visser' );
	expect( $html )->toContain( 'Silwer' );
	expect( $html )->not->toContain( 'ink-ontdek-soek__leeg' );
} );

test( 'toHtml renders the empty-state line when a query matches nothing', function (): void {
	ink_search_render_stubs();

	$html = Search::toHtml( 'xyzzy', array(), array() );

	expect( $html )->toContain( 'ink-ontdek-soek__form' );          // form still shows
	expect( $html )->toContain( "Probeer 'n ander soekterm" );      // empty-state
	expect( $html )->not->toContain( 'ink-ontdek-soek__groep' );
} );

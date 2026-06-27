<?php
/**
 * Unit tests for auto cross-surfacing (Story 11.4, FR-55).
 *
 * Target: {@see \Ink\Training\RelatedTraining}. The pure `queryArgs()` (the
 * shared-term OR `tax_query`, self-exclusion, bounding) and the pure `toHtml()`
 * (heading + link list, empty string when nothing shares a term) are unit-testable
 * without WordPress. The load-bearing FR-55 invariant — a work that shares no term
 * surfaces NOTHING — is proven by `toHtml([])` and the `queryArgs` clause logic.
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Training;

use Ink\Training\RelatedTraining;
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
 * Register the escaping stubs the render path needs.
 */
function ink_verwant_render_stubs(): void {
	Functions\when( 'esc_html__' )->returnArg( 1 );
	Functions\when( 'esc_html' )->returnArg( 1 );
	Functions\when( 'esc_url' )->returnArg( 1 );
}

// --- queryArgs ---

test( 'queryArgs targets published opleiding_artikel, newest-first, bounded, excluding self', function (): void {
	$args = RelatedTraining::queryArgs( 42, array( 5 ), array(), 3 );

	expect( $args['post_type'] )->toBe( PostTypes::OPLEIDING_ARTIKEL );
	expect( $args['post_status'] )->toBe( 'publish' );
	expect( $args['posts_per_page'] )->toBe( 3 );
	expect( $args['orderby'] )->toBe( 'date' );
	expect( $args['order'] )->toBe( 'DESC' );
	expect( $args['no_found_rows'] )->toBeTrue();
	expect( $args['post__not_in'] )->toBe( array( 42 ) );
} );

test( 'queryArgs builds a single tax_query clause when only one taxonomy has terms (no OR relation)', function (): void {
	$genre_only = RelatedTraining::queryArgs( 0, array( 5, 6 ), array(), 3 );
	expect( $genre_only['tax_query'][0] )->toBe(
		array(
			'taxonomy' => Taxonomies::GENRE,
			'field'    => 'term_id',
			'terms'    => array( 5, 6 ),
		)
	);
	expect( $genre_only['tax_query'] )->not->toHaveKey( 'relation' );

	$vaardigheid_only = RelatedTraining::queryArgs( 0, array(), array( 9 ), 3 );
	expect( $vaardigheid_only['tax_query'][0]['taxonomy'] )->toBe( Taxonomies::VAARDIGHEID );
	expect( $vaardigheid_only['tax_query'] )->not->toHaveKey( 'relation' );
} );

test( 'queryArgs ORs the genre and vaardigheid clauses when both have terms', function (): void {
	$args = RelatedTraining::queryArgs( 0, array( 5 ), array( 9 ), 3 );

	expect( $args['tax_query']['relation'] )->toBe( 'OR' );
	// Both taxonomies present as clauses.
	$taxonomies = array( $args['tax_query'][0]['taxonomy'], $args['tax_query'][1]['taxonomy'] );
	expect( $taxonomies )->toContain( Taxonomies::GENRE );
	expect( $taxonomies )->toContain( Taxonomies::VAARDIGHEID );
} );

test( 'queryArgs with no excluded id omits post__not_in', function (): void {
	$args = RelatedTraining::queryArgs( 0, array( 5 ), array(), 3 );
	expect( $args )->not->toHaveKey( 'post__not_in' );
} );

test( 'queryArgs floors a non-positive limit to at least one', function (): void {
	expect( RelatedTraining::queryArgs( 0, array( 5 ), array(), 0 )['posts_per_page'] )->toBe( 1 );
} );

// --- toHtml (the FR-55 surfaces / surfaces-nothing invariant) ---

test( 'toHtml renders the Verwante leerhulpbronne heading + a link per related item', function (): void {
	ink_verwant_render_stubs();

	$links = array(
		array( 'title' => 'Oor metafore', 'permalink' => '/opleiding/metafore' ),
		array( 'title' => 'Ritme en rym', 'permalink' => '/opleiding/ritme' ),
	);

	$html = RelatedTraining::toHtml( $links );
	expect( $html )->toContain( 'ink-opleiding-verwant' );
	expect( $html )->toContain( 'Verwante leerhulpbronne' );
	expect( $html )->toContain( 'Oor metafore' );
	expect( $html )->toContain( '/opleiding/metafore' );
	expect( $html )->toContain( 'Ritme en rym' );
} );

test( 'toHtml renders NOTHING when no training shares a term (no heading, no shell) — the FR-55 invariant', function (): void {
	ink_verwant_render_stubs();

	expect( RelatedTraining::toHtml( array() ) )->toBe( '' );
} );

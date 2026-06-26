<?php
/**
 * Unit tests for suggested next reads (Story 7.6, FR-31).
 *
 * Target: {@see \Ink\Engagement\SuggestedReads}. The heart of "shared taxonomy
 * terms, no manual linking" is the pure `queryArgs()` (the OR tax_query shape);
 * `toHtml()` renders the card list and returns '' for no suggestions (no empty
 * section). Both are pure.
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Engagement;

use Ink\Engagement\SuggestedReads;
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
	$types = SuggestedReads::readableTypes();

	expect( $types )->toBe( array( PostTypes::GEDIG, PostTypes::STORIE, PostTypes::ARTIKEL ) );
	expect( $types )->not->toContain( PostTypes::SKRYFWERK );
} );

test( 'queryArgs builds an OR tax_query with one clause per non-empty taxonomy', function (): void {
	$args = SuggestedReads::queryArgs(
		42,
		array(
			'genre'          => array( 5, 6 ),
			'vaardigheid'    => array(),       // empty → no clause
			'ster_gradering' => array( 9 ),
		),
		SuggestedReads::readableTypes(),
		4
	);

	expect( $args['post__not_in'] )->toBe( array( 42 ) );           // excludes the current post
	expect( $args['post_status'] )->toBe( 'publish' );
	expect( $args['posts_per_page'] )->toBe( 4 );
	expect( $args['post_type'] )->toBe( array( 'gedig', 'storie', 'artikel' ) );

	expect( $args['tax_query']['relation'] )->toBe( 'OR' );
	// genre + ster_gradering clauses (vaardigheid omitted — it was empty).
	$clauses = array_values( array_filter( $args['tax_query'], 'is_array' ) );
	expect( $clauses )->toHaveCount( 2 );
	expect( $clauses[0] )->toBe( array( 'taxonomy' => 'genre', 'field' => 'term_id', 'terms' => array( 5, 6 ) ) );
	expect( $clauses[1] )->toBe( array( 'taxonomy' => 'ster_gradering', 'field' => 'term_id', 'terms' => array( 9 ) ) );
} );

test( 'toHtml renders the Verwante stukke heading and a card per suggestion', function (): void {
	Functions\when( '__' )->returnArg( 1 );
	Functions\when( 'esc_html__' )->returnArg( 1 );
	Functions\when( 'esc_html' )->returnArg( 1 );
	Functions\when( 'esc_url' )->returnArg( 1 );

	$cards = array(
		array( 'title' => 'Herfsblare', 'permalink' => '/gedig/herfsblare', 'type' => 'gedig', 'author' => 'Lid Een' ),
	);

	$html = SuggestedReads::toHtml( $cards );

	expect( $html )->toContain( 'Verwante stukke' );
	expect( $html )->toContain( 'Herfsblare' );
	expect( $html )->toContain( '/gedig/herfsblare' );
	expect( $html )->toContain( 'Lid Een' );
} );

test( 'toHtml renders nothing when there are no suggestions (no empty section)', function (): void {
	expect( SuggestedReads::toHtml( array() ) )->toBe( '' );
} );

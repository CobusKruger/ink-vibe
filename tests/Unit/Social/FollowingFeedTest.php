<?php
/**
 * Unit tests for the following-feed block (Story 9.3, FR-39).
 *
 * Target: {@see \Ink\Social\FollowingFeed}. The pure `queryArgs()` (constrained
 * to followed authors, with the decisive empty-`author__in` guard) and the pure
 * `toHtml()` (card list + two distinct empty states), plus the logged-out gate.
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Social;

use Ink\Social\FollowingFeed;
use Ink\Content\PostTypes;
use Brain\Monkey;
use Brain\Monkey\Functions;

beforeEach( function (): void {
	Monkey\setUp();
	Functions\when( '__' )->returnArg( 1 );
	Functions\when( 'esc_html' )->returnArg( 1 );
	Functions\when( 'esc_html__' )->returnArg( 1 );
	Functions\when( 'esc_url' )->returnArg( 1 );
} );

afterEach( function (): void {
	Monkey\tearDown();
} );

test( 'queryArgs constrains to the followed authors, readable published types, newest first', function (): void {
	$args = FollowingFeed::queryArgs( array( 7, 42 ), 20 );

	expect( $args['author__in'] )->toBe( array( 7, 42 ) );
	expect( $args['post_status'] )->toBe( 'publish' );
	expect( $args['orderby'] )->toBe( 'date' );
	expect( $args['order'] )->toBe( 'DESC' );
	expect( $args['post_type'] )->toBe( array( PostTypes::GEDIG, PostTypes::STORIE, PostTypes::ARTIKEL ) );
	expect( $args['post_type'] )->not->toContain( PostTypes::SKRYFWERK );
} );

test( 'queryArgs guards the empty author list to match NOTHING, not everything', function (): void {
	$args = FollowingFeed::queryArgs( array(), 20 );

	// The decisive guard: an empty author__in would be ignored by WP_Query and
	// return all posts; [0] matches no real author.
	expect( $args['author__in'] )->toBe( array( 0 ) );
} );

test( 'toHtml renders a card per followed-writer publication', function (): void {
	$cards = array(
		array( 'title' => 'Vlerke', 'permalink' => '/vlerke', 'type' => PostTypes::GEDIG, 'author' => 'Anja' ),
		array( 'title' => 'Brug', 'permalink' => '/brug', 'type' => PostTypes::STORIE, 'author' => 'Pieter' ),
	);

	$html = FollowingFeed::toHtml( $cards, FollowingFeed::STATE_FEED );

	expect( $html )->toContain( 'ink-volg-voer__list' );
	expect( $html )->toContain( 'Vlerke' );
	expect( $html )->toContain( '/brug' );
	expect( $html )->toContain( 'Pieter' );
} );

test( 'toHtml renders the follows-nobody empty state (no list)', function (): void {
	$html = FollowingFeed::toHtml( array(), FollowingFeed::STATE_NO_FOLLOWS );

	expect( $html )->toContain( 'ink-volg-voer__leeg' );
	expect( $html )->toContain( "Volg 'n skrywer" );
	expect( $html )->not->toContain( '<ul' );
} );

test( 'toHtml renders the nothing-published-yet empty state when following but no posts', function (): void {
	$html = FollowingFeed::toHtml( array(), FollowingFeed::STATE_FEED );

	expect( $html )->toContain( 'ink-volg-voer__leeg' );
	expect( $html )->toContain( 'Nuwe werk van hierdie skrywers' );
	expect( $html )->not->toContain( '<ul' );
} );

test( 'render returns nothing for a logged-out visitor', function (): void {
	Functions\when( 'is_user_logged_in' )->justReturn( false );

	expect( FollowingFeed::render() )->toBe( '' );
} );

test( 'render shows the follows-nobody empty state when the member follows nobody (no query)', function (): void {
	Functions\when( 'is_user_logged_in' )->justReturn( true );
	Functions\when( 'get_current_user_id' )->justReturn( 7 );

	$wpdb            = \Mockery::mock();
	$wpdb->prefix    = 'wp_';
	$GLOBALS['wpdb'] = $wpdb;
	$wpdb->shouldReceive( 'prepare' )->andReturn( 'PREPARED' );
	$wpdb->shouldReceive( 'get_col' )->andReturn( array() ); // follows nobody

	$html = FollowingFeed::render();

	expect( $html )->toContain( "Volg 'n skrywer" );
	expect( $html )->not->toContain( '<ul' );

	unset( $GLOBALS['wpdb'] );
} );

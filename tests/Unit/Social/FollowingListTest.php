<?php
/**
 * Unit tests for the "Wie ek volg" following-list block (§5.6 My Profiel rebuild).
 *
 * Target: {@see \Ink\Social\FollowingList}. The pure `toHtml()` (card markup +
 * `data-ink-remove-on-unfollow` + unfollow-button state + the ratified empty
 * state) and the logged-out `render()` gate.
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Social;

use Ink\Social\FollowingList;
use Brain\Monkey;
use Brain\Monkey\Functions;

beforeEach( function (): void {
	Monkey\setUp();
	Functions\when( '__' )->returnArg( 1 );
	Functions\when( 'esc_html' )->returnArg( 1 );
	Functions\when( 'esc_html__' )->returnArg( 1 );
	Functions\when( 'esc_attr' )->returnArg( 1 );
	Functions\when( 'esc_url' )->returnArg( 1 );
	Functions\when( 'home_url' )->alias( fn( $path = '' ) => 'https://ink.test' . $path );
} );

afterEach( function (): void {
	Monkey\tearDown();
} );

test( 'toHtml renders a card grid with data-ink-remove-on-unfollow and a following-state unfollow button', function (): void {
	$html = FollowingList::toHtml(
		array(
			array( 'id' => 7, 'name' => 'Marisa Steyn', 'bio' => 'Skryf oor die see.', 'avatar' => '<img src="avatar-7.jpg" />' ),
			array( 'id' => 42, 'name' => 'Jan Botha', 'bio' => '', 'avatar' => '<img src="avatar-42.jpg" />' ),
		)
	);

	expect( $html )->toContain( 'Skrywers wat jy volg' );
	expect( $html )->toContain( 'Nuwe werk van hierdie skrywers verskyn in jou aktiwiteitsvoer.' );

	expect( $html )->toContain( 'data-ink-remove-on-unfollow' );
	expect( $html )->toContain( 'Marisa Steyn' );
	expect( $html )->toContain( '<em>Skryf oor die see.</em>' );
	expect( $html )->toContain( 'avatar-7.jpg' );

	// Every row is, by definition, a followee — the unfollow button must
	// render in the "following" (pressed) state.
	expect( $html )->toContain( 'data-ink-skrywer="7"' );
	expect( $html )->toContain( 'data-ink-skrywer="42"' );
	expect( substr_count( $html, 'is-following' ) )->toBe( 2 );
	expect( substr_count( $html, 'aria-pressed="true"' ) )->toBe( 2 );
	expect( $html )->toContain( '>Volg tans<' );

	// A blank bio must not render an empty italic paragraph.
	expect( $html )->not->toContain( '<em></em>' );
} );

test( 'toHtml renders the ratified empty state when the member follows nobody', function (): void {
	$html = FollowingList::toHtml( array() );

	expect( $html )->toContain( 'Jy volg nog niemand nie' );
	expect( $html )->toContain( "Volg 'n skrywer om hul nuwe stukke in jou aktiwiteitsvoer te sien." );
	expect( $html )->toContain( '>Ontdek skrywers<' );
	expect( $html )->toContain( 'ink-volg-lys__ontdek' );
	expect( $html )->not->toContain( '<ul' );
	expect( $html )->not->toContain( 'data-ink-remove-on-unfollow' );
} );

test( 'render returns nothing for a logged-out visitor', function (): void {
	Functions\when( 'is_user_logged_in' )->justReturn( false );

	expect( FollowingList::render() )->toBe( '' );
} );

test( 'render resolves followee ids to writer cards for a logged-in member (non-vacuous)', function (): void {
	Functions\when( 'is_user_logged_in' )->justReturn( true );
	Functions\when( 'get_current_user_id' )->justReturn( 1 );

	$wpdb            = \Mockery::mock();
	$wpdb->prefix    = 'wp_';
	$GLOBALS['wpdb'] = $wpdb;
	$wpdb->shouldReceive( 'prepare' )->andReturn( 'PREPARED' );
	$wpdb->shouldReceive( 'get_col' )->andReturn( array( '7' ) );

	Functions\when( 'get_userdata' )->justReturn( new \WP_User( 7 ) );
	Functions\when( 'get_the_author_meta' )->alias(
		function ( $field, $id ) {
			return 'display_name' === $field ? 'Marisa Steyn' : 'Skryf oor die see.';
		}
	);
	Functions\when( 'get_avatar' )->justReturn( '<img src="avatar-7.jpg" />' );

	$html = FollowingList::render();

	expect( $html )->toContain( 'Marisa Steyn' );
	expect( $html )->toContain( 'data-ink-skrywer="7"' );

	unset( $GLOBALS['wpdb'] );
} );

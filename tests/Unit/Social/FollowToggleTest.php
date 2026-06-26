<?php
/**
 * Unit tests for the Volg / Volg tans toggle block (Story 9.2, FR-38).
 *
 * Target: {@see \Ink\Social\FollowToggle}. The pure `toHtml()` (state-correct
 * label + aria) and the `render()` visibility gate (logged-out / own-profile
 * render nothing; a third-party target renders).
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Social;

use Ink\Social\FollowToggle;
use Brain\Monkey;
use Brain\Monkey\Functions;

beforeEach( function (): void {
	Monkey\setUp();
	Functions\when( '__' )->returnArg( 1 ); // Terms::label returns the Afrikaans source.
	Functions\when( 'esc_html' )->returnArg( 1 );
	Functions\when( 'esc_attr' )->returnArg( 1 );
} );

afterEach( function (): void {
	Monkey\tearDown();
} );

test( 'toHtml renders the Volg label and unpressed state when not following', function (): void {
	$html = FollowToggle::toHtml( 42, false );

	expect( $html )->toContain( '>Volg<' );
	expect( $html )->toContain( 'aria-pressed="false"' );
	expect( $html )->toContain( 'data-ink-skrywer="42"' );
	expect( $html )->not->toContain( 'is-following' );
} );

test( 'toHtml renders the Volg tans label and pressed state when following', function (): void {
	$html = FollowToggle::toHtml( 42, true );

	expect( $html )->toContain( '>Volg tans<' );
	expect( $html )->toContain( 'aria-pressed="true"' );
	expect( $html )->toContain( 'is-following' );
} );

test( 'render returns nothing for a logged-out reader', function (): void {
	Functions\when( 'is_user_logged_in' )->justReturn( false );

	expect( FollowToggle::render( array( 'skrywerId' => 42 ) ) )->toBe( '' );
} );

test( 'render returns nothing when the target is the viewer (cannot follow yourself)', function (): void {
	Functions\when( 'is_user_logged_in' )->justReturn( true );
	Functions\when( 'get_current_user_id' )->justReturn( 42 );

	expect( FollowToggle::render( array( 'skrywerId' => 42 ) ) )->toBe( '' );
} );

test( 'render shows the toggle for a third-party target (non-vacuous)', function (): void {
	Functions\when( 'is_user_logged_in' )->justReturn( true );
	Functions\when( 'get_current_user_id' )->justReturn( 7 );

	$wpdb            = \Mockery::mock();
	$wpdb->prefix    = 'wp_';
	$GLOBALS['wpdb'] = $wpdb;
	$wpdb->shouldReceive( 'prepare' )->andReturn( 'PREPARED' );
	$wpdb->shouldReceive( 'get_var' )->andReturn( null ); // viewer does not yet follow

	$html = FollowToggle::render( array( 'skrywerId' => 42 ) );

	expect( $html )->toContain( 'data-ink-skrywer="42"' );
	expect( $html )->toContain( '>Volg<' );

	unset( $GLOBALS['wpdb'] );
} );

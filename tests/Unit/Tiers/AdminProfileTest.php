<?php
/**
 * Unit tests for the staff set/adjust Gradering admin UI (Story 5.2).
 *
 * Target: {@see \Ink\Tiers\AdminProfile} — the capability-gated user-profile
 * section + the sanctioned `$_POST` save path that calls {@see \Ink\Tiers\Api::promote()}.
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Tiers;

use Ink\Kernel\Tier;
use Ink\Tiers\AdminProfile;
use Brain\Monkey;
use Brain\Monkey\Functions;
use Brain\Monkey\Actions;
use Mockery;

beforeEach( function (): void {
	Monkey\setUp();

	$wpdb            = Mockery::mock();
	$wpdb->prefix    = 'wp_';
	$GLOBALS['wpdb'] = $wpdb;

	Functions\when( 'current_time' )->justReturn( '2026-06-26 07:30:00' );
} );

afterEach( function (): void {
	unset( $GLOBALS['wpdb'], $_POST );
	Monkey\tearDown();
} );

/**
 * AC-3: no nonce → nothing happens (no write).
 */
test( 'save does nothing without the nonce', function (): void {
	$_POST = array();
	Functions\expect( 'update_user_meta' )->never();

	( new AdminProfile() )->save( 42 );
	expect( true )->toBeTrue();
} );

/**
 * AC-3: a non-MANAGE_TIERS user cannot write a grade.
 */
test( 'save denies a user without MANAGE_TIERS', function (): void {
	$_POST = array( 'ink_set_gradering_nonce' => 'n', 'ink_gradering' => 'goud' );
	Functions\when( 'wp_unslash' )->returnArg( 1 );
	Functions\when( 'sanitize_text_field' )->returnArg( 1 );
	Functions\when( 'wp_verify_nonce' )->justReturn( true );
	Functions\when( 'current_user_can' )->justReturn( false );

	Functions\expect( 'update_user_meta' )->never();
	Actions\expectDone( 'ink/tier_promoted' )->never();

	( new AdminProfile() )->save( 42 );
	expect( true )->toBeTrue();
} );

/**
 * AC-1/AC-3: a valid POST promotes with the sanitized values and actor = the
 * acting staff user.
 */
test( 'save promotes with sanitized values and the acting staff actor', function (): void {
	$_POST = array(
		'ink_set_gradering_nonce' => 'n',
		'ink_gradering'           => 'goud',
		'ink_gradering_reason'    => 'Wenner Oktober',
		'ink_gradering_uitdaging' => '11',
	);
	Functions\when( 'wp_unslash' )->returnArg( 1 );
	Functions\when( 'sanitize_text_field' )->returnArg( 1 );
	Functions\when( 'wp_verify_nonce' )->justReturn( true );
	Functions\when( 'current_user_can' )->justReturn( true );
	Functions\when( 'absint' )->alias( fn ( $v ): int => abs( (int) $v ) );
	Functions\when( 'get_current_user_id' )->justReturn( 5 );
	Functions\when( 'get_user_meta' )->justReturn( 'silwer' ); // current grade.
	Functions\when( 'update_user_meta' )->justReturn( true );
	$GLOBALS['wpdb']->shouldReceive( 'insert' )->once()->andReturn( 1 );

	Actions\expectDone( 'ink/tier_promoted' )->once()->with( 42, Tier::Silwer, Tier::Goud, 5, 11 );

	( new AdminProfile() )->save( 42 );
	expect( true )->toBeTrue();
} );

/**
 * AC-3: an invalid grade value is rejected (no write).
 */
test( 'save rejects an invalid grade value', function (): void {
	$_POST = array( 'ink_set_gradering_nonce' => 'n', 'ink_gradering' => 'platinum' );
	Functions\when( 'wp_unslash' )->returnArg( 1 );
	Functions\when( 'sanitize_text_field' )->returnArg( 1 );
	Functions\when( 'wp_verify_nonce' )->justReturn( true );
	Functions\when( 'current_user_can' )->justReturn( true );

	Functions\expect( 'update_user_meta' )->never();
	Actions\expectDone( 'ink/tier_promoted' )->never();

	( new AdminProfile() )->save( 42 );
	expect( true )->toBeTrue();
} );

/**
 * AC-3: renderField outputs the section (nonce + grade select with Terms labels)
 * only for a MANAGE_TIERS holder.
 */
test( 'renderField outputs the grade select for a MANAGE_TIERS holder', function (): void {
	Functions\when( 'current_user_can' )->justReturn( true );
	Functions\when( 'get_user_meta' )->justReturn( 'brons' );
	Functions\when( '__' )->returnArg( 1 );
	Functions\when( 'esc_html' )->returnArg( 1 );
	Functions\when( 'esc_attr' )->returnArg( 1 );
	Functions\when( 'esc_html__' )->returnArg( 1 );
	Functions\when( 'selected' )->justReturn( '' );
	Functions\when( 'wp_nonce_field' )->justReturn( '' );
	Functions\when( 'get_posts' )->justReturn( array() );

	$user     = Mockery::mock( \WP_User::class );
	$user->ID = 42;

	ob_start();
	( new AdminProfile() )->renderField( $user );
	$html = (string) ob_get_clean();

	expect( $html )->toContain( 'name="ink_gradering"' );
	expect( $html )->toContain( 'value="brons"' );
	expect( $html )->toContain( 'value="meester"' );
	expect( $html )->toContain( 'Gradering' );
	expect( $html )->toContain( 'name="ink_gradering_reason"' );
	expect( $html )->toContain( 'name="ink_gradering_uitdaging"' );
} );

/**
 * AC-3: a non-holder sees nothing.
 */
test( 'renderField outputs nothing for a non-holder', function (): void {
	Functions\when( 'current_user_can' )->justReturn( false );

	$user     = Mockery::mock( \WP_User::class );
	$user->ID = 42;

	ob_start();
	( new AdminProfile() )->renderField( $user );
	$html = (string) ob_get_clean();

	expect( $html )->toBe( '' );
} );

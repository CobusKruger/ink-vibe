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

test( 'toHtml renders the shared writer card per followee with the Volg tans toggle', function (): void {
	Functions\when( 'number_format_i18n' )->returnArg( 1 );

	$card = static fn ( int $id, string $name, string $bio ): array => array(
		'id'          => $id,
		'name'        => $name,
		'profile_url' => 'https://ink.test/skrywer/' . $id . '/',
		'tier'        => 'brons',
		'bio'         => $bio,
		'avatar_url'  => 'avatar-' . $id . '.jpg',
		'genres'      => array(),
		'stats'       => array( 'works' => 1, 'followers' => 2, 'hartjies' => 3 ),
		'following'   => true,
		'logged_in'   => true,
	);

	$html = FollowingList::toHtml( array( $card( 7, 'Marisa Steyn', 'Skryf oor die see.' ), $card( 42, 'Jan Botha', '' ) ) );

	expect( $html )->toContain( 'Skrywers wat jy volg' );
	expect( $html )->toContain( 'Nuwe werk van hierdie skrywers verskyn in jou aktiwiteitsvoer.' );

	// Shared Ontdek card, not a bespoke one.
	expect( substr_count( $html, 'ink-ontdek-skrywers__item' ) )->toBe( 2 );
	expect( $html )->toContain( 'Marisa Steyn' );
	expect( $html )->toContain( 'Skryf oor die see.' );
	expect( $html )->toContain( 'avatar-7.jpg' );

	// Every row is a followee: the toggle renders in the pressed "Volg tans" state.
	expect( $html )->toContain( 'data-ink-skrywer="7"' );
	expect( $html )->toContain( 'data-ink-skrywer="42"' );
	expect( substr_count( $html, 'aria-pressed="true"' ) )->toBe( 2 );
	expect( $html )->toContain( '>Volg tans<' );
} );

test( 'toHtml renders the ratified empty state when the member follows nobody', function (): void {
	$html = FollowingList::toHtml( array() );

	expect( $html )->toContain( 'Jy volg nog niemand nie' );
	expect( $html )->toContain( "Volg 'n skrywer om hul nuwe stukke in jou aktiwiteitsvoer te sien." );
	expect( $html )->toContain( '>Ontdek skrywers<' );
	expect( $html )->toContain( 'ink-volg-lys__ontdek' );
	expect( $html )->not->toContain( '<ul' );
} );

test( 'render returns nothing for a logged-out visitor', function (): void {
	Functions\when( 'is_user_logged_in' )->justReturn( false );

	expect( FollowingList::render() )->toBe( '' );
} );

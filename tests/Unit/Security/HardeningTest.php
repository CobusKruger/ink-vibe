<?php
/**
 * Unit tests for origin-side security hardening (Story 18.3, §14.16).
 *
 * Target: {@see \Ink\Security\Hardening} — pure decisions (author enumeration,
 * restricted REST routes) + the filter callbacks (REST route removal, generator
 * emptying). Brain-Monkey, no WordPress.
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Security;

use Ink\Security\Hardening;
use Brain\Monkey;
use Brain\Monkey\Functions;

beforeEach( function (): void {
	Monkey\setUp();
} );

afterEach( function (): void {
	Monkey\tearDown();
} );

// --- author enumeration decision ---

test( 'isAuthorEnumeration flags an anonymous ?author= probe on an author archive', function (): void {
	$h = new Hardening();

	expect( $h->isAuthorEnumeration( array( 'author' => '1' ), true, false ) )->toBeTrue();
} );

test( 'isAuthorEnumeration does NOT flag a logged-in user', function (): void {
	$h = new Hardening();

	expect( $h->isAuthorEnumeration( array( 'author' => '1' ), true, true ) )->toBeFalse();
} );

test( 'isAuthorEnumeration does NOT flag a non-author request', function (): void {
	$h = new Hardening();

	expect( $h->isAuthorEnumeration( array( 'author' => '1' ), false, false ) )->toBeFalse();
} );

test( 'isAuthorEnumeration does NOT flag an author archive without the ?author param', function (): void {
	$h = new Hardening();

	expect( $h->isAuthorEnumeration( array(), true, false ) )->toBeFalse();
} );

// --- REST users-endpoint restriction ---

test( 'restrictedRestRoutes lists the user-listing routes', function (): void {
	$routes = ( new Hardening() )->restrictedRestRoutes();

	expect( $routes )->toContain( '/wp/v2/users' );
	expect( $routes )->toContain( '/wp/v2/users/(?P<id>[\d]+)' );
} );

test( 'shouldRestrictUsersEndpoint hides the endpoint only for anonymous callers', function (): void {
	$h = new Hardening();

	expect( $h->shouldRestrictUsersEndpoint( false ) )->toBeTrue();
	expect( $h->shouldRestrictUsersEndpoint( true ) )->toBeFalse();
} );

test( 'filterRestUserRoutes removes the user routes for an anonymous caller', function (): void {
	Functions\when( 'is_user_logged_in' )->justReturn( false );

	$endpoints = array(
		'/wp/v2/users'                  => array( 'callback' ),
		'/wp/v2/users/(?P<id>[\d]+)'    => array( 'callback' ),
		'/wp/v2/posts'                  => array( 'callback' ),
	);

	$out = ( new Hardening() )->filterRestUserRoutes( $endpoints );

	expect( $out )->not->toHaveKey( '/wp/v2/users' );
	expect( $out )->not->toHaveKey( '/wp/v2/users/(?P<id>[\d]+)' );
	// Non-user routes are preserved (non-vacuous).
	expect( $out )->toHaveKey( '/wp/v2/posts' );
} );

test( 'filterRestUserRoutes keeps the user routes for a logged-in editor', function (): void {
	Functions\when( 'is_user_logged_in' )->justReturn( true );

	$endpoints = array( '/wp/v2/users' => array( 'callback' ) );

	$out = ( new Hardening() )->filterRestUserRoutes( $endpoints );

	expect( $out )->toHaveKey( '/wp/v2/users' );
} );

// --- version disclosure ---

test( 'emptyGenerator returns an empty string regardless of input', function (): void {
	$h = new Hardening();

	expect( $h->emptyGenerator( '<meta name="generator" content="WordPress 7.0">' ) )->toBe( '' );
	expect( $h->emptyGenerator( '' ) )->toBe( '' );
} );

// --- register() wires hardening behind opt-out filters (non-vacuous) ---

test( 'register disables xmlrpc when the opt-out filter is on (default)', function (): void {
	$added = array();
	Functions\when( 'apply_filters' )->alias( static fn ( string $hook, $value ) => $value );
	Functions\when( 'remove_action' )->justReturn( true );
	Functions\when( 'add_action' )->justReturn( true );
	Functions\when( 'add_filter' )->alias(
		static function ( string $hook, $cb ) use ( &$added ): bool {
			$added[] = $hook;
			return true;
		}
	);

	( new Hardening() )->register();

	expect( $added )->toContain( 'xmlrpc_enabled' );
} );

test( 'register skips xmlrpc disabling when the opt-out filter returns false', function (): void {
	$added = array();
	// xmlrpc filter off; the others default on.
	Functions\when( 'apply_filters' )->alias(
		static fn ( string $hook, $value ) => 'ink_security_disable_xmlrpc' === $hook ? false : $value
	);
	Functions\when( 'remove_action' )->justReturn( true );
	Functions\when( 'add_action' )->justReturn( true );
	Functions\when( 'add_filter' )->alias(
		static function ( string $hook, $cb ) use ( &$added ): bool {
			$added[] = $hook;
			return true;
		}
	);

	( new Hardening() )->register();

	expect( $added )->not->toContain( 'xmlrpc_enabled' );
	// the other hardenings still wired (non-vacuous: the_generator is on).
	expect( $added )->toContain( 'the_generator' );
} );

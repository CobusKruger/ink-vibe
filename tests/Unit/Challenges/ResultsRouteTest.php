<?php
/**
 * Unit tests for the per-round results URL (WP1, N1, D5).
 *
 * Target: {@see \Ink\Challenges\ResultsRoute}. The real rewrite, template and redirect
 * behaviour needs a running site; these pin the decisions.
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Challenges;

use Ink\Challenges\ResultsRoute;
use Ink\Challenges\WinnersPost;
use Brain\Monkey;
use Brain\Monkey\Functions;

beforeEach( function (): void {
	Monkey\setUp();
} );

afterEach( function (): void {
	Monkey\tearDown();
	unset( $GLOBALS['wp_query'] );
} );

/**
 * Stub the request: the endpoint's query var (null = absent), and which singular it is.
 *
 * @param string|null $endpoint The query var's value; null when absent.
 * @param string      $singular The queried post type; '' for not singular.
 */
function ink_results_request( ?string $endpoint, string $singular ): void {
	Functions\when( 'get_query_var' )->alias( fn ( string $var, $default = '' ) => 'uitslae' === $var && null !== $endpoint ? $endpoint : $default );
	Functions\when( 'is_singular' )->alias( fn ( $type = '' ) => '' !== $singular && ( '' === $type || $type === $singular ) );
	Functions\when( 'get_queried_object_id' )->justReturn( 77 );
	Functions\when( 'is_404' )->justReturn( false );
}

test( 'register adds the endpoint on the permalink mask and wires the handlers', function (): void {
	defined( 'EP_PERMALINK' ) || define( 'EP_PERMALINK', 1 );

	Functions\expect( 'add_rewrite_endpoint' )->once()->with( 'uitslae', EP_PERMALINK );

	( new ResultsRoute() )->register();

	expect( has_action( 'init', array( ResultsRoute::class, 'flushIfStale' ) ) )->toBe( 99 );
	expect( has_filter( 'single_template_hierarchy', array( ResultsRoute::class, 'templateHierarchy' ) ) )->not->toBeFalse();
	expect( has_filter( 'do_redirect_guess_404_permalink', array( ResultsRoute::class, 'guessAllowed' ) ) )->not->toBeFalse();
	// Ahead of redirect_canonical at 10, which is what acts on core's guess.
	expect( has_action( 'template_redirect', array( ResultsRoute::class, 'guard' ) ) )->toBe( 1 );
	expect( has_action( 'template_redirect', array( ResultsRoute::class, 'redirectResultPost' ) ) )->toBe( 2 );
} );

test( 'the flush runs once per version', function (): void {
	Functions\when( 'get_option' )->justReturn( '' );
	Functions\expect( 'flush_rewrite_rules' )->once()->with( false );
	Functions\expect( 'update_option' )->once()->with( ResultsRoute::VERSION_OPTION, ResultsRoute::VERSION );

	ResultsRoute::flushIfStale();
} );

test( 'an applied version does not flush', function (): void {
	Functions\when( 'get_option' )->justReturn( ResultsRoute::VERSION );
	Functions\expect( 'flush_rewrite_rules' )->never();

	ResultsRoute::flushIfStale();
} );

test( 'the bare /uitslae/ gives an empty query var, and that counts as present', function (): void {
	ink_results_request( '', 'uitdaging' );
	expect( ResultsRoute::endpointPresent() )->toBeTrue();
	expect( ResultsRoute::isResultsView() )->toBeTrue();

	ink_results_request( null, 'uitdaging' );
	expect( ResultsRoute::endpointPresent() )->toBeFalse();
	expect( ResultsRoute::isResultsView() )->toBeFalse();
} );

test( 'the results template goes first on a results view only', function (): void {
	ink_results_request( '', 'uitdaging' );
	expect( ResultsRoute::templateHierarchy( array( 'single-uitdaging.php', 'single.php' ) ) )
		->toBe( array( 'single-uitdaging-uitslae.php', 'single-uitdaging.php', 'single.php' ) );

	ink_results_request( null, 'uitdaging' );
	expect( ResultsRoute::templateHierarchy( array( 'single-uitdaging.php' ) ) )->toBe( array( 'single-uitdaging.php' ) );
} );

test( 'core 404 guess is off on the endpoint and untouched elsewhere', function (): void {
	ink_results_request( '', '' );
	expect( ResultsRoute::guessAllowed( true ) )->toBeFalse();

	ink_results_request( null, '' );
	expect( ResultsRoute::guessAllowed( true ) )->toBeTrue();
	expect( ResultsRoute::guessAllowed( false ) )->toBeFalse();
} );

/**
 * A wp_query double that records set_404().
 */
function ink_results_wp_query(): object {
	$GLOBALS['wp_query'] = new class() {
		public int $set404 = 0;

		public function set_404(): void {
			++$this->set404;
		}
	};

	return $GLOBALS['wp_query'];
}

test( 'the endpoint on another post type is a 404: the mask also matches gedig and posts', function (): void {
	ink_results_request( '', 'gedig' );
	$query = ink_results_wp_query();

	Functions\expect( 'status_header' )->once()->with( 404 );
	Functions\expect( 'nocache_headers' )->once();

	ResultsRoute::guard();

	expect( $query->set404 )->toBe( 1 );
} );

test( 'a round with no results is a 404', function (): void {
	ink_results_request( '', 'uitdaging' );
	$query = ink_results_wp_query();

	Functions\when( 'get_posts' )->justReturn( array() );
	\WP_Query::$ink_test_posts = array();

	Functions\expect( 'status_header' )->once()->with( 404 );
	Functions\expect( 'nocache_headers' )->once();

	ResultsRoute::guard();

	expect( $query->set404 )->toBe( 1 );
} );

test( 'a round with a linked result post passes the guard', function (): void {
	ink_results_request( '', 'uitdaging' );
	$query = ink_results_wp_query();

	Functions\when( 'get_posts' )->justReturn( array( 68532 ) );
	Functions\expect( 'status_header' )->never();

	ResultsRoute::guard();

	expect( $query->set404 )->toBe( 0 );
} );

test( 'the guard stays out of a request without the endpoint', function (): void {
	ink_results_request( null, 'gedig' );
	$query = ink_results_wp_query();

	Functions\expect( 'status_header' )->never();

	ResultsRoute::guard();

	expect( $query->set404 )->toBe( 0 );
} );

test( 'the URL composes on the round, with exactly one slash either way', function (): void {
	expect( ResultsRoute::compose( 'https://x/uitdaging/oktober-2026/' ) )->toBe( 'https://x/uitdaging/oktober-2026/uitslae/' );
	expect( ResultsRoute::compose( 'https://x/uitdaging/oktober-2026' ) )->toBe( 'https://x/uitdaging/oktober-2026/uitslae/' );
} );

test( 'each old result post redirects to its own round', function (): void {
	Functions\when( 'get_post_meta' )->alias(
		fn ( int $id, string $key ) => WinnersPost::UITDAGING_META === $key ? array( 100 => '10', 101 => '20' )[ $id ] ?? '' : ''
	);
	Functions\when( 'get_post_type' )->justReturn( 'uitdaging' );
	Functions\when( 'get_post_status' )->justReturn( 'publish' );
	Functions\when( 'get_permalink' )->alias( fn ( int $id ): string => "https://x/uitdaging/r{$id}/" );

	expect( ResultsRoute::redirectTarget( 100 ) )->toBe( 'https://x/uitdaging/r10/uitslae/' );
	expect( ResultsRoute::redirectTarget( 101 ) )->toBe( 'https://x/uitdaging/r20/uitslae/' );
} );

test( 'a post with no link, or a link to anything but a published round, does not redirect', function (): void {
	Functions\when( 'get_post_meta' )->justReturn( '' );
	expect( ResultsRoute::redirectTarget( 100 ) )->toBe( '' );

	Functions\when( 'get_post_meta' )->justReturn( '10' );
	Functions\when( 'get_post_type' )->justReturn( 'post' );
	Functions\when( 'get_post_status' )->justReturn( 'publish' );
	expect( ResultsRoute::redirectTarget( 100 ) )->toBe( '' );

	Functions\when( 'get_post_type' )->justReturn( 'uitdaging' );
	Functions\when( 'get_post_status' )->justReturn( 'draft' );
	expect( ResultsRoute::redirectTarget( 100 ) )->toBe( '' );
} );

test( 'the 301 fires only for a plain view of a post', function (): void {
	Functions\when( 'is_preview' )->justReturn( true );
	Functions\when( 'is_feed' )->justReturn( false );
	ink_results_request( null, 'post' );
	Functions\expect( 'wp_safe_redirect' )->never();

	ResultsRoute::redirectResultPost(); // A preview.

	Functions\when( 'is_preview' )->justReturn( false );
	ink_results_request( null, 'gedig' );
	ResultsRoute::redirectResultPost(); // Not a post.

	ink_results_request( '', 'post' );
	ResultsRoute::redirectResultPost(); // /x/uitslae/ on a post: the guard's 404, not a redirect.

	expect( true )->toBeTrue();
} );

<?php
/**
 * Unit tests for the system-Pages registry (first migration rehearsal fix).
 *
 * Target: {@see \Ink\Kernel\SystemPages}. The theme's page templates only render
 * when a Page exists at their slug; these tests pin the registry, the idempotent
 * ensure step and the guards, and assert that no theme template ships without a
 * Page (or a documented reason).
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Kernel;

use Ink\Kernel\SystemPages;
use Brain\Monkey;
use Brain\Monkey\Functions;

beforeEach( function (): void {
	Monkey\setUp();
} );

afterEach( function (): void {
	Monkey\tearDown();
} );

function ink_test_page( int $id, string $status = 'publish', string $content = '', string $slug = 'x' ): \WP_Post {
	$p               = new \WP_Post();
	$p->ID           = $id;
	$p->post_status  = $status;
	$p->post_content = $content;
	$p->post_name    = $slug;
	$p->post_type    = 'page';

	return $p;
}

test( 'every page template in the theme has a system Page or a documented exclusion', function (): void {
	$templates = glob( dirname( __DIR__, 3 ) . '/wp-content/themes/ink-foundation/templates/page-*.html' );
	expect( $templates )->not->toBeEmpty();

	$known = array_merge( array_keys( SystemPages::PAGES ), SystemPages::MANAGED_ELSEWHERE, array_keys( SystemPages::EXCLUDED ) );

	foreach ( $templates as $file ) {
		$slug = substr( basename( $file, '.html' ), strlen( 'page-' ) );
		expect( $known )->toContain( $slug );
	}
} );

test( 'the registry has no slug that is both a system Page and excluded', function (): void {
	expect( array_intersect( array_keys( SystemPages::PAGES ), array_keys( SystemPages::EXCLUDED ), SystemPages::MANAGED_ELSEWHERE ) )->toBe( array() );
} );

test( 'ensure creates a missing Page empty, published and flagged', function (): void {
	Functions\when( 'get_page_by_path' )->alias( fn ( string $slug ) => 'skryf' === $slug ? null : ink_test_page( 7, 'publish', '', $slug ) );
	Functions\when( 'get_post_meta' )->justReturn( '1' );
	Functions\when( 'is_wp_error' )->justReturn( false );

	$inserted = array();
	Functions\when( 'wp_insert_post' )->alias(
		function ( array $a ) use ( &$inserted ): int {
			$inserted[] = $a;
			return 99;
		}
	);
	Functions\expect( 'wp_update_post' )->never();

	$r = SystemPages::ensure();

	expect( $r['created'] )->toBe( array( 'skryf' ) )
		->and( $r['failed'] )->toBe( array() )
		->and( $inserted )->toHaveCount( 1 )
		->and( $inserted[0]['post_name'] )->toBe( 'skryf' )
		->and( $inserted[0]['post_type'] )->toBe( 'page' )
		->and( $inserted[0]['post_status'] )->toBe( 'publish' )
		->and( $inserted[0]['post_content'] )->toBe( '' )
		->and( $inserted[0]['meta_input'] )->toBe( array( SystemPages::META => 1 ) );
} );

test( 'ensure is a no-op when every Page exists and is flagged', function (): void {
	Functions\when( 'get_page_by_path' )->alias( fn ( string $slug ) => ink_test_page( 7, 'publish', '', $slug ) );
	Functions\when( 'get_post_meta' )->justReturn( '1' );
	Functions\expect( 'wp_insert_post' )->never();
	Functions\expect( 'wp_update_post' )->never();
	Functions\expect( 'update_post_meta' )->never();

	$r = SystemPages::ensure();

	expect( $r['created'] )->toBe( array() )
		->and( $r['repaired'] )->toBe( array() )
		->and( $r['existing'] )->toHaveCount( count( SystemPages::PAGES ) );
} );

test( 'ensure empties a legacy UPME login shortcode and republishes a trashed Page', function (): void {
	Functions\when( 'get_page_by_path' )->alias(
		function ( string $slug ) {
			return match ( $slug ) {
				'teken-in' => ink_test_page( 510, 'publish', '[upme_login]', $slug ),
				'skryf'    => ink_test_page( 11, 'trash', '', $slug ),
				default    => ink_test_page( 7, 'publish', '', $slug ),
			};
		}
	);
	Functions\when( 'get_post_meta' )->justReturn( '1' );

	$updates = array();
	Functions\when( 'wp_update_post' )->alias(
		function ( array $a ) use ( &$updates ): int {
			$updates[ $a['ID'] ] = $a;
			return $a['ID'];
		}
	);

	$r = SystemPages::ensure();

	expect( $r['repaired'] )->toBe( array( 'teken-in', 'skryf' ) )
		->and( $updates[510] )->toBe( array( 'ID' => 510, 'post_content' => '' ) )
		->and( $updates[11] )->toBe( array( 'ID' => 11, 'post_status' => 'publish' ) );
} );

test( 'ensure leaves real content on an existing Page alone', function (): void {
	Functions\when( 'get_page_by_path' )->alias( fn ( string $slug ) => ink_test_page( 7, 'publish', '<p>Hallo</p>', $slug ) );
	Functions\when( 'get_post_meta' )->justReturn( '1' );
	Functions\expect( 'wp_update_post' )->never();

	expect( SystemPages::ensure()['repaired'] )->toBe( array() );
} );

test( 'ensure reports a failed insert', function (): void {
	Functions\when( 'get_page_by_path' )->justReturn( null );
	Functions\when( 'is_wp_error' )->justReturn( true );
	Functions\when( 'wp_insert_post' )->justReturn( 0 );

	expect( SystemPages::ensure()['failed'] )->toHaveCount( count( SystemPages::PAGES ) );
} );

test( 'ensureIfStale skips once the registry version is applied', function (): void {
	Functions\when( 'get_option' )->justReturn( SystemPages::version() );
	Functions\expect( 'get_page_by_path' )->never();

	SystemPages::ensureIfStale();
} );

test( 'ensureIfStale records the version only when nothing failed', function (): void {
	Functions\when( 'get_option' )->justReturn( 'old' );
	Functions\when( 'get_page_by_path' )->alias( fn ( string $slug ) => ink_test_page( 7, 'publish', '', $slug ) );
	Functions\when( 'get_post_meta' )->justReturn( '1' );
	Functions\expect( 'update_option' )->once()->with( SystemPages::VERSION_OPTION, SystemPages::version() );

	SystemPages::ensureIfStale();
} );

test( 'a system Page cannot be deleted, other posts can', function (): void {
	Functions\when( 'get_post_meta' )->alias( fn ( int $id ) => 5 === $id ? '1' : '' );

	expect( SystemPages::protectDeletion( array( 'delete_pages' ), 'delete_post', 1, array( 5 ) ) )->toBe( array( 'do_not_allow' ) )
		->and( SystemPages::protectDeletion( array( 'delete_pages' ), 'delete_post', 1, array( 6 ) ) )->toBe( array( 'delete_pages' ) )
		->and( SystemPages::protectDeletion( array( 'edit_pages' ), 'edit_post', 1, array( 5 ) ) )->toBe( array( 'edit_pages' ) );
} );

test( 'a system Page keeps its slug on edit', function (): void {
	Functions\when( 'get_post_meta' )->justReturn( '1' );
	Functions\when( 'get_post' )->justReturn( ink_test_page( 5, 'publish', '', 'skryf' ) );

	$out = SystemPages::keepSlug( array( 'post_type' => 'page', 'post_name' => 'iets-anders' ), array( 'ID' => 5 ) );

	expect( $out['post_name'] )->toBe( 'skryf' );
} );

test( 'keepSlug ignores new posts and unflagged Pages', function (): void {
	Functions\when( 'get_post_meta' )->justReturn( '' );
	$in = array( 'post_type' => 'page', 'post_name' => 'nuut' );

	expect( SystemPages::keepSlug( $in, array( 'ID' => 0 ) ) )->toBe( $in )
		->and( SystemPages::keepSlug( $in, array( 'ID' => 9 ) ) )->toBe( $in );
} );

<?php
/**
 * Unit tests for the Bydraes-tab unified per-post render (My Profiel rebuild §5.4/§3).
 *
 * Target: {@see \Ink\Social\BydraesSurface}. The pure `toHtml()` (per-row
 * type/status/date/read-count/pin-toggle/edit-view markup, the "Gepubliseer"-
 * always badge, the flagged empty state), `rows()`'s query shape + per-row data
 * assembly (read count + pin state + edit/view links, fixture-excluded), and
 * the logged-out `render()` gate.
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Social;

use Ink\Social\BydraesSurface;
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
	Functions\when( 'number_format_i18n' )->alias( static fn ( $n ): string => (string) $n );
	Functions\when( '_n' )->alias( static fn ( string $s, string $p, int $n ): string => 1 === $n ? $s : $p );
} );

afterEach( function (): void {
	Monkey\tearDown();
	\WP_Query::$ink_test_posts = array();
} );

// --- toHtml (pure) ---

test( 'toHtml renders the H2 + a matching "Nuwe bydrae" button', function (): void {
	$html = BydraesSurface::toHtml( array() );

	expect( $html )->toContain( 'Jou bydraes' );
	expect( $html )->toContain( 'ink-bydraes__nuwe' );
	expect( $html )->toContain( 'Nuwe bydrae' );
	expect( $html )->toContain( 'https://ink.test/skryf/' );
} );

test( 'toHtml renders one unified row per own work: type, always "Gepubliseer", date, read count, pin toggle, view + edit actions', function (): void {
	$html = BydraesSurface::toHtml(
		array(
			array(
				'id'         => 7,
				'title'      => 'Vlerke',
				'type'       => 'gedig',
				'type_label' => 'Gedig',
				'date'       => '3 September 2026',
				'read_count' => 12,
				'read_label' => '12 lesers',
				'is_pinned'  => true,
				'edit_url'   => 'https://ink.test/wp-admin/post.php?post=7&action=edit',
				'view_url'   => 'https://ink.test/vlerke/',
			),
		)
	);

	expect( $html )->toContain( 'ink-bydraes__lys' );
	expect( $html )->toContain( 'Vlerke' );
	expect( $html )->toContain( 'Gedig' );
	expect( $html )->toContain( 'Gepubliseer' );
	expect( $html )->not->toContain( 'Konsep' );
	expect( $html )->toContain( '3 September 2026' );
	expect( $html )->toContain( '12 lesers' );
	expect( $html )->toContain( 'https://ink.test/vlerke/' );
	expect( $html )->toContain( 'https://ink.test/wp-admin/post.php?post=7&action=edit' );
	expect( $html )->toContain( '>Sien<' );
	expect( $html )->toContain( '>Wysig<' );

	// The pin toggle is PinnedWorksManager::toggleHtml() verbatim — same markup
	// vasgespel.js's REST wiring already targets.
	expect( $html )->toContain( 'ink-vasgespel__knoppie is-pinned' );
	expect( $html )->toContain( 'data-ink-post="7"' );
	expect( $html )->toContain( 'aria-pressed="true"' );
} );

test( 'toHtml renders the unpinned toggle state correctly', function (): void {
	$html = BydraesSurface::toHtml(
		array(
			array(
				'id'         => 42,
				'title'      => 'Brug',
				'type'       => 'storie',
				'type_label' => 'Storie',
				'date'       => '',
				'read_count' => 0,
				'read_label' => '0 lesers',
				'is_pinned'  => false,
				'edit_url'   => '',
				'view_url'   => 'https://ink.test/brug/',
			),
		)
	);

	expect( $html )->toContain( 'data-ink-post="42"' );
	expect( $html )->toContain( 'aria-pressed="false"' );

	// No edit capability (edit_url empty) — graceful degrade, no broken/empty href.
	expect( $html )->not->toContain( '>Wysig<' );
} );

test( 'toHtml renders the flagged copy-debt placeholder when the writer has no published works, never a bare list', function (): void {
	$html = BydraesSurface::toHtml( array() );

	expect( $html )->toContain( 'ink-bydraes__leeg' );
	expect( $html )->toContain( 'NEEDS HUMAN AFRIKAANS' );
	expect( $html )->not->toContain( '<ul' );
} );

// --- render() gate ---

test( 'render returns nothing for a logged-out visitor', function (): void {
	Functions\when( 'is_user_logged_in' )->justReturn( false );

	expect( BydraesSurface::render() )->toBe( '' );
} );

// --- rows() — query shape + per-row data assembly ---

/**
 * Stage a fixture-excluded own-author WP_Query result + the WP function reads
 * `rows()` makes per post, so the query→row-assembly path runs end-to-end
 * without a real DB (mirrors `Sponsors\ApiTest`'s `ink_sponsors_stage_query()`).
 *
 * @param list<array{id:int, title:string, type:string}> $works The staged posts.
 */
function ink_bydraes_stage_query( array $works ): void {
	$posts = array();

	foreach ( $works as $work ) {
		$post            = new \WP_Post();
		$post->ID        = $work['id'];
		$post->post_type = $work['type'];
		$posts[]         = $post;
	}

	\WP_Query::$ink_test_posts = $posts;

	Functions\when( 'get_the_title' )->alias(
		static function ( $post ) use ( $works ) {
			$id = is_object( $post ) ? $post->ID : (int) $post;
			foreach ( $works as $work ) {
				if ( $work['id'] === $id ) {
					return $work['title'];
				}
			}
			return '';
		}
	);
	Functions\when( 'get_post_type' )->alias( static fn ( $post ) => $post->post_type );
	Functions\when( 'get_post_time' )->justReturn( 1757116800 );
	Functions\when( 'date_i18n' )->alias( static fn ( $format, $ts ) => 'DATE:' . $ts );
	Functions\when( 'get_option' )->justReturn( 'j F Y' );
	Functions\when( 'get_edit_post_link' )->alias( static fn ( $id ) => 'https://ink.test/edit/' . $id );
	Functions\when( 'get_permalink' )->alias( static fn ( $id ) => 'https://ink.test/werk/' . $id );
}

test( 'rows runs the own-author published-and-fixture-excluded query and attaches read count + pin state + edit/view links', function (): void {
	ink_bydraes_stage_query(
		array(
			array( 'id' => 7, 'title' => 'Vlerke', 'type' => 'gedig' ),
			array( 'id' => 8, 'title' => 'QA FIXTURE — moet nie wys nie', 'type' => 'storie' ),
		)
	);

	Functions\when( 'get_post_meta' )->alias(
		static fn ( int $id, string $key, bool $single = true ) => 7 === $id ? 5 : 0
	);
	Functions\when( 'get_user_meta' )->justReturn( array( 7 ) ); // post 7 is pinned

	$rows = BydraesSurface::rows( 1 );

	expect( \WP_Query::$ink_test_last_args['post_type'] )->toBe( array( 'gedig', 'storie', 'artikel' ) );
	expect( \WP_Query::$ink_test_last_args['post_status'] )->toBe( 'publish' );
	expect( \WP_Query::$ink_test_last_args['author'] )->toBe( 1 );

	// The fixture-titled post is excluded entirely.
	expect( $rows )->toHaveCount( 1 );

	expect( $rows[0]['id'] )->toBe( 7 );
	expect( $rows[0]['title'] )->toBe( 'Vlerke' );
	expect( $rows[0]['type'] )->toBe( 'gedig' );
	expect( $rows[0]['read_count'] )->toBe( 5 );
	expect( $rows[0]['read_label'] )->toBe( '5 lesers' );
	expect( $rows[0]['is_pinned'] )->toBeTrue();
	expect( $rows[0]['edit_url'] )->toBe( 'https://ink.test/edit/7' );
	expect( $rows[0]['view_url'] )->toBe( 'https://ink.test/werk/7' );
} );

test( 'rows returns an empty list for a non-positive user id without querying', function (): void {
	expect( BydraesSurface::rows( 0 ) )->toBe( array() );
} );

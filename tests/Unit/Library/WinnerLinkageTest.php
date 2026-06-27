<?php
/**
 * Unit tests for the Biblioteek winner→challenge linkage (Story 10.5, FR-53).
 *
 * Target: {@see \Ink\Library\WinnerLinkage}. The pure `toHtml()` (label + link
 * markup, empty when no links) and `linksFor()` (resolve `uitdagingsrondte` round
 * terms → published `uitdaging` links, fail-safe skips) are unit-testable with WP
 * term/post functions stubbed via Brain Monkey.
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Library;

use Ink\Library\WinnerLinkage;
use Brain\Monkey;
use Brain\Monkey\Functions;

beforeEach( function (): void {
	Monkey\setUp();
} );

afterEach( function (): void {
	Monkey\tearDown();
} );

// --- toHtml ---

test( 'toHtml renders nothing when there are no producing-challenge links', function (): void {
	expect( WinnerLinkage::toHtml( array() ) )->toBe( '' );
} );

test( 'toHtml renders the label and a link per challenge, escaping every value', function (): void {
	Functions\when( '__' )->returnArg( 1 );
	Functions\when( 'esc_html__' )->returnArg( 1 );
	Functions\when( 'esc_html' )->returnArg( 1 );
	Functions\when( 'esc_url' )->returnArg( 1 );

	$html = WinnerLinkage::toHtml(
		array(
			array( 'title' => 'Herfsuitdaging 2025', 'permalink' => '/uitdaging/herfs-2025' ),
			array( 'title' => 'Wenners-rondte', 'permalink' => '/uitdaging/wenners' ),
		)
	);

	expect( $html )->toContain( 'ink-biblioteek-uitdaging' );
	expect( $html )->toContain( 'Uit die uitdaging:' );
	expect( $html )->toContain( 'Herfsuitdaging 2025' );
	expect( $html )->toContain( '/uitdaging/herfs-2025' );
	expect( $html )->toContain( 'Wenners-rondte' );
	expect( $html )->toContain( '/uitdaging/wenners' );
} );

// --- linksFor ---

test( 'linksFor returns nothing for a non-positive id or when the post has no round terms', function (): void {
	expect( WinnerLinkage::linksFor( 0 ) )->toBe( array() );

	Functions\when( 'get_the_terms' )->justReturn( false ); // WP returns false when none
	expect( WinnerLinkage::linksFor( 5 ) )->toBe( array() );
} );

test( 'linksFor resolves a round term to its published uitdaging link', function (): void {
	Functions\when( 'get_the_terms' )->justReturn( array( new \WP_Term( 'uitdaging-7' ) ) );
	Functions\when( 'get_post_type' )->justReturn( 'uitdaging' );
	Functions\when( 'get_post_status' )->justReturn( 'publish' );
	Functions\when( 'get_the_title' )->justReturn( 'Herfsuitdaging' );
	Functions\when( 'get_permalink' )->justReturn( '/uitdaging/herfs' );

	expect( WinnerLinkage::linksFor( 5 ) )->toBe(
		array( array( 'title' => 'Herfsuitdaging', 'permalink' => '/uitdaging/herfs' ) )
	);
} );

test( 'linksFor skips an unparseable slug, a non-uitdaging post, and an unpublished uitdaging', function (): void {
	// Unparseable round-term slug → no link (get_post_* never consulted).
	Functions\when( 'get_the_terms' )->justReturn( array( new \WP_Term( 'iets-anders' ) ) );
	expect( WinnerLinkage::linksFor( 5 ) )->toBe( array() );

	// Parseable slug but the post is not a uitdaging.
	Functions\when( 'get_the_terms' )->justReturn( array( new \WP_Term( 'uitdaging-7' ) ) );
	Functions\when( 'get_post_type' )->justReturn( 'gedig' );
	Functions\when( 'get_post_status' )->justReturn( 'publish' );
	expect( WinnerLinkage::linksFor( 5 ) )->toBe( array() );

	// Parseable slug, a uitdaging, but not published (draft/deleted).
	Functions\when( 'get_post_type' )->justReturn( 'uitdaging' );
	Functions\when( 'get_post_status' )->justReturn( 'draft' );
	expect( WinnerLinkage::linksFor( 5 ) )->toBe( array() );
} );

test( 'linksFor de-dupes repeated round terms for the same uitdaging', function (): void {
	Functions\when( 'get_the_terms' )->justReturn(
		array( new \WP_Term( 'uitdaging-7' ), new \WP_Term( 'uitdaging-7' ) )
	);
	Functions\when( 'get_post_type' )->justReturn( 'uitdaging' );
	Functions\when( 'get_post_status' )->justReturn( 'publish' );
	Functions\when( 'get_the_title' )->justReturn( 'Herfsuitdaging' );
	Functions\when( 'get_permalink' )->justReturn( '/uitdaging/herfs' );

	expect( WinnerLinkage::linksFor( 5 ) )->toHaveCount( 1 );
} );

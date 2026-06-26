<?php
/**
 * Unit tests for the personalised discovery surfaces (Story 8.5, FR-36, AD-7).
 *
 * Target: {@see \Ink\Discovery\DiscoverySurfaces}. The pure `WP_User_Query` arg
 * builders (new voices / recently active / writers-like), the `excludeId` /
 * `formsFor` helpers and the pure `toHtml()` (a titled row per non-empty surface)
 * are unit-testable without WordPress.
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Discovery;

use Ink\Discovery\DiscoverySurfaces;
use Ink\Discovery\SkrywerIndex;
use Ink\Content\PostTypes;
use Brain\Monkey;
use Brain\Monkey\Functions;

beforeEach( function (): void {
	Monkey\setUp();
} );

afterEach( function (): void {
	Monkey\tearDown();
} );

test( 'newVoicesArgs orders writers by first publication desc (writer-scoped, ids)', function (): void {
	$args = DiscoverySurfaces::newVoicesArgs( 6 );

	expect( $args['fields'] )->toBe( 'ID' );
	expect( $args['number'] )->toBe( 6 );
	expect( $args['orderby'] )->toBe( array( 'sorteer' => 'DESC' ) );
	expect( $args['meta_query']['sorteer']['key'] )->toBe( SkrywerIndex::FIRST_PUBLISH_META );
	expect( $args['meta_query']['sorteer']['type'] )->toBe( 'NUMERIC' );
} );

test( 'recentlyActiveArgs orders writers by last publication desc', function (): void {
	$args = DiscoverySurfaces::recentlyActiveArgs( 6 );

	expect( $args['meta_query']['sorteer']['key'] )->toBe( SkrywerIndex::LAST_PUBLISH_META );
	expect( $args['orderby'] )->toBe( array( 'sorteer' => 'DESC' ) );
} );

test( 'writersLikeArgs builds an OR over the form flags and excludes the reference writer', function (): void {
	$args = DiscoverySurfaces::writersLikeArgs( array( PostTypes::GEDIG, PostTypes::ARTIKEL ), 7, 6 );

	expect( $args['exclude'] )->toBe( array( 7 ) );
	expect( $args['meta_query']['relation'] )->toBe( 'OR' );
	$clauses = array_values( array_filter( $args['meta_query'], 'is_array' ) );
	expect( $clauses[0] )->toBe( array( 'key' => 'ink_skrywer_het_gedig', 'value' => '1' ) );
	expect( $clauses[1] )->toBe( array( 'key' => 'ink_skrywer_het_artikel', 'value' => '1' ) );
} );

test( 'writersLikeArgs with no forms returns a match-nothing query', function (): void {
	$args = DiscoverySurfaces::writersLikeArgs( array(), 7, 6 );

	expect( $args['include'] )->toBe( array( 0 ) ); // id 0 matches no user
	expect( $args )->not->toHaveKey( 'meta_query' );
} );

test( 'excludeId removes the viewer and is a no-op when absent', function (): void {
	expect( DiscoverySurfaces::excludeId( array( 1, 7, 9 ), 7 ) )->toBe( array( 1, 9 ) );
	expect( DiscoverySurfaces::excludeId( array( 1, 9 ), 7 ) )->toBe( array( 1, 9 ) );
} );

test( 'formsFor returns only the forms the writer has a flag for', function (): void {
	Functions\when( 'get_user_meta' )->alias(
		static fn ( int $uid, string $key, bool $single ): string => 'ink_skrywer_het_storie' === $key ? '1' : ''
	);

	expect( DiscoverySurfaces::formsFor( 7 ) )->toBe( array( PostTypes::STORIE ) );
} );

test( 'toHtml renders a titled row per non-empty surface and skips empty ones', function (): void {
	Functions\when( '__' )->returnArg( 1 );
	Functions\when( 'esc_html' )->returnArg( 1 );
	Functions\when( 'esc_attr' )->returnArg( 1 );
	Functions\when( 'esc_url' )->returnArg( 1 );

	$html = DiscoverySurfaces::toHtml(
		array(
			'nuwe_stemme'    => array( array( 'name' => 'Anna', 'profile_url' => '/a', 'gradering' => 'Brons' ) ),
			'onlangs_aktief' => array(), // empty → no row
		)
	);

	expect( $html )->toContain( 'ink-ontdek-vlakke' );
	expect( $html )->toContain( 'Nuwe stemme' );
	expect( $html )->toContain( 'Anna' );
	expect( $html )->toContain( 'Brons' );
	// The empty surface renders no row.
	expect( $html )->not->toContain( 'Onlangs aktief' );
	expect( $html )->not->toContain( 'ink-ontdek-vlak--onlangs_aktief' );
} );

test( 'toHtml renders nothing when every surface is empty', function (): void {
	expect( DiscoverySurfaces::toHtml( array( 'nuwe_stemme' => array(), 'onlangs_aktief' => array() ) ) )->toBe( '' );
} );

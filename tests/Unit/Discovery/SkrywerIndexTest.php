<?php
/**
 * Unit tests for the per-writer discovery denormalization (Story 8.3, FR-34).
 *
 * Target: {@see \Ink\Discovery\SkrywerIndex}. On a bydrae's publish the author's
 * form flag, first-publication (once) and read-total seed are maintained in
 * user-meta; the genre↔type / form-flag mappings are pure.
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Discovery;

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

test( 'genreToType maps the three genre filters and null for anything else', function (): void {
	expect( SkrywerIndex::genreToType( 'digkuns' ) )->toBe( PostTypes::GEDIG );
	expect( SkrywerIndex::genreToType( 'prosa' ) )->toBe( PostTypes::STORIE );
	expect( SkrywerIndex::genreToType( 'artikels' ) )->toBe( PostTypes::ARTIKEL );
	expect( SkrywerIndex::genreToType( 'paddas' ) )->toBeNull();
	expect( SkrywerIndex::genreToType( '' ) )->toBeNull();
} );

test( 'formFlagKey is the per-form has-published key', function (): void {
	expect( SkrywerIndex::formFlagKey( PostTypes::GEDIG ) )->toBe( 'ink_skrywer_het_gedig' );
	expect( SkrywerIndex::formFlagKey( PostTypes::STORIE ) )->toBe( 'ink_skrywer_het_storie' );
} );

test( 'publishing a bydrae sets the form flag, the first publication and seeds the read total', function (): void {
	Functions\when( 'get_user_meta' )->justReturn( '' ); // first-publish + read-total both absent
	Functions\expect( 'update_user_meta' )->once()->with( 7, 'ink_skrywer_het_gedig', '1' );
	Functions\expect( 'update_user_meta' )->once()->with( 7, SkrywerIndex::FIRST_PUBLISH_META, \Mockery::type( 'int' ) );
	Functions\expect( 'update_user_meta' )->once()->with( 7, SkrywerIndex::LAST_PUBLISH_META, \Mockery::type( 'int' ) );
	Functions\expect( 'update_user_meta' )->once()->with( 7, SkrywerIndex::READ_TOTAL_META, 0 );

	SkrywerIndex::onTransition(
		'publish',
		'draft',
		(object) array( 'post_type' => PostTypes::GEDIG, 'post_author' => 7, 'post_date_gmt' => '2026-06-01 10:00:00' )
	);
} );

test( 'first publication is set ONCE but last publication is ALWAYS refreshed', function (): void {
	// Both first-publish and read-total already exist → first-publish + read-total
	// are NOT re-written; the form flag + last-publish ARE.
	Functions\when( 'get_user_meta' )->justReturn( '1700000000' );
	Functions\expect( 'update_user_meta' )->once()->with( 7, 'ink_skrywer_het_storie', '1' );
	Functions\expect( 'update_user_meta' )->once()->with( 7, SkrywerIndex::LAST_PUBLISH_META, \Mockery::type( 'int' ) );
	Functions\expect( 'update_user_meta' )->never()->with( 7, SkrywerIndex::FIRST_PUBLISH_META, \Mockery::any() );

	SkrywerIndex::onTransition(
		'publish',
		'draft',
		(object) array( 'post_type' => PostTypes::STORIE, 'post_author' => 7, 'post_date_gmt' => '2026-06-02 10:00:00' )
	);
} );

test( 'a non-publish transition writes nothing', function (): void {
	Functions\expect( 'update_user_meta' )->never();

	SkrywerIndex::onTransition( 'draft', 'publish', (object) array( 'post_type' => PostTypes::GEDIG, 'post_author' => 7 ) );
} );

test( 'publishing a non-bydrae (page) writes nothing', function (): void {
	Functions\expect( 'update_user_meta' )->never();

	SkrywerIndex::onTransition( 'publish', 'draft', (object) array( 'post_type' => 'page', 'post_author' => 7 ) );
} );

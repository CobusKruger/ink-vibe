<?php
/**
 * Unit tests for the /registreer/ slug-release repair (Epic 19 auth fidelity
 * pass).
 *
 * Target: {@see \Ink\Social\AuthPageRelease}. Confirms the idempotent no-op
 * fast path, the rename-then-create repair path (scoped strictly to
 * post_type `buddypress`), and that a `page` at the target slug is never
 * touched even if renaming logic were somehow reached for it.
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Social;

use Ink\Social\AuthPageRelease;
use Brain\Monkey;
use Brain\Monkey\Functions;
use Mockery;

beforeEach( function (): void {
	Monkey\setUp();
	Functions\when( '__' )->returnArg( 1 );
} );

afterEach( function (): void {
	Monkey\tearDown();
} );

test( 'ensureTargetSlugIsFree is a no-op when a real page already occupies the slug', function (): void {
	Functions\expect( 'get_page_by_path' )
		->once()
		->with( 'registreer', OBJECT, 'page' )
		->andReturn( (object) array( 'ID' => 999 ) );

	Functions\expect( 'post_type_exists' )->never();
	Functions\expect( 'wp_update_post' )->never();
	Functions\expect( 'wp_insert_post' )->never();
	Functions\expect( 'flush_rewrite_rules' )->never();

	( new AuthPageRelease() )->ensureTargetSlugIsFree();
} );

test( 'ensureTargetSlugIsFree renames a stray buddypress-post-type object off the slug, then creates the page', function (): void {
	Functions\expect( 'get_page_by_path' )
		->once()
		->with( 'registreer', OBJECT, 'page' )
		->andReturn( null );

	Functions\expect( 'post_type_exists' )
		->once()
		->with( 'buddypress' )
		->andReturn( true );

	Functions\expect( 'get_page_by_path' )
		->once()
		->with( 'registreer', OBJECT, 'buddypress' )
		->andReturn( (object) array( 'ID' => 448 ) );

	Functions\expect( 'wp_update_post' )
		->once()
		->with(
			Mockery::on(
				static function ( $args ): bool {
					return 448 === $args['ID'] && 'registreer' !== $args['post_name'];
				}
			)
		);

	Functions\expect( 'wp_insert_post' )
		->once()
		->with(
			Mockery::on(
				static function ( $args ): bool {
					return 'registreer' === $args['post_name']
						&& 'page' === $args['post_type']
						&& 'publish' === $args['post_status'];
				}
			)
		);

	Functions\expect( 'flush_rewrite_rules' )->once()->with( false );

	( new AuthPageRelease() )->ensureTargetSlugIsFree();
} );

test( 'ensureTargetSlugIsFree skips the rename when BuddyPress\'s post type is not registered, but still creates the page', function (): void {
	Functions\expect( 'get_page_by_path' )
		->once()
		->with( 'registreer', OBJECT, 'page' )
		->andReturn( null );

	Functions\expect( 'post_type_exists' )
		->once()
		->with( 'buddypress' )
		->andReturn( false );

	Functions\expect( 'wp_update_post' )->never();

	Functions\expect( 'wp_insert_post' )
		->once()
		->with(
			Mockery::on(
				static function ( $args ): bool {
					return 'registreer' === $args['post_name'] && 'page' === $args['post_type'];
				}
			)
		);

	Functions\expect( 'flush_rewrite_rules' )->once()->with( false );

	( new AuthPageRelease() )->ensureTargetSlugIsFree();
} );

test( 'ensureTargetSlugIsFree skips the rename when no buddypress-post-type object occupies the slug', function (): void {
	Functions\expect( 'get_page_by_path' )
		->once()
		->with( 'registreer', OBJECT, 'page' )
		->andReturn( null );

	Functions\expect( 'post_type_exists' )
		->once()
		->with( 'buddypress' )
		->andReturn( true );

	Functions\expect( 'get_page_by_path' )
		->once()
		->with( 'registreer', OBJECT, 'buddypress' )
		->andReturn( null );

	Functions\expect( 'wp_update_post' )->never();

	Functions\expect( 'wp_insert_post' )->once();

	Functions\expect( 'flush_rewrite_rules' )->once()->with( false );

	( new AuthPageRelease() )->ensureTargetSlugIsFree();

	expect( true )->toBeTrue();
} );

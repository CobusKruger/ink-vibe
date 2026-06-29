<?php
/**
 * Unit tests for read-count tracking (Story 8.3, FR-34, AD-7).
 *
 * Target: {@see \Ink\Discovery\ReadCount}. The increments are thin meta bumps;
 * the guard counts only a single readable-bydrae front-end view.
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Discovery;

use Ink\Discovery\ReadCount;
use Ink\Discovery\SkrywerIndex;
use Brain\Monkey;
use Brain\Monkey\Functions;

beforeEach( function (): void {
	Monkey\setUp();
} );

afterEach( function (): void {
	Monkey\tearDown();
} );

test( 'incrementPost adds one to the work read count', function (): void {
	Functions\when( 'get_post_meta' )->justReturn( '4' );
	Functions\expect( 'update_post_meta' )->once()->with( 42, ReadCount::READ_COUNT_META, 5 );

	ReadCount::incrementPost( 42 );
} );

test( 'incrementAuthor adds one to the writer read total', function (): void {
	Functions\when( 'get_user_meta' )->justReturn( '9' );
	Functions\expect( 'update_user_meta' )->once()->with( 7, SkrywerIndex::READ_TOTAL_META, 10 );

	ReadCount::incrementAuthor( 7 );
} );

test( 'maybeCount counts a single readable-bydrae view (post + author)', function (): void {
	Functions\when( 'is_admin' )->justReturn( false );
	Functions\when( 'is_feed' )->justReturn( false );
	Functions\when( 'is_preview' )->justReturn( false );
	Functions\when( 'is_singular' )->justReturn( true );
	Functions\when( 'get_queried_object_id' )->justReturn( 42 );
	Functions\when( 'get_post_meta' )->justReturn( '0' );
	Functions\when( 'get_user_meta' )->justReturn( '0' );
	Functions\when( 'get_post_field' )->justReturn( 7 );
	// Story 18.9: a human, anonymous viewer, no analytics provider wired → the
	// ink-core fallback counter records (Analytics::recordView → incrementPost/Author).
	Functions\when( 'get_current_user_id' )->justReturn( 0 );
	Functions\when( 'apply_filters' )->returnArg( 2 ); // provider_active → its default (false)
	Functions\when( 'wp_unslash' )->returnArg( 1 );
	Functions\when( 'sanitize_text_field' )->returnArg( 1 );
	$_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (human browser)';
	Functions\expect( 'update_post_meta' )->once()->with( 42, ReadCount::READ_COUNT_META, 1 );
	Functions\expect( 'update_user_meta' )->once()->with( 7, SkrywerIndex::READ_TOTAL_META, 1 );

	( new ReadCount() )->maybeCount();

	unset( $_SERVER['HTTP_USER_AGENT'] );
} );

test( 'maybeCount does NOT count an obvious bot view (18.9 hardening, non-vacuous)', function (): void {
	Functions\when( 'is_admin' )->justReturn( false );
	Functions\when( 'is_feed' )->justReturn( false );
	Functions\when( 'is_preview' )->justReturn( false );
	Functions\when( 'is_singular' )->justReturn( true );
	Functions\when( 'get_queried_object_id' )->justReturn( 42 );
	Functions\when( 'get_post_field' )->justReturn( 7 );
	Functions\when( 'get_current_user_id' )->justReturn( 0 );
	Functions\when( 'wp_unslash' )->returnArg( 1 );
	Functions\when( 'sanitize_text_field' )->returnArg( 1 );
	$_SERVER['HTTP_USER_AGENT'] = 'Googlebot/2.1 (+http://www.google.com/bot.html)';
	Functions\expect( 'update_post_meta' )->never();
	Functions\expect( 'update_user_meta' )->never();

	( new ReadCount() )->maybeCount();

	unset( $_SERVER['HTTP_USER_AGENT'] );
} );

test( 'maybeCount does nothing when the view is not a singular bydrae (non-vacuous guard)', function (): void {
	Functions\when( 'is_admin' )->justReturn( false );
	Functions\when( 'is_feed' )->justReturn( false );
	Functions\when( 'is_preview' )->justReturn( false );
	Functions\when( 'is_singular' )->justReturn( false ); // e.g. an archive / page
	Functions\expect( 'update_post_meta' )->never();
	Functions\expect( 'update_user_meta' )->never();

	( new ReadCount() )->maybeCount();
} );

test( 'maybeCount does nothing in the admin', function (): void {
	Functions\when( 'is_admin' )->justReturn( true );
	Functions\expect( 'update_post_meta' )->never();

	( new ReadCount() )->maybeCount();
} );

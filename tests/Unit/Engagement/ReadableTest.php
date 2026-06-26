<?php
/**
 * Unit tests for the engagement readable-bydrae gate (Epic 7 review patch).
 *
 * Target: {@see \Ink\Engagement\Readable::isBydrae()} — the single source the
 * reaction / Gemeenskapsreaksie / leeslys write paths use so engagement can only
 * be written against a published gedig/storie/artikel, never a Page, the
 * skryfwerk bucket, or any other published object. Non-vacuous: the published-
 * gedig case proves true while every excluded case proves false.
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Engagement;

use Ink\Engagement\Readable;
use Brain\Monkey;
use Brain\Monkey\Functions;

beforeEach( function (): void {
	Monkey\setUp();
} );

afterEach( function (): void {
	Monkey\tearDown();
} );

test( 'a published gedig/storie/artikel is a readable bydrae', function (): void {
	Functions\when( 'get_post_status' )->justReturn( 'publish' );

	foreach ( array( 'gedig', 'storie', 'artikel' ) as $type ) {
		Functions\when( 'get_post_type' )->justReturn( $type );
		expect( Readable::isBydrae( 42 ) )->toBeTrue( "should accept published {$type}" );
	}
} );

test( 'a non-bydrae published post is rejected (page, skryfwerk, attachment)', function (): void {
	Functions\when( 'get_post_status' )->justReturn( 'publish' );

	foreach ( array( 'page', 'skryfwerk', 'attachment', 'post' ) as $type ) {
		Functions\when( 'get_post_type' )->justReturn( $type );
		expect( Readable::isBydrae( 42 ) )->toBeFalse( "should reject published {$type}" );
	}
} );

test( 'an unpublished bydrae is rejected', function (): void {
	Functions\when( 'get_post_status' )->justReturn( 'draft' );
	Functions\when( 'get_post_type' )->justReturn( 'gedig' );

	expect( Readable::isBydrae( 42 ) )->toBeFalse();
} );

test( 'a non-positive post id is rejected without touching the post', function (): void {
	expect( Readable::isBydrae( 0 ) )->toBeFalse();
} );

<?php
/**
 * Unit tests for the Gemeenskapsreaksie upvote (owner-approved 2026-11-02): the comment-meta
 * store and the REST controller's pure rules.
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Engagement;

use Ink\Engagement\ResponseUpvoteController;
use Ink\Engagement\ResponseUpvotes;
use Ink\Tests\Support\CodeScan;
use Brain\Monkey;
use Brain\Monkey\Functions;

beforeEach( function (): void {
	Monkey\setUp();
	Functions\when( '__' )->returnArg( 1 );
} );

afterEach( function (): void {
	Monkey\tearDown();
} );

test( 'distinctIds keeps each positive voter once', function (): void {
	expect( ResponseUpvotes::distinctIds( array( '7', 7, '9', 0, 'x', array(), '-3' ) ) )->toBe( array( 7, 9 ) );
} );

test( 'count and has read the distinct voters of the response', function (): void {
	Functions\when( 'get_comment_meta' )->justReturn( array( '7', '7', '9' ) );

	expect( ResponseUpvotes::count( 5 ) )->toBe( 2 );
	expect( ResponseUpvotes::has( 5, 9 ) )->toBeTrue();
	expect( ResponseUpvotes::has( 5, 4 ) )->toBeFalse();
	expect( ResponseUpvotes::has( 5, 0 ) )->toBeFalse();
} );

test( 'an unvoted response reads 0 and no id reads nothing', function (): void {
	Functions\when( 'get_comment_meta' )->justReturn( array() );

	expect( ResponseUpvotes::count( 5 ) )->toBe( 0 );
	expect( ResponseUpvotes::voters( 0 ) )->toBe( array() );
} );

test( 'add stores one row per member', function (): void {
	Functions\when( 'get_comment_meta' )->justReturn( array() );
	Functions\expect( 'add_comment_meta' )->once()->with( 5, ResponseUpvotes::META_VOTER, 7, false )->andReturn( 12 );

	expect( ResponseUpvotes::add( 5, 7 ) )->toBeTrue();
} );

test( 'add is a no-op when the member already voted', function (): void {
	Functions\when( 'get_comment_meta' )->justReturn( array( '7' ) );
	Functions\expect( 'add_comment_meta' )->never();

	expect( ResponseUpvotes::add( 5, 7 ) )->toBeFalse();
} );

test( 'remove deletes only the one member row', function (): void {
	Functions\expect( 'delete_comment_meta' )->once()->with( 5, ResponseUpvotes::META_VOTER, 7 )->andReturn( true );

	expect( ResponseUpvotes::remove( 5, 7 ) )->toBeTrue();
	expect( ResponseUpvotes::remove( 5, 0 ) )->toBeFalse();
} );

test( 'summaryFor gives each response its count and the viewer vote', function (): void {
	Functions\when( 'get_comment_meta' )->alias(
		static fn ( int $id ): array => 1 === $id ? array( '7', '8' ) : array()
	);

	expect( ResponseUpvotes::summaryFor( array( 1, 2 ), 7 ) )->toBe(
		array(
			1 => array(
				'count' => 2,
				'voted' => true,
			),
			2 => array(
				'count' => 0,
				'voted' => false,
			),
		)
	);
	expect( ResponseUpvotes::summaryFor( array( 1 ), 0 )[1]['voted'] )->toBeFalse();
} );

test( 'the upvote route needs a logged-in member (the REST nonce is checked by core)', function (): void {
	Functions\when( 'is_user_logged_in' )->justReturn( true );
	expect( ( new ResponseUpvoteController() )->permission() )->toBeTrue();

	Functions\when( 'is_user_logged_in' )->justReturn( false );
	expect( ( new ResponseUpvoteController() )->permission() )->toBeFalse();
} );

test( 'validate refuses a visitor, a non-response, an unpublished work and the author', function (): void {
	expect( ResponseUpvoteController::validate( true, true, 5, 0 )->get_error_code() )->toBe( 'ink_opstem_not_logged_in' );
	expect( ResponseUpvoteController::validate( false, true, 5, 7 )->get_error_code() )->toBe( 'ink_opstem_invalid_response' );
	expect( ResponseUpvoteController::validate( true, false, 5, 7 )->get_error_code() )->toBe( 'ink_opstem_invalid_response' );
	expect( ResponseUpvoteController::validate( true, true, 7, 7 )->get_error_code() )->toBe( 'ink_opstem_own_response' );
} );

test( 'validate passes for another member on a published work', function (): void {
	expect( ResponseUpvoteController::validate( true, true, 5, 7 ) )->toBeNull();
} );

test( 'the upvote store and controller are conflation-clean (no Tiers / Entitlement)', function (): void {
	$root = dirname( __DIR__, 3 );

	foreach ( array( 'ResponseUpvotes.php', 'ResponseUpvoteController.php' ) as $file ) {
		$code = CodeScan::withoutComments( $root . '/wp-content/plugins/ink-core/src/Engagement/' . $file );

		expect( $code )->toContain( 'class ' );
		expect( $code )->not->toContain( 'Ink\\Tiers' );
		expect( $code )->not->toContain( 'Ink\\Entitlement' );
	}
} );

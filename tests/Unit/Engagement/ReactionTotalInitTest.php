<?php
/**
 * Unit tests for the denormalized reaction-total initialiser + facade key
 * (Story 8.2, FR-33, AD-7).
 *
 * Target: {@see \Ink\Engagement\ReactionTotalInit} (seed `0` at publish so a
 * zero-reaction work is not dropped by the "Mees geliefd" meta join) and
 * {@see \Ink\Engagement\Api::reactionTotalMetaKey()} (the cross-module contract).
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Engagement;

use Ink\Engagement\ReactionTotalInit;
use Ink\Engagement\ReactionStore;
use Ink\Engagement\Api;
use Brain\Monkey;
use Brain\Monkey\Functions;

beforeEach( function (): void {
	Monkey\setUp();
} );

afterEach( function (): void {
	Monkey\tearDown();
} );

test( 'the facade exposes the denormalized reaction-total meta key (single source)', function (): void {
	expect( Api::reactionTotalMetaKey() )->toBe( ReactionStore::TOTAL_META_KEY );
	expect( Api::reactionTotalMetaKey() )->toBe( 'ink_reaksie_telling' );
} );

test( 'publishing a bydrae with no stored total seeds it to 0', function (): void {
	Functions\when( 'get_post_status' )->justReturn( 'publish' );
	Functions\when( 'get_post_type' )->justReturn( 'gedig' );
	Functions\when( 'get_post_meta' )->justReturn( '' ); // absent
	Functions\expect( 'update_post_meta' )->once()->with( 99, ReactionStore::TOTAL_META_KEY, 0 );

	ReactionTotalInit::onTransition( 'publish', 'draft', (object) array( 'ID' => 99 ) );
} );

test( 'publishing a bydrae that already has a total does NOT clobber it', function (): void {
	Functions\when( 'get_post_status' )->justReturn( 'publish' );
	Functions\when( 'get_post_type' )->justReturn( 'storie' );
	Functions\when( 'get_post_meta' )->justReturn( '17' ); // a live count already
	Functions\expect( 'update_post_meta' )->never();

	ReactionTotalInit::onTransition( 'publish', 'draft', (object) array( 'ID' => 99 ) );
} );

test( 'a non-publish transition is a no-op', function (): void {
	Functions\expect( 'update_post_meta' )->never();

	ReactionTotalInit::onTransition( 'draft', 'publish', (object) array( 'ID' => 99 ) );
} );

test( 'publishing a non-bydrae (e.g. a page or the skryfwerk bucket) is a no-op', function (): void {
	Functions\when( 'get_post_status' )->justReturn( 'publish' );
	Functions\when( 'get_post_type' )->justReturn( 'page' ); // not a readable bydrae
	Functions\expect( 'update_post_meta' )->never();

	ReactionTotalInit::onTransition( 'publish', 'draft', (object) array( 'ID' => 99 ) );
} );

<?php
/**
 * Unit tests for the Sponsors module facade (Story 14.1, FR-58).
 *
 * Target: {@see \Ink\Sponsors\Api} — the sole public cross-module surface. The
 * sponsor read-model lookup ({@see Api::sponsorFor()}, type-guarded) and the
 * canonical meta-key set ({@see Api::metaKeys()}) are unit-testable with WP mocked.
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Sponsors;

use Ink\Sponsors\Api;
use Ink\Sponsors\Sponsor;
use Ink\Content\FieldSets;
use Ink\Content\PostTypes;
use Brain\Monkey;
use Brain\Monkey\Functions;

beforeEach( function (): void {
	Monkey\setUp();
} );

afterEach( function (): void {
	Monkey\tearDown();
} );

test( 'metaKeys returns exactly the five FR-58 sponsor meta keys, single-sourced from FieldSets', function (): void {
	expect( Api::metaKeys() )->toBe(
		array(
			FieldSets::BORG_LINK,
			FieldSets::BORG_TIER,
			FieldSets::BORG_START_DATE,
			FieldSets::BORG_END_DATE,
			FieldSets::BORG_PLACEMENT,
		)
	);
} );

test( 'sponsorFor returns a Sponsor read-model for a real borg post', function (): void {
	Functions\when( 'get_post_type' )->justReturn( PostTypes::BORG );
	Functions\when( 'get_the_title' )->justReturn( 'Borg 1' );
	Functions\when( 'get_post_meta' )->justReturn( '' );

	$sponsor = Api::sponsorFor( 42 );

	expect( $sponsor )->toBeInstanceOf( Sponsor::class );
	expect( $sponsor->postId )->toBe( 42 );
} );

test( 'sponsorFor returns null for a post of a different type (no phantom sponsor)', function (): void {
	Functions\when( 'get_post_type' )->justReturn( PostTypes::GEDIG );

	expect( Api::sponsorFor( 42 ) )->toBeNull();
} );

test( 'sponsorFor returns null for a non-positive id without touching get_post_type', function (): void {
	// get_post_type intentionally NOT stubbed — a 0 id must short-circuit before it.
	expect( Api::sponsorFor( 0 ) )->toBeNull();
	expect( Api::sponsorFor( -3 ) )->toBeNull();
} );

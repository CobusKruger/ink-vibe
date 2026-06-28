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

// --- Story 14.2: campaign-window delegation (via the WP_Query stub) ---

/**
 * Stage WP_Query to return $count borg posts (ids 1..$count) with the given window
 * meta, so the Api → Campaign → WP wrapper path runs end-to-end without a real DB.
 *
 * @param array<int, array{start: string, end: string}> $windows Per-post campaign windows.
 */
function ink_sponsors_stage_query( array $windows ): void {
	$posts = array();
	$meta  = array();

	foreach ( $windows as $i => $window ) {
		$id              = $i + 1;
		$post            = new \WP_Post();
		$post->ID        = $id;
		$post->post_type = \Ink\Content\PostTypes::BORG;
		$posts[]         = $post;

		$meta[ $id ] = array(
			FieldSets::BORG_START_DATE => $window['start'],
			FieldSets::BORG_END_DATE   => $window['end'],
		);
	}

	\WP_Query::$ink_test_posts = $posts;

	Functions\when( 'get_the_title' )->justReturn( 'Borg' );
	Functions\when( 'get_post_meta' )->alias(
		static fn ( int $id, string $key, bool $single = false ) => $meta[ $id ][ $key ] ?? ''
	);
}

afterEach( function (): void {
	\WP_Query::$ink_test_posts = array();
} );

test( 'activeSponsors delegates to Campaign and returns only in-window sponsors', function (): void {
	$now = new \DateTimeImmutable( '2026-06-22 12:00:00', new \DateTimeZone( 'Africa/Johannesburg' ) );
	ink_sponsors_stage_query(
		array(
			array( 'start' => '2026-06-01', 'end' => '2026-06-30' ), // active
			array( 'start' => '2026-01-01', 'end' => '2026-01-31' ), // expired
			array( 'start' => '', 'end' => '' ),                     // evergreen → active
		)
	);

	$active = Api::activeSponsors( $now );

	expect( $active )->toHaveCount( 2 );
	expect( $active[0]->postId )->toBe( 1 );
	expect( $active[1]->postId )->toBe( 3 );
} );

test( 'featuredSponsor delegates to Campaign and returns null when none is active', function (): void {
	$now = new \DateTimeImmutable( '2026-06-22 12:00:00', new \DateTimeZone( 'Africa/Johannesburg' ) );
	ink_sponsors_stage_query(
		array(
			array( 'start' => '2026-01-01', 'end' => '2026-01-31' ), // expired
		)
	);

	expect( Api::featuredSponsor( $now ) )->toBeNull();
} );

test( 'featuredSponsor returns one of the active sponsors when several are active', function (): void {
	$now = new \DateTimeImmutable( '2026-06-22 12:00:00', new \DateTimeZone( 'Africa/Johannesburg' ) );
	ink_sponsors_stage_query(
		array(
			array( 'start' => '', 'end' => '' ),
			array( 'start' => '', 'end' => '' ),
		)
	);

	$featured = Api::featuredSponsor( $now );

	expect( $featured )->toBeInstanceOf( Sponsor::class );
	expect( in_array( $featured->postId, array( 1, 2 ), true ) )->toBeTrue();
} );

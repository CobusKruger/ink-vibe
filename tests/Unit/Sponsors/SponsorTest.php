<?php
/**
 * Unit tests for the Sponsors read-model (Story 14.1, FR-58).
 *
 * Target: {@see \Ink\Sponsors\Sponsor}. The `forPost()` meta read (default-safe)
 * and the `logoUrl()`/`hasLogo()` featured-image resolvers are unit-testable with
 * WordPress mocked.
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Sponsors;

use Ink\Sponsors\Sponsor;
use Ink\Content\FieldSets;
use Brain\Monkey;
use Brain\Monkey\Functions;

beforeEach( function (): void {
	Monkey\setUp();
} );

afterEach( function (): void {
	Monkey\tearDown();
} );

/**
 * Stub `get_post_meta` to return values from a key→value map (the stored meta).
 *
 * @param array<string, mixed> $meta The stored meta, keyed by meta key.
 */
function ink_sponsors_stub_meta( array $meta ): void {
	Functions\when( 'get_post_meta' )->alias(
		static fn ( int $id, string $key, bool $single = false ) => $meta[ $key ] ?? ''
	);
}

// --- forPost ---

test( 'forPost reads all five FR-58 fields plus the name off the post into typed properties', function (): void {
	Functions\when( 'get_the_title' )->justReturn( 'Uitgewery Protea' );
	ink_sponsors_stub_meta(
		array(
			FieldSets::BORG_LINK       => 'https://protea.test',
			FieldSets::BORG_TIER       => 'Goud',
			FieldSets::BORG_START_DATE => '2026-01-01',
			FieldSets::BORG_END_DATE   => '2026-12-31',
			FieldSets::BORG_PLACEMENT  => 'tuisblad',
		)
	);

	$sponsor = Sponsor::forPost( 42 );

	expect( $sponsor->postId )->toBe( 42 );
	expect( $sponsor->name )->toBe( 'Uitgewery Protea' );
	expect( $sponsor->link )->toBe( 'https://protea.test' );
	expect( $sponsor->tier )->toBe( 'Goud' );
	expect( $sponsor->startDate )->toBe( '2026-01-01' );
	expect( $sponsor->endDate )->toBe( '2026-12-31' );
	expect( $sponsor->placement )->toBe( 'tuisblad' );
} );

test( 'forPost is default-safe — missing/non-scalar meta degrades to typed empty defaults', function (): void {
	Functions\when( 'get_the_title' )->justReturn( '' );
	// Every key absent → '' from the stub; a non-scalar value coerces to '', never a fatal.
	ink_sponsors_stub_meta( array( FieldSets::BORG_LINK => array( 'not', 'scalar' ) ) );

	$sponsor = Sponsor::forPost( 7 );

	expect( $sponsor->name )->toBe( '' );
	expect( $sponsor->link )->toBe( '' );
	expect( $sponsor->tier )->toBe( '' );
	expect( $sponsor->startDate )->toBe( '' );
	expect( $sponsor->endDate )->toBe( '' );
	expect( $sponsor->placement )->toBe( '' );
} );

// --- logoUrl / hasLogo ---

test( 'logoUrl resolves the featured image to its URL at the requested size, hasLogo true', function (): void {
	Functions\when( 'get_post_thumbnail_id' )->justReturn( 99 );
	Functions\when( 'wp_get_attachment_image_url' )->alias(
		static fn ( int $id, string $size ) => 'https://ink.test/logo-' . $id . '-' . $size . '.png'
	);

	$sponsor = new Sponsor( 42, 'P', '', '', '', '', '' );
	expect( $sponsor->logoUrl() )->toBe( 'https://ink.test/logo-99-medium.png' );
	expect( $sponsor->logoUrl( 'large' ) )->toBe( 'https://ink.test/logo-99-large.png' );
	expect( $sponsor->hasLogo() )->toBeTrue();
} );

test( 'logoUrl is empty and hasLogo false when there is no featured image', function (): void {
	Functions\when( 'get_post_thumbnail_id' )->justReturn( 0 );

	$sponsor = new Sponsor( 42, 'P', '', '', '', '', '' );
	expect( $sponsor->logoUrl() )->toBe( '' );
	expect( $sponsor->hasLogo() )->toBeFalse();
} );

test( 'logoUrl is empty when the featured-image attachment no longer resolves to a URL', function (): void {
	Functions\when( 'get_post_thumbnail_id' )->justReturn( 99 );
	Functions\when( 'wp_get_attachment_image_url' )->justReturn( false );

	$sponsor = new Sponsor( 42, 'P', '', '', '', '', '' );
	expect( $sponsor->logoUrl() )->toBe( '' );
	expect( $sponsor->hasLogo() )->toBeFalse();
} );

test( 'logoUrl returns empty for a non-positive post id without touching the thumbnail resolver', function (): void {
	// get_post_thumbnail_id intentionally NOT stubbed — a 0 id must short-circuit before it.
	$sponsor = new Sponsor( 0, 'P', '', '', '', '', '' );
	expect( $sponsor->logoUrl() )->toBe( '' );
	expect( $sponsor->hasLogo() )->toBeFalse();
} );

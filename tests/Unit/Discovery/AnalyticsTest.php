<?php
/**
 * Unit tests for the read-analytics provider seam + hardening (Story 18.9, R8).
 *
 * Target: {@see \Ink\Discovery\Analytics} — pure isBot()/shouldRecordView()
 * hardening + the provider-aware recordView()/viewCount() seams. Brain-Monkey, no WP.
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Discovery;

use Ink\Discovery\Analytics;
use Ink\Discovery\ReadCount;
use Brain\Monkey;
use Brain\Monkey\Functions;

beforeEach( function (): void {
	Monkey\setUp();
} );

afterEach( function (): void {
	Monkey\tearDown();
} );

// --- isBot() ---

test( 'isBot flags obvious crawlers and an empty UA', function (): void {
	expect( Analytics::isBot( 'Googlebot/2.1' ) )->toBeTrue();
	expect( Analytics::isBot( 'curl/8.4.0' ) )->toBeTrue();
	expect( Analytics::isBot( 'HeadlessChrome/120' ) )->toBeTrue();
	expect( Analytics::isBot( '' ) )->toBeTrue();
	expect( Analytics::isBot( '   ' ) )->toBeTrue();
} );

test( 'isBot does not flag a normal browser UA', function (): void {
	expect( Analytics::isBot( 'Mozilla/5.0 (Macintosh) Safari/605' ) )->toBeFalse();
} );

// --- shouldRecordView() ---

test( 'shouldRecordView records a human anonymous view', function (): void {
	expect( Analytics::shouldRecordView( 'Mozilla/5.0', 0, 7 ) )->toBeTrue();
} );

test( 'shouldRecordView excludes the author viewing their own work', function (): void {
	expect( Analytics::shouldRecordView( 'Mozilla/5.0', 7, 7 ) )->toBeFalse();
} );

test( 'shouldRecordView excludes a bot even for a different author', function (): void {
	expect( Analytics::shouldRecordView( 'Googlebot/2.1', 0, 7 ) )->toBeFalse();
} );

test( 'shouldRecordView records a different logged-in reader', function (): void {
	expect( Analytics::shouldRecordView( 'Mozilla/5.0', 3, 7 ) )->toBeTrue();
} );

// --- recordView(): provider hand-off vs fallback ---

test( 'recordView falls back to the ink-core counter when no provider is wired', function (): void {
	Functions\when( 'apply_filters' )->justReturn( false ); // provider inactive
	Functions\when( 'get_post_meta' )->justReturn( '0' );
	Functions\when( 'get_user_meta' )->justReturn( '0' );
	Functions\expect( 'update_post_meta' )->once()->with( 42, ReadCount::READ_COUNT_META, 1 );
	Functions\expect( 'update_user_meta' )->once();

	Analytics::recordView( 42, 7 );
} );

test( 'recordView hands off to the provider (no ink-core counter bump) when active', function (): void {
	Functions\when( 'apply_filters' )->justReturn( true ); // provider active
	Functions\expect( 'do_action' )->once()->with( 'ink/analytics_record_view', 42, 7 );
	// The fallback counter must NOT run when a provider owns the data.
	Functions\expect( 'update_post_meta' )->never();
	Functions\expect( 'update_user_meta' )->never();

	Analytics::recordView( 42, 7 );
} );

// --- viewCount(): read seam ---

test( 'viewCount returns the meta count by default and lets a provider override it', function (): void {
	Functions\when( 'get_post_meta' )->justReturn( '12' );

	// Default: filter returns the meta value unchanged.
	Functions\when( 'apply_filters' )->returnArg( 2 );
	expect( Analytics::viewCount( 42 ) )->toBe( 12 );

	// Provider override.
	Monkey\tearDown();
	Monkey\setUp();
	Functions\when( 'get_post_meta' )->justReturn( '12' );
	Functions\when( 'apply_filters' )->justReturn( 99 );
	expect( Analytics::viewCount( 42 ) )->toBe( 99 );
} );

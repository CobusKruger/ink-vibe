<?php
/**
 * Unit tests for the leeslys REST controller (Story 7.7, FR-29, AD-6).
 *
 * Pure `validate()` + `permission()` plus the conflation guardrail over the
 * controller, store and both blocks (no Tiers/Entitlement — the leeslys is open
 * to any lid, never entitlement-gated).
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Engagement;

use Ink\Engagement\ReadingListController;
use Ink\Tests\Support\CodeScan;
use Brain\Monkey;
use Brain\Monkey\Functions;

beforeEach( function (): void {
	Monkey\setUp();
} );

afterEach( function (): void {
	Monkey\tearDown();
} );

test( 'permission allows any logged-in lid (not entitlement-gated)', function (): void {
	Functions\when( 'is_user_logged_in' )->justReturn( true );
	expect( ( new ReadingListController() )->permission() )->toBeTrue();

	Functions\when( 'is_user_logged_in' )->justReturn( false );
	expect( ( new ReadingListController() )->permission() )->toBeFalse();
} );

test( 'validate rejects an unreadable post and passes a readable one', function (): void {
	$error = ReadingListController::validate( false );
	expect( $error )->toBeInstanceOf( \WP_Error::class );
	expect( $error->get_error_code() )->toBe( 'ink_leeslys_invalid_post' );

	expect( ReadingListController::validate( true ) )->toBeNull();
} );

test( 'the leeslys controller, store and blocks are conflation-clean (no Tiers / Entitlement)', function (): void {
	$root  = dirname( __DIR__, 3 ) . '/wp-content/plugins/ink-core/src/Engagement/';
	$files = array(
		$root . 'ReadingListController.php',
		$root . 'ReadingListStore.php',
		$root . 'ReadingListToggle.php',
		$root . 'ReadingList.php',
	);

	$scanned = 0;
	foreach ( $files as $file ) {
		$code = CodeScan::withoutComments( $file );
		expect( $code )->toContain( 'class ' );
		++$scanned;
		expect( $code )->not->toContain( 'Ink\\Tiers' );
		expect( $code )->not->toContain( 'Ink\\Entitlement' );
	}

	expect( $scanned )->toBe( 4 );
} );

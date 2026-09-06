<?php
/**
 * Unit tests for the kennisgewings REST controller (My Profiel rebuild §5.8, AD-6).
 *
 * Target: {@see \Ink\Notifications\KennisgewingsController}. Mirrors
 * {@see \Ink\Social\FollowControllerTest}/{@see \Ink\Social\PinnedWorksControllerTest}'s
 * bar exactly: `permission()` is exercised directly; `handleList()`/
 * `handleMarkAllRead()` are NOT unit-invoked because they type-hint
 * `WP_REST_Request`/return `WP_REST_Response` — WordPress classes that are only
 * available as static-analysis stubs (`php-stubs/wordpress-stubs`, explicitly
 * "not autoloadable at runtime" per `tests/bootstrap.php`), never instantiated
 * anywhere else in this suite either. There is no `validate()` here (unlike
 * Follow/PinnedWorks): both handlers act only on `get_current_user_id()`, so
 * `permission()` is the whole authorisation surface.
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Notifications;

use Ink\Notifications\KennisgewingsController;
use Brain\Monkey;
use Brain\Monkey\Functions;

beforeEach( function (): void {
	Monkey\setUp();
} );

afterEach( function (): void {
	Monkey\tearDown();
} );

test( 'permission allows any logged-in lid (no target to own, unlike Follow/PinnedWorks)', function (): void {
	Functions\when( 'is_user_logged_in' )->justReturn( true );
	expect( ( new KennisgewingsController() )->permission() )->toBeTrue();

	Functions\when( 'is_user_logged_in' )->justReturn( false );
	expect( ( new KennisgewingsController() )->permission() )->toBeFalse();
} );

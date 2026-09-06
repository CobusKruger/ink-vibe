<?php
/**
 * Unit tests for the Notifications module facade's newest surface.
 *
 * Target: {@see \Ink\Notifications\Api::unreadCount()} — the My Profiel
 * rebuild §5.3/§5.8 entry point other modules/consumers reach the shared
 * unread-kennisgewing count through (AD-1). {@see Api}'s pre-existing methods
 * (`send`/`randomMessage`/`templateBody`/`notify`/`markAllRead`) are already
 * covered indirectly by their consumers' own test suites (e.g.
 * `ReceiptNotificationTest`, `PromotionEmailsTest`) — no dedicated `Api`
 * test file existed before this one.
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Notifications;

use Ink\Notifications\Api;
use Brain\Monkey;
use Brain\Monkey\Functions;

beforeEach( function (): void {
	Monkey\setUp();
	Functions\when( '__' )->returnArg( 1 );
	Functions\when( 'get_user_meta' )->justReturn( '' );
} );

afterEach( function (): void {
	Monkey\tearDown();
} );

// Deliberately the ONLY test in this file: it relies on
// `bp_notifications_get_notifications_for_user` being genuinely UNDEFINED
// (real `function_exists() === false`, the guarded no-op branch) — Brain
// Monkey/Patchwork makes a mocked function's mere EXISTENCE leak across every
// later test in the process once ANY test anywhere calls `Functions\when()`/
// `expect()` on it (see the fuller note in KennisgewingsSurfaceTest's "rows
// returns nothing when BuddyPress is absent" test). Do NOT add a second test
// here that mocks that function "with BuddyPress present" data — the full
// present-BuddyPress behaviour of `unreadCount()` is already covered by
// KennisgewingsSurfaceTest's `unreadCount` tests, which own that function name.
test( 'unreadCount delegates to the KennisgewingsSurface (0 when BuddyPress is absent)', function (): void {
	expect( Api::unreadCount( 7 ) )->toBe( 0 );
} );

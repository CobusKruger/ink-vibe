<?php
/**
 * Unit tests for the kennisgewing emitter + mark-all-read boundary
 * (Story 9.9, FR-44, AD-5).
 *
 * Target: {@see \Ink\Notifications\Kennisgewings}. The load-bearing assertion:
 * the timestamp-boundary unread logic is race-free — a notification created
 * AFTER the mark-all boundary stays unread (no phantom-unread).
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Notifications;

use Ink\Notifications\Kennisgewings;
use Ink\Notifications\NotificationType;
use Brain\Monkey;
use Brain\Monkey\Functions;

beforeEach( function (): void {
	Monkey\setUp();
} );

afterEach( function (): void {
	Monkey\tearDown();
} );

test( 'isUnread: a kennisgewing created AFTER the mark-all boundary stays unread (no phantom-unread)', function (): void {
	$boundary = '2026-06-26 12:00:00';

	// Non-vacuous: one before the boundary is READ...
	expect( Kennisgewings::isUnread( '2026-06-26 11:59:59', $boundary ) )->toBeFalse();
	// ...one after (arrived during/after the mark-all click) is still UNREAD.
	expect( Kennisgewings::isUnread( '2026-06-26 12:00:01', $boundary ) )->toBeTrue();
	// equal to the boundary counts as read (not strictly after).
	expect( Kennisgewings::isUnread( $boundary, $boundary ) )->toBeFalse();
} );

test( 'isUnread: an empty boundary (never marked all read) means everything is unread', function (): void {
	expect( Kennisgewings::isUnread( '2026-06-26 12:00:00', '' ) )->toBeTrue();
} );

test( 'countUnread counts only the kennisgewings after the boundary', function (): void {
	$boundary = '2026-06-26 12:00:00';
	$created  = array( '2026-06-26 11:00:00', '2026-06-26 13:00:00', '2026-06-26 14:00:00' );

	expect( Kennisgewings::countUnread( $created, $boundary ) )->toBe( 2 );
} );

test( 'add no-ops (false) when BuddyPress is absent', function (): void {
	// bp_notifications_add_notification undefined → guarded no-op.
	expect( Kennisgewings::add( 7, NotificationType::Reaksie, 42, 9 ) )->toBeFalse();
} );

test( 'add never notifies the actor about their own action', function (): void {
	Functions\when( 'bp_notifications_add_notification' )->justReturn( 1 );

	// Same user as actor → suppressed (no self-notify), even with BP present.
	expect( Kennisgewings::add( 7, NotificationType::Reaksie, 42, 7 ) )->toBeFalse();
	// A different recipient is written.
	expect( Kennisgewings::add( 7, NotificationType::Reaksie, 42, 9 ) )->toBeTrue();
} );

test( 'add rejects a non-positive recipient id', function (): void {
	Functions\when( 'bp_notifications_add_notification' )->justReturn( 1 );

	expect( Kennisgewings::add( 0, NotificationType::VolgWerk, 42 ) )->toBeFalse();
} );

test( 'markAllRead stores the GMT boundary as the source of truth', function (): void {
	Functions\expect( 'current_time' )->once()->with( 'mysql', true )->andReturn( '2026-06-26 12:00:00' );
	Functions\expect( 'update_user_meta' )->once()->with( 7, Kennisgewings::MARK_META, '2026-06-26 12:00:00' );

	Kennisgewings::markAllRead( 7 );
} );

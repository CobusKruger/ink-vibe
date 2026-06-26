<?php
/**
 * Unit tests for the kennisgewing source subscriptions (Story 9.9, FR-44).
 *
 * Target: {@see \Ink\Notifications\Events}. The pure `mentionedLogins()` parser
 * and the source guards (a non-reaksie comment / non-publish transition emit
 * nothing).
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Notifications;

use Ink\Notifications\Events;
use Brain\Monkey;

beforeEach( function (): void {
	Monkey\setUp();
} );

afterEach( function (): void {
	Monkey\tearDown();
} );

test( 'mentionedLogins extracts deduped, lowercased @handles', function (): void {
	$body = 'Mooi @Anja en @pieter — ook @Anja weer, dankie @kobus.';

	expect( Events::mentionedLogins( $body ) )->toBe( array( 'anja', 'pieter', 'kobus' ) );
} );

test( 'mentionedLogins finds nothing in plain text', function (): void {
	expect( Events::mentionedLogins( 'Net gewone teks sonder enige verwysing.' ) )->toBe( array() );
} );

test( 'mentionedLogins does not treat an email address as a mention', function (): void {
	// No leading whitespace/start before the @ in "jan@example.com".
	expect( Events::mentionedLogins( 'Kontak jan@example.com asseblief.' ) )->toBe( array() );
} );

test( 'onComment ignores a non-reaksie comment (no fatal, emits nothing)', function (): void {
	$comment               = new \stdClass();
	$comment->comment_type = 'comment'; // a normal WP comment, not ink_reaksie

	// No BP / no post-field lookups should be reached; just assert no fatal.
	( new Events() )->onComment( 5, $comment );

	expect( true )->toBeTrue();
} );

test( 'onTransition ignores a non-publish transition (no fatal, emits nothing)', function (): void {
	$post            = new \stdClass();
	$post->post_type = 'gedig';

	( new Events() )->onTransition( 'draft', 'auto-draft', $post );

	expect( true )->toBeTrue();
} );

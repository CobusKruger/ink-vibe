<?php
/**
 * Standing launch-scope guardrail — Private Messaging is deferred (Story 9.8,
 * FL 9.8, §14.7).
 *
 * Messaging is code-enforced OFF by the Story 9.1 BuddyPress scope. This named
 * guard fails the suite if a future edit re-adds `messages` to launch scope —
 * pointing the contributor at the §14.7 launch-scope decision.
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Social;

use Ink\Social\BuddyPress;
use Brain\Monkey;

beforeEach( function (): void {
	Monkey\setUp();
} );

afterEach( function (): void {
	Monkey\tearDown();
} );

test( 'Private Messaging is OFF at launch — messages is forced off, never scoped on', function (): void {
	expect( BuddyPress::FORCED_OFF )->toContain( 'messages' ); // §14.7: off at launch
	expect( BuddyPress::SCOPED_ON )->not->toContain( 'messages' );
} );

test( 'scopeComponents strips messages even when the cloned DB had it active (non-vacuous)', function (): void {
	$active = array(
		'xprofile' => '1',
		'members'  => '1',
		'messages' => '1', // the cloned DB carried Messaging active...
	);

	// Guard non-vacuity: the input genuinely has Messaging active.
	expect( $active )->toHaveKey( 'messages' );

	$scoped = BuddyPress::scopeComponents( $active );

	// ...but the launch scope forces it off.
	expect( $scoped )->not->toHaveKey( 'messages' );
} );

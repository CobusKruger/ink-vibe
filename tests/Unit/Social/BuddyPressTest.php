<?php
/**
 * Unit tests for the BuddyPress scoped-component config (Story 9.1, FR-37).
 *
 * Target: {@see \Ink\Social\BuddyPress}. The pure `scopeComponents()` reducer is
 * unit-testable without BuddyPress loaded — it returns exactly the INK scope
 * regardless of what the cloned DB had active.
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

test( 'scopeComponents keeps every INK-scoped component when given the full BP list', function (): void {
	$full = array(
		'xprofile'      => '1',
		'members'       => '1',
		'notifications' => '1',
		'settings'      => '1',
		'friends'       => '1',
		'activity'      => '1',
		'groups'        => '1',
		'blogs'         => '1',
		'messages'      => '1',
	);

	$scoped = BuddyPress::scopeComponents( $full );

	foreach ( BuddyPress::SCOPED_ON as $component ) {
		expect( $scoped )->toHaveKey( $component );
		expect( $scoped[ $component ] )->toBe( '1' );
	}
} );

test( 'scopeComponents strips every forced-off component (non-vacuous — they are present in the input)', function (): void {
	$full = array(
		'xprofile' => '1',
		'members'  => '1',
		'friends'  => '1',
		'activity' => '1',
		'groups'   => '1',
		'blogs'    => '1',
		'messages' => '1',
	);

	// Guard non-vacuity: the input genuinely carries the forbidden components,
	// so a no-op `scopeComponents` (return $active) would leave them and fail.
	foreach ( BuddyPress::FORCED_OFF as $component ) {
		expect( $full )->toHaveKey( $component );
	}

	$scoped = BuddyPress::scopeComponents( $full );

	foreach ( BuddyPress::FORCED_OFF as $component ) {
		expect( $scoped )->not->toHaveKey( $component );
	}
} );

test( 'scopeComponents adds the INK scope when the active set is empty', function (): void {
	$scoped = BuddyPress::scopeComponents( array() );

	expect( array_keys( $scoped ) )->toBe( BuddyPress::SCOPED_ON );
} );

test( 'scopeComponents is idempotent — an already-scoped set is a fixpoint', function (): void {
	$once  = BuddyPress::scopeComponents( array( 'friends' => '1' ) );
	$twice = BuddyPress::scopeComponents( $once );

	expect( $twice )->toBe( $once );
} );

test( 'the scoped-on and forced-off sets are disjoint and cover the spec scope', function (): void {
	expect( array_intersect( BuddyPress::SCOPED_ON, BuddyPress::FORCED_OFF ) )->toBe( array() );

	// FR-37: Profiles (xprofile), Directory (members), Notifications on;
	// Friends, Activity, Groups, Blogs, Messaging off.
	expect( BuddyPress::SCOPED_ON )->toContain( 'xprofile' );
	expect( BuddyPress::SCOPED_ON )->toContain( 'members' );
	expect( BuddyPress::SCOPED_ON )->toContain( 'notifications' );
	expect( BuddyPress::FORCED_OFF )->toContain( 'friends' );
	expect( BuddyPress::FORCED_OFF )->toContain( 'messages' );
} );

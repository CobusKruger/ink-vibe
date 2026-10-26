<?php
/**
 * Unit tests for where a writer's bio is read from.
 *
 * Target: {@see \Ink\Social\ProfileBio}.
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Social;

use Ink\Social\ProfileBio;
use Brain\Monkey;
use Brain\Monkey\Functions;

beforeEach( function (): void {
	Monkey\setUp();
} );

afterEach( function (): void {
	Monkey\tearDown();
} );

test( 'a bio the member saved on My Profiel is used as is', function (): void {
	Functions\when( 'get_the_author_meta' )->justReturn( '  Ek skryf gedigte.  ' );
	Functions\expect( 'xprofile_get_field_data' )->never();

	expect( ProfileBio::for( 5 ) )->toBe( 'Ek skryf gedigte.' );
} );

test( 'with no saved bio, the old BuddyPress Biografie field is used', function (): void {
	Functions\when( 'get_the_author_meta' )->justReturn( '' );
	Functions\when( 'get_user_meta' )->justReturn( '' );
	Functions\expect( 'xprofile_get_field_data' )->once()->with( 'Biografie', 5 )->andReturn( '<p>Met sy   vingers &amp; sleutelbord</p>' );

	expect( ProfileBio::for( 5 ) )->toBe( 'Met sy vingers & sleutelbord' );
} );

test( 'a member who cleared their bio on purpose is not refilled from the old field', function (): void {
	Functions\when( 'get_the_author_meta' )->justReturn( '' );
	Functions\when( 'get_user_meta' )->justReturn( '1' );
	Functions\expect( 'xprofile_get_field_data' )->never();

	expect( ProfileBio::for( 5 ) )->toBe( '' );
} );

test( 'saving a bio marks it as the member\'s own', function (): void {
	Functions\expect( 'update_user_meta' )->once()->with( 5, ProfileBio::EDITED_META, '1' );

	ProfileBio::markEdited( 5 );
} );

test( 'clean turns a profile-field bio into plain text', function (): void {
	expect( ProfileBio::clean( "<p>Hallo   &amp; dag</p>\t" ) )->toBe( 'Hallo & dag' )
		->and( ProfileBio::clean( '' ) )->toBe( '' );
} );

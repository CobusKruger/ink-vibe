<?php
/**
 * Unit tests for the /lede/ public profile routing.
 *
 * Target: {@see \Ink\Social\PublicProfileRoutes}.
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Social;

use Ink\Social\PublicProfileRoutes;

test( '/lede/<name> is the public profile', function (): void {
	expect( PublicProfileRoutes::decide( 'lede/cobus' ) )->toBe( array( 'action' => 'profile', 'target' => 'cobus' ) )
		->and( PublicProfileRoutes::decide( '/lede/1981/' ) )->toBe( array( 'action' => 'profile', 'target' => '1981' ) );
} );

test( 'BuddyPress sub-pages go to the public profile, and the bare directory to Ontdek', function (): void {
	expect( PublicProfileRoutes::decide( 'lede/cobus/friends' ) )->toBe( array( 'action' => 'redirect', 'target' => '/lede/cobus/' ) )
		->and( PublicProfileRoutes::decide( 'lede/cobus/messages/compose' ) )->toBe( array( 'action' => 'redirect', 'target' => '/lede/cobus/' ) )
		->and( PublicProfileRoutes::decide( 'lede' ) )->toBe( array( 'action' => 'redirect', 'target' => '/ontdek/' ) );
} );

test( 'the old /author/ URLs redirect to /lede/', function (): void {
	expect( PublicProfileRoutes::decide( 'author/cobus' ) )->toBe( array( 'action' => 'redirect', 'target' => '/lede/cobus/' ) )
		->and( PublicProfileRoutes::decide( 'author/cobus/page/2' ) )->toBe( array( 'action' => 'redirect', 'target' => '/lede/cobus/' ) );
} );

test( 'anything else is left alone', function (): void {
	foreach ( array( '', 'skryf', 'ledegids', 'gedig/lede', 'authors' ) as $path ) {
		expect( PublicProfileRoutes::decide( $path )['action'] )->toBe( 'none' );
	}
} );

test( 'the profile path encodes the name', function (): void {
	expect( PublicProfileRoutes::pathFor( 'anne marie' ) )->toBe( '/lede/anne%20marie/' );
} );

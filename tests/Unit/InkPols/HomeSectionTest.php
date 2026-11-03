<?php
/**
 * Unit tests for the InkPols section on Tuis (WP3, N6).
 *
 * Target: {@see \Ink\InkPols\HomeSection}.
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\InkPols;

use Ink\InkPols\HomeSection;
use Brain\Monkey;
use Brain\Monkey\Functions;

beforeEach( function (): void {
	Monkey\setUp();
} );

afterEach( function (): void {
	Monkey\tearDown();
} );

test( 'the caller owns the band and container around the shared grid', function (): void {
	$html = HomeSection::wrap( '<div data-audit-id="magazine-grid"></div>' );

	expect( $html )->toContain( 'class="ink-band ink-band--poem ink-band--cream alignfull" data-audit-id="magazine"' )
		->toContain( 'data-audit-id="magazine-container"' )
		->toContain( 'magazine-grid' );
} );

test( 'an empty grid leaves no shell', function (): void {
	expect( HomeSection::wrap( '' ) )->toBe( '' );
} );

test( 'production path: no published issue hides the section', function (): void {
	Functions\when( 'get_posts' )->justReturn( array() );

	$query = new \WP_Query();
	unset( $query );

	expect( HomeSection::render() )->toBe( '' );
} );

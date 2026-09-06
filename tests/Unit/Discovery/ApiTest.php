<?php
/**
 * Unit tests for the Discovery module facade (Epic 8; My Profiel rebuild §5.4).
 *
 * Target: {@see \Ink\Discovery\Api}. The read-count + "leser"/"lesers" label
 * delegation the Bydraes-tab unified render ({@see \Ink\Social\BydraesSurface})
 * reaches Discovery through (the `deptrac.yaml` Social→Discovery edge).
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Discovery;

use Ink\Discovery\Api;
use Ink\Discovery\ReadCount;
use Brain\Monkey;
use Brain\Monkey\Functions;

beforeEach( function (): void {
	Monkey\setUp();
	Functions\when( '_n' )->alias( static fn ( string $s, string $p, int $n ): string => 1 === $n ? $s : $p );
	Functions\when( 'number_format_i18n' )->alias( static fn ( $n ): string => (string) $n );
} );

afterEach( function (): void {
	Monkey\tearDown();
} );

test( 'readCountFor reads the denormalized read-count meta for a post', function (): void {
	Functions\when( 'get_post_meta' )->alias(
		static fn ( int $id, string $key, bool $single = true ) => ReadCount::READ_COUNT_META === $key && 7 === $id ? 12 : ''
	);

	expect( Api::readCountFor( 7 ) )->toBe( 12 );
	expect( Api::readCountFor( 99 ) )->toBe( 0 );
} );

test( 'readerLabel renders the verb-less "leser"/"lesers" form, never "lesing"', function (): void {
	expect( Api::readerLabel( 1 ) )->toBe( '1 leser' );
	expect( Api::readerLabel( 0 ) )->toBe( '0 lesers' );
	expect( Api::readerLabel( 12 ) )->toBe( '12 lesers' );
	expect( Api::readerLabel( 5 ) )->not->toContain( 'lesing' );
} );

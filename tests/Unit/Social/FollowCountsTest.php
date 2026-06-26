<?php
/**
 * Unit tests for the verb-less volgeling-count label (Story 9.2, FR-38).
 *
 * Target: {@see \Ink\Social\FollowCounts}. Singular `volgeling` at 1, plural
 * `volgelinge` at 0 and 2+ — the glossary forms (NEVER "volger").
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Social;

use Ink\Social\FollowCounts;
use Brain\Monkey;
use Brain\Monkey\Functions;

beforeEach( function (): void {
	Monkey\setUp();
	// gettext plural passthrough: pick singular/plural by count, like production.
	Functions\when( '_n' )->alias(
		static fn( string $single, string $plural, int $number ): string => 1 === $number ? $single : $plural
	);
	Functions\when( 'number_format_i18n' )->alias(
		static fn( $number ): string => (string) $number
	);
} );

afterEach( function (): void {
	Monkey\tearDown();
} );

test( 'volgelingLabel uses the singular at exactly one follower', function (): void {
	expect( FollowCounts::volgelingLabel( 1 ) )->toBe( '1 volgeling' );
} );

test( 'volgelingLabel uses the plural at zero and many', function (): void {
	expect( FollowCounts::volgelingLabel( 0 ) )->toBe( '0 volgelinge' );
	expect( FollowCounts::volgelingLabel( 12 ) )->toBe( '12 volgelinge' );
} );

test( 'volgelingLabel never uses the rejected "volger" form', function (): void {
	expect( FollowCounts::volgelingLabel( 3 ) )->not->toContain( 'volger' );
} );

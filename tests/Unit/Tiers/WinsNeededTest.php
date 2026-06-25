<?php
/**
 * Unit tests for the "wins needed" subtext (Story 5.9).
 *
 * Target: {@see \Ink\Tiers\PromotionEngine::progressFor()} +
 * {@see \Ink\Tiers\Api::winsNeededSubtext()} — the threshold math + the `_n()`
 * Afrikaans subtext, hidden at Goud/Meester.
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Tiers;

use Ink\Kernel\Tier;
use Ink\Tiers\Api;
use Ink\Tiers\PromotionEngine;
use Brain\Monkey;
use Brain\Monkey\Functions;

beforeEach( function (): void {
	Monkey\setUp();
	Functions\when( '__' )->returnArg( 1 );
	// _n picks the singular when n === 1, else the plural.
	Functions\when( '_n' )->alias(
		static fn ( string $single, string $plural, int $number ): string => 1 === $number ? $single : $plural
	);
} );

afterEach( function (): void {
	Monkey\tearDown();
} );

/**
 * Stub `get_user_meta` to return the grade for the tier key and the win count for
 * the win-count key.
 */
function ink_stub_grade_and_count( string $grade, int $count ): void {
	Functions\when( 'get_user_meta' )->alias(
		static function ( int $user_id, string $key, bool $single ) use ( $grade, $count ) {
			return 'ink_writer_tier' === $key ? $grade : (string) $count;
		}
	);
}

/**
 * AC-1/AC-2: progressFor returns needed + next for Brons/Silwer, null for terminal.
 */
test( 'progressFor returns wins-needed and the next grade for auto grades', function (): void {
	expect( PromotionEngine::progressFor( Tier::Brons, 4 ) )->toBe( array( 'needed' => 1, 'next' => Tier::Silwer ) );
	expect( PromotionEngine::progressFor( Tier::Brons, 1 ) )->toBe( array( 'needed' => 4, 'next' => Tier::Silwer ) );
	expect( PromotionEngine::progressFor( Tier::Silwer, 14 ) )->toBe( array( 'needed' => 1, 'next' => Tier::Goud ) );
} );

/**
 * AC-1: progressFor is null at a terminal grade.
 */
test( 'progressFor is null for Goud and Meester', function (): void {
	expect( PromotionEngine::progressFor( Tier::Goud, 99 ) )->toBeNull();
	expect( PromotionEngine::progressFor( Tier::Meester, 0 ) )->toBeNull();
} );

/**
 * AC-1: the subtext uses the singular form when one win is needed.
 */
test( 'winsNeededSubtext uses the singular for one win needed', function (): void {
	ink_stub_grade_and_count( 'brons', 4 );

	expect( Api::winsNeededSubtext( 42 ) )->toBe( '1 top 3 uitslag nodig om Silwer te bereik' );
} );

/**
 * AC-1: the subtext uses the plural for several wins needed.
 */
test( 'winsNeededSubtext uses the plural for several wins needed', function (): void {
	ink_stub_grade_and_count( 'brons', 1 );

	expect( Api::winsNeededSubtext( 42 ) )->toBe( '4 top 3 uitslae nodig om Silwer te bereik' );
} );

/**
 * AC-1: a Silwer writer counts toward Goud.
 */
test( 'winsNeededSubtext for Silwer counts toward Goud', function (): void {
	ink_stub_grade_and_count( 'silwer', 14 );

	expect( Api::winsNeededSubtext( 42 ) )->toBe( '1 top 3 uitslag nodig om Goud te bereik' );
} );

/**
 * AC-1: hidden (null) at Goud and Meester.
 */
test( 'winsNeededSubtext is null at Goud and Meester', function (): void {
	ink_stub_grade_and_count( 'goud', 3 );
	expect( Api::winsNeededSubtext( 42 ) )->toBeNull();

	ink_stub_grade_and_count( 'meester', 0 );
	expect( Api::winsNeededSubtext( 42 ) )->toBeNull();
} );

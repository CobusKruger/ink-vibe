<?php
/**
 * Unit tests for the CadenceType value type (Story 12B.1, R9).
 *
 * Target: {@see \Ink\Kernel\CadenceType} — the `maandeliks`/`jaarliks` value set
 * for a `uitdaging`'s cadence. `fromMeta()` must default to monthly for every
 * non-exact value so a legacy/absent/junk meta never reads as annual.
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Kernel;

use Ink\Kernel\CadenceType;

test( 'default is monthly (legacy/unset rounds stay monthly)', function (): void {
	expect( CadenceType::default() )->toBe( CadenceType::Maandeliks );
} );

test( 'fromMeta maps the exact backing strings to their cases', function (): void {
	expect( CadenceType::fromMeta( 'maandeliks' ) )->toBe( CadenceType::Maandeliks );
	expect( CadenceType::fromMeta( 'jaarliks' ) )->toBe( CadenceType::Jaarliks );
} );

test( 'fromMeta defaults to monthly for empty, unknown, or non-string values', function (): void {
	expect( CadenceType::fromMeta( '' ) )->toBe( CadenceType::Maandeliks );
	expect( CadenceType::fromMeta( 'rubbish' ) )->toBe( CadenceType::Maandeliks );
	expect( CadenceType::fromMeta( 'Jaarliks' ) )->toBe( CadenceType::Maandeliks ); // case-sensitive: not the backing value
	expect( CadenceType::fromMeta( null ) )->toBe( CadenceType::Maandeliks );
	expect( CadenceType::fromMeta( 2026 ) )->toBe( CadenceType::Maandeliks );
} );

test( 'values is exactly the two cadences in declaration order', function (): void {
	expect( CadenceType::values() )->toBe( array( 'maandeliks', 'jaarliks' ) );
} );

test( 'values never drifts from the enum cases (single-source)', function (): void {
	$fromCases = array_map( static fn ( CadenceType $t ): string => $t->value, CadenceType::cases() );

	expect( CadenceType::values() )->toBe( $fromCases );
} );

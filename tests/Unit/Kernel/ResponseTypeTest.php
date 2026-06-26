<?php
/**
 * Unit tests for the ResponseType value type (Story 7.4, FR-27).
 *
 * Target: {@see \Ink\Kernel\ResponseType}. `values()` is the single source for the
 * REST `enum` arg + write-path validation; it must stay exactly the three
 * Gemeenskapsreaksie types and never drift from the enum cases.
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Kernel;

use Ink\Kernel\ResponseType;

test( 'values is exactly the three response types in declaration order', function (): void {
	expect( ResponseType::values() )->toBe( array( 'lof', 'insig', 'voorstel' ) );
} );

test( 'values never drifts from the enum cases (single-source)', function (): void {
	$fromCases = array_map( static fn ( ResponseType $t ): string => $t->value, ResponseType::cases() );

	expect( ResponseType::values() )->toBe( $fromCases );
} );

test( 'tryFrom rejects a non-response value', function (): void {
	expect( ResponseType::tryFrom( 'hartjie' ) )->toBeNull(); // a reaction, not a response type
	expect( ResponseType::tryFrom( '' ) )->toBeNull();
	expect( ResponseType::tryFrom( 'lof' ) )->toBe( ResponseType::Lof );
} );

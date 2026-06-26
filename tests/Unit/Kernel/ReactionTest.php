<?php
/**
 * Unit tests for the Reaction value type (Story 7.3, FR-26).
 *
 * Target: {@see \Ink\Kernel\Reaction}. `values()` is the single source for the
 * REST `enum` arg + write-path validation, so it must stay exactly the three
 * line-reaction marks and never drift from the enum cases.
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Kernel;

use Ink\Kernel\Reaction;

test( 'values is exactly the three reaction marks in declaration order', function (): void {
	expect( Reaction::values() )->toBe( array( 'hartjie', 'duim_op', 'wow' ) );
} );

test( 'values never drifts from the enum cases (single-source)', function (): void {
	$fromCases = array_map( static fn ( Reaction $r ): string => $r->value, Reaction::cases() );

	expect( Reaction::values() )->toBe( $fromCases );
} );

test( 'tryFrom rejects an unknown reaction value', function (): void {
	expect( Reaction::tryFrom( 'lof' ) )->toBeNull();   // a response type, not a reaction
	expect( Reaction::tryFrom( '' ) )->toBeNull();
	expect( Reaction::tryFrom( 'hartjie' ) )->toBe( Reaction::Hartjie );
} );

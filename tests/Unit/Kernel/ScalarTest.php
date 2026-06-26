<?php
/**
 * Unit tests for the shared untrusted-scalar guard/coercion helper (Epic-2 debt #2).
 *
 * Target: {@see \Ink\Kernel\Scalar} — the ONE source of truth for "is this value
 * safe to coerce, and how". Concentrates the inline `is_scalar()` idiom that the
 * Epic-5 retro flagged as recurring across Content/Accounts/Tiers/Entitlement. The
 * helper is pure PHP (no WordPress), so these tests load no Brain Monkey doubles —
 * they pin each scalar branch AND each non-scalar (array/object/null) fallback so a
 * regression that drops a guard or a floor is caught.
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Kernel;

use Ink\Kernel\Scalar;

/**
 * safe(): every PHP scalar is safe to coerce; arrays/objects/null are not.
 */
test( 'safe() accepts scalars and rejects non-scalars', function (): void {
	expect( Scalar::safe( 'string' ) )->toBeTrue();
	expect( Scalar::safe( 42 ) )->toBeTrue();
	expect( Scalar::safe( 3.14 ) )->toBeTrue();
	expect( Scalar::safe( true ) )->toBeTrue();
	expect( Scalar::safe( '' ) )->toBeTrue();

	expect( Scalar::safe( array( 'x' ) ) )->toBeFalse();
	expect( Scalar::safe( (object) array() ) )->toBeFalse();
	expect( Scalar::safe( null ) )->toBeFalse();
} );

/**
 * asString(): scalars cast verbatim (no trim/sanitise); non-scalars hit the default.
 */
test( 'asString() casts scalars and falls back on non-scalars', function (): void {
	expect( Scalar::asString( '  spaced  ' ) )->toBe( '  spaced  ' ); // no trimming
	expect( Scalar::asString( 42 ) )->toBe( '42' );
	expect( Scalar::asString( true ) )->toBe( '1' );

	expect( Scalar::asString( array( 'x' ) ) )->toBe( '' );           // default default
	expect( Scalar::asString( null, 'fallback' ) )->toBe( 'fallback' );
} );

/**
 * asInt(): scalars cast to int; non-scalars hit the (overridable) default.
 */
test( 'asInt() casts scalars and falls back on non-scalars', function (): void {
	expect( Scalar::asInt( '15' ) )->toBe( 15 );
	expect( Scalar::asInt( 7 ) )->toBe( 7 );
	expect( Scalar::asInt( -3 ) )->toBe( -3 );                 // asInt does NOT floor
	expect( Scalar::asInt( 'not-a-number' ) )->toBe( 0 );

	expect( Scalar::asInt( array( 1 ) ) )->toBe( 0 );          // default default
	expect( Scalar::asInt( null, -1 ) )->toBe( -1 );
} );

/**
 * asNonNegativeInt(): floors negatives AND non-scalars at the min (default 0).
 */
test( 'asNonNegativeInt() floors negatives and non-scalars at the min', function (): void {
	expect( Scalar::asNonNegativeInt( '15' ) )->toBe( 15 );
	expect( Scalar::asNonNegativeInt( -5 ) )->toBe( 0 );       // negative floored
	expect( Scalar::asNonNegativeInt( '-9' ) )->toBe( 0 );
	expect( Scalar::asNonNegativeInt( array() ) )->toBe( 0 );  // non-scalar floored
	expect( Scalar::asNonNegativeInt( null ) )->toBe( 0 );

	expect( Scalar::asNonNegativeInt( 2, 5 ) )->toBe( 5 );     // custom floor applies
	expect( Scalar::asNonNegativeInt( 9, 5 ) )->toBe( 9 );
} );

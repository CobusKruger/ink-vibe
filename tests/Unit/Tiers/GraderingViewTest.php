<?php
/**
 * Unit tests for the Gradering display presenter (Story 5.4).
 *
 * Target: {@see \Ink\Tiers\Api::gradingView()} + {@see \Ink\Tiers\GraderingView}
 * — the typed, presentation-ready view (label via Terms, Meester-is-special).
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Tiers;

use Ink\Kernel\Tier;
use Ink\Tiers\Api;
use Brain\Monkey;
use Brain\Monkey\Functions;

beforeEach( function (): void {
	Monkey\setUp();
	Functions\when( '__' )->returnArg( 1 ); // Terms::label returns the Afrikaans source.
} );

afterEach( function (): void {
	Monkey\tearDown();
} );

/**
 * AC-2: the view carries the typed grade + the Terms label + the css modifier.
 */
test( 'gradingView returns the grade, label and css modifier', function ( string $stored, Tier $tier, string $label ): void {
	Functions\when( 'get_user_meta' )->justReturn( $stored );

	$view = Api::gradingView( 42 );

	expect( $view->tier )->toBe( $tier );
	expect( $view->label )->toBe( $label );
	expect( $view->cssModifier() )->toBe( $tier->value );
} )->with( array(
	'brons'   => array( 'brons', Tier::Brons, 'Brons' ),
	'silwer'  => array( 'silwer', Tier::Silwer, 'Silwer' ),
	'goud'    => array( 'goud', Tier::Goud, 'Goud' ),
	'meester' => array( 'meester', Tier::Meester, 'Meester' ),
) );

/**
 * AC-1: isMeester is true only for Meester.
 */
test( 'isMeester is true only for Meester', function (): void {
	Functions\when( 'get_user_meta' )->justReturn( 'meester' );
	expect( Api::gradingView( 1 )->isMeester )->toBeTrue();

	Functions\when( 'get_user_meta' )->justReturn( 'goud' );
	expect( Api::gradingView( 1 )->isMeester )->toBeFalse();
} );

/**
 * AC-1: the colour token is `primary` for Meester (#EA4015, not danger) and the
 * grade value for every other grade.
 */
test( 'colorToken is primary for Meester and the grade value otherwise', function (): void {
	Functions\when( 'get_user_meta' )->justReturn( 'meester' );
	expect( Api::gradingView( 1 )->colorToken() )->toBe( 'primary' );

	Functions\when( 'get_user_meta' )->justReturn( 'goud' );
	expect( Api::gradingView( 1 )->colorToken() )->toBe( 'goud' );
} );

/**
 * AC-2: an unset writer defaults to Brons (via forUser).
 */
test( 'gradingView defaults an unset writer to Brons', function (): void {
	Functions\when( 'get_user_meta' )->justReturn( '' );

	expect( Api::gradingView( 99 )->tier )->toBe( Tier::Brons );
} );

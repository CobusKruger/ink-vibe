<?php
/**
 * Unit tests for the profile's win accomplishments.
 *
 * Target: {@see \Ink\Challenges\ProfileAccomplishments::item()}.
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Challenges;

use Brain\Monkey;
use Ink\Challenges\ProfileAccomplishments;

beforeEach( function (): void {
	Monkey\setUp();
	// The label goes through gettext; return the text as is.
	Monkey\Functions\when( '__' )->returnArg();
} );

afterEach( function (): void {
	Monkey\tearDown();
} );

test( 'a win reads "Nde wen" with the round title, dated by the round', function (): void {
	$item = ProfileAccomplishments::item( array( 'round_date' => '2024-03-02 08:00:00' ), 3, 'Maart 2024' );

	expect( $item )->not->toBeNull();
	expect( $item['label'] )->toBe( '3de wen' );
	expect( $item['detail'] )->toBe( 'Maart 2024' );
	expect( $item['at'] )->toBe( strtotime( '2024-03-02 08:00:00 UTC' ) );
} );

test( 'a win with no known count shows nothing rather than a wrong label', function (): void {
	expect( ProfileAccomplishments::item( array( 'round_date' => '2024-03-02 08:00:00' ), 0, 'Maart 2024' ) )->toBeNull();
} );

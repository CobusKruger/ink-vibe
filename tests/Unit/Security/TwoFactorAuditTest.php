<?php
/**
 * Unit tests for the staff 2FA coverage audit (Story 18.3, §14.16).
 *
 * Target: {@see \Ink\Security\TwoFactorAudit::staffMissingTwoFactor()} — the pure
 * coverage check over resolved staff rows. Brain-Monkey, no WordPress.
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Security;

use Ink\Security\TwoFactorAudit;
use Brain\Monkey;

beforeEach( function (): void {
	Monkey\setUp();
} );

afterEach( function (): void {
	Monkey\tearDown();
} );

test( 'staffMissingTwoFactor returns only the staff lacking a second factor', function (): void {
	$missing = TwoFactorAudit::staffMissingTwoFactor(
		array(
			array( 'id' => 1, 'login' => 'admin', 'has_2fa' => true ),
			array( 'id' => 2, 'login' => 'redakteur', 'has_2fa' => false ),
			array( 'id' => 3, 'login' => 'sub', 'has_2fa' => true ),
		)
	);

	expect( $missing )->toHaveCount( 1 );
	expect( $missing[0]['login'] )->toBe( 'redakteur' );
} );

test( 'staffMissingTwoFactor returns empty when every staff member has 2FA', function (): void {
	$missing = TwoFactorAudit::staffMissingTwoFactor(
		array(
			array( 'id' => 1, 'login' => 'admin', 'has_2fa' => true ),
			array( 'id' => 2, 'login' => 'redakteur', 'has_2fa' => true ),
		)
	);

	expect( $missing )->toBe( array() );
} );

test( 'staffMissingTwoFactor treats a missing has_2fa flag as NOT covered (fail-safe)', function (): void {
	$missing = TwoFactorAudit::staffMissingTwoFactor(
		array(
			array( 'id' => 1, 'login' => 'admin' ), // no has_2fa key.
		)
	);

	expect( $missing )->toHaveCount( 1 );
	expect( $missing[0]['login'] )->toBe( 'admin' );
} );

test( 'staffMissingTwoFactor re-indexes the result (list, not preserving keys)', function (): void {
	$missing = TwoFactorAudit::staffMissingTwoFactor(
		array(
			array( 'id' => 1, 'login' => 'a', 'has_2fa' => true ),
			array( 'id' => 2, 'login' => 'b', 'has_2fa' => false ),
		)
	);

	expect( array_keys( $missing ) )->toBe( array( 0 ) );
} );

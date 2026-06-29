<?php
/**
 * Unit tests for the registration anti-spam guard (Story 18.10, FR-3a/R6).
 *
 * Target: {@see \Ink\Accounts\RegistrationGuard} — the pure evaluate() decision
 * (honeypot → timing → challenge → rate, in order) + the guard() orchestration via
 * overridable seams. Brain-Monkey, no WordPress.
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Accounts;

use Ink\Accounts\RegistrationGuard;
use Brain\Monkey;
use Brain\Monkey\Functions;

beforeEach( function (): void {
	Monkey\setUp();
} );

afterEach( function (): void {
	Monkey\tearDown();
} );

// --- evaluate(): the pure decision ---

test( 'evaluate passes a clean human registration', function (): void {
	$reason = RegistrationGuard::evaluate(
		array(
			'honeypot_filled'  => false,
			'elapsed_seconds'  => 12,
			'challenge_passed' => true,
			'attempts'         => 0,
		)
	);

	expect( $reason )->toBeNull();
} );

test( 'evaluate blocks a filled honeypot first', function (): void {
	$reason = RegistrationGuard::evaluate(
		array(
			'honeypot_filled'  => true,
			'elapsed_seconds'  => 0, // also too fast, but honeypot wins (cheapest/certain).
			'challenge_passed' => false,
			'attempts'         => 99,
		)
	);

	expect( $reason )->toBe( RegistrationGuard::REASON_HONEYPOT );
} );

test( 'evaluate blocks a too-fast submission', function (): void {
	$reason = RegistrationGuard::evaluate(
		array(
			'honeypot_filled'  => false,
			'elapsed_seconds'  => 1, // < MIN_SECONDS
			'challenge_passed' => true,
			'attempts'         => 0,
		)
	);

	expect( $reason )->toBe( RegistrationGuard::REASON_TOO_FAST );
} );

test( 'evaluate blocks a failed challenge', function (): void {
	$reason = RegistrationGuard::evaluate(
		array(
			'honeypot_filled'  => false,
			'elapsed_seconds'  => 10,
			'challenge_passed' => false,
			'attempts'         => 0,
		)
	);

	expect( $reason )->toBe( RegistrationGuard::REASON_CHALLENGE );
} );

test( 'evaluate blocks when the rate limit is reached', function (): void {
	$reason = RegistrationGuard::evaluate(
		array(
			'honeypot_filled'  => false,
			'elapsed_seconds'  => 10,
			'challenge_passed' => true,
			'attempts'         => RegistrationGuard::MAX_ATTEMPTS,
		)
	);

	expect( $reason )->toBe( RegistrationGuard::REASON_RATE );
} );

test( 'evaluate allows exactly up to the limit (boundary)', function (): void {
	$reason = RegistrationGuard::evaluate(
		array(
			'honeypot_filled'  => false,
			'elapsed_seconds'  => 10,
			'challenge_passed' => true,
			'attempts'         => RegistrationGuard::MAX_ATTEMPTS - 1,
		)
	);

	expect( $reason )->toBeNull();
} );

test( 'evaluate defaults are fail-open on missing timing/challenge but fail-safe on honeypot', function (): void {
	// Empty signals: no honeypot, no timing (defaults to PHP_INT_MAX elapsed → not fast),
	// challenge defaults pass, attempts 0 → passes (so a missing field never falsely blocks).
	expect( RegistrationGuard::evaluate( array() ) )->toBeNull();
} );

// --- messageFor() ---

test( 'messageFor gives a rate-specific Afrikaans message and a generic fallback', function (): void {
	Functions\when( '__' )->returnArg( 1 );

	expect( RegistrationGuard::messageFor( RegistrationGuard::REASON_RATE ) )
		->toContain( 'Te veel registrasiepogings' );
	expect( RegistrationGuard::messageFor( RegistrationGuard::REASON_HONEYPOT ) )
		->toContain( 'kon nie jou registrasie verifieer' );
} );

// --- guard(): orchestration via seams ---

test( 'guard adds a WP_Error and fires the blocked-attempt action for a bot', function (): void {
	Functions\when( '__' )->returnArg( 1 );
	Functions\when( 'apply_filters' )->returnArg( 2 );

	$errors = \Mockery::mock( '\WP_Error' );
	$errors->shouldReceive( 'add' )->once()->with( RegistrationGuard::REASON_HONEYPOT, \Mockery::type( 'string' ) );

	Functions\expect( 'do_action' )
		->once()
		->with( RegistrationGuard::HOOK_BLOCKED, RegistrationGuard::REASON_HONEYPOT, \Mockery::type( 'string' ) );

	$guard = new class() extends RegistrationGuard {
		protected function honeypotValue(): string {
			return 'i-am-a-bot';
		}
		protected function renderedAt(): int {
			return 100;
		}
		protected function now(): int {
			return 130; // 30s elapsed (human).
		}
		protected function challengePassed(): bool {
			return true;
		}
		protected function requesterIp(): string {
			return '203.0.113.7';
		}
		protected function attemptCount(): int {
			return 0;
		}
		protected function recordAttempt(): void {
		}
	};

	$guard->guard( $errors );
} );

test( 'guard returns the errors untouched for a clean human registration', function (): void {
	Functions\when( '__' )->returnArg( 1 );
	Functions\when( 'apply_filters' )->returnArg( 2 );

	$errors = \Mockery::mock( '\WP_Error' );
	$errors->shouldReceive( 'add' )->never();
	Functions\expect( 'do_action' )->never();

	$guard = new class() extends RegistrationGuard {
		protected function honeypotValue(): string {
			return '';
		}
		protected function renderedAt(): int {
			return 100;
		}
		protected function now(): int {
			return 140; // 40s elapsed.
		}
		protected function challengePassed(): bool {
			return true;
		}
		protected function requesterIp(): string {
			return '203.0.113.7';
		}
		protected function attemptCount(): int {
			return 0;
		}
		protected function recordAttempt(): void {
		}
	};

	expect( $guard->guard( $errors ) )->toBe( $errors );
} );

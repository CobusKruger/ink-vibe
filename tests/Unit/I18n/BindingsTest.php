<?php
/**
 * Unit tests for the `ink/term` Block Bindings source (AD-10; Story 2.0 + the
 * Story 17.4 misconfigured-binding guard).
 *
 * Target: {@see \Ink\I18n\Bindings::resolve}. A valid binding key resolves to its
 * glossary label; a missing/unregistered key renders NOTHING (no raw-key leak to a
 * visitor) and is surfaced in dev/CI via `_doing_it_wrong`.
 *
 * Harness: Brain Monkey; `__()` is an identity passthrough so the Afrikaans source
 * literal in the registry is what `Terms::label()` returns.
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\I18n;

use Ink\I18n\Bindings;
use Brain\Monkey;
use Brain\Monkey\Functions;

beforeEach( function (): void {
	Monkey\setUp();
	Functions\when( '__' )->returnArg( 1 );
	Functions\when( 'esc_html' )->returnArg( 1 );  // guard-message escaping (Story 17.4)
} );

afterEach( function (): void {
	Monkey\tearDown();
} );

test( 'resolve returns the glossary label for a valid binding key', function (): void {
	// Non-vacuous: a real registered key really resolves to its Afrikaans label.
	expect( Bindings::resolve( array( 'key' => 'gradering' ) ) )->toBe( 'Gradering' );
} );

test( 'resolve renders nothing (not the raw key) for an unregistered key', function (): void {
	ink_reset_guard_spies();

	$out = Bindings::resolve( array( 'key' => 'no_such_concept' ) );

	expect( $out )->toBe( '' );                  // NOT the raw key — no leak to a visitor.
	expect( $out )->not->toBe( 'no_such_concept' );
	expect( $GLOBALS['ink_test_doing_it_wrong'] )->toHaveCount( 1 );
} );

test( 'resolve renders nothing for a missing key arg', function (): void {
	ink_reset_guard_spies();

	expect( Bindings::resolve( array() ) )->toBe( '' );
	expect( $GLOBALS['ink_test_doing_it_wrong'] )->toHaveCount( 1 );
} );

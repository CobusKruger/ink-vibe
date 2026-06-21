<?php
/**
 * Unit tests for the terminology label registry (AD-10).
 *
 * Target: {@see \Ink\I18n\Terms} (Story 2.0).
 *
 * Authored ready-to-run; the runner (Pest function API + Brain Monkey, the
 * `tests/bootstrap.php` lifecycle, `phpunit.xml` Unit testsuite) is the 1.11
 * scaffold built out in the 18.8 CI buildout. Mirrors the 1.10 AdminLanguageTest
 * precedent.
 *
 * Harness assumptions (provided by tests/bootstrap.php):
 *  - Brain\Monkey is set up/torn down per test.
 *  - `__()` is stubbed as an identity passthrough (returns its first argument),
 *    so the Afrikaans SOURCE literal in the registry is what `label()` returns —
 *    matching production, where `ink-core` ships no English `.mo` and gettext
 *    returns the source string unchanged.
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\I18n;

use Ink\I18n\Terms;
use Brain\Monkey;
use Brain\Monkey\Functions;

beforeEach( function (): void {
	Monkey\setUp();
	// gettext passthrough: the registry's Afrikaans source literal is returned.
	Functions\when( '__' )->returnArg( 1 );
} );

afterEach( function (): void {
	Monkey\tearDown();
} );

/**
 * AC-1: a core concept key resolves to its glossary UI-term label.
 */
test( 'label returns the Afrikaans label for a known concept key', function (): void {
	expect( Terms::label( 'membership' ) )->toBe( 'Lidmaatskap' );
	expect( Terms::label( 'gradering' ) )->toBe( 'Gradering' );
	expect( Terms::label( 'bydrae' ) )->toBe( 'Bydrae' );
} );

/**
 * AC-1: CPT singular + plural keys resolve to the glossary meervoud forms.
 */
test( 'label returns singular and plural CPT labels', function (): void {
	expect( Terms::label( 'storie' ) )->toBe( 'Storie' );
	expect( Terms::label( 'storie_plural' ) )->toBe( 'Stories' );
	expect( Terms::label( 'gedig_plural' ) )->toBe( 'Gedigte' );
	expect( Terms::label( 'borg_plural' ) )->toBe( 'Borge' );
	expect( Terms::label( 'inkpols_uitgawe' ) )->toBe( 'Uitgawe' );
} );

/**
 * AC-1: taxonomy keys resolve to their UI-term labels.
 */
test( 'label returns taxonomy labels', function (): void {
	expect( Terms::label( 'vaardigheid' ) )->toBe( 'Vaardigheidsarea' );
	expect( Terms::label( 'ster_gradering' ) )->toBe( 'Ster gradering' );
} );

/**
 * AC-2: has() reports membership of the registry.
 */
test( 'has reports whether a key is registered', function (): void {
	expect( Terms::has( 'membership' ) )->toBeTrue();
	expect( Terms::has( 'gedig' ) )->toBeTrue();
	expect( Terms::has( 'definitely_not_a_key' ) )->toBeFalse();
} );

/**
 * AC-2: an unknown key fails safe — it returns the key itself (no fatal, no
 * English string), so a missing concept degrades to a visible developer signal
 * rather than a crash.
 */
test( 'label fails safe for an unknown key by returning the key', function (): void {
	expect( Terms::label( 'no_such_concept' ) )->toBe( 'no_such_concept' );
} );

/**
 * AC-2: all() exposes the full registry (the leak-scan inspection surface) and
 * every value is a non-empty string label.
 */
test( 'all returns the full registry as the inspectable surface', function (): void {
	$all = Terms::all();

	expect( $all )->toBeArray();
	expect( $all )->toHaveKey( 'membership' );
	expect( $all )->toHaveKey( 'ster_gradering_plural' );

	foreach ( $all as $key => $value ) {
		expect( $value )->toBeString();
		expect( $value )->not->toBe( '' );
	}
} );

<?php
/**
 * Unit tests for the INK editorial capability mapping (AD-6, Story 3.3).
 *
 * Target: {@see \Ink\Kernel\Capabilities} — the deferred Epic-2 role/cap work
 * that Story 3.3 activates. Asserts the four custom caps are GRANTED to real
 * roles (administrator + editor / redakteur) at activation, and revoked on
 * deactivation — the inverse of the "deny-everyone stub" bug class: a cap that
 * gates a live path is granted to a real role here, never to nobody.
 *
 * Brain Monkey, no WordPress/DB. `get_role` is aliased to return a capturing
 * `WP_Role` double so the `add_cap`/`remove_cap` mutations can be asserted
 * without WordPress loaded.
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Kernel;

use Ink\Kernel\Capabilities;
use Brain\Monkey;
use Brain\Monkey\Functions;

/**
 * A minimal capturing WP_Role double: records every add/remove so the grant can
 * be asserted as a set, mirroring WordPress's role-store semantics.
 */
function ink_make_role_double(): object {
	return new class() {
		/** @var array<string, bool> */
		public array $caps = array();

		public function add_cap( string $cap ): void { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid
			$this->caps[ $cap ] = true;
		}

		public function remove_cap( string $cap ): void { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid
			unset( $this->caps[ $cap ] );
		}
	};
}

beforeEach( function (): void {
	Monkey\setUp();
} );

afterEach( function (): void {
	Monkey\tearDown();
} );

/**
 * The registry names exactly the four `ink_`-prefixed editorial caps.
 */
test( 'all() exposes exactly the four prefixed editorial caps', function (): void {
	expect( Capabilities::all() )->toBe( array(
		'ink_manage_tiers',
		'ink_manage_challenges',
		'ink_manage_sponsors',
		'ink_moderate',
	) );
} );

/**
 * AC-5: grantToEditor() grants every custom cap to BOTH the editor (redakteur)
 * and the administrator role — so a custom-cap gate never locks out an admin.
 */
test( 'grantToEditor grants every custom cap to editor and administrator', function (): void {
	$roles = array(
		'editor'        => ink_make_role_double(),
		'administrator' => ink_make_role_double(),
	);

	Functions\when( 'get_role' )->alias(
		fn ( string $name ) => $roles[ $name ] ?? null
	);

	Capabilities::grantToEditor();

	foreach ( $roles as $role ) {
		foreach ( Capabilities::all() as $cap ) {
			expect( $role->caps )->toHaveKey( $cap );
		}
	}
} );

/**
 * AC-5 deny-everyone guard: the moderate cap (which gates the taxonomy
 * term-management live path) IS granted to a real role — not left ungranted.
 */
test( 'the moderate cap that gates term-management is granted to editor', function (): void {
	$editor = ink_make_role_double();

	Functions\when( 'get_role' )->alias(
		fn ( string $name ) => 'editor' === $name ? $editor : null
	);

	Capabilities::grantToEditor();

	expect( $editor->caps )->toHaveKey( Capabilities::MODERATE );
} );

/**
 * AC-5: a missing role is skipped, never fatals (fail-safe activation).
 */
test( 'grantToEditor is fail-safe when a role is absent', function (): void {
	Functions\when( 'get_role' )->justReturn( null );

	Capabilities::grantToEditor(); // Must not throw.

	expect( true )->toBeTrue();
} );

/**
 * AC-5: deactivation revokes exactly what activation granted (no orphaned caps).
 */
test( 'revokeFromEditor removes every custom cap it granted', function (): void {
	$editor = ink_make_role_double();

	Functions\when( 'get_role' )->alias(
		fn ( string $name ) => 'editor' === $name ? $editor : null
	);

	Capabilities::grantToEditor();
	expect( $editor->caps )->not->toBeEmpty();

	Capabilities::revokeFromEditor();

	foreach ( Capabilities::all() as $cap ) {
		expect( $editor->caps )->not->toHaveKey( $cap );
	}
} );

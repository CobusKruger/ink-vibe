<?php
/**
 * Unit tests for the INK taxonomy registrar (AD-1, AD-5, AD-6, AD-10).
 *
 * Target: {@see \Ink\Content\Taxonomies} and the {@see \Ink\Content\Api} facade
 * (Story 2.2).
 *
 * Authored ready-to-run; the runner (Pest function API + Brain Monkey, the
 * `tests/bootstrap.php` lifecycle, `phpunit.xml` Unit testsuite) is the 1.11
 * scaffold built out in the 18.8 CI buildout. Mirrors the 2.1 PostTypesTest
 * precedent.
 *
 * Harness assumptions (provided by tests/bootstrap.php):
 *  - Brain\Monkey is set up/torn down per test.
 *  - `__()` is stubbed as an identity passthrough (returns its first argument),
 *    so the Afrikaans SOURCE literal in the Terms registry is what labels carry.
 *    `sprintf()` is the native PHP function, so composed admin chrome interpolates
 *    the registry noun for real.
 *  - `register_taxonomy()` is aliased to capture every (slug, object_types, args)
 *    call so the definitions can be asserted without WordPress loaded.
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Content;

use Ink\Content\Api;
use Ink\Content\PostTypes;
use Ink\Content\Taxonomies;
use Ink\Kernel\Capabilities;
use Brain\Monkey;
use Brain\Monkey\Functions;

/**
 * Register every taxonomy with `register_taxonomy` captured, returning the
 * slug => [object_types, args] map the registrar produced.
 *
 * @return array<string, array{object_types: array<int, string>, args: array<string, mixed>}>
 */
function ink_capture_registered_taxonomies(): array {
	$captured = array();

	Functions\when( 'register_taxonomy' )->alias(
		function ( string $slug, $object_types, array $args ) use ( &$captured ): void {
			$captured[ $slug ] = array(
				'object_types' => (array) $object_types,
				'args'         => $args,
			);
		}
	);

	( new Taxonomies() )->register();

	return $captured;
}

beforeEach( function (): void {
	Monkey\setUp();
	// gettext passthrough: the registry's Afrikaans source literal is returned.
	Functions\when( '__' )->returnArg( 1 );
} );

afterEach( function (): void {
	Monkey\tearDown();
} );

/**
 * AC-1: the registrar declares exactly the four migration-load-bearing slugs,
 * single-sourced through `Taxonomies::all()`.
 */
test( 'all() exposes exactly the four INK taxonomy slugs with exact code IDs', function (): void {
	expect( Taxonomies::all() )->toBe( array(
		'genre',
		'vaardigheid',
		'uitdagingsrondte',
		'ster_gradering',
	) );
} );

/**
 * AC-1: the class constants carry the exact migration-load-bearing code IDs.
 */
test( 'the slug constants are the exact migration-load-bearing code IDs', function (): void {
	expect( Taxonomies::GENRE )->toBe( 'genre' );
	expect( Taxonomies::VAARDIGHEID )->toBe( 'vaardigheid' );
	expect( Taxonomies::UITDAGINGSRONDTE )->toBe( 'uitdagingsrondte' );
	expect( Taxonomies::STER_GRADERING )->toBe( 'ster_gradering' );
} );

/**
 * AC-1: `register()` registers exactly the four taxonomies, once each.
 */
test( 'register() registers exactly the four expected taxonomies', function (): void {
	$registered = ink_capture_registered_taxonomies();

	expect( array_keys( $registered ) )->toBe( Taxonomies::all() );
	expect( $registered )->toHaveCount( 4 );
} );

/**
 * AC-2: `genre` and `vaardigheid` are shared across the bydrae CPTs AND
 * `opleiding_artikel` (training) — the auto-surfacing overlap.
 */
test( 'genre and vaardigheid are shared across bydraes and training', function (): void {
	$registered = ink_capture_registered_taxonomies();

	foreach ( array( 'genre', 'vaardigheid' ) as $shared ) {
		$object_types = $registered[ $shared ]['object_types'];

		foreach ( PostTypes::bydraeTypes() as $bydrae ) {
			expect( $object_types )->toContain( $bydrae );
		}
		expect( $object_types )->toContain( PostTypes::OPLEIDING_ARTIKEL );
	}
} );

/**
 * AC-2: `object_types` are sourced from PostTypes — every target is a real
 * registered CPT slug (no stray/typo'd literals).
 */
test( 'every taxonomy object_type is a registered CPT slug', function (): void {
	$registered = ink_capture_registered_taxonomies();
	$cpts       = PostTypes::all();

	foreach ( $registered as $slug => $entry ) {
		expect( $entry['object_types'] )->not->toBeEmpty();
		foreach ( $entry['object_types'] as $object_type ) {
			expect( $cpts )->toContain( $object_type );
		}
	}
} );

/**
 * AC-4: every taxonomy is block-editor / REST ready, controlled (hierarchical)
 * and surfaced in the admin column.
 */
test( 'every taxonomy is show_in_rest, hierarchical, show_admin_column', function (): void {
	$registered = ink_capture_registered_taxonomies();

	foreach ( $registered as $slug => $entry ) {
		expect( $entry['args']['show_in_rest'] )->toBeTrue();
		expect( $entry['args']['hierarchical'] )->toBeTrue();
		expect( $entry['args']['show_admin_column'] )->toBeTrue();
		expect( $entry['args']['public'] )->toBeTrue();
	}
} );

/**
 * AC-3: labels resolve through the Terms registry — the `genre` name is the
 * registry plural ("Genres"), `vaardigheid` singular the registry singular.
 */
test( 'labels are sourced from the Terms registry', function (): void {
	$registered = ink_capture_registered_taxonomies();

	expect( $registered['genre']['args']['labels']['name'] )->toBe( 'Genres' );
	expect( $registered['genre']['args']['labels']['singular_name'] )->toBe( 'Genre' );
	expect( $registered['vaardigheid']['args']['labels']['singular_name'] )->toBe( 'Vaardigheidsarea' );
	expect( $registered['ster_gradering']['args']['labels']['name'] )->toBe( 'Ster graderings' );
} );

/**
 * AC-3: composed admin chrome interpolates the registry noun via sprintf — the
 * scaffolding verb is generic Afrikaans, the noun comes from the registry.
 */
test( 'composed admin labels interpolate the registry noun', function (): void {
	$registered = ink_capture_registered_taxonomies();

	expect( $registered['genre']['args']['labels']['add_new_item'] )->toBe( 'Voeg nuwe Genre by' );
	expect( $registered['genre']['args']['labels']['search_items'] )->toBe( 'Soek Genres' );
	expect( $registered['vaardigheid']['args']['labels']['edit_item'] )->toBe( 'Wysig Vaardigheidsarea' );
} );

/**
 * Story 3.3 (2.2 gap): controlled-vocabulary term MANAGEMENT (add/edit/delete)
 * is restricted to the staff `ink_moderate` cap on every taxonomy, so a gratis
 * lid / member cannot fork the controlled vocabulary. `assign_terms` stays broad
 * (`edit_posts`) — picking an existing term while authoring is not a vocabulary
 * mutation.
 */
test( 'term management is staff-only (ink_moderate); assign stays broad', function (): void {
	$registered = ink_capture_registered_taxonomies();

	foreach ( $registered as $slug => $entry ) {
		$caps = $entry['args']['capabilities'];

		expect( $caps['manage_terms'] )->toBe( Capabilities::MODERATE );
		expect( $caps['edit_terms'] )->toBe( Capabilities::MODERATE );
		expect( $caps['delete_terms'] )->toBe( Capabilities::MODERATE );
		expect( $caps['manage_terms'] )->not->toBe( 'manage_categories' );
		expect( $caps['assign_terms'] )->toBe( 'edit_posts' );
	}
} );

/**
 * AC-4: the Content facade exposes the taxonomy slug surface, delegating to
 * Taxonomies.
 */
test( 'Api facade exposes the taxonomy slug surface', function (): void {
	expect( Api::taxonomies() )->toBe( Taxonomies::all() );
} );

/**
 * Story 8.1 (closes the 2.2 single-source gap): every taxonomy's rewrite slug is
 * DERIVED from its code-id constant (underscore → hyphen), not a hand-typed
 * literal. `ster_gradering` resolves to `ster-gradering` (the public URL is
 * preserved) and the other three are identity. Non-vacuous: a regression to the
 * old `'ster-gradering'` literal — or to the underscore code id leaking into the
 * URL — fails this assertion.
 */
test( 'rewrite slugs are derived from the code-id constant (single source)', function (): void {
	$registered = ink_capture_registered_taxonomies();

	$expected = array(
		'genre'            => 'genre',
		'vaardigheid'      => 'vaardigheid',
		'uitdagingsrondte' => 'uitdagingsrondte',
		'ster_gradering'   => 'ster-gradering', // underscore → hyphen, URL preserved.
	);

	foreach ( $expected as $slug => $rewrite ) {
		expect( $registered[ $slug ]['args']['rewrite']['slug'] )->toBe( $rewrite );
	}

	// The ster_gradering URL must NOT leak the underscore code id.
	expect( $registered['ster_gradering']['args']['rewrite']['slug'] )->not->toContain( '_' );
} );

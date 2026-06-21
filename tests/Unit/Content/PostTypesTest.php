<?php
/**
 * Unit tests for the INK custom post-type registrar (AD-1, AD-5, AD-6, AD-10).
 *
 * Target: {@see \Ink\Content\PostTypes} and the {@see \Ink\Content\Api} facade
 * (Story 2.1).
 *
 * Authored ready-to-run; the runner (Pest function API + Brain Monkey, the
 * `tests/bootstrap.php` lifecycle, `phpunit.xml` Unit testsuite) is the 1.11
 * scaffold built out in the 18.8 CI buildout. Mirrors the 2.0 TermsTest
 * precedent.
 *
 * Harness assumptions (provided by tests/bootstrap.php):
 *  - Brain\Monkey is set up/torn down per test.
 *  - `__()` is stubbed as an identity passthrough (returns its first argument),
 *    so the Afrikaans SOURCE literal in the Terms registry is what labels carry
 *    — matching production (ink-core ships no English `.mo`). `sprintf()` is the
 *    native PHP function (Brain Monkey does not intercept it), so composed admin
 *    chrome interpolates the registry noun for real.
 *  - `register_post_type()` is aliased to capture every (slug, args) call so the
 *    definitions can be asserted without WordPress loaded.
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Content;

use Ink\Content\Api;
use Ink\Content\PostTypes;
use Brain\Monkey;
use Brain\Monkey\Functions;

/**
 * Register every CPT with `register_post_type` captured, returning the
 * slug => args map the registrar produced.
 *
 * @return array<string, array<string, mixed>>
 */
function ink_capture_registered_post_types(): array {
	$captured = array();

	Functions\when( 'register_post_type' )->alias(
		function ( string $slug, array $args ) use ( &$captured ): void {
			$captured[ $slug ] = $args;
		}
	);

	( new PostTypes() )->register();

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
 * AC-1: the registrar declares exactly the nine migration-load-bearing slugs as
 * class constants, single-sourced through `PostTypes::all()`.
 */
test( 'all() exposes exactly the nine INK CPT slugs with exact code IDs', function (): void {
	expect( PostTypes::all() )->toBe( array(
		'gedig',
		'storie',
		'artikel',
		'skryfwerk',
		'biblioteek_item',
		'opleiding_artikel',
		'uitdaging',
		'inkpols_uitgawe',
		'borg',
	) );
} );

/**
 * AC-1: the class constants carry the exact migration-load-bearing code IDs
 * (old `verhaal` → `storie`, `inkpols` → `inkpols_uitgawe`).
 */
test( 'the slug constants are the exact migration-load-bearing code IDs', function (): void {
	expect( PostTypes::GEDIG )->toBe( 'gedig' );
	expect( PostTypes::STORIE )->toBe( 'storie' );
	expect( PostTypes::ARTIKEL )->toBe( 'artikel' );
	expect( PostTypes::SKRYFWERK )->toBe( 'skryfwerk' );
	expect( PostTypes::BIBLIOTEEK_ITEM )->toBe( 'biblioteek_item' );
	expect( PostTypes::OPLEIDING_ARTIKEL )->toBe( 'opleiding_artikel' );
	expect( PostTypes::UITDAGING )->toBe( 'uitdaging' );
	expect( PostTypes::INKPOLS_UITGAWE )->toBe( 'inkpols_uitgawe' );
	expect( PostTypes::BORG )->toBe( 'borg' );
} );

/**
 * AC-1: `register()` registers exactly the nine CPTs, once each.
 */
test( 'register() registers exactly the nine expected post types', function (): void {
	$registered = ink_capture_registered_post_types();

	expect( array_keys( $registered ) )->toBe( PostTypes::all() );
	expect( $registered )->toHaveCount( 9 );
} );

/**
 * AC-1: the library/training CPTs keep their documented URL prefixes
 * (`/biblioteek/`, `/opleiding/`); `borg` has no public archive.
 */
test( 'archive prefixes follow the migration plan', function (): void {
	$registered = ink_capture_registered_post_types();

	expect( $registered['biblioteek_item']['has_archive'] )->toBe( 'biblioteek' );
	expect( $registered['biblioteek_item']['rewrite']['slug'] )->toBe( 'biblioteek' );
	expect( $registered['opleiding_artikel']['has_archive'] )->toBe( 'opleiding' );
	expect( $registered['opleiding_artikel']['rewrite']['slug'] )->toBe( 'opleiding' );
	expect( $registered['borg']['has_archive'] )->toBeFalse();
} );

/**
 * AC-4: every registration is block-editor / REST ready and strict.
 */
test( 'every CPT is registered show_in_rest with map_meta_cap and a menu icon', function (): void {
	$registered = ink_capture_registered_post_types();

	foreach ( $registered as $slug => $args ) {
		expect( $args['show_in_rest'] )->toBeTrue();
		expect( $args['map_meta_cap'] )->toBeTrue();
		expect( $args['menu_icon'] )->toBeString()->not->toBe( '' );
		expect( $args['supports'] )->toBeArray()->not->toBeEmpty();
	}
} );

/**
 * AC-2: labels resolve through the Terms registry — the `storie` name is the
 * registry plural ("Stories"), the singular_name the registry singular.
 */
test( 'labels are sourced from the Terms registry', function (): void {
	$registered = ink_capture_registered_post_types();

	expect( $registered['storie']['labels']['name'] )->toBe( 'Stories' );
	expect( $registered['storie']['labels']['singular_name'] )->toBe( 'Storie' );
	expect( $registered['gedig']['labels']['name'] )->toBe( 'Gedigte' );
	expect( $registered['borg']['labels']['singular_name'] )->toBe( 'Borg' );
} );

/**
 * AC-2: composed admin chrome interpolates the registry noun via sprintf — the
 * scaffolding verb is generic Afrikaans, the noun comes from the registry.
 */
test( 'composed admin labels interpolate the registry noun', function (): void {
	$registered = ink_capture_registered_post_types();

	expect( $registered['gedig']['labels']['add_new_item'] )->toBe( 'Voeg nuwe Gedig by' );
	expect( $registered['gedig']['labels']['edit_item'] )->toBe( 'Wysig Gedig' );
	expect( $registered['storie']['labels']['search_items'] )->toBe( 'Soek Stories' );
} );

/**
 * AC-3: the Content facade exposes the slug surface — all nine slugs and the
 * four member-submission ("bydrae") CPTs — delegating to PostTypes.
 */
test( 'Api facade exposes the slug surface', function (): void {
	expect( Api::all() )->toBe( PostTypes::all() );
	expect( Api::bydraeTypes() )->toBe( array( 'gedig', 'storie', 'artikel', 'skryfwerk' ) );
} );

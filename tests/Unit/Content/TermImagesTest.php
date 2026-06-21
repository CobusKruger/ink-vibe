<?php
/**
 * Unit tests for the native term-image capability (AD-1, AD-6).
 *
 * Target: {@see \Ink\Content\TermImages} and the {@see \Ink\Content\Api} facade
 * (Story 2.5).
 *
 * Authored ready-to-run; the runner (Pest function API + Brain Monkey, the
 * `tests/bootstrap.php` lifecycle, `phpunit.xml` Unit testsuite) is the 1.11
 * scaffold built out in the 18.8 CI buildout. Mirrors the 2.1–2.4 precedents.
 *
 * Harness assumptions (provided by tests/bootstrap.php):
 *  - Brain\Monkey is set up/torn down per test.
 *  - `register_term_meta()` is aliased to capture every (taxonomy, key, args).
 *  - `add_action`/`__`/`absint`/`get_term_meta` are stubbed so the registration
 *    and read paths run without WordPress.
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Content;

use Ink\Content\Api;
use Ink\Content\TermImages;
use Brain\Monkey;
use Brain\Monkey\Functions;

/**
 * Register the term meta with `register_term_meta` captured, returning the
 * taxonomy => args map the registrar produced.
 *
 * @return array<string, array<string, mixed>>
 */
function ink_capture_registered_term_meta(): array {
	$captured = array();

	Functions\when( 'register_term_meta' )->alias(
		function ( string $taxonomy, string $key, array $args ) use ( &$captured ): void {
			$captured[ $taxonomy ] = array( 'key' => $key, 'args' => $args );
		}
	);

	( new TermImages() )->register();

	return $captured;
}

beforeEach( function (): void {
	Monkey\setUp();
	Functions\when( '__' )->returnArg( 1 );
	Functions\when( 'add_action' )->justReturn( true );
	Functions\when( 'absint' )->alias( fn ( $v ): int => abs( (int) $v ) );
} );

afterEach( function (): void {
	Monkey\tearDown();
} );

/**
 * AC-1/AC-2: the term meta registers against exactly the three image taxonomies
 * (not the rating taxonomy `ster_gradering`).
 */
test( 'term image registers against genre, vaardigheid, uitdagingsrondte only', function (): void {
	$registered = ink_capture_registered_term_meta();

	$taxonomies = array_keys( $registered );
	sort( $taxonomies );

	expect( $taxonomies )->toBe( array( 'genre', 'uitdagingsrondte', 'vaardigheid' ) );
	expect( $taxonomies )->not->toContain( 'ster_gradering' );
} );

/**
 * AC-2: the meta key is the single-source constant.
 */
test( 'the term-image meta key is ink_term_image_id', function (): void {
	expect( TermImages::META_KEY )->toBe( 'ink_term_image_id' );

	$registered = ink_capture_registered_term_meta();
	foreach ( $registered as $taxonomy => $entry ) {
		expect( $entry['key'] )->toBe( 'ink_term_image_id' );
	}
} );

/**
 * AC-2: each registration is single, integer, REST-aware, absint-sanitised, gated.
 */
test( 'term image meta is single integer, show_in_rest, absint-sanitised and gated', function (): void {
	$registered = ink_capture_registered_term_meta();

	foreach ( $registered as $taxonomy => $entry ) {
		$args = $entry['args'];
		expect( $args['single'] )->toBeTrue();
		expect( $args['type'] )->toBe( 'integer' );
		expect( $args['show_in_rest'] )->toBeTrue();
		expect( $args['default'] )->toBe( 0 );
		expect( $args['sanitize_callback'] )->toBe( 'absint' );
		expect( $args['auth_callback'] )->toBeCallable();
	}
} );

/**
 * AC-4: imageId() reads the native meta and casts to int (0 when unset).
 */
test( 'imageId returns the stored attachment id as an int', function (): void {
	Functions\when( 'get_term_meta' )->justReturn( '7' );
	expect( TermImages::imageId( 123 ) )->toBe( 7 );

	Functions\when( 'get_term_meta' )->justReturn( '' );
	expect( TermImages::imageId( 123 ) )->toBe( 0 );
} );

/**
 * AC-4: the facade delegates to TermImages.
 */
test( 'Api facade exposes the term-image surface', function (): void {
	Functions\when( 'get_term_meta' )->justReturn( '42' );
	expect( Api::termImageId( 5 ) )->toBe( 42 );
	expect( Api::termImageTaxonomies() )->toBe( TermImages::imageTaxonomyList() );
} );

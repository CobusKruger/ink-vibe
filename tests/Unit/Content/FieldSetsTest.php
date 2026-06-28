<?php
/**
 * Unit tests for the INK per-CPT admin field sets (AD-1, AD-6).
 *
 * Target: {@see \Ink\Content\FieldSets} and the {@see \Ink\Content\Api} facade
 * (Story 2.4).
 *
 * Authored ready-to-run; the runner (Pest function API + Brain Monkey, the
 * `tests/bootstrap.php` lifecycle, `phpunit.xml` Unit testsuite) is the 1.11
 * scaffold built out in the 18.8 CI buildout. Mirrors the 2.1–2.3 precedents.
 *
 * Harness assumptions (provided by tests/bootstrap.php):
 *  - Brain\Monkey is set up/torn down per test.
 *  - `register_post_meta()` is aliased to capture every (cpt, key, args) call.
 *  - `add_action`/`__` and the WP sanitiser core functions are stubbed so the
 *    registration path and the captured `sanitize_callback`s run without WP.
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Content;

use Ink\Content\Api;
use Ink\Content\FieldSets;
use Brain\Monkey;
use Brain\Monkey\Functions;

/**
 * Register every field with `register_post_meta` captured, returning the
 * "cpt::key" => args map the registrar produced.
 *
 * @return array<string, array{cpt: string, key: string, args: array<string, mixed>}>
 */
function ink_capture_registered_fields(): array {
	$captured = array();

	Functions\when( 'register_post_meta' )->alias(
		function ( string $cpt, string $key, array $args ) use ( &$captured ): void {
			$captured[ "{$cpt}::{$key}" ] = array(
				'cpt'  => $cpt,
				'key'  => $key,
				'args' => $args,
			);
		}
	);

	( new FieldSets() )->register();

	return $captured;
}

beforeEach( function (): void {
	Monkey\setUp();
	Functions\when( '__' )->returnArg( 1 );
	Functions\when( 'add_action' )->justReturn( true );
	Functions\when( 'sanitize_text_field' )->returnArg( 1 );
	Functions\when( 'sanitize_textarea_field' )->returnArg( 1 );
	Functions\when( 'esc_url_raw' )->returnArg( 1 );
	Functions\when( 'absint' )->alias( fn ( $v ): int => abs( (int) $v ) );
} );

afterEach( function (): void {
	Monkey\tearDown();
} );

/**
 * AC-2: field meta registers against exactly the three editorial CPTs — never a
 * bydrae/library/training CPT.
 */
test( 'field meta registers against only inkpols_uitgawe, uitdaging, borg', function (): void {
	$registered = ink_capture_registered_fields();

	$cpts = array_values( array_unique( array_map( fn ( $e ) => $e['cpt'], $registered ) ) );
	sort( $cpts );

	expect( $cpts )->toBe( array( 'borg', 'inkpols_uitgawe', 'uitdaging' ) );
	expect( $cpts )->not->toContain( 'gedig' );
} );

/**
 * AC-1/AC-2: each CPT registers its expected field keys.
 */
test( 'each CPT registers its expected field keys', function (): void {
	$registered = ink_capture_registered_fields();
	$keys       = array_map( fn ( $e ) => $e['key'], $registered );

	// InkPols.
	expect( $keys )->toContain( 'ink_inkpols_issue_date' );
	expect( $keys )->toContain( 'ink_inkpols_volume' );
	expect( $keys )->toContain( 'ink_inkpols_cover_id' );
	expect( $keys )->toContain( 'ink_inkpols_pdf_id' );
	expect( $keys )->toContain( 'ink_inkpols_teaser' );
	// Challenge.
	expect( $keys )->toContain( 'ink_uitdaging_theme' );
	expect( $keys )->toContain( 'ink_uitdaging_deadline' );
	// Sponsor.
	expect( $keys )->toContain( 'ink_borg_link' );
	expect( $keys )->toContain( 'ink_borg_tier' );
	expect( $keys )->toContain( 'ink_borg_start_date' );
	expect( $keys )->toContain( 'ink_borg_end_date' );
	expect( $keys )->toContain( 'ink_borg_placement' );
} );

/**
 * AC-2: every field is single + REST-aware + sanitised + capability-gated.
 */
test( 'every field is single, show_in_rest, sanitised and auth-gated', function (): void {
	$registered = ink_capture_registered_fields();

	foreach ( $registered as $id => $entry ) {
		expect( $entry['args']['single'] )->toBeTrue();
		expect( $entry['args']['show_in_rest'] )->toBeTrue();
		expect( $entry['args'] )->toHaveKey( 'sanitize_callback' );
		expect( $entry['args']['auth_callback'] )->toBeCallable();
		expect( $entry['args'] )->toHaveKey( 'default' );
	}
} );

/**
 * AC-2: attachment-ID fields are integer-typed.
 */
test( 'cover and pdf attachment-id fields are integer typed', function (): void {
	$registered = ink_capture_registered_fields();

	expect( $registered['inkpols_uitgawe::ink_inkpols_cover_id']['args']['type'] )->toBe( 'integer' );
	expect( $registered['inkpols_uitgawe::ink_inkpols_pdf_id']['args']['type'] )->toBe( 'integer' );
	expect( $registered['inkpols_uitgawe::ink_inkpols_cover_id']['args']['default'] )->toBe( 0 );
} );

/**
 * AC-2/AC-4: the captured sanitize callbacks coerce correctly — attachment id via
 * absint, date via the date sanitiser (valid kept, junk dropped).
 */
test( 'captured sanitize callbacks coerce field values', function (): void {
	$registered = ink_capture_registered_fields();

	$cover_sanitize = $registered['inkpols_uitgawe::ink_inkpols_cover_id']['args']['sanitize_callback'];
	expect( call_user_func( $cover_sanitize, '12abc' ) )->toBe( 12 );

	$date_sanitize = $registered['inkpols_uitgawe::ink_inkpols_issue_date']['args']['sanitize_callback'];
	expect( call_user_func( $date_sanitize, '2026-06-21' ) )->toBe( '2026-06-21' );
	expect( call_user_func( $date_sanitize, 'nonsense' ) )->toBe( '' );

	$deadline_sanitize = $registered['uitdaging::ink_uitdaging_deadline']['args']['sanitize_callback'];
	expect( call_user_func( $deadline_sanitize, '2026-06-21T18:00' ) )->toBe( '2026-06-21T18:00' );
} );

/**
 * AC-5: the facade exposes the full field meta-key surface (12 keys).
 */
test( 'Api facade exposes the field meta-key surface', function (): void {
	expect( Api::fieldMetaKeys() )->toBe( FieldSets::metaKeys() );
	expect( FieldSets::metaKeys() )->toHaveCount( 12 );
} );

/**
 * Story 12.3 (deferred from Epic 2): the meta-box save path now enforces the per-CPT
 * editorial capability — a user with edit_post but WITHOUT ink_manage_challenges can
 * no longer write uitdaging meta via the meta box (matching the REST auth_callback).
 */
test( 'save denies an uitdaging meta write when the per-CPT cap is missing', function (): void {
	$_POST = array(
		'ink_content_fieldsets_nonce' => 'n',
		'ink_uitdaging_theme'      => 'Herfs',
	);
	Functions\when( 'wp_unslash' )->returnArg( 1 );
	Functions\when( 'sanitize_text_field' )->returnArg( 1 );
	Functions\when( 'wp_verify_nonce' )->justReturn( true );
	Functions\when( 'wp_is_post_autosave' )->justReturn( false );
	Functions\when( 'wp_is_post_revision' )->justReturn( false );
	// edit_post → true, but the editorial cap ink_manage_challenges → false.
	Functions\when( 'current_user_can' )->alias(
		fn ( string $cap ): bool => 'edit_post' === $cap
	);

	Functions\expect( 'update_post_meta' )->never();

	$post            = new \WP_Post();
	$post->post_type = 'uitdaging';
	( new FieldSets() )->save( 42, $post );

	expect( true )->toBeTrue();

	unset( $_POST );
} );

/**
 * Story 12.3: with BOTH edit_post and the per-CPT cap, the meta-box save writes.
 */
test( 'save writes uitdaging meta when both edit_post and the per-CPT cap are held', function (): void {
	$_POST = array(
		'ink_content_fieldsets_nonce' => 'n',
		'ink_uitdaging_theme'      => 'Herfs',
	);
	Functions\when( 'wp_unslash' )->returnArg( 1 );
	Functions\when( 'sanitize_text_field' )->returnArg( 1 );
	Functions\when( 'wp_verify_nonce' )->justReturn( true );
	Functions\when( 'wp_is_post_autosave' )->justReturn( false );
	Functions\when( 'wp_is_post_revision' )->justReturn( false );
	Functions\when( 'current_user_can' )->justReturn( true );

	Functions\expect( 'update_post_meta' )->once()->with( 42, 'ink_uitdaging_theme', 'Herfs' );

	$post            = new \WP_Post();
	$post->post_type = 'uitdaging';
	( new FieldSets() )->save( 42, $post );

	expect( true )->toBeTrue();

	unset( $_POST );
} );

/**
 * Story 14.1 (deferred from Epic 2): the borg meta-box save path enforces the
 * per-CPT editorial capability — a user with edit_post but WITHOUT
 * ink_manage_sponsors cannot write borg meta via the meta box, closing the
 * REST-vs-meta-box capability divergence (the REST auth_callback already gates on
 * MANAGE_SPONSORS). Locks the generic 12.3 fix against a borg regression.
 */
test( 'save denies a borg meta write when the per-CPT cap (ink_manage_sponsors) is missing', function (): void {
	$_POST = array(
		'ink_content_fieldsets_nonce' => 'n',
		'ink_borg_tier'               => 'Goud',
	);
	Functions\when( 'wp_unslash' )->returnArg( 1 );
	Functions\when( 'sanitize_text_field' )->returnArg( 1 );
	Functions\when( 'wp_verify_nonce' )->justReturn( true );
	Functions\when( 'wp_is_post_autosave' )->justReturn( false );
	Functions\when( 'wp_is_post_revision' )->justReturn( false );
	// edit_post → true, but the editorial cap ink_manage_sponsors → false.
	Functions\when( 'current_user_can' )->alias(
		fn ( string $cap ): bool => 'edit_post' === $cap
	);

	Functions\expect( 'update_post_meta' )->never();

	$post            = new \WP_Post();
	$post->post_type = 'borg';
	( new FieldSets() )->save( 42, $post );

	expect( true )->toBeTrue();

	unset( $_POST );
} );

/**
 * Story 14.1: with BOTH edit_post and ink_manage_sponsors, the borg meta-box save
 * writes — proving the denial above is non-vacuous (the write WOULD happen with
 * the cap).
 */
test( 'save writes borg meta when both edit_post and ink_manage_sponsors are held', function (): void {
	$_POST = array(
		'ink_content_fieldsets_nonce' => 'n',
		'ink_borg_tier'               => 'Goud',
	);
	Functions\when( 'wp_unslash' )->returnArg( 1 );
	Functions\when( 'sanitize_text_field' )->returnArg( 1 );
	Functions\when( 'wp_verify_nonce' )->justReturn( true );
	Functions\when( 'wp_is_post_autosave' )->justReturn( false );
	Functions\when( 'wp_is_post_revision' )->justReturn( false );
	Functions\when( 'current_user_can' )->justReturn( true );

	Functions\expect( 'update_post_meta' )->once()->with( 42, 'ink_borg_tier', 'Goud' );

	$post            = new \WP_Post();
	$post->post_type = 'borg';
	( new FieldSets() )->save( 42, $post );

	expect( true )->toBeTrue();

	unset( $_POST );
} );

/**
 * Story 14.1: the borg field-set is wired to the MANAGE_SPONSORS editorial cap
 * (the single source the save path + REST auth_callback both read), and that cap
 * is in the activation grant set — so the cap reconciliation is structurally
 * closed, not just behaviourally.
 */
test( 'borg field-set uses the MANAGE_SPONSORS cap and it is in the activation grant set', function (): void {
	$registered = ink_capture_registered_fields();

	// Every captured borg field's auth_callback gates on ink_manage_sponsors:
	// with the cap → writable, without → not. (auth_callback === current_user_can($cap).)
	Functions\when( 'current_user_can' )->alias(
		fn ( string $cap ): bool => \Ink\Kernel\Capabilities::MANAGE_SPONSORS === $cap
	);
	foreach ( array( 'ink_borg_link', 'ink_borg_tier', 'ink_borg_start_date', 'ink_borg_end_date', 'ink_borg_placement' ) as $key ) {
		$auth = $registered[ "borg::{$key}" ]['args']['auth_callback'];
		expect( $auth() )->toBeTrue();
	}

	expect( \Ink\Kernel\Capabilities::all() )->toContain( \Ink\Kernel\Capabilities::MANAGE_SPONSORS );
} );

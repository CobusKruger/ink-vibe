<?php
/**
 * Unit tests for the "Wysig profiel" edit-modal render (My Profiel rebuild §5.1).
 *
 * Target: {@see \Ink\Social\ProfileEditor}. `render()` gathers the current
 * user's own name/tagline/bio/avatar and gates on login; `toHtml()` is pure
 * (escaping only) and renders the modal hidden by default, with the trigger/
 * display-update contract's data attributes and the REST-auth model this block
 * relies on (the nonce travels via `wp_localize_script` to `profiel-edit.js`,
 * the same mechanism as `vasgespel.js`/`volg.js` — no embedded form nonce
 * field, matching those siblings' exact convention).
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Social;

use Ink\Social\ProfileEditor;
use Brain\Monkey;
use Brain\Monkey\Functions;

beforeEach( function (): void {
	Monkey\setUp();
	Functions\when( '__' )->returnArg( 1 );
	Functions\when( 'esc_html' )->returnArg( 1 );
	Functions\when( 'esc_html__' )->returnArg( 1 );
	Functions\when( 'esc_attr' )->returnArg( 1 );
	Functions\when( 'esc_textarea' )->returnArg( 1 );
} );

afterEach( function (): void {
	Monkey\tearDown();
} );

test( 'render returns nothing for a logged-out visitor', function (): void {
	Functions\when( 'is_user_logged_in' )->justReturn( false );

	expect( ProfileEditor::render() )->toBe( '' );
} );

test( 'render gathers the current user\'s own name/bio/tagline/avatar', function (): void {
	Functions\when( 'is_user_logged_in' )->justReturn( true );
	Functions\when( 'get_current_user_id' )->justReturn( 7 );
	Functions\when( 'get_the_author_meta' )->alias(
		function ( string $field, int $user_id ) {
			expect( $user_id )->toBe( 7 );

			return 'display_name' === $field ? 'Susan Skrywer' : 'Oor my as skrywer.';
		}
	);
	Functions\when( 'get_user_meta' )->justReturn( 'Skryf om te lewe.' );
	Functions\when( 'get_avatar' )->alias(
		function ( int $user_id, int $size ): string {
			expect( $user_id )->toBe( 7 );
			expect( $size )->toBe( 96 );

			return '<img src="https://example.test/avatar.jpg" alt="" />';
		}
	);

	$html = ProfileEditor::render();

	expect( $html )->toContain( 'value="Susan Skrywer"' );
	expect( $html )->toContain( 'Oor my as skrywer.' );
	expect( $html )->toContain( 'value="Skryf om te lewe."' );
	expect( $html )->toContain( 'https://example.test/avatar.jpg' );
} );

test( 'toHtml renders the modal hidden by default with the documented trigger-close contract', function (): void {
	$html = ProfileEditor::toHtml(
		array(
			'name'    => 'Susan',
			'bio'     => 'Bio',
			'tagline' => 'Leuse',
			'avatar'  => '',
		)
	);

	expect( $html )->toContain( 'id="ink-profiel-redigeer"' );
	expect( $html )->toContain( 'is-hidden' );
	expect( $html )->toContain( 'aria-hidden="true"' );
	expect( $html )->toContain( 'data-ink-profiel-redigeer-modal' );
	expect( $html )->toContain( 'data-ink-profiel-redigeer-sluit' );
	expect( $html )->toContain( 'data-ink-profiel-redigeer-vorm' );
	expect( $html )->toContain( 'role="dialog"' );
	expect( $html )->toContain( 'aria-modal="true"' );

	// REST auth travels via the localized `inkProfielEdit.nonce` header (the
	// vasgespel.js/volg.js convention) — never an embedded form nonce field.
	expect( $html )->not->toContain( 'wp_nonce_field' );
	expect( $html )->not->toContain( '_wpnonce' );
} );

test( 'toHtml renders each field with its current value and expected name attribute', function (): void {
	$html = ProfileEditor::toHtml(
		array(
			'name'    => 'Susan Skrywer',
			'bio'     => 'Oor my as skrywer.',
			'tagline' => 'Skryf om te lewe.',
			'avatar'  => '',
		)
	);

	expect( $html )->toContain( 'name="display_name"' );
	expect( $html )->toContain( 'value="Susan Skrywer"' );
	expect( $html )->toContain( 'name="tagline"' );
	expect( $html )->toContain( 'value="Skryf om te lewe."' );
	expect( $html )->toContain( 'name="bio"' );
	expect( $html )->toContain( 'Oor my as skrywer.' );
	expect( $html )->toContain( '<button type="submit"' );
	expect( $html )->toContain( '<button type="button"' );
} );

test( 'toHtml omits the read-only avatar entirely when none is available', function (): void {
	$html = ProfileEditor::toHtml(
		array(
			'name'    => 'Susan',
			'bio'     => '',
			'tagline' => '',
			'avatar'  => '',
		)
	);

	expect( $html )->not->toContain( 'ink-profiel-redigeer__avatar' );
} );

test( 'toHtml renders the read-only avatar image when available (no edit control)', function (): void {
	$html = ProfileEditor::toHtml(
		array(
			'name'    => 'Susan',
			'bio'     => '',
			'tagline' => '',
			'avatar'  => '<img src="https://example.test/avatar.jpg" alt="" />',
		)
	);

	expect( $html )->toContain( 'ink-profiel-redigeer__avatar' );
	expect( $html )->toContain( 'https://example.test/avatar.jpg' );
	expect( $html )->not->toContain( 'type="file"' );
} );

test( 'toHtml escapes attribute-breaking values in the field values', function (): void {
	// Override the beforeEach pass-through stubs with the REAL WordPress
	// escaping functions for this one test, so the assertion is meaningful
	// rather than a no-op pass — a plain `returnArg` would make an unescaped
	// injection indistinguishable from an escaped one.
	Functions\when( 'esc_html' )->alias( static fn ( string $s ): string => htmlspecialchars( $s, ENT_QUOTES ) );
	Functions\when( 'esc_html__' )->returnArg( 1 );
	Functions\when( 'esc_attr' )->alias( static fn ( string $s ): string => htmlspecialchars( $s, ENT_QUOTES ) );
	Functions\when( 'esc_textarea' )->alias( static fn ( string $s ): string => htmlspecialchars( $s, ENT_QUOTES ) );

	$html = ProfileEditor::toHtml(
		array(
			'name'    => '"><script>alert(1)</script>',
			'bio'     => '"><script>alert(2)</script>',
			'tagline' => '"><script>alert(3)</script>',
			'avatar'  => '',
		)
	);

	expect( $html )->not->toContain( '<script>alert(1)</script>' );
	expect( $html )->not->toContain( '<script>alert(2)</script>' );
	expect( $html )->not->toContain( '<script>alert(3)</script>' );
} );

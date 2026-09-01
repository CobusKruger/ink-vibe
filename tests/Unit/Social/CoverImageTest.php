<?php
/**
 * Unit tests for the Skrywerprofiel cover-image user meta (Phase-2 fidelity pass).
 *
 * Target: {@see \Ink\Social\CoverImage}. `register()` wires the meta + admin
 * hooks directly (no nested `add_action('init', …)`); `urlFor()` resolves the
 * attachment id to a URL, empty when unset/missing.
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Social;

use Ink\Social\CoverImage;
use Brain\Monkey;
use Brain\Monkey\Functions;

beforeEach( function (): void {
	Monkey\setUp();
} );

afterEach( function (): void {
	Monkey\tearDown();
} );

test( 'register() registers the user meta directly and wires the profile-edit hooks', function (): void {
	$captured = array();

	Functions\when( 'register_meta' )->alias(
		function ( string $object_type, string $key, array $args ) use ( &$captured ): void {
			$captured['object_type'] = $object_type;
			$captured['key']         = $key;
			$captured['args']        = $args;
		}
	);

	( new CoverImage() )->register();

	expect( $captured['object_type'] )->toBe( 'user' );
	expect( $captured['key'] )->toBe( CoverImage::META );
	expect( $captured['args']['type'] )->toBe( 'integer' );
	expect( $captured['args']['default'] )->toBe( 0 );

	expect( has_action( 'show_user_profile', 'Ink\Social\CoverImage::renderField' ) )->not->toBeFalse();
	expect( has_action( 'edit_user_profile', 'Ink\Social\CoverImage::renderField' ) )->not->toBeFalse();
	expect( has_action( 'personal_options_update', 'Ink\Social\CoverImage::save' ) )->not->toBeFalse();
	expect( has_action( 'edit_user_profile_update', 'Ink\Social\CoverImage::save' ) )->not->toBeFalse();
} );

test( 'urlFor returns empty when no attachment id is set', function (): void {
	Functions\when( 'get_user_meta' )->justReturn( 0 );

	expect( CoverImage::urlFor( 42 ) )->toBe( '' );
} );

test( 'urlFor resolves the attachment id to a URL at the requested size', function (): void {
	Functions\when( 'get_user_meta' )->justReturn( '17' );
	Functions\when( 'wp_get_attachment_image_url' )->alias(
		function ( int $id, string $size ): string {
			expect( $id )->toBe( 17 );
			expect( $size )->toBe( 'large' );

			return 'https://example.test/omslag-large.jpg';
		}
	);

	expect( CoverImage::urlFor( 42, 'large' ) )->toBe( 'https://example.test/omslag-large.jpg' );
} );

test( 'urlFor returns empty when the attachment no longer resolves to a URL', function (): void {
	Functions\when( 'get_user_meta' )->justReturn( '17' );
	Functions\when( 'wp_get_attachment_image_url' )->justReturn( false );

	expect( CoverImage::urlFor( 42 ) )->toBe( '' );
} );

test( 'save() is a no-op without edit_user capability', function (): void {
	Functions\when( 'current_user_can' )->justReturn( false );
	$called = false;
	Functions\when( 'update_user_meta' )->alias(
		function () use ( &$called ): void {
			$called = true;
		}
	);

	CoverImage::save( 42 );

	expect( $called )->toBeFalse();
} );

test( 'save() is a no-op when the nonce is missing or invalid', function (): void {
	Functions\when( 'current_user_can' )->justReturn( true );
	Functions\when( 'wp_verify_nonce' )->justReturn( false );
	Functions\when( 'sanitize_text_field' )->returnArg( 1 );
	Functions\when( 'wp_unslash' )->returnArg( 1 );

	$_POST[ 'ink_social_cover_image_nonce' ] = 'bad-nonce';
	$_POST[ CoverImage::META ]               = '99';

	$called = false;
	Functions\when( 'update_user_meta' )->alias(
		function () use ( &$called ): void {
			$called = true;
		}
	);

	CoverImage::save( 42 );

	expect( $called )->toBeFalse();

	unset( $_POST[ 'ink_social_cover_image_nonce' ], $_POST[ CoverImage::META ] );
} );

test( 'save() writes the sanitised attachment id when authorised', function (): void {
	Functions\when( 'current_user_can' )->justReturn( true );
	Functions\when( 'wp_verify_nonce' )->justReturn( true );
	Functions\when( 'sanitize_text_field' )->returnArg( 1 );
	Functions\when( 'wp_unslash' )->returnArg( 1 );
	Functions\when( 'absint' )->alias( static fn ( $n ): int => abs( (int) $n ) );

	$_POST[ 'ink_social_cover_image_nonce' ] = 'good-nonce';
	$_POST[ CoverImage::META ]               = '123';

	$captured = array();
	Functions\when( 'update_user_meta' )->alias(
		function ( int $user_id, string $key, $value ) use ( &$captured ): void {
			$captured = array( $user_id, $key, $value );
		}
	);

	CoverImage::save( 42 );

	expect( $captured )->toBe( array( 42, CoverImage::META, 123 ) );

	unset( $_POST[ 'ink_social_cover_image_nonce' ], $_POST[ CoverImage::META ] );
} );

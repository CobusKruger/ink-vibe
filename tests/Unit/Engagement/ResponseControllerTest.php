<?php
/**
 * Unit tests for the Gemeenskapsreaksie REST controller (Story 7.4, FR-27, AD-6).
 *
 * Targets the pure core — `validate()`, `permission()` — plus a conflation
 * guardrail (controller + store reference neither Tiers nor Entitlement). The
 * "every response carries a type" guarantee (AC #4) is proven at the write layer:
 * an unknown type is rejected.
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Engagement;

use Ink\Engagement\ResponseController;
use Ink\Tests\Support\CodeScan;
use Brain\Monkey;
use Brain\Monkey\Functions;

beforeEach( function (): void {
	Monkey\setUp();
} );

afterEach( function (): void {
	Monkey\tearDown();
} );

test( 'permission allows any logged-in lid (not entitlement-gated)', function (): void {
	Functions\when( 'is_user_logged_in' )->justReturn( true );
	expect( ( new ResponseController() )->permission() )->toBeTrue();

	Functions\when( 'is_user_logged_in' )->justReturn( false );
	expect( ( new ResponseController() )->permission() )->toBeFalse();
} );

test( 'validate rejects an unreadable post', function (): void {
	$error = ResponseController::validate( 'lof', 'teks', false );

	expect( $error )->toBeInstanceOf( \WP_Error::class );
	expect( $error->get_error_code() )->toBe( 'ink_gemeenskapsreaksie_invalid_post' );
} );

test( 'validate rejects an unknown type (every response must carry a valid type)', function (): void {
	$error = ResponseController::validate( 'hartjie', 'teks', true ); // a reaction, not a response type

	expect( $error )->toBeInstanceOf( \WP_Error::class );
	expect( $error->get_error_code() )->toBe( 'ink_gemeenskapsreaksie_invalid_type' );
} );

test( 'validate rejects empty content', function (): void {
	$error = ResponseController::validate( 'lof', '   ', true );

	expect( $error )->toBeInstanceOf( \WP_Error::class );
	expect( $error->get_error_code() )->toBe( 'ink_gemeenskapsreaksie_empty' );
} );

test( 'validate passes for a readable post, a known type and real content', function (): void {
	expect( ResponseController::validate( 'lof', 'Pragtige beeldspraak.', true ) )->toBeNull();
	expect( ResponseController::validate( 'voorstel', 'Probeer dalk...', true ) )->toBeNull();
} );

test( 'the response controller and store are conflation-clean (no Tiers / Entitlement)', function (): void {
	$root  = dirname( __DIR__, 3 );
	$files = array(
		$root . '/wp-content/plugins/ink-core/src/Engagement/ResponseController.php',
		$root . '/wp-content/plugins/ink-core/src/Engagement/ResponseStore.php',
		$root . '/wp-content/plugins/ink-core/src/Engagement/ResponsesList.php',
	);

	$scanned = 0;
	foreach ( $files as $file ) {
		$code = CodeScan::withoutComments( $file );

		expect( $code )->toContain( 'class ' ); // non-vacuous: real code scanned
		++$scanned;

		expect( $code )->not->toContain( 'Ink\\Tiers' );
		expect( $code )->not->toContain( 'Ink\\Entitlement' );
	}

	expect( $scanned )->toBe( 3 );
} );

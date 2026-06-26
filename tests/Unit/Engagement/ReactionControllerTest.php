<?php
/**
 * Unit tests for the line-reaction REST controller (Story 7.3, FR-26, AD-6).
 *
 * Targets the pure core of {@see \Ink\Engagement\ReactionController} — `validate()`,
 * `decideRemoval()`, `permission()` — plus a conflation guardrail: the controller
 * and store source reference neither Tiers nor Entitlement (engagement is open to
 * any lid, never entitlement-gated). The "reactions only on content lines, not
 * blank separators" rule (AC #6) is proven at the WRITE layer here.
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Engagement;

use Ink\Engagement\ReactionController;
use Ink\Kernel\Reaction;
use Ink\Tests\Support\CodeScan;
use Brain\Monkey;
use Brain\Monkey\Functions;

beforeEach( function (): void {
	Monkey\setUp();
} );

afterEach( function (): void {
	Monkey\tearDown();
} );

// Body with content lines at physical index 0 and 2; index 1 is a blank separator.
const INK_REACT_BODY = "reël een\n\nreël twee";

test( 'permission allows any logged-in lid (not entitlement-gated)', function (): void {
	Functions\when( 'is_user_logged_in' )->justReturn( true );
	expect( ( new ReactionController() )->permission() )->toBeTrue();

	Functions\when( 'is_user_logged_in' )->justReturn( false );
	expect( ( new ReactionController() )->permission() )->toBeFalse();
} );

test( 'validate rejects an unreadable post', function (): void {
	$error = ReactionController::validate( 0, 'hartjie', false, INK_REACT_BODY );

	expect( $error )->toBeInstanceOf( \WP_Error::class );
	expect( $error->get_error_code() )->toBe( 'ink_reaksie_invalid_post' );
} );

test( 'validate rejects an unknown reaction', function (): void {
	$error = ReactionController::validate( 0, 'lof', true, INK_REACT_BODY );

	expect( $error )->toBeInstanceOf( \WP_Error::class );
	expect( $error->get_error_code() )->toBe( 'ink_reaksie_invalid_reaction' );
} );

test( 'validate rejects a blank-separator line (reactions only on content lines)', function (): void {
	$error = ReactionController::validate( 1, 'hartjie', true, INK_REACT_BODY ); // index 1 is blank

	expect( $error )->toBeInstanceOf( \WP_Error::class );
	expect( $error->get_error_code() )->toBe( 'ink_reaksie_invalid_line' );
} );

test( 'validate rejects an out-of-range line', function (): void {
	$error = ReactionController::validate( 99, 'hartjie', true, INK_REACT_BODY );

	expect( $error )->toBeInstanceOf( \WP_Error::class );
	expect( $error->get_error_code() )->toBe( 'ink_reaksie_invalid_line' );
} );

test( 'validate passes for a real content line with a known reaction', function (): void {
	// Non-vacuous: index 0 and 2 ARE content lines in the same fixture used above.
	expect( ReactionController::validate( 0, 'hartjie', true, INK_REACT_BODY ) )->toBeNull();
	expect( ReactionController::validate( 2, 'wow', true, INK_REACT_BODY ) )->toBeNull();
} );

test( 'decideRemoval toggles off only when re-selecting the same reaction', function (): void {
	expect( ReactionController::decideRemoval( Reaction::Hartjie, Reaction::Hartjie ) )->toBeTrue();
	expect( ReactionController::decideRemoval( Reaction::Hartjie, Reaction::Wow ) )->toBeFalse();
	expect( ReactionController::decideRemoval( null, Reaction::Hartjie ) )->toBeFalse();
} );

test( 'the reaction controller and store are conflation-clean (no Tiers / Entitlement)', function (): void {
	$root  = dirname( __DIR__, 3 );
	$files = array(
		$root . '/wp-content/plugins/ink-core/src/Engagement/ReactionController.php',
		$root . '/wp-content/plugins/ink-core/src/Engagement/ReactionStore.php',
	);

	$scanned = 0;
	foreach ( $files as $file ) {
		$code = CodeScan::withoutComments( $file );

		// Non-vacuous: prove real code (a class body) was actually scanned.
		expect( $code )->toContain( 'class ' );
		++$scanned;

		expect( $code )->not->toContain( 'Ink\\Tiers' );
		expect( $code )->not->toContain( 'Ink\\Entitlement' );
	}

	expect( $scanned )->toBe( 2 );
} );

<?php
/**
 * Unit tests for the INK post-signup onboarding state (AD-1, Story 3.3).
 *
 * Target: {@see \Ink\Accounts\Onboarding} — the one-time, skippable onboarding
 * flag (`ink_onboarding_complete`) registered on the Story-2.3 user-meta
 * substrate, its `is_scalar`-guarded sanitiser, the own-record `auth_callback`,
 * and the single flag-write path (`markComplete`). The nonce-protected
 * `completeViaPost` handler and the rendered theme surface are covered by E2E
 * (Story 18.8) — it ends in `exit`, so it is out of unit scope.
 *
 * Brain Monkey, no WordPress/DB. WP helpers the SUT calls
 * (`register_meta`, `rest_sanitize_boolean`, `get_user_meta`,
 * `update_user_meta`, `get_current_user_id`, `current_user_can`) are mocked.
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Accounts;

use Ink\Accounts\Onboarding;
use Brain\Monkey;
use Brain\Monkey\Functions;

beforeEach( function (): void {
	Monkey\setUp();

	// WP's rest_sanitize_boolean: "false"/"0"/"" read as false, else cast.
	Functions\when( 'rest_sanitize_boolean' )->alias(
		function ( $value ): bool {
			if ( is_string( $value ) ) {
				$lower = strtolower( $value );
				if ( in_array( $lower, array( 'false', '0', '' ), true ) ) {
					return false;
				}
			}
			return (bool) $value;
		}
	);
} );

afterEach( function (): void {
	Monkey\tearDown();
} );

/**
 * AC-1/AC-4: the flag-key constant is the exact `ink_`-prefixed ID (single source).
 */
test( 'the onboarding-flag key constant is the exact prefixed ID', function (): void {
	expect( Onboarding::ONBOARDING_COMPLETE )->toBe( 'ink_onboarding_complete' );
} );

/**
 * AC-1/AC-4: the flag registers against the `user` object type with single,
 * boolean type, a `false` default (unset ⇒ show onboarding), and both callbacks.
 */
test( 'registerMeta registers the onboarding flag on the user substrate', function (): void {
	$captured = array();

	Functions\when( 'register_meta' )->alias(
		function ( string $object_type, string $key, array $args ) use ( &$captured ): void {
			$captured[ $key ] = array(
				'object_type' => $object_type,
				'args'        => $args,
			);
		}
	);

	( new Onboarding() )->registerMeta();

	expect( $captured )->toHaveKey( Onboarding::ONBOARDING_COMPLETE );

	$entry = $captured[ Onboarding::ONBOARDING_COMPLETE ];
	expect( $entry['object_type'] )->toBe( 'user' );
	expect( $entry['args']['single'] )->toBeTrue();
	expect( $entry['args']['type'] )->toBe( 'boolean' );
	expect( $entry['args']['show_in_rest'] )->toBeTrue();
	expect( $entry['args']['default'] )->toBeFalse();
	expect( $entry['args'] )->toHaveKey( 'sanitize_callback' );
	expect( $entry['args'] )->toHaveKey( 'auth_callback' );
} );

/**
 * AC-1 recurring-bug guard: a non-scalar payload (array/object) falls back to the
 * `false` default rather than reaching a scalar coercion (the `is_scalar` idiom).
 */
test( 'sanitizeFlag rejects a non-scalar payload to the false default', function (): void {
	expect( Onboarding::sanitizeFlag( array( 'evil' => true ) ) )->toBeFalse();
	expect( Onboarding::sanitizeFlag( (object) array( 'x' => 1 ) ) )->toBeFalse();
} );

/**
 * AC-1: a scalar normalises through rest_sanitize_boolean — truthy ⇒ true,
 * "0"/"false"/"" ⇒ false.
 */
test( 'sanitizeFlag normalises scalar truthiness', function (): void {
	expect( Onboarding::sanitizeFlag( true ) )->toBeTrue();
	expect( Onboarding::sanitizeFlag( 1 ) )->toBeTrue();
	expect( Onboarding::sanitizeFlag( '1' ) )->toBeTrue();
	expect( Onboarding::sanitizeFlag( 'true' ) )->toBeTrue();
	expect( Onboarding::sanitizeFlag( '0' ) )->toBeFalse();
	expect( Onboarding::sanitizeFlag( 'false' ) )->toBeFalse();
	expect( Onboarding::sanitizeFlag( '' ) )->toBeFalse();
} );

/**
 * AC-4: a logged-out request cannot set the flag (own-record gate).
 */
test( 'authOwnRecord denies a logged-out request', function (): void {
	Functions\when( 'get_current_user_id' )->justReturn( 0 );

	expect( Onboarding::authOwnRecord( true, Onboarding::ONBOARDING_COMPLETE, 5 ) )->toBeFalse();
} );

/**
 * AC-4: a lid may set their OWN onboarding flag (self-set state).
 */
test( 'authOwnRecord allows the own record', function (): void {
	Functions\when( 'get_current_user_id' )->justReturn( 7 );

	expect( Onboarding::authOwnRecord( false, Onboarding::ONBOARDING_COMPLETE, 7 ) )->toBeTrue();
} );

/**
 * AC-4: a write to another user's flag requires the staff `edit_user` cap —
 * a lid cannot set another lid's onboarding state.
 */
test( 'authOwnRecord defers to edit_user for a foreign record', function (): void {
	Functions\when( 'get_current_user_id' )->justReturn( 7 );
	Functions\expect( 'current_user_can' )
		->once()
		->with( 'edit_user', 9 )
		->andReturn( false );

	expect( Onboarding::authOwnRecord( false, Onboarding::ONBOARDING_COMPLETE, 9 ) )->toBeFalse();
} );

/**
 * AC-1: hasCompleted reads the flag through the sanitiser; an invalid user id is
 * fail-safe false (never errors the presentation gate).
 */
test( 'hasCompleted reads the stored flag and is fail-safe for an invalid id', function (): void {
	Functions\when( 'get_user_meta' )->justReturn( true );
	expect( Onboarding::hasCompleted( 42 ) )->toBeTrue();

	Functions\when( 'get_user_meta' )->justReturn( '' );
	expect( Onboarding::hasCompleted( 42 ) )->toBeFalse();

	expect( Onboarding::hasCompleted( 0 ) )->toBeFalse();
} );

/**
 * AC-1/AC-4 + THE conflation rule: the complete/skip write touches ONLY the
 * onboarding flag — no entitlement, no membership, no tier re-stamp, no intent.
 */
test( 'markComplete writes only the onboarding flag, never entitlement/tier/intent', function (): void {
	$written = array();

	Functions\when( 'update_user_meta' )->alias(
		function ( $user_id, $key, $value ) use ( &$written ): bool {
			$written[ $key ] = $value;
			return true;
		}
	);

	Onboarding::markComplete( 7 );

	expect( array_keys( $written ) )->toBe( array( Onboarding::ONBOARDING_COMPLETE ) );
	expect( $written[ Onboarding::ONBOARDING_COMPLETE ] )->toBeTrue();
	expect( $written )->not->toHaveKey( 'ink_writer_tier' );
	expect( $written )->not->toHaveKey( 'ink_writer_intent' );
	expect( $written )->not->toHaveKey( 'ink_membership' );
	expect( $written )->not->toHaveKey( 'ink_entitlement' );
} );

/**
 * AC-1/AC-4: markComplete no-ops for an invalid user id (never writes a stray row).
 */
test( 'markComplete no-ops for an invalid user id', function (): void {
	Functions\expect( 'update_user_meta' )->never();

	Onboarding::markComplete( 0 );
} );

/**
 * AC-1: register() wires the meta registration on `init` and the nonce-protected
 * skip/complete handler on the logged-in `admin_post` action (no `nopriv`).
 */
test( 'register() hooks init and the admin-post complete handler', function (): void {
	( new Onboarding() )->register();

	expect( has_action( 'init', 'Ink\Accounts\Onboarding->registerMeta()' ) )->not->toBeFalse();
	expect(
		has_action( 'admin_post_' . Onboarding::postAction(), 'Ink\Accounts\Onboarding->completeViaPost()' )
	)->not->toBeFalse();
} );

/**
 * AC-4: the nonce action and field accessors expose the `ink_`-prefixed single
 * source the theme bridge re-uses (never a duplicated literal).
 */
test( 'the nonce + post-action accessors are the prefixed single source', function (): void {
	expect( Onboarding::postAction() )->toBe( 'ink_onboarding_complete' );
	expect( Onboarding::nonceAction() )->toBe( 'ink_accounts_onboarding_complete' );
	expect( Onboarding::nonceName() )->toBe( 'ink_accounts_onboarding_nonce' );
} );

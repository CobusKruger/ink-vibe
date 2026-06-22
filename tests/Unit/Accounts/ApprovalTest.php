<?php
/**
 * Unit tests for the INK R6 manual-approval backstop (AD-1, Story 3.6).
 *
 * Target: {@see \Ink\Accounts\Approval} — the OFF-by-default registration
 * approval backstop: the fail-safe-OFF toggle, the `user_register` pending stamp
 * (only-when-ON), the WP-native login gate (`wp_authenticate_user` → Afrikaans
 * `WP_Error`), the approve/reject write logic, and the Afrikaans-source decision
 * email registration (send toggle OFF).
 *
 * The nonce-protected `approveViaPost` / `rejectViaPost` handlers end in
 * `wp_safe_redirect` + `exit` and the rendered admin queue screen are covered by
 * E2E (Story 18.8) — out of unit scope, exactly as {@see Onboarding::completeViaPost}
 * is. The unit suite exercises the pure logic those handlers delegate to.
 *
 * Brain Monkey, no WordPress/DB. The real `Ink\Kernel\Capabilities` constant and
 * `Ink\I18n\Terms` registry are autoloaded, so the asserted cap/labels are the
 * genuine single-source values (no literals duplicated in the test).
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Accounts;

use Ink\Accounts\Approval;
use Ink\Kernel\Capabilities;
use Brain\Monkey;
use Brain\Monkey\Functions;

beforeEach( function (): void {
	Monkey\setUp();

	// `__()` is identity in unit context (no .mo) so Afrikaans source renders.
	Functions\when( '__' )->returnArg( 1 );

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
 * AC-1/AC-5: the toggle, meta and email keys are the exact `ink_`-prefixed IDs
 * (single source) — and the queue is gated on the LIVE granted MODERATE cap.
 */
test( 'the option / meta / cap constants are the exact prefixed single source', function (): void {
	expect( Approval::OPTION_ENABLED )->toBe( 'ink_account_approval_enabled' );
	expect( Approval::META_PENDING )->toBe( 'ink_account_pending' );
	expect( Approval::META_REJECTED )->toBe( 'ink_account_rejected' );
	// Template keys carry the `_email` suffix — deliberately distinct from the
	// META_* user-meta keys (no cross-subsystem aliasing).
	expect( Approval::APPROVED_TEMPLATE_KEY )->toBe( 'ink_account_approved_email' );
	expect( Approval::REJECTED_TEMPLATE_KEY )->toBe( 'ink_account_rejected_email' );
	expect( Approval::REJECTED_TEMPLATE_KEY )->not->toBe( Approval::META_REJECTED );
	// Login-gate error codes are distinct from the meta keys, and pending ≠ rejected.
	expect( Approval::ERROR_CODE )->toBe( 'ink_account_pending_login' );
	expect( Approval::ERROR_CODE_REJECTED )->toBe( 'ink_account_rejected_login' );
	// The queue is gated on the live, activation-granted cap (not a new stub cap).
	expect( Capabilities::MODERATE )->toBe( 'ink_moderate' );
} );

/**
 * AC-1 (the hard constraint): the toggle defaults OFF — an UNSET option reads
 * false, so signup is frictionless by default (UJ-1).
 */
test( 'isEnabled defaults OFF when the option is unset', function (): void {
	Functions\when( 'get_option' )->justReturn( false );

	expect( Approval::isEnabled() )->toBeFalse();
} );

/**
 * AC-1: a GARBLED option value (non-scalar, or an unrecognised string) reads OFF —
 * manual approval is never accidentally ON.
 */
test( 'isEnabled is fail-safe OFF for garbled values', function (): void {
	Functions\when( 'get_option' )->justReturn( array( 'corrupt' => true ) );
	expect( Approval::isEnabled() )->toBeFalse();

	Functions\when( 'get_option' )->justReturn( 'maybe' );
	expect( Approval::isEnabled() )->toBeFalse();

	Functions\when( 'get_option' )->justReturn( '0' );
	expect( Approval::isEnabled() )->toBeFalse();

	Functions\when( 'get_option' )->justReturn( 0 );
	expect( Approval::isEnabled() )->toBeFalse();
} );

/**
 * AC-2: a redakteur can explicitly turn the backstop ON (bool, int or string).
 */
test( 'isEnabled reads ON only for an explicit truthy value', function (): void {
	Functions\when( 'get_option' )->justReturn( true );
	expect( Approval::isEnabled() )->toBeTrue();

	Functions\when( 'get_option' )->justReturn( 1 );
	expect( Approval::isEnabled() )->toBeTrue();

	Functions\when( 'get_option' )->justReturn( '1' );
	expect( Approval::isEnabled() )->toBeTrue();

	Functions\when( 'get_option' )->justReturn( 'true' );
	expect( Approval::isEnabled() )->toBeTrue();
} );

/**
 * AC-2/AC-5: the pending + rejected meta register on the `user` substrate with
 * single, boolean, false default and both callbacks (mirrors Onboarding house style).
 */
test( 'registerMeta registers the pending + rejected flags on the user substrate', function (): void {
	$captured = array();

	Functions\when( 'register_meta' )->alias(
		function ( string $object_type, string $key, array $args ) use ( &$captured ): void {
			$captured[ $key ] = array(
				'object_type' => $object_type,
				'args'        => $args,
			);
		}
	);

	( new Approval() )->registerMeta();

	foreach ( array( Approval::META_PENDING, Approval::META_REJECTED ) as $key ) {
		expect( $captured )->toHaveKey( $key );
		$entry = $captured[ $key ];
		expect( $entry['object_type'] )->toBe( 'user' );
		expect( $entry['args']['single'] )->toBeTrue();
		expect( $entry['args']['type'] )->toBe( 'boolean' );
		expect( $entry['args']['default'] )->toBeFalse();
		expect( $entry['args'] )->toHaveKey( 'sanitize_callback' );
		expect( $entry['args'] )->toHaveKey( 'auth_callback' );
	}
} );

/**
 * AC-5 recurring-bug guard: a non-scalar payload falls back to the false default
 * rather than reaching a scalar coercion (the `is_scalar` idiom).
 */
test( 'sanitizeFlag rejects a non-scalar payload to the false default', function (): void {
	expect( Approval::sanitizeFlag( array( 'evil' => true ) ) )->toBeFalse();
	expect( Approval::sanitizeFlag( (object) array( 'x' => 1 ) ) )->toBeFalse();
	expect( Approval::sanitizeFlag( '1' ) )->toBeTrue();
} );

/**
 * AC-2/AC-3: the pending/rejected meta write is STAFF-gated — only a moderator
 * (`ink_moderate`) may write it (NOT self-set, unlike onboarding).
 */
test( 'authModerate gates the flag write on the live ink_moderate cap', function (): void {
	Functions\expect( 'current_user_can' )
		->once()
		->with( Capabilities::MODERATE )
		->andReturn( true );
	expect( Approval::authModerate() )->toBeTrue();

	Functions\expect( 'current_user_can' )
		->once()
		->with( Capabilities::MODERATE )
		->andReturn( false );
	expect( Approval::authModerate() )->toBeFalse();
} );

/**
 * AC-1 (the frictionless guarantee, UJ-1): when the backstop is OFF,
 * `user_register` stamps NOTHING — a new account is immediately usable.
 */
test( 'maybeMarkPending writes nothing when the backstop is OFF', function (): void {
	Functions\when( 'get_option' )->justReturn( false ); // OFF.
	Functions\expect( 'update_user_meta' )->never();

	( new Approval() )->maybeMarkPending( 42 );
} );

/**
 * AC-2: when ON, a self-service registration is stamped pending — and ONLY the
 * pending flag is written (THE conflation rule: no entitlement/tier/intent).
 */
test( 'maybeMarkPending stamps only the pending flag when ON (self-service)', function (): void {
	$written = array();

	Functions\when( 'get_option' )->justReturn( true ); // ON.
	Functions\when( 'current_user_can' )->justReturn( false ); // no moderator actor.
	Functions\when( 'update_user_meta' )->alias(
		function ( $user_id, $key, $value ) use ( &$written ): bool {
			$written[ $key ] = $value;
			return true;
		}
	);

	( new Approval() )->maybeMarkPending( 7 );

	expect( array_keys( $written ) )->toBe( array( Approval::META_PENDING ) );
	expect( $written[ Approval::META_PENDING ] )->toBeTrue();
	expect( $written )->not->toHaveKey( 'ink_writer_tier' );
	expect( $written )->not->toHaveKey( 'ink_writer_intent' );
	expect( $written )->not->toHaveKey( 'ink_membership' );
	expect( $written )->not->toHaveKey( 'ink_entitlement' );
} );

/**
 * AC-2 (documented trusted-bypass edge): an account created in wp-admin by a
 * moderator (the actor holds `ink_moderate`) is NOT queued, even when ON — the
 * queue vets untrusted self-registration only.
 */
test( 'maybeMarkPending trusted-bypasses an account created by a moderator', function (): void {
	Functions\when( 'get_option' )->justReturn( true ); // ON.
	Functions\when( 'current_user_can' )->justReturn( true ); // moderator actor.
	Functions\expect( 'update_user_meta' )->never();

	( new Approval() )->maybeMarkPending( 7 );
} );

/**
 * AC-1: the login gate is a no-op when the backstop is OFF — login stays
 * frictionless, and flipping OFF instantly un-gates any still-pending account.
 */
test( 'blockPendingLogin is a no-op (returns the user) when OFF', function (): void {
	Functions\when( 'get_option' )->justReturn( false ); // OFF.

	$user   = new \WP_User( 5 );
	$result = ( new Approval() )->blockPendingLogin( $user );

	expect( $result )->toBe( $user );
} );

/**
 * AC-2/AC-4: when ON AND the account is pending, the gate returns an Afrikaans
 * `WP_Error` (zero English leakage) carrying the `ink_`-coded key — WP's
 * credential mechanism is used, not reimplemented.
 */
test( 'blockPendingLogin returns an Afrikaans WP_Error when ON and pending', function (): void {
	Functions\when( 'get_option' )->justReturn( true ); // ON.
	Functions\when( 'get_user_meta' )->alias(
		function ( $user_id, $key, $single ) {
			return Approval::META_PENDING === $key; // pending true, rejected false.
		}
	);

	$result = ( new Approval() )->blockPendingLogin( new \WP_User( 5 ) );

	expect( $result )->toBeInstanceOf( \WP_Error::class );
	expect( $result->get_error_code() )->toBe( Approval::ERROR_CODE );

	$message = strtolower( $result->get_error_message() );
	expect( $message )->toContain( 'goedkeuring' ); // Afrikaans status label.
	// Zero English leakage (Gate D).
	expect( $message )->not->toContain( 'account' );
	expect( $message )->not->toContain( 'pending' );
	expect( $message )->not->toContain( 'approval' );
	expect( $message )->not->toContain( 'waiting' );
} );

/**
 * AC-2/AC-4 (review patch): a REJECTED account gets a DISTINCT error code +
 * Afrikaans message — it is NOT told it is "waiting for approval".
 */
test( 'blockPendingLogin returns a distinct rejected WP_Error when ON and rejected', function (): void {
	Functions\when( 'get_option' )->justReturn( true ); // ON.
	Functions\when( 'get_user_meta' )->alias(
		function ( $user_id, $key, $single ) {
			return Approval::META_REJECTED === $key; // rejected true, pending false.
		}
	);

	$result = ( new Approval() )->blockPendingLogin( new \WP_User( 5 ) );

	expect( $result )->toBeInstanceOf( \WP_Error::class );
	expect( $result->get_error_code() )->toBe( Approval::ERROR_CODE_REJECTED );
	expect( $result->get_error_code() )->not->toBe( Approval::ERROR_CODE ); // not the pending code.

	$message = strtolower( $result->get_error_message() );
	expect( $message )->toContain( 'verwerp' ); // Afrikaans "rejected", not "waiting".
	// Zero English leakage (Gate D).
	expect( $message )->not->toContain( 'account' );
	expect( $message )->not->toContain( 'rejected' );
	expect( $message )->not->toContain( 'pending' );
	expect( $message )->not->toContain( 'waiting' );
} );

/**
 * AC-1: when ON but the account is NOT pending/rejected, the gate passes the
 * resolved user through unchanged.
 */
test( 'blockPendingLogin passes a non-pending user through unchanged', function (): void {
	Functions\when( 'get_option' )->justReturn( true ); // ON.
	Functions\when( 'get_user_meta' )->justReturn( '' ); // neither pending nor rejected.

	$user   = new \WP_User( 9 );
	$result = ( new Approval() )->blockPendingLogin( $user );

	expect( $result )->toBe( $user );
} );

/**
 * AC-2: the gate never OVERRIDES an upstream credential failure — a WP_Error
 * passed in (WP rejected the password) is returned unchanged, not replaced.
 */
test( 'blockPendingLogin passes an upstream WP_Error through unchanged', function (): void {
	Functions\when( 'get_option' )->justReturn( true ); // ON.

	$upstream = new \WP_Error( 'incorrect_password', 'x' );
	$result   = ( new Approval() )->blockPendingLogin( $upstream );

	expect( $result )->toBe( $upstream );
	expect( $result->get_error_code() )->toBe( 'incorrect_password' );
} );

/**
 * AC-3 + THE conflation rule: approve clears ONLY the approval flags (pending +
 * rejected) so the lid can log in — no entitlement / tier / membership write.
 */
test( 'approve clears only the approval flags, never entitlement/tier', function (): void {
	$deleted = array();

	Functions\when( 'get_userdata' )->justReturn( false ); // email path no-ops.
	Functions\when( 'delete_user_meta' )->alias(
		function ( $user_id, $key ) use ( &$deleted ): bool {
			$deleted[] = $key;
			return true;
		}
	);
	Functions\expect( 'update_user_meta' )->never(); // approve writes no new flag.

	Approval::approve( 7 );

	expect( $deleted )->toContain( Approval::META_PENDING );
	expect( $deleted )->toContain( Approval::META_REJECTED );
	expect( $deleted )->not->toContain( 'ink_writer_tier' );
	expect( $deleted )->not->toContain( 'ink_entitlement' );
} );

/**
 * AC-3: reject performs the documented (reversible "mark blocked") action — it
 * clears pending and SETS the rejected flag; it writes only the approval flags.
 */
test( 'reject clears pending and sets the rejected flag (documented action)', function (): void {
	$deleted = array();
	$written = array();

	Functions\when( 'get_userdata' )->justReturn( false ); // email path no-ops.
	Functions\when( 'delete_user_meta' )->alias(
		function ( $user_id, $key ) use ( &$deleted ): bool {
			$deleted[] = $key;
			return true;
		}
	);
	Functions\when( 'update_user_meta' )->alias(
		function ( $user_id, $key, $value ) use ( &$written ): bool {
			$written[ $key ] = $value;
			return true;
		}
	);

	Approval::reject( 7 );

	expect( $deleted )->toContain( Approval::META_PENDING );
	expect( array_keys( $written ) )->toBe( array( Approval::META_REJECTED ) );
	expect( $written[ Approval::META_REJECTED ] )->toBeTrue();
	expect( $written )->not->toHaveKey( 'ink_writer_tier' );
	expect( $written )->not->toHaveKey( 'ink_entitlement' );
} );

/**
 * AC-3/AC-5: approve/reject no-op for an invalid user id (never a stray write).
 */
test( 'approve and reject no-op for an invalid user id', function (): void {
	Functions\expect( 'delete_user_meta' )->never();
	Functions\expect( 'update_user_meta' )->never();

	Approval::approve( 0 );
	Approval::reject( 0 );
} );

/**
 * AC-5: register() wires the `user_register` stamp, the WP-native login gate, the
 * meta registration, the admin-menu queue, and BOTH `admin_post` write handlers.
 */
test( 'register() wires every backstop hook', function (): void {
	Functions\when( 'get_option' )->justReturn( array() );
	Functions\when( 'update_option' )->justReturn( true );

	( new Approval() )->register();

	expect( has_action( 'init', 'Ink\Accounts\Approval->registerMeta()' ) )->not->toBeFalse();
	expect( has_action( 'user_register', 'Ink\Accounts\Approval->maybeMarkPending()' ) )->not->toBeFalse();
	expect( has_filter( 'wp_authenticate_user', 'Ink\Accounts\Approval->blockPendingLogin()' ) )->not->toBeFalse();
	expect( has_action( 'admin_menu', 'Ink\Accounts\Approval->registerQueueScreen()' ) )->not->toBeFalse();
	expect(
		has_action( 'admin_post_' . Approval::ACTION_APPROVE, 'Ink\Accounts\Approval->approveViaPost()' )
	)->not->toBeFalse();
	expect(
		has_action( 'admin_post_' . Approval::ACTION_REJECT, 'Ink\Accounts\Approval->rejectViaPost()' )
	)->not->toBeFalse();
} );

/**
 * AC-4: BOTH decision-email templates register Afrikaans-source and DISABLED
 * (send toggle OFF until human copy lands) — and never reach wp_mail.
 *
 * Mirrors RegistrationTest: inject a known store via the facade and read back
 * registration state directly (an unregistered template is ALSO fail-safe OFF, so
 * a send()===false-only assertion would pass green even if registration broke).
 */
test( 'the approval + rejection emails register Afrikaans, DISABLED, and never send', function (): void {
	Functions\when( 'update_option' )->justReturn( true );
	Functions\when( 'get_option' )->justReturn( array() );
	Functions\expect( 'wp_mail' )->never(); // toggles OFF ⇒ no dispatch.

	$store    = new \Ink\Notifications\TemplateStore();
	$notifier = new \Ink\Notifications\Notifier( $store );
	$facade   = new \ReflectionClass( \Ink\Notifications\Api::class );
	foreach ( array( 'store', 'notifier' ) as $prop ) {
		$facade->getProperty( $prop )->setValue( null, null );
	}
	\Ink\Notifications\Api::bootstrap( $store, $notifier );

	( new Approval() )->registerEmailTemplates();

	foreach ( array( Approval::APPROVED_TEMPLATE_KEY, Approval::REJECTED_TEMPLATE_KEY ) as $key ) {
		expect( $store->isRegistered( $key ) )->toBeTrue();
		expect( $store->isEnabled( $key ) )->toBeFalse();
		expect( $store->body( $key ) )->toContain( '{skrywer}' );
	}

	// Afrikaans-source subjects (zero English leakage).
	$approved_subject = strtolower( $store->subject( Approval::APPROVED_TEMPLATE_KEY ) );
	expect( $approved_subject )->toContain( 'goedgekeur' );
	expect( $approved_subject )->not->toContain( 'approved' );

	// Send path gated OFF → false (and asserted: no wp_mail).
	$sent = \Ink\Notifications\Api::send( Approval::APPROVED_TEMPLATE_KEY, 'lid@ink.test', array( 'skrywer' => 'Jan' ) );
	expect( $sent )->toBeFalse();
} );

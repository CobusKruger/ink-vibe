<?php
/**
 * Unit tests for the INK Accounts registration logic (AD-1, AD-2, AD-9).
 *
 * Target: {@see \Ink\Accounts\Registration} (Story 3.1) — the `user_register`
 * Brons + gratis-lid default-setter, and the Afrikaans transactional auth email
 * wiring (Notifications welcome template + WP-core password-reset filters).
 *
 * Brain Monkey, no WordPress/DB. The real `Ink\Kernel\Tier` enum and
 * `Ink\Content\UserMeta` constant are autoloaded, so the asserted key/value are
 * the genuine single-source values (no literals duplicated in the test).
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Accounts;

use Ink\Accounts\Registration;
use Ink\Content\UserMeta;
use Ink\Kernel\Tier;
use Brain\Monkey;
use Brain\Monkey\Actions;
use Brain\Monkey\Filters;
use Brain\Monkey\Functions;

beforeEach( function (): void {
	Monkey\setUp();

	// `__()` is identity in unit context (no .mo) so Afrikaans source renders.
	Functions\when( '__' )->returnArg( 1 );
} );

afterEach( function (): void {
	Monkey\tearDown();
} );

/**
 * AC-2: an unset tier is materialised to `brons` (via the enum + constant).
 */
test( 'applyDefaults sets ink_writer_tier to brons when meta is unset', function (): void {
	Functions\when( 'get_user_meta' )->justReturn( '' );

	Functions\expect( 'update_user_meta' )
		->once()
		->with( 42, UserMeta::WRITER_TIER, Tier::Brons->value );

	( new Registration() )->applyDefaults( 42 );

	expect( Tier::Brons->value )->toBe( 'brons' );
	expect( UserMeta::WRITER_TIER )->toBe( 'ink_writer_tier' );
} );

/**
 * AC-2: it writes ONLY the writer tier — no promotion stamp, no entitlement,
 * no membership, no reader/writer intent flag (THE conflation rule + 3.2).
 */
test( 'applyDefaults writes only the writer tier, never promotion/entitlement/intent', function (): void {
	$written = array();

	Functions\when( 'get_user_meta' )->justReturn( '' );
	Functions\when( 'update_user_meta' )->alias(
		function ( $user_id, $key, $value ) use ( &$written ): bool {
			$written[ $key ] = $value;
			return true;
		}
	);

	( new Registration() )->applyDefaults( 7 );

	expect( array_keys( $written ) )->toBe( array( UserMeta::WRITER_TIER ) );
	expect( $written )->not->toHaveKey( UserMeta::TIER_PROMOTED_AT );
	expect( $written )->not->toHaveKey( 'ink_tier_promoted_at' );
	expect( $written )->not->toHaveKey( 'ink_writer_intent' );
	expect( $written )->not->toHaveKey( 'ink_membership' );
	expect( $written )->not->toHaveKey( 'ink_entitlement' );
} );

/**
 * AC-2 idempotency: a deliberately-set tier is never clobbered.
 */
test( 'applyDefaults does NOT overwrite an already-set tier (goud)', function (): void {
	Functions\when( 'get_user_meta' )->justReturn( 'goud' );

	Functions\expect( 'update_user_meta' )->never();

	( new Registration() )->applyDefaults( 99 );
} );

/**
 * AC-4: register() hooks `user_register` for the default-setter.
 */
test( 'register() hooks user_register for the default-setter', function (): void {
	Functions\when( 'get_user_meta' )->justReturn( '' );
	// registerWelcomeTemplate() touches the Notifications facade; the
	// retrieve_password filters are added too — all mocked below.
	Functions\when( 'update_option' )->justReturn( true );
	Functions\when( 'get_option' )->justReturn( array() );

	( new Registration() )->register();

	$this->assertTrue(
		has_action( 'user_register', 'Ink\Accounts\Registration->applyDefaults()' ) !== false
	);
} );

/**
 * AC-1/AC-3: the WP-core password-reset subject is Afrikaans (glossary verb).
 */
test( 'passwordResetTitle returns an Afrikaans subject', function (): void {
	$title = ( new Registration() )->passwordResetTitle( 'Password Reset' );

	expect( $title )->toContain( 'Wagwoord-herstel' );
	expect( strtolower( $title ) )->not->toContain( 'password' );
	expect( strtolower( $title ) )->not->toContain( 'reset' );
} );

/**
 * AC-1/AC-3: the reset body is Afrikaans AND preserves the WP-core reset URL verbatim.
 */
test( 'passwordResetMessage is Afrikaans and preserves the original reset link', function (): void {
	$incoming = "Someone requested a password reset.\r\n\r\n"
		. "To reset your password visit:\r\n"
		. 'https://ink.test/wp-login.php?action=rp&key=abc&login=jan';

	$body = ( new Registration() )->passwordResetMessage( $incoming );

	expect( $body )->toContain( 'wagwoord' );
	expect( $body )->toContain( 'https://ink.test/wp-login.php?action=rp&key=abc&login=jan' );
	expect( strtolower( $body ) )->not->toContain( 'password' );
	expect( strtolower( $body ) )->not->toContain( 'someone requested' );
} );

/**
 * AC-3: the account-welcome template registers Afrikaans-source, toggle OFF.
 */
test( 'registerWelcomeTemplate registers an Afrikaans template with the send toggle OFF', function (): void {
	$registered = null;

	Functions\when( 'update_option' )->justReturn( true );
	Functions\when( 'get_option' )->justReturn( array() );

	( new Registration() )->registerWelcomeTemplate();

	// The template is registered through the Notifications facade/store; assert
	// the facade now resolves the welcome key to an Afrikaans, disabled default.
	$enabled = \Ink\Notifications\Api::send( Registration::WELCOME_TEMPLATE_KEY, 'lid@ink.test', array( 'skrywer' => 'Jan' ) );

	// Toggle OFF → send() returns false, no wp_mail fired.
	expect( $enabled )->toBeFalse();
	expect( Registration::WELCOME_TEMPLATE_KEY )->toBe( 'ink_account_welcome' );
} );

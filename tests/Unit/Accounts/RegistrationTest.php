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
 * AC-1/AC-3 regression guard: register() also wires BOTH WP-core reset-mail
 * filters — dropping either silently re-introduces an English reset email
 * (Review P2 — the register() test previously only asserted `user_register`).
 */
test( 'register() wires both retrieve_password Afrikaans filters', function (): void {
	Functions\when( 'get_user_meta' )->justReturn( '' );
	Functions\when( 'update_option' )->justReturn( true );
	Functions\when( 'get_option' )->justReturn( array() );

	( new Registration() )->register();

	$this->assertTrue(
		has_filter( 'retrieve_password_title', 'Ink\Accounts\Registration->passwordResetTitle()' ) !== false
	);
	$this->assertTrue(
		has_filter( 'retrieve_password_message', 'Ink\Accounts\Registration->passwordResetMessage()' ) !== false
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
 * AC-3 / Review P1: a wrapped (`<URL>`) or trailing-punctuation reset link is
 * extracted CLEAN — the hardened regex stops at markup delimiters and trims
 * trailing sentence punctuation, so the angle brackets / period never corrupt
 * the link (the old greedy `\S+` captured them).
 */
test( 'passwordResetMessage extracts a wrapped / trailing-punctuation reset URL cleanly', function (): void {
	$url      = 'https://ink.test/wp-login.php?action=rp&key=abc&login=jan';
	$incoming = "Someone requested a password reset.\r\n\r\n"
		. "To reset your password visit:\r\n"
		. '<' . $url . '>.';

	$body = ( new Registration() )->passwordResetMessage( $incoming );

	expect( $body )->toContain( $url );
	expect( $body )->not->toContain( '<' . $url );
	expect( $body )->not->toContain( $url . '>' );
	expect( $body )->not->toContain( $url . '.' );
} );

/**
 * AC-3 / Review (decision: keep-as-is) — DOCUMENTS the accepted behavior when no
 * URL can be extracted: the body stays fully Afrikaans (zero English leakage) and
 * simply carries no link. The owner accepted this over an English-body fallback.
 */
test( 'passwordResetMessage stays Afrikaans (no English leak) when the body has no URL', function (): void {
	$body = ( new Registration() )->passwordResetMessage( 'Someone requested a password reset. No link present.' );

	expect( $body )->toContain( 'wagwoord' );
	expect( $body )->not->toContain( 'http' );
	expect( strtolower( $body ) )->not->toContain( 'password' );
	expect( strtolower( $body ) )->not->toContain( 'someone requested' );
} );

/**
 * AC-3: the account-welcome template registers Afrikaans-source, toggle OFF.
 *
 * Review P2: assert the template is actually REGISTERED and DISABLED with an
 * Afrikaans subject — not merely that `send()` returns false. An unregistered
 * template is also fail-safe OFF (TemplateStore::isEnabled → false), so a
 * `send()===false`-only assertion would pass green even if registration broke.
 * We inject a known store via the facade and read it back, and assert wp_mail
 * NEVER fires while the toggle is OFF.
 */
test( 'registerWelcomeTemplate registers an Afrikaans, DISABLED template and never sends', function (): void {
	Functions\when( 'update_option' )->justReturn( true );
	Functions\when( 'get_option' )->justReturn( array() );
	// The toggle is OFF, so the dispatch path must never reach wp_mail.
	Functions\expect( 'wp_mail' )->never();

	// Reset + wire the facade to a store we hold, so we can read registration
	// state directly (the facade exposes only send()/randomMessage()).
	$store    = new \Ink\Notifications\TemplateStore();
	$notifier = new \Ink\Notifications\Notifier( $store );
	$facade   = new \ReflectionClass( \Ink\Notifications\Api::class );
	foreach ( array( 'store', 'notifier' ) as $prop ) {
		// setAccessible() is a no-op since PHP 8.1 (deprecated 8.5); private
		// statics are reflection-writable without it.
		$facade->getProperty( $prop )->setValue( null, null );
	}
	\Ink\Notifications\Api::bootstrap( $store, $notifier );

	( new Registration() )->registerWelcomeTemplate();

	// Genuinely registered (not just absent → fail-safe OFF)...
	expect( $store->isRegistered( Registration::WELCOME_TEMPLATE_KEY ) )->toBeTrue();
	// ...disabled by default (no wp_mail until human copy lands)...
	expect( $store->isEnabled( Registration::WELCOME_TEMPLATE_KEY ) )->toBeFalse();
	// ...and Afrikaans-source (zero English leakage).
	$subject = $store->subject( Registration::WELCOME_TEMPLATE_KEY );
	expect( $subject )->toContain( 'Welkom' );
	expect( strtolower( $subject ) )->not->toContain( 'welcome' );
	expect( $store->body( Registration::WELCOME_TEMPLATE_KEY ) )->toContain( '{skrywer}' );

	// The send path is gated OFF → false, and (asserted above) no wp_mail.
	$sent = \Ink\Notifications\Api::send( Registration::WELCOME_TEMPLATE_KEY, 'lid@ink.test', array( 'skrywer' => 'Jan' ) );
	expect( $sent )->toBeFalse();
	expect( Registration::WELCOME_TEMPLATE_KEY )->toBe( 'ink_account_welcome' );
} );

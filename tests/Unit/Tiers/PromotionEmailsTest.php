<?php
/**
 * Unit tests for the auto-promotion congratulation email (Story 5.10).
 *
 * Target: {@see \Ink\Tiers\PromotionEmails} — registers the two Afrikaans-source
 * templates (toggle OFF) and dispatches via the 1.12 Notifications store on an
 * AUTOMATIC promotion only.
 *
 * Harness mirrors PurchaseActivationTest: a real Notifications store/notifier is
 * wired into the facade (reset per test via reflection); the send toggle is
 * driven by a `get_option` READ; `wp_mail` is asserted. No network.
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Tiers;

use Ink\Kernel\Tier;
use Ink\Tiers\PromotionEmails;
use Ink\Notifications\Api as Notifications;
use Ink\Notifications\Notifier;
use Ink\Notifications\TemplateStore;
use Brain\Monkey;
use Brain\Monkey\Functions;

beforeEach( function (): void {
	Monkey\setUp();
	Functions\when( '__' )->returnArg( 1 );

	// Reset the Notifications facade so each test wires a known store.
	$facade = new \ReflectionClass( Notifications::class );
	foreach ( array( 'store', 'notifier' ) as $prop ) {
		$facade->getProperty( $prop )->setValue( null, null );
	}
} );

afterEach( function (): void {
	Monkey\tearDown();
} );

/**
 * Wire a fresh store + notifier into the Notifications facade and return the store.
 */
function ink_tiers_wire_notifications(): TemplateStore {
	$store    = new TemplateStore();
	$notifier = new Notifier( $store );
	Notifications::bootstrap( $store, $notifier );

	return $store;
}

/**
 * Drive the given template toggles ON via a `get_option` READ (never a write).
 */
function ink_tiers_enable_toggles( array $keys ): void {
	Functions\when( 'get_option' )->alias(
		function ( string $name, $default = false ) use ( $keys ) {
			if ( TemplateStore::OPTION === $name ) {
				$row = array();
				foreach ( $keys as $key ) {
					$row[ $key ] = array( 'enabled' => true );
				}
				return $row;
			}
			return $default;
		}
	);
}

/**
 * A genuine WP_User double (the recipient + greeting source).
 */
function ink_tiers_userdata( string $email, string $display = '', string $login = 'skrywer' ): \WP_User {
	return new \WP_User( 7, $email, $display, $login );
}

/**
 * AC-3: the hook + template keys are the exact single-source constants.
 */
test( 'the hook and template keys are the exact constants', function (): void {
	expect( PromotionEmails::HOOK )->toBe( 'ink/tier_promoted' );
	expect( PromotionEmails::SILWER_TEMPLATE_KEY )->toBe( 'ink_tier_promoted_silwer_email' );
	expect( PromotionEmails::GOUD_TEMPLATE_KEY )->toBe( 'ink_tier_promoted_goud_email' );
} );

/**
 * AC-2: both templates register Afrikaans-source and DISABLED by default.
 */
test( 'both templates register Afrikaans-source and disabled by default', function (): void {
	Functions\when( 'get_option' )->justReturn( array() );
	$store = ink_tiers_wire_notifications();

	( new PromotionEmails() )->registerTemplates();

	expect( $store->isRegistered( PromotionEmails::SILWER_TEMPLATE_KEY ) )->toBeTrue();
	expect( $store->isRegistered( PromotionEmails::GOUD_TEMPLATE_KEY ) )->toBeTrue();
	expect( $store->isEnabled( PromotionEmails::SILWER_TEMPLATE_KEY ) )->toBeFalse();
	expect( $store->isEnabled( PromotionEmails::GOUD_TEMPLATE_KEY ) )->toBeFalse();
} );

/**
 * AC-1: an automatic promotion to Silwer sends the congratulation email once.
 */
test( 'an auto promotion to Silwer sends one email', function (): void {
	ink_tiers_enable_toggles( array( PromotionEmails::SILWER_TEMPLATE_KEY ) );
	ink_tiers_wire_notifications();
	Functions\when( 'get_userdata' )->justReturn( ink_tiers_userdata( 'skrywer@ink.test', 'Jan' ) );
	Functions\expect( 'wp_mail' )->once()->andReturn( true );

	$emails = new PromotionEmails();
	$emails->registerTemplates();
	$emails->onTierPromoted( 7, Tier::Brons, Tier::Silwer, 0, 0 );
} );

/**
 * AC-1: an automatic promotion to Goud sends once.
 */
test( 'an auto promotion to Goud sends one email', function (): void {
	ink_tiers_enable_toggles( array( PromotionEmails::GOUD_TEMPLATE_KEY ) );
	ink_tiers_wire_notifications();
	Functions\when( 'get_userdata' )->justReturn( ink_tiers_userdata( 'skrywer@ink.test', 'Jan' ) );
	Functions\expect( 'wp_mail' )->once()->andReturn( true );

	$emails = new PromotionEmails();
	$emails->registerTemplates();
	$emails->onTierPromoted( 7, Tier::Silwer, Tier::Goud, 0, 0 );
} );

/**
 * AC-1: a MANUAL promotion (actor != 0) sends nothing.
 */
test( 'a manual promotion sends nothing', function (): void {
	ink_tiers_enable_toggles( array( PromotionEmails::SILWER_TEMPLATE_KEY ) );
	ink_tiers_wire_notifications();
	Functions\expect( 'get_userdata' )->never();
	Functions\expect( 'wp_mail' )->never();

	$emails = new PromotionEmails();
	$emails->registerTemplates();
	$emails->onTierPromoted( 7, Tier::Brons, Tier::Silwer, 3, 0 ); // actor 3 = staff.
} );

/**
 * AC-1: a promotion to a non-auto target (Meester) sends nothing — no template.
 */
test( 'a promotion to Meester sends nothing', function (): void {
	ink_tiers_enable_toggles( array( PromotionEmails::SILWER_TEMPLATE_KEY, PromotionEmails::GOUD_TEMPLATE_KEY ) );
	ink_tiers_wire_notifications();
	Functions\expect( 'get_userdata' )->never();
	Functions\expect( 'wp_mail' )->never();

	$emails = new PromotionEmails();
	$emails->registerTemplates();
	$emails->onTierPromoted( 7, Tier::Goud, Tier::Meester, 0, 0 );
} );

/**
 * AC-2: a missing/invalid user sends nothing.
 */
test( 'an invalid user sends nothing', function (): void {
	ink_tiers_enable_toggles( array( PromotionEmails::SILWER_TEMPLATE_KEY ) );
	ink_tiers_wire_notifications();
	Functions\when( 'get_userdata' )->justReturn( false );
	Functions\expect( 'wp_mail' )->never();

	$emails = new PromotionEmails();
	$emails->registerTemplates();
	$emails->onTierPromoted( 7, Tier::Brons, Tier::Silwer, 0, 0 );
} );

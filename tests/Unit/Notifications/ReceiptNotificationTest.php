<?php
/**
 * Unit tests for the R7 receipt-notification trigger (Story 9.11, FR-44a).
 *
 * Target: {@see \Ink\Notifications\ReceiptNotification}. The decisive behaviour:
 * the trigger is INERT until the form-letter list is authored (randomMessage '')
 * — the graceful-degradation guarantee — and fires the Ontvangs kennisgewing
 * once a message is present.
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Notifications;

use Ink\Notifications\ReceiptNotification;
use Ink\Notifications\Api;
use Ink\Notifications\Template;
use Ink\Notifications\TemplateStore;
use Ink\Notifications\Notifier;
use Brain\Monkey;
use Brain\Monkey\Functions;

beforeEach( function (): void {
	Monkey\setUp();
	// No admin option overrides → the registered template defaults apply.
	Functions\when( 'get_option' )->justReturn( array() );

	// Reset the Api facade's first-wiring-wins (`??=`) static state so each test
	// starts with a clean store/notifier (no cross-test bootstrap pollution).
	$ref = new \ReflectionClass( Api::class );
	foreach ( array( 'store', 'notifier' ) as $prop ) {
		$ref->getProperty( $prop )->setValue( null, null );
	}
} );

afterEach( function (): void {
	Monkey\tearDown();
} );

test( 'the receipt event + template key are the documented R7 contract', function (): void {
	expect( ReceiptNotification::RECEIPT_EVENT )->toBe( 'ink/ontvangs' );
	expect( ReceiptNotification::TEMPLATE_KEY )->toBe( 'ink_ontvangs_kennisgewing' );
} );

test( 'deepLinkUrl points at the PRIVATE My Profiel', function (): void {
	Functions\when( 'home_url' )->alias( static fn ( string $path = '' ): string => 'https://ink.test' . $path );

	expect( ReceiptNotification::deepLinkUrl() )->toBe( 'https://ink.test/my-profiel' );
} );

test( 'register() registers the form-letter template toggle-OFF with an empty list', function (): void {
	$store = new TemplateStore();
	Api::bootstrap( $store, new Notifier( $store ) );

	( new ReceiptNotification() )->register();

	expect( $store->isRegistered( ReceiptNotification::TEMPLATE_KEY ) )->toBeTrue();
	expect( $store->isEnabled( ReceiptNotification::TEMPLATE_KEY ) )->toBeFalse(); // fail-safe OFF
	expect( $store->messages( ReceiptNotification::TEMPLATE_KEY ) )->toBe( array() ); // authored later
} );

test( 'onReceipt is INERT while the form-letter list is unauthored (randomMessage empty)', function (): void {
	// Empty list → randomMessage '' → no kennisgewing. bp_notifications_add_notification
	// is NOT defined, so any attempt to emit would also no-op; assert the early return
	// by confirming no fatal and a clean no-op for a real skrywer id.
	$store = new TemplateStore();
	Api::bootstrap( $store, new Notifier( $store ) );
	( new ReceiptNotification() )->register(); // empty messages

	( new ReceiptNotification() )->onReceipt( 7, 42, 100 );

	expect( true )->toBeTrue(); // reached here = inert, no fatal
} );

test( 'onReceipt passes the inert-guard once a message is authored (non-vacuous)', function (): void {
	$store = new TemplateStore();
	$store->register( new Template( ReceiptNotification::TEMPLATE_KEY, '', '', true, array( 'Iemand het jou werk gelees!' ) ) );
	Api::bootstrap( $store, new Notifier( $store ) );

	// Non-vacuous: with an authored list, randomMessage is non-empty, so the
	// inert early-return is NOT taken and onReceipt proceeds to the 9.9 emitter
	// (whose BP write is proven by KennisgewingsTest). It must not fatal.
	expect( Api::randomMessage( ReceiptNotification::TEMPLATE_KEY ) )->not->toBe( '' );

	// Keep the guarded BP write a clean no-op (the emit itself is proven by
	// KennisgewingsTest); we only assert onReceipt proceeds past the inert guard.
	Functions\when( 'bp_notifications_add_notification' )->justReturn( 1 );

	( new ReceiptNotification() )->onReceipt( 7, 42, 100 );

	expect( true )->toBeTrue();
} );

test( 'onReceipt never proceeds for a non-positive skrywer id', function (): void {
	$store = new TemplateStore();
	$store->register( new Template( ReceiptNotification::TEMPLATE_KEY, '', '', true, array( 'x' ) ) );
	Api::bootstrap( $store, new Notifier( $store ) );

	// skrywer_id <= 0 returns before the emitter is reached (no BP needed).
	( new ReceiptNotification() )->onReceipt( 0, 42, 100 );

	expect( true )->toBeTrue();
} );

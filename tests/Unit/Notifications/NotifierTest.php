<?php
/**
 * Unit tests for the toggle-gated transactional dispatcher (AD-9).
 *
 * Target: {@see \Ink\Notifications\Notifier} (Story 1.12).
 *
 * Runs against the Story 1.11 harness (Pest + Brain Monkey via
 * tests/bootstrap.php). `get_option`, `wp_mail` and `wp_rand` are stubbed.
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Notifications;

use Ink\Notifications\Notifier;
use Ink\Notifications\Template;
use Ink\Notifications\TemplateStore;
use Brain\Monkey;
use Brain\Monkey\Functions;

beforeEach( function (): void {
	Monkey\setUp();
} );

afterEach( function (): void {
	Monkey\tearDown();
} );

test( 'does NOT send when the per-event toggle is off', function (): void {
	Functions\when( 'get_option' )->justReturn( array() );
	Functions\expect( 'wp_mail' )->never();

	$store = new TemplateStore();
	$store->register( new Template( 'promo', 'Baie geluk', 'Beste {skrywer}.', false ) );

	expect( ( new Notifier( $store ) )->send( 'promo', 'jan@voorbeeld.test', array( 'skrywer' => 'Jan' ) ) )
		->toBeFalse();
} );

test( 'does NOT send when the recipient is empty', function (): void {
	Functions\when( 'get_option' )->justReturn( array() );
	Functions\expect( 'wp_mail' )->never();

	$store = new TemplateStore();
	$store->register( new Template( 'promo', 'Baie geluk', 'Beste {skrywer}.', true ) );

	expect( ( new Notifier( $store ) )->send( 'promo', '', array( 'skrywer' => 'Jan' ) ) )->toBeFalse();
} );

test( 'sends the merged Afrikaans subject + body via wp_mail when enabled', function (): void {
	Functions\when( 'get_option' )->justReturn( array() );
	Functions\expect( 'wp_mail' )
		->once()
		->with( 'jan@voorbeeld.test', 'Baie geluk', 'Beste Jan, jy is na Silwer bevorder.' )
		->andReturn( true );

	$store = new TemplateStore();
	$store->register( new Template( 'promo', 'Baie geluk', 'Beste {skrywer}, jy is na Silwer bevorder.', true ) );

	expect( ( new Notifier( $store ) )->send( 'promo', 'jan@voorbeeld.test', array( 'skrywer' => 'Jan' ) ) )
		->toBeTrue();
} );

test( 'randomMessage returns a member of the stored list (R7 mechanism)', function (): void {
	Functions\when( 'get_option' )->justReturn( array() );
	Functions\when( 'wp_rand' )->justReturn( 1 );

	$store = new TemplateStore();
	$store->register( new Template( 'receipt', '', '', true, array( 'Dankie!', 'Ontvang, dankie!', 'Mooi so!' ) ) );

	expect( ( new Notifier( $store ) )->randomMessage( 'receipt' ) )->toBe( 'Ontvang, dankie!' );
} );

test( 'randomMessage handles empty and single-item lists', function (): void {
	Functions\when( 'get_option' )->justReturn( array() );

	$store = new TemplateStore();
	$store->register( new Template( 'leeg', '', '', true, array() ) );
	$store->register( new Template( 'een', '', '', true, array( 'Slegs een.' ) ) );

	$notifier = new Notifier( $store );

	expect( $notifier->randomMessage( 'leeg' ) )->toBe( '' );
	expect( $notifier->randomMessage( 'een' ) )->toBe( 'Slegs een.' );
} );

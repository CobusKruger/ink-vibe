<?php
/**
 * Unit tests for the options-backed form-letter store (AD-9).
 *
 * Target: {@see \Ink\Notifications\TemplateStore} (Story 1.12).
 *
 * Runs against the Story 1.11 harness (Pest + Brain Monkey via
 * tests/bootstrap.php). `get_option`/`update_option` are stubbed per test.
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Notifications;

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

test( 'returns the Afrikaans default when no override is stored', function (): void {
	Functions\when( 'get_option' )->justReturn( array() );

	$store = new TemplateStore();
	$store->register( new Template( 'tier_promotion', 'Baie geluk', 'Beste {skrywer}, jy is bevorder.', true ) );

	expect( $store->subject( 'tier_promotion' ) )->toBe( 'Baie geluk' );
	expect( $store->body( 'tier_promotion' ) )->toBe( 'Beste {skrywer}, jy is bevorder.' );
	expect( $store->isEnabled( 'tier_promotion' ) )->toBeTrue();
} );

test( 'an admin override wins over the registered default', function (): void {
	Functions\when( 'get_option' )->justReturn(
		array( 'tier_promotion' => array( 'body' => 'Aangepaste teks vir {skrywer}.', 'enabled' => false ) )
	);

	$store = new TemplateStore();
	$store->register( new Template( 'tier_promotion', 'Baie geluk', 'Beste {skrywer}.', true ) );

	expect( $store->body( 'tier_promotion' ) )->toBe( 'Aangepaste teks vir {skrywer}.' );
	expect( $store->isEnabled( 'tier_promotion' ) )->toBeFalse();
	// Subject was not overridden, so it falls through to the default.
	expect( $store->subject( 'tier_promotion' ) )->toBe( 'Baie geluk' );
} );

test( 'an unregistered/unconfigured event is fail-safe OFF and empty', function (): void {
	Functions\when( 'get_option' )->justReturn( array() );

	$store = new TemplateStore();

	expect( $store->isRegistered( 'nope' ) )->toBeFalse();
	expect( $store->isEnabled( 'nope' ) )->toBeFalse();
	expect( $store->body( 'nope' ) )->toBe( '' );
	expect( $store->messages( 'nope' ) )->toBe( array() );
} );

test( 'setBody persists a merged override row via update_option', function (): void {
	Functions\when( 'get_option' )->justReturn( array() );
	Functions\expect( 'update_option' )
		->once()
		->with( TemplateStore::OPTION, \Mockery::type( 'array' ) )
		->andReturn( true );

	( new TemplateStore() )->setBody( 'receipt', 'Dankie {skrywer}.' );
} );

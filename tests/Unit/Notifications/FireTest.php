<?php
/**
 * Unit tests for Notifier::fire() / deliver() / preview(), member opt-outs and store reset.
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Notifications;

use Brain\Monkey;
use Brain\Monkey\Functions;
use Ink\Notifications\Notifier;
use Ink\Notifications\Preferences;
use Ink\Notifications\Template;
use Ink\Notifications\TemplateStore;

beforeEach( function (): void {
	Monkey\setUp();
	Functions\stubs(
		array(
			'esc_url'  => static fn ( $v ) => $v,
			'esc_html' => static fn ( $v ) => $v,
			'__'       => static fn ( $v ) => $v,
		)
	);
} );

afterEach( function (): void {
	Monkey\tearDown();
} );

/**
 * A store with one system + one social event, both enabled.
 */
function ink_fire_store(): TemplateStore {
	$store = new TemplateStore();
	$store->register( new Template( 'sys', 'Sistem', 'Hallo {skrywer}', true ) );
	$store->register( new Template( 'soc', 'Sosiaal', 'Hallo {skrywer}, {werk}', true, array(), Template::KIND_SOCIAL, 'G', 'L', array( 'skrywer', 'werk' ), 'reaksies', 'reaksies' ) );

	return $store;
}

function ink_fire_user(): \WP_User {
	$user               = new \WP_User();
	$user->user_email   = 'jan@voorbeeld.test';
	$user->display_name = 'Jan';
	$user->user_login   = 'jan';

	return $user;
}

test( 'fire sends a system event to the resolved user and ignores opt-outs', function (): void {
	Functions\when( 'get_option' )->justReturn( array() );
	Functions\when( 'get_userdata' )->justReturn( ink_fire_user() );
	Functions\when( 'get_user_meta' )->justReturn( array( 'reaksies' ) );
	Functions\expect( 'wp_mail' )->once()->with( 'jan@voorbeeld.test', 'Sistem', \Mockery::type( 'string' ), \Mockery::type( 'array' ) )->andReturn( true );

	expect( ( new Notifier( ink_fire_store() ) )->fire( 'sys', 7 ) )->toBeTrue();
} );

test( 'fire suppresses a social event the member opted out of', function (): void {
	Functions\when( 'get_option' )->justReturn( array() );
	Functions\when( 'get_user_meta' )->justReturn( array( 'reaksies' ) );
	Functions\expect( 'wp_mail' )->never();

	expect( ( new Notifier( ink_fire_store() ) )->fire( 'soc', 7, array( 'werk' => 'Lente' ) ) )->toBeFalse();
} );

test( 'fire sends a social event (no Action Scheduler) with an unsubscribe footer', function (): void {
	Functions\when( 'get_option' )->justReturn( array() );
	Functions\when( 'get_userdata' )->justReturn( ink_fire_user() );
	Functions\when( 'get_user_meta' )->justReturn( '' );
	Functions\when( 'wp_salt' )->justReturn( 'salt' );
	Functions\when( 'home_url' )->justReturn( 'https://ink.test/' );
	Functions\when( 'add_query_arg' )->alias( static fn ( $args, $url ) => $url . '?' . http_build_query( $args ) );
	Functions\expect( 'wp_mail' )->once()->with(
		'jan@voorbeeld.test',
		'Sosiaal',
		\Mockery::on( static fn ( $html ): bool => str_contains( (string) $html, 'Hallo Jan, Lente' ) && str_contains( (string) $html, 'ink_unsub=reaksies' ) ),
		\Mockery::type( 'array' )
	)->andReturn( true );

	expect( ( new Notifier( ink_fire_store() ) )->fire( 'soc', 7, array( 'werk' => 'Lente' ) ) )->toBeTrue();
} );

test( 'fire does nothing for an unregistered or disabled event', function (): void {
	Functions\when( 'get_option' )->justReturn( array() );
	Functions\expect( 'wp_mail' )->never();

	$store = new TemplateStore();
	$store->register( new Template( 'uit', 'x', 'y', false ) );
	$notifier = new Notifier( $store );

	expect( $notifier->fire( 'bestaan_nie', 7 ) )->toBeFalse();
	expect( $notifier->fire( 'uit', 7 ) )->toBeFalse();
} );

test( 'preview sends even when the event is switched off', function (): void {
	Functions\when( 'get_option' )->justReturn( array() );
	Functions\expect( 'wp_mail' )->once()->andReturn( true );

	$store = new TemplateStore();
	$store->register( new Template( 'uit', 'x', 'y', false ) );

	expect( ( new Notifier( $store ) )->preview( 'uit', 'admin@voorbeeld.test' ) )->toBeTrue();
} );

test( 'opt-outs read the user meta and the unsubscribe token is per user', function (): void {
	Functions\when( 'get_user_meta' )->justReturn( array( 'reaksies' ) );
	Functions\when( 'wp_salt' )->justReturn( 'salt' );

	expect( Preferences::optOuts( 3 ) )->toBe( array( 'reaksies' ) );
	expect( Preferences::isOptedOut( 3, 'reaksies' ) )->toBeTrue();
	expect( Preferences::isOptedOut( 3, 'ander' ) )->toBeFalse();
	expect( Preferences::token( 3, 'reaksies' ) )->not->toBe( Preferences::token( 4, 'reaksies' ) );
} );

test( 'store reset drops the event override and its per-term toggles only', function (): void {
	$saved = null;
	Functions\when( 'get_option' )->justReturn(
		array(
			'ev'     => array( 'body' => 'x' ),
			'ev_6'   => array( 'enabled' => true ),
			'ev_12'  => array( 'enabled' => true ),
			'ev_foo' => array( 'enabled' => true ),
			'ander'  => array( 'enabled' => true ),
		)
	);
	Functions\when( 'update_option' )->alias(
		static function ( $name, $value ) use ( &$saved ): bool {
			$saved = $value;

			return true;
		}
	);

	( new TemplateStore() )->reset( 'ev' );

	expect( array_keys( (array) $saved ) )->toBe( array( 'ev_foo', 'ander' ) );
} );

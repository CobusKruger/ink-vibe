<?php
/**
 * Unit tests for the reserved Biblioteek auto-update hook (Story 10.6, R4 stub).
 *
 * Target: {@see \Ink\Library\AutoUpdate} — the reserved `ink/biblioteek_wen_bywerking`
 * event seam. The hook EXISTS and is INVOKED at P0 (so 12A.3 can wire to it without
 * rework); its body is a documented no-op (deferred to §9.4).
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Library;

use Ink\Library\AutoUpdate;
use Brain\Monkey;
use Brain\Monkey\Actions;

beforeEach( function (): void {
	Monkey\setUp();
} );

afterEach( function (): void {
	Monkey\tearDown();
} );

test( 'HOOK follows the INK ink/... event-surface convention', function (): void {
	expect( AutoUpdate::HOOK )->toBe( 'ink/biblioteek_wen_bywerking' );
} );

test( 'register wires the deferred-body listener to the reserved hook', function (): void {
	Actions\expectAdded( AutoUpdate::HOOK )->once();

	( new AutoUpdate() )->register();
} );

test( 'triggerForWinner fires the hook once with the winner payload for a positive uitdaging id', function (): void {
	Actions\expectDone( AutoUpdate::HOOK )->once()->with( 7, array( 11, 22 ) );

	AutoUpdate::triggerForWinner( 7, array( 11, 22 ) );
} );

test( 'triggerForWinner is fail-safe — it never fires for a non-positive uitdaging id', function (): void {
	Actions\expectDone( AutoUpdate::HOOK )->never();

	AutoUpdate::triggerForWinner( 0, array( 11 ) );
	AutoUpdate::triggerForWinner( -3 );
} );

test( 'onWinnerCommitted is a callable no-op at P0 (the deferred body writes nothing)', function (): void {
	// The body is deferred (§9.4): it must be callable and side-effect-free. No WP
	// write function is stubbed, so any post/meta write inside would error the test.
	( new AutoUpdate() )->onWinnerCommitted( 7, array( 11, 22 ) );

	expect( true )->toBeTrue();
} );

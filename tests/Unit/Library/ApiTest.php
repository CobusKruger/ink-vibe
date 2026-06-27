<?php
/**
 * Unit tests for the Library module facade (Story 10.6, R4 stub).
 *
 * Target: {@see \Ink\Library\Api} — the sole public cross-module surface. Story
 * 12A.3 (R2 winner ingestion) reaches the reserved Biblioteek auto-update seam
 * through this facade, never `AutoUpdate` internals.
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Library;

use Ink\Library\Api;
use Ink\Library\AutoUpdate;
use Brain\Monkey;
use Brain\Monkey\Actions;

beforeEach( function (): void {
	Monkey\setUp();
} );

afterEach( function (): void {
	Monkey\tearDown();
} );

test( 'winnerCommittedHook exposes the AutoUpdate hook constant (single source)', function (): void {
	expect( Api::winnerCommittedHook() )->toBe( AutoUpdate::HOOK );
} );

test( 'notifyWinnerCommitted delegates to the seam and fires the hook once', function (): void {
	Actions\expectDone( AutoUpdate::HOOK )->once()->with( 7, array( 11 ) );

	Api::notifyWinnerCommitted( 7, array( 11 ) );
} );

test( 'notifyWinnerCommitted is fail-safe via the seam — no fire for a non-positive id', function (): void {
	Actions\expectDone( AutoUpdate::HOOK )->never();

	Api::notifyWinnerCommitted( 0 );
} );

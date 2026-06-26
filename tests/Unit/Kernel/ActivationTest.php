<?php
/**
 * Unit tests for the version-gated schema-upgrade path (code review of Epic 5
 * Group A — audit-durability fix).
 *
 * Target: {@see \Ink\Kernel\Activation::maybeUpgrade()} — the `admin_init`
 * routine that re-runs the idempotent dbDelta schema install when the plugin
 * code is newer than the recorded DB version, so a custom table added in a
 * later release is created on an in-place plugin update (not only on
 * reactivation). The Kernel Schema registry is empty in unit context, so
 * Schema::install() is a no-op; these tests pin the version gate itself.
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Kernel;

use Ink\Kernel\Activation;
use Brain\Monkey;
use Brain\Monkey\Functions;

beforeEach( function (): void {
	Monkey\setUp();
	defined( 'INK_CORE_VERSION' ) || define( 'INK_CORE_VERSION', '0.1.0' );
} );

afterEach( function (): void {
	Monkey\tearDown();
} );

/**
 * An unset DB-version option installs the schema and records the current version.
 */
test( 'maybeUpgrade installs and records the version when the DB version is unset', function (): void {
	Functions\when( 'get_option' )->justReturn( '' );
	Functions\expect( 'update_option' )->once()->with( Activation::DB_VERSION_OPTION, INK_CORE_VERSION );

	Activation::maybeUpgrade();
} );

/**
 * A recorded version older than the code triggers the upgrade.
 */
test( 'maybeUpgrade upgrades when the recorded version is older than the code', function (): void {
	Functions\when( 'get_option' )->justReturn( '0.0.1' );
	Functions\expect( 'update_option' )->once()->with( Activation::DB_VERSION_OPTION, INK_CORE_VERSION );

	Activation::maybeUpgrade();
} );

/**
 * A current (or newer) recorded version is a no-op — no re-install, no write.
 */
test( 'maybeUpgrade is a no-op when the recorded version is current', function (): void {
	Functions\when( 'get_option' )->justReturn( INK_CORE_VERSION );
	Functions\expect( 'update_option' )->never();

	Activation::maybeUpgrade();
} );

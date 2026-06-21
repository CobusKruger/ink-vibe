<?php
/**
 * Custom-table schema registry.
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Kernel;

defined( 'ABSPATH' ) || exit;

/**
 * Kernel-owned registry of custom-table schemas.
 *
 * AD-5/AD-1: each module owns its custom tables but the Kernel owns the schema
 * REGISTRY so all `dbDelta()` installs run through one ordered place at
 * activation. Modules register their table DDL via {@see Schema::register()}
 * in a later epic; {@see Schema::install()} then applies them through
 * `dbDelta()`.
 *
 * At 1.7 the registry is EMPTY — no table DDL is declared and `install()` is a
 * no-op. This is the reserved extension point, not an implementation. No
 * `$wpdb` query, no `dbDelta()` table DDL is written here yet.
 *
 * @package Ink\Core
 */
final class Schema {

	/**
	 * Registered table-schema providers, keyed by table identifier.
	 *
	 * Each value is a callable returning the `dbDelta()`-compatible SQL for one
	 * custom table. Empty at 1.7.
	 *
	 * @var array<string, callable(): string>
	 */
	private static array $providers = [];

	/**
	 * Register a custom-table schema provider.
	 *
	 * Reserved seam for later modules (e.g. follow graph, tier history). The
	 * provider returns the `dbDelta()` SQL for its table when `install()` runs.
	 *
	 * @param string            $id       Unique table identifier.
	 * @param callable(): string $provider Returns dbDelta-compatible SQL.
	 */
	public static function register( string $id, callable $provider ): void {
		self::$providers[ $id ] = $provider;
	}

	/**
	 * Apply every registered schema through `dbDelta()`.
	 *
	 * Invoked by {@see Activation::activate()}. No-op while the registry is
	 * empty (the 1.7 state). When modules register providers in later epics,
	 * this is where their tables are created/upgraded.
	 */
	public static function install(): void {
		if ( [] === self::$providers ) {
			return;
		}

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		foreach ( self::$providers as $provider ) {
			dbDelta( $provider() );
		}
	}
}

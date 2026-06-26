<?php
/**
 * Activation / deactivation handlers.
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Kernel;

use Ink\Content\PostTypes;

defined( 'ABSPATH' ) || exit;

/**
 * Plugin activation and deactivation lifecycle.
 *
 * Activation records the schema-version option, runs the (currently empty)
 * schema registry, grants the INK editorial custom caps to the `editor` role
 * (Story 3.3 / AD-6 — role/cap grants persist, so they run at activation, not on
 * every `init`), flushes rewrite rules for the Epic-2 CPTs/taxonomies, and guards
 * the PHP 8.3 / WP 7.0 minimum. It creates NO tables, CPTs or terms — each module
 * owns and migrates its own store in a later epic (AD-5/AD-1). Deactivation
 * revokes the editorial caps and flushes rewrite rules.
 *
 * @package Ink\Core
 */
final class Activation {

	/**
	 * Option key holding the installed schema/DB version.
	 *
	 * Later modules' `dbDelta()` migrations compare against this to decide
	 * whether to upgrade.
	 */
	public const DB_VERSION_OPTION = 'ink_core_db_version';

	/**
	 * Minimum supported PHP version.
	 */
	private const MIN_PHP = '8.3';

	/**
	 * Minimum supported WordPress version.
	 */
	private const MIN_WP = '7.0';

	/**
	 * Run on plugin activation.
	 *
	 * Guards minimum versions, records the DB-version option, runs the empty
	 * schema registry, grants the editorial custom caps to the `editor` role, and
	 * flushes rewrite rules.
	 */
	public static function activate(): void {
		self::guardEnvironment();

		update_option( self::DB_VERSION_OPTION, INK_CORE_VERSION );

		// Run the schema registry. It is empty at 1.7 (no module has registered
		// a table yet), so this is a no-op; each module registers its own
		// dbDelta schema in a later epic (AD-5).
		Schema::install();

		// Story 3.3 / AD-6: grant the four INK editorial custom caps to the
		// editorial roles (admin + editor / redakteur), and the INK-content
		// primitive caps (from the CPTs' custom capability_type) to the
		// content-managing roles. These persist in the DB, so they belong at
		// activation — NOT on every `init`. Idempotent + fail-safe; the caps that
		// gate live paths ARE granted to real roles (no deny-everyone stub).
		Capabilities::grantToEditor();
		PostTypes::grantContentCaps();

		// Flush the rewrite rules the Epic 2 CPTs/taxonomies introduce.
		flush_rewrite_rules();
	}

	/**
	 * Run pending schema upgrades when the plugin code is newer than the
	 * recorded DB version.
	 *
	 * The activation hook fires only on (re)activation — NOT when an
	 * already-active plugin's code is updated in place. A custom table added in
	 * a later release (e.g. the Story 5.3 `ink_tier_history` audit log) would
	 * therefore be missing on every upgraded site until a manual reactivation,
	 * silently dropping the FR-12 audit rows {@see \Ink\Tiers\Api::promote()}
	 * would otherwise write. Hooked on `admin_init`, this compares
	 * `INK_CORE_VERSION` against the stored {@see self::DB_VERSION_OPTION} and,
	 * when the code is newer (or the option is unset), re-runs the idempotent
	 * `dbDelta()` schema install and records the new version. Schema-adding
	 * releases MUST bump `INK_CORE_VERSION` for this gate to fire.
	 */
	public static function maybeUpgrade(): void {
		$installed = (string) get_option( self::DB_VERSION_OPTION, '' );

		if ( '' !== $installed && version_compare( $installed, INK_CORE_VERSION, '>=' ) ) {
			return;
		}

		Schema::install();

		update_option( self::DB_VERSION_OPTION, INK_CORE_VERSION );
	}

	/**
	 * Run on plugin deactivation.
	 *
	 * Revokes the editorial custom caps it granted (no orphaned caps left behind)
	 * and flushes rewrite rules so any rules the plugin contributed are cleared.
	 */
	public static function deactivate(): void {
		Capabilities::revokeFromEditor();
		PostTypes::revokeContentCaps();

		flush_rewrite_rules();
	}

	/**
	 * Deactivate and halt with an Afrikaans message if the runtime does not
	 * meet the PHP 8.3 / WP 7.0 minimum.
	 */
	private static function guardEnvironment(): void {
		global $wp_version;

		$php_ok = version_compare( PHP_VERSION, self::MIN_PHP, '>=' );
		$wp_ok  = isset( $wp_version ) && version_compare( (string) $wp_version, self::MIN_WP, '>=' );

		if ( $php_ok && $wp_ok ) {
			return;
		}

		deactivate_plugins( plugin_basename( INK_CORE_FILE ) );

		wp_die(
			esc_html__(
				'INK Core vereis PHP 8.3 of hoër en WordPress 7.0 of hoër. Dateer asseblief op voordat jy die inprop aktiveer.',
				'ink-core'
			)
		);
	}
}

<?php
/**
 * Activation / deactivation handlers.
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Kernel;

defined( 'ABSPATH' ) || exit;

/**
 * Plugin activation and deactivation lifecycle.
 *
 * Activation records the schema-version option, runs the (currently empty)
 * schema registry, flushes rewrite rules as a stub for the CPTs/taxonomies
 * Epic 2 will register, and guards the PHP 8.3 / WP 7.0 minimum. It creates NO
 * tables, CPTs or terms — each module owns and migrates its own store in a
 * later epic (AD-5/AD-1). Deactivation flushes rewrite rules.
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
	 * schema registry, and flushes rewrite rules.
	 */
	public static function activate(): void {
		self::guardEnvironment();

		update_option( self::DB_VERSION_OPTION, INK_CORE_VERSION );

		// Run the schema registry. It is empty at 1.7 (no module has registered
		// a table yet), so this is a no-op; each module registers its own
		// dbDelta schema in a later epic (AD-5).
		Schema::install();

		// Stub for the rewrite rules the Epic 2 CPTs/taxonomies will introduce.
		// Harmless now (no custom rules exist); the correct activation pattern.
		flush_rewrite_rules();
	}

	/**
	 * Run on plugin deactivation.
	 *
	 * Flushes rewrite rules so any rules the plugin contributed are cleared.
	 */
	public static function deactivate(): void {
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

<?php
/**
 * Where the migration's input files live.
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Kernel;

defined( 'ABSPATH' ) || exit;

/**
 * Resolves a migration input file (e.g. `writer-accounts.csv`) to a path.
 *
 * The owner keeps some of these files outside the repo. When the `INK_MIGRATION_DATA_DIR`
 * constant (wp-config.php) names a folder and the file is there, that copy wins; otherwise
 * the plugin's own `migration-data/` folder is used. The deploy mirrors the plugin with
 * `--delete`, so files copied by hand into the site's `migration-data/` do not survive it.
 *
 * Lives in the Kernel so Content and Migration can share it without depending on each other.
 */
final class MigrationData {

	/**
	 * The path to read a file from. Pure.
	 *
	 * @param string $file       The file name.
	 * @param string $override   The configured folder, or ''.
	 * @param string $plugin_dir The plugin's migration-data folder.
	 */
	public static function resolve( string $file, string $override, string $plugin_dir ): string {
		if ( '' !== $override ) {
			$candidate = rtrim( $override, '/\\' ) . '/' . $file;

			if ( is_readable( $candidate ) ) {
				return $candidate;
			}
		}

		return rtrim( $plugin_dir, '/\\' ) . '/' . $file;
	}

	/**
	 * The path to read a file from, using the configured folder.
	 *
	 * @param string $file The file name.
	 */
	public static function path( string $file ): string {
		$override = defined( 'INK_MIGRATION_DATA_DIR' ) ? (string) constant( 'INK_MIGRATION_DATA_DIR' ) : '';

		return self::resolve( $file, $override, dirname( __DIR__, 2 ) . '/migration-data' );
	}
}

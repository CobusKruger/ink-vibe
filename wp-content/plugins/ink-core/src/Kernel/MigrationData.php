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
 * All migration data lives outside the repo and the plugin, in the one folder named by the
 * `INK_MIGRATION_DATA_DIR` constant (wp-config.php); `docs/migration-data.md` describes it.
 * There is no fallback: a missing file stops `wp ink migrate-all` before any step runs,
 * because a missing writer CSV would otherwise quietly lose wins.
 *
 * Lives in the Kernel so Content, InkPols and Migration can share it without depending on
 * each other.
 */
final class MigrationData {

	/**
	 * The files `wp ink migrate-all` needs.
	 *
	 * @var list<string>
	 */
	public const FILES = array(
		'writer-accounts.csv',
		'writer-aliases.csv',
		'inkpols-uitgawes.csv',
	);

	/**
	 * A file's path in a folder. Pure.
	 *
	 * @param string $file The file name.
	 * @param string $dir  The folder, or '' when none is configured.
	 * @return string The path, or '' when no folder is configured.
	 */
	public static function resolve( string $file, string $dir ): string {
		return '' === $dir ? '' : rtrim( $dir, '/\\' ) . '/' . $file;
	}

	/**
	 * What stops a migration from reading its data. Reads the file system.
	 *
	 * @param string $dir The folder, or '' when none is configured.
	 * @return list<string> One message per problem; empty when all is well.
	 */
	public static function problems( string $dir ): array {
		if ( '' === $dir ) {
			return array( 'INK_MIGRATION_DATA_DIR is nie in wp-config.php gestel nie.' );
		}

		$problems = array();

		foreach ( self::FILES as $file ) {
			$path = self::resolve( $file, $dir );

			if ( ! is_readable( $path ) ) {
				$problems[] = sprintf( 'Migrasiedata nie gevind nie: %s', $path );
			}
		}

		return $problems;
	}

	/**
	 * The configured folder, or ''.
	 */
	public static function dir(): string {
		return defined( 'INK_MIGRATION_DATA_DIR' ) ? (string) constant( 'INK_MIGRATION_DATA_DIR' ) : '';
	}

	/**
	 * The path to read a file from, or '' when no folder is configured.
	 *
	 * @param string $file The file name.
	 */
	public static function path( string $file ): string {
		return self::resolve( $file, self::dir() );
	}
}

<?php
/**
 * Autoload bridge for ink-core.
 *
 * Prefers Composer's optimized PSR-4 autoloader once it has been built
 * (`vendor/autoload.php`, assembled in CI per AD-4/AD-8). When `vendor/` is
 * absent — the committed repo state, since only first-party code is committed
 * and dependencies are fetched at build (AD-4) — it registers a small
 * hand-rolled PSR-4 loader mapping the `Ink\` prefix onto `src/`.
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink;

defined( 'ABSPATH' ) || exit;

$ink_core_composer_autoload = INK_CORE_PATH . 'vendor/autoload.php';

if ( is_readable( $ink_core_composer_autoload ) ) {
	require_once $ink_core_composer_autoload;

	// Composer also loads the procedural surface via its "files" autoload once
	// built; until then the fallback below requires it explicitly.
	if ( ! function_exists( 'Ink\\ink_core' ) ) {
		require_once INK_CORE_PATH . 'src/functions.php';
	}

	return;
}

/**
 * Hand-rolled PSR-4 autoloader for the `Ink\` namespace → `src/`.
 *
 * Example: `Ink\Kernel\Plugin` → `{INK_CORE_PATH}src/Kernel/Plugin.php`.
 *
 * @param string $class Fully-qualified class name requested by the engine.
 */
spl_autoload_register(
	static function ( string $class ): void {
		$prefix   = 'Ink\\';
		$base_dir = INK_CORE_PATH . 'src/';

		$len = strlen( $prefix );

		if ( 0 !== strncmp( $prefix, $class, $len ) ) {
			return;
		}

		$relative_class = substr( $class, $len );

		// Reject any traversal attempt before touching the filesystem.
		if ( str_contains( $relative_class, '..' ) ) {
			return;
		}

		$relative_path = str_replace( '\\', '/', $relative_class ) . '.php';
		$file          = $base_dir . $relative_path;

		if ( is_readable( $file ) ) {
			require_once $file;
		}
	}
);

// The procedural/global surface (snake_case template-tag-style helpers) is not
// a namespaced class, so PSR-4 cannot resolve it — load it eagerly.
require_once INK_CORE_PATH . 'src/functions.php';

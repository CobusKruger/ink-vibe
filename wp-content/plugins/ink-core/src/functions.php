<?php
/**
 * Procedural / global surface for ink-core.
 *
 * Per the naming convention (architecture Implementation Patterns), the
 * `ink_` snake_case namespace is reserved for the global/procedural WordPress
 * surface only — template tags, global helper functions, hooks, options. Class
 * methods stay camelCase. This file holds the one public procedural entry point.
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink;

use Ink\Kernel\Plugin;

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'Ink\\ink_core' ) ) {
	/**
	 * Public procedural accessor for the ink-core Kernel.
	 *
	 * The single global entry point to the booted plugin instance, e.g. for
	 * template tags or third-party integrations that cannot reach the class
	 * directly. Returns the same singleton `Plugin::boot()` wires on
	 * `plugins_loaded`.
	 *
	 * @return Plugin The booted Kernel plugin instance.
	 */
	function ink_core(): Plugin {
		return Plugin::boot();
	}
}

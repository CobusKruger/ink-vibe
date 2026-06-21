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

if ( ! function_exists( 'Ink\\ink_term' ) ) {
	/**
	 * Return the Afrikaans UI label for a glossary concept key (AD-10).
	 *
	 * The procedural shorthand for {@see Kernel} consumers and template tags;
	 * delegates to the single-source registry {@see I18n\Terms::label()}. Callers
	 * pass the concept KEY (e.g. `ink_term( 'membership' )`), never the inline
	 * literal — the only `__()` literals for these concepts live in the registry,
	 * keeping `wp i18n make-pot` complete and a term re-decision a one-file edit.
	 *
	 * Theme PHP patterns should prefer the decoupled `ink_foundation_term()`
	 * bridge (guarded against `ink-core` being inactive); static block-template
	 * HTML uses the `ink/term` Block Bindings source ({@see I18n\Bindings}).
	 *
	 * @param string $key Glossary concept key.
	 * @return string The label, or the key itself if unregistered.
	 */
	function ink_term( string $key ): string {
		return I18n\Terms::label( $key );
	}
}

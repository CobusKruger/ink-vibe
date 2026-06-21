<?php
/**
 * Module bootstrap contract.
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Kernel;

defined( 'ABSPATH' ) || exit;

/**
 * Contract every ink-core runtime module bootstrap implements.
 *
 * Per AD-1's canonical module skeleton, each module ships a `Module.php`
 * (bootstrap + hook registration) plus an `Api.php` facade (its sole public
 * cross-module surface). The Kernel collects module bootstraps via
 * {@see Plugin::addModule()} and calls {@see Module::register()} on each at
 * `init`. This interface types that seam so the wiring is explicit and
 * type-checked rather than relying on duck-typed conventions.
 *
 * No module is registered at 1.7 — the reserved module directories contain
 * placeholder `Module` implementations whose `register()` is a documented
 * no-op until their epic.
 *
 * @package Ink\Core
 */
interface Module {

	/**
	 * Register this module's hooks, post types, routes, etc.
	 *
	 * Called once by the Kernel at `init`. Implementations own their entire
	 * runtime surface (AD-1 facade discipline); cross-module calls go through
	 * the other module's `Api` facade, never into its internals.
	 */
	public function register(): void;
}

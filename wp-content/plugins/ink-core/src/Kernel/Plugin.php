<?php
/**
 * Kernel bootstrap.
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Kernel;

defined( 'ABSPATH' ) || exit;

/**
 * The ink-core Kernel bootstrap.
 *
 * Single, idempotent entry point booted on `plugins_loaded`. It wires only
 * cross-cutting Kernel concerns (i18n loading today) and exposes the
 * module-registration seam that later stories use to attach their module
 * bootstraps. It contains ZERO feature logic — no CPTs, taxonomies, meta,
 * entitlement, Gradering, comment filters or REST routes. Those each live in
 * their own module and register through {@see Plugin::addModule()} in later epics.
 *
 * Per AD-1 the plugin main file "loads Kernel, then each module's bootstrap";
 * this class is that Kernel.
 *
 * @package Ink\Core
 */
final class Plugin {

	/**
	 * The single booted instance.
	 */
	private static ?Plugin $instance = null;

	/**
	 * Whether {@see Plugin::run()} has already wired its hooks.
	 */
	private bool $booted = false;

	/**
	 * Registered module bootstraps, keyed by module identifier.
	 *
	 * Reserved seam: later stories register `Ink\{Module}\Module` instances
	 * here so the Kernel can call `register()` on each at boot. Empty at 1.7.
	 *
	 * @var array<string, Module>
	 */
	private array $modules = [];

	/**
	 * Private constructor — use {@see Plugin::boot()}.
	 */
	private function __construct() {}

	/**
	 * Boot (or return) the singleton and wire Kernel hooks exactly once.
	 *
	 * Wired onto `plugins_loaded` from the bootstrap via a first-class callable.
	 * Idempotent: repeated calls return the same instance without re-wiring.
	 *
	 * @return Plugin The booted Kernel instance.
	 */
	public static function boot(): Plugin {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		self::$instance->run();

		return self::$instance;
	}

	/**
	 * Return the booted instance, booting it if necessary.
	 *
	 * @return Plugin The Kernel instance.
	 */
	public static function instance(): Plugin {
		return self::boot();
	}

	/**
	 * Wire the Kernel's own hooks. Runs once; safe to call repeatedly.
	 */
	private function run(): void {
		if ( $this->booted ) {
			return;
		}

		$this->booted = true;

		// Load the text domain on `init` (current WP guidance), wired here from
		// the `plugins_loaded` boot.
		add_action( 'init', I18n::load( ... ) );

		// Admin-language mechanism (§14.14): force editor/administrator users to
		// the English admin locale via the read-time `get_user_locale` filter.
		// The callback is a strict no-op outside wp-admin, so the site/front-end
		// locale stays `af` for everyone. This is a cross-cutting Kernel i18n
		// concern, wired here (not in a feature module, not in the bootstrap).
		add_filter( 'get_user_locale', I18n::forceStaffAdminLocale( ... ), 10, 2 );

		// Reserved seam: dispatch each registered module's bootstrap on `init`.
		// No modules are registered at 1.7, so this is a no-op until later epics.
		add_action( 'init', $this->registerModules( ... ) );
	}

	/**
	 * Register a module bootstrap with the Kernel.
	 *
	 * The documented extension point: later stories construct their
	 * `Ink\{Module}\Module` and hand it to the Kernel, which invokes
	 * `register()` on each module at `init`. No-op consumers exist at 1.7.
	 *
	 * @param string $id     Unique module identifier (e.g. 'content').
	 * @param Module $module The module bootstrap to register.
	 * @return Plugin Fluent self, for chaining registrations.
	 */
	public function addModule( string $id, Module $module ): Plugin {
		$this->modules[ $id ] = $module;

		return $this;
	}

	/**
	 * Whether a module is registered under the given identifier.
	 *
	 * @param string $id Module identifier.
	 */
	public function hasModule( string $id ): bool {
		return isset( $this->modules[ $id ] );
	}

	/**
	 * Invoke `register()` on every registered module. No-op while none are
	 * registered (the 1.7 state).
	 */
	private function registerModules(): void {
		foreach ( $this->modules as $module ) {
			$module->register();
		}
	}
}

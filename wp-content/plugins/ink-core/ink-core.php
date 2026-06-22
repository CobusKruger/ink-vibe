<?php
/**
 * Plugin Name:       INK Core
 * Plugin URI:        https://ink.org.za/
 * Description:       INK se kern-besigheidslogika: inhoudsmodelle, lidmaatskap-toegang, Gradering, indiening, leesbetrokkenheid, uitdagings, borge en kennisgewings. Die enigste plek vir INK-besigheidsreëls (drie-laag-skeiding).
 * Version:           0.1.0
 * Requires at least: 7.0
 * Requires PHP:      8.3
 * Author:            INK
 * Author URI:        https://ink.org.za/
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       ink-core
 * Domain Path:       /languages
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink;

defined( 'ABSPATH' ) || exit;

/**
 * Plugin version. Bumped per release; the activation handler records it as the
 * `ink_core_db_version` option so later modules' dbDelta migrations can compare
 * and upgrade.
 */
const VERSION = '0.1.0';

// Path / URL constants — no hardcoded URLs anywhere; resolved from this file.
define( 'INK_CORE_VERSION', VERSION );
define( 'INK_CORE_FILE', __FILE__ );
define( 'INK_CORE_PATH', plugin_dir_path( __FILE__ ) );
define( 'INK_CORE_URL', plugin_dir_url( __FILE__ ) );

// Autoload bridge: Composer's optimized loader when built (CI / Story 1.11),
// else a hand-rolled PSR-4 fallback so the plugin is loadable in the repo today.
require_once INK_CORE_PATH . 'src/autoload.php';

// Activation / deactivation: versioned-DB-option stub, empty schema registry,
// rewrite-rule flush stub, and PHP/WP minimum-version guard.
register_activation_hook( INK_CORE_FILE, array( Kernel\Activation::class, 'activate' ) );
register_deactivation_hook( INK_CORE_FILE, array( Kernel\Activation::class, 'deactivate' ) );

// Boot the Kernel once WordPress and all plugins are loaded. The Kernel is the
// single place later stories register their module bootstraps.
add_action( 'plugins_loaded', Kernel\Plugin::boot( ... ) );

/**
 * Register the runtime module bootstraps with the Kernel.
 *
 * Each module is handed to the Kernel via its `addModule()` seam; the Kernel
 * calls `register()` on each at `init`. Registration runs on `plugins_loaded`
 * (which precedes `init`), so every module is in the map before the dispatch.
 * The bootstrap only REGISTERS modules — the behaviour lives inside each module.
 */
add_action(
	'plugins_loaded',
	static function (): void {
		Kernel\Plugin::instance()->addModule( 'content', new Content\Module() );
		Kernel\Plugin::instance()->addModule( 'engagement', new Engagement\Module() );
		Kernel\Plugin::instance()->addModule( 'notifications', new Notifications\Module() );
	}
);

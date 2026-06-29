<?php
/**
 * Plugin Name:       INK Core
 * Plugin URI:        https://ink.org.za/
 * Description:       INK se kern-besigheidslogika: inhoudsmodelle, lidmaatskap-toegang, Gradering, indiening, leesbetrokkenheid, uitdagings, borge en kennisgewings. Die enigste plek vir INK-besigheidsreëls (drie-laag-skeiding).
 * Version:           0.1.2
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
const VERSION = '0.1.2';

// Path / URL constants — no hardcoded URLs anywhere; resolved from this file.
define( 'INK_CORE_VERSION', VERSION );
define( 'INK_CORE_FILE', __FILE__ );
define( 'INK_CORE_PATH', plugin_dir_path( __FILE__ ) );
define( 'INK_CORE_URL', plugin_dir_url( __FILE__ ) );

// Autoload bridge: Composer's optimized loader when built (CI / Story 1.11),
// else a hand-rolled PSR-4 fallback so the plugin is loadable in the repo today.
require_once INK_CORE_PATH . 'src/autoload.php';

// Register custom-table schema providers at INCLUDE time (not inside a
// plugins_loaded/init closure). On the activation request, plugins_loaded has
// already fired before activate_plugin() includes this file and runs the
// activation hook, so an init-registered provider would be invisible to
// Schema::install(). The include-time registration guarantees the provider is
// present when activation creates the tables. ink-core.php is the composition
// root, outside deptrac's src/ scan, so no tracked edge is created.
Kernel\Schema::register( Tiers\PromotionLog::TABLE, array( Tiers\PromotionLog::class, 'schemaSql' ) ); // Story 5.3: graderingsgeskiedenis audit log.
Kernel\Schema::register( Engagement\ReactionStore::TABLE, array( Engagement\ReactionStore::class, 'schemaSql' ) ); // Story 7.3: line-reaction table.
Kernel\Schema::register( Engagement\ReadingListStore::TABLE, array( Engagement\ReadingListStore::class, 'schemaSql' ) ); // Story 7.7: leeslys table.
Kernel\Schema::register( Social\FollowStore::TABLE, array( Social\FollowStore::class, 'schemaSql' ) ); // Story 9.2: asymmetric follow graph.
Kernel\Schema::register( Social\RatingStore::TABLE, array( Social\RatingStore::class, 'schemaSql' ) ); // Story 9.6: reader ratings & reviews.

// Activation / deactivation: versioned-DB-option, schema registry install,
// rewrite-rule flush, and PHP/WP minimum-version guard.
register_activation_hook( INK_CORE_FILE, array( Kernel\Activation::class, 'activate' ) );
register_deactivation_hook( INK_CORE_FILE, array( Kernel\Activation::class, 'deactivate' ) );

// Run pending custom-table schema upgrades for an in-place plugin update: the
// activation hook fires only on (re)activation, so a table added in a later
// release would be missing on an upgraded site until reactivation. admin_init
// keeps the dbDelta off the front-end request path; dbDelta is idempotent.
add_action( 'admin_init', array( Kernel\Activation::class, 'maybeUpgrade' ) );

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
		Kernel\Plugin::instance()->addModule( 'accounts', new Accounts\Module() );
		Kernel\Plugin::instance()->addModule( 'entitlement', new Entitlement\Module() );
		Kernel\Plugin::instance()->addModule( 'tiers', new Tiers\Module() );
			Kernel\Plugin::instance()->addModule( 'submission', new Submission\Module() );
		Kernel\Plugin::instance()->addModule( 'discovery', new Discovery\Module() );
		Kernel\Plugin::instance()->addModule( 'social', new Social\Module() );
		Kernel\Plugin::instance()->addModule( 'library', new Library\Module() );
		Kernel\Plugin::instance()->addModule( 'training', new Training\Module() );
		Kernel\Plugin::instance()->addModule( 'challenges', new Challenges\Module() );
		Kernel\Plugin::instance()->addModule( 'inkpols', new InkPols\Module() );
		Kernel\Plugin::instance()->addModule( 'sponsors', new Sponsors\Module() );
		Kernel\Plugin::instance()->addModule( 'forms', new Forms\Module() );
		Kernel\Plugin::instance()->addModule( 'migration', new Migration\Module() );
		Kernel\Plugin::instance()->addModule( 'seo', new Seo\Module() );
	}
);

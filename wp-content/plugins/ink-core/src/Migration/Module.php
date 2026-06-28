<?php
/**
 * Migration module bootstrap — Epic 16 (Migration & redirects, NFR-4).
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Migration;

use Ink\Kernel\Module as ModuleContract;

defined( 'ABSPATH' ) || exit;

/**
 * Migration module — the once-off brownfield migration toolkit (Epic 16).
 *
 * Houses the scripted, ordered migration commands that move the cloned INK site
 * onto the new model: DB sanitise ({@see DbSanitiser}, 16.1), user role
 * reassignment ({@see UserReclassifier}, 16.2), writer-tier CSV import
 * ({@see TierImport}, 16.3), read-only subscription verification
 * ({@see SubscriptionVerifier}, 16.4), post → CPT reclassification
 * ({@see PostReclassifier}, 16.5), library/training sub-path migration
 * ({@see LibraryTrainingMigrator}, 16.6), 301 redirect generation + serving
 * ({@see RedirectGenerator}, 16.7), fresh navigation rebuild
 * ({@see NavigationRebuilder}, 16.8), BuddyPress friendship → follow transform
 * ({@see FollowGraphMigration}, 16.9), read-only media verification
 * ({@see MediaVerifier}, 16.10), selective options carry-forward
 * ({@see OptionsCarryForward}, 16.11) and WPBakery `[vc_*]` shortcode cleanup
 * ({@see ShortcodeCleanup}, 16.12) — the full Epic-16 migration toolkit.
 * The migration *commands* are **WP-CLI-only** (never a web request): the
 * mutating ones once-off + idempotent (the `Ink\Challenges\Migration` /
 * `Ink\InkPols\Migration` shape), the verification ones read-only and naturally
 * re-runnable. The sole runtime surface is `RedirectGenerator`'s `template_redirect`
 * handler, which SERVES the migration 301s on every front-end request (its build
 * step stays CLI-only).
 *
 * Conflation-clean: the migration commands read `$wpdb` + `Ink\Content` + WP core
 * only — they never couple `Ink\Tiers` to `Ink\Entitlement`.
 *
 * @package Ink\Core
 */
final class Module implements ModuleContract {

	/**
	 * Register this module's collaborators.
	 *
	 * Dispatched once by the Kernel on `init`. Each collaborator self-gates to
	 * WP-CLI, so registering them on a web request is a no-op (the thin-`Module`
	 * house style).
	 */
	public function register(): void {
		( new DbSanitiser() )->register();
		( new UserReclassifier() )->register();
		( new TierImport() )->register();
		( new SubscriptionVerifier() )->register();
		( new PostReclassifier() )->register();
		( new LibraryTrainingMigrator() )->register();
		( new RedirectGenerator() )->register();
		( new NavigationRebuilder() )->register();
		( new FollowGraphMigration() )->register();
		( new MediaVerifier() )->register();
		( new OptionsCarryForward() )->register();
		( new ShortcodeCleanup() )->register();
	}
}

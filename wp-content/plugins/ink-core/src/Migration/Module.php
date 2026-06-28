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
 * reassignment ({@see UserReclassifier}, 16.2) and — as the epic progresses —
 * tier CSV import, post reclassification, redirect generation, and the remaining
 * migration steps. Every command is a
 * once-off, idempotent, **WP-CLI-only** operation (never a web request), the
 * shape established by `Ink\Challenges\Migration` / `Ink\InkPols\Migration`.
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
	}
}

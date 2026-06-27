<?php
/**
 * Library (Biblioteek) module bootstrap — Epic 10.
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Library;

use Ink\Kernel\Module as ModuleContract;

defined( 'ABSPATH' ) || exit;

/**
 * Library module — the Biblioteek section (Epic 10).
 *
 * Owns the curated/reference `biblioteek_item` archive + single surfaces under the
 * migration-load-bearing `/biblioteek/` URL prefix (FR-52). Live at 10.1: the
 * {@see Archive} works-archive block (featured strip + genre filter + search +
 * card grid). The winner↔challenge linkage display (10.5) and the auto-update hook
 * (10.6) extend this module.
 *
 * Conflation-clean: browsing published Biblioteek work is open — the module reads
 * only `Ink\Content` (CPT/taxonomy slugs) + the `Terms` registry + WP core, never
 * `Ink\Entitlement`/`Ink\Tiers`.
 *
 * @package Ink\Core
 */
final class Module implements ModuleContract {

	/**
	 * Register this module's hooks.
	 *
	 * Dispatched once by the Kernel on `init`. Delegates to the collaborator block
	 * so this bootstrap stays thin (the Discovery/Engagement house style).
	 */
	public function register(): void {
		( new Archive() )->register();
	}
}

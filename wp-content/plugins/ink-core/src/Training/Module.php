<?php
/**
 * Training (Opleiding) module bootstrap — Epic 11.
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Training;

use Ink\Kernel\Module as ModuleContract;

defined( 'ABSPATH' ) || exit;

/**
 * Training module — the Opleiding section (Epic 11).
 *
 * Owns the `opleiding_artikel` resource hub + single surfaces under the
 * migration-load-bearing `/opleiding/` URL prefix (FR-54). A resource hub, NOT an
 * LMS: no course/lesson/progress mechanics. Live at 11.1: the {@see Hub} block
 * (featured strip + search + card grid). Later stories add the `vaardigheid`
 * faceted filter (11.2), the redakteur-se-rak entry points (11.3), auto
 * cross-surfacing (11.4) and the contribution CTA (11.5).
 *
 * Conflation-clean: browsing published Opleiding work is open — the module reads
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
	 * so this bootstrap stays thin (the Library/Discovery house style).
	 */
	public function register(): void {
		( new Hub() )->register();
	}
}

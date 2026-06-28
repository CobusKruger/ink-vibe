<?php
/**
 * Sponsors module bootstrap (reserved).
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Sponsors;

use Ink\Kernel\Module as ModuleContract;

defined( 'ABSPATH' ) || exit;

/**
 * Sponsors module — the sponsor (borg) section (Epic 14).
 *
 * Owns the read-model + reader surfaces over the `borg` CPT (FR-58). The CPT +
 * the five sponsor fields already exist from Epic 2 ({@see \Ink\Content\PostTypes}
 * 2.1, {@see \Ink\Content\FieldSets} 2.4); this module is the read-model + surface
 * layer on top, mirroring how `Ink\InkPols` (13.1) and `Ink\Challenges` (12.1)
 * were built over their Epic-2 CPTs.
 *
 * The module carries the read-model ({@see Sponsor}) + facade ({@see Api}) from
 * 14.1, the campaign-window scheduler/rotation ({@see Campaign}) from 14.2, and the
 * homepage sponsor strip ({@see HomepageStrip}) server block from 14.3; the
 * recognition section lands in 14.4. Conflation-clean: reads only `Ink\Content` +
 * `Ink\Kernel` + WP core, never `Ink\Tiers`/`Ink\Entitlement`.
 *
 * @package Ink\Core
 */
final class Module implements ModuleContract {

	/**
	 * Register this module's hooks.
	 *
	 * Registers the homepage sponsor-strip server block (14.3). The read-model
	 * ({@see Sponsor}), facade ({@see Api}) and scheduler ({@see Campaign}) are
	 * stateless reads consumed on demand — they have nothing to hook.
	 */
	public function register(): void {
		( new HomepageStrip() )->register();
	}
}

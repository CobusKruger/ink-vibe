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
 * 14.1; the campaign-window scheduler/rotation lands in 14.2, the homepage strip
 * in 14.3, and the recognition section in 14.4 — those stories add the render
 * hooks here. Conflation-clean: reads only `Ink\Content` + `Ink\Kernel` + WP
 * core, never `Ink\Tiers`/`Ink\Entitlement`.
 *
 * @package Ink\Core
 */
final class Module implements ModuleContract {

	/**
	 * Register this module's hooks.
	 *
	 * A deliberate no-op at 14.1 — the read-model ({@see Sponsor}) and facade
	 * ({@see Api}) are stateless reads with nothing to hook. The homepage strip
	 * (14.3) and recognition section (14.4) add render hooks here.
	 */
	public function register(): void {
		// Read-model + facade only at 14.1; render hooks land in 14.3/14.4.
	}
}

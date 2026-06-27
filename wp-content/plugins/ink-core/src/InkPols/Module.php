<?php
/**
 * InkPols module bootstrap — Epic 13 (FR-57).
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\InkPols;

use Ink\Kernel\Module as ModuleContract;

defined( 'ABSPATH' ) || exit;

/**
 * InkPols module — the periodical (InkPols) section (Epic 13).
 *
 * Owns the `inkpols_uitgawe` reader surfaces under the migration-load-bearing
 * `/inkpols/` URL prefix (FR-57). The CPT + the five issue fields already exist
 * from Epic 2 ({@see \Ink\Content\PostTypes} 2.1, {@see \Ink\Content\FieldSets}
 * 2.4); this module is the read-model + surface layer on top, mirroring how
 * `Ink\Challenges` was built over the Epic-2 `uitdaging` CPT.
 *
 * The module carries the read-model ({@see Issue}) + facade ({@see Api}) plus,
 * from 13.2, the by-year archive ({@see Archive}) and single-issue metadata
 * ({@see SingleIssue}) server blocks. Story 13.3 (PDF viewing) extends the
 * single-issue surface. Conflation-clean: reads only `Ink\Content` +
 * `Ink\Kernel` + WP core, never `Ink\Tiers`/`Ink\Entitlement`.
 *
 * @package Ink\Core
 */
final class Module implements ModuleContract {

	/**
	 * Register this module's hooks.
	 *
	 * Dispatched once by the Kernel on `init`. Delegates to the collaborator
	 * blocks so this bootstrap stays thin (the Library/Challenges house style).
	 */
	public function register(): void {
		( new Archive() )->register();
		( new SingleIssue() )->register();
	}
}

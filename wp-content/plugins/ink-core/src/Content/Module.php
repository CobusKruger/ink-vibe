<?php
/**
 * Content module bootstrap (reserved).
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Content;

use Ink\Kernel\Module as ModuleContract;

defined( 'ABSPATH' ) || exit;

/**
 * Content module bootstrap.
 *
 * Owns the INK content models (AD-1: Content owns CPTs/taxonomies/meta). Live at
 * 2.1: registration of the nine INK CPTs (`gedig`, `storie`, `artikel`,
 * `skryfwerk`, `biblioteek_item`, `opleiding_artikel`, `uitdaging`,
 * `inkpols_uitgawe`, `borg`) via {@see PostTypes}.
 *
 * RESERVED for the rest of Epic 2: taxonomies (`genre`, `vaardigheid`,
 * `uitdagingsrondte`, `ster_gradering` — Story 2.2), user meta (2.3), per-CPT
 * admin field sets (2.4) and native term images (2.5).
 *
 * @package Ink\Core
 */
final class Module implements ModuleContract {

	/**
	 * Register this module's hooks.
	 *
	 * Dispatched once by the Kernel on `init` (via `Plugin::registerModules()`).
	 * Delegates CPT registration to {@see PostTypes} so this bootstrap stays thin,
	 * mirroring the Engagement `Module` → `Comments` house style.
	 */
	public function register(): void {
		( new PostTypes() )->register();

		// Reserved: taxonomies (2.2), user meta (2.3), admin field sets (2.4) and
		// native term images (2.5) register here through the rest of Epic 2.
	}
}

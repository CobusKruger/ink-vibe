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
 * `inkpols_uitgawe`, `borg`) via {@see PostTypes}. Live at 2.2: the four INK
 * taxonomies (`genre`, `vaardigheid`, `uitdagingsrondte`, `ster_gradering`) via
 * {@see Taxonomies}. Live at 2.3: the writer-tier user meta (`ink_writer_tier`,
 * `ink_tier_promoted_at`) via {@see UserMeta}. Live at 2.4: the per-CPT admin
 * field sets (InkPols / challenge / sponsor editorial meta + meta boxes) via
 * {@see FieldSets}.
 *
 * RESERVED for the rest of Epic 2: native term images (2.5).
 *
 * @package Ink\Core
 */
final class Module implements ModuleContract {

	/**
	 * Register this module's hooks.
	 *
	 * Dispatched once by the Kernel on `init` (via `Plugin::registerModules()`).
	 * Delegates registration to thin collaborators ({@see PostTypes},
	 * {@see Taxonomies}, {@see UserMeta}, {@see FieldSets}) so this bootstrap stays
	 * thin, mirroring the Engagement `Module` → `Comments` house style. CPTs
	 * register BEFORE taxonomies so every taxonomy `object_type` target already
	 * exists.
	 */
	public function register(): void {
		( new PostTypes() )->register();
		( new Taxonomies() )->register();
		( new UserMeta() )->register();
		( new FieldSets() )->register();

		// Reserved: native term images (2.5) register here in the rest of Epic 2.
	}
}

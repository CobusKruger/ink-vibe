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
 * Content module — RESERVED extension point for Epic 2.
 *
 * Will register the INK CPTs (`gedig`, `storie`, `artikel`, `skryfwerk`,
 * `biblioteek_item`, `opleiding_artikel`, `uitdaging`, `inkpols_uitgawe`,
 * `borg`), taxonomies (`genre`, `vaardigheid`, `uitdagingsrondte`,
 * `ster_gradering`) and user/post meta. NOTHING is implemented at 1.7 —
 * `register()` is a documented no-op until Epic 2.
 *
 * @package Ink\Core
 */
final class Module implements ModuleContract {

	/**
	 * Register this module's hooks. No-op until Epic 2.
	 */
	public function register(): void {
		// Reserved: CPTs, taxonomies and meta are registered here in Epic 2.
	}
}

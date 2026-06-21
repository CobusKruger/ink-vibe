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
 * Sponsors module — RESERVED extension point for Epic 14.
 *
 * Will own borg scheduling/rotation logic and the homepage sponsor placement.
 * NOTHING is implemented at 1.7.
 *
 * @package Ink\Core
 */
final class Module implements ModuleContract {

	/**
	 * Register this module's hooks. No-op until Epic 14.
	 */
	public function register(): void {
		// Reserved: borg scheduling/rotation lands in Epic 14.
	}
}

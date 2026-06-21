<?php
/**
 * Forms module bootstrap (reserved).
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Forms;

use Ink\Kernel\Module as ModuleContract;

defined( 'ABSPATH' ) || exit;

/**
 * Forms module — RESERVED extension point for Story 15.4 + 18.4.
 *
 * Will own the contact form (kontak) and the content-report path. May later
 * fold into an adjacent module if it stays thin (AD-1, ~8–12 modules). NOTHING
 * is implemented at 1.7.
 *
 * @package Ink\Core
 */
final class Module implements ModuleContract {

	/**
	 * Register this module's hooks. No-op until Epic 15 / 18.
	 */
	public function register(): void {
		// Reserved: contact + report forms land in Epic 15.4 / 18.4.
	}
}

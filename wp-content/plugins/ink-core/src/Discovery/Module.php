<?php
/**
 * Discovery module bootstrap (reserved).
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Discovery;

use Ink\Kernel\Module as ModuleContract;

defined( 'ABSPATH' ) || exit;

/**
 * Discovery module — RESERVED extension point for Epic 8 (+ Training cross-
 * surfacing 11.2/11.4).
 *
 * Will own search, faceted queries, shared-taxonomy cross-surfacing and the
 * trending job (server-rendered discovery with denormalized sort counts, AD-7).
 * NOTHING is implemented at 1.7.
 *
 * @package Ink\Core
 */
final class Module implements ModuleContract {

	/**
	 * Register this module's hooks. No-op until Epic 8.
	 */
	public function register(): void {
		// Reserved: search, faceted queries, cross-surfacing land in Epic 8.
	}
}

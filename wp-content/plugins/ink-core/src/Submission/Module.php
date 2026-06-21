<?php
/**
 * Submission module bootstrap (reserved).
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Submission;

use Ink\Kernel\Module as ModuleContract;

defined( 'ABSPATH' ) || exit;

/**
 * Submission module — RESERVED extension point for Epic 6.
 *
 * Will own the Skryf dynamic block, draft/publish (konsep/plaas) states, and
 * the publish-moment flow that calls `Entitlement::can_submit()` (AD-2) and
 * delegates challenge entry to `Challenges::enter()` (AD-3) — always through
 * facades, never owning entry rules itself. NOTHING is implemented at 1.7.
 *
 * @package Ink\Core
 */
final class Module implements ModuleContract {

	/**
	 * Register this module's hooks. No-op until Epic 6.
	 */
	public function register(): void {
		// Reserved: Skryf block, draft/publish, gate + entry delegation, Epic 6.
	}
}

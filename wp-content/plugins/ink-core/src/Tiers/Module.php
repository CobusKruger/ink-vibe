<?php
/**
 * Tiers module bootstrap (reserved).
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Tiers;

use Ink\Kernel\Module as ModuleContract;

defined( 'ABSPATH' ) || exit;

/**
 * Tiers module — RESERVED extension point for Epic 5.
 *
 * Will own writer Gradering (ster gradering), the promotion log
 * (graderingsgeskiedenis), the win-count threshold engine (5/15) and
 * `Tiers::promote()` — the SOLE write path for `ink_writer_tier`. NOTHING is
 * implemented at 1.7.
 *
 * THE conflation rule (AD-1, FR-13): Gradering controls competition pools and
 * is kept strictly independent of lidmaatskap entitlement —
 * `Ink\Tiers` ⟂ `Ink\Entitlement`. This module MUST NOT reference
 * `Ink\Entitlement\*`. It reads the `Tier` value type from the shared Kernel
 * (`Ink\Kernel\Tier`), never from another module — so no inter-module edge is
 * created. The absence of the Tiers⟷Entitlement edge is enforced in CI by
 * Deptrac/PHPArkitect (Story 1.11/AD-8).
 *
 * @package Ink\Core
 */
final class Module implements ModuleContract {

	/**
	 * Register this module's hooks. No-op until Epic 5.
	 */
	public function register(): void {
		// Reserved: Gradering model, promotion log + engine land here in Epic 5.
	}
}

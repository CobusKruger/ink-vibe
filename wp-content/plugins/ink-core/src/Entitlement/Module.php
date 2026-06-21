<?php
/**
 * Entitlement module bootstrap (reserved).
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Entitlement;

use Ink\Kernel\Module as ModuleContract;

defined( 'ABSPATH' ) || exit;

/**
 * Entitlement module — RESERVED extension point for Epic 4.
 *
 * Will own the WooCommerce Memberships seam and `can_submit()` (the
 * submission-entitlement gate, evaluated against the lidmaatskap end date in
 * SAST — AD-2). NOTHING is implemented at 1.7.
 *
 * THE conflation rule (AD-1, FR-13): Entitlement controls submission
 * entitlement and is kept strictly independent of writer Gradering —
 * `Ink\Entitlement` ⟂ `Ink\Tiers`. This module MUST NOT reference
 * `Ink\Tiers\*`; the absence of that edge is the conflation rule, enforced in
 * CI by Deptrac/PHPArkitect (Story 1.11/AD-8).
 *
 * @package Ink\Core
 */
final class Module implements ModuleContract {

	/**
	 * Register this module's hooks. No-op until Epic 4.
	 */
	public function register(): void {
		// Reserved: WC Memberships seam + can_submit() gate land here in Epic 4.
	}
}

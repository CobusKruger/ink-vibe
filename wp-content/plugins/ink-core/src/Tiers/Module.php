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
 * Owns writer Gradering (ster gradering): the {@see Api::forUser()} read (5.1),
 * the {@see PromotionLog} audit table (5.3), the sole {@see Api::promote()}
 * write path + the {@see AdminProfile} staff UI (5.2), and — in later stories —
 * the win-count threshold engine (5/15, 5.7/5.8). The `MANAGE_TIERS` capability
 * the admin UI gates on is granted to editor + admin at activation (Story 3.3,
 * {@see \Ink\Kernel\Capabilities::grantToEditor()}); this module does not grant
 * it. The `ink_tier_history` schema provider is registered at include time in
 * `ink-core.php` (activation timing — see that file).
 *
 * THE conflation rule (AD-1, FR-13): Gradering controls competition pools and
 * is kept strictly independent of lidmaatskap entitlement —
 * `Ink\Tiers` ⟂ `Ink\Entitlement`. This module MUST NOT reference
 * `Ink\Entitlement\*`. It reads the `Tier` value type from the shared Kernel
 * (`Ink\Kernel\Tier`), never from another domain module. The absence of the
 * Tiers⟷Entitlement edge is enforced in CI by Deptrac (Story 1.11/AD-8).
 *
 * @package Ink\Core
 */
final class Module implements ModuleContract {

	/**
	 * Register this module's hooks: the staff Gradering admin UI (Story 5.2).
	 */
	public function register(): void {
		( new AdminProfile() )->register();
	}
}

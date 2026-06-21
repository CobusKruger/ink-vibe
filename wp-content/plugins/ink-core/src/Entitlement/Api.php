<?php
/**
 * Entitlement module public facade (reserved).
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Entitlement;

defined( 'ABSPATH' ) || exit;

/**
 * Entitlement module facade — RESERVED.
 *
 * The sole public cross-module surface for Entitlement (Epic 4); will expose
 * `can_submit()`. Other modules reach Entitlement only through this facade
 * (AD-1). MUST NOT reference `Ink\Tiers\*` (THE conflation rule). No methods
 * are exposed at 1.7.
 *
 * @package Ink\Core
 */
final class Api {
}

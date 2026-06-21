<?php
/**
 * Tiers module public facade (reserved).
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Tiers;

defined( 'ABSPATH' ) || exit;

/**
 * Tiers module facade — RESERVED.
 *
 * The sole public cross-module surface for Tiers (Epic 5); will expose the
 * Gradering read API (used by Challenges for pool segmentation) and
 * `promote()` (the sole tier write path, used by Challenges for
 * winner→promotion). Other modules reach Tiers only through this facade (AD-1).
 * MUST NOT reference `Ink\Entitlement\*` (THE conflation rule). No methods are
 * exposed at 1.7.
 *
 * @package Ink\Core
 */
final class Api {
}

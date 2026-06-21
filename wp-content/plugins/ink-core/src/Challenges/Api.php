<?php
/**
 * Challenges module public facade (reserved).
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Challenges;

defined( 'ABSPATH' ) || exit;

/**
 * Challenges module facade — RESERVED.
 *
 * The sole public cross-module surface for Challenges (Epic 12); will expose
 * `enter()` (called by Submission at the publish moment). Other modules reach
 * Challenges only through this facade (AD-1). No methods are exposed at 1.7.
 *
 * @package Ink\Core
 */
final class Api {
}

<?php
/**
 * Content module public facade (reserved).
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Content;

defined( 'ABSPATH' ) || exit;

/**
 * Content module facade — RESERVED.
 *
 * The sole public cross-module surface for Content (Epic 2). Other modules
 * reach Content only through this facade (AD-1), never into its internals. No
 * methods are exposed at 1.7.
 *
 * @package Ink\Core
 */
final class Api {
}

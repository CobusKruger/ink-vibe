<?php
/**
 * Accounts module public facade (reserved).
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Accounts;

defined( 'ABSPATH' ) || exit;

/**
 * Accounts module facade — the sole public cross-module surface (AD-1).
 *
 * Other modules reach Accounts only through this facade. Story 3.1 exposes no
 * methods: the account-creation default is an internal `user_register` reaction
 * ({@see Registration}) with no cross-module consumer, and "gratis lid" is the
 * absence of an active lidmaatskap (nothing to query here — submission
 * entitlement is evaluated by Epic 4 / AD-2, not by this module). The surface is
 * left reserved/minimal rather than inventing un-consumed methods (the Epic-2
 * retro flagged orphan additions as gold-plating).
 *
 * @package Ink\Core
 */
final class Api {
}

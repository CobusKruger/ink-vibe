<?php
/**
 * Writer Gradering enum.
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Kernel;

defined( 'ABSPATH' ) || exit;

/**
 * Writer Gradering (tier) — the fixed value set for `ink_writer_tier`.
 *
 * Kernel-owned (NOT in the Tiers module) so that BOTH the Tiers module and the
 * Challenges module can read it from the shared Kernel without creating an
 * inter-module dependency edge. The backing string is the persisted DB value
 * (lowercase Afrikaans); never duplicate these literals across the codebase.
 *
 * THE conflation rule (AD-1): Gradering controls competition pools and is kept
 * strictly independent of lidmaatskap entitlement — `Ink\Tiers` ⟂
 * `Ink\Entitlement`. No tier/promotion logic lives in this enum; it is a value
 * type only.
 *
 * `Meester` is a manual-only terminal grade (never auto-promoted), modelled
 * here for completeness (Epic 5). No behaviour is attached at 1.7.
 *
 * @package Ink\Core
 */
enum Tier: string {

	case Brons   = 'brons';
	case Silwer  = 'silwer';
	case Goud    = 'goud';
	case Meester = 'meester';
}

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
 * `Meester` is a manual-only terminal grade (never auto-promoted). Epic 5
 * (Story 5.1) attaches the data-model behaviour: the single-source default
 * ({@see self::default()}), the manual-only/terminal semantics
 * ({@see self::isManualOnly()} / {@see self::isAutoPromotable()}), and the
 * Kernel-owned meta-key single source ({@see self::META_KEY}) that BOTH the
 * `Ink\Content\UserMeta` registrar and the `Ink\Tiers\Api` reader share
 * without an inter-module dependency edge. Presentation (the `primary #EA4015`
 * Meester colour token, the Afrikaans grade labels) stays OUT of the enum —
 * it is owned by Story 5.4 (theme tokens + the I18n terminology registry).
 *
 * @package Ink\Core
 */
enum Tier: string {

	case Brons   = 'brons';
	case Silwer  = 'silwer';
	case Goud    = 'goud';
	case Meester = 'meester';

	/**
	 * The `ink_writer_tier` user-meta key — Kernel-owned single source.
	 *
	 * Lives here (not on `Ink\Content\UserMeta`) so the `Ink\Tiers` reader can
	 * reference it without a forbidden `Tiers → Content` edge (deptrac allows
	 * `Tiers: [Kernel]` only). `UserMeta::WRITER_TIER` aliases this constant.
	 */
	public const META_KEY = 'ink_writer_tier';

	/**
	 * The `ink_tier_promoted_at` user-meta key — Kernel-owned single source.
	 *
	 * Paired with {@see self::META_KEY}; `UserMeta::TIER_PROMOTED_AT` aliases it.
	 */
	public const PROMOTED_AT_META_KEY = 'ink_tier_promoted_at';

	/**
	 * The `ink_tier_win_count` user-meta key — Kernel-owned single source (Story 5.7).
	 *
	 * Holds the top-3 wins accumulated toward the next Gradering; reset to 0 by
	 * the `Ink\Tiers\Api::promote()` path. Kernel-owned so the `Ink\Tiers` reader/
	 * writer needs no `Tiers → Content` edge; `UserMeta::WIN_COUNT` aliases it.
	 */
	public const WIN_COUNT_META_KEY = 'ink_tier_win_count';

	/**
	 * The single-source default grade for an unset/new writer.
	 *
	 * Mirrors the `register_meta` default and the read-side guarantee in
	 * {@see \Ink\Tiers\Api::forUser()} (a raw `get_user_meta` of an unset user
	 * returns `''`, not this default — read through the Tiers facade).
	 */
	public static function default(): self {
		return self::Brons;
	}

	/**
	 * Whether this grade is manual-only — staff-set, never produced by the
	 * automatic promotion engine (Story 5.8).
	 *
	 * Only `Meester` is manual-only (the sole path to it is a staff set/adjust).
	 */
	public function isManualOnly(): bool {
		return self::Meester === $this;
	}

	/**
	 * Whether the automatic promotion engine (Story 5.8) may promote FROM this
	 * grade.
	 *
	 * `Brons` and `Silwer` have auto-thresholds (5 / 15 wins — Story 5.8);
	 * `Goud` is terminal for auto-promotion (no threshold above it) and
	 * `Meester` is manual-only terminal. The 5/15 thresholds themselves are NOT
	 * encoded here — they live in the engine (Story 5.8); this only states which
	 * grades participate in auto-promotion at all.
	 */
	public function isAutoPromotable(): bool {
		return match ( $this ) {
			self::Brons, self::Silwer => true,
			self::Goud, self::Meester => false,
		};
	}
}

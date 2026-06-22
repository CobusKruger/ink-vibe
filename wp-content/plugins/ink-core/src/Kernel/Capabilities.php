<?php
/**
 * Custom capability registry.
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Kernel;

defined( 'ABSPATH' ) || exit;

/**
 * Kernel-owned registry of INK custom capabilities.
 *
 * The three-tier permission check (AD-6) routes editorial actions through
 * `current_user_can('ink_{cap}')`. The custom capabilities
 * (`ink_manage_tiers`, `ink_manage_challenges`, `ink_manage_sponsors`,
 * `ink_moderate`) are mapped to roles in a later epic. This stub reserves the
 * seam and names the capabilities as constants so modules reference one source.
 *
 * NO capability is granted to any role at 1.7 — no `add_cap()` / `WP_Role`
 * mutation is performed. This is the reserved extension point only.
 *
 * @package Ink\Core
 */
final class Capabilities {

	/**
	 * Manage writer Gradering (set/adjust tiers, view history).
	 */
	public const MANAGE_TIERS = 'ink_manage_tiers';

	/**
	 * Manage challenges (uitdagings) and adjudication.
	 */
	public const MANAGE_CHALLENGES = 'ink_manage_challenges';

	/**
	 * Manage sponsors (borge) and scheduling.
	 */
	public const MANAGE_SPONSORS = 'ink_manage_sponsors';

	/**
	 * Moderate content and process reports.
	 */
	public const MODERATE = 'ink_moderate';

	/**
	 * All INK custom capabilities, for iteration by the (later) role-mapping
	 * step.
	 *
	 * @return list<string>
	 */
	public static function all(): array {
		return array(
			self::MANAGE_TIERS,
			self::MANAGE_CHALLENGES,
			self::MANAGE_SPONSORS,
			self::MODERATE,
		);
	}
}

<?php
/**
 * Tiers module public facade (reserved).
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Tiers;

use Ink\Kernel\Tier;

defined( 'ABSPATH' ) || exit;

/**
 * Tiers module facade — the sole public cross-module surface for Tiers (Epic 5).
 *
 * Exposes the Gradering read API (used by Challenges for pool segmentation and
 * by the profile/discovery surfaces); `promote()` (the sole tier write path)
 * lands in Story 5.7/5.8. Other modules reach Tiers only through this facade
 * (AD-1). MUST NOT reference `Ink\Entitlement\*` (THE conflation rule) and reads
 * only the Kernel `Tier` value type + WordPress — never another domain module
 * (deptrac `Tiers: [Kernel]`).
 *
 * @package Ink\Core
 */
final class Api {

	/**
	 * The typed, default-safe writer Gradering for a user.
	 *
	 * THE read path every Epic-5 consumer must use instead of a raw
	 * `get_user_meta()`. The `register_meta` `default` ({@see \Ink\Content\UserMeta})
	 * only resolves on WP's default-aware read paths, so a raw
	 * `get_user_meta( $id, 'ink_writer_tier', true )` for a writer who never had a
	 * tier written returns `''` — not `brons`. This accessor guarantees a `Tier`
	 * case in every case: an unset/empty/non-scalar value yields
	 * {@see Tier::default()} (Brons), and any unrecognised stored string is
	 * coerced back to the default — never `null`, never a raw string. Closes the
	 * Epic-2 review deferral on Story 2.3.
	 *
	 * @param int $user_id The WordPress user id.
	 * @return Tier The writer's Gradering, defaulting to Brons.
	 */
	public static function forUser( int $user_id ): Tier {
		$raw = get_user_meta( $user_id, Tier::META_KEY, true );

		if ( ! is_scalar( $raw ) || '' === (string) $raw ) {
			return Tier::default();
		}

		return Tier::tryFrom( (string) $raw ) ?? Tier::default();
	}
}

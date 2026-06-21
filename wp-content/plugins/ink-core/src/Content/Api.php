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
 * Content module facade.
 *
 * The sole public cross-module surface for Content (AD-1): other modules
 * (Submission, Discovery, Training, Challenges, …) read the INK post-type and
 * taxonomy slugs through this facade, never reaching into {@see PostTypes} or
 * {@see Taxonomies} internals. At 2.1/2.2 it exposes the slug surface; later
 * Epic-2 stories extend it with meta accessors.
 *
 * @package Ink\Core
 */
final class Api {

	/**
	 * Every INK post-type slug, registration order preserved.
	 *
	 * @return list<string>
	 */
	public static function all(): array {
		return PostTypes::all();
	}

	/**
	 * The member-submission CPTs ("bydraes"): gedig, storie, artikel, skryfwerk.
	 *
	 * @return list<string>
	 */
	public static function bydraeTypes(): array {
		return PostTypes::bydraeTypes();
	}

	/**
	 * Every INK taxonomy slug: genre, vaardigheid, uitdagingsrondte, ster_gradering.
	 *
	 * @return list<string>
	 */
	public static function taxonomies(): array {
		return Taxonomies::all();
	}

	/**
	 * The writer-tier user-meta keys: ink_writer_tier, ink_tier_promoted_at.
	 *
	 * The registered key surface only; the behavioural tier read/write API is the
	 * Epic-5 Tiers facade.
	 *
	 * @return list<string>
	 */
	public static function userMetaKeys(): array {
		return UserMeta::keys();
	}
}

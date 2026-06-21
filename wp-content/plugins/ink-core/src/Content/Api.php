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
 * (Submission, Discovery, …) read the INK post-type slugs through this facade,
 * never reaching into {@see PostTypes}' internals. At 2.1 it exposes the slug
 * surface only; later Epic-2 stories extend it with taxonomy/meta accessors.
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
}

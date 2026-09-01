<?php
/**
 * The single predicate for "is this a QA-fixture post" (theme-fidelity rework).
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Kernel;

defined( 'ABSPATH' ) || exit;

/**
 * Recognises real, seeded QA-fixture content by its title convention.
 *
 * Several dynamic home-page blocks read directly from a live `WP_Query` with no
 * data-seam filter to intercept (documented in the theme's
 * `functions.php` as the "REAL SEEDED WP CONTENT" fixture technique — used when a
 * block has no `*_FILTER` seam to hook, e.g. {@see \Ink\Sponsors\HomepageStrip}).
 * Those fixtures are real, published posts titled with the `QA FIXTURE — ` prefix
 * (the established convention — unmistakable in wp-admin lists) so they can be
 * seeded without polluting editorial content, but a live query has no way to know
 * they are not real content: left alone, they leak onto real pages exactly like
 * genuine posts would (Epic-19 theme-fidelity rework finding).
 *
 * This is the ONE place that recognises the convention, so every live-query
 * consumer excludes fixtures the same way. Pure, WordPress-free — lives in the
 * Kernel (the shared base every module already depends on; deptrac.yaml) so it
 * introduces zero new cross-module edges.
 *
 * @package Ink\Core
 */
final class QaFixture {

	/**
	 * The title prefix every seeded QA-fixture post carries.
	 *
	 * @var string
	 */
	public const TITLE_PREFIX = 'QA FIXTURE';

	/**
	 * Whether a post title marks it as seeded QA-fixture content.
	 *
	 * @param string $title The post title (already resolved, e.g. via `get_the_title()`).
	 * @return bool
	 */
	public static function isFixtureTitle( string $title ): bool {
		return str_starts_with( trim( $title ), self::TITLE_PREFIX );
	}
}

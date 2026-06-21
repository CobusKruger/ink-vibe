<?php
/**
 * Social module bootstrap (reserved).
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Social;

use Ink\Kernel\Module as ModuleContract;

defined( 'ABSPATH' ) || exit;

/**
 * Social module — RESERVED extension point for Epic 9.
 *
 * Will own the custom asymmetric follow graph (one-way; BuddyPress Friend
 * Connections are OFF), the following-feed, pinned/selected works, reader
 * ratings & reviews, and the BuddyPress glue (scoped profiles/directory/
 * notifications). NOTHING is implemented at 1.7.
 *
 * @package Ink\Core
 */
final class Module implements ModuleContract {

	/**
	 * Register this module's hooks. No-op until Epic 9.
	 */
	public function register(): void {
		// Reserved: follow graph, feed, ratings, BP glue land in Epic 9.
	}
}

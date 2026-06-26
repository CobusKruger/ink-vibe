<?php
/**
 * Discovery module bootstrap (reserved).
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Discovery;

use Ink\Kernel\Module as ModuleContract;

defined( 'ABSPATH' ) || exit;

/**
 * Discovery module — the Ontdek surfaces (Epic 8; + Training cross-surfacing
 * 11.2/11.4).
 *
 * Owns search, faceted queries, shared-taxonomy cross-surfacing and the trending
 * job (server-rendered discovery with denormalized sort counts, AD-7). Live at
 * 8.1: the {@see WorksArchive} works-archive block (the Ontdek hub's default
 * listing). The bydraes-tab filter/sort, skrywers tab, search and personalised
 * surfaces extend this module in Stories 8.2–8.5.
 *
 * @package Ink\Core
 */
final class Module implements ModuleContract {

	/**
	 * Register this module's hooks.
	 *
	 * Dispatched once by the Kernel on `init`. Delegates to the collaborator
	 * blocks so this bootstrap stays thin (the Engagement/Content house style).
	 */
	public function register(): void {
		( new WorksArchive() )->register();
		( new TrendingScore() )->register();
		( new SkrywerIndex() )->register();
		( new ReadCount() )->register();
		( new SkrywersTab() )->register();
	}
}

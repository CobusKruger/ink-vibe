<?php
/**
 * Engagement module bootstrap (reserved).
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Engagement;

use Ink\Kernel\Module as ModuleContract;

defined( 'ABSPATH' ) || exit;

/**
 * Engagement module bootstrap.
 *
 * Owns INK reading engagement (which lives here, NOT in free-form WP comments).
 * Live at 1.8: the site-wide comment-disable layer ({@see Comments}) that closes
 * WordPress's public commenting surface while leaving the sanctioned programmatic
 * custom comment types (`ink_reaksie`, `ink_moderator_terugvoer`) untouched.
 *
 * RESERVED for Epic 7: line highlights + reactions, structured community
 * responses (Gemeenskapsreaksies: Lof/Insig/Voorstel), the reading list (leeslys)
 * and denormalized engagement counts.
 *
 * @package Ink\Core
 */
final class Module implements ModuleContract {

	/**
	 * Register this module's hooks.
	 *
	 * Dispatched once by the Kernel on `init` (via `Plugin::registerModules()`).
	 * Delegates the comment-disable behaviour to {@see Comments} so this bootstrap
	 * stays thin, mirroring the Kernel `Plugin` → collaborators house style.
	 */
	public function register(): void {
		( new Comments() )->register();
		( new GedigBody() )->register();
		( new ReactionController() )->register();
		( new ResponseController() )->register();
		( new ResponsesList() )->register();
		( new ContextualPrompts() )->register();
		( new SuggestedReads() )->register();
		( new ReadingListController() )->register();
		( new ReadingListToggle() )->register();
		( new ReadingList() )->register();
	}
}

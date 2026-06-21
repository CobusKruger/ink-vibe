<?php
/**
 * Site-wide comment-disable layer.
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Engagement;

defined( 'ABSPATH' ) || exit;

/**
 * Disables WordPress's public/free-form commenting site-wide (feature 1.8).
 *
 * INK reading engagement lives in `ink-core` (structured Gemeenskapsreaksies,
 * line highlights + reactions, leeslys, ratings) — NOT in free-form WP comments.
 * This collaborator closes the public comment surface:
 *
 *  - forces `comments_open` / `pings_open` to false for every post type/query
 *    (the runtime guard — wins at `PHP_INT_MAX` priority);
 *  - removes `comments`/`trackbacks` post-type support so the editor discussion
 *    panel and the front-end comment form never render (the UI guard);
 *  - hides the comment admin/UI chrome (Comments menu, admin-bar node, dashboard
 *    "Recent Comments" widget) and the front-end comments feed.
 *
 * What it deliberately does NOT do (AD-5a):
 *
 *  - It does NOT unregister the comments subsystem. The core Comments admin list
 *    screen stays reachable as the moderation surface for the two SANCTIONED
 *    programmatic custom comment types — `ink_reaksie` (Gemeenskapsreaksies,
 *    Epic 7) and `ink_moderator_terugvoer` (moderator feedback, Story 12A.5).
 *  - It does NOT interfere with programmatic `wp_insert_comment()` writes.
 *    WordPress's `comments_open`/`pings_open` guard the public new-comment path,
 *    NOT direct programmatic inserts — so the structured stores that reuse the
 *    comment substrate keep working regardless of this layer. That is the
 *    inherent seam preserving the sanctioned exception.
 *  - It touches ONLY WP's public commenting surface — no `$wpdb` query, no
 *    `wp_delete_comment`, no custom-table mutation, no comment-type registration.
 *
 * Re-enable seam: every close decision passes through the
 * {@see Comments::FILTER_OPEN_EXCEPTION} filter (default false). A later story
 * that genuinely needs a UI-path context re-opened for a reserved ink
 * comment-type can filter it to true for that NARROW context — without ever
 * re-enabling site-wide commenting. This story registers no exception.
 *
 * @package Ink\Core
 */
final class Comments {

	/**
	 * Sanctioned extension point: the single documented filter through which a
	 * later story may re-open commenting for a specific context.
	 *
	 * `apply_filters( self::FILTER_OPEN_EXCEPTION, false, $post_id, $context )`
	 * — returns false everywhere by default (commenting closed). Programmatic
	 * `wp_insert_comment` for the custom types is unaffected and does not rely on
	 * this filter.
	 */
	public const FILTER_OPEN_EXCEPTION = 'ink_comment_open_exception';

	/**
	 * Register all comment-disable hooks.
	 *
	 * Called once by {@see Module::register()} (dispatched by the Kernel on
	 * `init`). Hooks are wired via first-class-callable method references.
	 */
	public function register(): void {
		// AC-1: force the open flags closed for every post type/query. Late
		// priority so the close wins over any post-stored value or other plugin.
		add_filter( 'comments_open', $this->closeComments( ... ), PHP_INT_MAX, 2 );
		add_filter( 'pings_open', $this->closeComments( ... ), PHP_INT_MAX, 2 );

		// AC-2: remove comment/trackback support from every post type (UI guard).
		add_action( 'init', $this->removeCommentSupport( ... ), PHP_INT_MAX );

		// AC-3: drop the global comments feed link.
		add_filter( 'feed_links_show_comments_feed', '__return_false' );

		// AC-3: hide the admin/UI chrome (chrome only — moderation surface stays).
		add_action( 'admin_menu', $this->removeCommentsMenu( ... ) );
		add_action( 'admin_bar_menu', $this->removeAdminBarComments( ... ), PHP_INT_MAX );
		add_action( 'wp_dashboard_setup', $this->removeDashboardCommentsWidget( ... ) );
	}

	/**
	 * Force comments/pings closed, subject only to the sanctioned exception seam.
	 *
	 * Ignores the incoming stored value: commenting is closed site-wide unless a
	 * later story filters {@see Comments::FILTER_OPEN_EXCEPTION} to true for a
	 * specific context. Default is always false.
	 *
	 * @param bool       $open    Incoming open flag (ignored).
	 * @param int|string $post_id Post the flag is evaluated for.
	 * @return bool False (closed) by default; true only if the exception filter says so.
	 */
	public function closeComments( bool $open, int|string $post_id = 0 ): bool {
		return (bool) apply_filters( self::FILTER_OPEN_EXCEPTION, false, (int) $post_id, 'comments_open' );
	}

	/**
	 * Remove `comments` and `trackbacks` support from every registered post type.
	 *
	 * Stops the editor discussion panel and the front-end comment form from
	 * rendering. Runs late on `init` so CPTs registered earlier this hook are
	 * covered.
	 */
	public function removeCommentSupport(): void {
		foreach ( get_post_types() as $post_type ) {
			if ( post_type_supports( $post_type, 'comments' ) ) {
				remove_post_type_support( $post_type, 'comments' );
			}

			if ( post_type_supports( $post_type, 'trackbacks' ) ) {
				remove_post_type_support( $post_type, 'trackbacks' );
			}
		}
	}

	/**
	 * Remove the admin "Comments" top-level menu (chrome only).
	 *
	 * The core Comments list screen stays reachable for moderating the sanctioned
	 * programmatic custom comment types (AD-5a) — only the menu chrome is hidden.
	 */
	public function removeCommentsMenu(): void {
		remove_menu_page( 'edit-comments.php' );
	}

	/**
	 * Remove the admin-bar "Comments" node.
	 *
	 * @param \WP_Admin_Bar $admin_bar The admin-bar instance passed by `admin_bar_menu`.
	 */
	public function removeAdminBarComments( \WP_Admin_Bar $admin_bar ): void {
		$admin_bar->remove_node( 'comments' );
	}

	/**
	 * Remove the dashboard "Recent Comments / Activity" comment widget.
	 */
	public function removeDashboardCommentsWidget(): void {
		remove_meta_box( 'dashboard_recent_comments', 'dashboard', 'normal' );
	}
}

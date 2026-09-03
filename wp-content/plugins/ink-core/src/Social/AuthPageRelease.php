<?php
/**
 * Frees the `/registreer/` URL from BuddyPress's own Register CPT post
 * (Epic 19 auth fidelity pass).
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Social;

defined( 'ABSPATH' ) || exit;

/**
 * A brownfield DB clone had `bp-pages['register']` mapped to a real WordPress
 * post — post_type `buddypress`, not `page` — at the `registreer` slug. That
 * `buddypress`-post-type object is BuddyPress's own legacy, un-translated,
 * unstyled Register screen; WordPress's generic slug lookup resolves
 * `/registreer/` to it because it is the ONLY post anywhere in the DB with
 * that slug — no `page`-post-type object with slug `registreer` has ever
 * existed, so the theme's own `templates/page-registreer.html` /
 * `patterns/auth-register.php` (which explicitly commit to using WordPress's
 * OWN `wp-login.php?action=register` endpoint, never BuddyPress's) has never
 * actually been reachable at that URL.
 *
 * {@see BuddyPress::excludeAuthPages()} stops BuddyPress's own
 * `bp_is_register_page()`/component-classification logic from treating that
 * URL as its Register screen, but does NOT change which post the slug
 * resolves to — that's DB content, not something a read-side filter can fix.
 * This class does the (idempotent, self-healing) content-level repair: rename
 * the stray `buddypress`-post-type object off the `registreer` slug, then
 * ensure a real `page`-post-type object exists there so the theme's own
 * template can finally render. Same "code-enforced, not a one-time admin
 * fix-up" reasoning as {@see BuddyPress::scopeComponents()} — any environment
 * cloned from this brownfield DB (staging, production) carries the identical
 * stray mapping, so this must self-heal on every load, not run once by hand.
 */
final class AuthPageRelease {

	/**
	 * The slug BuddyPress's own Register post is renamed to once freed.
	 *
	 * Kept, not trashed — BuddyPress's own code may still reference the post ID
	 * (e.g. a stale `bp-pages` option entry); renaming off the collision slug is
	 * enough, deleting it is unnecessary risk for no further benefit.
	 */
	private const FREED_SLUG = 'buddypress-register-legacy';

	/**
	 * The slug this repair claims for the theme's own Register page.
	 */
	private const TARGET_SLUG = 'registreer';

	/**
	 * Register this class's hooks.
	 *
	 * Runs late on `init` (after BuddyPress's own `init`-time `buddypress` CPT
	 * registration) so `post_type_exists( 'buddypress' )` is reliable. Renaming
	 * the post takes effect on THIS request (WordPress's generic page lookup
	 * re-queries by slug every time — nothing here is process-cached), but
	 * `bp_get_signup_slug()` — which the Members component reads while building
	 * its `registreer/%member_register%`-shaped rewrite permastruct — feeds
	 * WordPress's compiled `rewrite_rules` option, which is cached until
	 * explicitly flushed. Without a flush, the OLD compiled regex (baked while
	 * the stray post still held the `registreer` slug) keeps matching the URL
	 * and routing it through BuddyPress's handler regardless of the rename, so
	 * {@see self::ensureTargetSlugIsFree()} triggers a flush itself, but only
	 * on the (rare, self-healing) repair path — never on the steady-state
	 * no-op, so this stays cheap after the first successful run.
	 */
	public function register(): void {
		add_action( 'init', array( $this, 'ensureTargetSlugIsFree' ), 20 );
	}

	/**
	 * Idempotent repair: no-op once a real `page` exists at {@see self::TARGET_SLUG}.
	 */
	public function ensureTargetSlugIsFree(): void {
		if ( $this->pageAlreadyExists() ) {
			return;
		}

		$this->renameStrayBuddyPressPost();
		$this->createRegisterPage();

		// Soft flush: the stray post's rename only takes effect for BuddyPress's
		// OWN `registreer`-slug permastruct once the compiled rewrite_rules
		// option is regenerated — see the class-level rationale above.
		flush_rewrite_rules( false );
	}

	/**
	 * Whether a `page`-post-type object already occupies the target slug.
	 */
	private function pageAlreadyExists(): bool {
		$existing = get_page_by_path( self::TARGET_SLUG, OBJECT, 'page' );

		return null !== $existing;
	}

	/**
	 * Rename any `buddypress`-post-type object squatting on the target slug.
	 *
	 * Scoped strictly to `post_type = 'buddypress'` — never touches a `page` (or
	 * any other post type) even if one somehow already carries this slug, so a
	 * re-run after a partial/manual fix elsewhere can never clobber real content.
	 */
	private function renameStrayBuddyPressPost(): void {
		if ( ! post_type_exists( 'buddypress' ) ) {
			return;
		}

		$stray = get_page_by_path( self::TARGET_SLUG, OBJECT, 'buddypress' );

		if ( null === $stray ) {
			return;
		}

		wp_update_post(
			array(
				'ID'        => $stray->ID,
				'post_name' => self::FREED_SLUG,
			)
		);
	}

	/**
	 * Create the real `page` object the theme's `page-registreer.html`
	 * template hierarchy match needs.
	 *
	 * Empty content — the FSE template supplies the entire
	 * `ink-foundation/auth-register` pattern; the page object exists purely to
	 * give WordPress something of post_type `page` to route the slug to.
	 */
	private function createRegisterPage(): void {
		wp_insert_post(
			array(
				'post_title'  => __( 'Registreer', 'ink-foundation' ),
				'post_name'   => self::TARGET_SLUG,
				'post_type'   => 'page',
				'post_status' => 'publish',
				'post_content' => '',
			)
		);
	}
}

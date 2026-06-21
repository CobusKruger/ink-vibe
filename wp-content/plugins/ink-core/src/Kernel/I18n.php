<?php
/**
 * Text-domain loader + admin-language mechanism (the Kernel i18n concern).
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Kernel;

defined( 'ABSPATH' ) || exit;

/**
 * The ink-core Kernel i18n concern (Story 1.10 — built out from the 1.7 stub).
 *
 * Owns the two cross-cutting i18n behaviours the architecture lists as Kernel
 * concerns ("i18n loader"):
 *
 *  1. Text-domain loading — loads the `ink-core` text domain from the plugin's
 *     `/languages` directory on `init` ({@see I18n::load()}).
 *  2. The admin-language mechanism (§14.14) — forces editor/administrator users'
 *     ADMIN language to English (`en_US`) via the supported read-time
 *     `get_user_locale` filter ({@see I18n::forceStaffAdminLocale()}), while the
 *     site/front-end locale stays `af`.
 *
 * **Afrikaans is the gettext SOURCE language of every ink-core string** — the
 * `__()`/`_e()` literal IS the Afrikaans text. ink-core ships **no English `.mo`**
 * (`ink-core-en_US.mo`/`.po`). Consequently, under a staff member's forced-English
 * admin locale gettext finds no `ink-core` translation for `en_US` and falls
 * through to the Afrikaans source literal — so ink-core's own admin labels render
 * Afrikaans inside the English WP-core/3rd-party admin chrome (the admin-language
 * split, §14.15). There is nothing to do at runtime for §14.15: the policy is the
 * ABSENCE of an English `.mo` (enforced by `languages/.gitkeep`), not code.
 *
 * Site locale `af` itself rides the brownfield DB / `wp_options` (carried forward
 * in migration Story 16.11) — it is NOT hard-switched here (that would fight WP
 * and the Site Editor). This class only makes the plumbing correct so that, with
 * locale `af`, custom strings resolve Afrikaans.
 *
 * Wired by {@see Plugin} on `init` (load) / via `get_user_locale` (admin locale).
 *
 * @package Ink\Core
 */
final class I18n {

	/**
	 * The ink-core text domain. Matches the plugin directory, main file and
	 * the header `Text Domain`.
	 */
	public const TEXT_DOMAIN = 'ink-core';

	/**
	 * The locale staff (editor/administrator) are forced to in wp-admin (§14.14).
	 *
	 * WordPress's canonical built-in English locale — no language pack required.
	 * If the org ever prefers `en_GB`, this is the single point of change.
	 */
	public const ADMIN_LOCALE = 'en_US';

	/**
	 * Load the `ink-core` text domain.
	 *
	 * Wired by {@see Plugin} on the `init` hook (current WP guidance —
	 * `load_plugin_textdomain` must not run before `init`). The language files,
	 * if any AFRIKAANS artifact is ever shipped, live in the plugin's
	 * `/languages` directory (Domain Path). No English `.mo` is shipped here
	 * (§14.15) — see the class docblock.
	 */
	public static function load(): void {
		load_plugin_textdomain(
			self::TEXT_DOMAIN,
			false,
			dirname( plugin_basename( INK_CORE_FILE ) ) . '/languages'
		);
	}

	/**
	 * Force editor/administrator users to the English admin locale (§14.14).
	 *
	 * Filters `get_user_locale`: returns {@see I18n::ADMIN_LOCALE} (`en_US`) for
	 * users with the editor/administrator-defining capability — but ONLY in admin
	 * context. Outside wp-admin it returns the incoming `$locale` unchanged, so
	 * the front end is untouched: front-end output resolves via the SITE locale
	 * (`get_locale()`/`determine_locale()`), which does not consult
	 * `get_user_locale`, so visitor output stays Afrikaans (`af`) for everyone,
	 * staff included. The `is_admin()` guard is belt-and-braces on top of that.
	 *
	 * Why the read-time filter (and not `update_user_meta($id,'locale','en_US')`):
	 * the filter is idempotent, performs no DB mutation, is consulted by admin
	 * locale resolution, and cannot leak to the front end. Force-writing the
	 * `locale` user-meta on read would be a mutation-on-read anti-pattern and
	 * would fight a staff member who legitimately set their own preference.
	 *
	 * @param string         $locale  The user locale WordPress resolved.
	 * @param int|\WP_User    $user_id The user id (or `WP_User`) the locale is for.
	 * @return string `en_US` for staff in wp-admin; the incoming `$locale` otherwise.
	 */
	public static function forceStaffAdminLocale( string $locale, int|\WP_User $user_id ): string {
		// Front end (and any non-admin context) is never touched — site locale wins.
		if ( ! is_admin() ) {
			return $locale;
		}

		$user = $user_id instanceof \WP_User ? $user_id : get_userdata( $user_id );

		if ( ! $user instanceof \WP_User ) {
			return $locale;
		}

		// `edit_others_posts` is the editor/administrator-defining capability; a
		// member/subscriber lacks it, so only staff are forced to English admin.
		if ( user_can( $user, 'edit_others_posts' ) ) {
			return self::ADMIN_LOCALE;
		}

		return $locale;
	}
}

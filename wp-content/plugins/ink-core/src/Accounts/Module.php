<?php
/**
 * Accounts module bootstrap.
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Accounts;

use Ink\Kernel\Module as ModuleContract;

defined( 'ABSPATH' ) || exit;

/**
 * Accounts module bootstrap (Epic 3, Stories 3.1 + 3.3).
 *
 * Owns INK's account-creation + post-signup-onboarding BUSINESS BEHAVIOUR:
 *  - the new-account defaults (sets `ink_writer_tier = brons` / gratis lid on
 *    `user_register`) and the Afrikaans transactional auth email wiring
 *    (account-welcome template via the Notifications capability + Afrikaans
 *    WP-core password-reset mail filters) — {@see Registration} (Story 3.1);
 *  - the one-time, skippable post-signup onboarding STATE (the
 *    `ink_onboarding_complete` user-meta flag + its nonce-protected
 *    skip/complete write) — {@see Onboarding} (Story 3.3).
 * WordPress owns the auth MECHANISM (credential storage, sessions, lost-password
 * tokens) — this module hooks it, never reimplements it. The Afrikaans auth +
 * onboarding SCREENS are presentation and live in the `ink-foundation` theme;
 * the first-action prompt is a graceful-degrading PRESENTATION seam there.
 *
 * THE conflation rule (AD-1): a new account is a gratis lid — the ABSENCE of an
 * active lidmaatskap. This module writes only the writer tier + the onboarding
 * flag and carries NO reference to `Ink\Entitlement` / `Ink\Tiers`; submission
 * entitlement is a separate WooCommerce-Memberships concern evaluated at the
 * publish moment (Epic 4 / AD-2).
 *
 * The R6 social-login SEAM (Story 3.5) also lives here: {@see SocialLogin} is a
 * read-only availability helper (`isAvailable()`, default-off filter) the theme
 * bridge consumes to decide whether to render the social section — it has no
 * hooks of its own (so it is not wired into {@see register()}), implements NO
 * OAuth, and stores no tokens; the vetted plugin owns the auth, integrated via
 * hooks at deploy time.
 *
 * Explicitly NOT this module's job: the follow graph (Story 9.2 / `Ink\Social`)
 * and the leeslys `ink_reading_list` (Story 7.7 / `Ink\Engagement`) — onboarding
 * only PROMPTS toward them and degrades gracefully, building no table / REST
 * write; the reader/writer intent gate (Story 3.2 — REMOVED; no intent flag is
 * stored); the anti-spam defense build-out (Story 3.4 decided it; hardening is
 * 18.10) and the approval queue (Story 3.6, R6); social-login OAuth itself (the
 * vetted plugin's job — this module only exposes the availability seam); the
 * Lidmaatskap purchase flow (Epic 4); the My Profiel / Skrywerprofiel pages
 * (Epic 9); the promotion engine / full editorial-role policy (Epic 5).
 *
 * @package Ink\Core
 */
final class Module implements ModuleContract {

	/**
	 * Register this module's hooks.
	 *
	 * Dispatched once by the Kernel on `init` (via `Plugin::registerModules()`).
	 * Delegates to the {@see Registration} (Story 3.1) and {@see Onboarding}
	 * (Story 3.3) collaborators so this bootstrap stays thin, mirroring the
	 * `Content\Module → {PostTypes,Taxonomies,UserMeta,…}` /
	 * `Engagement\Module → Comments` multi-collaborator house style.
	 */
	public function register(): void {
		( new Registration() )->register();
		( new Onboarding() )->register();
	}
}

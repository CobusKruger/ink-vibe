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
 *    skip/complete write) — {@see Onboarding} (Story 3.3);
 *  - the OFF-by-default R6 manual-approval BACKSTOP (the fail-safe-OFF toggle,
 *    the WP-native "wag vir goedkeuring" pending account state stamped on
 *    `user_register` when enabled, the `wp_authenticate_user` login gate, and
 *    the admin approval-queue screen to goedkeur/verwerp) — {@see Approval}
 *    (Story 3.6). It is never the launch default; when OFF, signup stays exactly
 *    as frictionless as today (UJ-1).
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
 * stored); the always-on anti-spam baseline (Story 3.4 decided it — Turnstile,
 * email double-opt-in, honeypot/timing — layers 1–4, NOT built here) and the
 * edge rate-limiting / IP-reputation / Patchstack / Turnstile-tuning /
 * blocked-attempt-analytics HARDENING around the pending state (Story 18.10) —
 * the 3.6 toggle + pending state + approval queue themselves are owned above by
 * {@see Approval}; social-login OAuth itself (the
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
	 * Delegates to the {@see Registration} (Story 3.1), {@see Onboarding}
	 * (Story 3.3) and {@see Approval} (Story 3.6) collaborators so this bootstrap
	 * stays thin, mirroring the `Content\Module → {PostTypes,Taxonomies,UserMeta,…}`
	 * / `Engagement\Module → Comments` multi-collaborator house style.
	 *
	 * {@see SocialLogin} (Story 3.5) is deliberately NOT wired here — it is a
	 * read-only availability seam with no hooks of its own. {@see Approval}, by
	 * contrast, registers hooks (`user_register`, `wp_authenticate_user`,
	 * `admin_menu`, `admin_post_*`), so it MUST be wired; its runtime behaviour
	 * stays gated OFF-by-default by its own toggle.
	 */
	public function register(): void {
		( new Registration() )->register();
		( new Onboarding() )->register();
		( new Approval() )->register();
	}
}

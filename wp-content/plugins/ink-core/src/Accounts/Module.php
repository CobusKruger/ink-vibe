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
 * Accounts module bootstrap (Epic 3, Story 3.1).
 *
 * Owns INK's account-creation BUSINESS BEHAVIOUR — the new-account defaults
 * (sets `ink_writer_tier = brons` / gratis lid on `user_register`) and the
 * Afrikaans transactional auth email wiring (account-welcome template via the
 * Notifications capability + Afrikaans WP-core password-reset mail filters).
 * WordPress owns the auth MECHANISM (credential storage, sessions, lost-password
 * tokens) — this module hooks it, never reimplements it. The Afrikaans auth
 * SCREENS are presentation and live in the `ink-foundation` theme.
 *
 * THE conflation rule (AD-1): a new account is a gratis lid — the ABSENCE of an
 * active lidmaatskap. This module writes only the writer tier and carries NO
 * reference to `Ink\Entitlement` / `Ink\Tiers`; submission entitlement is a
 * separate WooCommerce-Memberships concern evaluated at the publish moment
 * (Epic 4 / AD-2).
 *
 * Explicitly NOT this module's job: the registration lifecycle / onboarding /
 * first-action prompt (Story 3.3), the reader/writer intent gate (Story 3.2 —
 * REMOVED; no intent flag is stored), anti-spam / social-login / the
 * approval queue (Stories 3.4–3.6, R6), and the member/editor roles + CPT
 * `capability_type` / taxonomy term-caps (deferred to Story 3.3).
 *
 * @package Ink\Core
 */
final class Module implements ModuleContract {

	/**
	 * Register this module's hooks.
	 *
	 * Dispatched once by the Kernel on `init` (via `Plugin::registerModules()`).
	 * Delegates to {@see Registration} so this bootstrap stays thin, mirroring the
	 * `Engagement\Module → Comments` / `Notifications\Module` house style.
	 */
	public function register(): void {
		( new Registration() )->register();
	}
}

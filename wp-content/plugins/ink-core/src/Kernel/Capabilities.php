<?php
/**
 * Custom capability registry.
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Kernel;

defined( 'ABSPATH' ) || exit;

/**
 * Kernel-owned registry of INK custom capabilities.
 *
 * The three-tier permission check (AD-6) routes editorial actions through
 * `current_user_can('ink_{cap}')`. The custom capabilities
 * (`ink_manage_tiers`, `ink_manage_challenges`, `ink_manage_sponsors`,
 * `ink_moderate`) name the editorial actions as constants so modules reference
 * one source.
 *
 * Story 3.3 (the deferred Epic-2 role/cap work) activates this seam: the four
 * custom caps are granted to the WordPress `editor` role (redakteur) — AD-6
 * line 463–465, editorial caps are static custom capabilities granted to the
 * `editor` role AT ACTIVATION. {@see Capabilities::grantToEditor()} performs the
 * persistent `WP_Role::add_cap()` mutation; it is invoked from
 * {@see \Ink\Kernel\Activation::activate()} (NOT on every `init` — role/cap
 * grants persist in the DB). The gratis lid maps to the WordPress `subscriber`
 * role (the default self-registration role); engagement is gated on
 * `is_user_logged_in()` + nonce, not a capability (AD-6), so no extra grant is
 * required for it.
 *
 * Deferred to Epic 5 (noted, not built here): the full editorial-role policy,
 * per-tier author caps, and the promotion-UI authorization scope.
 *
 * @package Ink\Core
 */
final class Capabilities {

	/**
	 * Manage writer Gradering (set/adjust tiers, view history).
	 */
	public const MANAGE_TIERS = 'ink_manage_tiers';

	/**
	 * Manage challenges (uitdagings) and adjudication.
	 */
	public const MANAGE_CHALLENGES = 'ink_manage_challenges';

	/**
	 * Manage sponsors (borge) and scheduling.
	 */
	public const MANAGE_SPONSORS = 'ink_manage_sponsors';

	/**
	 * Moderate content and process reports.
	 */
	public const MODERATE = 'ink_moderate';

	/**
	 * All INK custom capabilities, for iteration by the (later) role-mapping
	 * step.
	 *
	 * @return list<string>
	 */
	public static function all(): array {
		return array(
			self::MANAGE_TIERS,
			self::MANAGE_CHALLENGES,
			self::MANAGE_SPONSORS,
			self::MODERATE,
		);
	}

	/**
	 * The WordPress role the editorial custom caps are granted to (redakteur).
	 */
	public const EDITOR_ROLE = 'editor';

	/**
	 * The WordPress administrator role (keeps every INK custom cap).
	 */
	public const ADMIN_ROLE = 'administrator';

	/**
	 * The roles that receive INK's editorial custom caps at activation.
	 *
	 * `editor` (redakteur) per AD-6; `administrator` so a custom-cap gate (e.g. the
	 * taxonomy term-management caps mapped onto {@see MODERATE}) never locks out an
	 * admin — the deny-everyone guard.
	 *
	 * @return list<string>
	 */
	private static function editorialRoles(): array {
		return array( self::ADMIN_ROLE, self::EDITOR_ROLE );
	}

	/**
	 * Grant the four INK custom caps to the editorial roles (admin + editor).
	 *
	 * Story 3.3 / AD-6: editorial caps are static custom capabilities granted at
	 * ACTIVATION (this runs from {@see \Ink\Kernel\Activation::activate()}, not on
	 * every `init` — the grant persists in the DB). Idempotent: `add_cap()` is a
	 * no-op when the role already holds the cap, so re-activation never duplicates.
	 *
	 * Fail-safe: a missing role (removed by a site customisation) is skipped, never
	 * fatals. This is the "deny-everyone stub" guard inverted — the editorial caps
	 * this story gates live paths on ARE actually granted to real roles here.
	 */
	public static function grantToEditor(): void {
		foreach ( self::editorialRoles() as $role_name ) {
			$role = get_role( $role_name );

			if ( null === $role ) {
				continue;
			}

			foreach ( self::all() as $cap ) {
				$role->add_cap( $cap );
			}
		}
	}

	/**
	 * Remove the four INK custom caps from the editorial roles.
	 *
	 * The activation grant's inverse, for {@see \Ink\Kernel\Activation::deactivate()}
	 * to leave no orphaned custom caps behind. Fail-safe when a role is absent.
	 */
	public static function revokeFromEditor(): void {
		foreach ( self::editorialRoles() as $role_name ) {
			$role = get_role( $role_name );

			if ( null === $role ) {
				continue;
			}

			foreach ( self::all() as $cap ) {
				$role->remove_cap( $cap );
			}
		}
	}
}

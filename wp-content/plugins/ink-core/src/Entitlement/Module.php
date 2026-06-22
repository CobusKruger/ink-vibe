<?php
/**
 * Entitlement module bootstrap.
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Entitlement;

use Ink\Kernel\Module as ModuleContract;

defined( 'ABSPATH' ) || exit;

/**
 * Entitlement module — the WooCommerce Memberships seam (Epic 4).
 *
 * Story 4.1 populated the FIRST piece of this module: the config-driven
 * lidmaatskap PLAN REGISTRY ({@see MembershipPlans} / {@see LidmaatskapTerm} /
 * {@see MembershipPlan}), exposed to the rest of `ink-core` through {@see Api}
 * (the only cross-module surface). The three launch plan slots are fixed-term
 * (1/6/12 months — {@see LidmaatskapTerm}); the price is owned by the WooCommerce
 * product and resolved at runtime ({@see MembershipPlans::priceFor()}), never a
 * hardcoded value. No auto-renew / no discount at launch (Stories 4.9–4.11 are
 * post-launch).
 *
 * Story 4.2 adds the front-end PayFast purchase SEAM ({@see PurchaseActivation}):
 * it initiates a purchase of a 4.1 plan by handing off to the WooCommerce checkout
 * / WC PayFast gateway, and REACTS to the WooCommerce Memberships activation
 * transition (`wc_memberships_user_membership_status_changed`, gated on `active`)
 * to self-activate the lidmaatskap with no manual EFT/admin step and fire the
 * thank-you/activation email trigger via the Notifications API (placeholder
 * template, send toggle OFF — Story 4.8 owns the copy). PayFast is off-site; this
 * module stores no card data and hardcodes no gateway credential (AD-4).
 *
 * Still RESERVED for later Epic-4 stories (NOT built here): the submission-
 * entitlement gate `can_submit()` (Story 4.3 / AD-2 — evaluated against the
 * lidmaatskap end date in SAST) and the lifecycle email COPY + expiry warnings
 * (Stories 4.7/4.8).
 *
 * THE conflation rule (AD-1, FR-13): Entitlement controls submission entitlement
 * and is kept strictly independent of writer Gradering — `Ink\Entitlement` ⟂
 * `Ink\Tiers`. This module MUST NOT reference `Ink\Tiers\*`; the absence of that
 * edge is the conflation rule, enforced in CI by Deptrac/PHPArkitect (AD-1/AD-8).
 *
 * @package Ink\Core
 */
final class Module implements ModuleContract {

	/**
	 * Register this module's hooks.
	 *
	 * Dispatched once by the Kernel on `init` (via `Plugin::registerModules()`).
	 *
	 * Story 4.1 registers the PRODUCT-MAPPING SETTING — the `term-months =>
	 * product_id` map ({@see MembershipPlans::OPTION_PRODUCTS}) that drives which
	 * WooCommerce product each plan slot resolves its runtime price from. Without
	 * this, nothing could populate the mapping and the registry would always return
	 * null in practice (the "no producer" gap). `register_setting()` declares the
	 * option's existence, type, and a fail-safe `is_array`-guarded sanitiser so a
	 * later admin UI (the Lidmaatskap admin surface) — or `update_option()` /
	 * `wp i18n`-style scripting — has a documented, sanitised persisted store. The
	 * map can ALSO be supplied/overridden without persistence via the
	 * {@see MembershipPlans::FILTER_PRODUCTS} filter (the config seam), so site
	 * config needs no `ink-core` edit.
	 *
	 * The plan registry itself ({@see MembershipPlans}) stays a passive, config-
	 * driven ACCESSOR consumed on demand through {@see Api} — it owns no runtime
	 * hooks. The 4.3 `can_submit()` gate remains RESERVED for its own story.
	 *
	 * Story 4.2 wires the {@see PurchaseActivation} collaborator (the one-
	 * collaborator-per-concern house style, mirroring `Accounts\Module →
	 * {Registration, Onboarding, Approval}`): it registers the WooCommerce
	 * Memberships activation listener + the activation email template. Its
	 * behaviour is self-gated (the `new_status === active` check + the send toggle),
	 * so wiring it unconditionally is safe.
	 */
	public function register(): void {
		add_action( 'init', array( $this, 'registerSettings' ) );

		( new PurchaseActivation() )->register();
	}

	/**
	 * Register the `ink_membership_plan_products` option as a sanitised setting.
	 *
	 * The persisted store for the product mapping (FIX-D): a `term-months =>
	 * product_id` array, fail-safe empty. The `sanitize_callback` coerces any
	 * payload through {@see sanitizeProductMap()} so a non-array / non-scalar write
	 * can never reach the lookup (the Epic-2 "non-scalar to coercion" bug class).
	 * Mirrors {@see \Ink\Accounts\Approval::registerMeta()}' single-source-const +
	 * registered-default + guarded-sanitiser house style.
	 */
	public function registerSettings(): void {
		register_setting(
			'ink_entitlement',
			MembershipPlans::OPTION_PRODUCTS,
			array(
				'type'              => 'array',
				'default'           => array(),
				'sanitize_callback' => array( self::class, 'sanitizeProductMap' ),
				'show_in_rest'      => false,
			)
		);
	}

	/**
	 * Sanitise the product-mapping option to an `int term-months => int product_id` array.
	 *
	 * Fail-safe: a non-array payload collapses to an empty map; each row is kept
	 * only when both the key and the value coerce to a positive int (a term length
	 * and a product id are both positive ints). No raw value reaches persistence.
	 *
	 * @param mixed $value The submitted option value.
	 * @return array<int, int> The sanitised map.
	 */
	public static function sanitizeProductMap( mixed $value ): array {
		if ( ! is_array( $value ) ) {
			return array();
		}

		$clean = array();

		foreach ( $value as $key => $product_id ) {
			// Array keys are inherently int|string; only the value needs the guard
			// (the Epic-2 "non-scalar to coercion" bug class).
			if ( ! is_scalar( $product_id ) ) {
				continue;
			}

			$term_months = (int) trim( (string) $key );
			$product_id  = (int) $product_id;

			if ( $term_months > 0 && $product_id > 0 ) {
				$clean[ $term_months ] = $product_id;
			}
		}

		return $clean;
	}
}

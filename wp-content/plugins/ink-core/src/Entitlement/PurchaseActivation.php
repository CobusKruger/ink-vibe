<?php
/**
 * Front-end PayFast purchase / self-activation seam (Story 4.2, FR-5/UJ-2).
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Entitlement;

use Ink\Notifications\Api as Notifications;
use Ink\Notifications\Template;

defined( 'ABSPATH' ) || exit;

/**
 * The `ink-core` business SEAM around the OFF-SITE PayFast flow (Story 4.2, FR-5).
 *
 * WooCommerce + WooCommerce Memberships + the WooCommerce PayFast Gateway are the
 * platform plugins that OWN the cart / checkout, the off-site card capture + the
 * ITN (Instant Transaction Notification) callback, and the act of GRANTING /
 * ACTIVATING a membership (project-context.md: "do not reimplement"). This class is
 * NOT any of that — it is the thin INK seam that:
 *
 *  1. INITIATES a purchase of a Story-4.1 plan by HANDING OFF to the WooCommerce
 *     checkout / WC PayFast gateway ({@see purchaseUrl()}) — it builds no card form,
 *     resolves the WC product via the 4.1 registry ({@see MembershipPlans}), and
 *     references no PayFast credential; the off-site gateway does the rest.
 *  2. REACTS to the WooCommerce Memberships activation transition
 *     ({@see HOOK_STATUS_CHANGED}, gated on {@see STATUS_ACTIVE}) to SELF-ACTIVATE
 *     the lidmaatskap with NO manual EFT / admin step (replacing UJ-2's manual
 *     activation) and fire the thank-you/activation email trigger via the
 *     Notifications API ({@see onMembershipStatusChanged()}). `ink-core` never flips
 *     the membership status itself — WC Memberships owns the status; this only
 *     reacts to it (AD-3/AD-9 addendum: "the Entitlement module gains a notify
 *     responsibility … it emits the lifecycle events Notifications consumes").
 *
 * PCI scope stays LOW (project-context.md Security): this class stores NO card / PAN
 * / CVV / cardholder data anywhere (no option, no meta, no log), captures no card
 * fields, and hardcodes no PayFast merchant id / key / passphrase — any config rides
 * `.env` / a WP option (AD-4) and is read by the WC PayFast gateway, not here. Tests
 * use the PayFast SANDBOX only; this class targets no gateway endpoint literal.
 *
 * No recurring / auto-renew (one-off fixed-term purchase only — Stories 4.9–4.11 are
 * post-launch); the 4.1 registry already models no recurring concept.
 *
 * THE conflation rule (AD-1, FR-13): buying / activating a lidmaatskap is strictly
 * independent of writer Gradering — this class carries ZERO reference to `Ink\Tiers`
 * and never writes `ink_writer_tier`. A paid Brons writer stays Brons; activation
 * grants entitlement, never a tier.
 *
 * Scope (Story 4.2): the purchase-initiation seam + the activation listener + the
 * activation email TRIGGER. NOT built here: the gateway / checkout / ITN
 * (platform-owned); the `can_submit()` entitlement gate (Story 4.3, AD-2 — still
 * reserved); the Lidmaatskap page / renewal UI (4.4/4.5 — theme); store-UI
 * suppression (4.6); the Afrikaans status-messaging surface (4.7); the REAL
 * activation/lifecycle email COPY + the expiry-warning emails + per-term toggles
 * (Story 4.8 — 4.2 wires only the activation trigger with a placeholder, toggle OFF).
 *
 * Non-`final` for a deliberate testability seam: the WC-availability check is a
 * `protected isWooCommerceAvailable()` method, so the "WooCommerce inactive" branch
 * is deterministically unit-testable via a test subclass (Brain Monkey-defined
 * function symbols persist within a PHP process, so an inline `function_exists`
 * guard cannot be simulated as "absent" once a sibling test has defined the symbol).
 * This mirrors the 4.1 {@see MembershipPlans} precedent.
 *
 * @package Ink\Core
 */
class PurchaseActivation {

	/**
	 * The WooCommerce Memberships status-transition action this seam reacts to.
	 *
	 * Fires as `( WC_Memberships_User_Membership $user_membership, string
	 * $old_status, string $new_status )` on every membership status transition,
	 * including INTO `active` — for the PayFast-return grant AND any admin/manual
	 * grant. Preferred over `woocommerce_payment_complete` /
	 * `woocommerce_order_status_completed` (which fire for ANY order, not just a
	 * membership grant, and would force an order→membership lookup) and over the
	 * bare `wc_memberships_user_membership_saved` (which fires on every save, not
	 * just an activation). Single source so the hook name is never an inline literal.
	 */
	public const HOOK_STATUS_CHANGED = 'wc_memberships_user_membership_status_changed';

	/**
	 * The WooCommerce Memberships "active" status value (the platform's own string).
	 *
	 * The handler gates on a transition INTO this status, so it is idempotent: a
	 * no-op save or a transition to any other status does nothing (no double-send).
	 */
	public const STATUS_ACTIVE = 'active';

	/**
	 * The Notifications template/event key for the thank-you / activation email.
	 *
	 * Story 4.2 registers this Afrikaans-source template with its send toggle OFF
	 * and a `[WAG OP MENSLIKE KOPIE]` placeholder body, and fires its TRIGGER on
	 * activation. Story 4.8 owns the curated copy + turning the toggle on. The
	 * `_email` suffix keeps the key in the Notifications keyspace.
	 */
	public const ACTIVATED_TEMPLATE_KEY = 'ink_membership_activated_email';

	/**
	 * Register this seam's hooks. Invoked once from {@see Module::register()}
	 * (dispatched by the Kernel on `init`).
	 *
	 * Wires the WC Memberships activation listener and registers the Afrikaans-
	 * source activation email template (toggle OFF). Mirrors {@see \Ink\Accounts\Approval::register()}:
	 * the hook is wired unconditionally; the behaviour is gated by the
	 * `new_status === active` check and the send toggle.
	 */
	public function register(): void {
		add_action( self::HOOK_STATUS_CHANGED, array( $this, 'onMembershipStatusChanged' ), 10, 3 );

		$this->registerEmailTemplate();
	}

	/**
	 * React to a WooCommerce Memberships status transition — SELF-ACTIVATE on `→ active`.
	 *
	 * The reaction (AC-1/AC-2): when (and only when) a membership transitions INTO
	 * `active`, confirm the betaalde lid (WC Memberships owns the status record — we
	 * do not write it) and fire the thank-you/activation email trigger ONCE. Any
	 * non-active or no-op transition is a no-op (idempotent — no double-send).
	 *
	 * Graceful, fail-safe, PCI-clean: it reads ONLY the membership owner's id (off
	 * the WC object, behind a `method_exists()` guard) and never any card / gateway
	 * field; a malformed object or a missing user is a silent no-op (never a fatal),
	 * and it persists NOTHING (no card data, no meta, no option, no log).
	 *
	 * THE conflation rule: writes/reads NO `ink_writer_tier`, no promotion log — zero
	 * `Ink\Tiers` coupling.
	 *
	 * PER-ACTIVATION (incl. RENEWAL / REACTIVATION), STATELESS — by design. The gate
	 * is "a GENUINE transition INTO active" (`new === active AND old !== active`), so
	 * it fires on the FIRST activation AND on every later re-entry into active — a
	 * renewal (`expired → active`) and a reactivation (`cancelled → active`) each
	 * re-fire the thank-you email. This is INTENDED and aligns with Story 4.8's AC
	 * ("a thank-you email is sent on EVERY activation"). It is deliberately STATELESS:
	 * there is NO persisted "already-thanked" dedupe marker — adding one would break
	 * the per-activation semantics. The ONLY thing the gate suppresses is a no-op
	 * `active → active` save (not a real activation), so one genuine activation = one
	 * send attempt, with no double-send on an idempotent re-save.
	 *
	 * @param mixed  $user_membership The WC_Memberships_User_Membership (platform object).
	 * @param string $old_status      The previous status (unused — we gate on the new one).
	 * @param string $new_status      The new status; we react only to `active`.
	 */
	public function onMembershipStatusChanged( mixed $user_membership, string $old_status, string $new_status ): void {
		// Fire only on a genuine transition INTO active: the new status must be
		// active AND the old status must NOT already be active. This makes the
		// handler idempotent — a no-op save (active → active) or any non-active
		// transition does nothing, so one activation = one email send attempt.
		if ( self::STATUS_ACTIVE !== $new_status || self::STATUS_ACTIVE === $old_status ) {
			return;
		}

		$user_id = $this->membershipOwnerId( $user_membership );

		if ( $user_id <= 0 ) {
			return; // Malformed membership object ⇒ graceful no-op.
		}

		$this->sendActivationEmail( $user_id, $user_membership );
	}

	/**
	 * Build the WooCommerce checkout URL that starts the off-site PayFast purchase of
	 * a Story-4.1 plan — or null when it cannot be offered.
	 *
	 * The hand-off (AC-1/AC-3): this resolves the term's mapped WooCommerce product
	 * via the 4.1 registry ({@see MembershipPlans::productIdFor()}) and returns the WC
	 * add-to-cart → checkout URL, where the WC PayFast gateway does the off-site card
	 * capture. It builds NO card form, re-defines NO plan or price, and references NO
	 * PayFast credential or endpoint — the gateway (configured from `.env`, AD-4)
	 * owns the sandbox/live switch and the redirect.
	 *
	 * A plain URL is returned (no state change here), keeping the attack surface
	 * minimal — the cart/checkout WRITE is WooCommerce's own nonce-protected flow.
	 * Returns null when WooCommerce is absent or the plan is not sellable
	 * ({@see Api::isAvailable()}) — graceful, never an invented endpoint.
	 *
	 * @param LidmaatskapTerm $term The fixed term whose plan to purchase.
	 * @return string|null The WooCommerce checkout URL, or null when unavailable.
	 */
	public function purchaseUrl( LidmaatskapTerm $term ): ?string {
		if ( ! $this->isWooCommerceAvailable() ) {
			return null;
		}

		// Reuse the 4.1 registry — never re-define a plan. Offer only a sellable slot.
		$plans = new MembershipPlans();

		if ( ! $plans->isAvailable( $term ) ) {
			return null;
		}

		$product_id = $plans->productIdFor( $term );

		if ( null === $product_id ) {
			return null;
		}

		$checkout_url = wc_get_checkout_url();

		if ( ! is_string( $checkout_url ) || '' === $checkout_url ) {
			return null;
		}

		// Hand off to the WC checkout with the mapped product as an add-to-cart arg;
		// the WC PayFast gateway takes over off-site from there.
		return (string) add_query_arg( 'add-to-cart', $product_id, $checkout_url );
	}

	/**
	 * Register the Afrikaans-source thank-you / activation email template (toggle OFF).
	 *
	 * Mirrors {@see \Ink\Accounts\Registration::registerWelcomeTemplate()}: the
	 * Afrikaans-source subject/body are LITERAL `__( …, 'ink-core' )` strings (so
	 * `wp i18n make-pot` extracts them; no English `.mo` ships), the body carries the
	 * `{skrywer}` greeting token, and the per-event send toggle is OFF by default.
	 *
	 * GATE — human Afrikaans copy: Story 4.8 owns the curated thank-you/activation
	 * copy and turning the toggle on. Until then the body is a short, clearly-marked
	 * `[WAG OP MENSLIKE KOPIE]` placeholder built only from approved glossary terms
	 * (`[NEEDS HUMAN AFRIKAANS]` in `ui-copy-translations.md`) — never AI-translated
	 * prose, so NO `wp_mail` fires today.
	 */
	public function registerEmailTemplate(): void {
		Notifications::registerTemplate(
			new Template(
				self::ACTIVATED_TEMPLATE_KEY,
				// Subject — glossary-only placeholder, sentence case.
				__( 'Jou lidmaatskap is aktief [WAG OP MENSLIKE KOPIE]', 'ink-core' ),
				// Body — PLACEHOLDER. [NEEDS HUMAN AFRIKAANS] — toggle stays OFF until
				// Story 4.8's curated copy lands; glossary terms only, no invented prose.
				__( 'Hallo {skrywer}, jou lidmaatskap is nou aktief. [WAG OP MENSLIKE KOPIE]', 'ink-core' ),
				// Send toggle OFF by default — no wp_mail until human copy is approved.
				false
			)
		);
	}

	/**
	 * Resolve the membership owner's id off the WC Memberships object (PCI-clean).
	 *
	 * Reads ONLY `get_user_id()` (behind a `method_exists()` guard so a malformed
	 * object is a graceful no-op) — never any card / gateway field. Returns 0 when
	 * the object is malformed.
	 *
	 * @param mixed $user_membership The WC_Memberships_User_Membership object.
	 * @return int The owner's user id, or 0.
	 */
	private function membershipOwnerId( mixed $user_membership ): int {
		if ( ! is_object( $user_membership ) || ! method_exists( $user_membership, 'get_user_id' ) ) {
			return 0;
		}

		return (int) $user_membership->get_user_id();
	}

	/**
	 * Fire the thank-you / activation email trigger ONCE for the activated lid.
	 *
	 * Resolves the recipient + greeting name from the membership owner
	 * (`get_userdata()`, falling back to `user_login` when `display_name` is empty —
	 * the {@see \Ink\Accounts\Approval::sendDecisionEmail()} precedent), then calls
	 * {@see Notifications::send()} once. A missing user / empty email is a graceful
	 * no-op.
	 *
	 * PER-TERM TOGGLE (Story 4.8, AC-2/AC-4): the activation thank-you is toggleable
	 * PER TERM (1/6/12). When the activated membership's term resolves, the send is
	 * gated on the per-term toggle key ({@see LifecycleEmails::toggleKeyFor()} over the
	 * shared {@see ACTIVATED_TEMPLATE_KEY}) — fail-safe OFF, so an unconfigured term
	 * does not send. When the term cannot be resolved (malformed membership / WC absent
	 * / non-INK membership), it FALLS BACK to the base template toggle (the
	 * {@see Notifications::send()} gate on {@see ACTIVATED_TEMPLATE_KEY}) as the
	 * authority — so the existing 4.2 behaviour (base-toggle-gated, OFF until copy
	 * lands) is preserved when no per-term toggle is in play. Either way there is
	 * exactly ONE activation template; only the toggle gating is per term.
	 *
	 * @param int   $user_id         The activated lid.
	 * @param mixed $user_membership The WC membership (to resolve the term for the toggle).
	 */
	private function sendActivationEmail( int $user_id, mixed $user_membership = null ): void {
		$user = get_userdata( $user_id );

		// Accept ONLY a genuine WP_User (the Approval::sendDecisionEmail() house
		// pattern) — a filtered / unexpected non-WP_User return from get_userdata()
		// is a graceful no-op, never a dispatch with an unresolved recipient.
		if ( ! ( $user instanceof \WP_User ) || '' === (string) $user->user_email ) {
			return;
		}

		// Per-term toggle gate: when the term resolves, the (thank-you, term) pair must
		// be ON (fail-safe OFF). When it does not resolve, fall through to the base
		// template toggle (the Notifications::send() gate) as the sole authority.
		$term = $this->resolveTerm( $user_membership );
		if ( $term instanceof LidmaatskapTerm && ! $this->isActivationEnabledForTerm( $term ) ) {
			return;
		}

		$skrywer = (string) $user->display_name;
		if ( '' === $skrywer ) {
			$skrywer = (string) $user->user_login;
		}

		Notifications::send(
			self::ACTIVATED_TEMPLATE_KEY,
			(string) $user->user_email,
			array( 'skrywer' => $skrywer )
		);
	}

	/**
	 * Whether the activation thank-you is enabled for a term — the per-term toggle.
	 *
	 * Reads the per-term toggle key ({@see LifecycleEmails::toggleKeyFor()} over
	 * {@see ACTIVATED_TEMPLATE_KEY}) through the 1.12 Notifications options row, so an
	 * unset (thank-you, term) pair is fail-safe OFF. The SAME derivation the warnings
	 * use ({@see LifecycleEmails::isEnabledForTerm()}) — one source for the matrix.
	 *
	 * @param LidmaatskapTerm $term The activated membership's term.
	 * @return bool True when the (thank-you, term) pair is explicitly ON.
	 */
	private function isActivationEnabledForTerm( LidmaatskapTerm $term ): bool {
		$toggle_key = LifecycleEmails::toggleKeyFor( self::ACTIVATED_TEMPLATE_KEY, $term );

		$all = get_option( \Ink\Notifications\TemplateStore::OPTION, array() );

		if ( ! is_array( $all ) ) {
			return false;
		}

		$row = $all[ $toggle_key ] ?? array();

		if ( ! is_array( $row ) || ! array_key_exists( 'enabled', $row ) ) {
			return false; // Unconfigured (thank-you, term) pair ⇒ fail-safe OFF.
		}

		return (bool) $row['enabled'];
	}

	/**
	 * Resolve the activated membership's fixed term from its plan products (4.1 inverse).
	 *
	 * Mirrors {@see LifecycleEmails::resolveTerm()}: the term is the {@see LidmaatskapTerm}
	 * whose mapped Story-4.1 product id is among the membership's plan products. Returns
	 * null for a non-INK / malformed membership or when WooCommerce is absent — the
	 * activation send then falls back to the base template toggle.
	 *
	 * @param mixed $user_membership The WC Memberships user-membership object.
	 * @return LidmaatskapTerm|null The resolved fixed term, or null.
	 */
	private function resolveTerm( mixed $user_membership ): ?LidmaatskapTerm {
		if ( ! is_object( $user_membership ) || ! method_exists( $user_membership, 'get_plan' ) ) {
			return null;
		}

		$plan = $user_membership->get_plan();

		if ( ! is_object( $plan ) || ! method_exists( $plan, 'get_product_ids' ) ) {
			return null;
		}

		$plan_product_ids = array_map( 'intval', (array) $plan->get_product_ids() );
		$plans            = new MembershipPlans();

		foreach ( MembershipPlans::terms() as $term ) {
			$product_id = $plans->productIdFor( $term );

			if ( null !== $product_id && in_array( $product_id, $plan_product_ids, true ) ) {
				return $term;
			}
		}

		return null;
	}

	/**
	 * Whether WooCommerce's checkout API is available in this request.
	 *
	 * The "do not reimplement" boundary (project-context.md): when WooCommerce is
	 * inactive the purchase seam degrades gracefully (returns null) rather than
	 * fatalling on a missing `wc_get_checkout_url()`. A `protected` seam (not an
	 * inline `function_exists()`) so the unavailable branch is deterministically
	 * unit-testable — the 4.1 {@see MembershipPlans::isWooCommerceAvailable()}
	 * precedent (Brain Monkey-defined symbols persist within a process).
	 *
	 * @return bool True when `wc_get_checkout_url()` can be called.
	 */
	protected function isWooCommerceAvailable(): bool {
		return function_exists( 'wc_get_checkout_url' );
	}
}

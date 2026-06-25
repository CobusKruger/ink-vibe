<?php
/**
 * INK Foundation — theme bootstrap (presentation only).
 *
 * Per the architecture (FSE theme tree): this file registers PATTERNS / BLOCK
 * STYLES ONLY — no business logic. All INK business rules, content models,
 * tier/submission/follow logic, and data access live in the `ink-core` plugin
 * (Story 1.7), never in the theme.
 *
 * @package ink-foundation
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit; // No direct access.
}

/**
 * Load the `ink-foundation` text domain so the theme's own presentation strings
 * resolve (Story 1.10 — theme half of the i18n scaffolding).
 *
 * The theme's user-facing labels (pattern-category + block-style names below) are
 * authored in Afrikaans as the gettext SOURCE language, sentence case (Gate D),
 * in the `ink-foundation` domain. Like `ink-core`, the theme ships NO English
 * `.mo` — Afrikaans is the source, so gettext returns it directly. This call is
 * the documented theme i18n entry point; it makes the domain loadable for any
 * future Afrikaans/community artifact under `/languages`. Loading the
 * presentation layer's OWN text domain is presentation infrastructure — it adds
 * no business logic to the theme (three-layer separation holds).
 */
function ink_foundation_load_textdomain(): void {
	load_theme_textdomain( 'ink-foundation', get_template_directory() . '/languages' );
}
add_action( 'init', 'ink_foundation_load_textdomain' );

/**
 * Register the INK building-block inserter category so the theme patterns group
 * together in the Site Editor inserter. Label is Afrikaans, sentence case (Gate D).
 */
function ink_foundation_register_pattern_categories(): void {
	register_block_pattern_category(
		'ink-foundation',
		array(
			'label'       => __( 'INK-boublokke', 'ink-foundation' ),
			'description' => __( 'Kern-boublokke vir die samestelling van bladsye.', 'ink-foundation' ),
		)
	);
}
add_action( 'init', 'ink_foundation_register_pattern_categories' );

/**
 * Register the core block style variations (card / button / emphasis).
 *
 * These are token-driven presentation treatments applied to any block instance
 * (the composition patterns consume them). All CSS values resolve to theme.json
 * tokens via the generated `--wp--preset--*` / `--wp--custom--*` custom properties
 * — zero hardcoded colours, spacing, radius, or type sizes (Gate A). Labels are
 * Afrikaans, sentence case (Gate D).
 */
function ink_foundation_register_block_styles(): void {
	// Card: a bordered, soft-shadowed, rounded surface container.
	register_block_style(
		'core/group',
		array(
			'name'         => 'card',
			'label'        => __( 'Kaart', 'ink-foundation' ),
			'inline_style' => '.wp-block-group.is-style-card{'
				. 'background-color:var(--wp--preset--color--surface-alt);'
				. 'border:1px solid var(--wp--preset--color--border);'
				. 'border-radius:var(--wp--custom--radius--lg);'
				. 'box-shadow:var(--wp--preset--shadow--sm);'
				. 'padding:var(--wp--preset--spacing--s-24);'
				. '}',
		)
	);

	// Card (details): the same bordered, rounded, soft-shadowed surface as the group
	// card, registered for core/details so the FAQ accordions (Lidmaatskap-blad,
	// Story 4.4) actually pick up the treatment — the group registration's selector
	// (`.wp-block-group.is-style-card`) does NOT match a `core/details` block, so the
	// className was previously a no-op. Token-only (Gate A).
	register_block_style(
		'core/details',
		array(
			'name'         => 'card',
			'label'        => __( 'Kaart', 'ink-foundation' ),
			'inline_style' => '.wp-block-details.is-style-card{'
				. 'background-color:var(--wp--preset--color--surface-alt);'
				. 'border:1px solid var(--wp--preset--color--border);'
				. 'border-radius:var(--wp--custom--radius--lg);'
				. 'box-shadow:var(--wp--preset--shadow--sm);'
				. '}',
		)
	);

	// Pill: a fully-rounded button variant (distinct from core fill + is-style-outline).
	register_block_style(
		'core/button',
		array(
			'name'         => 'pill',
			'label'        => __( 'Pil', 'ink-foundation' ),
			'inline_style' => '.wp-block-button.is-style-pill .wp-block-button__link{'
				. 'border-radius:var(--wp--custom--radius--full);'
				. '}',
		)
	);

	// Emphasis: an accented call-out treatment (left rule + tinted background).
	register_block_style(
		'core/group',
		array(
			'name'         => 'emphasis',
			'label'        => __( 'Klem', 'ink-foundation' ),
			'inline_style' => '.wp-block-group.is-style-emphasis{'
				. 'background-color:var(--wp--preset--color--secondary);'
				. 'border-left:var(--wp--preset--spacing--s-4) solid var(--wp--preset--color--primary);'
				. 'border-radius:var(--wp--custom--radius--md);'
				. 'padding:var(--wp--preset--spacing--s-24);'
				. '}'
				. '.wp-block-group.is-style-emphasis :where(p){'
				. 'font-family:var(--wp--preset--font-family--body);'
				. '}',
		)
	);
}
add_action( 'init', 'ink_foundation_register_block_styles' );

if ( ! function_exists( 'ink_foundation_term' ) ) {
	/**
	 * Theme-side bridge to the `ink-core` terminology registry (AD-10, Story 2.0).
	 *
	 * PHP patterns (`patterns/*.php`) render glossary labels through this bridge so
	 * they read from the same single source as `ink-core` — without hardcoding the
	 * Afrikaans literal in the theme. It is `function_exists`-guarded so the theme
	 * never fatals when `ink-core` is inactive (it returns the provided fallback).
	 * This is presentation infrastructure — a label lookup with a graceful degrade,
	 * not business logic (three-layer separation holds).
	 *
	 * Static block-template HTML (`templates/*.html`) cannot call PHP — it binds to
	 * the `ink/term` Block Bindings source registered by `ink-core` instead.
	 *
	 * @param string $key      Glossary concept key (e.g. 'gradering').
	 * @param string $fallback Returned when `ink-core` is not active.
	 * @return string The Afrikaans label, or the fallback.
	 */
	function ink_foundation_term( string $key, string $fallback = '' ): string {
		if ( function_exists( 'Ink\\ink_term' ) ) {
			return \Ink\ink_term( $key );
		}

		return $fallback;
	}
}

if ( ! function_exists( 'ink_foundation_onboarding_complete' ) ) {
	/**
	 * Whether the current lid has completed/dismissed onboarding (Story 3.3).
	 *
	 * A thin presentation gate so the onboarding template can decide whether to
	 * show the flow — driving AC-1's one-time / no-re-nag behaviour without any
	 * business logic in the theme (it merely reads the `ink-core`-owned flag
	 * through the module's read surface). `class_exists`-guarded so the theme
	 * never fatals when `ink-core` is inactive — it then reports "not complete"
	 * (false), which is harmless presentation degradation.
	 *
	 * @return bool True once the lid has finished or skipped onboarding.
	 */
	function ink_foundation_onboarding_complete(): bool {
		if ( ! class_exists( 'Ink\\Accounts\\Onboarding' ) || ! function_exists( 'get_current_user_id' ) ) {
			return false;
		}

		return \Ink\Accounts\Onboarding::hasCompleted( get_current_user_id() );
	}
}

if ( ! function_exists( 'ink_foundation_onboarding_form_fields' ) ) {
	/**
	 * Echo the hidden form fields the onboarding skip/complete POST needs.
	 *
	 * Presentation glue only: the nonce field + the `admin-post` action hidden
	 * input, sourced from the `ink-core` {@see \Ink\Accounts\Onboarding} single
	 * source (never a duplicated literal). State-change discipline (nonce +
	 * own-record capability + sanitise) lives in the `ink-core` handler, not the
	 * theme. `class_exists`-guarded so the theme degrades to no fields (the form
	 * simply will not authorise) when `ink-core` is inactive — never fatals.
	 */
	function ink_foundation_onboarding_form_fields(): void {
		if ( ! class_exists( 'Ink\\Accounts\\Onboarding' ) ) {
			return;
		}

		wp_nonce_field(
			\Ink\Accounts\Onboarding::nonceAction(),
			\Ink\Accounts\Onboarding::nonceName()
		);

		printf(
			'<input type="hidden" name="action" value="%s" />',
			esc_attr( \Ink\Accounts\Onboarding::postAction() )
		);
	}
}

if ( ! function_exists( 'ink_foundation_membership_plans' ) ) {
	/**
	 * The lidmaatskap plan rows for the Lidmaatskap page pattern (Story 4.4, FR-7).
	 *
	 * Presentation glue only: a read-through to the `ink-core` Entitlement facade
	 * ({@see \Ink\Entitlement\Api::planRows()}) so the pricing-table pattern can
	 * surface DYNAMIC plan data — term label, the WooCommerce-resolved price, the
	 * sellability flag, and the WC/PayFast purchase URL — WITHOUT any plan business
	 * logic living in the theme (three-layer separation). The theme iterates the
	 * rows it is handed and renders them with token-only locked blocks; it computes
	 * nothing, hardcodes no price, and never re-queries WooCommerce. All plan
	 * shaping lives in `ink-core`'s `PlanPresenter` read-model.
	 *
	 * `class_exists`-guarded so the theme degrades gracefully to an empty list when
	 * `ink-core` is inactive — the pattern then renders its static labels without a
	 * live price/CTA, never a fatal.
	 *
	 * @return array<int, array{months:int, term_label:string, price:string|null, is_available:bool, purchase_url:string|null}>
	 */
	function ink_foundation_membership_plans(): array {
		if ( ! class_exists( 'Ink\\Entitlement\\Api' ) ) {
			return array();
		}

		return \Ink\Entitlement\Api::planRows();
	}
}

if ( ! function_exists( 'ink_foundation_renewal_plans' ) ) {
	/**
	 * The lidmaatskap renewal rows for the My Profiel renewal section (Story 4.5, FR-8).
	 *
	 * Presentation glue only: a read-through to the `ink-core` Entitlement facade
	 * ({@see \Ink\Entitlement\Api::renewalRows()}) so the renewal section pattern can
	 * surface DYNAMIC renewal data — term label, the WooCommerce-resolved price, the
	 * sellability flag, and the WC/PayFast purchase URL (the RENEW CTA target) — WITHOUT
	 * any plan business logic living in the theme (three-layer separation). The rows are
	 * the same 4.4 plan-row shape REUSED for renewal: "renew" at launch is the manual
	 * fixed-term purchase (renew = buy another fixed term via the 4.2 hand-off); there is
	 * no auto-renew/recurring affordance and no discount/savings field. The theme
	 * iterates the rows it is handed and renders token-only locked blocks; it computes
	 * nothing, hardcodes no price, and never re-queries WooCommerce.
	 *
	 * `class_exists`-guarded so the theme degrades gracefully to an empty list when
	 * `ink-core` is inactive — the section then renders its static term labels without a
	 * live price/CTA, never a fatal.
	 *
	 * @return array<int, array{months:int, term_label:string, price:string|null, price_display:string|null, is_available:bool, purchase_url:string|null}>
	 */
	function ink_foundation_renewal_plans(): array {
		if ( ! class_exists( 'Ink\\Entitlement\\Api' ) ) {
			return array();
		}

		return \Ink\Entitlement\Api::renewalRows();
	}
}

if ( ! function_exists( 'ink_foundation_is_member_logged_in' ) ) {
	/**
	 * Whether the current viewer is a logged-in lid (Story 4.5 renewal-section gate).
	 *
	 * A thin presentation gate so the renewal section (and its interim host) renders the
	 * renew options only for a logged-in lid, and a "Meld aan om te hernieu" fallback
	 * otherwise. This is NOT the submission-entitlement gate (that is Story 4.3/6.8,
	 * `Api::can_submit()`) — the renewal surface is open to any logged-in lid wishing to
	 * extend access; no entitlement logic lives in the theme. `function_exists`-guarded
	 * so the theme degrades to "logged out" (false) outside a WordPress runtime — never
	 * fatals.
	 *
	 * @return bool True when a logged-in lid is viewing.
	 */
	function ink_foundation_is_member_logged_in(): bool {
		if ( ! function_exists( 'is_user_logged_in' ) ) {
			return false;
		}

		return is_user_logged_in();
	}
}

if ( ! function_exists( 'ink_foundation_social_login_available' ) ) {
	/**
	 * Whether the social-login section should render on the auth surfaces (Story 3.5).
	 *
	 * Reads the `ink-core` R6 seam ({@see \Ink\Accounts\SocialLogin::isAvailable()})
	 * so the auth patterns can decide whether to paint the social section. R6 social
	 * login is a vetted-plugin seam — the theme carries NO OAuth, only this
	 * presentation gate. `class_exists`-guarded so the theme degrades to "no social
	 * section" (false) when `ink-core` is inactive — never fatals.
	 *
	 * @return bool True when a vetted social-login plugin is available.
	 */
	function ink_foundation_social_login_available(): bool {
		if ( ! class_exists( 'Ink\\Accounts\\SocialLogin' ) ) {
			return false;
		}

		return \Ink\Accounts\SocialLogin::isAvailable();
	}
}

if ( ! function_exists( 'ink_foundation_social_login_buttons' ) ) {
	/**
	 * Fire the render action the active social-login plugin hooks for its buttons.
	 *
	 * Presentation seam only: when a vetted plugin is available it paints its
	 * provider buttons by hooking {@see \Ink\Accounts\SocialLogin::BUTTONS_ACTION};
	 * when absent this emits nothing (graceful degradation — the e-mail auth path
	 * stays usable). The theme owns NO OAuth and reimplements no provider logic.
	 * `class_exists`-guarded so it never fatals when `ink-core` is inactive.
	 */
	function ink_foundation_social_login_buttons(): void {
		if ( ! class_exists( 'Ink\\Accounts\\SocialLogin' ) ) {
			return;
		}

		if ( ! \Ink\Accounts\SocialLogin::isAvailable() ) {
			return;
		}

		// The active vetted plugin hooks this action to paint its provider buttons.
		// Seam contract: a deploy-time integration must flip the availability filter
		// AND hook this action — with the filter true but nothing hooked (a
		// misconfiguration) the divider/consent chrome shows without buttons.
		do_action( \Ink\Accounts\SocialLogin::BUTTONS_ACTION );
	}
}

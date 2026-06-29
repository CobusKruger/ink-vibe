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
 * Enqueue the Skryf live-counter enhancement on the Skryf page only (Story 6.2).
 *
 * Progressive enhancement: the script gives live line/word feedback and swaps the
 * per-type body placeholder. The authoritative counting rules live in `ink-core`
 * ({@see \Ink\Submission\Counters}); this is only the client mirror. With JS off,
 * the form still submits — no business logic in the theme.
 */
function ink_foundation_enqueue_skryf_assets(): void {
	if ( ! function_exists( 'is_page' ) || ! is_page( 'skryf' ) ) {
		return;
	}

	$theme = wp_get_theme();

	wp_enqueue_script(
		'ink-foundation-skryf-counter',
		get_theme_file_uri( 'assets/js/skryf-counter.js' ),
		array(),
		(string) $theme->get( 'Version' ),
		true
	);
}
add_action( 'wp_enqueue_scripts', 'ink_foundation_enqueue_skryf_assets' );

/**
 * Enqueue the line-reactions client on a single gedig (Story 7.3, FR-26).
 *
 * The reading-surface reaction widget attaches to the `[data-ink-line]` anchors
 * the ink/gedig-body block renders and writes through the `ink/v1/reaksie` REST
 * endpoint. Business logic stays server-side; this only ships the thin client +
 * its config (REST root, nonce, post id, Afrikaans reaction labels). Loaded only
 * where the anchors exist.
 */
function ink_foundation_enqueue_line_reactions(): void {
	if ( ! function_exists( 'is_singular' ) || ! is_singular( 'gedig' ) ) {
		return;
	}

	$theme = wp_get_theme();

	wp_enqueue_script(
		'ink-foundation-line-reactions',
		get_theme_file_uri( 'assets/js/line-reactions.js' ),
		array(),
		(string) $theme->get( 'Version' ),
		true
	);

	wp_localize_script(
		'ink-foundation-line-reactions',
		'inkLineReactions',
		array(
			'restUrl'   => esc_url_raw( rest_url( 'ink/v1/reaksie' ) ),
			'nonce'     => wp_create_nonce( 'wp_rest' ),
			'postId'    => get_the_ID(),
			'reactions' => array(
				array(
					'key'   => 'hartjie',
					'label' => __( 'Hartjie', 'ink-foundation' ),
					'glyph' => '♥',
				),
				array(
					'key'   => 'duim_op',
					'label' => __( 'Duim op', 'ink-foundation' ),
					'glyph' => '👍',
				),
				array(
					'key'   => 'wow',
					'label' => __( 'Wow', 'ink-foundation' ),
					'glyph' => '✨',
				),
			),
		)
	);
}
add_action( 'wp_enqueue_scripts', 'ink_foundation_enqueue_line_reactions' );

/**
 * Enqueue the Gemeenskapsreaksie form client on a single work (Story 7.4, FR-27).
 *
 * The ink/gemeenskapsreaksies block renders the typed response form server-side;
 * this thin client posts it through the `ink/v1/gemeenskapsreaksie` REST endpoint
 * (the only feedback path). Loaded on the reading surfaces (gedig/storie/artikel).
 */
function ink_foundation_enqueue_gemeenskapsreaksie(): void {
	if ( ! function_exists( 'is_singular' ) || ! is_singular( array( 'gedig', 'storie', 'artikel' ) ) ) {
		return;
	}

	$theme = wp_get_theme();

	wp_enqueue_script(
		'ink-foundation-gemeenskapsreaksie',
		get_theme_file_uri( 'assets/js/gemeenskapsreaksie.js' ),
		array(),
		(string) $theme->get( 'Version' ),
		true
	);

	wp_localize_script(
		'ink-foundation-gemeenskapsreaksie',
		'inkGemeenskapsreaksie',
		array(
			'restUrl' => esc_url_raw( rest_url( 'ink/v1/gemeenskapsreaksie' ) ),
			'nonce'   => wp_create_nonce( 'wp_rest' ),
		)
	);
}
add_action( 'wp_enqueue_scripts', 'ink_foundation_enqueue_gemeenskapsreaksie' );

/**
 * Enqueue the leeslys save-toggle client on a single work (Story 7.7, FR-29).
 *
 * The ink/leeslys-knoppie block server-renders the toggle in its saved state;
 * this thin client flips it through the `ink/v1/leeslys` REST endpoint and shows
 * the human-authored confirmation toast. The two toast strings are authored
 * Afrikaans (ui-copy-translations.md 155/156), localised verbatim.
 */
function ink_foundation_enqueue_leeslys(): void {
	if ( ! function_exists( 'is_singular' ) || ! is_singular( array( 'gedig', 'storie', 'artikel' ) ) ) {
		return;
	}

	$theme = wp_get_theme();

	wp_enqueue_script(
		'ink-foundation-leeslys',
		get_theme_file_uri( 'assets/js/leeslys.js' ),
		array(),
		(string) $theme->get( 'Version' ),
		true
	);

	wp_localize_script(
		'ink-foundation-leeslys',
		'inkLeeslys',
		array(
			'restUrl'     => esc_url_raw( rest_url( 'ink/v1/leeslys' ) ),
			'nonce'       => wp_create_nonce( 'wp_rest' ),
			'savedText'   => __( 'Gestoor na jou leeslys', 'ink-foundation' ),
			'removedText' => __( 'Verwyder van jou leeslys', 'ink-foundation' ),
		)
	);
}
add_action( 'wp_enqueue_scripts', 'ink_foundation_enqueue_leeslys' );

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

if ( ! function_exists( 'ink_foundation_skryf_model' ) ) {
	/**
	 * The Skryf submission-form view-model for the Skryf page pattern (Story 6.1).
	 *
	 * Presentation glue only: a read-through to the `ink-core` Submission facade
	 * ({@see \Ink\Submission\Api::formModel()}) so the Skryf pattern can render the
	 * DYNAMIC bits — the submittable bydrae types (with their Afrikaans nouns) and
	 * the form wiring (post action, field names) — WITHOUT any submission logic in
	 * the theme (three-layer separation). `class_exists`-guarded so the theme
	 * degrades to an empty model when `ink-core` is inactive — never a fatal.
	 *
	 * @return array<string, mixed> The form view-model, or an empty array.
	 */
	function ink_foundation_skryf_model(): array {
		if ( ! class_exists( 'Ink\\Submission\\Api' ) ) {
			return array();
		}

		return \Ink\Submission\Api::formModel();
	}
}

if ( ! function_exists( 'ink_foundation_skryf_form_fields' ) ) {
	/**
	 * Echo the hidden form fields the Skryf submission POST needs.
	 *
	 * Presentation glue only: the nonce field + the `admin-post` action hidden
	 * input, sourced from the `ink-core` {@see \Ink\Submission\SubmissionForm}
	 * single source. State-change discipline (nonce + logged-in + sanitise) lives
	 * in the `ink-core` handler, not the theme. `class_exists`-guarded so the theme
	 * degrades to no fields (the form simply will not authorise) when `ink-core` is
	 * inactive — never fatals.
	 */
	function ink_foundation_skryf_form_fields(): void {
		if ( ! class_exists( 'Ink\\Submission\\SubmissionForm' ) ) {
			return;
		}

		wp_nonce_field(
			\Ink\Submission\SubmissionForm::nonceAction(),
			\Ink\Submission\SubmissionForm::nonceName()
		);

		printf(
			'<input type="hidden" name="action" value="%s" />',
			esc_attr( \Ink\Submission\SubmissionForm::postAction() )
		);
	}
}

if ( ! function_exists( 'ink_foundation_skryf_success' ) ) {
	/**
	 * The success-screen view-model for a freshly published bydrae (Story 6.7).
	 *
	 * Presentation glue: a read-through to {@see \Ink\Submission\Api::successModel()}
	 * so the Skryf pattern can render "Jou [gedig/storie/artikel] is gepubliseer"
	 * after a plaas, without any logic in the theme. `class_exists`-guarded; returns
	 * an empty array when `ink-core` is inactive or the id is not a published bydrae
	 * (the pattern then just shows the form) — never a fatal.
	 *
	 * @param int $post_id The published bydrae id (from the post-plaas redirect).
	 * @return array<string, mixed> The success model, or an empty array.
	 */
	function ink_foundation_skryf_success( int $post_id ): array {
		if ( ! class_exists( 'Ink\\Submission\\Api' ) ) {
			return array();
		}

		$model = \Ink\Submission\Api::successModel( $post_id );

		return is_array( $model ) ? $model : array();
	}
}

if ( ! function_exists( 'ink_foundation_skryf_denial' ) ) {
	/**
	 * The Afrikaans publish-denial message for the Skryf gate (Story 6.8).
	 *
	 * Presentation glue: a read-through to {@see \Ink\Submission\Api::denialMessage()}
	 * (the Entitlement 4.7 access-denied copy) so the pattern can show the denial
	 * after a non-entitled plaas, without any gate logic in the theme.
	 * `class_exists`-guarded; empty string when `ink-core` is inactive.
	 *
	 * @return string The denial message, or ''.
	 */
	function ink_foundation_skryf_denial(): string {
		if ( ! class_exists( 'Ink\\Submission\\Api' ) ) {
			return '';
		}

		return \Ink\Submission\Api::denialMessage();
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

if ( ! function_exists( 'ink_foundation_gradering_badge' ) ) {
	/**
	 * The accessible writer-Gradering badge for the profile templates (Story 5.4, FR-14).
	 *
	 * Presentation glue only: reads the typed display view from the `ink-core`
	 * Tiers facade ({@see \Ink\Tiers\Api::gradingView()}) and renders a token-only
	 * badge. The grade LABEL is always rendered as text (a11y — never colour-only);
	 * the leading mark is decorative (`aria-hidden`). Meester carries the
	 * `ink-gradering--meester` modifier, which the theme maps to the brand
	 * `primary` (#EA4015) token (NOT `danger`). Story 9.4 embeds this on the public
	 * Skrywerprofiel + private My Profiel.
	 *
	 * `class_exists`-guarded so the theme degrades to an empty string when
	 * `ink-core` is inactive — never a fatal. Computes nothing; all grade logic
	 * lives in `ink-core` (three-layer separation).
	 *
	 * @param int $user_id The writer (0 → current user).
	 * @return string The badge HTML, or '' when ink-core is inactive.
	 */
	function ink_foundation_gradering_badge( int $user_id = 0 ): string {
		if ( ! class_exists( 'Ink\\Tiers\\Api' ) ) {
			return '';
		}

		$user_id = $user_id > 0 ? $user_id : get_current_user_id();
		$view    = \Ink\Tiers\Api::gradingView( $user_id );

		return sprintf(
			'<span class="ink-gradering ink-gradering--%1$s"><span class="ink-gradering__mark" aria-hidden="true">&#9733;</span><span class="ink-gradering__label">%2$s</span></span>',
			esc_attr( $view->cssModifier() ),
			esc_html( $view->label )
		);
	}
}

if ( ! function_exists( 'ink_foundation_wenner_banier' ) ) {
	/**
	 * The accessible winner banner for a placed work (Story 12A.6, C9).
	 *
	 * Presentation glue only: reads the placement + per-tier banner markup from the
	 * `ink-core` Challenges presenter ({@see \Ink\Challenges\WinnerBanner::forPost()}),
	 * which carries the algehele-wenner/wenner variant + the `ink-gradering--{tier}`
	 * colour convention (Meester → `primary` #EA4015) and pairs colour with a real text
	 * label (a11y — never colour-only). A non-placed work yields ''.
	 *
	 * `class_exists`-guarded so the theme degrades to '' when `ink-core` is inactive —
	 * never a fatal. All placement logic lives in `ink-core` (three-layer separation).
	 *
	 * @param int $post_id The entry (bydrae) post id (0 → current post).
	 * @return string The banner HTML, or '' when ink-core is inactive / the work didn't place.
	 */
	function ink_foundation_wenner_banier( int $post_id = 0 ): string {
		if ( ! class_exists( 'Ink\\Challenges\\WinnerBanner' ) ) {
			return '';
		}

		$post_id = $post_id > 0 ? $post_id : (int) get_the_ID();

		return \Ink\Challenges\WinnerBanner::forPost( $post_id );
	}
}

if ( ! function_exists( 'ink_foundation_gradering_wins_needed' ) ) {
	/**
	 * The private-My-Profiel "wins needed" subtext (Story 5.9, FR-14 / R3).
	 *
	 * Presentation glue only: reads the composed Afrikaans subtext from the
	 * `ink-core` Tiers facade ({@see \Ink\Tiers\Api::winsNeededSubtext()}) — e.g.
	 * "4 top 3 uitslae nodig om Silwer te bereik". Returns '' for a Goud/Meester
	 * writer (no next grade — hidden) or when ink-core is inactive. The threshold
	 * math + the `_n()` copy live in `ink-core`; the theme computes nothing. Story
	 * 9.4 places this near the 5.4 badge on the private My Profiel.
	 *
	 * @param int $user_id The writer (0 → current user).
	 * @return string The subtext (escaped), or '' when hidden/inactive.
	 */
	function ink_foundation_gradering_wins_needed( int $user_id = 0 ): string {
		if ( ! class_exists( 'Ink\\Tiers\\Api' ) ) {
			return '';
		}

		$user_id = $user_id > 0 ? $user_id : get_current_user_id();
		$subtext = \Ink\Tiers\Api::winsNeededSubtext( $user_id );

		return null === $subtext ? '' : esc_html( $subtext );
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

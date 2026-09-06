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
 * The QA/component-gallery page slug (Phase 2, Theme Visual-Fidelity rework —
 * `docs/theme-fidelity-rework-plan.md`, workstream B).
 *
 * A single source for the page-template auto-match (`templates/page-{slug}.html`),
 * the fixture-data filter gate ({@see ink_foundation_is_qa_gallery()}) and the
 * home-asset enqueue condition below — every place that needs to know "is this
 * the QA gallery page" reads this one constant. See `patterns/qa-bloks.php` for
 * the full extensibility pattern future fidelity-pass agents should follow.
 */
if ( ! defined( 'INK_FOUNDATION_QA_GALLERY_SLUG' ) ) {
	define( 'INK_FOUNDATION_QA_GALLERY_SLUG', 'qa-bloks' );
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
 * Enqueue the Skryf live-counter enhancement + stylesheet on the Skryf page only
 * (Story 6.2; stylesheet added in the Theme Visual-Fidelity Phase 2 pass, page 8).
 *
 * Progressive enhancement: the script gives live line/word feedback and swaps the
 * per-type body placeholder. The authoritative counting rules live in `ink-core`
 * ({@see \Ink\Submission\Counters}); this is only the client mirror. With JS off,
 * the form still submits — no business logic in the theme.
 *
 * `skryf.css` carries the form's own presentation (type-selector cards, field
 * styling, challenge checkboxes, actions) — before this the page had NO CSS
 * anywhere and rendered as raw unstyled browser form chrome, mirroring the same
 * gap already found and fixed on opleiding/biblioteek/uitdagings-list.
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

	wp_enqueue_style(
		'ink-foundation-skryf',
		get_theme_file_uri( 'assets/css/skryf.css' ),
		array(),
		(string) $theme->get( 'Version' )
	);
}
add_action( 'wp_enqueue_scripts', 'ink_foundation_enqueue_skryf_assets' );

/**
 * Enqueue the Ontdek tab-toggle enhancement on the Ontdek page only
 * (Theme-Fidelity re-audit, page 11 — never previously started).
 *
 * Progressive enhancement over the `#bydraes`/`#skrywers` anchor-jump nav: both
 * panels are server-rendered up-front (AD-7, no REST for discovery), so with
 * this script disabled the two sections simply stack. See `ontdek-tabs.js`'s own
 * docblock. The bulk of this page's fidelity fix (card/tab/pill/search styling)
 * lives in `theme.json`'s global `styles.css` (the established `.ink-ontdek-*`
 * convention, not a dedicated stylesheet — this page never had one before).
 */
function ink_foundation_enqueue_ontdek_assets(): void {
	if ( ! function_exists( 'is_page' ) || ! is_page( 'ontdek' ) ) {
		return;
	}

	$theme = wp_get_theme();

	wp_enqueue_script(
		'ink-foundation-ontdek-tabs',
		get_theme_file_uri( 'assets/js/ontdek-tabs.js' ),
		array(),
		(string) $theme->get( 'Version' ),
		true
	);
}
add_action( 'wp_enqueue_scripts', 'ink_foundation_enqueue_ontdek_assets' );

/**
 * Enqueue the Kontak contact-form stylesheet on the Kontak page only
 * (Theme-Fidelity re-audit, page 15 — never previously started).
 *
 * `kontak.css` carries the form's own presentation (field/label/textarea/notice/
 * submit-button styling) — before this the `ink/kontak-vorm` block (Story 15.4)
 * had NO CSS anywhere and rendered as raw unstyled browser form chrome
 * (inputs computed `font-family: Arial`), mirroring the same gap already found
 * and fixed on skryf/lees-storie/my-profiel.
 */
function ink_foundation_enqueue_kontak_assets(): void {
	if ( ! function_exists( 'is_page' ) || ! is_page( 'kontak' ) ) {
		return;
	}

	$theme = wp_get_theme();

	wp_enqueue_style(
		'ink-foundation-kontak',
		get_theme_file_uri( 'assets/css/kontak.css' ),
		array(),
		(string) $theme->get( 'Version' )
	);
}
add_action( 'wp_enqueue_scripts', 'ink_foundation_enqueue_kontak_assets' );

/**
 * Enqueue the Auth (meld-aan / registreer / wagwoord-herstel) stylesheet on
 * those three pages only (Theme-Fidelity re-audit, page 16 — the last page).
 *
 * `auth.css` carries the three forms' shared presentation (field/label/hint/
 * remember-me/submit-button styling, the auth card's own padding/hover
 * overrides). Before this file `meld-aan` rendered the core `wp:loginout`
 * block's raw, unstyled `wp_login_form()` markup, and `registreer` /
 * `wagwoord-herstel` already carried `.ink-auth-*` classes with ZERO matching
 * CSS anywhere — the same "zero CSS" gap already found and fixed on kontak/
 * skryf. See `assets/css/auth.css`'s own docblock for the Lovable-reference
 * note (the live preview returned Internal Server Error site-wide this
 * session).
 */
function ink_foundation_enqueue_auth_assets(): void {
	if ( ! function_exists( 'is_page' ) || ! is_page( array( 'meld-aan', 'registreer', 'wagwoord-herstel' ) ) ) {
		return;
	}

	$theme = wp_get_theme();

	wp_enqueue_style(
		'ink-foundation-auth',
		get_theme_file_uri( 'assets/css/auth.css' ),
		array(),
		(string) $theme->get( 'Version' )
	);
}
add_action( 'wp_enqueue_scripts', 'ink_foundation_enqueue_auth_assets' );

/**
 * Enqueue the reading-page engagement stylesheet on a single gedig, storie OR
 * artikel (Theme-Fidelity re-audit, page 3 — lees-gedig — extended to
 * lees-storie in the third pass, and to lees-artikel in the fourth pass once
 * `reading-artikel.php` was rebuilt onto storie's exact shape, sticky
 * engagement bar included).
 *
 * `reading.css` carries the floating engagement bar's `position:sticky`
 * treatment (the sticky-bottom pill matching Lovable's `ReadStory.tsx` "Floating
 * Action Bar", a component shared unconditionally between poetry and prose) —
 * before this file there was NO dedicated reading-page stylesheet anywhere
 * (the `.ink-reaksie-bar` pill styling itself lives in theme.json's global
 * CSS, shared across all three reading patterns; only the sticky positioning
 * is scoped here). See `patterns/reading-gedig.php`/`patterns/reading-storie.php`/
 * `patterns/reading-artikel.php`'s own docblocks for why `.ink-reaksie-bar`
 * had to move to a top-level block for `sticky` to actually have room to float.
 */
function ink_foundation_enqueue_reading_engagement_assets(): void {
	if ( ! function_exists( 'is_singular' ) || ! is_singular( array( 'gedig', 'storie', 'artikel' ) ) ) {
		return;
	}

	$theme = wp_get_theme();

	wp_enqueue_style(
		'ink-foundation-reading-engagement',
		get_theme_file_uri( 'assets/css/reading.css' ),
		array(),
		(string) $theme->get( 'Version' )
	);
}
add_action( 'wp_enqueue_scripts', 'ink_foundation_enqueue_reading_engagement_assets' );

/**
 * Stamp `data-audit-id="gedig-title"`/`"storie-title"` onto the rendered
 * `core/post-title` block on a single gedig, storie OR artikel
 * (Theme-Fidelity re-audit, page 3, Tier-1 measurement anchor — extended to
 * storie in the third pass, and to artikel in the fourth once
 * `reading-artikel.php` was rebuilt onto storie's shape). `wp:post-title` is a
 * dynamic core block with no static markup in the reading patterns to
 * hand-edit (unlike the badge/hint pills, which are plain `wp:paragraph`
 * blocks and carry the attribute directly in the pattern file) — this is
 * core's own per-block-name render filter (`render_block_core/{name}`).
 *
 * Artikel deliberately gets the literal string `"storie-title"`, NOT a new
 * `"artikel-title"` value — mirrors `ReadStory.tsx`'s own
 * `data-audit-id={isPoetry ? "gedig-title" : "storie-title"}` ternary, which
 * has no third branch: Article and Short Story share the identical id in
 * Lovable's own source.
 */
function ink_foundation_audit_id_reading_title( string $block_content ): string {
	if ( ! function_exists( 'is_singular' ) ) {
		return $block_content;
	}

	if ( is_singular( 'gedig' ) ) {
		$audit_id = 'gedig-title';
	} elseif ( is_singular( array( 'storie', 'artikel' ) ) ) {
		$audit_id = 'storie-title';
	} else {
		return $block_content;
	}

	$with_audit_id = preg_replace( '/<h1\b/', '<h1 data-audit-id="' . $audit_id . '"', $block_content, 1 );

	return null !== $with_audit_id ? $with_audit_id : $block_content;
}
add_filter( 'render_block_core/post-title', 'ink_foundation_audit_id_reading_title' );

/**
 * Stamp `data-audit-id="storie-paragraph"` onto the FIRST rendered paragraph
 * of a storie/artikel's `core/post-content` output (Theme-Fidelity third pass,
 * lees-storie, Tier-1 measurement anchor — extended to artikel in the fourth
 * pass, same "reuse storie's literal id" rationale as the title filter above).
 * `wp:post-content` is a dynamic core block with no static per-paragraph
 * markup to hand-edit.
 */
function ink_foundation_audit_id_reading_paragraph( string $block_content ): string {
	if ( ! function_exists( 'is_singular' ) || ! is_singular( array( 'storie', 'artikel' ) ) ) {
		return $block_content;
	}

	$with_audit_id = preg_replace( '/<p\b/', '<p data-audit-id="storie-paragraph"', $block_content, 1 );

	return null !== $with_audit_id ? $with_audit_id : $block_content;
}
add_filter( 'render_block_core/post-content', 'ink_foundation_audit_id_reading_paragraph' );

/**
 * Auto-link bare URLs in a storie/artikel's prose body (Theme-Fidelity fourth
 * pass, docs/theme-fidelity-audit-handoff.md, 2026-09-05, item 10 — a genuine
 * product-owner-requested NEW feature, not a Lovable fidelity fix; Lovable's
 * own sample prose bodies never contain a bare URL to diff against).
 *
 * Styled like the site footer's own nav-link recipe — confirmed live via
 * `getComputedStyle()` on a real footer link, NOT assumed: the footer's own
 * links render in `muted-text` (`#6B7280`, a genuine gray), never the sitewide
 * `elements.link` primary/brand-red default that every bare `<a>` gets
 * otherwise. `.ink-prose-link` sets `color:muted-text` explicitly (it can't
 * rely on inheriting the footer's color the way the footer's own links do,
 * since it isn't scoped inside the footer template part) PLUS a traditional
 * underline on top (the footer's own links carry no underline, so this is a
 * NEW class, not a reuse) PLUS a trailing "opens in new tab" icon glyph (the
 * same inline-SVG house style already used in `reading-storie.php`/
 * `reading-gedig.php`'s hint pills). `target="_blank" rel="noopener
 * noreferrer"` per the request.
 *
 * An earlier version of this feature let the primary/brand-red color bleed
 * through from `elements.link` (never overridden), which is also what made
 * the reading pages' avatar+name byline link render brand-red/underlined
 * once `isLink:true` was added to `wp:avatar`/`wp:post-author-name` in the
 * same pass — flagged by the product owner and fixed alongside this: the
 * byline link resets to `ink-text`/no-underline (`.wp-block-post-author-
 * name__link`/`.wp-block-avatar__link` in `theme.json`), matching what it
 * looked like before it became a link at all, while `.ink-prose-link` gets
 * its own explicit `muted-text` instead of quietly inheriting brand-red.
 *
 * Deliberately NOT applied to gedig: poetry has no free-form prose paragraphs
 * beyond a short dedication, and this filter is gated to storie/artikel only
 * (never fires on `wp:ink/gedig-body`'s own separate render path anyway, but
 * gated explicitly for clarity and defence-in-depth).
 *
 * Skips text already inside an `<a>` (split-and-rejoin on existing anchor
 * tags, only linkifying the non-anchor segments) so a URL a writer already
 * hand-linked is never double-wrapped.
 */
function ink_foundation_autolink_prose_urls( string $block_content ): string {
	if ( ! function_exists( 'is_singular' ) || ! is_singular( array( 'storie', 'artikel' ) ) ) {
		return $block_content;
	}

	$segments = preg_split( '/(<a\b[^>]*>.*?<\/a>)/is', $block_content, -1, PREG_SPLIT_DELIM_CAPTURE );

	if ( ! is_array( $segments ) ) {
		return $block_content;
	}

	foreach ( $segments as $index => $segment ) {
		// Leave existing anchors (the odd-indexed capture groups) untouched.
		if ( 0 === strpos( $segment, '<a ' ) || 0 === strpos( $segment, '<a>' ) ) {
			continue;
		}

		$linked = preg_replace_callback(
			'/\bhttps?:\/\/[^\s<>"\']+/i',
			'ink_foundation_prose_link_markup',
			$segment
		);

		if ( null !== $linked ) {
			$segments[ $index ] = $linked;
		}
	}

	return implode( '', $segments );
}
add_filter( 'render_block_core/post-content', 'ink_foundation_autolink_prose_urls' );

/**
 * Build one auto-linked `<a>` for {@see ink_foundation_autolink_prose_urls()}.
 * Trailing sentence punctuation (`.`, `,`, `)`, etc.) is peeled off the URL and
 * placed back outside the anchor, so "See https://ink.example." doesn't pull
 * the full stop into the link.
 *
 * @param array<int, string> $matches The regex match (index 0 = the raw URL).
 * @return string
 */
function ink_foundation_prose_link_markup( array $matches ): string {
	$url     = $matches[0];
	$trailer = '';

	if ( preg_match( '/^(.*?)([.,;:!?)\]]+)$/', $url, $trim_matches ) ) {
		$url     = $trim_matches[1];
		$trailer = $trim_matches[2];
	}

	$icon = '<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" focusable="false" aria-hidden="true" style="display:inline;vertical-align:-1px;margin-left:2px"><path d="M15 3h6v6"/><path d="M10 14 21 3"/><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/></svg>';

	return '<a href="' . esc_url( $url ) . '" class="ink-prose-link" target="_blank" rel="noopener noreferrer">'
		. esc_html( $url ) . $icon . '</a>' . $trailer;
}

/**
 * Re-skin (not replace) WordPress core's OWN `wp-login.php?action=resetpass`
 * screen — the one auth screen this theme deliberately leaves as WordPress's
 * native `login_header()` markup rather than a custom page, because it carries
 * real WP-native interactive machinery (password-strength meter, "Generate
 * password") with no cheap equivalent to reproduce (Theme-Fidelity re-audit,
 * page 16 — the last page). See `assets/css/wp-login-brand.css`'s own
 * docblock for the full rationale + the hand-copied token values (this screen
 * renders standalone, no `wp_head`, so no `--wp--preset--*` custom properties
 * exist to `var()` against here).
 */
function ink_foundation_enqueue_wp_login_brand(): void {
	$action = isset( $_GET['action'] ) && is_scalar( $_GET['action'] ) ? sanitize_key( wp_unslash( (string) $_GET['action'] ) ) : 'login';

	if ( ! in_array( $action, array( 'resetpass', 'rp' ), true ) ) {
		return;
	}

	$theme = wp_get_theme();

	wp_enqueue_style(
		'ink-foundation-wp-login-brand',
		get_theme_file_uri( 'assets/css/wp-login-brand.css' ),
		array(),
		(string) $theme->get( 'Version' )
	);
}
add_action( 'login_enqueue_scripts', 'ink_foundation_enqueue_wp_login_brand' );

/**
 * Swap WordPress core's own logo link/title on `wp-login.php` for INK's
 * (same screen as above) — `login_headerurl`/`login_headertext` are core's
 * own, documented seam for exactly this, no core markup touched.
 */
function ink_foundation_login_headerurl(): string {
	return home_url( '/' );
}
add_filter( 'login_headerurl', 'ink_foundation_login_headerurl' );

function ink_foundation_login_headertext(): string {
	return get_bloginfo( 'name' );
}
add_filter( 'login_headertext', 'ink_foundation_login_headertext' );

/**
 * Enqueue the line-resonance client on a single gedig or storie (Story 7.3,
 * FR-26; extended 2026-09-06 to also drive the floating single-heart toggle).
 *
 * The reading-surface resonance widget attaches to the `[data-ink-line]` anchors
 * the ink/gedig-body block renders (gedig only) AND to the floating "enkel"
 * heart button `Ink\Engagement\ReactionTotals::toHtmlEnkel()` renders (gedig +
 * storie — a no-op `querySelector` miss wherever the relevant markup is absent),
 * writing both through the same `ink/v1/reaksie` REST endpoint. Business logic
 * stays server-side; this only ships the thin client + its config (REST root,
 * nonce, post id, the Afrikaans control label, the login URL for a guest's
 * floating-heart click).
 *
 * Post-Epic-19 fidelity pass: Lovable's `PoetryReader.tsx` gives each line ONE
 * heart toggle, not a picker of reaction types — so the client always sends the
 * single `hartjie` enum case (a safe, already-valid `Ink\Kernel\Reaction` case
 * for this write path, confirmed against `Ink\Engagement\ReactionController`) and
 * this config carries no `duim_op`/`wow` entries; that type choice never existed
 * in the design this control now matches. `reactedLines` is the aggregate list of
 * line/paragraph indexes that already carry a reaction from ANY reader — mirrors
 * `ink_foundation_enqueue_text_highlight_reactions()`'s `reactedParagraphs` — so
 * the persisted filled-heart state renders on page load for every visitor, not
 * just the ephemeral click that just resonated; the floating heart's initial
 * `is-active` state is seeded from this SAME list (its anchor is just another
 * index in it), not a second read.
 */
function ink_foundation_enqueue_line_reactions(): void {
	if ( ! function_exists( 'is_singular' ) || ! is_singular( array( 'gedig', 'storie' ) ) ) {
		return;
	}

	$theme   = wp_get_theme();
	$post_id = get_the_ID();

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
			'restUrl'      => esc_url_raw( rest_url( 'ink/v1/reaksie' ) ),
			'nonce'        => wp_create_nonce( 'wp_rest' ),
			'postId'       => $post_id,
			'label'        => __( 'Merk hierdie reël', 'ink-foundation' ),
			'reactedLines' => ( $post_id && class_exists( '\\Ink\\Engagement\\ReactionStore' ) )
				? array_values( \Ink\Engagement\ReactionStore::indexesWithReactions( (int) $post_id ) )
				: array(),
			'loginUrl'     => esc_url_raw( home_url( \Ink\Accounts\AuthRedirects::LOGIN_URL_PATH ) ),
		)
	);
}
add_action( 'wp_enqueue_scripts', 'ink_foundation_enqueue_line_reactions' );

/**
 * Enqueue the text-selection highlighting client on a single storie OR artikel
 * (Theme-Fidelity fourth pass, docs/theme-fidelity-audit-handoff.md,
 * 2026-09-05, item 9 — REPLACES the removed
 * `ink_foundation_enqueue_text_highlight_reactions()`/`text-highlight-
 * reactions.js`, which reused gedig's whole-paragraph hartjie/duim_op/wow
 * REST-backed "resonance" mechanism — confirmed, on a fresh read of
 * `HighlightableText.tsx`/`HighlightsPanel.tsx`, to be architecturally wrong:
 * Lovable's real storie/artikel interaction is select-text → "Highlight"
 * tooltip → confirm → add to an in-memory, session-local highlight list/
 * counter. No REST call, no login gate, no persistence — see
 * `highlightable-text.js`'s own docblock for the full citation).
 *
 * Now extended to artikel (the third pass had deliberately scoped this to
 * storie only, reasoning "no page-map row for lees-artikel" — moot now that
 * `reading-artikel.php` was rebuilt onto storie's exact shape, prose body
 * included).
 *
 * The Afrikaans control labels localised here (`highlightLabel`/
 * `panelTitleLabel`/`emptyLabel`/`removeLabel`) are NEW copy with no existing
 * `docs/ui-copy-translations.md` row — hand-authored in this pass, matching
 * this reading surface's established tone (e.g. gedig's "Merk hierdie reël"),
 * not run through the formal copy-debt translation-sheet pipeline. Flagged as
 * copy-debt for a future authoring pass, same as other minor new strings
 * logged elsewhere in `docs/theme-fidelity-rework-plan.md`.
 */
function ink_foundation_enqueue_highlightable_text(): void {
	if ( ! function_exists( 'is_singular' ) || ! is_singular( array( 'storie', 'artikel' ) ) ) {
		return;
	}

	$theme = wp_get_theme();

	wp_enqueue_script(
		'ink-foundation-highlightable-text',
		get_theme_file_uri( 'assets/js/highlightable-text.js' ),
		array(),
		(string) $theme->get( 'Version' ),
		true
	);

	wp_localize_script(
		'ink-foundation-highlightable-text',
		'inkHighlightableText',
		array(
			'containerSelector' => '.ink-lees-storie__prose',
			'highlightLabel'    => __( 'Merk uit', 'ink-foundation' ),
			'panelTitleLabel'   => __( 'Jou uitgeligte gedeeltes', 'ink-foundation' ),
			'emptyLabel'        => __( 'Kies enige teks in die stuk om onvergeetlike gedeeltes uit te lig.', 'ink-foundation' ),
			'removeLabel'       => __( 'Verwyder hooglig', 'ink-foundation' ),
		)
	);
}
add_action( 'wp_enqueue_scripts', 'ink_foundation_enqueue_highlightable_text' );

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
 * Afrikaans (ui-copy-translations.md 155/156), localised verbatim. Already
 * enqueued regardless of login state (gated on post type only) — the block now
 * renders for guests too (product-owner decision, post-Epic-19 follow-up), so
 * `loginUrl` (the shared `Ink\Accounts\AuthRedirects::LOGIN_URL_PATH`, never a
 * new hardcoded path) is always localised; the client only uses it for a guest's
 * `data-ink-guest` click. `errorText` (2026-09-06, the optimistic-UI fix — see
 * `leeslys.js`'s docblock) is new, hand-authored copy with no existing
 * `docs/ui-copy-translations.md` row; flagged as copy-debt for a future
 * authoring pass, same as other minor new strings logged elsewhere in
 * `docs/theme-fidelity-rework-plan.md`.
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
			'errorText'   => __( 'Kon nie stoor nie. Probeer weer.', 'ink-foundation' ),
			'loginUrl'    => esc_url_raw( home_url( \Ink\Accounts\AuthRedirects::LOGIN_URL_PATH ) ),
		)
	);
}
add_action( 'wp_enqueue_scripts', 'ink_foundation_enqueue_leeslys' );

/**
 * Enqueue the vasgespelde-werke pin/unpin client on My Profiel (Story 9.5, FR-41).
 *
 * Root-cause fix (Phase-2 fidelity pass): `ink/vasgespel-bestuur` server-rendered
 * `.ink-vasgespel__knoppie` buttons with no client ever wired to flip them through
 * `ink/v1/vasgespel` — clicking fired no REST request at all. Loaded only on My
 * Profiel, mirroring the leeslys enqueue above.
 */
function ink_foundation_enqueue_vasgespel(): void {
	if ( ! function_exists( 'is_page' ) || ! is_page( 'my-profiel' ) ) {
		return;
	}

	$theme = wp_get_theme();

	wp_enqueue_script(
		'ink-foundation-vasgespel',
		get_theme_file_uri( 'assets/js/vasgespel.js' ),
		array(),
		(string) $theme->get( 'Version' ),
		true
	);

	wp_localize_script(
		'ink-foundation-vasgespel',
		'inkVasgespel',
		array(
			'restUrl'      => esc_url_raw( rest_url( 'ink/v1/vasgespel' ) ),
			'nonce'        => wp_create_nonce( 'wp_rest' ),
			'pinnedText'   => __( 'Vasgespeld', 'ink-foundation' ),
			'unpinnedText' => __( 'Speld vas', 'ink-foundation' ),
		)
	);
}
add_action( 'wp_enqueue_scripts', 'ink_foundation_enqueue_vasgespel' );

/**
 * Enqueue the volg/unfollow client on My Profiel + the public Skrywerprofiel
 * (Story 9.2, FR-38).
 *
 * Root-cause fix (Phase-2 fidelity pass, found while wiring the "Wie ek volg"
 * tab): `ink/volg-knoppie` server-renders `.ink-volg-knoppie` buttons with no
 * client ever wired to flip them through `ink/v1/volg` — the same missing-JS
 * shape as the vasgespel pin toggle above, just never previously caught because
 * My Profiel had no "Wie ek volg" list to click an unfollow button from.
 */
function ink_foundation_enqueue_volg(): void {
	$on_my_profiel     = function_exists( 'is_page' ) && is_page( 'my-profiel' );
	$on_skrywerprofiel = function_exists( 'is_author' ) && is_author();

	if ( ! $on_my_profiel && ! $on_skrywerprofiel ) {
		return;
	}

	$theme = wp_get_theme();

	wp_enqueue_script(
		'ink-foundation-volg',
		get_theme_file_uri( 'assets/js/volg.js' ),
		array(),
		(string) $theme->get( 'Version' ),
		true
	);

	wp_localize_script(
		'ink-foundation-volg',
		'inkVolg',
		array(
			'restUrl'       => esc_url_raw( rest_url( 'ink/v1/volg' ) ),
			'nonce'         => wp_create_nonce( 'wp_rest' ),
			'followText'    => __( 'Volg', 'ink-foundation' ),
			'followingText' => __( 'Volg tans', 'ink-foundation' ),
		)
	);
}
add_action( 'wp_enqueue_scripts', 'ink_foundation_enqueue_volg' );

/**
 * Enqueue the "Wysig profiel" edit-modal client on My Profiel (My Profiel
 * rebuild §5.1).
 *
 * `ink/profiel-redigeer` server-renders the hidden modal + form, but nothing
 * ever wires the open/close interaction or the `ink/v1/profiel` save request —
 * the same missing-JS shape as the vasgespel/volg enqueues above. Loaded only
 * on My Profiel. Safe to load ahead of the identity-strip step embedding the
 * modal's trigger: `profiel-edit.js` no-ops entirely when `#ink-profiel-redigeer`
 * isn't present in the DOM.
 */
function ink_foundation_enqueue_profiel_edit(): void {
	if ( ! function_exists( 'is_page' ) || ! is_page( 'my-profiel' ) ) {
		return;
	}

	$theme = wp_get_theme();

	wp_enqueue_script(
		'ink-foundation-profiel-edit',
		get_theme_file_uri( 'assets/js/profiel-edit.js' ),
		array(),
		(string) $theme->get( 'Version' ),
		true
	);

	wp_localize_script(
		'ink-foundation-profiel-edit',
		'inkProfielEdit',
		array(
			'restUrl'    => esc_url_raw( rest_url( 'ink/v1/profiel' ) ),
			'nonce'      => wp_create_nonce( 'wp_rest' ),
			// Copy-debt: the transient saving/error status text has no ratified
			// Afrikaans yet — flagged per the standard afrikaans-copy-debt-process
			// (docs/afrikaans-translation-sheet.md PROFIEL-REDIGEER-BESIG /
			// PROFIEL-REDIGEER-FOUT, docs/afrikaans-copy-worklist.md) rather than
			// invented here.
			'savingText' => __( '[NEEDS HUMAN AFRIKAANS] — saving-status text not yet authored in ui-copy-translations.md.', 'ink-foundation' ),
			'errorText'  => __( '[NEEDS HUMAN AFRIKAANS] — save-error status text not yet authored in ui-copy-translations.md.', 'ink-foundation' ),
		)
	);
}
add_action( 'wp_enqueue_scripts', 'ink_foundation_enqueue_profiel_edit' );

/**
 * Enqueue the "Merk alles as gelees" client on My Profiel (My Profiel rebuild
 * §5.8). `KennisgewingsSurface::toHtml()` server-renders the button + list, but
 * nothing wires the click through `ink/v1/kennisgewings` — the same
 * missing-JS shape as the vasgespel/volg/profiel-edit enqueues above. Loaded
 * only on My Profiel.
 */
function ink_foundation_enqueue_kennisgewings(): void {
	if ( ! function_exists( 'is_page' ) || ! is_page( 'my-profiel' ) ) {
		return;
	}

	$theme = wp_get_theme();

	wp_enqueue_script(
		'ink-foundation-kennisgewings',
		get_theme_file_uri( 'assets/js/kennisgewings.js' ),
		array(),
		(string) $theme->get( 'Version' ),
		true
	);

	wp_localize_script(
		'ink-foundation-kennisgewings',
		'inkKennisgewings',
		array(
			'restUrl' => esc_url_raw( rest_url( 'ink/v1/kennisgewings' ) ),
			'nonce'   => wp_create_nonce( 'wp_rest' ),
		)
	);
}
add_action( 'wp_enqueue_scripts', 'ink_foundation_enqueue_kennisgewings' );

/**
 * Enqueue the My Profiel tab-toggle enhancement on My Profiel only (My Profiel
 * rebuild §5.2 — the 7-tab shell had no JS toggle before this).
 *
 * Progressive enhancement over the 7 stacked `[data-ink-profiel-panel]`
 * sections, mirroring `ink_foundation_enqueue_ontdek_assets()` above exactly —
 * see `profiel-tabs.js`'s own docblock. With this script disabled the 7
 * sections simply stack in ratified order.
 */
function ink_foundation_enqueue_profiel_tabs(): void {
	if ( ! function_exists( 'is_page' ) || ! is_page( 'my-profiel' ) ) {
		return;
	}

	$theme = wp_get_theme();

	wp_enqueue_script(
		'ink-foundation-profiel-tabs',
		get_theme_file_uri( 'assets/js/profiel-tabs.js' ),
		array(),
		(string) $theme->get( 'Version' ),
		true
	);
}
add_action( 'wp_enqueue_scripts', 'ink_foundation_enqueue_profiel_tabs' );

/**
 * Enqueue the My Profiel identity-strip + tab-shell stylesheet on My Profiel
 * only (My Profiel rebuild §6).
 *
 * `profiel.css` carries the identity strip's and tab shell's presentation —
 * before this file `my-profiel.php` rendered bare block markup with no
 * dedicated stylesheet at all, mirroring `reading.css`'s narrow,
 * single-page-scoped enqueue pattern. Deliberately near-empty for now (see the
 * file's own docblock) — the full Lovable-fidelity styling pass is a later
 * build step.
 */
function ink_foundation_enqueue_profiel_assets(): void {
	if ( ! function_exists( 'is_page' ) || ! is_page( 'my-profiel' ) ) {
		return;
	}

	$theme = wp_get_theme();

	wp_enqueue_style(
		'ink-foundation-profiel',
		get_theme_file_uri( 'assets/css/profiel.css' ),
		array(),
		(string) $theme->get( 'Version' )
	);
}
add_action( 'wp_enqueue_scripts', 'ink_foundation_enqueue_profiel_assets' );

/**
 * Enqueue the Skrywerprofiel "Deel" (share) client on an author archive.
 *
 * The button + its ratified Afrikaans labels are server-rendered by
 * {@see \Ink\Social\SkrywerProfiel::toHtml()} on `data-ink-deel-*` attributes;
 * this thin client only performs the clipboard write. Loaded only on the
 * public skrywer profile (`is_author()`), mirroring the pattern above.
 */
function ink_foundation_enqueue_skrywer_deel(): void {
	if ( ! function_exists( 'is_author' ) || ! is_author() ) {
		return;
	}

	$theme = wp_get_theme();

	wp_enqueue_script(
		'ink-foundation-skrywer-deel',
		get_theme_file_uri( 'assets/js/skrywer-deel.js' ),
		array(),
		(string) $theme->get( 'Version' ),
		true
	);
}
add_action( 'wp_enqueue_scripts', 'ink_foundation_enqueue_skrywer_deel' );

/**
 * Enqueue the home (Tuisblad) stylesheet on the front page only (Epic 19, §0.8).
 *
 * The theme.json `css` string only carries the ink-core widget styles; the
 * home-page shared primitives that need @keyframes / ::before / gradient-clip
 * (fade-up, underline-slide, the .ink-hero-texture plus-pattern layer, the
 * .ink-text-gradient accent, the ink-btn-* sizing utilities, and the
 * prefers-reduced-motion base) live in a real enqueued stylesheet. Gated to the
 * front page, mirroring the script-enqueue pattern above and versioned to the
 * theme so cache-busting rides the theme version. Presentation only — no
 * business logic (three-layer separation holds).
 *
 * Also loads on the QA/component gallery page ({@see INK_FOUNDATION_QA_GALLERY_SLUG})
 * — that page renders the SAME home-page dynamic blocks (`ink/huidige-uitdaging`,
 * `ink/wenner-kollig`, `ink/uitgesoekte-bydraes`, `ink/borg-strook`) with fixture
 * data, so it needs the same stylesheet those blocks' markup is styled by. Any
 * future gallery addition that needs a DIFFERENT page's conditionally-enqueued
 * CSS must extend that page's own enqueue condition the same way (see
 * `patterns/qa-bloks.php` for the pattern).
 */
function ink_foundation_enqueue_home_assets(): void {
	$is_front = function_exists( 'is_front_page' ) && is_front_page();
	$is_qa    = function_exists( 'is_page' ) && is_page( INK_FOUNDATION_QA_GALLERY_SLUG );

	if ( ! $is_front && ! $is_qa ) {
		return;
	}

	$theme = wp_get_theme();

	wp_enqueue_style(
		'ink-foundation-home',
		get_theme_file_uri( 'assets/css/home.css' ),
		array(),
		(string) $theme->get( 'Version' )
	);
}
add_action( 'wp_enqueue_scripts', 'ink_foundation_enqueue_home_assets' );

/**
 * === QA/component gallery — fixture-data pattern ===
 *
 * (Phase 2, Theme Visual-Fidelity rework — `docs/theme-fidelity-rework-plan.md`,
 * workstream B.) Several of INK's dynamic blocks need real WordPress content/data
 * to render anything — but nothing was seeded on the live site, so their visual
 * fidelity could never be checked. The QA gallery page
 * (`/` . INK_FOUNDATION_QA_GALLERY_SLUG, template `templates/page-qa-bloks.html`,
 * content `patterns/qa-bloks.php`) is a persistent, repo-tracked page that embeds
 * those blocks and feeds them realistic fixture data — a real committed template,
 * reusable by every future fidelity pass, not a throwaway.
 *
 * TWO fixture techniques are used, depending on whether the block's `ink-core`
 * class exposes a data-seam filter:
 *
 * 1. FILTER SEAM (preferred). Several blocks already read their payload through an
 *    `apply_filters()` seam with a live-query fallback (house style — see e.g.
 *    {@see \Ink\Challenges\CurrentChallenge::DATA_FILTER}). Hook that filter here,
 *    gated by {@see ink_foundation_is_qa_gallery()} so the fixture data can NEVER
 *    leak into a real page's rendering of the same block — every callback below
 *    returns the untouched `$data` argument (falling through to the block's own
 *    live/default behaviour) on every OTHER page. This is the pattern to reach for
 *    first: check the block's PHP class for a `*_FILTER` constant before seeding
 *    real content.
 * 2. REAL SEEDED WP CONTENT. When a block has no filter seam (e.g. the sponsor
 *    strip, `ink/borg-strook`, reads directly from `Campaign::activeSponsors()`
 *    with no seam to hook), the only option is real fixture posts living in this
 *    site's database — NOT tracked by git. Each such fixture is titled with the
 *    `QA FIXTURE — ` prefix so it is unmistakable in wp-admin lists and can never
 *    be confused with real editorial content. See the commit/session notes for
 *    exactly what was seeded. Epic-19 theme-fidelity rework finding: a live query
 *    has no way to tell such a fixture apart from real content, so it silently
 *    leaked onto every real page rendering the same block — see
 *    {@see \Ink\Kernel\QaFixture}, which every affected `ink-core` live-query class
 *    now consults to exclude fixtures by default. `ink/borg-strook` has no filter
 *    seam to gate a fixture-only PAYLOAD, so its exclusion is instead gated the
 *    other way round: {@see ink_foundation_qa_fixture_include_sponsors()} turns the
 *    exclusion back OFF, but only on this gallery page.
 *
 * TO ADD YOUR OWN PAGE'S HARD-TO-REACH BLOCK to this gallery in a future fidelity
 * pass: (a) check its `ink-core` class for a `*_FILTER` data seam — if one exists,
 * add a gated callback here following the three examples below; (b) if none
 * exists, seed real fixture WP content via wp-admin (browser), title-prefixed
 * `QA FIXTURE — `, and note it in your session report (it's DB state, not
 * git-tracked, and future sessions need to know it exists); (c) add your block to
 * `patterns/qa-bloks.php` with a clear section heading identifying it; (d) if your
 * block's styling lives in a conditionally-enqueued stylesheet (like `home.css`
 * below), extend that stylesheet's enqueue condition to also fire on the QA
 * gallery page, exactly as {@see ink_foundation_enqueue_home_assets()} does.
 */

/**
 * Whether the current request is rendering the QA/component gallery page.
 *
 * The single gate every fixture-filter callback below checks FIRST — fixture data
 * must never leak into a real page's rendering of these blocks. `is_page()` is
 * safe to call here: every callback only runs from inside block rendering
 * (`do_blocks()`/`render_block()`), which happens well after the main query has
 * resolved.
 */
function ink_foundation_is_qa_gallery(): bool {
	return function_exists( 'is_page' ) && is_page( INK_FOUNDATION_QA_GALLERY_SLUG );
}

/**
 * Fixture payload for `ink/huidige-uitdaging` (the weekly-challenge card, both the
 * compact hero-aside variant and the larger feature variant — same payload, the
 * block's own `variant` attribute picks the markup). Hooks
 * `Ink\Challenges\CurrentChallenge::DATA_FILTER`; shape per that class's
 * {@see \Ink\Challenges\CurrentChallenge::resolveCurrent()} payload.
 *
 * @param mixed $data The filter's incoming value (null unless another filter
 *                     already supplied a payload).
 * @return mixed
 */
function ink_foundation_qa_fixture_current_challenge( mixed $data ): mixed {
	if ( ! ink_foundation_is_qa_gallery() ) {
		return $data;
	}

	return array(
		'title'       => 'QA FIXTURE — Skryf ’n brief aan jou toekomstige self',
		'url'         => '#qa-fixture-huidige-uitdaging',
		'excerpt'     => 'Hierdie week nooi ons jou om ’n brief te skryf aan wie jy oor tien jaar hoop om te wees. Verken hoop, vrees en die pad wat voorlê — enige genre werk.',
		'deadline'    => '30 September 2026',
		'eyebrow'     => 'September-uitdaging',
		'entry_count' => 24,
	);
}
add_filter( 'ink_home_current_challenge', 'ink_foundation_qa_fixture_current_challenge' ); // Ink\Challenges\CurrentChallenge::DATA_FILTER.

/**
 * Fixture payload for `ink/wenner-kollig` (the winner spotlight). Hooks
 * `Ink\Challenges\FeaturedWinners::FEATURED_FILTER`; shape per that class's
 * {@see \Ink\Challenges\FeaturedWinners::orderFeed()} docblock.
 *
 * @param mixed $data The filter's incoming value (null unless another filter
 *                     already supplied a payload).
 * @return mixed
 */
function ink_foundation_qa_fixture_featured_winner( mixed $data ): mixed {
	if ( ! ink_foundation_is_qa_gallery() ) {
		return $data;
	}

	return array(
		'winners' => array(
			array(
				'id'        => 900001,
				'rank'      => 1,
				'title'     => 'Die Laaste Reën',
				'url'       => '#qa-fixture-winner-1',
				'month'     => 'Augustus',
				'author'    => 'M. van der Merwe',
				'quote'     => 'Elke druppel het ’n storie gedra wat ek nooit geweet het ek moes vertel nie.',
				'win_label' => '2de wen',
			),
			array(
				'id'     => 900002,
				'rank'   => 2,
				'title'  => 'Skadu’s van Somer',
				'url'    => '#qa-fixture-winner-2',
				'month'  => 'Augustus',
				'author' => 'J. Botha',
			),
			array(
				'id'     => 900003,
				'rank'   => 3,
				'title'  => 'Die Huis op die Hoek',
				'url'    => '#qa-fixture-winner-3',
				'month'  => 'Augustus',
				'author' => 'L. Naidoo',
			),
		),
	);
}
add_filter( 'ink_home_featured_winner', 'ink_foundation_qa_fixture_featured_winner' ); // Ink\Challenges\FeaturedWinners::FEATURED_FILTER.

/**
 * FRONT-PAGE VISUAL-PARITY DEMO CONTENT for `ink/wenner-kollig` — **not** a
 * completion of Epic 12A's real winner-data wiring.
 *
 * Read this before touching it. `Ink\Challenges\FeaturedWinners` sources its whole
 * payload from one filter ({@see \Ink\Challenges\FeaturedWinners::FEATURED_FILTER})
 * and nothing in `ink-core` hooks it — its own docblock still says "Epic 12A is
 * unbuilt", which is now STALE (12A shipped and merged). The winner spotlight is
 * therefore a **stranded capability**: the block, its markup, its ordering contract
 * and its styling all exist, but no production code path ever feeds it, so the
 * Tuisblad's winner slot can never render real adjudication results. Confirmed
 * against this install's database on 2026-09-05: zero `ink_entry_placement` meta
 * rows exist, so there is not even latent real data to query. Closing that gap
 * (querying the most recent concluded challenge's `Placements` and composing the
 * announcement payload) is real feature work owned by Challenges, NOT by the theme,
 * and remains OPEN.
 *
 * What this function does instead: supply believable demo content on the REAL front
 * page only, so the section's fidelity can be reviewed against Lovable's "December
 * Winner" card at all (product-owner finding #3, "there is no test fixture for the
 * winning entry"). It is deliberately a SEPARATE hook from
 * {@see ink_foundation_qa_fixture_featured_winner()} rather than a widening of that
 * function's gate: conflating "QA gallery fixture payload" with "content a visitor
 * sees on the real home page" is exactly the mistake that let `QA FIXTURE — `
 * sponsor posts leak onto the live site earlier in this rework. For the same reason
 * the copy here carries NO `QA FIXTURE — ` prefix — it is front-of-house demo
 * content, and {@see \Ink\Kernel\QaFixture} exclusion must not apply to it.
 *
 * Delete this function (and its `add_filter`) the moment Challenges supplies the
 * real payload; the block will pick the real data up with no other change.
 *
 * No section-level 'title'/'url' any more (fourth-pass follow-up, 2026-09-05):
 * {@see \Ink\Challenges\FeaturedWinners::toHtml()} stopped rendering a section
 * heading (direct product-owner finding: "DESEMBER SE WENNERS" above the card is
 * not supposed to be there — Lovable's `ChallengeSection.tsx` has no such heading,
 * each card carries its own eyebrow). This entry is rank 2 (an ordinary wenner,
 * not the algehele wenner) with an avatar, matching Lovable's own "December
 * Winner" / "Sarah Mitchell" demo card exactly (same fields populated, same
 * Unsplash placeholder photo Lovable's own `ChallengeSection.tsx` hardcodes for
 * this entry — reused rather than inventing a new placeholder).
 *
 * @param mixed $data The filter's incoming value (null unless another filter
 *                     already supplied a payload).
 * @return mixed
 */
function ink_foundation_homepage_demo_winner( mixed $data ): mixed {
	if ( ! function_exists( 'is_front_page' ) || ! is_front_page() ) {
		return $data;
	}

	// Never override a real payload, so this evaporates the moment 12A wires one up.
	if ( null !== $data ) {
		return $data;
	}

	return array(
		'winners' => array(
			array(
				'id'         => 1,
				'rank'       => 2,
				'title'      => __( 'Die laaste lig van winter', 'ink-foundation' ),
				'url'        => '/uitdagings/',
				'month'      => __( 'Desember', 'ink-foundation' ),
				'author'     => 'Sarie Mostert',
				'quote'      => __( 'Die kers het geflikker teen die ryp-geverfde venster, elke dansende skaduwee ’n herinnering aan somers lank verby …', 'ink-foundation' ),
				'avatar_url' => 'https://images.unsplash.com/photo-1494790108377-be9c29b29330?w=100&h=100&fit=crop&crop=face',
				'avatar_alt' => 'Sarie Mostert',
				'win_label'  => __( '3de wen', 'ink-foundation' ),
			),
		),
	);
}
add_filter( 'ink_home_featured_winner', 'ink_foundation_homepage_demo_winner' ); // Ink\Challenges\FeaturedWinners::FEATURED_FILTER.

/**
 * Fixture payload for `ink/uitgesoekte-bydraes` ("Die redakteur se keuse" featured
 * stream — one spanning featured card + three standard cards). Hooks
 * `Ink\Discovery\FeaturedStream::DATA_FILTER`; shape per that class's
 * {@see \Ink\Discovery\FeaturedStream::resolveStream()} payload.
 *
 * @param mixed $data The filter's incoming value (null unless another filter
 *                     already supplied a payload).
 * @return mixed
 */
function ink_foundation_qa_fixture_featured_stream( mixed $data ): mixed {
	if ( ! ink_foundation_is_qa_gallery() ) {
		return $data;
	}

	return array(
		array(
			'title'          => 'QA FIXTURE — Die Wind Onthou',
			'url'            => '#qa-fixture-bydrae-1',
			'category'       => 'Gedig',
			'read_minutes'   => 3,
			'excerpt'        => '’n Meditasie oor herinnering, geskryf in die stilte tussen twee reënseisoene.',
			'author'         => 'Anika Pretorius',
			'avatar_url'     => '',
			'hart_count'     => 128,
			'response_count' => 14,
		),
		array(
			'title'          => 'QA FIXTURE — Ligte in die Karoo',
			'url'            => '#qa-fixture-bydrae-2',
			'category'       => 'Storie',
			'read_minutes'   => 7,
			'excerpt'        => 'ʼn Reisende musikant vind onverwagte vriendskap in ’n klein Karoo-dorpie.',
			'author'         => 'Dawid Coetzee',
			'avatar_url'     => '',
			'hart_count'     => 76,
			'response_count' => 9,
		),
		array(
			'title'          => 'QA FIXTURE — Vaders en Seuns',
			'url'            => '#qa-fixture-bydrae-3',
			'category'       => 'Artikel',
			'read_minutes'   => 5,
			'excerpt'        => 'ʼn Eerlike blik op drie generasies mans en die woorde wat hulle nooit vir mekaar gesê het nie.',
			'author'         => 'Zanele Khumalo',
			'avatar_url'     => '',
			'hart_count'     => 54,
			'response_count' => 6,
		),
		array(
			'title'          => 'QA FIXTURE — Die Klavier wat Nooit Speel Nie',
			'url'            => '#qa-fixture-bydrae-4',
			'category'       => 'Storie',
			'read_minutes'   => 4,
			'excerpt'        => 'ʼn Erfstuk in die sitkamer word die stille getuie van ’n gesin se opgekropte hartseer.',
			'author'         => 'Pieter Human',
			'avatar_url'     => '',
			'hart_count'     => 39,
			'response_count' => 3,
		),
	);
}
add_filter( 'ink_home_featured_stream', 'ink_foundation_qa_fixture_featured_stream' ); // Ink\Discovery\FeaturedStream::DATA_FILTER.

/**
 * Fixture override for `ink/borg-strook` (the sponsor strip) — the "REAL SEEDED WP
 * CONTENT" technique's other half. This block has no `*_FILTER` data seam (it reads
 * `Campaign::activeSponsors()` directly), so `Ink\Sponsors\HomepageStrip` itself
 * EXCLUDES `QA FIXTURE — ` titled sponsors from every real page by default
 * (Epic-19 theme-fidelity rework finding: they were leaking onto the real Tuisblad).
 * This callback is the one place that turns that exclusion back OFF, gated to the
 * QA gallery page only, so the three real seeded fixture `borg` posts described in
 * `patterns/qa-bloks.php` stay visible there — their whole purpose.
 *
 * @param mixed $include The filter's incoming value (false unless another filter
 *                        already overrode it).
 * @return mixed
 */
function ink_foundation_qa_fixture_include_sponsors( mixed $include ): mixed {
	return ink_foundation_is_qa_gallery() ? true : $include;
}
add_filter( 'ink_borg_strook_include_fixtures', 'ink_foundation_qa_fixture_include_sponsors' ); // Ink\Sponsors\HomepageStrip::INCLUDE_FIXTURES_FILTER.

/**
 * Fixture override for `ink/borg-erkenning` (the Oor INK sponsor recognition
 * section) — same shape as {@see ink_foundation_qa_fixture_include_sponsors()},
 * added during the Epic-19 theme-fidelity re-audit (page 14, oor-ink). This block
 * has no `*_FILTER` data seam either (it also reads `Campaign::activeSponsors()`
 * directly), and — unlike the homepage strip — had NO exclusion at all before this
 * pass, so the same three real seeded `QA FIXTURE — ` `borg` posts leaked onto the
 * real `/oor-ink/` page (confirmed live). `Ink\Sponsors\RecognitionSection` now
 * excludes them the same way; this callback turns that exclusion back OFF, gated to
 * the QA gallery page only, reusing the SAME three seeded fixture posts (no new
 * fixture content needed — they already exist for the homepage strip).
 *
 * @param mixed $include The filter's incoming value (false unless another filter
 *                        already overrode it).
 * @return mixed
 */
function ink_foundation_qa_fixture_include_sponsors_erkenning( mixed $include ): mixed {
	return ink_foundation_is_qa_gallery() ? true : $include;
}
add_filter( 'ink_borg_erkenning_include_fixtures', 'ink_foundation_qa_fixture_include_sponsors_erkenning' ); // Ink\Sponsors\RecognitionSection::INCLUDE_FIXTURES_FILTER.

/**
 * Fixture override for `ink/opleiding-argief` (the Opleiding hub) — same
 * "turn the exclusion back on for this page only" shape as
 * {@see ink_foundation_qa_fixture_include_sponsors()}. This block has no
 * `*_FILTER` data seam either (a live paginated `WP_Query`,
 * {@see \Ink\Training\Hub::runQuery()}), so `Ink\Training\Hub` itself EXCLUDES
 * `QA FIXTURE — ` titled `opleiding_artikel` posts from every real page by
 * default (Epic-19 theme-fidelity rework finding: with only fixture-titled
 * `opleiding_artikel` posts existing on this dev site, 100% of the real
 * `/opleiding/` page's content was fixture data). This callback turns that
 * exclusion back OFF, gated to the QA gallery page only, so the three real
 * seeded fixture `opleiding_artikel` posts stay visible here for the featured
 * shelf + card-grid fidelity check.
 *
 * @param mixed $include The filter's incoming value (false unless another
 *                        filter already overrode it).
 * @return mixed
 */
function ink_foundation_qa_fixture_include_opleiding( mixed $include ): mixed {
	return ink_foundation_is_qa_gallery() ? true : $include;
}
add_filter( 'ink_opleiding_argief_include_fixtures', 'ink_foundation_qa_fixture_include_opleiding' ); // Ink\Training\Hub::INCLUDE_FIXTURES_FILTER.

/**
 * Fixture override for `ink/biblioteek-argief` (the Biblioteek archive) — same
 * "turn the exclusion back on for this page only" shape as
 * {@see ink_foundation_qa_fixture_include_opleiding()}. This block has no
 * `*_FILTER` data seam either (a live paginated `WP_Query`,
 * {@see \Ink\Library\Archive::runQuery()}), so `Ink\Library\Archive` itself
 * EXCLUDES `QA FIXTURE — ` titled `biblioteek_item` posts from every real page by
 * default (Epic-19 theme-fidelity rework finding, re-found during the biblioteek
 * re-audit: with only fixture-titled `biblioteek_item` posts existing on this dev
 * site, 100% of the real `/biblioteek/` page's content — including the "Uitgelig"
 * featured strip — was fixture data). This callback turns that exclusion back OFF,
 * gated to the QA gallery page only, so the three real seeded fixture
 * `biblioteek_item` posts stay visible here for the featured shelf + card-grid
 * fidelity check.
 *
 * @param mixed $include The filter's incoming value (false unless another
 *                        filter already overrode it).
 * @return mixed
 */
function ink_foundation_qa_fixture_include_biblioteek( mixed $include ): mixed {
	return ink_foundation_is_qa_gallery() ? true : $include;
}
add_filter( 'ink_biblioteek_argief_include_fixtures', 'ink_foundation_qa_fixture_include_biblioteek' ); // Ink\Library\Archive::INCLUDE_FIXTURES_FILTER.

/**
 * Fixture override for `ink/uitdaging-argief` (the Uitdagings list) — same
 * "turn the exclusion back on for this page only" shape as
 * {@see ink_foundation_qa_fixture_include_biblioteek()}. This block has no
 * `*_FILTER` data seam either (a live paginated `WP_Query`,
 * {@see \Ink\Challenges\Archive::runQuery()}), so `Ink\Challenges\Archive` itself
 * EXCLUDES `QA FIXTURE — ` titled `uitdaging` posts from every real page by
 * default (Epic-19 theme-fidelity re-audit finding: the archive query had NO
 * exclusion at all, so the real `/uitdaging/` page showed 3 QA FIXTURE cards
 * alongside the one real published challenge). This callback turns that
 * exclusion back OFF, gated to the QA gallery page only, so the three real
 * seeded fixture `uitdaging` posts stay visible here for the card-grid fidelity
 * check.
 *
 * @param mixed $include The filter's incoming value (false unless another
 *                        filter already overrode it).
 * @return mixed
 */
function ink_foundation_qa_fixture_include_uitdagings( mixed $include ): mixed {
	return ink_foundation_is_qa_gallery() ? true : $include;
}
add_filter( 'ink_uitdaging_argief_include_fixtures', 'ink_foundation_qa_fixture_include_uitdagings' ); // Ink\Challenges\Archive::INCLUDE_FIXTURES_FILTER.

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

	// INK button variants (Epic 19, §0.1 / §9). Colour/border/radius/font +
	// :hover + a visible :focus-visible ring (2px primary + 2px offset, a11y) are
	// carried on the block-style inline_style so they load site-wide (not only on
	// the front page). Button SIZE is NOT a block style (register_block_style is a
	// single radio axis, so size x variant can't combine) — size is baked
	// per-instance in the locked patterns, or via the ink-btn-* utilities in
	// home.css. Radius is capped at radius.md (6px) / radius.lg (8px) — never a
	// pill. Token-only (Gate A); all colour/type resolves to --wp--preset--*.
	//
	// is-style-ink-primary — literary: primary fill / surface-alt text / shadow.md,
	// hover -> primary-light.
	register_block_style(
		'core/button',
		array(
			'name'         => 'ink-primary',
			'label'        => __( 'INK primêr', 'ink-foundation' ),
			'inline_style' => '.wp-block-button.is-style-ink-primary .wp-block-button__link{'
				. 'background-color:var(--wp--preset--color--primary);'
				. 'color:var(--wp--preset--color--surface-alt);'
				. 'font-family:var(--wp--preset--font-family--display);'
				. 'border:0;'
				. 'border-radius:var(--wp--custom--radius--md);'
				. 'box-shadow:var(--wp--preset--shadow--md);'
				. 'transition:all .15s ease;'
				. '}'
				. '.wp-block-button.is-style-ink-primary .wp-block-button__link:hover{'
				. 'background-color:var(--wp--preset--color--primary-light);'
				. '}'
				. '.wp-block-button.is-style-ink-primary .wp-block-button__link:focus-visible{'
				. 'outline:2px solid var(--wp--preset--color--primary);'
				. 'outline-offset:2px;'
				. '}'
				. '.wp-block-button.is-style-ink-primary .wp-block-button__link:disabled{'
				. 'opacity:.5;'
				. '}',
		)
	);

	// is-style-ink-outline — 2px primary border / primary text, hover -> fill
	// primary + surface-alt text.
	register_block_style(
		'core/button',
		array(
			'name'         => 'ink-outline',
			'label'        => __( 'INK omlyn', 'ink-foundation' ),
			'inline_style' => '.wp-block-button.is-style-ink-outline .wp-block-button__link{'
				. 'background-color:transparent;'
				. 'color:var(--wp--preset--color--primary);'
				. 'font-family:var(--wp--preset--font-family--display);'
				. 'border:2px solid var(--wp--preset--color--primary);'
				. 'border-radius:var(--wp--custom--radius--md);'
				. 'transition:all .15s ease;'
				. '}'
				. '.wp-block-button.is-style-ink-outline .wp-block-button__link:hover{'
				. 'background-color:var(--wp--preset--color--primary);'
				. 'color:var(--wp--preset--color--surface-alt);'
				. '}'
				. '.wp-block-button.is-style-ink-outline .wp-block-button__link:focus-visible{'
				. 'outline:2px solid var(--wp--preset--color--primary);'
				. 'outline-offset:2px;'
				. '}'
				. '.wp-block-button.is-style-ink-outline .wp-block-button__link:disabled{'
				. 'opacity:.5;'
				. '}',
		)
	);

	// is-style-ink-ghost — transparent / ink-text at rest, hover -> accent
	// (sage) fill + surface-alt text. Added for the header's logged-out "Teken
	// in" action (Epic 19 fourth-pass fidelity fix, 2026-09-05): Lovable's
	// header pairs a `variant="ghost"` Sign-in button with a `variant="literary"`
	// (ink-primary) Join button — confirmed live (`preview--quill-muse-heart.
	// lovable.app`) at rest: transparent background, `rgb(24,29,37)` (ink-text)
	// label, 36px/0 12px/6px-radius box, no border. Ghost's hover state
	// (`hover:bg-accent hover:text-accent-foreground`) resolves to Lovable's own
	// sage-toned `--accent`/near-white `--accent-foreground` custom-property
	// pair (`src/index.css`) — mapped here to INK's own `accent`/`surface-alt`
	// tokens, the closest existing equivalents.
	register_block_style(
		'core/button',
		array(
			'name'         => 'ink-ghost',
			'label'        => __( 'INK skim', 'ink-foundation' ),
			'inline_style' => '.wp-block-button.is-style-ink-ghost .wp-block-button__link{'
				. 'background-color:transparent;'
				. 'color:var(--wp--preset--color--ink-text);'
				. 'font-family:var(--wp--preset--font-family--display);'
				. 'border:0;'
				. 'border-radius:var(--wp--custom--radius--md);'
				. 'transition:all .15s ease;'
				. '}'
				. '.wp-block-button.is-style-ink-ghost .wp-block-button__link:hover{'
				. 'background-color:var(--wp--preset--color--accent);'
				. 'color:var(--wp--preset--color--surface-alt);'
				. '}'
				. '.wp-block-button.is-style-ink-ghost .wp-block-button__link:focus-visible{'
				. 'outline:2px solid var(--wp--preset--color--primary);'
				. 'outline-offset:2px;'
				. '}'
				. '.wp-block-button.is-style-ink-ghost .wp-block-button__link:disabled{'
				. 'opacity:.5;'
				. '}',
		)
	);

	// is-style-ink-sage — accent (sage) fill / surface-alt text, hover ->
	// accent-light (used by the §7 borg strip CTA).
	register_block_style(
		'core/button',
		array(
			'name'         => 'ink-sage',
			'label'        => __( 'INK salie', 'ink-foundation' ),
			'inline_style' => '.wp-block-button.is-style-ink-sage .wp-block-button__link{'
				. 'background-color:var(--wp--preset--color--accent);'
				. 'color:var(--wp--preset--color--surface-alt);'
				. 'font-family:var(--wp--preset--font-family--display);'
				. 'border:0;'
				. 'border-radius:var(--wp--custom--radius--md);'
				. 'box-shadow:var(--wp--preset--shadow--md);'
				. 'transition:all .15s ease;'
				. '}'
				. '.wp-block-button.is-style-ink-sage .wp-block-button__link:hover{'
				. 'background-color:var(--wp--preset--color--accent-light);'
				. '}'
				. '.wp-block-button.is-style-ink-sage .wp-block-button__link:focus-visible{'
				. 'outline:2px solid var(--wp--preset--color--primary);'
				. 'outline-offset:2px;'
				. '}'
				. '.wp-block-button.is-style-ink-sage .wp-block-button__link:disabled{'
				. 'opacity:.5;'
				. '}',
		)
	);

	// is-style-ink-sage-outline — 2px accent border / accent text, hover -> fill
	// accent + surface-alt text.
	register_block_style(
		'core/button',
		array(
			'name'         => 'ink-sage-outline',
			'label'        => __( 'INK salie omlyn', 'ink-foundation' ),
			'inline_style' => '.wp-block-button.is-style-ink-sage-outline .wp-block-button__link{'
				. 'background-color:transparent;'
				. 'color:var(--wp--preset--color--accent);'
				. 'font-family:var(--wp--preset--font-family--display);'
				. 'border:2px solid var(--wp--preset--color--accent);'
				. 'border-radius:var(--wp--custom--radius--md);'
				. 'transition:all .15s ease;'
				. '}'
				. '.wp-block-button.is-style-ink-sage-outline .wp-block-button__link:hover{'
				. 'background-color:var(--wp--preset--color--accent);'
				. 'color:var(--wp--preset--color--surface-alt);'
				. '}'
				. '.wp-block-button.is-style-ink-sage-outline .wp-block-button__link:focus-visible{'
				. 'outline:2px solid var(--wp--preset--color--primary);'
				. 'outline-offset:2px;'
				. '}'
				. '.wp-block-button.is-style-ink-sage-outline .wp-block-button__link:disabled{'
				. 'opacity:.5;'
				. '}',
		)
	);

	// INK card (Epic 19, §0.5) — bordered, soft-shadowed surface with a
	// reduced-motion-safe hover-lift (translateY(-4px) + shadow.lg). Distinct from
	// the plain `is-style-card` (no lift). Used by the hero challenge card + the
	// featured story cards (19.3 / 19.4). Token-only (Gate A).
	register_block_style(
		'core/group',
		array(
			'name'         => 'ink-card',
			'label'        => __( 'INK-kaart', 'ink-foundation' ),
			'inline_style' => '.wp-block-group.is-style-ink-card{'
				. 'background-color:var(--wp--preset--color--surface-alt);'
				. 'border:1px solid var(--wp--preset--color--border);'
				. 'border-radius:var(--wp--custom--radius--xl);'
				. 'box-shadow:var(--wp--preset--shadow--sm);'
				. 'padding:var(--wp--preset--spacing--s-24);'
				. 'transition:transform .3s ease, box-shadow .3s ease;'
				. '}'
				. '.wp-block-group.is-style-ink-card:hover{'
				. 'transform:translateY(-4px);'
				. 'box-shadow:var(--wp--preset--shadow--lg);'
				. '}'
				. '@media (prefers-reduced-motion:reduce){'
				. '.wp-block-group.is-style-ink-card{transition:none;}'
				. '.wp-block-group.is-style-ink-card:hover{transform:none;}'
				. '}',
		)
	);

	// INK header (Epic 19, §1) — the site-wide sticky header treatment. Applied
	// to the header pattern's outer group. Header CSS CANNOT live in home.css
	// (front-page-only); the header renders on every page, so its sticky /
	// translucent-surface / backdrop-blur / bottom-border / 64px-row / nav
	// hover+underline+focus treatment ships here as a block-style inline_style
	// (loads site-wide). Token-only (Gate A); the surface/95 translucency uses
	// the color-mix convention with an opaque `surface` fallback first (§0.7).
	//
	// The translucent surface + backdrop-blur live on a `::before` decorative
	// layer, NOT on `.is-style-ink-header` itself (Phase 2 fidelity pass fix):
	// `backdrop-filter` on an element establishes a new containing block for
	// any `position:fixed` DESCENDANT (CSS Filter Effects spec, same family as
	// `transform`/`perspective`/`contain:paint`). WordPress core's mobile nav
	// overlay (`.wp-block-navigation__responsive-container.is-menu-open`) is a
	// `position:fixed` element nested inside this header, expecting to size
	// itself against the viewport — with backdrop-filter on the header, it
	// instead sized against the header's own ~64px-tall row, collapsing the
	// mobile menu to an unusable 64px-tall sliver. Confirmed via live
	// getBoundingClientRect() before/after on `nuwe-ink.local` at a 500×757
	// viewport: {width:500,height:64} with the filter present vs the correct
	// full-viewport {width:500,height:757} with it removed. Moving the same
	// filter + tint onto a `::before` (which has no fixed-position descendants
	// of its own) keeps the identical frosted-glass visual, verified pixel-
	// identical on desktop, while leaving the real header element filter-free
	// so its nav-overlay descendant is free to size against the viewport again.
	register_block_style(
		'core/group',
		array(
			'name'         => 'ink-header',
			'label'        => __( 'INK kopstuk', 'ink-foundation' ),
			// The sticky positioning lives on the semantic `<header
			// class="wp-block-template-part">` WRAPPER, not on this inner
			// `.is-style-ink-header` group (fourth-pass fidelity fix, 2026-09-05,
			// product-owner finding #10 "the Lovable header is sticky, the Ink one
			// scrolls away"). `position:sticky` only floats an element WITHIN its own
			// containing block; the inner group's containing block is the `<header>`
			// element, which is sized to exactly the same 65px as the group itself, so
			// there was zero room to stick and the whole thing scrolled away with the
			// page (confirmed live on nuwe-ink.local: at scrollY 1200 the header's
			// getBoundingClientRect().top read -1168, i.e. never stuck at all, despite
			// position/top/z-index all computing correctly). The `<header>`'s OWN parent
			// is `.wp-site-blocks`, which spans the whole document — so the same three
			// declarations, moved one level out, have the full page height to stick
			// through. Only the positioning moves; the border/tint/blur/row treatment
			// stays on the inner group so the visual is byte-identical.
			'inline_style' => 'header.wp-block-template-part{'
				. 'position:sticky;'
				. 'top:0;'
				. 'z-index:50;'
				. '}'
				// Now that the header genuinely sticks, it must clear WordPress's own
				// admin bar for signed-in users — the admin bar is `position:fixed` at
				// 32px tall above 782px, so a `top:0` sticky header slides underneath
				// it. Below 782px the admin bar is `position:absolute` (it scrolls
				// away), so the offset goes back to 0 there. Logged-out visitors are
				// unaffected: no `.admin-bar` class, no offset.
				. 'body.admin-bar header.wp-block-template-part{top:32px;}'
				. '@media screen and (max-width:782px){'
				. 'body.admin-bar header.wp-block-template-part{top:0;}'
				. '}'
				. '.wp-block-group.is-style-ink-header{'
				. 'border-bottom:1px solid var(--wp--preset--color--border);'
				. '}'
				. '.wp-block-group.is-style-ink-header::before{'
				. 'content:"";'
				. 'position:absolute;'
				. 'inset:0;'
				. 'z-index:-1;'
				. 'background-color:var(--wp--preset--color--surface);'
				. 'background-color:color-mix(in srgb, var(--wp--preset--color--surface) 95%, transparent);'
				. '-webkit-backdrop-filter:blur(4px);'
				. 'backdrop-filter:blur(4px);'
				. '}'
				. '.wp-block-group.is-style-ink-header .ink-header-row{'
				. 'min-height:64px;'
				. '}'
				// Logo hover (Lovable: `transition-transform group-hover:rotate-12`
				// on the feather icon, triggered by hovering the whole logo Link).
				// The feather icon here is a sibling of — not wrapped by — the
				// site-title's own <a>, so there is no single link to hover; target
				// the shared `.ink-header-brand` lockup group instead (hovering
				// anywhere in the lockup, icon or text, rotates the icon).
				. '.wp-block-group.is-style-ink-header .ink-header-feather{'
				. 'transition:transform .2s ease;'
				. '}'
				. '.wp-block-group.is-style-ink-header .ink-header-brand:hover .ink-header-feather{'
				. 'transform:rotate(12deg);'
				. '}'
				. '@media (prefers-reduced-motion:reduce){'
				. '.wp-block-group.is-style-ink-header .ink-header-feather{transition:none;}'
				. '}'
				. '.wp-block-group.is-style-ink-header .wp-block-navigation-item__content{'
				. 'position:relative;'
				. 'color:var(--wp--preset--color--muted-text);'
				. 'transition:color .15s ease;'
				. '}'
				. '.wp-block-group.is-style-ink-header .wp-block-navigation-item__content::after{'
				. 'content:"";'
				. 'position:absolute;'
				. 'left:0;'
				. 'bottom:-2px;'
				. 'width:0;'
				. 'height:2px;'
				. 'background-color:var(--wp--preset--color--primary);'
				. 'transition:width .3s ease;'
				. '}'
				. '.wp-block-group.is-style-ink-header .wp-block-navigation-item__content:hover,'
				. '.wp-block-group.is-style-ink-header .wp-block-navigation-item__content:focus-visible{'
				. 'color:var(--wp--preset--color--ink-text);'
				. '}'
				. '.wp-block-group.is-style-ink-header .wp-block-navigation-item__content:hover::after,'
				. '.wp-block-group.is-style-ink-header .wp-block-navigation-item__content:focus-visible::after{'
				. 'width:100%;'
				. '}'
				. '.wp-block-group.is-style-ink-header .wp-block-navigation-item__content:focus-visible{'
				. 'outline:2px solid var(--wp--preset--color--primary);'
				. 'outline-offset:2px;'
				. '}'
				. '@media (prefers-reduced-motion:reduce){'
				. '.wp-block-group.is-style-ink-header .wp-block-navigation-item__content,'
				. '.wp-block-group.is-style-ink-header .wp-block-navigation-item__content::after{transition:none;}'
				. '}'
				// Mobile menu overlay (< 600px, WP core's own breakpoint for the
				// hamburger toggle): the nav block's desktop `justifyContent:"right"`
				// (correct for the horizontal row) was also right-aligning the
				// STACKED overlay list, crowding every link flush against the
				// screen edge. WP core's own overlay padding additionally computes
				// to 0 here because it reads an unset `--wp--style--root--padding-*`
				// custom property. Override both so the open mobile menu reads as a
				// left-aligned, breathing-room list (matching Lovable's mobile nav
				// panel) instead of a right-hugging column.
				. '@media (max-width:599px){'
				. '.wp-block-group.is-style-ink-header .wp-block-navigation__responsive-container.is-menu-open{'
				. 'padding:var(--wp--preset--spacing--s-32) var(--wp--preset--spacing--s-24);'
				. '}'
				. '.wp-block-group.is-style-ink-header .wp-block-navigation__responsive-container-content,'
				. '.wp-block-group.is-style-ink-header .wp-block-navigation__responsive-container-content .wp-block-navigation__container{'
				. 'align-items:flex-start!important;'
				. '}'
				. '}',
		)
	);

	// INK footer (Epic 19, §10) — the site-wide footer treatment. Like the header,
	// the footer renders on EVERY page, so its treatment CANNOT live in home.css
	// (front-page-only): the secondary/30 band, 1px top border, 80px top margin, the
	// 4-column grid that collapses to one column < 768px, the brand row, the muted
	// link columns (hover -> text), and the bottom bar + filled-terracotta heart all
	// ship here as a block-style inline_style (loads site-wide). Token-only (Gate A);
	// the secondary/30 tint uses the color-mix convention with an opaque `secondary`
	// fallback first (§0.7).
	register_block_style(
		'core/group',
		array(
			'name'         => 'ink-footer',
			'label'        => __( 'INK voetstuk', 'ink-foundation' ),
			'inline_style' => '.wp-block-group.is-style-ink-footer{'
				. 'background-color:var(--wp--preset--color--secondary);'
				. 'background-color:color-mix(in srgb, var(--wp--preset--color--secondary) 30%, transparent);'
				. 'border-top:1px solid var(--wp--preset--color--border);'
				. 'margin-top:var(--wp--preset--spacing--s-80);'
				. '}'
				. '.wp-block-group.is-style-ink-footer .ink-footer-kolomme{'
				. 'display:grid;'
				. 'grid-template-columns:1fr;'
				. 'gap:var(--wp--preset--spacing--s-32);'
				. '}'
				. '@media (min-width:768px){'
				. '.wp-block-group.is-style-ink-footer .ink-footer-kolomme{grid-template-columns:repeat(4, 1fr);}'
				. '}'
				. '.wp-block-group.is-style-ink-footer .ink-footer-handelsmerk{'
				. 'display:flex;'
				. 'flex-direction:column;'
				. 'gap:var(--wp--preset--spacing--s-16);'
				. '}'
				. '.wp-block-group.is-style-ink-footer .ink-footer-veer{'
				. 'display:inline-flex;'
				. 'color:var(--wp--preset--color--primary);'
				. '}'
				. '.wp-block-group.is-style-ink-footer .ink-footer-blurb{'
				. 'margin:0;'
				. 'line-height:var(--wp--custom--line-height--relaxed);'
				. '}'
				. '.wp-block-group.is-style-ink-footer .ink-footer-kolom h3{'
				. 'margin:0 0 var(--wp--preset--spacing--s-16);'
				. 'font-family:var(--wp--preset--font-family--heading);'
				. '}'
				. '.wp-block-group.is-style-ink-footer .ink-footer-lys{'
				. 'list-style:none;'
				. 'margin:0;'
				. 'padding:0;'
				. 'display:flex;'
				. 'flex-direction:column;'
				. 'gap:var(--wp--preset--spacing--s-8);'
				. '}'
				. '.wp-block-group.is-style-ink-footer .ink-footer-lys a{'
				. 'color:var(--wp--preset--color--muted-text);'
				. 'font-size:var(--wp--preset--font-size--sm);'
				. 'text-decoration:none;'
				. 'transition:color .15s ease;'
				. '}'
				. '.wp-block-group.is-style-ink-footer .ink-footer-lys a:hover,'
				. '.wp-block-group.is-style-ink-footer .ink-footer-lys a:focus-visible{'
				. 'color:var(--wp--preset--color--ink-text);'
				. '}'
				. '.wp-block-group.is-style-ink-footer .ink-footer-onderbalk{'
				. 'margin-top:var(--wp--preset--spacing--s-40);'
				. 'padding-top:var(--wp--preset--spacing--s-24);'
				. 'border-top:1px solid var(--wp--preset--color--border);'
				. 'gap:var(--wp--preset--spacing--s-16);'
				. '}'
				. '.wp-block-group.is-style-ink-footer .ink-footer-hart{'
				. 'display:inline-flex;'
				. 'align-items:center;'
				. 'gap:var(--wp--preset--spacing--s-4);'
				. 'margin:0;'
				. '}'
				. '.wp-block-group.is-style-ink-footer .ink-footer-hart__ikoon{'
				. 'color:var(--wp--preset--color--primary);'
				. '}'
				. '@media (prefers-reduced-motion:reduce){'
				. '.wp-block-group.is-style-ink-footer .ink-footer-lys a{transition:none;}'
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

if ( ! function_exists( 'ink_foundation_uitdaging_cta_subtitel' ) ) {
	/**
	 * Presentation glue: the uitdaging closing-CTA subtitle ("Sluit aan by N
	 * skrywers…"), sourced from {@see \Ink\Challenges\SinglePage::ctaSubtitleHtml()}
	 * (Post-Epic-19 fidelity pass, workstream 6). Reads the round's entry count via
	 * {@see \Ink\Challenges\SinglePage::entryCount()} — no business logic in the
	 * theme (three-layer separation holds).
	 *
	 * @param int $uitdaging_id The uitdaging post id (`get_the_ID()` in the pattern).
	 * @return string Self-escaped HTML (a `<p>`), or '' when ink-core is inactive
	 *                or the round has no entries yet.
	 */
	function ink_foundation_uitdaging_cta_subtitel( int $uitdaging_id ): string {
		if ( ! class_exists( '\\Ink\\Challenges\\SinglePage' ) ) {
			return '';
		}

		return \Ink\Challenges\SinglePage::ctaSubtitleHtml(
			\Ink\Challenges\SinglePage::entryCount( $uitdaging_id )
		);
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
	 * `primary` (#EC3B13) token (NOT `danger`). Story 9.4 embeds this on the public
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
	 * colour convention (Meester → `primary` #EC3B13) and pairs colour with a real text
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
	 * renew options only for a logged-in lid, and a "Teken in om te hernieu" fallback
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

if ( ! function_exists( 'ink_foundation_icon' ) ) {
	/**
	 * Render an inline SVG icon — the shared theme icon convention (Epic 19, §0.9).
	 *
	 * The Lovable design carries a Lucide icon on nearly every button and card.
	 * The theme has no icon *system*: icons are hand-placed inline `<svg>` inside
	 * the button/card flex row (locked static patterns), or emitted by the
	 * `ink-core` block PHP in the dynamic sections. This helper standardises the
	 * markup so every hand-placed icon follows the same rules:
	 *
	 * - 16px (`size-4`), `stroke:currentColor` (Lucide icons are stroke-drawn, so
	 *   they inherit the button/link text colour), `fill:none`, viewBox `0 0 24 24`.
	 * - **Decorative by default** — `aria-hidden="true"` + `focusable="false"` so
	 *   assistive tech skips it (the adjacent visible label carries the meaning).
	 * - **Meaning-bearing icons** (a reaction count with no adjacent text, an
	 *   icon-only control) pass a non-empty `$label`; the icon then gets
	 *   `role="img"` + a `<title>` and is exposed to AT.
	 *
	 * This is presentation infrastructure — inert markup, no business logic
	 * (three-layer separation holds). `$paths` is trusted theme-authored SVG inner
	 * markup (path/circle/line elements), NOT user input.
	 *
	 * @param string $paths Inner SVG markup (e.g. Lucide `<path .../>` elements).
	 * @param string $label Accessible label; empty (default) = decorative.
	 * @return string The inline `<svg>` string.
	 */
	function ink_foundation_icon( string $paths, string $label = '' ): string {
		$open  = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="ink-icon"';
		$open .= '' !== $label
			? sprintf( ' role="img" aria-label="%1$s"><title>%1$s</title>', esc_attr( $label ) )
			: ' aria-hidden="true" focusable="false">';

		return $open . $paths . '</svg>';
	}
}

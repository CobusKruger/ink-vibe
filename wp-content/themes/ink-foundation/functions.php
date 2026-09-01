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
	$on_my_profiel   = function_exists( 'is_page' ) && is_page( 'my-profiel' );
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
		'title'   => 'QA FIXTURE — Augustus-wenneraankondiging',
		'url'     => '#qa-fixture-wenner-kollig',
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
			'inline_style' => '.wp-block-group.is-style-ink-header{'
				. 'position:sticky;'
				. 'top:0;'
				. 'z-index:50;'
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

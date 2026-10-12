# 01 · gemeenskap — primitive register (Phase 4b)

**Register:** `maps/_primitive-observations.gemeenskap.csv` — 140 rows (66 `lovable`,
50 `ink`, 24 `source`). The lead merges it into `maps/_primitive-observations.csv`.
**Captures:** `tmp/fidelity-captures/{lovable,ink}/gemeenskap@{390,768,1440}.ndjson` —
determinism 3/3 identical and `verify --sample 30` passed for all six.
**Scope:** `<main>` only. Header and footer instances are excluded (row 00).

## How the rows were produced

`run.sh primitives` was run over the six captures first (`tmp/gemeenskap/prims-raw.csv`,
285 rows). Its classifier does not fit this page well enough to register as-is:

- it does not recognise Lovable's `Button asChild` links (no `button` tag, no
  `wp-block-button__link`) — **all four Lovable buttons were missed**;
- INK eyebrows have no class (their recipe is inline block attributes), so they were
  missed; so was the INK icon tile;
- it classes the INK `div.wp-block-button` *wrapper* as the button and the real `<a>`
  as a generic `button`;
- it includes header/footer instances and a zero-height Lovable `<section>` (the toast
  live region).

So the page's register was built by `tmp/gemeenskap/build-prims.py`, which reads the same
captures, uses **the same property families and the same empty/`normal`/`none`/`auto`
filter** as `extract-primitives.mjs`, classifies with page-specific rules, excludes the
header/footer subtrees, and collapses an instance whose values are identical at several
widths into one row (`390|768|1440`). INK button rows are the rendered `<a>`, with the
kind resolved from the wrapper's class (row 00's convention). `accent-block` rows add
`border-left-width` (the only left-border property in the capture allowlist).

## Variant counts (distinct `declarations` / instances)

| kind | Lovable | INK | note |
|---|---|---|---|
| heading | 9 / 26 | 11 / 28 | INK card h2 is fluid: 20.27 / 21.71 / 24px at 390 / 768 / 1440 (Lovable fixed 24px) |
| section-band | 4 / 7 | 5 / 6 | INK value band has no vertical padding rule at all |
| eyebrow | 3 / 9 | 1 / 2 | INK eyebrow line-height 18px vs 16px; no class (inline attrs ×2) |
| card | 2 / 6 | 2 / 4 | INK adds `box-shadow` (is-style-card); bg/border tokens differ |
| button-outline | 2 / 2 | 2 / 2 | both sides have a light + an inverted variant; INK's are page-local |
| button-primary | 1 / 2 | 1 / 2 | |
| icon-tile | 1 / 2 | 1 / 2 | |
| accent-block | 1 / 4 | 1 / 4 | |
| step-badge | 1 / 6 | — | INK is `::before`, not capturable |
| avatar | 2 / 2 | — | spotlight only (missing on INK) |

## Source attribution — what defines the page's primitives

Every `.ink-gemeenskap-*` rule lives in the `theme.json` `styles.css` string
(`theme.json:162`, site-wide); block styles come from `functions.php`
(`register_block_style`, site-wide). **No page stylesheet is enqueued for
`is_page('gemeenskap')`** (`functions.php:80-952`).

### Declaration comparison — findings

| id | kind | finding |
|---|---|---|
| P1-1 | `button-outline` | Two page-local outline variants, `.ink-gemeenskap-btn-neutral` and `.ink-gemeenskap-btn-neutral-dark`, compete with the registered `is-style-ink-outline` (`functions.php:1442`). Declaration by declaration vs the registered style: border **1px `border`** vs **2px `primary`**; text **`ink-text`** vs **`primary`**; font **`body`** vs **`display`**; background `surface-alt` vs `transparent`; radius inherited from `elements.button` (6px) — same. They share nothing but radius. Lovable's own `outline` variant (1px `input` border, body font) is what the neutral class tracks, so the *registered* outline style is the one diverging from the reference here. |
| P1-2 | `button-size` | `padding:0 s-32; height:44px; font-weight:medium` is declared **byte-identically** under `.ink-gemeenskap-hero/.ink-gemeenskap-cta .wp-block-buttons .wp-block-button__link`, `.ink-uitdaging__hero/.ink-uitdaging-cta …`, `.ink-oor-ink-kontak …`, and (with display/align additions) `.ink-lidmaatskap-plans …` in the same css string, and again as `.ink-btn-lg` in `home.css:218`. Five definitions of Lovable's one `size="lg"`. |
| P1-3 | `section-band` | `.ink-gemeenskap-hiw`, `.ink-gemeenskap-principles`, `.ink-gemeenskap-cta` carry the identical body `padding-top/bottom: s-80`; `.ink-gemeenskap-hero` adds a 7rem md step. `.ink-gemeenskap-value` has **no** rule, so its computed vertical padding is 0 (Lovable `py-20` = 80px). Horizontal padding (`s-24`) is an inline attribute on all five sections, not a class. |
| P1-4 | `card` | Registered `is-style-card` (`functions.php:1356`: padding s-24) has its padding overridden by `.ink-gemeenskap-card` with `!important` (s-32 / s-40 at md). Computed padding then matches Lovable (32 / 40px) — the override is a second card-padding definition, not a fix to the first. `is-style-card` also emits `shadow.sm`, which Lovable's card does not have. |
| P1-5 | `icon-tile` / `step-badge` | `.ink-gemeenskap-card__icon` and `.ink-gemeenskap-steps li::before` repeat the same "10% primary circle + primary glyph" recipe at 40px and 32px. The step badge sets a literal `font-weight:600` where every other weight on the page uses `--wp--custom--font-weight--*`. Row 00's tiles are different classes again (home.css / ink-core). |
| P1-6 | `eyebrow` | No eyebrow class exists on this page: weight 500 / uppercase / 0.2em tracking / margin are re-typed as block attributes on each of the two instances (`gemeenskap.php:54-55`, `:196-197`). Row 00's eyebrows are three different `__boskrif`/`__etiket` classes in `home.css`. The recipe now exists in at least four places, none shared. |
| P1-7 | `heading` | Six page-scoped heading rules, most with `!important`, override preset classes: `.ink-gemeenskap-hiw h2.has-xxxl-font-size` and `.ink-gemeenskap-principles h2.has-xxl-font-size` have **identical bodies** reached from **different** presets (xxxl vs xxl) — the preset class on the block no longer predicts the rendered size. `.ink-gemeenskap-card ul li h3` sets the body font on an h3 (computes Inter; Lovable's equivalent computes Lora). |
| P1-8 | `accent-block` | `.ink-gemeenskap-principle` (2px primary left rule + padding) is a second left-rule recipe alongside the registered `is-style-emphasis` (`functions.php:1820`, left rule + tinted background). |
| P1-9 | `button-icon-label` | `.wp-block-button.ink-btn-icon .wp-block-button__link` is defined in `theme.json:162` **and** `home.css:303` (already registered by row 00). On this page only the theme.json copy loads; the home.css copy is redundant here, not inert. |

### Load-scope cross-check

**No live load-scope bug on this page.** Every class the pattern emits is defined either
in the site-wide `theme.json` css string or in a site-wide block style; none depends on a
conditionally enqueued stylesheet. All `.ink-gemeenskap-*` classes are emitted only by
`patterns/gemeenskap.php` (searched `patterns/`, `templates/`, `parts/` and
`ink-core/src/`; every hit read), so their site-wide scope is **wider** than their use —
over-broad, not inert. Stage 3 should note that about 50 page-only rules ride in a string that
loads on every page.

## Vocabulary

Added to `README.md` §4.1 under "Added during row 01": **`step-badge`** (numbered circle
in an ordered list) and **`accent-block`** (left-rule text block). Existing kinds used:
`button-primary`, `button-outline`, `button-size`, `button-icon-label`, `card`,
`icon-tile`, `eyebrow`, `heading`, `section-band`, `avatar`.

## Limits

Rendered values come from the six verified captures only. The INK step badge (`::before`)
and the inline SVG icons are not captured, so they have `source` rows but no `ink` rows.
`border-left-style/-color` are outside the capture allowlist. Nothing here judges which
variant is correct — that is Stage 2.

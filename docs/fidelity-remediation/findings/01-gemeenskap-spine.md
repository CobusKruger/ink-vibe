# 01 · gemeenskap — section spine + structural findings

| | |
|---|---|
| Page slug | `gemeenskap` (inventory row 01) |
| Reference | Lovable `/community` — `src/pages/Community.tsx` (served from an isolated build on `http://localhost:4181/community`) |
| INK | `https://nuwe-ink.local/gemeenskap/` — `templates/page-gemeenskap.html` → `patterns/gemeenskap.php` |
| Date | 2026-10-12 / 13 |
| Phase | 2 (spine), with Phase 3 join-key coverage and pointers to the Phase 4 drift |
| Treatment | **A** — reference diff (pairing ✅ in `agent-brief.md` §2.1; confirmed by reading both sides) |

Header and footer are **out of scope** here: both were verified on row 00 (`tuisblad`).
They appear below only where the page-level composition differs.

---

## 1. Files read end to end

**Lovable** (`/Users/cobus/Development/ink-lovable`)

| File | Read | Notes |
|---|---|---|
| `src/pages/Community.tsx` | full (362 lines before Phase 3) | the whole page; every section is inline — it imports no page-level components |
| `src/components/ui/button.tsx` | full | the `literary` / `outline` variants and `lg` size the page's four buttons expand to |
| `src/App.tsx` | **located only** (`:14` import, `:38` route) | confirms `/community → Community`; no claim rests on the rest of the file |
| `src/components/layout/Header.tsx` | not read | header out of scope (row 00) |

**INK** (`/Users/cobus/Development/ink-vibe/wp-content/themes/ink-foundation`)

| Link | File | Read |
|---|---|---|
| 1 · template | `templates/page-gemeenskap.html` | full (9 lines) |
| 2 · parts | `parts/header.html`, `parts/footer.html` | not read — header/footer out of scope (row 00) |
| 3 · pattern | `patterns/gemeenskap.php` | full (312 lines before Phase 3) |
| 4 · dynamic blocks | — | **none**: `gemeenskap.php` contains no `wp:ink/*` block and no `wp:pattern` reference (full read), so no ink-core render callback participates in this page's `<main>` |
| block styles | `functions.php` | `:1340-1832` (every `register_block_style` call) full; `:60-479`, `:860-1019`, `:2245-2279` (`ink_foundation_icon`) read; the remainder not read and nothing here rests on it |
| tokens + global css | `theme.json` | `:160-301` (styles / elements / blocks) read; the `styles.css` string (`:162`) extracted to `tmp/gemeenskap/theme-css.css` and every `.ink-gemeenskap-*`, `is-style-card`, `wp-block-button__link` and `ink-btn-icon` rule in it read |
| page stylesheets | `assets/css/*.css` | located only: none is enqueued for `is_page('gemeenskap')` (`functions.php:80-952`, every `wp_enqueue_style` read) |

The rendered page was fetched (`curl`, cache-busted) before and after Phase 3 to confirm
that the template applies and that the pattern's markup is what is served.

---

## 2. Section spine

| # | Lovable section (`Community.tsx`) | INK counterpart (`gemeenskap.php`) | Status |
|---|---|---|---|
| 0 | `<Header />` `:85` | `template-part header` (`page-gemeenskap.html:1`) | present — **out of scope** (row 00) |
| 1 | Hero `:87-117` | `section.ink-gemeenskap-hero` `:50-80` | present |
| 2 | Two-column value (For Writers / For Readers) `:119-170` | `section.ink-gemeenskap-value` `:82-138` | present |
| 3 | By the numbers (stats band) `:172-189` | — | **missing** (S-2, disclosed deferral) |
| 4 | This Month's Spotlight `:191-249` | — | **missing** (S-3, disclosed deferral) |
| 5 | How it works `:251-311` | `section.ink-gemeenskap-hiw` `:140-188` | present |
| 6 | Community principles `:313-331` | `section.ink-gemeenskap-principles` `:190-284` | present |
| 7 | Final CTA `:333-357` | `section.ink-gemeenskap-cta` `:286-312` | present |
| 8 | — (the page renders no `<Footer />`; `Community.tsx` imports only `Header`, `:2`) | `template-part footer` (`page-gemeenskap.html:9`) | **extra** (S-4) |

Order of the five shared sections is identical.

---

## 3. Structural findings

| id | class | finding | Lovable | INK |
|---|---|---|---|---|
| S-1 | `MECHANISM` | Section width/padding model. Every Lovable section is either a `.container mx-auto px-4` section or a full-bleed section whose **child** `.container` carries the padding, with a `max-w-*` box inside. INK sections are `alignfull` groups carrying their own inline horizontal padding (`s-24`), with WP *constrained* layout capping each **child** at `contentSize`/`wideSize` instead of a single max-width box. One fewer wrapper per section; the Lovable `.container` node has no INK node to bear an id. | `:88-90`, `:120-121`, `:252-255`, `:314-315`, `:334-335` | `:50-53`, `:82-85`, `:140-143`, `:190-193`, `:286-289` |
| S-2 | `SCOPE` | Stats band ("By the numbers", 4 figures) absent. Disclosed in the pattern docblock as **DEFERRED** pending a live-data ink-core block — explicitly "a genuine open feature-vs-style scope item … not a closed one". Owner decision. | `:172-189` | `gemeenskap.php:10-26` (disclosure) |
| S-3 | `SCOPE` | Spotlight section (featured writer card-link + featured reader card) absent. Same disclosure as S-2. | `:191-249` | `gemeenskap.php:10-26` (disclosure) |
| S-4 | `SCOPE` | INK renders the site footer on this page; the Lovable page renders none. Site-wide footer is an INK choice, covered by row 00. | `:2` (imports), `:83-359` | `page-gemeenskap.html:9` |
| S-5 | `SCOPE` | INK wraps the page in a `<main>` landmark (`group tagName:main`); Lovable has a plain `div.min-h-screen`. | `:84` | `page-gemeenskap.html:3-6` |
| S-6 | `STRUCTURE` | Eyebrows are `inline-block <span>` in Lovable and block `<p>` paragraphs in INK (twice on the page). The INK eyebrow also has no class of its own — its recipe is re-typed as block attributes on each instance. | `:91`, `:317` (and `:195`, spotlight) | `:54-55`, `:196-197` |
| S-7 | `MECHANISM` | Buttons: Lovable `Button asChild` merges into a single `<a>`; INK `core/button` wraps the `<a>` in a `div.wp-block-button` that carries the variant class. | `:102-113`, `:341-354` | `:68-73`, `:300-305` |
| S-8 | `SCOPE` | Button destinations differ: hero `/write` + `/browse` → INK `/registreer` + `/registreer`; CTA `/write` + `/browse` → INK `/registreer` + `/lees`. INK routes; not a visual finding — recorded so it is not "fixed" back. | `:103`, `:109`, `:342`, `:353` | `:69`, `:73`, `:301`, `:305` |
| S-9 | `STRUCTURE` | Hero secondary button: Lovable uses the shared `outline` variant (`button.tsx:14`); INK uses a page-local class `ink-gemeenskap-btn-neutral` rather than any registered button style. CTA secondary likewise uses page-local `ink-gemeenskap-btn-neutral-dark` (Lovable: `outline` + className overrides). See the primitives finding. | `:108-113`, `:347-354` | `:72-73`, `:304-305` |
| S-10 | `MECHANISM` | Value and how-it-works grids: Lovable CSS `grid md:grid-cols-2`; INK `core/columns` (flex) with a `core/column` wrapper per cell (`gemeenskap-value-col`, INK-only). `core/columns` stacks below WP's own breakpoint, not Tailwind's `md` — see the 768 drift. | `:121`, `:262` | `:84-87`, `:111-112`, `:156-159`, `:171-172` |
| S-11 | `STRUCTURE` | Card icon tile is a `<div>` in Lovable, a `<span>` in INK. | `:125` | `:91`, `:116` |
| S-12 | `MECHANISM` | How-it-works step badge: a DOM node (`div.w-8.h-8.rounded-full`, flex sibling of the text) in Lovable; a CSS counter on `.ink-gemeenskap-steps li::before` (absolutely positioned in a 48px left padding) in INK. The pseudo-element can carry no id and is invisible to the capture. | `:273-276` | `:162-166`; css `theme.json:162` (`.ink-gemeenskap-steps li::before`) |
| S-13 | `STRUCTURE` | Step title/description tags: `<div>` + `<p>` in Lovable, `<strong>` + `<span>` (both `display:block`) in INK. | `:278-279` | `:163-165`, `:176-178` |
| S-14 | `STRUCTURE` | Principles grid: one 4-cell `grid md:grid-cols-2` in Lovable; in INK a group holding **two** separate 2-column `core/columns` rows, each cell wrapped in a `core/column`. | `:322-329` | `:206-280` |
| S-15 | `STRUCTURE` | CTA width: in Lovable only the paragraph is narrowed (`max-w-xl`); the h2 and button row span the container. In INK the constrained group (`contentSize 36rem`) caps **every** child, h2 and buttons included. | `:335-340` | `:288-299` |
| S-16 | `MISSING` | Benefit-list icons exist on both sides, but the INK icon (inline SVG from `ink_foundation_icon()`) cannot take an id without changing the helper call — a **coverage gap**, not a missing element. Recorded so the gap is visible. | `:136` | `:100` (`functions.php:2271`) |

### Notes on classification

- **S-2 / S-3 are `SCOPE`, not `MISSING` and not `DIVERGENCE (accepted)`.** A disclosure
  exists (R6), but it explicitly calls the item open and deferred rather than accepted.
  The owner decides whether they are built; nothing here changes that.
- **S-1, S-7, S-10, S-12 are `MECHANISM`.** Each is the WordPress block-editor's way of
  expressing the same intent. They matter only through their computed output, which the
  Phase 4 drift measures — and in S-10 and S-15 that output *does* diverge (stacking
  breakpoint; constrained widths), so those are carried forward as drift, not here.
- **Background bands are not raised here.** The INK pattern sets `backgroundColor`
  attributes on different sections than Lovable paints `bg-cream/40` on, but a colour
  claim is a computed-value claim (R4). It appears in the drift (§5 below).
- **No disclosure comment** other than the docblock `:10-45` exists in `gemeenskap.php`
  (full read). The docblock's description of the 2026-09 re-audit (centred hero, eyebrow
  colour, numbered step badges, left-rule principles, dark CTA) is history, not a
  divergence disclosure.

---

## 4. Join-key coverage

Phase 3 kept every pre-existing id (none renamed) and added the rest Lovable-first.

| | Lovable (`Community.tsx`) | INK (`gemeenskap.php`) |
|---|---|---|
| ids before Phase 3 | 19 | 19 |
| ids after Phase 3 | **67** | **49** |
| paired (`maps/gemeenskap.csv`) | 48 | 48 |
| reference-only | 19 — stats (5) + spotlight (12) [S-2, S-3]; `card-writers-item-icon` [S-16]; `hiw-step-badge` [S-12] | — |
| INK-only | — | 1 — `gemeenskap-value-col` (core/column wrapper, S-10) |

One pre-existing mismatch was resolved without renaming: INK already had
`gemeenskap-hiw-step-li` (on the `<li>`) where Lovable had `gemeenskap-hiw-step-badge`
(on the badge `<div>`). Both are kept; the Lovable `<li>` now also carries
`gemeenskap-hiw-step-li`, and `-step-badge` stays reference-only (S-12).

Repeaters carry the id on the first instance only (benefit item, step, principle,
stat, spotlight card). The readers card carries its root id only (its inner structure is
the writers card's).

The 63 ids paired by the capture tool = these 48 + 15 row-00 header ids (header verified
on row 00; not re-reported).

---

## 5. Conclusion and limits

**What the divergence consists of.** Structurally the page is close: five of seven
Lovable sections are present in the same order, and the two that are not (stats,
spotlight) are a disclosed, open scope decision. Within the shared sections the
differences are mostly the block editor's mechanisms (S-1, S-7, S-10, S-12) plus a few
tag swaps (S-6, S-11, S-13) and two layout shapes that actually change the rendering
(S-14, S-15). The **visible** divergence is in the computed values — see the drift
documents:

- `findings/01-gemeenskap-drift.md` (1440), `-drift@768.md`, `-drift@390.md` — generated,
  both gates passed for every side × viewport (determinism 3/3 identical; verify 30-node
  sample, 0 mismatches).
- Headline drift at 1440: inner content columns capped at 768px (how-it-works,
  principles) and 576px (CTA) where Lovable runs 1024px / 1368px (S-10/S-15 made
  measurable); background bands inverted (Lovable tints hero + how-it-works; INK tints
  value + principles); value section has 0 vertical padding on INK (no
  `.ink-gemeenskap-value` rule) vs 80px; INK cards add a shadow; benefit titles compute
  Inter on INK vs Lora on Lovable. At 768 INK's `core/columns` have already stacked
  (cards 720px wide, value section 1198 vs 782px tall) while Lovable is two-column.

**What this document cannot support.** It makes **no** claim about any computed value —
size, colour, spacing, weight, radius, shadow (R4). Every such statement above is a
pointer to the generated drift files, not evidence in its own right. Presence/absence
claims rest only on the files listed in §1 as read in full; the header/footer parts and
`Header.tsx` were deliberately not read and nothing here rests on them. The INK icon SVGs
and the `::before` step badge are not in the capture, so their rendered values are
unmeasured (S-12, S-16).

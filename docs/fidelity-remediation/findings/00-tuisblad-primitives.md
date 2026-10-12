# 00 — Tuisblad · Phase 4b primitive register

**Page:** `tuisblad` (row 00, owns header + footer) · **INK:** `https://nuwe-ink.local/` ·
**Lovable:** `http://localhost:4173/` · **Date:** 2026-10-12 · **Phase:** 4b · **Stage:** 1
(record only — no CSS, block style, pattern or harness file was changed)

**Outputs**

- `maps/_primitive-observations.tuisblad.csv` — this page's rows
- `maps/_primitive-observations.csv` — the merged register (header + this page; first page)

Record, do not judge: nothing below says which variant is right. That is Stage 2.

---

## 1. Inputs and files read

**Captures** (gate-verified today, deployed code = branch): `tmp/fidelity-captures/{ink,lovable}/tuisblad@{390,768,1440}.ndjson`,
fed through `tools/fidelity/run.sh primitives` (330 raw rows → `tmp/primitives/raw.csv`).
Join table: `maps/tuisblad.csv`.

**Read end to end:** `assets/css/home.css` (1–1438); `patterns/header-main.php`, `hero.php`,
`cta-band.php`, `borg-strook.php`, `footer-main.php`, `featured-grid.php`,
`archetype-a-editorial-landing.php`; `templates/front-page.html`;
`tools/fidelity/extract-primitives.mjs`, `run.sh`.

**Read in the relevant span:** `functions.php` 1–230 (every page-gated enqueue), 440–480,
865–1160 (home enqueue 935–952), 1330–1832 (all `register_block_style` calls);
`theme.json` 1–245 (palette, font sizes, `styles.elements`, `styles.blocks`);
`ink-core/src/Challenges/CurrentChallenge.php` 290–460, `FeaturedWinners.php` 230–379,
`Discovery/FeaturedStream.php` 280–460, `Sponsors/HomepageStrip.php` 150–237; the
button lines of `gemeenskap.php` 66–74 / 298–302, `reading-uitdaging.php` 90–98 / 144–152,
`skryf.php` 72–80, `my-profiel.php` 169–173; Lovable `components/ui/button.tsx`,
`components/home/CallToAction.tsx`.

**Parsed, not sampled (R1):** `theme.json` → `styles.css` (100,674 chars, a single line at
`theme.json:162`) and every `assets/css/*.css`, split into rules by
`tmp/primitives/cssrules.py`. Every hit used below was then read in place. The parser
reached every rule in every theme stylesheet, so "no theme rule targets X" claims (e.g. the
nav toggle) rest on that full parse. They do not rest on a grep.

`ink-core` ships no stylesheet and makes no `wp_enqueue_style` call. A file enumeration
found no `.css` under `ink-core`. Every class an `ink-core` block emits is therefore styled
by the theme.

---

## 2. Register shape: side, viewport and source rows

README §4.1 lists 9 columns. The tool emits 11 (`page,side,viewport,…`). **The register
uses the tool's 11-column header.** The reasons:

- **Both sides are kept.** Stage 2 step 4 prefers "the variant backed by a Lovable reference
  measurement". That requires the Lovable value for the same `audit_id` in the same
  register. Attribution columns are filled on INK rows only. Lovable rows leave them empty
  and carry the Lovable component/variant in `note` where it matters (`button.tsx` variant,
  file:line).
- **All three viewports are kept.** Responsive steps are part of a recipe, e.g. card padding
  s-24→s-32 at 768 and heading size steps. To keep the file readable, an instance whose
  declarations are identical at several viewports is **one row with
  `viewport = 390|768|1440`**. Where the declarations differ, it gets one row per distinct
  set (`390`, `768|1440`, …).
- **`side = source` rows hold the attribution.** There is one row per CSS definition, so
  multiple definitions mean multiple rows, as Phase 4b asks. `selector` is the selector as
  written in source, `defined_in` is one `file:line`, `load_scope` is that definition's
  gate, `used_in` lists every emitter that was read and confirmed, `declarations` is the
  **source** body, and `audit_id` names the tuisblad instances it reaches. Each measured INK
  row carries every definition that reaches it, compressed per file
  (`functions.php:1413,1422,…`), together with its scopes and emitter.
- Inline `style=""` attributes and WP preset classes (`has-*-font-size`,
  `has-secondary-background-color`) are recorded as definitions. They set primitive values
  and, in one case, override a block style (P00-1).

**Recommendation for the lead:** amend README §4.1's column list to the 11-column header and
document the `side=source` and `viewport` list conventions before the fan-out. Following its
own instruction, this pass edited only the vocabulary list in the README.

### Row counts

| | INK | Lovable | source |
|---|---|---|---|
| measured instance-rows before collapsing (3 viewports) | 288 (96/vp) | 276 (92/vp) | — |
| rows in the register after collapsing viewports | 115 | 107 | 143 |

**Total: 365 data rows.**

The register also contains 276 instance-rows that the tool did not classify and that were
added from the capture using the tool's own property lists: 135 INK and 141 Lovable, before
collapsing. **42 tool rows were dropped**, each logged in `tmp/primitives/dropped.txt`:

- 18 `.wp-block-button` wrapper `<div>`s. The recipe targets the child link, and the wrapper
  class is quoted in the link row's note.
- 12 `.ink-uitgesoekte-bydraes__kaart-inhoud` card bodies.
- 3 `__kenteken-teks` text spans.
- 6 Lovable decorative `rounded-full` circles.
- 3 Lovable 0-px toast `<section>`s.

Kinds per instance (1440, INK / Lovable): nav-link 18/17 · heading 14/14 · section-band
10/8 · card 7/7 · meta-item 6/6 · tier-chip 6/6 · button-primary 5/5 · avatar 5/5 ·
meta-row 5/5 · eyebrow 4/4 · pill 4/4 · button-outline 3/3 · badge 2/2 · icon-tile 2/2 ·
button-icon 2/1 · button-ghost 1/1 · button-sage-outline 1/1 · link-underline 1/1.
`button-size` and `button-icon-label` exist only as source rows (8 and 2 definitions).

**Distinct INK selectors (DOM class-list form): 55.** 53 are attributed to theme sources.
2 have no theme source: the core navigation toggle,
`.wp-block-navigation__responsive-container-open` and `-close`, which only WP core's
block-library CSS styles.

---

## 3. Variants per kind (computed `declarations`, N distinct across M instances)

| kind | INK @390 | @768 | @1440 | Lovable @1440 | base source definitions |
|---|---|---|---|---|---|
| section-band | 9/10 | 9/10 | 9/10 | 7/8 | 7 band recipes + 6 inline paddings |
| heading | 6/14 | 6/14 | 7/14 | 7/14 | 3 element + 2 preset + 9 class rules |
| button-primary | 4/5 | 4/5 | 4/5 | 4/5 | 4 (`theme.json:214`, `functions.php:1413`, `home.css:405`, `home.css:1380`) |
| card | 3/7 | 4/7 | 4/7 | 5/7 | 4 applied + 2 registered-unused |
| eyebrow | 4/4 | 4/4 | 4/4 | 4/4 | 4 |
| button-outline | 3/3 | 3/3 | 3/3 | 3/3 | 3 |
| meta-row | 3/5 | 3/5 | 3/5 | 2/5 | 3 |
| tier-chip | 3/6 | 3/6 | 3/6 | 3/6 | 1 base + 3 tier + `ink-hover-scale` |
| meta-item | 2/6 | 2/6 | 2/6 | 2/6 | 4 rule sets |
| pill / badge / avatar / icon-tile | 2/4 · 2/2 · 2/5 · 2/2 | = | = | 1/4 · 2/2 · 2/5 · 2/2 | 1 · 2 · 2 · 2 |
| nav-link | 2/18 | 2/18 | 2/18 | 2/17 | 2 (header, footer) |
| button-size | — | — | — | — | 5 mechanisms, 8 rules (P00-5) |

---

## 4. PRIMITIVE findings

Each cites the definitions on both sides. "≡" means declaration-identical.

| id | finding | definitions |
|---|---|---|
| **P00-1** | **Footer band defined twice; the block style's tint does not apply.** The block style declares `secondary` mixed to 30%. The markup also carries `has-secondary-background-color` (from `backgroundColor:"secondary"`). The captured background is `rgb(237,233,224)` = `secondary #EDE9E0` at 100%. | `functions.php:1744-1749` vs `patterns/footer-main.php:22-23` → preset `theme.json:11` |
| **P00-2** | **Primary button: 4 base definitions.** The block style and `__aksie` share bg/colour/font/radius/shadow. They differ in `transition` (`all` vs `background-color`), and `__aksie` adds weight 500 + its own size. The block style declares no weight: header-join and hero-primary compute 400, the `__aksie` buttons compute 500. The cta button is an inverse colourway (surface-alt fill, primary text, radius lg, no shadow, hover `opacity:.9` instead of a colour change). | `theme.json:214` · `functions.php:1413-1431` · `home.css:405-438` · `home.css:1367,1380-1389` |
| **P00-3** | **Outline button: 3 definitions.** `ink-outline`: 2px primary, primary text, md radius. `__skakel`: 1px gold-muted/30, ink-text, weight 500, md. `--sekonder`: 2px surface-alt, surface-alt text, lg radius. | `functions.php:1442-1460` · `home.css:753-785` · `home.css:1391-1401` |
| **P00-4** | **Sage-outline replicated.** The colours, border, radius, font, transition, hover and focus are ≡. The replica adds layout, size, weight 500 and `text-decoration`. Disclosed at `home.css:1241-1242`. | `functions.php:1538-1556` vs `home.css:1243-1268` |
| **P00-5** | **Button size: 5 mechanisms.** (a) Inline padding s-8/s-12 + `has-sm-font-size`, with no height. (b) `.ink-btn-lg`: `height:44px`, padding 0/s-32, md. (c) `.ink-cta-band__knoppie`: `min-height:48px`, padding s-8/s-40, lg, radius lg. (d) `__aksie` ≡ `__skakel` size: `min-height:40px`, s-8/s-16, sm. (e) `__cta`: `min-height:44px`, s-8/s-32, md. (b)/(e) and (c)/`.ink-btn-xl` differ only in fixed `height` vs `min-height` and in vertical padding 0 vs s-8. `.ink-btn-sm` and `.ink-btn-xl`: grep located no consumer (locator only, R1). | `header-main.php:89,93` · `home.css:196,218` · `home.css:1367` vs `home.css:225` · `home.css:405` ≡ `home.css:753` (size) · `home.css:1243` |
| **P00-6** | **Two utility recipes duplicated site-wide and front-page-only.** `.ink-btn-icon` link layout ≡ and `.ink-icon` box ≡. | `theme.json:162` ≡ `home.css:303`; `theme.json:162` ≡ `home.css:34` |
| **P00-7** | **The `is-style-ink-card` recipe exists 3 times (PRIM-4 still holds).** bg, border, radius xl, shadow sm, padding s-24, transition and hover-lift are ≡ across all three. `--kompak` adds s-32 at ≥768. Two further card recipes: `--kenmerk` (radius xxl, shadow md, s-32, no lift) and the winner card (radius xxl, s-32, 1px gold-muted/30, gradient, no shadow, flex gap s-16). `is-style-card` (radius **lg**) is registered but applied to no tuisblad instance. | `functions.php:1569-1585` ≡ `home.css:441-453` ≡ `home.css:878-890`; `home.css:530`; `home.css:611,623`; `functions.php:1356` |
| **P00-8** | **Eyebrow: 4 definitions.** `__boskrif` ≡ `__etiket` except colour (primary vs accent). The challenge eyebrow has no uppercase/tracking. The winner eyebrow uses heading font + semibold + gold. `letter-spacing:0.08em` is a literal in two rules. | `home.css:815` · `home.css:1114` · `home.css:555` · `home.css:664` |
| **P00-9** | **Badge vs pill box.** The two badges share radius full + the primary/10 ground. They differ in padding (s-6/s-16 vs s-4/s-12), size (sm vs xs), weight (500 vs 600), case/tracking and display. The `__pil` box ≡ the `__kenteken` box (s-4/s-12, radius full, xs) but differs in weight and colour. The per-type pill colour is **DIVERGENCE (accepted)**, disclosed at `home.css:940-955`. | `home.css:286` · `home.css:361` · `home.css:929-973` |
| **P00-10** | **Icon tile box duplicated.** Box ≡ (inline-flex, padding s-8, radius lg). They differ only in tint and colour (primary/10 vs gold/20). | `home.css:544` vs `home.css:682` |
| **P00-11** | **Avatar: 2 definitions.** One is s-48 (token) + a 2px gold-muted/30 ring. The other is `32px` literal, no ring. | `home.css:728` vs `home.css:1048` |
| **P00-12** | **Meta item: 4 rule sets for "icon + muted small text".** All are inline-flex, gap s-4, muted. Sizes are xs, sm, xs, inherited sm. | `home.css:376-386` · `home.css:575` · `home.css:975` · `home.css:1060-1072` |
| **P00-13** | **Headings.** cta-h2 and the footer h3s compute weight **700**. None of their definitions declares a weight, so the UA default applies. Every other tuisblad heading declares 600 (inline or `--font-weight--semibold`). The font-family token splits between `display` (featured h2/h3) and `heading` (the rest), though both resolve to Lora. One preset, `hero`, is stepped for hero-h1 (`!important` overrides) and left fluid for cta-h2. | `theme.json:168-187`, `theme.json:120`, `home.css:1353`, `functions.php:1771` vs `hero.php:36`, `home.css:388,693,824,988,1123`; `home.css:262-276` |
| **P00-14** | **Section band.** The tint varies: feature band secondary/30, borg band secondary/20, footer (P00-1). Band padding is declared inline on 5 bands, but in CSS on one. The header's tint lives on `::before`, so the capture reads it as transparent (MECHANISM). | `home.css:509`, `home.css:1090`; `hero.php:26`, `front-page.html:10`, `borg-strook.php:24`, `cta-band.php:23`, `footer-main.php:23` vs `home.css:793`; `functions.php:1651` |
| **P00-15** | **Link-underline recipe defined twice, ≡.** | `functions.php:1684,1698` ≡ `home.css:110,121` |
| **P00-16** | **Tier chip.** Two `:hover` scale rules hit one element (both `scale(1.05)`, both `.15s`). The chip uses token `goud` (#C9B88A, the same value as `gold-muted`), while the winner card uses `gold` (#E8B130), so two gold token names exist. | `home.css:1178` + `home.css:134`; `home.css:1193` vs `home.css:627,672` |
| **P00-17** | **Nav link: header and footer defined separately.** Both use muted colour with ink-text on hover. | `functions.php:1679,1694` + `theme.json:238` vs `functions.php:1783,1789` |

---

## 5. Load-scope cross-check

Every tuisblad primitive resolves to one of these scopes:

- `site-wide (block style)`: `functions.php`
- `site-wide (theme.json)`
- `site-wide (preset class)`
- `inline style attr`
- `is_front_page() || is_page('qa-bloks')`: `home.css`, gated at `functions.php:935-952`

**No live load-scope bug was found on this page.** Every class emitted on tuisblad is styled
by a sheet that loads on the front page. For each `home.css`-only class, the emitters located
by grep are the front page and `qa-bloks` only (the locations are listed in each source row's
`used_in`).

**PRIM-1 status (from `01-primitives-audit.md` §3): the inert-class case no longer reproduces
at the sites it cited.** Grep locates `.ink-btn-lg` only in `hero.php:45,49`. Reading
`gemeenskap.php:68-73,300-301` and `reading-uitdaging.php:92-97,146-151` shows `ink-btn-icon`
without `ink-btn-lg`. `.ink-btn-icon` now has a site-wide copy at `theme.json:162`, so it
works there, though as a duplicate (P00-6). Sizing on those pages has moved to page-context
selectors in `theme.json:162` (`.ink-gemeenskap-hero/-cta`, `.ink-uitdaging__hero`/
`.ink-uitdaging-cta`, `.ink-lidmaatskap-plans`, `.ink-oor-ink-kontak`: `height:44px`,
padding 0/s-32). Those pages will register them. The primitive itself is still defined only
in a page-scoped sheet (`home.css:218`).

**Latent (flagged in `note` as `LATENT:`), not currently live:**

- **L-1:** `patterns/archetype-a-editorial-landing.php:6,10-16` declares
  `Block Types: core/post-content`, so WordPress offers it as a starter for **any** new page.
  It composes `hero`, `featured-grid` and `cta-band`. On any page other than the front page
  or `qa-bloks`, `home.css` does not load. The hero texture, badge, `.ink-btn-lg`, h1 steps,
  the cta band and its buttons, the whole featured stream, `.ink-underline-slide` and the
  compact challenge card would all be inert there.
- **L-2:** The four `ink-core` dynamic blocks are registered globally:
  `ink/huidige-uitdaging` `CurrentChallenge.php:48`, `ink/wenner-kollig` `FeaturedWinners.php:42`,
  `ink/uitgesoekte-bydraes` `FeaturedStream.php:55` and `ink/borg-strook` `HomepageStrip.php:44`.
  All of their CSS is in `home.css`. Inserted on any other page, they render unstyled.

Both are latent only as far as the repo shows. Page content stored in the database was not
inspected (`wp-cli` cannot reach the Local DB, README §9). A page built from archetype A
would make L-1 live.

**Nuance:** Phase 4b treats `functions.php` block-style `inline_style` as site-wide. In a
block theme, WordPress attaches that CSS to the block's own stylesheet handle, so it prints
on pages that render that block type. Header and footer put `core/group` and `core/button`
on every page, so this changes nothing here. It could matter for a page with no
`core/button`.

---

## 6. Harness gaps found (E10, for the worklist; harness not changed)

- **Unclassified kinds.** The classifier misses `<a>` buttons on both sides (Lovable
  `<Button asChild>`, INK `__aksie`/`__skakel`/`__cta`), nav and footer links, sponsor
  chips, meta rows and items, icon tiles, the Lovable winner card and cta-band, and the
  header and footer bands. All of these were added by hand from the capture.
- **Misfires.** `<section>` → section-band claims two cards. `-kaart\b` matches
  `__kaart-inhoud`. `__etiket` → badge claims an eyebrow. `rounded-full` → pill claims
  avatars, decorative circles and badges. Sonner's toast `<section>` is emitted. Wrapper
  `<div class="wp-block-button is-style-*">` rows are emitted as button variants, although
  the recipe targets their child link.
- **Property lists.** The button family has no `height`/`min-height`, so the P00-5 size
  differences are not visible in `declarations`. The avatar (surface) family has no
  `width`/`height`. When a row is reclassified across families, its declarations must be
  recomputed; this was done for `sponsors-eyebrow`.

---

## 7. Vocabulary added to README §4.1

The list gained the following kinds:

- `heading` and `search-button`: the tool already emits both.
- `button-sage-outline`
- `button-icon-label`: the `.ink-btn-icon` modifier, not an icon-only button.
- `icon-tile`
- `meta-item`
- `nav-link`
- `tier-chip`

The list also now notes that the tool's generic `button` fallback must be resolved to a
variant.

## 8. Limits

- This document makes no claim about which variant is correct. Computed-value comparisons
  against Lovable are in `00-tuisblad-drift*.md`, not here.
- "No consumer" statements for `.ink-btn-sm`/`-xl` and for archetype-a placement rest on
  grep (locator only, R1).
- Database-stored page content was not inspected (see §5).

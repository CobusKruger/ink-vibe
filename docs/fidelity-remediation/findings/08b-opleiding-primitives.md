# 08b · opleiding — primitive register (Phase 4b)

**Register:** `maps/_primitive-observations.opleiding.csv` (52 rows: 16 `ink`, 36 `source`)
**Captures:** `tmp/fidelity-captures/ink/opleiding@{390,768,1440}.ndjson` against
`lovable/library@…`. All passed determinism (3/3) and `verify --sample 30` (0 mismatches).
**Tool:** `run.sh primitives --exclude-chrome`. Viewport-identical instances are collapsed with `|`.

**Lovable rows are not repeated here.** The shared `/library` reference is registered once, in
`_primitive-observations.biblioteek.csv` (row 08a), so Stage 2 does not count its instances twice.

## 1. What the register holds

| side | kinds (rows) |
|---|---|
| ink | filter-chip 4 · heading 4 · section-band 2 · search-field 2 · eyebrow 1 · search-button 1 · button-primary 1 · card 1 |
| source | heading 7 · button-outline 6 · card 5 · filter-chip 4 · search-button 3 · button-primary 3 · search-field 2 · meta-item 2 · section-band 2 · eyebrow 1 · pill 1 |

**Resolution by hand:**
- One generic `button` came out of the tool: the CTA `<a class="ink-opleiding-bydra__knoppie">`. It is
  resolved to `button-primary` (primary fill, `shadow.md`, no block style).
- The INK empty-state box was added as `card` (noted as content-less).

As on 08a, the card, pill, meta-item, card-title, shelf and result-count primitives are
**not rendered live**. All published `opleiding_artikel` posts are `QA FIXTURE — ` titled and are
excluded by `Hub::runQuery()`. They appear only as `source` rows.

## 2. Source attribution

Every `.ink-opleiding*` and `.ink-opleiding-bydra*` primitive is defined **once**, in `theme.json`
→ `styles.css`:
- no `assets/css/*.css` hit and no block-style `inline_style` defines any of them;
- `functions.php` enqueues nothing for this page.

`used_in`:
- `ink-core/src/Training/Hub.php`, with filter chips and pager through `Kernel/ArchiveRender.php`;
- `ink-core/src/Training/ContributionCta.php` (the CTA);
- the pattern shell's inline padding (`patterns/opleiding.php:22-23`).

## 3. Load-scope cross-check

All definitions are **site-wide**. The consumers render on `/opleiding/` (and on `/qa-bloks/` for
the hub), so **no inert class and no live load-scope bug**. `.ink-icon` has three definitions
(`theme.json`, `home.css`, `skryf.css`); only the `theme.json` one reaches this page (recorded on 08a).

## 4. Declaration comparison

The full biblioteek ↔ opleiding comparison is in `08a-biblioteek-primitives.md` §4, which covers the
same two rule sets. The headline is five identical duplicated rule sets plus eight divergent kinds.
Specific to this page:

| kind | definition | compared with | difference |
|---|---|---|---|
| search-button | `.ink-opleiding__soek-knoppie` | `.ink-biblioteek__soek-knoppie` | no `font-family` → **renders in UA Arial 13.33px** (computed `font-family: Arial` at all three widths) |
| button-primary | `.ink-opleiding-bydra__knoppie` | registered `is-style-ink-primary` (`functions.php:1445-1452`) | restates fill, radius, `shadow.md` and colour, but **not** `font-family:display`. Renders Inter; Lovable's `literary` button renders Lora. A further definition of `button-primary` outside the block-style recipe |
| section-band | `.ink-opleiding-bydra` (`<aside>`: padding `s-64 s-24`, `border-top`, `margin-top:s-64`) | Lovable `.border-t.bg-cream/40` band | page-local band recipe. No tint, not full-bleed (spine O-17) |
| eyebrow | `.ink-opleiding__eyebrow` (heading family, italic, `md`) | Lovable eyebrow span (14px, `0.35px` tracking) | renders 16px with no tracking. This is the only eyebrow definition for this page's shape |
| heading (CTA h2) | `.ink-opleiding-bydra__titel` (`xxxxl`, regular, `line-height:1.111`) | Lovable `text-3xl md:text-4xl` | one size at all widths (36px), where Lovable has 30/36 |

## 5. PRIMITIVE findings

| id | kind | finding |
|---|---|---|
| PRIM-08-1 … 5 | (see 08a) | Shared with row 08a: duplicated and divergent `.ink-biblioteek__*` / `.ink-opleiding__*` rule sets. |
| PRIM-08-6 | button-primary | `.ink-opleiding-bydra__knoppie` is a bespoke primary-button definition in `theme.json`, beside the registered `is-style-ink-primary` block style. It omits that style's display font. |
| PRIM-08-7 | search-button | Opleiding's search submit has no `font-family`, so it falls back to the UA default (Arial) on the live page. The shared component carries the defect; it is recorded here and not fixed (R8). |

Header and footer primitives are row 00's and are excluded. No CSS was changed (R8).

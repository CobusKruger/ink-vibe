# 18 · ledegids — primitive register (Phase 4b)

**Page:** `https://nuwe-ink.local/ledegids/` · **Treatment:** C · **Date:** 2026-10-13 · **Stage 1**
**Register:** `maps/_primitive-observations.ledegids.csv` (28 `ink` rows, 0 `source` rows)
**Captures:** `tmp/fidelity-captures/ink/ledegids@{390,768,1440}.ndjson` — determinism gate 3/3 identical and
`verify --sample 30` 0 mismatches at every width. No duplicate audit-ids.

Header and footer were verified on row 00 (`--exclude-chrome`) and are not re-reported.

## Files read end to end

- `templates/page-ledegids.html`, `patterns/ledegids.php`
- `plugins/ink-core/src/Discovery/SkrywersTab.php` (`ink/ontdek-skrywers`, shared with `/ontdek` row 03)
- `functions.php` (all of it)
- `theme.json` `styles.css`, parsed rule by rule (#223-#226 and #245-#273 are the SkrywersTab rules)

## How the raw output was resolved

| Raw tool row | Resolution |
|---|---|
| `filter-chip` `.ink-ontdek-skrywers__filter-knoppie` ×4 | genre chips (1 active + 3) |
| `filter-chip` `.ink-ontdek-skrywers__sorteer-knoppie` ×2 | note `sort chip` (row 03 convention) |
| `section-band` `writers-archive` | **dropped**: ink-core block-root `<section>` |
| `writers-card-name` (`<a>`) | **added by hand** as `heading` (row 03 convention: card title as `<a>`). One row covers 3 identical instances |
| `card` / `avatar` / `tier-label` / `pill` | 3 writers render, so 3 cards, 3 avatars, 3 grade labels and 4 genre pills |

**Source rows.** Every SkrywersTab definition (#223, #248/#250, #251/#253, #259, #265, #267, #269, #271) is
already a `source` row in `_primitive-observations.ontdek.csv`. They are not duplicated here, so Stage 2 counts each
once. This page adds no definition of its own: `ledegids.php` uses only block attributes.

## Declaration comparison

- **card.** `theme.json` #223 (`.ink-ontdek-skrywers__item.is-style-card` and four sibling selectors) restates
  the registered `functions.php:1431` card recipe word for word: surface-alt, 1px border, radius lg, shadow sm,
  padding s-24. It does so because the registered `.wp-block-group.is-style-card` cannot match an `<li>`.
  Rendered values are identical to the lidmaatskap plan card. The duplication is the finding.
- **pill vs filter-chip.** The genre pill is `2px 12px`, a 1px primary@40% border (a `color-mix`, the only
  reduced-alpha token colour on these pages) and 9999px radius. The genre filter chip is `8px 16px`, a 1px
  `border` and a surface fill. The `2px` vertical padding is off-token (`18-ledegids-conformance.md`).
- **heading.** Page `h1` "Ledegids" (`ledegids.php:33`) is `has-xxl-font-size` (fluid 20.27 / 21.71 / 24px). The
  block's own `h2` "Skrywers" (#259) renders the same fluid xxl immediately below it, so the page has two headings of
  equal size for one listing. Kontak and lidmaatskap put their h1 at xxxl→32px.

## Load-scope cross-check

| Definition | load_scope | used_in | Result |
|---|---|---|---|
| SkrywersTab rules (theme.json styles.css) | site-wide | `SkrywersTab.php`, embedded by `ledegids.php` and `ontdek.php` | consistent |
| `functions.php:1431` `.wp-block-group.is-style-card` | site-wide | emitted on `li.ink-ontdek-skrywers__item` | **inert on this element** (selector mismatch). Compensated by #223. `PRIMITIVE`: duplicate recipe |

No live load-scope bug on this page.

## OFF-TOKEN

30 instances, 3 distinct values (`findings/18-ledegids-conformance.md`): genre-pill `padding-top` / `padding-bottom`
2px, and the writer bio `line-height` 1.6 (`22.4px ÷ 14px`, #272 `line-height:1.6`).

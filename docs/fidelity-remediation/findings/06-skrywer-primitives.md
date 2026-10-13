# 06 · skrywer — Primitive register (Phase 4b)

| | |
|---|---|
| **Page** | `skrywer` · Lovable `/writer/w1` ↔ INK `/author/cobus/` (anonymous) |
| **Date** | 2026-10-13 |
| **Register** | `maps/_primitive-observations.skrywer.csv`: 129 rows (55 lovable · 30 ink · 44 source) |
| **Captures** | `tmp/fidelity-captures/{lovable,ink}/skrywer@{390,768,1440}.ndjson`. All six passed `determinism-check.sh` (3 runs, identical) and `run.sh verify --sample 30` (0 mismatches) |

Header and footer are excluded (`--exclude-chrome`, row 00's scope). Nothing in this document
is a fix. Every item is recorded for Stage 2 (R8/R9).

---

## 1. How the register was built

1. `run.sh primitives --exclude-chrome` over all six captures produced 180 raw rows.
2. Kinds the tool cannot detect were added **by audit-id**, using the tool's own per-family
   property sets and filter:
   - `meta-item`: joined, location, followers, read time, age, counts, review count
   - `link-underline`: see-all
   - `eyebrow`: the works kicker
   - INK `section-band`: stats, works and CTA `<div>`s
   - INK `pill`: the genre pill, which the tool missed
3. Misclassifications were corrected (§5) and generic `button` rows resolved to
   `button-outline`.
4. Instances with identical computed values at several widths were collapsed into one row,
   with widths joined by `|`. The tool emits one row per viewport.
5. Attribution: every INK selector on this page resolves to `theme.json` `styles.css` or
   `functions.php`. The renderer `SkrywerProfiel.php` was read to confirm each class is
   emitted, and the line is cited in `used_in`.
6. `source` rows carry each INK definition as written, one row per rule (`@media` variants
   and `:hover` included).

**Instances by kind (rendered rows, after collapse)**

| kind | lovable | ink | source defs | note |
|---|---|---|---|---|
| heading | 12 | 9 | 10 | INK headings are 6 bespoke selectors |
| card | 7 | 1 | 4 | INK accomplishment card not rendered (no data) |
| pill | 7 | 2 | 2 | |
| section-band | 7 | 6 | 6 | |
| eyebrow | 7 | 3 | 2 | |
| meta-item | 6 | 4 | 3 | |
| icon-tile | 3 | 0 | 1 | INK tile not rendered (no data) |
| button-outline | 2 | 2 | 5 | |
| button-primary | 2 | 0 | 4 | INK Follow is logged-in only |
| link-underline | 1 | 1 | 2 | Lovable link is hidden < 768 |
| avatar | 1 | 1 | 2 | |
| tier-label | 0 | 1 | 3 | INK-only Gradering badge |

---

## 2. Declaration comparison

Each item is a `PRIMITIVE` finding (README §5): record, defer to Stage 2–3.

| id | kind | finding |
|---|---|---|
| **PRIM-06-1** | pill | **Same recipe declared twice in one renderer.** `.ink-skrywerprofiel__genre` and `.ink-skrywerprofiel__vasgespel-tipe` (theme.json) are identical except `align-items:center` vs `align-self:flex-start`. Both compute to padding 2/10px, 1px primary@40% border, full radius. That matches Lovable's `Badge variant=outline` surface on every captured property. Duplication with no single source. |
| **PRIM-06-2** | button-outline | **Two bespoke outline buttons plus a registered block style.** `.ink-skrywerprofiel__deel` (s-8/s-16, sm, `line-height:1.25rem`) and `.ink-skrywerprofiel__cta-ontdek` (s-12/s-24, then re-sized by `.ink-skrywerprofiel__cta-aksies .ink-skrywerprofiel__cta-ontdek` to `height:2.75rem;padding:0 2rem;1rem`) each restate the 2px-primary-border recipe and its `:hover`. `is-style-ink-outline` (`functions.php:1515`) is a third definition of the same kind, not used here. **Rendered output matches Lovable** on every button-family property: Share = `literary-outline` default size, Discover = `literary-outline` `lg`. The drift is in the definitions, not the pixels. Lovable's Share button carries a `Share2` svg; INK's is text only (spine F18-class difference). |
| **PRIM-06-3** | card | **The card recipe is re-declared locally, and the block-style class is inert.** `.ink-skrywerprofiel__vasgespel-item` restates border, radius, surface, padding and the hover lift. The `<li>` also carries `is-style-card`, but the registered selector is `.wp-block-group.is-style-card` (`functions.php:1431`), which cannot match a raw `<li>`, so the class does nothing. Against Lovable `Card`: INK has **no resting shadow** (Lovable `shadow-sm` `0 1px 2px rgba(0,0,0,.05)`); padding sits on the card (24px) rather than on an inner `CardContent`; and the surface (`#FDFCFA` vs `#FDFDFC`) and border (`#E8E4DC` vs `#E7E1DA`) are token-level near-misses. Hover: INK uses `shadow--lg` with a 0.15s transition, Lovable `--shadow-elevated` with 0.3s (`.card-hover`); this is source only, as hover is not captured. A third card definition, `.ink-skrywerprofiel__prestasie` (padding s-16, no shadow), exists for accomplishment rows but is not rendered for this writer. |
| **PRIM-06-4** | heading | **Six heading selectors, each restating family, size and weight**: `__naam`, `__oor-titel`, `__vasgespel-titel`, `__cta-titel`, `__prestasies-titel`, `__lesergradering-titel`. `__vasgespel-titel` and `__cta-titel` are identical (hero-sm, then xxxxl with `line-height:1.111` from 768). That computes to **39.996px against Lovable's 40px**, a rounding artefact of the unitless ratio. `__lesergradering-titel` is **declared twice in theme.json** with different bottom margins (s-12, then s-16); the later rule wins. `__prestasies-titel` renders as a 20px/700 h2 where Lovable has a 12px uppercase muted eyebrow h3 (spine F10). |
| **PRIM-06-5** | eyebrow | `.ink-skrywerprofiel__stat-etiket` matches Lovable's stat label on size, line-height, weight, `letter-spacing:0.3px` and uppercase. Only the muted colour differs: `rgb(107,114,128)` vs `rgb(103,111,126)`. That is the `muted-text` token rather than this selector, and the same delta appears on every muted node on the page. The works kicker `.ink-skrywerprofiel__vasgespel-kicker` (italic serif primary, 14/20, 0.35px) matches Lovable's kicker **text span**. Lovable's kicker **row** is 16/24 (spine F13: INK has no row wrapper or icon). |
| **PRIM-06-6** | meta-item | **No INK meta item has an icon** (Lovable: MapPin, CalendarDays, Clock, Heart, MessageCircle). Card-footer items compute to `line-height:18px` against Lovable's 16px: `.ink-skrywerprofiel__vasgespel-voet` sets `font-size:xs` and no line-height, so it inherits 1.5. Identity meta items (14/20) match. |
| **PRIM-06-7** | avatar | `.ink-skrywerprofiel__foto img` uses `--wp--preset--shadow--lg`, which computes to `rgba(24,29,37,.12) 0 12px 40px -8px` (Lovable's `--shadow-elevated` value). Lovable's avatar uses Tailwind `shadow-lg`, a two-layer `0 10px 15px -3px / 0 4px 6px -4px` black@10%. So the same token name maps to different shadows on the two sides. Border colour is `surface` (`#F8F6F2`) vs Lovable `background` (`#FAF8F5`). |
| **PRIM-06-8** | section-band | Stats band tint: INK `surface`@40% (`248,246,242`) vs Lovable `cream`@40% (`250,248,245`). **The works band has no tint on INK** (transparent; Lovable `cream`@30%), only the border-top. Every INK band is constrained to the 768px column (spine F1), not full-bleed. |
| **PRIM-06-9** | button-primary | `.ink-volg-knoppie` has one base definition plus **three context overrides**: `.ink-skrywerprofiel__cta-aksies .ink-volg-knoppie` (lg sizing), `.ink-outeur-kaart__aksies .ink-volg-knoppie` (theme.json) and `.ink-volg-lys__item .ink-volg-knoppie` (`profiel.css:671`). It is **not captured**, because Follow renders only for logged-in viewers (spine F22), so its computed values against Lovable `literary` are unmeasured. |
| **PRIM-06-10** | tier-label | `.ink-gradering` (heading font, 600, 16px, plus a decorative ★) is the member Gradering badge, an INK-only element on this page (spine F5). Its shape differs from the vocabulary's `tier-label` definition (bare uppercase xs accent text). Stage 2 should decide whether these are one kind or two. `.ink-gradering--brons` has no rule; only `--meester` is styled. |

---

## 3. Load-scope cross-check

**No live load-scope bug on this page.**

- No stylesheet under `assets/css/` is enqueued for `is_author()`. The only `is_author()`
  enqueues are scripts: `volg.js` (`functions.php:761-790`) and `skrywer-deel.js`
  (`functions.php:930-945`).
- Every INK definition the page relies on lives in `theme.json` `styles.css` (site-wide) or a
  `functions.php` block-style `inline_style` (site-wide). Every class in `used_in` is
  therefore styled wherever it renders.
- `profiel.css:671` (`.ink-volg-lys__item .ink-volg-knoppie`) is gated to
  `is_page('my-profiel')`. Its consumer, `FollowingList.php:154`, renders only there, so the
  scope matches.

Two related observations, neither a load-scope bug:

- **Inert class (selector scope, not load scope).** `is-style-card` on
  `SkrywerProfiel.php:591`'s `<li>` never matches `.wp-block-group.is-style-card`. The card
  looks right only because the BEM class re-declares the whole recipe (PRIM-06-3).
- **Over-broad scope.** Some 90 `.ink-skrywerprofiel*` / `.ink-leseroordeel-vorm*` rules
  ship to every page through theme.json, although they are only ever emitted on author
  archives. This is the established theme.json convention, recorded for Stage 3's
  `primitives.css` split.

---

## 4. Component-kind vocabulary

**No additions.** Every instance on this page maps to an existing kind:

- `meta-item`, `link-underline` and `tier-label` from rows 00 and 03
- `icon-tile` (Lovable accomplishment tiles)
- the seed list for the rest

The Lovable "Pinned" badge (sage-tinted, borderless, full radius) is filed under `pill`, with
the note saying so.

---

## 5. Tool behaviour observed (for the harness worklist)

1. **`tier-label` substring misfire.** `extract-primitives.mjs` classes anything containing
   `gradering` as `tier-label`. It filed `.ink-skrywerprofiel__lesergradering-titel` (an h2)
   and `-leeg` (empty-state text) as tier labels, plus the badge's wrapper `<p>` and its
   `__mark`/`__label` children. These were corrected or dropped by hand.
2. **INK `__genre` pill missed.** The pill rule keys on `-tipe`. Added by audit-id.
3. **No viewport collapse.** The tool writes one row per viewport. The README's register
   spec says identical instances are one row with widths joined by `|`. Collapsed by hand
   (scratch script `tmp/skrywer/build-register.py`).
4. **`diff.mjs` pairs the LAST instance of a duplicated id** (`byId.set` overwrites,
   `diff.mjs:91-92`). Row 06's pre-existing all-instance ids (`skrywer-genre-pill`,
   `skrywer-workcard`, `skrywer-workcard-title`) were narrowed to the first instance on both
   sides so the join is first ↔ first. Other rows with pre-existing all-instance ids may be
   silently pairing last ↔ last.

---

## 6. Limits

- The register reflects **one writer's data, anonymously**. The accomplishment card and icon
  tile, the rating stat, the Follow buttons and the rating form were not rendered, so they
  appear only as `source` rows (or not at all on the INK side).
- Hover and focus states are not captured. The hover comparisons in PRIM-06-2 and PRIM-06-3
  come from source and are not measured.
- `declarations` on rendered rows use the tool's per-family property subset. The full 52-property
  comparison is in `06-skrywer-drift*.md`.

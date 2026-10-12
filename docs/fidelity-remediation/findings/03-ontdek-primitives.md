# 03 · ontdek: primitive register (Phase 4b)

| | |
|---|---|
| Page | `ontdek` (row 03, treatment A) |
| Register | `maps/_primitive-observations.ontdek.csv` (105 rows: 23 `lovable`, 50 `ink`, 32 `source`) |
| Captures | `tmp/fidelity-captures/{lovable,ink}/ontdek@{390,768,1440}.ndjson`, plus INK-only `ink/ontdek-skrywers@{390,768,1440}.ndjson` (`/ontdek/#skrywers`) |
| Gates | determinism (3 runs, byte-identical) and `verify --sample 30` (0 mismatches) **pass on all 9 captures** |
| Date | 2026-10-13 |

Header and footer are excluded. They were registered on row 00.

## Method

1. I ran `run.sh primitives` over the 9 captures after removing the nodes this page does not
   own: the header and footer subtrees; in the default INK capture, the `hidden`
   `#skrywers` panel; in the skrywers capture, everything outside it. 468 raw rows came
   out.
2. A post-processor (`tmp/ontdek-03/register.py`) ports the tool's rules, property families
   and declaration format. It is **cross-checked to reproduce the tool's 468 rows exactly**
   before it does anything else. It then:
   - resolves the tool kinds README requires resolving: the generic `button`, and the
     Tailwind `rounded-full` nodes the tool calls `pill` (Lovable avatar → `avatar`,
     bookmark → `button-icon`, filter and sort pills → `filter-chip`, `TabButton` → `tab`,
     shadcn `Input` → `search-field`, INK `.ink-ontdek-soek__knoppie` → `search-button`)
   - adds the instances the tool's class rules cannot see: Lovable eyebrow and meta items;
     INK type pill (`.ink-lees-tipe`), meta items, card titles rendered as `<a>`,
     gradering labels, pagination link
   - joins viewports where the values are identical (`390|768|1440`)
   - folds identical repeat instances into one row, with the count in `note`
3. Source attribution: every INK selector was traced to its definition by reading
   `theme.json` `styles.css` (extracted; it sits on `theme.json:162`) and
   `functions.php:1383-1415`. The consumer is read in the render callback or pattern. The
   `source` rows carry each definition's body exactly as written.

## Load-scope cross-check

**No live load-scope bug on this page.** Every primitive definition that reaches `/ontdek/`
is in `theme.json` `styles.css` or a `functions.php` block style. Both are site-wide.

- No `assets/css/*` sheet is enqueued here. `functions.php:115-130` enqueues only
  `ontdek-tabs.js`, gated by `is_page('ontdek')`. Its only consumer is `patterns/ontdek.php`.
- `SkrywersTab` also renders on `/ledegids` (`patterns/ledegids.php:48`). Its classes are
  site-wide, so they are styled there too. The panel-hiding rule
  (`[data-ink-ontdek-panel][hidden]`) is site-wide but only matters where `ontdek-tabs.js`
  runs.
- One near-miss, recorded for Stage 2: `is-style-card` sits on raw `<li>`s here
  (`WorksArchive.php:474`, `SkrywersTab.php:308`, `DiscoverySurfaces.php:298`,
  `Search.php:236,250`). The registered block style (`functions.php:1388`,
  `.wp-block-group.is-style-card`) can never match an `<li>`. The class would be inert
  except that `theme.json:162` re-states the full recipe per selector (P1).

## PRIMITIVE findings: declaration comparison

| id | kind | finding |
|---|---|---|
| 03-P1 | card | **Three definitions of one recipe.** `functions.php:1388` (group: bg, border, radius-lg, shadow-sm, padding s-24), `functions.php:1408` (details: the same, without padding) and `theme.json:162` (`.ink-ontdek-{werke,skrywers,vlak,soek}__item.is-style-card`: the same body again, because the registered selector cannot match an `<li>`). Duplication with no single source. Within the page, the three INK cards also differ: `gap` 12px (werke, skrywers) vs 4px (vlak), and hover lift on werke/skrywers only. Against Lovable `Card`: padding sits on the card (24px) rather than in `CardContent`; shadow `rgba(24,29,37,.06) 0 2px 12px -2px` vs Lovable `shadow-sm` `rgba(0,0,0,.05) 0 1px 2px`; bg `253,252,250` vs `253,253,252`; border `232,228,220` vs `231,225,218`. Radius 8px matches |
| 03-P2 | filter-chip | **Three chip recipes on one page**: type filter, sort and date (`theme.json:162`). Werke and skrywers share one selector list per recipe, which is good. Filter chip vs Lovable: padding 8/16, radius, 14px and the active primary fill all match. It differs on line-height 21 vs 20 and on its tokens (inactive bg `248,246,242` vs `250,248,245`; active text `253,252,250` vs `255,255,255`). Sort chip vs Lovable: padding 6/12 matches; `display` flex vs block; active bg `237,233,224` vs `240,235,230`. The **date chip** has no Lovable counterpart and is a different recipe: padding 4/12, xs type, and an **accent** (sage) active fill where the filter chip uses primary |
| 03-P3 | pill | **Three pill definitions plus an inline one.** `.ink-lees-tipe` is shared with the reading patterns; `.ink-ontdek-werke__type.ink-lees-tipe` is a page-local override (xs/600); the gedig 15% tint is an **inline `style` in PHP** (`WorksArchive.php:533`); `.ink-ontdek-skrywers__genre-pil` is a separate outline recipe. Lovable uses one shadcn `Badge variant=outline` for both the story type and the writer genres: border primary/40, transparent fill, 2/10 padding. INK's genre pill is close to it (2/12, the same border). INK's type pill is a different, filled recipe (4/12, no border, 10–15% tint). The per-type colour is DIVERGENCE (accepted) (`WorksArchive.php:478-513`). The fill-vs-outline recipe is not covered by that disclosure |
| 03-P4 | tier-label | **Three identical copies.** `.ink-ontdek-skrywers__gradering`, `.ink-ontdek-vlak__gradering` and `.ink-ontdek-soek__tipe,…__gradering` carry the same body (xs, uppercase, 0.08em, accent), each defined separately in `theme.json:162`. There is no Lovable counterpart (spine S22) |
| 03-P5 | heading | `.ink-ontdek-werke__heading` and `.ink-ontdek-skrywers__heading` have identical bodies defined separately (both panel headings are EXTRA, spine S10). The card title is an `<a>` styled as a heading: 18px / 23.4px vs Lovable `h3` 20px / 27.5px. The intro h1 matches Lovable at all three widths (36 / 48 / 60px, 600) |
| 03-P6 | search-button | `.ink-ontdek-soek__knoppie` is a **bespoke primary-button recipe** (body font, 600, radius-md, padding 12/24, no shadow). It is not `is-style-ink-primary` (display font, shadow, per the row 00 source rows). It has no Lovable counterpart (spine S5a) |
| 03-P7 | search-field | `.ink-ontdek-soek__veld` vs shadcn `Input` (`h-12 pl-11`): padding 12/16/12/40 vs 8/12/8/44; height 43px vs 48px; width 486 vs 576px at ≥768. **No `color` is declared**, so the input text computes to UA black `rgb(0,0,0)` where Lovable uses the foreground token. Font size is 14px at every width; Lovable is 16px at 390 and 14px from 768 |
| 03-P8 | tab | `.ink-ontdek-tabs__knoppie` matches Lovable `TabButton` on padding (16/20), 14px/500 and the underline mechanism. It differs on line-height (`normal` vs 20px) and on the token values. `profiel.css` describes itself as mirroring this tab shape (row 02 scope). Check for a second definition in Stage 2 |
| 03-P9 | meta-item | `.ink-ontdek-werke__leestyd` / `__teller`: 12px with line-height 18px vs Lovable 16px |
| 03-P10 | avatar | Two avatar definitions on the page: 28px (`__foto`, works) and 56px with border (`__foto`, skrywers). Lovable's writer avatar is 64px with border. The writers tab is not captured on Lovable, so this rests on source (`Browse.tsx` `w-16 h-16`) and is **not** a measured claim |
| 03-P11 | section-band | Band padding is authored per pattern as inline block attributes (`patterns/ontdek.php:53,63,92,102`, `archive-intro.php:9`), not as a shared recipe. The intro band has **no background tint and no bottom border** (Lovable `bg-cream/40 border-b`); the tab band has its border on the inner `nav` rather than on the band |

### Systemic: token values, not recipes

Most colour deltas in the drift documents are the **same token pair everywhere**, not
per-component choices:

| | Lovable | INK |
|---|---|---|
| foreground | `rgb(24,29,37)` | `rgb(26,29,33)` |
| muted | `rgb(103,111,126)` | `rgb(107,114,128)` |
| border | `rgb(231,225,218)` | `rgb(232,228,220)` |
| background | `rgb(250,248,245)` | `rgb(248,246,242)` |
| secondary | `rgb(240,235,230)` | `rgb(237,233,224)` |

This is a palette-level reconciliation for Stage 2 (`docs/design-handoff/tokens/token-map.md`).
It is not a per-primitive defect.

## Limits

- **Lovable writers tab not captured.** It is React state with no URL, and the harness has
  no click step. The INK skrywers panel *is* captured (`/ontdek/#skrywers`) and registered
  as `ink` rows. There are no `lovable` rows for writer cards, genre badges, the 64px avatar
  or the Follow button.
- Search-results, empty and logged-in states are not captured.
- `register.py` groups identical repeats into one row. The README's "one row per
  instance" is honoured as one row per *distinct* instance, with the folded count in
  `note`.

## Harness defect found on this row (fixed)

`tools/fidelity/capture.mjs` (C4) waited on **every** incomplete `<img>`. A
`loading="lazy"` image inside a `display:none` subtree is never fetched, so its load event
never fires. The INK capture of `/ontdek/` hung indefinitely, on the three writer avatars in
the hidden `#skrywers` panel.

The fix filters the wait to rendered images (`getClientRects().length > 0`). It changes no
capture output on pages that did not hang, and `TOOL_VERSION` is unchanged.

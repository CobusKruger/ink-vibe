# 08a · biblioteek — primitive register (Phase 4b)

**Register:** `maps/_primitive-observations.biblioteek.csv` (145 rows: 101 `lovable`, 13 `ink`, 31 `source`)
**Captures:** `tmp/fidelity-captures/{lovable/library,ink/biblioteek}@{390,768,1440}.ndjson`. Each
passed `determinism-check.sh` (3/3 identical) and `run.sh verify --sample 30` (0 mismatches).
**Tool:** `run.sh primitives --exclude-chrome` (header/footer belong to row 00). Viewport-identical
instances are collapsed to one row with the widths joined by `|`.

The Lovable rows describe the shared `/library` reference. They are recorded **once, here**, and
row 08b's register carries only its INK and source rows.

## 1. What the register holds

| side | kinds (rows) |
|---|---|
| lovable | heading 26 · card 19 · pill 19 · meta-item 19 · filter-chip 8 · section-band 6 · search-field 2 · button-primary 1 · eyebrow 1 |
| ink | filter-chip 4 · heading 3 · section-band 2 · search-field 2 · search-button 1 · card 1 |
| source | heading 7 · button-outline 6 · card 5 · filter-chip 4 · search-button 3 · search-field 2 · meta-item 2 · pill 1 · section-band 1 |

**Resolution by hand:**
- The Lovable search input came out of the tool as `form-field`. It is filed as `search-field`
  (a shadcn `<Input>` with a leading icon).
- Added because the tool cannot classify them: 19 Lovable `meta-item` rows (the card read-time
  spans), the Lovable `eyebrow` (serif italic, not uppercase), and the INK empty-state box (`card`,
  noted as content-less).
- No generic `button` rows remained on this page.

**The live INK page renders very few primitives:** intro, search, filter chips and the empty state.
The only published `biblioteek_item` posts are `QA FIXTURE — ` titled and excluded by
`Archive::runQuery()`. So the card, pill, meta-item, card-title and shelf-heading primitives exist
only as `source` rows (`NOT RENDERED` in the note). Their computed values are unmeasured.

## 2. Source attribution

Every `.ink-biblioteek*` primitive is defined **once**, in `theme.json` → `styles.css`:
- no `assets/css/*.css` file and no `register_block_style()` `inline_style` defines any of them;
- `functions.php` enqueues no stylesheet for this page.

`used_in` is `ink-core/src/Library/Archive.php`, whose filter chips and pager come through
`Kernel/ArchiveRender.php` (`pill()`, `pagination()`, shared with other archives under each
archive's own CSS prefix). The pattern shell's padding is element-scoped inline style
(`patterns/biblioteek.php:22-23`). The block's root `<section class="ink-biblioteek">` has no rule.

## 3. Load-scope cross-check

- **`load_scope` is `site-wide` for every definition** (`theme.json`). The consumers render on
  `/biblioteek/` and on `/qa-bloks/` (row 21 gallery), and both are covered.
- **No inert class.** Every class `Archive.php` emits has a matching rule.
- `.ink-icon` (the card/search icon class) is defined three times: `theme.json`, `assets/css/home.css`
  (front page) and `assets/css/skryf.css` (`/skryf`). Only the `theme.json` rule reaches this page.
  This is recorded for Stage 2. It is not a live bug here.

**No live load-scope bug on this page.**

## 4. Declaration comparison

Rows 08a and 08b render the same Lovable component set from two renderers, `Library\Archive` and
`Training\Hub`. Each has its own `theme.json` rule set, and the two were compared declaration by
declaration:

| kind | `.ink-biblioteek__…` | `.ink-opleiding__…` | verdict |
|---|---|---|---|
| filter-chip | `filter-knoppie` (4 rules) | `filter-knoppie` (4 rules) | **identical**: duplication, no single source |
| heading (h1) | `heading` + 2 media rules | `heading` + 2 media rules | **identical**: duplication |
| meta-item | `item-leestyd` (+ `svg`) | `item-leestyd` (+ `svg`) | **identical**: duplication |
| heading (card title `<a>`) | `titel` | `titel` | **identical**: duplication |
| button-outline (pager) | `vorige,volgende` | `vorige,volgende` | **identical**: duplication |
| search-field | padding `s-12 s-16 s-12 s-40`, `font-size:md`, `line-height:normal` | padding `s-8 s-16 s-8 calc(s-40+s-4)`, `font-size:sm`, `line-height:1.25rem` | **differ.** Rendered: 50px tall / 16px text vs 39px / 14px |
| search-button | `font-family:inherit` | *(no font-family)* | **differ.** Opleiding renders in UA **Arial 13.33px**. Neither sets a font-size |
| card | no padding, `overflow:hidden`; padding sits on `__item-inhoud` | `padding:s-24; gap:s-8` on the `<li>` | **differ** (structure follows: 08a keeps a body wrapper, 08b does not) |
| card (featured modifier) | `uitgelig-item`: `color-mix(…20%…)` border only | `rak-item`: adds a `border-color:primary` fallback before the `color-mix` | **differ** (fallback only) |
| pill | `genre`: padding `s-4 s-12`, no line-height | `item-pil`: padding `2px 10px`, `line-height:1.333` | **differ**: box size |
| heading (shelf h2) | `uitgelig-titel`: weight **regular**, `line-height:1.333`, `margin-bottom:s-24` | `rak-titel`: weight **semibold**, no line-height, `margin:0` | **differ** |
| card (empty box) | `leeg`: box only; the title styling sits on `leeg-teks` (display xl) | `leeg`: the box itself carries display xl type | **differ** |
| button-outline (empty clear) | `leeg-skoon`: **1px** border, padding `s-8 s-24`, no font set | `leeg-skoon`: **2px** border, padding `s-8 s-16`, body `sm` | **differ** |

**Against the reference (recorded, not judged):**
- Lovable's search input is 48px tall with 16px text at 390 and 14px at ≥768. Neither INK
  variant matches across widths.
- Lovable's category badge is `2px 10px` padding, which is the 08b value, not the 08a value.
- Lovable's filter chip has the same box as both INK chips (8/16 padding, 1px border,
  radius-full). It differs in line-height (20 vs 21px), in the inactive fill
  (`background` vs transparent), and in its leading icon.

## 5. PRIMITIVE findings

| id | kind | finding |
|---|---|---|
| PRIM-08-1 | filter-chip, heading, meta-item, button-outline (pager) | Five identical rule sets are duplicated between `.ink-biblioteek__*` and `.ink-opleiding__*` in `theme.json`. Nothing keeps them in step. |
| PRIM-08-2 | search-field | Two divergent search-field definitions for one Lovable control. |
| PRIM-08-3 | search-button | Two definitions. Only biblioteek resets `font-family`, so 08b's button renders in Arial. Neither sets a font-size. |
| PRIM-08-4 | card / pill / shelf heading | The card, its featured modifier, its category pill and the shelf h2 each have two diverging definitions. All are unrendered live, so the divergence is source-only until content exists. |
| PRIM-08-5 | card + button-outline (empty state) | The empty-state box and its clear control diverge between the two archives (type placement, border width, padding). |

Header and footer primitives are row 00's and are excluded. No CSS was changed (R8).

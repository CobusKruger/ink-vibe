# 07 · lees-storie: primitive register (Phase 4b)

**Register:** `maps/_primitive-observations.lees-storie.csv` has 114 rows:

- 44 `lovable` (rendered instances)
- 36 `ink` (rendered instances)
- 34 `source` (INK CSS definitions)

The lead merges it into `maps/_primitive-observations.csv` at the end of Stage 1.

**Inputs:** the six gated captures, `tmp/fidelity-captures/{lovable,ink}/lees-storie@{390,768,1440}.ndjson`.

- Determinism: 3/3 runs identical at every side × viewport.
- `verify --sample 30`: passed on all six.
- Extraction ran through `run.sh primitives --exclude-chrome`, giving 204 raw rows; 489 header and footer nodes were dropped.
- The build script, `tmp/lees-storie/build_register.py`, then did four things:
  1. collapsed rows whose values are identical across viewports into one row (`390|768|1440`)
  2. collapsed identical repeater instances into one row (`xN identical repeater instances`)
  3. resolved every generic row to a kind from the vocabulary
  4. added the rows the classifier cannot detect

## 1. Resolutions applied

| Rows | Tool said | Filed as | Why |
|---|---|---|---|
| INK `.ink-reaksies__type--*` ×3 | form-label | `filter-chip` | A `<label>` wrapping a visually-hidden radio is the visible chip. It is Lovable's response-type `<button>`. Declarations were recomputed with the button property set |
| INK `.ink-reaksies__type-input` ×3 | form-field | dropped | Visually-hidden radio (1px clip) |
| INK `.ink-outeur-kaart`, `.ink-reaksies` | card / section-band | dropped | Layout wrappers with no surface. Lovable's counterparts were not classified either |
| INK `.ink-outeur-kaart-band` | card | `section-band` | The author band. The tool classified it as `card` because of its top and bottom borders |
| like button, both sides | button | `button-icon-label` | heart + count |
| comments link, both sides (`<a>`) | not detected | `button-icon-label` | icon + count |
| upvote, both sides | button / not detected | `button-icon-label` | INK is an inert `<span>` with no icon (note) |
| bookmark, share, highlights tab, panel close | button | `button-icon` | icon-only |
| Lovable `View all works`, INK `.ink-outeur-kaart__sien-alle` | button | **`button-subtle`** (new kind, README §4.1) | shadcn `variant="subtle"` |
| Lovable `Reply` | button | `button-ghost` | Bare text button with no variant. Nearest kind; the note says so |
| Lovable critique type spans | pill | `badge` | Aligned with INK `.ink-reaksies__badge` |
| Highlights-panel counters, both sides | pill / not detected | `count-chip` | |
| INK `storie-hint`, `storie-engagement-bar`, `.ink-reaksies__form`, `.ink-reaksies__item`, `.ink-highlights-panel`, `__title`, `__count` | not detected | pill / pill / card / card / card / heading / count-chip | No class or tag hook the tool recognises |
| Lovable `reading-header` (`<div>`), `reading-body` (`<article>`) | not detected | `section-band` | Parity with the INK `<section>` bands |
| Byline read time / date and response time, both sides | not detected | `meta-item` | |

## 2. Declaration comparison

Where a kind on this page has more than one INK definition, the definitions were compared
declaration by declaration. Only the INK side gets this comparison. The Lovable side is the
reference and has no INK source to compare.

**P-1 · `.ink-reaksie-bar` has two definitions.**
- **theme.json** sets: flex, `justify-content:space-between`, gap s-24, `width:fit-content`,
  margin `s-32 auto 0`, padding s-12/s-24, border, radius full, an **opaque** `surface-alt`
  background and a literal shadow.
- **`reading.css:36`** sets `position:sticky`, `bottom` s-24, `z-index` 40, a **95%**
  `surface-alt` background via color-mix, and `backdrop-filter:blur(4px)`.

The two sheets disagree on the background. Computed: `color(srgb … / 0.95)`, so reading.css
wins. The pill recipe is split across a site-wide string and a page-scoped file.

Measured: Lovable's pill (`reading-engagement-pill`, `max-w-md`) is **448px** wide at 1440.
INK's pill (`storie-engagement-bar`, `fit-content`) is **180px** wide. Both are bbox widths
from the 1440 captures.

**P-2 · The like button takes three rules.** All three match the same `<button>`:
- theme.json `.ink-reaksie-tellers`: flex, wrap, **gap s-16**, muted text, **font-size sm**
- theme.json `button.ink-reaksie-tellers--enkel`: reset, plus `font:inherit`
- `reading.css:56` `.ink-reaksie-tellers--enkel`: inline-flex, **gap s-8**, muted text

Computed: gap 8px (reading.css wins) and font-size 16px (`font:inherit` beats `sm`). The two
definitions contradict each other on gap and on size. The `volledig` base rule was never meant
for this variant, but it still matches it.

**P-3 · Button resets are inconsistent inside one bar.** The like button inherits the page
font through `font:inherit`. The bookmark (`.ink-leeslys-knoppie`), highlights tab
(`.ink-highlights-toggle`) and panel close (`.ink-highlights-panel__close`) have no font
reset. They compute **Arial 13.33px**, the browser's default button font. The equivalent
Lovable buttons compute Inter 16px.

**P-4 · Two bespoke INK `button-primary` recipes in one context, and neither is the
registered style.** `is-style-ink-primary` is registered at `functions.php:1440`.

| | `.ink-reaksies__submit` | `.ink-volg-knoppie` (+ author-card override) |
|---|---|---|
| height / padding | `height:2.5rem; padding:0 s-16` | `padding:s-8 s-16`, overridden to `height:2.25rem; padding:0 s-12` |
| gap | s-8 | s-4 |
| line-height | unset | 1.25rem |
| font | heading, sm, medium | heading, sm, medium |
| radius / colours | md, primary / surface-alt | md, primary / surface-alt |
| shadow | none (computed `none`) | none |

Lovable's `literary` button computes `box-shadow: 0 4px 20px -4px rgba(24,29,37,.08)`
(`shadow-soft`). Neither INK recipe carries a shadow, while the registered
`is-style-ink-primary` carries `shadow.md`.

**P-5 · The card recipe is duplicated.** `.ink-reaksies__form` and `.ink-reaksies__item`
each restate the same recipe: padding s-24, 1px border, radius **xl**, `surface-alt`
background, no shadow. Neither uses the registered `is-style-card` (`functions.php:1381`:
radius **lg** + `shadow.sm`) or `is-style-ink-card` (`:1596`: radius xl + `shadow.sm` + hover
lift).

**P-6 · `button-subtle` (new kind).** `.ink-outeur-kaart__sien-alle` against Lovable
`variant="subtle" size="sm"`:
- **Same:** height 36px, radius 6px, `secondary/50` fill, ink text, 14px.
- **Different:** INK padding-x is 16px (Lovable 12px), INK weight 400 (Lovable 500).

**P-7 · `filter-chip`.** `.ink-reaksies__type` against Lovable's response-type chip:
- **Same:** padding 8/16, full radius, `secondary/50` fill, muted text, 14px.
- **Different:** INK weight 400 (Lovable 500).

**P-8 · `badge`.** `.ink-reaksies__badge` is a base rule plus three type variants
(`.ink-reaksie--{lof,insig,voorstel} .ink-reaksies__badge`), all in theme.json.
- **Same as Lovable:** padding 2/8, full radius, 10% tint.
- **Different:** INK weight 500 (Lovable 400).

**P-9 · `.ink-lees-tipe` (pill) is shared with /ontdek cards.** It has a second, compound
definition, `.ink-ontdek-werke__type.ink-lees-tipe` (font-size xs / weight 600). On the
reading page, size and colour come from per-instance block attributes
(`reading-storie.php:76`: sm, `primary`, weight 500). The tint mechanism is
`color-mix(currentColor 10%)`, so the fill follows whatever text colour the instance is
given. Artikel's muted badge (disclosed) inherits a grey tint the same way.

**P-10 · Hard-coded values sit in primitive recipes instead of tokens.**
- `.ink-storie-hint` sets `color:#181D25`, a literal rather than `ink-text`.
- `box-shadow:0 12px 40px -8px rgba(24,29,37,0.12)` is written out literally in three
  recipes: `.ink-reaksie-bar`, `.ink-highlights-toggle` and `.ink-highlights-panel`.

These are recorded for Stage 2. They are not OFF-TOKEN findings, which apply to Treatment C
pages only.

**P-11 · The responses h2 has no recipe of its own.** No `.ink-reaksies__heading` rule exists
in theme.json. The h2 takes the global `elements.h2` (fluid `xxl`), which computes:

| viewport | INK size | INK weight | Lovable |
|---|---|---|---|
| 390 | 20.27px | 700 | 24px / 600 |
| 768 | 21.71px | 700 | 24px / 600 |
| 1440 | 24px | 700 | 24px / 600 |

## 3. Load-scope cross-check

| Definition | load_scope | used_in | Result |
|---|---|---|---|
| `assets/css/reading.css` (`.ink-reaksie-bar`, `.ink-reaksie-tellers--enkel`, `.ink-lees-storie__prose`) | `is_singular(['gedig','storie','artikel'])` (`functions.php:205-206`) | `reading-storie.php`, `reading-gedig.php`, `reading-artikel.php`; `ReactionTotals::toHtmlEnkel` (enkel variant only placed in those three patterns) | covered, no live bug |
| everything else above (theme.json `styles.css`) | site-wide | reading patterns, the ink-core reading blocks, `highlightable-text.js` (script gated to storie/artikel) | covered |

**No live load-scope bug on this page.** Three observations sit next to the check:

- **Stale documentation.** `reading.css:1-15` states it is "NOT reading-artikel" and
  `:55` cites the deleted `text-highlight-reactions.js`. Both contradict the enqueue at
  `functions.php:205-206` and the replacement script `highlightable-text.js`. This does not
  break anything, but the next person to read the file is misled.
- **Possibly orphaned definitions.** theme.json still carries `.ink-storie__para.has-reaksie`
  and the `.ink-storie-highlight-bar*` family, which belonged to the removed
  text-highlight-reactions client. A search of `assets/js/`, `patterns/` and
  `ink-core/src/` located no consumer. This is a grep locate only (R1), not a full-read
  absence proof, so "dead CSS" is a candidate for Stage 3 to confirm.
- **artikel.** The reading.css consumers are covered there too, because the enqueue
  includes artikel.

## 4. What this register does not hold

- **States not captured:**
  - interactive states: the open highlights panel, the tooltip, a selected chip, liked
    or saved
  - the logged-in follow button (INK only, S-10)
  - the non-empty related-reads sections (S-19)
- **Pseudo-elements:** the drop cap (`::first-letter`, both sides) is not captured.
- **Lovable mock data:** the Lovable critique repeaters are mock data (3 instances, each
  with a different badge colour). The INK side has a single `lof` response, so the `insig`
  and `voorstel` badge variants exist on INK only as `source` rows.

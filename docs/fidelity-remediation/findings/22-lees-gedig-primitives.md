# 22 · lees-gedig — Primitive register (Phase 4b)

**Register:** `maps/_primitive-observations.lees-gedig.csv`, 85 rows:

- 34 lovable, 29 ink, 22 source.
- Viewport-identical instances are collapsed into one row, with the widths joined by `|`.
- Repeated identical instances without an id are one row, with the count in `note`.

**Input:** `run.sh primitives --exclude-chrome` over both sides at 390/768/1440 gave 258 raw
rows. The reductions and corrections below were made by hand.

**Header and footer:** excluded (row 00).

**Vocabulary:** no new `component_kind`. Every instance fits the README §4.1 list, using
row 07's `button-subtle` and its filing rules for the engagement bar.

---

## 1. Hand corrections to the tool output (T2 leftovers / T6)

| Correction | Why |
|---|---|
| INK `reading-author-band` changed `card` → `section-band` | T6: the `-band` class became `card` because the node also carries a border |
| INK `reading-author-card` (`.ink-outeur-kaart`) and `reading-responses-inner` (`.ink-reaksies`) **dropped** | Not primitives: no fill, border or radius (tool filed them as card / section-band) |
| INK `.ink-reaksies__type` changed `form-label` → `filter-chip` | A `<label>` styled as a chip. Its declarations were re-read from the capture with the button property set, so they compare against Lovable's chip |
| INK `.ink-reaksies__type-input` changed `form-field` → `checkbox` | A visually hidden radio. README: `checkbox` covers radios |
| Lovable response badges changed `pill` → `badge` | Small type badges, not pills |
| Generic `button` rows resolved | Like → `button-icon-label`; bookmark / share / per-line heart → `button-icon`; upvote → `button-icon-label`; Reply → `button-ghost`; all-works links → `button-subtle`; Follow → `button-primary` |
| **Added by hand** (tool did not classify) | INK `gedig-hint` (pill), INK `gedig-engagement-bar` (pill, the counterpart of Lovable `reading-engagement-pill`), INK `reading-response-form` (card), both `reading-comments-link` anchors (`button-icon-label`) |
| Lovable `gedig-stanza-line` buttons (17 per viewport) **dropped** | Full-width poem-line toggles, not a shared component kind. Their INK counterpart is a `<p>`; the shared piece is the heart (`button-icon`) |

---

## 2. Source attribution summary

| INK definition | defined_in | load_scope | consumers (`used_in`) |
|---|---|---|---|
| `.ink-lees-tipe` | theme.json **+ inline override** `reading-gedig.php:89` | site-wide | 6 reading patterns + FeaturedStream + WorksArchive |
| `.ink-gedig-hint` | theme.json | site-wide | reading-gedig.php |
| `.ink-gedig__react` (+ `.is-active`) | theme.json | site-wide | line-reactions.js (enqueued gedig+storie) |
| `.ink-reaksie-bar` | theme.json **+** `reading.css:36` | site-wide / `is_singular([gedig,storie,artikel])` | reading-gedig/storie/artikel |
| `.ink-reaksie-tellers--enkel` | theme.json `button.…` **+** `reading.css:56` | site-wide / reading.css scope | ReactionTotals (via 3 reading patterns) |
| `.ink-leeslys-knoppie`, `.ink-kommentaar-telling` | theme.json | site-wide | ReadingListToggle / ResponseCount (3 reading patterns) |
| `.ink-outeur-kaart__foto/__naam/__sien-alle` | theme.json | site-wide | ReadingAuthorCard (3 reading patterns) |
| `.ink-reaksies__form/__item/__type/__type-input/__input/__submit/__badge` | theme.json | site-wide | ResponsesList (3 reading patterns) |
| `.ink-reaksies__heading` | **no class rule**; theme.json `elements.h2` | site-wide | ResponsesList |
| Section bands | **inline block styles** in `reading-gedig.php:85/117/137/147` | inline | reading-gedig.php |

---

## 3. Load-scope cross-check

- **No inert class on this page.** Every class rendered here is defined in `theme.json`
  (site-wide), or in `reading.css`. `reading.css` is gated at `functions.php:206` to
  `is_singular(['gedig','storie','artikel'])`, which is exactly the set of patterns that
  emit `.ink-reaksie-bar` / `.ink-reaksie-tellers--enkel`.
- **Stale disclosure:** `reading.css:11-15` says the file is enqueued on gedig/storie only and
  "never reaches reading-artikel". `functions.php:206` includes artikel. This is
  documentation drift, not a load bug.
- **Live behavioural load-scope bug (script, not stylesheet).** It sits next to this page
  and was found while attributing `.ink-gedig__react`:
  - `ReactionTotals::firstAnchorFor()` (`ReactionTotals.php:199-215`) gives **artikel** a
    click anchor, so the artikel floating heart renders as a `<button data-ink-post …>`.
  - Its only handler, `line-reactions.js`, is enqueued on `is_singular(['gedig','storie'])`
    (`functions.php:591`).
  - So the artikel like-button is a live button with no handler. Clicking does nothing.
  - Not measured (artikel is not captured). Flag for the owner.

---

## 4. Declaration comparison (INK vs Lovable, matched instances, 1440)

| kind | instance | equal | differs |
|---|---|---|---|
| pill | `gedig-badge` | padding 4/12, radius full, 15% accent fill (via the inline override) | no `gap` on INK (6px on Lovable; the icon is spaced by an inline `margin-right:4px` span) |
| pill | `gedig-hint` | padding 8/16, radius full, 15% accent fill, 14px | no `gap` on INK (8px) |
| pill | engagement bar | padding 12/24, radius full, 1px border, shadow, 95% fill | fill token `surface-alt` (253,252,250) vs Lovable `card` (253,253,252); border colour (232,228,220) vs (231,225,218) |
| button-icon-label | like (`gedig-reaksie-tellers`) | no box; 16px Inter; muted colour | icon 18px vs 20px; count weight 400 + ink colour vs 500 + muted |
| button-icon-label | comments link | no box | 14px/21px vs 16px/24px; gap 4px vs 8px; count weight 400 vs 500 |
| button-icon | bookmark | no box, muted | 18px vs 20px icon (computed font is irrelevant: icon only) |
| button-icon | per-line heart | 14px glyph | **at rest:** INK opacity 1 + primary colour on reacted lines (S-7) vs Lovable opacity 0 muted; INK is `display:flex` vs block |
| button-subtle | all-works | fill secondary/50, radius 6, height 36, 14px | padding-inline 16px vs 12px; weight 400 vs 500; line-height 21 vs 20 |
| button-primary | submit | primary fill, radius 6, Lora 14px/500 | text colour `surface-alt` (253,252,250) vs white; **no shadow** (Lovable `shadow-soft`); height from a fixed 40px vs py-2; label "Plaas" vs "Share Response" (copy) |
| filter-chip | response type | padding 8/16, radius full, secondary/50 fill, 14px | weight 400 vs 500; line-height 21 vs 20; gap from the class vs `gap-2` |
| form-field | textarea | 1px border, radius 6, 14px | padding 12 vs 8/12; fill **white** vs cream `background` (250,248,245); text colour black vs ink; rendered height 51px vs 100px min |
| card | compose form | padding 24, radius 12, 1px border | flex column + gap 12 vs block + margins; fill token (253,252,250) vs (253,253,252) |
| heading | responses h2 | Lora, 24px at 1440 | weight **700** vs 600 (no class rule, so `elements.h2` wins); fluid 20.3/21.7px at 390/768 vs fixed 24px |
| heading | author name | Lora 20px/600 | line-height 24 vs 28 |
| avatar | byline 40px | radius full | `display:inline` vs block |
| avatar | author 96px | 4px surface border, radius full | border colour token (248,246,242) vs (250,248,245) |
| section-band | header / body / author / responses | header and author padding-block 48 | INK adds 24px inline padding on every band (Lovable pads the inner container); body band 24/64 vs Lovable 80/80 at 1440 (`py-16 md:py-20`) |

### Duplicate definitions (PRIMITIVE findings, deferred to Stage 2)

- **P-1:** two definitions of the floating pill:
  - `.ink-reaksie-bar` is defined in theme.json and again in `reading.css:36`.
  - The second overrides the first's `background-color`.
- **P-2:** two definitions of `.ink-reaksie-tellers--enkel`:
  - one in theme.json, as `button.…`;
  - one in `reading.css:56`.
- **P-3:** the type pill is redefined page-locally:
  - `.ink-lees-tipe` uses a 10% fill;
  - the gedig badge overrides it to 15% via an inline style at `reading-gedig.php:89` (disclosed at `:23-27`).
- **P-4:** separate pill recipes for the same shape:
  - `.ink-gedig-hint` is a separate pill recipe from `.ink-lees-tipe`;
  - row 07 has a third, the storie hint.
- **P-5:** `.ink-reaksies__submit` is a bespoke replica of the registered `is-style-ink-primary` button (`functions.php:1488`). It differs in text colour and shadow.
- **P-6:** the same card box is declared twice in theme.json:
  - `.ink-reaksies__form`;
  - `.ink-reaksies__item`.
- **P-7:** the header band's fill exists as two inline recipes:
  - `reading-gedig.php:85` uses `surface` 50%;
  - `reading-storie.php:73` uses `secondary` 30%;
  - no shared class.

# 04 · skryf — primitive register (Phase 4b)

**Rows:** `maps/_primitive-observations.skryf.csv`, 107 rows:

| side | rows | covers |
|---|---|---|
| `lovable` | 36 | step-1 and step-2 instances |
| `ink` | 39 | authenticated capture |
| `source` | 32 | one row per INK CSS definition, body as written |

**Scope:**

- Header and footer are excluded (row 00). The captures were cut to the `<main>` subtree
  (`*.main-only.ndjson`) before extraction.
- The three hidden form inputs (nonce, referer, action) are excluded because they are not
  rendered (`display:none`).
- Identical computed values at several widths are collapsed to one row (`390|768|1440`).
- Every generic `button` row was resolved to a kind: `button-ghost` (Change type),
  `button-outline` (Save Draft / Stoor konsep), `button-primary` (Publish / Plaas) and
  `checkbox` (Radix checkbox).

**Method.** Extraction used the property families and declaration filter of
`extract-primitives.mjs`, applied by a page-specific classifier
(`tmp/fidelity-skryf/build-register.mjs`, scratch) so that every row carries a real kind
rather than the tool's generic `button` / `form-label` guesses. Source attribution was
done by reading `assets/css/skryf.css` in full, the `theme.json` `styles.elements` and
extracted `styles.css` string (no `skryf` rule), and `functions.php`. For the
cross-page comparison I also read `kontak.css:25-139`, `auth.css:90-239` and
`functions.php:1440-1494, 1596-1618`.

## Kinds on this page

| kind | lovable rows | ink rows | INK source definitions |
|---|---|---|---|
| heading | 5 | 5 | skryf.css:29, :35, :184; theme.json h1/heading |
| card | 7 | 4 | skryf.css:91, :108, :112, :116, :120 (type card); :49 + :287 (challenges panel) |
| icon-tile | 4 | 3 | skryf.css:148, :166, :172, :178 |
| form-field | 6 | 12 | skryf.css:214, :231, :236, :271 |
| form-label | 2 | 6 | skryf.css:56, :205, :297 |
| form-hint | 1 | 1 | skryf.css:313 |
| checkbox *(new)* | 4 | 4 | skryf.css:138 (radio), :355 (checkbox) |
| choice-row *(new)* | 4 | 1 | skryf.css:319, :343 |
| button-primary | 1 | 1 | skryf.css:414 + :468, :441 |
| button-outline | 1 | 1 | skryf.css:414 + :451 + :458 |
| button-ghost | 1 | 0 | — (Change type is part of the S-2 divergence) |
| section-band | 0 | 1 | theme.json constrained layout + skryf.php:51 inline padding |

**Every primitive on this page is defined in a page-scoped stylesheet** (`skryf.css`,
enqueued only when `is_page('skryf')`, `functions.php:80-102`). That is an R8 violation
for every kind above except section-band. Each `source` row carries the note.

## Declaration comparison

**PRIM-skryf-1 · button-primary: a replica of `is-style-ink-primary`.** The page's
`.ink-skryf-submit` (`skryf.css:468`) plus `.ink-skryf-actions button` (`:414`)
restate the registered block style (`functions.php:1445-1463`), declaration by
declaration:

| declaration | `is-style-ink-primary` | skryf replica |
|---|---|---|
| background-color | primary | primary |
| color | surface-alt | surface-alt |
| font-family | display | display |
| border | 0 | 0 |
| border-radius | radius-md | radius-md |
| box-shadow | shadow-md | shadow-md |
| transition | `all .15s` | `background-color, opacity .15s` |
| disabled | opacity .5 | adds `cursor:default; pointer-events:none` |
| size | — | adds `min-height:44px`, `padding:0 s-32`, `font-size:md`, `weight:medium` |

The added size recipe restates `kontak.css:115-123`, which uses `height:44px`, not
`min-height`. The replica exists because the actions are raw `<button>`s in `wp:html`
(disclosed at `skryf.css:395-399`). Against Lovable (editor-state drift): the text is
`rgb(255,255,255)` versus surface-alt `rgb(253,252,250)`, line-height `24px` versus
`normal`, white-space `nowrap` versus `normal`, and there is no 8px icon gap because the
icon is missing (S-13).

**PRIM-skryf-2 · button-outline: two unrelated recipes share the name.**
`.ink-skryf-draft` (`skryf.css:451, :458`) is a *neutral* outline: a 1px `border` colour,
`surface` background, `ink-text` and the body font. It matches Lovable's `variant="outline"`.
The registered `is-style-ink-outline` (`functions.php:1474-1492`) is a *primary* outline:
2px primary border, primary text, display font. That matches Lovable's
`variant="literary-outline"`. The two have no declaration in common except the radius.
The neutral recipe also appears as `.ink-auth-secondary` (`auth.css:232-239`). Stage 2
should treat these as two kinds, not variants of one.

**PRIM-skryf-3 · form-field: three page copies, divergent.** `skryf.css:214`,
`kontak.css:33-45` and `auth.css:106-123` share:

- width 100% and border-box sizing;
- padding `s-8 s-12`;
- a 1px `border` colour and radius-md;
- `surface` background and `ink-text` colour;
- font-size sm.

They differ in:

- **font-family:** skryf uses display (serif) for text and textarea (`:231`); kontak and
  auth use body.
- **height:** only auth fixes `s-40` (40px). skryf and kontak leave it implicit, and the
  measured skryf title input is **36px** against Lovable's 40px.
- **textarea:** skryf has `min-height:400px; resize:none` (`:271`); kontak has
  `min-height:160px; resize:vertical` (`kontak.css:60-64`).

Measured against Lovable (`Input` with `font-serif text-lg`): at 390 Lovable's title is
18px/28px and INK's is 14px/normal. At ≥768 both are 14px (Lovable's `md:text-sm` wins).

**PRIM-skryf-4 · form-label: duplicated.** `skryf.css:205` and the kontak label rule
(`kontak.css:25-30`) declare the same set: block, mb s-8, body, sm, medium, ink-text.
That is duplication without a single source. The measured line-height is 21px on INK
against 20px on Lovable.

**PRIM-skryf-5 · form-hint: three copies.** `skryf.css:313`, `kontak.css:69-74` and
`auth.css:140-146` all set `font-size:xs` and muted-text. Their display and margins
differ.

**PRIM-skryf-6 · checkbox: duplicated and natively rendered.** `skryf.css:355` and
`auth.css:184-189` both set 16×16 with `accent-color: primary`. skryf adds
`margin-top:2px; flex-shrink:0`, auth uses `margin:0`. The radio (`skryf.css:138`) is a
third 16px / accent-primary copy. Because the controls are native, the computed
font-family is Arial 13.33px, with no CSS border and no radius. Lovable's Radix checkbox
has a 1px primary border and 4px radius (MECHANISM, spine S-8).

**PRIM-skryf-7 · card: the type card versus `is-style-ink-card`.** `.ink-skryf-type`
(`skryf.css:91`) and `functions.php:1601-1616` share the surface-alt background,
radius-xl and padding s-24. They differ in:

| declaration | `is-style-ink-card` | `.ink-skryf-type` |
|---|---|---|
| border | 1px | 2px |
| box-shadow at rest | shadow-sm | none |
| hover | translateY(-4px) + shadow-lg | per-type border colour (`:108-118`) |
| selected state | — | primary border + shadow-md (`:120`) |

Lovable's card pads 24px below 768 and 28px at ≥768 (`p-6 md:p-7`); INK uses a fixed
24px. The challenges panel (`skryf.css:287`) is a third card recipe: 1px border,
radius-lg and padding s-16, where Lovable uses 20px.

**PRIM-skryf-8 · heading: a page-scoped mobile pin.** `.ink-skryf-title`
(`skryf.css:29-38`) pins the h1 at 1.875rem on mobile and `xxxxl` from 768, overriding
`theme.json` h1 (`xxxl`). Its own comment says the same pin is "already applied on
gemeenskap/lidmaatskap/oor-ink". That is a known, disclosed duplication of one h1 recipe
across at least four page sheets.

## Load-scope cross-check

Every `.ink-skryf-*` selector observed is:

- **defined** only in `assets/css/skryf.css`: no hit in `theme.json` `styles.css`, in any
  other `assets/css/*.css`, or in `functions.php`;
- **loaded** only when `is_page('skryf')` (`functions.php:81`);
- **emitted** only by `patterns/skryf.php`, which only `templates/page-skryf.html:5`
  includes. `header-main.php:12` names the pattern in a docblock only.

`used_in` therefore sits inside `load_scope`. **No inert class and no load-scope live bug
on this page.** The risk is latent: the pattern is styled only while it sits on the page
whose slug is `skryf`. Inserting it anywhere else would render it unstyled.

## Limits

- **Success screen not captured.** No primitive instances from it are registered, so its
  buttons (`is-style-ink-primary` / `is-style-ink-outline` at `skryf.php:74-80`) are not
  in the register.
- **Hover, focus and checked states** are recorded from source only (`source` rows);
  computed rows are at rest.
- **Two `lovable` capture states feed the register.** The rows come from the type-picker
  state and the editor state; the `note` column says which.

# 13 · oor-ink — Primitive observations (Phase 4b)

The register is `maps/_primitive-observations.oor-ink.csv`: 46 rows, made up of 11 `lovable`,
22 `ink` and 13 `source`.

**Inputs.** The Lovable captures are `oor-ink@{390,768,1440}-scope-sponsors` and the INK
captures are the full-page `oor-ink@{390,768,1440}`. All passed determinism and verify.
`run.sh primitives --exclude-chrome` was run over all six in one pass, so rows that are
identical across widths collapse to `390|768|1440`. The rows were then post-processed by
`tmp/r1314/register.py`. Header and footer are row 00's.

**Hand resolution** (README §4.1):
- The tool's generic `button` rows were resolved to variants:
  - `.ink-borg-erkenning__cta` → `button-sage-outline`: accent 2px border, accent text,
    transparent fill.
  - The *Kontak ons* `core/button` link → `button-primary`. It has primary colour block
    attributes and no `is-style-ink-primary` wrapper.
- The tool missed some rows, which were added from the captures:
  - every `tier-chip`, both sides: 6 Lovable and 6 INK, every instance (row 00's convention);
  - the core/list link (`link-underline`, first instance).
- The block-root `section.ink-borg-erkenning` that the tool tags as `section-band` was dropped,
  following row 02's convention.
- `source` rows give each `theme.json` rule that defines a selector used here, as written, one
  rule per row. The parallel homepage definitions in `home.css` are row 00's rows; they are
  cited in `note` and not duplicated.

**Attribution.** Every class on this page is defined in `theme.json` styles.css, in inline block
attributes, or in `theme.json` element styles. All of them are therefore **site-wide**.
`used_in` is `patterns/oor-ink.php`, `patterns/borg-erkenning.php` or `RecognitionSection.php`.
Each was read to confirm the class is emitted.

---

## Declaration comparison

### tier-chip — one recipe, two definitions, and both diverge from Lovable the same way

| | Lovable (`SponsorsSection.tsx:37-43`) | INK `.ink-borg-erkenning__item*` (theme.json) | INK `.ink-borg-strook__chip*` (`home.css:1166-1221`, row 00) |
|---|---|---|---|
| padding / radius | 12/24 · 8px | 12/24 · `radius.lg` | identical to theme.json |
| chip font | Inter 400 (the serif is on the inner name span) | Lora (`heading`) 500 on the `<li>` | identical |
| gold tint | `gold` #E8B130 at 10% bg / 20% border | **`goud`** #C9B88A at 10% / 20% | identical |
| gold size | box 16px, name `text-lg` 18px | `lg` 18px on the `<li>` | identical |
| silver | `secondary` bg + `border` | `secondary` + `border` | identical |
| bronze | `muted/50`, **no border** | `secondary` at 50% + **1px transparent border** | identical |

The two INK definitions are declaration-for-declaration identical: one variant (N=1) with two
definitions (M=2). That is duplication, and it diverges the moment one copy is edited. Against
Lovable both copies differ in the same three places: the chip-box family and weight, the gold
hue (`goud` vs `gold`; both are palette tokens) and the bronze border.

### eyebrow — three recipes on one page

| | colour | size | weight | tracking | line-height | defined |
|---|---|---|---|---|---|---|
| Lovable `sponsors-eyebrow` | sage (82,122,102) | 14px | 500 | 0.7px (0.05em) | 20px | Tailwind |
| INK `sponsors-eyebrow` | accent (82,122,102) | 14px | 500 | 1.12px (0.08em) | 21px | theme.json `.ink-borg-erkenning__etiket`; identical copy at `home.css:1114` |
| INK `oor-ink-hero-eyebrow` | **primary** (236,59,19) | **12px** | 500 | **2.4px (0.2em)** | 18px | inline block attrs, `oor-ink.php:36` |

`oor-ink.php:23-27` says the hero recipe was copied from "the Lovable-verified gemeenskap
plain-uppercase-eyebrow recipe". So the page now carries both of the eyebrow recipes that exist
site-wide, one above the other.

### heading

| | size | weight | lh | margins | defined |
|---|---|---|---|---|---|
| Lovable `sponsors-h2` | 24px → 30px @md | 600 | 32/36px | `mt-2 mb-4` | Tailwind |
| INK `sponsors-h2` | 24px → 32px @768 | 600 | 1.2 | 0 (flex gap 16px) | theme.json `.ink-borg-erkenning__titel`: **literal `24px!important` / `32px!important`** |
| INK homepage twin | `xxl` → `xxxl` tokens | 600 | 1.2 | `s-8` / `s-16` | `home.css:1123-1136` (row 00) |
| INK C headings (h1 + 3×h2) | 32px / 24px (`!important` pins) | **700** | 1.2 | — | theme.json `elements.h1/h2` + `.ink-oor-ink-*` pins |

The sponsor heading has two definitions with **different declarations**: literal `!important`
sizes against tokens, and margins against a gap. The C headings fall to UA bold (700) because
`styles.elements.heading` sets no weight.

### button-sage-outline — three INK copies, one recipe, and the font differs from Lovable

The definitions are `.ink-borg-erkenning__cta` (theme.json), `.ink-borg-strook__cta`
(`home.css:168,1243`) and `is-style-ink-sage-outline` (`functions.php`, row 00). Every INK copy
uses the **display** family (Lora). Lovable's `sage-outline` inherits Inter. The theme.json copy
alone adds `box-sizing:border-box`. Padding (8px/32px with `min-height:44px`), border, radius
and colour agree, and match Lovable's `h-11 px-8` in effect.

### button-primary (*Kontak ons*, C region)

This is `core/button` with `backgroundColor:primary` and `textColor:surface-alt` attributes
(`oor-ink.php:86-87`). It is styled by theme.json `elements.button` (Lora, `radius.md`) plus a
page-scoped rule, `.ink-oor-ink-kontak .wp-block-buttons .wp-block-button__link{padding:0 32px;
height:44px;font-weight:500}`. That rule is a primitive defined page-locally (R8), and
`oor-ink.php:18-27` discloses it. The text colour is `surface-alt` (253,252,250), where every
Lovable primary button uses white.

### section-band

The C bands are 64px/24px padding from inline block attributes; *org* and *meer* are filled with
`surface-alt`. The sponsor band (`borg-erkenning.php:20`) has the same padding and **no fill**,
whereas Lovable has `secondary/20` and the homepage twin has `.ink-borg-band`.

## Load-scope cross-check

Every selector used on this page is defined site-wide (theme.json or inline), so **no class is
inert here**. The reverse direction holds too: `home.css`'s `.ink-borg-strook*` loads on
`is_front_page() || is_page('qa-bloks')`, and `HomepageStrip` renders only there. There is no
live load-scope bug on row 13.

## Vocabulary

No new `component_kind`. Rows 13 and 14 use only existing kinds: `tier-chip`, `eyebrow`,
`heading`, `button-sage-outline`, `button-primary`, `section-band` and `link-underline`.

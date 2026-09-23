# 01 — Primitives audit (cross-cutting)

**Scope:** a partial, early sample — not the audit · **Produced:** 2026-09-23
**Status:** evidence + seed rows for the Stage 1 register. **No action follows from this
document on its own.**

This explains the long-standing symptom *"buttons match on one page but not another,
sometimes not even in another section of the same page."* It was produced before the
staged pipeline existed, by surveying stylesheets directly rather than by auditing pages.

**How to use it:**

- As **evidence** that the primitive problem is real and large — it is what motivated
  rules R8 and R9 and the four-stage order in `README.md` §4.0.
- As **seed rows** for `maps/_primitive-observations.csv`. The definitions catalogued in
  §3–§6 belong in the register; transcribe them rather than rediscovering them.
- As a **worked example** of the two checks that matter: comparing declarations between
  two definitions of one component, and cross-checking a definition's load scope against
  where its class is used.

**What not to do with it:** do not work §8 as a remediation queue yet. Its findings are
real but its evidence base is partial — it surveyed stylesheets, not pages, so it cannot
see which variant is *correct*, only that variants exist. Choosing a canonical recipe from
this document would be exactly the premature consolidation R9 forbids. The remediation
order in §8 is retained as a *sketch* for Stage 3, to be re-derived from the complete
register.

---

## 1. Sources read

- `wp-content/themes/ink-foundation/functions.php` — block-style registrations
  (`ink_foundation_register_block_styles()`, lines 1274-1757) and all seven
  `wp_enqueue_style` call sites
- `wp-content/themes/ink-foundation/theme.json` — the global `styles.css` payload
  (100,674 chars, 596 selectors)
- `wp-content/themes/ink-foundation/assets/css/` — `home.css`, `auth.css`, `kontak.css`,
  `profiel.css`, `reading.css`, `skryf.css`, `wp-login-brand.css`

---

## 2. Headline

A shared primitive layer **exists**: `functions.php:1274-1757` registers 11 site-wide block
styles — `ink-primary`, `ink-outline`, `ink-ghost`, `ink-sage`, `ink-sage-outline`,
`ink-card`, `ink-header`, `ink-footer`, `card`, `pill`, `emphasis`.

It is comprehensively bypassed. Counting selectors that define component appearance:

| Primitive family | Distinct selectors | Files defining them | Registered block styles |
|---|---|---|---|
| button | **57** | 6 | 5 |
| heading / eyebrow | 35 | 4 | — |
| card | 21 | 3 | 2 |
| pill / badge | 19 | 3 | 1 |
| form field | 19 | 5 | 0 |

*The family classifier is coarse — it matches on selector name, so a container selector
whose descendant is a button is counted too. Treat the counts as magnitude, not as an exact
defect list. The duplications in §4 are exact and were compared declaration by
declaration.*

---

## 3. `PRIM-1` — a primitive defined in a page-scoped stylesheet · **live bug**

Button **size** (`.ink-btn-sm`, `.ink-btn-lg`, `.ink-btn-xl`, `.ink-btn-icon`) is defined
only in `assets/css/home.css:196-303`. That stylesheet is enqueued on the front page and
the QA gallery only (`functions.php:860-876`).

The classes are used in four patterns:

| Pattern | Page | Result |
|---|---|---|
| `patterns/hero.php:45,49` | front page | works |
| `patterns/cta-band.php:43,47` | front page | works |
| `patterns/gemeenskap.php` | `/gemeenskap` | **no CSS loaded — class is inert** |
| `patterns/reading-uitdaging.php` | single uitdaging | **no CSS loaded — class is inert** |

Buttons on Gemeenskap and on a challenge page carry `ink-btn-lg ink-btn-icon` in the markup
and render at default size, while byte-identical markup on the homepage renders large.

**Class:** `PRIMITIVE` · **Fix:** move the size modifiers to the site-wide layer
(`theme.json` global styles, or a block style). Do not copy them into a second page
stylesheet.

---

## 4. `PRIM-2` — the same component implemented independently per page · **proven drift**

Three sections implement a search box. Each has its own definition in `theme.json`'s global
CSS, and all three differ:

**Search button** — 3 instances, 3 distinct definitions:

| | `.ink-ontdek-soek__knoppie` | `.ink-opleiding__soek-knoppie` | `.ink-biblioteek__soek-knoppie` |
|---|---|---|---|
| font-family | `body` | *(unset)* | `inherit` |
| font-weight | `600` | `--font-weight--medium` | `--font-weight--medium` |
| font-size | `sm` | *(unset)* | *(unset)* |

Same padding, radius, colour and transition — so the divergence is purely in type, which is
exactly the class of difference that reads as "nearly right".

**Search field** — 3 instances, 3 distinct definitions:

| | `ontdek` | `opleiding` | `biblioteek` |
|---|---|---|---|
| sizing | `width:100%` | `flex:1` | `flex:1` |
| background | `surface` | `surface-alt` | `surface-alt` |
| vertical padding | `s-12` | `s-8` | `s-12` |
| font-size | `sm` | `sm` | `md` |
| line-height | *(unset)* | `1.25rem` *(literal)* | `--line-height--normal` |

**Different background colour for the same control.** Note also the raw `1.25rem` literal —
an `OFF-TOKEN` value inside the primitive layer itself.

**Filter chip** — `.ink-opleiding__filter-knoppie` and `.ink-biblioteek__filter-knoppie`
are currently **byte-identical**. No drift yet; pure duplication, and a guaranteed future
divergence the first time one is touched.

**Class:** `PRIMITIVE` · **Fix:** one `search-field` / `search-button` / `filter-chip`
recipe in the shared layer, consumed by all three.

---

## 5. `PRIM-3` — primary-button appearance re-implemented at least six times

| Definition | Where |
|---|---|
| `is-style-ink-primary` block style | `functions.php:1333` — *the sanctioned one* |
| `.ink-cta-band__knoppie--primer` | `home.css` (front page only) |
| `.ink-auth-submit button` | `auth.css:207-223` (3 auth pages only) |
| `.ink-skryf-submit` | `skryf.css:468-475` (skryf only) |
| `.ink-kontak-vorm__stuur` | `kontak.css` (kontak only) |
| `.ink-leseroordeel-vorm__stuur` | `theme.json` global |

Plus four per-page overrides of the *core* button link, each re-specifying appearance for
one page's buttons: `.ink-uitdaging-cta`, `.ink-gemeenskap-cta`, `.ink-lidmaatskap-plans`,
`.ink-oor-ink-kontak` (all `theme.json` global).

**Class:** `PRIMITIVE`

---

## 6. `PRIM-4` — the card recipe is replicated rather than reused, by design decision

`patterns/hero.php:11-16` states it outright: the `is-style-ink-card` recipe is
*"replicated there rather than wrapping the COLLAPSING block in a static
`is-style-ink-card` group (which would show an empty card when no challenge is open)"*.

The reasoning is sound — the constraint is real. The *implementation* is what creates
drift: the recipe now exists twice, with nothing keeping the copies in step.

**Class:** `PRIMITIVE` · **Fix:** make the recipe a class the block's own markup can carry
(so the collapse still works), rather than a copy. Owner decision, since it changes a
documented deliberate choice — see rule R6.

---

## 7. Root cause

Every stylesheet in the theme is gated to a page or post type:

| Stylesheet | Gate | `functions.php` |
|---|---|---|
| `home.css` | `is_front_page()` or QA gallery | 860-876 |
| `skryf.css` | `is_page('skryf')` | 80-102 |
| `kontak.css` | `is_page('kontak')` | 142-156 |
| `auth.css` | `is_page(['meld-aan','registreer','wagwoord-herstel'])` | 172-186 |
| `reading.css` | `is_singular(['gedig','storie','artikel'])` | 205-219 |
| `profiel.css` | *(page-scoped)* | — |

Page scoping is correct for genuinely page-specific layout. It is wrong for primitives, and
nothing in the theme currently enforces that distinction — so each fidelity pass, working
one page at a time, added another page-scoped sheet that restyled the same components. The
enqueue docblocks narrate exactly this: *"the same 'zero CSS' gap already found and fixed
on kontak/skryf"* (`functions.php:167-168`).

**This is the mechanism by which *fixing* page-by-page makes the site less consistent** —
and it is the reason primitives are consolidated once, at the shared layer, in a single
pass (Stage 3).

Note the distinction the staged pipeline rests on: *auditing* page-by-page is exactly
right — it is the only way to discover what the primitive set actually is, and which
variant of each the reference supports. It is *fixing* page-by-page that causes the harm.
Stage 1 audits every page and changes nothing; Stages 2–3 then consolidate from a complete
picture (R9).

---

## 8. Remediation sketch — for Stage 3, not for now

**Not a work queue.** Re-derive this from the complete observation register after Stage 2
synthesis. It is recorded here only so the shape of the eventual work is visible.

1. `PRIM-1` — move button sizing into the shared stylesheet. Smallest, and un-breaks
   Gemeenskap and the uitdaging pages.
2. `PRIM-2` — collapse search field / search button / filter chip to one recipe each.
3. `PRIM-3` — retire the bespoke submit buttons onto the canonical primary-button recipe.
4. `PRIM-4` — owner decision first (R6), then implement.
5. Re-audit: no primitive defined outside `assets/css/primitives.css`; no component with
   two definitions.

Which *variant* of each becomes canonical is a Stage 2 decision and is deliberately not
answered here — this document surveyed stylesheets, not pages, and cannot see which
variant the reference supports.

---

## 9. Limits

- **Audited:** buttons and cards in full; search fields and filter chips in full.
- **Not audited:** pills/badges (19 selectors), headings/eyebrows (35), remaining form
  controls (19), section bands. Same method applies; the counts in §2 suggest similar
  findings.
- **No rendered measurement.** Every claim here is a definition-level claim — which
  selectors exist, where they are defined, what they declare, and where they load. What
  they *compute to* is Phase 4 (R4). `PRIM-2`'s drift is provable from declarations alone
  because the properties differ literally, not merely in resolved value.

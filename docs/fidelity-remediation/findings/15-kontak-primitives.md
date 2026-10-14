# 15 · kontak — primitive register (Phase 4b)

**Page:** `https://nuwe-ink.local/kontak/` · **Treatment:** C · **Date:** 2026-10-13 · **Stage 1**
**Register:** `maps/_primitive-observations.kontak.csv` (18 `ink` rows, 7 `source` rows)
**Captures:** `tmp/fidelity-captures/ink/kontak@{390,768,1440}.ndjson` — determinism gate 3/3 identical and
`verify --sample 30` 0 mismatches at every width. No duplicate audit-ids.

Header and footer were verified on row 00 (`--exclude-chrome`) and are not re-reported.

## Files read end to end

- `templates/page-kontak.html`, `parts/header.html` / `parts/footer.html` (one-line forwards, row 00)
- `patterns/kontak.php`
- `plugins/ink-core/src/Forms/ContactForm.php` (`ink/kontak-vorm`)
- `assets/css/kontak.css`
- `functions.php` (all 2354 lines: enqueue gates, block styles)
- `theme.json` — `settings` and `styles.elements` read; `styles.css` parsed rule by rule (599 rules)

## How the raw output was resolved

| Raw tool row | Resolution |
|---|---|
| generic `button` (`.wp-element-button`, `kontak-submit`) | `button-primary`: primary fill, surface-alt text, radius md. It is a raw `<button>` with no block-style variant |
| `form-label` 16px/400 with no id + `form-field` input 348px with no id | **dropped**: the off-screen honeypot (`.ink-kontak-vorm__hp`, `ContactForm.php:171`), as rows 09a/09b dropped `.ink-reg-hp` |
| `kontak-hint` (`.ink-kontak-vorm__wenk`) | **added by hand** as `form-hint`, since the tool has no form-hint detector |
| `kontak-privacy` (`.ink-kontak-vorm__privaatheid`) | **added by hand** as `form-hint` (same xs / muted recipe) |
| `form-field` rows | per field. The widths differ by viewport (342px at 390, 640px at 768/1440), so each field has two rows |

Not captured: the result notice (`.ink-kontak-vorm__notice--ok|--fout`, `kontak.css:83-100`) renders only after a
round-trip, and no form was submitted. If it is ever registered, it is an alert banner, and no vocabulary kind
fits it yet. Focus rings (`kontak.css:53`, `:130`) and hover (`:126`) were not captured either.

## Declaration comparison

- **button-primary — the 44px sizing layer is defined three times.** `kontak.css:115` (`height:44px; padding:0
  s-32; font-weight:medium; inline-flex`), `theme.json` styles.css #562 (`.ink-lidmaatskap-plans … .wp-block-button__link`,
  the same four values plus centring) and #577 (`.ink-oor-ink-kontak …`, the same values but no `display`). The
  colour, family and radius come from a fourth source, `theme.json:214` `elements.button`. The rendered result here
  is radius **6px**. Lidmaatskap renders the same sizing with radius **9999px** (`is-style-pill`). So two
  "primary CTA" recipes differ only in radius.
- **form-hint — three definitions.** `kontak.css:69` (`display:block; margin-bottom:s-8; xs; muted`) vs
  `auth.css:140` (`display:block; margin-top:s-4; font-family:body; xs; muted`) vs `skryf.css:313`. Kontak's sets
  no `font-family` and inherits Inter. The spacing direction differs (below vs above).
- **h1 size pin.** `.ink-kontak-hero h1.has-xxxl-font-size{font-size:32px!important}` (#578) is one of four
  section-scoped copies (#560, #574, #575, #578) that override the fluid `xxxl` preset to a flat 32px. This
  duplication holds the page heading scale together.
- `form-label` / `form-field` (`kontak.css:24`, `:33`, `:60`): each is the only definition on this page. Stage 2
  compares them with the `auth.css` / `skryf.css` field recipes in the other registers.

## Load-scope cross-check

| Definition | load_scope | used_in | Result |
|---|---|---|---|
| `assets/css/kontak.css` (all rules) | `is_page('kontak')` (`functions.php:143`) | `ContactForm.php`, embedded only by `patterns/kontak.php` | consistent today. **Latent:** the `ink/kontak-vorm` block placed on any other page renders unstyled (raw browser form, Arial inputs) |
| `theme.json` #578 (h1 pin) | site-wide | `patterns/kontak.php` | consistent |
| `theme.json:214` elements.button | site-wide | everywhere | consistent |

No live load-scope bug on this page.

## OFF-TOKEN

0 off-token values (`findings/15-kontak-conformance.md`).

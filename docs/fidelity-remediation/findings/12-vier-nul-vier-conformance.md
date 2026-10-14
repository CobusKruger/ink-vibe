# 12 · vier-nul-vier (404) — OFF-TOKEN conformance (Treatment C, Stage 1)

**GENERATED — do not hand-edit.** Generator: `tmp/rows-c/off-token.py`. Rerun (sandbox off, from the repo root):

```bash
python3 tmp/rows-c/off-token.py --page vier-nul-vier --nn 12 --title 'vier-nul-vier (404)' \
    --out docs/fidelity-remediation/findings/12-vier-nul-vier-conformance.md
```

Generated 2026-10-13 from:

- `tmp/fidelity-captures/ink/vier-nul-vier@390.ndjson` — https://nuwe-ink.local/storie/pannekoek/ @ 390px, 107 nodes, 35 audit-ids
- `tmp/fidelity-captures/ink/vier-nul-vier@768.ndjson` — https://nuwe-ink.local/storie/pannekoek/ @ 768px, 107 nodes, 35 audit-ids
- `tmp/fidelity-captures/ink/vier-nul-vier@1440.ndjson` — https://nuwe-ink.local/storie/pannekoek/ @ 1440px, 107 nodes, 35 audit-ids

## Capture coverage and structural notes

*(Hand-authored context, kept in the generator's `PAGE_NOTES` so a rerun reproduces it. Header and footer were verified on row 00 and are not re-reported.)*

- **Resolution chain.** `https://nuwe-ink.local/storie/pannekoek/` returns HTTP 404 with body class `error404`. The theme has no `404.html`, so WordPress falls back to `templates/index.html`: header part → `<main>` (padding s-64 / s-24) → an `alignwide` group holding `core/post-title` + `core/post-content` → footer part. On a 404 both dynamic blocks emit nothing, so `<main>` renders an **empty** group (`vier-nul-vier-inner`, 0px tall). The document `<title>` is `Page not found - INK` (English, from WordPress core).
- **Lovable reference (for orientation only, no diff).** `src/pages/NotFound.tsx` (route `*`, `src/App.tsx:44`) was built to `tmp/lovable-dist/notfound`, served on :4192 and captured at 390/768/1440 (`tmp/fidelity-captures/lovable/vier-nul-vier@*.ndjson`; `run.sh verify --sample 30` passed at all three, no gate run, no audit-ids). It renders **no header or footer**: one full-viewport `bg-muted` flex box (`rgb(243, 240, 237)` = `muted` token) centring an `h1` "404" (36px / 700 / Lora / 40px line-height), a `p` "Oops! Page not found" (20px, muted-foreground) and an underlined primary link "Return to Home" (16px).
- **Obvious structural deltas** (§5 classes; record only, owner decides):
  - `MISSING` — INK shows no heading, no explanatory message and no link home; the visitor sees header, ~128px of empty `<main>` padding, and footer. (`templates/index.html:3-13` vs `NotFound.tsx:13-19`.)
  - `STRUCTURE` — Lovable's 404 is a bare full-screen page; INK keeps site chrome. Keeping chrome is arguably better UX; it is an owner call, not a defect by itself.
  - `SCOPE` — any heading / message / link INK adds needs authored Afrikaans copy (R2), and the English `<title>` "Page not found" is copy debt.
- **Shared fallback.** `templates/index.html` also serves any other route without a dedicated template, so the `vier-nul-vier-*` ids appear there too.

## Method

- **Token set** = `docs/design-handoff/tokens/theme-tokens.json` ∪ `wp-content/themes/ink-foundation/theme.json` settings (palette, fontSizes incl. fluid min–max ranges, spacingSizes, shadow presets, custom radius / lineHeight / fontWeight), as mapped by `docs/design-handoff/tokens/token-map.md`. Both value sets are accepted where they differ (e.g. primary `#EA4015` vs `#EC3B13`, accent `#4D8066` vs `#527A66`).
- **Scope:** every node inside `<main>` (header/footer are row 00's). Nodes with `display:none` or a 0×0 box (e.g. closed `<details>` content, hidden inputs) are skipped; off-screen but boxed nodes are kept.
- **Colour:** `background-color` on every node; `color` only on text-bearing nodes (own text, or `a`/`button`/`input`/`textarea`/`select`/`svg`); `border-top-color` only where a top border is drawn. RGB within ±1 of a token. A token colour at alpha < 1 is listed separately (not counted OFF-TOKEN).
- **Type:** font-size must equal a preset (px at 16px root) or fall inside a fluid preset's min–max; line-height ÷ font-size must be 1.2 / 1.5 / 1.7 (±0.02); family and weight must be token members.
- **Spacing:** padding / margin / gap, absolute value, ±0.5px of a spacing step. Equal left/right margins that are not a step are treated as `auto` centring and ignored.
- **Radius:** a radius step or ≥ 9999px (full). **Shadow:** each layer must equal a preset (colour, alpha, offsets).
- Values of `0` / `auto` / `none` / `normal` / `transparent` are ignored. Counts are instances summed across the three viewports.

## Summary

Nodes in `<main>`: 6 (of 321); skipped as unrendered: 0; centring margins ignored: 0.

| Property group | Values tested | On token | OFF-TOKEN | Distinct off-token values |
|---|---|---|---|---|
| colour | 0 | 0 | 0 | 0 |
| font-size | 0 | 0 | 0 | 0 |
| line-height | 0 | 0 | 0 | 0 |
| font-family | 0 | 0 | 0 | 0 |
| font-weight | 0 | 0 | 0 | 0 |
| spacing | 17 | 17 | 0 | 0 |
| radius | 0 | 0 | 0 | 0 |
| shadow | 0 | 0 | 0 | 0 |

**0 OFF-TOKEN instances, 0 distinct (property, value) pairs.**

## OFF-TOKEN values

### Colour

None.

### Type (font-size, line-height, family, weight)

None.

### Spacing (padding / margin / gap)

None.

### Radius

None.

### Shadow

None.

## Token colours at reduced alpha (listed, not counted)

None.

## Limits

- Membership only: an on-token value can still be the *wrong* token for its component. That is the `OFF-RECIPE` check, deferred to Stage 4 (README §4.6).
- Computed values only (R4): which stylesheet produced an off-token literal is not known here; see the page's `-primitives.md` for source attribution of primitive kinds.
- Pseudo-elements, hover/focus states and unrendered branches are not captured.

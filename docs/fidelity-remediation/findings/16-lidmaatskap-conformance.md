# 16 · lidmaatskap — OFF-TOKEN conformance (Treatment C, Stage 1)

**GENERATED — do not hand-edit.** Generator: `tmp/rows-c/off-token.py`. Rerun (sandbox off, from the repo root):

```bash
python3 tmp/rows-c/off-token.py --page lidmaatskap --nn 16 \
    --out docs/fidelity-remediation/findings/16-lidmaatskap-conformance.md
```

Generated 2026-10-13 from:

- `tmp/fidelity-captures/ink/lidmaatskap@390.ndjson` — https://nuwe-ink.local/lidmaatskap/ @ 390px, 165 nodes, 63 audit-ids
- `tmp/fidelity-captures/ink/lidmaatskap@768.ndjson` — https://nuwe-ink.local/lidmaatskap/ @ 768px, 165 nodes, 63 audit-ids
- `tmp/fidelity-captures/ink/lidmaatskap@1440.ndjson` — https://nuwe-ink.local/lidmaatskap/ @ 1440px, 165 nodes, 63 audit-ids

## Capture coverage and structural notes

*(Hand-authored context, kept in the generator's `PAGE_NOTES` so a rerun reproduces it. Header and footer were verified on row 00 and are not re-reported.)*

- Captured anonymously. The three FAQ `<details>` are closed, so their answers (`lidmaatskap-faq-answer`) are 0×0 and skipped. The open state, hover and focus were not captured.
- Live data observations (record only): all three plans render **R50,00** and link to the same `?add-to-cart=926` (also seen on row 02's renewal plans). The plan descriptions render a **literal backslash**, "Volle toegang vir \\’n maand." (`lidmaatskap.php:38,40`: `\&#8217;` inside a single-quoted `__()` string).
- The disabled-plan branch (`Binnekort beskikbaar`, `lidmaatskap.php:141`) and the ink-core-inactive fallback do not render here.

## Method

- **Token set** = `docs/design-handoff/tokens/theme-tokens.json` ∪ `wp-content/themes/ink-foundation/theme.json` settings (palette, fontSizes incl. fluid min–max ranges, spacingSizes, shadow presets, custom radius / lineHeight / fontWeight), as mapped by `docs/design-handoff/tokens/token-map.md`. Both value sets are accepted where they differ (e.g. primary `#EA4015` vs `#EC3B13`, accent `#4D8066` vs `#527A66`).
- **Scope:** every node inside `<main>` (header/footer are row 00's). Nodes with `display:none` or a 0×0 box (e.g. closed `<details>` content, hidden inputs) are skipped; off-screen but boxed nodes are kept.
- **Colour:** `background-color` on every node; `color` only on text-bearing nodes (own text, or `a`/`button`/`input`/`textarea`/`select`/`svg`); `border-top-color` only where a top border is drawn. RGB within ±1 of a token. A token colour at alpha < 1 is listed separately (not counted OFF-TOKEN).
- **Type:** font-size must equal a preset (px at 16px root) or fall inside a fluid preset's min–max; line-height ÷ font-size must be 1.2 / 1.5 / 1.7 (±0.02); family and weight must be token members.
- **Spacing:** padding / margin / gap, absolute value, ±0.5px of a spacing step. Equal left/right margins that are not a step are treated as `auto` centring and ignored.
- **Radius:** a radius step or ≥ 9999px (full). **Shadow:** each layer must equal a preset (colour, alpha, offsets).
- Values of `0` / `auto` / `none` / `normal` / `transparent` are ignored. Counts are instances summed across the three viewports.

## Summary

Nodes in `<main>`: 180 (of 495); skipped as unrendered: 0; centring margins ignored: 18.

| Property group | Values tested | On token | OFF-TOKEN | Distinct off-token values |
|---|---|---|---|---|
| colour | 138 | 138 | 0 | 0 |
| font-size | 90 | 90 | 0 | 0 |
| line-height | 90 | 90 | 0 | 0 |
| font-family | 90 | 90 | 0 | 0 |
| font-weight | 90 | 90 | 0 | 0 |
| spacing | 302 | 302 | 0 | 0 |
| radius | 108 | 108 | 0 | 0 |
| shadow | 18 | 18 | 0 | 0 |

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

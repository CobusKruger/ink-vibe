# 18 · ledegids — OFF-TOKEN conformance (Treatment C, Stage 1)

**GENERATED — do not hand-edit.** Generator: `tmp/rows-c/off-token.py`. Rerun (sandbox off, from the repo root):

```bash
python3 tmp/rows-c/off-token.py --page ledegids --nn 18 \
    --out docs/fidelity-remediation/findings/18-ledegids-conformance.md
```

Generated 2026-10-13 from:

- `tmp/fidelity-captures/ink/ledegids@390.ndjson` — https://nuwe-ink.local/ledegids/ @ 390px, 151 nodes, 58 audit-ids
- `tmp/fidelity-captures/ink/ledegids@768.ndjson` — https://nuwe-ink.local/ledegids/ @ 768px, 151 nodes, 58 audit-ids
- `tmp/fidelity-captures/ink/ledegids@1440.ndjson` — https://nuwe-ink.local/ledegids/ @ 1440px, 151 nodes, 58 audit-ids

## Capture coverage and structural notes

*(Hand-authored context, kept in the generator's `PAGE_NOTES` so a rerun reproduces it. Header and footer were verified on row 00 and are not re-reported.)*

- `ink/ontdek-skrywers` (`Discovery\SkrywersTab`) is the same block row 03 measured on `/ontdek`; its `writers-*` ids are row 03's and are reused unchanged. Only the six `ledegids-*` ids are this page's own.
- Structure observation: the page stacks the pattern's `h1` "Ledegids" directly above the block's own `h2` "Skrywers" (`ledegids.php:33`, `SkrywersTab.php:242`) — two headings for one listing.
- Data: 3 writers render (fewer than `PER_PAGE` 12, so no pagination): two QA users ("INK QA MyProfiel", "INK QA Skryf") and "Cobus Kruger" — QA accounts are visible on the real page. Avatars are Gravatar defaults. Genre / sort filter states other than the default were not captured.

## Method

- **Token set** = `docs/design-handoff/tokens/theme-tokens.json` ∪ `wp-content/themes/ink-foundation/theme.json` settings (palette, fontSizes incl. fluid min–max ranges, spacingSizes, shadow presets, custom radius / lineHeight / fontWeight), as mapped by `docs/design-handoff/tokens/token-map.md`. Both value sets are accepted where they differ (e.g. primary `#EA4015` vs `#EC3B13`, accent `#4D8066` vs `#527A66`).
- **Scope:** every node inside `<main>` (header/footer are row 00's). Nodes with `display:none` or a 0×0 box (e.g. closed `<details>` content, hidden inputs) are skipped; off-screen but boxed nodes are kept.
- **Colour:** `background-color` on every node; `color` only on text-bearing nodes (own text, or `a`/`button`/`input`/`textarea`/`select`/`svg`); `border-top-color` only where a top border is drawn. RGB within ±1 of a token. A token colour at alpha < 1 is listed separately (not counted OFF-TOKEN).
- **Type:** font-size must equal a preset (px at 16px root) or fall inside a fluid preset's min–max; line-height ÷ font-size must be 1.2 / 1.5 / 1.7 (±0.02); family and weight must be token members.
- **Spacing:** padding / margin / gap, absolute value, ±0.5px of a spacing step. Equal left/right margins that are not a step are treated as `auto` centring and ignored.
- **Radius:** a radius step or ≥ 9999px (full). **Shadow:** each layer must equal a preset (colour, alpha, offsets).
- Values of `0` / `auto` / `none` / `normal` / `transparent` are ignored. Counts are instances summed across the three viewports.

## Summary

Nodes in `<main>`: 138 (of 453); skipped as unrendered: 0; centring margins ignored: 6.

| Property group | Values tested | On token | OFF-TOKEN | Distinct off-token values |
|---|---|---|---|---|
| colour | 129 | 129 | 0 | 0 |
| font-size | 63 | 63 | 0 | 0 |
| line-height | 63 | 57 | 6 | 1 |
| font-family | 63 | 63 | 0 | 0 |
| font-weight | 63 | 63 | 0 | 0 |
| spacing | 379 | 355 | 24 | 2 |
| radius | 192 | 192 | 0 | 0 |
| shadow | 9 | 9 | 0 | 0 |

**30 OFF-TOKEN instances, 3 distinct (property, value) pairs.**

## OFF-TOKEN values

### Colour

None.

### Type (font-size, line-height, family, weight)

| Property | Value | Count | Viewports | Note | Examples |
|---|---|---|---|---|---|
| line-height | `22.4px (÷14px = 1.600)` | 6 | 390\|768\|1440 | tokens 1.2 / 1.5 / 1.7 | `writers-card-bio`; `p.ink-ontdek-skrywers__bio` in `writers-grid` |

### Spacing (padding / margin / gap)

| Property | Value | Count | Viewports | Note | Examples |
|---|---|---|---|---|---|
| padding-bottom | `2px` | 12 | 390\|768\|1440 | nearest s-4 (theme-tokens) (4px) | `writers-card-genre`; `span.ink-ontdek-skrywers__genre-pil` in `writers-grid` |
| padding-top | `2px` | 12 | 390\|768\|1440 | nearest s-4 (theme-tokens) (4px) | `writers-card-genre`; `span.ink-ontdek-skrywers__genre-pil` in `writers-grid` |

### Radius

None.

### Shadow

None.

## Token colours at reduced alpha (listed, not counted)

| Property | Value | Count | Viewports | Token | Examples |
|---|---|---|---|---|---|
| border-top-color | `color(srgb 0.92549 0.231373 0.0745098 / 0.4)` | 12 | 390\|768\|1440 | primary (theme.json) at alpha 0.4 | `writers-card-genre`; `span.ink-ontdek-skrywers__genre-pil` in `writers-grid` |

## Limits

- Membership only: an on-token value can still be the *wrong* token for its component. That is the `OFF-RECIPE` check, deferred to Stage 4 (README §4.6).
- Computed values only (R4): which stylesheet produced an off-token literal is not known here; see the page's `-primitives.md` for source attribution of primitive kinds.
- Pseudo-elements, hover/focus states and unrendered branches are not captured.

# 17 · inkpols — OFF-TOKEN conformance (Treatment C, Stage 1)

**GENERATED — do not hand-edit.** Generator: `tmp/rows-c/off-token.py`. Rerun (sandbox off, from the repo root):

```bash
python3 tmp/rows-c/off-token.py --page inkpols --nn 17 \
    --out docs/fidelity-remediation/findings/17-inkpols-conformance.md
```

Generated 2026-10-13 from:

- `tmp/fidelity-captures/ink/inkpols@390.ndjson` — https://nuwe-ink.local/inkpols/ @ 390px, 111 nodes, 38 audit-ids
- `tmp/fidelity-captures/ink/inkpols@768.ndjson` — https://nuwe-ink.local/inkpols/ @ 768px, 111 nodes, 38 audit-ids
- `tmp/fidelity-captures/ink/inkpols@1440.ndjson` — https://nuwe-ink.local/inkpols/ @ 1440px, 111 nodes, 38 audit-ids

## Capture coverage and structural notes

*(Hand-authored context, kept in the generator's `PAGE_NOTES` so a rerun reproduces it. Header and footer were verified on row 00 and are not re-reported.)*

- **List:** the database holds **zero** `inkpols_uitgawe` posts in any status, so `ink/inkpols-argief` renders only its heading + empty-state paragraph ("Geen Uitgawes gevind nie."). The year group / card / cover / date / volume / teaser ids (`Archive.php:205-256`) are installed but **unmeasured**.
- **Single:** with no issue in the database, there is no single `inkpols_uitgawe` to capture. `reading-inkpols.php`, `SingleIssue.php` and `Viewer.php` carry `inkpols-single-*` ids for a later pass; nothing about the single page is measured here. No QA fixture exists for this post type either.
- The archive `<title>` is "Uitgawes - INK" (post-type archive label) while the visible h1 is "InkPols".

## Method

- **Token set** = `docs/design-handoff/tokens/theme-tokens.json` ∪ `wp-content/themes/ink-foundation/theme.json` settings (palette, fontSizes incl. fluid min–max ranges, spacingSizes, shadow presets, custom radius / lineHeight / fontWeight), as mapped by `docs/design-handoff/tokens/token-map.md`. Both value sets are accepted where they differ (e.g. primary `#EA4015` vs `#EC3B13`, accent `#4D8066` vs `#527A66`).
- **Scope:** every node inside `<main>` (header/footer are row 00's). Nodes with `display:none` or a 0×0 box (e.g. closed `<details>` content, hidden inputs) are skipped; off-screen but boxed nodes are kept.
- **Colour:** `background-color` on every node; `color` only on text-bearing nodes (own text, or `a`/`button`/`input`/`textarea`/`select`/`svg`); `border-top-color` only where a top border is drawn. RGB within ±1 of a token. A token colour at alpha < 1 is listed separately (not counted OFF-TOKEN).
- **Type:** font-size must equal a preset (px at 16px root) or fall inside a fluid preset's min–max; line-height ÷ font-size must be 1.2 / 1.5 / 1.7 (±0.02); family and weight must be token members.
- **Spacing:** padding / margin / gap, absolute value, ±0.5px of a spacing step. Equal left/right margins that are not a step are treated as `auto` centring and ignored.
- **Radius:** a radius step or ≥ 9999px (full). **Shadow:** each layer must equal a preset (colour, alpha, offsets).
- Values of `0` / `auto` / `none` / `normal` / `transparent` are ignored. Counts are instances summed across the three viewports.

## Summary

Nodes in `<main>`: 18 (of 333); skipped as unrendered: 0; centring margins ignored: 2.

| Property group | Values tested | On token | OFF-TOKEN | Distinct off-token values |
|---|---|---|---|---|
| colour | 6 | 6 | 0 | 0 |
| font-size | 6 | 6 | 0 | 0 |
| line-height | 6 | 6 | 0 | 0 |
| font-family | 6 | 6 | 0 | 0 |
| font-weight | 6 | 6 | 0 | 0 |
| spacing | 29 | 25 | 4 | 4 |
| radius | 0 | 0 | 0 | 0 |
| shadow | 0 | 0 | 0 | 0 |

**4 OFF-TOKEN instances, 4 distinct (property, value) pairs.**

## OFF-TOKEN values

### Colour

None.

### Type (font-size, line-height, family, weight)

None.

### Spacing (padding / margin / gap)

| Property | Value | Count | Viewports | Note | Examples |
|---|---|---|---|---|---|
| margin-bottom | `18.3702px` | 1 | 768 | nearest s-16 (theme-tokens) (16px) | `inkpols-heading` |
| margin-bottom | `21.44px` | 1 | 1440 | nearest s-24 (theme-tokens) (24px) | `inkpols-heading` |
| margin-top | `18.3702px` | 1 | 768 | nearest s-16 (theme-tokens) (16px) | `inkpols-heading` |
| margin-top | `21.44px` | 1 | 1440 | nearest s-24 (theme-tokens) (24px) | `inkpols-heading` |

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

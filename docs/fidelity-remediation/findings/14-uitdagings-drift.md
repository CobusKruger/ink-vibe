# Computed-style diff — uitdagings @ 1440px

**GENERATED — do not hand-edit.** Regenerate with `tools/fidelity/run.sh diff`.

- Reference: `tmp/fidelity-captures/lovable/uitdagings@1440-scope-challenge.ndjson` — http://localhost:4193/ (17 nodes)
- INK: `tmp/fidelity-captures/ink/uitdagings@1440-scope-challenge.ndjson` — https://nuwe-ink.local/uitdaging/ (12 nodes)
- Paired on `data-audit-id`: **6**
- Reference-only ids: 4 (challenge-card-corner, challenge-card-icon, challenge-card-eyebrow, challenge-card-desc)
- INK-only ids: 2 (challenge-card-theme-pill, challenge-card-status-pill)

## Systemic — architectural, not per-element

Each of these differs identically on (nearly) every paired node, so it is one
decision rather than many findings. Resolve at the layer, not on the page.

| property | reference | INK | nodes |
|---|---|---|---|
| box-sizing | `border-box` | `content-box` | 6/6 |

## 81 per-element deltas across 6 elements

### box — 47

| audit-id | property | reference | INK |
|---|---|---|---|
| `challenge-card` | column-gap | `normal` | `12px` |
| `challenge-card` | display | `block` | `flex` |
| `challenge-card` | flex-direction | `row` | `column` |
| `challenge-card` | gap | `normal` | `12px` |
| `challenge-card` | height | `382px` | `114px` |
| `challenge-card` | overflow | `hidden` | `visible` |
| `challenge-card` | padding-bottom | `32px` | `24px` |
| `challenge-card` | padding-left | `32px` | `24px` |
| `challenge-card` | padding-right | `32px` | `24px` |
| `challenge-card` | padding-top | `32px` | `24px` |
| `challenge-card` | position | `relative` | `static` |
| `challenge-card` | row-gap | `normal` | `12px` |
| `challenge-card` | width | `668px` | `1318px` |
| `challenge-card-btn` | column-gap | `8px` | `4px` |
| `challenge-card-btn` | display | `inline-flex` | `flex` |
| `challenge-card-btn` | gap | `8px` | `4px` |
| `challenge-card-btn` | height | `40px` | `21px` |
| `challenge-card-btn` | justify-content | `center` | `normal` |
| `challenge-card-btn` | min-height | `0px` | `auto` |
| `challenge-card-btn` | padding-bottom | `8px` | `0px` |
| `challenge-card-btn` | padding-left | `16px` | `0px` |
| `challenge-card-btn` | padding-right | `16px` | `0px` |
| `challenge-card-btn` | padding-top | `8px` | `0px` |
| `challenge-card-btn` | row-gap | `8px` | `4px` |
| `challenge-card-btn` | width | `185.969px` | `87.9219px` |
| `challenge-card-head` | flex-wrap | `nowrap` | `wrap` |
| `challenge-card-head` | height | `36px` | `24px` |
| `challenge-card-head` | margin-bottom | `16px` | `0px` |
| `challenge-card-head` | min-height | `0px` | `auto` |
| `challenge-card-head` | width | `602px` | `1318px` |
| `challenge-card-meta` | align-items | `normal` | `center` |
| `challenge-card-meta` | gap | `16px` | `8px 16px` |
| `challenge-card-meta` | height | `20px` | `21px` |
| `challenge-card-meta` | justify-content | `normal` | `space-between` |
| `challenge-card-meta` | margin-bottom | `24px` | `0px` |
| `challenge-card-meta` | min-height | `0px` | `auto` |
| `challenge-card-meta` | padding-top | `0px` | `16px` |
| `challenge-card-meta` | row-gap | `16px` | `8px` |
| `challenge-card-meta` | width | `602px` | `1318px` |
| `challenge-card-meta-item` | column-gap | `6px` | `8px` |
| `challenge-card-meta-item` | gap | `6px` | `8px` |
| `challenge-card-meta-item` | row-gap | `6px` | `8px` |
| `challenge-card-meta-item` | width | `101px` | `243.906px` |
| `challenge-card-title` | height | `36px` | `28px` |
| `challenge-card-title` | margin-bottom | `12px` | `0px` |
| `challenge-card-title` | min-height | `0px` | `auto` |
| `challenge-card-title` | width | `602px` | `1318px` |

### colour — 11

| audit-id | property | reference | INK |
|---|---|---|---|
| `challenge-card` | background-color | `rgb(253, 253, 252)` | `rgb(253, 252, 250)` |
| `challenge-card` | border-top-color | `rgb(231, 225, 218)` | `rgb(232, 228, 220)` |
| `challenge-card` | color | `rgb(24, 29, 37)` | `rgb(26, 29, 33)` |
| `challenge-card-btn` | background-color | `rgb(236, 59, 19)` | `rgba(0, 0, 0, 0)` |
| `challenge-card-btn` | color | `rgb(255, 255, 255)` | `rgb(236, 59, 19)` |
| `challenge-card-btn` | opacity | `1` | `0` |
| `challenge-card-head` | color | `rgb(24, 29, 37)` | `rgb(26, 29, 33)` |
| `challenge-card-meta` | border-top-color | `rgb(231, 225, 218)` | `rgb(232, 228, 220)` |
| `challenge-card-meta` | color | `rgb(103, 111, 126)` | `rgb(26, 29, 33)` |
| `challenge-card-meta-item` | color | `rgb(103, 111, 126)` | `rgb(107, 114, 128)` |
| `challenge-card-title` | color | `rgb(24, 29, 37)` | `rgb(26, 29, 33)` |

### type — 8

| audit-id | property | reference | INK |
|---|---|---|---|
| `challenge-card-btn` | font-family | `Lora, Georgia, serif` | `Inter, system-ui, sans-serif` |
| `challenge-card-btn` | line-height | `20px` | `21px` |
| `challenge-card-btn` | text-align | `center` | `start` |
| `challenge-card-btn` | white-space | `nowrap` | `normal` |
| `challenge-card-meta` | font-size | `14px` | `16px` |
| `challenge-card-meta` | line-height | `20px` | `24px` |
| `challenge-card-title` | font-size | `30px` | `20px` |
| `challenge-card-title` | line-height | `36px` | `28px` |

### micro — 15

| audit-id | property | reference | INK |
|---|---|---|---|
| `challenge-card` | border-bottom-left-radius | `16px` | `8px` |
| `challenge-card` | border-bottom-right-radius | `16px` | `8px` |
| `challenge-card` | border-top-left-radius | `16px` | `8px` |
| `challenge-card` | border-top-right-radius | `16px` | `8px` |
| `challenge-card` | box-shadow | `rgba(0, 0, 0, 0) 0px 0px 0px 0px, rgba(0, 0, 0, 0) 0px 0p…` | `rgba(24, 29, 37, 0.06) 0px 2px 12px -2px` |
| `challenge-card-btn` | border-bottom-left-radius | `6px` | `0px` |
| `challenge-card-btn` | border-bottom-right-radius | `6px` | `0px` |
| `challenge-card-btn` | border-top-left-radius | `6px` | `0px` |
| `challenge-card-btn` | border-top-right-radius | `6px` | `0px` |
| `challenge-card-btn` | border-top-style | `solid` | `none` |
| `challenge-card-btn` | box-shadow | `rgba(0, 0, 0, 0) 0px 0px 0px 0px, rgba(0, 0, 0, 0) 0px 0p…` | `none` |
| `challenge-card-head` | border-top-style | `solid` | `none` |
| `challenge-card-meta` | border-top-width | `0px` | `1px` |
| `challenge-card-meta-item` | border-top-style | `solid` | `none` |
| `challenge-card-title` | border-top-style | `solid` | `none` |


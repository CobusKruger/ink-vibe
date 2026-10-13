# Computed-style diff — biblioteek @ 390px

**GENERATED — do not hand-edit.** Regenerate with `tools/fidelity/run.sh diff`.

- Reference: `/Users/cobus/Development/ink-vibe/tmp/fidelity-captures/lovable/library@390.ndjson` — http://localhost:4188/library (339 nodes)
- INK: `/Users/cobus/Development/ink-vibe/tmp/fidelity-captures/ink/biblioteek@390-scope-cards.ndjson` — https://nuwe-ink.local/qa-bloks/ (103 nodes)
- Paired on `data-audit-id`: **31**
- Reference-only ids: 47 (header, header-row, header-logo, header-logo-icon, header-logo-text, header-nav, header-nav-home, header-nav-browse, header-nav-library, header-nav-challenges, header-nav-community, header-actions, header-btn-signin, header-btn-join, header-menu-toggle, library-intro-container, library-intro-inner, library-eyebrow, library-eyebrow-icon, library-eyebrow-text, library-lede, library-shelf-head, library-shelf-sub, library-browse, library-filter-icon, library-count, library-cta, library-cta-container, library-cta-h2, library-cta-text, library-cta-btn, footer, footer-grid, footer-brand, footer-logo, footer-logo-icon, footer-logo-text, footer-blurb, footer-col, footer-col-heading, footer-col-list, footer-col-item, footer-col-link, footer-bottom, footer-copyright, footer-made-with, footer-heart-icon)
- INK-only ids: 3 (library-archive, library-search-icon-wrap, library-search-btn)

## Systemic — architectural, not per-element

Each of these differs identically on (nearly) every paired node, so it is one
decision rather than many findings. Resolve at the layer, not on the page.

| property | reference | INK | nodes |
|---|---|---|---|
| box-sizing | `border-box` | `content-box` | 28/31 |

## 217 per-element deltas across 31 elements

### box — 141

| audit-id | property | reference | INK |
|---|---|---|---|
| `library-card` | display | `block` | `flex` |
| `library-card` | flex-direction | `row` | `column` |
| `library-card` | height | `252.75px` | `264.391px` |
| `library-card` | overflow | `visible` | `hidden` |
| `library-card` | width | `358px` | `340px` |
| `library-card-author` | height | `16px` | `18px` |
| `library-card-author` | width | `97.9219px` | `78.6094px` |
| `library-card-badge` | height | `22px` | `18px` |
| `library-card-badge` | padding-bottom | `2px` | `4px` |
| `library-card-badge` | padding-left | `10px` | `12px` |
| `library-card-badge` | padding-right | `10px` | `12px` |
| `library-card-badge` | padding-top | `2px` | `4px` |
| `library-card-badge` | width | `110.172px` | `47.9062px` |
| `library-card-body` | column-gap | `normal` | `8px` |
| `library-card-body` | gap | `normal` | `8px` |
| `library-card-body` | height | `250.75px` | `216.391px` |
| `library-card-body` | min-height | `0px` | `auto` |
| `library-card-body` | row-gap | `normal` | `8px` |
| `library-card-body` | width | `356px` | `292px` |
| `library-card-excerpt` | display | `block` | `flow-root` |
| `library-card-excerpt` | height | `68.25px` | `71.3906px` |
| `library-card-excerpt` | margin-top | `12px` | `0px` |
| `library-card-excerpt` | overflow | `visible` | `hidden` |
| `library-card-excerpt` | width | `308px` | `292px` |
| `library-card-footer` | column-gap | `normal` | `16px` |
| `library-card-footer` | gap | `normal` | `16px` |
| `library-card-footer` | height | `37px` | `21px` |
| `library-card-footer` | margin-top | `20px` | `0px` |
| `library-card-footer` | row-gap | `normal` | `16px` |
| `library-card-footer` | width | `308px` | `292px` |
| `library-card-meta` | column-gap | `normal` | `8px` |
| `library-card-meta` | gap | `normal` | `8px` |
| `library-card-meta` | height | `22px` | `28px` |
| `library-card-meta` | justify-content | `space-between` | `normal` |
| `library-card-meta` | margin-bottom | `16px` | `0px` |
| `library-card-meta` | row-gap | `normal` | `8px` |
| `library-card-meta` | width | `308px` | `292px` |
| `library-card-read` | height | `20px` | `21px` |
| `library-card-read` | width | `51.8438px` | `49.9062px` |
| `library-card-time` | height | `16px` | `18px` |
| `library-card-time` | width | `47.3281px` | `47.2031px` |
| `library-card-title` | height | `27.5px` | `55px` |
| `library-card-title` | width | `308px` | `292px` |
| `library-filter` | column-gap | `8px` | `normal` |
| `library-filter` | gap | `8px` | `normal` |
| `library-filter` | height | `38px` | `21px` |
| `library-filter` | row-gap | `8px` | `normal` |
| `library-filter` | width | `72.4531px` | `32px` |
| `library-filters` | height | `176px` | `39px` |
| `library-filters` | margin-bottom | `40px` | `32px` |
| `library-filters` | width | `358px` | `342px` |
| `library-grid` | grid-template-columns | `358px` | `342px` |
| `library-grid` | height | `4190.75px` | `1205.88px` |
| `library-grid` | width | `358px` | `342px` |
| `library-h1` | height | `90px` | `45px` |
| `library-h1` | max-width | `none` | `454.32px` |
| `library-h1` | min-height | `0px` | `auto` |
| `library-h1` | width | `358px` | `342px` |
| `library-intro` | column-gap | `normal` | `16px` |
| `library-intro` | display | `block` | `flex` |
| `library-intro` | flex-direction | `row` | `column` |
| `library-intro` | gap | `normal` | `16px` |
| `library-intro` | height | `476px` | `111px` |
| `library-intro` | margin-bottom | `0px` | `32px` |
| `library-intro` | padding-bottom | `0px` | `32px` |
| `library-intro` | row-gap | `normal` | `16px` |
| `library-intro` | width | `390px` | `342px` |
| `library-search` | column-gap | `normal` | `8px` |
| `library-search` | display | `block` | `flex` |
| `library-search` | gap | `normal` | `8px` |
| `library-search` | height | `48px` | `50px` |
| `library-search` | margin-top | `32px` | `0px` |
| `library-search` | min-height | `0px` | `auto` |
| `library-search` | row-gap | `normal` | `8px` |
| `library-search` | width | `358px` | `342px` |
| `library-search-icon` | min-height | `0px` | `auto` |
| `library-search-icon` | position | `absolute` | `static` |
| `library-search-input` | display | `flex` | `block` |
| `library-search-input` | height | `48px` | `50px` |
| `library-search-input` | min-height | `0px` | `auto` |
| `library-search-input` | padding-bottom | `8px` | `12px` |
| `library-search-input` | padding-left | `44px` | `40px` |
| `library-search-input` | padding-right | `12px` | `16px` |
| `library-search-input` | padding-top | `8px` | `12px` |
| `library-search-input` | width | `358px` | `254.047px` |
| `library-shelf` | height | `1021.75px` | `1256.88px` |
| `library-shelf` | margin-bottom | `0px` | `48px` |
| `library-shelf` | padding-bottom | `48px` | `0px` |
| `library-shelf` | padding-left | `16px` | `0px` |
| `library-shelf` | padding-right | `16px` | `0px` |
| `library-shelf` | padding-top | `48px` | `0px` |
| `library-shelf` | width | `390px` | `342px` |
| `library-shelf-card` | display | `block` | `flex` |
| `library-shelf-card` | flex-direction | `row` | `column` |
| `library-shelf-card` | height | `252.75px` | `264.391px` |
| `library-shelf-card` | overflow | `visible` | `hidden` |
| `library-shelf-card` | width | `358px` | `340px` |
| `library-shelf-card-author` | height | `16px` | `18px` |
| `library-shelf-card-author` | width | `97.9219px` | `78.6094px` |
| `library-shelf-card-badge` | height | `22px` | `18px` |
| `library-shelf-card-badge` | padding-bottom | `2px` | `4px` |
| `library-shelf-card-badge` | padding-left | `10px` | `12px` |
| `library-shelf-card-badge` | padding-right | `10px` | `12px` |
| `library-shelf-card-badge` | padding-top | `2px` | `4px` |
| `library-shelf-card-badge` | width | `110.172px` | `47.9062px` |
| `library-shelf-card-body` | column-gap | `normal` | `8px` |
| `library-shelf-card-body` | gap | `normal` | `8px` |
| `library-shelf-card-body` | height | `250.75px` | `216.391px` |
| `library-shelf-card-body` | min-height | `0px` | `auto` |
| `library-shelf-card-body` | row-gap | `normal` | `8px` |
| `library-shelf-card-body` | width | `356px` | `292px` |
| `library-shelf-card-excerpt` | display | `block` | `flow-root` |
| `library-shelf-card-excerpt` | height | `68.25px` | `71.3906px` |
| `library-shelf-card-excerpt` | margin-top | `12px` | `0px` |
| `library-shelf-card-excerpt` | overflow | `visible` | `hidden` |
| `library-shelf-card-excerpt` | width | `308px` | `292px` |
| `library-shelf-card-footer` | column-gap | `normal` | `16px` |
| `library-shelf-card-footer` | gap | `normal` | `16px` |
| `library-shelf-card-footer` | height | `37px` | `21px` |
| `library-shelf-card-footer` | margin-top | `20px` | `0px` |
| `library-shelf-card-footer` | row-gap | `normal` | `16px` |
| `library-shelf-card-footer` | width | `308px` | `292px` |
| `library-shelf-card-meta` | column-gap | `normal` | `8px` |
| `library-shelf-card-meta` | gap | `normal` | `8px` |
| `library-shelf-card-meta` | height | `22px` | `28px` |
| `library-shelf-card-meta` | justify-content | `space-between` | `normal` |
| `library-shelf-card-meta` | margin-bottom | `16px` | `0px` |
| `library-shelf-card-meta` | row-gap | `normal` | `8px` |
| `library-shelf-card-meta` | width | `308px` | `292px` |
| `library-shelf-card-read` | height | `20px` | `21px` |
| `library-shelf-card-read` | width | `51.8438px` | `49.9062px` |
| `library-shelf-card-time` | height | `16px` | `18px` |
| `library-shelf-card-time` | width | `47.3281px` | `47.2031px` |
| `library-shelf-card-title` | height | `27.5px` | `55px` |
| `library-shelf-card-title` | width | `308px` | `292px` |
| `library-shelf-grid` | grid-template-columns | `358px` | `342px` |
| `library-shelf-grid` | height | `833.75px` | `1205.88px` |
| `library-shelf-grid` | width | `358px` | `342px` |
| `library-shelf-h2` | height | `32px` | `27px` |
| `library-shelf-h2` | margin-bottom | `0px` | `24px` |
| `library-shelf-h2` | width | `205.328px` | `342px` |

### colour — 36

| audit-id | property | reference | INK |
|---|---|---|---|
| `library-card` | background-color | `rgb(253, 253, 252)` | `rgb(253, 252, 250)` |
| `library-card` | border-top-color | `rgb(231, 225, 218)` | `rgb(232, 228, 220)` |
| `library-card` | color | `rgb(24, 29, 37)` | `rgb(26, 29, 33)` |
| `library-card-author` | color | `rgb(103, 111, 126)` | `rgb(107, 114, 128)` |
| `library-card-body` | color | `rgb(24, 29, 37)` | `rgb(26, 29, 33)` |
| `library-card-excerpt` | color | `rgb(103, 111, 126)` | `rgb(107, 114, 128)` |
| `library-card-footer` | border-top-color | `rgb(231, 225, 218)` | `rgb(232, 228, 220)` |
| `library-card-footer` | color | `rgb(24, 29, 37)` | `rgb(26, 29, 33)` |
| `library-card-meta` | color | `rgb(24, 29, 37)` | `rgb(26, 29, 33)` |
| `library-card-time` | color | `rgb(103, 111, 126)` | `rgb(107, 114, 128)` |
| `library-card-title` | color | `rgb(24, 29, 37)` | `rgb(26, 29, 33)` |
| `library-filter` | color | `rgb(255, 255, 255)` | `rgb(253, 252, 250)` |
| `library-filters` | color | `rgb(24, 29, 37)` | `rgb(26, 29, 33)` |
| `library-grid` | color | `rgb(24, 29, 37)` | `rgb(26, 29, 33)` |
| `library-h1` | color | `rgb(24, 29, 37)` | `rgb(26, 29, 33)` |
| `library-intro` | background-color | `rgba(250, 248, 245, 0.4)` | `rgba(0, 0, 0, 0)` |
| `library-intro` | color | `rgb(24, 29, 37)` | `rgb(26, 29, 33)` |
| `library-search` | color | `rgb(24, 29, 37)` | `rgb(26, 29, 33)` |
| `library-search-icon` | color | `rgb(103, 111, 126)` | `rgb(107, 114, 128)` |
| `library-search-input` | background-color | `rgb(250, 248, 245)` | `rgb(253, 252, 250)` |
| `library-search-input` | border-top-color | `rgb(231, 225, 218)` | `rgb(232, 228, 220)` |
| `library-search-input` | color | `rgb(24, 29, 37)` | `rgb(26, 29, 33)` |
| `library-shelf` | color | `rgb(24, 29, 37)` | `rgb(26, 29, 33)` |
| `library-shelf-card` | background-color | `rgba(250, 248, 245, 0.6)` | `rgb(237, 233, 224)` |
| `library-shelf-card` | border-top-color | `rgba(236, 59, 19, 0.2)` | `rgb(233, 194, 180)` |
| `library-shelf-card` | color | `rgb(24, 29, 37)` | `rgb(26, 29, 33)` |
| `library-shelf-card-author` | color | `rgb(103, 111, 126)` | `rgb(107, 114, 128)` |
| `library-shelf-card-body` | color | `rgb(24, 29, 37)` | `rgb(26, 29, 33)` |
| `library-shelf-card-excerpt` | color | `rgb(103, 111, 126)` | `rgb(107, 114, 128)` |
| `library-shelf-card-footer` | border-top-color | `rgb(231, 225, 218)` | `rgb(232, 228, 220)` |
| `library-shelf-card-footer` | color | `rgb(24, 29, 37)` | `rgb(26, 29, 33)` |
| `library-shelf-card-meta` | color | `rgb(24, 29, 37)` | `rgb(26, 29, 33)` |
| `library-shelf-card-time` | color | `rgb(103, 111, 126)` | `rgb(107, 114, 128)` |
| `library-shelf-card-title` | color | `rgb(24, 29, 37)` | `rgb(26, 29, 33)` |
| `library-shelf-grid` | color | `rgb(24, 29, 37)` | `rgb(26, 29, 33)` |
| `library-shelf-h2` | color | `rgb(24, 29, 37)` | `rgb(26, 29, 33)` |

### type — 14

| audit-id | property | reference | INK |
|---|---|---|---|
| `library-card-author` | line-height | `16px` | `18px` |
| `library-card-badge` | line-height | `16px` | `18px` |
| `library-card-excerpt` | line-height | `22.75px` | `23.8px` |
| `library-card-read` | line-height | `20px` | `21px` |
| `library-card-time` | line-height | `16px` | `18px` |
| `library-filter` | line-height | `20px` | `21px` |
| `library-filter` | text-align | `center` | `start` |
| `library-shelf-card-author` | line-height | `16px` | `18px` |
| `library-shelf-card-badge` | line-height | `16px` | `18px` |
| `library-shelf-card-excerpt` | line-height | `22.75px` | `23.8px` |
| `library-shelf-card-read` | line-height | `20px` | `21px` |
| `library-shelf-card-time` | line-height | `16px` | `18px` |
| `library-shelf-h2` | font-size | `24px` | `20.2674px` |
| `library-shelf-h2` | line-height | `32px` | `27.0164px` |

### micro — 26

| audit-id | property | reference | INK |
|---|---|---|---|
| `library-card` | box-shadow | `rgba(0, 0, 0, 0) 0px 0px 0px 0px, rgba(0, 0, 0, 0) 0px 0p…` | `rgba(24, 29, 37, 0.06) 0px 2px 12px -2px` |
| `library-card-author` | border-top-style | `solid` | `none` |
| `library-card-body` | border-top-style | `solid` | `none` |
| `library-card-excerpt` | border-top-style | `solid` | `none` |
| `library-card-meta` | border-top-style | `solid` | `none` |
| `library-card-read` | border-top-style | `solid` | `none` |
| `library-card-time` | border-top-style | `solid` | `none` |
| `library-card-title` | border-top-style | `solid` | `none` |
| `library-filters` | border-top-style | `solid` | `none` |
| `library-grid` | border-top-style | `solid` | `none` |
| `library-h1` | border-top-style | `solid` | `none` |
| `library-intro` | border-top-style | `solid` | `none` |
| `library-search` | border-top-style | `solid` | `none` |
| `library-search-icon` | border-top-style | `solid` | `none` |
| `library-search-icon` | transform | `matrix(1, 0, 0, 1, 0, -8)` | `none` |
| `library-shelf` | border-top-style | `solid` | `none` |
| `library-shelf-card` | box-shadow | `rgba(0, 0, 0, 0) 0px 0px 0px 0px, rgba(0, 0, 0, 0) 0px 0p…` | `rgba(24, 29, 37, 0.06) 0px 2px 12px -2px` |
| `library-shelf-card-author` | border-top-style | `solid` | `none` |
| `library-shelf-card-body` | border-top-style | `solid` | `none` |
| `library-shelf-card-excerpt` | border-top-style | `solid` | `none` |
| `library-shelf-card-meta` | border-top-style | `solid` | `none` |
| `library-shelf-card-read` | border-top-style | `solid` | `none` |
| `library-shelf-card-time` | border-top-style | `solid` | `none` |
| `library-shelf-card-title` | border-top-style | `solid` | `none` |
| `library-shelf-grid` | border-top-style | `solid` | `none` |
| `library-shelf-h2` | border-top-style | `solid` | `none` |


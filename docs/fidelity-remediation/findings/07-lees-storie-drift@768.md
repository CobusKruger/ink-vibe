# Computed-style diff — lees-storie @ 768px

**GENERATED — do not hand-edit.** Regenerate with `tools/fidelity/run.sh diff`.

- Reference: `tmp/fidelity-captures/lovable/lees-storie@768.ndjson` — http://localhost:4187/read/sample (194 nodes)
- INK: `tmp/fidelity-captures/ink/lees-storie@768.ndjson` — https://nuwe-ink.local/storie/die-wenteltrap-na-die-lig/ (204 nodes)
- Paired on `data-audit-id`: **82**
- Reference-only ids: 7 (reading-byline-author, reading-byline-readtime, reading-engagement-pill, reading-share-btn, reading-author-follow, reading-response-row, reading-response-reply)
- INK-only ids: 4 (header-nav-profile, reading-byline-date, reading-response-who, footer-social)

## Systemic — architectural, not per-element

Each of these differs identically on (nearly) every paired node, so it is one
decision rather than many findings. Resolve at the layer, not on the page.

| property | reference | INK | nodes |
|---|---|---|---|
| border-top-style | `solid` | `none` | 74/82 |

## 541 per-element deltas across 82 elements

### box — 357

| audit-id | property | reference | INK |
|---|---|---|---|
| `footer` | height | `326px` | `386.188px` |
| `footer` | min-height | `auto` | `0px` |
| `footer` | padding-bottom | `0px` | `48px` |
| `footer` | padding-left | `0px` | `24px` |
| `footer` | padding-right | `0px` | `24px` |
| `footer` | padding-top | `0px` | `48px` |
| `footer` | position | `static` | `relative` |
| `footer-blurb` | box-sizing | `border-box` | `content-box` |
| `footer-blurb` | height | `91px` | `95.1875px` |
| `footer-blurb` | max-width | `none` | `768px` |
| `footer-blurb` | min-height | `0px` | `auto` |
| `footer-blurb` | width | `160px` | `156px` |
| `footer-bottom` | flex-wrap | `nowrap` | `wrap` |
| `footer-bottom` | height | `45px` | `46px` |
| `footer-bottom` | max-width | `none` | `1368px` |
| `footer-bottom` | width | `736px` | `720px` |
| `footer-brand` | column-gap | `normal` | `16px` |
| `footer-brand` | display | `block` | `flex` |
| `footer-brand` | flex-direction | `row` | `column` |
| `footer-brand` | gap | `normal` | `16px` |
| `footer-brand` | height | `144px` | `203.188px` |
| `footer-brand` | position | `static` | `relative` |
| `footer-brand` | row-gap | `normal` | `16px` |
| `footer-brand` | width | `160px` | `156px` |
| `footer-col` | height | `144px` | `179.188px` |
| `footer-col` | margin-top | `0px` | `24px` |
| `footer-col` | position | `static` | `relative` |
| `footer-col` | width | `160px` | `156px` |
| `footer-col-heading` | box-sizing | `border-box` | `content-box` |
| `footer-col-heading` | height | `24px` | `19.1875px` |
| `footer-col-heading` | max-width | `none` | `768px` |
| `footer-col-heading` | width | `160px` | `156px` |
| `footer-col-item` | box-sizing | `border-box` | `content-box` |
| `footer-col-item` | height | `20px` | `24px` |
| `footer-col-item` | min-height | `0px` | `auto` |
| `footer-col-item` | width | `160px` | `156px` |
| `footer-col-link` | box-sizing | `border-box` | `content-box` |
| `footer-col-list` | column-gap | `normal` | `8px` |
| `footer-col-list` | display | `block` | `flex` |
| `footer-col-list` | flex-direction | `row` | `column` |
| `footer-col-list` | gap | `normal` | `8px` |
| `footer-col-list` | height | `104px` | `144px` |
| `footer-col-list` | max-width | `none` | `768px` |
| `footer-col-list` | row-gap | `normal` | `8px` |
| `footer-col-list` | width | `160px` | `156px` |
| `footer-copyright` | box-sizing | `border-box` | `content-box` |
| `footer-copyright` | height | `20px` | `21px` |
| `footer-copyright` | width | `415.344px` | `434.828px` |
| `footer-grid` | grid-template-columns | `160px 160px 160px 160px` | `156px 156px 156px 156px` |
| `footer-grid` | height | `144px` | `203.188px` |
| `footer-grid` | max-width | `none` | `1368px` |
| `footer-grid` | width | `736px` | `720px` |
| `footer-heart-icon` | box-sizing | `border-box` | `content-box` |
| `footer-logo` | flex-wrap | `nowrap` | `wrap` |
| `footer-logo` | height | `28px` | `27px` |
| `footer-logo` | margin-bottom | `16px` | `0px` |
| `footer-logo` | margin-left | `0px` | `47.4219px` |
| `footer-logo` | margin-right | `0px` | `47.4219px` |
| `footer-logo` | max-width | `none` | `768px` |
| `footer-logo` | min-height | `0px` | `auto` |
| `footer-logo` | width | `160px` | `61.1562px` |
| `footer-logo-icon` | box-sizing | `border-box` | `content-box` |
| `footer-logo-text` | height | `28px` | `27px` |
| `footer-logo-text` | width | `62.9375px` | `33.1562px` |
| `footer-made-with` | box-sizing | `border-box` | `content-box` |
| `footer-made-with` | height | `20px` | `21px` |
| `footer-made-with` | width | `242.828px` | `217.016px` |
| `header` | height | `65px` | `92px` |
| `header` | min-height | `auto` | `0px` |
| `header` | padding-left | `0px` | `16px` |
| `header` | padding-right | `0px` | `16px` |
| `header` | position | `sticky` | `relative` |
| `header-actions` | flex-wrap | `nowrap` | `wrap` |
| `header-actions` | height | `36px` | `37px` |
| `header-actions` | width | `183.234px` | `171.797px` |
| `header-btn-join` | align-items | `center` | `normal` |
| `header-btn-join` | column-gap | `8px` | `normal` |
| `header-btn-join` | display | `flex` | `inline-block` |
| `header-btn-join` | gap | `8px` | `normal` |
| `header-btn-join` | height | `36px` | `37px` |
| `header-btn-join` | justify-content | `center` | `normal` |
| `header-btn-join` | min-height | `auto` | `0px` |
| `header-btn-join` | padding-bottom | `0px` | `8px` |
| `header-btn-join` | padding-top | `0px` | `8px` |
| `header-btn-join` | row-gap | `8px` | `normal` |
| `header-btn-join` | width | `101.891px` | `80.3125px` |
| `header-btn-signin` | align-items | `center` | `normal` |
| `header-btn-signin` | column-gap | `8px` | `normal` |
| `header-btn-signin` | display | `flex` | `inline-block` |
| `header-btn-signin` | gap | `8px` | `normal` |
| `header-btn-signin` | height | `36px` | `37px` |
| `header-btn-signin` | justify-content | `center` | `normal` |
| `header-btn-signin` | min-height | `auto` | `0px` |
| `header-btn-signin` | padding-bottom | `0px` | `8px` |
| `header-btn-signin` | padding-top | `0px` | `8px` |
| `header-btn-signin` | row-gap | `8px` | `normal` |
| `header-btn-signin` | width | `69.3438px` | `79.4844px` |
| `header-logo` | flex-wrap | `nowrap` | `wrap` |
| `header-logo` | height | `28px` | `30px` |
| `header-logo` | width | `101.922px` | `68.8281px` |
| `header-logo-icon` | box-sizing | `border-box` | `content-box` |
| `header-logo-text` | height | `28px` | `30px` |
| `header-logo-text` | width | `69.9219px` | `36.8281px` |
| `header-menu-toggle` | padding-bottom | `8px` | `0px` |
| `header-menu-toggle` | padding-left | `8px` | `0px` |
| `header-menu-toggle` | padding-right | `8px` | `0px` |
| `header-menu-toggle` | padding-top | `8px` | `0px` |
| `header-nav` | box-sizing | `border-box` | `content-box` |
| `header-nav` | flex-wrap | `nowrap` | `wrap` |
| `header-nav` | position | `static` | `relative` |
| `header-nav` | width | `453.125px` | `574.281px` |
| `header-nav-browse` | box-sizing | `border-box` | `content-box` |
| `header-nav-browse` | width | `56.4062px` | `54.6719px` |
| `header-nav-challenges` | box-sizing | `border-box` | `content-box` |
| `header-nav-challenges` | width | `84.25px` | `81.0469px` |
| `header-nav-community` | box-sizing | `border-box` | `content-box` |
| `header-nav-community` | width | `86.6406px` | `99.4062px` |
| `header-nav-home` | box-sizing | `border-box` | `content-box` |
| `header-nav-home` | width | `44.8281px` | `30.9375px` |
| `header-nav-library` | box-sizing | `border-box` | `content-box` |
| `header-nav-library` | width | `53px` | `72.0469px` |
| `header-row` | column-gap | `normal` | `24px` |
| `header-row` | flex-wrap | `nowrap` | `wrap` |
| `header-row` | gap | `normal` | `24px` |
| `header-row` | height | `64px` | `91px` |
| `header-row` | max-width | `none` | `1368px` |
| `header-row` | min-height | `0px` | `64px` |
| `header-row` | row-gap | `normal` | `24px` |
| `reading-author-actions` | box-sizing | `border-box` | `content-box` |
| `reading-author-actions` | width | `501.125px` | `388.844px` |
| `reading-author-all-works` | box-sizing | `border-box` | `content-box` |
| `reading-author-all-works` | column-gap | `8px` | `normal` |
| `reading-author-all-works` | gap | `8px` | `normal` |
| `reading-author-all-works` | justify-content | `center` | `normal` |
| `reading-author-all-works` | padding-left | `12px` | `16px` |
| `reading-author-all-works` | padding-right | `12px` | `16px` |
| `reading-author-all-works` | row-gap | `8px` | `normal` |
| `reading-author-all-works` | width | `120.344px` | `114.688px` |
| `reading-author-avatar` | max-width | `100%` | `none` |
| `reading-author-avatar` | min-height | `auto` | `0px` |
| `reading-author-band` | height | `210px` | `206px` |
| `reading-author-band` | margin-top | `0px` | `24px` |
| `reading-author-band` | padding-left | `0px` | `24px` |
| `reading-author-band` | padding-right | `0px` | `24px` |
| `reading-author-band` | position | `static` | `relative` |
| `reading-author-body` | box-sizing | `border-box` | `content-box` |
| `reading-author-body` | height | `112px` | `108px` |
| `reading-author-body` | width | `501.125px` | `388.844px` |
| `reading-author-card` | box-sizing | `border-box` | `content-box` |
| `reading-author-card` | height | `112px` | `108px` |
| `reading-author-card` | margin-left | `32px` | `24px` |
| `reading-author-card` | margin-right | `32px` | `24px` |
| `reading-body` | height | `781.641px` | `401.125px` |
| `reading-body` | margin-top | `0px` | `24px` |
| `reading-body` | padding-bottom | `48px` | `64px` |
| `reading-body` | padding-left | `0px` | `24px` |
| `reading-body` | padding-right | `0px` | `24px` |
| `reading-body` | padding-top | `48px` | `24px` |
| `reading-body` | position | `static` | `relative` |
| `reading-bookmark-btn` | align-items | `normal` | `center` |
| `reading-bookmark-btn` | display | `block` | `flex` |
| `reading-bookmark-btn` | height | `20px` | `21px` |
| `reading-bookmark-btn` | justify-content | `normal` | `center` |
| `reading-bookmark-btn` | width | `20px` | `18px` |
| `reading-byline` | column-gap | `16px` | `8px` |
| `reading-byline` | flex-wrap | `nowrap` | `wrap` |
| `reading-byline` | gap | `16px` | `8px` |
| `reading-byline` | margin-top | `0px` | `12px` |
| `reading-byline` | max-width | `none` | `768px` |
| `reading-byline` | row-gap | `16px` | `8px` |
| `reading-byline` | width | `736px` | `720px` |
| `reading-byline-avatar` | display | `block` | `inline` |
| `reading-byline-avatar` | max-width | `100%` | `none` |
| `reading-byline-avatar` | min-height | `auto` | `0px` |
| `reading-byline-name` | height | `24px` | `21px` |
| `reading-byline-name` | width | `128.156px` | `92.8594px` |
| `reading-byline-sep` | box-sizing | `border-box` | `content-box` |
| `reading-byline-sep` | height | `24px` | `21px` |
| `reading-byline-sep` | width | `9px` | `7.875px` |
| `reading-comments-count` | box-sizing | `border-box` | `content-box` |
| `reading-comments-count` | height | `24px` | `21px` |
| `reading-comments-count` | width | `20.1562px` | `5.70312px` |
| `reading-comments-link` | box-sizing | `border-box` | `content-box` |
| `reading-comments-link` | column-gap | `8px` | `4px` |
| `reading-comments-link` | gap | `8px` | `4px` |
| `reading-comments-link` | height | `24px` | `23px` |
| `reading-comments-link` | row-gap | `8px` | `4px` |
| `reading-comments-link` | width | `48.1562px` | `27.7031px` |
| `reading-header` | height | `297px` | `307.594px` |
| `reading-header` | padding-left | `0px` | `24px` |
| `reading-header` | padding-right | `0px` | `24px` |
| `reading-header` | position | `static` | `relative` |
| `reading-header-inner` | height | `200px` | `210.594px` |
| `reading-header-inner` | position | `static` | `relative` |
| `reading-header-inner` | width | `736px` | `720px` |
| `reading-hint-icon` | box-sizing | `border-box` | `content-box` |
| `reading-hint-icon` | height | `16px` | `14px` |
| `reading-hint-icon` | width | `16px` | `14px` |
| `reading-like-count` | box-sizing | `border-box` | `content-box` |
| `reading-like-count` | width | `26.8281px` | `9.875px` |
| `reading-like-icon` | box-sizing | `border-box` | `content-box` |
| `reading-like-icon` | height | `20px` | `18px` |
| `reading-like-icon` | width | `20px` | `18px` |
| `reading-response-actions` | box-sizing | `border-box` | `content-box` |
| `reading-response-actions` | min-height | `0px` | `auto` |
| `reading-response-author` | box-sizing | `border-box` | `content-box` |
| `reading-response-author` | width | `95.6562px` | `106.125px` |
| `reading-response-avatar` | box-sizing | `border-box` | `content-box` |
| `reading-response-avatar` | max-width | `100%` | `none` |
| `reading-response-badge` | box-sizing | `border-box` | `content-box` |
| `reading-response-badge` | column-gap | `4px` | `normal` |
| `reading-response-badge` | gap | `4px` | `normal` |
| `reading-response-badge` | height | `20px` | `18px` |
| `reading-response-badge` | row-gap | `4px` | `normal` |
| `reading-response-badge` | width | `69.9375px` | `32.5938px` |
| `reading-response-body` | box-sizing | `border-box` | `content-box` |
| `reading-response-body` | height | `146px` | `125px` |
| `reading-response-date` | box-sizing | `border-box` | `content-box` |
| `reading-response-date` | height | `16px` | `18px` |
| `reading-response-date` | margin-left | `316.797px` | `0px` |
| `reading-response-date` | width | `67.6094px` | `78.8438px` |
| `reading-response-footer` | box-sizing | `border-box` | `content-box` |
| `reading-response-footer` | height | `20px` | `21px` |
| `reading-response-form` | box-sizing | `border-box` | `content-box` |
| `reading-response-form` | column-gap | `normal` | `12px` |
| `reading-response-form` | display | `block` | `flex` |
| `reading-response-form` | flex-direction | `row` | `column` |
| `reading-response-form` | gap | `normal` | `12px` |
| `reading-response-form` | height | `314px` | `270px` |
| `reading-response-form` | row-gap | `normal` | `12px` |
| `reading-response-form` | width | `672px` | `622px` |
| `reading-response-input` | box-sizing | `border-box` | `content-box` |
| `reading-response-input` | display | `flex` | `block` |
| `reading-response-input` | height | `100px` | `51px` |
| `reading-response-input` | margin-bottom | `16px` | `0px` |
| `reading-response-input` | min-height | `100px` | `auto` |
| `reading-response-input` | padding-bottom | `8px` | `12px` |
| `reading-response-input` | padding-top | `8px` | `12px` |
| `reading-response-input` | width | `622px` | `596px` |
| `reading-response-intro` | box-sizing | `border-box` | `content-box` |
| `reading-response-intro` | height | `40px` | `48px` |
| `reading-response-intro` | margin-top | `0px` | `16px` |
| `reading-response-intro` | min-height | `0px` | `auto` |
| `reading-response-item` | align-items | `normal` | `flex-start` |
| `reading-response-item` | box-sizing | `border-box` | `content-box` |
| `reading-response-item` | column-gap | `normal` | `16px` |
| `reading-response-item` | display | `block` | `flex` |
| `reading-response-item` | gap | `normal` | `16px` |
| `reading-response-item` | height | `196px` | `125px` |
| `reading-response-item` | min-height | `0px` | `auto` |
| `reading-response-item` | row-gap | `normal` | `16px` |
| `reading-response-item` | width | `672px` | `622px` |
| `reading-response-list` | column-gap | `normal` | `16px` |
| `reading-response-list` | display | `block` | `flex` |
| `reading-response-list` | flex-direction | `row` | `column` |
| `reading-response-list` | gap | `normal` | `16px` |
| `reading-response-list` | height | `584px` | `175px` |
| `reading-response-list` | row-gap | `normal` | `16px` |
| `reading-response-meta` | box-sizing | `border-box` | `content-box` |
| `reading-response-meta` | flex-wrap | `nowrap` | `wrap` |
| `reading-response-meta` | justify-content | `normal` | `space-between` |
| `reading-response-submit` | justify-content | `center` | `normal` |
| `reading-response-submit` | padding-bottom | `8px` | `0px` |
| `reading-response-submit` | padding-top | `8px` | `0px` |
| `reading-response-submit` | width | `167.609px` | `87.6562px` |
| `reading-response-text` | box-sizing | `border-box` | `content-box` |
| `reading-response-text` | height | `78px` | `48px` |
| `reading-response-text` | margin-bottom | `0px` | `16px` |
| `reading-response-text` | margin-top | `0px` | `16px` |
| `reading-response-type` | box-sizing | `border-box` | `content-box` |
| `reading-response-type` | height | `36px` | `21px` |
| `reading-response-type` | width | `175.141px` | `43.5px` |
| `reading-response-types` | box-sizing | `border-box` | `content-box` |
| `reading-response-types` | height | `36px` | `37px` |
| `reading-response-types` | margin-bottom | `16px` | `0px` |
| `reading-response-types` | margin-left | `0px` | `2px` |
| `reading-response-types` | margin-right | `0px` | `2px` |
| `reading-response-types` | min-height | `0px` | `auto` |
| `reading-response-types` | width | `622px` | `618px` |
| `reading-response-upvote` | box-sizing | `border-box` | `content-box` |
| `reading-response-upvote` | height | `20px` | `21px` |
| `reading-response-upvote` | width | `37.375px` | `8.84375px` |
| `reading-responses` | height | `1090px` | `701.094px` |
| `reading-responses` | margin-top | `0px` | `24px` |
| `reading-responses` | padding-bottom | `48px` | `64px` |
| `reading-responses` | padding-left | `0px` | `24px` |
| `reading-responses` | padding-right | `0px` | `24px` |
| `reading-responses` | position | `static` | `relative` |
| `reading-responses-h2` | box-sizing | `border-box` | `content-box` |
| `reading-responses-h2` | height | `32px` | `26.0625px` |
| `reading-responses-h2` | margin-bottom | `32px` | `18.0204px` |
| `reading-responses-h2` | margin-top | `0px` | `18.0204px` |
| `reading-responses-inner` | box-sizing | `border-box` | `content-box` |
| `reading-responses-inner` | height | `994px` | `571.078px` |
| `reading-responses-inner` | margin-left | `32px` | `24px` |
| `reading-responses-inner` | margin-right | `32px` | `24px` |
| `storie-author-bio` | box-sizing | `border-box` | `content-box` |
| `storie-author-bio` | width | `501.125px` | `388.844px` |
| `storie-author-name` | box-sizing | `border-box` | `content-box` |
| `storie-author-name` | height | `28px` | `24px` |
| `storie-author-name` | width | `501.125px` | `388.844px` |
| `storie-badge` | box-sizing | `border-box` | `content-box` |
| `storie-badge` | column-gap | `6px` | `normal` |
| `storie-badge` | gap | `6px` | `normal` |
| `storie-badge` | height | `28px` | `20px` |
| `storie-badge` | margin-bottom | `16px` | `0px` |
| `storie-badge` | max-width | `none` | `768px` |
| `storie-badge` | row-gap | `6px` | `normal` |
| `storie-badge` | width | `99.8906px` | `39.4062px` |
| `storie-engagement-bar` | align-items | `normal` | `center` |
| `storie-engagement-bar` | column-gap | `normal` | `24px` |
| `storie-engagement-bar` | display | `block` | `flex` |
| `storie-engagement-bar` | gap | `normal` | `24px` |
| `storie-engagement-bar` | justify-content | `normal` | `space-between` |
| `storie-engagement-bar` | margin-left | `0px` | `294.203px` |
| `storie-engagement-bar` | margin-right | `0px` | `294.219px` |
| `storie-engagement-bar` | margin-top | `0px` | `32px` |
| `storie-engagement-bar` | max-width | `none` | `768px` |
| `storie-engagement-bar` | padding-bottom | `0px` | `12px` |
| `storie-engagement-bar` | padding-left | `0px` | `24px` |
| `storie-engagement-bar` | padding-right | `0px` | `24px` |
| `storie-engagement-bar` | padding-top | `0px` | `12px` |
| `storie-engagement-bar` | row-gap | `normal` | `24px` |
| `storie-engagement-bar` | width | `768px` | `179.578px` |
| `storie-highlights-panel` | box-sizing | `border-box` | `content-box` |
| `storie-highlights-panel` | display | `block` | `flex` |
| `storie-highlights-panel` | flex-direction | `row` | `column` |
| `storie-highlights-panel` | max-width | `none` | `652.8px` |
| `storie-highlights-toggle` | height | `40px` | `38px` |
| `storie-highlights-toggle` | width | `32px` | `30px` |
| `storie-hint` | box-sizing | `border-box` | `content-box` |
| `storie-hint` | column-gap | `8px` | `normal` |
| `storie-hint` | display | `inline-flex` | `flex` |
| `storie-hint` | gap | `8px` | `normal` |
| `storie-hint` | height | `36px` | `21px` |
| `storie-hint` | margin-left | `0px` | `161.234px` |
| `storie-hint` | margin-right | `0px` | `161.25px` |
| `storie-hint` | max-width | `none` | `768px` |
| `storie-hint` | row-gap | `8px` | `normal` |
| `storie-hint` | width | `385.922px` | `365.516px` |
| `storie-paragraph` | box-sizing | `border-box` | `content-box` |
| `storie-paragraph` | margin-bottom | `27px` | `0px` |
| `storie-paragraph` | margin-left | `0px` | `24px` |
| `storie-paragraph` | margin-right | `0px` | `24px` |
| `storie-paragraph` | max-width | `none` | `672px` |
| `storie-prose` | box-sizing | `border-box` | `content-box` |
| `storie-prose` | display | `block` | `flow-root` |
| `storie-prose` | height | `658.641px` | `313.125px` |
| `storie-prose` | max-width | `none` | `768px` |
| `storie-prose` | width | `672px` | `720px` |
| `storie-reaksie-tellers` | flex-wrap | `nowrap` | `wrap` |
| `storie-reaksie-tellers` | width | `54.8281px` | `35.875px` |
| `storie-title` | height | `40px` | `57.5938px` |
| `storie-title` | margin-bottom | `16px` | `0px` |
| `storie-title` | margin-top | `0px` | `12px` |
| `storie-title` | max-width | `none` | `768px` |
| `storie-title` | width | `736px` | `720px` |

### colour — 96

| audit-id | property | reference | INK |
|---|---|---|---|
| `footer` | background-color | `rgba(240, 235, 230, 0.3)` | `rgb(237, 233, 224)` |
| `footer` | border-top-color | `rgb(231, 225, 218)` | `rgb(232, 228, 220)` |
| `footer` | color | `rgb(24, 29, 37)` | `rgb(26, 29, 33)` |
| `footer-blurb` | color | `rgb(103, 111, 126)` | `rgb(107, 114, 128)` |
| `footer-bottom` | border-top-color | `rgb(231, 225, 218)` | `rgb(232, 228, 220)` |
| `footer-bottom` | color | `rgb(24, 29, 37)` | `rgb(26, 29, 33)` |
| `footer-brand` | color | `rgb(24, 29, 37)` | `rgb(26, 29, 33)` |
| `footer-col` | color | `rgb(24, 29, 37)` | `rgb(26, 29, 33)` |
| `footer-col-heading` | color | `rgb(24, 29, 37)` | `rgb(26, 29, 33)` |
| `footer-col-item` | color | `rgb(103, 111, 126)` | `rgb(26, 29, 33)` |
| `footer-col-link` | color | `rgb(103, 111, 126)` | `rgb(107, 114, 128)` |
| `footer-col-list` | color | `rgb(103, 111, 126)` | `rgb(26, 29, 33)` |
| `footer-copyright` | color | `rgb(103, 111, 126)` | `rgb(107, 114, 128)` |
| `footer-grid` | color | `rgb(24, 29, 37)` | `rgb(26, 29, 33)` |
| `footer-logo` | color | `rgb(24, 29, 37)` | `rgb(26, 29, 33)` |
| `footer-logo-text` | color | `rgb(24, 29, 37)` | `rgb(26, 29, 33)` |
| `footer-made-with` | color | `rgb(103, 111, 126)` | `rgb(107, 114, 128)` |
| `header` | background-color | `rgba(250, 248, 245, 0.95)` | `rgba(0, 0, 0, 0)` |
| `header` | color | `rgb(24, 29, 37)` | `rgb(26, 29, 33)` |
| `header-actions` | color | `rgb(24, 29, 37)` | `rgb(26, 29, 33)` |
| `header-btn-join` | color | `rgb(255, 255, 255)` | `rgb(253, 252, 250)` |
| `header-btn-signin` | color | `rgb(24, 29, 37)` | `rgb(26, 29, 33)` |
| `header-logo` | color | `rgb(24, 29, 37)` | `rgb(26, 29, 33)` |
| `header-logo-text` | color | `rgb(24, 29, 37)` | `rgb(26, 29, 33)` |
| `header-menu-toggle` | color | `rgb(24, 29, 37)` | `rgb(107, 114, 128)` |
| `header-nav` | color | `rgb(24, 29, 37)` | `rgb(107, 114, 128)` |
| `header-nav-browse` | color | `rgb(103, 111, 126)` | `rgb(107, 114, 128)` |
| `header-nav-challenges` | color | `rgb(103, 111, 126)` | `rgb(107, 114, 128)` |
| `header-nav-community` | color | `rgb(103, 111, 126)` | `rgb(107, 114, 128)` |
| `header-nav-home` | color | `rgb(103, 111, 126)` | `rgb(107, 114, 128)` |
| `header-nav-library` | color | `rgb(103, 111, 126)` | `rgb(107, 114, 128)` |
| `header-row` | color | `rgb(24, 29, 37)` | `rgb(26, 29, 33)` |
| `reading-author-actions` | color | `rgb(24, 29, 37)` | `rgb(26, 29, 33)` |
| `reading-author-all-works` | background-color | `rgba(240, 235, 230, 0.5)` | `rgba(237, 233, 224, 0.5)` |
| `reading-author-all-works` | color | `rgb(24, 29, 37)` | `rgb(26, 29, 33)` |
| `reading-author-avatar` | border-top-color | `rgb(250, 248, 245)` | `rgb(248, 246, 242)` |
| `reading-author-avatar` | color | `rgb(24, 29, 37)` | `rgb(26, 29, 33)` |
| `reading-author-band` | background-color | `rgba(240, 235, 230, 0.3)` | `rgba(237, 233, 224, 0.3)` |
| `reading-author-band` | border-top-color | `rgb(231, 225, 218)` | `rgb(232, 228, 220)` |
| `reading-author-band` | color | `rgb(24, 29, 37)` | `rgb(26, 29, 33)` |
| `reading-author-body` | color | `rgb(24, 29, 37)` | `rgb(26, 29, 33)` |
| `reading-author-card` | color | `rgb(24, 29, 37)` | `rgb(26, 29, 33)` |
| `reading-body` | color | `rgb(24, 29, 37)` | `rgb(26, 29, 33)` |
| `reading-bookmark-btn` | color | `rgb(103, 111, 126)` | `rgb(107, 114, 128)` |
| `reading-byline` | color | `rgb(103, 111, 126)` | `rgb(26, 29, 33)` |
| `reading-byline-avatar` | color | `rgb(103, 111, 126)` | `rgb(26, 29, 33)` |
| `reading-byline-name` | color | `rgb(24, 29, 37)` | `rgb(26, 29, 33)` |
| `reading-byline-sep` | color | `rgb(103, 111, 126)` | `rgb(107, 114, 128)` |
| `reading-comments-count` | color | `rgb(103, 111, 126)` | `rgb(107, 114, 128)` |
| `reading-comments-link` | color | `rgb(103, 111, 126)` | `rgb(107, 114, 128)` |
| `reading-header` | background-color | `rgba(240, 235, 230, 0.3)` | `rgba(237, 233, 224, 0.3)` |
| `reading-header` | color | `rgb(24, 29, 37)` | `rgb(26, 29, 33)` |
| `reading-header-inner` | color | `rgb(24, 29, 37)` | `rgb(26, 29, 33)` |
| `reading-like-count` | color | `rgb(103, 111, 126)` | `rgb(26, 29, 33)` |
| `reading-like-icon` | color | `rgb(103, 111, 126)` | `rgb(107, 114, 128)` |
| `reading-response-actions` | color | `rgb(24, 29, 37)` | `rgb(26, 29, 33)` |
| `reading-response-author` | color | `rgb(24, 29, 37)` | `rgb(26, 29, 33)` |
| `reading-response-avatar` | color | `rgb(24, 29, 37)` | `rgb(26, 29, 33)` |
| `reading-response-badge` | background-color | `rgba(82, 122, 102, 0.1)` | `rgba(232, 177, 48, 0.1)` |
| `reading-response-badge` | color | `rgb(82, 122, 102)` | `rgb(232, 177, 48)` |
| `reading-response-body` | color | `rgb(24, 29, 37)` | `rgb(26, 29, 33)` |
| `reading-response-date` | color | `rgb(103, 111, 126)` | `rgb(107, 114, 128)` |
| `reading-response-footer` | color | `rgb(24, 29, 37)` | `rgb(26, 29, 33)` |
| `reading-response-form` | background-color | `rgb(253, 253, 252)` | `rgb(253, 252, 250)` |
| `reading-response-form` | border-top-color | `rgb(231, 225, 218)` | `rgb(232, 228, 220)` |
| `reading-response-form` | color | `rgb(24, 29, 37)` | `rgb(26, 29, 33)` |
| `reading-response-input` | background-color | `rgb(250, 248, 245)` | `rgb(255, 255, 255)` |
| `reading-response-input` | border-top-color | `rgb(231, 225, 218)` | `rgb(232, 228, 220)` |
| `reading-response-input` | color | `rgb(24, 29, 37)` | `rgb(0, 0, 0)` |
| `reading-response-intro` | color | `rgb(103, 111, 126)` | `rgb(26, 29, 33)` |
| `reading-response-item` | background-color | `rgb(253, 253, 252)` | `rgb(253, 252, 250)` |
| `reading-response-item` | border-top-color | `rgb(231, 225, 218)` | `rgb(232, 228, 220)` |
| `reading-response-item` | color | `rgb(24, 29, 37)` | `rgb(26, 29, 33)` |
| `reading-response-list` | color | `rgb(24, 29, 37)` | `rgb(26, 29, 33)` |
| `reading-response-meta` | color | `rgb(24, 29, 37)` | `rgb(26, 29, 33)` |
| `reading-response-submit` | color | `rgb(255, 255, 255)` | `rgb(253, 252, 250)` |
| `reading-response-text` | color | `rgb(103, 111, 126)` | `rgb(26, 29, 33)` |
| `reading-response-type` | background-color | `rgba(240, 235, 230, 0.5)` | `rgba(237, 233, 224, 0.5)` |
| `reading-response-type` | color | `rgb(103, 111, 126)` | `rgb(107, 114, 128)` |
| `reading-response-types` | color | `rgb(24, 29, 37)` | `rgb(26, 29, 33)` |
| `reading-response-upvote` | color | `rgb(103, 111, 126)` | `rgb(107, 114, 128)` |
| `reading-responses` | color | `rgb(24, 29, 37)` | `rgb(26, 29, 33)` |
| `reading-responses-h2` | color | `rgb(24, 29, 37)` | `rgb(26, 29, 33)` |
| `reading-responses-inner` | color | `rgb(24, 29, 37)` | `rgb(26, 29, 33)` |
| `storie-author-bio` | color | `rgb(103, 111, 126)` | `rgb(107, 114, 128)` |
| `storie-author-name` | color | `rgb(24, 29, 37)` | `rgb(26, 29, 33)` |
| `storie-engagement-bar` | background-color | `rgba(0, 0, 0, 0)` | `rgba(253, 252, 250, 0.95)` |
| `storie-engagement-bar` | border-top-color | `rgb(231, 225, 218)` | `rgb(232, 228, 220)` |
| `storie-engagement-bar` | color | `rgb(24, 29, 37)` | `rgb(26, 29, 33)` |
| `storie-highlights-panel` | background-color | `rgb(253, 253, 252)` | `rgb(253, 252, 250)` |
| `storie-highlights-panel` | color | `rgb(24, 29, 37)` | `rgb(26, 29, 33)` |
| `storie-highlights-toggle` | color | `rgb(24, 29, 37)` | `rgb(26, 29, 33)` |
| `storie-paragraph` | color | `rgb(24, 29, 37)` | `rgb(26, 29, 33)` |
| `storie-prose` | color | `rgb(24, 29, 37)` | `rgb(26, 29, 33)` |
| `storie-reaksie-tellers` | color | `rgb(103, 111, 126)` | `rgb(107, 114, 128)` |
| `storie-title` | color | `rgb(24, 29, 37)` | `rgb(26, 29, 33)` |

### type — 67

| audit-id | property | reference | INK |
|---|---|---|---|
| `footer-blurb` | line-height | `22.75px` | `23.8px` |
| `footer-col-heading` | font-weight | `600` | `700` |
| `footer-col-heading` | line-height | `24px` | `19.2px` |
| `footer-col-item` | font-size | `14px` | `16px` |
| `footer-col-item` | line-height | `20px` | `24px` |
| `footer-col-link` | line-height | `20px` | `21px` |
| `footer-col-list` | font-size | `14px` | `16px` |
| `footer-col-list` | line-height | `20px` | `24px` |
| `footer-copyright` | line-height | `20px` | `21px` |
| `footer-heart-icon` | line-height | `20px` | `21px` |
| `footer-logo-text` | line-height | `28px` | `27px` |
| `footer-made-with` | line-height | `20px` | `21px` |
| `header-btn-join` | font-weight | `500` | `400` |
| `header-btn-join` | line-height | `20px` | `21px` |
| `header-btn-join` | text-align | `start` | `center` |
| `header-btn-join` | white-space | `nowrap` | `normal` |
| `header-btn-signin` | font-family | `Inter, system-ui, sans-serif` | `Lora, Georgia, serif` |
| `header-btn-signin` | font-weight | `500` | `400` |
| `header-btn-signin` | line-height | `20px` | `21px` |
| `header-btn-signin` | text-align | `start` | `center` |
| `header-btn-signin` | white-space | `nowrap` | `normal` |
| `header-logo-text` | line-height | `28px` | `30px` |
| `header-menu-toggle` | line-height | `24px` | `normal` |
| `reading-author-all-works` | font-weight | `500` | `400` |
| `reading-author-all-works` | line-height | `20px` | `21px` |
| `reading-author-all-works` | white-space | `nowrap` | `normal` |
| `reading-author-avatar` | text-align | `start` | `left` |
| `reading-author-card` | text-align | `start` | `left` |
| `reading-bookmark-btn` | font-family | `Inter, system-ui, sans-serif` | `Arial` |
| `reading-bookmark-btn` | font-size | `16px` | `13.3333px` |
| `reading-bookmark-btn` | line-height | `24px` | `normal` |
| `reading-byline-avatar` | line-height | `24px` | `0px` |
| `reading-byline-name` | font-size | `16px` | `14px` |
| `reading-byline-name` | line-height | `24px` | `21px` |
| `reading-byline-sep` | font-size | `16px` | `14px` |
| `reading-byline-sep` | line-height | `24px` | `21px` |
| `reading-comments-count` | font-size | `16px` | `14px` |
| `reading-comments-count` | font-weight | `500` | `400` |
| `reading-comments-count` | line-height | `24px` | `21px` |
| `reading-comments-link` | font-size | `16px` | `14px` |
| `reading-comments-link` | line-height | `24px` | `21px` |
| `reading-hint-icon` | line-height | `20px` | `21px` |
| `reading-response-badge` | font-weight | `400` | `500` |
| `reading-response-badge` | line-height | `16px` | `18px` |
| `reading-response-date` | line-height | `16px` | `18px` |
| `reading-response-date` | white-space | `normal` | `nowrap` |
| `reading-response-input` | line-height | `20px` | `normal` |
| `reading-response-intro` | font-size | `14px` | `16px` |
| `reading-response-intro` | line-height | `20px` | `24px` |
| `reading-response-submit` | line-height | `20px` | `normal` |
| `reading-response-submit` | white-space | `nowrap` | `normal` |
| `reading-response-text` | line-height | `26px` | `24px` |
| `reading-response-type` | font-weight | `500` | `400` |
| `reading-response-type` | line-height | `20px` | `21px` |
| `reading-response-type` | text-align | `center` | `start` |
| `reading-response-upvote` | line-height | `20px` | `21px` |
| `reading-response-upvote` | text-align | `center` | `start` |
| `reading-responses-h2` | font-size | `24px` | `21.7114px` |
| `reading-responses-h2` | font-weight | `600` | `700` |
| `reading-responses-h2` | line-height | `32px` | `26.0536px` |
| `storie-author-name` | line-height | `28px` | `24px` |
| `storie-highlights-toggle` | font-family | `Inter, system-ui, sans-serif` | `Arial` |
| `storie-highlights-toggle` | font-size | `16px` | `13.3333px` |
| `storie-highlights-toggle` | line-height | `24px` | `normal` |
| `storie-hint` | line-height | `20px` | `21px` |
| `storie-title` | font-size | `36px` | `48px` |
| `storie-title` | line-height | `40px` | `57.6px` |

### micro — 21

| audit-id | property | reference | INK |
|---|---|---|---|
| `header` | z-index | `50` | `auto` |
| `header-btn-join` | box-shadow | `rgba(0, 0, 0, 0) 0px 0px 0px 0px, rgba(0, 0, 0, 0) 0px 0p…` | `rgba(24, 29, 37, 0.08) 0px 4px 20px -4px` |
| `header-nav-browse` | z-index | `auto` | `1` |
| `header-nav-challenges` | z-index | `auto` | `1` |
| `header-nav-community` | z-index | `auto` | `1` |
| `header-nav-home` | z-index | `auto` | `1` |
| `header-nav-library` | z-index | `auto` | `1` |
| `reading-response-submit` | box-shadow | `rgba(0, 0, 0, 0) 0px 0px 0px 0px, rgba(0, 0, 0, 0) 0px 0p…` | `none` |
| `storie-engagement-bar` | border-bottom-left-radius | `0px` | `9999px` |
| `storie-engagement-bar` | border-bottom-right-radius | `0px` | `9999px` |
| `storie-engagement-bar` | border-bottom-width | `0px` | `1px` |
| `storie-engagement-bar` | border-left-width | `0px` | `1px` |
| `storie-engagement-bar` | border-right-width | `0px` | `1px` |
| `storie-engagement-bar` | border-top-left-radius | `0px` | `9999px` |
| `storie-engagement-bar` | border-top-right-radius | `0px` | `9999px` |
| `storie-engagement-bar` | border-top-width | `0px` | `1px` |
| `storie-engagement-bar` | box-shadow | `none` | `rgba(24, 29, 37, 0.12) 0px 12px 40px -8px` |
| `storie-highlights-panel` | box-shadow | `rgba(0, 0, 0, 0) 0px 0px 0px 0px, rgba(0, 0, 0, 0) 0px 0p…` | `rgba(24, 29, 37, 0.12) 0px 12px 40px -8px` |
| `storie-highlights-panel` | transform | `matrix(1, 0, 0, 1, 320, 0)` | `matrix(1, 0, 0, 1, 321, 0)` |
| `storie-highlights-toggle` | box-shadow | `rgba(0, 0, 0, 0) 0px 0px 0px 0px, rgba(0, 0, 0, 0) 0px 0p…` | `rgba(24, 29, 37, 0.12) 0px 12px 40px -8px` |
| `storie-highlights-toggle` | transform | `matrix(1, 0, 0, 1, 0, -20)` | `matrix(1, 0, 0, 1, 0, -19)` |


# Computed-style diff — tuisblad @ 390px

**GENERATED — do not hand-edit.** Regenerate with `tools/fidelity/run.sh diff`.

- Reference: `tmp/fidelity-captures/lovable/tuisblad@390.ndjson` — http://localhost:4173/ (252 nodes)
- INK: `tmp/fidelity-captures/ink/tuisblad@390.ndjson` — https://nuwe-ink.local/ (308 nodes)
- Paired on `data-audit-id`: **131**
- Reference-only ids: 7 (hero-texture, hero-stats, hero-stat, hero-stat-value, hero-stat-label, winner-byline, featured-all-link-mobile)
- INK-only ids: 6 (header-nav-profile, challenge-card-body, winner, winner-cards, sponsors-inner, footer-social)

## Systemic — architectural, not per-element

Each of these differs identically on (nearly) every paired node, so it is one
decision rather than many findings. Resolve at the layer, not on the page.

| property | reference | INK | nodes |
|---|---|---|---|
| border-top-style | `solid` | `none` | 116/131 |

## 869 per-element deltas across 131 elements

### box — 567

| audit-id | property | reference | INK |
|---|---|---|---|
| `challenge` | height | `1000px` | `1039.31px` |
| `challenge` | padding-left | `0px` | `24px` |
| `challenge` | padding-right | `0px` | `24px` |
| `challenge` | position | `static` | `relative` |
| `challenge-card` | height | `400px` | `492.25px` |
| `challenge-card` | width | `358px` | `342px` |
| `challenge-card-btn` | display | `inline-flex` | `flex` |
| `challenge-card-btn` | min-height | `0px` | `40px` |
| `challenge-card-btn` | width | `185.969px` | `276px` |
| `challenge-card-corner` | box-sizing | `border-box` | `content-box` |
| `challenge-card-desc` | box-sizing | `border-box` | `content-box` |
| `challenge-card-desc` | height | `130px` | `135.938px` |
| `challenge-card-desc` | margin-bottom | `24px` | `0px` |
| `challenge-card-desc` | min-height | `0px` | `auto` |
| `challenge-card-desc` | width | `292px` | `276px` |
| `challenge-card-eyebrow` | box-sizing | `border-box` | `content-box` |
| `challenge-card-eyebrow` | height | `20px` | `21px` |
| `challenge-card-eyebrow` | width | `125.453px` | `122.359px` |
| `challenge-card-head` | box-sizing | `border-box` | `content-box` |
| `challenge-card-head` | flex-wrap | `nowrap` | `wrap` |
| `challenge-card-head` | height | `36px` | `32px` |
| `challenge-card-head` | margin-bottom | `16px` | `0px` |
| `challenge-card-head` | min-height | `0px` | `auto` |
| `challenge-card-head` | width | `292px` | `276px` |
| `challenge-card-icon` | align-items | `normal` | `center` |
| `challenge-card-icon` | box-sizing | `border-box` | `content-box` |
| `challenge-card-icon` | display | `block` | `flex` |
| `challenge-card-icon` | height | `36px` | `16px` |
| `challenge-card-icon` | justify-content | `normal` | `center` |
| `challenge-card-icon` | width | `36px` | `16px` |
| `challenge-card-meta` | box-sizing | `border-box` | `content-box` |
| `challenge-card-meta` | column-gap | `16px` | `24px` |
| `challenge-card-meta` | gap | `16px` | `24px` |
| `challenge-card-meta` | height | `20px` | `66px` |
| `challenge-card-meta` | margin-bottom | `24px` | `0px` |
| `challenge-card-meta` | min-height | `0px` | `auto` |
| `challenge-card-meta` | row-gap | `16px` | `24px` |
| `challenge-card-meta` | width | `292px` | `276px` |
| `challenge-card-meta-item` | box-sizing | `border-box` | `content-box` |
| `challenge-card-meta-item` | column-gap | `6px` | `4px` |
| `challenge-card-meta-item` | gap | `6px` | `4px` |
| `challenge-card-meta-item` | height | `20px` | `21px` |
| `challenge-card-meta-item` | row-gap | `6px` | `4px` |
| `challenge-card-meta-item` | width | `101px` | `162.609px` |
| `challenge-card-title` | box-sizing | `border-box` | `content-box` |
| `challenge-card-title` | height | `32px` | `88.3125px` |
| `challenge-card-title` | margin-bottom | `12px` | `0px` |
| `challenge-card-title` | min-height | `0px` | `auto` |
| `challenge-card-title` | width | `292px` | `276px` |
| `challenge-grid` | align-items | `normal` | `stretch` |
| `challenge-grid` | grid-template-columns | `358px` | `342px` |
| `challenge-grid` | height | `872px` | `911.312px` |
| `challenge-grid` | max-width | `none` | `1368px` |
| `challenge-grid` | width | `358px` | `342px` |
| `cta` | height | `648px` | `577.812px` |
| `cta` | padding-bottom | `80px` | `64px` |
| `cta` | padding-left | `0px` | `24px` |
| `cta` | padding-right | `0px` | `24px` |
| `cta` | position | `static` | `relative` |
| `cta-actions` | align-items | `normal` | `center` |
| `cta-actions` | flex-direction | `column` | `row` |
| `cta-actions` | flex-wrap | `nowrap` | `wrap` |
| `cta-actions` | max-width | `none` | `768px` |
| `cta-actions` | width | `278px` | `262px` |
| `cta-band` | height | `488px` | `433.812px` |
| `cta-band` | max-width | `none` | `1368px` |
| `cta-band` | width | `358px` | `342px` |
| `cta-btn-primary` | display | `flex` | `inline-flex` |
| `cta-btn-primary` | min-height | `auto` | `48px` |
| `cta-btn-primary` | padding-bottom | `0px` | `8px` |
| `cta-btn-primary` | padding-top | `0px` | `8px` |
| `cta-btn-primary` | width | `278px` | `261.609px` |
| `cta-btn-secondary` | display | `flex` | `inline-flex` |
| `cta-btn-secondary` | min-height | `auto` | `48px` |
| `cta-btn-secondary` | padding-bottom | `0px` | `8px` |
| `cta-btn-secondary` | padding-top | `0px` | `8px` |
| `cta-btn-secondary` | width | `278px` | `231.719px` |
| `cta-circle-left` | box-sizing | `border-box` | `content-box` |
| `cta-circle-left` | max-width | `none` | `768px` |
| `cta-circle-right` | box-sizing | `border-box` | `content-box` |
| `cta-circle-right` | margin-top | `0px` | `24px` |
| `cta-circle-right` | max-width | `none` | `768px` |
| `cta-content` | height | `408px` | `329.812px` |
| `cta-content` | margin-top | `0px` | `24px` |
| `cta-content` | max-width | `none` | `768px` |
| `cta-content` | width | `278px` | `262px` |
| `cta-h2` | box-sizing | `border-box` | `content-box` |
| `cta-h2` | height | `108px` | `61.8125px` |
| `cta-h2` | max-width | `none` | `768px` |
| `cta-h2` | width | `278px` | `262px` |
| `cta-text` | box-sizing | `border-box` | `content-box` |
| `cta-text` | height | `140px` | `108px` |
| `cta-text` | width | `278px` | `262px` |
| `featured` | box-sizing | `border-box` | `content-box` |
| `featured` | height | `1348.5px` | `1043.91px` |
| `featured` | padding-left | `0px` | `24px` |
| `featured` | padding-right | `0px` | `24px` |
| `featured` | width | `390px` | `342px` |
| `featured-all-link` | box-sizing | `border-box` | `content-box` |
| `featured-card` | box-sizing | `border-box` | `content-box` |
| `featured-card` | height | `243.5px` | `165.594px` |
| `featured-card` | width | `358px` | `292px` |
| `featured-card-author` | box-sizing | `border-box` | `content-box` |
| `featured-card-author` | width | `132.984px` | `136.859px` |
| `featured-card-author-name` | box-sizing | `border-box` | `content-box` |
| `featured-card-author-name` | height | `20px` | `21px` |
| `featured-card-author-name` | width | `88.9844px` | `92.8594px` |
| `featured-card-avatar` | box-sizing | `border-box` | `content-box` |
| `featured-card-avatar` | max-width | `100%` | `none` |
| `featured-card-body` | box-sizing | `border-box` | `content-box` |
| `featured-card-body` | display | `block` | `flex` |
| `featured-card-body` | flex-direction | `row` | `column` |
| `featured-card-body` | height | `193.5px` | `165.594px` |
| `featured-card-body` | width | `308px` | `292px` |
| `featured-card-excerpt` | box-sizing | `border-box` | `content-box` |
| `featured-card-excerpt` | height | `45.5px` | `47.5938px` |
| `featured-card-excerpt` | margin-bottom | `12px` | `0px` |
| `featured-card-excerpt` | min-height | `0px` | `auto` |
| `featured-card-excerpt` | width | `308px` | `292px` |
| `featured-card-footer` | box-sizing | `border-box` | `content-box` |
| `featured-card-footer` | column-gap | `normal` | `16px` |
| `featured-card-footer` | gap | `normal` | `16px` |
| `featured-card-footer` | min-height | `0px` | `auto` |
| `featured-card-footer` | row-gap | `normal` | `16px` |
| `featured-card-footer` | width | `308px` | `292px` |
| `featured-card-meta` | box-sizing | `border-box` | `content-box` |
| `featured-card-meta` | height | `24px` | `26px` |
| `featured-card-meta` | min-height | `0px` | `auto` |
| `featured-card-meta` | width | `308px` | `292px` |
| `featured-card-pill` | align-items | `normal` | `center` |
| `featured-card-pill` | box-sizing | `border-box` | `content-box` |
| `featured-card-pill` | display | `block` | `flex` |
| `featured-card-pill` | height | `24px` | `18px` |
| `featured-card-pill` | padding-left | `10px` | `12px` |
| `featured-card-pill` | padding-right | `10px` | `12px` |
| `featured-card-pill` | width | `57.75px` | `33.9062px` |
| `featured-card-stat` | box-sizing | `border-box` | `content-box` |
| `featured-card-stat` | height | `20px` | `21px` |
| `featured-card-stat` | width | `37.3438px` | `28.8438px` |
| `featured-card-stats` | box-sizing | `border-box` | `content-box` |
| `featured-card-stats` | height | `20px` | `21px` |
| `featured-card-stats` | width | `87.3438px` | `70.5469px` |
| `featured-card-time` | box-sizing | `border-box` | `content-box` |
| `featured-card-time` | height | `16px` | `18px` |
| `featured-card-time` | width | `47.2031px` | `44.7656px` |
| `featured-card-title` | box-sizing | `border-box` | `content-box` |
| `featured-card-title` | height | `56px` | `24px` |
| `featured-card-title` | min-height | `0px` | `auto` |
| `featured-card-title` | width | `308px` | `292px` |
| `featured-container` | box-sizing | `border-box` | `content-box` |
| `featured-container` | height | `1220.5px` | `1043.91px` |
| `featured-container` | max-width | `none` | `1368px` |
| `featured-container` | padding-left | `16px` | `0px` |
| `featured-container` | padding-right | `16px` | `0px` |
| `featured-container` | width | `390px` | `342px` |
| `featured-eyebrow` | box-sizing | `border-box` | `content-box` |
| `featured-eyebrow` | display | `inline` | `block` |
| `featured-eyebrow` | height | `auto` | `21px` |
| `featured-eyebrow` | width | `auto` | `205.297px` |
| `featured-grid` | box-sizing | `border-box` | `content-box` |
| `featured-grid` | grid-template-columns | `358px` | `342px` |
| `featured-grid` | height | `1056.5px` | `945.469px` |
| `featured-grid` | width | `358px` | `342px` |
| `featured-h2` | box-sizing | `border-box` | `content-box` |
| `featured-h2` | height | `36px` | `29.4375px` |
| `featured-h2` | width | `281.109px` | `205.297px` |
| `featured-head` | box-sizing | `border-box` | `content-box` |
| `featured-head` | column-gap | `normal` | `24px` |
| `featured-head` | gap | `normal` | `24px` |
| `featured-head` | height | `68px` | `58.4375px` |
| `featured-head` | row-gap | `normal` | `24px` |
| `featured-head` | width | `358px` | `342px` |
| `featured-head-text` | box-sizing | `border-box` | `content-box` |
| `featured-head-text` | height | `68px` | `58.4375px` |
| `featured-head-text` | width | `281.109px` | `205.297px` |
| `featured-lead` | box-sizing | `border-box` | `content-box` |
| `featured-lead` | height | `282px` | `176.688px` |
| `featured-lead` | width | `358px` | `292px` |
| `featured-lead-author` | box-sizing | `border-box` | `content-box` |
| `featured-lead-author` | width | `141.859px` | `159.859px` |
| `featured-lead-author-name` | box-sizing | `border-box` | `content-box` |
| `featured-lead-author-name` | height | `20px` | `21px` |
| `featured-lead-author-name` | width | `97.8594px` | `115.859px` |
| `featured-lead-avatar` | box-sizing | `border-box` | `content-box` |
| `featured-lead-avatar` | max-width | `100%` | `none` |
| `featured-lead-body` | box-sizing | `border-box` | `content-box` |
| `featured-lead-body` | display | `block` | `flex` |
| `featured-lead-body` | flex-direction | `row` | `column` |
| `featured-lead-body` | height | `232px` | `176.688px` |
| `featured-lead-body` | width | `308px` | `292px` |
| `featured-lead-excerpt` | box-sizing | `border-box` | `content-box` |
| `featured-lead-excerpt` | display | `block` | `-webkit-box` |
| `featured-lead-excerpt` | height | `104px` | `54.375px` |
| `featured-lead-excerpt` | margin-bottom | `16px` | `0px` |
| `featured-lead-excerpt` | min-height | `0px` | `auto` |
| `featured-lead-excerpt` | width | `308px` | `292px` |
| `featured-lead-footer` | box-sizing | `border-box` | `content-box` |
| `featured-lead-footer` | column-gap | `normal` | `16px` |
| `featured-lead-footer` | gap | `normal` | `16px` |
| `featured-lead-footer` | min-height | `0px` | `auto` |
| `featured-lead-footer` | row-gap | `normal` | `16px` |
| `featured-lead-footer` | width | `308px` | `292px` |
| `featured-lead-meta` | box-sizing | `border-box` | `content-box` |
| `featured-lead-meta` | height | `24px` | `26px` |
| `featured-lead-meta` | min-height | `0px` | `auto` |
| `featured-lead-meta` | width | `308px` | `292px` |
| `featured-lead-pill` | align-items | `normal` | `center` |
| `featured-lead-pill` | box-sizing | `border-box` | `content-box` |
| `featured-lead-pill` | display | `block` | `flex` |
| `featured-lead-pill` | height | `24px` | `18px` |
| `featured-lead-pill` | padding-left | `10px` | `12px` |
| `featured-lead-pill` | padding-right | `10px` | `12px` |
| `featured-lead-pill` | width | `85.0469px` | `33.9062px` |
| `featured-lead-stat` | box-sizing | `border-box` | `content-box` |
| `featured-lead-stat` | height | `20px` | `21px` |
| `featured-lead-stat` | width | `43.2812px` | `28.8438px` |
| `featured-lead-stats` | box-sizing | `border-box` | `content-box` |
| `featured-lead-stats` | height | `20px` | `21px` |
| `featured-lead-stats` | width | `96.4844px` | `73.6875px` |
| `featured-lead-time` | box-sizing | `border-box` | `content-box` |
| `featured-lead-time` | height | `16px` | `18px` |
| `featured-lead-time` | width | `47.3125px` | `44.7656px` |
| `featured-lead-title` | box-sizing | `border-box` | `content-box` |
| `featured-lead-title` | height | `32px` | `24.3125px` |
| `featured-lead-title` | min-height | `0px` | `auto` |
| `featured-lead-title` | width | `308px` | `292px` |
| `footer` | height | `855.5px` | `1029.16px` |
| `footer` | min-height | `auto` | `0px` |
| `footer` | padding-bottom | `0px` | `48px` |
| `footer` | padding-left | `0px` | `24px` |
| `footer` | padding-right | `0px` | `24px` |
| `footer` | padding-top | `0px` | `48px` |
| `footer` | position | `static` | `relative` |
| `footer-blurb` | box-sizing | `border-box` | `content-box` |
| `footer-blurb` | height | `45.5px` | `47.5938px` |
| `footer-blurb` | max-width | `none` | `768px` |
| `footer-blurb` | min-height | `0px` | `auto` |
| `footer-blurb` | width | `358px` | `342px` |
| `footer-bottom` | flex-direction | `column` | `row` |
| `footer-bottom` | flex-wrap | `nowrap` | `wrap` |
| `footer-bottom` | height | `101px` | `104px` |
| `footer-bottom` | max-width | `none` | `1368px` |
| `footer-bottom` | width | `358px` | `342px` |
| `footer-brand` | column-gap | `normal` | `16px` |
| `footer-brand` | display | `block` | `flex` |
| `footer-brand` | flex-direction | `row` | `column` |
| `footer-brand` | gap | `normal` | `16px` |
| `footer-brand` | height | `89.5px` | `154.594px` |
| `footer-brand` | position | `static` | `relative` |
| `footer-brand` | row-gap | `normal` | `16px` |
| `footer-brand` | width | `358px` | `342px` |
| `footer-col` | height | `144px` | `155.188px` |
| `footer-col` | margin-top | `0px` | `24px` |
| `footer-col` | position | `static` | `relative` |
| `footer-col` | width | `358px` | `342px` |
| `footer-col-heading` | box-sizing | `border-box` | `content-box` |
| `footer-col-heading` | height | `24px` | `19.1875px` |
| `footer-col-heading` | max-width | `none` | `768px` |
| `footer-col-heading` | width | `358px` | `342px` |
| `footer-col-item` | box-sizing | `border-box` | `content-box` |
| `footer-col-item` | height | `20px` | `24px` |
| `footer-col-item` | min-height | `0px` | `auto` |
| `footer-col-item` | width | `358px` | `342px` |
| `footer-col-link` | box-sizing | `border-box` | `content-box` |
| `footer-col-list` | column-gap | `normal` | `8px` |
| `footer-col-list` | display | `block` | `flex` |
| `footer-col-list` | flex-direction | `row` | `column` |
| `footer-col-list` | gap | `normal` | `8px` |
| `footer-col-list` | height | `104px` | `120px` |
| `footer-col-list` | max-width | `none` | `768px` |
| `footer-col-list` | row-gap | `normal` | `8px` |
| `footer-col-list` | width | `358px` | `342px` |
| `footer-copyright` | box-sizing | `border-box` | `content-box` |
| `footer-copyright` | height | `40px` | `42px` |
| `footer-copyright` | width | `358px` | `342px` |
| `footer-grid` | grid-template-columns | `358px` | `342px` |
| `footer-grid` | height | `617.5px` | `788.156px` |
| `footer-grid` | max-width | `none` | `1368px` |
| `footer-grid` | width | `358px` | `342px` |
| `footer-heart-icon` | box-sizing | `border-box` | `content-box` |
| `footer-logo` | flex-wrap | `nowrap` | `wrap` |
| `footer-logo` | height | `28px` | `27px` |
| `footer-logo` | margin-bottom | `16px` | `0px` |
| `footer-logo` | margin-left | `0px` | `140.422px` |
| `footer-logo` | margin-right | `0px` | `140.422px` |
| `footer-logo` | max-width | `none` | `768px` |
| `footer-logo` | min-height | `0px` | `auto` |
| `footer-logo` | width | `358px` | `61.1562px` |
| `footer-logo-icon` | box-sizing | `border-box` | `content-box` |
| `footer-logo-text` | height | `28px` | `27px` |
| `footer-logo-text` | width | `62.9375px` | `33.1562px` |
| `footer-made-with` | box-sizing | `border-box` | `content-box` |
| `footer-made-with` | height | `20px` | `21px` |
| `footer-made-with` | width | `242.828px` | `217.016px` |
| `header` | min-height | `auto` | `0px` |
| `header` | padding-left | `0px` | `16px` |
| `header` | padding-right | `0px` | `16px` |
| `header` | position | `sticky` | `relative` |
| `header-actions` | display | `none` | `flex` |
| `header-actions` | flex-wrap | `nowrap` | `wrap` |
| `header-actions` | height | `auto` | `37px` |
| `header-actions` | min-height | `0px` | `auto` |
| `header-actions` | width | `auto` | `171.797px` |
| `header-btn-join` | align-items | `center` | `normal` |
| `header-btn-join` | column-gap | `8px` | `normal` |
| `header-btn-join` | display | `inline-flex` | `inline-block` |
| `header-btn-join` | gap | `8px` | `normal` |
| `header-btn-join` | height | `36px` | `37px` |
| `header-btn-join` | justify-content | `center` | `normal` |
| `header-btn-join` | padding-bottom | `0px` | `8px` |
| `header-btn-join` | padding-top | `0px` | `8px` |
| `header-btn-join` | row-gap | `8px` | `normal` |
| `header-btn-join` | width | `auto` | `80.3125px` |
| `header-btn-signin` | align-items | `center` | `normal` |
| `header-btn-signin` | column-gap | `8px` | `normal` |
| `header-btn-signin` | display | `inline-flex` | `inline-block` |
| `header-btn-signin` | gap | `8px` | `normal` |
| `header-btn-signin` | height | `36px` | `37px` |
| `header-btn-signin` | justify-content | `center` | `normal` |
| `header-btn-signin` | padding-bottom | `0px` | `8px` |
| `header-btn-signin` | padding-top | `0px` | `8px` |
| `header-btn-signin` | row-gap | `8px` | `normal` |
| `header-btn-signin` | width | `auto` | `79.4844px` |
| `header-logo` | flex-wrap | `nowrap` | `wrap` |
| `header-logo` | height | `28px` | `30px` |
| `header-logo` | width | `101.922px` | `68.8281px` |
| `header-logo-icon` | box-sizing | `border-box` | `content-box` |
| `header-logo-text` | height | `28px` | `30px` |
| `header-logo-text` | width | `69.9219px` | `36.8281px` |
| `header-menu-toggle` | display | `block` | `flex` |
| `header-menu-toggle` | height | `40px` | `24px` |
| `header-menu-toggle` | padding-bottom | `8px` | `0px` |
| `header-menu-toggle` | padding-left | `8px` | `0px` |
| `header-menu-toggle` | padding-right | `8px` | `0px` |
| `header-menu-toggle` | padding-top | `8px` | `0px` |
| `header-menu-toggle` | width | `40px` | `24px` |
| `header-nav` | box-sizing | `border-box` | `content-box` |
| `header-nav` | display | `none` | `flex` |
| `header-nav` | flex-wrap | `nowrap` | `wrap` |
| `header-nav` | height | `auto` | `24px` |
| `header-nav` | min-height | `0px` | `auto` |
| `header-nav` | position | `static` | `relative` |
| `header-nav` | width | `auto` | `24px` |
| `header-nav-browse` | box-sizing | `border-box` | `content-box` |
| `header-nav-browse` | display | `inline` | `block` |
| `header-nav-challenges` | box-sizing | `border-box` | `content-box` |
| `header-nav-challenges` | display | `inline` | `block` |
| `header-nav-community` | box-sizing | `border-box` | `content-box` |
| `header-nav-community` | display | `inline` | `block` |
| `header-nav-home` | box-sizing | `border-box` | `content-box` |
| `header-nav-home` | display | `inline` | `block` |
| `header-nav-library` | box-sizing | `border-box` | `content-box` |
| `header-nav-library` | display | `inline` | `block` |
| `header-row` | column-gap | `normal` | `24px` |
| `header-row` | flex-wrap | `nowrap` | `wrap` |
| `header-row` | gap | `normal` | `24px` |
| `header-row` | max-width | `none` | `1368px` |
| `header-row` | min-height | `0px` | `64px` |
| `header-row` | row-gap | `normal` | `24px` |
| `hero` | height | `920px` | `867.812px` |
| `hero` | padding-bottom | `48px` | `64px` |
| `hero` | padding-left | `0px` | `24px` |
| `hero` | padding-right | `0px` | `24px` |
| `hero` | padding-top | `48px` | `64px` |
| `hero-actions` | align-items | `normal` | `center` |
| `hero-actions` | margin-top | `0px` | `24px` |
| `hero-actions` | max-width | `none` | `768px` |
| `hero-actions` | width | `358px` | `342px` |
| `hero-aside` | height | `312px` | `307.812px` |
| `hero-aside` | position | `static` | `relative` |
| `hero-aside` | width | `358px` | `342px` |
| `hero-badge` | box-sizing | `border-box` | `content-box` |
| `hero-badge` | height | `32px` | `20px` |
| `hero-badge` | margin-bottom | `24px` | `0px` |
| `hero-badge` | max-width | `none` | `768px` |
| `hero-badge` | width | `268.5px` | `165.234px` |
| `hero-btn-outline` | display | `flex` | `inline-flex` |
| `hero-btn-outline` | min-height | `auto` | `0px` |
| `hero-btn-outline` | width | `227.078px` | `193.797px` |
| `hero-btn-primary` | display | `flex` | `inline-flex` |
| `hero-btn-primary` | min-height | `auto` | `0px` |
| `hero-btn-primary` | width | `197.766px` | `163.297px` |
| `hero-content` | height | `349px` | `384px` |
| `hero-content` | position | `static` | `relative` |
| `hero-content` | width | `358px` | `342px` |
| `hero-grid` | column-gap | `32px` | `48px` |
| `hero-grid` | gap | `32px` | `48px` |
| `hero-grid` | grid-template-columns | `358px` | `342px` |
| `hero-grid` | height | `693px` | `739.812px` |
| `hero-grid` | max-width | `none` | `1368px` |
| `hero-grid` | position | `static` | `relative` |
| `hero-grid` | row-gap | `32px` | `48px` |
| `hero-grid` | width | `358px` | `342px` |
| `hero-h1` | box-sizing | `border-box` | `content-box` |
| `hero-h1` | height | `75px` | `108px` |
| `hero-h1` | margin-bottom | `16px` | `0px` |
| `hero-h1` | margin-top | `0px` | `24px` |
| `hero-h1` | max-width | `none` | `768px` |
| `hero-h1` | width | `358px` | `342px` |
| `hero-paragraph` | box-sizing | `border-box` | `content-box` |
| `hero-paragraph` | height | `78px` | `72px` |
| `hero-paragraph` | margin-bottom | `24px` | `0px` |
| `hero-paragraph` | margin-top | `0px` | `24px` |
| `hero-paragraph` | max-width | `none` | `768px` |
| `hero-paragraph` | width | `358px` | `342px` |
| `hero-spotlight` | height | `312px` | `307.812px` |
| `hero-spotlight` | max-width | `none` | `768px` |
| `hero-spotlight` | overflow | `visible` | `hidden` |
| `hero-spotlight` | width | `358px` | `342px` |
| `hero-spotlight-badge` | box-sizing | `border-box` | `content-box` |
| `hero-spotlight-badge` | column-gap | `6px` | `4px` |
| `hero-spotlight-badge` | gap | `6px` | `4px` |
| `hero-spotlight-badge` | height | `24px` | `18px` |
| `hero-spotlight-badge` | row-gap | `6px` | `4px` |
| `hero-spotlight-badge` | width | `174.266px` | `92.3438px` |
| `hero-spotlight-body` | box-sizing | `border-box` | `content-box` |
| `hero-spotlight-body` | column-gap | `normal` | `16px` |
| `hero-spotlight-body` | display | `block` | `flex` |
| `hero-spotlight-body` | flex-direction | `row` | `column` |
| `hero-spotlight-body` | gap | `normal` | `16px` |
| `hero-spotlight-body` | height | `262px` | `257.812px` |
| `hero-spotlight-body` | row-gap | `normal` | `16px` |
| `hero-spotlight-body` | width | `308px` | `292px` |
| `hero-spotlight-btn` | display | `inline-flex` | `flex` |
| `hero-spotlight-btn` | min-height | `0px` | `40px` |
| `hero-spotlight-btn` | width | `308px` | `292px` |
| `hero-spotlight-corner` | box-sizing | `border-box` | `content-box` |
| `hero-spotlight-date` | box-sizing | `border-box` | `content-box` |
| `hero-spotlight-date` | height | `16px` | `18px` |
| `hero-spotlight-date` | width | `120.047px` | `142.234px` |
| `hero-spotlight-desc` | box-sizing | `border-box` | `content-box` |
| `hero-spotlight-desc` | height | `91px` | `95.1875px` |
| `hero-spotlight-desc` | margin-bottom | `24px` | `0px` |
| `hero-spotlight-desc` | min-height | `0px` | `auto` |
| `hero-spotlight-desc` | width | `308px` | `292px` |
| `hero-spotlight-head` | box-sizing | `border-box` | `content-box` |
| `hero-spotlight-head` | flex-wrap | `nowrap` | `wrap` |
| `hero-spotlight-head` | height | `24px` | `26px` |
| `hero-spotlight-head` | margin-bottom | `16px` | `0px` |
| `hero-spotlight-head` | min-height | `0px` | `auto` |
| `hero-spotlight-head` | width | `308px` | `292px` |
| `hero-spotlight-title` | box-sizing | `border-box` | `content-box` |
| `hero-spotlight-title` | height | `55px` | `48.625px` |
| `hero-spotlight-title` | margin-bottom | `12px` | `0px` |
| `hero-spotlight-title` | min-height | `0px` | `auto` |
| `hero-spotlight-title` | width | `308px` | `292px` |
| `sponsors` | height | `870px` | `896.062px` |
| `sponsors` | padding-left | `0px` | `24px` |
| `sponsors` | padding-right | `0px` | `24px` |
| `sponsors` | position | `static` | `relative` |
| `sponsors-btn` | display | `inline-flex` | `flex` |
| `sponsors-btn` | justify-content | `center` | `normal` |
| `sponsors-btn` | min-height | `0px` | `44px` |
| `sponsors-btn` | padding-bottom | `0px` | `8px` |
| `sponsors-btn` | padding-top | `0px` | `8px` |
| `sponsors-btn` | width | `244.016px` | `189.141px` |
| `sponsors-chip-bronze` | align-items | `normal` | `center` |
| `sponsors-chip-bronze` | box-sizing | `border-box` | `content-box` |
| `sponsors-chip-bronze` | display | `block` | `flex` |
| `sponsors-chip-bronze` | height | `48px` | `24px` |
| `sponsors-chip-bronze` | justify-content | `normal` | `center` |
| `sponsors-chip-bronze` | width | `141.312px` | `94.9219px` |
| `sponsors-chip-bronze-name` | box-sizing | `border-box` | `content-box` |
| `sponsors-chip-bronze-name` | display | `inline` | `block` |
| `sponsors-chip-bronze-name` | height | `auto` | `24px` |
| `sponsors-chip-bronze-name` | min-height | `0px` | `auto` |
| `sponsors-chip-bronze-name` | width | `auto` | `94.9219px` |
| `sponsors-chip-gold` | align-items | `normal` | `center` |
| `sponsors-chip-gold` | box-sizing | `border-box` | `content-box` |
| `sponsors-chip-gold` | display | `block` | `flex` |
| `sponsors-chip-gold` | height | `54px` | `27px` |
| `sponsors-chip-gold` | justify-content | `normal` | `center` |
| `sponsors-chip-gold` | width | `194.984px` | `191.094px` |
| `sponsors-chip-gold-name` | box-sizing | `border-box` | `content-box` |
| `sponsors-chip-gold-name` | display | `inline` | `block` |
| `sponsors-chip-gold-name` | height | `auto` | `27px` |
| `sponsors-chip-gold-name` | min-height | `0px` | `auto` |
| `sponsors-chip-gold-name` | width | `auto` | `191.094px` |
| `sponsors-chip-silver` | align-items | `normal` | `center` |
| `sponsors-chip-silver` | box-sizing | `border-box` | `content-box` |
| `sponsors-chip-silver` | display | `block` | `flex` |
| `sponsors-chip-silver` | height | `50px` | `24px` |
| `sponsors-chip-silver` | justify-content | `normal` | `center` |
| `sponsors-chip-silver` | width | `212.812px` | `160.781px` |
| `sponsors-chip-silver-name` | box-sizing | `border-box` | `content-box` |
| `sponsors-chip-silver-name` | display | `inline` | `block` |
| `sponsors-chip-silver-name` | height | `auto` | `24px` |
| `sponsors-chip-silver-name` | min-height | `0px` | `auto` |
| `sponsors-chip-silver-name` | width | `auto` | `160.781px` |
| `sponsors-eyebrow` | box-sizing | `border-box` | `content-box` |
| `sponsors-eyebrow` | display | `inline` | `block` |
| `sponsors-eyebrow` | height | `auto` | `21px` |
| `sponsors-eyebrow` | min-height | `0px` | `auto` |
| `sponsors-eyebrow` | width | `auto` | `92.0938px` |
| `sponsors-grid` | min-height | `0px` | `auto` |
| `sponsors-grid` | width | `358px` | `342px` |
| `sponsors-h2` | box-sizing | `border-box` | `content-box` |
| `sponsors-h2` | height | `32px` | `24.3125px` |
| `sponsors-h2` | min-height | `0px` | `auto` |
| `sponsors-h2` | width | `358px` | `215.703px` |
| `sponsors-intro` | box-sizing | `border-box` | `content-box` |
| `sponsors-intro` | height | `72px` | `108.75px` |
| `sponsors-intro` | min-height | `0px` | `auto` |
| `sponsors-intro` | width | `358px` | `342px` |
| `winner-author` | box-sizing | `border-box` | `content-box` |
| `winner-author` | height | `48px` | `52px` |
| `winner-author` | min-height | `0px` | `auto` |
| `winner-author` | position | `static` | `relative` |
| `winner-author` | width | `292px` | `276px` |
| `winner-author-details` | box-sizing | `border-box` | `content-box` |
| `winner-author-details` | display | `block` | `flex` |
| `winner-author-details` | flex-direction | `row` | `column` |
| `winner-author-details` | height | `44px` | `45px` |
| `winner-author-details` | width | `121.922px` | `102.906px` |
| `winner-author-name` | box-sizing | `border-box` | `content-box` |
| `winner-author-name` | min-height | `0px` | `auto` |
| `winner-author-name` | width | `121.922px` | `102.906px` |
| `winner-author-wins` | box-sizing | `border-box` | `content-box` |
| `winner-author-wins` | height | `20px` | `21px` |
| `winner-author-wins` | min-height | `0px` | `auto` |
| `winner-author-wins` | width | `121.922px` | `102.906px` |
| `winner-avatar` | box-sizing | `border-box` | `content-box` |
| `winner-avatar` | max-width | `100%` | `none` |
| `winner-btn` | column-gap | `8px` | `normal` |
| `winner-btn` | display | `inline-flex` | `flex` |
| `winner-btn` | gap | `8px` | `normal` |
| `winner-btn` | justify-content | `center` | `normal` |
| `winner-btn` | margin-top | `24px` | `0px` |
| `winner-btn` | min-height | `0px` | `40px` |
| `winner-btn` | position | `static` | `relative` |
| `winner-btn` | row-gap | `8px` | `normal` |
| `winner-btn` | width | `134.375px` | `191.734px` |
| `winner-card` | column-gap | `normal` | `16px` |
| `winner-card` | display | `block` | `flex` |
| `winner-card` | flex-direction | `row` | `column` |
| `winner-card` | gap | `normal` | `16px` |
| `winner-card` | height | `440px` | `387.062px` |
| `winner-card` | row-gap | `normal` | `16px` |
| `winner-card` | width | `358px` | `342px` |
| `winner-eyebrow` | box-sizing | `border-box` | `content-box` |
| `winner-eyebrow` | height | `36px` | `32px` |
| `winner-eyebrow` | margin-bottom | `16px` | `0px` |
| `winner-eyebrow` | min-height | `0px` | `auto` |
| `winner-eyebrow` | position | `static` | `relative` |
| `winner-eyebrow` | width | `292px` | `276px` |
| `winner-eyebrow-icon` | align-items | `normal` | `center` |
| `winner-eyebrow-icon` | box-sizing | `border-box` | `content-box` |
| `winner-eyebrow-icon` | display | `block` | `flex` |
| `winner-eyebrow-icon` | height | `36px` | `16px` |
| `winner-eyebrow-icon` | justify-content | `normal` | `center` |
| `winner-eyebrow-icon` | width | `36px` | `16px` |
| `winner-eyebrow-text` | box-sizing | `border-box` | `content-box` |
| `winner-eyebrow-text` | height | `20px` | `21px` |
| `winner-eyebrow-text` | width | `121.125px` | `121.25px` |
| `winner-quote` | box-sizing | `border-box` | `content-box` |
| `winner-quote` | height | `78px` | `108.75px` |
| `winner-quote` | margin-bottom | `24px` | `0px` |
| `winner-quote` | min-height | `0px` | `auto` |
| `winner-quote` | position | `static` | `relative` |
| `winner-quote` | width | `292px` | `276px` |
| `winner-title` | box-sizing | `border-box` | `content-box` |
| `winner-title` | height | `64px` | `24.3125px` |
| `winner-title` | margin-bottom | `8px` | `0px` |
| `winner-title` | min-height | `0px` | `auto` |
| `winner-title` | position | `static` | `relative` |
| `winner-title` | width | `292px` | `276px` |
| `winner-watermark` | box-sizing | `border-box` | `content-box` |

### colour — 152

| audit-id | property | reference | INK |
|---|---|---|---|
| `challenge` | background-color | `rgba(240, 235, 230, 0.3)` | `rgba(237, 233, 224, 0.3)` |
| `challenge` | color | `rgb(24, 29, 37)` | `rgb(26, 29, 33)` |
| `challenge-card` | background-color | `rgb(253, 253, 252)` | `rgb(253, 252, 250)` |
| `challenge-card` | border-top-color | `rgb(231, 225, 218)` | `rgb(232, 228, 220)` |
| `challenge-card` | color | `rgb(24, 29, 37)` | `rgb(26, 29, 33)` |
| `challenge-card-btn` | color | `rgb(255, 255, 255)` | `rgb(253, 252, 250)` |
| `challenge-card-corner` | color | `rgb(24, 29, 37)` | `rgb(26, 29, 33)` |
| `challenge-card-desc` | color | `rgb(103, 111, 126)` | `rgb(107, 114, 128)` |
| `challenge-card-head` | color | `rgb(24, 29, 37)` | `rgb(26, 29, 33)` |
| `challenge-card-icon` | color | `rgb(24, 29, 37)` | `rgb(236, 59, 19)` |
| `challenge-card-meta` | color | `rgb(103, 111, 126)` | `rgb(26, 29, 33)` |
| `challenge-card-meta-item` | color | `rgb(103, 111, 126)` | `rgb(107, 114, 128)` |
| `challenge-card-title` | color | `rgb(24, 29, 37)` | `rgb(26, 29, 33)` |
| `challenge-grid` | color | `rgb(24, 29, 37)` | `rgb(26, 29, 33)` |
| `cta` | color | `rgb(24, 29, 37)` | `rgb(26, 29, 33)` |
| `cta-actions` | color | `rgb(255, 255, 255)` | `rgb(26, 29, 33)` |
| `cta-band` | background-image | `linear-gradient(to right bottom, rgb(236, 59, 19), rgb(24…` | `linear-gradient(135deg, rgb(236, 59, 19), rgb(239, 104, 66))` |
| `cta-band` | color | `rgb(255, 255, 255)` | `rgb(26, 29, 33)` |
| `cta-btn-primary` | background-color | `rgb(255, 255, 255)` | `rgb(253, 252, 250)` |
| `cta-btn-secondary` | background-color | `rgb(250, 248, 245)` | `rgba(0, 0, 0, 0)` |
| `cta-btn-secondary` | border-top-color | `rgba(255, 255, 255, 0.3)` | `rgb(253, 252, 250)` |
| `cta-btn-secondary` | color | `rgb(255, 255, 255)` | `rgb(253, 252, 250)` |
| `cta-circle-left` | background-color | `rgba(255, 255, 255, 0.05)` | `rgba(253, 252, 250, 0.05)` |
| `cta-circle-left` | color | `rgb(255, 255, 255)` | `rgb(26, 29, 33)` |
| `cta-circle-right` | background-color | `rgba(255, 255, 255, 0.05)` | `rgba(253, 252, 250, 0.05)` |
| `cta-circle-right` | color | `rgb(255, 255, 255)` | `rgb(26, 29, 33)` |
| `cta-content` | color | `rgb(255, 255, 255)` | `rgb(26, 29, 33)` |
| `cta-h2` | color | `rgb(255, 255, 255)` | `rgb(253, 252, 250)` |
| `cta-text` | color | `rgba(255, 255, 255, 0.8)` | `rgb(253, 252, 250)` |
| `featured` | color | `rgb(24, 29, 37)` | `rgb(26, 29, 33)` |
| `featured-card` | background-color | `rgb(253, 253, 252)` | `rgb(253, 252, 250)` |
| `featured-card` | border-top-color | `rgb(231, 225, 218)` | `rgb(232, 228, 220)` |
| `featured-card` | color | `rgb(24, 29, 37)` | `rgb(26, 29, 33)` |
| `featured-card-author` | color | `rgb(24, 29, 37)` | `rgb(26, 29, 33)` |
| `featured-card-author-name` | color | `rgb(24, 29, 37)` | `rgb(26, 29, 33)` |
| `featured-card-avatar` | color | `rgb(24, 29, 37)` | `rgb(26, 29, 33)` |
| `featured-card-body` | color | `rgb(24, 29, 37)` | `rgb(26, 29, 33)` |
| `featured-card-excerpt` | color | `rgb(103, 111, 126)` | `rgb(107, 114, 128)` |
| `featured-card-footer` | color | `rgb(24, 29, 37)` | `rgb(26, 29, 33)` |
| `featured-card-meta` | color | `rgb(24, 29, 37)` | `rgb(26, 29, 33)` |
| `featured-card-pill` | background-color | `rgb(240, 235, 230)` | `rgba(82, 122, 102, 0.15)` |
| `featured-card-pill` | color | `rgb(24, 29, 37)` | `rgb(82, 122, 102)` |
| `featured-card-stat` | color | `rgb(103, 111, 126)` | `rgb(107, 114, 128)` |
| `featured-card-stats` | color | `rgb(103, 111, 126)` | `rgb(107, 114, 128)` |
| `featured-card-time` | color | `rgb(103, 111, 126)` | `rgb(107, 114, 128)` |
| `featured-card-title` | color | `rgb(24, 29, 37)` | `rgb(26, 29, 33)` |
| `featured-container` | color | `rgb(24, 29, 37)` | `rgb(26, 29, 33)` |
| `featured-grid` | color | `rgb(24, 29, 37)` | `rgb(26, 29, 33)` |
| `featured-h2` | color | `rgb(24, 29, 37)` | `rgb(26, 29, 33)` |
| `featured-head` | color | `rgb(24, 29, 37)` | `rgb(26, 29, 33)` |
| `featured-head-text` | color | `rgb(24, 29, 37)` | `rgb(26, 29, 33)` |
| `featured-lead` | background-color | `rgb(253, 253, 252)` | `rgb(253, 252, 250)` |
| `featured-lead` | border-top-color | `rgb(231, 225, 218)` | `rgb(232, 228, 220)` |
| `featured-lead` | color | `rgb(24, 29, 37)` | `rgb(26, 29, 33)` |
| `featured-lead-author` | color | `rgb(24, 29, 37)` | `rgb(26, 29, 33)` |
| `featured-lead-author-name` | color | `rgb(24, 29, 37)` | `rgb(26, 29, 33)` |
| `featured-lead-avatar` | color | `rgb(24, 29, 37)` | `rgb(26, 29, 33)` |
| `featured-lead-body` | color | `rgb(24, 29, 37)` | `rgb(26, 29, 33)` |
| `featured-lead-excerpt` | color | `rgb(103, 111, 126)` | `rgb(107, 114, 128)` |
| `featured-lead-footer` | color | `rgb(24, 29, 37)` | `rgb(26, 29, 33)` |
| `featured-lead-meta` | color | `rgb(24, 29, 37)` | `rgb(26, 29, 33)` |
| `featured-lead-pill` | background-color | `rgb(240, 235, 230)` | `rgba(82, 122, 102, 0.15)` |
| `featured-lead-pill` | color | `rgb(24, 29, 37)` | `rgb(82, 122, 102)` |
| `featured-lead-stat` | color | `rgb(103, 111, 126)` | `rgb(107, 114, 128)` |
| `featured-lead-stats` | color | `rgb(103, 111, 126)` | `rgb(107, 114, 128)` |
| `featured-lead-time` | color | `rgb(103, 111, 126)` | `rgb(107, 114, 128)` |
| `featured-lead-title` | color | `rgb(24, 29, 37)` | `rgb(26, 29, 33)` |
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
| `hero` | color | `rgb(24, 29, 37)` | `rgb(26, 29, 33)` |
| `hero-actions` | color | `rgb(24, 29, 37)` | `rgb(26, 29, 33)` |
| `hero-aside` | color | `rgb(24, 29, 37)` | `rgb(26, 29, 33)` |
| `hero-btn-primary` | color | `rgb(255, 255, 255)` | `rgb(253, 252, 250)` |
| `hero-content` | color | `rgb(24, 29, 37)` | `rgb(26, 29, 33)` |
| `hero-grid` | color | `rgb(24, 29, 37)` | `rgb(26, 29, 33)` |
| `hero-h1` | color | `rgb(24, 29, 37)` | `rgb(26, 29, 33)` |
| `hero-paragraph` | color | `rgb(103, 111, 126)` | `rgb(107, 114, 128)` |
| `hero-spotlight` | background-color | `rgb(253, 253, 252)` | `rgb(253, 252, 250)` |
| `hero-spotlight` | border-top-color | `rgb(231, 225, 218)` | `rgb(232, 228, 220)` |
| `hero-spotlight` | color | `rgb(24, 29, 37)` | `rgb(26, 29, 33)` |
| `hero-spotlight-body` | color | `rgb(24, 29, 37)` | `rgb(26, 29, 33)` |
| `hero-spotlight-btn` | color | `rgb(255, 255, 255)` | `rgb(253, 252, 250)` |
| `hero-spotlight-corner` | color | `rgb(24, 29, 37)` | `rgb(26, 29, 33)` |
| `hero-spotlight-date` | color | `rgb(103, 111, 126)` | `rgb(107, 114, 128)` |
| `hero-spotlight-desc` | color | `rgb(103, 111, 126)` | `rgb(107, 114, 128)` |
| `hero-spotlight-head` | color | `rgb(24, 29, 37)` | `rgb(26, 29, 33)` |
| `hero-spotlight-title` | color | `rgb(24, 29, 37)` | `rgb(26, 29, 33)` |
| `sponsors` | background-color | `rgba(240, 235, 230, 0.2)` | `rgba(237, 233, 224, 0.2)` |
| `sponsors` | color | `rgb(24, 29, 37)` | `rgb(26, 29, 33)` |
| `sponsors-chip-bronze` | background-color | `rgba(243, 240, 237, 0.5)` | `rgba(237, 233, 224, 0.5)` |
| `sponsors-chip-bronze` | border-top-color | `rgb(231, 225, 218)` | `rgba(0, 0, 0, 0)` |
| `sponsors-chip-bronze` | color | `rgb(24, 29, 37)` | `rgb(26, 29, 33)` |
| `sponsors-chip-bronze-name` | color | `rgb(24, 29, 37)` | `rgb(26, 29, 33)` |
| `sponsors-chip-gold` | background-color | `rgba(232, 177, 48, 0.1)` | `rgba(201, 184, 138, 0.1)` |
| `sponsors-chip-gold` | border-top-color | `rgba(232, 177, 48, 0.2)` | `rgba(201, 184, 138, 0.2)` |
| `sponsors-chip-gold` | color | `rgb(24, 29, 37)` | `rgb(26, 29, 33)` |
| `sponsors-chip-gold-name` | color | `rgb(24, 29, 37)` | `rgb(26, 29, 33)` |
| `sponsors-chip-silver` | background-color | `rgb(240, 235, 230)` | `rgb(237, 233, 224)` |
| `sponsors-chip-silver` | border-top-color | `rgb(231, 225, 218)` | `rgb(232, 228, 220)` |
| `sponsors-chip-silver` | color | `rgb(24, 29, 37)` | `rgb(26, 29, 33)` |
| `sponsors-chip-silver-name` | color | `rgb(24, 29, 37)` | `rgb(26, 29, 33)` |
| `sponsors-grid` | color | `rgb(24, 29, 37)` | `rgb(26, 29, 33)` |
| `sponsors-h2` | color | `rgb(24, 29, 37)` | `rgb(26, 29, 33)` |
| `sponsors-intro` | color | `rgb(103, 111, 126)` | `rgb(107, 114, 128)` |
| `winner-author` | color | `rgb(24, 29, 37)` | `rgb(26, 29, 33)` |
| `winner-author-details` | color | `rgb(24, 29, 37)` | `rgb(26, 29, 33)` |
| `winner-author-name` | color | `rgb(24, 29, 37)` | `rgb(26, 29, 33)` |
| `winner-author-wins` | color | `rgb(103, 111, 126)` | `rgb(107, 114, 128)` |
| `winner-avatar` | border-top-color | `rgba(232, 177, 48, 0.3)` | `rgba(201, 184, 138, 0.3)` |
| `winner-avatar` | color | `rgb(24, 29, 37)` | `rgb(26, 29, 33)` |
| `winner-btn` | background-color | `rgb(250, 248, 245)` | `rgba(0, 0, 0, 0)` |
| `winner-btn` | border-top-color | `rgba(232, 177, 48, 0.3)` | `rgba(201, 184, 138, 0.3)` |
| `winner-btn` | color | `rgb(24, 29, 37)` | `rgb(26, 29, 33)` |
| `winner-card` | background-color | `rgba(0, 0, 0, 0)` | `rgb(253, 252, 250)` |
| `winner-card` | background-image | `linear-gradient(to right bottom, rgba(232, 177, 48, 0.1),…` | `linear-gradient(135deg, color(srgb 0.909804 0.694118 0.18…` |
| `winner-card` | border-top-color | `rgba(217, 201, 166, 0.3)` | `rgba(201, 184, 138, 0.3)` |
| `winner-card` | color | `rgb(24, 29, 37)` | `rgb(26, 29, 33)` |
| `winner-eyebrow` | color | `rgb(24, 29, 37)` | `rgb(232, 177, 48)` |
| `winner-eyebrow-icon` | color | `rgb(24, 29, 37)` | `rgb(232, 177, 48)` |
| `winner-quote` | color | `rgb(103, 111, 126)` | `rgb(107, 114, 128)` |
| `winner-title` | color | `rgb(24, 29, 37)` | `rgb(26, 29, 33)` |
| `winner-watermark` | color | `rgb(24, 29, 37)` | `rgba(232, 177, 48, 0.2)` |

### type — 108

| audit-id | property | reference | INK |
|---|---|---|---|
| `challenge-card-btn` | line-height | `20px` | `21px` |
| `challenge-card-btn` | text-align | `center` | `start` |
| `challenge-card-btn` | white-space | `nowrap` | `normal` |
| `challenge-card-desc` | line-height | `26px` | `27.2px` |
| `challenge-card-eyebrow` | line-height | `20px` | `21px` |
| `challenge-card-meta` | font-size | `14px` | `16px` |
| `challenge-card-meta` | line-height | `20px` | `24px` |
| `challenge-card-meta-item` | line-height | `20px` | `21px` |
| `challenge-card-title` | font-size | `24px` | `24.5341px` |
| `challenge-card-title` | line-height | `32px` | `29.4409px` |
| `cta-btn-primary` | font-weight | `500` | `400` |
| `cta-btn-primary` | line-height | `28px` | `27px` |
| `cta-btn-primary` | white-space | `nowrap` | `normal` |
| `cta-btn-secondary` | font-weight | `500` | `400` |
| `cta-btn-secondary` | line-height | `28px` | `27px` |
| `cta-btn-secondary` | white-space | `nowrap` | `normal` |
| `cta-h2` | font-size | `30px` | `30.9px` |
| `cta-h2` | font-weight | `600` | `700` |
| `cta-h2` | line-height | `36px` | `30.9px` |
| `cta-text` | line-height | `28px` | `27px` |
| `featured-all-link` | white-space | `normal` | `nowrap` |
| `featured-card-author-name` | line-height | `20px` | `21px` |
| `featured-card-excerpt` | line-height | `22.75px` | `23.8px` |
| `featured-card-pill` | line-height | `16px` | `18px` |
| `featured-card-stat` | line-height | `20px` | `21px` |
| `featured-card-stats` | line-height | `20px` | `21px` |
| `featured-card-time` | line-height | `16px` | `18px` |
| `featured-card-title` | line-height | `28px` | `24px` |
| `featured-eyebrow` | line-height | `20px` | `21px` |
| `featured-h2` | font-size | `30px` | `24.5341px` |
| `featured-h2` | line-height | `36px` | `29.4409px` |
| `featured-lead-author-name` | line-height | `20px` | `21px` |
| `featured-lead-excerpt` | line-height | `26px` | `27.2px` |
| `featured-lead-pill` | line-height | `16px` | `18px` |
| `featured-lead-stat` | line-height | `20px` | `21px` |
| `featured-lead-stats` | line-height | `20px` | `21px` |
| `featured-lead-time` | line-height | `16px` | `18px` |
| `featured-lead-title` | font-size | `24px` | `20.2674px` |
| `featured-lead-title` | line-height | `32px` | `24.3209px` |
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
| `hero-btn-outline` | font-weight | `500` | `400` |
| `hero-btn-primary` | font-weight | `500` | `400` |
| `hero-btn-primary` | text-align | `start` | `center` |
| `hero-h1` | line-height | `37.5px` | `36px` |
| `hero-paragraph` | line-height | `26px` | `24px` |
| `hero-spotlight-badge` | line-height | `16px` | `18px` |
| `hero-spotlight-btn` | line-height | `20px` | `21px` |
| `hero-spotlight-btn` | white-space | `nowrap` | `normal` |
| `hero-spotlight-date` | line-height | `16px` | `18px` |
| `hero-spotlight-desc` | line-height | `22.75px` | `23.8px` |
| `hero-spotlight-title` | font-size | `20px` | `20.2674px` |
| `hero-spotlight-title` | line-height | `27.5px` | `24.3209px` |
| `sponsors-btn` | font-family | `Inter, system-ui, sans-serif` | `Lora, Georgia, serif` |
| `sponsors-btn` | white-space | `nowrap` | `normal` |
| `sponsors-chip-bronze` | font-family | `Inter, system-ui, sans-serif` | `Lora, Georgia, serif` |
| `sponsors-chip-bronze` | font-weight | `400` | `500` |
| `sponsors-chip-gold` | font-family | `Inter, system-ui, sans-serif` | `Lora, Georgia, serif` |
| `sponsors-chip-gold` | font-size | `16px` | `18px` |
| `sponsors-chip-gold` | font-weight | `400` | `500` |
| `sponsors-chip-gold` | line-height | `24px` | `27px` |
| `sponsors-chip-gold-name` | line-height | `28px` | `27px` |
| `sponsors-chip-silver` | font-family | `Inter, system-ui, sans-serif` | `Lora, Georgia, serif` |
| `sponsors-chip-silver` | font-weight | `400` | `500` |
| `sponsors-eyebrow` | line-height | `20px` | `21px` |
| `sponsors-h2` | font-size | `24px` | `20.2674px` |
| `sponsors-h2` | line-height | `32px` | `24.3209px` |
| `sponsors-intro` | line-height | `24px` | `27.2px` |
| `winner-author-wins` | line-height | `20px` | `21px` |
| `winner-btn` | font-family | `Inter, system-ui, sans-serif` | `Lora, Georgia, serif` |
| `winner-btn` | line-height | `20px` | `21px` |
| `winner-btn` | text-align | `center` | `start` |
| `winner-btn` | white-space | `nowrap` | `normal` |
| `winner-eyebrow` | font-family | `Inter, system-ui, sans-serif` | `Lora, Georgia, serif` |
| `winner-eyebrow` | font-size | `16px` | `14px` |
| `winner-eyebrow` | font-weight | `400` | `600` |
| `winner-eyebrow` | line-height | `24px` | `21px` |
| `winner-eyebrow-icon` | font-family | `Inter, system-ui, sans-serif` | `Lora, Georgia, serif` |
| `winner-eyebrow-icon` | font-size | `16px` | `14px` |
| `winner-eyebrow-icon` | font-weight | `400` | `600` |
| `winner-eyebrow-icon` | line-height | `24px` | `21px` |
| `winner-eyebrow-text` | font-family | `Inter, system-ui, sans-serif` | `Lora, Georgia, serif` |
| `winner-eyebrow-text` | font-weight | `500` | `600` |
| `winner-eyebrow-text` | line-height | `20px` | `21px` |
| `winner-quote` | line-height | `26px` | `27.2px` |
| `winner-title` | font-size | `24px` | `20.2674px` |
| `winner-title` | line-height | `32px` | `24.3209px` |

### micro — 42

| audit-id | property | reference | INK |
|---|---|---|---|
| `challenge-card` | box-shadow | `rgba(0, 0, 0, 0) 0px 0px 0px 0px, rgba(0, 0, 0, 0) 0px 0p…` | `rgba(24, 29, 37, 0.08) 0px 4px 20px -4px` |
| `challenge-card-btn` | box-shadow | `rgba(0, 0, 0, 0) 0px 0px 0px 0px, rgba(0, 0, 0, 0) 0px 0p…` | `rgba(24, 29, 37, 0.08) 0px 4px 20px -4px` |
| `challenge-card-corner` | z-index | `auto` | `0` |
| `cta-band` | transform | `none` | `matrix(1, 0, 0, 1, 0, 0)` |
| `cta-btn-secondary` | border-bottom-width | `1px` | `2px` |
| `cta-btn-secondary` | border-left-width | `1px` | `2px` |
| `cta-btn-secondary` | border-right-width | `1px` | `2px` |
| `cta-btn-secondary` | border-top-width | `1px` | `2px` |
| `cta-circle-left` | z-index | `auto` | `0` |
| `cta-circle-right` | z-index | `auto` | `0` |
| `cta-content` | z-index | `auto` | `1` |
| `featured-card` | box-shadow | `none` | `rgba(24, 29, 37, 0.06) 0px 2px 12px -2px` |
| `featured-eyebrow` | letter-spacing | `0.7px` | `1.12px` |
| `featured-lead` | box-shadow | `none` | `rgba(24, 29, 37, 0.06) 0px 2px 12px -2px` |
| `header` | z-index | `50` | `auto` |
| `header-btn-join` | box-shadow | `rgba(0, 0, 0, 0) 0px 0px 0px 0px, rgba(0, 0, 0, 0) 0px 0p…` | `rgba(24, 29, 37, 0.08) 0px 4px 20px -4px` |
| `header-nav-browse` | z-index | `auto` | `1` |
| `header-nav-challenges` | z-index | `auto` | `1` |
| `header-nav-community` | z-index | `auto` | `1` |
| `header-nav-home` | z-index | `auto` | `1` |
| `header-nav-library` | z-index | `auto` | `1` |
| `hero-aside` | transform | `matrix(1, 0, 0, 1, 0, 0)` | `none` |
| `hero-btn-primary` | box-shadow | `rgba(0, 0, 0, 0) 0px 0px 0px 0px, rgba(0, 0, 0, 0) 0px 0p…` | `rgba(24, 29, 37, 0.08) 0px 4px 20px -4px` |
| `hero-content` | transform | `matrix(1, 0, 0, 1, 0, 0)` | `none` |
| `hero-grid` | transform | `none` | `matrix(1, 0, 0, 1, 0, 0)` |
| `hero-grid` | z-index | `auto` | `1` |
| `hero-spotlight` | box-shadow | `none` | `rgba(24, 29, 37, 0.06) 0px 2px 12px -2px` |
| `hero-spotlight-badge` | letter-spacing | `0.3px` | `0.6px` |
| `hero-spotlight-body` | z-index | `10` | `1` |
| `hero-spotlight-btn` | box-shadow | `rgba(0, 0, 0, 0) 0px 0px 0px 0px, rgba(0, 0, 0, 0) 0px 0p…` | `rgba(24, 29, 37, 0.08) 0px 4px 20px -4px` |
| `sponsors` | transform | `none` | `matrix(1, 0, 0, 1, 0, 0)` |
| `sponsors-chip-bronze` | border-bottom-width | `0px` | `1px` |
| `sponsors-chip-bronze` | border-left-width | `0px` | `1px` |
| `sponsors-chip-bronze` | border-right-width | `0px` | `1px` |
| `sponsors-chip-bronze` | border-top-width | `0px` | `1px` |
| `sponsors-eyebrow` | letter-spacing | `0.7px` | `1.12px` |
| `winner-author` | z-index | `auto` | `1` |
| `winner-btn` | z-index | `auto` | `1` |
| `winner-eyebrow` | z-index | `auto` | `1` |
| `winner-quote` | z-index | `auto` | `1` |
| `winner-title` | z-index | `auto` | `1` |
| `winner-watermark` | z-index | `auto` | `0` |


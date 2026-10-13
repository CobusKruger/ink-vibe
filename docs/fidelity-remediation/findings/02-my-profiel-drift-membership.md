# Computed-style diff — my-profiel @ 1440px

**GENERATED — do not hand-edit.** Regenerate with `tools/fidelity/run.sh diff`.

- Reference: `tmp/fidelity-my-profiel/nochrome/lovable/my-profiel@1440-membership.ndjson` — http://localhost:4182/profile (131 nodes)
- INK: `tmp/fidelity-my-profiel/nochrome/ink/my-profiel@1440-auth-membership.ndjson` — https://nuwe-ink.local/my-profiel/ (207 nodes)
- Paired on `data-audit-id`: **37**
- Reference-only ids: 17 (profiel-identity-camera-btn, profiel-tab-icon, profiel-tab-badge, profiel-tab-badge-unread, profiel-membership-grid, profiel-membership-status-card, profiel-membership-status-label, profiel-membership-status-title, profiel-membership-status-separator, profiel-membership-status-list, profiel-membership-status-row, profiel-membership-status-dt, profiel-membership-status-dd, profiel-membership-status-dot, profiel-membership-plan-period, profiel-membership-renew-footer, profiel-membership-renew-note)
- INK-only ids: 58 (profiel-identity-person, profiel-identity-gradering, profiel-identity-gradering-badge, profiel-identity-wins-needed, profiel-identity-buttons, profiel-edit-overlay, profiel-edit-backdrop, profiel-edit-card, profiel-edit-title, profiel-edit-avatar-row, profiel-edit-avatar, profiel-edit-form, profiel-edit-field, profiel-edit-label, profiel-edit-input, profiel-edit-textarea, profiel-edit-actions, profiel-edit-cancel-btn, profiel-edit-save-btn, profiel-tabs-wide, profiel-overview-grid, profiel-about-col, profiel-about-card, profiel-about-head, profiel-about-title, profiel-about-edit-btn, profiel-about-bio, profiel-glance-col, profiel-glance-card, profiel-glance-title, profiel-glance-stats, profiel-glance-stat, profiel-glance-stat-label, profiel-glance-stat-value, profiel-recent-card, profiel-recent-title, profiel-posts-head, profiel-posts-title, profiel-posts-new-btn, profiel-posts-empty, profiel-reading-title, profiel-reading-empty, profiel-reading-empty-title, profiel-reading-empty-body, profiel-following-head-text, profiel-following-title, profiel-following-sub, profiel-following-empty, profiel-following-empty-title, profiel-following-empty-body, profiel-following-empty-btn, profiel-activity-title, profiel-activity-empty, profiel-notif-head, profiel-notif-title, profiel-notif-mark-btn, profiel-notif-empty, profiel-membership-renew-wide)

## Systemic — architectural, not per-element

Each of these differs identically on (nearly) every paired node, so it is one
decision rather than many findings. Resolve at the layer, not on the page.

| property | reference | INK | nodes |
|---|---|---|---|
| border-top-style | `solid` | `none` | 34/37 |

## 383 per-element deltas across 37 elements

### box — 235

| audit-id | property | reference | INK |
|---|---|---|---|
| `profiel-activity` | margin-top | `8px` | `32px` |
| `profiel-activity` | position | `static` | `relative` |
| `profiel-following` | margin-top | `8px` | `32px` |
| `profiel-following` | position | `static` | `relative` |
| `profiel-identity` | height | `225px` | `201px` |
| `profiel-identity` | padding-bottom | `0px` | `40px` |
| `profiel-identity` | padding-left | `0px` | `24px` |
| `profiel-identity` | padding-right | `0px` | `24px` |
| `profiel-identity` | padding-top | `0px` | `40px` |
| `profiel-identity` | position | `static` | `relative` |
| `profiel-identity-actions` | align-items | `normal` | `center` |
| `profiel-identity-actions` | width | `446.25px` | `453.094px` |
| `profiel-identity-avatar` | box-sizing | `border-box` | `content-box` |
| `profiel-identity-avatar` | display | `flex` | `block` |
| `profiel-identity-avatar` | overflow | `hidden` | `clip` |
| `profiel-identity-avatar` | position | `relative` | `static` |
| `profiel-identity-avatar-wrap` | box-sizing | `border-box` | `content-box` |
| `profiel-identity-avatar-wrap` | height | `112px` | `120px` |
| `profiel-identity-avatar-wrap` | position | `relative` | `static` |
| `profiel-identity-avatar-wrap` | width | `112px` | `120px` |
| `profiel-identity-container` | height | `224px` | `120px` |
| `profiel-identity-container` | margin-left | `20px` | `12px` |
| `profiel-identity-container` | margin-right | `20px` | `12px` |
| `profiel-identity-container` | max-width | `1400px` | `1368px` |
| `profiel-identity-container` | padding-bottom | `56px` | `0px` |
| `profiel-identity-container` | padding-left | `16px` | `0px` |
| `profiel-identity-container` | padding-right | `16px` | `0px` |
| `profiel-identity-container` | padding-top | `56px` | `0px` |
| `profiel-identity-container` | width | `1400px` | `1368px` |
| `profiel-identity-edit-btn` | justify-content | `center` | `normal` |
| `profiel-identity-edit-btn` | padding-bottom | `8px` | `0px` |
| `profiel-identity-edit-btn` | padding-top | `8px` | `0px` |
| `profiel-identity-edit-btn` | width | `133.641px` | `122.469px` |
| `profiel-identity-eyebrow` | box-sizing | `border-box` | `content-box` |
| `profiel-identity-eyebrow` | height | `16px` | `18px` |
| `profiel-identity-eyebrow` | max-width | `none` | `768px` |
| `profiel-identity-eyebrow` | width | `761.75px` | `337.031px` |
| `profiel-identity-name` | box-sizing | `border-box` | `content-box` |
| `profiel-identity-name` | height | `40px` | `28.7969px` |
| `profiel-identity-name` | max-width | `none` | `768px` |
| `profiel-identity-name` | width | `761.75px` | `337.031px` |
| `profiel-identity-new-btn` | column-gap | `8px` | `normal` |
| `profiel-identity-new-btn` | display | `flex` | `inline-flex` |
| `profiel-identity-new-btn` | gap | `8px` | `normal` |
| `profiel-identity-new-btn` | justify-content | `center` | `normal` |
| `profiel-identity-new-btn` | min-height | `auto` | `0px` |
| `profiel-identity-new-btn` | padding-bottom | `8px` | `0px` |
| `profiel-identity-new-btn` | padding-top | `8px` | `0px` |
| `profiel-identity-new-btn` | row-gap | `8px` | `normal` |
| `profiel-identity-new-btn` | width | `117.594px` | `117.703px` |
| `profiel-identity-public-btn` | column-gap | `8px` | `normal` |
| `profiel-identity-public-btn` | display | `flex` | `inline-flex` |
| `profiel-identity-public-btn` | gap | `8px` | `normal` |
| `profiel-identity-public-btn` | justify-content | `center` | `normal` |
| `profiel-identity-public-btn` | min-height | `auto` | `0px` |
| `profiel-identity-public-btn` | padding-bottom | `8px` | `0px` |
| `profiel-identity-public-btn` | padding-top | `8px` | `0px` |
| `profiel-identity-public-btn` | row-gap | `8px` | `normal` |
| `profiel-identity-public-btn` | width | `171.016px` | `176.922px` |
| `profiel-identity-row` | flex-wrap | `nowrap` | `wrap` |
| `profiel-identity-row` | height | `112px` | `120px` |
| `profiel-identity-row` | justify-content | `normal` | `space-between` |
| `profiel-identity-tagline` | box-sizing | `border-box` | `content-box` |
| `profiel-identity-tagline` | display | `block` | `none` |
| `profiel-identity-tagline` | height | `24px` | `auto` |
| `profiel-identity-tagline` | margin-left | `0px` | `auto` |
| `profiel-identity-tagline` | margin-right | `0px` | `auto` |
| `profiel-identity-tagline` | max-width | `none` | `768px` |
| `profiel-identity-tagline` | width | `761.75px` | `auto` |
| `profiel-identity-text` | height | `88px` | `79.7969px` |
| `profiel-identity-text` | position | `static` | `relative` |
| `profiel-identity-text` | width | `761.75px` | `337.031px` |
| `profiel-membership` | height | `318px` | `384.797px` |
| `profiel-membership` | margin-top | `8px` | `32px` |
| `profiel-membership` | position | `static` | `relative` |
| `profiel-membership-plan` | height | `128px` | `188px` |
| `profiel-membership-plan` | min-height | `auto` | `0px` |
| `profiel-membership-plan` | padding-bottom | `16px` | `24px` |
| `profiel-membership-plan` | padding-left | `16px` | `24px` |
| `profiel-membership-plan` | padding-right | `16px` | `24px` |
| `profiel-membership-plan` | padding-top | `16px` | `24px` |
| `profiel-membership-plan` | position | `static` | `relative` |
| `profiel-membership-plan` | width | `275.781px` | `240px` |
| `profiel-membership-plan-label` | box-sizing | `border-box` | `content-box` |
| `profiel-membership-plan-label` | height | `28px` | `24px` |
| `profiel-membership-plan-label` | max-width | `none` | `768px` |
| `profiel-membership-plan-label` | min-height | `auto` | `0px` |
| `profiel-membership-plan-label` | width | `69.75px` | `188px` |
| `profiel-membership-plan-price` | box-sizing | `border-box` | `content-box` |
| `profiel-membership-plan-price` | height | `32px` | `36px` |
| `profiel-membership-plan-price` | margin-top | `8px` | `16px` |
| `profiel-membership-plan-price` | max-width | `none` | `768px` |
| `profiel-membership-plan-price` | width | `239.781px` | `188px` |
| `profiel-membership-plans` | column-gap | `12px` | `24px` |
| `profiel-membership-plans` | display | `grid` | `flex` |
| `profiel-membership-plans` | gap | `12px` | `24px` |
| `profiel-membership-plans` | grid-template-columns | `275.781px 275.781px 275.781px` | `none` |
| `profiel-membership-plans` | height | `128px` | `188px` |
| `profiel-membership-plans` | margin-left | `0px` | `276px` |
| `profiel-membership-plans` | margin-right | `0px` | `276px` |
| `profiel-membership-plans` | margin-top | `0px` | `24px` |
| `profiel-membership-plans` | max-width | `none` | `768px` |
| `profiel-membership-plans` | row-gap | `12px` | `24px` |
| `profiel-membership-plans` | width | `851.344px` | `768px` |
| `profiel-membership-renew` | height | `318px` | `384.797px` |
| `profiel-membership-renew` | min-height | `auto` | `0px` |
| `profiel-membership-renew` | padding-bottom | `0px` | `48px` |
| `profiel-membership-renew` | padding-left | `0px` | `24px` |
| `profiel-membership-renew` | padding-right | `0px` | `24px` |
| `profiel-membership-renew` | padding-top | `0px` | `48px` |
| `profiel-membership-renew` | position | `static` | `relative` |
| `profiel-membership-renew` | width | `901.344px` | `1368px` |
| `profiel-membership-renew-btn` | column-gap | `8px` | `normal` |
| `profiel-membership-renew-btn` | display | `flex` | `inline-flex` |
| `profiel-membership-renew-btn` | gap | `8px` | `normal` |
| `profiel-membership-renew-btn` | min-height | `auto` | `44px` |
| `profiel-membership-renew-btn` | padding-bottom | `0px` | `12px` |
| `profiel-membership-renew-btn` | padding-left | `32px` | `8px` |
| `profiel-membership-renew-btn` | padding-right | `32px` | `8px` |
| `profiel-membership-renew-btn` | padding-top | `0px` | `12px` |
| `profiel-membership-renew-btn` | row-gap | `8px` | `normal` |
| `profiel-membership-renew-btn` | width | `220.422px` | `188px` |
| `profiel-membership-renew-desc` | box-sizing | `border-box` | `content-box` |
| `profiel-membership-renew-desc` | height | `20px` | `24px` |
| `profiel-membership-renew-desc` | margin-bottom | `20px` | `0px` |
| `profiel-membership-renew-desc` | margin-left | `0px` | `276px` |
| `profiel-membership-renew-desc` | margin-right | `0px` | `276px` |
| `profiel-membership-renew-desc` | margin-top | `0px` | `24px` |
| `profiel-membership-renew-desc` | max-width | `none` | `768px` |
| `profiel-membership-renew-desc` | width | `851.344px` | `768px` |
| `profiel-membership-renew-title` | box-sizing | `border-box` | `content-box` |
| `profiel-membership-renew-title` | height | `28px` | `28.7969px` |
| `profiel-membership-renew-title` | margin-bottom | `4px` | `0px` |
| `profiel-membership-renew-title` | margin-left | `0px` | `276px` |
| `profiel-membership-renew-title` | margin-right | `0px` | `276px` |
| `profiel-membership-renew-title` | max-width | `none` | `768px` |
| `profiel-membership-renew-title` | width | `851.344px` | `768px` |
| `profiel-notifications` | margin-top | `8px` | `32px` |
| `profiel-notifications` | position | `static` | `relative` |
| `profiel-overview` | margin-top | `8px` | `32px` |
| `profiel-overview` | position | `static` | `relative` |
| `profiel-posts` | margin-top | `8px` | `32px` |
| `profiel-posts` | position | `static` | `relative` |
| `profiel-reading` | margin-top | `8px` | `32px` |
| `profiel-reading` | position | `static` | `relative` |
| `profiel-tab-activity` | column-gap | `normal` | `8px` |
| `profiel-tab-activity` | gap | `normal` | `8px` |
| `profiel-tab-activity` | height | `32px` | `51px` |
| `profiel-tab-activity` | justify-content | `center` | `normal` |
| `profiel-tab-activity` | padding-bottom | `6px` | `16px` |
| `profiel-tab-activity` | padding-left | `12px` | `20px` |
| `profiel-tab-activity` | padding-right | `12px` | `20px` |
| `profiel-tab-activity` | padding-top | `6px` | `16px` |
| `profiel-tab-activity` | row-gap | `normal` | `8px` |
| `profiel-tab-activity` | width | `96.8594px` | `102.312px` |
| `profiel-tab-following` | column-gap | `normal` | `8px` |
| `profiel-tab-following` | gap | `normal` | `8px` |
| `profiel-tab-following` | height | `34px` | `51px` |
| `profiel-tab-following` | justify-content | `center` | `normal` |
| `profiel-tab-following` | padding-bottom | `6px` | `16px` |
| `profiel-tab-following` | padding-left | `12px` | `20px` |
| `profiel-tab-following` | padding-right | `12px` | `20px` |
| `profiel-tab-following` | padding-top | `6px` | `16px` |
| `profiel-tab-following` | row-gap | `normal` | `8px` |
| `profiel-tab-following` | width | `147.719px` | `117.734px` |
| `profiel-tab-membership` | column-gap | `normal` | `8px` |
| `profiel-tab-membership` | gap | `normal` | `8px` |
| `profiel-tab-membership` | height | `32px` | `51px` |
| `profiel-tab-membership` | justify-content | `center` | `normal` |
| `profiel-tab-membership` | padding-bottom | `6px` | `16px` |
| `profiel-tab-membership` | padding-left | `12px` | `20px` |
| `profiel-tab-membership` | padding-right | `12px` | `20px` |
| `profiel-tab-membership` | padding-top | `6px` | `16px` |
| `profiel-tab-membership` | row-gap | `normal` | `8px` |
| `profiel-tab-membership` | width | `129.875px` | `125.047px` |
| `profiel-tab-notifications` | column-gap | `normal` | `8px` |
| `profiel-tab-notifications` | gap | `normal` | `8px` |
| `profiel-tab-notifications` | height | `32px` | `51px` |
| `profiel-tab-notifications` | justify-content | `center` | `normal` |
| `profiel-tab-notifications` | padding-bottom | `6px` | `16px` |
| `profiel-tab-notifications` | padding-left | `12px` | `20px` |
| `profiel-tab-notifications` | padding-right | `12px` | `20px` |
| `profiel-tab-notifications` | padding-top | `6px` | `16px` |
| `profiel-tab-notifications` | row-gap | `normal` | `8px` |
| `profiel-tab-notifications` | width | `166.578px` | `141.625px` |
| `profiel-tab-overview` | column-gap | `normal` | `8px` |
| `profiel-tab-overview` | gap | `normal` | `8px` |
| `profiel-tab-overview` | height | `32px` | `51px` |
| `profiel-tab-overview` | justify-content | `center` | `normal` |
| `profiel-tab-overview` | padding-bottom | `6px` | `16px` |
| `profiel-tab-overview` | padding-left | `12px` | `20px` |
| `profiel-tab-overview` | padding-right | `12px` | `20px` |
| `profiel-tab-overview` | padding-top | `6px` | `16px` |
| `profiel-tab-overview` | row-gap | `normal` | `8px` |
| `profiel-tab-overview` | width | `87.5312px` | `84.3594px` |
| `profiel-tab-posts` | column-gap | `normal` | `8px` |
| `profiel-tab-posts` | gap | `normal` | `8px` |
| `profiel-tab-posts` | height | `32px` | `51px` |
| `profiel-tab-posts` | justify-content | `center` | `normal` |
| `profiel-tab-posts` | padding-bottom | `6px` | `16px` |
| `profiel-tab-posts` | padding-left | `12px` | `20px` |
| `profiel-tab-posts` | padding-right | `12px` | `20px` |
| `profiel-tab-posts` | padding-top | `6px` | `16px` |
| `profiel-tab-posts` | row-gap | `normal` | `8px` |
| `profiel-tab-posts` | width | `83.0781px` | `94.7031px` |
| `profiel-tab-reading` | column-gap | `normal` | `8px` |
| `profiel-tab-reading` | gap | `normal` | `8px` |
| `profiel-tab-reading` | height | `34px` | `51px` |
| `profiel-tab-reading` | justify-content | `center` | `normal` |
| `profiel-tab-reading` | padding-bottom | `6px` | `16px` |
| `profiel-tab-reading` | padding-left | `12px` | `20px` |
| `profiel-tab-reading` | padding-right | `12px` | `20px` |
| `profiel-tab-reading` | padding-top | `6px` | `16px` |
| `profiel-tab-reading` | row-gap | `normal` | `8px` |
| `profiel-tab-reading` | width | `161.031px` | `91.125px` |
| `profiel-tablist` | column-gap | `normal` | `4px` |
| `profiel-tablist` | gap | `normal` | `4px` |
| `profiel-tablist` | height | `42px` | `52px` |
| `profiel-tablist` | justify-content | `center` | `normal` |
| `profiel-tablist` | padding-bottom | `4px` | `0px` |
| `profiel-tablist` | padding-left | `4px` | `0px` |
| `profiel-tablist` | padding-right | `4px` | `0px` |
| `profiel-tablist` | padding-top | `4px` | `0px` |
| `profiel-tablist` | row-gap | `normal` | `4px` |
| `profiel-tabs-section` | height | `472px` | `564.797px` |
| `profiel-tabs-section` | margin-left | `20px` | `0px` |
| `profiel-tabs-section` | margin-right | `20px` | `0px` |
| `profiel-tabs-section` | margin-top | `0px` | `24px` |
| `profiel-tabs-section` | max-width | `1400px` | `none` |
| `profiel-tabs-section` | padding-bottom | `40px` | `48px` |
| `profiel-tabs-section` | padding-left | `16px` | `24px` |
| `profiel-tabs-section` | padding-right | `16px` | `24px` |
| `profiel-tabs-section` | padding-top | `40px` | `48px` |
| `profiel-tabs-section` | position | `static` | `relative` |
| `profiel-tabs-section` | width | `1400px` | `1440px` |

### colour — 45

| audit-id | property | reference | INK |
|---|---|---|---|
| `profiel-activity` | color | `rgb(24, 29, 37)` | `rgb(26, 29, 33)` |
| `profiel-following` | color | `rgb(24, 29, 37)` | `rgb(26, 29, 33)` |
| `profiel-identity` | background-color | `rgba(250, 248, 245, 0.4)` | `rgba(248, 246, 242, 0.4)` |
| `profiel-identity` | color | `rgb(24, 29, 37)` | `rgb(26, 29, 33)` |
| `profiel-identity-actions` | color | `rgb(24, 29, 37)` | `rgb(26, 29, 33)` |
| `profiel-identity-avatar` | border-top-color | `rgb(250, 248, 245)` | `rgb(253, 252, 250)` |
| `profiel-identity-avatar` | color | `rgb(24, 29, 37)` | `rgb(26, 29, 33)` |
| `profiel-identity-avatar-wrap` | color | `rgb(24, 29, 37)` | `rgb(26, 29, 33)` |
| `profiel-identity-container` | color | `rgb(24, 29, 37)` | `rgb(26, 29, 33)` |
| `profiel-identity-eyebrow` | color | `rgb(103, 111, 126)` | `rgb(107, 114, 128)` |
| `profiel-identity-name` | color | `rgb(24, 29, 37)` | `rgb(26, 29, 33)` |
| `profiel-identity-new-btn` | color | `rgb(255, 255, 255)` | `rgb(253, 252, 250)` |
| `profiel-identity-public-btn` | background-color | `rgba(240, 235, 230, 0.5)` | `rgba(237, 233, 224, 0.5)` |
| `profiel-identity-public-btn` | color | `rgb(24, 29, 37)` | `rgb(26, 29, 33)` |
| `profiel-identity-row` | color | `rgb(24, 29, 37)` | `rgb(26, 29, 33)` |
| `profiel-identity-tagline` | color | `rgb(103, 111, 126)` | `rgb(107, 114, 128)` |
| `profiel-identity-text` | color | `rgb(24, 29, 37)` | `rgb(26, 29, 33)` |
| `profiel-membership` | color | `rgb(24, 29, 37)` | `rgb(26, 29, 33)` |
| `profiel-membership-plan` | background-color | `rgba(0, 0, 0, 0)` | `rgb(253, 252, 250)` |
| `profiel-membership-plan` | border-top-color | `rgb(231, 225, 218)` | `rgb(232, 228, 220)` |
| `profiel-membership-plan` | color | `rgb(24, 29, 37)` | `rgb(26, 29, 33)` |
| `profiel-membership-plan-label` | color | `rgb(24, 29, 37)` | `rgb(26, 29, 33)` |
| `profiel-membership-plan-price` | color | `rgb(24, 29, 37)` | `rgb(26, 29, 33)` |
| `profiel-membership-plans` | color | `rgb(24, 29, 37)` | `rgb(26, 29, 33)` |
| `profiel-membership-renew` | background-color | `rgb(253, 253, 252)` | `rgba(0, 0, 0, 0)` |
| `profiel-membership-renew` | border-top-color | `rgb(231, 225, 218)` | `rgb(26, 29, 33)` |
| `profiel-membership-renew` | color | `rgb(24, 29, 37)` | `rgb(26, 29, 33)` |
| `profiel-membership-renew-btn` | color | `rgb(255, 255, 255)` | `rgb(253, 252, 250)` |
| `profiel-membership-renew-desc` | color | `rgb(103, 111, 126)` | `rgb(107, 114, 128)` |
| `profiel-membership-renew-title` | color | `rgb(24, 29, 37)` | `rgb(26, 29, 33)` |
| `profiel-notifications` | color | `rgb(24, 29, 37)` | `rgb(26, 29, 33)` |
| `profiel-overview` | color | `rgb(24, 29, 37)` | `rgb(26, 29, 33)` |
| `profiel-posts` | color | `rgb(24, 29, 37)` | `rgb(26, 29, 33)` |
| `profiel-reading` | color | `rgb(24, 29, 37)` | `rgb(26, 29, 33)` |
| `profiel-tab-activity` | color | `rgb(103, 111, 126)` | `rgb(107, 114, 128)` |
| `profiel-tab-following` | color | `rgb(103, 111, 126)` | `rgb(107, 114, 128)` |
| `profiel-tab-membership` | background-color | `rgb(250, 248, 245)` | `rgba(0, 0, 0, 0)` |
| `profiel-tab-membership` | color | `rgb(24, 29, 37)` | `rgb(26, 29, 33)` |
| `profiel-tab-notifications` | color | `rgb(103, 111, 126)` | `rgb(107, 114, 128)` |
| `profiel-tab-overview` | color | `rgb(103, 111, 126)` | `rgb(107, 114, 128)` |
| `profiel-tab-posts` | color | `rgb(103, 111, 126)` | `rgb(107, 114, 128)` |
| `profiel-tab-reading` | color | `rgb(103, 111, 126)` | `rgb(107, 114, 128)` |
| `profiel-tablist` | background-color | `rgba(250, 248, 245, 0.6)` | `rgba(0, 0, 0, 0)` |
| `profiel-tablist` | color | `rgb(103, 111, 126)` | `rgb(26, 29, 33)` |
| `profiel-tabs-section` | color | `rgb(24, 29, 37)` | `rgb(26, 29, 33)` |

### type — 44

| audit-id | property | reference | INK |
|---|---|---|---|
| `profiel-identity-edit-btn` | line-height | `20px` | `21px` |
| `profiel-identity-edit-btn` | white-space | `nowrap` | `normal` |
| `profiel-identity-eyebrow` | line-height | `16px` | `18px` |
| `profiel-identity-name` | font-size | `36px` | `24px` |
| `profiel-identity-name` | font-weight | `400` | `700` |
| `profiel-identity-name` | line-height | `40px` | `28.8px` |
| `profiel-identity-new-btn` | font-weight | `500` | `400` |
| `profiel-identity-new-btn` | line-height | `20px` | `21px` |
| `profiel-identity-new-btn` | text-align | `start` | `center` |
| `profiel-identity-new-btn` | white-space | `nowrap` | `normal` |
| `profiel-identity-public-btn` | line-height | `20px` | `21px` |
| `profiel-identity-public-btn` | text-align | `start` | `center` |
| `profiel-identity-public-btn` | white-space | `nowrap` | `normal` |
| `profiel-membership-plan` | text-align | `left` | `start` |
| `profiel-membership-plan-label` | font-size | `18px` | `20px` |
| `profiel-membership-plan-label` | font-weight | `400` | `700` |
| `profiel-membership-plan-label` | line-height | `28px` | `24px` |
| `profiel-membership-plan-label` | text-align | `left` | `start` |
| `profiel-membership-plan-price` | font-family | `Lora, Georgia, serif` | `Inter, system-ui, sans-serif` |
| `profiel-membership-plan-price` | font-weight | `400` | `700` |
| `profiel-membership-plan-price` | line-height | `32px` | `36px` |
| `profiel-membership-plan-price` | text-align | `left` | `start` |
| `profiel-membership-renew-btn` | font-size | `16px` | `14px` |
| `profiel-membership-renew-btn` | line-height | `24px` | `18.2px` |
| `profiel-membership-renew-btn` | white-space | `nowrap` | `normal` |
| `profiel-membership-renew-desc` | font-size | `14px` | `16px` |
| `profiel-membership-renew-desc` | line-height | `20px` | `24px` |
| `profiel-membership-renew-title` | font-size | `20px` | `24px` |
| `profiel-membership-renew-title` | font-weight | `400` | `700` |
| `profiel-membership-renew-title` | line-height | `28px` | `28.8px` |
| `profiel-tab-activity` | line-height | `20px` | `normal` |
| `profiel-tab-activity` | white-space | `nowrap` | `normal` |
| `profiel-tab-following` | line-height | `20px` | `normal` |
| `profiel-tab-following` | white-space | `nowrap` | `normal` |
| `profiel-tab-membership` | line-height | `20px` | `normal` |
| `profiel-tab-membership` | white-space | `nowrap` | `normal` |
| `profiel-tab-notifications` | line-height | `20px` | `normal` |
| `profiel-tab-notifications` | white-space | `nowrap` | `normal` |
| `profiel-tab-overview` | line-height | `20px` | `normal` |
| `profiel-tab-overview` | white-space | `nowrap` | `normal` |
| `profiel-tab-posts` | line-height | `20px` | `normal` |
| `profiel-tab-posts` | white-space | `nowrap` | `normal` |
| `profiel-tab-reading` | line-height | `20px` | `normal` |
| `profiel-tab-reading` | white-space | `nowrap` | `normal` |

### micro — 59

| audit-id | property | reference | INK |
|---|---|---|---|
| `profiel-identity-avatar` | box-shadow | `rgba(0, 0, 0, 0) 0px 0px 0px 0px, rgba(0, 0, 0, 0) 0px 0p…` | `rgba(24, 29, 37, 0.06) 0px 2px 12px -2px` |
| `profiel-identity-eyebrow` | letter-spacing | `0.6px` | `0.96px` |
| `profiel-identity-new-btn` | box-shadow | `rgba(0, 0, 0, 0) 0px 0px 0px 0px, rgba(0, 0, 0, 0) 0px 0p…` | `rgba(24, 29, 37, 0.08) 0px 4px 20px -4px` |
| `profiel-membership-plan` | box-shadow | `none` | `rgba(24, 29, 37, 0.06) 0px 2px 12px -2px` |
| `profiel-membership-renew` | border-bottom-left-radius | `8px` | `0px` |
| `profiel-membership-renew` | border-bottom-right-radius | `8px` | `0px` |
| `profiel-membership-renew` | border-bottom-width | `1px` | `0px` |
| `profiel-membership-renew` | border-left-width | `1px` | `0px` |
| `profiel-membership-renew` | border-right-width | `1px` | `0px` |
| `profiel-membership-renew` | border-top-left-radius | `8px` | `0px` |
| `profiel-membership-renew` | border-top-right-radius | `8px` | `0px` |
| `profiel-membership-renew` | border-top-width | `1px` | `0px` |
| `profiel-membership-renew` | box-shadow | `rgba(0, 0, 0, 0) 0px 0px 0px 0px, rgba(0, 0, 0, 0) 0px 0p…` | `none` |
| `profiel-membership-renew-btn` | border-bottom-left-radius | `6px` | `8px` |
| `profiel-membership-renew-btn` | border-bottom-right-radius | `6px` | `8px` |
| `profiel-membership-renew-btn` | border-top-left-radius | `6px` | `8px` |
| `profiel-membership-renew-btn` | border-top-right-radius | `6px` | `8px` |
| `profiel-membership-renew-btn` | box-shadow | `rgba(0, 0, 0, 0) 0px 0px 0px 0px, rgba(0, 0, 0, 0) 0px 0p…` | `none` |
| `profiel-tab-activity` | border-bottom-left-radius | `4px` | `0px` |
| `profiel-tab-activity` | border-bottom-right-radius | `4px` | `0px` |
| `profiel-tab-activity` | border-bottom-width | `0px` | `2px` |
| `profiel-tab-activity` | border-top-left-radius | `4px` | `0px` |
| `profiel-tab-activity` | border-top-right-radius | `4px` | `0px` |
| `profiel-tab-following` | border-bottom-left-radius | `4px` | `0px` |
| `profiel-tab-following` | border-bottom-right-radius | `4px` | `0px` |
| `profiel-tab-following` | border-bottom-width | `0px` | `2px` |
| `profiel-tab-following` | border-top-left-radius | `4px` | `0px` |
| `profiel-tab-following` | border-top-right-radius | `4px` | `0px` |
| `profiel-tab-membership` | border-bottom-left-radius | `4px` | `0px` |
| `profiel-tab-membership` | border-bottom-right-radius | `4px` | `0px` |
| `profiel-tab-membership` | border-bottom-width | `0px` | `2px` |
| `profiel-tab-membership` | border-top-left-radius | `4px` | `0px` |
| `profiel-tab-membership` | border-top-right-radius | `4px` | `0px` |
| `profiel-tab-membership` | box-shadow | `rgba(0, 0, 0, 0) 0px 0px 0px 0px, rgba(0, 0, 0, 0) 0px 0p…` | `none` |
| `profiel-tab-notifications` | border-bottom-left-radius | `4px` | `0px` |
| `profiel-tab-notifications` | border-bottom-right-radius | `4px` | `0px` |
| `profiel-tab-notifications` | border-bottom-width | `0px` | `2px` |
| `profiel-tab-notifications` | border-top-left-radius | `4px` | `0px` |
| `profiel-tab-notifications` | border-top-right-radius | `4px` | `0px` |
| `profiel-tab-overview` | border-bottom-left-radius | `4px` | `0px` |
| `profiel-tab-overview` | border-bottom-right-radius | `4px` | `0px` |
| `profiel-tab-overview` | border-bottom-width | `0px` | `2px` |
| `profiel-tab-overview` | border-top-left-radius | `4px` | `0px` |
| `profiel-tab-overview` | border-top-right-radius | `4px` | `0px` |
| `profiel-tab-posts` | border-bottom-left-radius | `4px` | `0px` |
| `profiel-tab-posts` | border-bottom-right-radius | `4px` | `0px` |
| `profiel-tab-posts` | border-bottom-width | `0px` | `2px` |
| `profiel-tab-posts` | border-top-left-radius | `4px` | `0px` |
| `profiel-tab-posts` | border-top-right-radius | `4px` | `0px` |
| `profiel-tab-reading` | border-bottom-left-radius | `4px` | `0px` |
| `profiel-tab-reading` | border-bottom-right-radius | `4px` | `0px` |
| `profiel-tab-reading` | border-bottom-width | `0px` | `2px` |
| `profiel-tab-reading` | border-top-left-radius | `4px` | `0px` |
| `profiel-tab-reading` | border-top-right-radius | `4px` | `0px` |
| `profiel-tablist` | border-bottom-left-radius | `6px` | `0px` |
| `profiel-tablist` | border-bottom-right-radius | `6px` | `0px` |
| `profiel-tablist` | border-bottom-width | `0px` | `1px` |
| `profiel-tablist` | border-top-left-radius | `6px` | `0px` |
| `profiel-tablist` | border-top-right-radius | `6px` | `0px` |


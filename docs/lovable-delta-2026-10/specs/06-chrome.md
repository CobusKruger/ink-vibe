# 06 Chrome: build spec (E4, E6)

**Status:** planned, awaiting recipe-gap ratification. **Map:** `../maps/chrome.csv`. No values here: every style value comes from captures (R4/R5). **Footer (E5) is deferred and excluded.**

**Files read:** `Header.tsx`, `Library.tsx`, `parts/header.html`, `patterns/header-main.php`, `functions.php` (`ink-header` block style, navigation audit filters), `primitives.css`, `theme.json` `styles.css` (parsed), `Library/Archive.php`, `Training/Hub.php`, `patterns/biblioteek.php`, `patterns/opleiding.php`.

## 1. Section spine

| Lovable | INK | Change |
|---|---|---|
| `Header.tsx:28` bar and row | `parts/header.html` → `patterns/header-main.php`, block style in `functions.php` | re-capture |
| `:40` desktop nav | core/navigation in `header-main.php:71-81` | add Kompetisies; hide below 1024 |
| `:65` desktop actions | `header-main.php:83-136` | hide below 1024 |
| `:112` menu toggle | core overlay toggle today | own button |
| `:126` mobile panel | core fixed overlay today | own inline panel |
| `Library.tsx:235` shelf H2 | `Training/Hub.php:604`, `Library/Archive.php:638` | class swap |
| `Library.tsx:311` closing CTA | **none on either page** | None: removed on purpose (owner, §11) |

## 2. E4: Kompetisies (D1)

- **Add** `<!-- wp:navigation-link {"label":"Kompetisies","url":"/kompetisies/"} /-->` between Uitdagings and Gemeenskap, Lovable's desktop position relative to those two.
- **Order then:** Tuis, Ontdek, Biblioteek, Opleiding, Uitdagings, Kompetisies, Gemeenskap. InkPols stays out (D1). The mobile list keeps Lovable's own order (acceptance, 2026-11-02): Tuis, Ontdek, Uitdagings, Kompetisies, Biblioteek, Opleiding, Gemeenskap, then My profiel when logged in.
- **Audit filter** (`functions.php:501-509`): it is keyed on `/ontdek`, `/opleiding`, `/gemeenskap` without a trailing slash, but the links carry one, so on dev **only `header-nav-home` and `header-nav-challenges` are stamped** (seen in the rendered page). Key on the authored URLs and add `/kompetisies/`, `/biblioteek/`. `header-nav-library` moves to `/biblioteek/`; Opleiding gets the INK-only `header-nav-training`.
- **Docs:** `docs/prod-menu-mapping.md` "Target top-level navigation" and rows 18 and 21; `docs/ink-prod-menu.md` as README §5 lists.

## 3. E4: header style must match Lovable

Bugs, where Lovable wins, except the breakpoint: the owner ruled 1024, not Lovable's md (2026-11-02). All from reading both sides; the capture confirms each.

| # | Lovable | INK today | Fix |
|---|---|---|---|
| H1 | Nav collapses to the toggle below md (`Header.tsx:40,112`) | Collapses below WordPress core's own smaller breakpoint (`functions.php:1693-1697`), so between the two widths it shows the desktop row | Collapse below 1024 (**accepted divergence**, §11) |
| H2 | Desktop actions hidden below md (`:65`) | No rule hides them (`header-main.php:100,126`), so they sit in the row at 390 | Hide below 1024 (**accepted divergence**, §11) |
| H3 | Menu opens as an inline panel under the row, pushing the page down (`:126-155`) | Core's fixed overlay, restyled (`functions.php:1725-1732`) | Own panel, §4 |
| H4 | Panel holds the auth buttons (`:137-152`) | Overlay holds links only | Two equal-width buttons in the panel |
| H5 | Panel links are foreground, not muted (`:129-136`) | Overlay uses `nav-link` colours | RG-CH-01 |

**Why 1024 (measured 2026-11-01, md breakpoint, 8 links logged in):** the row wrapped. Logged out: 136px at 768 and 820, 88px at 900 and 960, 64px from 1024. Logged in: 192px at 768 and 820, 136px at 900, 88px at 960 and 1024, 64px from 1100. Lovable stays 64px, but its row overflows the page to 962px wide below 962.

**Owner ruling (2026-11-02):** My profiel comes off the desktop row, because the avatar menu has it, and the header collapses below 1024. It stays in the mobile panel: the avatar menu is in `header-actions`, so it is hidden below 1024.

**Measured (2026-11-02, dev, logged out and logged in):** row 64px, header 65px with its border, no sideways overflow at 390, 768, 820, 900, 960, 1000, 1023, 1024, 1100, 1440. Toggle shown through 1023, gone at 1024; avatar hidden at 1023, shown at 1024. At 768: Enter, Space and Escape work, focus returns to the toggle, and the tab order follows the panel.

## 4. Mobile menu mechanism

Core's navigation overlay cannot do H1 to H4: its breakpoint is fixed in core CSS and it renders outside the flow.

- **Nav block:** set `overlayMenu` to `never`; `header-nav` is hidden below 1024 in the block style.
- **Toggle:** a `<button data-audit-id="header-menu-toggle" class="ink-icon-btn ink-icon-btn--menu" aria-expanded aria-controls>` in `.ink-header-row`, Menu and X icons as Lovable, shown below 1024.
- **Panel:** `<div class="ink-mobile-menu" hidden>` as a second child of the container, under the row; a `<nav>` of the same links; the action row by auth state.
- **Logged out:** Teken in (ghost), Sluit aan (primary). **Logged in:** Teken uit (ghost), Begin skryf (primary), plus My profiel in the list, the only way to it below 1024. Existing strings only.
- **Link list single source:** one PHP array (`inc/header-nav.php`) emits both navs, so the link set cannot drift; the panel only reorders it. My profiel is the mobile panel's only extra.
- **Script:** `assets/js/header-menu.js`, site-wide (toggle, `hidden`, `aria-expanded`, icon swap). `ink_foundation_enqueue_user_menu` is logged-in only, so this needs its own enqueue.
- **Retire:** `functions.php:1693-1697` and `:1716-1733`; the toggle part of `ink_foundation_audit_id_navigation`; the core toggle selector in the `--menu` primitive (`primitives.css:1628,1639`). The blur moved from `::before` to the `<header>` template part (acceptance, 2026-11-02), since no fixed descendant remains. A future one must not sit inside the header.

**Reference ids exist (2026-11-01, uncommitted on `chore/delta-audit-ids`):** added to `verse-haven/src/components/layout/Header.tsx`, attributes only, with the owner's approval: `header-mobile-panel`, `header-mobile-nav`, `header-mobile-nav-home`, `-browse`, `-challenges`, `-competitions`, `-magazine`, `-library`, `-community`, `-profile`, `header-mobile-actions`, `header-mobile-btn-signin`, `header-mobile-btn-join`. Checked by `vite preview` at 390 with the menu open, logged out: the 12 rendered ids each resolve once. `-profile` renders only with a user, so a logged-out capture won't have it. Lovable's logged-in Sign out and Start Writing buttons carry no id; the capture is logged out. Capture state: `--click header-menu-toggle --state mobile-menu` at 390 and 767 (Lovable only shows the toggle below md).

## 5. E6: Biblioteek and Opleiding H2s

**`.ink-heading-h2-section` already exists** (`primitives.css:118`, with its md step at `:199`), and it is the role Lovable's other same-class H2s measured into (`_recipes.csv` h2-section). **No CSS to add.** The H2s use the smaller role, `ink-heading-h2-sub`.

| Page | Where | Change | Reaches a visitor? |
|---|---|---|---|
| Opleiding | `Training/Hub.php:604` | `ink-heading-h2-sub` → `ink-heading-h2-section` | Yes; `library-shelf-h2` renders on dev |
| Biblioteek | `Library/Archive.php:638` | **none** (owner ruling, step F brief: the page has no "Alles" state; `library-shelf*` is `scope` in `maps/biblioteek.csv`) | **No.** `render()` passes no featured items (`Archive.php:268-269`, commit 019e2b2); nothing to measure |
| both | closing CTA | **none** | No element, on purpose (owner, §11) |

**Override check:** `theme.json` sets only `margin:0` on those two title classes; no size override. `_recipes.csv` lines 51 and 58 still cite these ids as evidence for the `-regular` variants, measured before this Lovable change; don't read them as the current sizes.

**Note:** the rendered Opleiding shelf still needs a capture diff for `library-shelf-h2` at 390, 768, 1440.

## 6. Element table

| audit-id | Lovable | recipe | data source | copy | status / note |
|---|---|---|---|---|---|
| `header` | Header.tsx:28 | block style `is-style-ink-header` | — | — | planned: re-capture only; no change expected |
| `header-row` | Header.tsx:30 | block style | — | — | planned: re-capture |
| `header-nav` | Header.tsx:40 | core/navigation, layout (page sheet) | — | — | divergence: hidden below 1024, Lovable below md (§11) |
| `header-nav-home` | Header.tsx:41 | `nav-link` bound | url `/` | Tuis (existing) | planned |
| `header-nav-browse` | Header.tsx:44 | `nav-link` bound | url `/ontdek/` | Ontdek (existing) | planned: id not stamped today: filter key lacks the trailing slash |
| `header-nav-library` | Header.tsx:47 | `nav-link` bound | url `/biblioteek/` | Biblioteek (existing) | planned: filter maps it to `/opleiding` today; move to `/biblioteek/` |
| `header-nav-training` | — | `nav-link` bound | url `/opleiding/` | Opleiding (existing) | scope: new INK-only id; D1: existing items stay |
| `header-nav-challenges` | Header.tsx:50 | `nav-link` bound | url `/uitdaging/` | Uitdagings (existing) | planned |
| `header-nav-competitions` | Header.tsx:53 | `nav-link` bound | url `/kompetisies/` | Kompetisies (D1) | planned |
| `header-nav-magazine` | Header.tsx:56 | none | — | — | scope: D1: InkPols stays out of the header. Not built |
| `header-nav-community` | Header.tsx:59 | `nav-link` bound | url `/gemeenskap/` | Gemeenskap (existing) | planned: id not stamped today: trailing slash |
| `header-nav-profile` | — | none | — | — | scope: off the desktop row, the avatar menu has it (owner, 2026-11-02). Not built |
| `header-actions` | Header.tsx:65 | layout (page sheet) | — | — | divergence: hidden below 1024, Lovable below md (§11) |
| `header-btn-signin` | Header.tsx:101 | existing ghost sm | — | Terms `teken_in` | planned: href on dev is `/teken-in`; no change |
| `header-btn-join` | Header.tsx:104 | existing primary sm | — | Terms `sluit_aan` | planned |
| `header-btn-write` | Header.tsx:68 | existing primary sm | logged in | existing | planned |
| `header-menu-toggle` | Header.tsx:113 | `ink-icon-btn ink-icon-btn--menu` | — | aria-label: copy-debt CHROME-MENU | planned: own button replaces core overlay toggle; divergence: shown below 1024, Lovable below md (§11) |
| `header-mobile-panel` | Header.tsx:127 | RECIPE-GAP RG-CH-01 | — | — | planned |
| `header-mobile-nav` | Header.tsx:128 | RECIPE-GAP RG-CH-01 | link array shared with desktop | — | planned |
| `header-mobile-nav-home`, `-browse`, `-challenges`, `-library`, `-community` | Header.tsx:129-135 | RECIPE-GAP RG-CH-01 | same urls as desktop | existing labels | planned |
| `header-mobile-nav-competitions` | Header.tsx:132 | RECIPE-GAP RG-CH-01 | url `/kompetisies/` | Kompetisies (D1) | planned |
| `header-mobile-nav-magazine` | Header.tsx:133 | none | — | — | scope: D1. Not built |
| `header-mobile-nav-training` | — | RECIPE-GAP RG-CH-01 | url `/opleiding/` | Opleiding (existing) | scope: INK-only, D1 |
| `header-mobile-nav-profile` | Header.tsx:136 | RECIPE-GAP RG-CH-01 | logged in | My profiel (existing) | planned: not in a logged-out capture |
| `header-mobile-actions` | Header.tsx:137 | RECIPE-GAP RG-CH-01 | auth state | — | planned |
| `header-mobile-btn-signin` | Header.tsx:147 | ghost button | logged out | Terms `teken_in` | planned |
| `header-mobile-btn-join` | Header.tsx:148 | primary button | logged out | Terms `sluit_aan` | planned |
| `library-shelf-h2 (Opleiding)` | Library.tsx:235 | `ink-heading-h2-section` | static | Die redakteur se rak (existing) | planned: class swap at Hub.php:604, from `ink-heading-h2-sub` |
| `library-shelf-h2 (Biblioteek)` | Library.tsx:235 | none | static | Uitgelig (existing) | scope: owner ruling, no "Alles" state; Archive.php:638 unchanged. Unreachable: `render()` passes no featured items since 019e2b2 |
| `library-cta` | Library.tsx:309 | none | — | — | scope: no INK element on either page: removed on purpose (owner) |
| `library-cta-container` | Library.tsx:310 | none | — | — | scope: no INK element on either page: removed on purpose (owner) |
| `library-cta-h2` | Library.tsx:311 | none | — | — | scope: no INK element on either page: removed on purpose (owner) |
| `library-cta-text` | Library.tsx:314 | none | — | — | scope: no INK element on either page: removed on purpose (owner) |
| `library-cta-btn` | Library.tsx:318 | none | — | — | scope: no INK element on either page: removed on purpose (owner) |

## 7. Page sheet

None. Header layout stays in the `ink-header` block style (site-wide); `RG-CH-01` goes in `primitives.css`. E6 changes classes only.

## 8. Recipe gaps

| gap | element | proposed class |
|---|---|---|
| RG-CH-01 | inline mobile menu panel and link | `ink-mobile-menu; ink-nav-link--mobile` |

## 9. URL and slug check (dev, read-only, 2026-11-01)

`/kompetisies/`: 200 (see `05-kompetisies` §8). All other nav URLs exist already. Ship this package after `05`, or the link lands on the legacy body.

## 10. Test data

None for the logged-out header. **Logged-in header:** `fidelity-capture` per `HANDOVER.md` §5.1; it is missing from dev after a refresh. E6 needs nothing beyond dev's existing Opleiding items.

## 11. Accepted divergences

| Item | Status | Tie |
|---|---|---|
| No Magazine link | `scope` | **D1.** Not on handoff §3's list, which says it is complete; D1 and handoff §5 step 5 rule it |
| Extra Opleiding link | `scope` | D1 ("INK's other header items are correct") |
| My profiel off the desktop row, in the mobile panel | `scope` | Owner, 2026-11-02: the avatar menu has it from 1024 |
| Mobile menu below 1024, not 768 | divergence | Owner, 2026-11-02: one clean row at every width. `divergence` in the map, with `accept` scoped to `@<1024`, on `header-nav` and its links, `header-actions`, `header-btn-*`, `header-menu-toggle`. Disclosed in `header-main.php`, `functions.php`, `primitives.css`, `header-menu.js` |
| Afrikaans labels | divergence | #3 |
| Closing CTA absent on Opleiding and Biblioteek | `scope` | Owner, 2026-11-01: removed on purpose. `library-cta*` is `scope` in the map, and the E6 CTA item closes as a no-op |

## 12. Questions for owner

None. The closing CTA was ruled (`owner-rulings.md`).

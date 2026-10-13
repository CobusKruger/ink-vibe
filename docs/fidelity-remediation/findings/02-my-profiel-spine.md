# 02 · my-profiel — spine + structural findings

| | |
|---|---|
| Page slug | `my-profiel` |
| Reference | Lovable `/profile` (`src/pages/Profile.tsx`, mock data, no auth) |
| INK | `https://nuwe-ink.local/my-profiel/` captured **authenticated** as `fidelity-capture` (subscriber) |
| Date | 2026-10-13 |
| Phases | 0–4b (Stage 1) |
| Treatment | **A** — reference diff |

The header and footer are row 00's scope. The header branches on auth state, so they are excluded here. Captures were diffed after stripping `header*`/`footer*` ids, and the primitive register used `--exclude-chrome`.

**Read this first: the INK capture user has no data.** `fidelity-capture` has no bio, tagline, posts, reading list, follows, kennisgewings or membership (live HTML, 2026-10-13). As a result, every INK repeater (post cards, saved works, writer cards, feed rows, notification rows, the membership status card) is **not rendered** on INK. Those elements carry first-instance ids in the renderers but could not be measured. Lovable always renders its seeded mock data. See §5 and §6.

---

## 1. Files read end to end

**Lovable** (`/Users/cobus/Development/ink-lovable`)
- `src/pages/Profile.tsx` (800 lines, incl. the `Stat`, `NotificationRow` and `EmptyHint` sub-components)
- `src/components/ui/button.tsx`, `badge.tsx`, `card.tsx`, `tabs.tsx`, `avatar.tsx`, `separator.tsx`
- `src/lib/readerStore.ts`, `src/data/works.ts`, `src/data/writers.ts`
- Not read: `ui/input.tsx` and `ui/textarea.tsx`, used only in the edit modal. No structural claim about them is made here. Their computed values are in `02-my-profiel-drift-edit.md`.

**INK** (`/Users/cobus/Development/ink-vibe/wp-content`)
1. Template: `themes/ink-foundation/templates/page-my-profiel.html`. The header and footer parts are not re-read (row 00).
2. Patterns: `patterns/my-profiel.php`, `patterns/lidmaatskap-hernu.php`, and `patterns/profile-summary.php` (read to rule it out: it is **not** in this page's chain).
3. Dynamic blocks:
   - `plugins/ink-core/src/Social/ProfileEditor.php` (`ink/profiel-redigeer`)
   - `Social/BydraesSurface.php` (`ink/bydraes`)
   - `Engagement/ReadingList.php` (`ink/leeslys`)
   - `Social/FollowingList.php` (`ink/volg-lys`)
   - `Social/FollowingFeed.php` (`ink/volg-voer`)
   - `Notifications/KennisgewingsSurface.php`
   - `Social/Tagline.php`
   - `Social/FollowToggle.php` lines 80–115 (`toHtml`)
   - `Social/PinnedWorksManager.php::toggleHtml` (lines 168–177)
4. `functions.php`: lines 130–460 (audit-id render filters), 755–965 (my-profiel enqueues), 1424–1610 (block styles) and 2160–2270 (Gradering bridges).
5. `assets/css/profiel.css` (1097 lines, the only page-scoped stylesheet; read for R6 disclosures), `assets/js/profiel-tabs.js`
6. `theme.json` `styles.css`, extracted and searched for `ink-volg-voer`, `ink-leeslys`, `ink-gradering`, `ink-ontdek-tabs__knoppie` and `ink-lidmaatskap-plans`. Those rules were read in full.
7. Disclosure source: `docs/my-profiel-rebuild-strategy.md` §1–§7

---

## 2. Section spine

| # | Lovable section (Profile.tsx) | INK counterpart | Status |
|---|---|---|---|
| S1 | Identity strip `<section>` 173–215 | `.ink-profiel-identiteit-band` my-profiel.php:102–189 | present |
| S2 | Tab shell `<section>` + `TabsList` 217–255 | second `<section>` + `nav.ink-profiel-tabs` my-profiel.php:195–216 | present (different mechanism, F1/F7) |
| S3 | Overview panel 258–301 | `#oorsig` my-profiel.php:220–320 | present |
| S4 | Posts panel 304–358 | `#bydraes` → `BydraesSurface` | present (empty for the capture user) |
| S5 | Reading list 361–436 | `#leeslys` → `ReadingList` | present (empty) |
| S6 | Following 439–495 | `#wie-ek-volg` → `FollowingList` | present (empty) |
| S7 | Activity 498–561 | `#aktiwiteit` → `FollowingFeed` | present (empty) |
| S8 | Notifications 565–581 | `#kennisgewings` → `KennisgewingsSurface` | present (empty) |
| S9 | Membership 584–666 | `#lidmaatskap` → status card (members only) + `lidmaatskap-hernu` | present (status card absent: non-member) |
| S10 | Edit modal 671–738 (conditional) | `ink/profiel-redigeer` my-profiel.php:192, always in the DOM, hidden | present |

No missing or extra section.

---

## 3. Structural findings

| ID | Class | Finding | Lovable | INK |
|---|---|---|---|---|
| F1 | MECHANISM | Radix Tabs mount only the active panel. INK server-renders all 7 panels and hides the inactive ones with `[hidden]` from JS, with a `#hash` deep link. | Profile.tsx:218,258… | my-profiel.php:220–416; profiel-tabs.js:37–76 |
| F2 | STRUCTURE | Identity row has 3 siblings on Lovable. INK wraps avatar + text in an extra `__persoon` group. At 390 Lovable stacks in a column; INK wraps a row (measured, drift@390). | 175–212 | my-profiel.php:118–160 |
| F3 | MISSING | Camera overlay button on the avatar. The ProfileEditor docblock defers avatar upload, but owner decision §7.1 included the avatar → **owner call**. | 181–187 | — (ProfileEditor.php:39–50) |
| F4 | MISSING | Button icons: Pencil / PenLine / ArrowRight (identity), Pencil (About Edit), PenLine (Posts New post). | 199,203,209,266,309 | my-profiel.php:166,172,176,235; BydraesSurface.php:188 |
| F5 | STRUCTURE | Action group: INK `Wysig profiel` is a bare `<button>`; the other two sit in a nested `wp-block-buttons`. | 196–212 | my-profiel.php:163–180 |
| F6 | SCOPE | Gradering badge + wins-needed text in the identity strip. Owner decision (strategy §7.5). | — | my-profiel.php:137–152 |
| F7 | DIVERGENCE (accepted?) | Underline tab bar vs Lovable's segmented pill `TabsList`. Disclosed at profiel.css:10–13 citing strategy §5.2, which is **not** in the §7 owner-ratified list → owner to confirm. | 219–255; tabs.tsx:15,30 | my-profiel.php:208–216; profiel.css:158–192 |
| F8 | MISSING | Tab icons (BookOpen, Bookmark, Users, Activity, Bell, Crown). | 222–252 | my-profiel.php:209–215 |
| F9 | MISSING | Tab count badges: reading / following (outline) and unread notifications (filled). Planned in strategy §5.8 pt 3, never built. | 226–249 | — |
| F10 | STRUCTURE (a11y) | INK tab `<button>`s carry `aria-selected` with no `role="tab"` / `tablist` / `tabpanel`. | — | my-profiel.php:208–216 |
| F11 | STRUCTURE | Every tab panel `<section>` keeps `"layout":{"type":"constrained"}`, so its children clamp to contentSize 768px inside the 1368px wrapper. Measured @1440: `profiel-overview-grid` 768 vs 1368, `profiel-recent-card` 768 vs 1368; same on posts, reading, following, notifications and membership heads. This is the same bug class the pattern's own "WIDTH BUG FIX" comments fixed on the two outer wrappers (my-profiel.php:104–113, 197–202). | 258 (no clamp) | my-profiel.php:220,323,330,337,344,351,360 |
| F12 | STRUCTURE | Overview card headings are `h2` on Lovable and `h3` on INK. | 263,274,292 | my-profiel.php:234,261,305 |
| F13 | MISSING | Membership one-liner: Crown icon and `Separator` element (INK uses a border-top instead, profiel.css:319–326). INK renders the line only when a renewal date exists. | 280–284 | my-profiel.php:280–292 |
| F14 | MISSING | Recent-activity row: icon tile, "Nh ago" line and right-hand unread dot. INK `<li>` is text only, with a left `::before` dot (profiel.css:352). For no data, INK keeps a heading-only shell (disclosed my-profiel.php:301); Lovable always has 3 rows. | 294–297, 752–777 | my-profiel.php:307–315 |
| F15 | MECHANISM | Stat: value then label (divs) vs `<dt>` then `<dd>`, reordered with CSS `order`. | 745–750 | my-profiel.php:265–278; profiel.css:303–317 |
| F16 | SCOPE | Posts empty state; Lovable has none. Copy-debt placeholder. | — | BydraesSurface.php:191–199 |
| F17 | STRUCTURE | Post title is `h3` on Lovable, `<a>` on INK. INK puts the date in the badge row; Lovable in the meta row. | 333–335 | BydraesSurface.php:221,225 |
| F18 | DIVERGENCE (accepted) | Read count replaces likes/comments; pin toggle added; no Konsep badge; "Sien" is primary instead of a ghost "View". Strategy §3/§5.4/§7.3/§7.5; profiel.css:494–499. | 334–353 | BydraesSurface.php:206–236 |
| F19 | MISSING | Reading-list header row: subtitle + "Find more to read". | 362–372 | ReadingList.php:106 (h2 only) |
| F20 | MISSING | Reading card: read time, Feather icon, byline, Read / Remove actions, card structure. | 384–430 | ReadingList.php:133–136 |
| F21 | MISSING | Reading empty-state CTA ("Browse works"). | 377, 792–794 | ReadingList.php:120–123 |
| F22 | STRUCTURE | Following: in the empty state INK drops the header row and the persistent "Ontdek skrywers" CTA; Lovable always shows "Discover writers". | 440–450 | FollowingList.php:125 vs 137–139 |
| F23 | STRUCTURE | Writer name is a link on Lovable, a `<span>` on INK. | 468–473 | FollowingList.php:156 |
| F24 | STRUCTURE | Unfollow control: ghost icon-only `UserMinus` vs `FollowToggle` text button ("Volg tans"). FollowToggle stamps a fixed `skrywer-follow-btn` id, so a populated list repeats that id (T5 class) and it cannot carry this row's id. | 478–488 | FollowToggle.php:104–114 |
| F25 | MISSING | Activity header subtitle. | 499–505 | FollowingFeed.php:220 |
| F26 | STRUCTURE | Activity row: Lovable has an avatar column + text column. INK has a head row with the avatar inside the author link. | 518–555 | FollowingFeed.php:262–315 |
| F27 | STRUCTURE / MISSING | Activity empty state: `EmptyHint` (dashed box, title, body, CTA) vs a bare `<p>`. Measured: no border, start-aligned. | 507–512, 779–797 | FollowingFeed.php:222–234 |
| F28 | MISSING | Notification row: type icon (INK's `::before` tile is empty, profiel.css:774) and time-ago line. | 762–772 | KennisgewingsSurface.php:318–321 |
| F29 | SCOPE | Notifications empty state; Lovable has none. Copy-debt placeholder. | — | KennisgewingsSurface.php:302–310 |
| F30 | STRUCTURE / MISSING | Membership status card: role swap. Lovable has Crown + "Inkwell Member" as the title row and "Active subscription" as a `p`; INK has an eyebrow "INK-lid" and an `h3`. Status dot and Calendar icon are missing. The card renders for active members only. | 586–614 | my-profiel.php:362–401 |
| F31 | STRUCTURE | Renew: Lovable has one `Card` (h3 + p + plans + footer). INK has a padded `alignfull` `<section>` band nested inside the panel, with an `h2` xxl. | 616–664 | lidmaatskap-hernu.php:74–157 |
| F32 | MISSING / DIVERGENCE | "/ Nmo" suffix and footer note missing. The savings line is a DIVERGENCE (accepted, FR-4 / strategy §2). | 639–658 | lidmaatskap-hernu.php:104–145 |
| F33 | DIVERGENCE (accepted) | Purchase card per plan with its own CTA, no select state; WooCommerce prices. Strategy §2/§5.9; profiel.css:859–866. | 622–662 | lidmaatskap-hernu.php:88–147 |
| F34 | STRUCTURE (**live bug**) | The edit modal root sits inside `<main>`'s constrained layout. Its `position:fixed; inset:0` box is clamped to **800×876 at (320, 24)** @1440, so the backdrop does not cover the viewport. Lovable: 1440×900. | 672 | my-profiel.php:192; ProfileEditor.php:127; profiel.css:924–944 |
| F35 | MISSING | Modal "Change photo". Same deferral as F3 → owner call. | 682–691 | ProfileEditor.php:135–140 |
| F36 | STRUCTURE | Modal heading `h3` vs `h2`; fields are `div` + label + Input on Lovable, `<p>` flex columns on INK. | 675–725 | ProfileEditor.php:133–158 |

Notes:
- **F7 / F33 / F18 checked against R6.** Each has an in-file disclosure. Only F18, F32 (savings) and F33 trace to owner-ratified decisions (strategy §2, §7). F7 rests on §5.2 and the CSS comment.
- **F3 / F35** contradict owner decision §7.1 ("real inline edit modal (name/tagline/bio/avatar)"). The deferral is an implementer docblock, not a ruling.
- **F34 and F11 share a root cause:** constrained-layout child rules applied to elements that must not be clamped. F34 is user-visible.
- **Not fidelity, but worth flagging:**
  - User-visible `[NEEDS HUMAN AFRIKAANS]` placeholders render on this page today: bio fallback, posts / reading / notifications empty states, modal tagline label, Cancel and Save, and the activity phrase.
  - In the Lidmaatskap tab, all three renewal plans show **R50,00** and link to the same `add-to-cart=926` (live HTML). This is either local data or a plan-mapping bug.

---

## 4. Drift headline (from the generated docs — not restated as numbers here)

Generated docs:
- `02-my-profiel-drift.md` (1440), `-drift@768.md` and `-drift@390.md` (default Oorsig tab)
- `02-my-profiel-drift-{posts,reading,following,activity,notifications,membership,edit}.md` (1440 states)

Lovable reading / following / activity were driven into their **empty** states (mock-only clicks, so no data was written), so they pair with INK's empty capture user.

Biggest measured items @1440:
1. **768px panel clamp (F11).**
2. **h1 name:** 24px / 700 vs 36px / 400. The profiel.css:60 font-size is dead under `has-xxl-font-size !important`, and the weight comes from the h1 element.
3. **Tabs:** 51px underline tabs vs 32px pills; `line-height` and `nowrap` differ.
4. **Card padding:** sits on the card on INK, on `CardContent` on Lovable (MECHANISM). Card titles are 18px vs 20px; shadow token differs.
5. **Activity panel h2:** 20px / 700, because `.ink-volg-voer__heading` is outside the panel-heading group. The other panels' h2 are 24px / 400.
6. **Membership:** plan price is Inter 700 vs Lora 400; renew title is 24px / 700 vs 20px / 400.

---

## 5. Join-key coverage

`maps/my-profiel.csv` has **188 ids**: 126 paired, 3 divergence, 43 missing-ink, 9 missing-lovable, 7 scope. Lovable ids use first instance only via props on `Stat` / `NotificationRow` / `EmptyHint`; INK repeaters use first instance only via an index guard in the renderers.

- Default capture: **43 ids paired** in `<main>` at each of 390, 768 and 1440.
- Across all 10 diffs, **83 of 129** paired/divergence ids were measured.
- The 46 unmeasured ids are **all** INK elements that render only with data. Repeaters: post / reading / writer / feed / notification rows. Populated-only wrappers: following `__kop` + CTA, membership status card + grid, glance membership line, recent list.
- Duplicate-id check (T5): empty on every capture.

Coverage gaps:
- **Populated INK states.** Needs an owner decision to seed `fidelity-capture` (or a second capture user) with posts, saves, follows, kennisgewings and a membership. No data was written here.
- **Not captured:** hover states, the tagline-present state, and the members-only Lidmaatskap layout.

---

## 6. Conclusion and limits

My Profiel has every Lovable section; nothing at section level is missing or extra. Its divergence breaks into four groups:
- **Layout:** the 768px panel clamp (F11) and its sibling live bug, the clamped edit-modal overlay (F34).
- **A missing icon and badge layer:** buttons, tabs, notification rows and the membership card (F4, F8, F9, F13, F14, F28, F30).
- **Header-row furniture** that Lovable has and INK's ink-core renderers lack: subtitles, secondary CTAs and empty-state CTAs (F19, F21, F22, F25, F27).
- **Owner-decided content departures** (F6, F18, F33).

Four items need an owner call: F3 / F35 (avatar editing vs §7.1), F7 (tab shape), and seeding the capture user.

**Limits.**
- This document makes no claim about computed values except where it cites the generated drift documents.
- The INK repeaters' structure is asserted from source only. Their rendered values are unmeasured (§5).
- The Radix `Tabs` / `Avatar` internals (node_modules) were not read, so no claim rests on their emitted roles.
- The header and footer are verified on row 00, not here.

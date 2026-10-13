# 06 · skrywer — Spine and structural findings

| | |
|---|---|
| **Page slug** | `skrywer` |
| **Lovable** | `/writer/w1` → `src/pages/Writer.tsx` (served from an isolated build on `:4186`) |
| **INK** | `https://nuwe-ink.local/author/cobus/` → `templates/author.html` |
| **Date** | 2026-10-13 |
| **Phase** | Stage 1 · Phases 0–4b (this document is the Phase 2 output) |
| **Treatment** | **A** · reference diff |

Phase 0: the pairing is owner-verified in `agent-brief.md` §2.1 (row 06, open question §3.1
resolved to `author.html`). Reading both sides confirmed it. `Writer.tsx` is the only Lovable
writer-profile page, and `author.html` is the only INK template that renders a public writer
profile (`ink/skrywerprofiel`, which renders nothing outside `is_author()`,
`SkrywerProfiel.php:92-95`).

**Header and footer** are out of scope. They were verified on row 00 (`tuisblad`) and are not
re-reported here.

---

## 1. Files read end to end

**Lovable**
- `src/App.tsx`: the route `/writer/:id` (line 37) only.
- `src/pages/Writer.tsx`, 461 lines before Phase 3, read in full. This includes the local
  components `StatBlock`, `AccomplishmentRow` and `WorkCard`.
- `src/components/ui/button.tsx`: variants `literary`, `literary-outline`, `subtle`; size `lg`.
- `src/components/ui/badge.tsx`: variants `outline` and the default.
- `src/components/ui/card.tsx`: `Card`, `CardContent`.
- `tailwind.config.ts`: the `container` block (centred, `2rem` padding, `2xl: 1400px`).
- `src/index.css`: `.card-hover` and the `--shadow-*` tokens.
- `Header.tsx` and `Footer.tsx` were not re-read. Row 00 covers them.

**INK — the resolution chain (README §3)**
1. Template: `wp-content/themes/ink-foundation/templates/author.html`, 12 lines.
2. Parts: `header` and `footer`. These are row 00's and were not re-read.
3. Patterns: none. The template composes two dynamic blocks directly.
4. Dynamic blocks:
   - `wp-content/plugins/ink-core/src/Social/SkrywerProfiel.php` (`ink/skrywerprofiel`), 719
     lines before Phase 3, read in full.
   - `wp-content/plugins/ink-core/src/Social/FollowToggle.php` (`ink/volg-knoppie`, embedded by
     the above), read in full.
   - `wp-content/plugins/ink-core/src/Social/RatingForm.php` (`ink/leseroordeel-vorm`), read in
     full.
   - `wp-content/plugins/ink-core/src/Social/Tagline.php`, read in full. It was read to
     establish where tagline data lives (F2).
- `functions.php`: lines 1–520 (enqueues and the `render_block_core/*` audit-id filters),
  740–970 (the volg, profiel and skrywer-deel enqueues; the only `is_author()` gates), and
  1415–1485 (the `card` and `pill` block styles). No stylesheet is enqueued for `is_author()`.
  The page is styled entirely by `theme.json` `styles.css`.
- `theme.json`: `settings.layout` (`contentSize: 768px`, `wideSize: 1368px`). The `styles.css`
  string was extracted and every `.ink-skrywerprofiel*`, `.ink-gradering*`,
  `.ink-volg-knoppie*` and `.ink-leseroordeel-vorm*` rule was read.
- The live anonymous render of `/author/cobus/` (`curl`, cache-busted) was used to confirm
  which conditional branches fire for this writer.

---

## 2. Section spine

Lovable's `<main>` holds five sibling `<section>`s, each with its own `container`
(max 1400px) or a full-bleed band. INK's `<main>` is a constrained `core/group`
(content 768px). It holds **one** `<section class="ink-skrywerprofiel">`, and every region
below is a child of that section (F1).

| # | Lovable section (`Writer.tsx`) | INK counterpart (`SkrywerProfiel.php`) | Status |
|---|---|---|---|
| 1 | Cover + identity `<section>` :183 — cover band :184, avatar, genres, name, tagline, meta, actions | `__omslag` :429 + `<header class="__kop">` :436 | present (restructured, F1/F4) |
| 2 | Rating + stats strip `<section>` :255 | `__statistieke` :494 | present (conditional items, F7/F8) |
| 3 | Bio + accomplishments `<section>` :293 | `__oor-prestasies` :533 | present |
| 4 | Curated works `<section>` :317 | `__vasgespel` :576 | present (pinned-only; conditional) |
| — | — | `__lesergradering` `<section>` :695 (reader reviews) | **extra** (F19) |
| 5 | CTA `<section>` :347 | `__cta` :631 | present |
| — | — | `ink/leseroordeel-vorm` (author.html:7) | **extra**, logged-in only (F20) |

---

## 3. Structural findings

Line numbers are post-Phase 3 (they include the added `data-audit-id` attributes).

| id | class | finding | Lovable | INK |
|---|---|---|---|---|
| F1 | STRUCTURE | Page architecture differs. Lovable has five sibling sections, each with its own `container` (1400px) or a full-bleed band (cover, stats strip, works band). INK has one section inside a constrained `<main>` (768px content, s-48/s-24 padding), so no region bleeds and the widest content is 768px at every viewport ≥ 816px | `Writer.tsx:181-367` | `author.html:3-8`; `SkrywerProfiel.php:423`; `theme.json` `settings.layout.contentSize` |
| F2 | MISSING | Tagline (italic quoted line under the name). The data exists (`ink_skrywer_leuse`, edited on My Profiel), but the public profile never reads it | `Writer.tsx:217-224` | `SkrywerProfiel.php:103-120` (profile array has no tagline); `Tagline.php:37,70` |
| F3 | MISSING | Location meta item (MapPin icon + text). INK has no location field. INK's meta items also carry no icons (CalendarDays on "joined") | `Writer.tsx:226-233` | `SkrywerProfiel.php:463-470` |
| F4 | STRUCTURE | Actions placement. In Lovable the Follow/Share group is a third flex column, stacked and right-aligned from md up. In INK the group is nested inside the identity text column, below the meta row | `Writer.tsx:237-249` | `SkrywerProfiel.php:472-489` |
| F5 | SCOPE | Gradering badge (★ + tier label) under the name. Not in Lovable. INK tier system; display-only per the class docblock | — | `SkrywerProfiel.php:135-147,458-461` |
| F6 | STRUCTURE | Followers moved. Lovable shows a "Followers" stat in the stats strip. INK shows "N volgelinge" in the identity meta row, and its strip has no followers stat | `Writer.tsx:285` | `SkrywerProfiel.php:464` |
| F7 | MISSING | (a) Reader-rating stat. INK renders it only when ≥ 1 rating is approved, so it is absent for this writer (data-dependent). (b) Even when it renders, the "N reader reviews" count line has no INK counterpart | `Writer.tsx:257-283` | `SkrywerProfiel.php:493-504` |
| F8 | SCOPE | The works stat adds a per-type breakdown line ("15 Gedig · 1 Storie") and hides zero-value types. Disclosed as ratified copy-sheet note 2. The Works and Hartjies stats are also conditional (hidden at 0), where Lovable always shows four cells | `Writer.tsx:284-286` | `SkrywerProfiel.php:194-196` (disclosure), `505-528` |
| F9 | STRUCTURE | Stats strip wrapper count. Lovable uses `<section>` (band) > `container` grid > cells, and the rating cell has an `md:border-r` divider. INK uses one `<div>` that is both band and grid | `Writer.tsx:255-257` | `SkrywerProfiel.php:494` |
| F10 | STRUCTURE | The Accomplishments heading is an `<h3>` rendered as an uppercase xs eyebrow in Lovable, and an `<h2>` in INK | `Writer.tsx:305` | `SkrywerProfiel.php:547,568` |
| F11 | STRUCTURE | Accomplishment row shape. Lovable uses `Card` > `CardContent` (p-4 flex) > icon tile + text, with a type-specific lucide icon. INK uses one flex `<div>` > ★ glyph tile + text. Content source differs too: INK rows are real Gradering promotions (docblock :340-348). **Not rendered for this writer** (no Gradering history), so INK shows the heading-only empty shell (:567-569) | `Writer.tsx:308-312,395-412` | `SkrywerProfiel.php:545-569` |
| F12 | STRUCTURE | Works-section header. Lovable uses `container` > flex row (kicker + h2 left, "See all works" link right, link hidden < 768). INK stacks kicker, h2 and grid, and puts the see-all link **after** the grid, aligned end and visible at all widths | `Writer.tsx:317-336` | `SkrywerProfiel.php:576-619` |
| F13 | MISSING | BookOpen icon in the works kicker | `Writer.tsx:322` | `SkrywerProfiel.php:577` |
| F14 | STRUCTURE | Work-card shape. In Lovable the whole card is the link (`Link` > `CardContent` > meta row > …). In INK the `<li>` is the flex column and only the title `<a>` is a link. Lovable's title is an `<h3>`; INK's is a bare `<a>` | `Writer.tsx:420-461` | `SkrywerProfiel.php:591-610` |
| F15 | MISSING | Read-time meta item (Clock icon + "8 min") on the work card | `Writer.tsx:430-433` | `SkrywerProfiel.php:591-593` |
| F16 | SCOPE | "Pinned" badge. Lovable's curated list mixes pinned and unpinned works and badges the pinned one. INK's list is pinned works only by construction (`PinnedWorks::forUser`, :311-338), so a badge would be on every card | `Writer.tsx:434-438` | `SkrywerProfiel.php:570-616` |
| F17 | MISSING | Type icon inside the work-card type pill (Feather / BookOpen / ScrollText) | `Writer.tsx:425-427` | `SkrywerProfiel.php:592` |
| F18 | MECHANISM | Card count icons are lucide `Heart` / `MessageCircle` svgs in Lovable and text glyphs `♡` / `💬` in INK. The rating stars are likewise svgs vs `★`/`☆` glyphs | `Writer.tsx:452-459,266-277` | `SkrywerProfiel.php:606-607,656-670` |
| F19 | SCOPE | Lesergradering section (reader reviews, Story 9.6), with an empty state for this writer. INK-only | — | `SkrywerProfiel.php:683-722` |
| F20 | SCOPE | `ink/leseroordeel-vorm` rating form. INK-only, rendered for logged-in non-self viewers only, so it is not in any capture | — | `author.html:7`; `RatingForm.php:75-120` |
| F21 | STRUCTURE | CTA wrapper count. Lovable uses `section.container` > `max-w-3xl text-center` inner > h2, p, actions. INK uses one flex-column `<div>` | `Writer.tsx:347-370` | `SkrywerProfiel.php:631-645` |
| F22 | SCOPE | Follow buttons (identity actions **and** CTA) render only for a logged-in viewer who is not the writer. Anonymous visitors, and so every capture, see Share only in the identity actions and Discover only in the CTA. Lovable always shows both Follow buttons | `Writer.tsx:238-244,357-363` | `FollowToggle.php:79-95`; `SkrywerProfiel.php:474-476,637-641` |
| F23 | MECHANISM | Cover image. Lovable uses CSS `background-image` on the band `<div>`. INK uses an `<img object-fit:cover>` inside the band, and omits the band entirely when no cover is set (data-dependent; cobus has one) | `Writer.tsx:184-190` | `SkrywerProfiel.php:428-434` |
| F24 | STRUCTURE | Avatar wrapper. In Lovable the `<img>` is a direct flex child. INK wraps the core `get_avatar()` `<img>` in `div.__foto` | `Writer.tsx:194-199` | `SkrywerProfiel.php:438-443` |
| F25 | STRUCTURE | Identity text column. Lovable uses a block `<div>` with margin-spaced children. INK uses a flex column (gap s-8) that also contains the gradering badge and the actions | `Writer.tsx:200-235` | `SkrywerProfiel.php:445-488` |

**Classification notes**
- **F1** is the dominant divergence. Most of the box-model drift in the generated diffs
  (widths of 768/720/342px against 1400/1440/390px) is this one decision. It sits in the
  template (`core/group` constrained layout) plus the single-section renderer, not in
  per-element CSS.
- **F5, F8, F16, F19, F20 and F22** are recorded as `SCOPE` because each reflects real INK
  functionality or data that the mockup lacks, or an auth rule. None is to be reverted
  silently; the owner decides.
- **F8** carries an in-source disclosure (`SkrywerProfiel.php:194-196`, "zero-value types
  omitted per the ratified copy sheet's note 2"). Under R6 the breakdown line is close to
  `DIVERGENCE (accepted)`. It is filed `SCOPE` because the disclosure covers the zero-omission
  and not the extra line itself.
- **F2** is `MISSING`, not `SCOPE`. INK already stores and edits a tagline (`Tagline.php`),
  and nothing discloses its absence from the public profile.
- **F3** has no data source in INK (no location field). It is filed `MISSING`, but porting it
  would need a new field, which is an owner call.
- **R6 check.** The renderer's comments were read for disclosures: the cover-banner note
  (:425-427), the 2/1 grid note (:527-529), the empty Prestasies shell "pre-existing behaviour"
  (:562), the copy-sheet notes (:38-40, :568-569, :608-611) and FR-40 "no read counts"
  (:34-36). None of them discloses F2–F4, F6, F7(b), F9–F15, F17, F18, F21, F24 or F25.

---

## 4. Join-key coverage

- **Lovable:** 77 `skrywer-*` ids are rendered. Repeaters (genre pill, accomplishment card,
  work card) are tagged on the first instance only. `StatBlock` derives `-label` and `-value`
  from its `auditId`.
- **INK:** 53 `skrywer-*` ids are served for `/author/cobus/` anonymously. The same renderer
  stamps a further 9 ids that are absent here because of data or auth state
  (`skrywer-stat-rating*`, `skrywer-rating-*`, `skrywer-prestasies-list`,
  `skrywer-prestasie*`, `skrywer-follow-btn`, `skrywer-cta-follow-btn`).
- **Paired: 44 `skrywer-*` ids at all three viewports**, plus 31 header/footer ids from
  row 00.
- **Lovable-only (33):** every one is a recorded finding (F1, F2, F3, F6, F7, F9, F11–F15,
  F21, F22), or is absent on INK only because of data or auth state (F7a, F11, F22).
- **INK-only (9 `skrywer-*`):** `skrywer-profiel` (F1), `skrywer-cover-image` (F23),
  `skrywer-avatar-wrap` (F24), `skrywer-gradering` (F5), `skrywer-meta-followers` (F6),
  `skrywer-stat-works-detail` (F8), `skrywer-lesergradering`, `-title` and `-empty` (F19).
- **Pre-existing ids reused unchanged in name.** From the owner's earlier "Added audit IDs"
  pass: `skrywer-genres`, `-genre-pill`, `-name`, `-tagline`, `-meta-joined`, `-follow-btn`,
  `-share-btn`, `-rating-value`, `-rating-stars`, `-stat-rating`, `-stat-works`,
  `-stat-followers`, `-stat-likes`, `-oor-prestasies-grid`, `-bio`, `-prestasies`,
  `-workcard`, `-workcard-title` and `-cta-follow-btn`. `skrywer-genre-pill`,
  `skrywer-workcard` and `skrywer-workcard-title` were on **every** repeater instance. Phase 3
  narrowed them to the first instance on both sides, because `diff.mjs` keys a `Map` by id and
  so pairs the **last** duplicate (`tools/fidelity/diff.mjs:91-92`). That would have joined
  Lovable's 4th card to INK's 1st.
- The full table, with file:line on both sides, is in `maps/skrywer.csv`.

---

## 5. Conclusion and limits

**What the divergence consists of.** The page has all five reference sections. It diverges
in three ways.

- **Architecture (F1).** A single constrained column replaces full-bleed bands and a 1400px
  container. That one decision accounts for most of the measured box-model drift.
- **A cluster of missing identity details and restructured groupings.** The tagline (F2),
  location (F3), action-column placement (F4), followers placement (F6), review count (F7b),
  the works header row (F12), whole-card links and read time on work cards (F14, F15), and
  icons throughout (F13, F17, F18).
- **INK-only scope.** The Gradering badge (F5), works breakdown (F8), reader reviews and
  rating form (F19, F20), and auth-gated Follow (F22).

**Data-dependent elements.** This audit used real data (cobus) and an anonymous session, so
the following are true of this capture only:

- Present but data-driven: genre pills ("Gedig", "Storie"), the Gradering label (Brons),
  "7 volgelinge", the joined month, the works total and breakdown, the Hartjies total (2), the
  cover image, the one-line bio, and one pinned card.
- Absent because of data: the reader-rating stat (0 approved ratings), accomplishment rows
  (no Gradering history; the empty heading shell renders instead), and further pinned cards.
- Absent because of auth: both Follow buttons and the rating form.
- A writer with no cover, bio, pins or published work would render a materially different page
  (`has-omslag` off, `__oor` and `__vasgespel` omitted, an empty stats strip).

**What this document cannot support.**
- It makes **no claim about computed values**. Size, colour, spacing and weight are Phase 4
  output (`06-skrywer-drift*.md`) and the primitive register (`06-skrywer-primitives.md`).
- It says nothing about the logged-in state. Follow, `is-following` and the rating form were
  not captured.
- It says nothing about the hover states (`.card-hover` lift against INK's `:hover` lift), the
  "Share" clipboard toast, or Lovable's `subtle` "Following" variant.

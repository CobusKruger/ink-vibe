# 03 · ontdek — spine and structural findings

| | |
|---|---|
| Page slug | `ontdek` (inventory row 03) |
| Lovable | `/browse` (`src/pages/Browse.tsx`), served from an isolated build on `:4183` |
| INK | `https://nuwe-ink.local/ontdek/` (`templates/page-ontdek.html`) |
| Date | 2026-10-13 |
| Phase | 2 (spine and structural findings), written alongside Phase 3 (join key) |
| Treatment | **A** (reference diff) |

Header and footer are **out of scope** here. They were verified on row 00 (`tuisblad`) and
are not re-reported. Their ids render on this page unchanged.

---

## 1. Files read end to end

**Lovable**

- `src/App.tsx` (route `/browse` → `Browse`, line 36)
- `src/pages/Browse.tsx` (entire file, including the local `TabButton`, `SortPill`,
  `StoryCard`, `WriterCard`, `Stat` and `EmptyState` components)
- `src/components/ui/input.tsx`, `button.tsx` (the `literary`, `literary-outline` and
  `subtle` variants), `badge.tsx` (the `outline` variant), `card.tsx` (`Card`, `CardContent`)
- `src/lib/readerStore.ts` (save/follow state, localStorage only; emits no DOM)
- `src/index.css` `.card-hover` (lines 173–180)
- `Header.tsx` / `Footer.tsx` were not re-read. They were covered by row 00.

**INK resolution chain (README §3)**

1. Template: `wp-content/themes/ink-foundation/templates/page-ontdek.html`
2. Parts: `header` and `footer`. Not re-read, covered by row 00.
3. Patterns: `patterns/ontdek.php` (whole file) → `patterns/archive-intro.php` (whole file)
4. Dynamic blocks and render callbacks, all read in full:
   - `ink/ontdek-soek` → `ink-core/src/Discovery/Search.php`
   - `ink/ontdek-vlakke` → `ink-core/src/Discovery/DiscoverySurfaces.php`
   - `ink/ontdek-werke` → `ink-core/src/Discovery/WorksArchive.php`
   - `ink/ontdek-skrywers` → `ink-core/src/Discovery/SkrywersTab.php`
   - shared helpers: `ink-core/src/Kernel/ArchiveRender.php` (`pill()`, `pagination()`)
5. Behaviour: `themes/ink-foundation/assets/js/ontdek-tabs.js`. The enqueue is
   `functions.php:115-130`, gated by `is_page('ontdek')`.
6. Styles (needed for disclosure checks only; no computed claim rests on them):
   `theme.json` `styles.css`, the `.ink-ontdek-*` block; `functions.php:1383-1415`
   (`is-style-card` block styles).

I cross-checked against the rendered page. A cache-busted `curl` of `/ontdek/`
(anonymous) gave a `<main>` whose markup matches the chain above, element for element. No
DB-stored template override is in play.

---

## 2. Section spine

| # | Lovable section (`Browse.tsx`) | INK counterpart | Status |
|---|---|---|---|
| — | Header | `parts/header` | covered by row 00 |
| 1 | Intro band: eyebrow, h1, lede, **search** (389–419) | `archive-intro.php` section (h1, lede, eyebrow) | present; search split out (S4) |
| 2 | *(search is inside section 1)* | `ontdek.php:53` search band → `ink/ontdek-soek` | **extra** section (S4) |
| 3 | — | `ontdek.php:63` surfaces band → `ink/ontdek-vlakke` | **extra** (S6, SCOPE) |
| 4 | Sticky tab bar (422–437) | `ontdek.php:73` `.ink-ontdek-tabbalk` | present |
| 5 | Content section, Stories state (440–512) | `ontdek.php:92` `#bydraes` → `ink/ontdek-werke` | present |
| 6 | Content section, Writers state (514–578), the **same** element | `ontdek.php:102` `#skrywers` → `ink/ontdek-skrywers` | present, by a different mechanism (S9) |
| — | Footer | `parts/footer` | covered by row 00 |

---

## 3. Structural findings

| id | class | finding | Lovable | INK |
|---|---|---|---|---|
| S2 | STRUCTURE | Intro text column: Lovable wraps the copy in a `max-w-3xl` column. INK wraps it in a full `alignwide` group and narrows the h1 and lede individually in CSS | Browse.tsx:391 | archive-intro.php:11 |
| S3 | MECHANISM | Lovable has a `container` element carrying the band padding. INK has none; the padding sits on the section | Browse.tsx:390 | archive-intro.php:9 |
| S4 | STRUCTURE | Search lives inside the intro on Lovable (`max-w-xl`, under the lede). INK renders it as a separate full-width section after the intro | Browse.tsx:404-416 | ontdek.php:52-60 |
| S5a | EXTRA | INK search has a "Soek" submit button; Lovable has none | — | Search.php:219 |
| S5b | MECHANISM | Lovable filters both grids live as you type (client state). INK submits a GET form, and the results render as their own grouped lists inside the search block, not by filtering the grid | Browse.tsx:337-371, 406-415 | Search.php:131-181, 232-257 |
| S6 | SCOPE | "Nuwe stemme" and "Onlangs aktief" writer-surface rows, plus two more rows when logged in. The section has no Lovable counterpart (Story 8.5 feature) | — | ontdek.php:62-70; DiscoverySurfaces.php:215-306 |
| S7 | DIVERGENCE (accepted) | Tab bar sticks at `top:0` (admin-bar offset) where Lovable uses `top-16`. This is disclosed: the theme has no sticky site header to pin below | Browse.tsx:422 | ontdek.php:19-24 (disclosure) |
| S8 | MISSING | Tab item counts (`12`, `8`) | Browse.tsx:428, 433 | ontdek.php:77-84 |
| S9 | MECHANISM | Lovable has one content section and re-renders its children per tab. INK renders both panels server-side and hides one with `hidden` (JS); the panel is hash/query addressable (`#skrywers`, `skrywer_*`) | Browse.tsx:440-580 | ontdek.php:91-109; ontdek-tabs.js:42-86 |
| S10 | EXTRA | Each panel opens with its own heading: "Bydraes" `<h1>` (a second h1 on the page) and "Skrywers" `<h2>`. Lovable has no panel heading | — | WorksArchive.php:343; SkrywersTab.php:242 |
| S11 | MECHANISM | Filter and sort pills are `<a>` GET links that reload the page. Lovable uses `<button>` client state | Browse.tsx:448-461, 608-631 | ArchiveRender.php:46-52; SkrywersTab.php:386-395 |
| S12 | MISSING | Type icons inside the filter pills (Feather / BookOpen / ScrollText) | Browse.tsx:457-459 | WorksArchive.php:651-676 |
| S13 | MISSING | "Sort by" label before the sort pills (both tabs) | Browse.tsx:466, 535 | WorksArchive.php:682; SkrywersTab.php:374 |
| S14 | SCOPE | Date-archive pill row ("Alle datums" plus the last six years); no Lovable counterpart | — | WorksArchive.php:707-750 |
| S15 | MISSING | Result-count line ("12 pieces", "8 writers") above the grid | Browse.tsx:494-499, 560-565 | WorksArchive.php:361 |
| S16 | MISSING | Save-to-reading-list bookmark button on each story card | Browse.tsx:648-660 | WorksArchive.php:474-499 |
| S17 | STRUCTURE | Card shape: Lovable `Card` > whole-card `<Link>` > `CardContent p-6`. INK is a single `<li class="is-style-card">` with the padding on the `li` and only the title linked. The same holds for writer cards (`CardContent`) | Browse.tsx:647, 662-663, 715-716 | WorksArchive.php:474-499; SkrywersTab.php:308 |
| S18 | STRUCTURE | The story card title is an `<h3>` on Lovable and a bare `<a>` on INK (no heading element). The writer name is the same case | Browse.tsx:673, 724 | WorksArchive.php:488; SkrywersTab.php:317 |
| S19 | SCOPE | Real-data pagination ("Vorige / Volgende", 12 per page) under the works grid. Lovable shows its 12 mock items unpaged | — | WorksArchive.php:373; ArchiveRender.php:66-92 |
| S20 | STRUCTURE | Writer sort options are in a different order with a different default: Lovable [Most read*, New voices], INK [Nuwe stemme*, Meeste gelees] | Browse.tsx:536-547 | SkrywersTab.php:97-99, 166, 374-385 |
| S21 | STRUCTURE | Writer card head: on Lovable the avatar and name block is a `<Link>` and holds the genre badges. On INK the head is a `<div>`, and the genres row sits *below* it as a sibling | Browse.tsx:717-735 | SkrywersTab.php:309-331 |
| S22 | SCOPE | Writer card shows the Gradering label (Brons / Silwer / Goud); no Lovable counterpart | — | SkrywersTab.php:318 |
| S23 | MISSING | Writer card stats grid (Works / Followers / Likes) and the Follow / Following button | Browse.tsx:743-755 | SkrywersTab.php:299-340 |
| S24 | MISSING | Empty state: Lovable renders a dashed box with a serif title, hint and a "Clear filters" `literary-outline` button. INK renders a single `<p>` ("Geen … gevind nie.") | Browse.tsx:762-772 | WorksArchive.php:353-359; SkrywersTab.php:248-254 |
| S25 | DIVERGENCE (accepted) | Story-card type pill is coloured per type (sage gedig / brand storie / grey artikel). Lovable is terracotta for every type. Disclosed as a direct product-owner instruction | Browse.tsx:665 | WorksArchive.php:478-513 (disclosure) |

### Notes on classification

- **S6, S14, S19, S22 are SCOPE, not EXTRA.** Each is a shipped INK feature backed by a
  story (8.5 surfaces, 8.1 date browse and pagination, 8.3 gradering). They need an owner
  decision, never a silent deletion.
- **S7 and S25 are DIVERGENCE (accepted)** only because a disclosure comment exists at the
  cited lines (R6). Neither is a claim that the rendered value is right.
- **S10** is EXTRA rather than SCOPE because no story asks for a visible panel heading,
  and the "Bydraes" `<h1>` gives the page two `<h1>`s. Check whether it is deliberate
  before removing it.
- **S11 and S9 are mechanism only.** No-JS and server-rendered discovery (AD-7) require
  real links and both panels in the DOM. Their computed output is what Phase 4 checks.
- **Copy is not a finding.** The labels differ (English mockup vs Afrikaans); R2 applies.

---

## 4. Join-key coverage

`maps/ontdek.csv` holds 89 ids:

- **Paired (both sides):** 55
- **missing-ink:** 13 (Lovable-only): `intro-container`, `tabs-tab-count`, `stories-filter-icon`,
  `stories-sort-label`, `stories-count`, `stories-card-save`, `stories-card-link`,
  `stories-card-body`, `writers-sort-label`, `writers-count`, `writers-card-body`,
  `writers-card-stats`, `writers-card-btn`
- **missing-lovable:** 12 (INK wrappers and extras)
- **scope:** 9 (surfaces ×7, `stories-pagination`, `writers-card-grade`)

All `writers-*` ids are installed on both sides. They are **not** paired in the default
capture. The Lovable writers tab is pure React state with no URL, so its DOM does not exist
in a resting capture. On INK the `#skrywers` panel is present but `hidden`. See the drift
documents' unpaired lists.

Repeaters are tagged on the first instance only. Pill ids are positional: pill 0 is active
in the default (unfiltered) state, and pill 1 is the first inactive pill.

---

## 5. Conclusion and limits

The page's divergence is mostly **missing affordances and added real-data features, not
missing sections**. Every Lovable section has an INK counterpart.

- INK adds two whole bands: a split-out search section with a submit button, and the
  discovery-surfaces rows.
- It adds panel headings, a date-archive row and pagination.
- It drops the tab counts, filter icons, "Sort by" label, result count, bookmark button,
  writer stats and Follow button, and the designed empty state.
- The card is reshaped: a padded `li` with a title-only link, where Lovable has a card
  with a whole-card link and `CardContent`.

**What this document cannot support**

- It makes **no claim about any computed value** (size, colour, spacing, weight). Those are
  in `03-ontdek-drift*.md`, generated from a rendered capture (R4).
- The **writers tab could not be diffed**. Lovable exposes no URL for it, and the capture
  harness has no click step. INK's skrywers panel was captured separately for the primitive
  register only (`ontdek-skrywers@*`, INK side).
- Logged-in states are not covered. They add two surface rows and change the header.
- Search-results and empty states are not captured. Both need a query or a filter with no
  results.

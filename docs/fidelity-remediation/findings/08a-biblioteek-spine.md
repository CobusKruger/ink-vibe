# 08a · biblioteek — spine + structural findings

## 1. Header

| | |
|---|---|
| Page slug | `biblioteek` (inventory row 08a) |
| Lovable | `/library` → `src/pages/Library.tsx` (served from an isolated build on `:4188`) |
| INK | `https://nuwe-ink.local/biblioteek/` |
| Date | 2026-10-13 |
| Phase | 0–4b (Stage 1 audit) |
| Treatment | **A** · Reference diff. One Lovable page is the reference for two INK pages; row 08b (`opleiding`) is audited against the same reference and the same audit-ids. |

**Pairing verified (Phase 0, `agent-brief.md` §3.3).** The running site serves `/biblioteek/` as a
**post-type archive**: its `<body>` carries `archive post-type-archive post-type-archive-biblioteek_item`
(cache-busted `curl`, 2026-10-13). The page is therefore `templates/archive-biblioteek_item.html`, not a
WP page on `page.html`. The `page-map.csv` target `page-biblioteek` does not exist.

Header and footer are out of scope. They were verified on row 00 and are not re-reported here.

## 2. Files read end to end

**Lovable**
- `src/App.tsx` (route `/library` → `Library`, line 35)
- `src/pages/Library.tsx` — the page, including its local `ArticleCard` component
- `src/components/ui/input.tsx`, `badge.tsx`, `card.tsx`, `button.tsx` — the shadcn primitives it uses
- (`components/layout/Header.tsx` and `Footer.tsx` are imported but out of scope: row 00)

**INK resolution chain (§3)**
1. `wp-content/themes/ink-foundation/templates/archive-biblioteek_item.html`
2. `parts/header.html`, `parts/footer.html` (one-liners forwarding to row 00's patterns; out of scope)
3. `patterns/biblioteek.php`
4. `ink/biblioteek-argief` → `wp-content/plugins/ink-core/src/Library/Archive.php`
   - its shared primitives `wp-content/plugins/ink-core/src/Kernel/ArchiveRender.php` (`pill()`, `pagination()`)
- `wp-content/themes/ink-foundation/functions.php` (all 2311 lines: no page-scoped stylesheet or
  enqueue exists for this page; the QA-fixture include filter for this block is gated to `/qa-bloks/`
  at lines 1344-1347)
- `theme.json` → `styles.css`, extracted and read for every `.ink-biblioteek*` rule (no CSS comments
  exist in that string, so there are no in-CSS disclosures to check)
- Live markup: the served `<main>` of `/biblioteek/` (cache-busted), and a read-only query of
  `wpjj_posts`. It shows 3 published `biblioteek_item` posts, all titled `QA FIXTURE — …`.

## 3. Section spine

| # | Lovable section (Library.tsx) | INK counterpart | Status |
|---|---|---|---|
| 1 | Intro band: eyebrow, h1, lede, search (`:199-227`) | `div.ink-biblioteek__intro`: h1 + search form (`Archive.php:487-490`) | **present, partial** (eyebrow + lede missing) |
| 2 | Editor's shelf, 3 featured cards (`:231-248`) | `div.ink-biblioteek__uitgelig` "Uitgelig" (`Archive.php:537-553`) | present in code. **Not rendered live** (§6) |
| 3 | Categories + count + grid (`:251-303`) | `div.ink-biblioteek__filter` + `ul.ink-biblioteek__list` + pager (`Archive.php:512-524, 594-621`) | present. Grid **not rendered live**, count **missing** |
| 3e | Empty state (`:272-288`, only when a filter matches nothing) | `div.ink-biblioteek__leeg` (`Archive.php:494-510`) | present, partial. **This is what INK renders live** |
| 4 | Closing CTA band "Have something to share?" (`:305-319`) | — | **missing** |
| — | — | Pattern shell `section` › `div.alignwide` › `section.ink-biblioteek` (`biblioteek.php:23-25`, `Archive.php:507/512`) | **extra** wrappers (B-1) |

## 4. Structural findings

| id | class | finding | Lovable | INK |
|---|---|---|---|---|
| B-1 | STRUCTURE | Lovable has four sibling full-width `<section>`s, each with its own `.container`. INK nests everything in one pattern shell `<section>` › `alignwide` `<div>` › block `<section>`, so no section is full-bleed. | `Library.tsx:199,231,251,305` | `patterns/biblioteek.php:23,25`; `Archive.php:507,512` |
| B-2 | STRUCTURE | The intro is a full-bleed band (`border-b bg-cream/40`) in Lovable. INK's intro is a `<div>` inside the content column with a bottom rule and no tint. | `Library.tsx:199` | `Archive.php:487` |
| B-3 | MISSING | Eyebrow row (Library icon + "The Learning Library" label). INK's intro has no eyebrow. INK's h1 instead carries the bare section label (`Terms::label('biblioteek')`) where Lovable puts an editorial headline. That is copy, so it is owner territory (R2). | `Library.tsx:202-205` | `Archive.php:487-490` |
| B-4 | MISSING | Lede paragraph under the h1. | `Library.tsx:209-213` | `Archive.php:487-490` |
| B-5 | EXTRA | Search has a "Soek" submit `<button>`. Lovable filters live, client-side, with no button. Probably `SCOPE` (AD-7 server-rendered GET search needs a submit). **Owner to confirm.** | `Library.tsx:216-224` | `Archive.php:579` |
| B-6 | MECHANISM | The search icon sits in an absolutely-positioned `<span>`. Lovable positions the `<svg>` itself. | `Library.tsx:217` | `Archive.php:574` |
| B-7 | MECHANISM | The search container is a GET `<form role=search>`, not a `<div>` (server round-trip vs React state). | `Library.tsx:216` | `Archive.php:572` |
| B-8 | MISSING | Shelf subtitle ("Three pieces to begin with."). The biblioteek shelf has an h2 only. | `Library.tsx:238-240` | `Archive.php:542-544` |
| B-9 | MECHANISM | Shelf header row wrapper (`flex items-end justify-between`) absent. The h2 is a direct child of the shelf. | `Library.tsx:233` | `Archive.php:543` |
| B-10 | MECHANISM | Shelf and grid are `<ul>/<li>` with `repeat(auto-fit,minmax(…))`, where Lovable has a `<div>` grid with breakpoint column counts (`md:grid-cols-3`; `md:grid-cols-2 lg:grid-cols-3`). The computed outcome is **unmeasured**, because neither grid renders on INK (§6). | `Library.tsx:243,299` | `Archive.php:544,512` |
| B-11 | MISSING | Category icons on the filter chips. The INK chips are text-only. | `Library.tsx:267` | `Kernel/ArchiveRender.php:46-52` via `Archive.php:599-619` |
| B-12 | MECHANISM | Filter chips are navigation `<a>` links (query-var reload). Lovable's are `<button>` state toggles. | `Library.tsx:256-270` | `ArchiveRender.php:49-51` |
| B-13 | SCOPE | Category set: Lovable has 8 mock categories. INK lists "Alles" plus the live `genre` terms in use (Digkuns, Fiksie, Opstelle on this site). | `Library.tsx:32-41` | `Archive.php:441-469` |
| B-14 | MISSING | Result-count line ("16 articles…"). Biblioteek goes straight from the filter row to the list. Row 08b's renderer has one. | `Library.tsx:291-295` | `Archive.php:512` |
| B-15 | STRUCTURE | The card title is an `<a>`, not an `<h3>` (no heading element in the card). | `Library.tsx:358` | `Archive.php:686` |
| B-16 | MECHANISM | Card link model: INK has a real title link plus a decorative `aria-hidden` "Lees" `<a>`. Lovable's `<Card>` has no href and its "Read" is a `<span>`. | `Library.tsx:343,366` | `Archive.php:686,690` |
| B-17 | MISSING | The author line lacks the "by …" prefix. Adding it is a copy addition, so owner (R2). | `Library.tsx:365` | `Archive.php:689` |
| B-18 | DIVERGENCE (accepted) | Cards carry a 16:9 cover image when the item has a thumbnail. Lovable cards have none. Disclosed in `Archive.php:33-40` and `:347-362` (page-map "image crop parity" note). This is an implementer disclosure, not an owner ruling: **owner to confirm**. | — | `Archive.php:657-659` |
| B-19 | SCOPE | Prev/next pager (12 per page). Lovable lists everything. | — | `Archive.php:524`; `ArchiveRender.php:66-92` |
| B-20 | MISSING | Empty-state help text ("Try a different search or browse all articles."). INK's empty state has a title only. | `Library.tsx:277-279` | `Archive.php:507-509` |
| B-21 | STRUCTURE | Empty-state clear control: Lovable always shows a `<Button variant="literary-outline">`. INK shows an `<a>` only when a genre/search is active. | `Library.tsx:280-288` | `Archive.php:498-505` |
| B-22 | MISSING | Closing CTA band (h2, text, "Submit a piece" button). `patterns/biblioteek.php` embeds only the archive block. Row 08b's pattern adds `ink/opleiding-bydra` for this. | `Library.tsx:305-319` | `patterns/biblioteek.php:26` |

Notes:
- **B-5** is filed `EXTRA` rather than `SCOPE` because no owner ruling records it. The likely
  outcome is `SCOPE`.
- **B-13/B-19** are `SCOPE`: INK serves real taxonomy and real paging.
- **B-18 (R6):** the disclosure exists, so this is not raised as `EXTRA`. It was written by an
  implementer, though, like row 04's S-2, so the owner should ratify it.
- **Copy (R2), recorded and not classified:** the shelf h2 reads "Uitgelig" (Lovable "Editor's shelf"; row 08b
  uses "Die redakteur se rak"). The h1 reads "Biblioteek". The empty title reads "Geen Biblioteek gevind nie.".
  Changing any of them is a copy operation.

## 5. Join-key coverage

`maps/biblioteek.csv` — 59 ids:
- **51 Lovable ids**, assigned once in `Library.tsx` and shared with row 08b.
- **34 paired.** Only **7 pair in the rendered capture**: `library-intro`, `-h1`, `-search`,
  `-search-icon`, `-search-input`, `-filters`, `-filter`. The other 27 pairs are emitted by
  `Archive.php` but not rendered: 24 shelf/grid/card ids (no non-fixture items), plus 3
  empty-state ids that Lovable does not render in its default state.
- **17 missing-ink**, **7 missing-lovable** (shell, archive root, icon wrap, submit button, cover
  images), **1 scope** (pager).

Repeaters are tagged on the first instance only: first featured card, first grid card, first
filter chip. The card and chip ids are emitted by `Library\Archive` and `Training\Hub` (row 08b)
with the same names. `Kernel\ArchiveRender` was **not** modified. The first chip is stamped from
`Archive::filterHtml()`. The same ids also render on `/qa-bloks/` (row 21, out of scope), which
embeds both archive blocks.

## 6. Conclusion and limits

The biblioteek page carries the Lovable page's **first and third sections only**: intro and
filters/grid. It has a reduced intro (no eyebrow, no lede), no result count, and no closing CTA.
Everything sits inside one constrained shell instead of full-bleed bands. Its card is
structurally close to Lovable's `ArticleCard`, plus a cover image, and its filter chips match in
shape but drop the icons.

**Limits, stated plainly:**
- **The live page shows its empty state.** The only published `biblioteek_item` posts are
  `QA FIXTURE — ` titled, and `Archive::runQuery()` (`Archive.php:287-306`) excludes them
  everywhere except `/qa-bloks/`. The featured shelf, the card grid and every card id are
  therefore **unmeasured**. Their drift and primitive values are absent from Phase 4/4b. Closing
  this needs real (non-fixture) content or an owner-approved capture route. No data was written
  (brief).
- The Lovable empty state, filtered states and hover states (card lift, "Read" reveal, chip
  hover) were not captured.
- This document makes **no claim about computed values** (R4). Those are in `08a-biblioteek-drift*.md`,
  which were generated from captures that passed the determinism gate (3/3 identical) and `verify --sample 30`
  (0 mismatches) at 390, 768 and 1440 on both sides.

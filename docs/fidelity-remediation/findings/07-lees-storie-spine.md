# 07 · lees-storie — Spine and structural findings

| | |
|---|---|
| Page slug | `lees-storie` (inventory row 07, "stories and articles") |
| Lovable | `/read/sample` → `ReadStory.tsx`. `getWork("sample")` is undefined, so the page falls back to `s3`, "The Lighthouse Keeper's Daughter", a Short Story (`ReadStory.tsx:71`, `data/works.ts:170-183`). The prose branch (`isPoetry === false`) is the one audited |
| INK | `https://nuwe-ink.local/storie/die-wenteltrap-na-die-lig/` (post 67907) → `single-storie.html` → `reading-storie.php` |
| Date | 2026-10-13 |
| Phase | Stage 1, Phases 0–4b |
| Treatment | **A** · reference diff |

Phase 0: the pairing is ratified in `agent-brief.md` §2.1 row 07. Reading both sides confirms
it. `ReadStory.tsx` is the only Lovable reading route, and its only branch is `isPoetry`.
Short Story and Article take the identical prose branch.

Header and footer were verified on row 00 and are out of scope here. Their ids appear in the
generated drift docs only because the diff tool does not exclude chrome.

---

## 1. Files read end to end

**Lovable** (`/Users/cobus/Development/ink-lovable`)

- `src/App.tsx` (route table, `/read/:id` → `ReadStory`, line 32)
- `src/pages/ReadStory.tsx`
- `src/components/reading/HighlightableText.tsx`
- `src/components/reading/HighlightsPanel.tsx`
- `src/components/reading/PoetryReader.tsx` (imported; not rendered on the prose branch)
- `src/components/ui/button.tsx`, `src/components/ui/textarea.tsx`
- `src/lib/readerStore.ts`
- `src/data/works.ts`
- `src/index.css` (`.prose-literary` block, lines 146–164: read for the drop-cap pseudo-element only)
- `Header.tsx` / `Footer.tsx`: out of scope (row 00)

**INK** (`/Users/cobus/Development/ink-vibe/wp-content`). Resolution chain per README §3:

1. Template: `themes/ink-foundation/templates/single-storie.html` (plus `single-artikel.html`, read to scope artikel)
2. Parts: `parts/header.html`, `parts/footer.html` (one-line forwards to row-00 patterns)
3. Pattern: `patterns/reading-storie.php` (plus `patterns/reading-artikel.php`, read to scope artikel)
4. Dynamic blocks, each class read in full:
   - `plugins/ink-core/src/Engagement/ReactionTotals.php` (`ink/reaksie-tellers`)
   - `plugins/ink-core/src/Engagement/ResponseCount.php` (`ink/kommentaar-telling`)
   - `plugins/ink-core/src/Engagement/ReadingListToggle.php` (`ink/leeslys-knoppie`)
   - `plugins/ink-core/src/Social/ReadingAuthorCard.php` (`ink/outeur-kaart`)
   - `plugins/ink-core/src/Social/FollowToggle.php` (`toHtml`, embedded by the author card)
   - `plugins/ink-core/src/Engagement/ResponsesList.php` (`ink/gemeenskapsreaksies`)
   - `plugins/ink-core/src/Engagement/SuggestedReads.php` (`ink/verwante-stukke`)
   - `plugins/ink-core/src/Training/RelatedTraining.php` (`ink/opleiding-verwant`)
- `themes/ink-foundation/functions.php` (all 2 311 lines as first read: enqueues, the `render_block_core/*` audit-id filters, block styles)
- `themes/ink-foundation/assets/js/highlightable-text.js` (builds the highlights tab, panel and tooltip at runtime)
- `themes/ink-foundation/assets/css/reading.css`
- `themes/ink-foundation/theme.json`: the `styles.css` string was extracted to `tmp/lees-storie/theme-css.css` and the reading rules read there. Also read: `styles.elements` and `styles.typography`
- The rendered page itself: `tmp/lees-storie/page.html`, a cache-busted curl. Everything in `<body>` outside header, `<main>` and footer was inspected, to establish what is injected outside the pattern chain. That is only scripts plus the JS-built highlights UI

---

## 2. Section spine

| # | Lovable section (`ReadStory.tsx`) | INK counterpart | Status |
|---|---|---|---|
| 0 | `HighlightsPanel`: fixed tab + off-canvas panel (`:128-135`, prose only) | `highlightable-text.js:259-303`, JS-built onto `<body>` | present (MECHANISM, S-9) |
| 1 | Story header band (`:139-189`): badge, h1, byline, hint pill | `reading-storie.php:72-102` header `<section>` | present |
| 2 | Body `<article>` (`:192-208`): `.prose-literary` + `HighlightableText` | `reading-storie.php:104-108` section + `core/post-content` | present |
| 3 | Floating action bar (`:211-240`): like · comments · bookmark · share | `reading-storie.php:110-118` `.ink-reaksie-bar` | present (share MISSING, S-8) |
| 4 | Author section (`:243-267`) | `reading-storie.php:120-128` band + `ink/outeur-kaart` | present |
| 5 | Critiques section (`:270-358`): h2, compose card, critique list | `reading-storie.php:130-142` → `ink/gemeenskapsreaksies` | present |
| — | — | `ink/verwante-stukke`, `ink/opleiding-verwant` (`reading-storie.php:136-138`) | **extra** (renders nothing on this post, S-19) |

Every Lovable section has an INK counterpart, in the same order.

---

## 3. Structural findings

| id | class | finding | Lovable | INK |
|---|---|---|---|---|
| S-1 | MECHANISM | Intro spacing. Lovable stacks per-element margins (badge `mb-4`, h1 `mb-4`, hint `mt-6`). INK uses one `blockGap` (s-12) on `.ink-lees-intro`, plus a `!important` top margin on `.ink-storie-hint` | `ReadStory.tsx:142,148,172` | `reading-storie.php:74`; theme.json `.ink-storie-hint` |
| S-2 | STRUCTURE | Type badge element: Lovable `<span>` pill, INK `<p>` with `.ink-lees-tipe` | `ReadStory.tsx:142` | `reading-storie.php:77` |
| S-3 | STRUCTURE | Story header band is a `<div>` on Lovable and a `<section>` on INK. The body is an `<article>` on Lovable and a `<section>` on INK | `ReadStory.tsx:139,192` | `reading-storie.php:73,105` |
| S-4 | STRUCTURE | Byline author: Lovable has **one** `<Link>` wrapping the avatar and name. INK renders **two** links (`core/avatar` isLink + `core/post-author-name` isLink, the name inside a `<div>`). The pattern docblock (`:50-52`) calls this an "approximation" | `ReadStory.tsx:156-163` | `reading-storie.php:84-86` |
| S-5 | MISSING | Read-time meta (clock icon + "12 min read") has no INK counterpart. No disclosure comment found | `ReadStory.tsx:166-169` | — (`reading-storie.php:82-94` read) |
| S-6 | EXTRA | INK shows the publish date (`core/post-date`) in the slot where Lovable shows read time. Possibly SCOPE: owner decision | — | `reading-storie.php:92` |
| S-7 | STRUCTURE | Floating bar: Lovable uses 3 nodes (sticky full-width wrapper › container › `max-w-md` pill). INK uses one node: `.ink-reaksie-bar` is both the sticky element and the pill. The pre-existing `storie-engagement-bar` id therefore pairs Lovable's *wrapper* with INK's *pill*, so the bar's drift rows compare different boxes | `ReadStory.tsx:211-213` | `reading-storie.php:111` |
| S-8 | MISSING | Share button (Share2 icon). No disclosure found | `ReadStory.tsx:235-237` | — (`reading-storie.php:110-118` has 3 blocks) |
| S-9 | MECHANISM | Highlights UI: Lovable renders it as a React component outside `<main>`, and the backdrop exists only when the panel is open. INK builds the tab, panel and an always-present `display:none` backdrop in JS on `DOMContentLoaded`. Both are closed at rest | `HighlightsPanel.tsx:23-95` | `highlightable-text.js:259-303` |
| S-10 | SCOPE | Follow button: Lovable always renders it (mock local state). INK renders `FollowToggle` only for a logged-in viewer who is not the author, so it is absent from the anonymous capture | `ReadStory.tsx:257-259` | `ReadingAuthorCard.php:81-87,116-118` |
| S-11 | STRUCTURE | Author card wrappers: INK adds a constrained group `<div>`, a nested `<section class="ink-outeur-kaart">` and a `__foto-omhulsel` `<div>` around the avatar. Lovable's `<img>` is a direct flex child | `ReadStory.tsx:244-251` | `reading-storie.php:122-126`; `ReadingAuthorCard.php:115-116` |
| S-12 | STRUCTURE | Responses nesting: INK has a `<section>` (pattern) › constrained `<div>` › `<section id="kommentaar">`, so two sections. Lovable has `<section>` › container › `max-w-2xl` `<div>` | `ReadStory.tsx:270-276` | `reading-storie.php:131-134`; `ResponsesList.php:140` |
| S-13 | STRUCTURE | Response card: Lovable has card `<div>` › flex-row `<div>` › avatar + body. INK's `<li>` is itself the flex row, one wrapper fewer | `ReadStory.tsx:324-325` | `ResponsesList.php:155` |
| S-14 | STRUCTURE | Response meta order: Lovable has author › type badge › time (`ml-auto`). INK has `__who` [badge › author] › date (`space-between`). The badge and author are reversed, and INK adds a wrapper | `ReadStory.tsx:328-345` | `ResponsesList.php:166-171` |
| S-15 | DIVERGENCE (disclosed) | Upvote: Lovable has a `<button>` with ChevronUp + count. INK has an inert `<span>` with an always-0 count and no icon. The docblock discloses the inert, always-zero read as deferred reaction-system work. It does **not** explain the missing icon | `ReadStory.tsx:349-352` | `ResponsesList.php:175`; disclosure `:38-43` |
| S-16 | DIVERGENCE (disclosed) | Reply action removed on INK. The disclosure is an implementer comment ("matching Lovable's fidelity does not extend to reproducing its dead controls"), not a recorded owner ruling. Owner to confirm | `ReadStory.tsx:353-355` | `ResponsesList.php:59-65` |
| S-17 | MECHANISM | Response-type chips: Lovable uses toggling `<button>`s in React state. INK uses a `<label>` wrapping a visually-hidden radio, with selection styled through `:has(input:checked)` | `ReadStory.tsx:285-298` | `ResponsesList.php:276-284`; theme.json `.ink-reaksies__type*` |
| S-18 | MECHANISM | Compose card: Lovable `<div>` with child margins (`mb-4`). INK `<form>` with flex-column `gap` (s-12) | `ReadStory.tsx:281-314` | `ResponsesList.php:272-295` |
| S-19 | EXTRA | `ink/verwante-stukke` + `ink/opleiding-verwant` follow the responses. They render empty here (no shared taxonomy term), so they were neither visible nor captured. On a tagged post they would add two INK-only sections | — | `reading-storie.php:136-138`; `SuggestedReads.php:186-204`; `RelatedTraining.php:227-243` |
| S-20 | SCOPE | Repeater data: Lovable shows 3 hard-coded critiques (insight/praise/suggestion) and 5 English paragraphs. INK shows 1 real response (lof) and 3 Afrikaans paragraphs. Heights and the first-badge colour differ for data reasons | `ReadStory.tsx:28-59`; `works.ts:22-30` | post 67907 content |
| S-21 | SCOPE | Bare URLs in prose are auto-linked on INK (`.ink-prose-link`). Disclosed as a product-owner-requested feature with no Lovable counterpart. Not exercised by this post | — | `functions.php:383-475` (disclosure + filter) |

### Notes on classification

- **S-7** is a STRUCTURE finding, not a MECHANISM one, because it breaks the join. The
  pre-existing id joins two boxes that are not the same visual element, so every
  `storie-engagement-bar` drift row compares a 1440px-wide transparent wrapper with a
  fit-content pill. The Lovable pill now carries `reading-engagement-pill`
  (`missing-ink`) so that Stage 4 can see the pill's own box.
- **S-15 / S-16** are recorded as disclosed divergences under R6, not as findings to fix.
  Both disclosures are implementer prose, not owner rulings, so both are listed for owner
  confirmation. Row 04's S-2 was handled the same way.
- **S-5 / S-6**: no disclosure comment mentions read time. The comments searched were in
  `reading-*.php`, `reading.css`, the Engagement classes and `ReadingAuthorCard.php`.
- **Drop cap.** Both sides set a drop cap through `::first-letter`: Lovable's
  `.prose-literary p:first-of-type`, INK's `.ink-lees-storie__prose>p:first-child`. The
  harness does not capture pseudo-elements, so whether the two render alike is unmeasured.

---

## 4. Join-key coverage

`maps/lees-storie.csv` has 61 rows: **51 paired**, 5 Lovable-only (`missing-ink`),
2 INK-only (`missing-lovable`), 2 `scope` and 1 `divergence`. At all three viewports the
drift docs report 82 paired ids. That figure includes the row-00 header and footer ids.

Ids that existed before this row were reused unchanged: `storie-badge`, `storie-title`,
`storie-hint`, `storie-paragraph`, `storie-highlight`, `storie-engagement-bar`,
`storie-reaksie-tellers` (INK only, now added to Lovable), `storie-author-name` and
`storie-author-bio`.

New ids follow two naming rules:
- **`reading-*`** for elements of the shared `ReadStory.tsx` code: header, byline, body,
  floating bar, author section and responses. These are generic, so row 22 (lees-gedig)
  reuses them.
- **`storie-*`** only where the element exists on the prose branch alone: `storie-prose`,
  `storie-highlights-toggle` and `storie-highlights-panel`. This follows the existing
  `storie-paragraph` / `storie-highlight` precedent.

Coverage gaps:
- **Not captured:** `storie-highlight` exists only after a text selection plus confirm.
  That is not a click-only state.
- **Not reachable anonymously on INK:** the follow button (S-10).
- **Icons:** only the hint and like icons are tagged.
- **Unmeasured:** the critique list's inter-item spacing, because only the first repeater
  instance carries ids.

---

## 5. Conclusion and limits

The page's sections are all present and in order. Its structural divergence lies inside
those sections:

- **missing elements:** the read time (S-5) and the share button (S-8);
- **one substituted element:** the date (S-6);
- **join mismatch:** the floating-bar node structure breaks the join (S-7);
- **extra wrappers and reordering in the responses block:** S-11 to S-14;
- **two disclosed omissions:** upvote (S-15) and reply (S-16).

Most of the remaining divergence is computed-value drift. That drift is recorded in the
generated `07-lees-storie-drift*.md` files and not repeated here.

This document makes **no claim about computed values** (R4).

**What it cannot support:**

- **Artikel.** `reading-artikel.php` has the same block shape as `reading-storie.php` and
  was read end to end, but artikel was not captured. These findings carry over to artikel
  structurally, with three known differences:
  - The badge colour is `muted-text`, a documented product-owner divergence
    (`reading-artikel.php:45-54`).
  - The artikel pattern carries none of this row's new pattern-level ids:
    `reading-header`, `reading-header-inner`, `reading-byline`, `reading-byline-sep`,
    `reading-hint-icon`, `reading-body`, `reading-author-band` and `reading-responses`.
  - `ReactionTotals::enkelAuditId()` and `ReadingAuthorCard::auditIdPrefix()` return
    `null` on artikel. As a result, `storie-reaksie-tellers`, `storie-author-name` and
    `storie-author-bio` are absent there.

  The generic ink-core ids and the byline and prose filters do fire on artikel. A
  separate capture is needed before any artikel drift claim.
- **Lees-gedig.** Row 22 is out of scope. Poetry's own elements were not audited.
- **Interactive and logged-in states:** the open highlights panel, the tooltip, the
  selected chip, the liked and saved states, the logged-in follow button and the
  non-empty related sections were not captured.

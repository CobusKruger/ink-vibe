# 22 · lees-gedig — Spine and structural findings

| | |
|---|---|
| Page slug | `lees-gedig` |
| Lovable | `/read/s2` ("Morning Coffee, Midnight Thoughts", `src/data/works.ts:153-168`), served from an isolated build on `:4202` |
| INK | `https://nuwe-ink.local/gedig/piet-punte/` |
| Date | 2026-10-13 |
| Phase | 2 (spine + structural findings). Phases 3, 4 and 4b are complete; their outputs are listed in §5 and §6 |
| Treatment | **A**, reference diff |

The header and footer are out of scope. Row 00 verified them. They appear in the generated
drift files only because `run.sh diff` has no chrome filter. Ignore every `header-*` and
`footer-*` row there.

---

## 1. Files read end to end

**Lovable**

- `src/App.tsx` (the route: `/read/:id` → `ReadStory`, line 32)
- `src/pages/ReadStory.tsx` (whole file, 372 lines). The `isPoetry` branches are the reference for this row
- `src/components/reading/PoetryReader.tsx` (whole file)
- `src/data/works.ts` (whole file; `s2` is at 153-168)
- `src/components/ui/button.tsx`, `src/components/ui/textarea.tsx`
- `src/components/reading/HighlightsPanel.tsx` and `HighlightableText.tsx` were not read for this row. Both render on the prose branch only (`ReadStory.tsx:128`, `:196-206`), so no claim here rests on them.

**INK — the resolution chain (README §3)**

1. Template: `templates/single-gedig.html`
2. Parts: `header` / `footer` (row 00 scope, not re-read)
3. Pattern: `patterns/reading-gedig.php` (whole file). `patterns/reading-storie.php` was read too, for the shared shape row 07 established
4. Dynamic blocks, each class read in full:
   - `ink/gedig-body` → `ink-core/src/Engagement/GedigBody.php`
   - `ink/reaksie-tellers` → `Engagement/ReactionTotals.php`
   - `ink/kommentaar-telling` → `Engagement/ResponseCount.php`
   - `ink/leeslys-knoppie` → `Engagement/ReadingListToggle.php`
   - `ink/outeur-kaart` → `Social/ReadingAuthorCard.php`
   - `ink/gemeenskapsreaksies` → `Engagement/ResponsesList.php`
   - `ink/verwante-stukke` → `Engagement/SuggestedReads.php`
   - `ink/opleiding-verwant` → `Training/RelatedTraining.php`

**INK — outside the chain**

- `functions.php` (whole file, 2354 lines): the reading-page enqueues, the audit-id render filters at 221-317, and the block styles
- `assets/css/reading.css` (whole file)
- `assets/js/line-reactions.js` (whole file). It builds the per-line hearts in the DOM
- `theme.json` `styles.css`: extracted and searched for every class this page emits (Phase 4b)
- The live rendered `<main>` of the INK page, fetched before and after Phase 3

---

## 2. Section spine

| # | Lovable section (`ReadStory.tsx`) | INK counterpart (`reading-gedig.php`) | Status |
|---|---|---|---|
| 1 | Poem header band `:139-190` (badge, italic title, byline, hint pill) | header `<section>` `:84-114` | present |
| 2 | Body `<article>` `:193-209` → `PoetryReader` (dedication, stanzas, per-line hearts) | body `<section>` `:116-124` → `ink/gedig-body` | present (no dedication) |
| 3 | Floating action bar `:212-243` (like, comments, bookmark, share) | `.ink-reaksie-bar` `:126-134` | present (no share) |
| 4 | Author section `:246-271` | `.ink-outeur-kaart-band` `:136-144` → `ink/outeur-kaart` | present |
| 5 | Responses `:274-364` (h2, compose card, 3 critiques) | `:146-158` → `ink/gemeenskapsreaksies` | present (list empty: no data) |
| — | — | `ink/verwante-stukke`, `ink/opleiding-verwant` (`:152-154`) | **extra** (both rendered empty on this post) |
| — | `HighlightsPanel` (prose only, `:128`) | none | correctly absent on both sides |

All five reference sections are present. Neither side has a missing or extra section on the
rendered page. The two INK-only blocks emit nothing for this poem.

---

## 3. Structural findings

| id | class | finding | Lovable | INK |
|---|---|---|---|---|
| S-1 | DIVERGENCE (accepted) | The hint copy reads "Merk hierdie reël" rather than a translation of "Tap any line that resonates with you" | `ReadStory.tsx:179` | `reading-gedig.php:109`, disclosed at `:28-30` |
| S-2 | STRUCTURE | Byline author: Lovable wraps avatar and name in one `<Link>`; INK renders two separate links (`core/avatar` + `core/post-author-name`) | `ReadStory.tsx:156-164` | `reading-gedig.php:96-98`, disclosed approximation at `:64-69` |
| S-3 | MISSING / EXTRA | Lovable shows read time (clock + "2 min read"); INK shows the publish date in that slot | `ReadStory.tsx:166-169` | `reading-gedig.php:104` |
| S-4 | MISSING | The dedication line ("— for the hour before anyone is awake —") has no INK counterpart. INK has no dedication field in its content model; a search of `ink-core/src` found none (located by grep, then confirmed by reading `GedigBody.php`) | `PoetryReader.tsx:26-30` | — |
| S-5 | STRUCTURE | Stanza wrapper: Lovable nests stanzas in a `space-y-10 md:space-y-12` stack. INK puts `.ink-gedig__stanza` and `.ink-gedig__sep` spacer divs directly in `.ink-gedig` and builds the gaps from blank-line separator elements | `PoetryReader.tsx:31-33` | `GedigBody.php:321-366` |
| S-6 | MECHANISM | Line element: each Lovable line is a full-width `<button>` with the heart svg inside its text span. Each INK line is a `<p data-ink-line>`, and `line-reactions.js` appends a separate `<button class="ink-gedig__react">` into the text span after load | `PoetryReader.tsx:49-70` | `GedigBody.php:385-387`, `line-reactions.js:73-126` |
| S-7 | DIVERGENCE (accepted) — owner to confirm | At rest INK fills the heart and highlights the line for every line that **any** reader has reacted to (`reactedLines`, aggregate). Lovable's resonance is session-local, so every heart is hidden (opacity 0) and no line is tinted. On this poem 5 of 10 lines render active, including line 1 | `PoetryReader.tsx:13, 53-67` | `functions.php:583-588, 614-616`; `line-reactions.js:92-95` |
| S-8 | MISSING (state) | The "You marked N lines as resonant" summary has no INK counterpart. It appears only after an interaction, so it was not captured | `PoetryReader.tsx:76-80` | — |
| S-9 | MECHANISM | Body column: Lovable `article > .container > .max-w-xl` (576px). INK `section > group (768px) > .ink-gedig` (576px via the group's `contentSize`), so the 576px box sits one level deeper | `ReadStory.tsx:193-195` | `reading-gedig.php:117-119` |
| S-10 | STRUCTURE | Floating bar: Lovable has a sticky full-width wrapper plus an inner pill. INK folds both into one `.ink-reaksie-bar` node (same as row 07) | `ReadStory.tsx:212-214` | `reading-gedig.php:127` |
| S-11 | MISSING | Share button (same as row 07) | `ReadStory.tsx:238-240` | — |
| S-12 | SCOPE | Author bio: INK omits the `<p>` when the author has no bio; this author has none. Lovable falls back to a stock sentence | `ReadStory.tsx:257-259` | `ReadingAuthorCard.php:120-122` |
| S-13 | SCOPE | Follow button: INK renders it only for a logged-in non-author, so it was not captured (same as row 07) | `ReadStory.tsx:261-263` | `ReadingAuthorCard.php:125-127` |
| S-14 | SCOPE (copy) | Lovable branches the responses copy on `isPoetry`: the heading "Responses to this poem", the intro, chip labels such as "A line that struck me", and the textarea placeholder. INK renders one prose-oriented set for every type ("Gemeenskapsreaksies", "Jou gedagtes oor hierdie stuk..."). Changing this is a copy decision (R2), not a fidelity fix | `ReadStory.tsx:111-121, 278, 283-285, 308` | `ResponsesList.php:140-141, 274, 288` |
| S-15 | STRUCTURE | Response card: Lovable has an inner flex-row wrapper; INK's `<li>` is the flex row (same as row 07). No INK instance renders on this page | `ReadStory.tsx:325` | `ResponsesList.php:155` |
| S-16 | DIVERGENCE (accepted) | The Reply action was removed on INK; the disclosure is the same as row 07 | `ReadStory.tsx:353-355` | `ResponsesList.php:59-65` |
| S-17 | MECHANISM | Response-type chips: Lovable `<button>`; INK `<label>` wrapping a visually hidden radio | `ReadStory.tsx:290-302` | `ResponsesList.php:278-283` |
| S-18 | EXTRA / SCOPE | `ink/verwante-stukke` and `ink/opleiding-verwant` have no Lovable counterpart. Both render empty on this post | — | `reading-gedig.php:152-154` |

### Notes on classification

- **S-7** is the most visible difference on this page. Unlike Lovable, half the poem renders
  highlighted with red hearts before the visitor does anything. It is recorded as an
  accepted divergence because `functions.php:583-588` documents the persistent aggregate
  state as deliberate ("so the persisted filled-heart state renders on page load for every
  visitor"). The owner should confirm that this is the intended reading experience, because
  in practice it reads as a stranger's highlights.
- **Data, not drift.** The s2 sample has 5 stanzas of 2–4 lines; Piet Punte has 10
  single-line stanzas. Stanza heights, and the INK response list being empty against
  Lovable's 3 mock critiques, are content differences. The `reading-response-*` item ids are
  marked `scope` in the map because row 07 already pairs them.
- **Phase 3 cleanup.** Earlier passes had stamped `gedig-stanza-line` on **every** line on
  both sides (`GedigBody.php`, `PoetryReader.tsx`). That is the T5 duplicate-id hazard.
  Phase 3 reduced it to the first instance only.

---

## 4. Join-key coverage

The map is `maps/lees-gedig.csv`, with 63 rows: 42 paired, 12 scope, 7 missing-ink, 1 missing-lovable, 1 divergence. These new ids were added, Lovable first:
`gedig-body-col`, `gedig-poem`, `gedig-dedication`, `gedig-stanzas`, `gedig-stanza`,
`gedig-line-text`, `gedig-line-heart`, plus `reading-hint-icon` on the poetry Heart. These
row 07 generic ids were installed on INK's gedig pattern: `reading-header`,
`reading-header-inner`, `reading-byline`, `reading-byline-sep`, `reading-hint-icon`,
`reading-body`, `reading-author-band`, `reading-responses`. INK's bar carries
`gedig-engagement-bar`.

- **Paired at 1440/768/390:** 42 page ids (73 including chrome). No capture has a duplicated audit-id.
- **Lovable-only (20):** `reading-byline-author` (S-2), `reading-byline-readtime` (S-3), `gedig-dedication` (S-4), `gedig-stanzas` (S-5), `reading-engagement-pill` (S-10), `reading-share-btn` (S-11), `gedig-author-bio` (S-12), `reading-author-follow` (S-13), and 12 `reading-response-*` item ids (data).
- **INK-only (1):** `reading-byline-date` (S-3).

---

## 5. Phase 4 and 4b outputs

- `findings/22-lees-gedig-drift.md` (1440), `-drift@768.md`, `-drift@390.md`. These are generated files.
  - Gates: 6/6 determinism (3 runs, identical) and 6/6 `verify --sample 30` passed.
- `maps/_primitive-observations.lees-gedig.csv` and `findings/22-lees-gedig-primitives.md`.

---

## 6. Conclusion and limits

**What the divergence consists of.** Structurally the page is complete: all five reference
sections are present and in order. The poem-specific gaps are:

- the missing dedication (S-4, which needs a content-model decision);
- a flatter stanza structure (S-5, S-6);
- the read-time / date swap shared with row 07;
- the missing share control;
- one behavioural difference that dominates the first impression: pre-filled aggregate
  resonance (S-7).

The responses section carries no poetry-specific copy (S-14). That is an owner and copy call.

**Limits.**

- This document makes no claim about computed values. Those live only in the generated
  drift files.
- Only the resting state is captured:
  - Lovable's hover heart reveal (`group-hover:opacity-60`) cannot be driven by `--click`.
  - The resonance summary (S-8) needs an interaction.
  - A click would write a reaction on INK, so it was not performed.
- Logged-out only. The Follow button and the logged-in bar states were not captured.
- INK's response-card ids could not be measured on this post because it has no responses
  (row 07 covers them).
- Lovable's roman-numeral stanza marker and INK's `.ink-gedig__line--marker` exist in both
  sources but not in either sample poem. They are unmeasured.

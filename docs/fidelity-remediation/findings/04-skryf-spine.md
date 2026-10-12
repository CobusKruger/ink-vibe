# 04 · skryf — spine and structural findings

| | |
|---|---|
| Page slug | `skryf` (inventory row 04) |
| Lovable | `/write` → `src/pages/Write.tsx` (served from an isolated build on `:4184`) |
| INK | `https://nuwe-ink.local/skryf/` → `templates/page-skryf.html` |
| Date | 2026-10-12/13 |
| Phases | 0–4b (this document is the Phase 2 output, updated with Phase 3 join-key coverage) |
| Treatment | **A** · reference diff |

**Pairing (Phase 0): confirmed.** Both are the "share your words" submission page. Both
have a type choice (poem/story/article ↔ gedig/storie/artikel), an optional
active-challenges checklist, a title field, a body editor with a live counter,
draft/publish actions and a post-publish success screen.

**Header and footer** were verified on row 00 and are out of scope here. The INK capture is
authenticated (see S-1), so the INK header is in its logged-in branch. Both headers
and footers were removed from the Phase 4 diff (§5).

---

## 1. Files read end to end

**Lovable**

- `src/App.tsx` (route table, `/write` → `Write`)
- `src/pages/Write.tsx` (433 lines: the editor view and the success view)
- `src/components/ui/button.tsx`, `textarea.tsx`, `input.tsx`, `label.tsx`, `checkbox.tsx`
  (every shadcn primitive `Write.tsx` imports)
- `Header.tsx` / `Footer.tsx`: not re-read. They are covered by row 00.

**INK resolution chain (§3)**

1. Template: `wp-content/themes/ink-foundation/templates/page-skryf.html` (9 lines). It holds
   the header part, a `<main>` group containing `wp:pattern ink-foundation/skryf`, and
   the footer part. The parts are row 00's.
2. Pattern: `wp-content/themes/ink-foundation/patterns/skryf.php` (225 lines). All of the
   page's DOM is emitted here. There are **no `wp:ink/*` dynamic blocks** on this page.
3. Bridges, all in `functions.php`: `ink_foundation_skryf_model()` (:1947),
   `ink_foundation_skryf_form_fields()` (:1967), `ink_foundation_skryf_success()` (:1997),
   `ink_foundation_skryf_denial()` (:2019), `ink_foundation_is_member_logged_in()`
   (:2188) and `ink_foundation_icon()` (:2271). The asset enqueue is at
   `functions.php:80-102`.
4. `ink-core`: `wp-content/plugins/ink-core/src/Submission/Api.php` (126 lines:
   `formModel()`, `successModel()`, `denialMessage()`). It supplies data only, never markup.
5. Presentation: `assets/css/skryf.css` (578 lines) and `assets/js/skryf-counter.js`
   (103 lines). Both are enqueued only when `is_page('skryf')`.
6. `theme.json`: `styles.elements` (h1, heading, button) and the `styles.css` string,
   which was extracted and searched; it has no `skryf` rule.

**Live markup.** I fetched the served HTML twice with a cache-busting `curl`: once
anonymous and once with the `fidelity-capture` session. It is consistent with the
pattern as read.

---

## 2. Section spine

Lovable `/write` is a **two-step client-side flow**. Step 1 is the type picker; clicking a
card swaps in the editor. A third view, the success screen, replaces both after publish.
INK renders one server-side form holding every field at once, with a native radio
fieldset as the type picker. The spine below is ordered by Lovable source.

| # | Lovable section (Write.tsx) | INK counterpart (skryf.php) | Status |
|---|---|---|---|
| 1 | Intro: h1 + lede in a centred wrapper (256-263) | h1 + lede, no wrapper (113-119) | present (wrapper missing, S-3) |
| 2 | Step 1 · type picker grid, 3 cards (265-298) | `fieldset.ink-skryf-types`, 3 radio cards (133-158) | present (mechanism differs, S-2) |
| 3 | Step 2 · selected-type indicator + "Change type" (302-329) | — | divergence (S-2) |
| 4 | Step 2 · active-challenges panel (332-372) | `fieldset.ink-skryf-challenges` (165-187) | present |
| 5 | Step 2 · title field (375-384) | `p.ink-skryf-field` title (189-192) | present |
| 6 | Step 2 · body field + counter (387-405) | `div.ink-skryf-field--body` (194-198) | present |
| — | — | featured-image and audio/video pickers (200-208) | extra (SCOPE, S-12) |
| 7 | Step 2 · actions: Save Draft / Publish (407-422) | `p.ink-skryf-actions` (216-219) | present |
| 8 | Success · banner card (157-178) | `.ink-skryf-success` group (58-84) | present (not captured) |
| 9 | Success · engage prompt: eyebrow/h2/text (181-195) | eyebrow/h2/p as section siblings (86-96) | present (wrapper missing, S-14) |
| 10 | Success · 3-card community-works grid (197-241) | — (flagged NOT BUILT, 98-110) | **missing** (S-4) |
| — | — | anonymous sign-in notice (121-124) | extra (SCOPE, S-1) |
| — | — | entitlement denial notice (126-130) | extra (SCOPE, conditional) |

---

## 3. Structural findings

| id | class | finding | Lovable | INK |
|---|---|---|---|---|
| S-1 | `SCOPE` | INK renders the form only for a logged-in user. An anonymous visitor gets the h1, the lede and a "Teken in om werk te plaas." paragraph. Lovable renders the editor for everyone. | `Write.tsx:251-429` (no auth gate) | `skryf.php:18, 121-124` |
| S-2 | `DIVERGENCE (accepted)` | Two-step picker→editor flow with a selected-type indicator and a "Change type" button, versus one form with a radio fieldset. INK type cards are `<label>`s wrapping radios, and gedig is pre-checked so card 1 renders in its selected state. Lovable cards are `<button>`s with no selected state. | `Write.tsx:265-329, 272-279` | `skryf.php:132-158, 144-145`; disclosed `assets/css/skryf.css:66-72` (selected state `:120-123`) |
| S-3 | `STRUCTURE` | The intro wrapper is missing. Lovable groups h1 + lede in a centred `div.mb-8`; INK's h1 and p are direct section children. | `Write.tsx:256-263` | `skryf.php:113-119` |
| S-4 | `MISSING` | The success screen's "Lift another writer today" 3-card community-works grid. INK flags it **NOT BUILT** pending an owner decision on the selection rule. This is a disclosure of an open gap, not an acceptance. | `Write.tsx:197-241` | `skryf.php:98-110` |
| S-5 | `EXTRA` | A visible type-picker legend, "Kies 'n tipe". Lovable's grid has no heading. | `Write.tsx:266` | `skryf.php:134` |
| S-6 | `STRUCTURE` | The type-card label is an `<h2>` in Lovable and a `<span>` in INK. | `Write.tsx:289` | `skryf.php:149` |
| S-7 | `STRUCTURE` | Challenges panel shape. Lovable has a header row (icon + a column holding the title `<p>` with a muted `(optional)` `<span>`, then the hint) and a `space-y-2` list wrapper. INK has a `<legend>` holding icon + text with "(opsioneel)" inline (no element), a sibling hint `<p>`, and rows as direct fieldset children. | `Write.tsx:333-344` | `skryf.php:168-184` |
| S-8 | `MECHANISM` | The challenge checkbox is a Radix `<button role="checkbox">` in Lovable and a native `<input type="checkbox">` in INK. The same split applies to the type radio (S-2). | `Write.tsx:358-363`, `ui/checkbox.tsx:11-21` | `skryf.php:180` |
| S-9 | `MISSING` | The per-challenge deadline line ("Ends …") and the title/deadline column. INK rows carry the title only, and `Api::formModel()` supplies only `id`/`title` per open challenge, so this needs data as well as markup. | `Write.tsx:364-367` | `skryf.php:181`; `Submission/Api.php:43` (return shape) |
| S-10 | `STRUCTURE` | The body field's label + counter sit in their own row in Lovable. INK flex-wraps label, counter and textarea in one container. | `Write.tsx:388-397` | `skryf.php:194-197` |
| S-11 | `MISSING` | The body label is per-type in Lovable ("Your {Poem/Short Story/Article}"). INK's is static ("Jou werk"). INK does already swap the per-type *placeholder* (`skryf-counter.js:74-81`). Adding a per-type label touches Afrikaans copy, so it is an owner decision (R2). | `Write.tsx:389-391` | `skryf.php:195` |
| S-12 | `SCOPE` | Featured-image and audio/video file pickers (Stories 6.4/6.5). Lovable has none. | — | `skryf.php:200-208` |
| S-13 | `MISSING` | The leading icons on the actions: Save (draft) and Send (publish). INK's buttons are text-only. | `Write.tsx:409, 419` | `skryf.php:217-218` |
| S-14 | `STRUCTURE` | Success engage-prompt wrapper. Lovable nests eyebrow/h2/text in a wrapper and inner column; INK emits them as section-level siblings. The eyebrow is a `<span>` in Lovable and a `<p>` in INK. | `Write.tsx:181-195` | `skryf.php:86-96` |
| S-15 | `MECHANISM` | "Write another piece" is a `<button>` that resets client state in Lovable and an `<a href="/skryf">` in INK. | `Write.tsx:170` | `skryf.php:75` |
| S-16 | `MISSING` | The Feather icon on "Write another piece". | `Write.tsx:171` | `skryf.php:75` |

### Notes on classification

- **S-2: why DIVERGENCE (accepted) and not STRUCTURE.** `skryf.css:66-72` is an
  in-place disclosure: "deliberately NOT reproducing the two-phase JS flow (higher risk,
  no functional upside for a short form)". Under R6 that stops the work here. The
  disclosure reads as an implementer's choice, not a recorded owner ruling. **The owner
  should confirm it.** S-2 accounts for six Lovable ids that have no INK counterpart
  (`skryf-type-indicator*`, `skryf-change-type-btn`), and for the selected-state
  border/shadow on `skryf-type-card` in the drift.
- **S-4 is MISSING, not DIVERGENCE.** The disclosure at `skryf.php:98-110` explicitly asks
  for an owner decision rather than recording one.
- **Data-driven differences are not findings.** INK shows the open challenges from the
  database (one at capture time); Lovable shows four static ones. Panel heights differ
  for that reason alone.
- **Outside the taxonomy, recorded for the owner and not fixed:** the storie and artikel
  type descriptions render a **literal backslash**: "Skep \’n vertelling…" and "Deel \’n
  essay…". `skryf.php:23-24` writes `'Skep \&#8217;n …'` inside a single-quoted PHP
  string, where `\&` is not an escape, so the backslash survives into the served HTML
  (confirmed by `curl`). It is a copy-rendering bug in Afrikaans copy, so it is the owner's call (R2).
- **The success view's container differs at source level.** Lovable switches to
  `max-w-5xl` for the success view (`Write.tsx:155`, versus `max-w-4xl` at :255). INK
  uses the same 896px section for both. No computed claim is made: the success state was
  not captured.

---

## 4. Join-key coverage (Phase 3)

`maps/skryf.csv` lists 62 ids:

| status | count |
|---|---|
| `paired` | 36 |
| `missing-ink` | 13 |
| `divergence` | 5 |
| `scope` | 6 |
| `missing-lovable` | 2 |

What each capture can pair:

| capture | Lovable state | paired ids | Lovable-only | INK-only |
|---|---|---|---|---|
| `skryf@{390,768,1440}` (required drift docs) | step 1, the type picker (initial render) | 9 | 1 | 23 |
| `skryf-editor@{390,768,1440}` (supplementary) | step 2, the editor, after clicking card 1 (Poem ↔ INK's default gedig) | 21 | 14 | 11 |

- **Nine ids pre-dated this audit** in `skryf.php`: `skryf-type-card`, `skryf-checkbox`,
  `skryf-title-input`, `skryf-body-textarea`, `skryf-draft-btn`, `skryf-publish-btn`,
  `skryf-success-heading`, `skryf-success-write-another` and `skryf-success-back-home`.
  All were reused unchanged. The two repeaters
  (`skryf-type-card`, `skryf-checkbox`) were previously stamped on *every* instance. They
  are now stamped on the first only, per the row 00 convention, because `diff.mjs` keys by
  id and would otherwise pair Lovable's first card with INK's last.
- **Success state: 12 pairs exist in source but are not captured on either side.** INK
  would need a real submission (forbidden), and Lovable would need typing plus submit.
- **No id on the sparkles and success-eyebrow icons.** On INK these are emitted by
  `ink_foundation_icon()`, which has no attribute hook, so no id was put on either side.

---

## 5. Conclusion and limits

**What the divergence consists of.** One accepted mechanism divergence dominates the
page: a single form against a two-step flow (S-2). Beyond it there are:

- one missing section: the success screen's community-works grid (S-4);
- a cluster of wrapper and shape differences in the challenges panel, body field, intro
  and success prompt (S-3, S-7, S-10, S-14);
- small missing elements: the deadline line, button icons and the per-type body label
  (S-9, S-11, S-13, S-16).

INK carries more than the mockup in two places, both SCOPE: the login gate and the file
pickers.

**What this document cannot support.** It makes no claim about any computed value:
size, colour, spacing or type. Those are in the generated drift documents
(`04-skryf-drift*.md`). Three limits apply to those documents:

- **Two Lovable states.** Lovable's initial render shows only the type picker. The
  editor-state diffs (`04-skryf-drift-editor*.md`) come from a capture taken after one
  scripted click on card 1. The click comes from scratch harness variants in
  `tmp/fidelity-skryf/` (`capture-x.mjs` / `verify-x.mjs`); both state captures passed
  the 3-run determinism gate and a 30-node verify.
- **INK is authenticated.** It was captured as `fidelity-capture`, and the header and
  footer ids were unkeyed before diffing (`*.page-only.ndjson`).
- **Success state not measured.** No computed claim is made about the success screen.

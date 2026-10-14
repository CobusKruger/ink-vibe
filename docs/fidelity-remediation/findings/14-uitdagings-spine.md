# 14 · uitdagings — Spine and structural findings

| | |
|---|---|
| Page slug | `uitdagings` (the challenge **list**) |
| INK | **https://nuwe-ink.local/uitdaging/** (`templates/archive-uitdaging.html`). The inventory URL `/uitdagings/` does not serve this page (S-U1) |
| Lovable | **no page.** The referenced region is the challenge card of `ChallengeSection.tsx`, rendered on the Lovable homepage `/` (`src/pages/Index.tsx:15`), captured from http://localhost:4193/ and scoped to `[data-audit-id="challenge-card"]` |
| Date | 2026-10-13 |
| Phases | 0–4b (Stage 1) |
| Treatment | **B (hybrid)** |

**A/C boundary.**
- **Treatment A**, diffed against Lovable: the challenge card. On INK that is the **first**
  `li.ink-uitdagings__item` of the list (`Archive.php:323`); repeaters are instrumented on
  their first instance only.
- **Treatment C**, conformance only: the archive section band (`uitdaging.php:22-23`), the
  bordered intro, the `Uitdagings` h1, and the grid `<ul>` that holds the cards
  (`Archive.php:356-368`). Its result is in `14-uitdagings-conformance.md`.
- Lovable's `challenge` band, its grid and its winner card are homepage composition. They
  belong to row 00 and are **not** part of this region.
- Header and footer belong to row 00.

---

## 0. Phase 0 — pairing issue (needs owner confirmation)

**S-U1 · live bug: `/uitdagings/` does not reach the list.** `curl -I https://nuwe-ink.local/uitdagings/`
returns a `301` (`x-redirect-by: WordPress`) to `/skryfwerk/uitdagings-stil-geen-hongerte/`, an
unrelated post. WordPress guessed it from the slug because no page exists at that path. The
`uitdaging` CPT archive lives at `/uitdaging/` (`ink-core/src/Content/PostTypes.php:190-197`,
`archive`/`rewrite` = `uitdaging`). The site nevertheless links to `/uitdagings` from:
- the header nav (built by `ink-core/src/Migration/NavigationRebuilder.php:121`; audit-id
  stamping keyed on it at `functions.php:410`);
- *Meer oor INK* (`patterns/oor-ink.php:109`);
- the QA winner fixture (`functions.php:1228`).

So the header's *Uitdagings* item leads readers to the wrong page.

I captured `/uitdaging/`. The decision rests on `agent-brief.md` §2.2, which names
`archive-uitdaging.html` + `uitdaging.php` as this row's code, and that template renders only
at `/uitdaging/`. **Owner:** confirm the pairing, and decide whether the CPT archive slug or
the links change. `tools/fidelity/targets.json:106` still says `/uitdagings/`; I did not edit
it.

## 1. Files read end to end

**Lovable**
- `src/pages/Index.tsx` — `ChallengeSection` renders on `/` (line 15)
- `src/components/home/ChallengeSection.tsx` (93 lines; the card is lines 10-45)
- `src/components/ui/button.tsx` (`variant="literary"`, default size)

**INK — resolution chain**
1. `templates/archive-uitdaging.html`
2. Header/footer parts: row 00 scope
3. `patterns/uitdaging.php`
4. Dynamic block `ink/uitdaging-argief` → `ink-core/src/Challenges/Archive.php` (the whole class)
- `ink-core/src/Content/PostTypes.php:190-197` (the archive slug, for S-U1)
- Style sources, for Phase 4b attribution only: `theme.json` `styles.css` (extracted)
- The live HTML of `/uitdaging/` and the `/uitdagings/` redirect headers (curl)

## 2. Section spine (Treatment A region: the card)

| # | Lovable card (`ChallengeSection.tsx`) | INK first card (`Archive.php`) | Status |
|---|---|---|---|
| A1 | card `div.bg-card.rounded-2xl.p-8…` (:10) | `li.ink-uitdagings__item` (:323) | present |
| A2 | corner tint (:11) | — | **missing** |
| A3 | head row: icon tile + month eyebrow (:13-18) | `div.__item-meta`: tema pill + countdown pill (:295-305) | present, different content |
| A4 | trophy icon tile (:14) | — | **missing** |
| A5 | month eyebrow "January Challenge" (:17) | — | **missing** |
| — | — | tema pill (:298), countdown/status pill (:302) | **extra** |
| A6 | title `<h2>` (:20) | `a.__titel` (:325) | present (no heading element) |
| A7 | description `<p>` (:24) | — | **missing** |
| A8 | meta row: date + entry count (:30-39) | `div.__item-voet`: date row + "Lees meer" (:311-320) | present, partial |
| A9 | meta item 1, calendar + "Ends Jan 31" (:31) | `span.sluitingsdatum-ry` (:312) | present |
| A10 | meta item 2, users + "234 Entries" (:35) | — | **missing** |
| A11 | literary `<Button>` "Submit Your Entry" (:41) | `a.__lees` "Lees meer", hover-reveal (:317) | present, different control |

The Treatment C remainder has no spine. In page order: `uitdagings` (band), then
`uitdagings-intro` and `uitdagings-h1`, then `uitdagings-list`, which contains **[A region ×N]**.

## 3. Structural findings — Treatment A region

| id | class | finding | Lovable | INK |
|---|---|---|---|---|
| S-U1 | live bug / Phase 0 | `/uitdagings/` 301s to an unrelated post; see §0 | — | `PostTypes.php:195-197`, `NavigationRebuilder.php:121`, `oor-ink.php:109` |
| S-U2 | MECHANISM | The card is an `<li>` in a grid `<ul>`. Lovable's is a standalone `<div>` | `:10` | `Archive.php:323,368` |
| S-U3 | MISSING | Corner tint (`absolute w-32 h-32 bg-terracotta/5 rounded-bl-full`) | `:11` | — |
| S-U4 | MISSING | Trophy icon tile (`p-2 bg-terracotta/10 rounded-lg`) | `:14-16` | — |
| S-U5 | MISSING | Month eyebrow (`text-sm font-medium text-terracotta`). INK has no per-challenge month label on the card | `:17` | — |
| S-U6 | SCOPE | The head row holds the INK-only data **tema** and a **countdown** pill ("Nog N dae" / "Sluit vandag" / "Gesluit"). The pill classes are shared with the single page (row 05) on purpose; this is disclosed in `Archive.php:265-272` | `:13` | `Archive.php:295-305` |
| S-U7 | STRUCTURE | The title is an `<a>` with no heading element. Lovable uses an `<h2>`. The cards have no heading semantics | `:20` | `Archive.php:325` |
| S-U8 | MISSING | Description. The card renders no excerpt or prompt text; `render()` passes no excerpt | `:24-28` | `Archive.php:235-246` |
| S-U9 | STRUCTURE | The meta row is a bordered **footer** (`margin-top:auto`, `border-top`) that also holds the CTA. Lovable has a plain row above the button | `:30` | `Archive.php:311-320` |
| S-U10 | MISSING | The second meta item, the entry count (`Users` + "234 Entries"). INK does not show an entry count on the list | `:35-38` | — |
| S-U11 | STRUCTURE | The CTA is different. Lovable shows a filled literary primary button, "Submit Your Entry". INK shows a "Lees meer" text link that is `aria-hidden`, `tabindex=-1`, revealed on hover or focus-within, and duplicates the title link. Its rationale comes from an implementer comment, `Archive.php:259-264` ("archive-card language … hover-reveal 'Lees meer' affordance"), not from an owner ruling. **Owner call** | `:41-44` | `Archive.php:317-319` |
| S-U12 | SCOPE | The context is different. The reference is the homepage's single **feature** card in a 2-column band. INK lays out N cards in an `auto-fit minmax(280px,1fr)` grid. With one live challenge, the single card stretches to the full 1318px row (measured) | `:8-10` | `uitdaging.php:24-26`, `Archive.php:368` |

**Not findings.** The copy differs ("Sluitingsdatum: 31 Oktober 2026", "Lees meer"); R2 applies.
The homepage's own challenge card is `CurrentChallenge` (kenmerk). It is a different renderer
for the same Lovable element, and row 00 owns it.

**R6 check.** `Archive.php:257-273` explains the card recipe (Hub/Library archive-card language,
reuse of the row 05 pill and date classes). That is an implementer rationale, not a ruling
against the Lovable card. So S-U3/4/5/8/10/11 stand as findings, and S-U11 is escalated.

**Not captured.** The closed state (`is-gesluit`), "Sluit vandag", pagination (a single page),
the empty state (`__leeg`), and hover/focus (`translateY(-2px)`, `shadow-lg`, the "Lees meer"
reveal). The live list holds **one** published, open challenge; the three `QA FIXTURE` ones are
excluded by design (`Archive.php:62-74`).

### Treatment C remainder — structural notes

| id | class | note | INK |
|---|---|---|---|
| S-UC1 | STRUCTURE | The h1 lives inside the block's bordered intro. It steps xxxxl → xxxxxl → xxxxxxl (36/48/60px), which is larger than any Lovable page h1 measured so far. That is a recipe question for Stage 4 | `Archive.php:356-358` |
| S-UC2 | STRUCTURE | The section band has only 16px of top padding (`s-16`) above the intro | `uitdaging.php:22` |

## 4. Join-key coverage

The full table is in `maps/uitdagings.csv`.
- **A region: 10 Lovable ids, 6 paired** at all three viewports: `challenge-card`, `-head`,
  `-title`, `-meta`, `-meta-item`, `-btn`.
  - Missing on INK: `-corner`, `-icon`, `-eyebrow`, `-desc`.
  - INK-only: `challenge-card-theme-pill` and `challenge-card-status-pill`. They are new ids in
    the row 00 namespace (status `missing-lovable`).
- These ids are **reused from row 00**. On the homepage they are emitted by `CurrentChallenge`
  (kenmerk). Here `Archive::cardHtml()` emits them on the first card only, through a new
  `audit` key that `render()` sets. `Archive.php` never renders on the homepage, and the
  homepage was re-checked after deploy: no duplicates. `/qa-bloks/` embeds both blocks
  (`qa-bloks.php:175`), so ids repeat there; it is out of the inventory and `--scope` is the
  mechanism.
- **Lovable:** no edits. Row 00 already instrumented every card element. The second meta item
  has no id (first instance only).
- **C region:** 4 page-prefixed ids: `uitdagings`, `-intro`, `-h1`, `-list`.

## 5. Conclusion and limits

The INK list card and the Lovable feature card share only a skeleton: a surface, a title, a
date row and a CTA. Five Lovable elements have no INK counterpart (S-U3/4/5/8/10). The CTA is
a different control (S-U11). The card's own recipe is a lighter "archive card" (8px radius,
24px padding, `shadow.sm`) rather than Lovable's feature card (16px radius, 32px padding,
`shadow.md`). Part of this is genuine scope: a list card cannot be a single feature card. The
owner needs to decide how far the list card should follow the reference (S-U11, S-U12) before
Stage 4 touches it. The page-level defect that matters most today is **S-U1**: the nav does
not reach this page at all.

This document makes no computed-value claim on its own; §6 summarises the Phase 4 output.

## 6. Phase 4 / 4b summary (measured)

The gates all passed. Determinism: 3 runs for each of the 9 captures (Lovable scoped ×3, INK
full ×3, INK scoped ×3). `verify --sample 30` passed on all of them. The **INK capture is
scoped** to `[data-audit-id="challenge-card"]` (`--scope-label challenge`) for the region diff.

Drift (`14-uitdagings-drift.md`, plus `@768` and `@390`): 6 paired ids and 81, 78 and 78
deltas. There is one systemic delta: `box-sizing` is content-box against border-box on 6/6
nodes. The top items:
1. **Card surface.** The radius is 8px against 16px; the padding is 24px against 32px; the
   shadow is `shadow.sm` (`0 2px 12px -2px /.06`) against Lovable `shadow-soft`, which is
   `shadow.md` (`0 4px 20px -4px /.08`). The card is a flex column with a 12px gap, where
   Lovable uses block flow with per-element margins. Its width is 1318px against 668px @1440
   (S-U12).
2. **Title.** 20px/28px against 30px/36px at ≥768, and 24px/32px against 20px/28px @390; the
   margin-bottom is 0 against 12px.
3. **CTA.** A transparent text link at opacity 0 (Inter 14px, primary colour, no padding,
   radius 0), against a filled terracotta button (Lora 14px, white, padding 8px/16px, radius
   6px, `shadow.md`).
4. **Meta row.** It has a border-top (1px) plus 16px of padding-top and space-between, where
   Lovable's row has none. Its colour is `ink-text` against muted; its font size is 16px
   against 14px (the item itself is 14px on both sides); the item gap is 8px against 6px.

The OFF-TOKEN check on the C remainder found **0** off-token values; see the conformance doc.
Inside the A region, 10 informational off-token literals exist (line-heights 1.4 and 1.429,
pill paddings 10px and 2px).

The primitives, `14-uitdagings-primitives.md`, show a third card variant (`.ink-uitdagings__item`)
and two pill paddings inside one card. No class is inert: every definition is site-wide.

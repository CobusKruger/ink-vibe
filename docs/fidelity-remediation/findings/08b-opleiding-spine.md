# 08b · opleiding — spine + structural findings

## 1. Header

| | |
|---|---|
| Page slug | `opleiding` (inventory row 08b) |
| Lovable | `/library` → `src/pages/Library.tsx` (served from an isolated build on `:4188`) |
| INK | `https://nuwe-ink.local/opleiding/` |
| Date | 2026-10-13 |
| Phase | 0–4b (Stage 1 audit) |
| Treatment | **A** · Reference diff. The same Lovable page and the same audit-ids as row 08a (`biblioteek`). |

**Pairing verified (Phase 0, `agent-brief.md` §3.3).** `/opleiding/` is served as a **post-type
archive**: its `<body>` carries `archive post-type-archive post-type-archive-opleiding_artikel`
(cache-busted `curl`, 2026-10-13). The template is `templates/archive-opleiding_artikel.html`. The
`page-map.csv` target `page-opleiding` does not exist. The header nav's "Library" link pairs with
`/opleiding` (`functions.php:366`, row 00).

Header and footer are out of scope. They were verified on row 00 and are not re-reported here.

## 2. Files read end to end

**Lovable**
- `src/App.tsx` (route `/library` → `Library`, line 35)
- `src/pages/Library.tsx` — the page, including its local `ArticleCard` component
- `src/components/ui/input.tsx`, `badge.tsx`, `card.tsx`, `button.tsx`

**INK resolution chain (§3)**
1. `wp-content/themes/ink-foundation/templates/archive-opleiding_artikel.html`
2. `parts/header.html`, `parts/footer.html` (out of scope, row 00)
3. `patterns/opleiding.php`
4. `ink/opleiding-argief` → `wp-content/plugins/ink-core/src/Training/Hub.php`;
   `ink/opleiding-bydra` → `wp-content/plugins/ink-core/src/Training/ContributionCta.php`;
   shared primitives `wp-content/plugins/ink-core/src/Kernel/ArchiveRender.php`
- `wp-content/themes/ink-foundation/functions.php` (all 2311 lines: no page-scoped stylesheet; the
  fixture-include filter for this block is gated to `/qa-bloks/`, lines 1320-1323)
- `theme.json` → `styles.css`, extracted and read for every `.ink-opleiding*` rule (no CSS comments)
- Live markup: the served `<main>` of `/opleiding/` (cache-busted), and a read-only `wpjj_posts` query.
  It shows 3 published `opleiding_artikel` posts, all `QA FIXTURE — ` titled.

## 3. Section spine

| # | Lovable section (Library.tsx) | INK counterpart | Status |
|---|---|---|---|
| 1 | Intro band: eyebrow, h1, lede, search (`:199-227`) | `div.ink-opleiding__intro` (`Hub.php:486-492`) | **present** |
| 2 | Editor's shelf (`:231-248`) | `div.ink-opleiding__rak` "Die redakteur se rak" (`Hub.php:587-604`) | present in code. **Not rendered live** (§6) |
| 3 | Categories + count + grid (`:251-303`) | `div.ink-opleiding__filter` + `p.__telling` + `ul.__list` + pager (`Hub.php:503-521, 645-672`) | present. Count + grid **not rendered live** |
| 3e | Empty state (`:272-288`) | `p/div.ink-opleiding__leeg` (`Hub.php:677-688`) | present, partial. **What INK renders live** (`Hub.php:686-697`) |
| 4 | Closing CTA band (`:305-319`) | `aside.ink-opleiding-bydra` (`ContributionCta.php:103-111`) | **present** (different shape, O-17) |
| — | — | Pattern shell `section` › `div.alignwide` › `section.ink-opleiding` (`opleiding.php:23-25`, `Hub.php:499/508`) | **extra** wrappers (O-1) |

## 4. Structural findings

| id | class | finding | Lovable | INK |
|---|---|---|---|---|
| O-1 | STRUCTURE | Lovable has four sibling full-width `<section>`s with their own containers. INK nests the hub and the CTA in one pattern shell `<section>` › `alignwide` `<div>`, so none is full-bleed. | `Library.tsx:199,231,251,305` | `patterns/opleiding.php:23,25,26,28`; `Hub.php:499,508` |
| O-2 | STRUCTURE | The intro is a full-bleed tinted band (`border-b bg-cream/40`) in Lovable. INK's intro is a `<div>` inside the content column with a bottom rule and no tint. | `Library.tsx:199` | `Hub.php:486` |
| O-3 | STRUCTURE | The eyebrow is a `<p>` (icon + `<span>`), where Lovable has a `<div>`. | `Library.tsx:202` | `Hub.php:487` |
| O-4 | EXTRA | "Soek" submit `<button>`. Lovable filters live with no button. Probably `SCOPE` (AD-7). **Owner to confirm.** | `Library.tsx:216-224` | `Hub.php:630` |
| O-5 | MECHANISM | The search container is a GET `<form>`, and the icon sits in an absolutely-positioned `<span>`. Lovable uses a `<div>` with the `<svg>` itself positioned. | `Library.tsx:216-217` | `Hub.php:623-625` |
| O-6 | MECHANISM | Shelf header row wrapper absent. The h2 and subtitle are direct children of the shelf. | `Library.tsx:233` | `Hub.php:592-594` |
| O-7 | MISSING | Category icons on the filter chips. | `Library.tsx:267` | `Kernel/ArchiveRender.php:46-52` via `Hub.php:650-670` |
| O-8 | MECHANISM | Filter chips are navigation `<a>` links, where Lovable has `<button>` state toggles. | `Library.tsx:256-270` | `ArchiveRender.php:49-51` |
| O-9 | SCOPE | Category set: Lovable has 8 mock categories. INK lists "Alles" plus the live `vaardigheid` terms (Begin hier, Digkuns, Skryfkuns on this site). | `Library.tsx:32-41` | `Hub.php:329-357` |
| O-10 | MECHANISM | Shelf and grid are `<ul>/<li>` with `repeat(auto-fit,minmax(…))`, where Lovable has a `<div>` grid with breakpoint column counts. The computed outcome is **unmeasured**: neither renders on INK (§6). | `Library.tsx:243,299` | `Hub.php:595,509` |
| O-11 | STRUCTURE | The card title is an `<a>`, not an `<h3>`. | `Library.tsx:358` | `Hub.php:748` |
| O-12 | MECHANISM | Card link model: INK has a real title link plus a decorative `aria-hidden` "Lees" `<a>`. Lovable's `<Card>` has no href and "Read" is a `<span>`. | `Library.tsx:343,366` | `Hub.php:748,752` |
| O-13 | MISSING | The author line lacks the "by …" prefix. That is a copy addition, so owner (R2). | `Library.tsx:365` | `Hub.php:751` |
| O-14 | MECHANISM | No card body wrapper: the `<li>` carries the padding itself, where Lovable has `<Card>` › `<CardContent className="p-6">`. Row 08a's renderer keeps the wrapper (`.ink-biblioteek__item-inhoud`). | `Library.tsx:343-348` | `Hub.php:746` |
| O-15 | SCOPE | Prev/next pager (12 per page). Lovable lists everything. | — | `Hub.php:518-521` |
| O-16 | STRUCTURE | Empty state: Lovable always has a title, help text and a Clear-filters `<Button>`. INK unfiltered is a single `<p>` that is both the dashed box and the title text, with no help text and no control. INK filtered is a `<div>` with help text and a clear `<a>`, but no title. | `Library.tsx:272-288` | `Hub.php:686-697` |
| O-17 | STRUCTURE | Closing CTA: Lovable has a full-bleed tinted `<section>` with `border-t` and a container. INK has an `<aside>` inside the content column, with a top rule and no tint. | `Library.tsx:305-320` | `ContributionCta.php:104`; `patterns/opleiding.php:28` |
| O-18 | MECHANISM | CTA button: a bespoke `<a>` with no block style, where Lovable has `<Button variant="literary" size="lg" asChild>`. | `Library.tsx:315-317` | `ContributionCta.php:109` |

Notes:
- The INK intro reproduces Lovable's eyebrow, h1, lede and search order. `Hub.php:479-485`
  documents this deliberately (eyebrow label = `opleiding`, per the curated
  `docs/ui-copy-translations.md` mapping). No disclosure covers O-2's missing band tint or O-17's
  CTA shape. Under R6 the absence of a disclosure is checked before raising these.
- **O-4** is `EXTRA` pending an owner ruling (likely `SCOPE`).
- Copy (R2) is not classified. INK's shelf ("Die redakteur se rak" / "Drie stukke om mee te begin."),
  empty-state, count and CTA strings are the curated Afrikaans and were left untouched.

## 5. Join-key coverage

`maps/opleiding.csv` — 57 ids, all reusing the 51 Lovable ids from row 08a:
- **42 paired.** **15 pair in the rendered capture**: `library-intro`, `-eyebrow`, `-eyebrow-icon`,
  `-eyebrow-text`, `-h1`, `-lede`, `-search`, `-search-icon`, `-search-input`, `-filters`, `-filter`,
  `-cta`, `-cta-h2`, `-cta-text`, `-cta-btn`. The other 27 pairs are emitted but unrendered: 24
  shelf/count/grid/card ids (no non-fixture items), plus 3 empty-state ids that Lovable does not
  render in its default state.
- **9 missing-ink**, **5 missing-lovable**, **1 scope**.

Repeaters are tagged on the first instance only. `Training\Hub` and `Library\Archive` (row 08a)
emit the same id names. `Kernel\ArchiveRender` was not modified. `ContributionCta` renders only in
`patterns/opleiding.php`. The archive ids also render on `/qa-bloks/` (row 21, out of scope).

## 6. Conclusion and limits

Structurally, opleiding is the closer of the two INK pages to `/library`. All four Lovable sections
have a counterpart, and the intro carries eyebrow, headline, lede and search in Lovable's order.
The divergence is mostly composition and mechanism:
- the bands are not full-bleed and have no tint (O-1, O-2, O-17);
- filter chips are links without icons (O-7, O-8);
- the card has no heading element (O-11) and no body wrapper (O-14);
- the empty state is split across two shapes (O-16).

**Limits:**
- **The live page shows its empty state.** All published `opleiding_artikel` posts are
  `QA FIXTURE — ` titled and are excluded by `Hub::runQuery()` (`Hub.php:301-320`). The shelf, count
  and every card id are **unmeasured** in Phase 4/4b. No data was written (brief).
- Filtered/empty Lovable states, the INK filtered state and hover states were not captured.
- No computed-value claim is made here (R4). See `08b-opleiding-drift*.md`, generated from captures
  that passed determinism (3/3) and `verify --sample 30` (0 mismatches) at 390/768/1440, both sides.

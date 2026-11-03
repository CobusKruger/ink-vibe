# 07 Uitdagings: build spec (`/challenges` to `/uitdaging/`)

**Status:** built 2026-11-02 (reference `cbd9d3a`); the owner rulings win over this text. The date slot holds `countdownLabel()`, not a closing date; `UITDAGINGS-SIEN-WENNERS` was not needed. **Date:** 2026-11-01. **Reference:** verse-haven `3e75fb8`. **Map:** `../maps/uitdagings.csv` (replaces `fidelity-remediation/maps/uitdagings.csv` at build). **New gaps:** `RG-UI-01` to `-03`, pending ratification.

**Owner ruling (2026-11-01), overrides everything below on past rounds:** take the design's heading and featured current-round card. **Past rounds keep INK's existing standard cards** (`Archive.php`, `challenge-card*` ids, tema and status pills, hover "Lees meer", paging), not the design's rows. So §4 and the row, results-button and "announced" items in §1, §2 and §7 don't apply, `challenge-card*` isn't retired, `countdownLabel()` stays, and the card links to its round. The band and H2 above the cards stay. The cards exclude the current round. Results are reached through the banner on the round page (`01-uitslae.md` §12).
This spec holds no style values (R4, R5). A recipe is a join for the capture to check.

**Files read:** Lovable `src/pages/Challenges.tsx`, `src/App.tsx` routes, `ui/badge.tsx`, `ui/button.tsx`, `data/awards.ts` (`decemberResults`). INK `templates/archive-uitdaging.html`, `patterns/uitdaging.php`, `Challenges/{Archive,CurrentChallenge,Cadence}.php`, `Kernel/ArchiveRender.php`, `SinglePage.php` (entry query), `WinnersPost.php` (lookups), `theme.json` `styles.css` (parsed), `primitives.css` (classes cited), `tests/Unit/Challenges/{ArchiveTest,UitdagingTemplateTest}.php`.

## 1. Section spine

The page stays one block, `ink/uitdaging-argief`, through template and pattern. Only `Archive::render()` and `toHtml()` change shape.

| # | Lovable | INK | Change |
|---|---|---|---|
| Root | `:24` `uitdagings-main` | `<main>` in `archive-uitdaging.html` | Add the id |
| 1 | `:25` `uitdagings-hero` | Intro band in `Archive::toHtml()` | Rebuild: no tint, eyebrow and intro added |
| 2 | `:39` `uitdagings-current` | **None** | New: current-round card, page 1 only |
| 3 | `:64` `uitdagings-past` | Card grid in `Archive::toHtml()` | Replace: grid becomes rows |
| INK | none | `ArchiveRender::pagination()` | Keep (§3) |

Treatment moves from B to **A**: the whole page now has a reference.

**Header and footer:** no work here. INK's header (`header-main.php:76`) and footer (`footer-main.php:68`) already link to `/uitdaging/`. The footer stays deferred.

**Retire on build:**
- Ids `uitdagings` (pattern), `uitdagings-h1`, `uitdagings-list` and all `challenge-card*` in `Archive::cardHtml()`. **`uitdagings-intro` moves:** INK has it on the band, Lovable on the paragraph, so leaving it would pair the wrong nodes.
- In `theme.json`: every `.ink-uitdagings*` rule, and that selector in the lists shared with Opleiding and Biblioteek. Those two pages keep theirs. `.ink-uitdaging__*` belongs to the single page: leave it.
- `Archive::countdownLabel()` and its tests, if Q2 stands. `Archive::entryCount()` (§2).

## 2. Element table

Ids marked † don't exist in Lovable yet (§4).

| Audit id | Lovable | Recipe | Data | Copy |
|---|---|---|---|---|
| `uitdagings-hero` | :25 | `ink-band ink-band--step ink-band--border-b` | | |
| `uitdagings-hero-container` † | :26 | `ink-container`; width in sheet | | |
| `uitdagings-eyebrow` | :27 | `ink-eyebrow` | | `Maandelikse uitdagings` (`ui-copy:38`) |
| `uitdagings-title` | :30 | `ink-heading-h1-index` (same classes as `library-h1`) | | debt `UITDAGINGS-H1` |
| `uitdagings-intro` | :33 | Page text | | debt `UITDAGINGS-INTRO` |
| `uitdagings-current` | :39 | `ink-band ink-band--step` | Page 1 only; absent with no round | |
| `uitdagings-current-container` † | :40 | `ink-container`; width in sheet | | |
| `uitdagings-current-card` | :41 | **RECIPE-GAP RG-UI-01** | `CurrentChallenge::current()` | |
| `uitdagings-current-badge`, `-icon` † | :42 | **RECIPE-GAP RG-UI-02** | `is_open`; round month | `Oop` or `Gesluit` (Terms `uitdaging_oop`, `uitdaging_gesluit`) ` · {Maand jaar}` |
| `uitdagings-current-title` | :45 | `ink-heading-h2-section` | Theme, else title (`01` §4), in quotes per Lovable | Round's text |
| `uitdagings-current-description` | :48 | Page text | `excerpt` in the payload; absent if empty | Editor's text |
| `uitdagings-current-meta` | :51 | **RECIPE-GAP RG-UI-03** | Absent if both items are | |
| `uitdagings-current-meta-close` † | :52 | `ink-meta ink-meta--sm` | Month end, **open only** | `Sluit 31 Jan` (`ui-copy:77`) |
| `uitdagings-current-meta-entries` † | :53 | `ink-meta ink-meta--sm` | Per-type counts, as the hero (`03` §5) | `18 gedigte · 12 stories · 4 artikels`, zeros dropped (`ui-copy:78`) |
| `uitdagings-current-enter` | :56 | `ink-btn ink-btn--primary` | Round permalink | Open: `Skryf in` (terms Deel 2). Closed: `Lees meer` (`Archive.php:351`) |
| `uitdagings-past` | :64 | `ink-band ink-band--step ink-band--border-t` | Absent if page 1 has no rows | |
| `uitdagings-past-container` † | :65 | `ink-container`; width in sheet | | |
| `uitdagings-past-title` | :66 | `ink-heading-h2-section` | | debt `UITDAGINGS-VORIGE-H2` |
| `uitdagings-past-list` | :69 | Layout in sheet | `Archive::queryArgs()` | |
| `uitdagings-past-0` † | :71 | Layout in sheet | One row (§4) | |
| `uitdagings-past-0-meta` † | :73 | Page text | Round month; `SinglePage::entryCount()` | `{Maand jaar} · ` + `_n` string `CurrentChallenge.php:415`; drop the count at zero |
| `uitdagings-past-0-title` † | :74 | `ink-heading-h2-block` on `<h3>` | Theme, else title, in quotes; links to the round (Q3) | Round's text |
| `uitdagings-past-0-announced` † | :75 | Page text | Linked post's `post_date`, newest if several (`01` §4); absent with no post | `Uitslae aangekondig {datum}` (README N5) |
| `uitdagings-past-0-results`, `-icon` † | :77 | `ink-btn ink-btn--outline` | `/uitdaging/<slug>/uitslae/`, **only when results exist** | debt `UITDAGINGS-SIEN-WENNERS` |
| `uitdagings-pagination` | none | `ink-pager`, `ink-pager__link` | §3 | `Vorige`, `Volgende` (existing) |
| `uitdagings-empty` | none | Existing `__leeg` | No rounds, or a page past the end | `Geen Uitdagings gevind nie.` (existing) |

**Copy-debt keys**, each a `[NEEDS HUMAN AFRIKAANS]` marker, added to `afrikaans-translation-sheet.md` and `afrikaans-copy-worklist.md` at build: `UITDAGINGS-H1`, `UITDAGINGS-INTRO`, `UITDAGINGS-VORIGE-H2`, `UITDAGINGS-SIEN-WENNERS`. Searched `docs/`, `_bmad-output/`, patterns and ink-core for the English and likely Afrikaans; none exists. Today's h1 `Uitdagings` is kept as the pattern's `aria-label`.

**Data, all shared with other specs. Build none of it twice.**

| Value | Source | Status |
|---|---|---|
| Current round | `CurrentChallenge::current()`: open, else newest (`03` §2) | Fallback is `03`'s fix. Payload gains `id`, `is_open`, month. Call `current()` directly; `DATA_FILTER` is the home card's seam |
| Round closes | Month end through the one helper in `Cadence.php` (`03` §2) | From `03`. No stored deadline: 0 of 140 rounds have one, and the helper derives it |
| Round month | Title, verified against post date, same helper (`01` §4) | Replaces `monthLabel( post_date )`, which names the wrong month for rounds posted just before month-end. A title with no month or year (`ChallengeResultTitle::monthYear()` returns null) falls back to the post date, so no row is blank |
| Entries | `SinglePage::entryCount()`, the existing single source (fixtures excluded) | Replaces `Archive::entryCount()`, which reads the term count and so counts fixtures. One query per row, at most 12 |
| Results exist | `01` §4 rule, as **one** public function on `RoundResults` used by the endpoint's 404, the banner and this page | From `01`. 101 of 140 rounds have a link; the other rows show no button and no announced line |

Competitions aren't in this list: they're their own post type (`05`, D-ruling), so the query needs no cadence filter.

**Closed current round.** The design has only the open state; the owner's rule is no closing date when closed. Following from that: the badge says `Gesluit`, not `Oop`, and the button says `Lees meer`, because `Skryf in` on a frozen round is false. No results button on the card: the round page's banner (`01` §12) carries it, and the gap is short because results come out after the next round starts.

## 3. Past-round count: paging stays

**What `Archive.php` does today:** `PER_PAGE` = 12, var `uitdaging_bladsy`, `render()` reads the page through `ArchiveRender::requestInt()`, and `toHtml()` calls `ArchiveRender::pagination()`: Vorige and Volgende as `ink-pager` links, nothing with one page. `runQuery()` filters fixtures in SQL, so `max_num_pages` stays right.

**The design has no paging because it has one row** (`past = [decemberResults]`, `:19`), not a representative list. With about 140 rounds, keep what exists. Don't build load-more, year groups or filters.

| Rule | Why |
|---|---|
| 12 rows per page, newest first | 139 past rounds give 12 pages; every row costs an entry and a results query |
| `queryArgs()` gains `post__not_in` for the current round, **on every page** | No duplicate of the card; page contents don't shift between pages |
| Current card only on page 1; hero on every page | Page 2 onward is browsing the list |
| Pager inside `uitdagings-past-list`'s band, below the list | As today. `Hub.php:531` `withAuditId()` is the precedent for the id |
| A page past the end | Unchanged: `toHtml()` renders the empty state |

Map status `scope`: INK-only, because the real data is 140 times the design's.

## 4. Join: `uitdagings-past-<slug>` can never pair

**Why:** `run.sh diff` joins on exact id strings, and maps have no alias column (R3). Lovable's ids are built from a mock slug (`uitdagings-past-firelight`, `-firelight-results`, `:71,77`). INK's slugs are real (`oktober-2026-getal-10-projek`) and the top row changes monthly. Stamping real slugs would give ids that match nothing and change at every refresh. Separately, the row's three text nodes (`:73-75`) carry no id, so they aren't captured at all.

**How the join should work:** Lovable needs positional ids, the convention already used by `magazine-past-{n}` and `competitions-external-{i}`.

**INK stamps them on exactly one row: the first with results.** Lovable's only row has a button, so a row without one reports `MISSING`. That's the house rule from `cardHtml()` (repeaters: one instance, `audit` flag); other rows carry no ids, so there are no duplicates and no `EXTRA`. Stamping the literal top row would start failing the moment a closed round waits for its results.

**Proposed Lovable change, attributes only (`Challenges.tsx`, applied 2026-11-01 under the owner's audit-id instruction, uncommitted):**

| Line | Change |
|---|---|
| 26, 40, 65 | Add `uitdagings-hero-container`, `uitdagings-current-container`, `uitdagings-past-container` |
| 43 | Add `uitdagings-current-badge-icon` |
| 52, 53 | Add `uitdagings-current-meta-close`, `uitdagings-current-meta-entries` |
| 70 | `past.map((c, i) => …)` |
| 71 | `uitdagings-past-${c.slug}` becomes `uitdagings-past-${i}` |
| 73, 74, 75 | Add `uitdagings-past-${i}-meta`, `-title`, `-announced` |
| 77, 78 | `-${c.slug}-results` becomes `-${i}-results`; add `-${i}-results-icon` |

Without it, the fallback is INK's row stamping the literal `uitdagings-past-firelight` and `-results`. It joins the row and button only, leaves the text unmeasured and ties the map to mock data. Not recommended.

`Button asChild` and `Badge` should forward the attribute to the rendered node; `--expect` at capture confirms it. Not run here.

## 5. Load scope and files

| Asset | Holds | Gate | Consumers |
|---|---|---|---|
| `assets/css/uitdagings.css` (new) | Container width, list rows and dividers, row direction by breakpoint, margins, page text | `is_post_type_archive( 'uitdaging' )`, or the QA gallery as `functions.php:1132` does | `patterns/uitdaging.php`, `qa-bloks.php:167` |
| `primitives.css` | RG-UI-01 to -03 | Site-wide | |

**Tests:** `ArchiveTest.php` changes with `cardHtml()` and `toHtml()`: add `post__not_in`, closed-state copy, no button without results, first-with-results stamping, no current card on page 2. `UitdagingTemplateTest.php` should pass unchanged.

**At build:** `tools/fidelity/targets.json` row 14 and `agent-brief.md` rows 14 and §2.1: treatment A, Lovable `/challenges`, drop the `ChallengeSection` reference.

**Order:** after spec 01 (results URL and rule) and spec 03's `Cadence` and `current()` fixes. Before those, the button 404s and the card is empty.

## 6. URLs and test data

**Dev was down during this spec (database connection failed, 2026-11-01), so nothing was re-checked.** Run at build. Last known: `/uitdaging/` 200 (row 14); no post named `uitdaging` (`01` §7); 140 rounds, 101 with results (`01`, `03`); `/uitdagings/` 301s to an unrelated post (row 14 Stage 4).

**Fixture:** F1 in `03` §9 gives an open November round, so the open state and `-meta-close` measure. After a refresh, without F1, the card shows the closed state with no `-meta-close` and that id reports `MISSING`. No other fixture: real rounds supply the rows. Measure at 390, 768, 1440 with `--scope main`.

## 7. Accepted divergences

| Divergence | Status | Tie |
|---|---|---|
| Afrikaans copy | divergence | Handoff item 3 |
| Pager; one card on page 1 only | `scope` | §3 |
| Past band in the standard wide container, not the design's 896 column | divergence, `accept=width@>=1024` and margins | Orchestrator decision, 2026-11-02, following the owner's standard-cards ruling. Cards measure 440 / 356 / 358, equal to `library-card` |
| Closed state of the card | divergence | Owner ruling on the closing date; rest of §2 follows |
| Row without results: no button, no announced line | divergence | Owner, this brief |
| Meta item icon gap | divergence, `accept=gap` | **NR-4** rules small meta items at 6px; Lovable uses `gap-2` here. `RG-KO-19` is not reused: its icon is smaller |
| Theme falls back to title, so the month can read twice (`"Oktober 2026 - Getal 10 projek"`) | Data | `01` §4: `ink_uitdaging_theme` is empty on all rounds; don't strip titles |

Anything else is `DRIFT`, and Lovable wins. Disclose in code comments (R6).

## Questions for owner

Searched: `docs/`, `_bmad-output/` (`epics.md` 12.2, story 12-2), `specs/`, code.

- **Q1. Approve the Lovable ids in §4?** `verse-haven` changes need approval. Attributes only, as with the header ids in `06` §4.
- **Q2. FR-46 countdown.** Story 12.2's acceptance says "challenges are listed with a countdown" (`Nog 5 dae`). The design shows a closing date, and past rows are all closed, so the list has nothing to count. **Default: closing date only; delete `countdownLabel()`.** Alternatively `-meta-close` can read `Nog 5 dae` in the same slot.
- **Q3. Link from a past row to its round.** The design's rows link only to results, so the 39 rounds without results, and every round's brief and entries, would have no way in from this page. INK's title links to the round today. **Default: keep that link on the `<h3>`, looking like Lovable's plain heading at rest.** That's a disclosed addition to the design.

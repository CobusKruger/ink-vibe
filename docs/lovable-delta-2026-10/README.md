# Lovable design delta (Oct 2026): build strategy

**Status:** STRATEGY. Nothing is built yet. Owner ruled on D1–D4 and D6–D9 on 2026-10-26 (§4). D5 and D10–D12 are still open.
**Start with `HANDOVER.md`** (state and next steps). InkPols (WP4) is fully specified in `inkpols.md`.
**Opened:** 2026-10-26.
**Lovable range:** `71c0847..86b69dd`, 22 commits. `71c0847` is the last commit the fidelity rework synced.
**Read first:** `docs/fidelity-remediation/README.md`. Its rules R1–R9 apply to this work unchanged.

This folder holds the plan for building the new Lovable design work right the first time. The
fidelity rework existed because pages were built first and audited afterwards. This plan
reverses that order: **the audit artefacts are written before the build and serve as its
acceptance test.**

---

## 1. New major elements

| # | Lovable source | What it is | INK target (proposed) | Builds on | Class |
|---|---|---|---|---|---|
| N1 | `src/pages/ChallengeResults.tsx`, `src/data/awards.ts` | Per-round results page: header (month, theme, announced date, entry count), judges' letter, **podium** with category tabs (Gedigte / Stories / Artikels); Gedigte split Goud/Silwer/Brons; 1st/2nd/3rd award cards with excerpt + judging-panel citation; honourable mentions; CTA to current round | `/uitdaging/<slug>/uitslae/`: rewrite endpoint on `uitdaging` → `templates/single-uitdaging-uitslae.html` | `Challenges/Placements.php:143-221`, `ModeratorFeedback.php:174`, `WinnersPost.php:169-208`, `ResultPostLinker.php`, `SinglePage::entryCount` | extend + new fields |
| N2 | `src/pages/Magazine.tsx` | InkPols archive: current-issue feature (cover, "in hierdie uitgawe" list, Lees aanlyn), past issues grid with year filter chips (default "Alles") | `/inkpols/`: `templates/archive-inkpols_uitgawe.html` (exists) | `InkPols/Archive.php:109-226` (year *sections* today). **Spec: `inkpols.md`** (no download buttons, A4 covers, naam replaces issue number) | extend |
| N3 | `src/pages/MagazineIssue.tsx` | Issue reader: top bar (back, issue title), flipbook, "in hierdie uitgawe" items, Vorige/Volgende | `/inkpols/<slug>/`: `templates/single-inkpols_uitgawe.html` (exists) | `InkPols/SingleIssue.php:77-102`, `Viewer.php` (Real3D; the flipbook appears **only** here). **Spec: `inkpols.md`** | extend |
| N4 | `src/pages/Competitions.tsx`, `src/data/competitions.ts` | Kompetisies page: dark hero for the **annual** competition, 3 category cards (word limit, guidelines), **Gala** section (details, programme, ticket pills, reserve) + "Evenings past" gallery by year, **external competitions directory** (search, genre + fee filter chips, cards, empty state), "submit a listing" box | `/kompetisies/`: `templates/page-kompetisies.html` + SystemPages entry | Annual round = `uitdaging` with cadence `jaarliks` (`Kernel/CadenceType.php:34-36`). **Nothing exists** for categories-with-guidelines, gala or directory | mostly new-feature |
| N5 | `src/components/home/ResultsBanner.tsx` | Home: gold link banner "Uitslae aangekondig · <maand>" → N1 | new dynamic block `ink/uitslae-banier` in `front-page.html` | `WinnersPost::latestAnnouncement()` (`:311`), `Cadence` | extend |
| N6 | `src/components/home/MagazineSection.tsx` | Home: latest published InkPols issue; hidden when there are no issues. This is the **only** route to InkPols (D1). | new block (shares N2's current-issue renderer) | N2, `inkpols.md` | extend |
| N7 | `src/pages/ReadStory.tsx:175-184` | Reading page: gold "1ste plek · Goud gedigte · <maand>" aside linking to N1, with the citation | new block in `reading-storie.php` / `reading-gedig.php` / artikel | `ModeratorFeedback::feedbackFor()` and `WinnerBanner::forPost` have **no consumer** (`functions.php:2147-2154` is never called) | extend |

There are no new design tokens: every colour, shadow and utility on these pages already
exists in `tailwind.config.ts` / `src/index.css`. The new photos (`gala-2025-*.jpg`,
`magazine-cover.jpg`) are placeholders and are not to be shipped.

## 2. Changes to existing pages

| # | Page | Lovable change | INK impact |
|---|---|---|---|
| E1 | Tuis | `ChallengeSection.tsx` rebuilt as **"Last month's winners"**: exactly 2 gold cards (best storie, best gedig), each with a category pill, title, type-specific excerpt (a gedig gets a left rule, prose an italic quote), avatar + "Nth challenge win" and a "Lees gedig/storie" button. The current-challenge card is **gone** from this section; the current round now lives only in the hero spotlight. | `front-page.html:9-18` pairs `ink/huidige-uitdaging` with `ink/wenner-kollig`. `FeaturedWinners` cards exist (`:173-321`), but the production payload `WinnersPost::featured()` (`:220-249`) carries only id/rank/title/url. The rich card today renders from a **theme demo filter** (`functions.php:1336-1361`). A real data path is needed. |
| E2 | Tuis | Section order is now Hero → ResultsBanner → Magazine → Winners → FeaturedWorks → Sponsors → CTA. | Template order plus N5/N6. |
| E3 | Tuis | Hero stats row removed. | **No-op**: INK never had one (`patterns/hero.php`). The hero copy changes are placeholder (R2). |
| E4 | Header | Desktop nav gains Competitions + Magazine; the mobile order moves Library after Magazine. | **Ruled (D1):** INK's header items are correct and only **gain Kompetisies**. Magazine stays out of the header; InkPols is reached from its home section. The header *style* must match Lovable (desktop and mobile). Re-capture the header after adding the item. `patterns/header-main.php:71-81`; record the change in `docs/prod-menu-mapping.md`. |
| E5 | Footer | Gains Competitions + Magazine links. | **Deferred** by the owner: the footer links need their own clean-up pass. Out of scope here. |
| E6 | Biblioteek + Opleiding | Shelf H2 `text-2xl/3xl` → `text-3xl/4xl font-semibold`; closing-CTA H2 adds `font-semibold` (`Library.tsx:235,311`). | Small CSS change on both pages (one reference, two pages, as in fidelity row 08). Check whether `.ink-heading-h2-section` in `primitives.css` already matches before adding anything. |
| E7 | Lees (storie/gedig/artikel) | N7 aside. | See N7. |
| E8 | Uitdaging single | No Lovable change, but N1 needs a link from the round to its results. | Add a link to `reading-uitdaging.php` once results exist. |

**Audit-id debt in the reference.** The rebuilt `ChallengeSection.tsx` **dropped** all of its
`data-audit-id`s (`challenge-card*`, `winner-*`), so the tuisblad join map
(`fidelity-remediation/maps/tuisblad.csv`) is now partly orphaned. The new pages have almost
none: Competitions has 4, ChallengeResults / Magazine / MagazineIssue / MagazineSection /
ResultsBanner have 0. This must be fixed before anything can be measured (§3, step B).

## 3. Strategy

### 3.1 Principles

1. **Specify first, build second, measure to accept.** Each page gets a build spec, written and
   ratified *before* any code: the spine, the audit-id map, a recipe for every element, a data
   source for every element, and the copy keys. The build is done when the capture diff against
   Lovable is clean. Nobody judges "looks right" by eye (R4/R5).
2. **No new primitives on a page (R8).** Every element maps to a ratified recipe in
   `fidelity-remediation/maps/_recipes.csv`. A genuinely new component (e.g. the segmented podium
   tablist, the tilted magazine cover, the dark hero band) is a **recipe gap**. It is ratified
   once, before the build, and goes into `primitives.css`. Page sheets only lay things out.
3. **Code owns structure; the DB owns only content.**
   - Every page is a theme template + pattern + dynamic blocks.
   - A slug Page is registered in `Kernel/SystemPages::PAGES`.
   - CPT archives and singles go through the template hierarchy.
   - Per-round sub-views are a rewrite endpoint.
   - Never `post_content` layout, never a hand-made Page, never `_wp_page_template` set by hand.
4. **Everything must survive `wp ink migrate-all` from a fresh prod copy.** Any *new* editorial
   data the design needs (gala details, external competitions, InkPols highlights, category
   guidelines) is either migrated from prod or seeded from a **committed input file** by a
   `migrate-all` step. It is never typed into nuwe-ink, which a refresh wipes. Fixtures for the
   capture harness follow the same rule.
5. **Lovable is structure, not copy (R2).** Afrikaans copy goes through
   `docs/afrikaans-copy-debt-process` (placeholder marker → translation sheet → wire back). An
   English mockup string never ships. Terms follow `docs/afrikaans-terms.md` (*uitdaging*,
   *uitgawe*, *Gradering*, *brons/silwer/goud*).
6. **Lean orchestration.** Use Opus subagents, at most 3 in parallel. Each gets a narrow,
   cold-start brief. The lead does small edits and commits per work package in both repos.

### 3.2 Sequence

| Step | What | Output | Gate |
|---|---|---|---|
| **A · Decide** | Owner rules on §4 D1–D12. | `decisions.md` in this folder | All D-rows answered, or explicitly deferred with the element cut from scope |
| **B · Prepare the reference** | In `ink-lovable`, add `data-audit-id`s Lovable-first (R3) to every visually meaningful element of N1–N7 and E1, restoring the tuisblad ids where the rebuilt section still has a counterpart. Make ids unique per page; the harness refuses duplicates. Build a preview. | ink-lovable commit; new SHA recorded | `run.sh capture --side lovable` resolves every `--expect` id at 390/768/1440 |
| **C · Sync the docs** | Run `lovable-design-sync` for `71c0847..<B SHA>`, **after** the skill is amended (§5). Record the SHA in `design-handoff/README.md`. Its last entry is `04217f7`; the fidelity rework's `71c0847` sync was never recorded. | handoff, readiness, spec §9.4/§14, feature list updated | New features appear in §14 as decisions (Rule 3), not as scope |
| **D · Build spec per page** | One file per work package: `specs/<NN>-<slug>.md` + `maps/<slug>.csv` (status `planned`). Contents: section spine (Lovable section ↔ INK template/pattern/block *to be created*, with file paths); element table (audit-id → recipe from `_recipes.csv` or `RECIPE-GAP`; data source = meta key / derived / new field / seed file; copy key); URL + slug-clash check against a refreshed DB; load scope of any page sheet = the pages that use it. | spec + map per WP | Owner ratifies **all recipe gaps in one batch** before any build starts |
| **E · Data, routes, registration** | ink-core first, with unit tests: new fields, migration/seed steps, rewrite endpoint (version-gated flush), SystemPages entries, enriched payloads. | ink-core commits | `composer test`, `composer stan`, deptrac clean |
| **F · Build** | Template + pattern + blocks that consume primitive classes only. Add recipe gaps to `primitives.css` (ratified versions only). Page sheet = layout, enqueued with a gate that covers every consumer. | theme commits | No primitive defined outside `primitives.css`; `copy:scan` grows only by registered markers |
| **G · Accept** | `tools/deploy-to-local.sh --apply` → curl check → `determinism-check.sh` → capture both sides → `run.sh diff` → `run.sh verify`. Append to `_primitive-observations.csv`. | `findings/<NN>-<slug>-drift.md` (generated) | Zero `MISSING`/`STRUCTURE`; every `DRIFT` closed or recorded as `DIVERGENCE (accepted)` with an in-code disclosure; `load_scope ⊇ used_in` |
| **H · Prove refresh** | Restore the prod snapshot, run `wp ink migrate-all`, curl every new URL (200, not a redirect), re-run G's captures on the refreshed DB. | runbook Verify table rows | Same diff result as G |

Steps D–H run **per work package**. A and B–C run once, up front.

### 3.3 Work packages (in build order)

Order rule: build the data and the link target before the things that link to it.

| WP | Scope | Why here | Depends on |
|---|---|---|---|
| **WP1 Uitslae** | N1 + E8 + the **Gradering-scope fix** (below) | It is the hub: N5, N7 and E1 all link to it. It reuses the most existing machinery. | D5, D11 |
| **WP2 Wenners on the reading page** | N7 | Small. It gives the stranded `ModeratorFeedback`/`WinnerBanner` capabilities a real consumer and a production-path test (the R12B lesson). | WP1 |
| **WP3 Tuis** | E1, E2, N5 (N6 waits for WP4) | It replaces the demo-filter path with real data. | WP1, D10, D11 |
| **WP4 InkPols** | N2, N3, N6 + the CSV-driven migration step that replaces the no-op `migrate-inkpols` | Fully specified (`inkpols.md`), with no open decision, so it **may go first**. Within WP4 the migration comes first: without it the archive is empty after a refresh and nothing can be measured. | owner fills `migration-data/inkpols-uitgawes.csv` |
| **WP5 Kompetisies** | N4: annual-competition hero + categories, gala (details, programme, fictional ticket prices, "Evenings past" photos by year), external competitions directory, submit-a-listing box | Largest scope. | D2–D4 ruled |
| **WP6 Chrome** | E4 (header gains Kompetisies), E6 | Small. It goes last so the nav link points at a page that exists. E5 is deferred. | D1 ruled |

**Gradering-scope fix (in WP1).** The established rule is that Gradering (Brons/Silwer/Goud)
is the author's grade and applies **to Gedigte only** (`migration-plan.md:234`). Story 12.5's
`Challenges/Pools.php:19-40` instead keys pools on Gradering × category for all three types,
so storie and artikel get Brons/Silwer/Goud pools as well. That was a code defect, not a design
choice. Fix it before building the podium:
- Gedigte keep per-Gradering pools.
- Storie and artikel become a single pool each.
- The consumers are `Placements::arrange`, `Coverage` and the ingestion/parser.
- The pool tests change with it.
- Check the migrated result data for any storie/artikel rows that carry a gradering.

### 3.4 Page ↔ template linkage per element

| Element | Linkage | Refresh behaviour |
|---|---|---|
| Kompetisies | `page-kompetisies.html` + `SystemPages::PAGES['kompetisies']` | **Adopts prod Page 59192 "Eksterne Kompetisies"**, keeping its ~1 KB legacy body in the DB (hidden, undeletable). Decide in D2: adopt and empty the body (extend the existing `[upme_*]` emptying rule), or use a fresh slug and a 301. |
| Uitslae | rewrite endpoint `uitslae` on `uitdaging` + `single_template_hierarchy` → `single-uitdaging-uitslae.html` | Code-defined; nothing in the DB. The winners `post` either stays as the canonical announcement with the endpoint as the designed view, or 301s to it (D5). |
| InkPols | existing `archive-`/`single-inkpols_uitgawe.html` | Issues are created from the committed CSV by the new migration step. The prod `/inkpols/` Page is retired, and the seven `inkpols-2025-11…` root Pages 301 to `/inkpols/<naam-slug>/` (`inkpols.md` §3). |
| Home blocks, reading aside | dynamic blocks in existing templates/patterns | Code-defined. |

**Guard test.** Extend `tests/Unit/Kernel/SystemPagesTest.php:40-50` so that *every*
`templates/*.html` must be accounted for: a page via SystemPages, an archive/single via a
registered post type, an endpoint view via an endpoint registry, or listed as excluded with a
reason. A template that nothing routes to then fails CI rather than being discovered at
rehearsal.

### 3.5 Harness gaps to close in WP1 (before any capture)

- `run.sh diff` reports drift but has no pass/fail. Add `--fail-on MISSING,STRUCTURE,DRIFT`
  with an accepted-divergence allowlist read from the map CSV, so step G is mechanical.
- `targets.json` points at fixture URLs that a refresh deletes (`uitdaging-enkel`,
  `/storie/die-wenteltrap…`, `/author/`). New targets use **refresh-stable** URLs: a real
  migrated round (e.g. Oktober 2026), a real migrated InkPols issue, and the Kompetisies page.
- `/qa-bloks/` does not exist after a refresh (it is EXCLUDED), yet the harness uses it for
  scoped captures. Either seed it in the dev-only path or stop relying on it.
- The Stage 3 class vocabulary is `docs/fidelity-remediation/class-map.md` (moved from `tmp/` on
  2026-10-26). Step D cites recipes by class name from it.

## 4. Owner decisions

### 4.1 Ruled 2026-10-26

| # | Ruling | Consequence for the build |
|---|---|---|
| D1 | The header gains **Kompetisies**. INK's other header items are already correct. InkPols is **not** in the header; it is reached from its dedicated home section. The header style must match Lovable. | E4 in WP6. `prod-menu-mapping.md` "Target top-level navigation" gets Kompetisies. |
| D2 | Kompetisies is the new design for prod's "Eksterne Kompetisies" page. | `/kompetisies/`, `page-kompetisies.html`, `SystemPages::PAGES['kompetisies']`. Adopt prod Page 59192 and empty its legacy body in code. |
| D3 | The annual competition's **web pages are in scope**, not deferred. Only the admin side is deferred and still undefined: how entries reach the judges and how winners come back. | Build the announcements (hero + categories), the gala section with its photos, and the external competition links. Build no entry/judging pipeline for the annual round. |
| D4 | Gala ticket prices are shown with **fictional** values and no in-site way to pay. Payments come after lidmaatskap payments are implemented. | The "Reserve" button gets no payment flow; decide its target (none / contact) in the build spec. Gala details, photos by year and external competitions are seeded from committed files (Principle 4). Mark the prices as placeholders in the seed file so they can't be mistaken for real ones. |
| D6 | A citation shows **only if the author has opted in** to showing it; otherwise it is not shown anywhere. | The award card and the reading-page aside render without the citation block when `ModeratorFeedback::feedbackFor()` returns nothing. The placement itself still shows. |
| D7 | *Not a decision.* Gradering applies to Gedigte only, as established long ago. | The code violates the rule. Fixed in WP1 (§3.3, Gradering-scope fix). |
| D8 | Accepted: add a per-round judges' letter and capture honourable mentions. | Letter meta on the `uitdaging`. `ResultsParser` stops discarding "Eervolle vermelding" (`:147-151`) and stores the mentions. |
| D9 | InkPols fully specified (separate discussion, 2026-10-26). | See `inkpols.md`. Summary: naam (e.g. "Julie/Augustus 2026") replaces issue number and season; the three featured items (Gedig/Storie/Artikel) are plain text; Real3D on the single only; no downloads; A4 covers; metadata from a committed CSV. |

### 4.2 Still open

| # | Question | Conflict / evidence | Recommendation |
|---|---|---|---|
| D5 | Results URL, and whether the winners `post` remains. | Results today are core posts at normal permalinks, rendered through `index.html` + `post-content`. | `/uitdaging/<slug>/uitslae/` as the designed view; the winners post 301s to it. |
| D10 | Home winners: exactly 2 cards (storie + gedig)? What about artikel? "Nth challenge win" is a **lifetime** count, but `ink_tier_win_count` resets on promotion (§14 #18). | | 2 cards as designed; derive the lifetime count from placements. |
| D11 | Place labels: "1ste/2de/3de plek" vs the existing "algehele wenner / wenner" (`Placements.php:80-88`). How is a Meester winner labelled on the Goud podium? | Open item in `tmp/post-roadmap-followup-worklist.md:74`. | Owner copy call. |
| D12 | Does the current round still appear on Tuis outside the hero? The rebuilt section drops the current-challenge card. | | Follow the design (hero only). |

## 5. Documentation that must change (so a refresh and the next sync keep this work)

| Doc | Change |
|---|---|
| `_bmad-output/project-context.md` | **New rule:** every theme page is code (template + pattern + blocks), registered in `SystemPages` / a post type / an endpoint. No layout in `post_content`. New editorial data is migrated or seeded from committed files. |
| `.claude/skills/lovable-design-sync/SKILL.md` | Step 2: the page-map row needs `wp_target` = an **existing or planned template** plus its linkage (SystemPages / CPT / endpoint). New step: assign Lovable `data-audit-id`s for new pages, update `fidelity-remediation/agent-brief.md` §2.1 + `tools/fidelity/targets.json`. DoD: record the synced SHA (it was missed for `71c0847`). |
| `docs/design-handoff-workflow.md`, `docs/lovable-block-theme-playbook.md` (DoD ~:250) | Add to the definition of done: "registered in code, survives `migrate-all`, accepted by harness diff". |
| `docs/migration-runbook.md` | `migrate-all` step list (new InkPols Pages→CPT step, seed steps); Verify table: new URLs (`/kompetisies/`, `/inkpols/`, one `/uitdaging/<slug>/uitslae/`); slug-clash risks (`kompetisies` 59192, `inkpols`). |
| `docs/migration-plan.md` | InkPols section (:210-216) is **stale**: it says prod has a CPT, but prod has Pages. URL map (:331). |
| `docs/design-handoff/page-map.csv`, `README.md`, `lovable-repo-analysis.md`, `docs/mockup-readiness-assessment.md` | Via the sync (step C). page-map also has stale targets (`page-biblioteek`, `page-opleiding`, `page-uitdagings`, `single-skrywer`). |
| `docs/specs/ink-consolidated-spec.md` §9.4, §14 (+ the inkpols line ~:338), `ink-feature-list.md` | Readiness; D1–D12 as §14 rows; the InkPols contradiction. |
| `docs/fidelity-remediation/agent-brief.md` §2.1, `tools/fidelity/targets.json`, `maps/` | New rows NN for each new page (Treatment A); fix stale rows (`/meld-aan`, `/uitdagings`, `/author/cobus`). |
| `docs/prod-menu-mapping.md`, `docs/ink-prod-menu.md` | Add Kompetisies to the target header nav (D1). Replace "pending the Lovable design" (:18 Eksterne Kompetisies, :21 gala pages) with `/kompetisies/`. |
| `_bmad-output/planning-artifacts/architecture.md` (~:1052) | Template tree. |
| `docs/afrikaans-terms.md`, `ui-copy-translations.md`, translation sheet | New terms/keys (uitslae, beoordelaarsbrief, eervolle vermelding, gala, seisoen…), after D2/D8/D9/D11. |

## 6. Risks carried in from the fidelity rework

| Risk | Where it bit before | Control in this plan |
|---|---|---|
| Built, then audited → divergence baked in | Whole Epic 19 rework | Build spec + harness acceptance (§3.2 D, G) |
| Page-local primitive copies | `findings/01-primitives-audit.md` §7 | Recipe-gap batch ratification; R8 |
| Inert CSS (load scope narrower than use) | Live bug 10 (InkPols cards had no CSS) | `load_scope ⊇ used_in` is an acceptance gate |
| Pages in the DB lost on refresh | Rehearsal 2026-10-25 (`SystemPages.php:15-25`) | Principle 3 + the extended guard test + step H |
| Wrong slug / link to unrelated content | `/uitdagings/` 301 to a post; `/lees` taken by a poem | Slug-clash check in step D against a refreshed DB |
| English or AI-Afrikaans copy shipped | 404 / My Profiel markers went public | Copy-debt markers; `copy:scan` ratchet |
| Stale local deploy captured | Row 00's first capture discarded | `deploy-to-local.sh --apply` + curl before every capture |
| Capability without a consumer | R12B HIGH; `ModeratorFeedback`/`WinnerBanner` today | WP2 wires them, with production-path tests |

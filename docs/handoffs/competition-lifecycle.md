# Handoff: competition lifecycle (entries, results, partner competitions)

**Written:** 2026-11-10. **For:** an agent or developer with no knowledge of earlier sessions.

**The job (if you inherit it):** build stories K1 to K6 from `docs/lovable-delta-2026-10/specs/05-kompetisies.md` **§14**, so the site can run a competition from announcement to results without any CSV or hand-edited file. The spec is written and the owner's rulings are in it. **No code has been written for §14.**

**Status (updated 2026-11-11):** K1 to K5 are built and **unit-tested only** (no integration, e2e or fidelity run yet; the results page layout is provisional). **K6 is done** (migration on the role model: results Pages reused as-is as the year's published results, Fotos Pages as gala photos, announcements/closure/reminders only marked; tags and SQL in `old-competitions-tagging.md`). **K7 is built** (2026-11-11; unit-tested only, not run against a real site): redirects for the retired Fotos and results Pages read the option `ink_kompetisie_old_pages` that K6 stores (`{id, path, jaar, soort: fotos|uitslae}`, `StaticRedirects::OPTION_OLD_PAGES`). Nothing is deployed; the tag SQL has not been run on production. The rest of this document is the original 2026-11-10 handoff, kept for context; its "No code has been written" and "Nothing built" lines are out of date.

Read `CLAUDE.md` first. **THE FIRST RULE** applies to every line of this work: after go-live, everything is added and edited on the site through real features. Never propose a CSV, seed file or migration re-run as how a competition is operated.

## 1. Why this work exists

The first competition design (`05-kompetisies` §1 to §13) treated a competition as one post per year holding hero, categories and gala, with the gala assumed planned and nothing for entries or results. The owner reviewed the production pages and found a competition has four kinds of page, and the design covered only two of them:

| Page | Purpose |
|---|---|
| Competition announcement | Tell people it exists; rules and categories; open entries; later, the terms linked to submitted work on Skryf |
| Gala announcement | Promote the gala; reservations and payment |
| Results (winners announcement) | Announce winners after the gala; celebrate the writers |
| Gala photos | Show past galas; inspire people to enter the next competition |

The old migration tags only results-adjacent pages 3 and 4 (gala and Fotos). It never imported the announcements, had no results at all, and assumed the gala was already planned when a competition was loaded. It showed photos without winners.

## 2. Owner rulings (do not re-litigate)

All from the owner, 2026-11-09, in conversation.

1. **A competition is not a challenge.** An Uitdaging is monthly (reward: feedback from an academic, bragging rights). A competition is usually yearly, with the gala as its finale. Share the *pattern* of the challenge flow, never its code: no `CadenceType`, no `ChallengeRound`, no `WinRecords`, and a competition entry is never counted as a challenge entry. (`CLAUDE.md` already says competitions are never challenges.)
2. **Types:** the big annual competition (usually Feb to May; the months are parameters, not constants) always has all four pages. Occasionally INK runs a competition with an outside organisation: announcement and results, **no gala**. Old partner competitions are **not** migrated; the type must be supported going forward.
3. **`eksterne_kompetisie` stays as is:** competitions with nothing to do with INK, listed so members can enter them elsewhere. We link out and do nothing else.
4. **Entries:** the Enter buttons link to `/skryf/`, and that is correct, **provided an open competition is available for selection there before the writer submits.** The Uitdaging entry term is created at the first submission, not on publish. Competitions copy that.
5. **Results are a separate route.** After a competition closes, the hero falls back to the generic annual info. When results are published the hero reads "Nou bekend: Die wenners van die 2026 Ink Kompetisie" and links to the results page. The same results page is linked from the gala photos, so an old competition's results are always reachable without a list of old competitions.
6. **Winners:** the gala also announces things that are not competition wins (Facebook-group excellence, book prizes supporting development). Winners need not be site users. For **new** galas the editor captures each winner, linking to their profile if they have one and to the photo of them receiving the award. **Old results posts are reused as-is, not parsed.**
7. **Promotion and reminders are out of scope for now** (at most a homepage banner or social posts later). Get the competition and gala pages workable first.
8. **Payment** (gala reservations and memberships) does not exist and is **separate work** (P1). The Reserve button stays a hook.

Added by the owner, 2026-11-11:

9. **The old "gala" Pages are results Pages.** Posted after the gala: an overview of the evening plus the announced winners. The `ink_old_gala` ids in `tag-old-competitions.sql` (2017 to 2025) are these results Pages. The 2016 ids 7802 and 9878 are duplicates of each other. The migration must not treat them as gala announcements.
10. **Navigation:** `/kompetisies/` shows the current competition and the featured gala photos. "Sien alles" goes to the gala photos page (`/kompetisies/gala/<jaar>/`). That page links to the results (`/kompetisies/<jaar>/uitslae/`). **Old Fotos Pages redirect to the gala photos page; old results Pages redirect to the results route.**
11. **Old announcements stay live at their own addresses:** competition announcements, gala announcements, closure notices and gala reminders are **not** trashed or redirected. They are marked with metadata only. The owner decides their categories later.
12. **2026 is the current competition.** Closed, no later one, gala in a few days. The owner will create its gala announcement on the site to test.

## 3. What already exists (verified in code on 2026-11-09)

Paths are under `wp-content/plugins/ink-core/src/`.

| Thing | State |
|---|---|
| CPT `kompetisie`, one post per yearly edition; classic screen; `FieldSets` box | Built. `Content/PostTypes.php`, `Content/FieldSets.php` (`kompetisieFields()`, `KOMPETISIE_PREFIX = ink_kompetisie_`). Not public: no single or archive. |
| CPT `eksterne_kompetisie` | Built, unchanged by this work. |
| `/kompetisies/` page (Page 59192 adopted by `SystemPages`) with hero, categories, gala band, Evenings past, directory | Built. `Competitions/` (`Hero`, `Categories`, `Gala`, `GalaPage`, `Directory`, `Data`, `Edition`, `GalaYear`, `Module`, `Migration`). |
| `/kompetisies/gala/<jaar>/` rewrite rule | Built (`GalaPage.php`). 404 for a year with no gala. |
| Default announcement (`ink_kompetisie_standaard = ja`), shown when no competition is open; `Data::shown()` falls back to it, then to `Edition::builtIn()`; `Module::ensureDefault()` creates the post once | Built (spec §12b, 2026-11-08). |
| Gala fields on the same post (`gala_naam`, `gala_datum`, `gala_deure`, `gala_plek`, `gala_drag`, `gala_program`, `kaartjie1..3_*`), optional | Built. |
| Five feature photos plus the whole gallery (`gala_foto1..5`, `gala_fotos`) | Built. |
| `Edition::isDefault()`, `acceptsEntries()`, `isCurrentEdition()` | Built. `acceptsEntries()` only means "not the default"; **there is no date-based state**. |
| Migration: `wp ink migrate-kompetisies` finds galas by `ink_old_gala`, `ink_old_gala_fotos` and the 2022 competition by `ink_old_competition`; creates `gala-<jaar>` / `kompetisie-<jaar>` posts; trashes the old Pages | Built and tested on dev; **not run against a real refresh with the tags**. Details: `docs/handoffs/old-competitions-tagging.md`. |

## 4. What is missing (the work)

Spec §14 has the full design. Summary, with the code you will touch:

| # | Story | Needs | Notes |
|---|---|---|---|
| K1 | `kompetisie` type, dates, derived state | `tipe` (`jaarliks` / `vennoot`), `vennoot_naam`, `oopdatum` beside `sluitingsdatum`, `Edition::state()` (announced / open / closed / results / archive, derived, never stored); `FieldSets`, `Edition`, `Data`, tests | Define `tipe` and the state as an `enum` per the project value-set rule (see `Kernel/CadenceType.php` for the convention; do not reuse it). |
| K2 | Entries on Skryf | New `Ink\Competitions\EntryLinking`, its own taxonomy and term meta; offer a published `open` competition on Skryf in its own group; get-or-create the term at the first submission; window and per-writer cap checks | Model on `Submission/ChallengeLinking.php` (`openChallenges()`, `isOpen()`, `link()`, `resolveRoundTerm()` at about line 300). **Do not reuse the round taxonomy.** Check how the Skryf form lists open challenges, and add a second group there. |
| K3 | Hero and gala band follow state; `vennoot` hides the gala | `Hero`, `Gala`, `Data` | `closed` shows the default announcement; `results` shows the "Nou bekend" line linking to the results route. Copy needs copy-debt keys and glossary terms. |
| K4 | Winner-record repeater | New ordered repeater input in `FieldSets` (it has scalars only, `media` and `gallery` inputs per spec §3.4) | Fields: `toekenning`, `wenner_gebruiker`, `wenner_naam` (required if no user), `inskrywing`, `foto`, `aantekening`. **Not `WinRecords`** (`Challenges/WinRecords.php` is keyed to an Uitdaging and needs a user and a post). |
| K5 | Results route and page | `/kompetisies/<jaar>/uitslae/` rewrite (mirror `GalaPage`; for the endpoint pattern see `Challenges/ResultsRoute.php`, `ResultsPage.php`); 404 for no published results; links from hero, year chips and `/kompetisies/gala/<jaar>/` | Page is code: template plus pattern plus blocks, registered like the others. |
| K6 | Migration | New role tag `ink_old_uitslae`; one `kompetisie` per year from all its tagged pages; results body kept as-is; 301 to the new route via `ink_gala_old_paths`; update `docs/migration-tools/tag-old-competitions.sql`, `migration-runbook.md` (step 2a and the `kompetisies` row), `old-competitions-tagging.md` | **Blocked on page ids from the owner** for the announcement and results Pages. Do not guess ids. |
| K7 | Redirects for retired Fotos and results Pages | Fotos Pages (by path and by `?p=<id>`) go 301 to `/kompetisies/gala/<jaar>/`; results Pages (by path and `?p=<id>`) go 301 to `/kompetisies/<jaar>/uitslae/`. Key by year, not one target. | **Built 2026-11-11, unit-tested only, not run against a real site.** `StaticRedirects::oldPageTarget()` reads `ink_kompetisie_old_pages` (`?p=`, `?page_id=` or path) and `RedirectGenerator::maybeRedirect()` serves it (priority 5, 404 requests only). `ink_gala_old_paths`, `storedGalaTarget()` and the 20 gala/Fotos paths in `EXACT` are removed; the `/ink-se-gala-aande/` index stays. Needs a snapshot-restore plus `migrate-all` plus deploy test on nuwe-ink. |
| P1 | Payment | Out of scope; separate work | |

**K6 as revised (2026-11-11):** announcements, gala announcements, closure notices and reminders are tagged and kept live, not imported or redirected. Results Pages are tagged `ink_old_uitslae` and feed the results route (K5, K7). Proposed meta names (not yet in code): `ink_old_kompetisie_aankondiging`, `ink_old_gala_aankondiging`, `ink_old_kompetisie_sluiting`, `ink_old_gala_herinnering`, each holding the year. Confirm names before writing the SQL.

**Production ids supplied by the owner (2026-11-11), for the tag SQL:**

| Role | Ids (year: id) |
|---|---|
| Competition announcement | 2026: 65779, 2025: 62712, 2024: 59124, 2023: 54585, 2022: 49428, 2021: 44831, 2020: 39633, 2018: 18477, 2017: 15351 (none for 2019 or 2016) |
| Closure notice | 2022: 49440 |
| Results | 2025: 64611, 2024: 61225, 2023: 57691, 2022: 52672, 2021: 47913, 2020: 43438, 2019: 37975, 2018: 28946, 2017: 17991, 2016: 9878 |
| Gala announcement | 2023: 57247, 2018: 27697, 2017: 17109, 2016: 5945 |
| Gala reminder | 2017: 16991, 2016: 7455 |
| Gala photos | 2025: 64688, 2024: 61228, 2023: 57753, 2022: 52674, 2021: 47916, 2020: 43440, 2019: 37984, 2018: 29087, 2017: 17993, 2016: 9949 |

2016 results is 9878; 7802 (tagged `ink_old_gala` in the current SQL) is its duplicate.

Also: mark **Epic 12B / story 12B.1** ("annual-cadence competition reusing the monthly machinery") as superseded in `_bmad-output/planning-artifacts/epics.md` and its story file `_bmad-output/implementation-artifacts/12b-1-annual-competition-management.md`. The spec header already says it is stale. The `CadenceType::Jaarliks` code from 12B.1 exists but competitions must not use it.

## 5. Gotchas

- **`PostTypes::args()`** was changed for `kompetisie` (non-public, `show_ui`). Read it before adding fields or capabilities; no new capability is needed (`ink_contents`).
- **Cache:** `/kompetisies/` is cached; editing a `kompetisie` must purge it (spec §3.5). The new results route needs the same purge on save.
- **Slug and rewrite clashes:** run `check-slug-collisions.php` after adding `/kompetisies/<jaar>/uitslae/`. `uitslae` and `gala/<jaar>` are rewrites under existing URLs, with no slug of their own.
- **Redirect priority:** `RedirectGenerator::maybeRedirect` runs on `template_redirect` at priority 5. If an old results URL lands on a poem, check that and the map (runbook "Redirects").
- **`migrate-kompetisies` must run before `clean-shortcodes`** and cannot be re-run after it (restore the pre-migrate snapshot and run `migrate-all` again). The new role must keep that ordering.
- **Compare typed titles and names only through `Ink\Migration\TextKey`.**
- **Afrikaans copy:** new strings need keys in `I18n/Terms.php` (glossary first) or a copy-debt marker. Never AI-translate Lovable's English.
- **Migration inputs:** exactly three files in `../migration-data/`; no new CSV may be introduced for this work. Production tags are meta set by SQL on the old site, not an input file.
- **Design fidelity:** the results page, any new section on `/kompetisies/`, and the hero state are visual work. `CLAUDE.md` says new pages follow the *inverted* method: a ratified build spec plus audit-ids **before** code; the capture/diff harness (`tools/fidelity/accept/gate-all.sh`) is the acceptance gate. **The Lovable design (`../verse-haven`) has no results route for competitions or winner list**; check whether it does before inventing a layout, and escalate if it doesn't. §14 sets behaviour only, **not** a layout or audit-ids.
- **Temp files** go in `./tmp/`, not `/tmp` or scratchpad directories. Do not add `Co-Authored-By` trailers (`CLAUDE.md`).

## 6. Not verified, and open

1. **Not ratified:** §14 has not been through the delta process (recipe-gap ratification, audit-ids). The layout of the results page is undesigned.
2. **Page ids: supplied** (table under §4). Still open: the meta names for the kept announcements (proposed under K6), and what the existing `migrate-kompetisies` should do now that its "gala" Pages are results Pages.
3. **Skryf form:** I read `ChallengeLinking` only for its public methods and the term creation. I did not read how the Skryf form renders the choices, so K2's UI work is unscoped.
4. **Tests:** existing tests are in `tests/Unit/Competitions/` (`MigrationTest.php` was seen; others were not listed). K1 to K6 need unit tests, and K2/K5 need integration or e2e coverage (`phpunit.integration.xml`, `playwright.config.js`).
5. **Winner profile display:** whether a user-linked competition win should also appear on the writer's profile or Gradering is **undecided** and not in the spec. Default: it does not (competitions are not challenges); ask the owner before building it.
6. **Second competition in one year:** the spec uses the `-2` key as galas do, but the route for it is not defined.
7. **Whether `migrate-kompetisies` has run on the current dev database** is unknown; check before testing the 2025 gala.

## 7. How to test it (owner's scenario)

The owner wants to set up the 2026 competition (closed), the 2026 gala (imminent), and load the 2025 gala's winners. The exact worklist:

**Create 1 `kompetisie` post** for 2026: `tipe` = `jaarliks`, `jaar` = 2026, title, `beskrywing`, `uitgawe`, `oopdatum` and `sluitingsdatum` both in the past (so the state is `closed`), the `kat1..3_*` categories, `gala_naam`, a near-future `gala_datum`, `gala_deure`, `gala_plek`, `gala_drag`, `gala_program`, `kaartjie1..3_*` (placeholder prices until P1). No photos or results. Expected: the hero shows the generic announcement, the gala band shows the planned 2026 gala with tickets and Reserve, and there is no results link.

**Edit 1 existing post, do not create:** the migrated `gala-2025` `kompetisie` (check it exists; run `migrate-kompetisies` first if not). Add a results intro and winner records (at least one user-linked, at least one free-text such as a Facebook-group or book prize, at least one with a photo picked from that year's gallery), then publish the results. Expected: `/kompetisies/2025/uitslae/` works and is linked from the 2025 chip and gala page.

**Prerequisites that are not posts:** the default announcement post (`standaard = ja`) exists (`Module::ensureDefault()`); any user accounts to be linked as winners exist.

**Note:** with a 2026 post present, 2025's state is `archive`, so the "Nou bekend" hero does not show for 2025; its results stay reachable from the chip and gala page. To test entries (K2) add a third `kompetisie` whose window is open now.

## 8. Deliverables so far

- `docs/lovable-delta-2026-10/specs/05-kompetisies.md`: **§14** appended; header scope line updated (2026-11-09).
- This handoff.
- Nothing else changed. In particular `tag-old-competitions.sql`, `epics.md`, the migration code and the story files are untouched.

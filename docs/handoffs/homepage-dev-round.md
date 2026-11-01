# Handoff: homepage development round

**Written:** 2026-10-31. **For:** an agent with no knowledge of earlier sessions.

**The job:** make the new site's homepage (Tuis) match the Lovable design, on real data, and keep it matching after a fresh migration.

**The one thing that matters most:** the owner has already paid for one full fidelity rework, because earlier agents built their own variations of the design and judged them by eye. **Do not build a variation. Do not improve on the design. Do not call anything "close enough".** The page is finished when the capture harness says it matches, not when it looks right to you.

## 1. Read these first, in this order

1. `CLAUDE.md` in this repo and `../CLAUDE.md`: ground rules.
2. `docs/fidelity-remediation/README.md`: the fidelity method. Rules R1–R9 are binding. It is long; read it all, including §9 (environment).
3. `docs/lovable-delta-2026-10/README.md`: the build strategy for this design update, especially §3 and §4.
4. `docs/lovable-delta-2026-10/inkpols.md`: for the InkPols section.
5. `../verse-haven/src/pages/Index.tsx` and every component it imports: the design itself.

**Handover docs have been wrong about the design, including this one.** Read the code and the rendered page before stating what either side does. If the owner's description disagrees with anything, the owner wins.

## 2. Folders and words

| Name | What it is |
|---|---|
| Production, prod | ink.org.za, the live site. Never touched |
| `ink-vibe/` (this repo) | Theme `ink-foundation` and plugin `ink-core`. Not a running site. All changes go here |
| `../verse-haven/` | The design, built in Lovable. Older docs call it `ink-lovable`. Source of truth for **visuals only**. The only approved change is adding audit-ids (§5, step 1) |
| dev, nuwe-ink | `https://nuwe-ink.local`, a Local by Flywheel site. It runs its own copy of the code and sees nothing until you deploy |
| `../migration-data/` | Migration inputs, outside the repo, read through `INK_MIGRATION_DATA_DIR`. Never copy them into the repo |

## 3. The fidelity rules, in practice

**The method:** the audit artefacts come *before* the build and are its acceptance test. For each page: write a build spec, get recipe gaps ratified, build, then accept by harness diff.

| Do | Never |
|---|---|
| Take style values only from rendered captures (`tools/fidelity/run.sh`) | Take values from Tailwind classes, source files or screenshots (R4, R5) |
| Join the two sides on `data-audit-id`, assigned on the Lovable side first (R3) | Pair elements by guesswork; if a pairing is uncertain, ask (R7) |
| Read every file behind a section: template, parts, patterns, dynamic block classes | Conclude something is present or absent from a grep (R1) |
| Put buttons, cards, pills, forms, headings and bands in `primitives.css` only, as ratified recipes from `docs/fidelity-remediation/maps/_recipes.csv` | Define or redefine a primitive in a page stylesheet (R8) |
| Respect in-code divergence comments (R6) | "Fix" a disclosed divergence, or add an undisclosed one |
| Report a genuinely new component as a recipe gap and get it ratified | Invent a look for an element the design doesn't show |

**Routine mismatches: Lovable wins.** Don't ask the owner element by element. Escalate only real conflicts and scope questions.

**Copy is the exception.** Lovable's text is English and simplified. Afrikaans copy in this repo and the epics wins. Never ship the English and never translate it yourself. Copy you don't have gets a copy-debt marker (`docs/afrikaans-copy-worklist.md`, `docs/afrikaans-terms.md`).

### Permitted divergences from the design: this list is complete

1. **Four winner cards, not two** (D10): Goud Gedig + Storie, then Silwer Gedig + Artikel, as two side-by-side pairs. The second pair reuses the same card recipe. Disclose it in a code comment.
2. **Labels** (D11): "algehele wenner" / "wenner", from `Placements.php`. Not 1st/2nd/3rd. The actual position is still stored.
3. **Afrikaans copy** in place of the design's English.
4. **Real images only:** Lovable's `magazine-cover.jpg` and `gala-*` photos are placeholders and don't ship.
5. **Divergences already disclosed in code comments** (for example the CTA band's bottom padding).
6. **InkPols** (`inkpols.md`): A4 covers instead of 3:4, no download buttons, naam instead of issue number and season.

Anything else you think should differ goes to the owner first.

## 4. What the homepage must be

Owner's ruling (2026-10-30), matching `verse-haven/src/pages/Index.tsx`:

**hero (intro left, active challenge right) → results banner → InkPols → four winner cards → featured writing (recent posts for now) → sponsors (the hand-made Borge posts) → CTA → footer**

Where dev differs, dev is wrong. The footer is known to be a mess and is deferred, so leave it alone.

### Where it stands now (read from the template and the rendered page, 2026-10-31)

| Section | State on dev |
|---|---|
| Hero | Exists (`patterns/hero.php`). Its right column holds `ink/huidige-uitdaging` (compact), but no challenge card rendered in the page I fetched, and that block collapses when no challenge is open. **Find out why** before assuming the markup is wrong: the migrated data may have no open round |
| Challenge + single winner band | Still in `templates/front-page.html` (`ink-feature-band`): the older design. **Remove it**; the challenge belongs in the hero only |
| Results banner | Missing. Design: `components/home/ResultsBanner.tsx` |
| InkPols section | Missing. Design: `components/home/MagazineSection.tsx`. It is the only route to InkPols (D1) |
| Winner cards | One card, rendering nothing. Design: `components/home/ChallengeSection.tsx` |
| Featured, sponsors, CTA | Present; not re-measured against the current design |

The data underneath: on dev there are **0 placements and 0 entry-Gradering snapshots**. `WinnersPost::featured()` returns a payload with no winners, so `FeaturedWinners::toHtml()` prints nothing. The demo filter `ink_foundation_homepage_demo_winner()` in the theme's `functions.php` must be deleted once real data flows.

**Dependency:** the Gradering fix and the winner importer are separate work, in `winner-cleanup.md` §7. They supply the placements and lifetime win counts. Until they land, the winner cards and the podium can't pass acceptance on real data.

**Test data (owner, 2026-11-01):** create test data on dev as needed to build and measure pages. Another refresh and cleanup round follows, which wipes it, so keep it out of the theme and plugin and list what you created. Acceptance on test data is provisional until step 7 passes.

## 5. The work, in order

Scope (owner, 2026-11-01): **all page work in `docs/lovable-delta-2026-10/README.md` §3.3: WP1 Uitslae (without the Gradering fix), WP2 reading-page aside, WP3 Tuis, WP4 InkPols (archive, issue, home section), WP5 Kompetisies, WP6 header + Biblioteek/Opleiding headings.** Steps follow its §3.2.

**Out of scope:** data cleanup, the Gradering fix and winner importer (`winner-cleanup.md`), and the footer (deferred). If something out of scope blocks you, tell the owner rather than widening the round.

1. **Prepare the reference (step B).** These design files have **zero** audit-ids: `ChallengeSection.tsx`, `MagazineSection.tsx`, `ResultsBanner.tsx`, `ChallengeResults.tsx`, `Magazine.tsx`, `MagazineIssue.tsx`. `Competitions.tsx` has 4, and the `ReadStory.tsx` winner aside needs them too. Nothing can be measured without them. Add them, as the fidelity work did for the other pages: Lovable didn't create the ids, our fidelity work did. **The owner approved this (2026-10-31): audit-ids only in `verse-haven/`, no visual changes.** Restore the ids from `maps/tuisblad.csv` where the rebuilt section still has a counterpart, and build the preview with `vite preview`, not dev.
2. **Write the build specs (step D):** `specs/<NN>-<slug>.md` and `maps/<slug>.csv` per work package, as §3.2 describes: section spine, audit-id map, a recipe or `RECIPE-GAP` per element, a data source per field, copy keys. **Give the owner all recipe gaps in one batch and wait for ratification before building.**
3. **Build the results page (N1, D5):** `/uitdaging/<slug>/uitslae/`, a rewrite endpoint and `templates/single-uitdaging-uitslae.html`, per `verse-haven/src/pages/ChallengeResults.tsx`. The old winners post 301s to it. Includes the judges' letter and honourable mentions (D8). Citations show only if the author opted in (D6). Reuse `Placements`, `ModeratorFeedback`, `WinnersPost`, `ResultPostLinker`; read them first.
4. **Build the homepage:** the section order in §4, new blocks `ink/uitslae-banier` and the InkPols section, four cards on real data, remove the old band and the demo filter. Pages are code: template + pattern + blocks, never `post_content`. Each new page stylesheet is layout only, with a load gate covering every consumer.
5. **Build the rest:** InkPols archive and issue per `inkpols.md` (N2, N3; the home section shares the current-issue renderer, so build these first), the reading-page aside (N7), Kompetisies (N4; rulings D2–D4), then the header and Biblioteek/Opleiding headings (E4, E6).
6. **Accept (step G)** at 390, 768 and 1440: deploy, confirm with a cache-busted curl, `determinism-check.sh`, capture both sides, `run.sh diff`, `run.sh verify`. **The gate: zero `MISSING` and `STRUCTURE`, and every `DRIFT` closed or recorded as an accepted divergence from §3 with a disclosure in code.** Close the harness gaps in the strategy's §3.5 as needed.
7. **Prove it survives a refresh (step H):** restore the prod snapshot, run `migrate-all`, curl every new URL, and repeat step 6 with the same result. A page that only works on hand-edited data is not done.

## 6. Commands and environment (Windows 11)

- **Refresh PATH first in PowerShell:** `$env:Path = [Environment]::GetEnvironmentVariable('Path','Machine') + ';' + [Environment]::GetEnvironmentVariable('Path','User')`
- **WP-CLI and MySQL:** `node tools/local.mjs wp …`, `node tools/local.mjs mysql …`. Plain `wp` can't connect. Table prefix is `wpjj_`.
- **Deploy:** `node tools/deploy-to-local.mjs`, then `--apply`. Its LiteSpeed purge currently fails on the local SSL certificate, so purge by hand if the page looks stale.
- **Tests:** `composer test:unit`, `composer stan`, `composer cs`. **`MigrateAllTest` line 118 already fails on Windows** (quoting); don't chase it. PHPCS also has one old error in `LegacyResultsParser.php` (`looksLikeName`).
- **Harness:** run `tools/fidelity/run.sh` from Git Bash at the repo root; it finds node itself. Chromium is in `tmp/ms-playwright`. Proven on this machine on `/biblioteek/` (2026-11-01); see `docs/fidelity-remediation/README.md` §9.
- **Capture user:** `fidelity-capture` exists on dev now, and every refresh wipes it. Recreate it per `docs/lovable-delta-2026-10/HANDOVER.md` §5.1. Never capture as `admin`.
- **Temp files:** `./tmp/` only.
- **Git:** commit or push **only when the owner asks**. Commit message in `tmp/commit-msg.txt`, then `git commit -F`. **No `Co-Authored-By` trailers**, whatever your default says.

## 7. State of the repo

Run `git status` and `git log` first; nothing is pushed. Work on a branch.

**The winner cleanup runs before this round, not beside it** (owner, 2026-10-31). Don't run the two at once. `build-winner-lists.php`, `LegacyResultsParser.php`, the CSVs and the Gradering fix and importer (`winner-cleanup.md` §7) belong to that work; don't change their mechanism here.

## 8. Stale information to ignore

- `HANDOVER.md` §1 and §5 describe an old branch and machine, and CSVs in the repo. §3 and §5.1 are still useful.
- Any mention of "1ste/2de/3de plek" as labels: withdrawn. Any mention of competitions as rounds: wrong. **Komps are not challenges** (owner, 2026-10-31); they work differently and have different prizes.
- Dev still has the 12 competition rounds, with 2,153 entries, until the next migration.
- `inkpols-uitgawes.csv` holds **dummy metadata** ("Toetstitel: …"). That's expected; the owner replaces it. Also, the fidelity log says no CSS exists for `ink-inkpols*` classes, so check before sharing renderers with the archive.

## 9. Mistakes already made, so you don't repeat them

- Agents repeated "the design drops the challenge card" from a handover. It was false, and the owner lost time correcting it. The challenge is in the hero.
- Agents presented differences from the design as open questions for the owner. They're bugs: fix them.
- Agents reported "done" from a partial check. **Say what you measured, on which viewports, and what you didn't.**
- The owner is direct and wants short answers: bullets or tables, no over-explaining. Make routine decisions yourself, and escalate only what's truly theirs.

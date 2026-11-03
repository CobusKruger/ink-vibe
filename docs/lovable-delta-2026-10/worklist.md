# Delta pages: worklist

Ticked only when an agent has reported it and the orchestrator has read the report. "Built" is not "accepted": a page is accepted only when the gate passes and the review of its accepted differences is done.

## Done

- [x] Audit-ids on all design pages, pushed to verse-haven (`cbd9d3a`)
- [x] Harness running on Windows, with a pass/fail gate
- [x] Build specs 01-07
- [x] 41 recipes ratified; 38 in `primitives.css`, RG-UI-01 to -03 added with the Challenges page
- [x] `ink-core`: month-based round dates, hero fallback, Uitslae route and redirects, InkPols admin, competition post types
- [x] `kompetisies-oud.csv` regenerated with every photo (3,379), imported on dev; `/kompetisies/gala/<jaar>/` built (2026-11-02)
- [x] Built and deployed, not accepted: Uitslae, Lees aside, Tuis, InkPols archive and issue, Kompetisies, header and mobile menu, Challenges page
- [x] Header: "My profiel" off the desktop row, mobile menu below 1024
- [x] 14px body text fixed (Simple CSS deactivated on dev, added to staging list and production audit)

## Done: acceptance round one (shared fixes)

- [x] Limit the gate's accepted differences by width
- [x] Shared style drift: card padding, chips, buttons, tabs, shadows, card title hover, gold button at 390
- [x] Before and after check on Biblioteek and Opleiding
- [x] Header through the full gate, signed in and out
- [x] Sign-out click at 390 and 768
- [x] Challenges: past cards back to full width
- [x] Challenges: review its accepted differences
- [x] Tuis hero: "Lees meer" on a closed round
- [x] Deploy script clears the pattern cache; find out why `MembershipDates.php` keeps listing
- [x] Copy scan works on Windows; baseline raised

## Running

**Acceptance round two: Tuis, Uitslae, Lees**
- [x] Tuis through the gate
- [x] Uitslae through the gate (all three tabs)
- [x] Lees: response upvote and poem dedication built; gate 0 open in all 18 states (signed in and out, 3 viewports, storie/artikel/gedig)
- [x] Round-page results banner measured
- [x] Tab keyboard use (identical to the design)

**Acceptance round three: InkPols, Kompetisies**
- [x] Gala photos and galas discovered from the old pages by the migration; CSV (now 285 bytes) holds optional overrides and the 2022 competition only; new galas redirect without code changes. Untested on a real refresh (shortcodes are already cleaned on dev)
- [x] Galas and the 2022 competition discovered from production tags (`docs/migration-tools/tag-old-competitions.sql`, checked on dev, ready for the owner to run on production), title search as fallback with a warning
- [ ] Owner: run the tag SQL on production (backup first) before the next snapshot
- [x] Four photos plus "Sien alles" on `/kompetisies/`
- [x] New page `/kompetisies/gala/<jaar>/` with infinite scroll
- [x] InkPols archive and issue through the gate
- [x] Kompetisies through the gate (all states)
- [ ] Sideways scroll at 390 on Kompetisies (526) and the issue page (422): clears with real copy; re-check when copy lands
- [x] Add and edit in a real admin session: publish guard, media picker, competitions

## Queued

**Finish**
- [x] Word-pasted poems: no huge gaps; 4 stanza-detection and empty-line bugs fixed in GedigBody (in repo, not yet deployed; changes how 1,439 of 11,250 poems render)
- [x] 16px check: no regression from the 16px body or the lost input margin on any page; `meld-aan` capture aborted because the page is now `/teken-in/` (map still named `meld-aan.csv`). Pages with Lovable counterparts still fail the gate on old Stage 1 maps (missing-ink rows), unrelated to this round
- [x] Refresh proof (2026-11-02): restore + tag SQL + `migrate-all` clean (20 steps ok), 54 URLs as expected, gates 68 of 68 with MISSING/STRUCTURE/DRIFT 0 on fixtures that have since been removed. Open: `verify` intermittent on `reading-response-author` width (0.015-0.3px); 136 rounds created vs 128 expected (competition-like rounds, winner-cleanup domain)
- [x] Docs in delta README §5 updated: every row DONE (the harness row, `targets.json` and `agent-brief.md` §2.1, closed 2026-11-03)
- [x] Acceptance gate promoted out of `tmp/` into `tools/fidelity/accept/` (one command, `gate-all.sh`; 68 of 68 reproduced 2026-11-03; the 6 closed-round gates were removed with the fixtures)
- [ ] Commit, when the owner asks
- [ ] (running) PHPCS: triage and fix the 46 errors (45 at HEAD + 1 new); then re-run the full gate (tools/fidelity/accept/gate-all.sh) once more on the final code

## Separate work, not in this list

- Gradering fix, competition exclusion and winner importer: built 2026-11-03 (`docs/handoffs/winner-cleanup.md`); the winner cards and the podium get their first acceptance on real data at the next refresh.
- Footer (deferred). LET WEL text (later).

## Waiting on the owner

- [ ] Afrikaans copy for copy-debt markers, including the menu button label
- [ ] Before launch: the InkPols CSV (now 8 rows, glans format, real-looking content) was filled by the owner on 2026-11-03: confirm it is final; the 8th issue (september-oktober-2026) is a draft because its PDF and cover are not in the production snapshot; gala highlights and captions (optional)
- [x] InkPols featured items changed to glans-1..3 with free-text tipe: fields, publish check, rendering, migration CSV, tests (1873 passed); InkPols + Tuis gates 0 open (issue-mid and issue-old to be re-run in the final full gate)

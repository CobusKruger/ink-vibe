# Full refresh worklist (nuwe-ink)

**Written 2026-11-03**, from the docs changed 2 to 3 November and checked against the code, the CSVs and the live Local site. Nothing here has been run. Owner tags: **[O]** owner, **[A]** agent, **[O/A]** either.

## Read first: what changed

The Gradering fix, the competition-round exclusion and the winner importer were built on 2026-11-03 (`docs/handoffs/winner-cleanup.md` §2) and run once on the existing dev copy. A real refresh is their first full proof. The refresh now has **21 `migrate-all` steps**; the new one is `winners` (`wp ink import-winners`, after `result-posts`). Wins come from the old result posts; no winner list is an input (owner, 2026-11-03).

**Migration inputs are exactly three files** in `../migration-data/`: `writer-accounts.csv`, `writer-aliases.csv`, `inkpols-uitgawes.csv`. Everything else is in `../backup-data/` and no code reads it (`MigrationDataTest` enforces this).

## Part 1. Owner prerequisites

- [ ] **[O]** Run `docs/migration-tools/tag-old-competitions.sql` on **production**, after a database backup. Closing SELECT must list **21 rows** (10 + 10 + 1). Skipping it: galas are found by title with 20 "tag it" warnings, and the 2022 competition is **not** imported
- [ ] **[O]** On production, edit two result posts so Francois Terblanche (`#46`) is told apart from Francois Meyer/GEDAGTES (`#66`): `/wenners-november-2017-oop-projek/` ("3 de plek: Francois Meyer met Konform") and `/uitslae-september-2019-verplasings-projek/` ("WENNER: No. 13 – Francois Meyer met Maria Magdelena"): change "Francois Meyer" to "Francois Terblanche". `writer-accounts.csv` is already updated
- [ ] **[O]** Take a fresh prod export **after** the SQL and the two edits: full DB dump and `wp-content/uploads`, at the same moment. The one in `../live-import/` is from 2026-10-28 and has no tags. Keep it outside the repo
- [x] **[O]** `inkpols-uitgawes.csv` is final (8 rows, 2026-11-03). Row 8 (`september-oktober-2026`) points at `2026/11/…` files that were not in the Oct-28 snapshot: it stays a **draft** unless the new uploads contain them
- [ ] **[O]** Afrikaans copy for the copy-debt markers (not a refresh blocker)

## Part 2. Code (done 2026-11-03; commit before the refresh)

- [x] Gradering fix (D7, D13): `Pools`, `Placements`, `Coverage` ties, `WinnerBanner`, `Ingestion::awardPromotions`
- [x] Competition rounds excluded: **128 rounds expected**, not 136
- [x] Winner importer as a `migrate-all` step, no new input file; `WinRecords` is the store; lifetime count from the records
- [x] Unit tests (suite: 1,889 passing, 4 pre-existing integration failures), `composer stan` clean
- [ ] **[O]** Commit. The prep script deploys from the repo, so the code to test must be the code in the working tree. **Uncommitted today:** all of the above, the fixture removal, the folder reorganisation and the doc corrections

## Part 3. Refresh

Windows. PowerShell: refresh PATH first (`$env:Path = [Environment]::GetEnvironmentVariable('Path','Machine') + ';' + [Environment]::GetEnvironmentVariable('Path','User')`). Git Bash has no node or PHP on PATH. The Local site `nuwe-ink` is running now (`https://nuwe-ink.local`, DB port 10011, prefix `wpjj_`).

1. [ ] Pre-flight: Local site running; `node tools/local.mjs info` shows the site; `git status` clean; the three files in `../migration-data/` present
2. [ ] Dry run: `node tools/rehearsal-prep.mjs --sql ../live-import/<new>.sql --wp-content ../live-import/wp-content` (a copy of prod's whole wp-content, with the new `uploads/2026/11` in it; its uploads, plugins, mu-plugins and themes are all mirrored in). Read what it says (missing data files stop it before the DB is touched)
3. [ ] Apply: same command plus `--apply`. About 17 min with 19 GB of uploads. It replaces the current nuwe-ink database. Expect the LiteSpeed purge to fail on the self-signed certificate (harmless)
4. [ ] Check the prep output: it lists the retired plugins and FluentSMTP it deactivated (`RETIRED_PLUGINS` in `tools/rehearsal-prep.mjs`) and ends with "retired plugins still active: none". `simple-css` must be off or the 16px body fails the design check. Confirm with `wp plugin list`
5. [ ] Pre-flight checks (read-only): `WP_ENVIRONMENT_TYPE` not production; `INK_MAIL_SINK` set; `INK_MIGRATION_DATA_DIR` set; `check-slug-collisions.php` (before); counts of `bibliotekon` / `opleiding`; categories `projekte` and `kompetisie` exist. Confirm the tag rows: expect 21 `ink_old_*` meta rows
6. [ ] Snapshot: `node tools/local.mjs mysqldump --single-transaction --result-file=tmp/nuwe-ink-pre-migrate.sql` (overwrites the old one: keep a copy if you want it)
7. [ ] Run `node tools/local.mjs wp ink migrate-all`, **in the background**, timed (about 6 min; `kompetisies` is long). It stops at the first failure; re-run to resume; `--from=<step>` / `--reset`. Needs the sandbox off in an agent session
8. [ ] Read every step's verdict. Investigate any `silent` / `all-zero`
9. [ ] Do **not** re-run `migrate-kompetisies` after `clean-shortcodes` (restore the snapshot instead). Never use `--force` on `kompetisies` or `inkpols` on the live site
10. [ ] Repeatability: restore the step-6 snapshot, run again, time it, compare counts (all but gradering matched on the last two runs)

## Part 4. Verify

**Agent checks** (`migration-runbook.md` "Verify" table, with the corrections below):

- [ ] `docs/migration-tools/post-counts.php`: skryfwerk about 150; biblioteek 312; opleiding 179; Gedigte shelf 91; Vrystaatstories 68; Oom Pine 65; Liegstories 29; Woordeboeke 7; Digkuns about 168
- [ ] Challenges: **128**; announcements about 133; entries about 6,700; Uitdagings dated, October 2026 round first
- [ ] Gradering and winner count: `docs/migration-tools/validate-gradering.php` (2026-11-02 copy: 111 graded, brons 70, silwer 28, goud 13; one unresolved by-line, Agie, 3 wins, deleted account); fuzzy matches; the `winners` step line (dev copy: 1,573 wins, 1,571 recorded, about 1,420 posts found, 14 with no account, 0 without a round); a Gedigte-only check (no Storie/Artikel win promotes); lifetime counts on a sample of writers, especially anyone near 5 or 20 wins and Francois Terblanche (`#46`) against Francois/GEDAGTES (`#66`): expect 2 and 7 wins
- [ ] `wp ink report-result-posts --file=tmp/result-posts.csv` (writes a scratch report in `tmp/`; not an input to anything)
- [ ] InkPols step line: expect 8 rows; 7 published and 1 draft **unless** the new uploads hold the September/October files. Run `wp ink migrate-inkpols --force` to read the draft warnings (reconciles, no duplicates; refresh only)
- [ ] Kompetisies step line: the last run said "11 created, 3379 photos linked, 3 rows or photos skipped, 21 old Pages trashed". Expect **0** "found by title; tag it" warnings with the SQL applied. Photos per year: 656, 441, 464, 571, 431, 386, 166, 101, 103, 60
- [ ] URLs: `curl -I` each row of the Verify table's URL list, cache-busted (the refresh proof counted 54). Include `/kompetisies/gala/2025/` 200 with 24 photos, `/kompetisies/gala/1999/` 404, the 21 old gala paths 301 to `/kompetisies/`, and `/ink-dig-en-skryfkompetisie-2022/` 200
- [ ] `check-slug-collisions.php` (after): only `afrikaanse-taalgids`
- [ ] `wp ink verify-redirects`, `wp ink verify-media` (last run: 161 missing upload files, up from 140: re-read against the new export), `wp ink audit-production`
- [ ] `vendor/bin/pest`, `composer stan`, `composer deptrac`

**Owner checks** (`migration-runbook.md` "Checks you do"): real pages (a Gedigte poet, Flipvis, a Woordeboeke item, an Opleiding module, an old challenge, `/ontdek/`, the home winners); old URLs logged out; challenge flow with judge emails in Mailpit (Mailpit routing is still unconfirmed); **Gradering spot-check, especially anyone near 5 or 20 wins**; accounts and memberships (member, writer, editor); account deletion blocked.

## Part 5. Capture user and gate

- [ ] **[A]** Deploy: `node tools/deploy-to-local.mjs` (dry run), then `--apply`; purge cache by hand if the page looks stale (it clears the pattern cache)
- [ ] **[A]** Recreate the capture user (never `admin`): `node tools/local.mjs mysql -e "source tools/fidelity/create-capture-user.sql"`, `node tools/local.mjs wp eval-file tools/fidelity/set-capture-password.php`, then `run.sh login`
- [ ] **[A]** Gate: `tools/fidelity/accept/gate-all.sh` (Git Bash; Lovable preview on `http://localhost:4175` via `vite preview`; Chromium in `tmp/ms-playwright`). Fixtures and the closed-round gate are gone (2026-11-03), so gates that need winners, responses or competition listings (Uitslae, the Lees winner aside, Kompetisies states) will fail or lack data until the importer and real data exist. Last result before the removal: 68 of 68, MISSING/STRUCTURE/DRIFT 0. **With real winner data the winner cards and podium get their first real acceptance**
- [ ] **[A]** Open items that touch the final gate: sideways scroll at 390 on Kompetisies (526) and the issue page (422) should clear with real copy; PHPCS triage of 46 errors is last, then one more full gate on the final code

## Part 6. Close out

- [ ] **[A]** Record the timings, the freeze versus incremental recommendation (`migration-runbook.md` Cutover), and every count that differs from expectation
- [x] Stale docs corrected (2026-11-03)
- [ ] **[O]** Cutover remains separate (`migration-runbook.md` Cutover): a second fresh rehearsal, freeze decision, DNS only after all checks pass. Production is never touched by any step above except the tag SQL in Part 1

## Not verified in this list

- The 54-URL list: the docs give the pattern, not a file. I did not find one script that runs it
- Whether the Oct-28 export already holds the September/October InkPols files (the CSV points at `2026/11/…`)
- Whether Mailpit receives the sink mail on this site
- That `kompetisies` works on a real refresh: dev's shortcodes are already cleaned, so the only proof is the next real run

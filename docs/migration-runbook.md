# INK migration runbook

Rebuild the new site from a fresh copy of production. The database is disposable and every human decision is a committed file, so a rehearsal and the real cutover are the same procedure.

**Status (2026-10-25):** the pipeline is built, merged to `main` and unit-tested. It has never run on a real database. The first rehearsal needs a fresh prod copy, which only you can supply.

## What remains for you

### A. Put a fresh copy of prod on nuwe-ink.local

1. **Clean up prod first.** The posts reassigned to the wrong user after account deletions (your task). A copy taken earlier carries the damage into the run.
2. **Take the copy from prod:** a full database export and the `wp-content/uploads` folder. Take both at the same moment; a database newer than its uploads gives broken images.
3. **Optional backup:** export the current nuwe-ink database first (`wp db export`). It holds the design-rework test data. Everything in it is disposable, so only keep it if you might want it.
4. **Replace the database.** In the Local shell for nuwe-ink: `wp db reset --yes`, then `wp db import <prod.sql>`. The table prefix must match `$table_prefix` in `wp-config.php` (currently `wpjj_`); if prod uses another prefix, change it in `wp-config.php`.
5. **Point it at the local URL:** `wp search-replace 'https://ink.org.za' 'https://nuwe-ink.local' --all-tables --skip-columns=guid`, then `wp rewrite flush`.
6. **Replace the uploads folder** with the prod one (`wp-content/uploads`).
7. **Leave `wp-content/plugins` alone** except for `ink-core`: the other plugins (BuddyPress, WooCommerce and its Memberships and PayFast add-ons and so on) already sit in the Local site, and the imported database expects them.
8. **Deploy this repo's code (from `main`)** with `tools/deploy-to-local.sh` (dry run: read what it lists), then `tools/deploy-to-local.sh --apply`. It mirrors `ink-foundation` and `ink-core` onto the Local site with `rsync --delete`, then purges caches. Needs the sandbox off.
9. **Activate:** `wp plugin activate ink-core` and `wp theme activate ink-foundation` if they are not already active.
10. **`wp-config.php`:**
    - `WP_ENVIRONMENT_TYPE` is already `local`; keep it anything but `production`.
    - Add `define( 'INK_MAIL_SINK', 'you@example.com' );` with an inbox you control. Without it, the site drops all outgoing mail (safe, but you cannot see the judge emails).
11. **Smoke check:** the site loads at `https://nuwe-ink.local/`, you can log in, and `wp eval 'echo wp_get_environment_type();'` prints `local`.

### B. Prepare for the process

1. **Winners data (gitignored, exists only on your machine).** `docs/data-cleanup/winners/` holds `tier-import.csv`, `writer-accounts.csv`, `writer-aliases.csv` and the winner lists. Back the folder up; it is not in git. The tier file is the July one with the corrected thresholds (silwer 5–14, goud 15+): 70 brons, 26 silwer, 14 goud. It does not yet contain the June to September 2026 results; the implementing agent refreshes it after the first run (see below).
2. **Review the two Afrikaans strings I added** for the copy-debt log: the tie line on the Ingestion page (you already adjusted it), and any labels in `wp ink` messages (Afrikaans CLI text is unreviewed).
3. **Decide before cutover, not before the rehearsal:** freeze versus incremental pass (depends on the timed run), and the exact cutover date.
4. **Local database socket.** The agent's WP-CLI recipe in `tmp/` points at the old site's MySQL socket. After step A it may change; the agent finds the new one (Local, "Database" tab) or you give it.
5. **Tell the agent:** WP-CLI against the Local database needs the sandbox off.

## Hand-over prompt for the implementing agent

Paste this to a fresh agent session in this repo after step A is done.

````text
You are running the first rehearsal of the INK migration on the Local site nuwe-ink (https://nuwe-ink.local/, files at /Users/cobus/Local Sites/nuwe-ink/app/public). A fresh copy of the prod database and uploads has just been imported and the code from `main` is deployed. Production must never be touched.

Read first, in this order: docs/migration-runbook.md, docs/migration-plan.md, docs/migration-result.md (the first, failed rehearsal and what each symptom was), docs/migration-tools/README.md, CLAUDE.md. The decisions are in code, not in your head: PostReclassifier, LegacyContentMap, StaticRedirects, Challenges\Migration, SourceCategorySync, ResultPostLinker, LegacyResultsParser, MigrateAll.

Rules:
- All scratch files go in ./tmp/. Commit messages go in tmp/commit-msg.txt and are used with `git commit -F`. No Co-Authored-By or Claude-Session trailers.
- Work on a new branch off main. WP-CLI against the Local database needs the sandbox off; use the site's own PHP/MySQL socket (find it in Local; the old one was /Users/cobus/Library/Application Support/Local/run/UQ1ASIydU/mysql/mysqld.sock and has probably changed).
- docs/ and _bmad-output/ are written with the Edit/Write tools, not the shell. docs/data-cleanup/ is gitignored (member emails): never commit it.
- Do not "fix forward" silently. Report each step's output verbatim and what you concluded; change code only after the user agrees, except for clear bugs in the migration code that tests can pin.

Do this:
1. Pre-flight, read-only. Confirm WP_ENVIRONMENT_TYPE is not production and INK_MAIL_SINK is set. Run docs/migration-tools/check-slug-collisions.php. Record counts of legacy `bibliotekon` / `opleiding` posts and the category tree. Confirm the categories `projekte` and `kompetisie` exist. Stop and report if anything is missing.
2. Back up the database (wp db export into ./tmp/).
3. Run `wp ink migrate-all --tiers=/Users/cobus/Development/ink-vibe/docs/data-cleanup/winners/tier-import.csv`. Time it. It stops at the first failure and resumes on re-run. Report every step's verdict; investigate anything flagged `silent` or `all-zero` before moving on.
4. Run docs/migration-tools/post-counts.php and compare with the expectations in "Verify" below. Run `wp ink report-result-posts --file=tmp/result-posts.csv` and docs/migration-tools/validate-parser.php and validate-gradering.php.
5. Refresh the baseline gradering: use LegacyResultsParser (see validate-gradering.php) on the result posts newer than the July extraction (June to September 2026), resolve writers via writer-accounts.csv, writer-aliases.csv and the DB users, and produce a diff against tier-import.csv. Do not overwrite tier-import.csv: put the proposed file and the diff in tmp/ for the owner to approve. Unresolved writers are listed, not guessed.
6. Run the migration a second time from a restored backup, to prove it is repeatable, and time it.
7. Report: timings, every flagged step, every number that differs from expectation, and a recommendation on freeze versus incremental.

Known risks to look for: the Pages `biblioteek`, `opleiding` and `inkpols` share slugs with the new archives (the first rehearsal warned about this); the generated Uitdagings may lack what the judge-email flow needs (deadlines, briefs); result posts in layouts the parser does not read (it reports them as unparsed); a result post whose title has no year cannot be linked to its challenge and is reported.
````

## Verify

### Checks the agent can do (read-only unless stated)

| Check | How | Expected |
| --- | --- | --- |
| Every step ran and did something | `migrate-all` summary; `ink_migrate_all_state` | no `failed`, no unexplained `silent` / `all-zero` |
| Post counts by type, drafts, leftovers | `docs/migration-tools/post-counts.php` | thousands of `gedig`; `storie` and `artikel` in the thousands; `skryfwerk` roughly 150 or fewer; drafts stay drafts |
| Legacy content moved | same script | `biblioteek_item` about 312, `opleiding_artikel` about 179; `bibliotekon` / `opleiding` left only for the skipped winners pages and dropped placeholders |
| Collections and skills | same script | Gedigte shelf 91; Vrystaatstories 68; Oom Pine 65; Liegstories 29; Kinderrympies one collection; Woordeboeke 7; Digkuns about 168 |
| Challenges | same script | one Uitdaging per category under `projekte` / `kompetisie`; entries counted per round; the current rounds (mei to oktober 2026) have entries |
| Result posts | `wp ink report-result-posts`; same script | about 12 a year, all by Anze or Inkmoderator, kept as core posts, linked to their Uitdaging where the month and year match |
| Parser quality on this copy | `validate-parser.php`, `validate-gradering.php` | about 97% exact on the 120 known posts; tiers identical to `tier-import.csv` apart from the known 4 |
| Slug clashes after migration | `check-slug-collisions.php` (run before and after) | only `afrikaanse-taalgids` (different URL prefixes) |
| Redirect map health | `wp ink verify-redirects` (map audit only) | no chains, loops or empty targets |
| Media | `wp ink verify-media` | no missing files for migrated content |
| Hardening | `wp ink audit-production` | no authoring-only plugins active |
| Unit suite | `vendor/bin/pest`, `composer stan`, `composer deptrac` | green (deptrac keeps its 3 known violations) |

The agent cannot judge how pages look or read, whether Afrikaans copy is right, or whether a writer's tier is fair.

### Checks you do

1. **Look at real pages:** a Gedigte shelf poet, a collection (Flipvis), a Woordeboeke item, an Opleiding module (check the Digkuns tab), an old challenge and its entries, `/ontdek/`, a winners announcement on the home page.
2. **Old URLs:** try a few of each, logged out, and see where they land: `/kategorie/lede-bydraes/…` and `/biblioteek/projek-wenners/…` (to Ontdek); `/opleiding/digkuns/` and the other dropped section pages (to `/opleiding/`); a moved `/opleiding/gedigte/<module>/`; a moved dictionary; the old `plagiaat-letterdiefstal` post. A real crawl of the old URLs is described in `docs/redirect-integrity-runbook.md`.
3. **Challenge flow:** pick an old challenge, run the judge emails, and confirm they arrive only in your sink inbox. Paste a made-up results text with a tie and see the Ingestion page list it as a tie, not block it.
4. **Gradering:** open the proposed tier file the agent leaves in `tmp/` and approve or correct the differences, especially anyone near 5 or 15 wins.
5. **Accounts and membership:** log in as a member, a writer and an editor; check a membership is active and that BuddyPress profiles, follows and notifications still behave; check a deleted user's posts did not move to an odd account.
6. **Account deletion:** confirm a member cannot delete their account (it is blocked until the account-lifecycle spec exists).
7. **Anything that surprises you** in the post counts above.

## Run (reference)

```
wp ink migrate-all --tiers=/Users/cobus/Development/ink-vibe/docs/data-cleanup/winners/tier-import.csv
```

It runs, in order: sanitise, users, tiers, verify-subscriptions, library-training, posts, challenges, challenge-entries, result-posts, inkpols, clean-shortcodes, rebuild-navigation, follows, options, term recount, generate-redirects, verify-redirects --fix, verify-media.

- It stops at the first failing step. Fix the cause and run the same command: steps that succeeded are skipped.
- `--from=<step>` re-runs from that step on. `--reset` forgets all stored outcomes.
- A step flagged `silent` or `all-zero` did nothing; decide whether that is right (for example no InkPols on a test copy) before going on.
- Note the total run time. It decides the cutover window.

## Known duplicate and slug checks (staging copy, 2026-10-24)

- One real duplicate: skryfwerk 35127 = opleiding 21602 (`plagiaat-letterdiefstal`), handled by `StaticRedirects::DUPLICATES`.
- Library items sharing a title with a member poem or story (`Die Sprokie`, `'n Perd van 'n ander kleur`) are different works in different post types; their URLs do not clash.
- After flattening, no two migrated items share a slug within a post type, except `afrikaanse-taalgids` (Opleiding module vs the Page), which live under different prefixes. Re-run `check-slug-collisions.php` on the fresh copy; prod will have changed.

## Cutover

1. Rehearse at least once, and once more on a second fresh copy (the second run proves it is repeatable). Time both.
2. If the timed run is short: announce a short freeze (no new posts or signups), take the final prod copy, run, verify, flip DNS.
3. If it is too long: build the incremental pass for posts and redirects only (a `--since` watermark; not built yet). Memberships and orders are never merged incrementally.
4. DNS flip only after the checks above pass.

## Rollback

Production is never modified by any step: the pipeline works on a copy. Rolling back is leaving DNS where it is. After the flip, rollback is pointing DNS back to the old host (keep it untouched for the agreed period).

## Where the decisions live

| Decision | File |
| --- | --- |
| Category slug to content type, genre markers, result posts kept as posts, committed duplicate | `ink-core/src/Migration/PostReclassifier.php` |
| Legacy Biblioteek/Opleiding roots to shelf/collection/skill, skips, placeholders | `ink-core/src/Migration/LegacyContentMap.php` |
| Section redirects, dropped placeholders, dropped duplicates | `ink-core/src/Migration/StaticRedirects.php` |
| Challenge rounds (descendants of `projekte` / `kompetisie`) | `ink-core/src/Challenges/Migration.php` |
| Challenge entries follow their source category | `ink-core/src/Challenges/SourceCategorySync.php` |
| Result posts linked to their challenge | `ink-core/src/Challenges/ResultPostLinker.php` |
| Reading winners out of old result posts | `ink-core/src/Migration/LegacyResultsParser.php` |
| Writer tiers (gitignored, local only) | `docs/data-cleanup/winners/tier-import.csv` |
| Gradering thresholds | `ink-core/src/Tiers/PromotionEngine.php` (silwer at 5, goud at 15) |

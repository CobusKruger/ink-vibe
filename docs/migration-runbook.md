# INK migration runbook

Rebuild the new site from a fresh copy of production. The database is disposable and every human decision is a committed file, so a rehearsal and the real cutover are the same procedure.

**Status (2026-10-25):** the pipeline is built, merged to `main` and unit-tested. It has never run on a real database. The first rehearsal needs a fresh prod copy, which only you can supply.

## What remains for you

Only the export itself. `tools/rehearsal-prep.sh` does everything else.

1. **Bring the prod export:** a full database dump (`.sql` or `.sql.gz`) and the `wp-content/uploads` folder (a folder, `.zip` or `.tar.gz`). Take both at the same moment; a database newer than its uploads gives broken images.
2. **Dry run, read what it says, then apply:**

   ```
   tools/rehearsal-prep.sh --sql ~/Downloads/prod.sql.gz --uploads ~/Downloads/uploads.zip
   tools/rehearsal-prep.sh --sql ~/Downloads/prod.sql.gz --uploads ~/Downloads/uploads.zip --apply
   ```

   The Local site must be running. With `--apply` it replaces the current nuwe-ink database (an old test copy; nothing is kept) with the prod dump, rewrites the prod URLs to `https://nuwe-ink.local` (with `wp search-replace`, which is safe for serialized data; it never touches the bare domain, so addresses like `moderator@ink.org.za` stay as they are), copies the uploads, deploys `ink-core` and `ink-foundation` from this repo with `tools/deploy-to-local.sh`, activates them, sets `WP_ENVIRONMENT_TYPE` and `INK_MAIL_SINK`, and flushes rewrites and caches. It prints the resulting URL, environment, plugin and theme status and a post count.
3. **Hand over to the agent** with the prompt below.

Later, and not before the rehearsal: decide freeze versus incremental (depends on the timed run) and the cutover date; review the Afrikaans strings added in this work for the copy-debt log (the tie line on the Ingestion page is already yours; the `wp ink` CLI messages are unreviewed).

Notes:

- **Mail.** Local runs Mailpit for this site. `INK_MAIL_SINK` defaults to `sink@nuwe-ink.local`: ink-core rewrites every outgoing message to it, and Mailpit (open it from the Local app) shows what the judge-email flow produced. With no sink set ink-core sends nothing at all. Whether Local routes `wp_mail` to Mailpit on this site is unconfirmed until the first judge email is generated.
- **Gradering needs no file.** `wp ink derive-tiers` (a `migrate-all` step) reads the moderators' result posts, counts each writer's Gedigte wins and sets the grade and the wins already counted at that grade, using the live promotion thresholds. The inputs it needs are committed in `ink-core/migration-data/` (by-line to account, spelling variants, which accounts publish result posts). It lists every by-line it could not resolve; add a row to `writer-accounts.csv` or `writer-aliases.csv` for those. The old hand-built `tier-import.csv` is optional now: pass `--tiers=<csv>` to `migrate-all` to import it instead, or keep it only to diff against (`docs/migration-tools/validate-gradering.php`). `docs/data-cleanup/` is gitignored (member emails) and exists only in your working copy.
- **Database socket.** The prep script finds the site's MySQL socket and client from Local's own `sites.json`. The agent can do the same, or reuse the `Database` tab's values in Local.
- **WP-CLI from an agent session** against the Local database needs the sandbox off.

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
1. Pre-flight, read-only. Confirm WP_ENVIRONMENT_TYPE is not production and INK_MAIL_SINK is set (tools/rehearsal-prep.sh normally did this). Run docs/migration-tools/check-slug-collisions.php. Record counts of legacy `bibliotekon` / `opleiding` posts and the category tree. Confirm the categories `projekte` and `kompetisie` exist. Stop and report if anything is missing.
2. Snapshot the freshly imported database into ./tmp/, so the migration can be re-run from the same starting point (step 6). Use Local's own `mysqldump` (it sits beside the `mysql` client that tools/rehearsal-prep.sh locates, and needs the socket); plain `wp db export` may not find a client.
3. Run `wp ink migrate-all`. Time it. It stops at the first failure and resumes on re-run. Report every step's verdict; investigate anything flagged `silent` or `all-zero` before moving on. The `tiers` step derives gradering from the result posts: read its output, in particular the unresolved by-lines and the fuzzy matches.
4. Run docs/migration-tools/post-counts.php and compare with the expectations in "Verify" below. Run `wp ink report-result-posts --file=tmp/result-posts.csv` and docs/migration-tools/validate-parser.php and validate-gradering.php.
5. Check the derived gradering. Run docs/migration-tools/validate-gradering.php and report: writers graded per tier, unresolved by-lines (with the number of wins each would add), fuzzy matches, and the diff against the old tier-import.csv if it is on this machine. Do not edit the committed mapping files without the owner's say-so; propose rows for the unresolved by-lines instead.
6. Restore the step-2 backup the same way, run the migration a second time to prove it is repeatable, and time it.
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
| Parser quality on this copy | `validate-parser.php`, `validate-gradering.php` | about 97% exact on the 120 known posts; derived tiers match the old `tier-import.csv` except single-win writers from old layouts (who default to brons) and the 15-versus-20 threshold question |
| Slug clashes after migration | `check-slug-collisions.php` (run before and after) | only `afrikaanse-taalgids` (different URL prefixes) |
| Redirect map health | `wp ink verify-redirects` (map audit only) | no chains, loops or empty targets |
| Media | `wp ink verify-media` | no missing files for migrated content |
| Hardening | `wp ink audit-production` | no authoring-only plugins active |
| Unit suite | `vendor/bin/pest`, `composer stan`, `composer deptrac` | green (deptrac keeps its 3 known violations) |

The agent cannot judge how pages look or read, whether Afrikaans copy is right, or whether a writer's tier is fair.

### Checks you do

1. **Look at real pages:** a Gedigte shelf poet, a collection (Flipvis), a Woordeboeke item, an Opleiding module (check the Digkuns tab), an old challenge and its entries, `/ontdek/`, a winners announcement on the home page.
2. **Old URLs:** try a few of each, logged out, and see where they land: `/kategorie/lede-bydraes/…` and `/biblioteek/projek-wenners/…` (to Ontdek); `/opleiding/digkuns/` and the other dropped section pages (to `/opleiding/`); a moved `/opleiding/gedigte/<module>/`; a moved dictionary; the old `plagiaat-letterdiefstal` post. A real crawl of the old URLs is described in `docs/redirect-integrity-runbook.md`.
3. **Challenge flow:** pick an old challenge, run the judge emails, and confirm they show up in Mailpit addressed to the sink, with the real judge addresses only in the subject line. Paste a made-up results text with a tie and see the Ingestion page list it as a tie, not block it.
4. **Gradering:** read the derived list the agent reports (writers per tier, unresolved by-lines, fuzzy matches) and spot-check a few writers you know, especially anyone near 5 or 20 total wins.
5. **Accounts and membership:** log in as a member, a writer and an editor; check a membership is active and that BuddyPress profiles, follows and notifications still behave; check a deleted user's posts did not move to an odd account.
6. **Account deletion:** confirm a member cannot delete their account (it is blocked until the account-lifecycle spec exists).
7. **Anything that surprises you** in the post counts above.

## Run (reference)

```
wp ink migrate-all
```

It runs, in order: sanitise, users, tiers (derived from the result posts), verify-subscriptions, library-training, posts, challenges, challenge-entries, result-posts, inkpols, clean-shortcodes, rebuild-navigation, follows, options, term recount, generate-redirects, verify-redirects --fix, verify-media.

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
| Which by-lines are which accounts; spelling variants; who publishes result posts | `ink-core/migration-data/*.csv` |
| Gradering thresholds (cumulative: 5 wins leave brons, 15 more leave silwer = 20 total) | `ink-core/src/Tiers/PromotionEngine.php` |

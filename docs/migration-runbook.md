# INK migration runbook

Rebuild the new site from a fresh copy of production. The database is disposable and every human decision is a committed file, so a rehearsal and the real cutover are the same procedure.

**Status (2026-10-25):** the first rehearsal has run on a real prod copy (about 90 s, repeatable from a restored snapshot). Its findings are fixed on branch `fix/rehearsal-findings`: the theme's Pages are now created by code, announcements are no longer entries, Uitdagings are dated, the public profile is `/lede/<name>`, and highlights are saved. InkPols is now migrated from `inkpols-uitgawes.csv` in the migration-data folder (branch `feat/inkpols-csv-migration`, `lovable-delta-2026-10/inkpols.md` §3 "As built"): one `inkpols_uitgawe` per row, attachments matched by upload path, 301s from the old Pages. An issue with every required field filled is **published**; one with a blank is a draft and its old Page stays live. **The CSV holds the owner's real metadata for 8 issues (filled 2026-11-03; the 2026-11-02 refresh ran on the earlier dummy `Toets…` version).** An issue publishes only if every required cell is filled; issue 8 (`september-oktober-2026`) points at `2026/11/` files, so it stays a draft unless the export's uploads contain them. Not yet fixed: the missing upload files (140 on the first copy, 161 on 2026-10-29), the footer links, and BuddyPress's member routes are redirected, not removed.

**Refresh proof (2026-11-02, Windows, on the delta work):** restore, tag SQL, `migrate-all` (360 s, exit 0, all 20 steps ok), 54 URLs as expected, capture gates 68 of 68 with MISSING/STRUCTURE/DRIFT 0. Open: the closed-round hero fails the gate at 768 (height only), `verify` is intermittent on `reading-response-author` width (0.015 to 0.3 px), and 136 rounds were created against 128 expected (competition-like rounds; `migration-plan.md`, `handoffs/winner-cleanup.md` §7). Findings from it are in "Refresh-proof findings" below.

## What remains for you

Only the export itself. `tools/rehearsal-prep.mjs` does everything else, on macOS or Windows (Node 20+, PHP and WP-CLI on PATH; it finds the site, its database port or socket and Local's `mysql` client from Local's own `sites.json`, see `tools/lib/local-site.mjs`).

0. **First time on a machine:** create the site in Local, named `nuwe-ink` (domain `nuwe-ink.local`), and start it. A new site has no uploads, so bring them.
1. **Bring the prod export:** a full database dump (`.sql` or `.sql.gz`), and optionally the `wp-content/uploads` folder (a folder, `.zip` or `.tar.gz`). If the Local site already holds the uploads from an earlier copy you can leave `--uploads` out; media added to prod since then is missing and `wp ink verify-media` will list it. If you do bring uploads, only differences are copied, and nothing is deleted. Take the dump and the uploads at the same moment.
2. **Dry run, read what it says, then apply:**

   ```
   node tools/rehearsal-prep.mjs --sql ../live-import/prod.sql --wp-content ../live-import/wp-content
   node tools/rehearsal-prep.mjs --sql ../live-import/prod.sql --wp-content ../live-import/wp-content --apply
   ```

   Keep the export outside the repo (`../live-import/` beside it); never commit it. The rehearsal must look exactly like prod, so bring prod's whole `wp-content` (`--wp-content`; the database dump and the folder taken at the same moment). Its `uploads`, `plugins`, `mu-plugins` and `themes` are each mirrored in (`--uploads`, `--plugins`, `--mu-plugins`, `--themes` override one folder). Things already on the site and not in the copy stay; `ink-core` and `ink-foundation` always come from the repo. After the import it runs `wp core update-db`, because prod runs an older WordPress than the dev site. The Local site must be running. It also sets `INK_MIGRATION_DATA_DIR` to `../migration-data/` (`--migration-data <dir>` to override), and stops before touching the database if a file in [migration-data.md](./migration-data.md) is missing. With `--apply` it replaces the current nuwe-ink database (an old test copy; nothing is kept) with the prod dump, rewrites the prod URLs to `https://nuwe-ink.local` (with `wp search-replace`, which is safe for serialized data; it never touches the bare domain, so addresses like `moderator@ink.org.za` stay as they are), copies the uploads, deploys `ink-core` and `ink-foundation` from this repo with `tools/deploy-to-local.mjs`, activates them, sets `WP_ENVIRONMENT_TYPE` and `INK_MAIL_SINK`, and flushes rewrites and caches. It prints the resulting URL, environment, plugin and theme status and a post count.
2a. **On production, before the export (owner):** run `docs/migration-tools/tag-old-competitions.sql` (database backup first). It adds post meta, nothing else, each holding the year: `ink_old_uitslae` (results Pages, 2016 to 2025) and `ink_old_gala_fotos` (gala photos Pages), which the migration imports and retires; and four marks for Pages that **stay live at their own addresses and are never imported, trashed or redirected**: `ink_old_kompetisie_aankondiging`, `ink_old_gala_aankondiging`, `ink_old_kompetisie_sluiting`, `ink_old_gala_herinnering`. Purpose: `wp ink migrate-kompetisies` builds one `kompetisie` per year from the tagged Pages instead of guessing from titles. Safe to repeat; the closing SELECT should list 36 rows. Skip it and the results Pages are still found by title (with a "found by title; tag it" warning each), but the kept Pages are then unmarked. The retired tags `ink_old_gala` and `ink_old_competition` are not used any more.
3. **Retired plugins and FluentSMTP are deactivated for you.** The prod database arrives with prod's plugin state (every retired plugin active, and FluentSMTP sending through prod's mail server), so `rehearsal-prep.mjs --apply` deactivates the list in its `RETIRED_PLUGINS` constant (the Phase 4a list in [staging-setup-guide.md](./staging-setup-guide.md) plus `fluent-smtp`; deactivated, never deleted) and prints which ones it switched off and whether any is still active. `simple-css` is on the list because its stored CSS sets the body text to 14px on every page (the design uses 16px) and prints each Page's `_simple_css` meta, which fails the fidelity gate; it is an authoring plugin the new site does not use (`ProductionHygiene::FORBIDDEN_PLUGINS`). A plugin added to the retired list later goes in that constant. Then snapshot: `node tools/local.mjs mysqldump --single-transaction --result-file=tmp/nuwe-ink-pre-migrate.sql`.
4. **Hand over to the agent** with the prompt below.

Later, and not before the rehearsal: decide freeze versus incremental (depends on the timed run) and the cutover date; review the Afrikaans strings added in this work for the copy-debt log (the tie line on the Ingestion page is already yours; the `wp ink` CLI messages are unreviewed).

Notes:

- **Rehearsal log (2026-10-29, Windows, clean Local site).** Prep took 17 min (19 GB uploads). Found and fixed: the new site's `wp_` prefix (now taken from the dump), the missing `wp core update-db`, no plugins on a new site (`--plugins`). The LiteSpeed purge fails over Local's self-signed certificate; harmless. FluentSMTP was active with prod's SMTP settings (now step 3). `migrate-all`: 6 min 13 s (the Mac took about 90 s), only `inkpols` flagged (all-zero, known). Matches expectations: skryfwerk 150, biblioteek 312, opleiding 179, collections, 140 Uitdagings, 133 announcements, parser 97.1% exact. Differences: 6,710 entries (+3, newer prod posts); result posts 102 of 128 linked; gradering 105 identical to `tier-import.csv`, 6 differ; unresolved by-lines Anja Nel (4 wins), Agie (3), Surika van der Merwe (1), plus one parser artefact; 161 missing upload files (was 140). Agie: account and posts deleted on prod, wins not counted, nobody moves up (owner). **Parser fix** (`LegacyResultsParser`): a short heading ending in Kommentaar/Besprekings ("GEDIGTE KOMMENTAAR:") now ends the winners region; two-digit "(10)" numbers in the commentary no longer turn the winners' "(1)-(3)" into entry numbers; with a place number, an unknown writer must be shaped like a name. Exact rows 97.1% → 97.7%; November 2018 now fully correct; fake wins from commentary (e.g. Palet) gone. After it: 110 writers graded (brons 69, silwer 29, goud 12), 106 identical to the July CSV. **Repeat run** (snapshot restored in 80 s, fixed parser): 6 min 10 s, every count identical to run 1 except gradering (the fix). Repeatable; freeze window ≈ export + 17 min prep + 6 min migrate + checks.
- **InkPols CSV.** `../migration-data/inkpols-uitgawes.csv`, with the rest of the migration data ([migration-data.md](./migration-data.md)). The owner filled it with real metadata on 2026-11-03 (8 issues); a new edition means a new row before the export.
- **Mail.** Local runs Mailpit for this site. `INK_MAIL_SINK` defaults to `sink@nuwe-ink.local`: ink-core rewrites every outgoing message to it, and Mailpit (open it from the Local app) shows what the judge-email flow produced. With no sink set ink-core sends nothing at all. Whether Local routes `wp_mail` to Mailpit on this site is unconfirmed until the first judge email is generated.
- **Gradering needs no file.** `wp ink derive-tiers` (a `migrate-all` step) reads the moderators' result posts (never a competition's), counts each writer's Gedigte wins and sets the grade and the wins already counted at that grade, using the live promotion thresholds. `wp ink import-winners` (the `winners` step) reads the same posts and records every win in every category. The inputs it needs (by-line to account, spelling variants: `writer-accounts.csv`, `writer-aliases.csv`) are **not in the repo**. Who publishes result posts is read from the posts. They live beside it, in `../migration-data/` (on Windows `C:\dev\ink\migration-data`). `rehearsal-prep.mjs` points WordPress at that folder; by hand, it is this line in the site's `wp-config.php`, before `wp ink migrate-all`:

  ```php
  define( 'INK_MIGRATION_DATA_DIR', 'C:/dev/ink/migration-data' );
  ```

  There is no fallback: if the constant or a file is missing, `migrate-all` stops before any step runs. It lists every by-line it could not resolve; add a row to `writer-accounts.csv` or `writer-aliases.csv` for those.
- **Existing code in `wp-content`.** Do not delete it. The plugins and mu-plugins already there are what the prod database expects; `ink-core` and `ink-foundation` are mirrored from this repo by the script. After the import, `wp plugin list` shows which prod plugins are active and which are missing from the Local site (WordPress quietly deactivates missing ones).
- **WP-CLI and MySQL against the site.** Use `node tools/local.mjs wp <args>`, `node tools/local.mjs mysql` and `node tools/local.mjs mysqldump`. They connect the way the prep script does (TCP port on Windows, socket on macOS). Plain `wp` fails: wp-config.php says `DB_HOST = 'localhost'`, and the system PHP's default port/socket is not Local's.
- **WP-CLI from an agent session** against the Local database needs the sandbox off.

## Hand-over prompt for the implementing agent

Paste this to a fresh agent session in this repo after step A is done.

````text
You are running the first rehearsal of the INK migration on the Local site nuwe-ink (https://nuwe-ink.local/; `node tools/local.mjs info` shows where its files are). A fresh copy of the prod database and uploads has just been imported and the code from `main` is deployed. Production must never be touched.

Read first, in this order: docs/migration-runbook.md, docs/migration-plan.md, docs/migration-result.md (the first, failed rehearsal and what each symptom was), docs/migration-tools/README.md, CLAUDE.md. The decisions are in code, not in your head: PostReclassifier, LegacyContentMap, StaticRedirects, Challenges\Migration, SourceCategorySync, ResultPostLinker, LegacyResultsParser, MigrateAll.

Rules:
- All scratch files go in ./tmp/. Commit messages go in tmp/commit-msg.txt and are used with `git commit -F`. No Co-Authored-By or Claude-Session trailers.
- Work on a new branch off main. WP-CLI against the Local database needs the sandbox off (macOS). Run every WP-CLI command as `node tools/local.mjs wp …` and MySQL as `node tools/local.mjs mysql …`; they find the site's database connection on macOS and Windows.
- docs/ and _bmad-output/ are written with the Edit/Write tools, not the shell.
- Do not "fix forward" silently. Report each step's output verbatim and what you concluded; change code only after the user agrees, except for clear bugs in the migration code that tests can pin.

Do this:
1. Pre-flight, read-only. Confirm WP_ENVIRONMENT_TYPE is not production and INK_MAIL_SINK is set (tools/rehearsal-prep.mjs normally did this). Run docs/migration-tools/check-slug-collisions.php. Record counts of legacy `bibliotekon` / `opleiding` posts and the category tree. Confirm the categories `projekte` and `kompetisie` exist. Stop and report if anything is missing.
2. Snapshot the freshly imported database into ./tmp/, so the migration can be re-run from the same starting point (step 6). Use `node tools/local.mjs mysqldump > tmp/<file>.sql` (Local's own mysqldump, correctly connected); plain `wp db export` may not find a client.
3. Run `wp ink migrate-all`. Time it. It stops at the first failure and resumes on re-run. Report every step's verdict; investigate anything flagged `silent` or `all-zero` before moving on. The `tiers` step derives gradering from the result posts: read its output, in particular the unresolved by-lines and the fuzzy matches.
4. Run docs/migration-tools/post-counts.php and compare with the expectations in "Verify" below. Run `wp ink report-result-posts --file=tmp/result-posts.csv` and docs/migration-tools/validate-gradering.php.
5. Check the derived gradering. Run docs/migration-tools/validate-gradering.php and report: writers graded per tier, unresolved by-lines (with the number of wins each would add), and fuzzy matches. Do not edit the mapping files (in INK_MIGRATION_DATA_DIR, outside the repo) without the owner's say-so; propose rows for the unresolved by-lines instead.
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
| Legacy content moved | same script | `biblioteek_item` about 312, `opleiding_artikel` about 179; no `bibliotekon` / `opleiding` rows left except unknown roots (gaps to rule on); the skipped winners pages and dropped placeholders become post type `retired` (meta `ink_retired_from`, `ink_retired_reason`) |
| Collections and skills | same script | Gedigte shelf 91; Vrystaatstories 68; Oom Pine 65; Liegstories 29; Kinderrympies one collection; Woordeboeke 7; Digkuns about 168 |
| Challenges | same script | one Uitdaging per category under `projekte`, except competitions (a `kompetisie` in the category name), which are not challenges: **128 expected** (the 2026-11-02 copy had 136 because the filter was not applied to the round list; fixed 2026-11-03); entries counted per round (6,707 on the first rehearsal copy: result posts and announcements are not entries); each Uitdaging dated from its announcement, so the October 2026 round lists first |
| Announcements | `migrate-posts` summary | one per round (133 of 140 on the first rehearsal copy), kept as core posts marked `ink_challenge_announcement` |
| Theme Pages | `wp ink ensure-pages`, `curl` each of `/teken-in/ /skryf/ /ontdek/ /gemeenskap/ /my-profiel/ /kompetisies/` | 200, not a redirect to a poem |
| New design URLs (delta, 2026-11) | `curl -I` each, cache-busted. Use refresh-stable URLs (a real migrated round, issue or Page), never a fixture | `/uitdaging/`, `/inkpols/`, `/inkpols/<an issue slug from the CSV>/` (e.g. `/inkpols/julie-augustus-2026/`) and `/kompetisies/`: 200. `/uitdaging/<a round with a linked result post>/uitslae/` (e.g. `augustus-2026-aanhalingsprojek`): 200; the same for a round with no results (e.g. `oktober-2026-getal-10-projek`): 404. The old result post (`/augustus-2026-aanhalingsprojek-kommentaar-en-uitslae/`): 301 to its own round's `/uitslae/`. `/kompetisies/gala/2016/` to `/2025/`: 200; `/kompetisies/gala/1999/`: 404. `/ink-se-gala-aande/` and the 20 gala and Fotos paths: 301 to `/kompetisies/`, **never** to a poem. `/ink-dig-en-skryfkompetisie-2022/`: 200 |
| InkPols after the step | `migrate-all` summary; `/inkpols/` | "8 geskep" with the 2026-11-03 CSV; 7 gepubliseer and 1 as konsep unless the export's uploads hold the September/October PDF and cover |
| Slug clashes introduced by the delta | `check-slug-collisions.php`; `curl` | `kompetisies`: prod Page 59192 is adopted by `SystemPages` (body and `_simple_css` emptied), not duplicated. `inkpols`: the prod Page is trashed and the CPT archive owns the URL. The `kompetisie` post type is not public, so it cannot clash with the category of that name. `uitslae` and `gala/<jaar>` are rewrite rules under existing URLs, with no slug of their own |
| Winners | `migrate-all` summary (`winners` step); `wp ink import-winners --force` shows the notes | On the 2026-11-02 copy (run on its dev database, 2026-11-03): 1,573 wins from 123 result posts, 1,571 recorded (2 have position 4), about 1,420 with their winning post found, 14 with no account, none without a round; 23 result posts got their round from their winners. A win with no post or no account is still recorded. Lifetime counts differ from the old hand-curated list for about 27 of 142 writers, mostly by 1 or 2 (a line with two winners, a typo in a by-line); the two writers printed as "Francois Meyer" are mixed up by 7 wins |
| Result posts | `wp ink report-result-posts`; same script | about 12 a year, all by Anze or Inkmoderator, kept as core posts, linked to their Uitdaging where the month and year match |
| Parser quality on this copy | `validate-gradering.php` | writers graded per tier (2026-11-02 copy: 111 graded, brons 70, silwer 28, goud 13), unresolved by-lines (only Agie, 3 wins, whose account is deleted), fuzzy matches. Read it; a win count slightly off for someone who has posted nothing in a year is accepted |
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

It runs, in order: sanitise, pages (creates the Pages the theme's page templates need: `Ink\Kernel\SystemPages`, also `wp ink ensure-pages`), users, tiers (derived from the result posts), verify-subscriptions, library-training, **link-categories** (`wp ink link-categories`, `PostCategoryLinker`: the ~150 posts the owner sorted by hand get their gedigte / verhale-kortverhale / artikels category, so `posts` classifies them; it must run before `posts`, which makes every later step, including winners, Gradering history, entry Gradering and counts, see the final types), posts, challenges, challenge-entries, result-posts, **winners** (`wp ink import-winners`: every old win as a record, placements and entry-Gradering snapshots; below), **tier-history** (`wp ink import-tier-history`: the Silwer/Goud promotions the old wins imply, no emails), **entry-gradering** (`wp ink import-entry-gradering`: the grade each old entry was submitted under, replayed from the promotion history against the entry's post date), inkpols, kompetisies, search-index, **counts** (`wp ink migrate-counts`: prod's views and likes; library and training views too), clean-shortcodes, rebuild-navigation, follows, options, term recount, generate-redirects, verify-redirects --fix, verify-media.

- It stops at the first failing step. Fix the cause and run the same command: steps that succeeded are skipped.
- `--from=<step>` re-runs from that step on. `--reset` forgets all stored outcomes.
- A step flagged `silent` or `all-zero` did nothing; decide whether that is right (for example no InkPols on a test copy) before going on.
- `kompetisies` (`wp ink migrate-kompetisies`) builds **one `kompetisie` per year** (`gala-<jaar>`, `tipe` = `jaarliks`) from the Pages tagged by step 2a. The results Page (`ink_old_uitslae`) is reused **as it is, not parsed**: its body (WPBakery tags stripped) goes into the post's `uitslae_oud` field, shown on `/kompetisies/<jaar>/uitslae/`, and the results are marked published. The Fotos Page (`ink_old_gala_fotos`) feeds the gala photos as before (3,379 for the ten years, owner ruling 2026-11-02). Both Pages, and the `/ink-se-gala-aande/` index, are then trashed; their ids, paths, years and target kind (`fotos` or `uitslae`) are stored in the option `ink_kompetisie_old_pages` for the redirects (story K7), and `RedirectGenerator` serves the 301s from it (K7, see Redirects below). The old `ink_gala_old_paths` option and the 20 fixed gala/Fotos paths in `StaticRedirects::EXACT` are gone. Announcements, gala announcements, closure notices and gala reminders (the four `ink_old_*_aankondiging/sluiting/herinnering` marks) are **not imported, trashed or redirected**; partner competitions are not migrated. No file is read (highlights and captions are edited on the site). A year whose results Page is empty keeps its Page and reports it; the 2016 duplicate (7802) is untagged and ignored with a warning. **It must run before `clean-shortcodes`** (it does): the photos are the ids in the Fotos Pages' gallery shortcodes, which `clean-shortcodes` removes; on a database where that already ran the step reads no photos, reports it, links nothing and keeps the Fotos Pages. **So `migrate-kompetisies` cannot be re-run after `clean-shortcodes`** (restore the pre-migrate snapshot and run `migrate-all` again instead). **Its skip detail is hidden inside `migrate-all`:** the summary says only "N rows or photos skipped", while the reasons are warnings on stderr. To read them, run the step on the restored snapshot before `clean-shortcodes` has run (`node tools/local.mjs wp ink migrate-kompetisies`, then `migrate-all`, where the step reports "skipped, already done"); the "found by title; tag it" ones mean step 2a was skipped. **Never run `--force` on the live site:** it overwrites site edits to imported posts. After go-live, competitions and listings are added and edited on the site. The import is long (use a background run, not a foreground one); `/kompetisies/` shows four photos a year and `/kompetisies/gala/<jaar>/` shows them all, 24 per page. Verify: `/kompetisies/gala/2025/` is 200 with 24 photos, `/kompetisies/gala/1999/` is 404, `/kompetisies/2025/uitslae/` is 200 with the old results text, and the kept announcements (e.g. `/ink-dig-en-skryfkompetisie-2022/`) are still 200.
- **InkPols after go-live:** an editor adds each issue in admin. Never run `migrate-inkpols --force` on the live site.
- `inkpols` warns once per issue it left as a draft, with the missing CSV fields; those warnings go to stderr, so `migrate-all` shows only the counts. **A fully filled CSV publishes**, so "0 drafts" says only that no cell is blank. Run `wp ink migrate-inkpols --force` to see them (it reconciles, never duplicates). After filling the CSV on a site you are not refreshing, run `wp ink migrate-inkpols --force`, `wp ink generate-redirects --force` and `wp ink verify-redirects --fix`.
- `maybeRedirect` (`RedirectGenerator`, the 301 serve handler) runs on `template_redirect` at **priority 5**, ahead of core's `redirect_canonical` (10). At the default priority core's 404 guess won first and sent an old path to any post whose slug matched its last segment (`/22-oktober-2016-1ste-gala-aand/fotos/` went to the poem `/gedig/fotos/`). If an old URL lands on an unrelated post, check that the deployed code has this priority and that the path is in the map.
- **Retired results and Fotos Pages (K7, owner ruling).** `maybeRedirect` also reads `ink_kompetisie_old_pages` (`StaticRedirects::oldPageTarget()`): `?p=<id>` / `?page_id=<id>` or the old pretty path (case, trailing slash and query ignored) gives a 301 to `/kompetisies/gala/<jaar>/` (Fotos Page) or `/kompetisies/<jaar>/uitslae/` (results Page). It only acts on a request that would 404, which a trashed Page does on both forms. An entry with an empty path matches by id only. Only migrated years are in the map, so the target year always exists. `/ink-se-gala-aande/` (no year) stays in `StaticRedirects::EXACT` and goes to `/kompetisies/`. Announcements, closure notices and reminders are kept live and never redirected. Check: logged out, `/?p=64688` and `/15-november-2025-10de-gala/fotos-15-november-2025/` both 301 to `/kompetisies/gala/2025/`, and the results Page's id/path to `/kompetisies/2025/uitslae/`. Unit-tested; not yet run against a real site.
- Note the total run time. It decides the cutover window.

## Test data

There is none. The old fixture scripts, the `ink-toets-uitslae` mu-plugin and the closed-round gate were removed (2026-11-03). Pages that show winners, citations, sponsors, responses or competitions show real migrated data, or nothing until the winner importer exists (`handoffs/winner-cleanup.md` §7).

## Refresh-proof findings (2026-11-02)

- **InkPols publishes, it does not draft, when the CSV is fully filled.** A fully filled CSV publishes every issue; check the CSV is the real one before a refresh.
- **`migrate-kompetisies` is once-only and cannot follow `clean-shortcodes`;** its skip detail is hidden inside `migrate-all` (above).
- **Round IDs shift** after every refresh; capture scripts look rounds up by slug.
- **`maybeRedirect` is at priority 5** (above); a deployment from before the fix sends old gala paths to poems.
- **Simple CSS must be deactivated** (step 3), or pages fail the fidelity gate.
- **136 rounds, not 128** (competition-like rounds; `migration-plan.md`).
- After a refresh also recreate `fidelity-capture` and re-deploy (`node tools/deploy-to-local.mjs`, dry run, then `--apply`; the deploy clears the pattern cache).

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
| Challenge rounds (descendants of `projekte`, except competitions) | `ink-core/src/Challenges/Migration.php` |
| Challenge entries follow their source category | `ink-core/src/Challenges/SourceCategorySync.php` |
| Result posts linked to their challenge | `ink-core/src/Challenges/ResultPostLinker.php` |
| Reading winners out of old result posts | `ink-core/src/Migration/LegacyResultsParser.php` |
| Which by-lines are which accounts; spelling variants; who publishes result posts | `../migration-data/*.csv` (outside the repo; `INK_MIGRATION_DATA_DIR`) |
| Gradering thresholds (cumulative: 5 wins leave brons, 15 more leave silwer = 20 total) | `ink-core/src/Tiers/PromotionEngine.php` |

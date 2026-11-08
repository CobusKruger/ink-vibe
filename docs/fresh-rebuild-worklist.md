# Fresh rebuild worklist (nuwe-ink)

From an empty Local site to a migrated copy of prod. Windows, Local's site shell (CMD).

Inputs in `C:\dev\ink\live-import\`:

| Path | What |
|---|---|
| `inkorgza_wp821.sql` | Prod DB dump |
| `uploads\` | Prod uploads, about 18 GB, including `2026\11` |
| `plugins\` | Prod plugins |
| `themes\` | Prod themes |

The uploads are not copied: the site's `wp-content\uploads` is a junction to `live-import\uploads`. Anything the site writes to uploads lands in that folder.

Migration data (three files) is in `C:\dev\ink\migration-data\`. Production is never touched by any step below.

---

## Part A. New Local site

### 1. Remove the old site

If an old nuwe-ink has an uploads junction, remove the junction **first**, so deleting the site cannot reach the prod uploads. `rmdir` without `/S` removes only the link:

```cmd
rmdir "%USERPROFILE%\Local Sites\nuwe-ink\app\public\wp-content\uploads"
```

Expected: no output. (If it says "The directory is not empty", it is a real folder, not a junction: fine, leave it.)

Then in Local: right-click **nuwe-ink** → **Delete**.

Expected: nuwe-ink is gone from Local's site list.

### 2. Create the site

In Local: **+** → Create a new site:

- Name: `nuwe-ink` (domain becomes `nuwe-ink.local`)
- Environment: **Custom**, PHP **8.3.36**, web server nginx, MySQL default
- WordPress user: anything (the prod dump replaces it; you log in with a prod account afterwards)

Start the site, then right-click → **Open site shell**.

Expected: a CMD window titled for nuwe-ink.

If Local offers a different PHP version, use it and update `"php"` in `tools/local.config.json` to that version's `php.exe`.

### 3. Tools check

```cmd
cd /d C:\dev\ink\ink-vibe
node --version
git status
node tools/local.mjs info
```

Expected:

- `v20` or later
- `nothing to commit, working tree clean` (the prep script deploys `ink-core` and `ink-foundation` from the working tree)
- `info` shows site `nuwe-ink`, web root `C:\Users\cobus\Local Sites\nuwe-ink\app\public`, a DB port

### 4. Link the uploads

```cmd
rmdir /S /Q "%USERPROFILE%\Local Sites\nuwe-ink\app\public\wp-content\uploads"
mklink /J "%USERPROFILE%\Local Sites\nuwe-ink\app\public\wp-content\uploads" "C:\dev\ink\live-import\uploads"
dir "%USERPROFILE%\Local Sites\nuwe-ink\app\public\wp-content\uploads\2026"
```

Expected:

- `rmdir`: no output, or "The system cannot find the file specified" (a new site may have no uploads folder yet)
- `Junction created for ...\uploads <<===>> C:\dev\ink\live-import\uploads`
- `dir`: folders `01` to `11`

---

## Part B. Prod copy, then the new code

### 5. Dry run

```cmd
node tools/rehearsal-prep.mjs --sql ../live-import/inkorgza_wp821.sql --plugins ../live-import/plugins --themes ../live-import/themes --stop-at prod
```

Expected:

```
== Plan ==
  stage:          prod copy only (stops before the new code)
  site:           C:\Users\cobus\Local Sites\nuwe-ink\app\public
  database:       local via 127.0.0.1:<port>
  prod dump:      ../live-import/inkorgza_wp821.sql
  uploads:        (none given: keeping the uploads already on the site)
  plugins:        ../live-import/plugins
  mu-plugins:     (none given: keeping what is on the site)
  themes:         ../live-import/themes
  old URL:        (read from the imported database)
  new URL:        https://nuwe-ink.local
  mail sink:      sink@nuwe-ink.local
  migration data: C:\dev\ink\migration-data
== DRY RUN: nothing was changed. Re-run with --apply to do it. ==
```

If it stops on a missing migration-data file, fix that first.

### 6. Prod copy

```cmd
node tools/rehearsal-prep.mjs --sql ../live-import/inkorgza_wp821.sql --plugins ../live-import/plugins --themes ../live-import/themes --stop-at prod --apply
```

Expected (a few minutes), among the output:

- `table prefix: ... -> wpjj_ (from the dump)`
- `replacing URLs on host: ink.org.za`
- `skipped: keeping the existing uploads`
- `deactivated fluent-smtp (mail now goes to Mailpit)`
- At the end:

```
  home:        https://nuwe-ink.local
  environment: local
  theme:       <prod's theme>
  fluent-smtp: off
  wp-cron:     disabled
  payfast:     sandbox
  posts:       <number>
```

If `fluent-smtp` is not `off`, stop: it sends real mail through prod's server.

Made safe, so it differs from prod in only these ways: FluentSMTP off (mail goes to Mailpit, still addressed to the real people), WordPress cron off (no membership, renewal or scheduled mail), PayFast in sandbox mode. Prod's mu-plugins are not there.

### 7. Check the prod copy

Prod's own theme and plugins are running. Check at `https://nuwe-ink.local`:

- Home page loads and looks like ink.org.za
- A post with images: text and images show
- Log in at `/wp-admin/` with a prod account
- Shop and a membership page load (do not check out)
- `%USERPROFILE%\Local Sites\nuwe-ink\logs\php\error.log`: no `PHP Fatal`

Optional snapshot, to redo step 8 without importing again:

```cmd
node tools/local.mjs mysqldump --single-transaction --result-file=tmp/nuwe-ink-prod-copy.sql
```

(Restore: `node tools/local.mjs mysql -e "source tmp/nuwe-ink-prod-copy.sql"`.)

### 8. New code

```cmd
node tools/rehearsal-prep.mjs --from prod
node tools/rehearsal-prep.mjs --from prod --apply
```

Expected: the dry run shows `stage: new code on the prod copy already on the site`. With `--apply`, among the output:

- `deactivated N retired plugin(s): ...` (includes `code-snippets`, `wp-migrate-db`, `simple-css`)
- At the end:

```
  home:        https://nuwe-ink.local
  environment: local
  ink-core:    active
  theme:       active
  retired plugins still active: none
  posts:       <number>

Next: log in at https://nuwe-ink.local/wp-admin/, then run: node tools/local.mjs wp ink migrate-all
```

Harmless: `LiteSpeed purge failed ... self-signed certificate`.

If you see `could not clear wp_theme_files_patterns` or a "critical error", do step 9; otherwise step 9 is a quick check.

### 9. Pattern cache and load check

```cmd
node tools/local.mjs mysql -e "DELETE FROM wpjj_options WHERE option_name LIKE '%wp_theme_files_patterns%'"
node tools/local.mjs wp eval "echo 'ok';"
```

Expected: no output from the first; `ok` from the second.

If the second shows a critical error, read the last `PHP Fatal` line in `%USERPROFILE%\Local Sites\nuwe-ink\logs\php\error.log`.

### 10. Plugins

```cmd
node tools/local.mjs wp plugin list --status=active --field=name
```

Expected: includes `ink-core`, `buddypress`, `woocommerce`, `woocommerce-memberships`, `woocommerce-payfast-gateway`, `contact-form-7`, `litespeed-cache`, `loco-translate`. Must **not** include `code-snippets`, `wp-migrate-db`, `fluent-smtp`, `simple-css`, `youzify`, `js_composer`, `wordpress-seo`.

### 11. Pre-flight checks

```cmd
node tools/local.mjs wp config get WP_ENVIRONMENT_TYPE
node tools/local.mjs wp config get INK_MAIL_SINK
node tools/local.mjs wp config get INK_MIGRATION_DATA_DIR
node tools/local.mjs wp term get category projekte --by=slug --fields=term_id,slug
node tools/local.mjs wp term get category kompetisie --by=slug --fields=term_id,slug
node tools/local.mjs wp post list --post_type=bibliotekon --format=count
node tools/local.mjs wp post list --post_type=opleiding --format=count
node tools/local.mjs mysql -e "SELECT meta_key, COUNT(*) FROM wpjj_postmeta WHERE meta_key LIKE 'ink_old_%' GROUP BY meta_key"
node tools/local.mjs wp --skip-plugins --skip-themes eval-file C:/dev/ink/ink-vibe/docs/migration-tools/check-slug-collisions.php
```

Expected:

| Check | Expected |
|---|---|
| `WP_ENVIRONMENT_TYPE` | `local` |
| `INK_MAIL_SINK` | `sink@nuwe-ink.local` |
| `INK_MIGRATION_DATA_DIR` | `C:/dev/ink/migration-data` |
| `projekte` | term_id 199 |
| `kompetisie` | term_id 200 |
| bibliotekon / opleiding | 561 / 194 (note them) |
| `ink_old_*` | competition 1, gala 10, gala_fotos 10 (21) |
| slug collisions | `PAGE CLASH /afrikaanse-taalgids ...` and `total: 1` |

### 12. Snapshot

```cmd
node tools/local.mjs mysqldump --single-transaction --result-file=tmp/nuwe-ink-pre-migrate.sql
dir tmp\nuwe-ink-pre-migrate.sql
```

Expected: no output from the dump; `dir` shows the file (several hundred MB).

(Restore: node tools/local.mjs mysql -e "source tmp/nuwe-ink-pre-migrate.sql".)


---

## Part C. Migrate

### 13. Run migrate-all

```cmd
echo %time%
node tools/local.mjs wp ink migrate-all
echo %time%
```

Expected: about 6 minutes; one summary line per step (24 steps; `winners` and `tier-history` follow `result-posts`, `counts` follows `search-index`), ending in:

```
Success: Migrasie voltooi.
```

If it says `Migrasie voltooi, maar N stap(pe) is gemerk`, read the flagged lines (`silent` / `all-zero`).
If it stops (`Migrasie gestaak`), fix the cause and run the same command again: it resumes.
Never re-run `migrate-kompetisies` by itself after `clean-shortcodes`; restore the snapshot instead (step 17).

### 14. Counts and gradering

```cmd
node tools/local.mjs wp --skip-plugins --skip-themes eval-file C:/dev/ink/ink-vibe/docs/migration-tools/post-counts.php
node tools/local.mjs wp --skip-plugins --skip-themes eval-file C:/dev/ink/ink-vibe/docs/migration-tools/validate-gradering.php
node tools/local.mjs wp ink report-result-posts --file=tmp/result-posts.csv
node tools/local.mjs mysql -e "SELECT meta_value AS reason, COUNT(*) FROM wpjj_posts p JOIN wpjj_postmeta m ON m.post_id=p.ID AND m.meta_key='ink_retired_reason' WHERE p.post_type='retired' GROUP BY reason"
node tools/local.mjs mysql -e "SELECT p.post_type, COUNT(*) AS items, SUM(m.meta_value > 0) AS with_views, SUM(m.meta_value) AS total_views FROM wpjj_posts p LEFT JOIN wpjj_postmeta m ON m.post_id=p.ID AND m.meta_key='_ink_read_count' WHERE p.post_type IN ('biblioteek_item','opleiding_artikel') AND p.post_status='publish' GROUP BY p.post_type"
```

Expected:

- **post-counts:** skryfwerk about 150; biblioteek 312; opleiding 179; Gedigte shelf 91; Vrystaatstories 68; Oom Pine 65; Liegstories 29; Woordeboeke 7; Digkuns about 168; challenges 128; announcements about 133; entries about 6,700
- **validate-gradering:** `result posts read: 126 | writers graded: 112 (brons 71 / silwer 28 / goud 13)`; fuzzy `Reinette Thiart -> reinttethiart`; unresolved `Agie = 3`
- **report-result-posts:** writes `tmp/result-posts.csv`
- **retired:** 264 rows in total (reasons `not-migrated` and `section-placeholder`; of the 8 Opleiding rows, 7 are placeholders and 1 is the stergradering page). Rows under an unknown root stay `bibliotekon` / `opleiding`; post-counts lists any left
- **challenges and winners:** challenges 128, October 2026 round first. The `winners` step line shows about 1,573 wins, 1,571 recorded, 14 with no account, 0 without a round. Francois Terblanche (`#46`) has 2 wins and Francois/GEDAGTES (`#66`) 7
- **InkPols step line:** 8 rows, all published
- **Kompetisies step line:** about "11 created, 3379 photos linked, 3 rows or photos skipped, 21 old Pages trashed"; 0 "found by title; tag it" warnings
- **library and training views:** the last query shows both types with views. Open a library item that had views on prod: it shows the prod number, plus 1 after your visit
- **views and likes:** 3752 = 8993 views, 1 like; 19614 = 36487 views, 5 likes; 59011 = 22599 views, 28 likes. Log in, open one of these poems and click the bottom heart: it fills and the count moves by 1; clicking again undoes it

### 15. After-migration checks

```cmd
node tools/local.mjs wp --skip-plugins --skip-themes eval-file C:/dev/ink/ink-vibe/docs/migration-tools/check-slug-collisions.php
node tools/local.mjs wp ink verify-redirects
node tools/local.mjs wp ink verify-media
node tools/local.mjs wp ink audit-production
```

Expected:

- slug collisions: only `afrikaanse-taalgids`, `total: 1`
- verify-redirects: no failures
- verify-media: about 161 missing upload files (known; compare with the last run)
- audit-production: lists `loco-translate` (staging tool, expected on dev); nothing else
- URLs: `curl -I` the redirect and page URLs, cache-busted (the earlier proof counted 54): `/kompetisies/gala/2025/` 200 with 24 photos, `/kompetisies/gala/1999/` 404, the 21 old gala paths 301 to `/kompetisies/`, `/ink-dig-en-skryfkompetisie-2022/` 200

### 16. Code checks

```cmd
vendor\bin\pest
composer stan
composer deptrac
```

Expected: pest about 1,907 passing, 0 failures (unit suite only; integration runs separately via `composer test:integration`); stan and deptrac clean.

### 17. Repeatability run (optional)

```cmd
node tools/local.mjs mysql -e "source tmp/nuwe-ink-pre-migrate.sql"
echo %time%
node tools/local.mjs wp ink migrate-all
echo %time%
```

Expected: restore about 80 s; migrate-all ends in `Success: Migrasie voltooi.`; counts in step 14 identical.

---

## Part D. Look at it

- Log in at `https://nuwe-ink.local/wp-admin/` with a prod account.
- Mail goes to Mailpit (open from Local), addressed to `sink@nuwe-ink.local`.
- Real pages: a Gedigte poet, Flipvis, a Woordeboeke item, an Opleiding module, an old challenge, `/ontdek/`, the home winners.
- Old URLs, logged out.
- Challenge flow, with the judge emails arriving in Mailpit.
- Gradering spot-check, especially anyone near 5 or 20 wins. On the 5th and 15th win the profile shows both the promotion and the win: intended.
- Accounts and memberships (member, writer, editor).
- Record the timings and every count that differs from the expectations above.

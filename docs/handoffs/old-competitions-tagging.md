# Handoff: tagging the old competition Pages

**Written:** 2026-11-02. **Rewritten:** 2026-11-11 (story K6, owner rulings 9 to 12 in `competition-lifecycle.md`). **For:** an agent or developer with no knowledge of earlier sessions.

**The job (if you inherit it):** the owner runs one SQL script on production. After that, `wp ink migrate-kompetisies` builds one `kompetisie` per year from the tagged Pages, and everything the owner keeps live is marked. No file has to be edited when a competition is added before the cutover.

**Status:** the script and the migration change are built and unit-tested. The script has **not** been run on production, and nothing has been tested against a real refresh yet (see §6).

## 1. Why tags

The old site has four kinds of competition Page per year, with no reliable structure: no common parent, titles that vary in case and wording, and the first migration listed every gala in a CSV (3,379 photo paths). The first tagging round (2026-11-02) also **misread the "gala" Pages**: they are results Pages (posted after the gala: overview of the evening plus the announced winners), not gala announcements. Tags fix both. They are a few rows of post meta on production, change no content, and a new competition needs the same rows added.

## 2. The tags (value = the year)

| Meta key | On | The migration |
|---|---|---|
| `ink_old_uitslae` | the results Page (10, 2016 to 2025; 2016 is 9878, **7802 is its duplicate and is not tagged**) | imports the body as-is into the year's results, publishes the results, trashes the Page |
| `ink_old_gala_fotos` | the gala photos ("Fotos") Page (10) | links the photos to the year, trashes the Page |
| `ink_old_kompetisie_aankondiging` | competition announcement (9; none for 2019, 2016) | **kept live**: not imported, trashed or redirected |
| `ink_old_gala_aankondiging` | gala announcement (2023, 2018, 2017, 2016) | kept live |
| `ink_old_kompetisie_sluiting` | closure notice (2022: 49440) | kept live |
| `ink_old_gala_herinnering` | gala reminder (2017, 2016) | kept live |

**Retired tags:** `ink_old_gala` (its Pages are results Pages: now `ink_old_uitslae`) and `ink_old_competition` (the 2022 announcement 49428: now `ink_old_kompetisie_aankondiging`). Neither was ever run on production, so nothing needs converting; the code no longer reads them.

The script adds 36 rows (10 + 10 + 9 + 1 + 4 + 2). Facts:

- Table prefix `wpjj_`, checked against the production dump (`../live-import/inkorgza_wp821.sql`). Ids are the owner's list of 2026-11-11.
- It only inserts, only where the Page exists and the row is not already there, so it is safe to run twice. A second run leaves 36 rows. It ends with a `SELECT` that should return 36 rows.
- It changes no title, content, status or URL. Take a database backup first.
- The kept announcements stay at their own addresses; the owner decides their categories later. Partner competitions are not migrated.

## 3. What the migration does

`Ink\Competitions\Migration` (`wp-content/plugins/ink-core/src/Competitions/Migration.php`), run by `wp ink migrate-kompetisies` and as the `kompetisies` step of `wp ink migrate-all`.

**Discovery (`discover()`):**
1. Pages tagged `ink_old_uitslae` are the years' results Pages. The Fotos Page is the tagged child, otherwise the Page tagged `ink_old_gala_fotos` with the same year (this is how 2016 works: its Fotos Page hangs under the duplicate 7802). A year with only a tagged Fotos Page still gets a row.
2. Fallback: a top-level Page with "gala" in its title and a "Foto..." child is a results Page, reported as **"found by title; tag it"** (a warning, not a skipped row). If its year already has a row (7802), it is ignored with a warning. Pages carrying a kept-role tag are never candidates.
3. Two results Pages for one year: the lower id is used, the other is reported as a skipped row.

**Import, per year:** one `kompetisie` post, key `gala-<jaar>` (the key is unchanged so a post created by the older migration is reused), title from the results Page, `tipe` = `jaarliks`.
- **Results:** the results Page's body (WPBakery `[vc_*]` tags stripped, markup otherwise untouched, not parsed for winners) is stored in the field `ink_kompetisie_uitslae_oud` and `ink_kompetisie_uitslae_gepubliseer` is set to `ja`. `UitslaePage` shows it after the intro paragraphs, filtered with `wp_kses_post`; `Edition::resultsBody()` reads it. The winner records stay empty; the editor may add an intro or records on the site. A results Page with no text keeps its Page and is reported.
- **Photos:** read from the Fotos Page: the ids in `vc_media_grid`, `vc_masonry_media_grid`, `vc_gallery` and `vc_single_image` shortcodes (`include`, `images`, `image`), in page order, duplicates dropped. A Fotos Page that yields no photos is reported and its Page is kept.
- The `/ink-se-gala-aande/` index Page is trashed too. Trash, never delete; attachments stay.

**What it records for the redirects (story K7).** Retired Pages are trashed, so before trashing the migration stores each one's old id and path:
- Option `ink_kompetisie_old_pages` (`StaticRedirects::OPTION_OLD_PAGES`): a list of `{id:int, path:string, jaar:int, soort:'fotos'|'uitslae'}`. `fotos` targets `/kompetisies/gala/<jaar>/`, `uitslae` targets `/kompetisies/<jaar>/uitslae/`. `path` has no trailing slash and may be `''`; `id` serves `?p=<id>`. The index Page is not in it (no year).
- Served by K7 (built, unit-tested, not run on a real site): `StaticRedirects::oldPageTarget()` matches `?p=<id>`, `?page_id=<id>` or the old path against this option, called from `RedirectGenerator::maybeRedirect()`. `ink_gala_old_paths`, `storedGalaTarget()` and the 20 gala/Fotos paths in `EXACT` were removed; only `/ink-se-gala-aande/` (no year) stays in `EXACT`, redirecting to `/kompetisies/`. Note: 7802 is not trashed (it is untagged), so its path still resolves live.

## 4. No CSV remains

`kompetisies-oud.csv` is gone (2026-11-03) and no code reads a file for this step. Production tags are post meta set by SQL, not an input file. The highlight and the captions are edited on the site. The old versions are in `../backup-data/migration-data/`.

## 5. Order and other traps

- **The step must run before `clean-shortcodes`.** Cleaning strips the gallery shortcodes from the Fotos Pages. `migrate-all` runs them in the right order, but the step **cannot be re-run on a cleaned database** (restore the pre-migrate snapshot and run `migrate-all` again). Never `--force` on the live site; after go-live nothing here runs.
- The 2024 Fotos Page (61228) has three empty `vc_media_grid` shortcodes with no `include` list: a harmless warning on every run; the 441 count still matches. Per-year photo counts: 656, 441, 464, 571, 431, 386, 166, 101, 103, 60 for 2025 down to 2016 (3,379).
- nuwe-ink ran older code: its `kompetisie-2022` post (from the old competition tag) is an orphan the new code does not touch, and its `gala-<jaar>` posts have the old body in `post_content`. Restore the snapshot and re-run `migrate-all` to test this.
- A new competition before the cutover: add its rows to the SQL and run it before the snapshot.

## 6. Verification, and what is not verified

Verified (unit tests, `tests/Unit/Competitions/MigrationTest.php` and `UitslaePageTest.php`): tagged Pages import with no warning; the title fallback gives the exact warning text; a year with one of its two tags works; the 2016 duplicate is ignored; kept-role Pages are never imported or trashed; the results body is kept as is and published; the stored id/path/year/kind map is exact.

Not verified:
- The step has **not run against a real refreshed database**. The refresh proof (step H) is the first real test; the results bodies' real HTML (shortcodes other than `vc_*`, embeds) has not been seen rendered.
- The results page layout is provisional (K5); the old body is shown inside it unstyled.
- The SQL has not been run on production, and ids are the owner's list, not re-checked against the dump.

## 7. Files

- `docs/migration-tools/tag-old-competitions.sql`: the script.
- `wp-content/plugins/ink-core/src/Competitions/Migration.php`: discovery and import. `Competitions/UitslaePage.php`, `Edition.php`, `Content/FieldSets.php`: the results body field.
- `wp-content/plugins/ink-core/src/Migration/StaticRedirects.php`, `RedirectGenerator.php`: redirects.
- Docs: `docs/migration-runbook.md` (step 2a and the `kompetisies` row), `docs/handoffs/competition-lifecycle.md`, `docs/lovable-delta-2026-10/specs/05-kompetisies.md` §14.
- Tests: `tests/Unit/Competitions/MigrationTest.php`, `tests/Unit/Migration/RedirectGeneratorTest.php`.

# Handoff: tagging the old galas and competitions

**Written:** 2026-11-02. **For:** an agent or developer with no knowledge of earlier sessions.

**The job (if you inherit it):** the owner runs one SQL script on production. After that, `wp ink migrate-kompetisies` finds every old gala, its photos and the 2022 competition from tags on the pages themselves. No file has to be edited when a gala is added before the cutover.

**Status:** the script and the migration change are built and tested on dev. The script has **not** been run on production, and nothing has been tested against a real refresh yet (see §6).

## 1. Why tags

The new site's Kompetisies page (`05-kompetisies`) shows an "Evenings past" section: one chip per gala year, a highlight, and photos. The data for it exists only as old WordPress Pages on production:

| Old content | Where it lives on production |
|---|---|
| Each gala | A top-level Page titled like "15 November 2025 - 10de Gala" (10 so far) |
| Its photos | A child "Fotos" Page whose body holds gallery shortcodes (`vc_gallery` and similar, as attachment ids) |
| The 2022 competition | A standalone Page, "INK dig- en Skryfkompetisie 2022" (id 49428) |

Nothing in that structure reliably says "this is a gala" or "this is an old competition":

- The pages have no common parent, and the titles vary in case, wording and format.
- The 2022 competition has no gala or Fotos Page at all, so no title rule can find it.
- The first version of the migration listed every gala in a CSV, including all 3,379 photo paths (151 KB). A new gala would not import until someone added a row. The owner expects another gala before the cutover, so that does not work (owner rule: nothing may need a hand-edited file to survive a refresh; see **THE FIRST RULE** in `CLAUDE.md`).

Tags fix this. They are a few rows of post meta on production, they change no content, and a new gala needs the same three rows added.

## 2. The tags

Post meta on the old Pages, added by `docs/migration-tools/tag-old-competitions.sql`:

| Meta key | Value | On |
|---|---|---|
| `ink_old_gala` | the year, e.g. `2025` | each gala Page |
| `ink_old_gala_fotos` | the same year | that gala's Fotos Page |
| `ink_old_competition` | the year, e.g. `2022` | an old annual competition Page |

The script adds 21 rows (10 + 10 + 1). Facts about it:

- Table prefix `wpjj_`, checked against the production dump (`../live-import/inkorgza_wp821.sql`).
- It only inserts, and only where the Page exists and the row is not already there, so it is safe to run twice. A second run leaves 21 rows.
- It ends with a `SELECT` that should return 21 rows.
- It changes no title, content, status or URL.
- Take a database backup before running it, as for any production change.
- The page ids and years in it were cross-checked against what the migration finds on dev. They matched.

## 3. What the migration does with them

`Ink\Competitions\Migration` (`wp-content/plugins/ink-core/src/Competitions/Migration.php`), run by `wp ink migrate-kompetisies` and as a step of `wp ink migrate-all`.

**Discovery (`discoverGalas()`), in this order:**
1. Pages tagged `ink_old_gala` are galas; the year comes from the tag. The Fotos Page is the tagged child, otherwise the Page tagged `ink_old_gala_fotos` with the same year. A gala with only one of its two tags still works.
2. Fallback: a top-level Page with "gala" in its title and a child Page titled "Foto…". Each use of the fallback is reported as **"found by title; tag it"** and appears in the summary's `warnings`. Warnings do not count as skipped rows.
3. A Page tagged `ink_old_competition` becomes a competition. There is **no title fallback** for competitions: an untagged one is simply not found.

**Import, per gala:**
- Name, year and body come from the gala Page.
- Photos are read from the Fotos Page itself: the ids in `vc_media_grid`, `vc_masonry_media_grid`, `vc_gallery` and `vc_single_image` shortcodes (`include`, `images`, `image`), in page order, duplicates dropped. Videos are attachments and link the same way.
- Key: `gala-<year>` (`-2` for a second gala in a year). A competition is `kompetisie-<year>`.
- It creates one `kompetisie` post per key, with the highlight and the gallery (`ink_kompetisie_gala_fotos`).
- The old Pages are trashed after their photos are linked, so the redirects take over.
- A Fotos Page that yields no photos is reported, nothing is overwritten and its Pages are kept. This guard exists because dev's Fotos Pages are already cleaned (see §5).

**Redirects.** The 21 old paths (`/ink-se-gala-aande/`, ten gala Pages, ten Fotos Pages) are a fixed list in `Migration\StaticRedirects::EXACT`, all going to `/kompetisies/`. For a gala that is not on that list, the migration records the old path of every Page it trashes in the option `ink_gala_old_paths`, before trashing, and `RedirectGenerator::maybeRedirect()` serves those paths through `StaticRedirects::storedGalaTarget()`. A new 2026 gala therefore redirects with no code change.

## 4. The CSV that remains

`../migration-data/kompetisies-oud.csv` is now **optional overrides** only. It is 285 bytes. A missing file is harmless.

| Column | Use |
|---|---|
| `sleutel` | The only required column: the key (`gala-2025`) or a bare year |
| `hoogtepunt` | A short highlight for that gala |
| `onderskrifte` | Captions, one `attachment-id|caption` per line |

A blank cell overrides nothing. The old 151 KB version, with every photo path, is in `../migration-data/backup/kompetisies-oud.csv`. The current file still has a now-redundant `kompetisie-2022` row, which is harmless.

## 5. Order and other traps

- **The step must run before `clean-shortcodes`.** Cleaning strips the gallery shortcodes from the Fotos Pages. `migrate-all` runs them in the right order, but the step **cannot be re-run on a cleaned database**: dev's Fotos Pages already hold only `<p></p>`. A first `--force` run on dev blanked the galleries before the guard existed; they were rebuilt from the backup CSV (656, 441, 464, 571, 431, 386, 166, 101, 103, 60 photos for 2025 down to 2016, 3,379 in all).
- The 2024 Fotos Page (61228) has three empty `vc_media_grid` shortcodes with no `include` list. This gives a harmless warning on every run; the 441 count still matches.
- Never `--force` on the live site. After go-live nothing here runs.
- A new gala before the cutover: add the same three-row pattern to the SQL for its two Pages and run it on production before the snapshot. If forgotten, the title fallback finds it and warns.

## 6. Verification, and what is not verified

Verified (unit tests in `tests/Unit/Competitions/MigrationTest.php`, and dev checks):
- Tagged Pages import with no warning; the title fallback gives the exact warning text; a gala with only one of its two tags works both ways round; a tagged competition imports with no CSV row; a CSV override does not duplicate a post; a gala absent from the CSV imports in full.
- On dev, inside a rolled-back transaction: with the SQL applied the migration found 11 rows (10 galas + the competition) and 0 warnings; without it, 10 rows and 20 warnings. After the rollback dev holds no `ink_old_*` rows.
- Per-year photo counts equal the list in §5.

Not verified:
- The step has **not run against a real refreshed database**, because dev's shortcodes are already cleaned. The photo parsing was proved against the production dump's Fotos Page bodies, not a live import. The refresh proof (step H) is the first real test.
- Three of the dev `kompetisie` posts (gala-2023, 2024, 2025) show a different meta checksum from before the first rebuild; the photo ids themselves are right, and the cause was not found.
- The SQL has not been run on production.

## 7. Files

- `docs/migration-tools/tag-old-competitions.sql`: the script.
- `wp-content/plugins/ink-core/src/Competitions/Migration.php`: discovery and import.
- `wp-content/plugins/ink-core/src/Migration/StaticRedirects.php`, `RedirectGenerator.php`: redirects.
- `wp-content/plugins/ink-core/src/Kernel/MigrationData.php`: the CSV is no longer in `FILES`; `tools/rehearsal-prep.mjs` no longer requires it.
- Docs: `docs/migration-data.md`, `docs/migration-runbook.md` (step 2a: run the SQL on production before the export), `docs/lovable-delta-2026-10/specs/05-kompetisies.md` §3.6 to 3.7.
- Tests: `tests/Unit/Competitions/MigrationTest.php`, `tests/Unit/Migration/RedirectGeneratorTest.php`.

# Migration data

**Three files, and no others.** They live outside the repo and the plugin, in `../migration-data/` beside the repo (`C:\dev\ink\migration-data` on Windows). `INK_MIGRATION_DATA_DIR` in the site's `wp-config.php` names that folder (`tools/rehearsal-prep.mjs` sets it), and `Ink\Kernel\MigrationData` reads from it. There is no fallback: `wp ink migrate-all` and `rehearsal-prep.mjs` both stop before doing anything if a file is missing.

**Everything else is banned as a migration input.** The migration reads the prod database and these three files only. No other CSV, spreadsheet or list may be read by `migrate-all`, whatever folder it sits in. `MigrationDataTest` fails if any code under `ink-core/src` names another `.csv` file. Superseded files are kept in `../backup-data/` (outside the repo, never read by any code).

| File | What | Why it cannot come from the database |
| --- | --- | --- |
| `writer-accounts.csv` | `name,user_login`. A by-line as printed on the old result posts (pen names, maiden names, spelling variants) and the account it belongs to | The knowledge exists nowhere else. Used by `wp ink derive-tiers` |
| `writer-aliases.csv` | `variant,canonical`. Misspellings of one by-line, mapped to its canonical form (then looked up in `writer-accounts.csv` or among the site's users) | Same |
| `inkpols-uitgawes.csv` | One row per InkPols uitgawe, owner-maintained (below) | The titles, descriptions and featured items are the owner's text |

Add a row to `writer-accounts.csv` or `writer-aliases.csv` when `derive-tiers` reports an unresolved writer that you identify.

## `inkpols-uitgawes.csv`

Columns: `slug` and `naam` (the months, e.g. `Julie/Augustus 2026`, shown as the eyebrow), `titel` and `beskrywing` (filled in by hand), `datum` (the first month of the issue, used as the publish date, so the newest row is the current issue), `pdf` and `voorblad` (paths under `wp-content/uploads/`, matching the prod attachments; the cover is also the social image), `ou_url` (the old prod Page, redirected to the new URL), and the three featured items `glans_1`, `glans_2`, `glans_3` (owner ruling 2026-11-03: a variety of items from the magazine), each with three columns: `glans_N_titel`, `glans_N_skrywer` and `glans_N_tipe` (free text saying what the item is, e.g. "Kortverhaal", shown as its label; max 40 characters). All nine are required. Read by `wp ink migrate-inkpols`: a row with any blank required cell (everything except `ou_url`) becomes a draft issue, and the step lists the missing cells. On prod, the February issue's PDF was uploaded twice; the file listed is the one its page used. After go-live an editor adds each issue in admin; the CSV is not used again.

## What is read from the database instead

- **Who publishes the result posts:** the authors of posts with a result title (`Ink\Content\ResultPostAuthors`), plus anyone who can edit others' posts. There is no list.
- **Old galas and the 2022 competition:** the `ink_old_gala`, `ink_old_gala_fotos` and `ink_old_competition` meta the owner sets on production with `docs/migration-tools/tag-old-competitions.sql` before the export. Untagged galas are found by title with a "found by title; tag it" warning; an untagged competition is not found. Highlights and captions are edited on the site.
- **Winners, wins and Gradering:** the old challenge result posts, read by `LegacyResultsParser`: `derive-tiers` sets each writer's grade from the Gedigte wins, and `import-winners` records every win (`WinnerImporter`). Competition result posts are never read. Deleted accounts and unresolved by-lines are reported, never guessed.

## Comparing text

People can't type Afrikaans reliably, so accents get dropped or swapped and the article 'n arrives as ŉ, ñ, ń, 'n, ’n, n or N. Writers also add their grade, class, word count and project to titles ("(Silwer gradering)", "[brons]", "Goudklas"). `Ink\Migration\TextKey` folds all of that away; compare titles and names only through it.

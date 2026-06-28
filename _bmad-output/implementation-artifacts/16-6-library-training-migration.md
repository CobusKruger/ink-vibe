---
baseline_commit: c7e6a40
---

# Story 16.6: Library/training migration

Status: done

<!-- Note: Validation is optional. Run validate-create-story for quality check before dev-story. -->

## Story

As an ink-core developer,
I want library/training migrated by URL sub-path,
so that prefixes and taxonomy survive. (FL 16.6)

## Acceptance Criteria

1. **Given** legacy library/training content **When** migrated by URL sub-path **Then** items map to CPT + taxonomy terms, keeping `/biblioteek/` and `/opleiding/` prefixes.
2. **Sub-path → CPT:** content under the `/biblioteek/` prefix → `biblioteek_item`; under `/opleiding/` → `opleiding_artikel`. Anything else is skipped (handled by 16.5). The CPTs register their archives at the `biblioteek`/`opleiding` bases, so the prefixes are preserved.
3. **Sub-path → taxonomy terms:** the intermediate path segments (between the prefix and the final post slug) become taxonomy terms — `genre` for `biblioteek_item`, `vaardigheid` for `opleiding_artikel` (their primary taxonomies). Terms are get-or-created and **appended** (never clobbering existing terms).
4. The pre-migration permalink is recorded in `PostReclassifier::SOURCE_URL_META` before the type change (shared with Story 16.7); rewrite rules flushed after.
5. Once-off + idempotent (`ink_migration_library_done`; `--force`) and **WP-CLI only** (`wp ink migrate-library-training`). Afrikaans `\WP_CLI` summary.
6. Conflation-clean: reads `Content\PostTypes` + `Content\Taxonomies` only; no `Tiers`/`Entitlement` coupling (the `Migration → Content` edge already exists). `composer test:unit` green (new `LibraryTrainingMigratorTest`); `composer cs` = 0 errors; `php -l`, `composer stan`, `composer deptrac`, `composer copy:scan` clean.

## Tasks / Subtasks

- [x] Task 1: Implement `LibraryTrainingMigrator` (AC: #1–#5)
  - [x] Added `wp-content/plugins/ink-core/src/Migration/LibraryTrainingMigrator.php`: `OPTION_DONE`, `CLI_COMMAND = 'ink migrate-library-training'`, `register()` (WP-CLI-only), `run(bool $force): array`, `hasRun()`, `markDone()`.
  - [x] Pure helpers: `pathSegments(string $path): array` (raw `parse_url` path, trimmed/exploded/filtered — kept WP-free + unit-testable), `cptForPath()`, `taxonomyForCpt()` (genre/vaardigheid), `termSlugsFromPath()` (segments between prefix and post slug).
  - [x] Overridable I/O seams: `legacyContent(): array` (prefix-filtered rows `{id, url}`), `recordSourceUrl()` (reuses `PostReclassifier::SOURCE_URL_META`), `setPostType()`, `assignTerms()` (get-or-create + append), `flushRewrites()`.
  - [x] Afrikaans CLI summary (biblioteek / opleiding counts + terms assigned).
- [x] Task 2: Register in the module (AC: #5)
  - [x] Added `( new LibraryTrainingMigrator() )->register();` to `Migration\Module::register()` + docblock.
- [x] Task 3: Tests (AC: #2, #3, #6)
  - [x] Added `tests/Unit/Migration/LibraryTrainingMigratorTest.php` (5 tests): `cptForPath` for biblioteek/opleiding/other; `termSlugsFromPath` drops the prefix + post slug and keeps the middle segments (empty for a flat `/biblioteek/slug/`); `taxonomyForCpt`; `run()` maps each item to its CPT, assigns terms in the right taxonomy (genre vs vaardigheid), records source URLs, flushes once.
  - [x] All gates green.

## Dev Notes

### What already exists (read before editing)
- `wp-content/plugins/ink-core/src/Content/PostTypes.php` — `BIBLIOTEEK_ITEM`/`OPLEIDING_ARTIKEL` slugs + their `biblioteek`/`opleiding` archive bases (the kept prefixes).
- `wp-content/plugins/ink-core/src/Content/Taxonomies.php` — `GENRE` (library primary), `VAARDIGHEID` (training primary; "Training is its primary home").
- `wp-content/plugins/ink-core/src/Migration/PostReclassifier.php` — reuse `SOURCE_URL_META` for the 301-source recording; mirror its seam shape and the `Challenges\Migration` get-or-create+append term pattern (`linkPostsToRound`).
- `tests/Unit/InkPols/MigrationTest.php` — anonymous-subclass-over-seams idiom.

### Architecture compliance (project-context.md)
- **Keep `/biblioteek/` and `/opleiding/` prefixes** (migration plan — high-value archive URLs). The CPT archive bases already do this; the migration only changes `post_type` + assigns terms, so single URLs stay on-prefix.
- **Migrate by URL sub-path; each sub-path becomes a taxonomy term** (migration plan). Append terms, never clobber.
- **Shared-taxonomy surfacing** — library/training share `genre`/`vaardigheid` with bydraes so resources surface automatically (Principle 8); never per-item manual linking.
- **Conflation-clean**, WP-CLI-only, idempotent + `--force`, Afrikaans CLI.

### Project Structure Notes
- NEW: `src/Migration/LibraryTrainingMigrator.php`, `tests/Unit/Migration/LibraryTrainingMigratorTest.php`.
- MODIFIED: `src/Migration/Module.php`. No new deptrac edge (`Migration → Content` already declared in 16.5).

### Testing standards
- Override the post/term seams; test the path/CPT/taxonomy helpers as pure functions.
- Run `composer test:unit`, `composer cs`, `php -l`, `composer stan`, `composer deptrac`, `composer copy:scan`.

### References
- [Source: _bmad-output/planning-artifacts/epics.md#Story 16.6: Library/training migration] (FL 16.6)
- [Source: docs/migration-plan.md — Library content (/biblioteek/); Training content (/opleiding/); "scriptable based on URL patterns … Each sub-path becomes a taxonomy term"; keep prefixes]
- [Source: _bmad-output/project-context.md — keep /biblioteek/ /opleiding/ prefixes; shared-taxonomy surfacing]
- [Source: wp-content/plugins/ink-core/src/Content/{PostTypes,Taxonomies}.php — CPT + taxonomy single sources]

## Dev Agent Record

### Agent Model Used

claude-opus-4-8 (BMAD dev-story workflow)

### Debug Log References

- `composer test:unit` → 979 passed, 1 skipped (3742 assertions). New `LibraryTrainingMigratorTest`: 5 passed.
- `composer cs` → 0 errors, 0 warnings (the raw `parse_url` in the pure helper carries a scoped phpcs:ignore + rationale).
- `composer stan` → No errors.
- `composer deptrac` → 3 pre-existing only; no new edge (`Migration → Content` already declared in 16.5).
- `composer copy:scan` → no new debt (baseline 8).
- `php -l` clean on `LibraryTrainingMigrator.php`.

### Completion Notes List

- `LibraryTrainingMigrator` maps `/biblioteek/` content → `biblioteek_item` and `/opleiding/` content → `opleiding_artikel` by URL prefix; the CPT archives register at those bases so the high-value prefixes are preserved.
- Sub-path segments between the prefix and the post slug become taxonomy terms — `genre` for library, `vaardigheid` for training (their primary taxonomies) — get-or-created and **appended** (never clobbering existing terms).
- Pre-migration permalink recorded in the shared `PostReclassifier::SOURCE_URL_META` (Story 16.7 source); rewrite rules flushed after.
- Once-off + idempotent (`ink_migration_library_done`; `--force`); WP-CLI-only (`wp ink migrate-library-training`); Afrikaans summary. Conflation-clean: reads `Content\PostTypes` + `Content\Taxonomies` only.

### File List

- `wp-content/plugins/ink-core/src/Migration/LibraryTrainingMigrator.php` (NEW)
- `wp-content/plugins/ink-core/src/Migration/Module.php` (MODIFIED — registered `LibraryTrainingMigrator` + docblock)
- `tests/Unit/Migration/LibraryTrainingMigratorTest.php` (NEW)
- `_bmad-output/implementation-artifacts/16-6-library-training-migration.md` (story record)
- `_bmad-output/implementation-artifacts/sprint-status.yaml` (status tracking)

## Change Log

- 2026-06-28 — Story 16.6 implemented: `LibraryTrainingMigrator` (sub-path → biblioteek_item/opleiding_artikel keeping /biblioteek/ /opleiding/ prefixes; path segments → genre/vaardigheid terms, appended; source-URL recorded for the 16.7 301). Status → review.

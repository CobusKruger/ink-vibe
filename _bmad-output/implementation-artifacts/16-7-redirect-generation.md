---
baseline_commit: c7e6a40
---

# Story 16.7: Redirect generation

Status: review

<!-- Note: Validation is optional. Run validate-create-story for quality check before dev-story. -->

## Story

As an ink-core developer,
I want 301s for every changed URL,
so that no link breaks (NFR-4). (FL 16.7)

## Acceptance Criteria

1. **Given** any CPT reassignment that changes a URL **When** migration runs **Then** the old permalink is recorded before reassignment and a 301 is emitted; redirects verified by crawl; `/biblioteek/`,`/opleiding/` prefixes kept.
2. The redirect map is built from the `PostReclassifier::SOURCE_URL_META` recorded by Stories 16.5/16.6 (old permalink) vs the post's current permalink (new). An entry is created ONLY when the **path changed** — an unchanged URL (e.g. a `/biblioteek/` single whose prefix was kept) produces **no redirect** (prefixes kept, AC #1).
3. The 301 is actually **emitted at runtime**: a `template_redirect` handler looks up the request path in the stored map and issues `wp_safe_redirect( $target, 301 )`. (The serve handler runs on every front-end request; the *build* is WP-CLI only.) A redirect-loop guard skips when the target path equals the request path.
4. The build step is once-off + idempotent (`ink_migration_redirects_done`; `--force` rebuilds) and **WP-CLI only** (`wp ink generate-redirects`); it stores the map in the `ink_migration_redirects` option and reports the count. "Verified by crawl" is the QA step (the build reports the count; the serve handler issues the 301s).
5. Path comparison is normalised (leading/trailing-slash-insensitive, path-only) so `/foo/` and `/foo` compare equal and full URLs reduce to their path.
6. Conflation-clean: reads only the recorded source-URL meta + WP core; no `Tiers`/`Entitlement`. `composer test:unit` green (new `RedirectGeneratorTest`, non-vacuous "unchanged URL → no redirect" + "changed URL → 301" cases); `composer cs` = 0 errors; `php -l`, `composer stan`, `composer deptrac`, `composer copy:scan` clean.

## Tasks / Subtasks

- [x] Task 1: Implement `RedirectGenerator` (AC: #1–#5)
  - [x] Added `wp-content/plugins/ink-core/src/Migration/RedirectGenerator.php`: `OPTION_MAP`, `OPTION_DONE`, `CLI_COMMAND = 'ink generate-redirects'`, `register()` (always adds `template_redirect`; adds the CLI build command under WP-CLI), `build(bool $force): array`, `maybeRedirect(): void`, `hasRun()`, `markDone()`.
  - [x] Pure helpers: `normalisePath()` (path-only, single leading slash, no trailing), `buildRedirectMap()` (old-path → new-URL, only when changed), `redirectTargetFor()`.
  - [x] Overridable I/O seams: `recordedRedirectSources()` (posts with `SOURCE_URL_META` via `meta_query EXISTS`), `currentPermalink()`, `storeMap()`, `loadMap()`, `requestPath()` (sanitised `REQUEST_URI`), `doRedirect()` (`wp_safe_redirect( $target, 301 )` + exit).
  - [x] Afrikaans CLI summary (301 rules built).
- [x] Task 2: Register in the module (AC: #3)
  - [x] Added `( new RedirectGenerator() )->register();` to `Migration\Module::register()`; corrected the module docblock (the serve handler is the sole runtime surface).
- [x] Task 3: Tests (AC: #2, #3, #5, #6)
  - [x] Added `tests/Unit/Migration/RedirectGeneratorTest.php` (8 tests): `normalisePath` slash-normalisation; `buildRedirectMap` — **non-vacuous**: a changed URL yields an entry, an unchanged URL (prefix kept) AND a trailing-slash-only diff yield NONE; missing old/new skipped; `redirectTargetFor` match/miss; `build()` stores the changed-URL map; `maybeRedirect()` 301s on a match, no-op on miss / empty map / self-target (loop guard).
  - [x] All gates green.

## Dev Notes

### What already exists (read before editing)
- `wp-content/plugins/ink-core/src/Migration/PostReclassifier.php` — `SOURCE_URL_META` (the recorded old permalink); 16.6 reuses it too. 16.7 reads it.
- `wp-content/plugins/ink-core/src/Migration/*` + `Module.php` — the seam pattern. NOTE: unlike the other commands, the redirect *serve* handler runs on every request (not WP-CLI-gated) — only the *build* is CLI-only.
- `tests/Unit/InkPols/MigrationTest.php` — anonymous-subclass-over-seams idiom.

### Architecture compliance (project-context.md)
- **Redirects are mandatory — every CPT reassignment that changes a URL emits a 301** (NFR-4). The old permalink is recorded BEFORE reassignment (16.5/16.6) and the 301 emitted here.
- **Keep `/biblioteek/` and `/opleiding/` prefixes** — an unchanged URL must produce NO redirect (the build's "only when changed" rule).
- **Escape/sanitise:** `requestPath()` sanitises `$_SERVER['REQUEST_URI']` (`wp_unslash` + `esc_url_raw`); the redirect uses `wp_safe_redirect`.
- **Conflation-clean**, idempotent build + `--force`, Afrikaans CLI.

### Project Structure Notes
- NEW: `src/Migration/RedirectGenerator.php`, `tests/Unit/Migration/RedirectGeneratorTest.php`.
- MODIFIED: `src/Migration/Module.php`. No new deptrac edge (Migration-internal).

### Testing standards
- Override the option/permalink/request seams; test the map + path helpers as pure functions.
- **Non-vacuous guard:** prove a changed URL produces a 301 AND an unchanged (prefix-kept) URL produces none — so a regression that over- or under-generates redirects fails.
- Run `composer test:unit`, `composer cs`, `php -l`, `composer stan`, `composer deptrac`, `composer copy:scan`.

### References
- [Source: _bmad-output/planning-artifacts/epics.md#Story 16.7: Redirect generation] (FL 16.7, NFR-4)
- [Source: docs/migration-plan.md — Redirects ("This is mandatory"); "record the old permalink before CPT reassignment and write a redirect rule after"; keep /biblioteek/ /opleiding/ prefixes]
- [Source: _bmad-output/project-context.md — redirects are mandatory; every CPT reassignment that changes a URL emits a 301]
- [Source: wp-content/plugins/ink-core/src/Migration/PostReclassifier.php — SOURCE_URL_META]

## Dev Agent Record

### Agent Model Used

claude-opus-4-8 (BMAD dev-story workflow)

### Debug Log References

- `composer test:unit` → 987 passed, 1 skipped (3760 assertions). New `RedirectGeneratorTest`: 8 passed.
- `composer cs` → 0 errors (fixed one Yoda-condition error on the changed-path comparison).
- `composer stan` → No errors.
- `composer deptrac` → 3 pre-existing only; no new edge (Migration-internal; reads `PostReclassifier::SOURCE_URL_META`).
- `composer copy:scan` → no new debt (baseline 8).
- `php -l` clean on `RedirectGenerator.php`.

### Completion Notes List

- `RedirectGenerator` has two halves: a **WP-CLI build** (`wp ink generate-redirects`, once-off + idempotent) that reads each post's recorded `SOURCE_URL_META` (old permalink) vs its current permalink and stores a map of CHANGED paths only; and a **runtime `template_redirect` serve handler** that 301s the request path to the mapped target.
- **Prefixes kept = no redirect:** `buildRedirectMap` emits an entry only when the normalised old path differs from the new — a `/biblioteek/` single whose prefix was kept (or a trailing-slash-only diff) produces nothing. The non-vacuous test proves both the changed→301 and unchanged→none cases.
- **Safety:** path comparison is slash-normalised; a redirect-loop guard skips when the target path equals the request path; `requestPath()` sanitises `REQUEST_URI` (`wp_unslash` + `esc_url_raw`); the 301 uses `wp_safe_redirect`.
- "Verified by crawl" is the QA step — the build reports the rule count; the serve handler issues the 301s. Conflation-clean (reads source-URL meta + WP core only).

### File List

- `wp-content/plugins/ink-core/src/Migration/RedirectGenerator.php` (NEW)
- `wp-content/plugins/ink-core/src/Migration/Module.php` (MODIFIED — registered `RedirectGenerator` + docblock)
- `tests/Unit/Migration/RedirectGeneratorTest.php` (NEW)
- `_bmad-output/implementation-artifacts/16-7-redirect-generation.md` (story record)
- `_bmad-output/implementation-artifacts/sprint-status.yaml` (status tracking)

## Change Log

- 2026-06-28 — Story 16.7 implemented: `RedirectGenerator` — WP-CLI build of the changed-URL 301 map from recorded source permalinks + a runtime `template_redirect` serve handler (prefixes kept = no redirect; loop-guarded). Status → review.

---
baseline_commit: c7e6a40
---

# Story 16.5: Post → CPT reclassification

Status: review

<!-- Note: Validation is optional. Run validate-create-story for quality check before dev-story. -->

## Story

As an ink-core developer,
I want posts reclassified to CPTs,
so that content lands in the right model. (FL 16.5)

## Acceptance Criteria

1. **Given** legacy posts **When** reclassified (category-driven) **Then** unclassifiable → `skryfwerk` catch-all (**not hand-classified at volume**); rewrite rules flushed **And** `inkpols`→`inkpols_uitgawe` rename; `monthly_challenge` not migrated 1:1 (uitdaging records built from round categories, real data folded in else dropped).
2. **Category-driven mapping** (content-type category is the source of truth): a `post` with exactly ONE recognised content-type category → that CPT (`gedig`/`storie`/`artikel`); **zero recognised OR conflicting (2+) content-type categories → `skryfwerk`** automatically (no hand-classification).
3. **Legacy `verhaal`→`storie`** is honoured in the category map (project-context migration-load-bearing rename); **`inkpols`→`inkpols_uitgawe`** is a post-type rename; **`monthly_challenge` posts are SKIPPED** (left for `Ink\Challenges\Migration` to build uitdaging records from round categories — real data folded in, else dropped — NOT a 1:1 conversion here).
4. **Old permalink recorded before reassignment**: every reassigned post's pre-migration permalink is stored in `ink_migration_source_url` user-/post-meta BEFORE its `post_type` changes, so Story 16.7 can emit the 301. (The redirect EMISSION is 16.7; 16.5 records the source.)
5. **Rewrite rules flushed** after reassignment (the deferred Epic-2 gap: the activation flush does not cover post-activation slug changes — this flush does).
6. **Slug-collision guard** (deferred Epic-2): a pure helper detects when a CPT archive base (`gedig`/`storie`/`artikel`/`skryfwerk`/`biblioteek`/`opleiding`/`uitdaging`/`inkpols`) collides with an existing page slug, and the command surfaces any collision as a warning before it flushes (so `/biblioteek/` etc. archive vs same-slug page is caught). The `/inkpols/` archive-URL mapping is recorded in `docs/migration-plan.md`.
7. Once-off + idempotent (`ink_migration_posts_done`; `--force` re-runs) and **WP-CLI only** (`wp ink migrate-posts`). Afrikaans `\WP_CLI` summary.
8. Conflation-clean: reads the `Content\PostTypes` slug registry + WP core; no `Tiers`/`Entitlement` coupling. `composer test:unit` green (new `PostReclassifierTest`, non-vacuous mapping + conflict guard); `composer cs` = 0 errors; `php -l`, `composer stan`, `composer deptrac` (declares `Migration → Content`), `composer copy:scan` clean.

## Tasks / Subtasks

- [x] Task 1: Implement `PostReclassifier` (AC: #1–#7)
  - [x] Added `wp-content/plugins/ink-core/src/Migration/PostReclassifier.php`: `OPTION_DONE`, `CLI_COMMAND = 'ink migrate-posts'`, `SOURCE_URL_META = 'ink_migration_source_url'` (shared with 16.7), `SKIPPED_TYPE`, `CATEGORY_CPT_MAP`, `register()` (WP-CLI-only), `run(bool $force): array`, `hasRun()`, `markDone()`.
  - [x] Pure helpers: `cptForCategorySlugs(array $slugs): string` (exact map incl. `verhaal`→`storie`; one distinct match → that CPT, zero/conflicting → `skryfwerk`), `renamedPostType(string $legacy): ?string` (`inkpols`→`inkpols_uitgawe`), `isSkippedType(string $legacy): bool` (`monthly_challenge`), `archiveSlugs(): array`, `slugCollisions(array $pageSlugs): array`.
  - [x] Overridable I/O seams: `legacyPosts(): array` (default `get_posts(post_type=['post','inkpols'])` + category slugs), `categorySlugsFor()`, `recordSourceUrl(int $id): void` (store `get_permalink` in `SOURCE_URL_META`), `setPostType(int $id, string $type): void` (`set_post_type`), `existingPageSlugs(): array`, `flushRewrites(): void` (`flush_rewrite_rules`).
  - [x] Orchestration: skip `monthly_challenge`; target = `renamedPostType()` ?? (`post` → `cptForCategorySlugs()`), other types untouched; record source URL → set type; tally `reassigned`/`to_skryfwerk`/`renamed`/`skipped`; warn on `slugCollisions`; flush once.
- [x] Task 2: Register + docs (AC: #5, #6)
  - [x] Added `( new PostReclassifier() )->register();` to `Migration\Module::register()`.
  - [x] Recorded the `/inkpols/` archive-URL mapping in `docs/migration-plan.md` (alongside `/biblioteek/` and `/opleiding/` — the deferred Epic-2 doc gap).
- [x] Task 3: Tests (AC: #2, #3, #6, #8)
  - [x] Added `tests/Unit/Migration/PostReclassifierTest.php` (7 tests): `cptForCategorySlugs` maps `gedig`/`verhaal`(→storie)/`kortverhaal`/`artikel`; **non-vacuous** — zero match AND conflicting (gedig+artikel) BOTH → `skryfwerk`; `renamedPostType`/`isSkippedType`; `slugCollisions` finds an archive↔page clash and is empty when none; `run()` skips `monthly_challenge` (no reassign, no source-URL record), renames `inkpols`, records source URLs in order, flushes exactly once.
  - [x] All gates green.

## Dev Notes

### What already exists (read before editing)
- `wp-content/plugins/ink-core/src/Content/PostTypes.php` — the CPT slug single source (`GEDIG`/`STORIE`/`ARTIKEL`/`SKRYFWERK`/`INKPOLS_UITGAWE` consts) and the archive bases (`gedig`/`storie`/`artikel`/`skryfwerk`/`biblioteek`/`opleiding`/`uitdaging`/`inkpols`). Source ALL slugs here (migration-load-bearing) — never inline a literal.
- `wp-content/plugins/ink-core/src/Challenges/Migration.php` — owns the legacy-challenge → uitdaging build (from round categories). 16.5 must NOT convert `monthly_challenge` 1:1 — it skips them for this migration.
- `wp-content/plugins/ink-core/src/InkPols/Migration.php` — owns the inkpols issue meta/PDF re-link (13.4); 16.5 only does the `post_type` rename.
- `wp-content/plugins/ink-core/src/Migration/*` + `Module.php` — the once-off-CLI + seam pattern.
- `tests/Unit/InkPols/MigrationTest.php` — the anonymous-subclass-over-seams idiom.

### Architecture compliance (project-context.md)
- **Category-driven CPT mapping; unclassifiable → `skryfwerk`; do NOT hand-classify at volume.** Conflicting categories also fall through to `skryfwerk` (migration-plan).
- **Record the old permalink BEFORE reassignment** (mandatory 301 prep — Story 16.7 emits). The `SOURCE_URL_META` is the shared seam.
- **`verhaal`→`storie`, `inkpols`→`inkpols_uitgawe`** are migration-load-bearing renames (project-context). `monthly_challenge` is not migrated 1:1.
- **Deferred Epic-2 review items handled here:** guard CPT-archive/page slug collisions; ensure the rewrite flush covers post-activation slug changes; record the `/inkpols/` archive-URL mapping in the migration plan.
- **Conflation-clean:** reads `Content\PostTypes` only; no tier/entitlement. Deptrac: declare `Migration → Content` (the CPT slug registry — mirrors Submission/Discovery/Library `→ Content`).

### Project Structure Notes
- NEW: `src/Migration/PostReclassifier.php`, `tests/Unit/Migration/PostReclassifierTest.php`.
- MODIFIED: `src/Migration/Module.php`, `deptrac.yaml` (`Migration → Content`), `docs/migration-plan.md` (`/inkpols/` mapping).

### Testing standards
- Override the post/DB seams; test the mapping/collision helpers as pure functions.
- **Non-vacuous guard:** assert that BOTH a no-match AND a conflicting-category post resolve to `skryfwerk` (so the "never guess at volume" rule can fail if mapping regresses), and that a `monthly_challenge` post is never reassigned.
- Run `composer test:unit`, `composer cs`, `php -l`, `composer stan`, `composer deptrac`, `composer copy:scan`.

### References
- [Source: _bmad-output/planning-artifacts/epics.md#Story 16.5: Post → CPT reclassification] (FL 16.5; deferred Epic-2 review items)
- [Source: docs/migration-plan.md — Classify existing posts; mapping approach; "conflicting or missing categories fall through to skryfwerk automatically"; Redirects (record old permalink before reassignment)]
- [Source: _bmad-output/project-context.md — categories→CPTs, skryfwerk catch-all, don't hand-classify at volume; verhaal→storie, inkpols→inkpols_uitgawe; mandatory 301]
- [Source: wp-content/plugins/ink-core/src/Content/PostTypes.php — CPT slug + archive single source]

## Dev Agent Record

### Agent Model Used

claude-opus-4-8 (BMAD dev-story workflow)

### Debug Log References

- `composer test:unit` → 974 passed, 1 skipped (3722 assertions). New `PostReclassifierTest`: 7 passed.
- `composer cs` → 0 errors, 0 warnings (phpcbf aligned the `CATEGORY_CPT_MAP` double-arrows).
- `composer stan` → No errors.
- `composer deptrac` → 3 pre-existing `Kernel\Activation → Content` violations only; the new `Migration → Content` edge is declared + allowed.
- `composer copy:scan` → no new debt (baseline 8).
- `php -l` clean on `PostReclassifier.php`.

### Completion Notes List

- `PostReclassifier` reclassifies legacy flat `post` content category-driven: a single recognised content-type category → that CPT (`CATEGORY_CPT_MAP`, incl. the legacy `verhaal`→`storie` rename); **zero recognised OR conflicting (2+) categories → `skryfwerk`** automatically — never hand-classified at volume (the non-vacuous guard covers both fall-through cases).
- **`inkpols`→`inkpols_uitgawe`** is a post-type rename; **`monthly_challenge` posts are SKIPPED** (left for `Challenges\Migration` to build uitdaging records from round categories — not converted 1:1 here). Other post types are left untouched.
- **301 prep:** each reassigned post's pre-migration permalink is recorded in `SOURCE_URL_META` BEFORE the `post_type` change (Story 16.7 emits the redirect from this). Rewrite rules are flushed once after the loop (covers the post-activation slug change — the deferred Epic-2 gap).
- **Slug-collision guard:** `slugCollisions()` detects CPT-archive ↔ page slug clashes (`/biblioteek/`, `/opleiding/`, `/inkpols/`, …) and the command warns before flushing; the `/inkpols/` archive-URL mapping was recorded in `docs/migration-plan.md`.
- Once-off + idempotent (`ink_migration_posts_done`; `--force`); WP-CLI-only (`wp ink migrate-posts`); Afrikaans summary. Conflation-clean: reads `Content\PostTypes` slug registry only.

### File List

- `wp-content/plugins/ink-core/src/Migration/PostReclassifier.php` (NEW)
- `wp-content/plugins/ink-core/src/Migration/Module.php` (MODIFIED — registered `PostReclassifier` + docblock)
- `deptrac.yaml` (MODIFIED — `Migration → Content` edge)
- `docs/migration-plan.md` (MODIFIED — `/inkpols/` archive-URL mapping + kept-prefix note)
- `tests/Unit/Migration/PostReclassifierTest.php` (NEW)
- `_bmad-output/implementation-artifacts/16-5-post-cpt-reclassification.md` (story record)
- `_bmad-output/implementation-artifacts/sprint-status.yaml` (status tracking)

## Change Log

- 2026-06-28 — Story 16.5 implemented: `PostReclassifier` (category→CPT, conflicting/unclassifiable → skryfwerk; inkpols→inkpols_uitgawe rename; monthly_challenge skipped; old-permalink recorded for the 16.7 301; rewrite flush + slug-collision guard) + migration-plan `/inkpols/` mapping. Status → review.

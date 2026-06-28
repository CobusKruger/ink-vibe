---
baseline_commit: c7e6a40
---

# Story 16.1: DB clone & sanitise

Status: done

<!-- Note: Validation is optional. Run validate-create-story for quality check before dev-story. -->

## Story

As an ink-core developer,
I want a clean DB baseline,
so that migration starts from a sane state. (FL 16.1)

## Acceptance Criteria

1. **Given** the cloned DB **When** sanitised **Then** transients/logs are stripped to a clean baseline (members, subscriptions, content, media preserved).
2. The sanitiser is a **once-off, idempotent** operation: a completion flag (`ink_migration_sanitise_done`) makes a re-run a no-op; a `--force` re-run is opt-in. (The `Ink\InkPols\Migration` / `Ink\Challenges\Migration` precedent.)
3. The trigger is **WP-CLI only** (`wp ink migrate-sanitise`) — NEVER auto-run on a web request (`defined('WP_CLI')` + `class_exists('\WP_CLI')` guard; production-hygiene rule).
4. **Transients** removed = every `_transient_%`, `_transient_timeout_%`, `_site_transient_%`, `_site_transient_timeout_%` option row (they regenerate on demand). Deletion uses `$wpdb->prepare()` / `$wpdb->esc_like()` — **never** interpolated SQL.
5. **Logs** removed = Action Scheduler *finished* actions (`complete`, `failed`, `canceled`) and their `actionscheduler_logs` rows — **pending / in-progress** scheduled actions are PRESERVED (deleting them would drop live scheduled work). Every log table touch is guarded by a table-existence check (the table may not exist on the clone).
6. **Preservation is the load-bearing guarantee:** the sanitiser touches ONLY transient option rows + the two Action Scheduler tables. It NEVER issues a write against users, usermeta, posts, postmeta, terms, comments, or WooCommerce/Memberships tables. (Members, subscriptions, content, media survive untouched — AC #1.)
7. The CLI command reports an Afrikaans summary (rows removed; "oorgeslaan — reeds gedoen" when skipped).
8. `composer test:unit` green (new `DbSanitiserTest`, non-vacuous); repo-wide `composer cs` = 0 errors; `php -l` clean.

## Tasks / Subtasks

- [x] Task 1: Create the `Ink\Migration` module scaffold (AC: #3)
  - [x] Added `wp-content/plugins/ink-core/src/Migration/Module.php` (`final class Module implements ModuleContract`) — thin bootstrap that `register()`s the migration collaborators (the InkPols/Library house style). For 16.1 it registers `( new DbSanitiser() )->register();`.
  - [x] Wired `Kernel\Plugin::instance()->addModule( 'migration', new Migration\Module() );` into the `plugins_loaded` bootstrap in `ink-core.php` (`namespace Ink;` so `Migration\Module` resolves).
  - [x] Declared the `Migration` deptrac layer (allowed dep: `Kernel`) so the new namespace is covered, not uncovered.
- [x] Task 2: Implement `DbSanitiser` (AC: #1–#7)
  - [x] Added `wp-content/plugins/ink-core/src/Migration/DbSanitiser.php` following the `Ink\Challenges\Migration` shape: `OPTION_DONE`, `CLI_COMMAND`, `register()` (WP-CLI-only), `run(bool $force): array`, `hasRun()`, `markDone()`.
  - [x] Pure helpers: `transientLikePrefixes(): array` (the 4 transient namespaces) and `purgeableActionStatuses(): array` (`complete`/`failed`/`canceled` — NOT pending/in-progress).
  - [x] Overridable I/O seams (so the orchestration is unit-testable without a DB): `deleteTransients(): int`, `deleteFinishedActions(): int`, `deleteOrphanLogs(): int`, `tableExists(string $table): bool`. The seams use `$wpdb->prepare()` + `$wpdb->esc_like()`, wrapped in `phpcs:disable/enable` DirectQuery blocks (the `FollowStore` house style).
  - [x] Afrikaans `\WP_CLI::success()` summary.
- [x] Task 3: Tests (AC: #6, #8)
  - [x] Added `tests/Unit/Migration/DbSanitiserTest.php` (7 tests, 38 assertions): idempotency (skip when done, run on `--force`); pure-helper contents; **non-vacuous preservation guard** — asserts every transient prefix is confined to a transient namespace (never a bare `%`/empty) and the action-status set excludes `pending`/`in-progress`; aggregation/summary shaping; the `esc_like`-escaped LIKE delete + the table-absent no-op via the Mockery `$wpdb` pattern.
  - [x] `composer test:unit` (949 passed / 1 skipped), repo-wide `composer cs` (0 errors, 0 warnings on the new files), `php -l`, `composer stan` (OK), `composer deptrac` (no new violations), `composer copy:scan` (no new debt) all green.

## Dev Notes

### What already exists (read before editing)
- `wp-content/plugins/ink-core/src/Challenges/Migration.php` and `src/InkPols/Migration.php` — the **once-off, idempotent, WP-CLI-only migration pattern** to mirror exactly (completion-option guard, `--force`, pure static helpers, overridable protected I/O seams, Afrikaans `\WP_CLI::success`). 16.1 is the same shape with `$wpdb` deletes instead of post/term inserts.
- `wp-content/plugins/ink-core/src/Social/FollowStore.php` — the **`$wpdb` house style**: `global $wpdb;`, `$wpdb->prepare()`, `$wpdb->esc_like()`, `$wpdb->query()`/`$wpdb->get_var()`. Mirror for the DELETEs.
- `wp-content/plugins/ink-core/ink-core.php` (lines ~78–97) — the `plugins_loaded` module-registration block. Add the `migration` module here.
- `wp-content/plugins/ink-core/src/InkPols/Module.php` — the thin-`Module` precedent.
- `tests/Unit/Social/FollowStoreTest.php` — the Mockery `$wpdb` test pattern (`$GLOBALS['wpdb']` set in `beforeEach`, `prepare`→`'PREPARED'`, `query`/`get_var` expectations).
- `tests/bootstrap.php` — defines `ABSPATH`, `ARRAY_A/N`, `OBJECT`; WordPress is NOT loaded (Brain Monkey mocks it).

### Architecture compliance (project-context.md)
- **Three-layer separation:** migration logic lives in `ink-core`, never the theme.
- **Never raw SQL with interpolation** — `$wpdb->prepare()` always; `esc_like()` for the LIKE prefixes (AC #4).
- **Migration is scripted, ordered, WP-CLI-triggered**, never on a web request; nothing diagnostic/migration runs on production (production-hygiene rule). The CLI guard is the technical enforcement.
- **Idempotent + `--force`** is the established migration contract (don't double-apply).
- **i18n:** the CLI summary is Afrikaans source text (admin-language split — `ink-core` ships Afrikaans, no English `.mo`).
- **Conflation-clean:** the sanitiser reads only `$wpdb` + WP options; zero `Ink\Tiers`/`Ink\Entitlement` coupling (no new deptrac edge).

### Why these targets (and only these)
- **Transients** are cache rows in `wp_options` (and `_site_transient_*`); stripping them yields a clean baseline and they regenerate transparently. This is exactly what core's own `delete_expired_transients()` does, broadened to all (not just expired) transients.
- **Action Scheduler** finished actions + logs accumulate without bound on a long-lived brownfield site; purging `complete`/`failed`/`canceled` (NOT pending/in-progress) clears the log noise without dropping live scheduled work. Action Scheduler ships with WooCommerce, so the tables are present on this stack — but the table-existence guard keeps the command safe on a clone where they are not.
- **Everything else is preserved by omission:** the sanitiser never names a user/post/term/comment/Woo table in a write. AC #6's guard test asserts the target set, the strongest cheap proof of the preservation guarantee.

### Project Structure Notes
- NEW module dir: `wp-content/plugins/ink-core/src/Migration/` (first Epic-16 file). Namespace `Ink\Migration`.
- NEW: `src/Migration/Module.php`, `src/Migration/DbSanitiser.php`, `tests/Unit/Migration/DbSanitiserTest.php`.
- MODIFIED: `ink-core.php` (one `addModule` line).
- This module is the home for the rest of Epic 16's CLI commands (user import, tier CSV, post reclassification, redirects, …), so the scaffold is built once here.

### Testing standards
- Unit tests mock WordPress (Brain Monkey / Mockery `$wpdb`); no real DB.
- **Non-vacuous guardrail:** the preservation test must assert the *positive* target set (transient prefixes + finished-action statuses) so it can actually fail if a future edit broadens the blast radius — not merely assert that some unrelated table is absent.
- Run `composer test:unit` (must pass), repo-wide `composer cs` (0 errors — the 🔴 Epic-15 gate), `php -l` on new files.

### References
- [Source: _bmad-output/planning-artifacts/epics.md#Story 16.1: DB clone & sanitise] (FL 16.1)
- [Source: docs/migration-plan.md — "Clone and sanitise the database" (order step 1); Settings and options; Should not be migrated (transients/cache, activity notifications)]
- [Source: _bmad-output/project-context.md — Migration is scripted/ordered; never raw SQL; production hygiene; admin-language split]
- [Source: wp-content/plugins/ink-core/src/Challenges/Migration.php — once-off WP-CLI migration pattern]
- [Source: wp-content/plugins/ink-core/src/Social/FollowStore.php — $wpdb prepare/esc_like house style]

## Dev Agent Record

### Agent Model Used

claude-opus-4-8 (BMAD dev-story workflow)

### Debug Log References

- `composer test:unit` → 949 passed, 1 skipped (3628 assertions). New `DbSanitiserTest`: 7 passed.
- `composer cs` (repo-wide) → 0 errors. New `Migration/` files: 0 errors, 0 warnings (DirectQuery sniffs suppressed in `phpcs:disable/enable` blocks per the `FollowStore` house style).
- `composer stan` → No errors (157 files).
- `composer deptrac` → 3 violations, all the pre-existing `Kernel\Activation → Content\PostTypes` ones (untouched); Migration layer added (Migration → Kernel allowed), no new edge.
- `composer copy:scan` → no new placeholder debt (baseline 8, found 8).
- `php -l` clean on `DbSanitiser.php` + `Module.php`.

### Completion Notes List

- Story 16.1 establishes the **`Ink\Migration` module** — the home for Epic 16's once-off, idempotent, WP-CLI-only migration toolkit. The module bootstrap is wired into `ink-core.php` and a `Migration` deptrac layer governs its dependencies.
- `DbSanitiser` strips the cloned DB to a clean baseline: all transient/site-transient option rows (`esc_like`-escaped LIKE so literal underscores are not wildcards) + finished Action Scheduler actions (`complete`/`failed`/`canceled`) + their orphaned log rows. **Pending/in-progress actions are preserved** (live scheduled work is never dropped), and every log-table touch is guarded by a `tableExists()` probe.
- **Preservation (AC #1/#6) is guaranteed by omission**: the sanitiser issues writes against ONLY the `wp_options` transient rows and the two `actionscheduler_*` tables — never users, posts, terms, comments, or WooCommerce/Memberships. The non-vacuous guard test asserts the positive target set (transient prefixes + finished-action statuses) so it fails if a future edit broadens the blast radius.
- Once-off + idempotent: `ink_migration_sanitise_done` makes a re-run a no-op; `--force` re-runs. WP-CLI-only trigger (`wp ink migrate-sanitise`) — never on a web request (production hygiene). Afrikaans CLI summary.
- Conflation-clean: touches only `$wpdb` + WP options; zero `Ink\Tiers`/`Ink\Entitlement` coupling.

### File List

- `wp-content/plugins/ink-core/src/Migration/Module.php` (NEW — module bootstrap)
- `wp-content/plugins/ink-core/src/Migration/DbSanitiser.php` (NEW — the 16.1 sanitiser)
- `wp-content/plugins/ink-core/ink-core.php` (MODIFIED — registered the `migration` module)
- `deptrac.yaml` (MODIFIED — added the `Migration` layer, allowed dep: `Kernel`)
- `tests/Unit/Migration/DbSanitiserTest.php` (NEW)
- `_bmad-output/implementation-artifacts/16-1-db-clone-sanitise.md` (story record)
- `_bmad-output/implementation-artifacts/sprint-status.yaml` (status tracking)

## Change Log

- 2026-06-28 — Story 16.1 implemented: `Ink\Migration` module scaffold + `DbSanitiser` (transients + finished Action Scheduler logs stripped; members/subscriptions/content/media preserved by omission) + non-vacuous preservation guard tests. Status → review.

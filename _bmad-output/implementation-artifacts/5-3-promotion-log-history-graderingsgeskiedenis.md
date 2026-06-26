---
baseline_commit: 6b7222de1980e87104d61ab5a08d5071decadbec
---

# Story 5.3: Promotion log / history (graderingsgeskiedenis)

Status: done

<!-- Note: Validation is optional. Run validate-create-story for quality check before dev-story. -->

> **Build-order note:** developed BEFORE Story 5.2 (admin UI). 5.2's AC ("a change-log entry is written") and 5.8 (the auto engine) both depend on a durable log + the single `Tiers::promote()` write path that appends to it. This story builds the **log substrate** (the `ink_tier_history` custom table + the append + the history read). Story 5.2 then adds `Tiers\Api::promote()` (meta write + log append) and the admin UI on top; 5.8 reuses the same append for `actor = system`.

## Story

As a redakteur,
I want an auditable Gradering history,
so that every change (manual or auto) is traceable. (FR-12)

## Acceptance Criteria

1. **A `graderingsgeskiedenis` append-only audit record exists as a custom table, and every committed Gradering change can be recorded with actor, date, reason, from→to, and an optional challenge link.** Given any Gradering change, when it commits, then a log record stores: the writer (`user_id`), `from`→`to` grade (Kernel `Tier` backing strings), the **actor** (a staff user id, or **0 = system** for an automatic promotion), the **date**, a **reason** string, and an **optional challenge link** (`challenge_id`, 0 = none). The store is a custom table `{$wpdb->prefix}ink_tier_history` (architecture line 879; append-only audit, FR-12), created through the Kernel `Schema` registry via `dbDelta()`. _[Source: epics.md#Story-5.3 AC; architecture.md line 372 ("Tier history (graderingsgeskiedenis) — custom table — append-only audit log (FR-12)"), line 879 (`{$wpdb->prefix}ink_{plural}` → `ink_tier_history`), lines 269-273 (`Tiers::promote()` writes the log: actor, date, reason, from→to, optional challenge link); src/Kernel/Schema.php (the `register()`/`install()` dbDelta seam); project-context.md ("Custom tables … declared via `dbDelta()` with the `$wpdb->prefix`")]_

2. **The log exposes a typed append and a typed per-writer history read, both in the `Ink\Tiers` module and conflation-clean.** Given the log substrate, when code records or reads history, then `Ink\Tiers\PromotionLog::record( int $user_id, Tier $from, Tier $to, int $actor_id = 0, string $reason = '', int $challenge_id = 0 )` appends one row (the append used by `Tiers::promote()` in 5.2/5.8 — this story provides the append, not the promote orchestration), and `PromotionLog::forUser( int $user_id ): list<PromotionLogEntry>` returns the writer's history newest-first as typed, immutable `PromotionLogEntry` value objects (each coercing its stored grade strings through the Kernel `Tier` enum, exposing `isSystem()` / `isChallengeLinked()`). All of it lives under `Ink\Tiers`, references only the Kernel `Tier` + WordPress `$wpdb`, and carries **zero** `Ink\Entitlement` reference (THE conflation rule; deptrac `Tiers: [Kernel]` holds). _[Source: architecture.md AD-1 (module owns its tables; the facade is the cross-module surface), lines 269-273; src/Kernel/Tier.php (Story 5.1 — `Tier::default()`, the backing strings); deptrac.yaml (`Tiers: [Kernel]`); project-context.md (strict types, prefix, single-source, `$wpdb->prepare()` always — never interpolate SQL)]_

3. **All SQL is safe and the table is registered for activation at the correct lifecycle point.** Given the WP house rules, when the table is written/read, then writes go through `$wpdb->insert()` with an explicit `$format` array (no interpolation), reads through `$wpdb->prepare()` (never string-built), and the schema provider is registered with `Ink\Kernel\Schema::register()` at **plugin include time** (in the `ink-core.php` composition root) — NOT inside a `plugins_loaded`/`init` closure — because the activation hook fires after `plugins_loaded` has already passed for the plugin being activated, so an init-registered provider would be invisible to `Schema::install()` at activation. The schema DDL is `dbDelta()`-compatible (two spaces after `PRIMARY KEY`, `KEY` indexes, `$wpdb->get_charset_collate()`). _[Source: project-context.md ("Never write raw SQL with interpolation — `$wpdb->prepare()` always"; "Custom tables … declared via `dbDelta()`"); src/Kernel/{Schema,Activation}.php (Schema::install runs at activation; the composition root is ink-core.php, OUTSIDE deptrac's `src/` scope); WP activation-hook ordering]_

4. **WP-house-rules + conflation-clean + authored AND PASSING Pest tests.** Given the project rules, when this story is built, then: every new `.php` is `<?php` + `declare(strict_types=1)` + `namespace Ink\Tiers;` + `defined('ABSPATH')||exit;`, classes PascalCase / methods camelCase, table/option/key literals are `ink_`-prefixed single-source constants, no raw `$_POST`/`$_GET`, no string-interpolated SQL. Pest unit tests are authored at `tests/Unit/Tiers/` and **run with `composer test:unit`; the full suite passes before the story is marked done** (baseline 283 passed / 1 skipped — zero regressions). This story establishes the **first `$wpdb` unit-test pattern** (a Mockery `$wpdb` double on the global) — keep it reusable. `composer cs` / `stan` / `deptrac` are run and recorded; deptrac stays green with NO new `Tiers` edge. _[Source: project-context.md (strict types, prefix, single-source, no raw superglobals/SQL, **testing rule 2026-06-22** author *and run* Pest; THE conflation rule); architecture.md AD-8; deptrac.yaml; tests/bootstrap.php (Brain Monkey + the ABSPATH sentinel)]_

## Tasks / Subtasks

> **Current state (read before starting):**
> - **The `Ink\Kernel\Schema` registry exists and is empty** (`register($id, callable): void`, `install(): void` runs `dbDelta()` at activation). `Schema::install()` is called from `Ink\Kernel\Activation::activate()`. NO module has registered a provider yet — this story is the FIRST custom table in ink-core.
> - **`ink-core.php` is the composition root** and is OUTSIDE deptrac's scanned `src/` path. The Tiers module is NOT currently in the `addModule()` bootstrap list (it is a reserved skeleton; `Tiers\Module::register()` is a no-op). This story does NOT need an init hook — the log is written/read through static facade calls — so it does NOT add Tiers to `addModule`. It DOES add ONE include-time `Schema::register()` call in `ink-core.php` (see AC-3 for why include-time).
> - **`Ink\Tiers\Api::forUser()` (Story 5.1) is the typed grade read**, and `Ink\Kernel\Tier` carries `default()` + the backing strings. Reuse them; do not re-derive.
> - **`Tiers::promote()` does NOT exist yet** — it is Story 5.2 (manual write path) / 5.8 (auto). This story builds the LOG that promote() will append to (`PromotionLog::record()`), NOT promote() itself, and NOT the `ink_writer_tier` meta write, NOT win-count (5.7).
> - **No `$wpdb` test exists yet.** Establish a Mockery `$wpdb` double assigned to `global $wpdb` in the test (Brain Monkey is already wired; `tests/bootstrap.php` defines the ABSPATH sentinel). Keep the helper reusable for 5.2/5.7/5.8.
>
> **Scope is the LOG SUBSTRATE ONLY.** Do NOT build: `Tiers::promote()` / the meta write (5.2), the admin UI (5.2), win-count (5.7), the auto engine (5.8), profile/history DISPLAY templates (5.4), discovery (5.5), or the congratulation email (5.10). Build the table + the typed append + the typed history read + the activation registration.

- [x] **Task 1 — `PromotionLogEntry` typed record (AC: 1, 2)**
  - [x] Added `Ink\Tiers\PromotionLogEntry` — `final` readonly value object (`id`, `userId`, `from`/`to` Tier, `actorId`, `reason`, `challengeId`, `createdAt`) with `isSystem()`, `isChallengeLinked()`, and `fromRow()` that coerces stored grade strings through `Tier::tryFrom() ?? Tier::default()`.
- [x] **Task 2 — `PromotionLog` table + append + read (AC: 1, 2, 3)**
  - [x] Added `Ink\Tiers\PromotionLog`: `TABLE = 'ink_tier_history'`, `tableName()`, `schemaSql()` (dbDelta DDL, all columns + PK + two KEYs + charset collate), `record()` (typed append via `$wpdb->insert` with explicit `$format`, GMT `current_time('mysql', true)`, grades as `->value`), `forUser()` (prepared per-writer read, newest-first, mapped to entries, `[]` when empty).
  - [x] SQL safety: `record()` uses `$wpdb->insert()` (no interpolation); `forUser()` binds `user_id` via `prepare()` (table name is a constant). The two unavoidable DirectDatabaseQuery/Interpolated sniffs are narrowly `phpcs:disable`d with justifying comments.
- [x] **Task 3 — Register the schema at include time (AC: 3)**
  - [x] `ink-core.php`: `Kernel\Schema::register( Tiers\PromotionLog::TABLE, array( Tiers\PromotionLog::class, 'schemaSql' ) );` added at top level (include time) with a comment explaining the activation-timing reason. Tiers NOT added to `addModule` (no init hook needed).
- [x] **Task 4 — Author AND run the Pest tests; record the gates (AC: 4)**
  - [x] `tests/Unit/Tiers/PromotionLogEntryTest.php` (4 tests) + `tests/Unit/Tiers/PromotionLogTest.php` (7 tests, establishes the reusable `Mockery` `$wpdb`-on-global pattern).
  - [x] `composer test:unit` → **294 passed / 1 skipped** (1329 assertions); baseline 283/1 → **+11 new, zero regressions**. `composer cs` (3 files) clean. `composer stan` clean (sandbox-off). `composer deptrac` → 3 pre-existing `Activation → PostTypes` violations only, **no new `Tiers` edge**, `Entitlement ⟂ Tiers` holds.

## Dev Notes

- **Why include-time schema registration (AC-3):** on the request that activates the plugin, `plugins_loaded` fires *before* `activate_plugin()` includes the plugin file, so any provider registered inside a `plugins_loaded`/`init` closure is NOT yet registered when the activation hook runs `Schema::install()`. Registering at the top level of `ink-core.php` (which IS executed by the activation include) guarantees the provider is present. `ink-core.php` lives outside deptrac's `src/` scan, so referencing `Tiers\PromotionLog` from the composition root creates no tracked edge.
- **Append-only:** no update/delete API — the table is an audit log. `record()` only inserts; `forUser()` only reads. The "from→to" pair makes each row self-describing without needing prior-row joins.
- **Actor = 0 means system:** an automatic promotion (5.8) records `actor_id = 0`; a manual staff change (5.2) records the staff user id. `PromotionLogEntry::isSystem()` encodes the distinction once.
- **Conflation rule:** `PromotionLog` reads/writes only its own table + the Kernel `Tier`; zero `Ink\Entitlement`. This is competition-history, never entitlement.
- **First `$wpdb` test pattern:** assign a `Mockery::mock()` to `global $wpdb` in `beforeEach` (with `prefix`, `insert`, `prepare`, `get_results`, `get_charset_collate` as needed) and unset in `afterEach`. Keep it copy-able for 5.2/5.7/5.8.

### Project Structure Notes

- New: `src/Tiers/PromotionLog.php`, `src/Tiers/PromotionLogEntry.php`; tests `tests/Unit/Tiers/PromotionLogTest.php`, `tests/Unit/Tiers/PromotionLogEntryTest.php`. UPDATE: `ink-core.php` (one include-time `Schema::register()` line).
- First ink-core custom table → first `dbDelta` provider, first `$wpdb` test. `Tiers\Module::register()` stays a no-op (still no init hook).

### References

- [Source: epics.md#Story-5.3]
- [Source: architecture.md lines 269-273, 372, 879; AD-1, AD-8]
- [Source: src/Kernel/Schema.php, src/Kernel/Activation.php, src/Kernel/Tier.php (5.1), ink-core.php]
- [Source: deptrac.yaml (`Tiers: [Kernel]`); project-context.md (dbDelta/$wpdb->prepare, prefix, strict types, testing rule, conflation rule)]

## Dev Agent Record

### Agent Model Used

claude-opus-4-8 (BMAD dev-story loop)

### Debug Log References

- `composer test:unit` → 294 passed / 1 skipped (1329 assertions).
- `composer cs` (PromotionLog.php, PromotionLogEntry.php, ink-core.php) → clean (after fixing docblock + narrowly scoped DB-sniff disables).
- `composer stan` → No errors (sandbox-off; phpstan TCP-server EPERM caveat).
- `composer deptrac` → 3 pre-existing `Ink\Kernel\Activation → Ink\Content\PostTypes` violations; no new edge, no `Tiers` violation.

### Completion Notes List

- **First ink-core custom table.** `{$wpdb->prefix}ink_tier_history` (graderingsgeskiedenis), created at activation through the existing Kernel `Schema` registry. The schema provider is registered at **plugin include time** in `ink-core.php` because the activation hook fires after `plugins_loaded` has passed for the plugin being activated — an init-registered provider would be invisible to `Schema::install()`.
- **Append + read only** — `PromotionLog::record()` (typed, safe insert) and `PromotionLog::forUser()` (prepared, newest-first, typed entries). No update/delete: it is an audit log. `PromotionLogEntry` is an immutable typed view, coercing stored grade strings through the Kernel `Tier` enum.
- **Build-order:** done before 5.2. `Tiers::promote()` (the meta write + this log append) and the admin UI are Story 5.2; the auto engine that records `actor = system` (0) is 5.8. This story is the substrate they call.
- **First `$wpdb` unit-test pattern** established (Mockery mock on `global $wpdb`, `prefix`/`insert`/`prepare`/`get_results`/`get_charset_collate`) — reusable for 5.2/5.7/5.8.
- **Conflation rule intact:** `PromotionLog`/`PromotionLogEntry` reference only the Kernel `Tier` + `$wpdb`; zero `Ink\Entitlement`. Deptrac confirms `Tiers: [Kernel]` holds.
- **No scope creep:** no promote orchestration, no `ink_writer_tier` write, no win-count, no UI, no display templates.

### File List

- `wp-content/plugins/ink-core/src/Tiers/PromotionLog.php` (NEW)
- `wp-content/plugins/ink-core/src/Tiers/PromotionLogEntry.php` (NEW)
- `wp-content/plugins/ink-core/ink-core.php` (UPDATE — include-time `Schema::register()` for the audit table)
- `tests/Unit/Tiers/PromotionLogTest.php` (NEW)
- `tests/Unit/Tiers/PromotionLogEntryTest.php` (NEW)

### Change Log

- 2026-06-26 — Story 5.3 implemented (create-story → dev-story, built before 5.2 as the log substrate). `ink_tier_history` custom table + typed `PromotionLog::record()`/`forUser()` + `PromotionLogEntry` value object; schema registered at include time for activation correctness. 294 passed / 1 skipped (+11); cs/stan clean; deptrac no new edge. Status → review.

## Review Findings (code review 2026-06-26, Group A: 5.1+5.3+5.7)

_3-layer adversarial review (Blind Hunter + Edge Case Hunter + Acceptance Auditor). All layers passed; AC coverage confirmed; conflation rule holds. Items below are the deduped, triaged residue._

- [x] [Review][Decision→Dismissed] Audit-log fidelity — lossy grade coercion to Brons — `PromotionLogEntry::fromRow()` and `Api::forUser()` coerce any unrecognised/empty/**mis-cased** stored grade to `Tier::Brons`. **RESOLVED 2026-06-26 — accept as-is (no code change).** Spec 5.1 AC-2 explicitly sanctions junk→Brons coercion on the read path, and the sole writer (`promote()`) always persists canonical lowercase `->value`, so the lossy/demotion path is only reachable by external/manual DB writes or a future bad import — a data-quality concern (Story 16.3 import), not a model defect. [`PromotionLogEntry.php:861-862`, `Api.php:54,88-101`]
- [x] [Review][Patch] Audit durability — table created only at activation + `promote()` ignores `record()` failure — **APPLIED 2026-06-26**: added `Activation::maybeUpgrade()` (admin_init, version-gated idempotent `Schema::install()` + `ink_core_db_version` bump) and `promote()` now fires `ink/tier_promotion_log_failed` + a `WP_DEBUG` `wp_trigger_error` when `record()` returns false. Tests: new `ActivationTest` (3) + `PromoteTest` audit-failure-seam case. — `dbDelta()` runs only in `Activation::activate()`; there is no version-gated upgrade routine (the `ink_core_db_version` option is written but never read back to trigger a migration). On an already-active site upgraded to this version — or on any failed insert — `record()` returns `false`, but `Api::promote()` discards that return: the grade meta + `promoted_at` are written and `ink/tier_promoted` fires while the audit row is silently dropped, defeating the FR-12 append-only guarantee. **RESOLVED 2026-06-26 — full fix:** add a version-gated `Schema::install()` upgrade routine (compare `INK_CORE_VERSION` vs stored `ink_core_db_version` on `admin_init`) AND make `promote()` react to `record() === false` (observable, not silent). [`ink-core.php:49`, `Kernel/Activation.php`, `Api.php` promote → `PromotionLog::record()`]
- [x] [Review][Patch] Tighten `forUser()` ORDER BY test to pin the `, id DESC` tiebreaker [`tests/Unit/Tiers/PromotionLogTest.php`] — **APPLIED 2026-06-26**: `Mockery::pattern` now matches `ORDER BY created_at DESC, id DESC`.
- [x] [Review][Patch] Assert the GMT flag in the `record()` timestamp test [`tests/Unit/Tiers/PromotionLogTest.php`] — **APPLIED 2026-06-26**: `current_time` now mocked via `Functions\expect(...)->with('mysql', true)`, asserting the GMT argument.
- [x] [Review][Defer] `PromotionLogEntry::createdAt` exposes a raw GMT string with no timezone marker [`PromotionLogEntry.php:866`] — deferred, display-boundary concern owned by the downstream history-display consumer (5.4+); storage choice (GMT) is correct.
- [x] [Review][Defer] `reason` column is unbounded `text` and unsanitised; an over-length value fails the insert under MySQL strict mode [`PromotionLog.php:727,733`] — deferred, the `reason` source is the staff admin UI (Story 5.2), where input validation/length belongs.

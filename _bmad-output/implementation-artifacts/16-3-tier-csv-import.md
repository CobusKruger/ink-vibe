---
baseline_commit: c7e6a40
---

# Story 16.3: Tier CSV import

Status: done

<!-- Note: Validation is optional. Run validate-create-story for quality check before dev-story. -->

## Story

As an ink-core developer,
I want tiers imported from CSV,
so that writers keep their Gradering. (FL 16.3)

## Acceptance Criteria

1. **Given** the tier CSV (email join key) **When** imported **Then** `ink_writer_tier` is set; missing/ambiguous → default `brons` **+ flag** (never a guessed Silwer/Goud/Meester).
2. The tier value is parsed against the canonical `Kernel\Tier` enum (`brons`/`silwer`/`goud`/`meester`), case-/whitespace-insensitive. **Any unrecognised or empty value → `Tier::default()` (`brons`) + a review flag** — the import NEVER guesses a higher grade. `ink_writer_tier` is written via `Kernel\Tier::META_KEY` (the single source).
3. The join key is **email**. A CSV row whose email matches no WP account is **not** silently dropped — it is counted and surfaced (manual-follow-up, per the migration plan), never used to create an account.
4. Once-off + idempotent (`ink_migration_tiers_done`; `--force` re-runs) and **WP-CLI only** (`wp ink migrate-tiers <path-to-csv>`) — never on a web request. The CSV path is a required CLI argument.
5. Conflation-clean: writes only the `ink_writer_tier` (+ a review-flag) user meta via the `Kernel\Tier` single source; it reads no membership and introduces **no `Entitlement` coupling** (tier ≠ subscription). Deptrac edge stays `Migration → Kernel`.
6. Afrikaans `\WP_CLI::success()` summary (set / defaulted-and-flagged / no-account); `\WP_CLI::error()` when the path argument is missing.
7. `composer test:unit` green (new `TierImportTest`, non-vacuous "never guesses a higher grade" guard); repo-wide `composer cs` = 0 errors; `php -l`, `composer stan`, `composer deptrac` (no new edge), `composer copy:scan` clean.

## Tasks / Subtasks

- [x] Task 1: Implement `TierImport` (AC: #1–#6)
  - [x] Added `wp-content/plugins/ink-core/src/Migration/TierImport.php` following the `DbSanitiser`/`UserReclassifier` shape: `OPTION_DONE`, `CLI_COMMAND`, `FLAG_META = 'ink_tier_import_review_flag'`, `register()` (WP-CLI-only, required path arg), `run(string $csvPath, bool $force): array`, `hasRun()`, `markDone()`.
  - [x] Pure helpers: `parseTier(string $raw): ?Tier` (lowercase+trim → `Tier::tryFrom`; null when unrecognised/empty) and `columnIndexes(array $header): array{email:?int, tier:?int}` (header-name detection: `mail`/`e-pos`/`epos`, `tier`/`grad`).
  - [x] Overridable I/O seams: `readRows(string $path): array` (default `fgetcsv` using `columnIndexes`, filesystem sniffs suppressed), `userIdForEmail(string $email): int` (default `get_user_by('email')`), `setTier(int $id, Tier $tier): void` (routes through `Tiers\Api::importBaselineGrade()` — the sanctioned tier-write), `flagForReview(int $id, string $reason): void` (default `update_user_meta` with `FLAG_META`).
  - [x] Orchestration: for each row → resolve user by email (0 → ++`no_account`, continue); `parseTier` → set the grade; null → set `brons` + `flagForReview` + ++`defaulted`.
  - [x] Afrikaans CLI summary + missing-path error.
- [x] Task 2: Register in the module + add the sanctioned write path (AC: #4, #5)
  - [x] Added `( new TierImport() )->register();` to `Migration\Module::register()`.
  - [x] Added `Tiers\Api::importBaselineGrade(int $user_id, Tier $tier): void` — a baseline SET (no log/promoted_at/win-reset/event) so tier writes stay inside `Ink\Tiers` (THE conflation guardrail) instead of the Migration layer poking `ink_writer_tier`. Declared the `Migration → Tiers` deptrac edge.
- [x] Task 3: Tests (AC: #1, #2, #7)
  - [x] Added `tests/Unit/Migration/TierImportTest.php` (5 tests): `parseTier` accepts the four canonical grades (any case) and returns **null** for `''`/`bronze`/`gold`/garbage; **non-vacuous guard** — a row with a missing/garbage tier results in `brons` + a flag, and NEVER `silwer`/`goud`/`meester`; `columnIndexes` detection; idempotency; `run()` counts set/defaulted/no_account over overridden seams.
  - [x] Added an `importBaselineGrade` test to `tests/Unit/Tiers/ApiTest.php` (writes only the grade meta — no promoted_at/win-count/log/event); the `ConflationGuardrailTest` still passes (write stays in `Tiers/Api.php`).
  - [x] All gates green.

## Dev Notes

### What already exists (read before editing)
- `wp-content/plugins/ink-core/src/Kernel/Tier.php` — the `Tier` enum (`brons`/`silwer`/`goud`/`meester`), `META_KEY = 'ink_writer_tier'`, `Tier::default()` = `Brons`, `Tier::tryFrom()`. Source EVERYTHING tier-related here (Kernel, already an allowed `Migration` dep) — do NOT reach into `Ink\Tiers`.
- `wp-content/plugins/ink-core/src/Tiers/Api.php` — `forUser()`/`promote()` are the *runtime* read/write paths; this import deliberately writes the meta directly (a baseline set, NOT a logged promotion — no `PromotionLog` entry, no `promoted_at`, no win-count reset for a migration baseline).
- `wp-content/plugins/ink-core/src/Migration/{DbSanitiser,UserReclassifier}.php` + `Module.php` — the once-off CLI command + seam pattern; `Module::register()` is where 16.3 registers.
- `tests/Unit/InkPols/MigrationTest.php` — the anonymous-subclass-over-seams test idiom.

### Architecture compliance (project-context.md)
- **Default `brons` + flag on missing/ambiguous, never guess Silwer/Goud** (edge-case rule + migration-plan). This is the load-bearing safety property.
- **THE conflation rule:** writer tier ≠ subscription. The import touches `ink_writer_tier` only; zero `Entitlement` read/coupling.
- **Model fixed value sets as enums** — parse against `Kernel\Tier`, never inline the grade literals.
- **Migration is scripted/ordered, WP-CLI-triggered** (migration order step 4 — after users exist). Idempotent + `--force`.
- **i18n:** Afrikaans CLI strings (admin-language split).

### Project Structure Notes
- NEW: `src/Migration/TierImport.php`, `tests/Unit/Migration/TierImportTest.php`.
- MODIFIED: `src/Migration/Module.php` (one `register()` line).
- No new deptrac layer/edge (Kernel only).

### Testing standards
- Override the file/DB seams so no real CSV/users are needed; test `parseTier`/`columnIndexes` as pure functions directly.
- **Non-vacuous guard:** assert that a missing/garbage tier yields `brons` AND a flag AND never a higher grade — exercise the real default path so it fails if a future edit starts guessing.
- Run `composer test:unit`, `composer cs`, `php -l`, `composer stan`, `composer deptrac`, `composer copy:scan`.

### References
- [Source: _bmad-output/planning-artifacts/epics.md#Story 16.3: Tier CSV import] (FL 16.3)
- [Source: docs/migration-plan.md — Writer tiers; "default to `brons` … and flag for review"; "Writers in the spreadsheet who do not have a WordPress account yet: flag for manual follow-up"; migration order step 4]
- [Source: _bmad-output/project-context.md — missing/ambiguous tier → brons + flag; THE conflation rule; enums for value sets]
- [Source: wp-content/plugins/ink-core/src/Kernel/Tier.php — enum + META_KEY single source]

## Dev Agent Record

### Agent Model Used

claude-opus-4-8 (BMAD dev-story workflow)

### Debug Log References

- `composer test:unit` → 962 passed, 1 skipped (3675 assertions). New `TierImportTest`: 5 passed; new `ApiTest` case: 1 passed.
- `composer cs` → 0 errors, 0 warnings on the new/changed files (CSV filesystem sniffs suppressed in a `phpcs:disable/enable` block).
- `composer stan` → No errors (fixed a redundant `is_array()` on the post-`false`-guard `fgetcsv` result).
- `composer deptrac` → 3 pre-existing `Kernel\Activation → Content` violations only; the new `Migration → Tiers` edge is declared and allowed.
- `composer copy:scan` → no new debt (baseline 8).
- `php -l` clean on `TierImport.php`.

### Completion Notes List

- `TierImport` imports the legacy tier spreadsheet keyed on **email**: each row's tier is parsed against `Kernel\Tier` (case-/whitespace-insensitive); a recognised grade is set, and a **missing/ambiguous/unrecognised value defaults to `brons` + a review flag** — never a guessed Silwer/Goud/Meester (AC #1, the load-bearing safety property, proven by a non-vacuous guard test).
- A CSV row whose email matches no WP account is **counted (`no_account`) and surfaced**, never used to create an account (migration-plan manual-follow-up).
- **Conflation-clean tier write:** the grade is set through the new `Tiers\Api::importBaselineGrade()` (a baseline SET — no promotion log/promoted_at/win-reset/event), keeping tier writes inside `Ink\Tiers` so the existing `ConflationGuardrailTest` (only `Tiers` + `Accounts\Registration` may write tier meta) stays green. The review flag uses a non-`ink_writer_tier*` key for the same reason.
- Once-off + idempotent (`ink_migration_tiers_done`; `--force`); WP-CLI-only (`wp ink migrate-tiers <path>`) with a required path arg + Afrikaans summary.

### File List

- `wp-content/plugins/ink-core/src/Migration/TierImport.php` (NEW)
- `wp-content/plugins/ink-core/src/Migration/Module.php` (MODIFIED — registered `TierImport`)
- `wp-content/plugins/ink-core/src/Tiers/Api.php` (MODIFIED — added `importBaselineGrade()`, the sanctioned baseline tier-write)
- `deptrac.yaml` (MODIFIED — `Migration → Tiers` edge)
- `tests/Unit/Migration/TierImportTest.php` (NEW)
- `tests/Unit/Tiers/ApiTest.php` (MODIFIED — `importBaselineGrade` coverage)
- `_bmad-output/implementation-artifacts/16-3-tier-csv-import.md` (story record)
- `_bmad-output/implementation-artifacts/sprint-status.yaml` (status tracking)

## Change Log

- 2026-06-28 — Story 16.3 implemented: `TierImport` (email-keyed CSV → `ink_writer_tier`; missing/ambiguous → `brons` + flag, never a guessed higher grade; no-account rows surfaced) via the new sanctioned `Tiers\Api::importBaselineGrade()` write path. Status → review.

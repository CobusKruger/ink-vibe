---
baseline_commit: c7e6a40
---

# Story 16.11: Options carry-forward (selective)

Status: done

<!-- Note: Validation is optional. Run validate-create-story for quality check before dev-story. -->

## Story

As an ink-core developer,
I want only deliberate options carried forward,
so that we don't clone `wp_options` wholesale. (FL 16.11)

## Acceptance Criteria

1. **Given** legacy options **When** carried forward **Then** only deliberate values (site URL/name, `af` locale) transfer; SEO config is set up fresh in Rank Math.
2. The carry-forward is **allowlist-driven**: ONLY `siteurl`, `home`, `blogname`, `blogdescription`, `WPLANG` (locale) transfer. Any other legacy option — SEO (Yoast/Rank Math), retired-plugin config, theme-framework cruft — is **dropped** (not in the allowlist).
3. The locale is **forced to `af`** (the confirmed Afrikaans locale) regardless of the legacy value or its absence.
4. Once-off + idempotent (`ink_migration_options_done`; `--force`) and **WP-CLI only** (`wp ink migrate-options`). The legacy-option source is an overridable seam with a **safe-empty default** (site-specific) — an un-configured run still forces the `af` locale but carries nothing else.
5. Conflation-clean: writes WP core options only; no `Tiers`/`Entitlement`. Afrikaans `\WP_CLI` summary. `composer test:unit` green (new `OptionsCarryForwardTest`, non-vacuous "SEO/plugin key dropped, allowlisted kept, locale forced af"); `composer cs` = 0 errors; `php -l`, `composer stan`, `composer deptrac`, `composer copy:scan` clean.

## Tasks / Subtasks

- [x] Task 1: Implement `OptionsCarryForward` (AC: #1–#4)
  - [x] Added `wp-content/plugins/ink-core/src/Migration/OptionsCarryForward.php`: `OPTION_DONE`, `CLI_COMMAND = 'ink migrate-options'`, `LOCALE = 'af'`, `register()` (WP-CLI-only), `run(bool $force): array`, `hasRun()`, `markDone()`.
  - [x] Pure helpers: `allowedOptions(): array` (siteurl/home/blogname/blogdescription/WPLANG) and `filterCarryForward(array $legacy): array` (intersect with allowlist; force `WPLANG => 'af'`).
  - [x] Overridable I/O seams: `legacyOptions(): array` (safe-empty default), `applyOption(string $key, string $value): void` (`update_option`).
  - [x] Afrikaans CLI summary (options carried + locale).
- [x] Task 2: Register in the module (AC: #4)
  - [x] Added `( new OptionsCarryForward() )->register();` to `Migration\Module::register()` + docblock.
- [x] Task 3: Tests (AC: #2, #3, #5)
  - [x] Added `tests/Unit/Migration/OptionsCarryForwardTest.php` (5 tests): `allowedOptions` contents (and that it excludes SEO); `filterCarryForward` — **non-vacuous**: keeps `siteurl`/`blogname`, DROPS `wpseo`/`rank_math_*`/a retired-plugin key, and FORCES `WPLANG=af` even when legacy sets `en_US` or omits it; `run()` applies the filtered set over the seam (siteurl+blogname+forced WPLANG, wpseo dropped), idempotent skip.
  - [x] All gates green.

## Dev Notes

### What already exists (read before editing)
- `wp-content/plugins/ink-core/src/Migration/*` + `Module.php` — the once-off-CLI + seam pattern; `Challenges\Migration::legacyCategories()` — the safe-empty-default convention for a site-specific source.
- `tests/Unit/InkPols/MigrationTest.php` — the anonymous-subclass-over-seams idiom.

### Architecture compliance (project-context.md)
- **Don't clone `wp_options` wholesale — carry forward only deliberate values (site URL/name, `af` locale). SEO is configured fresh in Rank Math** (Yoast retired). The allowlist encodes exactly this.
- **Migration is scripted/ordered, WP-CLI-triggered**, idempotent + `--force`. Afrikaans CLI.
- **Conflation-clean** (WP core options only).

### Project Structure Notes
- NEW: `src/Migration/OptionsCarryForward.php`, `tests/Unit/Migration/OptionsCarryForwardTest.php`.
- MODIFIED: `src/Migration/Module.php`. No new deptrac edge (WP core only).

### Testing standards
- Override the option seams; test `allowedOptions()`/`filterCarryForward()` as pure functions.
- **Non-vacuous guard:** prove a SEO/plugin key is DROPPED and the allowlisted keys KEPT, and the locale is forced `af` — so a regression that over-carries options or loses the locale fails.
- Run `composer test:unit`, `composer cs`, `php -l`, `composer stan`, `composer deptrac`, `composer copy:scan`.

### References
- [Source: _bmad-output/planning-artifacts/epics.md#Story 16.11: Options carry-forward (selective)] (FL 16.11)
- [Source: docs/migration-plan.md — Settings and options ("do not clone the wp_options table wholesale … only specific option values worth carrying forward … site URL and name, confirmed Afrikaans locale … SEO is not carried from Yoast — configured fresh in Rank Math")]
- [Source: _bmad-output/project-context.md — don't clone wp_options wholesale; carry forward only deliberate values; SEO fresh in Rank Math]

## Dev Agent Record

### Agent Model Used

claude-opus-4-8 (BMAD dev-story workflow)

### Debug Log References

- `composer test:unit` → 1007 passed, 1 skipped (3826 assertions). New `OptionsCarryForwardTest`: 5 passed.
- `composer cs` → 0 errors, 0 warnings.
- `composer stan` → No errors.
- `composer deptrac` → 3 pre-existing only; no new edge (WP core options only).
- `composer copy:scan` → no new debt (baseline 8).
- `php -l` clean on `OptionsCarryForward.php`.

### Completion Notes List

- `OptionsCarryForward` is allowlist-driven: ONLY `siteurl`/`home`/`blogname`/`blogdescription`/`WPLANG` carry forward; SEO (Yoast/Rank Math), retired-plugin, and theme-framework options are dropped by omission (no wholesale `wp_options` clone — AC #1). SEO is configured fresh in Rank Math.
- The `af` locale is **forced** regardless of the legacy `WPLANG` (even when legacy carried `en_US` or none). The non-vacuous test proves a SEO/plugin key is dropped, the allowlisted keys kept, and the locale forced.
- Legacy-option source is a safe-empty seam (site-specific); an un-configured run still forces `af` but carries nothing else. Once-off + idempotent (`ink_migration_options_done`; `--force`); WP-CLI-only (`wp ink migrate-options`); Afrikaans summary. Conflation-clean.

### File List

- `wp-content/plugins/ink-core/src/Migration/OptionsCarryForward.php` (NEW)
- `wp-content/plugins/ink-core/src/Migration/Module.php` (MODIFIED — registered `OptionsCarryForward` + docblock)
- `tests/Unit/Migration/OptionsCarryForwardTest.php` (NEW)
- `_bmad-output/implementation-artifacts/16-11-options-carry-forward-selective.md` (story record)
- `_bmad-output/implementation-artifacts/sprint-status.yaml` (status tracking)

## Change Log

- 2026-06-28 — Story 16.11 implemented: `OptionsCarryForward` — allowlist-only options carry-forward (site URL/name + forced `af` locale; SEO/plugin/cruft dropped; no wholesale clone). Status → review.

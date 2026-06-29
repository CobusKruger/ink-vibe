---
baseline_commit: ef775c1
---

# Story 18.7: Update governance & i18n resilience

Status: review

<!-- Note: Validation is optional. Run validate-create-story for quality check before dev-story. -->

## Story

As a site owner,
I want governed updates with i18n resilience,
so that updates don't break overrides or leak English. (NFR-7, NFR-1)

## Acceptance Criteria

1. **Given** updates **When** applied **Then** major core/plugin updates are staging-gated (regression on custom templates + translation refresh); language packs used for core/well-covered plugins; committed `.mo` for premium plugins re-checked after their updates; new untranslated strings caught by the leak scan, fixed on staging, redeployed (**Loco not on production**).
2. **Code deliverable — a committed-translation presence audit.** The "committed `.mo` for premium plugins re-checked after their updates" requirement becomes a standing, automatable check: `Ink\I18n\TranslationAudit` verifies the expected premium-plugin translation files are present in the committed languages home (`wp-content/languages/`), so a plugin update that ships new strings (or wipes a translation) is caught.
3. **Single source for the expected set** — `REQUIRED_TRANSLATIONS` lists the premium/niche plugins whose Afrikaans is the committed `.mo`/`.json` (per project-context: WooCommerce Memberships, PayFast gateway, Real3D Flipbook — the ones with no complete community pack). Filterable (`ink_i18n_required_translations`) so the exact filenames are confirmed/extended on staging.
4. **Pure check** `missingTranslations(array $present, array $required): array` (set difference) so it unit-tests without WordPress; a `wp ink audit-translations` CLI reports present vs. missing (warns on missing, success when complete). Overridable `presentTranslations()` seam reads the languages dir.
5. **Runbook** (`docs/update-governance-runbook.md`): the update-governance process — major updates staging-gated (regression on `ink-foundation` custom templates + translation refresh), risk-based depth (smoke-only for minor/security), language packs for core/well-covered plugins vs committed `.mo` for premium, the post-update `.mo` recheck (`wp ink audit-translations` + the 17.4/18.8 leak scan), and the redeploy-not-hand-edit rule (Loco never on production). Cross-references 18.3 (security stack), 18.6 (production hygiene), 17.4 (leak gate).
6. **Non-vacuous tests:** `missingTranslations()` returns the expected files absent from the present set (and empty when all present); the required set names the premium plugins; the CLI's pure path reports complete vs. incomplete via the seam.
7. **Three-layer + conflation clean:** logic in `ink-core` `Ink\I18n`; references neither Tiers nor Entitlement. No deptrac change (I18n classes are deptrac-uncovered, matching the existing `Terms`/`Bindings`).
8. **Afrikaans:** CLI output Afrikaans. No front-end strings; no copy debt (the audit reads filenames; it does not author translations — AI Afrikaans remains forbidden).
9. **Gates green:** `composer test:unit`, `composer cs`, `php -l`, `composer stan`, `composer copy:scan` clean; `composer deptrac` no new violations; baseline unchanged.

## Tasks / Subtasks

- [x] Task 1: `src/I18n/TranslationAudit.php` (AC: #2, #3, #4)
  - [x] `REQUIRED_TRANSLATIONS` single source (WooCommerce Memberships / PayFast / Real3D); `requiredSet()` filterable via `ink_i18n_required_translations`.
  - [x] Pure `missingTranslations(array $present, array $required): array` (set diff).
  - [x] `wp ink audit-translations` CLI (WP_CLI I/O in the guarded closure); overridable `presentTranslations()` (globs `WP_LANG_DIR` for `*.mo`/`*.json`).
- [x] Task 2: `src/I18n/Module.php` + bootstrap (AC: #2, #7)
  - [x] `Ink\I18n\Module` implements `Kernel\Module`; `register()` wires `TranslationAudit`; added to `ink-core.php` (`addModule('i18n', …)`). No deptrac layer (I18n stays uncovered, like Terms/Bindings).
- [x] Task 3: Runbook (AC: #1, #5) — `docs/update-governance-runbook.md` (risk-based depth, major-update procedure, hard rules, cross-refs to 18.3/18.6/17.4).
- [x] Task 4: Tests (AC: #6) — `tests/Unit/I18n/TranslationAuditTest.php` (5).
- [x] Task 5: Gates (AC: #9) — `composer test:unit` ✓ (1096 passed, 1 skipped, +5); `composer cs` ✓ (0/0); `php -l` ✓; `composer stan` ✓ (No errors — dropped a redundant `array_values` on a list); `composer copy:scan` ✓ (8/8); `composer deptrac` — no new violations.

## Dev Notes

### What is code vs. ops
Update governance is a *process* (runbook). The one mechanisable slice is the
post-update **recheck** that the committed premium-plugin translations are still
present — turned into `wp ink audit-translations` so it can run after every update
(and in cron/CI). The new-untranslated-string detection is the existing leak scan
(17.4 static `copy:scan` + 18.8 live `wp i18n`), referenced not duplicated.

### Architecture compliance (project-context.md)
- **Committed-translation home** — `wp-content/languages/README.md` is the load model; this audit checks presence there.
- **No AI Afrikaans** — the audit only checks filenames; it never authors translations.
- **Single source + filterable** — one `REQUIRED_TRANSLATIONS`; `ink_i18n_required_translations` extends it (exact filenames confirmed on staging).
- **Conflation rule** — zero Tiers/Entitlement. No deptrac layer for I18n (existing pattern).
- **WP_CLI + phpstan** — `WP_CLI::*` inside the guarded closure (18.2 lesson).

### Source tree components to touch
- NEW `src/I18n/TranslationAudit.php`, `src/I18n/Module.php`
- NEW `tests/Unit/I18n/TranslationAuditTest.php`
- NEW `docs/update-governance-runbook.md`
- UPDATE `ink-core.php` (register `i18n` module)

### References
- [Source: _bmad-output/planning-artifacts/epics.md#Story 18.7] — staging-gated updates, language packs, committed .mo recheck, leak scan.
- [Source: wp-content/languages/README.md] — committed-translation home + workflow (§14.13).
- [Source: _bmad-output/project-context.md] — update governance; premium-plugin .mo is the only defence (WooCommerce Memberships, PayFast, Real3D); Loco never on production.

## Dev Agent Record

### Agent Model Used

claude-opus-4-8

### Debug Log References

- `composer test:unit -- --filter=TranslationAudit` → 5 passed.
- `composer test:unit` → 1096 passed, 1 skipped.
- `composer stan` (sandbox off) → No errors.

### Completion Notes List

- `Ink\I18n\TranslationAudit` (`wp ink audit-translations`) is the post-update
  recheck that the committed premium-plugin translations (WooCommerce Memberships /
  PayFast / Real3D) are present in `wp-content/languages/`. Presence-only; it never
  authors translations.
- Exact filenames are confirmed on staging (vendor plugins not in repo); the set is
  filterable. Pure `missingTranslations()` + overridable `presentTranslations()` →
  unit-tested without WordPress.
- New `Ink\I18n\Module` registered in the bootstrap; I18n stays deptrac-uncovered
  (existing pattern for Terms/Bindings) so no layer/rule churn. Conflation-clean.
- The rest of update governance (staging-gate, language packs, leak-scan re-run,
  Loco-never-on-prod) is the runbook, cross-referencing 18.3/18.6/17.4.

### File List

- NEW `wp-content/plugins/ink-core/src/I18n/TranslationAudit.php`
- NEW `wp-content/plugins/ink-core/src/I18n/Module.php`
- NEW `tests/Unit/I18n/TranslationAuditTest.php`
- NEW `docs/update-governance-runbook.md`
- UPDATE `wp-content/plugins/ink-core/ink-core.php` (register `i18n` module)
- UPDATE `_bmad-output/implementation-artifacts/sprint-status.yaml` (18.7 status)

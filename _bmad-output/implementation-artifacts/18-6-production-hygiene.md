---
baseline_commit: ef775c1
---

# Story 18.6: Production hygiene

Status: review

<!-- Note: Validation is optional. Run validate-create-story for quality check before dev-story. -->

## Story

As a site owner,
I want no diagnostic/migration tools on production,
so that production stays clean and secure. (NFR-7)

## Acceptance Criteria

1. **Given** production **When** audited **Then** no dev/diagnostic/migration plugins (Loco, Code Snippets, WP Migrate Lite, String Locator, etc.) are active.
2. **Code deliverable — a standing production-hygiene audit.** A `ProductionHygiene` collaborator (in the existing `Ink\Security` module — "protect production") detects forbidden staging/authoring-only plugins that are **active on a production environment**, so a stray activation is caught rather than silently lingering.
3. **Single source for the forbidden set** — a `FORBIDDEN_PLUGINS` list of plugin basenames (Loco Translate, Code Snippets, WP Migrate (Lite), String Locator, Simple CSS, Query Monitor, Debug Bar, …) matching project-context's staging/authoring-only list. Filterable (`ink_security_forbidden_plugins`) so a deployment can extend it.
4. **Environment-aware:** the audit/warning only fires when `wp_get_environment_type() === 'production'` (staging/local may legitimately run these tools). Pure `forbiddenActive(array $active, array $forbidden): array` intersects the active-plugin list with the forbidden set.
5. **Surfaces:** an **admin notice** (Afrikaans, ink-core admin convention) to administrators listing any forbidden active plugin on production, and a `wp ink audit-production` CLI that reports the same (exit/warn on findings, success when clean).
6. **Runbook** (`docs/production-hygiene-runbook.md`): the staging/authoring-only plugin policy (which tools, why), the pre-deploy + post-deploy checklist, and that the audit re-runs (admin notice + CLI/cron) — the standing NFR-7 gate.
7. **Non-vacuous tests:** `forbiddenActive()` returns exactly the forbidden plugins present (and empty when production is clean); the forbidden set contains the named tools; the audit no-ops off-production (environment seam) and reports on production.
8. **Three-layer + conflation clean:** logic in `ink-core` `Ink\Security`; references neither Tiers nor Entitlement. No new deptrac edge (`Security` stays `[Kernel]`).
9. **Afrikaans:** admin notice + CLI output Afrikaans (admin/CLI tooling). No front-end strings; no copy debt.
10. **Gates green:** `composer test:unit`, `composer cs`, `php -l`, `composer stan`, `composer copy:scan` clean; `composer deptrac` no new violations; baseline unchanged.

## Tasks / Subtasks

- [x] Task 1: `src/Security/ProductionHygiene.php` (AC: #2, #3, #4, #5)
  - [x] `FORBIDDEN_PLUGINS` single source (Loco/Code Snippets/WP Migrate/String Locator/Simple CSS/Query Monitor/Debug Bar); `forbiddenSet()` filterable via `ink_security_forbidden_plugins`.
  - [x] Pure `forbiddenActive(array $active, array $forbidden): array`.
  - [x] `register()`: on production, `admin_notices` warning; `wp ink audit-production` CLI (WP_CLI I/O in the guarded closure). Overridable `isProduction()`/`activePlugins()` seams.
  - [x] Wired into `Security\Module::register()`.
- [x] Task 2: Runbook (AC: #1, #6) — `docs/production-hygiene-runbook.md` (policy + pre/post-deploy checklist + standing gate).
- [x] Task 3: Tests (AC: #7) — `tests/Unit/Security/ProductionHygieneTest.php` (6).
- [x] Task 4: Gates (AC: #10) — `composer test:unit` ✓ (1091 passed, 1 skipped, +6); `composer cs` ✓ (0/0); `php -l` ✓; `composer stan` ✓ (No errors); `composer copy:scan` ✓ (8/8); `composer deptrac` — `Security` stays `[Kernel]`, no new violations.

## Dev Notes

### What is code vs. ops
The *policy* (which plugins are staging-only, deploy discipline) is the runbook. The *enforcement* is a standing audit in code so a mistake is visible (admin notice) and checkable (`wp ink audit-production`, cron-able). Environment-gated so it never nags on staging/local where these tools belong.

### Architecture compliance (project-context.md)
- **Production hygiene rule** — Loco/Code Snippets/Simple CSS/WP Migrate Lite/String Locator are staging/authoring-only; never active on production. This story is its automated check.
- **Single source + filterable** — one `FORBIDDEN_PLUGINS` list; `ink_security_forbidden_plugins` extends it.
- **Conflation rule** — zero Tiers/Entitlement; `Security` stays `[Kernel]`.
- **WP_CLI + phpstan** — `WP_CLI::*` inside the guarded closure (the 18.2 lesson).
- **Brain-Monkey isolation** — `isProduction()`/`activePlugins()` overridable seams; pure intersection takes primitives.

### Source tree components to touch
- NEW `src/Security/ProductionHygiene.php`
- NEW `tests/Unit/Security/ProductionHygieneTest.php`
- NEW `docs/production-hygiene-runbook.md`
- UPDATE `src/Security/Module.php` (register collaborator)

### References
- [Source: _bmad-output/planning-artifacts/epics.md#Story 18.6] — no dev/diagnostic/migration plugins active on production.
- [Source: _bmad-output/project-context.md] — production-hygiene rule (the named staging-only tools).

## Dev Agent Record

### Agent Model Used

claude-opus-4-8

### Debug Log References

- `composer test:unit -- --filter=ProductionHygiene` → 6 passed.
- `composer test:unit` → 1091 passed, 1 skipped.
- `composer stan` (sandbox off) → No errors.

### Completion Notes List

- `Ink\Security\ProductionHygiene` is the standing NFR-7 gate: on production it flags
  any staging/authoring-only plugin (Loco/Code Snippets/WP Migrate/String Locator/…)
  via admin notice + `wp ink audit-production`; inert off-production.
- Pure `forbiddenActive()` + overridable `isProduction()`/`activePlugins()` seams →
  unit-tested without WordPress. Forbidden set is a single source, filterable.
- Added to the existing `Security` module (cohesive "protect production"); no new
  deptrac layer. Conflation-clean.

### File List

- NEW `wp-content/plugins/ink-core/src/Security/ProductionHygiene.php`
- NEW `tests/Unit/Security/ProductionHygieneTest.php`
- NEW `docs/production-hygiene-runbook.md`
- UPDATE `wp-content/plugins/ink-core/src/Security/Module.php` (register collaborator + docstring)
- UPDATE `_bmad-output/implementation-artifacts/sprint-status.yaml` (18.6 status)

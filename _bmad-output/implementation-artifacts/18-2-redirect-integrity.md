---
baseline_commit: ef775c1
---

# Story 18.2: Redirect integrity

Status: review

<!-- Note: Validation is optional. Run validate-create-story for quality check before dev-story. -->

## Story

As a site owner,
I want all old URLs to resolve,
so that SEO and links survive migration. (NFR-4)

## Acceptance Criteria

1. **Given** migrated URLs **When** verified **Then** all old URLs → correct 301 and 404s are tracked.
2. **Code deliverable — a redirect-integrity auditor** over the Story-16.7 map (`Ink\Migration\RedirectGenerator::OPTION_MAP`): pure `audit()` that flags the integrity defects a one-shot migration map can carry — **chains** (a target whose path is itself a redirect key → a 301-to-301 hop), **loops** (a target that normalises to its own key), and **empty/invalid targets**. Returns a structured report with an `ok` verdict.
3. **`fix` (flatten chains):** a pure `flatten()` that resolves each chain to its final destination (bounded iterations; a cycle is reported, never followed) so every old URL issues a **single** 301 to the live target — not a hop. Idempotent.
4. **CLI surface:** `wp ink verify-redirects` loads the stored map and prints the audit (count, chains, loops, empty); `--fix` flattens chains and re-stores the map; non-zero/`WP_CLI::warning` on defects, success when clean. WP-CLI-gated exactly like 16.7's `wp ink generate-redirects`.
5. **404 tracking + the live 301-verify crawl are documented** (need a running site — Epic 17 retro carry-forward): `docs/redirect-integrity-runbook.md` covering the Redirection plugin's 404 log (the "404s are tracked" mechanism — a configured platform plugin, not reimplemented in ink-core), the pre-cutover crawl that hits every recorded old URL and asserts a single 301 → 200, and the `wp ink verify-redirects` step in the cutover sequence.
6. **Non-vacuous tests** prove each defect class: a clean map audits `ok`; a chain is detected and `flatten()` collapses it to the final target; a loop is detected and **not** followed by flatten; an empty target is flagged; flatten is idempotent (re-running yields the same map). The existing `RedirectGeneratorTest` stays green.
7. **Three-layer + conflation clean:** all logic in `ink-core` Migration module; reads only the 16.7 map + WP-CLI; zero Tiers/Entitlement. No new deptrac edge (same `Ink\Migration` layer; reads `RedirectGenerator` constants in-module).
8. **Gates green:** `composer test:unit`, `composer cs`, `php -l`, `composer stan`, `composer copy:scan` clean; `composer deptrac` — no new violations; baseline unchanged.

## Tasks / Subtasks

- [x] Task 1: `src/Migration/RedirectIntegrity.php` — pure auditor (AC: #2, #3)
  - [x] `audit( array $map ): array` → `{count, chains[], loops[], empty[], ok}`.
  - [x] `flatten( array $map ): array` → resolve chains to final targets, bounded by `MAX_HOPS=25`; cycles left as-is and reported by `audit()`; empty targets dropped; idempotent.
  - [x] Reuse `RedirectGenerator::normalisePath()` for key/target comparison (single source).
- [x] Task 2: CLI `wp ink verify-redirects` (AC: #4)
  - [x] `register()` adds the command only under WP-CLI (mirror 16.7's guard); **all `WP_CLI::*` I/O lives inside the guarded closure** (house pattern — needed for both runtime correctness and phpstan's `class_exists` narrowing).
  - [x] Loads the stored map via an overridable `loadMap()` seam; prints the audit; `--fix` flattens + re-stores via `storeMap()` seam; warns on defects, success when clean. Afrikaans operator messages.
  - [x] Wired into `Migration\Module::register()`.
- [x] Task 3: Runbook (AC: #1, #5)
  - [x] `docs/redirect-integrity-runbook.md`: static map audit, the live pre-cutover 301-verify crawl (every old URL → single 301 → 200), Redirection 404-log config (the "404s tracked" mechanism), and the cutover-sequence placement.
- [x] Task 4: Tests (AC: #6)
  - [x] `tests/Unit/Migration/RedirectIntegrityTest.php` (9 tests): clean→ok; chain detected + flattened (incl. 3-hop); loop detected + not followed; empty target flagged + dropped; flatten idempotent.
- [x] Task 5: Gates (AC: #8)
  - [x] `composer test:unit` ✓ (1046 passed, 1 skipped, +9); `composer cs` ✓ (phpcbf aligned one `=`; 0 errors/0 warnings on new files); `php -l` ✓; `composer stan` ✓ (No errors — after moving WP_CLI I/O into the guarded closure); `composer copy:scan` ✓ (8/8); `composer deptrac` — no new violations (RedirectIntegrity is in the existing `Ink\Migration` layer; in-module read of `RedirectGenerator`).

## Dev Notes — completion addendum

- **phpstan + WP_CLI:** the project has no wp-cli stubs, so phpstan only resolves
  `\WP_CLI::*` calls that sit AFTER a `class_exists('\WP_CLI')` guard (its
  narrowing). An initial factor-out into a separate `runCli()` method lost that
  narrowing → 5 `class.notFound` errors. Fixed by keeping all `WP_CLI::*` I/O inside
  the guarded `register()` closure, matching `TierImport`/`RedirectGenerator`. Worth
  promoting as a convention reminder for the remaining CLI stories (18.x).
- The 3 pre-existing `Kernel\Activation → Content` deptrac violations remain (flagged
  under 18.1); 18.2 adds none.

## Dev Notes

### What is code vs. ops here
The redirect map is **built** by 16.7 (`wp ink generate-redirects`). Story 18.2 is the **verification** layer. Two halves:
- **Code (testable now):** static integrity of the map — chains/loops/empty — and a flatten fix. A migration map assembled from `old → current-permalink` records can develop a chain when post A's old URL equals post B's new URL (B moved into A's vacated slot); serving that as two hops is an SEO smell. `flatten()` collapses it.
- **Ops (needs a running site → runbook):** the live HTTP crawl that confirms each old URL returns a single 301 then 200, and the 404 log. 404 tracking is the **Redirection** plugin's built-in 404 logger (project-context platform plugin) — configured, not reimplemented. This is the Epic-17 retro carry-forward ("18.2 redirect integrity — the 301-verify crawl … needs a running staging site").

### Architecture compliance (project-context.md)
- **Single source:** path comparison reuses `RedirectGenerator::normalisePath()`; the map option/keys are 16.7's constants — never re-declared.
- **Overridable seams:** `loadMap()`/`storeMap()` are protected, so the CLI orchestration is unit-testable without WordPress (Brain-Monkey rule).
- **Conflation rule:** zero Tiers/Entitlement. Same `Ink\Migration` deptrac layer, in-module read of `RedirectGenerator` → no new edge.
- **No copy debt:** `wp-cli` operator messages are Afrikaans (mirroring 16.7's `WP_CLI::success` Afrikaans string); the runbook is a `docs/` file (excluded from `copy:scan`).

### Source tree components to touch
- NEW `wp-content/plugins/ink-core/src/Migration/RedirectIntegrity.php`
- NEW `tests/Unit/Migration/RedirectIntegrityTest.php`
- NEW `docs/redirect-integrity-runbook.md`
- UPDATE `wp-content/plugins/ink-core/src/Migration/Module.php` (register collaborator)

### References
- [Source: _bmad-output/planning-artifacts/epics.md#Story 18.2] — old URLs → 301; 404s tracked.
- [Source: wp-content/plugins/ink-core/src/Migration/RedirectGenerator.php] — 16.7 map build/serve; `OPTION_MAP`, `normalisePath()`.
- [Source: _bmad-output/implementation-artifacts/epic-17-retro-2026-06-28.md] — 18.2 owns the 301-verify crawl (staging-dependent).
- [Source: _bmad-output/project-context.md] — Redirection is a platform plugin; redirects mandatory on every URL-changing reassignment.

## Dev Agent Record

### Agent Model Used

claude-opus-4-8

### Debug Log References

- `composer test:unit -- --filter=RedirectIntegrity` → 9 passed (22 assertions).
- `composer test:unit` → 1046 passed, 1 skipped.
- `composer stan` (sandbox off) → No errors.

### Completion Notes List

- `RedirectIntegrity` is the verification layer over the 16.7 redirect map: pure
  `audit()` (chains/loops/empty) + `flatten()` (collapse chains to the final 301
  target, never follow cycles, drop empties, idempotent), surfaced via
  `wp ink verify-redirects [--fix]`.
- Live 301-verify crawl + 404 tracking are documented (need a running site;
  Redirection plugin owns the 404 log) — the Epic-17 carry-forward.
- Single-source: reuses `RedirectGenerator::normalisePath()` + `OPTION_MAP`.
  Conflation-clean; no new deptrac edge.

### File List

- NEW `wp-content/plugins/ink-core/src/Migration/RedirectIntegrity.php`
- NEW `tests/Unit/Migration/RedirectIntegrityTest.php`
- NEW `docs/redirect-integrity-runbook.md`
- UPDATE `wp-content/plugins/ink-core/src/Migration/Module.php` (register collaborator)
- UPDATE `_bmad-output/implementation-artifacts/sprint-status.yaml` (18.2 status)

---
baseline_commit: 4f41edb
---

# Story 17.4: No-English-leakage QA gate

Status: review

<!-- Note: Validation is optional. Run validate-create-story for quality check before dev-story. -->

## Story

As a QA engineer,
I want a standing leak gate,
so that no English reaches the front end. (NFR-1)

## Acceptance Criteria

1. **Given** Gate D **When** the automated English-leak scan runs (crawl key front-end pages + `wp i18n` counts, with the defined allowlist) **Then** every front-end template/pattern + user-facing email passes; the §12 leak vectors (incl. the 12A.0 form-letter store) are in scope; admin stays English (§14.14) **And** it re-runs after ungated updates (depends on 1.10 scaffolding).
2. **The standing gate is documented as a runbook** (the page-crawl + `wp i18n make-pot`/untranslated-count layer): which front-end pages to crawl, the allowlist, the §12 vectors incl. the 12A.0 form-letter store, that admin stays English (§14.14), and that it re-runs after ungated core/plugin updates (cron/CI). The **static subset already runs in CI** (`composer copy:scan`); the live page-crawl + `wp i18n` runtime layer is a **CI + cron standing gate owned by the staging/CI buildout** (Story 18.8 owns CI wiring) and needs a running site — documented, not silently dropped.
3. **Registry-label robustness folded into the gate (deferred Epic 2 review):** a missing/typo'd concept key must NOT ship a raw machine key (e.g. `genre_plural`) or a blank as a front-end/admin label.
   - **CPT registrar (2.1) `PostTypes::labels()`** and **taxonomy registrar (2.2) `Taxonomies::labels()`** assert `Terms::has()` for each concept key before use; an unknown key triggers `_doing_it_wrong` (caught in dev/CI) rather than silently composing a raw key into labels.
   - **`ink/term` Block Bindings `Bindings::resolve()`** renders **nothing** (empty string) for a missing/unknown binding key instead of the raw key — no raw-key/blank leak to a visitor — plus a `_doing_it_wrong` in dev.
   - **`Terms::label()` (and `ink_term()`)** gain a "called before `init`" guard (`_doing_it_wrong` on WP 6.7+ semantics) so early-translation misuse is caught.
4. **Non-vacuous tests** prove each guard: a bad key in a registrar is flagged; `resolve()` returns '' (not the raw key) for an unknown/empty key; `label()` warns when called before `init`. The existing `TermsTest` (11), `PostTypesTest`, `TaxonomiesTest`, and `AdminLanguageTest` stay green.
5. **No behavior regression to valid paths:** correctly-keyed CPTs/taxonomies/bindings render exactly as before; `Terms::label()` still fails safe (returns the key + `WP_DEBUG` notice) for the low-level helper contract.
6. **Gates green:** `composer test:unit`, `composer cs`, `php -l`, `composer stan`, `composer deptrac`, `composer copy:scan` all clean; baseline unchanged.

## Tasks / Subtasks

- [x] Task 1: Harden `Terms::label()` with a called-before-`init` guard (AC: #3, #4, #5)
  - [x] `Terms::label()` now emits `_doing_it_wrong` when `! did_action('init')` (caught in dev/CI, no fatal). The existing unknown-key fail-safe (return key + WP_DEBUG notice) is preserved.
- [x] Task 2: Assert `Terms::has()` in the registrars (AC: #3, #4)
  - [x] `PostTypes::labels()` calls a new private `assertTermKey()` for both keys; an unregistered key trips `_doing_it_wrong` (then label() fail-safes).
  - [x] `Taxonomies::labels()`: same `assertTermKey()` idiom.
- [x] Task 3: Fix `Bindings::resolve()` to not leak a raw/blank key (AC: #3, #4)
  - [x] `resolve()` now returns `''` (renders nothing) + `_doing_it_wrong` for an empty or unregistered key — never the raw key to a visitor. Valid keys resolve exactly as before.
- [x] Task 4: Tests (AC: #4, #5)
  - [x] `TermsTest`: before-`init` guard fires (init=0 → one `_doing_it_wrong`) and is silent once init fired; unknown-key fail-safe unchanged.
  - [x] `PostTypesTest` + `TaxonomiesTest`: real registration trips NO guard (non-vacuous); a bad key via the private `assertTermKey()` trips `_doing_it_wrong`.
  - [x] NEW `tests/Unit/I18n/BindingsTest.php`: valid key → label; unknown/empty key → `''` (NOT raw key) + warning.
  - [x] **Harness:** because Brain Monkey function stubs persist process-wide (breaking `function_exists` guards across tests), the guards call `did_action`/`_doing_it_wrong` directly and `tests/bootstrap.php` provides inspectable doubles (`did_action` defaults to "init fired" so the 1000+ existing tests stay silent; `_doing_it_wrong` records calls; `ink_reset_guard_spies()` resets). `esc_html` stubbed in the four guard-test files.
- [x] Task 5: Document the standing leak-gate runbook (AC: #1, #2)
  - [x] Added "Standing English-leak gate (NFR-1, Story 17.4)" to `docs/i18n-leak-vectors.md`: Layer 1 (static `copy:scan` + the new registry guards) and Layer 2 (live page-crawl page list + allowlist, `wp i18n make-pot`/untranslated-count, §12 vectors incl. the 12A.0 form-letter store, admin-stays-English §14.14, re-run-after-ungated-updates) — Layer 2 owned by the 18.8 CI buildout (needs a running site).
- [x] Task 6: Run gates (AC: #6)
  - [x] `composer test:unit` ✓ (1026 passed, 1 skipped, +9); `composer cs` ✓ (4 EscapeOutput errors fixed via `esc_html()`); `php -l` ✓; `composer stan` ✓ (No errors); `composer deptrac` ✓ (0 errors/0 warnings); `composer copy:scan` ✓ (8/8 unchanged).

## Dev Notes

### Current state (audit) — what exists vs. what to add
- **Static leak subset EXISTS:** `tools/leak-scan/scan-placeholders.php` (`composer copy:scan`, CI `quality` job) ratchets the three placeholder markers against `placeholder-baseline.json` (currently 8/3 files). Keep it as-is.
- **Live crawl + `wp i18n` counts:** NOT in repo (no running site here); documented as deferred to 17.4/Epic 18. → **document as a standing CI+cron runbook; runtime wiring is Story 18.8's** (project-context: "Story 18.8 owns CI wiring").
- **Registry-label robustness gaps (the concrete code work):**
  - `Terms::label()` (`I18n/Terms.php:226`) — unknown key returns the key (fail-safe, KEEP), but **no before-`init` guard**.
  - `Terms::has()` (`Terms.php:249`) — exists; use it in the registrars.
  - `PostTypes::labels()` (`Content/PostTypes.php:343`) + `Taxonomies::labels()` (`Content/Taxonomies.php:207`) — call `Terms::label()` directly with **no `Terms::has()` assertion**; a typo'd key ships the raw machine key as a label.
  - `Bindings::resolve()` (`I18n/Bindings.php:68`) — returns `Terms::label($key)`, i.e. the **raw key** for a misconfigured/empty binding → change to render nothing.

### Architecture compliance (project-context.md)
- **Guardrail tests must be non-vacuous:** prove the bad-key path is flagged AND a correct key is NOT — exercise the real registrar/binding so the assertion can fail.
- **Test the OUTCOME, not the arg-shape:** assert INK-owned behaviour (labels we compose, the value `resolve()` returns), not WP internals.
- **Controlled-vocabulary labels come from the `Ink\I18n\Terms` registry** — these guards protect that single source from shipping a raw key.
- **Admin stays English (§14.14)** via the already-wired `forceStaffAdminLocale` (AdminLanguageTest) — the gate must not break that; this story doesn't touch it.
- **No fatals in production:** guards use `_doing_it_wrong`/`function_exists` (dev/CI-surfacing), preserving the production fail-safe + the leak scan/crawl as the runtime backstop.

### Test-harness specifics
- `did_action` / `_doing_it_wrong` are NOT stubbed in `tests/bootstrap.php`, so `function_exists()`-guarded calls are skipped in the existing suite → the 11 `TermsTest` + registrar tests stay green without change. New guard tests explicitly `Functions\when('did_action')` / `Functions\expect('_doing_it_wrong')` to assert firing.
- `__()` is an identity passthrough in the suite (Afrikaans source literal returned).

### Project Structure Notes
- MODIFIED: `wp-content/plugins/ink-core/src/I18n/Terms.php`, `src/Content/PostTypes.php`, `src/Content/Taxonomies.php`, `src/I18n/Bindings.php`.
- MODIFIED tests: `tests/Unit/I18n/TermsTest.php`, `tests/Unit/Content/PostTypesTest.php`, `tests/Unit/Content/TaxonomiesTest.php`; NEW `tests/Unit/I18n/BindingsTest.php`.
- DOCS: `docs/i18n-leak-vectors.md` (standing-gate runbook section).
- No new deptrac edge (all within `ink-core`; `Content` already depends on `I18n` for labels). `placeholder-baseline.json` unchanged.

### Testing standards
- Run `composer test:unit`, `composer cs`, `php -l`, `composer stan`, `composer deptrac`, `composer copy:scan`.

### References
- [Source: _bmad-output/planning-artifacts/epics.md#Epic 17 — Story 17.4 + the "Deferred from Epic 2 review" note]
- [Source: docs/specs/ink-consolidated-spec.md#§12 leak vectors; §13; §14.14]
- [Source: wp-content/plugins/ink-core/src/I18n/Terms.php:226,249] (label/has)
- [Source: wp-content/plugins/ink-core/src/I18n/Bindings.php:68] (resolve)
- [Source: wp-content/plugins/ink-core/src/Content/PostTypes.php:343] (CPT labels)
- [Source: wp-content/plugins/ink-core/src/Content/Taxonomies.php:207] (taxonomy labels)
- [Source: tools/leak-scan/scan-placeholders.php] (static subset — keep)
- [Source: docs/i18n-leak-vectors.md] (17.2 — extend with the standing-gate runbook)

## Dev Agent Record

### Agent Model Used

claude-opus-4-8

### Debug Log References

- `composer test:unit` → 1026 passed (+9), 1 skipped, 3870 assertions.
- `composer cs` → clean (fixed 4 `WordPress.Security.EscapeOutput` errors by wrapping the `$key` in the `_doing_it_wrong` messages with `esc_html()`).
- `composer stan` ✓ (No errors), `composer deptrac` ✓ (0/0), `php -l` ✓, `composer copy:scan` ✓ (8/8 unchanged).
- **Brain Monkey gotcha (resolved):** an initial `function_exists('did_action'/'_doing_it_wrong')` guard design failed — Brain Monkey-defined functions persist process-wide, so `function_exists` leaked true across tests and unmocked calls threw (21 failures). Switched to direct calls + bootstrap test doubles (the project's "WP doubles live in bootstrap" convention).

### Completion Notes List

- **Part B (deferred Epic 2 registry-label robustness) — the concrete code:** three guards so a missing/typo'd concept key can never ship a raw machine key or blank as a label. `Terms::label()` warns if called before `init`; `PostTypes`/`Taxonomies` registrars assert `Terms::has()` per key (new private `assertTermKey()`); `Bindings::resolve()` renders nothing (not the raw key) for a misconfigured binding. All dev/CI-surfacing via `_doing_it_wrong`, never a production fatal; the low-level `Terms::label()` fail-safe (return key) is preserved.
- **Part A (the standing gate):** the static `copy:scan` subset already runs in CI and now the registry guards are its source-level catch for raw-key/blank leaks. The live page-crawl + `wp i18n` runtime layer needs a running site → documented as a CI+cron runbook (Layer 2 in `docs/i18n-leak-vectors.md`), owned by the Story 18.8 CI buildout. §12 vectors incl. the 12A.0 form-letter store, admin-stays-English, and re-run-after-ungated-updates are all captured.
- **Test isolation:** guard doubles in `tests/bootstrap.php` (`did_action` defaults to fired → existing suite silent; `_doing_it_wrong` records; `ink_reset_guard_spies()`); non-vacuous tests prove each guard fires on a bad path AND stays silent on the good path.

### File List

- `wp-content/plugins/ink-core/src/I18n/Terms.php` (MODIFIED — before-`init` guard on `label()`)
- `wp-content/plugins/ink-core/src/Content/PostTypes.php` (MODIFIED — `assertTermKey()` guard)
- `wp-content/plugins/ink-core/src/Content/Taxonomies.php` (MODIFIED — `assertTermKey()` guard)
- `wp-content/plugins/ink-core/src/I18n/Bindings.php` (MODIFIED — misconfigured-binding renders nothing)
- `tests/bootstrap.php` (MODIFIED — `did_action`/`_doing_it_wrong` doubles + `ink_reset_guard_spies()`)
- `tests/Unit/I18n/TermsTest.php` (MODIFIED — before-`init` guard tests + esc_html stub)
- `tests/Unit/I18n/BindingsTest.php` (NEW — `resolve()` guard tests)
- `tests/Unit/Content/PostTypesTest.php` (MODIFIED — unregistered-key guard tests)
- `tests/Unit/Content/TaxonomiesTest.php` (MODIFIED — unregistered-key guard tests)
- `docs/i18n-leak-vectors.md` (MODIFIED — Standing English-leak gate runbook)
- `_bmad-output/implementation-artifacts/17-4-no-english-leakage-qa-gate.md` (story file)
- `_bmad-output/implementation-artifacts/sprint-status.yaml` (status tracking)

## Change Log

| Date | Change |
|---|---|
| 2026-06-28 | Story 17.4 implemented: folded registry-label robustness into the leak gate (deferred Epic 2 review) — `Terms::label()` before-`init` guard, `PostTypes`/`Taxonomies` `Terms::has()` assertions, `Bindings::resolve()` renders nothing for a misconfigured binding (no raw-key leak); added bootstrap guard doubles + non-vacuous tests (BindingsTest new). Documented the standing English-leak gate runbook (static today; live crawl + wp i18n owned by 18.8). All gates green (1026 tests). Status → review. |

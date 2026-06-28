---
baseline_commit: f5d3d39
---

# Story 17.2: Residual plugin Afrikaans pass

Status: done

<!-- Note: Validation is optional. Run validate-create-story for quality check before dev-story. -->

## Story

As a content manager,
I want surviving third-party plugin strings translated,
so that no English leaks from plugins. (NFR-1, NFR-7)

## Acceptance Criteria

1. **Given** surviving plugins (BuddyPress, WC/Memberships/PayFast; Real3D Flipbook; Redirection front-end notices) **When** translated on staging with Loco **Then** `.po/.mo` are **committed to version control** (production loads them without Loco), w.org language packs used where complete.
2. **And** all leak vectors are covered (§12): validation/status/error messages, plugin-composed sentences, **transactional emails**, **plugin JavaScript** strings (Real3D JS `.json`), and out-of-band outputs (REST/AJAX/feeds) — human-authored, never AI-generated.
3. **The committed-translations home exists in-repo:** `wp-content/languages/` is created with a README documenting it as the load home for surviving third-party `.po/.mo/.json`, the Loco-on-staging → commit → production-loads-without-Loco workflow, and the no-AI-Afrikaans rule. This is the directory the already-wired loaders (`Ink\Kernel\I18n::load()`, `ink_foundation_load_textdomain()`, `Ink\InkPols\Viewer::registerScriptTranslations()` → `WP_LANG_DIR`) and `wp_set_script_translations` resolve against.
4. **The §12 leak-vector inventory + staging QA checklist is documented** (`docs/i18n-leak-vectors.md`): the five vectors enumerated, mapped per surviving plugin (BP / WC / Memberships / PayFast / Real3D / Redirection), each with a concrete staging verification step — this is the "execution + QA" runbook a human + staging site follow to author and verify the translations. Contact Form 7 / Report Content are noted as out-of-scope (custom `ink-core` forms at launch per 15.4 / 18.4).
5. **A guardrail test asserts the translation-loading seams stay wired** to the committed `wp-content/languages/` home (so a regression that breaks plugin-translation loading fails CI) — non-vacuous, asserting the real wiring, not a stub. The existing admin-language split (`AdminLanguageTest`) and terminology-leak tests remain green.
6. **Tracked content gate:** the actual `.po/.mo/.json` authoring for the surviving plugins is a **pre-launch staging + human-translator gate** (cannot be produced in-repo without a running site and a native author; AI translation is forbidden). It is recorded in the leak-vector doc and the Epic 17 carry-forward, not silently dropped.
7. **Gates green:** `composer test:unit`, `composer cs`, `php -l` (touched files), `composer stan`, `composer deptrac`, `composer copy:scan` all clean; baseline unchanged.

## Tasks / Subtasks

- [x] Task 1: Create the committed-translations home (AC: #1, #3)
  - [x] Created `wp-content/languages/README.md` documenting: its role as the load home for surviving third-party `.po/.mo/.json`; the loaders that resolve here; the Loco-staging → commit → prod workflow; w.org packs preferred; Loco NOT on production; no AI Afrikaans; and the forbidden English `ink-core` `.mo` (mirrors §14.15). The README is also the `.gitkeep` (keeps the dir tracked).
- [x] Task 2: Document the §12 leak-vector inventory + staging QA checklist (AC: #2, #4, #6)
  - [x] Created `docs/i18n-leak-vectors.md`: the five §12 vectors enumerated; per-plugin × per-vector table (BP/WC/Memberships/PayFast/Real3D/Redirection) with w.org-pack notes; concrete staging verification checklist per vector; Real3D JS `.json` + transactional-email vectors called out; CF7/Report Content out-of-scope; pre-launch staging+human gate recorded.
  - [x] Cross-linked the loaders + the standing English-leak scan (Story 17.4).
- [x] Task 3: Guardrail test for the loading seams (AC: #5)
  - [x] Added `tests/Unit/I18n/TranslationLoadingTest.php` (3 tests, behavioral, non-vacuous): `Viewer::registerScriptTranslations()` calls `wp_set_script_translations(handle, 'ink-core', WP_LANG_DIR)` when the script IS registered; does NOTHING when it is not (proves the guard); `I18n::load()` loads the `ink-core` domain from its `/languages` dir. Added `WP_LANG_DIR` + `INK_CORE_FILE` sentinels to `tests/bootstrap.php` (WP constants live in bootstrap, per the test-isolation rule).
  - [x] `AdminLanguageTest` + `TermsTest` remain green.
- [x] Task 4: Run gates (AC: #7)
  - [x] `composer test:unit` ✓ (1017 passed, 1 skipped, +3); `composer cs` ✓; `composer stan` ✓ (No errors); `composer deptrac` ✓ (0 errors/0 warnings); `composer copy:scan` ✓ (8/8 unchanged). `php -l` n/a (no logic PHP beyond the test).

## Dev Notes

### What already exists (read before editing) — infrastructure is COMPLETE
- **`Ink\Kernel\I18n`** (`wp-content/plugins/ink-core/src/Kernel/I18n.php`): `load()` calls `load_plugin_textdomain('ink-core', false, …/languages)` on `init`; `forceStaffAdminLocale()` filters `get_user_locale` → `en_US` for editor/administrator IN ADMIN ONLY, front end stays `af`. Wired in `Kernel\Plugin`. Tested by `tests/Unit/Kernel/AdminLanguageTest.php` (4 tests).
- **`ink_foundation_load_textdomain()`** (`themes/ink-foundation/functions.php`): `load_theme_textdomain('ink-foundation', …/languages)` on `init`.
- **`Ink\InkPols\Viewer::registerScriptTranslations()`** (Story 13.3): `wp_set_script_translations(handle, 'ink-core', WP_LANG_DIR)` — points Real3D Flipbook JS at the committed `wp-content/languages/` home; no-op-safe.
- **`Ink\I18n\Terms`**: single-source Afrikaans UI-label registry; `tests/Unit/I18n/TermsTest.php` (11 tests) + leak tests across Accounts/Entitlement/Org assert zero English leakage.
- **`ink-core/languages/.gitkeep`**: documents the no-English-`.mo` policy (§14.15) — mirror its spirit in the new `wp-content/languages/README.md`.

### Architecture compliance (project-context.md)
- **No AI-generated Afrikaans.** This story builds the home + runbook + guardrail; the actual plugin translations are human-authored on staging. Do NOT generate or commit any AI/translated `.po/.mo` here.
- **Translation workflow:** author on staging with Loco → **commit `.po/.mo` to version control** → production loads from `wp-content/languages/` without Loco. Prefer complete w.org language packs.
- **Loco / migration / diagnostic tools never on production.**
- **Three-layer separation:** no business logic added; this is infra-scaffold + docs + a structural test. No new deptrac edge.
- **Real3D Flipbook viewer controls are plugin JS** — translate via its JS `.json`, not `.mo` (already wired in `Viewer`).

### Why the .po/.mo content is out of in-repo scope (AC #6)
- Vendor plugins (BuddyPress, WooCommerce, Memberships, PayFast, Real3D) are NOT in the repo (they ride the brownfield install) and have no running instance here; Loco string extraction + human native-speaker authoring require a staging site. AI translation is forbidden. So the irreducible content deliverable is a tracked pre-launch staging+human gate — the same shape as 17.1's org-detail content gate. This story makes the gate explicit and ensures the moment the `.mo` lands it loads automatically (infra already wired).

### Project Structure Notes
- NEW: `wp-content/languages/README.md`, `docs/i18n-leak-vectors.md`, a test under `tests/Unit/I18n/` (or `tests/Unit/Kernel/`).
- No `.po/.mo/.json` committed by this story (none can be authored in-repo).
- `placeholder-baseline.json` unchanged.

### Testing standards
- Guardrail test must be **non-vacuous**: assert the real loader wiring (read `Viewer.php` source / invoke the method with mocks) so removing or misdirecting a loader fails. Do not assert against a stub that never loaded.
- Run `composer test:unit`, `composer cs`, `php -l`, `composer stan`, `composer deptrac`, `composer copy:scan`.

### References
- [Source: _bmad-output/planning-artifacts/epics.md#Epic 17 — Story 17.2]
- [Source: docs/specs/ink-consolidated-spec.md#§12 leak vectors; §13; §14.13–14.15]
- [Source: wp-content/plugins/ink-core/src/Kernel/I18n.php] (textdomain load + admin-locale filter)
- [Source: wp-content/plugins/ink-core/src/InkPols/Viewer.php] (Real3D JS `wp_set_script_translations` → WP_LANG_DIR)
- [Source: wp-content/themes/ink-foundation/functions.php] (theme textdomain load)
- [Source: wp-content/plugins/ink-core/languages/.gitkeep] (no-English-`.mo` policy)
- [Source: docs/plugin-transition-guide.md] (surviving plugins; Loco keep, LocoAI retire; CF7/Report Content → custom)
- [Source: tests/Unit/Kernel/AdminLanguageTest.php, tests/Unit/I18n/TermsTest.php]

## Dev Agent Record

### Agent Model Used

claude-opus-4-8

### Debug Log References

- `composer test:unit` → 1017 passed (+3), 1 skipped, 3856 assertions.
- `composer cs` ✓, `composer stan` ✓ (No errors), `composer deptrac` ✓ (0 errors/0 warnings), `composer copy:scan` ✓ (8/8, baseline unchanged).

### Completion Notes List

- **The infrastructure was already complete** (Story 1.10 textdomain loaders + admin-language split, 13.3 Real3D JS wiring, 2.0 terminology registry — all tested). This story added the **committed-translations home** (`wp-content/languages/README.md`), the **§12 leak-vector inventory + staging QA runbook** (`docs/i18n-leak-vectors.md`), and a **behavioral guardrail test** so the loading seams cannot silently regress.
- **The `.po/.mo/.json` content is a tracked pre-launch staging + human gate.** Vendor plugins are not in the repo, there is no running site here, and AI Afrikaans is forbidden — so the content cannot be produced in-repo. The gate is recorded in both new docs and will carry to the Epic 17 retro. The moment a `.mo`/`.json` lands in `wp-content/languages/`, the wired loaders pick it up.
- **Test isolation:** `WP_LANG_DIR` + `INK_CORE_FILE` sentinels added to `tests/bootstrap.php` (not per-test), per the Brain-Monkey constants-in-bootstrap rule.
- No business logic, no new deptrac edge; CF7/Report Content correctly excluded (custom `ink-core` forms at launch per 15.4/18.4).

### File List

- `wp-content/languages/README.md` (NEW — committed-translations home + workflow; also keeps the dir tracked)
- `docs/i18n-leak-vectors.md` (NEW — §12 leak-vector inventory + staging QA checklist)
- `tests/Unit/I18n/TranslationLoadingTest.php` (NEW — behavioral guardrail, 3 tests)
- `tests/bootstrap.php` (MODIFIED — `WP_LANG_DIR` + `INK_CORE_FILE` sentinels)
- `_bmad-output/implementation-artifacts/17-2-residual-plugin-afrikaans-pass.md` (story file)
- `_bmad-output/implementation-artifacts/sprint-status.yaml` (status tracking)

## Change Log

| Date | Change |
|---|---|
| 2026-06-28 | Story 17.2 implemented: created the committed-translations home (`wp-content/languages/README.md`) + §12 leak-vector inventory/staging-QA runbook (`docs/i18n-leak-vectors.md`) + behavioral guardrail test for the translation-loading seams (3 tests). Actual plugin `.po/.mo/.json` authoring recorded as a pre-launch staging+human gate (no AI Afrikaans). All gates green (1017 tests). Status → review. |

# Epic 17 Code Review (R17) — Afrikaans-first & localisation

**Date:** 2026-06-28
**Branch:** `epic-17-localisation` vs `main` (6e8b837)
**Commits reviewed:** f5d3d39 (17.1), de2ee63 (17.2), 4f41edb (17.3), 670a57b (17.4)
**Diff:** 24 files, ~1021 insertions / 61 deletions (~1415 diff lines)
**Method:** three parallel adversarial layers — Blind Hunter (diff-only), Edge Case Hunter (diff + project read), Acceptance Auditor (diff + 4 story specs + project-context + epics.md). All layers completed; none failed.

## Outcome

**Zero HIGH. Zero correctness defects. All ACs satisfied across all four stories.** Two test-harness robustness patches applied (R17-1, R17-2). Five findings dismissed as confirmations / within-AC / harmless config.

## Layer summaries

- **Blind Hunter — "No defects found."** Verified each guard is logically correct (before-`init`, registrar `Terms::has()`, `Bindings::resolve()` empty/unknown → `''`), the `Terms::label()` fail-safe is preserved, and every new test is non-vacuous (paired good/bad paths). Confirmed the `did_action` double's `?? 1` does not mask the before-init test (the `init` key is explicitly seeded to `0`).
- **Edge Case Hunter — 2 MEDIUM (test-harness), 4 LOW/no-finding.** Production guards sound (registrars run during `init`, so the before-init guard is correctly silent at registration; `Bindings::resolve()` cleanly returns `''`; `oor-ink.php` is inert literal markup). The two MEDIUM findings are both test-harness robustness → patched below.
- **Acceptance Auditor — "All ACs satisfied. No project-rule violations."** Confirmed: 17.1 org placeholders resolved (2018 + generic non-profit framing, no US 501(c)(3), no AI Afrikaans, deferrals + baseline untouched); 17.2 home + leak-vector doc + non-vacuous loader test, `.po/.mo` correctly left as a tracked staging+human gate; 17.3 docs reconciled with code IDs preserved (`ink_writer_tier`, enum values, migration "Vriendskappe" source term intact); 17.4 all three deferred-Epic-2 guards implemented per the L2118 note, standing-gate runbook documented.

## Patches applied (R17)

| ID | Finding | Fix | File(s) |
|---|---|---|---|
| R17-1 | Guard-spy globals reset manually in test bodies (not `beforeEach`); a before-init test failing before its trailing reset could leak `init=0` into the shared process | Reset `ink_reset_guard_spies()` in `beforeEach` of all four guard-test files for a guaranteed clean start | `tests/Unit/I18n/TermsTest.php`, `BindingsTest.php`, `Content/PostTypesTest.php`, `Content/TaxonomiesTest.php` |
| R17-2 | `did_action` double's `?? 1` fallback reported every unseeded hook as fired → could mask a future wrong-hook regression | Default unseeded hooks to NOT fired (`?? 0`); `init` is seeded to 1 by `ink_reset_guard_spies()` | `tests/bootstrap.php` |

Post-patch gates: `composer test:unit` ✓ (1026 passed, 1 skipped), `composer cs` ✓.

## Dismissed (5)

1. Guards confirmed correct + tests non-vacuous (Blind Hunter) — no action.
2. `oor-ink.php` change is inert literal block markup — no escaping concern.
3. `Terms::label()` before-init contract widening — the `wp_trigger_error`/`_doing_it_wrong` functions exist in WP 6.4+; no real lifecycle point where undefined.
4. `oor-ink.php` literal prose not gettext-wrapped — within AC; matches the Story 15.3 static-page-assembly convention by design (and is a known characteristic outside the runtime `wp i18n` layer).
5. `.claude/settings*.json` permission churn — harmless harness/session config consolidation, not Epic 17 logic. Noted for awareness; carried in 670a57b.

## Standing gate / pre-launch obligations recorded (not defects)

- The live page-crawl + `wp i18n` leak layer (17.4 Layer 2) is owned by the Story 18.8 CI buildout (needs a running site).
- The surviving-plugin `.po/.mo/.json` authoring (17.2) is a pre-launch staging + human-translator gate (no AI Afrikaans).
- `[stigtingsjaar]` = 2018 is provisional pending founder confirmation (a one-line later edit).

These belong in the Epic 17 retrospective carry-forward.

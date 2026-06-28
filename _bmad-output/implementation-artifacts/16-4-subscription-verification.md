---
baseline_commit: c7e6a40
---

# Story 16.4: Subscription verification

Status: done

<!-- Note: Validation is optional. Run validate-create-story for quality check before dev-story. -->

## Story

As an ink-core developer,
I want subscriptions verified (not imported),
so that memberships ride the DB clone correctly. (FL 16.4)

## Acceptance Criteria

1. **Given** the cloned subscriptions **When** verified on the new host **Then** memberships, plan IDs, access rules, and expiry are confirmed (no import script).
2. This is **read-only verification, NOT an import**: the command produces a report and **mutates nothing** (no `update_*`/`wp_insert_*`/`delete_*`). Subscriptions ride the DB clone; this confirms their integrity before cutover.
3. The report covers: total memberships, **counts per status**, **distinct plan IDs (with counts)**, and **expiry coverage** (how many active memberships are time-limited vs unlimited). It **flags** records that need human attention before cutover: a membership with no plan ID, or an unrecognised status.
4. Membership records are read through the **documented WooCommerce Memberships API** (`wc_memberships_get_user_membership()`), behind a `function_exists` guard (the `Entitlement\SubmissionGate`/`LifecycleEmails` house pattern) — never by assuming WC's internal table structure. WC Memberships absent → the command reports "not available" and exits cleanly (no fatal).
5. WP-CLI only (`wp ink verify-subscriptions`). No idempotency flag is needed (a read-only report is naturally re-runnable). Afrikaans `\WP_CLI` output.
6. Conflation-clean: reads membership state ONLY (no `ink_writer_tier` read/write); introduces no `Tiers` coupling. The WC read is an external-dependency call, so no new INK deptrac edge.
7. `composer test:unit` green (new `SubscriptionVerifierTest` covering the pure `summarise()`/flagging logic, the "test the OUTCOME not WC internals" rule); repo-wide `composer cs` = 0 errors; `php -l`, `composer stan`, `composer deptrac`, `composer copy:scan` clean.

## Tasks / Subtasks

- [x] Task 1: Implement `SubscriptionVerifier` (AC: #1–#6)
  - [x] Added `wp-content/plugins/ink-core/src/Migration/SubscriptionVerifier.php`: `CLI_COMMAND = 'ink verify-subscriptions'`, `register()` (WP-CLI-only), `verify(): array` (the report), NO `OPTION_DONE`/`markDone` (read-only).
  - [x] Pure helpers: `knownStatuses(): array` (active/paused/expired/cancelled/pending/free_trial/complimentary) and `summarise(array $records): array` → `{total, by_status, plans, active_with_expiry, active_unlimited, flagged}`; a record flags on missing plan (`geen-plan`) or unknown status (`onbekende-status`).
  - [x] Overridable I/O seams: `wooMembershipsAvailable(): bool` (`function_exists('wc_memberships_get_user_membership')`), `membershipRecords(): array` (default: `get_posts(post_type=wc_user_membership)` → `wc_memberships_get_user_membership()` getters → normalised `{user_id, plan_id, status, end_date}`; local `function_exists` guard for static analysis).
  - [x] `verify()` returns `summarise(records)` + an `available` flag; when unavailable, returns an empty report with `available=false`.
  - [x] Afrikaans `\WP_CLI` summary + per-status output; `\WP_CLI::warning` when WC Memberships is absent.
- [x] Task 2: Register in the module (AC: #5)
  - [x] Added `( new SubscriptionVerifier() )->register();` to `Migration\Module::register()`; corrected the module docblock ("the verification commands are read-only and naturally re-runnable").
- [x] Task 3: Tests (AC: #3, #7)
  - [x] Added `tests/Unit/Migration/SubscriptionVerifierTest.php` (5 tests): `summarise()` groups by status, lists plan counts, splits active expiry vs unlimited, and **flags** no-plan + unknown-status records (NOT healthy ones); `knownStatuses` contents; `verify()` returns `available=false` + empty report when the WC seam is unavailable (and never calls the read seam in that case). Pure-logic focus — does NOT assert WC internals.
  - [x] All gates green.

## Dev Notes

### What already exists (read before editing)
- `wp-content/plugins/ink-core/src/Entitlement/SubmissionGate.php` (`WC_MEMBERSHIPS_FN`, the `function_exists` guard, `wc_memberships_get_user_memberships()`) and `Entitlement/LifecycleEmails.php` (`wc_memberships_get_user_membership()` behind `function_exists`) — the documented-API + guard pattern to mirror for the read seam. Do NOT reach into WC tables.
- `wp-content/plugins/ink-core/src/Migration/{DbSanitiser,UserReclassifier,TierImport}.php` + `Module.php` — the once-off-CLI + seam pattern; this is the one Epic-16 command WITHOUT an idempotency flag (read-only).
- `tests/Unit/InkPols/MigrationTest.php` — anonymous-subclass-over-seams idiom.

### Architecture compliance (project-context.md)
- **Verification only, no import** (migration-plan: "subscription data migrates automatically with the database clone. No import script is required … Manually verify before cutover each active membership's state, plan ID, and expiry date — PRD MR-5"). The command mutates nothing.
- **Hook, don't edit / use the documented API** — read memberships via `wc_memberships_get_user_membership()`, never WC internals.
- **Test the OUTCOME, not third-party internals** — unit-test the INK-owned `summarise()` report shape, never assert WC call shapes through a mock.
- **THE conflation rule:** membership ≠ tier — this reads membership only, zero `Ink\Tiers` coupling.
- **i18n:** Afrikaans CLI output.

### Project Structure Notes
- NEW: `src/Migration/SubscriptionVerifier.php`, `tests/Unit/Migration/SubscriptionVerifierTest.php`.
- MODIFIED: `src/Migration/Module.php` (one `register()` line).
- No new deptrac layer/edge (reads WC external + WP core; INK dep stays `Migration → Kernel`/none).

### Testing standards
- Override the WC read seam so no real WC/DB is needed; test `summarise()`/`knownStatuses()` as pure functions.
- Run `composer test:unit`, `composer cs`, `php -l`, `composer stan`, `composer deptrac`, `composer copy:scan`.

### References
- [Source: _bmad-output/planning-artifacts/epics.md#Story 16.4: Subscription verification] (FL 16.4)
- [Source: docs/migration-plan.md — Subscriptions; "verification only; no data import needed"; "Manually verify before cutover each active membership's state, plan ID, and expiry date (PRD MR-5)"]
- [Source: _bmad-output/project-context.md — verify subscriptions (no import script); hook-don't-edit; test the OUTCOME; conflation rule]
- [Source: wp-content/plugins/ink-core/src/Entitlement/SubmissionGate.php — documented WC Memberships read + function_exists guard]

## Dev Agent Record

### Agent Model Used

claude-opus-4-8 (BMAD dev-story workflow)

### Debug Log References

- `composer test:unit` → 967 passed, 1 skipped (3695 assertions). New `SubscriptionVerifierTest`: 5 passed.
- `composer cs` → 0 errors, 0 warnings on the new files.
- `composer stan` → No errors (added a local `function_exists` guard before the un-stubbed WC reader, the SubmissionGate pattern, so static analysis stays clean without a baseline).
- `composer deptrac` → 3 pre-existing `Kernel\Activation → Content` violations only; no new edge (reads WC external + WP core, no INK module dep beyond Kernel/none).
- `composer copy:scan` → no new debt (baseline 8).
- `php -l` clean on `SubscriptionVerifier.php`.

### Completion Notes List

- `SubscriptionVerifier` is **read-only** — it produces a pre-cutover report and mutates nothing (no idempotency flag needed). Subscriptions ride the DB clone; this confirms their integrity (MR-5).
- The report covers total memberships, counts per status, distinct plan IDs (with counts), active expiry coverage (time-limited vs unlimited), and a **flagged** list of records needing human attention before cutover (no plan ID / unrecognised status).
- Records are read through the documented `wc_memberships_get_user_membership()` getters behind a `function_exists` guard (never WC internals); WC Memberships absent → `available=false`, clean exit. Conflation-clean: reads membership state only, zero `Ink\Tiers` coupling.
- Per the "test the OUTCOME, not WC internals" rule, the unit tests cover the INK-owned `summarise()` report shape + flagging, not WooCommerce call shapes.

### File List

- `wp-content/plugins/ink-core/src/Migration/SubscriptionVerifier.php` (NEW)
- `wp-content/plugins/ink-core/src/Migration/Module.php` (MODIFIED — registered `SubscriptionVerifier` + docblock)
- `tests/Unit/Migration/SubscriptionVerifierTest.php` (NEW)
- `_bmad-output/implementation-artifacts/16-4-subscription-verification.md` (story record)
- `_bmad-output/implementation-artifacts/sprint-status.yaml` (status tracking)

## Change Log

- 2026-06-28 — Story 16.4 implemented: `SubscriptionVerifier` — read-only pre-cutover WC Memberships verification report (status/plan/expiry coverage + flagged records), no import, via the documented WC API behind a guard. Status → review.

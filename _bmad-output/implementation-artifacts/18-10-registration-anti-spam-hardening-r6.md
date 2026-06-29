---
baseline_commit: ef775c1
---

# Story 18.10: Registration anti-spam hardening (R6)

Status: done

<!-- Note: Validation is optional. Run validate-create-story for quality check before dev-story. -->

## Story

As a site owner,
I want the registration endpoint hardened,
so that signup abuse is curbed. (FR-3a, R6)

## Acceptance Criteria

1. **Given** the 3.4 spike outcome **When** hardening is applied **Then** the security stack (18.3) gains the registration anti-abuse surface + the optional pending-approval state (3.6).
2. **The always-on anti-spam baseline** (the layers the Story-3.4 spike decided but did not build) is built on the registration endpoint as `Ink\Accounts\RegistrationGuard`: **honeypot**, **submit-timing**, a **Cloudflare-Turnstile challenge seam**, **per-IP rate-limiting**, and **blocked-attempt analytics**.
3. **Pure, ordered decision** `evaluate()` — honeypot → too-fast → failed-challenge → rate-limit (cheapest/most-certain first); returns a `REASON_*` code or null. Fail-safe on honeypot, fail-open on a missing timing/challenge field (a missing field never falsely blocks a real signup).
4. **Wired via WP-native hooks** — `register_form` emits the hidden honeypot + render-timestamp fields; `registration_errors` runs the guard and adds a `WP_Error` (Afrikaans message) on a block; `do_action( 'ink/registration_blocked', $reason, $ip )` fires for analytics. The Turnstile verdict comes through the `ink_registration_challenge_passed` filter (default pass until a provider is wired). Rate-limit via a per-IP transient (overridable seam).
5. **Complements, does not duplicate, the 3.6 pending-approval state** — `Approval` (Story 3.6) still owns the optional manual-approval queue; this guard is the always-on surface around it.
6. **Non-vacuous tests:** `evaluate()` passes a clean human; blocks each failure mode in order (honeypot wins over a simultaneously-fast/failed/over-limit attempt); the rate boundary (allow at limit-1, block at limit); `guard()` adds the error + fires the analytics action for a bot and returns the errors untouched for a clean human.
7. **Three-layer + conflation clean:** logic in `ink-core` Accounts module; references neither Tiers nor Entitlement (anti-spam is not gated on membership/Gradering). No deptrac change (Accounts is deptrac-uncovered, like the other Accounts collaborators).
8. **Afrikaans:** member-facing block messages + the honeypot label are Afrikaans via `ink-core`. No new copy debt (concrete copy).
9. **Gates green:** `composer test:unit`, `composer cs`, `php -l`, `composer stan`, `composer copy:scan`, `composer deptrac` (no new violations); baseline unchanged.

## Tasks / Subtasks

- [x] Task 1: `src/Accounts/RegistrationGuard.php` (AC: #2, #3, #4)
  - [x] Field/reason/window constants; pure `evaluate()` + `messageFor()`.
  - [x] `renderFields()` (honeypot + timestamp on `register_form`); `guard()` on `registration_errors`; `do_action('ink/registration_blocked')`.
  - [x] Overridable seams: `honeypotValue()`, `renderedAt()`, `now()`, `challengePassed()` (Turnstile filter), `requesterIp()`, `attemptCount()`/`recordAttempt()` (per-IP transient).
- [x] Task 2: Wire into `Accounts\Module::register()` + update the module docstring (18.10 baseline now built; remove the "NOT built here" note).
- [x] Task 3: Tests (AC: #6) — `tests/Unit/Accounts/RegistrationGuardTest.php` (10).
- [x] Task 4: Runbook (AC: #1) — added the "Registration anti-spam (18.10)" section to `docs/security-stack-runbook.md`.
- [x] Task 5: Gates (AC: #9) — `composer test:unit` ✓ (1123 passed, 1 skipped, +10); `composer cs` ✓ (REMOTE_ADDR sanitised via wp_unslash+sanitize_text_field; `ink/...` hook suppressed per AD); `php -l` ✓; `composer stan` ✓ (No errors); `composer copy:scan` ✓ (8/8 — concrete Afrikaans); `composer deptrac` — no new violations.

## Dev Notes

### What the 3.4 spike left to 18.10
Story 3.4 decided the baseline (Turnstile + double-opt-in + honeypot/timing) and 3.6
built the optional pending-approval state; the Accounts module docstring explicitly
parked the *always-on baseline + hardening* for Story 18.10. This story builds it.
Email double-opt-in is a WP-core/transactional-email concern; the in-code baseline here
is honeypot + timing + the Turnstile seam + rate-limit + analytics. Turnstile is the
challenge of choice (Cloudflare is already the edge — 18.3).

### Architecture compliance (project-context.md)
- **Sanctioned WP hooks** — `register_form` + `registration_errors` (WP verifies the
  registration nonce before `registration_errors`, so the guard reads `$_POST` for the
  bot-signal only, sanitised).
- **Brain-Monkey isolation** — pure `evaluate()` over primitives; every request read is
  an overridable seam (tested by subclass-and-override).
- **Conflation rule** — zero Tiers/Entitlement. Accounts is deptrac-uncovered → no edge.
- **`ink/...` event convention** — `ink/registration_blocked` with the AD phpcs suppression.

### Source tree components to touch
- NEW `wp-content/plugins/ink-core/src/Accounts/RegistrationGuard.php`
- NEW `tests/Unit/Accounts/RegistrationGuardTest.php`
- UPDATE `wp-content/plugins/ink-core/src/Accounts/Module.php` (register + docstring)
- UPDATE `docs/security-stack-runbook.md` (anti-spam section)

### References
- [Source: _bmad-output/planning-artifacts/epics.md#Story 18.10] — registration anti-abuse + pending-approval (3.6).
- [Source: wp-content/plugins/ink-core/src/Accounts/Module.php] — the "18.10 hardening NOT built here" note (now built).
- [Source: wp-content/plugins/ink-core/src/Accounts/Approval.php] — the 3.6 pending state this complements.
- [Source: _bmad-output/project-context.md] — Cloudflare edge; security defaults.

## Dev Agent Record

### Agent Model Used

claude-opus-4-8

### Debug Log References

- `composer test:unit -- --filter=RegistrationGuard` → 10 passed.
- `composer test:unit` → 1123 passed, 1 skipped.
- `composer stan` (sandbox off) → No errors.

### Completion Notes List

- `RegistrationGuard` is the always-on registration anti-abuse surface (honeypot,
  timing, Turnstile seam, per-IP rate-limit, blocked-attempt analytics) wired via
  `register_form` + `registration_errors`; complements (does not duplicate) the 3.6
  pending-approval queue.
- Pure ordered `evaluate()` (honeypot → fast → challenge → rate) + overridable seams →
  unit-tested without WordPress. Turnstile via `ink_registration_challenge_passed`
  (default pass until wired).
- Accounts module docstring updated (the baseline is no longer "NOT built here").
  Conflation-clean; no deptrac change (Accounts uncovered).

### File List

- NEW `wp-content/plugins/ink-core/src/Accounts/RegistrationGuard.php`
- NEW `tests/Unit/Accounts/RegistrationGuardTest.php`
- UPDATE `wp-content/plugins/ink-core/src/Accounts/Module.php`
- UPDATE `docs/security-stack-runbook.md`
- UPDATE `_bmad-output/implementation-artifacts/sprint-status.yaml` (18.10 status)

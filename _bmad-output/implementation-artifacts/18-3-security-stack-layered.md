---
baseline_commit: ef775c1
---

# Story 18.3: Security stack (layered)

Status: review

<!-- Note: Validation is optional. Run validate-create-story for quality check before dev-story. -->

## Story

As a site owner,
I want a layered security stack,
so that the site and origin are protected. (§14.16)

## Acceptance Criteria

1. **Given** the resolved stack **When** deployed **Then** Cloudflare (edge + login rule, origin locked) + staff 2FA + Patchstack (CVE alerts) + staging-gated updates + host malware scanning are in place; Loginizer/WordFence not used; PayFast off-site keeps PCI scope low.
2. **Code deliverable — the origin-side complement** to the edge stack (a new `Ink\Security` module): the hardening that genuinely belongs at the origin, not at Cloudflare, and that shrinks the login/enumeration attack surface Cloudflare's login rule also guards. Each hardening is **filterable (default on)** so a deployment can opt out without code changes:
   - **Disable XML-RPC** (`xmlrpc_enabled` → false) — a brute-force-amplification + pingback-DDoS vector INK does not use.
   - **Block username enumeration** — `?author=N` author-archive probing redirects home for unauthenticated requests; the public REST `/wp/v2/users` collection is removed for unauthenticated requests (kept for logged-in editors, who need it in the block editor).
   - **Remove version disclosure** — drop the `wp_generator` meta / `the_generator` output (info leak that helps target CVEs).
3. **Staff-2FA coverage audit** — since 2FA itself is a plugin (not reimplemented in ink-core), the module provides a **pure** `staffMissingTwoFactor()` that, given staff users + a "has 2FA" predicate, reports editors/administrators lacking 2FA, surfaced via `wp ink audit-2fa`. This is the verification that AC #1's "staff 2FA in place" actually holds.
4. **Runbook documents the external stack** (`docs/security-stack-runbook.md`): Cloudflare edge config + the login rule + origin lock (allow only Cloudflare IPs), Patchstack CVE alerting, staging-gated updates (cross-ref 18.7), host malware scanning, the explicit **"Loginizer/WordFence NOT used"** decision and why (Cloudflare + Patchstack + origin lock replace them), and PayFast off-site → low PCI scope.
5. **Non-vacuous tests** prove each hardening decision: xmlrpc disabled (and the opt-out filter re-enables it); an unauthenticated `?author=` request is flagged for redirect while a logged-in one is not; the restricted REST routes list is correct and the endpoint filter removes them only when unauthenticated; the generator string is emptied; `staffMissingTwoFactor()` returns exactly the staff without 2FA (and none when all covered).
6. **Three-layer + conflation clean:** all logic in `ink-core`; `Ink\Security` references neither Tiers nor Entitlement (hardening is not gated on membership/Gradering). New deptrac layer `Security` → allowed dep `Kernel` only.
7. **Afrikaans:** the `wp ink audit-2fa` operator output is Afrikaans (admin/CLI tooling convention). No front-end strings. No copy debt.
8. **Gates green:** `composer test:unit`, `composer cs`, `php -l`, `composer stan`, `composer copy:scan` clean; `composer deptrac` no new violations; baseline unchanged.

## Tasks / Subtasks

- [x] Task 1: `Ink\Security` module skeleton (AC: #2, #6)
  - [x] `src/Security/Module.php` implements `Kernel\Module`; `register()` wires `Hardening` + `TwoFactorAudit`.
  - [x] Deptrac layer `Security` (`#^Ink\\Security\\.*#`) → ruleset `Security: [Kernel]`.
  - [x] Registered module in `ink-core.php`.
- [x] Task 2: `src/Security/Hardening.php` — origin hardening (AC: #2, #5)
  - [x] `register()` wires each hardening behind its own `apply_filters('ink_security_*', true)` gate.
  - [x] Pure decisions: `isAuthorEnumeration()`, `restrictedRestRoutes()`, `shouldRestrictUsersEndpoint()`.
  - [x] Filter callbacks: `xmlrpc_enabled` → false; `template_redirect` author-enum guard; `rest_endpoints` users-route removal when unauthenticated; `the_generator` → '' + `wp_generator` removed.
- [x] Task 3: `src/Security/TwoFactorAudit.php` — 2FA coverage (AC: #3, #5, #7)
  - [x] Pure `staffMissingTwoFactor()` (fail-safe: missing flag = not covered).
  - [x] `wp ink audit-2fa` CLI (WP-CLI-gated; all WP_CLI I/O inside the guarded closure); Afrikaans output. Overridable `staffUsers()`/`hasTwoFactor()` seams (the latter via `ink_security_user_has_2fa` filter so it binds to the installed 2FA plugin, not a reimplementation).
- [x] Task 4: Runbook (AC: #1, #4)
  - [x] `docs/security-stack-runbook.md` — Cloudflare edge+login+origin-lock, Patchstack, host scanning, staff 2FA, staging-gated updates, "Loginizer/WordFence NOT used", PayFast off-site/low PCI.
- [x] Task 5: Tests (AC: #5)
  - [x] `tests/Unit/Security/HardeningTest.php` (11) + `tests/Unit/Security/TwoFactorAuditTest.php` (4).
- [x] Task 6: Gates (AC: #8)
  - [x] `composer test:unit` ✓ (1061 passed, 1 skipped, +15); `composer cs` ✓ (new files 0/0; required-but-unused WP-CLI callback params annotated); `php -l` ✓; `composer stan` ✓ (No errors); `composer copy:scan` ✓ (8/8); `composer deptrac` — `Security → [Kernel]` only, no new violations (the 3 pre-existing Kernel→Content remain).

## Dev Notes

### What is code vs. ops here
The AC's named stack (Cloudflare, Patchstack, host scanning, 2FA plugin, staging-gated updates) is **external/ops → runbook**. The legitimate code contribution is the *origin-side* hardening that makes "origin locked" real in code and reduces the same login/enumeration surface the edge rule guards — plus the audit that verifies 2FA coverage. Each hardening is filterable so it never fights a deployment's edge config.

### Architecture compliance (project-context.md)
- **Security defaults:** escape/sanitise/nonce are already project-wide; this module adds *surface reduction* (xmlrpc, enumeration, version) — defense in depth behind Cloudflare.
- **Don't reimplement plugin capability:** 2FA is a plugin; ink-core *audits* coverage, never implements TOTP. Loginizer/WordFence explicitly NOT used (Cloudflare + Patchstack + origin lock replace them) — documented, and this module is the in-repo origin hardening that lets us *not* run WordFence.
- **Brain-Monkey isolation:** `staffUsers()` is an overridable seam; pure decision helpers take primitives so tests need no WordPress.
- **Conflation rule:** zero Tiers/Entitlement. New deptrac layer `Security → [Kernel]` only.
- **WP_CLI + phpstan:** keep all `WP_CLI::*` I/O inside the `class_exists`-guarded closure (the 18.2 lesson).

### Source tree components to touch
- NEW `src/Security/Module.php`, `Hardening.php`, `TwoFactorAudit.php`
- NEW `tests/Unit/Security/HardeningTest.php`, `TwoFactorAuditTest.php`
- NEW `docs/security-stack-runbook.md`
- UPDATE `ink-core.php` (register `security` module); `deptrac.yaml` (Security layer)

### References
- [Source: _bmad-output/planning-artifacts/epics.md#Story 18.3] — the resolved §14.16 stack.
- [Source: _bmad-output/project-context.md] — Cloudflare-locked origin; staff 2FA; PayFast off-site (low PCI); don't reactivate Loginizer.

## Dev Agent Record

### Agent Model Used

claude-opus-4-8

### Debug Log References

- `composer test:unit -- --filter=Security` → 15 passed.
- `composer test:unit` → 1061 passed, 1 skipped.
- `composer stan` (sandbox off) → No errors.

### Completion Notes List

- `Ink\Security` = the origin-side complement to the edge stack. `Hardening`
  (xmlrpc off, username-enumeration block — author archive + REST users for
  anonymous only, version disclosure removed), each behind an `ink_security_*`
  opt-out filter. `TwoFactorAudit` verifies staff 2FA coverage (`wp ink audit-2fa`)
  binding to the installed 2FA plugin via `ink_security_user_has_2fa` — never
  reimplements TOTP.
- Pure decision helpers + overridable seams → fully unit-testable without WordPress.
- Conflation-clean; new deptrac layer `Security → [Kernel]` only.
- The external stack (Cloudflare/Patchstack/host/2FA-plugin) is the runbook;
  "Loginizer/WordFence NOT used" documented with rationale.

### File List

- NEW `wp-content/plugins/ink-core/src/Security/Module.php`
- NEW `wp-content/plugins/ink-core/src/Security/Hardening.php`
- NEW `wp-content/plugins/ink-core/src/Security/TwoFactorAudit.php`
- NEW `tests/Unit/Security/HardeningTest.php`
- NEW `tests/Unit/Security/TwoFactorAuditTest.php`
- NEW `docs/security-stack-runbook.md`
- UPDATE `wp-content/plugins/ink-core/ink-core.php` (register `security` module)
- UPDATE `deptrac.yaml` (Security layer + `Security → [Kernel]`)
- UPDATE `_bmad-output/implementation-artifacts/sprint-status.yaml` (18.3 status)

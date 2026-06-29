# Epic 18 code review (SEO, security & performance) — R18

**Date:** 2026-06-29
**Branch:** `epic-18-seo-security-performance` (baseline `ef775c1`)
**Scope:** Stories 18.1–18.10. 68 files, +5,801/−60. Production source ≈ 2,472 diff lines.
**Method:** three parallel adversarial layers — Blind Hunter (diff-only), Edge Case
Hunter (diff + repo), Acceptance Auditor (diff + specs + project-context).

## Verdict

**0 CRITICAL, 0 unresolved HIGH.** One HIGH (Cloudflare rate-limit IP) and three LOW
findings were **fixed** (R18 patches below). The remaining findings are accepted with
rationale (defense-in-depth layers, design decisions, or human-moderation downstream).
Conflation rule, three-layer separation, single-source slugs, enum value-sets, and
Afrikaans-first all verified clean by the Acceptance Auditor.

## Findings & disposition

### Fixed (R18 patches)

| # | Sev | Finding | Fix |
|---|-----|---------|-----|
| R1 | HIGH | `RegistrationGuard::requesterIp()` read `REMOTE_ADDR` directly — behind Cloudflare that is the shared edge IP, so the 5/15-min limit would lock **all** users out together; an empty IP collapsed into one global `md5('')` bucket. | Resolve `HTTP_CF_CONNECTING_IP` first, fall back to `REMOTE_ADDR`, filterable via `ink_registration_client_ip`. When no IP resolves, **skip** rate-limiting (return 0 / no-op) instead of sharing one global bucket. New test pins the empty-IP skip. |
| R2 | LOW | `wp ink audit-production` logged "not a production environment" but then **audited anyway**, warning about dev tools on a staging box where they belong. | Added `return;` after the not-production log. |
| R3 | LOW | `wp ink verify-redirects --fix` printed `Kettings platgemaak` (success) even when cyclic residue remained (flatten leaves cycles on their original target). | Warn (not success) when the post-flatten audit is not `ok`, naming the residual cycle count. |
| R4 | LOW | `Analytics` `'preview'` bot-marker was redundant (`ReadCount::maybeCount` already excludes `is_preview()`) and risked false-negatives on real reads. | Removed `'preview'` from `BOT_MARKERS`. |

### Accepted (with rationale)

- **Timing check fails open when `ink_reg_t` is absent/tampered** (Blind: HIGH). Kept
  fail-open: timing is one defense-in-depth layer (honeypot + Turnstile + rate-limit
  remain); failing closed on a missing field would block legitimate third-party/alt
  registration forms that don't render our field. Turnstile (the `ink_registration_challenge_passed`
  seam) is the primary bot defense. Documented in the security runbook.
- **Rate counter counts successful + bot attempts** (MEDIUM/LOW). This is correct
  rate-limit semantics — *N accounts per IP per window*, not *N failures*. The
  false-positive-lockout concern was the shared-IP issue, now fixed (R1).
- **`SchemaTypes::filterJsonLd` assumes a flat, name-keyed node graph and rewrites all
  Article-family nodes** (LOW). Matches Rank Math's documented `rank_math/json_ld`
  filter shape; an INK singular emits one primary article node. Revisit only if a Rank
  Math upgrade changes the graph to `@graph`-nested lists.
- **CacheControl does not auto-bypass logged-in personalised GET pages** (MEDIUM). By
  design (Story 18.5): the LiteSpeed config excludes logged-in users (or uses ESI) and
  the `ink_cache_bypass` filter is the per-page opt-in seam; the URI/block exclusion
  list lives in the runbook, keeping the module Kernel-only.
- **`ReportForm`/`ReportStore` do not validate that the reported `object_id` exists or
  that detail is length-capped** (LOW). Reports are human-moderated; reporting a
  just-deleted object is legitimate; `text` column (64 KB) far exceeds any real report.
- **Story 18.8 deliverables not in the source diff** (LOW, scope note). The
  integration/E2E/CI/live-crawl layers live in `tests/`, `tools/`, `.github/`, and
  root configs — outside the `src/` diff the layers audited. Authored + PHP-linted;
  they execute in CI (wp-env/Playwright/site), not the mocked-unit sandbox.

## Gates after R18 patches

`composer test:unit` ✓ 1124 passed / 1 skipped (+1 for the R1 test) · `composer cs` ✓
(only the 2 pre-existing Engagement slow-query warnings) · `composer stan` ✓ No errors ·
`composer copy:scan` ✓ 8/8 · `composer deptrac` — 0 new violations.

## Pre-existing debt flagged for the retro (NOT from Epic 18)

`deptrac` reports **3 violations** `Ink\Kernel\Activation → Ink\Content\PostTypes`
(`grantContentCaps()`/`revokeContentCaps()` at activation, `Activation.php:73,114`),
present on baseline `ef775c1`. Prior epics read deptrac's "Errors: 0" line as green; the
violations sat below a truncated report tail. Epic-18 adds none. **Retro decision owed:**
move the capability-type constants to Kernel, or add an explicit deptrac allowance for
the activation cap-grant.

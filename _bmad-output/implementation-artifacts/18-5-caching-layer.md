---
baseline_commit: ef775c1
---

# Story 18.5: Caching layer

Status: review

<!-- Note: Validation is optional. Run validate-create-story for quality check before dev-story. -->

## Story

As a site owner,
I want caching,
so that the site is fast. (NFR-3)

## Acceptance Criteria

1. **Given** the host **When** caching is configured **Then** LiteSpeed Cache + Cloudflare edge caching are active (§14.9).
2. **Code deliverable — cache-correctness for INK's dynamic surfaces.** LiteSpeed/Cloudflare are configured platform layers (project-context: do not reimplement). The risk a cache layer introduces is **stale/leaked personalised content**: a logged-in member's personalised surface or a form round-trip must never be served from a shared page cache. A new `Ink\Cache` module marks INK's private/dynamic surfaces non-cacheable.
3. **Pure decision** `shouldBypassCache(array $context): bool` — bypass when the request is an INK `admin-post` action (any action prefixed `ink_` — the contact/report handlers and any future INK form, detected generically without coupling to the Forms module), or when a module has opted the request out via the `ink_cache_bypass` filter. Driven by primitives so it unit-tests without WordPress.
4. **No-cache emission:** on a bypass, signal **both** layers — `nocache_headers()` + `DONOTCACHEPAGE` (generic) and `do_action( 'litespeed_control_set_nocache', … )` (LiteSpeed API). Inert/no-op when LiteSpeed is absent.
5. **Two clean seams instead of cross-module lists:** the generic `ink_` admin-post prefix (a Kernel-level single source — no Forms dependency) and the `ink_cache_bypass` filter (any personalised surface opts its own request out). The block-name/URI exclusion list for LiteSpeed/Cloudflare lives in the **runbook** (it is cache config, not code) so the `Cache` module never reaches into Discovery/Social/Forms constants and stays `Kernel`-only.
6. **Runbook** (`docs/caching-runbook.md`): LiteSpeed Cache settings (public cache on, **logged-in cache + ESI** or exclude-logged-in decision, cache-exclusion of the INK admin-post endpoints + personalised surfaces, object cache), Cloudflare edge-cache rules (cache static, **bypass `wp-admin`/`admin-post.php`/logged-in cookie**), and the purge model (LiteSpeed auto-purge on post save; manual purge on deploy).
7. **Non-vacuous tests:** `shouldBypassCache()` returns true for an INK admin-post action and for a filter-opted-out request, false for an anonymous normal page; the no-cache emitter fires the LiteSpeed action + sets `DONOTCACHEPAGE` when bypassing and does nothing otherwise.
8. **Three-layer + conflation clean:** all logic in `ink-core`; `Ink\Cache` references neither Tiers nor Entitlement (caching is not gated on membership/Gradering). New deptrac layer `Cache` → `Kernel` only (the action/block name lists are INK constants; it does not read other domains).
9. **Gates green:** `composer test:unit`, `composer cs`, `php -l`, `composer stan`, `composer copy:scan` clean; `composer deptrac` no new violations; baseline unchanged.

## Tasks / Subtasks

- [x] Task 1: `Ink\Cache` module skeleton (AC: #2, #8)
  - [x] `src/Cache/Module.php` implements `Kernel\Module`; `register()` wires `CacheControl`.
  - [x] Deptrac layer `Cache` → `[Kernel]`; registered module in `ink-core.php`.
- [x] Task 2: `src/Cache/CacheControl.php` (AC: #3, #4, #5)
  - [x] `ADMIN_POST_PREFIX = 'ink_'` single source; `BYPASS_FILTER = 'ink_cache_bypass'`.
  - [x] Pure `shouldBypassCache(array $context): bool` (`ink_admin_post` || `filtered_bypass`).
  - [x] `register()` hooks `send_headers`; `maybeBypass()` builds the context via overridable `isInkAdminPost()` seam + the bypass filter.
  - [x] `bypass()`: `nocache_headers()` + define `DONOTCACHEPAGE` + `do_action('litespeed_control_set_nocache', …)`.
- [x] Task 3: Runbook (AC: #1, #6)
  - [x] `docs/caching-runbook.md` — LiteSpeed (public/object/browser cache, logged-in ESI-or-exclude, do-not-cache list, purge model), Cloudflare (cache static, bypass wp-admin/admin-post/logged-in-cookie, honour origin no-cache), verify steps.
- [x] Task 4: Tests (AC: #7)
  - [x] `tests/Unit/Cache/CacheControlTest.php` (8).
- [x] Task 5: Gates (AC: #9)
  - [x] `composer test:unit` ✓ (1085 passed, 1 skipped, +8); `composer cs` ✓ (0/0); `php -l` ✓; `composer stan` ✓ (No errors); `composer copy:scan` ✓ (8/8); `composer deptrac` — `Cache → [Kernel]`, no new violations.

## Dev Notes

### What is code vs. ops
LiteSpeed + Cloudflare are configured (runbook). The code job is *cache correctness* — guaranteeing INK's personalised/transactional surfaces opt out so the cache can be aggressive on everything else without leaking a member's data or serving a stale form result. The exclusion lists are exposed as single sources so the LiteSpeed/Cloudflare config in the runbook stays in lockstep with the code.

### Architecture compliance (project-context.md)
- **Don't reimplement plugin capability** — no page-cache engine in ink-core; only no-cache *signalling*.
- **Single source** — the admin-post action names already live on `ContactForm`/`ReportForm`; `adminPostActions()` references those constants, never re-typing them. Personalised block names reference the Discovery/Engagement block constants where available.
- **Conflation rule** — zero Tiers/Entitlement; new deptrac layer `Cache → [Kernel]`.
- **Brain-Monkey isolation** — the request-context reads are overridable seams; the pure decision takes primitives.

### Source tree components to touch
- NEW `src/Cache/Module.php`, `CacheControl.php`
- NEW `tests/Unit/Cache/CacheControlTest.php`
- NEW `docs/caching-runbook.md`
- UPDATE `ink-core.php` (register `cache` module); `deptrac.yaml` (Cache layer)

### References
- [Source: _bmad-output/planning-artifacts/epics.md#Story 18.5] — LiteSpeed + Cloudflare edge caching (§14.9).
- [Source: wp-content/plugins/ink-core/src/Forms/ContactForm.php / ReportForm.php] — the admin-post action constants (cache-exclusion single source).
- [Source: _bmad-output/project-context.md] — LiteSpeed Cache is a platform plugin; Cloudflare edge caching.

## Dev Agent Record

### Agent Model Used

claude-opus-4-8

### Debug Log References

- `composer test:unit -- --filter=CacheControl` → 8 passed.
- `composer test:unit` → 1085 passed, 1 skipped.
- `composer stan` (sandbox off) → No errors.

### Completion Notes List

- `Ink\Cache\CacheControl` is cache-correctness only: it bypasses the page cache for
  INK admin-post round-trips (generic `ink_` prefix) and any surface that opts in via
  `ink_cache_bypass`, signalling both LiteSpeed (`litespeed_control_set_nocache`) and
  the generic cache (`DONOTCACHEPAGE` + `nocache_headers()`).
- Deliberately Kernel-only: the block/URI exclusion list is runbook config, not code,
  so Cache never couples to Forms/Discovery/Social constants.
- LiteSpeed + Cloudflare configuration + purge model are the runbook.

### File List

- NEW `wp-content/plugins/ink-core/src/Cache/Module.php`
- NEW `wp-content/plugins/ink-core/src/Cache/CacheControl.php`
- NEW `tests/Unit/Cache/CacheControlTest.php`
- NEW `docs/caching-runbook.md`
- UPDATE `wp-content/plugins/ink-core/ink-core.php` (register `cache` module)
- UPDATE `deptrac.yaml` (Cache layer + `Cache → [Kernel]`)
- UPDATE `_bmad-output/implementation-artifacts/sprint-status.yaml` (18.5 status)

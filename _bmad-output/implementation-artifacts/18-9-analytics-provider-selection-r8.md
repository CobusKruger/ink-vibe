---
baseline_commit: ef775c1
---

# Story 18.9: Analytics-provider selection (R8)

Status: review

<!-- Note: Validation is optional. Run validate-create-story for quality check before dev-story. -->

## Story

As a site owner,
I want an analytics provider selected,
so that read counts (9.12) have a data source. (FR-44b, R8)

## Acceptance Criteria

1. **Given** no analytics exists today **When** a provider is chosen **Then** a vetted-plugin analytics seam is wired (not reimplemented in `ink-core`); read counts surface on My Profiel via 9.12 **And** it sharpens the POPIA question (OQ-3), flagged to be addressed sooner.
2. **Provider-agnostic seam** (`Ink\Discovery\Analytics`) — ink-core does NOT implement analytics; the chosen vetted plugin wires in via filters/actions: `ink_analytics_provider_active`, `ink/analytics_record_view`, `ink_analytics_view_count`. With no provider, ink-core's existing `_ink_read_count` counter is the fallback.
3. **Hardening (Story 8.3 deferred → done here):** `shouldRecordView()` drops obvious bots (user-agent tripwire) and an author viewing their **own** work before recording — so the counts (fallback or provider) mean something. `ReadCount::maybeCount()` routes through this.
4. **My Profiel (9.12) keeps surfacing read counts** — unchanged when no provider is active (the surface reads the `_ink_read_count` meta, which is the seam's default source); `viewCount()` is the read seam a provider transparently overrides.
5. **Decision documented** (`docs/analytics-provider-decision.md`): a privacy-respecting, cookieless, self-hosted plugin (Burst Statistics / Independent Analytics), chosen for POPIA fit; how the plugin wires the seam on staging; and the OQ-3 sharpening (basic counts = low risk; per-user analytics would trigger POPIA consent — flagged).
6. **Non-vacuous tests:** `isBot()` flags crawlers + empty UA, not a browser; `shouldRecordView()` records a human reader, excludes the author's self-view + bots; `recordView()` hands off to the provider (no ink-core bump) when active and falls back to the counter when not; `viewCount()` defaults to the meta and honours a provider override; `maybeCount()` records a human view and does NOT count a bot.
7. **Three-layer + conflation clean:** logic in `ink-core` Discovery module (where read counts live); references neither Tiers nor Entitlement. No new deptrac edge (Discovery layer; `Analytics` uses `ReadCount` in-module + WP).
8. **Gates green:** `composer test:unit`, `composer cs`, `php -l`, `composer stan`, `composer copy:scan`, `composer deptrac` (no new violations); baseline unchanged.

## Tasks / Subtasks

- [x] Task 1: `src/Discovery/Analytics.php` — the seam + hardening (AC: #2, #3, #4)
  - [x] Pure `isBot()`, `shouldRecordView()`; provider seams `providerActive()`, `recordView()` (provider hand-off via `ink/analytics_record_view`, else `ReadCount` fallback), `viewCount()` (`ink_analytics_view_count`, default `_ink_read_count`).
- [x] Task 2: Wire `ReadCount::maybeCount()` (AC: #3) — bot/self-view guard + `Analytics::recordView()`; overridable `userAgent()`/`viewerId()` seams.
- [x] Task 3: Decision doc (AC: #1, #5) — `docs/analytics-provider-decision.md`.
- [x] Task 4: Tests (AC: #6) — `tests/Unit/Discovery/AnalyticsTest.php` (10) + updated `ReadCountTest` (human-records + bot-excluded).
- [x] Task 5: Gates (AC: #8) — `composer test:unit` ✓ (1113 passed, 1 skipped, +10); `composer cs` ✓ (the `ink/...` hook-name warning suppressed per the AD convention, as in Tiers); `php -l` ✓; `composer stan` ✓ (No errors); `composer copy:scan` ✓ (8/8); `composer deptrac` — no new violations.

## Dev Notes

### What is code vs. ops
The *provider choice* is a documented decision (privacy/POPIA constraints). The *code*
is the seam that lets the vetted plugin own the data without ink-core reimplementing
analytics — plus the bot/self-view hardening Story 8.3 explicitly deferred to 18.9. The
fallback counter keeps read counts working before/without a provider.

### Architecture compliance (project-context.md)
- **Don't reimplement plugin capability** — no analytics engine in ink-core; only a seam.
- **Read counts live in Discovery** — `Analytics` sits there with `ReadCount`; no new deptrac edge.
- **Conflation rule** — zero Tiers/Entitlement (reading is open).
- **`ink/...` event convention** — `do_action('ink/analytics_record_view')` with the AD-convention phpcs suppression (mirrors `ink/tier_promoted`).
- **Brain-Monkey isolation** — pure decisions take primitives; `userAgent()`/`viewerId()` are overridable seams.

### Source tree components to touch
- NEW `wp-content/plugins/ink-core/src/Discovery/Analytics.php`
- UPDATE `wp-content/plugins/ink-core/src/Discovery/ReadCount.php` (route through Analytics + seams)
- NEW `tests/Unit/Discovery/AnalyticsTest.php`; UPDATE `tests/Unit/Discovery/ReadCountTest.php`
- NEW `docs/analytics-provider-decision.md`

### References
- [Source: _bmad-output/planning-artifacts/epics.md#Story 18.9] — vetted-plugin seam; read counts (9.12); POPIA OQ-3.
- [Source: wp-content/plugins/ink-core/src/Discovery/ReadCount.php] — the 8.3 counter + its "18.9 hardening" deferral note.
- [Source: wp-content/plugins/ink-core/src/Discovery/ReadCountSurface.php] — the My Profiel read-count surface (9.12).
- [Source: _bmad-output/project-context.md] — don't reimplement plugin capability; analytics seam not in ink-core.

## Dev Agent Record

### Agent Model Used

claude-opus-4-8

### Debug Log References

- `composer test:unit -- --filter="Analytics|ReadCount"` → 20 passed.
- `composer test:unit` → 1113 passed, 1 skipped.
- `composer stan` (sandbox off) → No errors.

### Completion Notes List

- `Ink\Discovery\Analytics` is the provider-agnostic seam + the bot/self-view hardening
  8.3 deferred here. Provider active → `ink/analytics_record_view` hand-off (no ink-core
  bump); absent → the `ReadCount` fallback counter. `viewCount()` is the read seam
  (default `_ink_read_count`), so My Profiel (9.12) transparently picks up a provider.
- Provider decision (privacy-respecting, cookieless, self-hosted) documented with the
  OQ-3/POPIA sharpening — basic counts low-risk; per-user analytics flagged.
- Discovery-located → no new deptrac layer/edge. Conflation-clean.

### File List

- NEW `wp-content/plugins/ink-core/src/Discovery/Analytics.php`
- UPDATE `wp-content/plugins/ink-core/src/Discovery/ReadCount.php`
- NEW `tests/Unit/Discovery/AnalyticsTest.php`
- UPDATE `tests/Unit/Discovery/ReadCountTest.php`
- NEW `docs/analytics-provider-decision.md`
- UPDATE `_bmad-output/implementation-artifacts/sprint-status.yaml` (18.9 status)

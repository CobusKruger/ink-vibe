---
baseline_commit: 1c16256891a6dc57ec14033ca7ce9f2ab62f5363
---

# Story 14.2: Scheduling / rotation logic

Status: done

<!-- R14 code review (epic-14-code-review-2026-06-28.md): 0 HIGH/MEDIUM. 2 LOW patches — inverted window (start>end) made an intentional fail-closed contract + pinning test; rotation docblock softened (within-day stability holds for a constant active set). Deferred: per-request WP_Query caching (Epic 18), MAX_SPONSORS=100 cap (documented product bound). -->


<!-- Note: Validation is optional. Run validate-create-story for quality check before dev-story. -->

## Story

As a redakteur,
I want campaign-date-driven display,
so that sponsors show only in their window. (FR-58)

## Acceptance Criteria

**Given** campaign dates
**When** display is computed
**Then** sponsors show within their window with rotation; dates are inclusive of start and end (single-day start==end shows that day).

Decomposed:

1. The SAST boundary single source (`Kernel\Sast`) gains a **start-of-day** primitive + a **two-sided inclusive window** predicate — the reuse the carry-forward mandates ("reuse `Kernel\Sast` for the sponsor campaign-window math; don't hand-roll date boundaries"). `Sast::startOfDay(\DateTimeInterface): \DateTimeImmutable` returns `00:00:00` on the instant's SAST calendar day (the inclusive lower bound; the exact mirror of the existing `endOfDay()` which returns `23:59:59`). `Sast::isWithinDayRange(?\DateTimeInterface $start, ?\DateTimeInterface $end, ?\DateTimeInterface $now = null): bool` returns true iff `now >= startOfDay($start)` (when `$start` given) AND `now <= endOfDay($end)` (when `$end` given) — a **null** bound means unbounded on that side. Both boundaries are inclusive; `start == end` yields that single SAST day's `00:00:00 … 23:59:59` window.
2. A new `Sponsors\Campaign` scheduler owns the sponsor-window + rotation logic, reusing `Kernel\Sast` (zero hand-rolled date maths):
   - Pure `parseDate(string $ymd): ?\DateTimeImmutable` — parse a stored `Y-m-d` borg date as a SAST instant (the `Challenges\Deadline::parse` precedent, but Sponsors-owned to avoid a cross-module edge); `null` for `''`/invalid.
   - Pure `isActive(Sponsor $sponsor, ?\DateTimeImmutable $now = null): bool` — parse the sponsor's `startDate`/`endDate` and delegate to `Sast::isWithinDayRange`. **Policy:** an empty start = no lower bound; an empty end = no upper bound; **both empty = evergreen (always active)**. Documented in the method.
   - Pure `dayIndex(\DateTimeInterface $now): int` — the whole-days-since-epoch index (`floor(timestamp / 86400)`), the rotation cursor that advances once per calendar day.
   - Pure `rotate(list<Sponsor> $active, int $dayIndex): ?Sponsor` — `$active[ $dayIndex % count ]` (deterministic daily rotation over the active set), `null` for an empty set. Negative-safe modulo.
   - Pure `activeFrom(list<Sponsor> $sponsors, ?\DateTimeImmutable $now = null): list<Sponsor>` — filter a given list to those `isActive` (re-indexed list).
3. `Campaign` exposes the WP-touching surfaces (thin wrappers over the pure layer, the house-style split):
   - `queryArgs(): array` — pure `WP_Query` args for **published `borg`**, newest-first (`post_status=publish`, `orderby=date DESC`, `ignore_sticky_posts=true`), all sponsors (no `posts_per_page` cap beyond a sane bound — sponsors are few). Reads the CPT slug from `Content\PostTypes::BORG` (single source).
   - `activeSponsors(?\DateTimeImmutable $now = null): list<Sponsor>` — run the query, map each post via `Sponsor::forPost`, return `activeFrom(...)`. WP-touching wrapper; its pure pieces carry the assertions.
   - `featured(?\DateTimeImmutable $now = null): ?Sponsor` — `rotate( activeSponsors($now), dayIndex($now ?? Sast::now()) )`; the single "which sponsor shows now" entry point the homepage strip (14.3) consumes.
4. The `Sponsors\Api` facade gains `activeSponsors(?\DateTimeImmutable $now = null): list<Sponsor>` and `featuredSponsor(?\DateTimeImmutable $now = null): ?Sponsor` (delegating to `Campaign`) — the sole cross-module surface (AD-1) the 14.3 homepage strip + 14.4 recognition section consume. No new `Api` window logic — it only delegates.
5. Conflation-clean: `Sponsors` reads only `Ink\Content` (the `borg` CPT slug) + `Ink\Kernel` (`Sast`/`Scalar`) + WP core — **zero** `Ink\Tiers`/`Ink\Entitlement`. The scheduling decision is editorial (campaign dates), never gated on tier/membership. No new deptrac edge (the `Sponsors -> Content` + `Sponsors -> Kernel` edges from 14.1 already cover this).
6. The `Module::register()` stays a no-op at 14.2 (scheduling is pure read logic consumed on demand by 14.3/14.4 render hooks — there is nothing to hook yet).

## Tasks / Subtasks

- [x] Task 1: Extend `Kernel\Sast` (AC: 1)
  - [x] Subtask 1.1: `startOfDay(\DateTimeInterface): \DateTimeImmutable` — mirror `endOfDay()` exactly but `setTime( 0, 0, 0 )`.
  - [x] Subtask 1.2: `isWithinDayRange( ?$start, ?$end, ?$now = null ): bool` — inclusive both ends; null = unbounded; reuse `startOfDay`/`endOfDay`/`now`.
  - [x] Subtask 1.3: SastTest additions — startOfDay wall-clock + UTC instant + SAST-day-not-UTC; isWithinDayRange matrix (before/at-start, at/after-end, single-day, open-start, open-end, both-open).
- [x] Task 2: `Sponsors\Campaign` scheduler (AC: 2, 3, 5)
  - [x] Subtask 2.1: pure `parseDate`, `isActive`, `dayIndex`, `rotate`, `activeFrom`.
  - [x] Subtask 2.2: `queryArgs` (published borg newest-first, slug from `PostTypes::BORG`).
  - [x] Subtask 2.3: thin `activeSponsors` + `featured` WP wrappers.
- [x] Task 3: `Sponsors\Api` facade additions (AC: 4) — `activeSponsors()` + `featuredSponsor()` delegating to `Campaign`.
- [x] Task 4: Tests (AC: 2, 3, 4) — `tests/Unit/Sponsors/CampaignTest.php` (isActive window matrix incl. single-day + open bounds + evergreen; parseDate happy/empty/invalid; dayIndex per-SAST-day + rotate cycles + empty/negative; activeFrom filters + re-indexes; queryArgs shape) + `tests/Unit/Sponsors/ApiTest.php` additions (activeSponsors/featuredSponsor delegation, exercised via a new minimal `WP_Query` stub so the wiring is non-vacuous).
- [x] Task 5: Gates — `composer test:unit`, `composer cs`, `composer stan`, `composer deptrac`, `composer copy:scan` all green; counts in Completion Notes.

## Dev Notes

- **Reuse `Kernel\Sast` — do NOT hand-roll date boundaries (carry-forward 🔴).** The existing `Sast::endOfDay()`/`isThroughEndOfDay()` are the AD-2/AD-3 single source for "end of day SAST" (the Challenges deadline reuses them). 14.2 adds the symmetric `startOfDay()` + a two-sided `isWithinDayRange()` to the SAME Kernel helper so the sponsor window is computed with the project's one timezone authority (Africa/Johannesburg, UTC+2, no DST) — exactly the "analogue of the Challenges SAST deadline work" the carry-forward calls for. [Source: src/Kernel/Sast.php:39-104; _bmad-output/implementation-artifacts/13-... carry-forward; epics.md#Story 14.2]
- **Inclusive both ends; single-day works.** AC: "dates are inclusive of start and end (single-day start==end shows that day)." `startOfDay(start) = 00:00:00 SAST`, `endOfDay(end) = 23:59:59 SAST`; for `start == end` that is the full `00:00:00 … 23:59:59` of that one SAST day. The `endOfDay` boundary is already inclusive (`now <= endOfDay`); make `startOfDay` inclusive too (`now >= startOfDay`). Mirror the existing `isThroughEndOfDay` before/at/after boundary test for the new start boundary. [Source: src/Kernel/Sast.php:88-104; tests/Unit/Kernel/SastTest.php:83-94]
- **Empty-bound policy (decide + document):** borg start/end are optional meta. An empty start ⇒ no lower bound (active up to the end); an empty end ⇒ no upper bound (active from the start onward); BOTH empty ⇒ evergreen sponsor, always active. This is the most useful editorial behaviour (an evergreen patron with no campaign window still shows). `isWithinDayRange` models it directly via nullable bounds. [Source: this story AC-2]
- **Rotation is deterministic + daily, injectable for tests.** "with multiple active it rotates" (14.3 AC) — `rotate()` selects `$active[ dayIndex % count ]`, where `dayIndex` advances once per calendar day, so the featured sponsor cycles through the active set day by day and is STABLE within a day/render (no per-request fl! no `Math.random`). `now` is injectable end-to-end (the `Sast::now()` precedent) so the unit suite pins it. Use a negative-safe modulo (`(($i % $n) + $n) % $n`) even though `dayIndex` is non-negative post-1970. [Source: src/Kernel/Sast.php:80-86 (injectable now); epics.md#Story 14.3]
- **House-style query split (test the OUTCOME).** Mirror `Challenges\Archive`/`Library\Archive`: a pure `queryArgs()` returning the INK-built `WP_Query` args (unit-asserted) + a thin `new \WP_Query(queryArgs())` wrapper (`activeSponsors`) that is NOT unit-tested against a WP_Query mock (no WP_Query stub exists; testing a mock proves nothing). The window filter runs in the pure `activeFrom()`/`isActive()` which ARE unit-tested. [Source: src/Challenges/Archive.php:85-101,146-159; tests/Unit/Challenges/ArchiveTest.php:37-50; project-context "Test the OUTCOME, not the arg-shape"]
- **Date parse stays Sponsors-owned.** `Challenges\Deadline::parse` is the precedent but lives in the Challenges module — depending on it would add a `Sponsors -> Challenges` edge for a 6-line parse. Re-implement the tiny `parseDate` in `Campaign` (or as a private helper) reading the SAST tz from `Sast::TIMEZONE`. borg dates are `Y-m-d` (no time component; `FieldSets::sanitizeDate`). [Source: src/Challenges/Deadline.php:37-47; src/Content/FieldSets.php:360-368]
- **Conflation rule:** scheduling is editorial (campaign dates) — NO tier/entitlement anywhere. Keep `Sponsors` free of `Ink\Tiers`/`Ink\Entitlement` (the 14.1 deptrac edge set is unchanged — `Sponsors -> Content` + `Sponsors -> Kernel` only). [Source: deptrac.yaml (Sponsors); project-context "THE conflation rule"]
- **Testing rules (standing):** inject `now` to keep window/rotation deterministic (never assert against the wall clock — the SastTest `injected now` precedent). Guardrail/window tests must be non-vacuous — prove a date OUTSIDE the window returns false AND one inside returns true. Test the INK-owned outcome (the bool, the selected Sponsor, the queryArgs we build). [Source: project-context "Testing Rules"; tests/Unit/Kernel/SastTest.php:120-129]

### Project Structure Notes

- New: `wp-content/plugins/ink-core/src/Sponsors/Campaign.php`, `tests/Unit/Sponsors/CampaignTest.php`.
- Modified: `wp-content/plugins/ink-core/src/Kernel/Sast.php` (startOfDay + isWithinDayRange), `wp-content/plugins/ink-core/src/Sponsors/Api.php` (activeSponsors + featuredSponsor), `tests/Unit/Kernel/SastTest.php` (new boundary tests), `tests/Unit/Sponsors/ApiTest.php` (delegation tests).
- No theme files in 14.2 (the homepage strip is 14.3). No new meta, no new CPT, no new Terms keys, no new deptrac edge.

### References

- [Source: _bmad-output/planning-artifacts/epics.md#Story 14.2]
- [Source: _bmad-output/planning-artifacts/prds/prd-ink-vibe-2026-06-14/prd.md#FR-58]
- [Source: wp-content/plugins/ink-core/src/Kernel/Sast.php] — the SAST boundary single source to extend
- [Source: wp-content/plugins/ink-core/src/Challenges/Archive.php + Deadline.php] — queryArgs split + date-parse precedent
- [Source: wp-content/plugins/ink-core/src/Sponsors/Sponsor.php + Api.php] — the 14.1 read-model + facade to build on
- [Source: tests/Unit/Kernel/SastTest.php] — boundary-test pattern to mirror for the start boundary

## Dev Agent Record

### Agent Model Used

claude-opus-4-8

### Debug Log References

- Red→green caught a real timezone bug: the first `dayIndex` floored the raw UTC timestamp (`floor(getTimestamp()/86400)`), so an instant at `2026-06-22 23:00 SAST` (21:00 UTC) landed on a different index than `2026-06-22 00:00 SAST` (the previous UTC day) — splitting one SAST day across two rotation slots. Fixed to anchor on `Sast::startOfDay($now)` so the cursor is per-SAST-day (every instant in a SAST day → same index; consecutive SAST days differ by exactly 1, SAST having no DST).

### Completion Notes List

- Extended the Kernel SAST single source (`Sast`) with the symmetric `startOfDay()` (00:00:00 SAST, the inclusive lower bound — exact mirror of the existing `endOfDay()`) and a two-sided inclusive window predicate `isWithinDayRange(?start, ?end, ?now)` (null bound = unbounded that side; start==end = that whole SAST day). This is the carry-forward-mandated "reuse `Kernel\Sast` for the sponsor campaign-window math — don't hand-roll date boundaries"; the sponsor window now uses the project's one timezone authority, the analogue of the Challenges AD-3 deadline reuse.
- Built `Sponsors\Campaign` — the campaign-window + rotation logic, split the house-style way (pure layers carry the assertions; thin WP wrappers only query + delegate): pure `parseDate` (Sponsors-owned `Y-m-d`→SAST, no `Sponsors -> Challenges` edge), `isActive` (delegates to `Sast::isWithinDayRange`; evergreen when both dates empty), `activeFrom` (filter + re-index), `dayIndex` (per-SAST-day cursor), `rotate` (`$active[dayIndex % count]`, negative-safe, null for empty), `queryArgs` (published borg newest-first, slug from `PostTypes::BORG`); thin `activeSponsors`/`featured` WP_Query wrappers.
- `Sponsors\Api` gained `activeSponsors()` + `featuredSponsor()` — pure delegators to `Campaign`, the cross-module surfaces 14.3 (homepage strip) + 14.4 (recognition section) consume.
- **Rotation is deterministic + daily + injectable** — the featured sponsor cycles through the active set one step per SAST day and is stable within a day/render (no `rand`, no per-request churn); `now` is injectable end-to-end (the `Sast::now()` precedent), so the window + rotation maths is pinned in the unit suite.
- **New test infra:** added a minimal `WP_Query` test double (`tests/stubs/class-wp-query.php`, wired in `tests/bootstrap.php` behind a `class_exists` guard, same convention as the WP_Post/WP_User/WP_Term/WP_Error doubles) so the thin `activeSponsors`/`featured` wrappers + the Api delegation are exercised end-to-end (post → `Sponsor::forPost` → window filter → rotate) without a real DB — staged via a `WP_Query::$ink_test_posts` static the test sets. The integration suite (18.8) uses the real WP_Query.
- Conflation-clean: NO new deptrac edge — `Sponsors -> Content` + `Sponsors -> Kernel` (from 14.1) already cover this; zero Tiers/Entitlement (scheduling is editorial). `Module::register()` stays a no-op (nothing to hook until the 14.3/14.4 render surfaces).
- **Gates:** `composer test:unit` → 896 passed / 1 skipped (+18: 12 Campaign, 6 Sast boundary; 3 Api delegation), zero regressions; `composer cs` → 0 errors on the changed files (repo-wide: only the 2 documented pre-existing slow-query WARNINGS); `composer stan` → No errors (150 files); `composer deptrac` → 3 violations = the documented PRE-EXISTING `Kernel\Activation -> Content\PostTypes` baseline, **no new edge**; `composer copy:scan` → no new placeholder debt (14.2 adds no user-facing copy).

### File List

- `wp-content/plugins/ink-core/src/Kernel/Sast.php` (modified — startOfDay() + isWithinDayRange())
- `wp-content/plugins/ink-core/src/Sponsors/Campaign.php` (new)
- `wp-content/plugins/ink-core/src/Sponsors/Api.php` (modified — activeSponsors() + featuredSponsor() delegations)
- `tests/Unit/Kernel/SastTest.php` (modified — startOfDay + isWithinDayRange tests)
- `tests/Unit/Sponsors/CampaignTest.php` (new)
- `tests/Unit/Sponsors/ApiTest.php` (modified — campaign-window delegation tests)
- `tests/stubs/class-wp-query.php` (new — minimal WP_Query unit double)
- `tests/bootstrap.php` (modified — load the WP_Query stub behind a class_exists guard)

## Change Log

- 2026-06-28: Story 14.2 implemented — extended `Kernel\Sast` with startOfDay + a two-sided inclusive window predicate; built `Sponsors\Campaign` (window + daily rotation, pure layers + thin WP wrappers) + Api delegations; added a minimal WP_Query unit double. Status → review.

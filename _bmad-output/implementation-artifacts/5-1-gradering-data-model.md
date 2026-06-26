---
baseline_commit: 4f5e43d711c8d4c6467336ba23e46afac09e160a
---

# Story 5.1: Gradering data model

Status: done

<!-- Note: Validation is optional. Run validate-create-story for quality check before dev-story. -->

## Story

As an ink-core developer,
I want the Gradering enum modelled with Meester plus a typed, default-safe read path,
so that writer tiers have a typed, persisted store consumers can read without touching raw user-meta. (FR-11, R3)

## Acceptance Criteria

1. **The Kernel `Tier` enum models all four grades with Meester as a manual-only, never-auto-promoted terminal state, and `brons` as the single-source default.** Given the Kernel `Tier` enum, when a writer's Gradering is read, then `ink_writer_tier` ∈ {brons, silwer, goud, **meester**} with `brons` the default, and the enum exposes the *semantics* that Meester is manual-only and terminal for auto-promotion (it is never produced by the auto engine — Story 5.8) while keeping `Ink\Tiers ⟂ Ink\Entitlement` (THE conflation rule). The brand red-orange `primary #EA4015` rendering of Meester (not `danger`) is presentation, owned by Story 5.4 — this story encodes only the manual-only/terminal *data-model* semantics that 5.4/5.8 read. _[Source: epics.md#Story-5.1 AC; architecture.md AD-3 dec.6 (`meester` manual-only terminal, no threshold), lines 369-370 (user-meta value set incl. `meester`); src/Kernel/Tier.php (the enum, Meester already a case since 1.7); project-context.md ("Model fixed value sets as enums … the string is the persisted DB value; never duplicate these literals")]_

2. **A typed tier accessor guarantees the `brons` default for unset/empty/junk users — consumers never read raw `get_user_meta`.** Given any user id (including one whose `ink_writer_tier` was never written), when a consumer reads the writer's Gradering through the typed accessor, then it returns a `Tier` enum case, returning `Tier::Brons` for an unset/empty value and coercing any unrecognised stored string back to `Tier::Brons` — never `null`, never a raw string. This closes the **Epic-2 review deferral (2.3)**: the `register_meta` `default` only resolves on WP's default-aware read path, so a raw `get_user_meta($id, 'ink_writer_tier', true)` of an unset user returns `''`, not `brons`; consumers must use this accessor instead. _[Source: deferred-work.md "Deferred from: code review of Story 2.3 — user-meta" ("`brons` default only resolves on the WP registered-default read path … Epic-5 consumers should read tier via a typed accessor, not raw `get_user_meta`. Provide with the consumer."); epics.md#Story-5.1 "Deferred from Epic 2 review"; src/Content/UserMeta.php:71 (the registered default + `sanitizeTier` coercion precedent)]_

3. **The accessor lives in the `Ink\Tiers` module facade and is conflation-clean — it reads only Kernel + WordPress, never `Ink\Entitlement` and never another domain module.** Given the `Ink\Tiers\Api` facade (reserved since 1.7), when the typed accessor is added, then it lives on `Ink\Tiers\Api` (the sole public Tiers surface, AD-1), references zero `Ink\Entitlement\*` and zero other domain module, and the meta-key single-source it reads is **Kernel-owned** so `Tiers` keeps its deptrac allowlist of `[Kernel]` only (a `Tiers → Content` edge is NOT allowed). The `ink_writer_tier` / `ink_tier_promoted_at` meta-key literals are promoted to a Kernel single-source that BOTH `Ink\Content\UserMeta` (the registrar) and `Ink\Tiers\Api` (the reader) reference, exactly as the `Tier` enum itself is Kernel-owned "so both the Tiers module and the Challenges module can read it from the shared Kernel without creating an inter-module dependency edge". `Ink\Content\UserMeta::WRITER_TIER` / `::TIER_PROMOTED_AT` remain valid as aliases of the Kernel constants (no consumer/test breakage). _[Source: architecture.md AD-1 (module = dir + bootstrap + facade; the conflation rule `Entitlement ⟂ Tiers`), lines 269-285 (`Tiers::promote()` the sole write path; Challenges reads Tiers via the API); deptrac.yaml (`Tiers: [Kernel]` only — no Content edge); src/Kernel/Tier.php (Kernel-owned value type, the same single-source rationale); src/Tiers/Api.php (the reserved facade)]_

4. **WP-house-rules + conflation-clean + authored AND PASSING Pest tests.** Given the project rules, when this story is built, then: every touched `.php` keeps `<?php` + `declare(strict_types=1)` + correct `namespace` + `defined('ABSPATH')||exit;`; classes PascalCase / methods camelCase; the meta keys stay `ink_`-prefixed single-source constants; no raw `$_POST`/`$_GET`, no raw SQL; `Ink\Tiers` carries zero `Ink\Entitlement` / `Ink\Tiers→Content` reference. Pest unit tests are authored at `tests/Unit/Kernel/` (enum semantics) and `tests/Unit/Tiers/` (the typed accessor, incl. unset → brons, junk → brons, each valid grade round-trips) **and run with `composer test:unit`; the full suite passes before the story is marked done** (baseline 270 passed / 1 skipped — zero regressions). `composer cs` / `stan` / `deptrac` are run and recorded; deptrac must stay green with NO new `Tiers` edge. _[Source: project-context.md (strict types; prefix everything; single-source enums; no raw superglobals/SQL; **testing rule 2026-06-22** author *and run* Pest, suite passes before done; THE conflation rule); architecture.md AD-8 (Deptrac enforces `Entitlement ⟂ Tiers`); deptrac.yaml]_

## Tasks / Subtasks

> **Current state (read before starting):**
> - **The `Tier` enum already exists and already has the four cases** including `Meester` (`src/Kernel/Tier.php`, Kernel-owned since 1.7). It is currently a pure value type with NO methods ("No behaviour is attached at 1.7"). Epic 5 is explicitly when behaviour is attached. Brons is the conceptual default (encoded today only in `UserMeta`'s `register_meta` `default` + `sanitizeTier`).
> - **`Ink\Tiers\Api` and `Ink\Tiers\Module` are RESERVED skeletons** (1.7) with NO methods / a no-op `register()`. This story adds the FIRST real method to `Api` (the typed accessor). `Module::register()` stays a no-op — the accessor is a synchronous facade call (AD-6: "synchronous, returns a value → direct facade call"), it needs no hook. **Do NOT add an `addModule`/bootstrap edit** (the Tiers module is already registered in `ink-core.php`; verify, do not duplicate).
> - **The meta is registered by `Ink\Content\UserMeta` (Story 2.3)** with the key literals `'ink_writer_tier'` / `'ink_tier_promoted_at'` as `UserMeta` constants, `default => Tier::Brons->value`, and a `sanitizeTier()` that coerces junk → brons on WRITE. The gap this story closes is the READ side for unset users (raw `get_user_meta` returns `''`, not `brons`).
> - **Deptrac allows `Tiers: [Kernel]` ONLY.** A `Tiers → Content` edge would FAIL the build. That is why the meta-key single-source must move to Kernel (Task 1), so the Tiers reader and the Content registrar both depend only on Kernel.
> - **`ink_tier_win_count` is NOT in scope** — it is Story 5.7. Do not register it here. The promotion log table (5.3), the admin UI (5.2), `Tiers::promote()` (5.7/5.8) and the engine (5.8) are LATER stories — this story is the data model + read path only.
> - **Tests run now** (`composer test:unit`; Brain Monkey, no WP/DB). `tests/Unit/Content/UserMetaTest.php` already asserts `UserMeta::WRITER_TIER === 'ink_writer_tier'`, the brons default, and the sanitize coercion — keep those green (the Kernel-alias must preserve the constant values).
>
> **Scope is the DATA MODEL + typed read path ONLY.** Do NOT build: the admin set/adjust UI (5.2), the promotion-log table (5.3), profile display / the `primary #EA4015` rendering (5.4), discovery/winner labels (5.5), the conflation unit-test guardrail suite (5.6 — though this story must not violate it), win-count meta (5.7), `Tiers::promote()` or the auto engine (5.8), the wins-needed subtext (5.9), or the congratulation email (5.10).

- [x] **Task 1 — Promote the tier meta-key single-source to Kernel (AC: 3)**
  - [x] Added `Tier::META_KEY = 'ink_writer_tier'` + `Tier::PROMOTED_AT_META_KEY = 'ink_tier_promoted_at'` as Kernel-owned class constants on the `Ink\Kernel\Tier` enum (the existing Kernel single source for the value set). Kept them on the enum rather than a new `TierMeta` holder — minimal surface, and `ink_tier_win_count` (5.7) can join the same home.
  - [x] `Ink\Content\UserMeta::WRITER_TIER` / `::TIER_PROMOTED_AT` are now `= Tier::META_KEY` / `= Tier::PROMOTED_AT_META_KEY` aliases. Literal values unchanged → `UserMetaTest` stays green, no migration. `UserMeta` already imported `Ink\Kernel\Tier`; no new edge.
- [x] **Task 2 — Attach data-model semantics to the `Tier` enum (AC: 1)**
  - [x] Added `Tier::default(): self` → `Brons`.
  - [x] Added `Tier::isManualOnly(): bool` → true only for `Meester`.
  - [x] Added `Tier::isAutoPromotable(): bool` → true for `Brons`/`Silwer`, false for `Goud`/`Meester` (match-exhaustive). The 5/15 thresholds stay in Story 5.8.
  - [x] Enum kept presentation-free (no hex, no labels). Docblock updated to record Epic-5 behaviour attachment + the `Ink\Tiers ⟂ Ink\Entitlement` rule.
- [x] **Task 3 — Add the typed, default-safe accessor to `Ink\Tiers\Api` (AC: 2, 3)**
  - [x] Added `Api::forUser( int $user_id ): Tier` reading `get_user_meta( $user_id, Tier::META_KEY, true )`, returning `Tier::default()` for non-scalar/empty and `Tier::tryFrom() ?? Tier::default()` otherwise. Documented as the 2.3-deferral-closing consumer read path.
  - [x] Skipped the optional `tierValueForUser()` string convenience — the enum is the contract; a string getter adds no value and would invite raw-string consumers.
  - [x] `Api` references only `Ink\Kernel\Tier` + WP `get_user_meta`. `Module::register()` left a no-op (synchronous facade call needs no hook). Verified `ink-core.php` already registers the Tiers module — no `addModule` added.
- [x] **Task 4 — Author AND run the Pest tests; record the quality gates (AC: 4)**
  - [x] `tests/Unit/Kernel/TierTest.php` — 6 tests: backing strings + 4 cases, `default()`, `isManualOnly()`, `isAutoPromotable()`, the meta-key constants.
  - [x] `tests/Unit/Tiers/ApiTest.php` — unset→Brons, non-scalar→Brons, junk→Brons, each valid grade round-trips (4-row dataset incl. meester), and reads the single Kernel key by user id.
  - [x] `composer test:unit` → **283 passed / 1 skipped** (1288 assertions); baseline 270/1 → **+13 new, zero regressions**, UserMetaTest green. `composer cs` (3 touched files) clean. `composer stan` clean (sandbox-off; the phpstan TCP-server EPERM caveat). `composer deptrac` → 3 violations ALL pre-existing (`Ink\Kernel\Activation → Ink\Content\PostTypes`, lines 12/73/87), **zero new, no `Tiers → Content` edge, `Entitlement ⟂ Tiers` holds**.

## Dev Notes

- **Why the meta key moves to Kernel:** the deferred-2.3 accessor must live with its consumer (Tiers), but `deptrac.yaml` allows `Tiers: [Kernel]` only — reading `Ink\Content\UserMeta::WRITER_TIER` from `Tiers` would add a forbidden `Tiers → Content` edge. The `Tier` enum is already Kernel-owned for exactly this reason ("so both the Tiers module and the Challenges module can read it from the shared Kernel without creating an inter-module dependency edge"); the meta KEY is the storage identity of that value and belongs in the same single-source. `UserMeta`'s constants become aliases so nothing downstream changes.
- **What "unset returns brons" really fixes:** `register_meta(..., 'default' => 'brons')` only injects the default through WP's default-aware read paths; a bare `get_user_meta($id, 'ink_writer_tier', true)` for a user who never had a tier written returns `''`. Every Epic-5 consumer (5.4 profile display, 5.5 discovery, 5.8 engine, 5.9 subtext) must read through `Api::forUser()` so an unmigrated/new user reads as Brons, not empty.
- **Conflation rule is load-bearing here:** `Ink\Tiers` must never reference `Ink\Entitlement`, and this story adds the first real Tiers code — keep `Api::forUser()` to Kernel + `get_user_meta` only. Story 5.6 will add the structural guardrail tests; do not pre-empt them, but do not violate them either.
- **No presentation in the enum:** the `primary #EA4015` Meester colour (AC-1 of the epic) is a `theme.json` token applied in Story 5.4 with a paired text/icon for a11y (no colour-only encoding). Keep the enum a pure data type with behaviour — labels via the I18n registry, colour via tokens, both in 5.4.

### Project Structure Notes

- Touches: `src/Kernel/Tier.php` (UPDATE — add constants + methods), `src/Content/UserMeta.php` (UPDATE — alias constants to Kernel), `src/Tiers/Api.php` (UPDATE — add `forUser()`). `src/Tiers/Module.php` stays a no-op. New tests: `tests/Unit/Kernel/TierTest.php`, `tests/Unit/Tiers/ApiTest.php`.
- No DB/schema change (the promotion-log custom table is Story 5.3); no new option; no new hook. Pure typed-read addition + a key single-source relocation.

### References

- [Source: epics.md#Story-5.1 (Gradering data model AC + "Deferred from Epic 2 review")]
- [Source: deferred-work.md "Deferred from: code review of Story 2.3 — user-meta" (typed accessor / brons-default-on-raw-read)]
- [Source: architecture.md AD-1, AD-3 dec.6, AD-8; lines 269-285, 369-370]
- [Source: deptrac.yaml (`Tiers: [Kernel]` only — the conflation prohibition)]
- [Source: src/Kernel/Tier.php, src/Content/UserMeta.php, src/Tiers/{Api,Module}.php]
- [Source: tests/Unit/Content/UserMetaTest.php (Brain Monkey capture pattern + the constants/default/sanitize assertions to keep green)]
- [Source: project-context.md (strict types, prefix, single-source enums, no raw superglobals/SQL, testing rule 2026-06-22, THE conflation rule)]

## Dev Agent Record

### Agent Model Used

claude-opus-4-8 (BMAD dev-story loop)

### Debug Log References

- `composer test:unit` → 283 passed / 1 skipped (1288 assertions).
- `composer cs` (Tier.php, UserMeta.php, Tiers/Api.php) → clean.
- `composer stan` → No errors (run sandbox-off; the known phpstan TCP-server EPERM caveat).
- `composer deptrac` → 3 violations, all pre-existing `Ink\Kernel\Activation → Ink\Content\PostTypes` (12/73/87); no new edge.

### Completion Notes List

- **Data model + read path only** — the Gradering enum gains its first behaviour (default + manual-only/auto-promotable semantics) and the `ink_writer_tier` meta-key single-source moves to the Kernel `Tier` enum so the new `Ink\Tiers\Api::forUser()` reader can use it without a forbidden `Tiers → Content` deptrac edge.
- **2.3 deferral closed:** `Api::forUser()` is the consumer read path that guarantees the Brons default for unset/empty/junk users (a raw `get_user_meta(..., true)` returns `''` for an unwritten user). Every later Epic-5 consumer (5.4/5.5/5.8/5.9) must read through this, not raw meta.
- **Conflation rule intact:** `Ink\Tiers\Api` references only `Ink\Kernel\Tier` + WordPress; zero `Ink\Entitlement`. Deptrac confirms no `Tiers` edge beyond Kernel.
- **No scope creep:** `ink_tier_win_count` (5.7), `Tiers::promote()` (5.7/5.8), the promotion-log table (5.3), and all presentation (the `#EA4015` Meester colour + grade labels, Story 5.4) were deliberately NOT built. `Module::register()` stays a no-op.
- **Backward compatible:** `UserMeta::WRITER_TIER`/`::TIER_PROMOTED_AT` keep their exact literal values via Kernel aliases; `UserMetaTest` unchanged and green.

### File List

- `wp-content/plugins/ink-core/src/Kernel/Tier.php` (UPDATE — meta-key constants + `default()` / `isManualOnly()` / `isAutoPromotable()` + docblock)
- `wp-content/plugins/ink-core/src/Content/UserMeta.php` (UPDATE — constants aliased to the Kernel single source)
- `wp-content/plugins/ink-core/src/Tiers/Api.php` (UPDATE — `forUser()` typed accessor)
- `tests/Unit/Kernel/TierTest.php` (NEW)
- `tests/Unit/Tiers/ApiTest.php` (NEW)

### Change Log

- 2026-06-25 — Story 5.1 implemented (create-story → dev-story). Gradering data-model semantics + Kernel-owned meta-key single source + typed default-safe `Tiers\Api::forUser()` accessor (closes Epic-2/2.3 deferral). 283 passed / 1 skipped; cs/stan clean; deptrac no new edge. Status → review.

## Review Findings (code review 2026-06-26, Group A: 5.1+5.3+5.7)

_3-layer adversarial review (Blind Hunter + Edge Case Hunter + Acceptance Auditor). All four ACs evidenced; conflation rule holds; no Critical/High violations. One cross-cutting decision involves this story's read path:_

- [x] [Review][Decision→Dismissed] `Api::forUser()` case-sensitive junk→Brons coercion — `Tier::tryFrom('Goud')`/`'Brons'` (mis-cased) returns `null` → coerced to Brons. **RESOLVED 2026-06-26 — accept as-is (no code change).** AC-2 sanctions junk→Brons coercion by design; the sole writer persists lowercase `->value`, so the demotion path is only reachable by external/manual writes (a Story 16.3 import data-quality concern). Tracked with the dismissed audit-fidelity item in Story 5.3's Review Findings. [`Api.php:54`]

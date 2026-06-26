---
baseline_commit: e9a5e75
---

# Story 9.1: BuddyPress scoped config

Status: review

<!-- Note: Validation is optional. Run validate-create-story for quality check before dev-story. -->

## Story

As a site owner,
I want BuddyPress scoped to only what INK uses,
so that the community stays focused. (FR-37)

## Acceptance Criteria

**Given** BuddyPress
**When** configured
**Then** Profiles, Directory, Notifications are **on**; Private Messaging **off at launch** (§14.7); Friend Connections, site-wide Activity, Groups, Blogs **off**.

1. The scoped component set is **code-enforced in `ink-core`** (not a one-time admin click that the brownfield DB clone could carry forward differently): `Ink\Social` filters `bp_active_components` so the active set is exactly the INK scope — `xprofile` (Profiles), `members` (the member directory — Directory), `notifications` (Kennisgewings), plus `settings` (account settings, required for notification preferences). `members` stays because it is BuddyPress's required core component and owns the Directory.
2. The following components are **forced off** regardless of what the cloned DB had active: `friends` (Friend Connections — INK uses the custom one-way follow graph, Story 9.2), `activity` (site-wide Activity stream), `groups`, `blogs`, `messages` (Private Messaging — deferred at launch, §14.7 / Story 9.8).
3. The enforcement is **idempotent and order-independent** — it returns the same scoped set whether the incoming active-components array is empty, already-scoped, or polluted with every component.
4. **Graceful when BuddyPress is absent.** The `Social\Module` registers behind a guarded seam so the filter is only added when BuddyPress is present; with BP not loaded `ink-core` is a clean no-op (no fatals, no orphan hooks). The unit suite is mocked (no BP), so the scoping logic is a **pure method** unit-tested independently of the `bp_active_components` hook wiring.
5. **Three-layer & conflation-clean:** the scoping lives in `ink-core` (`Ink\Social`) via BuddyPress's own `bp_active_components` filter — "hook, don't edit" (no BP files touched, no BP internals assumed beyond the public filter + the documented component IDs). No business logic in the theme. Zero coupling to `Ink\Entitlement` / `Ink\Tiers` (community scope ≠ entitlement ≠ writer tier).
6. The `Social\Module` is **registered in the `ink-core` bootstrap** (`ink-core.php`) alongside the other modules, and its `register()` wires the scoped-config filter (replacing the reserved 1.7 no-op).

## Tasks / Subtasks

- [x] Task 1: Scoped-config provider (`Ink\Social\BuddyPress`) (AC: #1–#3, #5)
  - [x] Define the scope as a single source of truth: `SCOPED_ON = ['xprofile','members','notifications','settings']` and `FORCED_OFF = ['friends','activity','groups','blogs','messages']` (documented constants — these are BuddyPress's public component IDs, not INK enums, so they live here as the integration contract, not in `Kernel`).
  - [x] `scopeComponents($active): array` (pure) — returns an array containing exactly the `SCOPED_ON` ids (each `=> '1'`) and none of the `FORCED_OFF` ids — regardless of what was passed in. Total + idempotent + order-independent (the incoming value is intentionally ignored — the scope is fixed). This is the unit-testable core.
- [x] Task 2: Wire the filter + register the module (AC: #4, #6)
  - [x] `Social\Module::register()` — add `bp_active_components` filter → `BuddyPress::scopeComponents()`, guarded by a `protected buddyPressActive()` seam (returns `function_exists('buddypress')`) so it only wires when BuddyPress is present and no-ops cleanly when absent. Replaced the reserved 1.7 no-op body; docblock made honest. Class is non-`final` to expose the seam (the LifecycleEmails testability pattern).
  - [x] Register `Social\Module` in `ink-core.php` `plugins_loaded` bootstrap: `addModule( 'social', new Social\Module() )`.
- [x] Task 3: Tests + gates (AC: all)
  - [x] `tests/Unit/Social/BuddyPressTest.php`: `scopeComponents` (a) keeps every `SCOPED_ON` id when given the full BP component list, (b) **strips every `FORCED_OFF` id** (non-vacuous — asserts the input DOES contain friends/activity/groups/blogs/messages first, so the assertion can fail if scoping is a no-op), (c) adds the scoped ids when given an empty array, (d) is idempotent (an already-scoped set is a fixpoint), (e) scope sets are disjoint and cover FR-37.
  - [x] `tests/Unit/Social/ModuleTest.php`: `register()` adds the `bp_active_components` filter when BP is present and adds nothing when absent — driven via an anonymous subclass that pins the `buddyPressActive()` seam (no leaked process-wide stub between cases), non-vacuous.
  - [x] `composer test:unit` green (575 passed, 1 skipped, +7); `composer stan` clean (OK); `composer cs` 0 errors; `composer copy:scan` no new debt (config only — no user-facing copy); `composer deptrac` 3 pre-existing violations, 0 new (Social → Kernel only).

## Dev Notes

- **Code-enforce the scope, don't rely on the admin toggle** [Source: epics.md#Story 9.1; project-context "Brownfield: existing WordPress DB is cloned and reused"]: the cloned DB may have Friends/Activity/Groups/Messages active from the legacy site. A one-time admin un-check is fragile (a re-activated component or a restored option silently re-enables it). Filtering `bp_active_components` makes the scope **declarative and version-controlled** — the source of truth is `ink-core`, not the `bp-active-components` option. This is the same "config-as-code" intent as the rest of the platform-plugin scoping in project-context.
- **`members` must stay on** [Source: BuddyPress component model]: `members` is BuddyPress's required core component and owns the **member directory** (Story 9.7 ledegids surfaces through it). Directory ON = `members` ON. `xprofile` = extended Profiles. `settings` is kept because notification preferences (Story 9.9) live under it.
- **Friends OFF — INK uses the custom follow graph** [Source: project-context "Follow is custom in ink-core (asymmetric, one-way). BuddyPress Friend Connections are OFF"; architecture.md#AD-5 follow graph custom table; Story 9.2]: do NOT reach for a BuddyPress follow add-on; the one-way graph is `ink_follows` (Story 9.2). Friend Connections stays off.
- **Messages OFF at launch** [Source: epics.md#Story 9.8, §14.7; architecture.md#AD-9 "Private Messaging off at launch"]: deferred, non-blocking; Story 9.8 documents the deferral. The `messages` component is in `FORCED_OFF`.
- **Notifications ON, no parallel table** [Source: architecture.md#AD-5 "Kennisgewings → BuddyPress notifications store (register custom `ink` types) — BP Notifications is ON (FR-37/44)"]: the BP `notifications` component is the store Story 9.9/9.11 register custom `ink` types against. It must be ON.
- **Hook, don't edit** [project-context]: integrate through BuddyPress's own `bp_active_components` filter — never modify BP files or assume internals beyond the public filter + the stable component IDs. The component IDs are BuddyPress's public contract.
- **Module skeleton** [Source: src/Social/Module.php (reserved), architecture.md#Canonical module skeleton]: the reserved `Social\Module`/`Api` already exist from Story 1.7. This story replaces the `register()` no-op and registers the module in the bootstrap. Keep the scoping logic in a `BuddyPress` collaborator (pure `scopeComponents`) so it unit-tests without BP loaded — mirrors the codebase's "pure method + thin hook" pattern (e.g. Discovery args builders, Entitlement resolvers).
- **No copy** — this is a pure-config story; nothing user-facing ships, so there is no Afrikaans copy debt and no theme change.

### Project Structure Notes

- NEW ink-core: `src/Social/BuddyPress.php` (scoped-config provider — pure `scopeComponents` + scope constants).
- MOD ink-core: `src/Social/Module.php` (wire the `bp_active_components` filter, guarded; replace the 1.7 no-op), `ink-core.php` (register `social` module in the `plugins_loaded` bootstrap).
- NEW tests: `tests/Unit/Social/BuddyPressTest.php` (+ optional `ModuleTest`).
- deptrac: no new edge (Social → Kernel only).
- Note (don't build): the follow graph (9.2), profiles templates (9.4), notifications register (9.9), directory surface (9.7) — this story ONLY scopes which BP components are active.

### References

- [Source: _bmad-output/planning-artifacts/epics.md#Story 9.1]
- [Source: _bmad-output/planning-artifacts/architecture.md#AD-5 (storage — Kennisgewings → BP notifications store), #AD-9 (messaging off at launch)]
- [Source: wp-content/plugins/ink-core/src/Social/Module.php, Api.php (reserved 1.7 seams)]
- [Source: wp-content/plugins/ink-core/ink-core.php (module bootstrap)]
- [Source: _bmad-output/project-context.md#platform-plugins (BuddyPress scoped: profiles, directory, notifications; Friends/Groups/Messaging OFF), #hook-dont-edit, #three-layer]

## Dev Agent Record

### Agent Model Used

claude-opus-4-8 (BMAD dev-story loop, Epic 9)

### Debug Log References

- `composer stan` / `composer deptrac` run outside the sandbox (phpstan parallel-worker TCP bind) — same as Epics 5–8. No code findings; deptrac shows the 3 PRE-EXISTING Kernel→Content violations only (0 new edge from Social).
- `composer cs` exits non-zero only on the 2 PRE-EXISTING accepted slow-query WARNINGS in `Engagement/ResponseStore.php` + `SuggestedReads.php` (0 errors). The Social files are phpcs-clean.

### Completion Notes List

- **Scope is code-enforced, not an admin click** (`Ink\Social\BuddyPress::scopeComponents`): filters BuddyPress's own `bp_active_components` so the active set is *exactly* the INK slice — `xprofile` (Profiles), `members` (Directory — BP's required core component, hosts the 9.7 ledegids), `notifications` (the AD-5 BP store the 9.9/9.11 `ink` types register against), `settings` (notification prefs). Everything else is dropped, so `friends`/`activity`/`groups`/`blogs`/`messages` (Private Messaging, §14.7) are off regardless of what the brownfield DB clone had active. `scopeComponents` is pure/total/idempotent (it ignores the incoming value — the scope is fixed), which is why it unit-tests cleanly without BuddyPress.
- **Friends OFF** — INK's follow is the custom asymmetric `ink_follows` graph (Story 9.2), never BuddyPress Friend Connections; the `friends` component stays in `FORCED_OFF`.
- **Graceful + testable presence guard**: `Social\Module::register()` only adds the filter behind a `protected buddyPressActive()` seam (`function_exists('buddypress')`), so with BuddyPress absent (the unit/CI repo) `ink-core` is a clean no-op. The seam (vs an inline `function_exists`) lets the two tests pin presence/absence via an anonymous subclass without a process-wide `buddypress()` stub leaking between cases — the same pattern as `Entitlement\LifecycleEmails::isActionSchedulerAvailable()`. `Module` is non-`final` to expose the seam.
- **Integration-timing note (not a unit concern):** BuddyPress evaluates `bp_active_components` during its own bootstrap; the filter is registered from the module's `init`-dispatched `register()`. Effectiveness against a live BuddyPress (hook ordering relative to BP's component setup) is an integration/E2E concern owned by Story 18.8 (wp-env) — the pure `scopeComponents` contract is unaffected by where the filter is hooked. Flagged for the integration layer; no live BuddyPress exists in this repo (platform plugin, gitignored).
- **Conflation-clean:** Social depends only on `Kernel\Module`; zero `Ink\Entitlement` / `Ink\Tiers`. Community scope ≠ entitlement ≠ writer tier. No new deptrac edge.
- Tests 568→575 (+7); cs 0 errors; stan OK; copy:scan no new debt; deptrac 3 pre-existing (0 new).

### File List

- `wp-content/plugins/ink-core/src/Social/BuddyPress.php` (NEW — scoped-component reducer + scope constants)
- `wp-content/plugins/ink-core/src/Social/Module.php` (MOD — wire the guarded `bp_active_components` filter; non-final + `buddyPressActive()` seam; honest docblock)
- `wp-content/plugins/ink-core/ink-core.php` (MOD — register the `social` module in the `plugins_loaded` bootstrap)
- `tests/Unit/Social/BuddyPressTest.php` (NEW)
- `tests/Unit/Social/ModuleTest.php` (NEW)
- `_bmad-output/implementation-artifacts/9-1-buddypress-scoped-config.md` (NEW — this story)
- `_bmad-output/implementation-artifacts/sprint-status.yaml` (MOD — 9.1 + epic-9 status)

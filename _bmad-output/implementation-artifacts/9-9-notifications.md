---
baseline_commit: 1af843e
---

# Story 9.9: Notifications

Status: review

<!-- Note: Validation is optional. Run validate-create-story for quality check before dev-story. -->

## Story

As a lid,
I want kennisgewings for relevant events,
so that I stay informed. (FR-44)

## Acceptance Criteria

**Given** the notification system
**When** events fire
**Then** kennisgewings cover new Gemeenskapsreaksie/@mention, followed-writer new work, uitdaging announcement/deadline, lidmaatskap-expiry reminder, and read-receipt milestone (9.11)
**And** "Merk alles as gelees" marks read by a timestamp boundary (no phantom-unread)
**And** the expiry reminder shares its anchor with the 4.8 lifecycle warnings.

1. Kennisgewings use the **BuddyPress notifications store** (AD-5 — BP Notifications is ON from Story 9.1; no parallel table): a single INK component (`ink`) with a typed action per category. A closed `NotificationType` enum names the categories: new Gemeenskapsreaksie, @mention, followed-writer new work, uitdaging announcement/deadline, lidmaatskap-expiry, read-receipt (R7/9.11).
2. A **guarded emitter** `Kennisgewings::add()` writes a notification via `bp_notifications_add_notification()` behind a `function_exists` seam — a clean no-op when BuddyPress is absent (the unit/CI repo). All notification creation routes through it.
3. **"Merk alles as gelees" marks read by a TIMESTAMP BOUNDARY** (the explicit no-phantom-unread guarantee): marking-all-read stores a per-user GMT boundary (`ink_kennisgewings_gelees_op`); a notification is unread iff its created time is **strictly after** the boundary. A notification that arrives *during* the mark-all operation (created after the boundary) stays unread — never wrongly cleared (the phantom-unread bug a per-row mark-all would cause). The unread count is computed from the boundary, not by mutating each row. This logic is pure and unit-tested.
4. **The lidmaatskap-expiry reminder shares the 4.8 anchor**: the in-app expiry kennisgewing subscribes to the **same** Action Scheduler hook the 4.8 lifecycle emails fire on (`Entitlement\LifecycleEmails::HOOK_SEND_WARNING`) — one schedule, two outputs (email + in-app), no second timer.
4. **Wired sources** (where the source exists today): (a) **new Gemeenskapsreaksie** — on the `ink_reaksie` comment insert, notify the work's author; (b) **@mention** — parse `@mentions` in the reaksie body and notify each mentioned lid; (c) **followed-writer new work** — on a readable-bydrae publish, fan out to the author's volgelinge (`Social\Api::followerIdsFor()`). Each guarded; each no-self-notify.
6. **Deferred sources, emitter ready**: uitdaging announcement/deadline (the Challenges/Epic-12 events do not exist yet) and the read-receipt milestone (Story 9.11 / R7) wire to the SAME `Kennisgewings::add()` when their source lands — the type enum + emitter are in place now. Documented in `deferred-work.md`.
7. **Three-layer & conflation-clean:** all notification logic in `ink-core` (`Ink\Notifications`); guarded BP seams (hook, don't edit). The fan-out reads `Social\Api` + core post fields — zero `Ink\Tiers`; the expiry anchor references only the 4.8 hook-name constant (sharing, not gating). Exposed via `Notifications\Api`.
8. **Afrikaans-first:** any kennisgewing label/copy is glossary-backed (`kennisgewing`) or authored; BP composes the notification sentence, so INK supplies the typed action + the Afrikaans formatter (guarded). No AI Afrikaans.

## Tasks / Subtasks

- [x] Task 1: Type enum + emitter + mark-all-read boundary (`Ink\Notifications\NotificationType`, `Kennisgewings`) (AC: #1–#3, #7)
  - [x] `NotificationType: string` enum — `Reaksie='reaksie'`, `Mention='mention'`, `VolgWerk='volg_werk'`, `Uitdaging='uitdaging'`, `LidmaatskapVerval='lidmaatskap_verval'`, `Ontvangs='ontvangs'` (R7). `COMPONENT='ink'`.
  - [x] `Kennisgewings::add(int $user_id, NotificationType $type, int $item_id, int $actor_id=0): bool` — guarded `bp_notifications_add_notification()` (component `ink`, `component_action`=type value); no-op + false when BP absent or `$user_id<=0` or self (`$user_id===$actor_id`).
  - [x] Mark-all-read boundary: `MARK_META='ink_kennisgewings_gelees_op'`; `markAllRead(int $user_id): void` (store GMT now + guarded BP mark-all); `boundaryFor(int): string`; **pure** `isUnread(string $created_gmt, string $boundary_gmt): bool` (created strictly > boundary; empty boundary → unread) + `countUnread(list<string> $created_gmts, string $boundary): int`.
- [x] Task 2: Source subscriptions (`Ink\Notifications\Events`) (AC: #4, #5)
  - [x] `register()` on `init`: hook `wp_insert_comment` → if `comment_type==='ink_reaksie'`, notify the work author (`Reaksie`) + parse `@mentions` → notify each (`Mention`), skipping self; hook `transition_post_status` → on publish of a readable bydrae, fan out `VolgWerk` to `Social\Api::followerIdsFor( post_author )`; hook `LifecycleEmails::HOOK_SEND_WARNING` → emit `LidmaatskapVerval` to the member (the shared 4.8 anchor). All guarded; pure helpers (`mentionedLogins(string $body): list<string>`) unit-tested.
- [x] Task 3: Follow fan-out source (AC: #5)
  - [x] `FollowStore::followerIdsFor(int $skrywer_id): list<int>` (the reverse `KEY followee_id` query — who follows this writer); expose `Social\Api::followerIdsFor()`.
- [x] Task 4: Facade + wiring + deptrac (AC: #1, #7)
  - [x] `Notifications\Api`: `notify()`/`markAllRead()`/`unreadCount()` (or expose `Kennisgewings`). Register `Events` in `Notifications\Module`. deptrac: add `Notifications → [Social, Content, Entitlement]` (fan-out + post fields + the shared expiry-hook constant), documented (no Tiers; no gating).
- [x] Task 5: Tests + gates (AC: all)
  - [x] `KennisgewingsTest`: `isUnread`/`countUnread` boundary logic — a notification created AFTER the boundary stays unread (the no-phantom-unread guarantee, non-vacuous: one created before is read, one after is unread); `add` no-ops without BP, rejects self/`<=0`. `NotificationTypeTest`: the six cases + values. `EventsTest`: `mentionedLogins` extracts `@handle`s (and none from plain text); the comment/publish handlers skip self and call the emitter for the right targets (mock the seam). `FollowStoreTest` (extend): `followerIdsFor` queries `followee_id`.
  - [x] `composer test:unit` green; `composer stan` clean; `composer cs` 0 errors; `composer copy:scan` no new debt; `composer deptrac` clean (the new Notifications edges declared).

## Dev Notes

- **BP store, no parallel table** [Source: architecture.md#AD-5 "Kennisgewings → BuddyPress notifications store (register custom `ink` types) — BP Notifications is ON"]: register one `ink` component with a typed action per category; never a custom notifications table. BP composes/persists; INK supplies the type + (guarded) Afrikaans formatter. BP is absent in the repo, so everything is behind `function_exists` seams (the LifecycleEmails/Action-Scheduler precedent) and the testable core is the pure boundary logic.
- **The timestamp boundary IS the no-phantom-unread fix** [Source: epics.md#Story 9.9 AC]: a naive per-row "mark all read" races with notifications arriving mid-operation (they get cleared → phantom-read, or the count goes stale). Storing a single GMT boundary and computing unread as `created > boundary` is race-free: anything created after the click is still unread, deterministically. Unit-test that a post-boundary notification stays unread (non-vacuous against a pre-boundary one that is read).
- **Share the 4.8 anchor, don't add a timer** [Source: Entitlement/LifecycleEmails::HOOK_SEND_WARNING; epics.md#Story 9.9 "shares its anchor with the 4.8 lifecycle warnings"]: 4.8 already schedules the expiry warning via Action Scheduler on `ink_entitlement_send_expiry_warning`. 9.9 subscribes to that SAME hook for the in-app reminder — one schedule, two outputs. Reference the constant (single source), not a duplicated string.
- **Sources that don't exist yet are emitter-ready, not faked** [Source: only `ink/tier_promoted` events emitted today; Story 9.11 / Epic 12]: the reaksie/@mention/follow-publish sources are wired via the underlying WP hooks (`wp_insert_comment`, `transition_post_status`) that DO fire today. uitdaging (Challenges/Epic 12 events absent) and read-receipt (9.11/R7, depends on the 18.9 analytics) call the SAME `Kennisgewings::add()` when their source lands — built emitter, deferred wiring (recorded). Same graceful-sequencing pattern as 9.6 (held reviews) / R7-R8.
- **Self-notify guard** [AC #4/#5]: never notify the actor about their own action (you don't get a kennisgewing for your own reaksie / your own publish). Enforced in `add()` (`$user_id===$actor_id`) and the fan-out (skip the author).
- **Copy**: `kennisgewing` is the glossary term; the notification sentence is BP-composed — INK's guarded formatter supplies authored Afrikaans (copy-debt to ratify where no approved source). No AI Afrikaans.

### Project Structure Notes

- NEW ink-core: `src/Notifications/NotificationType.php` (enum), `src/Notifications/Kennisgewings.php` (emitter + boundary), `src/Notifications/Events.php` (source subscriptions).
- MOD ink-core: `src/Notifications/Api.php` (facade), `src/Notifications/Module.php` (wire Events), `src/Social/FollowStore.php` + `src/Social/Api.php` (`followerIdsFor`).
- MOD `deptrac.yaml` (Notifications → Social, Content, Entitlement — documented; no Tiers).
- NEW tests: `KennisgewingsTest`, `NotificationTypeTest`, `EventsTest`; MOD `FollowStoreTest`.
- MOD `deferred-work.md` (uitdaging + read-receipt source-wiring deferred to their owners).
- Note (don't build): the uitdaging announce/deadline events (Epic 12); the read-receipt source (9.11); a notification-centre UI page (BP renders the panel; an INK styling pass is theme/E2E, Story 18.x); e-mail digesting.

### References

- [Source: _bmad-output/planning-artifacts/epics.md#Story 9.9, #Story 9.11]
- [Source: _bmad-output/planning-artifacts/architecture.md#AD-5 (BP notifications store), #AD-6 (events), #AD-9 (notifications)]
- [Source: wp-content/plugins/ink-core/src/Entitlement/LifecycleEmails.php (HOOK_SEND_WARNING — the shared expiry anchor; the guarded-seam pattern)]
- [Source: wp-content/plugins/ink-core/src/Social/FollowStore.php (the reverse followee_id index for follower fan-out)]
- [Source: _bmad-output/project-context.md#three-layer, #conflation-rule, #Afrikaans-first, #hook-dont-edit]

## Dev Agent Record

### Agent Model Used

claude-opus-4-8 (BMAD dev-story loop, Epic 9)

### Debug Log References

- `composer stan` / `composer deptrac` run outside the sandbox. stan OK; deptrac 3 pre-existing (0 new — the new `Notifications → [Social, Content, Entitlement]` edges are declared, documented as fan-out + post-fields + the shared expiry-hook constant; no Tiers, no gating).
- `mentionedLogins` regex: `.` was dropped from the handle class so a trailing period (`@kobus.`) and an embedded email `@` are not captured as mentions.

### Completion Notes List

- **BP notifications store, no parallel table** (AD-5): a single `ink` component with a typed `NotificationType` action; everything routes through the guarded `Kennisgewings::add()` (`bp_notifications_add_notification` behind `function_exists`) — a clean no-op without BuddyPress (the repo/CI state). No self-notify; no write for a non-positive recipient.
- **The timestamp boundary is the no-phantom-unread fix**: `markAllRead()` stores a per-user GMT boundary (`ink_kennisgewings_gelees_op`); `isUnread(created, boundary)` = created strictly > boundary. A notification arriving during the mark-all click stays unread (asserted non-vacuously against a pre-boundary one that is read); the count is computed from the boundary, never by mutating each row.
- **Wired sources**: new Gemeenskapsreaksie + @mention (`wp_insert_comment` for `ink_reaksie` → work author + each mentioned lid), followed-writer new work (`transition_post_status` publish of a readable bydrae → fan out to `Social\Api::followerIdsFor`). The lidmaatskap-expiry reminder subscribes to the SAME 4.8 anchor (`LifecycleEmails::HOOK_SEND_WARNING`, 3-arg) — one schedule, two outputs.
- **Deferred sources, emitter ready**: uitdaging (Epic 12 events absent) and read-receipt (9.11/R7) call the same `add()` when their source lands — `NotificationType::Uitdaging`/`Ontvangs` + the emitter are in place. Recorded in `deferred-work.md`.
- **`FollowStore::followerIdsFor`** added (the reverse `KEY followee_id` query) + `Social\Api::followerIdsFor` facade for the fan-out.
- Tests 651→666 (+15); cs 0 errors; stan OK; copy:scan no new debt; deptrac 3 pre-existing (0 new).

### File List

- `wp-content/plugins/ink-core/src/Notifications/NotificationType.php` (NEW — kennisgewing type enum)
- `wp-content/plugins/ink-core/src/Notifications/Kennisgewings.php` (NEW — guarded emitter + mark-all-read boundary)
- `wp-content/plugins/ink-core/src/Notifications/Events.php` (NEW — reaksie/@mention/follow-publish/expiry source subscriptions)
- `wp-content/plugins/ink-core/src/Notifications/Api.php` (MOD — notify / markAllRead facade)
- `wp-content/plugins/ink-core/src/Notifications/Module.php` (MOD — wire Events)
- `wp-content/plugins/ink-core/src/Social/FollowStore.php` (MOD — followerIdsFor reverse query)
- `wp-content/plugins/ink-core/src/Social/Api.php` (MOD — followerIdsFor facade)
- `deptrac.yaml` (MOD — Notifications → Social, Content, Entitlement)
- `tests/Unit/Notifications/KennisgewingsTest.php` (NEW)
- `tests/Unit/Notifications/NotificationTypeTest.php` (NEW)
- `tests/Unit/Notifications/EventsTest.php` (NEW)
- `tests/Unit/Social/FollowStoreTest.php` (MOD — followerIdsFor)
- `_bmad-output/implementation-artifacts/deferred-work.md` (MOD — uitdaging + read-receipt source-wiring deferred)
- `_bmad-output/implementation-artifacts/9-9-notifications.md` (NEW — this story)
- `_bmad-output/implementation-artifacts/sprint-status.yaml` (MOD — 9.9 status)

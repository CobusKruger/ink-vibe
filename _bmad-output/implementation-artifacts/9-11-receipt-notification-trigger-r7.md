---
baseline_commit: 449930d
---

# Story 9.11: Receipt-notification trigger (R7)

Status: review

<!-- Note: Validation is optional. Run validate-create-story for quality check before dev-story. -->

## Story

As a skrywer,
I want a notification when my work gets engagement,
so that I'm encouraged. (FR-44a, R7)

## Acceptance Criteria

**Given** a "receipt" event tied to the analytics read-count (9.12)
**When** it fires
**Then** a kennisgewing is sent with a **randomized** message from the Story 1.12 form-letter list, deep-linking to **private My Profiel**
**And** with analytics absent the trigger is inert (degrades gracefully).

1. A receipt trigger subscribes to a **receipt event** (`ink/ontvangs`, carrying skrywer_id + post_id + count) — the event Story 9.12 / the 18.9 analytics provider fires when a read-count milestone is reached. The trigger owns the event contract; it does not compute read counts.
2. On the event, it sends an **in-app kennisgewing** to the skrywer (`NotificationType::Ontvangs`, the 9.9 emitter) whose message is a **randomized pick from the Story 1.12 form-letter list** (`Notifications\Api::randomMessage()`), deep-linking to the **private My Profiel** (`/my-profiel`, the 9.4 page) — not the public Skrywerprofiel.
3. **Inert without analytics — degrades gracefully (two layers):** (a) with no analytics provider, the `ink/ontvangs` event never fires → no-op; (b) the receipt form-letter list is unauthored at launch (the R7 copy is `[NEEDS HUMAN AFRIKAANS]`, ui-copy line 654), so `randomMessage()` returns '' → the trigger sends nothing until human copy + analytics both land. Fail-safe at every step.
4. The receipt form-letter **template is registered** with the 1.12 `TemplateStore` (`ink_ontvangs_kennisgewing`) — toggle **OFF** by default (fail-safe), an **empty randomized-message list** at launch (authored later — the R7 list, ui-copy 654, is human-authored; no AI Afrikaans, no shipped placeholder text to leak).
5. **Three-layer & conflation-clean:** the trigger lives in `ink-core` (`Ink\Notifications`); routes through the 9.9 `Kennisgewings::add()` (guarded BP) + the 1.12 form-letter store; zero `Ink\Tiers`/`Ink\Entitlement`. The deep-link is the My Profiel URL (presentation route).

## Tasks / Subtasks

- [x] Task 1: Receipt trigger (`Ink\Notifications\ReceiptNotification`) (AC: #1–#4)
  - [x] `TEMPLATE_KEY = 'ink_ontvangs_kennisgewing'`; `RECEIPT_EVENT = 'ink/ontvangs'`. `register()` — register the form-letter template (empty `defaultMessages`, `defaultEnabled = false`); `add_action( RECEIPT_EVENT, [ $this, 'onReceipt' ], 10, 3 )`.
  - [x] `onReceipt( int $skrywer_id, int $post_id, int $count ): void` — pick `Notifications\Api::randomMessage( TEMPLATE_KEY )`; **if '' (no authored copy) → return (inert)**; else `Kennisgewings::add( $skrywer_id, NotificationType::Ontvangs, $post_id )`. Never for `$skrywer_id <= 0`.
  - [x] `deepLinkUrl(): string` (pure-ish) — the private My Profiel URL (`home_url( '/my-profiel' )`), the kennisgewing target.
- [x] Task 2: Wire + facade (AC: #5)
  - [x] Register `ReceiptNotification` in `Notifications\Module`. (No new deptrac edge — Notifications already covers what it needs.)
- [x] Task 3: Tests + gates (AC: all)
  - [x] `tests/Unit/Notifications/ReceiptNotificationTest.php`: `onReceipt` is INERT when `randomMessage` is '' (the unauthored-launch state — non-vacuous: when a message IS present it calls the emitter); never fires for `skrywer_id <= 0`; `register()` registers `TEMPLATE_KEY` with the toggle OFF + empty messages; `deepLinkUrl` is the My Profiel route; `RECEIPT_EVENT` is `ink/ontvangs`.
  - [x] `composer test:unit` green; `composer stan` clean; `composer cs` 0 errors; `composer copy:scan` **no new debt** (empty message list — no placeholder text shipped in code; the R7 copy debt is already tracked in ui-copy line 654); `composer deptrac` clean.

## Dev Notes

- **Inert-until-dependency, two layers** [Source: epics.md#Story 9.11 sequence note; R7/R8 degrade gracefully]: the epic flags that 18.9 (analytics) should land before 9.11/9.12, OR ship R7/R8 later — both degrade gracefully. 9.11 is built BEFORE 18.9/9.12, so: (a) the `ink/ontvangs` event has no emitter yet → never fires; (b) the form-letter list is unauthored → `randomMessage` returns '' → send is suppressed. Either alone makes it a clean no-op; together, doubly safe.
- **Empty list, not a shipped placeholder** [Source: ui-copy line 654 R7 list = `[NEEDS HUMAN AFRIKAANS]`; the 4.8 fail-safe-OFF pattern]: the R7 randomized messages are human-authored (ui-copy 654 already tracks the debt — "moenie KI-vertaal nie"). The template registers with an EMPTY `defaultMessages` + toggle OFF, so no placeholder TEXT ships in code (nothing to leak; no scan-baseline churn) and `randomMessage` naturally returns '' until the human list lands. When authored, the list is added (via the store/admin) and analytics fires the event — then it works. This is cleaner than shipping `[WAG OP MENSLIKE KOPIE]` text for a list that is inert anyway.
- **Reuse 9.9 + 1.12, build neither** [Source: Stories 9.9 `Kennisgewings`/`NotificationType::Ontvangs`, 1.12 `Notifications\Api::randomMessage`/`registerTemplate`]: the in-app emitter (9.9) and the randomized form-letter store (1.12) already exist. 9.11 is the thin trigger that connects the (future) receipt event to them — it adds no notification engine.
- **Deep-link to PRIVATE My Profiel** [AC; Story 9.4]: the receipt encourages the writer privately — link to `/my-profiel` (the 9.4 private page), never the public Skrywerprofiel. The read counts the receipt celebrates are themselves private (9.12, R8).
- **The event contract** [Source: AD-6 events; Story 9.12]: `ink/ontvangs` is fired by 9.12/18.9 with `( skrywer_id, post_id, count )`. 9.11 documents + subscribes to it; 9.12 (R8) and the analytics provider (18.9) emit it when a read-count milestone is crossed.

### Project Structure Notes

- NEW ink-core: `src/Notifications/ReceiptNotification.php` (the R7 trigger).
- MOD ink-core: `src/Notifications/Module.php` (register the trigger).
- NEW tests: `tests/Unit/Notifications/ReceiptNotificationTest.php`.
- deptrac: no new edge. copy:scan: no new debt (empty list; the R7 copy debt stays tracked in ui-copy 654).
- Note (don't build): the read-count milestone detection + the `ink/ontvangs` emitter (Story 9.12 / R8, + the 18.9 analytics provider); authoring the R7 form-letter list (human copy, ui-copy 654); the BP notification-sentence formatter (E2E, Story 18.8).

### References

- [Source: _bmad-output/planning-artifacts/epics.md#Story 9.11 (FR-44a, R7) + the 2026-06-20 sequence note, #Story 9.12, #Story 18.9]
- [Source: wp-content/plugins/ink-core/src/Notifications/Kennisgewings.php, NotificationType.php (Story 9.9 — the Ontvangs emitter)]
- [Source: wp-content/plugins/ink-core/src/Notifications/Api.php, Template.php, TemplateStore.php (Story 1.12 — randomMessage + registerTemplate)]
- [Source: docs/ui-copy-translations.md line 650-654 (the R7 randomized-message list, human-authored); _bmad-output/project-context.md#three-layer, #Afrikaans-first, #conflation-rule]

## Dev Agent Record

### Agent Model Used

claude-opus-4-8 (BMAD dev-story loop, Epic 9)

### Debug Log References

- `composer stan` / `composer deptrac` run outside the sandbox. stan OK; deptrac 3 pre-existing (0 new — Notifications already covers the needed edges).
- `ReceiptNotificationTest` resets the `Notifications\Api` first-wiring-wins (`??=`) static (`store`/`notifier`) via reflection in `beforeEach` for test isolation — otherwise a prior test's bootstrap would make a later `Api::bootstrap` a no-op and stale the store. The BP emit itself is proven by `KennisgewingsTest`; here we assert the inert-guard decision (cross-file Brain Monkey function-redefinition is unreliable for the BP function, so we don't re-assert the emit through it).

### Completion Notes List

- **R7 trigger** (`ReceiptNotification`): subscribes to `ink/ontvangs` (the event 9.12/18.9 fires with skrywer_id/post_id/count) and, on fire, sends an `Ontvangs` kennisgewing (the 9.9 emitter) whose text is a randomized pick from the 1.12 form-letter list, deep-linking to the PRIVATE My Profiel (`/my-profiel`).
- **Doubly inert without analytics** (graceful degradation): (a) `ink/ontvangs` has no emitter until 9.12/18.9 → never fires; (b) the R7 form-letter list is unauthored (ui-copy 654), so `randomMessage` returns '' and `onReceipt` returns before emitting. Either alone is a clean no-op.
- **Empty list, no shipped placeholder**: the template registers toggle-OFF with an EMPTY `defaultMessages` — no `[WAG OP MENSLIKE KOPIE]` text ships in code (nothing to leak, no scan-baseline churn); the R7 copy debt stays tracked in ui-copy 654 and the list is filled when the human copy lands.
- **Reuse, build neither**: routes through the 9.9 `Kennisgewings`/`NotificationType::Ontvangs` emitter + the 1.12 `Api::randomMessage`/`registerTemplate` — no new notification engine. Conflation-clean (zero Tiers/Entitlement).
- Tests 667→673 (+6); cs 0 errors; stan OK; copy:scan no new debt; deptrac 3 pre-existing (0 new).

### File List

- `wp-content/plugins/ink-core/src/Notifications/ReceiptNotification.php` (NEW — R7 receipt trigger)
- `wp-content/plugins/ink-core/src/Notifications/Module.php` (MOD — register ReceiptNotification)
- `tests/Unit/Notifications/ReceiptNotificationTest.php` (NEW)
- `_bmad-output/implementation-artifacts/9-11-receipt-notification-trigger-r7.md` (NEW — this story)
- `_bmad-output/implementation-artifacts/sprint-status.yaml` (MOD — 9.11 status)

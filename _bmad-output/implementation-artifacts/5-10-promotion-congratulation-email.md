---
baseline_commit: 0171b2ef8f2d6f9111eed1fdfa811e7a63d67d62
---

# Story 5.10: Promotion congratulation email

Status: review

<!-- Note: Validation is optional. Run validate-create-story for quality check before dev-story. -->

> **Build-order note:** uses the 5.8 `ink/tier_promoted` event (actor 0 = auto) + the Story-1.12 form-letter store. Mirrors the 4.2/4.8 Notifications consumer pattern.

## Story

As a skrywer,
I want a congratulation email on auto-promotion,
so that I'm recognised when I advance. (FR-12a, R3)

## Acceptance Criteria

1. **On an automatic promotion, a templated Afrikaans congratulation email is sent via the Story-1.12 form-letter store (e.g. "Baie geluk! Jy is na Silwer bevorder.").** Given an auto-promotion (the `ink/tier_promoted` event with `actor_id = 0`, fired by the 5.8 engine), when it commits, then a congratulation email is dispatched through `Ink\Notifications\Api::send()` to the writer, with the grade-specific glossary copy ("Baie geluk! Jy is na Silwer bevorder." / "… na Goud bevorder."). Auto-promotion only ever targets Silwer or Goud (Brons→Silwer, Silwer→Goud; Goud is terminal-for-auto, Meester is manual-only), so exactly two templates cover it. A **manual** staff change (`actor_id != 0`) does NOT send the congratulation email. _[Source: epics.md#Story-5.10 AC; afrikaans-terms.md line 213 ("Gradering-bevordering | 'Baie geluk! Jy is na Silwer bevorder.'"), line 73 (bevorder; 5/15 auto, Meester manual); src/Tiers/Api.php (5.2 `ink/tier_promoted` event; 5.8 actor 0 = engine); src/Notifications/Api.php (1.12 `registerTemplate`/`send`, `{skrywer}` merge); src/Entitlement/PurchaseActivation.php (the 4.2 register+send consumer precedent)]_

2. **Built as an `Ink\Tiers` Notifications consumer reusing the 1.12 store (no new engine), Afrikaans-source, toggle fail-safe OFF, conflation-clean.** Given the three-layer rule, when this is built, then a new `Ink\Tiers\PromotionEmails` collaborator (wired by `Tiers\Module::register()`) subscribes `ink/tier_promoted` and registers two Afrikaans-source templates (`ink_tier_promoted_silwer_email`, `ink_tier_promoted_goud_email`) on the 1.12 `TemplateStore` via `Notifications\Api::registerTemplate()` — each with the glossary copy wrapped in literal `__( …, 'ink-core' )`, the `{skrywer}` greeting merge, and the send toggle **fail-safe OFF** (the 4.2/4.8 convention — staff enable in production). The handler sends only for `actor_id === 0` and a Silwer/Goud target, resolving the recipient via `get_userdata()` (`instanceof \WP_User` + non-empty `user_email`; `display_name` → `user_login` fallback). It reuses 1.12 (NO new email engine) and carries **zero** other domain coupling — but it DOES add the allowed `Tiers → Notifications` Api-facade edge (the documented "extend a module's allowlist as real Api usage appears" path, exactly as 4.2 added `Entitlement → Notifications`; Notifications depends only on Kernel, no cycle). Still **zero `Ink\Entitlement`** (THE conflation rule). _[Source: architecture.md AD-9 (Notifications composes Afrikaans transactional email, toggle-gated), AD-1; src/Entitlement/PurchaseActivation.php (the consumer pattern: registerEmailTemplate + get_userdata + send); src/Notifications/{Api,Template,TemplateStore}.php; deptrac.yaml (`Entitlement: [Kernel, Notifications]` precedent to mirror for Tiers)]_

3. **WP-house-rules + Afrikaans glossary copy + authored AND PASSING tests.** Given the project rules, when this is built, then: the new `.php` keeps strict types / namespace / guard / PascalCase / camelCase; template/hook keys are `ink_`-prefixed single-source constants; copy is the glossary-approved Afrikaans gettext source (afrikaans-terms.md line 213 — NOT AI-translated); the toggle is fail-safe OFF; no raw superglobals. `deptrac.yaml` adds `Notifications` to the `Tiers` allowlist (the only ruleset change). Pest unit tests are authored at `tests/Unit/Tiers/` and **run with `composer test:unit`; the full suite passes before done** (baseline 331 passed / 1 skipped — zero regressions). `composer cs`/`stan`/`deptrac` run and recorded; deptrac green (the new `Tiers → Notifications` edge is now ALLOWED — no violation). _[Source: project-context.md (strict types, single-source, Afrikaans source/no AI translation, no raw superglobals, **testing rule 2026-06-22**, conflation rule); architecture.md AD-8; src/Entitlement/PurchaseActivationTest.php precedent for the Notifications consumer test harness]_

## Tasks / Subtasks

> **Current state (read before starting):**
> - **`ink/tier_promoted` (5.2) fires** with `( $user_id, Tier $from, Tier $to, int $actor_id, int $challenge_id )`; the 5.8 engine fires it with `actor_id = 0`. Subscribe to it.
> - **The 1.12 Notifications capability:** `Notifications\Api::registerTemplate( new Template( $key, $subject, $body, $enabled = false, $messages = [] ) )` + `send( $key, $to, [ 'skrywer' => $name ] )`. `{skrywer}` is the only merge token; `TemplateStore::isEnabled()` is fail-safe OFF. Mirror `PurchaseActivation::registerEmailTemplate()` + `onMembershipStatusChanged()` (the register + get_userdata + send shape).
> - **Two templates, not one:** the 1.12 store only merges `{skrywer}`, so the grade name ("Silwer"/"Goud") must be baked into the body — and auto-promotion only targets Silwer/Goud. Register one template per target.
> - **Deptrac:** `Tiers` currently allows `[Kernel]` only. Add `Notifications` (mirrors `Entitlement: [Kernel, Notifications]` from 4.2). This is the FIRST `Tiers` allowlist extension.
> - **Copy is glossary-approved** (afrikaans-terms.md line 213) — use it directly; NO `[WAG OP MENSLIKE KOPIE]` placeholder needed. Toggle still OFF by default (the standing email convention).
> - **Test harness:** copy the PurchaseActivationTest helpers (`ink_wire_notifications`, the `get_option` toggle-enable, `ink_userdata`, the reflection facade reset).
>
> **Scope is the AUTO-PROMOTION congratulation email only.** Do NOT build: a manual-promotion email (auto only — AC-1), a new email engine (reuse 1.12), an admin settings screen (toggle persists via 1.12), the in-app kennisgewing (9.9), or any change to the engine/event (5.8 already fires it). Two templates + the subscriber.

- [x] **Task 1 — `Ink\Tiers\PromotionEmails` collaborator (AC: 1, 2)**
  - [x] New `PromotionEmails` (`HOOK`, `SILWER_TEMPLATE_KEY`, `GOUD_TEMPLATE_KEY`). `register()` subscribes `ink/tier_promoted` (4 args — challenge id not needed) + `registerTemplates()`.
  - [x] `registerTemplates()` registers both Afrikaans-source templates (glossary copy "Baie geluk! Jy is na Silwer/Goud bevorder.", `{skrywer}` greeting, toggle OFF).
  - [x] `onTierPromoted()` returns unless `actor_id === 0`; maps `$to` → key via a match (Silwer/Goud, else null); resolves recipient via `get_userdata()` (`instanceof WP_User` + email; `display_name`→`user_login`); `Notifications::send()`.
- [x] **Task 2 — Wire + deptrac (AC: 2, 3)**
  - [x] `Tiers\Module::register()` now also wires `PromotionEmails`.
  - [x] `deptrac.yaml`: `Notifications` added to the `Tiers` allowlist (commented, mirroring the 4.2 `Entitlement → Notifications` rationale).
- [x] **Task 3 — Author AND run the Pest tests; record the gates (AC: 3)**
  - [x] `tests/Unit/Tiers/PromotionEmailsTest.php` (7 tests: constants; both templates registered + disabled; auto→Silwer one mail; auto→Goud one mail; manual sends nothing; →Meester sends nothing; invalid user sends nothing). Reused the Notifications-consumer harness.
  - [x] `composer test:unit` → **338 passed / 1 skipped** (1454 assertions), zero regressions. `composer cs` (2 files) clean (dropped the trailing unused hook arg). `composer stan` clean (sandbox-off). `composer deptrac` → 3 pre-existing only; the new `Tiers → Notifications` edge is ALLOWED (no violation).

## Dev Notes

- **Two templates because 1.12 only merges `{skrywer}`** — the grade name is baked into each body. Auto-promotion only reaches Silwer/Goud, so two templates are complete; Brons (never a target) and Meester (manual-only) get none.
- **Auto only:** the handler gates on `actor_id === 0`. A redakteur's manual set/correction (5.2, actor = staff id) does NOT email — matches the AC ("Given an auto-promotion").
- **Toggle OFF by default** (the 4.2/4.8 standing convention): the capability is fully wired; production staff enable the sends. The copy is real (glossary line 213), so no `[WAG OP MENSLIKE KOPIE]` placeholder — but the send still respects the fail-safe-OFF toggle.
- **deptrac edge:** `Tiers → Notifications` is the first extension of the Tiers allowlist, mirroring 4.2's `Entitlement → Notifications`. Notifications depends only on Kernel (never back on Tiers), so no cycle; `Entitlement ⟂ Tiers` is untouched.
- **Conflation rule:** the email is a Gradering (competition) recognition; it reads only the grade + the writer's WP account, never entitlement.

### Project Structure Notes

- New: `src/Tiers/PromotionEmails.php`; test `tests/Unit/Tiers/PromotionEmailsTest.php`. UPDATE: `src/Tiers/Module.php` (wire), `deptrac.yaml` (`Tiers: [Kernel, Notifications]`).

### References

- [Source: epics.md#Story-5.10; afrikaans-terms.md line 213 (the approved copy)]
- [Source: src/Tiers/Api.php (ink/tier_promoted, actor 0), src/Notifications/{Api,Template,TemplateStore}.php (1.12)]
- [Source: src/Entitlement/PurchaseActivation.php + tests/Unit/Entitlement/PurchaseActivationTest.php (consumer + test precedent)]
- [Source: deptrac.yaml (Entitlement: [Kernel, Notifications] precedent); project-context.md (Afrikaans source, single-source, conflation, testing rule)]

## Dev Agent Record

### Agent Model Used

claude-opus-4-8 (BMAD dev-story loop)

### Debug Log References

- `composer test:unit` → 338 passed / 1 skipped (1454 assertions).
- `composer cs` (PromotionEmails.php, Module.php) → clean (after dropping the trailing unused hook arg).
- `composer stan` → No errors (sandbox-off).
- `composer deptrac` → 3 pre-existing `Activation → PostTypes`; the new `Tiers → Notifications` edge is ALLOWED (no violation), `Entitlement ⟂ Tiers` holds.

### Completion Notes List

- **Notifications consumer, reusing 1.12** — no new email engine. `PromotionEmails` subscribes the `ink/tier_promoted` event (5.2/5.8) and sends only for an automatic promotion (`actor_id === 0`); a manual staff change never congratulates.
- **Two templates** (Silwer, Goud) because the 1.12 store merges only `{skrywer}` — the grade name is baked into each body; auto-promotion only targets Silwer/Goud, so the pair is complete. Both register with the glossary-approved copy (afrikaans-terms.md line 213) as the Afrikaans gettext source, toggle fail-safe OFF (the 4.2/4.8 convention; staff enable in production).
- **deptrac edge added:** `Tiers → Notifications` (first extension of the Tiers allowlist), mirroring 4.2's `Entitlement → Notifications`. No cycle (Notifications → Kernel only); `Entitlement ⟂ Tiers` untouched.
- **Conflation-clean:** reads the grade + the writer's WP account, never entitlement. Recipient resolution mirrors `PurchaseActivation` (`instanceof WP_User`, `display_name`→`user_login`).
- **No scope creep:** no manual-promotion email, no admin screen, no 9.9 in-app kennisgewing, no engine/event change.

### File List

- `wp-content/plugins/ink-core/src/Tiers/PromotionEmails.php` (NEW)
- `wp-content/plugins/ink-core/src/Tiers/Module.php` (UPDATE — wire PromotionEmails)
- `deptrac.yaml` (UPDATE — `Tiers: [Kernel, Notifications]`)
- `tests/Unit/Tiers/PromotionEmailsTest.php` (NEW)

### Change Log

- 2026-06-26 — Story 5.10 implemented (create-story → dev-story; final Epic-5 story). `PromotionEmails` Notifications consumer — two Afrikaans-source congratulation templates (toggle OFF), sent on auto-promotion (actor 0) to Silwer/Goud via the 1.12 store; `Tiers → Notifications` deptrac edge added. 338 passed / 1 skipped; cs/stan clean; deptrac no new violation. Status → review.

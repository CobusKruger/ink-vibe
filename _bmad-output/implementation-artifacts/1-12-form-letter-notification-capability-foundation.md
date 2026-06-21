---
baseline_commit: c5821d806c1700331ae3a352f097d8a0646c6fe8
---

# Story 1.12: Form-letter + notification capability (foundation)

Status: done

<!-- Note: Validation is optional. Run validate-create-story for quality check before dev-story. -->

## Story

As an `ink-core` developer,
I want the lightweight form-letter + notification capability established in the Foundation phase — by building out the reserved `Ink\Notifications` module (AD-9): a WP-options-backed form-letter/template store (per-template body text + per-event send on/off toggle + a randomized message list), a single greeting-line name-merge resolver (`{skrywer}` → resolved at send time, **not** a template engine), and a transactional-email dispatch + `ink/{module}/{event}` event-consumer seam (Notifications composes and sends via `wp_mail`, gated by the toggle),
so that its downstream consumers — the winners-announcement post (R2, 12A.4), the promotion congratulation email (R3, 5.10), the membership lifecycle emails (R5, 4.8), and the post-receipt notification incl. its randomized message list (R7, 9.11) — build on a ready shared store and depend **backwards** on Foundation rather than on a forward dependency (§14.1; AD-9).

## Acceptance Criteria

1. **Form-letter / template store: WP options-backed, `ink_`-prefixed, per-template body + per-event send toggle + randomized message list — NO custom table, NO template engine.** Given the Notifications module (options-based, minimal dependencies — WP options + Kernel only, per AD-9), when the capability is built, then a store (`Ink\Notifications`) reads/writes, **via the WP options API with `ink_`-prefixed keys**, for each registered template/event: (a) the **form-letter body text**, (b) a **per-event send on/off toggle**, and (c) a **randomized message list** (for R7). It is a flat options-backed config store — **no new custom table** (low-volume, admin-edited config values are a natural fit for the options API, AD-9) and explicitly **not** a configurable rich template engine. _[Source: epics.md#Story-1.12 AC "stores form-letter text (WP options) + name-merge greeting … + per-event send on/off toggles + a randomized message list — **not** a rich template engine"; architecture.md lines 606–609 AD-9 "WP `options`-based stored text per template/event (the `ink_` prefix), with per-event send on/off toggles and the randomized message list for R7. No new custom table"; project-context.md "Prefix everything ink_/Ink\", "Never write raw SQL"]_

2. **Name-merge resolver: a single greeting-line token (`{skrywer}`) resolved at send time — explicitly NOT Twig/Blade/conditionals/loops/WYSIWYG.** Given a stored form-letter body containing the greeting-line token, when a message is composed, then the merge resolver substitutes the **single documented token `{skrywer}`** (e.g. `Beste {skrywer}, …`) from a resolved send-time context, and nothing more — no conditionals, no loops, no nested/iterating template syntax, no WYSIWYG builder. Unknown/unprovided tokens are handled deterministically and documented (left literal or stripped — the dev picks and documents one). The deliberate constraint **is** the decision; anything richer is out of scope. _[Source: epics.md#Story-1.12 AC "name-merge greeting (e.g. 'Beste {skrywer}, …')"; architecture.md lines 610–613 AD-9 "Name-merge placeholder ONLY — a single greeting-line merge token (e.g. `{skrywer}`), resolved at send time. **Not** Twig/Blade/Mustache, not conditionals, not loops, not a WYSIWYG template builder"; docs/afrikaans-terms.md line 30 "skrywer = writer", line 118 "vormbriefsjabloon" (form-letter template)]_

3. **Transactional-email dispatch + `ink/{module}/{event}` event-consumer seam — Notifications composes & sends via `wp_mail`, toggle-gated; it is a downstream consumer, NOT a cross-domain write path.** Given the store + resolver, when a dispatch is requested for a template/event, then Notifications (a) checks the per-event **send toggle** — when **off**, it does **not** send (no `wp_mail`); when **on**, it (b) resolves the template + applies the name-merge and (c) **dispatches a transactional email via `wp_mail`** with an Afrikaans subject/body (Woo/BP's own templated mail stays the platform's). The **`ink/{module}/{event}` subscriber seam** (AD-6 namespaced events) + the **Action Scheduler fan-out** point are established so downstream consumers (R2/R3/R5/R7) attach by subscribing to events — Notifications stays a **downstream event consumer**, **not** a cross-domain *write* path: it must not `use`/call `Ink\Entitlement` or `Ink\Tiers` (THE conflation rule — it subscribes to `ink/...` action *strings* from both domains, holding the AD-8 Deptrac invariant green). _[Source: architecture.md lines 614–620 AD-9 "Notifications expands from BP-only to transactional email … composes and dispatches transactional email via `wp_mail` … It stays a downstream event consumer (AD-6 `ink/...` events + Action Scheduler fan-out), not a cross-domain write path", 654–659 "Notifications is the only shared downstream event consumer … it is not a cross-domain write path", 467–476 AD-6 events + Action Scheduler; project-context.md "transactional emails are entirely Afrikaans"]_

4. **Randomized message list (R7): a stored list + a selector returning a random message — the mechanism, not the R7 content.** Given a template/event configured with a randomized message list (the R7 receipt-notification case), when a message is selected, then the capability returns a **randomly-chosen** message from the stored list (via WP's seeded RNG, `wp_rand()`, so it is mockable/testable), gracefully handling empty/single-item lists. This story builds the **mechanism**; the actual R7 message content + its event wiring ship with 9.11. _[Source: epics.md#Story-1.12 AC "a randomized message list"; architecture.md lines 606–609 AD-9 "the randomized message list for R7"; epics.md line 1278 "a kennisgewing is sent with a **randomized** message from the Story 1.12 form-letter list"; project-context.md "kennisgewing" (notification)]_

5. **Afrikaans-source text + the option store is in NFR-1 leak-scan scope.** Given every default/stored template string, when authored, then it is **Afrikaans as the gettext source** with the `ink-core` text domain and **no English `.mo`** shipped (so gettext returns Afrikaans even under a staff member's forced-English admin locale — §14.15 / Story 1.10). The **options store is documented as a new NFR-1 Afrikaans-leak vector** — admin-authored template text bypasses the build-time `.mo` + page-crawl scan — to be covered by the **standing leak scan** (built later, Story 17.4 / Epic 18) by scanning the stored option values and/or enforcing Afrikaans at the admin authoring boundary. This story records the scope obligation; it does **not** build the scan. _[Source: architecture.md lines 622–628 AD-9 "Admin-authored template text is a new Afrikaans-leak vector not covered today … The standing leak-scan is therefore extended to cover the template/options store"; project-context.md "ink-core ships no English `.mo`", "English-leak check is an automated test … a standing gate"; 1-10 §14.15 no-English-`.mo` policy]_

6. **Built in the `Ink\Notifications` module (Kernel-only deps), built out from the 1.7 skeleton + wired via the Kernel; strict, prefixed, statically verifiable — and NO `.docx`/PhpWord parser.** Given the 1.7 scaffold reserved `Ink\Notifications\{Module,Api}` for "Story 1.12 / AD-9", when this story is implemented, then: (a) `Notifications\Module::register()` is **built out** (mirroring the 1.8 `Engagement\Module` → collaborators house style) and the module is **registered through the Kernel** via `Plugin::addModule( 'notifications', new Notifications\Module() )` in `ink-core.php` (the Engagement precedent at `ink-core.php`), **not** a parallel `add_filter`/`add_action` in the bootstrap; (b) the cross-module surface is the **`Notifications\Api` facade only** (AD-1), though delivery is event-driven; (c) dependencies stay **WP options + Kernel only** — no `use` of other domain modules (keeps Deptrac green, AD-8). Every new/edited `.php` starts `<?php` + `declare(strict_types=1)` + the `Ink\Notifications` (or `Ink\Kernel`) namespace + `defined('ABSPATH')||exit;`; classes `Ink\`-namespaced PascalCase with camelCase methods; any global/procedural surface `ink_`-prefixed; **no raw `$_POST`/`$_GET`**; options via the WP options API (never raw SQL). **No `.docx`/PhpWord parser is added** (explicitly out of scope, AD-9 — paste-text only; the XXE/zip-bomb surface must not be reintroduced). Ready-to-run **Pest** unit tests are authored at the repo-root `tests/Unit/Notifications/` (the Story 1.11 harness home + convention); `php -l`/Pest **execution is deferred** to a real dev/CI env (no PHP/Composer in-repo — the 1.1–1.11 precedent), with structural `python3` checks substituting. _[Source: architecture.md lines 603–604 AD-9 "owned by the Notifications module", 847–850 "Canonical module skeleton … Api.php (the only public facade)", 629–633 AD-9 "No `.docx` / PhpWord parser — explicitly OUT OF SCOPE … the entire untrusted-ZIP / XXE / zip-bomb attack surface … removed from scope and must not be added"; `src/Notifications/{Module,Api}.php` "RESERVED … Story 1.12"; `src/Engagement/Module.php` 1.8 build-out precedent; `ink-core.php` line 62 `addModule('engagement', …)`; Story 1.11 `tests/` harness + `tests/README.md` convention]_

## Tasks / Subtasks

> **Current state (read before starting):** Story 1.7 created `Ink\Notifications\Module` and `Ink\Notifications\Api` as **reserved no-op skeletons** whose docblocks say they "will own … the form-letter / template options store (plain text + name-merge only — NOT a template engine, AD-9) and transactional email. NOTHING is implemented at 1.7; the form-letter/notification capability foundation is **Story 1.12**." The Kernel (`Ink\Kernel\Plugin`) exposes `addModule( string $id, Module $module )` and dispatches each registered module's `register()` on `init`; `ink-core.php` already registers the Engagement module that way (`Kernel\Plugin::instance()->addModule( 'engagement', new Engagement\Module() )`) — **1.12 adds the `'notifications'` registration the same way**. Story 1.8's `Engagement\Module::register()` is the **build-out precedent** (thin bootstrap delegating to a collaborator: `( new Comments() )->register();`). The **Story 1.11 test harness now exists** at the repo root (`composer.json`, `phpunit.xml`, `tests/bootstrap.php` + Brain Monkey; tests live in `tests/Unit/{Module}/`, namespace `Ink\Tests\Unit\{Module}` — see `tests/README.md`), so 1.12's tests go in `tests/Unit/Notifications/`. There is still **no PHP binary / Composer in this authoring environment** (execution deferred to CI, 1.1–1.11 precedent). **Scope is the CAPABILITY FOUNDATION only.** Do **NOT** build: the actual R2/R3/R5/R7 template content + per-event subscriptions (those ship with 12A.4 / 5.10 / 4.8 / 9.11 and depend backwards on this story), BuddyPress notification types (Epic 9.9), an admin settings screen to edit templates (ships with the first consumer that needs its template authored / Epic 9.9), concrete Action Scheduler jobs (ride with their consumers), or the NFR-1 leak-scan tool (Story 17.4 / Epic 18). **Never** add a `.docx`/PhpWord parser (AD-9).

- [x] **Task 1 — Form-letter / template options store (AC: 1, 5, 6)**
  - [x] Add a `Template` definition value object (key, default Afrikaans-source body, default send-toggle, optional message-list) and a `TemplateStore` (options-backed repository) in `src/Notifications/`. `ink_`-prefixed option keys (e.g. `ink_notifications_templates` or per-template `ink_notif_{key}_*`); read/update body, read/set toggle, read message-list — all via `get_option`/`update_option` (never raw SQL).
  - [x] Defaults are **Afrikaans source** (`ink-core` text domain, sentence case); ship **no English `.mo`**. The store returns the Afrikaans default when no admin-overridden option exists (the §14.15 fall-through pattern from Story 1.10).
  - [x] Keep the store a flat config surface — no custom table, no `dbDelta`, no rich-engine structures. Capability-check writes where a write path exists (foundation may expose read + a guarded set; the admin authoring screen itself is deferred).

- [x] **Task 2 — Name-merge resolver (AC: 2, 6)**
  - [x] Add a resolver (method/small class) that substitutes the single greeting-line token `{skrywer}` from a send-time context map (e.g. `[ 'skrywer' => $display_name ]`) via simple string substitution. Support only the documented token set (start with `{skrywer}`); **no** conditionals/loops/nesting/WYSIWYG.
  - [x] Define and document the unknown/unprovided-token behaviour (leave literal **or** strip — pick one, document it in the method docblock). Escape appropriately at the point the merged string is used (email body is plain text via `wp_mail`; if any HTML context, `esc_html`/`wp_kses_post` at output).

- [x] **Task 3 — Transactional-email dispatch + event-consumer seam (AC: 3, 6)**
  - [x] Add a `Notifier` (or `Api`-fronted method) `send( string $template_key, string $to, array $context ): bool` that: reads the per-event toggle → **returns without sending when off**; else resolves the template body + applies the name-merge + composes an Afrikaans subject; dispatches via **`wp_mail`**; returns success. No Woo/BP template duplication.
  - [x] Establish the **`ink/{module}/{event}` subscriber seam** (AD-6): document (and provide the thin plumbing for) how a downstream consumer subscribes an event to a `Notifier::send(...)` call, and where **Action Scheduler** fan-out attaches for bulk/deferred sends. Do **not** wire concrete R2/R3/R5/R7 events here (they ship with their consumers).
  - [x] **Conflation guard:** Notifications must **not** `use` or call `Ink\Entitlement` / `Ink\Tiers` — it listens to `ink/...` action strings only. Verify no such `use` is introduced (keeps the AD-8 Deptrac invariant green).

- [x] **Task 4 — Randomized message list selector (AC: 4)**
  - [x] Add a selector returning a random entry from a template's stored message list using `wp_rand()` (mockable). Handle empty list (return empty/default, documented) and single-item list (return it). This is the R7 mechanism; the R7 content + wiring is 9.11.

- [x] **Task 5 — Build out the module + wire it through the Kernel (AC: 6)**
  - [x] Build out `Notifications\Module::register()` (thin bootstrap → collaborators, mirroring 1.8 `Engagement\Module`): register the event-subscriber plumbing / store as needed. Expose the public surface on `Notifications\Api` (the only cross-module facade, AD-1) — e.g. `Api::send(...)`, `Api::randomMessage(...)`, template accessors.
  - [x] Register the module with the Kernel in `ink-core.php`: `Kernel\Plugin::instance()->addModule( 'notifications', new Notifications\Module() );` (next to the existing `'engagement'` registration). **No** parallel `add_action`/`add_filter` in the bootstrap; **no** feature logic in the Kernel.

- [x] **Task 6 — Afrikaans-source + NFR-1 leak-scan scope; confirm no PhpWord (AC: 5, 6)**
  - [x] Document (in the store/module docblocks) that the options store is a **new NFR-1 Afrikaans-leak vector** (admin-authored text bypasses the build-time `.mo`), to be covered by the standing leak scan (Story 17.4 / Epic 18) — by scanning stored option values and/or enforcing Afrikaans at the authoring boundary. Do **not** build the scan.
  - [x] Confirm **no English `.mo`** is added for `ink-core`; defaults are Afrikaans source. Confirm **no** `.docx`/PhpWord/PhpOffice dependency is introduced anywhere (AD-9).

- [x] **Task 7 — Author ready-to-run Pest unit tests (AC: 1–4)**
  - [x] Create `tests/Unit/Notifications/` (namespace `Ink\Tests\Unit\Notifications`, the Story 1.11 convention) with Brain Monkey + Pest cases: (a) merge resolves `{skrywer}` and leaves/strips unknown tokens per the documented rule; (b) `send()` does **not** call `wp_mail` when the toggle is off, and **does** (with merged Afrikaans body) when on (mock `wp_mail`, the option getters, the toggle); (c) the randomized selector returns a list member (mock `wp_rand`) and handles empty/single-item lists; (d) the store reads the Afrikaans default when no option is set and the override when set (mock `get_option`/`update_option`).
  - [x] Header note matching the 1.8/1.10/1.11 precedent: targets `Ink\Notifications\…`, runs against the Story 1.11 harness (`tests/bootstrap.php` + Brain Monkey), no `ABSPATH` guard.

- [x] **Task 8 — Static verification (no PHP/Composer in-repo; execution deferred) (AC: 1–6)**
  - [x] **Structure:** every new/edited `.php` starts `<?php` + one `declare(strict_types=1);`; `ink-core` class files declare the `Ink\Notifications` (or `Ink\Kernel`) namespace + `defined('ABSPATH')||exit;`; test files carry **no** `ABSPATH` guard and use `Ink\Tests\Unit\Notifications`; balanced braces; no closing `?>`.
  - [x] **Prefix/options/i18n:** option keys `ink_`-prefixed; options via `get_option`/`update_option` (no raw SQL); strings Afrikaans-source with the `ink-core` domain; no raw `$_POST`/`$_GET`.
  - [x] **Conflation/Deptrac:** grep-verify `src/Notifications/` introduces **no** `use Ink\Entitlement` / `use Ink\Tiers`; the module depends on Kernel + WP options only (Deptrac ruleset stays green).
  - [x] **Wiring:** `ink-core.php` registers `'notifications'` via `addModule`; `Notifications\Api` is the only public surface; **no** PhpWord/`.docx` dependency anywhere.
  - [x] **Deferred (cannot run here):** `php -l`, `composer install`, `vendor/bin/pest` — defer to a real dev/CI env (1.1–1.11 precedent); record the structural-check commands + results in the Dev Agent Record.

## Dev Notes

### Architecture / decisions this story builds on
- **AD-9 — the form-letter/notification store is WP options + name-merge only, owned by Notifications, which now also does transactional email.** It is a **downstream event consumer**, never a cross-domain write path. Relocated from former 12A.0 to Foundation Story 1.12 (2026-06-20) so consumers (4.8, 5.10, 9.11, 12A.4) depend **backwards**. [Source: architecture.md lines 591–637; epics.md lines 408–420, 1551]
- **AD-6 — inter-module comms:** synchronous-returns-a-value → facade; fire-and-react cross-cutting → namespaced `ink/{module}/{event}` actions (producers don't know consumers); deferred/bulk → Action Scheduler (bundled with WooCommerce, no new queue). Notifications subscribes; it does not get called directly for most delivery. [Source: architecture.md lines 467–486, 824, 864–865]
- **AD-1 / conflation rule:** Notifications is the only shared downstream consumer of both `Ink\Entitlement` and `Ink\Tiers` events, but must not reference either class — enforced by Deptrac in CI (Story 1.11 `deptrac.yaml`). [Source: architecture.md lines 654–659]
- **§14.15 / Story 1.10 — no English `.mo`:** default template text is Afrikaans source; gettext returns it even under forced-English admin locale.

### Existing code being touched / relied on (read before starting)
- `wp-content/plugins/ink-core/src/Notifications/Module.php` + `Api.php` — reserved no-op skeletons explicitly for "Story 1.12 / AD-9". **Build these out.**
- `wp-content/plugins/ink-core/src/Engagement/Module.php` — the 1.8 build-out precedent (thin `register()` delegating to a collaborator). Mirror its shape.
- `wp-content/plugins/ink-core/src/Kernel/Plugin.php` — `addModule()` / `registerModules()` seam; `register()` dispatched on `init`. Do not modify the Kernel; register through it.
- `wp-content/plugins/ink-core/ink-core.php` — line ~62 registers `'engagement'`; **add the `'notifications'` registration here**, same pattern.
- Story 1.11 harness: `composer.json`, `phpunit.xml`, `tests/bootstrap.php`, `tests/README.md` — author tests at `tests/Unit/Notifications/`.

### Glossary (use these exact terms — Afrikaans source)
- **kennisgewing** = notification (plural *kennisgewings*) [afrikaans-terms.md line 147].
- **vormbrief / vormbriefsjabloon** = form-letter / form-letter template [line 118].
- **skrywer** = writer; the merge token is `{skrywer}`, greeting `Beste {skrywer}, …` [line 30; AD-9].

### Scope boundaries (do NOT build here)
- R2/R3/R5/R7 template **content** + per-event subscriptions → 12A.4 / 5.10 / 4.8 / 9.11 (they depend backwards on this foundation).
- BuddyPress notification **types** → Epic 9.9. Admin template-editing **screen** → ships with the first consumer needing it / 9.9.
- Concrete **Action Scheduler** jobs → ride with their consumers. **NFR-1 leak-scan tool** → Story 17.4 / Epic 18.
- **No `.docx`/PhpWord parser, ever** (AD-9 — paste-text only).

### Environment constraint
No PHP binary / Composer in this authoring environment (1.1–1.11 precedent). Author code + tests **ready-to-run** against the Story 1.11 harness; validate structurally with `python3`; defer `php -l`/`composer install`/Pest execution to a real dev/CI env.

### Project Structure Notes
- New ink-core files under `src/Notifications/` (store, template VO, resolver, notifier/collaborators); built-out `Module.php` + `Api.php`.
- Edited: `ink-core.php` (add `'notifications'` `addModule`).
- New tests under `tests/Unit/Notifications/`.
- No change to the Kernel, the theme, or other modules.

### References
- [Source: _bmad-output/planning-artifacts/epics.md#Story-1.12 (lines 408–420) + relocation note (line 1551) + R7 randomized-message (line 1278) + winners form-letter (line 1602)]
- [Source: _bmad-output/planning-artifacts/architecture.md — AD-9 (591–637), AD-6 (431–486), AD-1 conflation (654–659), module skeleton (847–850), events/Action Scheduler (824, 864–865)]
- [Source: _bmad-output/project-context.md — transactional emails Afrikaans, prefix/strict-types, no raw superglobals, options not raw SQL, no English `.mo`, leak-scan standing gate]
- [Source: docs/afrikaans-terms.md — kennisgewing (147), vormbriefsjabloon (118), skrywer (30)]
- [Source: wp-content/plugins/ink-core/src/Notifications/{Module,Api}.php; src/Engagement/Module.php; src/Kernel/Plugin.php; ink-core.php]

## Dev Agent Record

### Agent Model Used

claude-opus-4-8[1m] (Claude Opus 4.8, 1M context)

### Debug Log References

Static verification (no PHP binary / Composer in the authoring environment — `php -l`/Pest execution deferred to a real dev/CI env, per the 1.1–1.11 precedent and AC-6):

- **PHP structure (all 6 `src/Notifications/*.php`):** start `<?php` + one `declare(strict_types=1);` + `namespace Ink\Notifications;` + `defined('ABSPATH')||exit;`; no closing `?>`; no raw `$_POST`/`$_GET`/`$_REQUEST`.
- **Conflation rule:** `grep` confirms **no** `use Ink\Entitlement` / `use Ink\Tiers` (and no code reference) in `src/Notifications/` — the only mentions are docblock prose explaining the rule. Module depends on `Ink\Kernel\Module` + WP options only → AD-8 Deptrac invariant (`Ink\Entitlement ⟂ Ink\Tiers`) stays green.
- **No PhpWord/`.docx`:** confirmed absent from the new code and both `composer.json`s (AD-9).
- **Options/i18n:** single `ink_`-prefixed option (`ink_notifications_templates`), persisted via `get_option`/`update_option` (no `$wpdb`/raw SQL); default strings are Afrikaans source (`ink-core` domain, no English `.mo`).
- **Kernel wiring:** `ink-core.php` registers `addModule( 'notifications', new Notifications\Module() )` next to `'engagement'`.
- **Tests:** 3 files under `tests/Unit/Notifications/`, namespace `Ink\Tests\Unit\Notifications`, no `ABSPATH` guard — ready-to-run against the Story 1.11 harness.

### Completion Notes List

- **Built out the reserved `Ink\Notifications` module (AD-9 foundation):**
  - `Template` — immutable value object (key, Afrikaans-source default subject/body/messages, default send toggle).
  - `MergeResolver` — substitutes only the whitelisted greeting-line token `{skrywer}`; unknown/unprovided tokens **left literal** (documented choice — a visible `{skrywer}` flags a misconfigured caller rather than emitting "Beste , …"); explicitly not a template engine (no conditionals/loops/nesting/WYSIWYG).
  - `TemplateStore` — options-backed (single `ink_notifications_templates` option), per-key body/subject/toggle/message-list with fall-through to Afrikaans defaults; unregistered/unconfigured event is **fail-safe OFF**; pure-persistence writes (capability-gating is the deferred admin-screen/REST boundary's job, documented).
  - `Notifier` — toggle-gated `wp_mail` dispatch (no send when toggle off or recipient empty), resolves + merges Afrikaans subject/body; `randomMessage()` (R7) via `wp_rand()` with empty/single-item handling.
  - `Api` facade — sole cross-module surface (`registerTemplate`/`send`/`randomMessage`), wired by `Module::register()` via `Api::bootstrap()`, lazily safe.
- **Wired through the Kernel** in `ink-core.php` (`addModule('notifications', …)`, mirroring the 1.8 `'engagement'` precedent); no parallel `add_action`/`add_filter` in the bootstrap; no feature logic in the Kernel.
- **Scope held to the foundation:** no concrete R2/R3/R5/R7 template content or event subscriptions (they ship with 12A.4 / 5.10 / 4.8 / 9.11 and depend backwards); no BuddyPress notification types (9.9); no admin settings screen (deferred to the first consumer needing it / 9.9); no concrete Action Scheduler jobs; no leak-scan tool (17.4/18.x). Following the Story 1.10 precedent, **no orphan example template was registered** — registering an un-consumed Afrikaans string would be gold-plating; the store starts empty and consumers register their definitions.
- **NFR-1 leak-scan scope recorded** in the `TemplateStore` docblock: stored override values are an admin-authored Afrikaans-leak vector the build-time `.mo`/page-crawl scan does not see; the standing scan (17.4/18.x) covers the option values and/or enforces Afrikaans at the authoring boundary.
- **3 ready-to-run Pest unit suites** (merge resolution incl. unknown-token + non-engine cases; store default/override/fail-safe/persist; notifier toggle-off + empty-recipient + merged-send + random-message + empty/single). `vendor/bin/pest`/`php -l` execution deferred to CI (no PHP/Composer in-repo); structural `python3` checks substitute.

### File List

**Added (ink-core):**
- `wp-content/plugins/ink-core/src/Notifications/Template.php`
- `wp-content/plugins/ink-core/src/Notifications/MergeResolver.php`
- `wp-content/plugins/ink-core/src/Notifications/TemplateStore.php`
- `wp-content/plugins/ink-core/src/Notifications/Notifier.php`

**Modified (ink-core):**
- `wp-content/plugins/ink-core/src/Notifications/Module.php` (built out from the reserved no-op skeleton)
- `wp-content/plugins/ink-core/src/Notifications/Api.php` (built out the facade)
- `wp-content/plugins/ink-core/ink-core.php` (register the `'notifications'` module with the Kernel)

**Added (tests):**
- `tests/Unit/Notifications/MergeResolverTest.php`
- `tests/Unit/Notifications/TemplateStoreTest.php`
- `tests/Unit/Notifications/NotifierTest.php`

## Change Log

| Date       | Change                                                                                                                            |
|------------|-----------------------------------------------------------------------------------------------------------------------------------|
| 2026-06-21 | Story 1.12 created (create-story) — form-letter/notification capability foundation spec; status ready-for-dev.                     |
| 2026-06-21 | Implemented (dev-story): built out `Ink\Notifications` (Template/MergeResolver/TemplateStore/Notifier/Api), wired via Kernel, added 3 Pest unit suites; structural verification (execution deferred to real dev/CI env). Status → review. |
| 2026-06-21 | Adversarial code review (3 layers). 1 decision + 1 patch raised → status review → in-progress. |

## Review Findings

Code review (2026-06-21): adversarial 3-layer review (Blind Hunter [diff-only], Edge Case Hunter [diff+project], Acceptance Auditor [diff+spec]). No layer failed outright; findings below. Structural checks PASS: conflation rule clean (no `use Ink\Entitlement`/`Ink\Tiers` — only docblock prose), no PhpWord/`.docx`, no raw `$_POST`/`$_GET`/`$_REQUEST`, no `$wpdb`/raw SQL, single `ink_`-prefixed option, strict_types + `defined('ABSPATH')||exit;` on all `src` files, no closing `?>`, Kernel-wired via `addModule('notifications', …)`. ACs 1/2/3(facade seam)/4/6 substantially met.

**Resolution (2026-06-21):** decision 5a accepted (consumer-side `__()` at registration; contract documented in the `Template` + `Api::registerTemplate()` docblocks) and the `Api::bootstrap()` bug fixed (first-wiring-wins `??=`). Zero open decision/patch findings → Status `done`.

- [x] [Review][Decision] AC-5 gettext sourcing not realized in code — `Template` defaults are raw Afrikaans string literals, never wrapped in `__( …, 'ink-core' )`, so no string is gettext-sourced and the text domain is never applied at the foundation level. AC-5 + Task 1 require defaults be "Afrikaans as the gettext **source** with the `ink-core` text domain". Mitigation: the store registers no orphan template (consumers author the real strings and could call `__()` at registration), but nothing in `Template`/`TemplateStore`/`Api::registerTemplate` standardizes or demonstrates gettext sourcing. DECISION: (a) accept consumer-side `__()` at registration and document the contract in the `Template`/`Api::registerTemplate` docblocks, OR (b) have the foundation own the gettext call. [wp-content/plugins/ink-core/src/Notifications/Template.php:39-45] — RESOLVED (5a, 2026-06-21): consumers wrap `__()` at registration; contract now documented in the `Template` class docblock and `Api::registerTemplate()` docblock. Foundation unchanged (it never sees the literals); `wp i18n make-pot` extracts the literals at the consumer call sites; no English `.mo` ships so the Afrikaans source is the rendered output.
- [x] [Review][Patch] `Api` lazy fallback store/notifier silently discarded by later `bootstrap()` — if any consumer calls `Api::registerTemplate()`/`Api::send()`/`Api::randomMessage()` before `Module::register()` runs (e.g. on `plugins_loaded`, which precedes the `init` module dispatch), `Api` lazily builds its own `TemplateStore`/`Notifier`; when `Module::register()` later calls `Api::bootstrap()` it overwrites `self::$store`/`self::$notifier`, silently dropping any templates registered into the lazy instance. Make `bootstrap()` no-op-if-already-set, or merge registrations, or have `bootstrap()` register into the existing lazy store. [wp-content/plugins/ink-core/src/Notifications/Api.php:45-48] — RESOLVED (2026-06-21, commit 0201be8): `bootstrap()` now first-wiring-wins (`??=`), preserving any templates registered into a pre-bootstrap lazy store.
- [x] [Review][Defer] Mail-header injection surface on merged subject via `wp_mail` — a merge value landing in the subject could in theory carry `\r\n`; PHPMailer encodes the subject so risk is low, and no untrusted merge values flow here yet (consumers supply context in later epics). [wp-content/plugins/ink-core/src/Notifications/Notifier.php:57-60] — deferred, low severity / not yet reachable.
- [x] [Review][Defer] AC-3 "thin plumbing" for the event-consumer + Action Scheduler seam is documentation-only (the `Api::send` facade is the seam); concrete subscriptions are correctly scoped out to consumer epics. [wp-content/plugins/ink-core/src/Notifications/Module.php:46-54] — deferred, acceptable for foundation.

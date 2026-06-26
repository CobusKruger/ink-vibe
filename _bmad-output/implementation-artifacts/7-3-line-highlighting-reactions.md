---
baseline_commit: 95e628c
---

# Story 7.3: Line highlighting + reactions

Status: done

### Review Findings

- [x] [Review][Patch] Write path accepted any published post — added a readable-bydrae gate (`Ink\Engagement\Readable::isBydrae`, gedig/storie/artikel only) so a crafted REST call can't react to a Page/skryfwerk/attachment. [`ReactionController.php`, `Readable.php`]
- [x] [Review][Defer] Reaction toggle has no in-flight button disable (double-click race) — harmless (UNIQUE key prevents dup rows); client polish for the E2E pass (18.8). See deferred-work.md.

## Story

As a lid,
I want to highlight a line and react,
so that I can encourage specific moments. (FR-26, UJ-3)

## Acceptance Criteria

**Given** a work
**When** I select text
**Then** I can add a reaksie (hartjie / duim op / wow) — reactions only, no public inline commentary/annotation ("encouragement, not critique").

1. A logged-in lid can attach a **reaksie** to a specific content line of a poem — one of exactly **hartjie / duim_op / wow** (the `Ink\Kernel\Reaction` enum, the single source). The line is identified by the `data-ink-line` anchor Story 7.2 emits (0-based physical-line index).
2. **Reactions only — no free-form text.** There is NO comment/annotation field anywhere on this path: the store has no body/text column and the REST endpoint accepts only `{post_id, line, reaction}`. (Free-form feedback is the structured Gemeenskapsreaksie of Story 7.4, never an inline annotation here.) This is a load-bearing guarantee — guard it non-vacuously.
3. **One reaction per user per line** (AD-5: a highlight always carries a reaction; consolidated). Reacting again **changes** the reaction (upsert); re-selecting the same reaction **removes** it (toggle off). Enforced by a UNIQUE key `(post_id, line_index, user_id)`.
4. **Engagement is open to any lid — NOT entitlement-gated** (AD-6 §2; FR-24–42). The write path is gated by `is_user_logged_in()` + the REST nonce only; it never calls Entitlement or Tiers (conflation-clean).
5. The write path is the **first `ink/v1` REST endpoint** (`POST/DELETE ink/v1/reaksie`, AD-6 §1): nonce + logged-in permission, input sanitised, validation returns `WP_Error` with `ink_`-coded **Afrikaans** messages. Reads stay server-rendered; REST is the write path.
6. **Reactions land only on content lines, never blank separators** (7.2 contract). Validated at the WRITE layer: the submitted `line_index` must be a content-line index of the target post's stored body (re-tokenised via `GedigBody::tokenize`), else `WP_Error`. A blank-separator index is rejected.
7. The reading surface offers a per-line reaction affordance wired to the endpoint (the `.ink-gedig__line:hover` seam from 7.2). The client is small enqueued JS using the Interactivity API/REST (AD-7); business logic stays server-side. (JS behaviour is E2E-verified later — Story 18.8 — consistent with the project's deferred E2E layer; this story unit-tests the PHP store + REST + validation and structurally guards the enqueue.)
8. The custom table is created/owned/migrated by the Engagement module via `dbDelta` through the Kernel `Schema` registry (AD-5), registered in `ink-core.php` like `PromotionLog`. Counts surfacing ("342 hartjies") is **Story 7.8** — 7.3 provides the store + a `forPost` read it builds on.

## Tasks / Subtasks

- [x] Task 1: `Reaction` enum single-source helper (AC: #1, #5)
  - [x] Added `Ink\Kernel\Reaction::values()` (derived from `self::cases()`) — single source for the REST `enum` arg + validation.
- [x] Task 2: `Ink\Engagement\ReactionStore` custom table (AC: #2, #3, #6, #8)
  - [x] `TABLE='ink_line_reactions'`, `tableName()`, `schemaSql()` (id PK, post_id, line_index int unsigned, user_id, reaction varchar(20), created_at; `UNIQUE KEY user_line (post_id,line_index,user_id)`, `KEY post_id`). NO text/body column.
  - [x] `set()` upsert (prepared `INSERT … ON DUPLICATE KEY UPDATE`), `remove()` (`$wpdb->delete`), `userReaction()` (`get_var` → `Reaction::tryFrom`), `forPost()` (rows, empty→`array()`). Mirrors `PromotionLog` phpcs:disable blocks + GMT timestamp.
- [x] Task 3: `Ink\Engagement\ReactionController` REST (AC: #4, #5, #6)
  - [x] `register()` → `rest_api_init` → `POST` (set/toggle) + `DELETE` (remove) on `ink/v1/reaksie`; `permission()` = `is_user_logged_in()`; args with `enum`=`Reaction::values()`.
  - [x] Pure `validate(line, reactionRaw, postReadable, postContent): ?WP_Error` (Afrikaans `ink_reaksie_*` codes: invalid_post / invalid_reaction / invalid_line) — `line` validated as a CONTENT-line index via `GedigBody::tokenize` (rejects blanks + out-of-range at the write layer). Pure `decideRemoval(?current, requested)` toggle. Thin callbacks: `absint`/`sanitize_key`, `get_post_status`/`get_post`, then `set`/`remove`, returning `{line, reaction, removed}`.
- [x] Task 4: Wire module + schema + theme client (AC: #7, #8)
  - [x] `Engagement\Module::register()` registers `ReactionController`. `ink-core.php` registers the schema provider next to `PromotionLog` and bumps `VERSION`/header to `0.1.1` so `maybeUpgrade` installs the table on existing sites.
  - [x] `assets/js/line-reactions.js` — minimal client: attaches reaction controls to each `[data-ink-line]`, POSTs to `ink/v1/reaksie` with `X-WP-Nonce`, toggles `is-active` off the server response. Enqueued on `is_singular('gedig')` via `ink_foundation_enqueue_line_reactions()`; REST root + `wp_create_nonce('wp_rest')` + post id + Afrikaans labels localised. No business logic in JS.
- [x] Task 5: Tests + gates (AC: all)
  - [x] `tests/Unit/Kernel/ReactionTest.php` (3): `values()` is exactly `['hartjie','duim_op','wow']`, matches `cases()`, `tryFrom` rejects non-reactions.
  - [x] `tests/Unit/Engagement/ReactionStoreTest.php` (7, Mockery `$wpdb`): prefixing; schema dbDelta-compatible + UNIQUE key + **no text/body/comment column** (non-vacuous AC #2 guard); `set` upsert args; `remove` delete; `userReaction`/`forPost` mapping + empty→`array()`.
  - [x] `tests/Unit/Engagement/ReactionControllerTest.php` (8, Brain Monkey): permission logged-in/out; validate rejects unreadable post / unknown reaction / blank-separator line / out-of-range, passes for real content lines (non-vacuous, same fixture); `decideRemoval` toggle; **conflation guard** — controller + store source contain no `Ink\Tiers`/`Ink\Entitlement` (CodeScan, non-vacuous).
  - [x] `composer test:unit` green (424→442, +18), `cs`/`stan` clean, `copy:scan` no new debt, `deptrac` 3 pre-existing violations (0 new), 0 errors/warnings.

## Dev Notes

- **Custom-table pattern** [Source: src/Tiers/PromotionLog.php:37-157]: copy `TABLE`/`tableName()`/`schemaSql()` verbatim in shape — `$wpdb->get_charset_collate()`, two spaces after `PRIMARY KEY`, `KEY name (col)`. Writes use `$wpdb->insert`/`query` with explicit format arrays + the `phpcs:disable WordPress.DB.DirectDatabaseQuery.*` blocks; reads use `$wpdb->prepare` with interpolated (constant) table name + the `PreparedSQL.InterpolatedNotPrepared` disable. Timestamps `current_time('mysql', true)`.
- **Schema registry** [Source: src/Kernel/Schema.php; src/Kernel/Activation.php; ink-core.php:42-60]: register the provider at include time in `ink-core.php` (NOT in a hook — activation fires before init). `Activation::activate()` + `maybeUpgrade()` (admin_init version bump) run `dbDelta` idempotently. `INK_CORE_VERSION` must bump so `maybeUpgrade` installs the new table on existing installs — check the constant in `ink-core.php` and bump the patch.
- **First REST endpoint** [Source: architecture.md AD-6 §1; grep confirms none exist]: 7.3 establishes the `ink/v1` pattern — `register_rest_route('ink/v1','/reaksie', …)` on `rest_api_init`, uniform `permission_callback` + `args` + `WP_Error` Afrikaans. Reads stay server-rendered (the gedig block); REST is writes only.
- **Reaction enum** [Source: src/Kernel/Reaction.php]: `Hartjie/DuimOp/Wow` (values `hartjie/duim_op/wow`). Add only `values()` (single-source list); no business logic in the enum. Validate with `Reaction::tryFrom()`.
- **Line contract** [Source: src/Engagement/GedigBody.php; 7-2 story]: `data-ink-line` = 0-based physical-line index. Validate a submitted `line` by `GedigBody::tokenize($post->post_content)` and confirming a `['type'=>'line','index'=>$line]` token exists — this rejects blank-separator and out-of-range indices at the WRITE layer (the durability rule: enforce the module-owned guarantee on every path, not just the display). Reuses GedigBody (same module — no new deptrac edge).
- **No entitlement gate** [Source: architecture.md AD-6 §2; project-context.md conflation rule]: engagement actions are `is_user_logged_in()` + nonce only. The store + controller carry ZERO `Ink\Tiers` / `Ink\Entitlement` reference — assert it (conflation guardrail), mirroring `tests/Unit/Tiers/ConflationGuardrailTest.php`.
- **Reactions-only** [Source: epics.md#Story 7.3; AD-5a "Gemeenskapsreaksie is the only feedback path" — reactions are NOT commentary]: the store has no text column; the endpoint takes no text. Guard non-vacuously (assert the schema columns AND assert no text/body column).
- **Counts are 7.8** — provide `forPost`/`userReaction` reads; the verb-less `_n()` count surfaces ("342 hartjies") are Story 7.8. Don't build count display here.
- **Front-end** [Source: architecture.md AD-7; functions.php skryf enqueue]: small enqueued JS, conditional on `is_singular('gedig')`, REST + nonce localised. JS isn't unit-tested (no JS harness; E2E deferred to 18.8) — keep it thin and reflect server state; structurally guard the enqueue registration in a theme/functions test if cheap.
- **Testing** [Source: tests/Unit/Tiers/PromotionLogTest.php]: Mockery `$wpdb` (`$GLOBALS['wpdb']`), `shouldReceive('insert'/'query'/'prepare'/'get_results')`, `Mockery::pattern()` for SQL. Brain Monkey for `is_user_logged_in`, `absint`, `sanitize_key`, `get_post`, `get_post_status`, `__`.

### Project Structure Notes

- New ink-core: `src/Engagement/ReactionStore.php`, `src/Engagement/ReactionController.php`; MOD `src/Kernel/Reaction.php` (`values()`), `src/Engagement/Module.php` (wire controller), `ink-core.php` (Schema::register + version bump).
- New theme: `assets/js/line-reactions.js`; MOD `functions.php` (enqueue on gedig singles).
- New tests: `ReactionStoreTest`, `ReactionControllerTest`; MOD/extend `ReactionTest`.
- deptrac: no change — Engagement → Kernel; ReactionController/Store use `GedigBody` + `ReactionStore` (same Engagement layer) + Kernel `Reaction`. No Content/Entitlement/Tiers edge.

### References

- [Source: _bmad-output/planning-artifacts/epics.md#Story 7.3]
- [Source: _bmad-output/planning-artifacts/architecture.md#AD-5, #AD-6, #AD-7]
- [Source: wp-content/plugins/ink-core/src/Tiers/PromotionLog.php]
- [Source: wp-content/plugins/ink-core/src/Kernel/Schema.php, Activation.php, Reaction.php]
- [Source: wp-content/plugins/ink-core/src/Engagement/GedigBody.php]
- [Source: _bmad-output/project-context.md#conflation-rule, #cross-story-durability, #escaping]

## Dev Agent Record

### Agent Model Used

claude-opus-4-8 (BMAD dev-story loop, Epic 7)

### Debug Log References

- phpcs: `array_map`'s `static fn ( self $case )` tripped the WordPress reserved-keyword sniff (`case`) — renamed the closure param to `$reaction`.
- phpstan ran clean once invoked outside the sandbox (the parallel-worker TCP bind is blocked by the sandbox; same as 7.2).

### Completion Notes List

- First `ink/v1` REST endpoint in the codebase — establishes the AD-6 write-path pattern (route + `permission_callback` = logged-in + REST nonce + Afrikaans `WP_Error`).
- First module-owned custom table for Engagement (`ink_line_reactions`), installed via the Kernel `Schema` registry like `PromotionLog`; bumped `INK_CORE_VERSION` to 0.1.1 so `maybeUpgrade()` creates it on in-place updates.
- One-reaction-per-user-per-line via a UNIQUE key + upsert; re-selecting the same reaction toggles off (`decideRemoval`). Reactions-only — no text column anywhere (guarded non-vacuously in the schema test).
- AC #6 enforced at the WRITE layer: a reaction's `line` must be a real content-line index (`GedigBody::tokenize`), so blank separators / out-of-range indices are rejected server-side, not just hidden in the UI — the cross-story durability rule applied to the 7.2 anchor contract.
- Conflation-clean & not entitlement-gated: controller + store reference zero `Ink\Tiers`/`Ink\Entitlement` (asserted). Engagement → Kernel only; no new deptrac edge (reuses `GedigBody`/`ReactionStore` in-module + Kernel `Reaction`).
- Counts surfacing ("342 hartjies") deferred to Story 7.8; `forPost`/`userReaction` reads provided as its foundation.
- Front-end is a thin enqueued client (gedig singles only); JS behaviour is E2E-verified later (18.8) per the project's deferred E2E layer — the PHP store + REST + validation are fully unit-tested here.
- Tests 424→442 (+18); cs/stan clean; copy:scan no new debt; deptrac 0 new violations (3 pre-existing baseline).

### File List

- `wp-content/plugins/ink-core/src/Kernel/Reaction.php` (MOD — `values()`)
- `wp-content/plugins/ink-core/src/Engagement/ReactionStore.php` (NEW — `ink_line_reactions` table)
- `wp-content/plugins/ink-core/src/Engagement/ReactionController.php` (NEW — `ink/v1/reaksie` REST)
- `wp-content/plugins/ink-core/src/Engagement/Module.php` (MOD — wire ReactionController)
- `wp-content/plugins/ink-core/ink-core.php` (MOD — Schema::register + VERSION 0.1.1)
- `wp-content/themes/ink-foundation/assets/js/line-reactions.js` (NEW)
- `wp-content/themes/ink-foundation/functions.php` (MOD — enqueue on gedig singles)
- `tests/Unit/Kernel/ReactionTest.php` (NEW)
- `tests/Unit/Engagement/ReactionStoreTest.php` (NEW)
- `tests/Unit/Engagement/ReactionControllerTest.php` (NEW)
- `_bmad-output/implementation-artifacts/7-3-line-highlighting-reactions.md` (NEW — this story)

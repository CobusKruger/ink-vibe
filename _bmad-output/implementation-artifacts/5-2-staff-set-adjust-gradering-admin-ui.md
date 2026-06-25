---
baseline_commit: 3fe8b510feff44d211fc81110412edb580e967a1
---

# Story 5.2: Staff set/adjust Gradering admin UI

Status: review

<!-- Note: Validation is optional. Run validate-create-story for quality check before dev-story. -->

> **Build-order note:** developed AFTER Story 5.3 (the `ink_tier_history` log substrate). This story adds the **single write path** `Tiers\Api::promote()` (which appends to 5.3's `PromotionLog`) and the staff admin UI that calls it.

## Story

As a redakteur,
I want to set a writer's Gradering in any direction with a reason,
so that I can promote, correct, or assign Meester with an audit trail. (FR-12, UJ-5)

## Acceptance Criteria

1. **A redakteur can set any writer's Gradering in any direction — including Meester (the only path to Meester) — recording a reason and an optional linked challenge, and every change writes a `graderingsgeskiedenis` log entry.** Given the admin UI on a user's edit screen, when a `MANAGE_TIERS` holder changes a writer's Gradering, then they can set it to any grade (up, down, or to **Meester** — the manual-only terminal grade, which has NO other path), enter a **reason**, optionally link an **uitdaging** (challenge), and on save a change-log entry is written via `Ink\Tiers\PromotionLog::record()` (actor = the acting staff user id, from→to, reason, optional challenge link). _[Source: epics.md#Story-5.2 AC; architecture.md lines 269-273 (`Tiers::promote()` the sole write path, writes the graderingsgeskiedenis log: actor, date, reason, from→to, optional challenge link), lines 280-283 (Meester manual-only, staff-assigned); afrikaans-terms.md lines 68-75 (Gradering / Meester / bevorder / graderingsgeskiedenis terms); src/Tiers/PromotionLog.php (5.3 — `record()`)]_

2. **`Ink\Tiers\Api::promote()` is THE sole write path for `ink_writer_tier` — it writes the meta, stamps a normalised `ink_tier_promoted_at`, appends the audit record, and fires an event seam; it never reads `Ink\Entitlement`.** Given a Gradering change (manual now, automatic in 5.8), when `Api::promote( int $user_id, Tier $to, int $actor_id = 0, string $reason = '', int $challenge_id = 0 ): bool` runs, then it: reads the current grade via `Api::forUser()` (5.1); on an actual change (`from !== to`) writes `ink_writer_tier` = `$to->value` and `ink_tier_promoted_at` = a **canonical normalised GMT datetime** (`current_time( 'mysql', true )` — closing the 2.3 "validate/normalise the `ink_tier_promoted_at` datetime format in the `Tiers::promote()` writer" deferral); appends `PromotionLog::record( ... )`; fires `do_action( 'ink/tier_promoted', $user_id, $from, $to, $actor_id, $challenge_id )` (the seam Story 5.10's congratulation email subscribes to); and returns `true`. A no-op change (`from === to`) writes nothing, logs nothing, and returns `false`. `actor_id = 0` denotes the automatic engine (5.8); a manual change passes the staff user id. `promote()` references only the Kernel `Tier` + `Ink\Tiers\PromotionLog` + WordPress — zero `Ink\Entitlement` (THE conflation rule). _[Source: architecture.md lines 269-283 (`Tiers::promote()` SOLE write path + win-count reset in 5.7), line 483 (`ink/...` event surface); deferred-work.md "Deferred from: code review of Story 2.3" ("validate/normalise the `ink_tier_promoted_at` datetime format in the `Tiers::promote()` writer"); src/Tiers/Api.php (5.1 `forUser()`), src/Kernel/Tier.php; deptrac.yaml (`Tiers: [Kernel]`)]_

3. **The UI is a capability-gated user-profile section using the sanctioned `$_POST` admin pattern (nonce → cap → unslash + sanitize → write); `MANAGE_TIERS` is the gate.** Given the WordPress user-edit screen, when the page renders for a `MANAGE_TIERS` holder, then a "Gradering" section shows the writer's current grade and a control to set a new grade (the `Tier` cases, labelled from the `Ink\I18n\Terms` registry — never bare literals), a reason field, and an optional uitdaging select; for a non-holder nothing is rendered. On submit, the save handler verifies a nonce, re-checks `current_user_can( Capabilities::MANAGE_TIERS )`, `wp_unslash` + sanitises every field (grade coerced through `Tier::tryFrom()`, reason `sanitize_text_field`, challenge `absint`), and calls `Api::promote()` with `actor_id = get_current_user_id()`. It never reads a raw superglobal. **`MANAGE_TIERS` is already granted** to `editor` (redakteur) + `administrator` at activation (Story 3.3, `Capabilities::grantToEditor()`), closing the 2.3 "grant `MANAGE_TIERS` to this role" deferral; **per-target scope** is the editorial model — a `MANAGE_TIERS` holder may set ANY writer's grade (redakteurs manage all writers), so the cap (not per-author ownership) is the authorization boundary, recorded here as the deliberate decision closing the 2.3 "decide per-target scoping" deferral. _[Source: epics.md#Story-5.2 ("Deferred from Epic 2 review"); deferred-work.md "Deferred from: code review of Story 2.3" (grant `MANAGE_TIERS`; per-target scoping; the `auth_callback` ignores `$object_id`); src/Kernel/Capabilities.php (`MANAGE_TIERS`, `grantToEditor()` grants it to editor+admin at activation); src/Content/FieldSets.php (the sanctioned nonce → cap → `wp_unslash` + `sanitize_*` → write `$_POST` pattern — the ONLY other `$_POST` site); src/I18n/Terms.php (the grade labels `gradering`/`brons`/`silwer`/`goud`/`meester` already registered); src/Kernel/Tier.php]_

4. **WP-house-rules + Afrikaans admin + conflation-clean + authored AND PASSING Pest tests.** Given the project rules, when this story is built, then: every new `.php` is `<?php` + `declare(strict_types=1)` + `namespace Ink\Tiers;` + `defined('ABSPATH')||exit;`; classes PascalCase / methods camelCase; option/nonce/meta keys `ink_`-prefixed single-source constants; ALL output escaped (`esc_html`/`esc_attr`), ALL input sanitised, nonce on the state-changing save; no raw `$_POST`/`$_GET` (the sanctioned pattern only); no string-interpolated SQL. Admin chrome is Afrikaans (`ink-core` admin surfaces are Afrikaans; grade/term labels via `Terms`, generic field labels authored in Afrikaans as the gettext source, mirroring `FieldSets`' "besonderhede"). The Tiers module is wired into the `ink-core.php` bootstrap and `Tiers\Module::register()` registers the admin collaborator (no logic in the theme/`functions.php`). Pest unit tests are authored at `tests/Unit/Tiers/` and **run with `composer test:unit`; the full suite passes before done** (baseline 294 passed / 1 skipped — zero regressions). `composer cs` / `stan` / `deptrac` run and recorded; deptrac green, no new `Tiers` edge. _[Source: project-context.md (strict types, prefix, escape-on-output/sanitise-on-input/nonces, no raw superglobals, single-source, Afrikaans admin for ink-core, "No business logic in the theme", **testing rule 2026-06-22**; THE conflation rule); architecture.md AD-1, AD-8; src/Content/FieldSets.php (admin pattern); deptrac.yaml]_

## Tasks / Subtasks

> **Current state (read before starting):**
> - **`Ink\Tiers\Api::forUser()` (5.1)** is the typed grade read; **`Ink\Tiers\PromotionLog::record()` (5.3)** is the audit append; **`Ink\Kernel\Tier` (5.1)** has `cases()`, `default()`, the backing strings. Reuse all three; do NOT re-derive.
> - **`Tiers::promote()` does NOT exist yet — build it here** (this story's Task 1). It is the SOLE write path for `ink_writer_tier`; 5.8 (auto engine) will CALL it (actor 0) and 5.7 will ADD a win-count reset inside it. Design the signature so those extensions are additive.
> - **`MANAGE_TIERS` is already granted** to editor + administrator at activation (`Capabilities::grantToEditor()`, Story 3.3). Do NOT re-grant. The 2.3 "until mapped the auth_callback denies everyone" concern is already resolved.
> - **The Tiers module is NOT in the `ink-core.php` `addModule` list yet** (5.3 only added an include-time `Schema::register()`). This story ADDS `Tiers\Module` to the bootstrap (it now needs `init`/admin hooks) and implements `Tiers\Module::register()` to wire the admin collaborator. The 5.3 include-time `Schema::register()` line STAYS as-is.
> - **The `$_POST` admin pattern is established in `Ink\Content\FieldSets`** (nonce → guards → `current_user_can` → `wp_unslash` + `sanitize_*` → write). Mirror it EXACTLY for the user-profile save (`edit_user_profile_update`). This becomes the SECOND sanctioned `$_POST` site.
> - **Grade + term labels already exist in `Ink\I18n\Terms`** (`gradering`/`brons`/`silwer`/`goud`/`meester`, lines 115-119). Use `Terms::label( $tier->value )` for option labels and `Terms::label('gradering')` for the section. `I18n` is not a deptrac layer (uncovered), and `FieldSets` already depends on `Terms` — no new tracked edge.
> - **The `uitdaging` CPT exists** (registered by `Content\PostTypes`); query recent uitdagings with `get_posts()` for the optional challenge select. Do NOT couple to the Challenges module (Epic 12) — a plain `get_posts` of the CPT is fine. The challenge link is stored as the post id (`absint`).
> - **`$wpdb` Mockery-on-global test pattern** established in `PromotionLogTest` (5.3) — reuse it for `promote()` (which calls `PromotionLog::record()` → `$wpdb->insert`).
>
> **Scope is the SET/ADJUST WRITE PATH + ADMIN UI ONLY.** Do NOT build: win-count or its reset (5.7 — `promote()` gets the reset there), the auto engine (5.8), profile DISPLAY of the grade or history (5.4), the congratulation email (5.10 — only the `do_action` seam is added here), discovery (5.5), or any history-list rendering (keep the admin box to the set form + current grade).

- [x] **Task 1 — `Tiers\Api::promote()` the sole write path (AC: 1, 2)**
  - [x] Added `Api::promote( int $user_id, Tier $to, int $actor_id = 0, string $reason = '', int $challenge_id = 0 ): bool` — reads `forUser()`, no-ops + returns false when `from === to`, else writes both meta keys (`ink_writer_tier`, normalised GMT `ink_tier_promoted_at`), appends `PromotionLog::record()`, fires `ink/tier_promoted`, returns true.
  - [x] Docblock records: SOLE write path; `actor_id = 0` = the engine; 5.7 adds the win-count reset; `ink/tier_promoted` is the 5.10 seam; conflation-clean.
- [x] **Task 2 — `Tiers\AdminProfile` admin UI collaborator (AC: 1, 3)**
  - [x] New `Ink\Tiers\AdminProfile` with nonce + field-name constants. `register()` → `edit_user_profile` + `edit_user_profile_update`.
  - [x] `renderField( WP_User $user )` — cap-gated; renders the `Terms`-labelled Gradering section: current grade, a grade `<select>` over `Tier::cases()` (labels via `Terms`, current `selected`), a reason input, and an optional uitdaging `<select>` (`get_posts`, "Geen" first). Nonce field; every value escaped.
  - [x] `save( int $user_id )` — sanctioned `$_POST` path mirroring `FieldSets`: nonce → `current_user_can( MANAGE_TIERS )` → `wp_unslash` + `Tier::tryFrom()`/`sanitize_text_field`/`absint` → `Api::promote(..., get_current_user_id(), ...)`. No raw superglobal.
- [x] **Task 3 — Wire the Tiers module (AC: 4)**
  - [x] `ink-core.php` bootstrap now `addModule( 'tiers', new Tiers\Module() )`; `Tiers\Module::register()` wires `( new AdminProfile() )->register()`. The 5.3 include-time `Schema::register()` line kept.
  - [x] Module docblock notes `MANAGE_TIERS` is already granted at activation (3.3); no grant added.
- [x] **Task 4 — Author AND run the Pest tests; record the gates (AC: 4)**
  - [x] `tests/Unit/Tiers/PromoteTest.php` (5 tests: write+log+event, no-op, downward, Meester, actor-default-0) + `tests/Unit/Tiers/AdminProfileTest.php` (6 tests: no-nonce, no-cap, valid promote w/ sanitized values + acting actor, invalid grade rejected, render w/ cap, render empty w/o cap).
  - [x] `composer test:unit` → **305 passed / 1 skipped** (1369 assertions); baseline 294/1 → **+11 new, zero regressions**. `composer cs` (4 files) clean. `composer stan` clean (sandbox-off). `composer deptrac` → 3 pre-existing `Activation → PostTypes` violations only, **no new `Tiers` edge**.

## Dev Notes

- **`promote()` is the spine of Epic 5.** 5.8 calls it with `actor_id = 0`; 5.7 adds `delete_user_meta`/reset of `ink_tier_win_count` inside it; 5.10 hooks `ink/tier_promoted`. Keep it minimal and additive — meta write + log + event now.
- **2.3 deferrals closed here:** (a) `MANAGE_TIERS` grant — already done in 3.3, verified; (b) per-target scoping — decided: a global editorial cap (redakteurs manage all writers), the cap is the boundary; (c) `ink_tier_promoted_at` format — `promote()` now writes a canonical `current_time('mysql', true)`, so the field is always a valid normalised datetime regardless of any historical raw write.
- **`edit_user_profile` vs `show_user_profile`:** use `edit_user_profile` (+ `edit_user_profile_update`) — staff editing ANOTHER user. A writer never sets their own grade. (My-Profiel DISPLAY of one's own grade is read-only, Story 5.4.)
- **Afrikaans:** the section heading + grade options come from `Terms` (glossary-backed); generic field labels ("Rede", "Gekoppelde uitdaging", a "Geen" option) are admin chrome authored in Afrikaans as the gettext source, exactly as `FieldSets` authors "besonderhede"/"Uitgawedatum". No new glossary CONCEPT is introduced (reason/linked-challenge are generic chrome, not controlled vocabulary). No AI-translated prose.
- **Conflation rule:** the entire write path + UI references only Kernel `Tier`, `Ink\Tiers\PromotionLog`, `Ink\I18n\Terms`, `Ink\Kernel\Capabilities`, and WordPress. Zero `Ink\Entitlement`. Setting a grade must never touch membership; deptrac confirms.
- **Second `$_POST` site:** annotate the `wp_unslash` access with the same `phpcs:ignore ...InputNotSanitized` note `FieldSets` uses where sanitisation is on the following line; nonce + cap are verified first.

### Project Structure Notes

- New: `src/Tiers/AdminProfile.php`; tests `tests/Unit/Tiers/AdminProfileTest.php` (+ promote tests in ApiTest or a new file). UPDATE: `src/Tiers/Api.php` (`promote()`), `src/Tiers/Module.php` (`register()` wires AdminProfile), `ink-core.php` (addModule 'tiers').
- First Tiers init/admin hook; first `do_action('ink/...')` event in Tiers.

### References

- [Source: epics.md#Story-5.2]
- [Source: architecture.md lines 269-283, 463-468, 483; AD-1, AD-6, AD-8]
- [Source: deferred-work.md "Deferred from: code review of Story 2.3 — user-meta"]
- [Source: src/Tiers/Api.php, src/Tiers/PromotionLog.php, src/Kernel/{Tier,Capabilities}.php, src/I18n/Terms.php, src/Content/FieldSets.php, ink-core.php]
- [Source: docs/afrikaans-terms.md lines 68-75 (Gradering/Meester/bevorder/graderingsgeskiedenis)]
- [Source: deptrac.yaml; project-context.md (admin Afrikaans, $_POST pattern, escape/sanitise/nonce, conflation rule, testing rule)]

## Dev Agent Record

### Agent Model Used

claude-opus-4-8 (BMAD dev-story loop)

### Debug Log References

- `composer test:unit` → 305 passed / 1 skipped (1369 assertions).
- `composer cs` (Api.php, AdminProfile.php, Module.php, ink-core.php) → clean (after annotating the deliberate `ink/...` event-name sniff).
- `composer stan` → No errors (sandbox-off).
- `composer deptrac` → 3 pre-existing `Activation → PostTypes`; no new edge, no `Tiers` violation.

### Completion Notes List

- **`Api::promote()` is the spine of Epic 5** — the sole `ink_writer_tier` write path. 5.8 will call it with `actor_id = 0`; 5.7 will add the `ink_tier_win_count` reset inside it; 5.10 hooks the new `ink/tier_promoted` event. Built minimal + additive: meta write + normalised `ink_tier_promoted_at` + log append + event.
- **First `do_action` in ink-core.** Used the `ink/...` slash event-surface convention (architecture line 483) and annotated the WPCS underscore sniff — this sets the precedent for all future first-party domain events.
- **Three 2.3 deferrals closed:** (a) `MANAGE_TIERS` grant — verified already done in 3.3; (b) per-target scoping — decided as a global editorial capability (redakteurs manage all writers; the cap is the boundary), recorded in the AdminProfile docblock; (c) `ink_tier_promoted_at` format — `promote()` writes a canonical `current_time('mysql', true)`.
- **Admin UI** on `edit_user_profile` (staff editing another user), cap-gated, second sanctioned `$_POST` site (mirrors `FieldSets`). Grade options + section labelled from the `Terms` registry (no bare literals); generic field labels ("Rede vir verandering", "Gekoppelde uitdaging (opsioneel)", "Geen") authored in Afrikaans as the gettext source — no AI translation, no new glossary concept.
- **Conflation rule intact:** the whole write path + UI reference only Kernel `Tier`/`Capabilities`, this module, and `Ink\I18n\Terms`; zero `Ink\Entitlement`. Deptrac confirms `Tiers: [Kernel]` holds.
- **No scope creep:** no win-count (5.7), no auto engine (5.8), no profile/history display (5.4/5.5), no email body (5.10 — only the event seam).

### File List

- `wp-content/plugins/ink-core/src/Tiers/Api.php` (UPDATE — `promote()` sole write path + `ink/tier_promoted` event)
- `wp-content/plugins/ink-core/src/Tiers/AdminProfile.php` (NEW — staff set/adjust user-profile UI)
- `wp-content/plugins/ink-core/src/Tiers/Module.php` (UPDATE — `register()` wires AdminProfile)
- `wp-content/plugins/ink-core/ink-core.php` (UPDATE — addModule 'tiers')
- `tests/Unit/Tiers/PromoteTest.php` (NEW)
- `tests/Unit/Tiers/AdminProfileTest.php` (NEW)

### Change Log

- 2026-06-26 — Story 5.2 implemented (create-story → dev-story, after 5.3). `Tiers\Api::promote()` sole write path (meta + normalised promoted_at + log append + `ink/tier_promoted` event) and the `MANAGE_TIERS`-gated `AdminProfile` set/adjust UI. Closed three Story-2.3 deferrals. 305 passed / 1 skipped (+11); cs/stan clean; deptrac no new edge. Status → review.

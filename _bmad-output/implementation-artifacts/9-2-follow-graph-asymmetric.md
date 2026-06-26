---
baseline_commit: d378725
---

# Story 9.2: Follow graph (asymmetric)

Status: review

<!-- Note: Validation is optional. Run validate-create-story for quality check before dev-story. -->

## Story

As a lid,
I want to follow a skrywer one-way,
so that I can track writers without mutual consent. (FR-38, UJ-3)

## Acceptance Criteria

**Given** the `ink-core` follow graph
**When** I volg a skrywer
**Then** follow is asymmetric with volgeling/following counts and Volg / Volg tans UI (replaces friendships, no BuddyPress friend add-on).

1. The follow graph is a **custom `ink-core` table** `{$wpdb->prefix}ink_follows` (AD-5) with asymmetric edges: `user_id` (the follower) → `followee_id` (the followed skrywer), `created_at` UTC. A `UNIQUE KEY (user_id, followee_id)` dedups (a repeat follow is a no-op, not a duplicate row); `KEY followee_id` powers the reverse volgeling-count query; `KEY user_id` powers the member's following list (the 9.3 feed). Registered through the Kernel `Schema` registry at activation — **NOT** BuddyPress Friends, **no** BuddyPress follow add-on.
2. **Asymmetric** — following is one-way and needs no mutual consent: `follow(A, B)` creates exactly the A→B edge and does not imply B→A (contrast the migration's friendship→**two** mutual follow records). `unfollow` is idempotent.
3. **Self-follow is rejected** — `follow(A, A)` is a no-op (a user cannot follow themselves); enforced in the store and validated at the REST boundary with an Afrikaans `WP_Error`.
4. **Counts:** the graph exposes a **volgeling count** (how many follow a skrywer — `COUNT` over `followee_id`) and a **following count** (how many a member follows — `COUNT` over `user_id`), both verb-less and pluralized with `_n()` using the glossary's `volgeling`/`volgelinge` (NEVER "volger"). Counts are read through the `Social\Api` facade.
5. **REST write path** `ink/v1/volg` (Afrikaans noun, AD-6): `POST` follows, `DELETE` unfollows. Gated by `is_user_logged_in()` + the REST nonce ONLY — **never** entitlement- or tier-gated (THE conflation rule: following is open to any lid; it is not a paid/competition capability). Validation (target is a real user, not self) returns an Afrikaans `WP_Error`.
6. **Volg / Volg tans toggle UI** — a server-rendered `ink/volg-knoppie` block renders in its correct initial state for the current viewer (`isFollowing()` → "Volg tans", else "Volg"), shown only to a logged-in lid who is **not** the target skrywer; the enqueued client flips it through `ink/v1/volg`. Labels come from the **glossary-backed Terms registry** (`Volg` / `Volg tans` — human-authored, approved; `volgeling` for counts), never inline literals. The block takes a target-skrywer id so Story 9.4 (Skrywerprofiel) and skrywer cards can place it.
7. **`Social\Api` facade** is the sole cross-module surface (AD-1): `isFollowing()`, `followerCount()`, `followingCount()`, `followeeIdsFor(user)` (the ids a member follows — consumed by the 9.3 following-feed and 9.4 profile). No other module touches `FollowStore` directly.
8. **Three-layer & conflation-clean:** all follow logic in `ink-core` (`Ink\Social`); the theme only embeds the block. Zero `Ink\Entitlement` / `Ink\Tiers` coupling (follow is open; it is neither an entitlement nor a tier signal). `deptrac`: Social may depend on Kernel + I18n (Terms) + Content (writer check) — declare any new edge.

## Tasks / Subtasks

- [x] Task 1: Follow-graph store (`Ink\Social\FollowStore`) (AC: #1–#4)
  - [x] `TABLE = 'ink_follows'`; `tableName()` (prefixed); `schemaSql()` dbDelta DDL (`id` PK, `user_id`, `followee_id`, `created_at`; `UNIQUE KEY user_followee (user_id,followee_id)`, `KEY followee_id`, `KEY user_id`) — mirror `ReadingListStore` exactly (two spaces after `PRIMARY KEY`, `get_charset_collate()`).
  - [x] `follow(int $user, int $followee): bool` — `INSERT ... ON DUPLICATE KEY UPDATE created_at` (dedup), **returns false without writing when `$user === $followee` or either id ≤ 0** (self-follow guard). `unfollow(int,int): bool` (idempotent `$wpdb->delete`). `isFollowing(int,int): bool`.
  - [x] `followerCount(int $followee): int` (COUNT over `followee_id`), `followingCount(int $user): int` (COUNT over `user_id`), `followeeIdsFor(int $user): list<int>` (the ids a member follows, newest first — for the 9.3 feed). Bound `$wpdb->prepare`, documented `phpcs:ignore` for the direct-query class (same as `ReadingListStore`).
- [x] Task 2: Count labels + Terms (AC: #4, #6)
  - [x] Add glossary-backed keys to `I18n\Terms::map()`: `volg` ("Volg"), `volg_tans` ("Volg tans"), `volg_nie_meer` ("Volg nie meer nie") — all human-authored/approved (afrikaans-terms.md §Deel "volg").
  - [x] `Social\FollowCounts::volgelingLabel(int $n): string` — verb-less `_n( '%s volgeling', '%s volgelinge', $n, 'ink-core' )` with `number_format_i18n` (mirror the 7.8 verb-less reaction counts; "volgeling"/"volgelinge" are approved, NOT "volger").
- [x] Task 3: REST write path (`Ink\Social\FollowController`) (AC: #5)
  - [x] `register()` → `rest_api_init`; `POST`+`DELETE` on `ink/v1/volg` with a `followee_id` integer arg; `permission()` = `is_user_logged_in()`. `handleFollow`/`handleUnfollow` `absint` the param, `validate()` (pure) and call the store. Return `{ followee_id, following: bool }`.
  - [x] `validate( bool $targetIsUser, bool $isSelf ): ?WP_Error` (pure) — `ink_volg_invalid_target` ("Hierdie skrywer is nie beskikbaar nie.") when not a real user; `ink_volg_self` ("Jy kan nie jouself volg nie.") when self. Afrikaans, glossary-clean.
- [x] Task 4: Toggle block (`Ink\Social\FollowToggle`) (AC: #6)
  - [x] `BLOCK = 'ink/volg-knoppie'`; register on `init` (`register_block_type` guard). `render( $attributes )` — resolve the target skrywer id (block attribute `skrywerId`, else queried author); return '' when logged-out, when no target, or when the target IS the viewer. `toHtml( int $skrywer_id, bool $following ): string` (pure) — button with `data-ink-skrywer`, `aria-pressed`, `is-following` class, label `Volg tans`/`Volg` from `Terms::label()`. Escaped.
  - [x] `.ink-volg-knoppie*` token-only styles in `theme.json` (mirror `.ink-leeslys-knoppie`).
- [x] Task 5: Facade + wiring (AC: #7, #1)
  - [x] `Social\Api`: `isFollowing()`, `followerCount()`, `followingCount()`, `followeeIdsFor()` delegating to `FollowStore`; `volgelingLabel()` delegating to `FollowCounts`. (The sole public Social surface.)
  - [x] `Social\Module::register()` — wire `FollowController` + `FollowToggle` (the store/api are stateless statics). Register the DDL in `ink-core.php`: `Kernel\Schema::register( Social\FollowStore::TABLE, [Social\FollowStore::class, 'schemaSql'] )`.
- [x] Task 6: Tests + gates (AC: all)
  - [x] `tests/Unit/Social/FollowStoreTest.php`: self-follow returns false and writes nothing (non-vacuous — a valid A→B DOES write); dedup (`ON DUPLICATE KEY`); unfollow idempotent; the count/list SQL targets the right column + binds ids (mock `$wpdb`). Mirror `ReadingListStoreTest`.
  - [x] `tests/Unit/Social/FollowControllerTest.php`: `validate` rejects non-user + self with the right Afrikaans codes, passes a valid distinct target; routes + `is_user_logged_in` permission.
  - [x] `tests/Unit/Social/FollowToggleTest.php`: `toHtml` renders "Volg"/"Volg tans" by state, escapes, sets `aria-pressed`; `render` returns '' logged-out and when target === viewer (non-vacuous — a third-party target DOES render).
  - [x] `tests/Unit/Social/FollowCountsTest.php`: singular `volgeling` at 1, plural `volgelinge` at 0/2+.
  - [x] `composer test:unit` green; `composer stan` clean; `composer cs` 0 errors; `composer copy:scan` no new debt (all copy is glossary-approved); `composer deptrac` clean (declare Social→Content/I18n if introduced).

## Dev Notes

- **Mirror the leeslys store exactly** [Source: src/Engagement/ReadingListStore.php; architecture.md#AD-5 "Follow graph (volg) → custom table — asymmetric edges, bidirectional indexed queries"]: `ink_follows` is structurally the leeslys table with `(user_id, followee_id)` instead of `(user_id, post_id)`. Same dbDelta DDL conventions, same `INSERT ... ON DUPLICATE KEY` dedup, same `$wpdb->delete` idempotent remove, same documented `phpcs:ignore` for the direct-query class. The reverse index (`KEY followee_id`) is what makes the volgeling count and "who follows this writer" cheap.
- **Asymmetric, replaces friendships** [Source: project-context "Follow is custom in ink-core (asymmetric, one-way). BuddyPress Friend Connections are OFF"; afrikaans-terms.md line 152 "Eenrigting sosiale verbinding … Asimmetries; vervang die vorige vriendskapsmodel"; Story 9.1 forced `friends` off]: one edge per follow; do NOT mirror a reverse edge. The migration (Epic 16) converts each legacy friendship into TWO mutual follow records — that is a migration concern, not this store's behavior.
- **Glossary terms are human-authored — use them, don't invent** [Source: afrikaans-terms.md lines 152–154, 187–188, 207; project-context Afrikaans-first]: `volg` (action/code), button `Volg` → `Volg tans` once following, `Volg nie meer nie` to unfollow, follower = `volgeling` (plural `volgelinge`, NEVER "volger"), toast "Jy volg nou [naam]." All approved — register them in `Terms::map()` and the count `_n()` phrasing; **zero AI Afrikaans, zero copy debt**.
- **Conflation rule** [project-context THE conflation rule; AD-6 §2 three-tier permission]: following is an *engagement* action — gated by `is_user_logged_in()` + nonce, NEVER `Entitlement::can_submit()` or a tier. A gratis lid may follow (afrikaans-terms.md line 47: "mag lees, reageer, volg"). Keep `Ink\Social` free of `Ink\Entitlement`/`Ink\Tiers`.
- **Verb-less counts** [Source: Story 7.8 reaction counts; architecture.md#AD-5 counts via object cache]: the volgeling count is a noun phrase ("12 volgelinge"), not "12 mense volg" — mirror the 7.8 `_n()` verb-less pattern. Direct `COUNT` is fine for v1 (counts are not measured hot; AD-5 says denormalize only if hot) — no counter column yet.
- **Toggle placement is 9.4** [Source: Story 9.4 profile templates; 8.5 precedent where surfaces were built then placed]: this story ships the `ink/volg-knoppie` block + its REST + counts; the Skrywerprofiel and skrywer-card placement is Story 9.4 (the block takes a `skrywerId` attribute so it can be embedded anywhere a writer context exists). Mirror the `ink/leeslys-knoppie` server-render-then-client-flip pattern (no flash).
- **Facade discipline** [AD-1]: `Social\Api` is the only surface other modules call — the 9.3 feed reads `followeeIdsFor()`, the 9.4 profile reads `followerCount()`/`isFollowing()`. Do not let other modules touch `FollowStore`.

### Project Structure Notes

- NEW ink-core: `src/Social/FollowStore.php`, `src/Social/FollowController.php`, `src/Social/FollowToggle.php`, `src/Social/FollowCounts.php`.
- MOD ink-core: `src/Social/Api.php` (expose the facade methods), `src/Social/Module.php` (wire controller + toggle), `src/I18n/Terms.php` (volg labels), `ink-core.php` (Schema::register the follows table).
- MOD theme: `wp-content/themes/ink-foundation/theme.json` (`.ink-volg-knoppie` token styles). A small enqueued client to flip the toggle (mirror the leeslys client) — reuse the existing engagement client if one is shared; otherwise add `assets/js/volg.js` enqueued behind the block.
- NEW tests: `FollowStoreTest`, `FollowControllerTest`, `FollowToggleTest`, `FollowCountsTest`.
- deptrac: Social→I18n (Terms) and Social→Content (writer existence check) may be new edges — declare them in `deptrac.yaml` if introduced (Social already allowed → Kernel).
- Note (don't build): the following-feed (9.3 — consumes `followeeIdsFor`), profile placement + volgeling-count display (9.4), follow notifications (9.9 — fires off a follow event), friendship→two-follows migration (Epic 16).

### References

- [Source: _bmad-output/planning-artifacts/epics.md#Story 9.2]
- [Source: _bmad-output/planning-artifacts/architecture.md#AD-5 (follow graph custom table, counts), #AD-6 (ink/v1 nouns, three-tier permission), #Naming Patterns (ink_follows, FKs user_id/followee_id)]
- [Source: wp-content/plugins/ink-core/src/Engagement/ReadingListStore.php, ReadingListController.php, ReadingListToggle.php (the mirrored leeslys pattern)]
- [Source: wp-content/plugins/ink-core/src/I18n/Terms.php (label registry); docs/afrikaans-terms.md lines 152–154, 187–188, 207, 243–244]
- [Source: _bmad-output/project-context.md#follow-custom-ink-core, #conflation-rule, #Afrikaans-first]

## Dev Agent Record

### Agent Model Used

claude-opus-4-8 (BMAD dev-story loop, Epic 9)

### Debug Log References

- `composer stan` / `composer deptrac` run outside the sandbox (phpstan parallel-worker TCP bind). stan OK; deptrac 3 PRE-EXISTING Kernel→Content violations only — **0 new edge** (Social→I18n for `Terms` is already permitted; no new deptrac rule needed).
- `FollowStore` carries the same documented `phpcs:ignore` block as `ReadingListStore` for the direct-query / interpolated-table-name class (bounded, bound values, constant table name).

### Completion Notes List

- **Asymmetric follow graph** (`ink_follows`, mirrors the leeslys table with `(user_id, followee_id)`): `FollowStore::follow()` writes exactly the one-way A→B edge via `INSERT ... ON DUPLICATE KEY UPDATE` (dedup); `unfollow` is an idempotent delete. `UNIQUE KEY user_followee` dedups, `KEY followee_id` powers the volgeling count, `KEY user_id` powers the following list (the 9.3 feed). Registered with the Kernel `Schema` registry at include time in `ink-core.php` (like the 7.3/7.7 tables) so activation creates it — NOT BuddyPress Friends.
- **Self-follow rejected at two layers**: `FollowStore::follow()` returns false WITHOUT writing when `$user === $followee` or either id ≤ 0 (the store guard, non-vacuously tested — a distinct edge DOES write); `FollowController::validate()` returns `ink_volg_self` ("Jy kan nie jouself volg nie.") at the REST boundary, plus `ink_volg_invalid_target` when the followee is not a real user.
- **REST `ink/v1/volg`** (Afrikaans noun, AD-6): POST follows / DELETE unfollows, gated by `is_user_logged_in()` + nonce ONLY — never `Entitlement`/`Tiers` (a gratis lid may follow; THE conflation rule holds). Validation is a pure `validate(bool,bool)`.
- **Volg / Volg tans toggle** (`ink/volg-knoppie`): server-rendered in its correct state (`isFollowing()`), shown only to a logged-in lid who is not the target (you cannot follow yourself). Takes a `skrywerId` attribute so 9.4 (Skrywerprofiel) + skrywer cards place it; mirrors the leeslys server-render-then-client-flip (no flash). Labels come from the glossary-backed `Terms` registry (`volg` → "Volg", `volg_tans` → "Volg tans", `volg_nie_meer` → "Volg nie meer nie" — human-authored, approved). Verb-less volgeling count via `FollowCounts::volgelingLabel()` (`_n` "volgeling"/"volgelinge", NEVER "volger").
- **Facade discipline** (AD-1): `Social\Api` exposes `isFollowing`/`followerCount`/`followingCount`/`followeeIdsFor`/`volgelingLabel`; the 9.3 feed + 9.4 profile read through it, never `FollowStore`.
- **Conflation-clean**, enforced by a `CodeScan` guardrail over all four Social follow files (no `Ink\Tiers`/`Ink\Entitlement`). No new deptrac edge.
- Tests 575→600 (+25); cs 0 errors; stan OK; copy:scan no new debt; deptrac 3 pre-existing (0 new).

### File List

- `wp-content/plugins/ink-core/src/Social/FollowStore.php` (NEW — ink_follows custom-table store + self-follow guard + counts)
- `wp-content/plugins/ink-core/src/Social/FollowCounts.php` (NEW — verb-less volgeling-count label)
- `wp-content/plugins/ink-core/src/Social/FollowController.php` (NEW — ink/v1/volg REST POST/DELETE + pure validate)
- `wp-content/plugins/ink-core/src/Social/FollowToggle.php` (NEW — ink/volg-knoppie server block)
- `wp-content/plugins/ink-core/src/Social/Api.php` (MOD — follow facade methods)
- `wp-content/plugins/ink-core/src/Social/Module.php` (MOD — wire FollowController + FollowToggle)
- `wp-content/plugins/ink-core/src/I18n/Terms.php` (MOD — volg / volg_tans / volg_nie_meer keys)
- `wp-content/plugins/ink-core/ink-core.php` (MOD — Schema::register the follows table)
- `wp-content/themes/ink-foundation/theme.json` (MOD — .ink-volg-knoppie / .ink-volgelinge token styles)
- `tests/Unit/Social/FollowStoreTest.php` (NEW)
- `tests/Unit/Social/FollowControllerTest.php` (NEW)
- `tests/Unit/Social/FollowToggleTest.php` (NEW)
- `tests/Unit/Social/FollowCountsTest.php` (NEW)
- `_bmad-output/implementation-artifacts/9-2-follow-graph-asymmetric.md` (NEW — this story)
- `_bmad-output/implementation-artifacts/sprint-status.yaml` (MOD — 9.2 status)

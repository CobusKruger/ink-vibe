---
baseline_commit: e1c69b7
---

# Story 7.7: Reading list (leeslys)

Status: review

## Story

As a lid,
I want a personal leeslys,
so that I can save works to read. (FR-29, UJ-3)

## Acceptance Criteria

**Given** a work
**When** I save/remove it
**Then** it is added/removed from my leeslys with confirmation toasts and surfaced on my profile.

1. A logged-in lid can **save** a work to, or **remove** it from, their personal leeslys from the reading surface (a save toggle reflecting the current saved state).
2. The leeslys is **deduped** — saving the same work twice is a no-op (a UNIQUE `(user_id, post_id)` key); removing is idempotent.
3. Save/remove confirmation **toasts** show the human-authored Afrikaans copy verbatim from `ui-copy-translations.md` (155/156: "Gestoor na jou leeslys" / "Verwyder van jou leeslys").
4. The saved works are **surfaced on the profile** (a leeslys list block).
5. The write path is the `ink/v1/leeslys` REST endpoint (AD-6): `is_user_logged_in()` + REST nonce, NOT entitlement-gated (AD-6 §2; conflation-clean). Validation → Afrikaans `WP_Error`.
6. The table is created/owned/migrated by the Engagement module via the Kernel `Schema` registry (AD-5): `ink_reading_list(user_id, post_id, created_at)`, indexed for the member's list AND the reverse "who saved this".
7. Three-layer & conflation-clean: store + REST in `ink-core` (Engagement); toggle/list are server blocks (AD-7) with the theme owning presentation; zero `Ink\Tiers`/`Ink\Entitlement`.

## Tasks / Subtasks

- [x] Task 1: Term + `ReadingListStore` custom table (AC: #2, #6)
  - [x] Add `leeslys` => 'Leeslys' to `Terms::map()` (glossary line 151).
  - [x] `Ink\Engagement\ReadingListStore`: `const TABLE='ink_reading_list'`, `tableName()`, `schemaSql()` (id PK, user_id, post_id, created_at; `UNIQUE KEY user_post (user_id,post_id)`; `KEY user_id`; `KEY post_id` for the reverse query).
  - [x] `add(user_id, post_id): bool` (prepared `INSERT … ON DUPLICATE KEY UPDATE` — dedup no-op), `remove(user_id, post_id): bool`, `has(user_id, post_id): bool`, `forUser(user_id): list<int>` (post ids, newest first). Mirror `ReactionStore` phpcs/format conventions + GMT timestamp.
- [x] Task 2: `ReadingListController` REST (AC: #1, #5)
  - [x] `register()` → `rest_api_init` → `POST /leeslys` (save) + `DELETE /leeslys` (remove) under `ink/v1`. `permission()` = `is_user_logged_in()`. `args`: `post_id` (int, req).
  - [x] Pure `validate(bool $postReadable): ?WP_Error` (`ink_leeslys_invalid_post`). Callbacks: `absint` post, readable check, `add`/`remove`, return `{ post_id, saved:bool }`.
- [x] Task 3: Toggle + list blocks (AC: #1, #3, #4)
  - [x] `Ink\Engagement\ReadingListToggle` server block `ink/leeslys-knoppie`: renders a save button reflecting `has(current_user, post)` (server-rendered initial state, `is-saved` class + `aria-pressed`). Pure `toHtml(int $post_id, bool $saved): string`.
  - [x] `Ink\Engagement\ReadingList` server block `ink/leeslys`: lists `forUser(current_user)` as cards (title link + type badge via Terms), heading "Leeslys"; empty state graceful. Pure `toHtml(array $cards): string`.
  - [x] Embed `ink/leeslys-knoppie` in the reading header of the three reading patterns; embed `ink/leeslys` in `profile-summary.php`.
- [x] Task 4: Client + wiring (AC: #1, #3)
  - [x] `assets/js/leeslys.js`: toggle button → POST/DELETE `ink/v1/leeslys` with `X-WP-Nonce`; flips `is-saved`/`aria-pressed`; shows the authored Afrikaans toast (localised). Enqueue on `is_singular(['gedig','storie','artikel'])` via `ink_foundation_enqueue_leeslys()`; localise REST root + nonce + the two toast strings (authored, `ink-foundation` domain).
  - [x] `Engagement\Module::register()` registers the controller + both blocks. `ink-core.php`: `Schema::register(ReadingListStore::TABLE, …)` + bump `VERSION` to 0.1.2.
  - [x] `.ink-leeslys*` CSS in `theme.json` `styles.css` (tokens).
- [x] Task 5: Tests + gates (AC: all)
  - [x] `ReadingListStoreTest` (Mockery `$wpdb`): prefixing; schema dbDelta-compatible + UNIQUE `user_post` + reverse `KEY post_id`; `add` upsert (dedup); `remove` delete; `has` get_var bool; `forUser` ids newest-first, empty→`array()`.
  - [x] `ReadingListControllerTest`: permission logged-in; `validate` rejects unreadable post, passes a readable one; conflation guard (no Tiers/Entitlement, CodeScan over controller + store + both blocks).
  - [x] `ReadingListBlocksTest`: `ReadingListToggle::toHtml` reflects saved/unsaved (the `is-saved` class present only when saved — non-vacuous both states); `ReadingList::toHtml` renders the heading + a card per work and escapes, empty → graceful (heading only / nothing).
  - [x] Extend `ReadingTemplatesTest`: each reading pattern embeds `wp:ink/leeslys-knoppie`; `profile-summary.php` embeds `wp:ink/leeslys`.
  - [x] `composer test:unit` green, `cs`/`stan` clean, `copy:scan` no new debt, `deptrac` clean (Engagement → [Kernel, Content]; toggle/list use Terms + PostTypes + own store).

## Dev Notes

- **Custom table** [Source: src/Engagement/ReactionStore.php (Story 7.3); architecture.md AD-5 "ink_reading_list(user_id, post_id, created_at) — indexed, supports reverse who-saved-this, dedup; avoids unbounded serialized user-meta"]: copy the ReactionStore shape; UNIQUE `(user_id,post_id)` for dedup, `KEY post_id` for the reverse "who saved this" query (a later discovery sort). Register the schema in `ink-core.php` next to the others + bump `INK_CORE_VERSION` so `maybeUpgrade()` installs it.
- **REST** [Source: src/Engagement/ReactionController.php]: same pattern — pure `validate`, thin callback, Afrikaans `WP_Error`, logged-in permission. POST save / DELETE remove; client decides which from the button state.
- **Toasts** [Source: docs/ui-copy-translations.md:155-156]: authored verbatim — localise both strings to the client; show on success. No invented copy (copy:scan clean).
- **Profile surfacing** [Source: patterns/profile-summary.php]: the dedicated My Profiel page is Epic 9; for 7.7, embed the `ink/leeslys` list block in the existing `profile-summary.php` pattern (renders the current user's saved works). Epic 9 wires profile-owner context; v1 lists the logged-in member's own leeslys.
- **leeslys label** [Source: docs/afrikaans-terms.md:151]: `leeslys` is a glossary term (code `reading_list`) — add the display label to Terms; the block heading uses it.
- **Not entitlement-gated / conflation-clean** [AD-6 §2; project-context]: logged-in + nonce only; zero Tiers/Entitlement (assert).
- **Testing** [Source: tests/Unit/Engagement/ReactionStoreTest.php, ReactionControllerTest.php]: Mockery `$wpdb`; pure `validate`/`toHtml` direct; CodeScan conflation guard.

### Project Structure Notes

- New ink-core: `src/Engagement/ReadingListStore.php`, `ReadingListController.php`, `ReadingListToggle.php`, `ReadingList.php`; MOD `src/I18n/Terms.php`, `src/Engagement/Module.php`, `ink-core.php` (Schema + VERSION 0.1.2).
- New theme: `assets/js/leeslys.js`; MOD `functions.php` (enqueue), `theme.json` (styles), `patterns/reading-{storie,artikel,gedig}.php` (toggle), `patterns/profile-summary.php` (list).
- New tests: `ReadingListStoreTest`, `ReadingListControllerTest`, `ReadingListBlocksTest`; MOD `ReadingTemplatesTest`.
- deptrac: Engagement → [Kernel, Content] (already); no Entitlement/Tiers edge.

### References

- [Source: _bmad-output/planning-artifacts/epics.md#Story 7.7]
- [Source: _bmad-output/planning-artifacts/architecture.md#AD-5, #AD-6]
- [Source: wp-content/plugins/ink-core/src/Engagement/ReactionStore.php, ReactionController.php]
- [Source: docs/ui-copy-translations.md:155-156; docs/afrikaans-terms.md:151]
- [Source: _bmad-output/project-context.md#conflation-rule, #afrikaans-first]

## Dev Agent Record

### Agent Model Used

claude-opus-4-8 (BMAD dev-story loop, Epic 7)

### Debug Log References

- stan run outside the sandbox (parallel-worker TCP bind). No code issues.

### Completion Notes List

- `ink_reading_list` custom table (Engagement-owned, Schema registry, ink-core → 0.1.2). UNIQUE `(user_id,post_id)` dedups saves; `KEY post_id` indexes the reverse "who saved this" (a later discovery sort). `add` upserts (repeat-save no-op), `remove` idempotent.
- `ink/v1/leeslys` REST (3rd ink/v1 endpoint): POST save / DELETE remove; logged-in + nonce only, not entitlement-gated; pure `validate`; Afrikaans `WP_Error`. Conflation-clean (asserted across controller + store + both blocks).
- `ink/leeslys-knoppie` server block renders the save toggle in its correct initial state (`has()`), so no client-side flash; embedded in all three reading headers. `ink/leeslys` block surfaces the member's saved works on the profile (embedded in `profile-summary.php`; the dedicated My Profiel page is Epic 9).
- Toasts use the human-authored Afrikaans verbatim (ui-copy 155/156), localised to the thin client — no invented copy. `leeslys` label added to the Terms registry (glossary line 151).
- JS reflects server state only; JS/E2E deferred to 18.8.
- Tests 467→483 (+16); cs/stan clean; copy:scan no new debt; deptrac 0 new violations (Engagement→[Kernel,Content]).

### File List

- `wp-content/plugins/ink-core/src/Engagement/ReadingListStore.php` (NEW — ink_reading_list table)
- `wp-content/plugins/ink-core/src/Engagement/ReadingListController.php` (NEW — ink/v1/leeslys REST)
- `wp-content/plugins/ink-core/src/Engagement/ReadingListToggle.php` (NEW — ink/leeslys-knoppie block)
- `wp-content/plugins/ink-core/src/Engagement/ReadingList.php` (NEW — ink/leeslys profile block)
- `wp-content/plugins/ink-core/src/Engagement/Module.php` (MOD — wire controller + blocks)
- `wp-content/plugins/ink-core/src/I18n/Terms.php` (MOD — leeslys label)
- `wp-content/plugins/ink-core/ink-core.php` (MOD — Schema::register + VERSION 0.1.2)
- `wp-content/themes/ink-foundation/patterns/reading-storie.php` (MOD — toggle)
- `wp-content/themes/ink-foundation/patterns/reading-artikel.php` (MOD — toggle)
- `wp-content/themes/ink-foundation/patterns/reading-gedig.php` (MOD — toggle)
- `wp-content/themes/ink-foundation/patterns/profile-summary.php` (MOD — leeslys list)
- `wp-content/themes/ink-foundation/assets/js/leeslys.js` (NEW)
- `wp-content/themes/ink-foundation/functions.php` (MOD — enqueue)
- `wp-content/themes/ink-foundation/theme.json` (MOD — `.ink-leeslys*` + `.ink-toast` styles)
- `tests/Unit/Engagement/ReadingListStoreTest.php` (NEW)
- `tests/Unit/Engagement/ReadingListControllerTest.php` (NEW)
- `tests/Unit/Engagement/ReadingListBlocksTest.php` (NEW)
- `tests/Unit/Engagement/ReadingTemplatesTest.php` (MOD — toggle/list embed guards)
- `_bmad-output/implementation-artifacts/7-7-reading-list-leeslys.md` (NEW — this story)

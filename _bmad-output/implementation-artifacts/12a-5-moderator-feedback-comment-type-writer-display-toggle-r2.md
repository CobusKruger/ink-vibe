---
baseline_commit: 18bca5a5503d223055e9c76957ac45d3f1ed24ae
---

# Story 12A.5: Moderator-feedback comment type + writer display toggle (R2)

Status: done

## Story

As a skrywer,
I want moderator feedback stored privately and shown only if I enable it,
so that I control whether critique appears on my work. (FR-50-R2, C5)

## Acceptance Criteria

**Given** ingestion (the 12A.3 commit)
**When** feedback is written
**Then** it is stored as a custom structured `comment_type = ink_moderator_terugvoer` ("Terugvoer van die moderator") via `wp_insert_comment` (NOT a re-enabled WP comment)
**And** it is visible on a work **only when the writer enables it on My Profiel** (a sanctioned exception to the Gemeenskapsreaksies-only rule).

Decomposed:

1. `Challenges\ModeratorFeedback` fills the 12A.3 `Ingestion::commitModeratorFeedback` reserved seam: writes one `ink_moderator_terugvoer` comment per entry via `wp_insert_comment` (idempotent — skips an entry that already carries feedback). Returns the count written.
2. The display gate: `feedbackFor(int $post_id)` returns the feedback **only when the work's author has enabled the display toggle** (`ink_wys_moderator_terugvoer` user meta, default OFF) — the sanctioned exception; otherwise it stays private.
3. A self-service toggle the writer controls (their own profile field; the 9.4 My Profiel surface reads the same meta). Nonce + self-or-moderate auth, `is_scalar`→`wp_unslash`→sanitise.

## Tasks / Subtasks

- [x] Task 1: `Challenges\ModeratorFeedback` (AC: 1,2,3)
  - [x] `COMMENT_TYPE = 'ink_moderator_terugvoer'`, `DISPLAY_META = 'ink_wys_moderator_terugvoer'`
  - [x] `recordForRound(int $uitdaging_id, list $commentary): int` — per `{post_id,title,text}`: `wp_insert_comment` of the custom type (skip empty text / existing feedback); count. Protected `insertComment`/`hasFeedback` seams
  - [x] `isDisplayEnabled(int $user_id): bool` (meta, default OFF); `feedbackFor(int $post_id): list<string>` — gated by the POST AUTHOR's toggle (protected `authorOf`/`commentsFor`/`isDisplayEnabled` seams)
  - [x] `register()`: register the user meta + the self-profile toggle field (`show_user_profile`/`edit_user_profile` + `*_update` save) + (no comments_open change — `wp_insert_comment` bypasses it)
  - [x] profile save: nonce + (self OR `MODERATE`) + `is_scalar`→`wp_unslash`→rest_sanitize_boolean
- [x] Task 2: wire `Ingestion::commitModeratorFeedback()` → `ModeratorFeedback::recordForRound()`; register `ModeratorFeedback` in `Module::register()`
- [x] Task 3: Tests — `ModeratorFeedbackTest`: recordForRound writes one comment per entry, skips empty + already-fed (idempotent); feedbackFor returns texts when the author's toggle is ON and NOTHING when OFF (non-vacuous: same feedback present both ways); isDisplayEnabled reads meta
- [x] Task 4: Gates — test:unit / cs / stan / deptrac / copy:scan green. **No new deptrac edge** (self-contained in Challenges; `wp_insert_comment`/user-meta are core; Terms is uncovered; the comment type is NOT routed through Engagement). New Afrikaans copy ("Terugvoer van die moderator", toggle label) as `__()` literals.

## Dev Notes

- **Custom comment type, not a re-enabled WP comment.** `wp_insert_comment(['comment_type' => 'ink_moderator_terugvoer', …])` is programmatic and bypasses the site-wide `comments_open` disable (Engagement Comments, Story 1.8) — so Challenges needs NO Engagement edge and never re-opens comments. The type is distinct from the `ink_reaksie` Gemeenskapsreaksies type. [Source: src/Engagement/Comments.php; project-context.md]
- **The display gate IS the privacy control (C5):** feedback is stored on commit but `feedbackFor` returns it only when the WORK'S AUTHOR has `ink_wys_moderator_terugvoer` ON (default OFF) — the writer owns whether critique shows. The 9.4 My Profiel template reads the same meta to render the self-service toggle on the front end; this story registers the meta + a wp-admin self-profile control as the concrete editable surface. [Source: epics.md#Story 12A.5]
- **Idempotency:** `Ingestion`'s per-round commit-done marker guards re-runs; `recordForRound` adds a defensive per-post `hasFeedback` skip so a stray re-entry never double-comments. [Source: src/Challenges/Ingestion.php]
- **Sanctioned exception** to the "Gemeenskapsreaksies are the only feedback path" rule — documented at the type. Conflation-clean (zero Tiers/Entitlement). House style + testing rules as prior stories (pure gate logic behind seams; non-vacuous toggle test). [Source: project-context.md]

### Project Structure Notes

- New: `src/Challenges/ModeratorFeedback.php`, `tests/Unit/Challenges/ModeratorFeedbackTest.php`.
- Modified: `src/Challenges/Ingestion.php` (commitModeratorFeedback delegates), `src/Challenges/Module.php` (register).
- No new deptrac edge.

### References

- [Source: _bmad-output/planning-artifacts/epics.md#Story 12A.5]
- [Source: src/Challenges/Ingestion.php (12A.3), src/Engagement/Comments.php (1.8), src/Tiers/AdminProfile.php (profile $_POST pattern)]

## Dev Agent Record

### Agent Model Used

claude-opus-4-8

### Debug Log References

- None of note. (Removed a leftover garbled heading expression + unused `Terms` import before gates.)

### Completion Notes List

- `Challenges\ModeratorFeedback` fills the `Ingestion::commitModeratorFeedback` seam: `recordForRound` writes one `ink_moderator_terugvoer` comment per entry via `wp_insert_comment` (a programmatic custom type that bypasses the site-wide `comments_open` disable — NOT a re-enabled WP comment, no Engagement edge), skipping empty text + already-fed entries (idempotent).
- The privacy control (C5): `feedbackFor(post_id)` returns the stored feedback ONLY when the work's author has `ink_wys_moderator_terugvoer` enabled (default OFF). Non-vacuous test: same feedback present, shown when ON, hidden when OFF.
- Self-service toggle registered as boolean user meta + a profile field (`show_user_profile`/`edit_user_profile` + `*_update` save) with nonce + self-or-MODERATE auth + `is_scalar`→`wp_unslash`→`rest_sanitize_boolean`. The 9.4 My Profiel surface reads the same meta.
- Sanctioned exception to the Gemeenskapsreaksies-only rule, documented at the type; distinct from `ink_reaksie`. Conflation-clean.
- New Afrikaans copy as `__()` literals ("Moderator-terugvoer", toggle label + helper).
- Gates: `composer test:unit` 1165→1171 (+6), 1 skipped; `cs` 0 errors; `stan` OK; `deptrac` 3 pre-existing only (no new edge — self-contained in Challenges); `copy:scan` clean.

### File List

- `wp-content/plugins/ink-core/src/Challenges/ModeratorFeedback.php` (new)
- `wp-content/plugins/ink-core/src/Challenges/Ingestion.php` (modified — commitModeratorFeedback delegates)
- `wp-content/plugins/ink-core/src/Challenges/Module.php` (modified — register ModeratorFeedback)
- `tests/Unit/Challenges/ModeratorFeedbackTest.php` (new)

### Change Log

- 2026-06-29 — Story 12A.5 implemented: ink_moderator_terugvoer custom comment type + author-controlled display toggle; fills the 12A.3 commitModeratorFeedback seam. 6 unit tests. Suite 1165→1171.

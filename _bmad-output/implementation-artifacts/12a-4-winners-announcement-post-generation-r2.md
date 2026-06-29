---
baseline_commit: 03f41d0a875f1005198f6220e1dc89d3d5a7f93b
---

# Story 12A.4: Winners-announcement post generation (R2)

Status: review

## Story

As a redakteur,
I want the wenneraankondiging post generated automatically on commit,
so that I don't hand-write it. (FR-50-R2, R2)

## Acceptance Criteria

**Given** confirmed results (the 12A.3 commit)
**When** the post generates
**Then** a wenneraankondiging is created from a **simple form-letter template** (Story 1.12), **takes the featured home slot** (15.6 `FeaturedWinners` `FEATURED_FILTER`), and **lists the winning entries with links**.

Decomposed:

1. `Challenges\WinnersPost` fills the 12A.3 `Ingestion::commitWinnersPost` reserved seam: composes + publishes the announcement and returns its post id (idempotent — never double-posts for a round).
2. The body frame comes from a Story-1.12 Notifications `Template` (`ink_wenneraankondiging`, Afrikaans source); `WinnersPost` appends the ordered winning-entry links. The announcement is a standard `post` (editorial chrome, not member content), linked to its round via `ink_wenneraankondiging_uitdaging` meta.
3. `WinnersPost` hooks `FeaturedWinners::FEATURED_FILTER` to supply `{title, url, winners:[{id,rank,title,url}]}` from the latest announcement + the round's placements — so the home slot surfaces it (15.6 collapses when the filter yields nothing; now it's filled). Ordering is `FeaturedWinners::order` (algehele wenner first) — 12A.7 owns the rule.

## Tasks / Subtasks

- [x] Task 1: `Notifications\Api::templateBody(string $key, array $context = []): string` — read a registered form-letter body (store + MergeResolver) WITHOUT sending (the facade read 12A.4 needs).
- [x] Task 2: `Challenges\WinnersPost` (AC: 1,2,3)
  - [x] `TEMPLATE_KEY = 'ink_wenneraankondiging'`, `UITDAGING_META = 'ink_wenneraankondiging_uitdaging'`
  - [x] `register()`: register the Afrikaans `Template` + add the `FEATURED_FILTER` callback
  - [x] pure `composeBody(string $frame, list $entries): string` (frame + per-entry "{label}: {title}" link list), `composeTitle(string $uitdaging_title): string`
  - [x] `generate(int $uitdaging_id, list $winners): int` — idempotent (existing announcement → return it); resolve winner titles/links; `wp_insert_post` (post, publish); stamp `UITDAGING_META`; protected `insertPost`/`existingPostFor`/`entryView` seams
  - [x] `featured(mixed $value): mixed` — FEATURED_FILTER callback: latest announcement → `{title,url,winners}` (winners = round entries with placement rank); pure `featuredPayload()`
- [x] Task 3: wire `Ingestion::commitWinnersPost()` → `WinnersPost::generate()` (same module); register `WinnersPost` in `Module::register()`
- [x] Task 4: Tests — `WinnersPostTest` (composeBody/Title pure; generate composes + inserts + is idempotent via seams — non-vacuous; featuredPayload builds the slot payload; templateBody facade read)
- [x] Task 5: Gates — test:unit / cs / stan / deptrac / copy:scan. deptrac: **NEW Challenges → Notifications edge** (the form-letter template read) — pre-flagged; mirrors Tiers→Notifications (5.10) / Entitlement→Notifications (4.8). New Afrikaans `Template` copy + title as `__()` literals.

## Dev Notes

- **The wenneraankondiging is a standard `post`** (editorial announcement, not a bydrae CPT), linked to its round by `ink_wenneraankondiging_uitdaging` meta so the FEATURED_FILTER can find the latest one. [decision — documented]
- **Form-letter template (Story 1.12):** `WinnersPost` registers a Notifications `Template` and reads its body via the new `Api::templateBody()` (no send — the post body, not an email). The template is the congratulatory FRAME; `WinnersPost` appends the ordered winner links. The Challenges→Notifications edge is the one pre-flagged new edge for Epic 12A. [Source: src/Notifications/Template.php (lists 12A.4 as a consumer); src/Notifications/Api.php]
- **Featured slot (15.6):** `FeaturedWinners` already owns the `ink/wenner-kollig` block + `FEATURED_FILTER` + `order()` (algehele wenner first). 12A.4 supplies the payload; 12A.7 owns the ordering rule. The slot collapsed to empty until now (forward seam). [Source: src/Challenges/FeaturedWinners.php]
- **Idempotency:** `Ingestion` already guards the per-round commit; `WinnersPost::generate` adds a defence-in-depth check (an existing announcement for the round → return it, no double-post). [Source: src/Challenges/Ingestion.php]
- House style + admin/testing rules as prior stories (pure composers + impure seams; test the OUTCOME; non-vacuous idempotency). [Source: project-context.md]

### Project Structure Notes

- New: `src/Challenges/WinnersPost.php`, `tests/Unit/Challenges/WinnersPostTest.php`.
- Modified: `src/Notifications/Api.php` (templateBody), `src/Challenges/Ingestion.php` (commitWinnersPost delegates), `src/Challenges/Module.php` (register WinnersPost), `deptrac.yaml` (Challenges → Notifications edge).

### References

- [Source: _bmad-output/planning-artifacts/epics.md#Story 12A.4]
- [Source: src/Challenges/FeaturedWinners.php (15.6), Ingestion.php (12A.3), Cadence.php (12.3)]
- [Source: src/Notifications/Template.php, Api.php (Story 1.12)]

## Dev Agent Record

### Agent Model Used

claude-opus-4-8

### Debug Log References

- Test mocked `sprintf` (internal — Patchwork `NotUserDefined`); removed (the real `sprintf` works with `__` returnArg). Frame read isolated behind a `bodyFrame()` seam so unit tests don't touch the Notifications static facade.

### Completion Notes List

- `Challenges\WinnersPost` fills the `Ingestion::commitWinnersPost` seam: composes + publishes the wenneraankondiging (a standard `post`, idempotent — existing announcement returned not duplicated), body framed by the Story-1.12 `ink_wenneraankondiging` form-letter template (read via the new `Notifications\Api::templateBody()`) + the ordered winner links, linked to its round via `ink_wenneraankondiging_uitdaging` meta.
- Hooks `FeaturedWinners::FEATURED_FILTER` (15.6) to supply `{title,url,winners}` from the latest announcement + the round's placed entries — the home slot was collapsed-empty until now. Ordering (algehele wenner first) is `FeaturedWinners::order` (12A.7's rule).
- `Notifications\Api::templateBody()` added — reads a registered form-letter body (store + MergeResolver) without sending (the AD-9 form-letter read path; the `Template` class already names 12A.4 a consumer).
- New deptrac edge **Challenges → Notifications** added + documented (mirrors Tiers→Notifications 5.10 / Entitlement→Notifications 4.8). Conflation-clean.
- New Afrikaans copy as `__()` literals (template subject/body, "Wenners: %s", "Wenneraankondiging").
- Gates: `composer test:unit` 1156→1165 (+9), 1 skipped; `cs` 0 errors; `stan` OK; `deptrac` 3 pre-existing only (the new Challenges→Notifications edge is allowed); `copy:scan` clean.

### File List

- `wp-content/plugins/ink-core/src/Challenges/WinnersPost.php` (new)
- `wp-content/plugins/ink-core/src/Notifications/Api.php` (modified — templateBody)
- `wp-content/plugins/ink-core/src/Challenges/Ingestion.php` (modified — commitWinnersPost delegates)
- `wp-content/plugins/ink-core/src/Challenges/Module.php` (modified — register WinnersPost)
- `deptrac.yaml` (modified — Challenges → Notifications edge)
- `tests/Unit/Challenges/WinnersPostTest.php` (new)

### Change Log

- 2026-06-29 — Story 12A.4 implemented: winners-announcement post generation from the Story-1.12 form-letter template + home featured-slot payload; fills the 12A.3 commitWinnersPost seam. 9 unit tests. Suite 1156→1165.

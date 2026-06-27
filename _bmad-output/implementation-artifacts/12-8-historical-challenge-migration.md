# Story 12.8: Historical challenge migration

Status: review

## Story

As an ink-core developer,
I want historical challenges migrated,
so that past rounds and their linkages survive. (FL 12.8, §14.6)

## Acceptance Criteria

**Given** old challenge categories
**When** the once-off DB update runs
**Then** categories → `uitdagingsrondte` terms + an `uitdaging` record per round, preserving each piece's challenge linkage (full brief/deadline only where old data exists).

Decomposed:

1. A once-off, **idempotent** `Challenges\Migration` converts each legacy challenge category into: (a) an `uitdaging` post (round record) and (b) an `uitdagingsrondte` round term keyed to it via `ChallengeRound::slugFor()`.
2. Every piece (post) filed under the legacy category is re-linked to the new round term (append, never clobbering existing terms) — preserving each piece's challenge linkage.
3. The uitdaging's **brief** is populated from the legacy category description only where it exists; the deadline meta is left empty when the legacy data has none ("full brief/deadline only where old data exists").
4. Idempotent + guarded: a completion option flag means a re-run is a no-op (a `--force` re-run is opt-in); triggered as a once-off via WP-CLI (`wp ink migrate-challenges`), never auto-run on a normal request.
5. Conflation-clean: reads `Ink\Content` (CPT/taxonomy/round-slug single sources) + WP core — zero Tiers/Entitlement.

## Tasks / Subtasks

- [x] Task 1: `Challenges\Migration` — `OPTION_DONE` flag + `hasRun()`/`markDone()`; pure `uitdagingPostArr(object $category)` (title=name, content=brief only where description exists, type=uitdaging, status=publish) + `briefFrom()`; orchestrating `run(bool $force=false)` over overridable seams (`legacyCategories()`, `createUitdaging()`, `ensureRoundTerm()`, `postsInCategory()`, `linkPostsToRound()`); returns a summary.
- [x] Task 2: WP-CLI trigger — `register()` registers `wp ink migrate-challenges` only when `WP_CLI` is defined (no auto-run on a web request).
- [x] Task 3: Module wiring — `Challenges\Module::register()` registers `Migration` (the WP-CLI guard means it is inert on normal requests).
- [x] Task 4: Tests — `tests/Unit/Challenges/MigrationTest.php` (uitdagingPostArr maps name→title + description→content, empty description → empty content; run() is a no-op when hasRun; run() with seams creates a round per category, links the pieces, marks done, returns the summary; --force overrides the guard).
- [x] Task 5: Gates — test/cs/stan/deptrac green; no new deptrac edge (Migration reads Content + Kernel only).

## Dev Notes

- Round term keyed via `ChallengeRound::slugFor($uitdaging_id)` (the single-source join key) so migrated rounds use the SAME convention as live submissions (6.6) and the Library linkage (10.5). [Source: src/Content/ChallengeRound.php:46]
- Reassign with `wp_set_object_terms( $post_id, [term_id], UITDAGINGSRONDTE, true )` (append) so a piece's existing terms are preserved. [Source: src/Content/Taxonomies.php:46]
- Idempotency mirrors the schema-upgrade option pattern (`ink_core_db_version`); a completion option flag + a `--force` escape hatch. [Source: ink-core.php:30; src/Kernel/Activation.php]
- Once-off only: no `init` auto-run (destructive); WP-CLI is the trigger. The migration is inert on every normal request. Epic 16 owns the broader brownfield migration; 12.8 is the challenge slice.
- "full brief/deadline only where old data exists": brief = legacy category description when non-empty; deadline meta is NOT fabricated (legacy categories carry no deadline) — left empty, so the single page omits the deadline line (12.1) gracefully.

### Project Structure Notes

- New: `src/Challenges/Migration.php`, `tests/Unit/Challenges/MigrationTest.php`.
- Modified: `src/Challenges/Module.php`.

### References

- [Source: _bmad-output/planning-artifacts/epics.md#Story 12.8]
- [Source: docs/afrikaans-terms.md] lines 122, 295

## Dev Agent Record

### Agent Model Used

claude-opus-4-8

### Debug Log References

### Completion Notes List

- `Challenges\Migration` is a once-off, idempotent DB update: each legacy `category` → an `uitdaging` post + an `uitdagingsrondte` round term (slug via `ChallengeRound::slugFor`, the live join key) + every piece in the category re-linked (append, preserving existing terms). Brief = legacy description where present; no deadline fabricated.
- Guarded: completion option `ink_challenge_migration_done` makes a re-run a no-op; `--force` opt-in. Trigger is WP-CLI only (`wp ink migrate-challenges`) — inert on every web request (no `init` auto-run).
- Pure builders (`uitdagingPostArr`/`briefFrom`) + overridable I/O seams make the orchestration unit-testable without the WP term/post API.
- Conflation-clean: reads `Ink\Content` + Kernel + WP core, zero Tiers/Entitlement. No new deptrac edge.
- **Gates:** `composer test` → 810 passed / 2 skipped (+5); `composer cs` → 0 errors; `composer stan` → No errors; `composer deptrac` → 3 pre-existing violations only.

### File List

- `wp-content/plugins/ink-core/src/Challenges/Migration.php` (new)
- `wp-content/plugins/ink-core/src/Challenges/Module.php` (modified — register Migration)
- `tests/Unit/Challenges/MigrationTest.php` (new)

### Change Log

- 2026-06-27: Story 12.8 implemented — once-off idempotent historical challenge migration (categories → uitdaging + round terms, pieces re-linked), WP-CLI triggered. Status → review.

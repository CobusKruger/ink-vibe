---
baseline_commit: c7e6a40
---

# Story 16.9: BuddyPress data + friendship→follow

Status: review

<!-- Note: Validation is optional. Run validate-create-story for quality check before dev-story. -->

## Story

As an ink-core developer,
I want friendships converted to follows,
so that the social graph survives the model change. (FL 16.9, MR-8)

## Acceptance Criteria

1. **Given** legacy BuddyPress data **When** migrated **Then** each **confirmed** friendship → **two** mutual follow records (dedup; skip orphaned; pending not converted); old activity trimmed; messaging deferred.
2. A **confirmed** friendship (A,B) produces **two directed** follow records — A→B and B→A — written through the canonical `Ink\Social\FollowStore::follow()` (so the unique-edge constraint dedups naturally). **Pending** friend requests are NOT converted.
3. **Orphaned edges skipped**: a follow whose follower or followee is not a valid imported account is skipped (counted), never written. Self-edges (A==B) and non-positive ids are skipped.
4. **Dedup**: duplicate friendship rows / reciprocal duplicates collapse to a single directed pair each (the pure pairing dedups; `FollowStore` is also idempotent).
5. **Old activity trimmed**: BuddyPress activity older than the retention threshold (2 years) is discarded (a seam over the activity table, guarded by existence). **Messaging is deferred** (rides the DB clone; not touched here).
6. Once-off + idempotent (`ink_migration_follows_done`; `--force`) and **WP-CLI only** (`wp ink migrate-follows`). Afrikaans `\WP_CLI` summary.
7. Conflation-clean: writes the follow graph via `Social\FollowStore` only; no `Tiers`/`Entitlement`. `composer test:unit` green (new `FollowGraphMigrationTest`, non-vacuous "confirmed→2, pending→0, orphaned skipped" guard); `composer cs` = 0 errors; `php -l`, `composer stan`, `composer deptrac` (declares `Migration → Social`), `composer copy:scan` clean.

## Tasks / Subtasks

- [x] Task 1: Implement `FollowGraphMigration` (AC: #1–#6)
  - [x] Added `wp-content/plugins/ink-core/src/Migration/FollowGraphMigration.php`: `OPTION_DONE`, `CLI_COMMAND = 'ink migrate-follows'`, `ACTIVITY_RETENTION_YEARS = 2`, `register()` (WP-CLI-only), `run(bool $force): array`, `hasRun()`, `markDone()`.
  - [x] Pure helpers: `followPairsFromFriendships(array $friendships): array` (confirmed only → deduped directed `[a,b]` pairs, skipping self/invalid/pending) and `cutoffDate(string $now): string` (now − 2 years).
  - [x] Overridable I/O seams: `friendships(): array` (`{prefix}bp_friends` table, existence-guarded), `validUser(int $id): bool`, `recordFollow(int $a, int $b): bool` (`FollowStore::follow`), `trimOldActivity(string $cutoff): int` (`{prefix}bp_activity` < cutoff, guarded), `now(): string`.
  - [x] Orchestration: count pending (not converted); build deduped pairs; per pair skip orphaned (count) else record; trim old activity. Afrikaans CLI summary.
- [x] Task 2: Register + deptrac (AC: #2, #7)
  - [x] Added `( new FollowGraphMigration() )->register();` to `Migration\Module::register()` + docblock; declared the `Migration → Social` deptrac edge.
- [x] Task 3: Tests (AC: #2, #3, #4, #7)
  - [x] Added `tests/Unit/Migration/FollowGraphMigrationTest.php` (6 tests): `followPairsFromFriendships` — **non-vacuous**: a confirmed friendship yields BOTH directed pairs, a pending one yields NONE, self/zero-id skipped, reciprocal duplicates deduped; `cutoffDate` subtracts 2 years; `run()` records 2 follows for the valid friendship, skips both orphaned directions (counts 2), counts pending, trims activity, idempotent skip.
  - [x] All gates green.

## Dev Notes

### What already exists (read before editing)
- `wp-content/plugins/ink-core/src/Social/FollowStore.php` — `follow(int $user_id, int $followee_id): bool` (user_id follows followee_id), backed by the unique-edge `ink_follows` table (so duplicate writes are idempotent). The canonical follow write path — route ALL follow writes through it.
- `wp-content/plugins/ink-core/src/Migration/*` + `Module.php` — the once-off-CLI + seam pattern.
- `tests/Unit/Social/FollowStoreTest.php` — the Mockery `$wpdb` idiom (only needed if a seam is exercised against the real store; the migration tests override `recordFollow`).

### Architecture compliance (project-context.md)
- **Friendships → follow: each confirmed friendship → TWO mutual one-way `volg` records** (PRD MR-8); dedup; skip orphaned/flagged; pending NOT converted. BuddyPress Friend Connections are OFF — the cloned friend tables are READ, transformed, never the live store.
- **Follow is custom in `ink-core`** (`Social\FollowStore`) — never a BuddyPress follow add-on.
- **Activity trimmed** (discard > 2 years); **messaging deferred** (rides the DB clone).
- **Conflation-clean**, WP-CLI-only, idempotent + `--force`, Afrikaans CLI. Deptrac: declare `Migration → Social`.

### Project Structure Notes
- NEW: `src/Migration/FollowGraphMigration.php`, `tests/Unit/Migration/FollowGraphMigrationTest.php`.
- MODIFIED: `src/Migration/Module.php`, `deptrac.yaml` (`Migration → Social`).

### Testing standards
- Override the friends/user/follow/activity seams; test the pairing + cutoff helpers as pure functions.
- **Non-vacuous guard:** confirmed → 2 records, pending → 0, orphaned skipped — so a regression that converts pending or drops one direction fails.
- Run `composer test:unit`, `composer cs`, `php -l`, `composer stan`, `composer deptrac`, `composer copy:scan`.

### References
- [Source: _bmad-output/planning-artifacts/epics.md#Story 16.9: BuddyPress data + friendship→follow] (FL 16.9, MR-8)
- [Source: docs/migration-plan.md — BuddyPress community data ("Friendships → follow … convert each confirmed friendship into two one-way volg records, dedup … skip edges to non-imported/flagged accounts; pending … not converted"; activity trimmed; messages ride the clone)]
- [Source: _bmad-output/project-context.md — friendships→follow (two mutual records); follow is custom in ink-core]
- [Source: wp-content/plugins/ink-core/src/Social/FollowStore.php — follow() canonical write]

## Dev Agent Record

### Agent Model Used

claude-opus-4-8 (BMAD dev-story workflow)

### Debug Log References

- `composer test:unit` → 998 passed, 1 skipped (3792 assertions). New `FollowGraphMigrationTest`: 6 passed.
- `composer cs` → 0 errors (fixed two interpolated-SQL errors caused by a mid-method `phpcs:enable` in an early-return block — the disable now spans lexically through the last `$wpdb` call).
- `composer stan` → No errors.
- `composer deptrac` → 3 pre-existing only; the new `Migration → Social` edge is declared + allowed.
- `composer copy:scan` → no new debt (baseline 8).
- `php -l` clean on `FollowGraphMigration.php`.

### Completion Notes List

- `FollowGraphMigration` transforms confirmed BuddyPress friendships into INK's asymmetric follow graph: each confirmed (A,B) → **two** directed records A→B + B→A via the canonical `Social\FollowStore::follow()` (unique-edge table dedups). **Pending friendships are never converted**; self-edges/non-positive ids skipped; **orphaned edges** (follower/followee not a valid imported account) skipped + counted; reciprocal/duplicate rows dedup to one directed pair each. Non-vacuous test proves confirmed→2, pending→0, orphaned-skipped.
- BuddyPress activity older than 2 years is trimmed (a guarded `bp_activity` delete); **messaging is deferred** (rides the DB clone, untouched).
- Once-off + idempotent (`ink_migration_follows_done`; `--force`); WP-CLI-only (`wp ink migrate-follows`); Afrikaans summary. Conflation-clean: writes via `Social\FollowStore` only (the declared `Migration → Social` edge).

### File List

- `wp-content/plugins/ink-core/src/Migration/FollowGraphMigration.php` (NEW)
- `wp-content/plugins/ink-core/src/Migration/Module.php` (MODIFIED — registered `FollowGraphMigration` + docblock)
- `deptrac.yaml` (MODIFIED — `Migration → Social` edge)
- `tests/Unit/Migration/FollowGraphMigrationTest.php` (NEW)
- `_bmad-output/implementation-artifacts/16-9-buddypress-data-friendship-follow.md` (story record)
- `_bmad-output/implementation-artifacts/sprint-status.yaml` (status tracking)

## Change Log

- 2026-06-28 — Story 16.9 implemented: `FollowGraphMigration` (confirmed BP friendship → two mutual `FollowStore` records; pending/orphaned skipped; deduped; old activity trimmed; messaging deferred). Status → review.

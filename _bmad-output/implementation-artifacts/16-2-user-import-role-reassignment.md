---
baseline_commit: c7e6a40
---

# Story 16.2: User import + role reassignment

Status: done

<!-- Note: Validation is optional. Run validate-create-story for quality check before dev-story. -->

## Story

As an ink-core developer,
I want a single member base role,
so that legacy reader/writer roles are dropped. (FL 16.2, FR-2)

## Acceptance Criteria

1. **Given** legacy users **When** imported **Then** a single member base role applies (no reader/writer distinction), profile fields cleaned, legacy Youzify/BP roles dropped.
2. The single member base role is the WordPress **`subscriber`** role (the default self-registration role — `Kernel\Capabilities` docblock; the gratis lid maps to `subscriber`). `WP_User::set_role()` REPLACES all of a user's roles with the one base role, so any legacy reader/writer/Youzify/BuddyPress custom role is dropped in the same call (FR-2: no reader/writer distinction — any member may publish once subscribed).
3. **Staff are preserved, never demoted.** A user holding `administrator` or `editor` (redakteur) keeps their role — the reassignment skips them. (Demoting an editor to subscriber would lock staff out of the admin.)
4. **Profile-field cleanup** removes legacy Youzify/BuddyPress noise meta keys. Because deleting the wrong meta is destructive and the exact legacy field set is site-specific (migration-plan: "scriptable once the field mapping is confirmed"), the key list is an **overridable seam with a safe-empty default** — an un-configured run drops legacy roles but cleans no meta (deliberate, like the `Challenges\Migration::legacyCategories()` safe default).
5. Once-off + idempotent (`ink_migration_users_done`; `--force` re-runs) and **WP-CLI only** (`wp ink migrate-users`) — never on a web request.
6. Conflation-clean: the command reads `Kernel\Capabilities` (role-name constants) + WP user API only; it never touches `ink_writer_tier` (that is Story 16.3) and introduces no `Entitlement`/`Tiers` coupling.
7. Afrikaans `\WP_CLI::success()` summary (reassigned / staff preserved / meta cleaned; "oorgeslaan — reeds gedoen" when skipped).
8. `composer test:unit` green (new `UserReclassifierTest`, non-vacuous staff-preservation guard); repo-wide `composer cs` = 0 errors; `php -l`, `composer stan`, `composer deptrac` (no new edge), `composer copy:scan` clean.

## Tasks / Subtasks

- [x] Task 1: Implement `UserReclassifier` (AC: #1–#7)
  - [x] Added `wp-content/plugins/ink-core/src/Migration/UserReclassifier.php` following the `DbSanitiser`/`Challenges\Migration` shape: `OPTION_DONE`, `CLI_COMMAND`, `BASE_ROLE = 'subscriber'`, `register()` (WP-CLI-only), `run(bool $force): array`, `hasRun()`, `markDone()`.
  - [x] Pure helpers: `preservedRoles(): array` (`administrator`, `editor` from `Kernel\Capabilities`) and `isStaff(array $roles): bool` (intersection with `preservedRoles()`).
  - [x] Overridable I/O seams: `legacyUserIds(): array` (default `get_users(['fields'=>'ID'])`), `userRoles(int $id): array` (default from `get_userdata()`, false-guarded), `reassignToBaseRole(int $id): void` (default `( new WP_User($id) )->set_role(self::BASE_ROLE)`), `legacyMetaKeys(): array` (default **empty**), `cleanLegacyMeta(int $id): int` (default `delete_user_meta` over `legacyMetaKeys()`, empty keys skipped).
  - [x] Afrikaans `\WP_CLI::success()` summary.
- [x] Task 2: Register in the module (AC: #5)
  - [x] Added `( new UserReclassifier() )->register();` to `Migration\Module::register()`.
- [x] Task 3: Tests (AC: #3, #8)
  - [x] Added `tests/Unit/Migration/UserReclassifierTest.php` (7 tests, 21 assertions): idempotency (skip/`--force`); pure-helper contents; **non-vacuous staff-preservation guard** — `isStaff()` true for `administrator` and for `editor`, false for `subscriber`/`reader`/`writer`/a Youzify role/empty; `run()` reassigns non-staff (10,12), skips staff (11 editor, 13 admin), counts correctly; meta cleaned only for configured keys (empty default verified).
  - [x] All gates green.

## Dev Notes

### What already exists (read before editing)
- `wp-content/plugins/ink-core/src/Migration/DbSanitiser.php` + `Module.php` — the 16.1 module + once-off CLI command pattern to mirror. `Module::register()` is where 16.2 adds its collaborator.
- `wp-content/plugins/ink-core/src/Kernel/Capabilities.php` — `ADMIN_ROLE = 'administrator'`, `EDITOR_ROLE = 'editor'`; its docblock states the gratis lid maps to the WP `subscriber` role. Source the role-name constants from here (single source).
- `wp-content/plugins/ink-core/src/Challenges/Migration.php` — the safe-empty overridable-seam convention (`legacyCategories()`), mirrored for `legacyMetaKeys()` (destructive op → opt-in).
- `tests/Unit/InkPols/MigrationTest.php` — the anonymous-subclass-over-seams + idempotency test idiom.
- `tests/stubs/class-wp-user.php` — the unit-suite `WP_User` double (read before relying on `set_role`/`roles` in a seam; the tests override the seams, so the real `WP_User` is not needed in-test).

### Architecture compliance (project-context.md)
- **FR-2:** one member base role, no reader/writer distinction; any member may publish once subscribed (entitlement is subscription, never role). The reassignment encodes exactly this.
- **Migration is scripted, ordered, WP-CLI-triggered**; users ride the DB clone (no account import) — this story does the *role reassignment + profile cleanup* the plan calls scriptable, AFTER 16.1 sanitise and before 16.3 tiers (migration order step 3).
- **Don't demote staff** — the editorial-role policy (AD-6) keeps `editor`/`administrator`; reassignment is for member accounts only.
- **i18n:** Afrikaans CLI summary (admin-language split).
- **Conflation-clean:** role reassignment ≠ tier ≠ entitlement. No `ink_writer_tier` write (16.3), no membership read. Deptrac edge: `Migration → Kernel` (already allowed; `Capabilities` is Kernel).

### Project Structure Notes
- NEW: `src/Migration/UserReclassifier.php`, `tests/Unit/Migration/UserReclassifierTest.php`.
- MODIFIED: `src/Migration/Module.php` (one `register()` line).
- No new deptrac layer/edge (uses Kernel only).

### Testing standards
- Unit tests mock WP (Brain Monkey); override the I/O seams so no real users/DB are needed.
- **Non-vacuous guard:** the staff-preservation test must assert `isStaff()` returns true for BOTH `editor` and `administrator` (the protected set) AND false for member roles — so it fails if a future edit drops a protected role or mis-classifies a member role.
- Run `composer test:unit`, repo-wide `composer cs` (0 errors), `php -l`, `composer stan`, `composer deptrac`, `composer copy:scan`.

### References
- [Source: _bmad-output/planning-artifacts/epics.md#Story 16.2: User import + role reassignment] (FL 16.2, FR-2)
- [Source: docs/migration-plan.md — Users and profiles; "assign a single member base role … no reader/writer distinction … Drop legacy Youzify/BuddyPress custom roles"; migration order step 3]
- [Source: _bmad-output/project-context.md — FR-2 one base role; conflation rule; admin-language split]
- [Source: wp-content/plugins/ink-core/src/Kernel/Capabilities.php — role-name constants + subscriber base-role note]
- [Source: wp-content/plugins/ink-core/src/Migration/DbSanitiser.php — once-off CLI migration pattern]

## Dev Agent Record

### Agent Model Used

claude-opus-4-8 (BMAD dev-story workflow)

### Debug Log References

- `composer test:unit` → 956 passed, 1 skipped (3649 assertions). New `UserReclassifierTest`: 7 passed.
- `composer cs` → 0 errors, 0 warnings on the new files.
- `composer stan` → No errors (fixed one `isset.property` on the non-nullable `WP_User::$roles` by false-guarding `get_userdata()` and casting `(array) $user->roles`).
- `composer deptrac` → 3 pre-existing `Kernel\Activation → Content` violations only; no new edge (reads `Kernel\Capabilities`, already an allowed `Migration → Kernel` dep).
- `composer copy:scan` → no new debt (baseline 8).
- `php -l` clean on `UserReclassifier.php`.

### Completion Notes List

- `UserReclassifier` collapses every non-staff account onto the single member base role `subscriber` via `WP_User::set_role()` (which replaces ALL roles in one call, so legacy reader/writer/Youzify/BuddyPress custom roles are dropped) — FR-2's "no reader/writer distinction".
- **Staff preserved:** `isStaff()` (sourced from `Kernel\Capabilities::ADMIN_ROLE`/`EDITOR_ROLE`) skips `administrator`/`editor` so an editor is never demoted to subscriber and locked out of the admin. The non-vacuous guard test asserts both protected roles AND that member roles are not protected.
- **Profile cleanup is opt-in:** `legacyMetaKeys()` defaults to EMPTY (destructive + site-specific — the `Challenges\Migration::legacyCategories()` safe-default convention). An un-configured run drops legacy roles but cleans no meta; a site overrides with the confirmed Youzify/BP field set.
- Once-off + idempotent (`ink_migration_users_done`; `--force`); WP-CLI-only (`wp ink migrate-users`); Afrikaans summary. Conflation-clean: no `ink_writer_tier` write (16.3), no membership read.

### File List

- `wp-content/plugins/ink-core/src/Migration/UserReclassifier.php` (NEW)
- `wp-content/plugins/ink-core/src/Migration/Module.php` (MODIFIED — registered `UserReclassifier`)
- `tests/Unit/Migration/UserReclassifierTest.php` (NEW)
- `_bmad-output/implementation-artifacts/16-2-user-import-role-reassignment.md` (story record)
- `_bmad-output/implementation-artifacts/sprint-status.yaml` (status tracking)

## Change Log

- 2026-06-28 — Story 16.2 implemented: `UserReclassifier` (non-staff accounts → single `subscriber` base role, staff preserved, opt-in legacy profile-meta cleanup) + non-vacuous staff-preservation guard tests. Status → review.

---
baseline_commit: c7e6a40
---

# Story 16.8: InkPols / sponsors / nav

Status: review

<!-- Note: Validation is optional. Run validate-create-story for quality check before dev-story. -->

## Story

As an ink-core developer,
I want InkPols/sponsors/nav rebuilt,
so that structure matches the new IA. (FL 16.8)

## Acceptance Criteria

1. **Given** §11/§13/IA **When** migration runs **Then** InkPols and sponsors migrate and navigation is rebuilt fresh.
2. **Navigation is rebuilt fresh** (the new deliverable): a `NavigationRebuilder` creates/updates a single canonical `wp_navigation` entity carrying the **new IA** top-level items (NOT a copy of the old site's menu) — so the Site Editor has one clean, correct menu. Idempotent (get-or-create by title), once-off + `--force`, WP-CLI only.
3. The IA item list is an ordered set of `{label, url}` reflecting the established reader-facing sections (Tuis, Ontdek, Biblioteek, Opleiding, Uitdagings, InkPols, Gemeenskap, Oor INK, Kontak) at their canonical routes — labels are **already-authored Afrikaans** content (no new copy debt; they match the theme nav + Epic-15 org pages), written as menu *data* (staff-editable), not gettext UI strings.
4. **InkPols** migration is the existing `wp ink migrate-inkpols` command (Story 13.4 `InkPols\Migration`) — it is part of the ordered run (migration step 9); 16.8 does NOT duplicate it. **Sponsors** are **manual entry** (migration plan: very low volume; the `borg` CPT + fields are ready from Epic 14) — 16.8 scripts no sponsor import. Both facts are documented in the story.
5. Conflation-clean: the nav rebuild reads only WP core (creates a `wp_navigation` post); no `Tiers`/`Entitlement`/cross-module coupling. Afrikaans `\WP_CLI` summary.
6. `composer test:unit` green (new `NavigationRebuilderTest`); `composer cs` = 0 errors; `php -l`, `composer stan`, `composer deptrac` (no new edge), `composer copy:scan` clean.

## Tasks / Subtasks

- [x] Task 1: Implement `NavigationRebuilder` (AC: #2, #3, #5)
  - [x] Added `wp-content/plugins/ink-core/src/Migration/NavigationRebuilder.php`: `OPTION_DONE`, `CLI_COMMAND = 'ink rebuild-navigation'`, `NAV_TITLE = 'Hoofnavigasie'`, `register()` (WP-CLI-only), `run(bool $force): array`, `hasRun()`, `markDone()`.
  - [x] Pure helpers: `navItems(): array` (ordered new-IA `{label, url}`) and `toNavigationMarkup(array $items): string` (`wp:navigation` wrapping one JSON-attr `wp:navigation-link` per item).
  - [x] Overridable I/O seams: `existingNavId(): int` (find `wp_navigation` by title), `createNav()` (`wp_insert_post`), `updateNav()` (`wp_update_post`).
  - [x] Afrikaans CLI summary (created/updated + item count).
- [x] Task 2: Register in the module (AC: #2)
  - [x] Added `( new NavigationRebuilder() )->register();` to `Migration\Module::register()` + docblock.
- [x] Task 3: Tests (AC: #3, #6)
  - [x] Added `tests/Unit/Migration/NavigationRebuilderTest.php` (5 tests): `navItems()` is the expected ordered IA (labels + canonical routes incl. the Epic-15 org pages); `toNavigationMarkup()` wraps one `wp:navigation-link` per item in a `wp:navigation` block; `run()` creates when none exists, updates the existing entity (no duplicate), idempotent skip.
  - [x] All gates green.

## Dev Notes

### What already exists (read before editing)
- `wp-content/themes/ink-foundation/patterns/header-main.php` — the inline `wp:navigation` + `wp:navigation-link` block markup (the design default nav). The rebuilt `wp_navigation` entity mirrors this IA so the Site Editor's menu matches.
- `wp-content/plugins/ink-core/src/InkPols/Migration.php` — `wp ink migrate-inkpols` (Story 13.4): the InkPols back-catalogue migration. 16.8 references it, does NOT duplicate.
- `wp-content/plugins/ink-core/src/Sponsors/` — the `borg` CPT (Epic 14). Sponsor records are entered manually (migration plan); no script here.
- `wp-content/plugins/ink-core/src/Migration/*` + `Module.php` — the once-off-CLI + seam pattern.

### Architecture compliance (project-context.md)
- **Rebuild navigation fresh in the new block theme** — the new IA, NOT a copy of the old site's menu (migration plan). Bookmarked deep links are handled by the Story 16.7 redirects, not by copying the old menu.
- **Sponsors: manual entry** (migration plan — low volume, safer by hand). InkPols rides its own `migrate-inkpols` command.
- **Afrikaans nav labels are content** (menu data staff edit), authored Afrikaans matching the theme/glossary — not AI-translated, no new copy debt.
- **Conflation-clean**, WP-CLI-only, idempotent + `--force`.

### Project Structure Notes
- NEW: `src/Migration/NavigationRebuilder.php`, `tests/Unit/Migration/NavigationRebuilderTest.php`.
- MODIFIED: `src/Migration/Module.php`. No new deptrac edge (WP core only).

### Testing standards
- Override the post seams; test `navItems()`/`toNavigationMarkup()` as pure functions.
- Run `composer test:unit`, `composer cs`, `php -l`, `composer stan`, `composer deptrac`, `composer copy:scan`.

### References
- [Source: _bmad-output/planning-artifacts/epics.md#Story 16.8: InkPols / sponsors / nav] (FL 16.8)
- [Source: docs/migration-plan.md — Navigation and menus ("manually rebuild … new menu should reflect the new IA"); Sponsors ("manual entry"); InkPols ("manually or short script"); migration order steps 9–11]
- [Source: wp-content/themes/ink-foundation/patterns/header-main.php — the new-IA nav]
- [Source: wp-content/plugins/ink-core/src/InkPols/Migration.php — wp ink migrate-inkpols]

## Dev Agent Record

### Agent Model Used

claude-opus-4-8 (BMAD dev-story workflow)

### Debug Log References

- `composer test:unit` → 992 passed, 1 skipped (3780 assertions). New `NavigationRebuilderTest`: 5 passed.
- `composer cs` → 0 errors, 0 warnings (fixed two `=>`/`=` alignment warnings).
- `composer stan` → No errors.
- `composer deptrac` → 3 pre-existing only; no new edge (WP core only).
- `composer copy:scan` → no new debt (baseline 8).
- `php -l` clean on `NavigationRebuilder.php`.

### Completion Notes List

- **Nav (new deliverable):** `NavigationRebuilder` creates/updates ONE canonical `wp_navigation` entity ("Hoofnavigasie") from the new IA — Tuis/Ontdek/Biblioteek/Opleiding/Uitdagings/InkPols/Gemeenskap/Oor INK/Kontak at their canonical routes (incl. the Epic-15 org pages). Get-or-create by title (no duplicate on `--force`). Labels are already-authored Afrikaans content (no new copy debt).
- **InkPols** rides the existing `wp ink migrate-inkpols` (`InkPols\Migration`, Story 13.4) — part of the ordered run, not duplicated here. **Sponsors** are entered manually (the `borg` CPT is ready from Epic 14; volume too low to script — migration plan). Both documented; no script.
- Once-off + idempotent (`ink_migration_navigation_done`; `--force`); WP-CLI-only (`wp ink rebuild-navigation`); Afrikaans summary. Conflation-clean: creates a `wp_navigation` post via WP core only.

### File List

- `wp-content/plugins/ink-core/src/Migration/NavigationRebuilder.php` (NEW)
- `wp-content/plugins/ink-core/src/Migration/Module.php` (MODIFIED — registered `NavigationRebuilder` + docblock)
- `tests/Unit/Migration/NavigationRebuilderTest.php` (NEW)
- `_bmad-output/implementation-artifacts/16-8-inkpols-sponsors-nav.md` (story record)
- `_bmad-output/implementation-artifacts/sprint-status.yaml` (status tracking)

## Change Log

- 2026-06-28 — Story 16.8 implemented: `NavigationRebuilder` (fresh new-IA `wp_navigation` entity, get-or-create) — the nav rebuild; InkPols rides `migrate-inkpols` (13.4) and sponsors are manual entry (documented). Status → review.

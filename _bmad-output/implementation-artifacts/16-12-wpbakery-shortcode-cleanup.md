---
baseline_commit: c7e6a40
---

# Story 16.12: WPBakery shortcode cleanup

Status: review

<!-- Note: Validation is optional. Run validate-create-story for quality check before dev-story. -->

## Story

As an ink-core developer,
I want legacy shortcodes stripped,
so that no `[vc_*]` renders as raw text. (FL 16.12)

## Acceptance Criteria

1. **Given** legacy content **When** cleaned **Then** `[vc_*]` shortcodes are stripped/converted and none render as raw text.
2. The cleanup **strips every WPBakery `[vc_*]` / `[/vc_*]` tag while preserving the inner content** (e.g. `[vc_column_text]Hallo[/vc_column_text]` → `Hallo`), across both opening, closing, and self-closing forms — so no raw `[vc_*]` shortcode survives to render as literal text (the retired WPBakery/Qode stack is never reactivated).
3. **Non-`vc_` content is untouched**: core/other shortcodes (`[gallery]`, `[caption]`, …) and ordinary text/markup are left intact — only the WPBakery prefix is targeted.
4. A post is rewritten ONLY when its content actually changed (no needless writes); the run reports how many posts were cleaned.
5. Once-off + idempotent (`ink_migration_shortcodes_done`; `--force`) and **WP-CLI only** (`wp ink clean-shortcodes`). Afrikaans `\WP_CLI` summary.
6. Conflation-clean: reads/writes post content via WP core only; no `Tiers`/`Entitlement`. `composer test:unit` green (new `ShortcodeCleanupTest`, non-vacuous "vc stripped, inner kept, non-vc preserved"); `composer cs` = 0 errors; `php -l`, `composer stan`, `composer deptrac`, `composer copy:scan` clean.

## Tasks / Subtasks

- [x] Task 1: Implement `ShortcodeCleanup` (AC: #1–#5)
  - [x] Added `wp-content/plugins/ink-core/src/Migration/ShortcodeCleanup.php`: `OPTION_DONE`, `CLI_COMMAND = 'ink clean-shortcodes'`, `register()` (WP-CLI-only), `run(bool $force): array`, `hasRun()`, `markDone()`.
  - [x] Pure helper: `stripVcShortcodes(string $content): string` (`/\[\/?vc_[^\]]*\]/` → ''; keeps inner content; leaves non-`vc_` shortcodes intact).
  - [x] Overridable I/O seams: `contentRecords(): array` (posts via `s='[vc_'` search → `{id, content}`), `updatePostContent(int $id, string $content): void` (`wp_update_post`).
  - [x] Orchestration: clean each record; write only when changed; tally cleaned. Afrikaans CLI summary.
- [x] Task 2: Register in the module (AC: #5)
  - [x] Added `( new ShortcodeCleanup() )->register();` to `Migration\Module::register()` + completed the module docblock (full Epic-16 toolkit).
- [x] Task 3: Tests (AC: #2, #3, #6)
  - [x] Added `tests/Unit/Migration/ShortcodeCleanupTest.php` (4 tests): `stripVcShortcodes` — **non-vacuous**: strips `[vc_row]`/`[vc_column_text]…[/vc_column_text]`/attributes keeping inner text, removes self-closing `[vc_separator]`, and LEAVES `[gallery]`/`[caption]`/plain text untouched (incl. a vc-wrapped gallery → gallery kept); `run()` rewrites only changed posts (skips the unchanged one), counts them, idempotent skip.
  - [x] All gates green.

## Dev Notes

### What already exists (read before editing)
- `wp-content/plugins/ink-core/src/Migration/*` + `Module.php` — the once-off-CLI + seam pattern.
- `tests/Unit/InkPols/MigrationTest.php` — the anonymous-subclass-over-seams idiom.

### Architecture compliance (project-context.md)
- **`[vc_*]` shortcodes stripped/converted; none render as raw text** (migration plan: "WPBakery layout metadata should not be migrated"; the retired WPBakery/Qode stack is never reactivated, so its shortcodes must not be left to render literally).
- **Migration is scripted/ordered, WP-CLI-triggered**, idempotent + `--force`. Afrikaans CLI.
- **Conflation-clean** (post content via WP core only).
- This story STRIPS the wrapper tags (the AC's "none render as raw text"); a full shortcode→block conversion is a richer future enhancement, not required here.

### Project Structure Notes
- NEW: `src/Migration/ShortcodeCleanup.php`, `tests/Unit/Migration/ShortcodeCleanupTest.php`.
- MODIFIED: `src/Migration/Module.php`. No new deptrac edge (WP core only).

### Testing standards
- Override the content seams; test `stripVcShortcodes()` as a pure function.
- **Non-vacuous guard:** prove `[vc_*]` is stripped (inner content kept) AND a non-`vc_` shortcode is preserved — so a regression that over-strips (eats real shortcodes/content) or under-strips (leaves `[vc_*]`) fails.
- Run `composer test:unit`, `composer cs`, `php -l`, `composer stan`, `composer deptrac`, `composer copy:scan`.

### References
- [Source: _bmad-output/planning-artifacts/epics.md#Story 16.12: WPBakery shortcode cleanup] (FL 16.12)
- [Source: docs/migration-plan.md — "WPBakery layout metadata" (what can be dropped / should not be migrated)]
- [Source: _bmad-output/project-context.md — never reactivate retired plugins (WPBakery/Qode stack); migration is scripted/WP-CLI]

## Dev Agent Record

### Agent Model Used

claude-opus-4-8 (BMAD dev-story workflow)

### Debug Log References

- `composer test:unit` → 1011 passed, 1 skipped (3839 assertions). New `ShortcodeCleanupTest`: 4 passed.
- `composer cs` → 0 errors, 0 warnings.
- `composer stan` → No errors.
- `composer deptrac` → 3 pre-existing only; no new edge (WP core post content only).
- `composer copy:scan` → no new debt (baseline 8).
- `php -l` clean on `ShortcodeCleanup.php`.

### Completion Notes List

- `ShortcodeCleanup` strips every WPBakery `[vc_*]` / `[/vc_*]` tag (opening, closing, self-closing, with attributes) while preserving the inner content, so no raw `[vc_*]` survives to render literally (the retired WPBakery/Qode stack is never reactivated). Non-`vc_` shortcodes (`[gallery]`, `[caption]`) and ordinary content are left intact — the non-vacuous test proves both over-strip and under-strip would fail.
- A post is rewritten ONLY when its content actually changed (the default seam pre-filters by a `[vc_` search and `run()` skips unchanged content), so no needless writes.
- Once-off + idempotent (`ink_migration_shortcodes_done`; `--force`); WP-CLI-only (`wp ink clean-shortcodes`); Afrikaans summary. Conflation-clean: post content via WP core only.
- This completes the **Ink\Migration toolkit** for Epic 16 (12/12 commands).

### File List

- `wp-content/plugins/ink-core/src/Migration/ShortcodeCleanup.php` (NEW)
- `wp-content/plugins/ink-core/src/Migration/Module.php` (MODIFIED — registered `ShortcodeCleanup` + completed docblock)
- `tests/Unit/Migration/ShortcodeCleanupTest.php` (NEW)
- `_bmad-output/implementation-artifacts/16-12-wpbakery-shortcode-cleanup.md` (story record)
- `_bmad-output/implementation-artifacts/sprint-status.yaml` (status tracking)

## Change Log

- 2026-06-28 — Story 16.12 implemented: `ShortcodeCleanup` — strips legacy WPBakery `[vc_*]` tags (inner content kept; non-vc shortcodes preserved; changed-only writes). Completes the Epic-16 migration toolkit. Status → review.

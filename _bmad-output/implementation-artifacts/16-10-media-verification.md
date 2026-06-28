---
baseline_commit: c7e6a40
---

# Story 16.10: Media verification

Status: done

<!-- Note: Validation is optional. Run validate-create-story for quality check before dev-story. -->

## Story

As an ink-core developer,
I want media verified post-migration,
so that uploads/audio/video/PDFs work. (FL 16.10)

## Acceptance Criteria

1. **Given** migrated media **When** verified **Then** uploads are accessible, audio/video play, and PDFs open.
2. Read-only verification (no mutation): the command reports total attachments, **counts per media class** (image / audio / video / pdf / other, derived from MIME), and a **flagged list of attachments whose backing file is missing** on disk (the "accessible" check — `get_attached_file()` + file existence).
3. WP-CLI only (`wp ink verify-media`); no idempotency flag (naturally re-runnable). Afrikaans `\WP_CLI` output (per-class counts + missing-file count).
4. Conflation-clean: reads attachments + the uploads filesystem via WP core only; no `Tiers`/`Entitlement`/cross-module coupling. `composer test:unit` green (new `MediaVerifierTest`, pure class/summarise logic — the "test the OUTCOME" rule); `composer cs` = 0 errors; `php -l`, `composer stan`, `composer deptrac`, `composer copy:scan` clean.

## Tasks / Subtasks

- [x] Task 1: Implement `MediaVerifier` (AC: #1–#3)
  - [x] Added `wp-content/plugins/ink-core/src/Migration/MediaVerifier.php`: `CLI_COMMAND = 'ink verify-media'`, `register()` (WP-CLI-only), `verify(): array`, NO idempotency flag (read-only).
  - [x] Pure helpers: `mediaClassFor(string $mime): string` (image/audio/video/pdf/other) and `summarise(array $records): array` → `{total, by_class, missing}`; a record flags when `exists` is false.
  - [x] Overridable I/O seam: `attachmentRecords(): array` (every attachment → `{id, mime, exists}` via `get_attached_file()` + `file_exists()`).
  - [x] Afrikaans `\WP_CLI` summary (per-class counts + missing-file count).
- [x] Task 2: Register in the module (AC: #3)
  - [x] Added `( new MediaVerifier() )->register();` to `Migration\Module::register()` + docblock.
- [x] Task 3: Tests (AC: #2, #4)
  - [x] Added `tests/Unit/Migration/MediaVerifierTest.php` (4 tests): `mediaClassFor` maps image/*, audio/*, video/*, application/pdf, and other; `summarise` counts per class and **flags only the missing-file records** (not present ones); `verify()` summarises the seam records.
  - [x] All gates green.

## Dev Notes

### What already exists (read before editing)
- `wp-content/plugins/ink-core/src/Migration/SubscriptionVerifier.php` — the read-only-verifier pattern (no idempotency flag; pure `summarise()` + a read seam; `verify()` returns an INK-owned report). Mirror it.
- `wp-content/plugins/ink-core/src/Migration/*` + `Module.php` — the CLI + seam pattern.
- `tests/Unit/Migration/SubscriptionVerifierTest.php` — the verifier test idiom.

### Architecture compliance (project-context.md)
- **Media verification, not migration:** `wp-content/uploads/` + attachment posts ride the DB clone; this confirms uploads are accessible, audio/video files present, PDFs present (migration plan, verify steps).
- **Test the OUTCOME** — unit-test the INK-owned class/summarise logic, not WP attachment internals.
- **Conflation-clean**, WP-CLI-only, read-only, Afrikaans CLI.

### Project Structure Notes
- NEW: `src/Migration/MediaVerifier.php`, `tests/Unit/Migration/MediaVerifierTest.php`.
- MODIFIED: `src/Migration/Module.php`. No new deptrac edge (WP core only).

### Testing standards
- Override the attachment read seam; test `mediaClassFor()`/`summarise()` as pure functions.
- Run `composer test:unit`, `composer cs`, `php -l`, `composer stan`, `composer deptrac`, `composer copy:scan`.

### References
- [Source: _bmad-output/planning-artifacts/epics.md#Story 16.10: Media verification] (FL 16.10)
- [Source: docs/migration-plan.md — Media ("uploads migrates as-is … verify uploads accessible, audio/video playable, PDFs accessible"); verify step 13]
- [Source: _bmad-output/project-context.md — verify media; test the OUTCOME]
- [Source: wp-content/plugins/ink-core/src/Migration/SubscriptionVerifier.php — read-only verifier pattern]

## Dev Agent Record

### Agent Model Used

claude-opus-4-8 (BMAD dev-story workflow)

### Debug Log References

- `composer test:unit` → 1002 passed, 1 skipped (3806 assertions). New `MediaVerifierTest`: 4 passed.
- `composer cs` → 0 errors, 0 warnings.
- `composer stan` → No errors.
- `composer deptrac` → 3 pre-existing only; no new edge (WP core only).
- `composer copy:scan` → no new debt (baseline 8).
- `php -l` clean on `MediaVerifier.php`.

### Completion Notes List

- `MediaVerifier` is **read-only**: it reports total attachments, counts per media class (image/audio/video/pdf/other from MIME), and a **flagged list of attachments whose backing file is missing on disk** (`get_attached_file()` + `file_exists()` — the "accessible" check). No mutation, no idempotency flag.
- Per the "test the OUTCOME" rule, the unit tests cover the INK-owned `mediaClassFor()`/`summarise()` logic + the missing-file flagging, not WP attachment internals.
- WP-CLI-only (`wp ink verify-media`), Afrikaans summary. Conflation-clean: reads attachments + uploads filesystem via WP core only.

### File List

- `wp-content/plugins/ink-core/src/Migration/MediaVerifier.php` (NEW)
- `wp-content/plugins/ink-core/src/Migration/Module.php` (MODIFIED — registered `MediaVerifier` + docblock)
- `tests/Unit/Migration/MediaVerifierTest.php` (NEW)
- `_bmad-output/implementation-artifacts/16-10-media-verification.md` (story record)
- `_bmad-output/implementation-artifacts/sprint-status.yaml` (status tracking)

## Change Log

- 2026-06-28 — Story 16.10 implemented: `MediaVerifier` — read-only post-migration media report (per-class counts + missing-file flags). Status → review.

---
baseline_commit: 9ddafcd
---

# Story 13.4: Back-catalogue migration

Status: done

<!-- R13 code review (epic-13-code-review-2026-06-28.md): P2 — run() now counts created (new inserts) vs reconciled (--force matched the source marker) separately so the CLI summary never overstates inserts (ensureIssue returns {id,created}); P3 — issueDateFromName hardened: whole-word month match, first-by-string-position, plausible 19xx/20xx year only. Deferred: site-specific seam wiring (Epic 16) + findIssueForLegacy integration test (18.8). 0 unresolved HIGH/MEDIUM. -->


<!-- Note: Validation is optional. Run validate-create-story for quality check before dev-story. -->

## Story

As an ink-core developer,
I want existing PDFs re-linked,
so that the back catalogue survives. (FL 13.4)

## Acceptance Criteria

**Given** legacy issues
**When** migration runs
**Then** existing PDFs are re-linked and month/year naming is replaced with date+volume meta.

Decomposed:

1. A once-off, idempotent `InkPols\Migration` converts each legacy InkPols issue into the `inkpols_uitgawe` model — mirroring the Story 12.8 `Challenges\Migration` (the established once-off migration house style): WP-CLI-only trigger, completion-option guard, `--force` reconcile, overridable I/O seams, safe-empty default.
2. **Existing PDFs are re-linked:** for each legacy issue, the migration resolves its existing PDF attachment id (an overridable `pdfIdFor()` seam — the back catalogue rides the cloned DB, attachments already exist) and writes it to `FieldSets::INKPOLS_PDF_ID` — never re-uploading or duplicating the media.
3. **Month/year naming → date + volume meta:** a pure `issueDateFromName()` parses the legacy month/year naming (Afrikaans month + year, e.g. "Mei 2018", or numeric `YYYY-MM` / `MM/YYYY`) into a normalised `Y-m-01` and writes it to `FieldSets::INKPOLS_ISSUE_DATE`; the volume label is written to `FieldSets::INKPOLS_VOLUME` (an overridable `volumeFor()` seam, defaulting to the legacy name when no explicit volume exists). An unparseable name leaves the issue date empty rather than fabricating a date.
4. **Idempotent + guarded:** a completion option (`OPTION_DONE`) makes a re-run a no-op (`--force` opt-in); a `SOURCE_LEGACY_META` marker makes `ensureIssue()` get-or-create (a `--force` re-run reconciles the existing migrated issue instead of inserting a duplicate — the 12.8 R12 precedent). A malformed empty-name legacy issue is skipped (no untitled published issue).
5. **WP-CLI only:** the trigger is `wp ink migrate-inkpols` — registered ONLY under `WP_CLI`, never auto-run on a web request (the 12.8 precedent). The `legacyIssues()` seam returns an EMPTY list by default (the legacy selection is site-specific; a blanket default would mis-convert unrelated posts), so an un-overridden run is a deliberate no-op.
6. Conflation-clean: reads `Ink\Content` (the CPT slug + the `FieldSets` meta-key constants) + WP core only — **zero** `Ink\Tiers`/`Ink\Entitlement`. No new deptrac edge (the `InkPols -> Content` edge already exists).

## Tasks / Subtasks

- [x] Task 1: `InkPols\Migration` class (AC: 1, 4, 5, 6) — `class` (not final; overridable seams), `OPTION_DONE = ink_inkpols_migration_done`, `CLI_COMMAND = ink migrate-inkpols`, `SOURCE_LEGACY_META = ink_inkpols_source_id`; `register()` WP-CLI-guarded `add_command` with an Afrikaans success summary.
- [x] Task 2: Pure builders (AC: 3)
  - [x] Subtask 2.1: `MONTHS` Afrikaans-month→`MM` map + pure `issueDateFromName(string $name): string` — Afrikaans "Maand JJJJ" / numeric `YYYY-MM` / `MM/YYYY` → `Y-m-01`; '' when unparseable.
  - [x] Subtask 2.2: Pure `issuePostArr(object $issue): array` — `inkpols_uitgawe`, title from the legacy name, `publish`.
- [x] Task 3: Idempotency-guarded orchestration (AC: 2, 3, 4) — `run(bool $force): array{skipped,created,relinked}`: skip when done (unless force); per legacy issue skip empty-name → `ensureIssue()` (get-or-create via the marker) → write issue-date + volume + (when present) pdf meta → tally; `markDone()`.
- [x] Task 4: Overridable I/O seams (AC: 2, 4, 5) — `legacyIssues()` (safe `[]` default), `ensureIssue()`/`findIssueForLegacy()`/`createIssue()`, `pdfIdFor()`, `volumeFor()`, `setIssueMeta()`, `hasRun()`/`markDone()`.
- [x] Task 5: Module wiring (AC: 5) — `InkPols\Module::register()` registers the `Migration` CLI trigger (the WP-CLI guard keeps it inert on web requests).
- [x] Task 6: Tests — `tests/Unit/InkPols/MigrationTest.php` (issueDateFromName Afrikaans/numeric/unparseable; issuePostArr; run idempotent no-op; run relinks PDFs + writes date/volume meta + marks done; empty-name skip; `--force` reconcile reuses the marked issue) — mirroring `Challenges\MigrationTest`.
- [x] Task 7: Gates — `composer test:unit`, `composer cs`, `composer stan`, `composer deptrac`, `composer copy:scan` all green; record counts in Completion Notes.

## Dev Notes

- **Mirror Story 12.8 `Challenges\Migration` exactly** — it is the proven once-off-migration house style: WP-CLI-only `register()`, `OPTION_DONE` idempotency, `--force` + a `SOURCE_*_META` get-or-create marker (R12 reconcile, no duplicates), pure builders + overridable I/O seams, and a safe-empty `legacyIssues()` default. [Source: src/Challenges/Migration.php]
- **Re-link, never re-upload.** The back catalogue rides the cloned DB (brownfield) — the PDF attachments already exist. The migration only writes the existing attachment id into `FieldSets::INKPOLS_PDF_ID`; resolving that id from the legacy issue is the site-specific `pdfIdFor()` seam. [Source: project-context.md "Brownfield"; "Migration is scripted and ordered … InkPols"]
- **Meta-key single source:** write via `FieldSets::INKPOLS_ISSUE_DATE` / `INKPOLS_VOLUME` / `INKPOLS_PDF_ID` (the 2.4 constants the 13.1 read-model reads back) — never inline the `ink_inkpols_*` literals. [Source: src/Content/FieldSets.php:46-50]
- **Date normalisation is the AC's core:** "month/year naming replaced with date+volume meta". `issueDateFromName()` is pure and unit-tested across the Afrikaans-month and numeric shapes; it normalises to the first of the month (`Y-m-01`) and returns '' (no fabricated date) when it cannot parse — the read-model's `year()`/`displayDate()` then degrade gracefully (13.1). The Afrikaans month map is migration-local (parsing legacy data); it does not depend on `Challenges\Cadence` (deptrac forbids `InkPols -> Challenges`). [Source: epics.md#Story 13.4; src/InkPols/Issue.php]
- **Migration order:** the binding sequence runs InkPols after posts+redirects (project-context migration order). This story provides the scripted, idempotent tool; the actual legacy-issue selection + `pdfIdFor`/`volumeFor` wiring is supplied at migration time (Epic 16), exactly as 12.8 left `legacyCategories()` to the site. [Source: project-context.md "Migration is scripted and ordered"]
- **No new copy:** the only user-facing string is the WP-CLI success summary (admin/CLI Afrikaans, `ink-core` domain) — generic migration chrome, mirroring 12.8's summary; no glossary term, no front-end copy. [Source: src/Challenges/Migration.php:82-91]
- **Conflation rule:** migration touches only content models — no tier/entitlement. [Source: project-context.md "THE conflation rule"]

### Project Structure Notes

- New: `src/InkPols/Migration.php`, `tests/Unit/InkPols/MigrationTest.php`.
- Modified: `src/InkPols/Module.php` (register the Migration CLI trigger).
- No deptrac change (the `InkPols -> Content` edge already covers `PostTypes`/`FieldSets`). No new CPT/meta/Terms.

### References

- [Source: _bmad-output/planning-artifacts/epics.md#Story 13.4]
- [Source: wp-content/plugins/ink-core/src/Challenges/Migration.php] — the once-off migration house style (12.8)
- [Source: tests/Unit/Challenges/MigrationTest.php] — the seam-override test pattern
- [Source: wp-content/plugins/ink-core/src/Content/FieldSets.php] — InkPols meta-key constants

## Dev Agent Record

### Agent Model Used

claude-opus-4-8

### Debug Log References

### Completion Notes List

- Built `InkPols\Migration` — the once-off, idempotent back-catalogue migration, mirroring `Challenges\Migration` (12.8): WP-CLI-only `wp ink migrate-inkpols`, `OPTION_DONE` guard, `--force` + `SOURCE_LEGACY_META` get-or-create reconcile (no duplicates), safe-empty `legacyIssues()` default, overridable I/O seams.
- **Re-link, never re-upload:** `run()` writes the existing PDF attachment id (from the `pdfIdFor()` seam) into `FieldSets::INKPOLS_PDF_ID`; only counts a relink when a positive id is present (an issue with no PDF still migrates, no pdf meta written).
- **Month/year → date+volume:** pure `issueDateFromName()` parses Afrikaans "Maand JJJJ" (a migration-local month map, no `Challenges` dep), numeric `YYYY-MM` and `MM/YYYY` → normalised `Y-m-01`; an out-of-range month or unparseable name yields '' (no fabricated date — the 13.1 read-model degrades gracefully). Volume via the `volumeFor()` seam (explicit legacy volume, else the legacy name).
- All meta written via the `FieldSets::INKPOLS_*` constants (single source the 13.1 read-model reads back). Registered as the Migration CLI trigger in `InkPols\Module` (WP-CLI guard keeps it inert on web requests).
- Conflation-clean: reads `Ink\Content` + WP core only — zero Tiers/Entitlement, no new deptrac edge. Only user-facing string is the CLI success summary (admin Afrikaans, `ink-core` domain) — no front-end copy.
- **Deferred (Epic 16 migration wiring):** the site supplies the real `legacyIssues()` selection + `pdfIdFor()`/`volumeFor()` resolution at migration time (exactly as 12.8 left `legacyCategories()` to the site).
- **Gates:** `composer test:unit` → 858 passed / 1 skipped (+9, zero regressions); `composer cs` → clean on new files; `composer stan` → No errors; `composer deptrac` → 3 = the documented pre-existing `Kernel\Activation -> Content` baseline, no new edge; `composer copy:scan` → no new placeholder debt.

### File List

- `wp-content/plugins/ink-core/src/InkPols/Migration.php` (new)
- `wp-content/plugins/ink-core/src/InkPols/Module.php` (modified — register the Migration CLI trigger)
- `tests/Unit/InkPols/MigrationTest.php` (new)

### Change Log

- 2026-06-28: Story 13.4 implemented — once-off idempotent InkPols back-catalogue migration (`wp ink migrate-inkpols`): re-links existing PDFs + normalises month/year naming to date+volume meta. Status → review.

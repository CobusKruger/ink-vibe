# Story 12.2: Uitdagings list page

Status: review

## Story

As a skrywer,
I want a challenges list with countdown,
so that I can find open challenges. (FR-46)

## Acceptance Criteria

**Given** the list page (Archetype B)
**When** it renders
**Then** challenges are listed with a countdown.

Decomposed:

1. An `archive-uitdaging` FSE template renders within locked chrome and embeds an `ink-foundation/uitdaging` pattern that hosts the server-rendered `ink/uitdaging-argief` block (mirrors `archive-biblioteek_item.html` + `biblioteek.php` + `ink/biblioteek-argief`).
2. The block lists published `uitdaging` posts newest-first, paginated (reuses `Kernel\ArchiveRender::pagination`), each as a card: title → permalink + the **tema** + the **sluitingsdatum**.
3. Each card shows a server-computed **countdown** derived from the inclusive end-of-day-SAST deadline: `Nog N dae` while open, `Sluit vandag` on the deadline day, `Gesluit` once closed (pure `countdownLabel()`); a card with no deadline shows no countdown.
4. Open challenges carry an `is-oop` marker and closed ones `is-gesluit` (same SAST boundary as 12.1).
5. With no published challenges the block renders a graceful empty state.
6. Conflation-clean: reads only `Ink\Content` + `Kernel\Sast`/`ArchiveRender` + `Terms` + WP core — zero Tiers/Entitlement.

## Tasks / Subtasks

- [x] Task 1: `Challenges\Archive` server block — `ink/uitdaging-argief`; pure `queryArgs` + `countdownLabel` + `cardHtml`/`toHtml`, thin `render()`. Mirrors `Library\Archive`.
- [x] Task 2: Terminology — `tema` + `uitdaging_sluit_vandag` added to `Terms::map()` (reuses `uitdaging_gesluit`/`sluitingsdatum`/`uitdaging_plural`); the day-count strings are in-module `__()` sentences (singular "Nog 1 dag" / plural "Nog %d dae").
- [x] Task 3: Module wiring — `Challenges\Module::register()` registers `Archive`.
- [x] Task 4: Theme — `templates/archive-uitdaging.html` + `patterns/uitdaging.php`.
- [x] Task 5: Tests — `ArchiveTest.php` (11 cases) + `UitdagingTemplateTest.php` extended (+2 archive cases).
- [x] Task 6: Gates — all green; no new deptrac edge (Challenges->Content from 12.1).
- [x] Task 7 (refactor): extracted `Challenges\Deadline` (parse/format) shared by 12.1 `SinglePage` + 12.2 `Archive` — removed the duplicated private helpers.

## Dev Notes

- Mirror `Ink\Library\Archive` exactly (PER_PAGE, PAGED_VAR, register/registerBlock, pure queryArgs+toHtml, ArchiveRender::pagination). [Source: src/Library/Archive.php]
- Deadline meta `FieldSets::UITDAGING_DEADLINE`, theme meta `FieldSets::UITDAGING_THEME`; parse like 12.1 `SinglePage::parseDeadline`. SAST boundary via `Sast::isThroughEndOfDay`. [Source: src/Challenges/SinglePage.php; src/Kernel/Sast.php]
- Countdown is server-computed (AD-7 server-render house style; no client JS framework in the theme). Days-remaining = SAST calendar-day difference to `Sast::endOfDay(deadline)`.
- `tema` glossary label (challenge_theme) line 123; sluitingsdatum line 124. [Source: docs/afrikaans-terms.md]

### Project Structure Notes

- New: `src/Challenges/Archive.php`, `templates/archive-uitdaging.html`, `patterns/uitdaging.php`, `tests/Unit/Challenges/ArchiveTest.php`.
- Modified: `src/Challenges/Module.php`, `src/I18n/Terms.php`, `tests/Unit/Challenges/UitdagingTemplateTest.php`.

### References

- [Source: _bmad-output/planning-artifacts/epics.md#Story 12.2]
- [Source: wp-content/plugins/ink-core/src/Library/Archive.php]

## Dev Agent Record

### Agent Model Used

claude-opus-4-8

### Debug Log References

### Completion Notes List

- `ink/uitdaging-argief` lists published uitdagings newest-first, paginated, with a per-card server-computed countdown (`Nog N dae` / `Nog 1 dag` / `Sluit vandag` / `Gesluit`) off the inclusive end-of-day-SAST boundary. Card shows title→permalink, tema, sluitingsdatum + `is-oop`/`is-gesluit` marker.
- Countdown is server-rendered by SAST calendar-day diff (no client JS framework in the theme; AD-7). Reused `Kernel\ArchiveRender::pagination`.
- Refactor: pulled the duplicated deadline parse/format out of `SinglePage` (12.1) into a new `Challenges\Deadline` single source — both surfaces now share it. `Kernel\Sast` remains the single source for the open/closed boundary.
- **Gates:** `composer test` → 777 passed / 2 skipped (+12, zero regressions); `composer cs` → 0 errors (2 pre-existing warnings; one alignment nit auto-fixed in Archive.php); `composer stan` → No errors; `composer deptrac` → 3 pre-existing violations only, no new edge.

### File List

- `wp-content/plugins/ink-core/src/Challenges/Archive.php` (new)
- `wp-content/plugins/ink-core/src/Challenges/Deadline.php` (new — shared parse/format)
- `wp-content/plugins/ink-core/src/Challenges/SinglePage.php` (modified — use Deadline)
- `wp-content/plugins/ink-core/src/Challenges/Module.php` (modified — register Archive)
- `wp-content/plugins/ink-core/src/I18n/Terms.php` (modified — tema + sluit_vandag keys)
- `wp-content/themes/ink-foundation/templates/archive-uitdaging.html` (new)
- `wp-content/themes/ink-foundation/patterns/uitdaging.php` (new)
- `tests/Unit/Challenges/ArchiveTest.php` (new)
- `tests/Unit/Challenges/UitdagingTemplateTest.php` (modified — +2 archive cases)

### Change Log

- 2026-06-27: Story 12.2 implemented — uitdagings list page (paginated card grid + countdown). Extracted shared `Challenges\Deadline`. Status → review.

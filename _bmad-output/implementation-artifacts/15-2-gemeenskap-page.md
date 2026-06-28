---
baseline_commit: 3728fd3
---

# Story 15.2: Gemeenskap page

Status: done

## Story

As a besoeker,
I want a conversion/marketing page,
so that I understand the value of joining. (FR-60)

## Acceptance Criteria

1. **Given** the Gemeenskap page **When** it renders **Then** it presents value props, principles, how-it-works, and CTAs.
2. The page is served by a slug-based FSE template `templates/page-gemeenskap.html` (thin wrapper: header part → content pattern → footer part), matching the `page-ledegids.html` convention; content lives in a `patterns/gemeenskap.php` content pattern.
3. Presentation only — no business logic, no `WP_Query`, no server-rendered `ink/` block. (Dynamic surfaces from the Lovable design — live statistics counters and the "Kollig"/spotlight featured writer-reader — are NOT built here; they need live data and belong to a future ink-core block. Documented as deferred.)
4. All copy is the human-authored Afrikaans from `docs/ui-copy-translations.md` (Gemeenskap-bladsy section, lines ~257–357) — verbatim, never AI-translated. Sentence-case headings.
5. All colours/spacing/type use `theme.json` tokens — no hardcoded values. Critical structure block-locked consistent with sibling patterns.
6. CTAs link to real routes: "Sluit aan as skrywer"/"Skep jou rekening" → `/registreer`; "Kyk eers rond" → `/lees`.

## Tasks / Subtasks

- [x] Task 1: Create `patterns/gemeenskap.php` content pattern (AC: #1, #3, #4, #5, #6)
  - [x] Hero: eyebrow "Die INK-gemeenskap", H1 (line 264), intro (line 265), two buttons (Sluit aan as skrywer / Sluit aan as leser → /registreer).
  - [x] Value props "Vir skrywers" (3 benefit cards) and "Vir lesers" (4 benefit cards) using the `is-style-card` group style, on a surface-alt band.
  - [x] "Hoe INK werk" (H2 + supporting line + two 3-step columns for lesers/skrywers).
  - [x] "Gemeenskapsbeginsels" (eyebrow + H2 + 4 principle cards).
  - [x] Closing CTA mirroring `cta-band` (secondary band, H2 line 353, body line 354, buttons: Skep jou rekening → /registreer, Kyk eers rond → /lees).
  - [x] Registered slug `ink-foundation/gemeenskap`, Afrikaans Title, Categories `ink-foundation, page`. Token-only; sentence-case; locked.
- [x] Task 2: Create `templates/page-gemeenskap.html` thin wrapper (AC: #2)
  - [x] header part → `<main>` → `wp:pattern ink-foundation/gemeenskap` → footer part, matching `page-ledegids.html`.
- [x] Task 3: Tests (AC: #1, #2, #3)
  - [x] Added `tests/Unit/Org/GemeenskapTemplateTest.php` (3 tests, 14 assertions): template embeds the pattern within locked chrome; pattern is non-vacuous (hero H1 + the four AC section headings) and carries no `wp:ink/` block / no post query (three-layer guard).
- [x] Task 4: Gates
  - [x] `composer test:unit` green (918 passed / 1 skipped); `phpcs` on the new pattern → 0 errors; `composer copy:scan` no new debt; `php -l` clean.

## Dev Notes

### Convention (read first)
- Thin template wrapper: `templates/page-ledegids.html` is the exact shape to copy. Slug-based `page-{slug}.html` is auto-applied by WP when a page with slug `gemeenskap` exists (brownfield: the page row rides the DB clone / is created in admin). No `customTemplates` entry needed (those are for user-selectable templates only).
- Content pattern convention: `patterns/ledegids.php`, `patterns/lidmaatskap.php`, etc. hold page content; the template just references them.
- Card style: `className":"is-style-card"` group (see `featured-grid.php`). Buttons: `primary` bg + `surface-alt` text (filled) and `is-style-outline` + `primary` text (outline) — see `hero.php`/`cta-band.php`.
- i18n: static block-pattern starting content carries raw human-authored Afrikaans literals (the resolved convention — gettext is only for PHP-executed conditional seams, e.g. `auth-login.php`). All Gemeenskap copy is already authored, so no placeholder markers and no copy:scan debt.

### Architecture compliance (project-context.md)
- Three-layer separation: presentation only; no tier/challenge/follow logic. The live-stats and spotlight sections from the Lovable mock are dynamic data → deferred (would be an ink-core block), NOT faked with hardcoded numbers.
- Design tokens (Gate A) only; sentence-case headings (Gate D); Afrikaans-only, human-authored.

### Project Structure Notes
- New: `wp-content/themes/ink-foundation/patterns/gemeenskap.php`, `wp-content/themes/ink-foundation/templates/page-gemeenskap.html`, `tests/Unit/Org/GemeenskapTemplateTest.php`.
- No ink-core changes. No theme.json changes.

### References
- [Source: _bmad-output/planning-artifacts/epics.md#Story 15.2: Gemeenskap page] (FR-60)
- [Source: docs/ui-copy-translations.md#Gemeenskap-bladsy lines 257–357]
- [Source: docs/design-handoff/page-map.csv — page-gemeenskap sections]
- [Source: wp-content/themes/ink-foundation/templates/page-ledegids.html — thin wrapper convention]
- [Source: wp-content/themes/ink-foundation/patterns/{hero,featured-grid,cta-band}.php]

## Dev Agent Record

### Agent Model Used

claude-opus-4-8 (BMAD dev-story workflow)

### Debug Log References

- `composer test:unit` → 918 passed, 1 skipped. New `GemeenskapTemplateTest`: 3 passed.
- One iteration: the three-layer guard test tripped on the pattern's own docblock (literal "WP_Query" in a comment) — reworded the comment to "no post queries"; re-ran green.
- `phpcs` on `gemeenskap.php` → exit 0. `composer copy:scan` → no new debt. `php -l` clean.

### Completion Notes List

- Gemeenskap is a presentation-only conversion page: `page-gemeenskap.html` (thin wrapper) → `gemeenskap.php` (content). Slug-based template, auto-applied to a page with slug `gemeenskap`; no `customTemplates` entry (consistent with `page-ledegids.html`).
- All five sections built with verbatim human-authored Afrikaans (ui-copy lines 257–357): hero, "Vir skrywers"/"Vir lesers" value props (card grids), "Hoe INK werk" (two 3-step columns), "Gemeenskapsbeginsels" (4 principle cards), closing CTA. Satisfies AC #1 (value props, principles, how-it-works, CTAs).
- **Deferred (documented in the pattern docblock):** the Lovable design's live-statistics counters and the "Kollig" featured-writer/reader spotlight are dynamic-data surfaces — they need live counts/queries and belong to a future ink-core block. NOT faked with hardcoded numbers and NOT in the AC list. This keeps the page three-layer-clean.
- No ink-core changes; no theme.json changes.

### File List

- `wp-content/themes/ink-foundation/patterns/gemeenskap.php` (NEW)
- `wp-content/themes/ink-foundation/templates/page-gemeenskap.html` (NEW)
- `tests/Unit/Org/GemeenskapTemplateTest.php` (NEW)
- `_bmad-output/implementation-artifacts/15-2-gemeenskap-page.md` (story record)
- `_bmad-output/implementation-artifacts/sprint-status.yaml` (status tracking)

## Change Log

- 2026-06-28 — Story 15.2 implemented: Gemeenskap conversion page (content pattern + thin template + structural tests). Deferred dynamic stats/spotlight to a future ink-core block. Status → done.

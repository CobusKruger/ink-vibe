---
baseline_commit: 088682a
---

# Story 15.3: Oor INK

Status: done

## Story

As a besoeker,
I want an about page,
so that I learn INK's mission and contacts. (FR-60)

## Acceptance Criteria

1. **Given** Oor INK (assembly-only) **When** it renders **Then** it shows mission, contact, sponsors, and org pages.
2. **And** it uses clearly-marked placeholders for founding year + SA legal status (pre-launch content gate; never US "501(c)(3)" wording).
3. Served by slug-based `templates/page-oor-ink.html` (thin wrapper) → `patterns/oor-ink.php` content pattern, matching the `page-ledegids.html` convention.
4. The sponsors section embeds the already-built `ink-foundation/borg-erkenning` pattern (Story 14.4) — which itself wraps the server-rendered `ink/borg-erkenning` ink-core block. This is the sanctioned three-layer seam (theme pattern embeds an ink-core block), NOT new sponsor logic.
5. The "Word 'n borg" CTA inside `borg-erkenning` already targets `/kontak` (guarded `home_url('/kontak')`, Story 14.4) — confirm it resolves once 15.4 Kontak exists; no change needed in this story beyond noting it.
6. All prose is human-authored Afrikaans (reuse ui-copy rows; never AI-translate); org-detail placeholders use the `[stigtingsjaar]` / `[regstatus]` token convention from project-context (NOT the `[NEEDS HUMAN AFRIKAANS]` copy-marker — these are org-value placeholders, a distinct pre-launch gate). Tokens only; sentence-case.

## Tasks / Subtasks

- [x] Task 1: Create `patterns/oor-ink.php` content pattern (AC: #1, #2, #4, #6)
  - [x] Mission/hero: eyebrow "Oor INK", H1 "Ons missie", mission prose reusing the authored "niewinsgerigte literêre tuiste" line (ui-copy 265) and the "sedert [stigtingsjaar]" tagline (placeholder, not hardcoded).
  - [x] Org/legal detail section: clearly-marked `[regstatus]` + `[stigtingsjaar]` placeholders; generic Afrikaans framing ("'n Niewinsgerigte gemeenskapsorganisasie") — no US legal wording.
  - [x] Contact section: prose + button "Kontak ons" → `/kontak`.
  - [x] Sponsors: embeds `ink-foundation/borg-erkenning` (the 14.4 ink-core seam).
  - [x] Org pages: "Meer oor INK" links group (Gemeenskap → /gemeenskap, Uitdagings → /uitdagings, Word 'n borg → /kontak).
  - [x] Registered slug `ink-foundation/oor-ink`, Afrikaans Title, Categories `ink-foundation, page`. Locked; tokens only.
- [x] Task 2: Create `templates/page-oor-ink.html` thin wrapper (AC: #3)
  - [x] header part → `<main>` → `wp:pattern ink-foundation/oor-ink` → footer part.
- [x] Task 3: Tests (AC: #1, #2, #4)
  - [x] Added `tests/Unit/Org/OorInkTemplateTest.php` (3 tests, 14 assertions): embed within locked chrome; mission/contact/sponsors(borg-erkenning)/org-pages content; org-placeholder presence + the US-legal-wording ABSENCE guard.
- [x] Task 4: Gates
  - [x] `composer test:unit` green (921 passed / 1 skipped); `phpcs` 0 errors; `composer copy:scan` no new debt; `php -l` clean.

## Dev Notes

### Convention / existing assets
- `page-ledegids.html` = thin-wrapper shape. `borg-erkenning.php` (slug `ink-foundation/borg-erkenning`) already exists (Story 14.4) and is inserter-available; it wraps `wp:ink/borg-erkenning`. Embedding it is the three-layer seam — do NOT re-implement sponsor logic.
- The `borg-erkenning` block's internal "Word 'n borg" CTA already points at `home_url('/kontak')` (14.4). 15.4 creates the Kontak page, so the link resolves after this epic.
- **Org placeholders** (project-context "Org placeholders" rule): `[stigtingsjaar]`, `[regstatus]` — clearly-marked, pending real values at a pre-launch content gate. These are content-value placeholders, distinct from the `[NEEDS HUMAN AFRIKAANS]` Afrikaans-copy marker; they do NOT trip `composer copy:scan` (which only scans the three copy-markers). Never ship US "501(c)(3)" wording.

### Architecture compliance
- Three-layer: the page is presentation; the only dynamic content is the embedded ink-core `borg-erkenning` block (sanctioned). No raw queries in the theme.
- Design tokens only; sentence-case; Afrikaans-only human-authored prose.

### Guard-test nuance
- Unlike 15.1/15.2, this page SHOULD contain `wp:ink/borg-erkenning` (the sponsor block). The guard asserts that embed is present (the seam) and that NO US legal wording leaked — do NOT assert "no wp:ink/" here.

### References
- [Source: _bmad-output/planning-artifacts/epics.md#Story 15.3: Oor INK] (FR-60)
- [Source: docs/ui-copy-translations.md lines 33, 49, 105, 265]
- [Source: _bmad-output/project-context.md — "Org placeholders" rule, Gate A/D]
- [Source: wp-content/themes/ink-foundation/patterns/borg-erkenning.php — Story 14.4 sponsor section]
- [Source: docs/design-handoff/page-map.csv — page-oor-ink sections (assembly-only)]

## Dev Agent Record

### Agent Model Used

claude-opus-4-8 (BMAD dev-story workflow)

### Debug Log References

- `composer test:unit` → 921 passed, 1 skipped. New `OorInkTemplateTest`: 3 passed.
- Iteration: the US-legal-wording absence guard initially failed because the pattern's own docblock contained the literal "501(c)(3)" — reworded the comment to "US nonprofit legal-status wording"; re-ran green. (Same self-reference trap as 15.2's "WP_Query" docblock — guard tests substring-match the whole file.)
- `phpcs` on `oor-ink.php` → exit 0. `composer copy:scan` → no new debt. `php -l` clean.

### Completion Notes List

- Oor INK is assembly-only: `page-oor-ink.html` → `oor-ink.php`. Sections: mission (with `[stigtingsjaar]` placeholder), org/legal detail (`[regstatus]` + `[stigtingsjaar]`, generic Afrikaans, no US wording), contact (CTA → /kontak), sponsors (embeds the 14.4 `borg-erkenning` ink-core block), and a "Meer oor INK" org-pages links group. Satisfies AC #1 + #2.
- The embedded `borg-erkenning` block's "Word 'n borg" CTA already targets `/kontak` (`RecognitionSection.php:130`, guarded `home_url('/kontak')`) — confirmed; it resolves once 15.4 creates the Kontak page. AC #5 satisfied with no code change.
- Org-detail placeholders `[stigtingsjaar]`/`[regstatus]` are the project-context "Org placeholders" convention — a pre-launch content gate distinct from the Afrikaans-copy markers; they do NOT trip `composer copy:scan`. Real values pending owner confirmation before launch.
- Three-layer clean: the only dynamic content is the sanctioned `wp:ink/borg-erkenning` block (via its pattern); no raw queries in the theme.

### File List

- `wp-content/themes/ink-foundation/patterns/oor-ink.php` (NEW)
- `wp-content/themes/ink-foundation/templates/page-oor-ink.html` (NEW)
- `tests/Unit/Org/OorInkTemplateTest.php` (NEW)
- `_bmad-output/implementation-artifacts/15-3-oor-ink.md` (story record)
- `_bmad-output/implementation-artifacts/sprint-status.yaml` (status tracking)

## Change Log

- 2026-06-28 — Story 15.3 implemented: Oor INK about page (mission + org placeholders + contact + embedded borg-erkenning sponsors + org-pages links) with the pre-launch US-legal-wording guard. Status → done.

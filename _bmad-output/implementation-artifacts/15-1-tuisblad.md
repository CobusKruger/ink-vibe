---
baseline_commit: 1aa8e8aaa5fed22cba59adfc77ed17f6894de4d0
---

# Story 15.1: Tuisblad

Status: done

<!-- Note: Validation is optional. Run validate-create-story for quality check before dev-story. -->

## Story

As a besoeker,
I want a welcoming homepage,
so that I understand INK and find featured content. (FR-59)

## Acceptance Criteria

1. **Given** the Tuisblad (reference-ready) **When** it renders **Then** it shows hero spotlight, challenge section, featured works, sponsors, and CTA — in that vertical order.
2. The page is assembled entirely from theme patterns/blocks (presentation only). **No business logic in the theme** — no `WP_Query`, no PHP data fetching in `front-page.html`. (The borg-strook pattern's dynamic rendering is owned by `Ink\Sponsors`, not the template.)
3. All user-facing copy is Afrikaans and goes through the `ink-foundation` text domain in any `.php` pattern markup (no raw literal user-facing text in pattern markup); copy matches `docs/ui-copy-translations.md` where authored.
4. All colours/spacing/type use `theme.json` tokens — no hardcoded values.
5. The challenge ("Uitdaging") section is a static teaser/entry-point that links to `/uitdagings`; it carries no per-challenge query logic (a dynamic "current challenge" surface is Epic 12/12A territory, not this assembly story).
6. The featured-works section reuses the existing `ink-foundation/featured-grid` pattern; the CTA reuses `ink-foundation/cta-band`; the sponsors strip reuses `ink-foundation/borg-strook` (already wired).
7. Critical editorial structure is block-locked (`lock:{move,remove}`) consistent with the rest of `front-page.html` and archetype A.

## Tasks / Subtasks

- [x] Task 1: Create the home challenge teaser pattern (AC: #1, #3, #4, #5, #7)
  - [x] Add `wp-content/themes/ink-foundation/patterns/huidige-uitdaging.php` — a static "Uitdaging" entry-point section (eyebrow + heading + supporting paragraph + button to `/uitdagings`), following the structure/locking conventions of `hero.php` / `cta-band.php`.
  - [x] Register slug `ink-foundation/huidige-uitdaging`, Afrikaans Title, appropriate Categories (`featured, call-to-action, ink-foundation`).
  - [x] All copy raw Afrikaans block-content (matching sibling content patterns hero/cta-band, per the resolved convention: gettext is for PHP-executed seams, not editable block-pattern starting content); sentence-case heading; tokens only; section + buttons block-locked.
- [x] Task 2: Assemble `front-page.html` into the required section order (AC: #1, #2, #6)
  - [x] Kept the existing header part, the existing inline hero block, and the existing `borg-strook` pattern reference.
  - [x] Inserted, in order after the hero: `huidige-uitdaging` → `featured-grid` → (sponsors `borg-strook`, already present) → `cta-band`. Final order: header → hero → uitdaging → featured works → sponsors → CTA → footer.
  - [x] Confirmed the wenneraankondiging featured slot is **out of scope here** (Story 15.6 inserts it); insertion point left structurally clean (between hero and uitdaging).
- [x] Task 3: Copy fidelity + glossary (AC: #3)
  - [x] Used authored Afrikaans copy from `docs/ui-copy-translations.md` (Uitdagingafdeling + "Vir skrywers" rows): heading "Maandelikse uitdagings" (line 278), body "Uitdagings wat jou skryfvermoëns toets…" (line 279). No unauthored strings — no placeholder workflow needed; `composer copy:scan` shows no new debt.
- [x] Task 4: Tests (AC: #1, #2, #6)
  - [x] Added `tests/Unit/Org/TuisbladTemplateTest.php` (3 tests, 16 assertions) following the `OpleidingTemplateTest` precedent: asserts the assembly + section ordering + the static-teaser/three-layer guard (no `wp:ink/` block, no `WP_Query`). Non-vacuous (positive markers asserted first). `composer test:unit` green: 915 passed / 1 skipped.
  - [x] Repo-wide `composer cs` = 0 errors (only pre-existing Epic-7 slow-query warnings remain, untouched); `php -l` clean on the new pattern.

## Dev Notes

### What already exists (read before editing)
- `wp-content/themes/ink-foundation/templates/front-page.html` — currently: header part → `<main>` with an **inline hero** (eyebrow "Tuisblad", H1 "Waar woorde lesers vind", intro, two buttons) → `borg-strook` pattern → footer part. The hero is inline markup (NOT the `hero.php` pattern). Preserve it; do not swap it for the pattern unless trivial — the AC only requires a hero spotlight, which the inline block satisfies.
- `patterns/featured-grid.php` (slug `ink-foundation/featured-grid`, "Uitgesoekte rooster") — 3-column card grid of featured works. Reuse as-is.
- `patterns/cta-band.php` (slug `ink-foundation/cta-band`, "Oproep tot aksie") — closing CTA with two buttons. Reuse as-is.
- `patterns/borg-strook.php` (slug `ink-foundation/borg-strook`) — homepage sponsor strip; its dynamic rendering is owned by `Ink\Sponsors\HomepageStrip` (Story 14.3). Already referenced in `front-page.html`. Leave wiring untouched.
- `patterns/hero.php` (slug `ink-foundation/hero`, "Held-seksie") and `cta-band.php` are the structural/locking templates to mirror for the new `huidige-uitdaging.php` pattern.

### Page-map (design intent)
- `docs/design-handoff/page-map.csv` — Tuisblad (`front-page`, reference-ready) sections: **Hero; Uitdaging; Wenneraankondiging (featured slot — 15.6); Uitgesoekte werke; Borge; CTA-band.** This story builds Hero (exists) + Uitdaging + Uitgesoekte werke + Borge (exists) + CTA-band. The Wenneraankondiging featured slot is Story 15.6.

### Architecture compliance (project-context.md)
- **Three-layer separation:** `front-page.html` and patterns are presentation only. No tier/challenge/submission/query logic in templates. The challenge teaser must be a static link section, NOT a live "current challenge" query.
- **Block theme / FSE:** patterns are `.php` in `patterns/`; reference them from the template via `<!-- wp:pattern {"slug":"ink-foundation/…"} /-->`.
- **Theme-pattern i18n convention (Gate D):** every user-facing string in `patterns/*.php` goes through a gettext call with the `ink-foundation` text domain. The `composer copy:scan` leak-scan flags bare text nodes — keep the new pattern clean.
- **Design tokens (Gate A):** spacing via `var:preset|spacing|s-*`, colours via palette slugs (`primary`, `secondary`, `accent`, `muted-text`, `surface-alt`), type via named font-size presets. Mirror the exact token usage in `hero.php`/`cta-band.php`.
- **Heading casing:** sentence case ("Hierdie maand se uitdaging", not Title Case).

### Project Structure Notes
- New file: `wp-content/themes/ink-foundation/patterns/huidige-uitdaging.php`.
- Modified file: `wp-content/themes/ink-foundation/templates/front-page.html`.
- No `ink-core` changes expected. No new templateParts/customTemplates entries (front-page is a hierarchy template, already present).
- The `/uitdagings` archive route exists (Epic 12 `archive-uitdaging.html`); link the teaser there.

### Testing standards
- Theme presentation is covered by E2E/visual checks, not unit tests (test pyramid). Do not invent a brittle HTML-string unit test for the template. Keep `composer test:unit` green; run repo-wide `composer cs` (0 errors — this is the 🔴 Epic-15 gate) and `php -l` on the new pattern.

### References
- [Source: _bmad-output/planning-artifacts/epics.md#Story 15.1: Tuisblad] (FR-59)
- [Source: docs/design-handoff/page-map.csv — front-page sections]
- [Source: docs/ui-copy-translations.md — Tuisblad copy lines ~54–70]
- [Source: _bmad-output/project-context.md — three-layer separation, Gate A/D, sentence case]
- [Source: wp-content/themes/ink-foundation/templates/front-page.html — current assembly]
- [Source: wp-content/themes/ink-foundation/patterns/{hero,featured-grid,cta-band,borg-strook}.php]

## Dev Agent Record

### Agent Model Used

claude-opus-4-8 (BMAD dev-story workflow)

### Debug Log References

- `composer test:unit` → 915 passed, 1 skipped (3500 assertions). New `TuisbladTemplateTest`: 3 passed.
- `composer cs` (repo-wide) → 0 errors (pre-existing Epic-7 slow-query WARNINGS only, in `SuggestedReads.php`, untouched). `phpcs` on changed files → exit 0.
- `composer copy:scan` → no new placeholder debt (baseline 6, found 6).
- `php -l huidige-uitdaging.php` → no syntax errors.

### Completion Notes List

- Tuisblad is a presentation-only FSE assembly. `front-page.html` now renders, in order: header → inline hero spotlight → `huidige-uitdaging` (challenge teaser) → `featured-grid` (featured works) → `borg-strook` (sponsors, already wired in 14.3) → `cta-band` → footer — satisfying AC #1.
- The challenge section is a **static entry-point** to `/uitdagings`, carrying no per-challenge query (AC #5, three-layer separation). The dynamic "current challenge" card (month name, live entry counts) remains Epic 12/12A territory; a guard test asserts the teaser embeds no `wp:ink/` server block and no `WP_Query`.
- New pattern copy is human-authored Afrikaans lifted from `docs/ui-copy-translations.md` (never AI-translated): heading "Maandelikse uitdagings", body from the "Vir skrywers" value-prop row. Button "Ontdek uitdagings" composes glossary-authored terms.
- The wenneraankondiging featured slot (page-map "Wenneraankondiging") is deliberately NOT built here — it is Story 15.6, which will insert a featured area on the home page. The insertion point (between hero and the challenge teaser) is left structurally clean.
- No `ink-core` changes; no theme.json changes (front-page is a hierarchy template already present).

### File List

- `wp-content/themes/ink-foundation/patterns/huidige-uitdaging.php` (NEW)
- `wp-content/themes/ink-foundation/templates/front-page.html` (MODIFIED — added challenge/featured/CTA pattern refs)
- `tests/Unit/Org/TuisbladTemplateTest.php` (NEW)
- `_bmad-output/implementation-artifacts/15-1-tuisblad.md` (story record)
- `_bmad-output/implementation-artifacts/sprint-status.yaml` (status tracking)

## Change Log

- 2026-06-28 — Story 15.1 implemented: Tuisblad assembly (challenge teaser pattern + featured/sponsors/CTA bands on front-page.html) + structural guardrail tests. Status → done.

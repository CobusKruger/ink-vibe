---
baseline_commit: bec16aee9012cfe70c2fdd6f481b77f4cc251823
---

# Story 14.4: Sponsor recognition page

Status: done

<!-- R14 code review (epic-14-code-review-2026-06-28.md): 0 HIGH/MEDIUM. No 14.4 patches. Confirmed: all active sponsors shown, copy verbatim from the registry, always-renders/grid-degrades, shared SponsorLink. Deferred: MAX_SPONSORS=100 cap vs "every active sponsor" (documented product bound). -->


<!-- Note: Validation is optional. Run validate-create-story for quality check before dev-story. -->

## Story

As a sponsor,
I want full recognition on Oor INK,
so that supporters are acknowledged. (FR-58)

## Acceptance Criteria

**Given** Oor INK
**When** it renders
**Then** a full sponsor recognition section is provided.

Decomposed:

1. A server-rendered block `ink/borg-erkenning` (new `Sponsors\RecognitionSection`) renders the FULL sponsor recognition section: an eyebrow ("Ons borge"), a section heading ("Moontlik gemaak deur"), a thank-you description, a grid of **all currently active sponsor logos** (linked), and a "Word 'n borg" call-to-action. House-style split: thin `render()` (reads the active sponsors) + pure `toHtml(list<Sponsor>): string` (the markup) — the `InkPols\Archive` / `HomepageStrip` shape.
2. The section shows **all active sponsors** (not the single rotated pick): `render()` reads `Campaign::activeSponsors()` (14.2 — every sponsor within its campaign window) and renders each as a linked logo (name fallback when no logo), so recognition is complete rather than rotated. Ordering is the query order (newest-first); tier ordering is NOT introduced (the controlled `SponsorTier` enum stays deferred — glossary gate, see 14.1).
3. The section renders **always** (it is an evergreen "thank you + become a sponsor" panel on Oor INK), degrading gracefully when there are no active sponsors: the eyebrow, heading, description and CTA still render; only the logo **grid** is omitted (no empty grid chrome). This differs from the 14.3 homepage strip (which fully collapses) — a recognition section with no current sponsors still acknowledges + invites.
4. All section copy comes from the `ink-core` terminology registry (controlled-vocabulary rule, Gate D) — three glossary/ui-copy-authored keys added to `I18n\Terms`: reuse `borge_blad_titel` ("Ons borge", 14.3) for the eyebrow; add `borge_afdeling_titel` ("Moontlik gemaak deur", ui-copy line 104), `borge_beskrywing` (the thank-you sentence, ui-copy line 105), and `word_borg` ("Word 'n borg", ui-copy line 106). These are AUTHORED Afrikaans (not placeholders), so `copy:scan` stays green. No bare literals in the block or pattern.
5. The "Word 'n borg" CTA links to the contact page (`/kontak`, the Epic-15.4 Kontak slug) via a guarded `home_url('/kontak')` (fallback `/kontak`) — a site-relative page link, not a hardcoded asset URL. (Assumption: the become-a-sponsor enquiry routes through Kontak; the link target is trivially adjustable in 15.3/15.4 if the destination differs.)
6. The theme pattern `ink-foundation/borg-erkenning` wraps the block in a token-only section frame (spacing tokens + constrained layout, the existing section-pattern house style: `hero.php`/`cta-band.php`/`featured-grid.php`) and is available in the inserter for the Oor INK page assembly (Story 15.3 "Oor INK (assembly-only)" embeds it alongside mission/contact/org — the same way 14.3's strip is ready for the 15.1 Tuisblad). The pattern carries NO raw copy (the block self-renders all copy from the registry), so `copy:scan` stays green.
7. `Sponsors\Module::register()` registers the recognition block alongside the 14.3 strip. Conflation-clean: the block references only `Ink\Sponsors` (`Campaign`/`Sponsor`) + `Ink\I18n\Terms` + WP core — zero Tiers/Entitlement, no new deptrac edge.

## Tasks / Subtasks

- [x] Task 1: Terms registry keys (AC: 4) — added `borge_afdeling_titel`, `borge_beskrywing`, `word_borg` to `I18n\Terms` (glossary/ui-copy-authored verbatim) + `TermsTest` assertions.
- [x] Task 2: `Sponsors\RecognitionSection` block (AC: 1, 2, 3, 5, 7)
  - [x] Subtask 2.1: `const BLOCK = 'ink/borg-erkenning'`; `register()`/`registerBlock()` (init, `register_block_type` guard) — the `HomepageStrip` shape.
  - [x] Subtask 2.2: thin `render()` → `toHtml( Campaign::activeSponsors() )`.
  - [x] Subtask 2.3: pure `toHtml(list<Sponsor>): string` — eyebrow + heading + description + (grid when non-empty) + CTA; each logo linked via the shared `SponsorLink`, name fallback; full escaping.
  - [x] Subtask 2.4: guarded `contactUrl()` (`home_url('/kontak')` fallback `/kontak`) for the CTA href.
- [x] Task 3: Wire the block into the module (AC: 7) — `Module::register()` also registers `RecognitionSection`.
- [x] Task 4: Theme pattern (AC: 6) — new `patterns/borg-erkenning.php` (token-only wrapper around `<!-- wp:ink/borg-erkenning /-->`, no raw copy), inserter-available section pattern for Oor INK (15.3 embeds).
- [x] Task 5: Tests (AC: 1-5) — `tests/Unit/Sponsors/RecognitionSectionTest.php` (eyebrow/heading/description/CTA render always; grid present with sponsors, absent with none; per-sponsor linked logo + name fallback; permalink fallback; escaping; BLOCK const) + `tests/Unit/I18n/TermsTest.php` additions.
- [x] Task 6: Gates — `composer test:unit`, `composer cs`, `composer stan`, `composer deptrac`, `composer copy:scan` all green; counts in Completion Notes.
- [x] Task 7 (refactor): extracted the shared `Sponsors\SponsorLink` linked-logo/name renderer (link-target decision single-sourced) and refactored 14.3 `HomepageStrip` to use it — removes the duplication a second consumer would create.

## Dev Notes

- **Server-rendered block, three-layer separation — the 14.3 twin.** Mirror `Sponsors\HomepageStrip` (14.3) and `InkPols\Archive` (13.2): an `init`-registered dynamic block with a `render_callback`, a thin `render()` reading data, and a pure `toHtml()` building markup (unit-tested without WP). [Source: src/Sponsors/HomepageStrip.php; src/InkPols/Archive.php:34-72,162-212]
- **Show ALL active sponsors (not rotated).** The recognition section acknowledges everyone currently in-window — read `Campaign::activeSponsors()`, render each. Contrast 14.3 which shows the single `Campaign::featured()` rotated pick. Do NOT rotate or cap here (sponsors are few; `Campaign::queryArgs` already bounds the query). [Source: src/Sponsors/Campaign.php:activeSponsors(); 14.2 story]
- **Renders always, grid degrades.** Unlike the homepage strip's full collapse, the recognition panel always shows the eyebrow/heading/description/CTA (an evergreen thank-you + invitation); only the logo grid is omitted when `activeSponsors()` is empty. Non-vacuous test: prove the grid appears WITH sponsors and is absent WITHOUT, while the copy persists. [Source: this story AC-3]
- **Copy from the registry (Gate D / controlled-vocabulary).** All four strings are glossary/ui-copy-authored Afrikaans (`SponsorsSection.tsx`): eyebrow "Ons borge" (reuse `borge_blad_titel`), H2 "Moontlik gemaak deur", the thank-you description, button "Word 'n borg". Add the three new keys to `I18n\Terms` and reference via `Terms::label()` — never a bare literal (the InkPols/Challenges precedent). Authored copy → no `copy:scan` placeholder debt. [Source: docs/ui-copy-translations.md:99-106; src/I18n/Terms.php:167-170 (borge keys); project-context "Controlled-vocabulary UI labels come from the ink-core terminology registry"]
- **Exact authored strings** (copy verbatim from ui-copy-translations.md — never re-translate, Afrikaans is the source of truth):
  - `borge_afdeling_titel` = `Moontlik gemaak deur`
  - `borge_beskrywing` = `As 'n niewinsgerigte organisasie steun ons op die gulhartigheid van ons borge om hierdie gemeenskap te laat floreer. Dankie dat jy in die krag van woorde glo.`
  - `word_borg` = `Word 'n borg`
  [Source: docs/ui-copy-translations.md:104-106]
- **Logo + link from the 14.1 VO, the 14.3 link rules.** Each grid item: `Sponsor::logoUrl('medium')` (name fallback when ''), linked to the external `link` (`target=_blank rel="noopener sponsored"`) else the permalink else no anchor — reuse the exact pattern from `HomepageStrip::toHtml`. Consider extracting the shared "linked logo/name" helper if it reduces duplication cleanly; otherwise mirror it. [Source: src/Sponsors/HomepageStrip.php:toHtml(); src/Sponsors/Sponsor.php]
- **CTA target.** "Word 'n borg" → the contact page via guarded `home_url('/kontak')` (fallback `/kontak`). Not a hardcoded asset URL — a site-relative page link built through the WP helper. The Kontak page is Epic 15.4; the link is trivially retargetable if the destination differs. [Source: epics.md#Story 15.4 (Kontak)]
- **Section pattern, inserter-available (the Oor INK seam).** `ink-foundation/borg-erkenning` is a token-only section pattern (no copy), like `hero.php`/`cta-band.php`/`featured-grid.php` — available for the Oor INK page assembly (15.3), which embeds it alongside mission/contact/org. 14.4 does NOT create the Oor INK template (that is 15.3's "assembly-only" scope); it delivers the complete, ready section. [Source: wp-content/themes/ink-foundation/patterns/hero.php, cta-band.php, featured-grid.php; epics.md#Story 15.3]
- **Conflation rule + deptrac:** recognition is editorial — NO tier/entitlement. No new deptrac edge (`I18n` is uncovered, not a layer — the 14.3 precedent; `Sponsors -> Content`/`Kernel` from 14.1/14.2 suffice). [Source: deptrac.yaml (Sponsors); project-context "THE conflation rule"]
- **Testing rules (standing):** test the INK-owned OUTPUT (the markup, the grid present/absent), not a `register_block_type`/WP_Query mock; non-vacuous grid + copy-persists assertions; pass-through escaper stubs to assert markup; stub `home_url`/attachment resolvers (the 14.3 precedent). [Source: project-context "Testing Rules"; tests/Unit/Sponsors/HomepageStripTest.php]

### Project Structure Notes

- New: `wp-content/plugins/ink-core/src/Sponsors/RecognitionSection.php`, `wp-content/themes/ink-foundation/patterns/borg-erkenning.php`, `tests/Unit/Sponsors/RecognitionSectionTest.php`.
- Modified: `wp-content/plugins/ink-core/src/Sponsors/Module.php` (register the recognition block), `wp-content/plugins/ink-core/src/I18n/Terms.php` (three keys), `tests/Unit/I18n/TermsTest.php` (assert the new keys).
- No new CPT, no new meta, no new deptrac edge. New copy is glossary/ui-copy-authored (no placeholder debt). The Oor INK page template/assembly is Story 15.3.

### References

- [Source: _bmad-output/planning-artifacts/epics.md#Story 14.4]
- [Source: _bmad-output/planning-artifacts/prds/prd-ink-vibe-2026-06-14/prd.md#FR-58]
- [Source: docs/ui-copy-translations.md:99-106] — the authored SponsorsSection copy (verbatim)
- [Source: wp-content/plugins/ink-core/src/Sponsors/HomepageStrip.php] — the 14.3 block twin (toHtml + linked-logo pattern)
- [Source: wp-content/plugins/ink-core/src/Sponsors/Campaign.php] — activeSponsors() (all in-window sponsors)
- [Source: wp-content/themes/ink-foundation/patterns/cta-band.php, featured-grid.php] — section-pattern house style

## Dev Agent Record

### Agent Model Used

claude-opus-4-8

### Debug Log References

### Completion Notes List

- Built the `ink/borg-erkenning` server block (`Sponsors\RecognitionSection`) — the full Oor INK sponsor recognition section, the `HomepageStrip`/`InkPols\Archive` house style (init-registered dynamic block, thin `render()` reading `Campaign::activeSponsors()`, pure `toHtml()`).
- **Shows ALL active sponsors** (the recognition contract), not the single rotated pick — reads `Campaign::activeSponsors()` and renders each as a linked logo (grid). Newest-first order; tier ordering NOT introduced (controlled `SponsorTier` enum still deferred — 14.1 glossary gate).
- **Renders always, grid degrades:** the eyebrow ("Ons borge"), heading ("Moontlik gemaak deur"), thank-you description and "Word 'n borg" CTA always render (the evergreen acknowledge + invite); only the logo grid is omitted when there are no active sponsors. Non-vacuously tested (grid present WITH sponsors, absent WITHOUT, copy persists either way) — deliberately different from the 14.3 strip's full collapse.
- **All copy from the registry (Gate D):** three new glossary/ui-copy-authored keys (`borge_afdeling_titel`, `borge_beskrywing`, `word_borg`) added to `I18n\Terms`, reusing `borge_blad_titel` for the eyebrow. Afrikaans copied VERBATIM from `docs/ui-copy-translations.md` (source of truth, never re-translated). No bare literals; authored copy → no `copy:scan` debt.
- **Shared `SponsorLink` extracted (quality):** the linked-logo/name + link-target decision (external `link` → permalink → no anchor, name fallback, full escaping) is now one helper consumed by BOTH the 14.3 strip and the 14.4 grid — `HomepageStrip` was refactored onto it (its 6 tests stay green, markup identical). Pre-empts the duplication a second consumer creates; each surface keeps its own BEM class namespace via params.
- **CTA → contact page** via guarded `home_url('/kontak')` (fallback `/kontak`) — a site-relative page link (not a hardcoded asset URL); targets the Epic-15.4 Kontak page (trivially retargetable). The Oor INK page template/assembly is Story 15.3; 14.4 delivers the complete section as an inserter-available token-only pattern (`ink-foundation/borg-erkenning`), the `hero.php`/`cta-band.php` section-pattern house style.
- Conflation-clean: references only `Ink\Sponsors` + `Ink\I18n\Terms` + WP core; no new deptrac edge (`I18n` uncovered). `Module::register()` now registers both the strip + recognition blocks.
- **Gates:** `composer test:unit` → 910 passed / 1 skipped (+7: 6 RecognitionSection, 1 Terms; the 6 HomepageStrip tests still green post-refactor), zero regressions; `composer cs` → 0 errors on the changed files (repo-wide: only the 2 documented pre-existing slow-query WARNINGS); `composer stan` → No errors; `composer deptrac` → 3 = the documented PRE-EXISTING baseline, **no new edge**; `composer copy:scan` → no new placeholder debt.

### File List

- `wp-content/plugins/ink-core/src/Sponsors/RecognitionSection.php` (new)
- `wp-content/plugins/ink-core/src/Sponsors/SponsorLink.php` (new — shared linked-logo renderer)
- `wp-content/plugins/ink-core/src/Sponsors/HomepageStrip.php` (modified — refactored onto SponsorLink)
- `wp-content/plugins/ink-core/src/Sponsors/Module.php` (modified — register the recognition block)
- `wp-content/plugins/ink-core/src/I18n/Terms.php` (modified — borge_afdeling_titel/borge_beskrywing/word_borg)
- `wp-content/themes/ink-foundation/patterns/borg-erkenning.php` (new — token-only section pattern)
- `tests/Unit/Sponsors/RecognitionSectionTest.php` (new)
- `tests/Unit/I18n/TermsTest.php` (modified — assert the new keys)

## Change Log

- 2026-06-28: Story 14.4 implemented — the `ink/borg-erkenning` Oor INK recognition section (all active sponsors, always-rendered copy + degrading grid, registry copy) + theme pattern; extracted the shared `SponsorLink` helper and refactored the 14.3 strip onto it. Status → review.

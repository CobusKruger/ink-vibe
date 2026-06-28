---
baseline_commit: ec0ce5dadd05901defa1a28a1adba708a0b8d765
---

# Story 14.3: Homepage sponsor placement

Status: review

<!-- Note: Validation is optional. Run validate-create-story for quality check before dev-story. -->

## Story

As a site owner,
I want a subtle sponsor strip,
so that sponsors are recognised without clutter. (FR-58)

## Acceptance Criteria

**Given** the homepage
**When** it renders
**Then** one featured or rotating sponsor shows in a subtle strip; **with no active sponsor the strip collapses gracefully**; with multiple active it rotates; no logo dumps on content pages.

Decomposed:

1. A server-rendered block `ink/borg-strook` (new `Sponsors\HomepageStrip`) renders the homepage sponsor strip. All business logic stays in `ink-core` (three-layer separation): the block calls `Campaign::featured()` (14.2) to get the single daily-rotated active sponsor and renders it. House-style split: thin `render()` (reads the featured sponsor) + pure `toHtml(?Sponsor): string` (the markup) — the same shape as `InkPols\Archive`.
2. **Collapses gracefully** — when `Campaign::featured()` returns `null` (no active sponsor), `toHtml(null)` returns `''` (the block renders nothing; no empty heading, no orphan strip chrome). This is the AC's load-bearing invariant.
3. **Rotates with multiple active** — the block consumes `Campaign::featured()`, which already returns the daily-rotated pick from the active set (14.2). With one active sponsor it shows that one; with several it shows today's rotation slot. No rotation logic is re-implemented in the block.
4. The strip is **subtle + token-only** (Gate A): a small eyebrow label + the sponsor logo (linked). The eyebrow reads **"Ons borge"** from the `ink-core` terminology registry — added as a glossary-backed key `borge_blad_titel` (glossary line 171 "Die borgskapsblad → Ons borge"; ui-copy `SponsorsSection` line 103 "Our Supporters → Ons borge"), the SINGLE source shared with the 14.4 recognition section. The logo is `Sponsor::logoUrl('medium')`; when a sponsor has no logo the strip falls back to the sponsor **name** as the link text (never a broken `<img>`). The link target is the sponsor's external `link` when set (`rel="noopener sponsored"`, `target="_blank"`), else the sponsor's own permalink, else no anchor (logo/name only). Every value escaped at output (`esc_url`/`esc_attr`/`esc_html`).
5. **No logo dumps on content pages** — the strip ships as ONE block embedded in ONE theme pattern (`ink-foundation/borg-strook`), placed only on the homepage template (`front-page.html`). It is NOT auto-injected via `the_content`/a global hook, so single `gedig`/`storie`/`artikel`/library/etc. templates never carry it. The invariant holds by construction (no content-filter hook anywhere in Sponsors). A code comment + test asserts the block is not registered on any content hook.
6. The theme pattern `ink-foundation/borg-strook` wraps the block in a subtle, token-only frame (spacing tokens + a muted surface) and is embedded in `front-page.html` so the homepage renders the strip. The pattern carries NO raw user-facing copy (the eyebrow comes from the block via the registry) — `copy:scan` stays green. (Story 15.1 Tuisblad may re-position the strip within the full homepage assembly; 14.3 delivers the reusable block + pattern + a working homepage placement.)
7. `Sponsors\Module::register()` now registers the strip block (the "render hooks land in 14.3" promised at 14.1/14.2). Conflation-clean: the block references only `Ink\Sponsors` (`Campaign`/`Sponsor`) + `Ink\I18n\Terms` + WP core — zero Tiers/Entitlement, no new deptrac edge beyond the existing `Sponsors -> Content`/`Sponsors -> Kernel` (Terms is `Ink\I18n`; check whether a `Sponsors -> I18n` edge already exists from precedent and add it if deptrac flags it — InkPols/Challenges already depend on `I18n\Terms`).

## Tasks / Subtasks

- [x] Task 1: Terms registry key (AC: 4) — added `borge_blad_titel => __( 'Ons borge', 'ink-core' )` to `I18n\Terms` (glossary-backed; shared with 14.4) + a `TermsTest` assertion.
- [x] Task 2: `Sponsors\HomepageStrip` block (AC: 1, 2, 3, 4, 5, 7)
  - [x] Subtask 2.1: `const BLOCK = 'ink/borg-strook'`; `register()` → `add_action('init', registerBlock)`; `registerBlock()` guards `function_exists('register_block_type')`.
  - [x] Subtask 2.2: thin `render()` → `toHtml( Campaign::featured() )`.
  - [x] Subtask 2.3: pure `toHtml(?Sponsor): string` — `''` when null; else eyebrow (registry) + linked logo/name; external-link vs permalink vs no-anchor; full escaping.
- [x] Task 3: deptrac (AC: 7) — confirmed NO `Sponsors -> I18n` edge needed: `Ink\I18n` is not a deptrac layer (uncovered), so consuming `I18n\Terms` is allowed without an edge (the InkPols/Challenges precedent). No Tiers/Entitlement edge; deptrac unchanged (3 pre-existing baseline).
- [x] Task 4: Wire the block into the module (AC: 7) — `Module::register()` calls `( new HomepageStrip() )->register()`.
- [x] Task 5: Theme pattern + homepage embed (AC: 5, 6) — new `patterns/borg-strook.php` (subtle token-only wrapper [`surface-alt` bg + spacing tokens] around `<!-- wp:ink/borg-strook /-->`, no raw copy); embedded `<!-- wp:pattern {"slug":"ink-foundation/borg-strook"} /-->` in `front-page.html` before the footer.
- [x] Task 6: Tests (AC: 1-5) — `tests/Unit/Sponsors/HomepageStripTest.php` (toHtml renders eyebrow + linked logo, escaping; toHtml(null) === '' [collapse]; external link vs permalink fallback vs no-anchor; logo-absent falls back to name; BLOCK const).
- [x] Task 7: Gates — `composer test:unit`, `composer cs`, `composer stan`, `composer deptrac`, `composer copy:scan` all green; counts in Completion Notes.

## Dev Notes

- **Server-rendered block, three-layer separation.** Mirror `InkPols\Archive` (13.2) exactly: a dynamic block registered on `init` with a `render_callback`, a thin `render()` that reads data, and a pure `toHtml()` that builds the markup (unit-tested without WP). Business logic (which sponsor, rotation) stays in `ink-core` (`Campaign`); the theme only embeds the block. [Source: src/InkPols/Archive.php:34-72,162-212; src/Content/... ; project-context "Block theme, not classic" + "No business logic in the theme"]
- **Consume `Campaign::featured()` — do not re-roll rotation.** 14.2 already returns the single daily-rotated active sponsor (or null). The strip is a pure presenter of that one value: one active → that sponsor; many active → today's slot; none → null → collapse. [Source: src/Sponsors/Campaign.php:featured(); 14.2 story]
- **Collapse = return ''.** The empty-state contract is `toHtml(null) === ''` (no chrome at all), NOT an "empty strip" with a heading. This is different from `InkPols\Archive` (which renders a "geen uitgawes" line) because a homepage sponsor strip with no sponsor must vanish, not announce its emptiness. Unit-test this explicitly (non-vacuous: prove a sponsor DOES render, then prove null renders nothing). [Source: this story AC-2]
- **Eyebrow label from the registry (Gate D / controlled-vocabulary rule).** "Ons borge" is a glossary label (afrikaans-terms line 171, ui-copy line 103) — it MUST come from `Terms::label('borge_blad_titel')`, never a bare literal in the block or pattern. Add the key to `I18n\Terms` (it is shared with the 14.4 recognition-section title). The pattern file carries NO copy, so `copy:scan` (which flags bare text in `patterns/*.php`) stays green. [Source: docs/afrikaans-terms.md:171; docs/ui-copy-translations.md:99-106; src/I18n/Terms.php:167-168; project-context "Controlled-vocabulary UI labels come from the ink-core terminology registry"]
- **No logo dumps on content pages (the AC's anti-clutter rule).** Achieve it by NOT hooking the strip into `the_content` or any global render — it is a block embedded only in the homepage pattern. Single content templates (`single-*.html`) do not include it. Keep Sponsors free of any `add_filter('the_content', …)`. [Source: this story AC-5; epics.md#Story 14.3 "no logo dumps on content pages"]
- **Sponsor link + logo from the 14.1 VO.** `Sponsor::logoUrl('medium')` (featured image, '' when none) and `Sponsor->link` (the `esc_url_raw`-sanitised outbound URL) + `Sponsor->name` (title). External link → `target="_blank" rel="noopener sponsored"`; no external link → permalink (`get_permalink`, guarded) ; neither → render the logo/name without an anchor. Escape every value. [Source: src/Sponsors/Sponsor.php:logoUrl(),link,name]
- **Subtle + token-only (Gate A).** No hardcoded colours/spacing/type — the pattern wrapper uses `var:preset|spacing|*` + a token colour (e.g. `surface-alt`/`muted-text`), mirroring the front-page hero's block attributes. The block emits semantic classes (`ink-borg-strook__*`) but ships no custom CSS (the InkPols precedent — `style.css` is the theme header only). [Source: wp-content/themes/ink-foundation/templates/front-page.html:7-9; wp-content/themes/ink-foundation/patterns/inkpols.php:22-23; wp-content/themes/ink-foundation/style.css]
- **Theme i18n convention.** If the pattern needs any visible label (it should not — the block self-renders the eyebrow), route it through `ink_foundation_term()` (the bridge), never a raw literal — but the cleanest design keeps ALL copy in the block via the registry, so the pattern is structural-only and `copy:scan`-exempt. [Source: wp-content/themes/ink-foundation/patterns/inkpols.php:18-20; project-context "Theme-pattern i18n convention"]
- **Testing rules (standing):** test the INK-owned OUTPUT (the markup the block emits, the collapse), not a WP_Query/`register_block_type` mock. Guardrail (collapse + no-content-injection) tests must be non-vacuous. Brain-Monkey: stub `get_permalink`/attachment resolvers via the function stubs (the 13.2/14.1 precedent). [Source: project-context "Testing Rules"]

### Project Structure Notes

- New: `wp-content/plugins/ink-core/src/Sponsors/HomepageStrip.php`, `wp-content/themes/ink-foundation/patterns/borg-strook.php`, `tests/Unit/Sponsors/HomepageStripTest.php`.
- Modified: `wp-content/plugins/ink-core/src/Sponsors/Module.php` (register the strip block), `wp-content/plugins/ink-core/src/I18n/Terms.php` (`borge_blad_titel`), `wp-content/themes/ink-foundation/templates/front-page.html` (embed the pattern), `tests/Unit/I18n/TermsTest.php` (assert the new key), possibly `deptrac.yaml` (Sponsors -> I18n if flagged).
- No new CPT, no new meta. The new copy ("Ons borge") is glossary-backed (authored, not a placeholder) — adds NO copy:scan debt.

### References

- [Source: _bmad-output/planning-artifacts/epics.md#Story 14.3]
- [Source: _bmad-output/planning-artifacts/prds/prd-ink-vibe-2026-06-14/prd.md#FR-58]
- [Source: wp-content/plugins/ink-core/src/InkPols/Archive.php] — server-rendered block house style (register/render/toHtml split)
- [Source: wp-content/themes/ink-foundation/patterns/inkpols.php] — theme pattern embedding an ink-core block
- [Source: wp-content/themes/ink-foundation/templates/front-page.html] — the homepage template to embed into
- [Source: wp-content/plugins/ink-core/src/Sponsors/Campaign.php] — featured() (the rotated active sponsor)
- [Source: docs/afrikaans-terms.md:166-172; docs/ui-copy-translations.md:99-106] — authored "Ons borge" copy

## Dev Agent Record

### Agent Model Used

claude-opus-4-8

### Debug Log References

### Completion Notes List

- Built the `ink/borg-strook` server block (`Sponsors\HomepageStrip`) — the subtle homepage sponsor strip, the `InkPols\Archive` house style (init-registered dynamic block, thin `render()` reading `Campaign::featured()`, pure `toHtml()`). All business logic (which sponsor + rotation) stays in `ink-core`; the theme only embeds the block.
- **Collapse contract:** `toHtml(null) === ''` — with no active sponsor the block renders nothing (no orphan chrome), the AC's load-bearing invariant; non-vacuously tested (a sponsor DOES render; null renders nothing).
- **Rotation** is consumed, not re-rolled: the block presents `Campaign::featured()` (the 14.2 daily-rotated active pick) — one active shows that one, several show today's slot.
- **Subtle + token-only:** the eyebrow "Ons borge" comes from the `Terms` registry (new glossary-backed `borge_blad_titel`, shared with 14.4 — never a bare literal); the strip links the logo to the external sponsor `link` (`target=_blank rel="noopener sponsored"`), else the sponsor permalink, else no anchor; falls back to the sponsor NAME when there is no logo (never a broken `<img>`). Every value escaped at output. The pattern wrapper is token-only (`surface-alt` bg + spacing presets, the front-page hero precedent) and carries NO copy.
- **No logo dumps on content pages:** the strip is one block in one homepage pattern (`ink-foundation/borg-strook`, embedded in `front-page.html`) — NOT hooked into `the_content` or any global render, so single content templates never carry it (the invariant holds by construction; Sponsors registers no content filter).
- `Module::register()` now registers the strip block (the render hook promised at 14.1/14.2). Conflation-clean: references only `Ink\Sponsors` + `Ink\I18n\Terms` + WP core. No new deptrac edge (`I18n` is uncovered, not a layer — the InkPols/Challenges precedent).
- **Gates:** `composer test:unit` → 903 passed / 1 skipped (+7: 6 HomepageStrip, 1 Terms), zero regressions; `composer cs` → 0 errors on the changed files (repo-wide: only the 2 documented pre-existing slow-query WARNINGS); `composer stan` → No errors; `composer deptrac` → 3 violations = the documented PRE-EXISTING baseline, **no new edge**; `composer copy:scan` → no new placeholder debt (the pattern is structural-only; "Ons borge" is glossary-authored, not a placeholder).

### File List

- `wp-content/plugins/ink-core/src/Sponsors/HomepageStrip.php` (new)
- `wp-content/plugins/ink-core/src/Sponsors/Module.php` (modified — register the strip block)
- `wp-content/plugins/ink-core/src/I18n/Terms.php` (modified — `borge_blad_titel` glossary key)
- `wp-content/themes/ink-foundation/patterns/borg-strook.php` (new — subtle token-only pattern)
- `wp-content/themes/ink-foundation/templates/front-page.html` (modified — embed the strip pattern)
- `tests/Unit/Sponsors/HomepageStripTest.php` (new)
- `tests/Unit/I18n/TermsTest.php` (modified — assert `borge_blad_titel`)

## Change Log

- 2026-06-28: Story 14.3 implemented — the `ink/borg-strook` homepage sponsor strip server block (collapses when none, daily-rotated, token-only) + theme pattern embedded in `front-page.html`; "Ons borge" added to the Terms registry. Status → review.

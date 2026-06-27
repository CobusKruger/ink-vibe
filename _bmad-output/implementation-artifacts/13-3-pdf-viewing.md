---
baseline_commit: 00bbe12
---

# Story 13.3: PDF viewing

Status: review

<!-- Note: Validation is optional. Run validate-create-story for quality check before dev-story. -->

## Story

As a reader,
I want to read issues as a flipbook,
so that I can browse the PDF in-page. (FR-57)

## Acceptance Criteria

**Given** Real3D Flipbook (reactivated)
**When** an issue opens
**Then** the PDF renders as a flipbook (no individual-article extraction)
**And** viewer controls are Afrikaans via the plugin's JS translations (accepted exception to NFR-3/NFR-5).

Decomposed:

1. A server-rendered `ink/inkpols-leser` block renders, on a single `inkpols_uitgawe`, the issue PDF as a Real3D Flipbook — using the issue's `pdfUrl()` from the 13.1 read-model (the whole PDF; **no individual-article extraction**). It is wired into the `reading-inkpols.php` single-issue pattern (after the besonderhede metadata, before `post-content`).
2. **Hook, don't reimplement** (commodity-plugin rule): the flipbook is produced by delegating to Real3D Flipbook's own shortcode (the `SHORTCODE_TAG` constant), behind a `shortcode_exists()` guard — INK never reimplements a PDF viewer. The PDF URL is passed via the documented `pdf` attribute.
3. **Graceful degradation + a11y fallback:** when the flipbook plugin is inactive (shortcode absent), the block renders a direct-PDF link — `Lees die uitgawe` (glossary button text) opening `pdfUrl()` — so the issue is always reachable even without the premium plugin (the FR-57 / validation-L-3 mobile/a11y fallback). With **no** PDF on the issue (`hasPdf()` false), the block renders nothing (no broken viewer, no dead link).
4. Afrikaans viewer controls (the accepted NFR-3/NFR-5 exception): Real3D Flipbook's controls are plugin JavaScript, so they are localised via the plugin's **JS translations** (the `wp_set_script_translations` / plugin `.json` lang mechanism), authored on staging and committed under `wp-content/languages/` — **not** an ink-core `.mo`. Story 13.3 records this as a translation-workflow obligation (the plugin is assembled at build time, not in-repo) and adds the guarded script-translation wiring where the plugin's script handle is known; the actual `.json` authoring is the standing translation workflow (Epic 17).
5. Terminology: add `inkpols_lees_uitgawe` = `Lees die uitgawe` (glossary line 143, human-approved) to the Terms registry — the fallback link text, single-sourced (no bare literal, no AI Afrikaans, no copy debt).
6. Conflation-clean: the block reads only `Ink\InkPols` (the read-model/facade) + the `Terms` registry + WP core (`do_shortcode`/`shortcode_exists`/`esc_*`) — **zero** `Ink\Tiers`/`Ink\Entitlement` (reading a published issue's PDF is open, never gated). No new deptrac edge beyond the existing `InkPols -> Content`/`-> Kernel`.

## Tasks / Subtasks

- [x] Task 1: Terminology (AC: 5) — add `'inkpols_lees_uitgawe' => __( 'Lees die uitgawe', 'ink-core' )` to `I18n\Terms::map()`; `TermsTest` stays green.
- [x] Task 2: `InkPols\Viewer` server block (AC: 1, 2, 3, 6)
  - [x] Subtask 2.1: `register()`/`registerBlock()` `ink/inkpols-leser` (function_exists-guarded), mirroring `SingleIssue`.
  - [x] Subtask 2.2: `SHORTCODE_TAG` constant (`real3dflipbook`) + pure `shortcodeFor(string $pdfUrl): string` building `[real3dflipbook pdf="…"]` (URL escaped).
  - [x] Subtask 2.3: Pure `embedHtml(string $pdfUrl, bool $flipbookAvailable, string $shortcodeOutput): string` — flipbook wrapper when available, else the `Lees die uitgawe` direct-PDF fallback link; '' when `$pdfUrl` is empty.
  - [x] Subtask 2.4: Thin `render()` — resolve current issue via `Api::issueFor`, type-guard, `hasPdf()` guard, branch on `shortcode_exists(SHORTCODE_TAG)` (running `do_shortcode` only when present), compose.
  - [x] Subtask 2.5: Guarded script-translation wiring (AC: 4) — a `registerScriptTranslations()` no-op-safe hook that calls `wp_set_script_translations()` for the flipbook handle ONLY when both the function and the plugin handle exist (records the obligation; safe when the plugin is absent).
- [x] Task 3: Module wiring (AC: 1) — `InkPols\Module::register()` registers `Viewer` alongside `Archive`/`SingleIssue`.
- [x] Task 4: Theme (AC: 1) — embed `<!-- wp:ink/inkpols-leser /-->` in `patterns/reading-inkpols.php` (own section, after besonderhede, before `post-content`).
- [x] Task 5: Tests — `tests/Unit/InkPols/ViewerTest.php` (shortcodeFor builds the pdf-attr shortcode, URL escaped; embedHtml flipbook-available wraps the shortcode output; embedHtml fallback renders the `Lees die uitgawe` link to the PDF when unavailable; embedHtml '' for empty url) + extend `InkPolsTemplateTest` (reading pattern embeds `wp:ink/inkpols-leser`).
- [x] Task 6: Gates — `composer test:unit`, `composer cs`, `composer stan`, `composer deptrac`, `composer copy:scan` all green; record counts in Completion Notes.

## Dev Notes

- **Real3D Flipbook is a commodity plugin — never reimplement it.** It is assembled at build time (git-ignored), so integrate via its documented shortcode behind a `shortcode_exists()` guard and degrade gracefully when absent — the same `function_exists`/protected-seam pattern used for WooCommerce/BuddyPress throughout ink-core. [Source: project-context.md "Hook, don't edit"; "Platform plugins (commodity capabilities — do not reimplement)"]
- **Shortcode shape:** Real3D Flipbook renders a direct PDF via `[real3dflipbook pdf="URL"]`. Keep the tag in a `SHORTCODE_TAG` constant so the integration point is single-sourced and easy to reconcile against the installed plugin version (a deferred verification item — the plugin is not in-repo). [Source: epics.md#Story 13.3]
- **PDF URL comes from the 13.1 read-model** — `Issue::pdfUrl()` / `hasPdf()`. Never re-resolve the attachment in this block. [Source: src/InkPols/Issue.php]
- **a11y / mobile fallback (validation L-3):** the flipbook is a known, accepted exception to NFR-3 (light front-end JS) and NFR-5 (legibility/a11y). The graceful direct-PDF link (`Lees die uitgawe`) is the minimum fallback the adversarial review recommended; the always-on accessible delivery is deferred pending resources (FR-57 note). [Source: prd.md#FR-57 (lines 531-532); validation-report.md L-3]
- **Afrikaans controls = plugin JS translations, not `.mo`.** The viewer chrome is the plugin's JavaScript; it is localised via the plugin's JS `.json` translations (authored on staging, committed to `wp-content/languages/`), per the i18n-leak-vectors rule. ink-core cannot author another plugin's strings here — 13.3 adds the guarded `wp_set_script_translations` wiring and records the `.json` authoring as the standing translation-workflow obligation (Epic 17). [Source: project-context.md "Real3D Flipbook viewer controls are plugin JS"; "transactional emails / plugin JavaScript strings"]
- **Glossary:** `lees die uitgawe` is the human-approved PDF button text (afrikaans-terms.md line 143) — add it to the Terms registry, do not inline. [Source: docs/afrikaans-terms.md:143]
- **House style:** mirror `SingleIssue` — `register()` → `registerBlock()` → pure `embedHtml()` + thin `render()`. Keep `do_shortcode` out of the pure layer (the render branches and passes the already-expanded output into `embedHtml`). [Source: src/InkPols/SingleIssue.php]
- **Conflation rule:** no tier/entitlement read — reading a published issue's PDF is open. [Source: project-context.md "THE conflation rule"]

### Project Structure Notes

- New: `src/InkPols/Viewer.php`, `tests/Unit/InkPols/ViewerTest.php`.
- Modified: `src/InkPols/Module.php` (register Viewer), `src/I18n/Terms.php` (+1 key), `patterns/reading-inkpols.php` (embed the leser block), `tests/Unit/InkPols/InkPolsTemplateTest.php` (assert the embed).
- No deptrac change (InkPols edges already cover the reads). No new CPT/meta.

### References

- [Source: _bmad-output/planning-artifacts/epics.md#Story 13.3]
- [Source: _bmad-output/planning-artifacts/prds/prd-ink-vibe-2026-06-14/prd.md#FR-57]
- [Source: _bmad-output/planning-artifacts/prds/prd-ink-vibe-2026-06-14/validation-report.md] — L-3 flipbook a11y fallback
- [Source: wp-content/plugins/ink-core/src/InkPols/SingleIssue.php] — single-page block house style

## Dev Agent Record

### Agent Model Used

claude-opus-4-8

### Debug Log References

### Completion Notes List

- Built `InkPols\Viewer` (`ink/inkpols-leser`): renders the issue PDF as a Real3D Flipbook by delegating to the plugin's own shortcode (`SHORTCODE_TAG = real3dflipbook`, `pdf` attribute) behind a `shortcode_exists()` guard — INK never reimplements the viewer (commodity-plugin rule). The whole PDF, no per-article extraction.
- **Graceful degradation + a11y fallback:** when the plugin is inactive the block renders a direct-PDF `Lees die uitgawe` link (the FR-57 / validation-L-3 fallback); with no PDF (`hasPdf()` false) it renders nothing. Pure `embedHtml()` keeps `do_shortcode` out of the testable layer (render branches and passes the expanded output in).
- **Afrikaans controls (accepted NFR-3/NFR-5 exception):** the viewer chrome is plugin JS → localised via the plugin's JS translations. Added the no-op-safe `registerScriptTranslations()` wiring (`wp_set_script_translations` only when the plugin's script handle is registered) to record the obligation in code; the actual `.json` authoring is the standing translation workflow (Epic 17, recorded for the retro).
- Single source: added `inkpols_lees_uitgawe` = `Lees die uitgawe` to `I18n\Terms` (glossary line 143, human-approved — no AI Afrikaans, no copy debt). Wired the leser block into `reading-inkpols.php` (own section after besonderhede, before `post-content`).
- Conflation-clean: reads only `Ink\InkPols` + `Terms` + WP core — zero Tiers/Entitlement, no new deptrac edge.
- **Gates:** `composer test:unit` → 849 passed / 1 skipped (+4, zero regressions); `composer cs` → clean on new files; `composer stan` → No errors; `composer deptrac` → 3 = the documented pre-existing `Kernel\Activation -> Content` baseline, no new edge; `composer copy:scan` → no new placeholder debt.
- **Deferred (for the Epic-13 review/retro):** (1) reconcile `SHORTCODE_TAG`/`SCRIPT_HANDLE` against the actually-installed Real3D Flipbook version (the plugin is assembled at build time, not in-repo); (2) author + commit the flipbook JS `.json` Afrikaans translations on staging (Epic 17 translation workflow).

### File List

- `wp-content/plugins/ink-core/src/InkPols/Viewer.php` (new)
- `wp-content/plugins/ink-core/src/InkPols/Module.php` (modified — register Viewer)
- `wp-content/plugins/ink-core/src/I18n/Terms.php` (modified — +1 `inkpols_lees_uitgawe` key)
- `wp-content/themes/ink-foundation/patterns/reading-inkpols.php` (modified — embed the leser block)
- `tests/Unit/InkPols/ViewerTest.php` (new)
- `tests/Unit/InkPols/InkPolsTemplateTest.php` (modified — assert the leser embed)

### Change Log

- 2026-06-28: Story 13.3 implemented — InkPols PDF flipbook viewer (`ink/inkpols-leser`) via Real3D Flipbook shortcode delegation + direct-PDF a11y fallback + guarded JS-translation wiring. Status → review.

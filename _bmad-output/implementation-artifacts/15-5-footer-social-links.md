---
baseline_commit: d66ed1d
---

# Story 15.5: Footer / social links

Status: done

## Story

As a besoeker,
I want a footer with social links,
so that I can navigate and find INK elsewhere. (FR-62)

## Acceptance Criteria

1. **Given** the footer **When** it renders **Then** a theme-native footer/social-links pattern shows (replacing the legacy social-icons plugin).
2. The social links use the WordPress core `social-links` / `social-link` blocks (theme-native) — NOT the retired "Ultimate Social Media Icons" plugin (spec §retired-plugins) and no third-party social-icon plugin.
3. The links sit inside the existing `footer-main` pattern (the footer template part `template-parts/footer.html` → `ink-foundation/footer-main`), styled with `theme.json` tokens.
4. The platform URLs are clearly-marked placeholders (org-detail values, like `[stigtingsjaar]`) for the editor/owner to set the real handles pre-launch; the section heading is Afrikaans ("Volg ons").
5. No business logic in the theme; tokens only; sentence-case.

## Tasks / Subtasks

- [x] Task 1: Add the social-links section to `patterns/footer-main.php` (AC: #1–#5)
  - [x] A "Volg ons" heading + a core `wp:social-links` (`is-style-logos-only`) block with `wp:social-link` children for Facebook, Instagram and X, placeholder `#` URLs (org-detail value the owner sets pre-launch).
  - [x] Placed within the footer group above the copyright row; tokens only; footer structure intact.
- [x] Task 2: Glossary (AC: #4)
  - [x] Added a "Webwerf-chroom (voettekst)" subsection to `docs/afrikaans-terms.md` with "voettekst" + "Sosiale skakels" (heading "Volg ons", core block not a plugin) — glossary-first.
- [x] Task 3: Tests (AC: #1, #2)
  - [x] Added `tests/Unit/Org/FooterSocialLinksTest.php` (3 tests, 13 assertions): footer part embeds footer-main; footer-main carries the core `wp:social-links` + a `wp:social-link` per platform (non-vacuous); and a guard that NO legacy social-icon plugin handle/shortcode remains.
- [x] Task 4: Gates
  - [x] `composer test:unit` green (933 passed / 1 skipped); `phpcs` 0 errors; `composer copy:scan` no new debt; `php -l` clean.

## Dev Notes

### Existing assets
- `template-parts/footer.html` → `<!-- wp:pattern {"slug":"ink-foundation/footer-main"} /-->`. The footer content lives in `patterns/footer-main.php` (a `core/template-part/footer` block-type pattern): a tagline, three link columns (Ontdek / Gemeenskap / Ondersteun ons), and a copyright row. Currently has NO social links — this story adds them.
- WordPress core social blocks: `wp:social-links` (container) + `wp:social-link {"service":"facebook","url":"…"}` children — theme-native, the sanctioned replacement for the retired "Ultimate Social Media Icons" plugin.

### Architecture compliance
- Presentation only; tokens only (Gate A); Afrikaans heading, sentence case (Gate D). The platform URLs are org-detail placeholders (`#`) the owner sets pre-launch — same class as `[stigtingsjaar]`/`[regstatus]`; they do not trip `composer copy:scan` (which only scans the copy-markers).

### References
- [Source: _bmad-output/planning-artifacts/epics.md#Story 15.5] (FR-62)
- [Source: docs/specs/ink-consolidated-spec.md §retired plugins — "Ultimate Social Media Icons (→ theme footer pattern)"]
- [Source: wp-content/themes/ink-foundation/patterns/footer-main.php — current footer]
- [Source: wp-content/themes/ink-foundation/template-parts/footer.html — footer part wiring]

## Dev Agent Record

### Agent Model Used

claude-opus-4-8 (BMAD dev-story workflow)

### Debug Log References

- `composer test:unit` → 933 passed, 1 skipped. New `FooterSocialLinksTest`: 3 passed.
- Iteration: the legacy-plugin-absence guard initially failed because the footer comment named the retired plugin ("Ultimate…") — reworded the comment to "the sanctioned replacement for the retired social-icon plugin"; re-ran green. (Same docblock self-reference trap seen in 15.2/15.3/15.4 — guards substring-match the whole file.)
- `phpcs` on `footer-main.php` → 0 errors. `composer copy:scan` → no new debt. `php -l` clean.

### Completion Notes List

- Added a "Volg ons" social-links section to `patterns/footer-main.php` using the WordPress core `wp:social-links` / `wp:social-link` blocks (Facebook, Instagram, X) — theme-native, the sanctioned replacement for the retired "Ultimate Social Media Icons" plugin (AC #1, #2). The footer template part (`template-parts/footer.html` → `footer-main`) carries it.
- Platform URLs are clearly-marked `#` placeholders — an org-detail pre-launch value the owner sets (same class as `[stigtingsjaar]`); they do not trip copy:scan.
- Glossary-first: added "voettekst" + "Sosiale skakels" to `docs/afrikaans-terms.md` (the latter was flagged missing).
- Presentation only; tokens only; sentence-case heading.

### File List

- `wp-content/themes/ink-foundation/patterns/footer-main.php` (MODIFIED — social-links section)
- `docs/afrikaans-terms.md` (MODIFIED — voettekst + Sosiale skakels glossary terms)
- `tests/Unit/Org/FooterSocialLinksTest.php` (NEW)
- `_bmad-output/implementation-artifacts/15-5-footer-social-links.md`, `sprint-status.yaml` (tracking)

## Change Log

- 2026-06-28 — Story 15.5 implemented: theme-native footer social links (core social-links block) replacing the legacy plugin, glossary terms, and a guard test. Status → done.

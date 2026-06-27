---
baseline_commit: 199c8b1
---

# Story 9.7: Member directory (ledegids)

Status: done

<!-- Note: Validation is optional. Run validate-create-story for quality check before dev-story. -->

## Story

As a lid,
I want a member directory,
so that I can discover writers. (FR-43)

## Acceptance Criteria

**Given** the ledegids
**When** it renders
**Then** it provides a writer-discovery surface.

1. A **ledegids** page exists (`templates/page-ledegids.html`) that provides a writer-discovery surface — INK's own listing, not a default BuddyPress members-directory screen.
2. It **reuses the proven Story 8.3 writer discovery** (`ink/ontdek-skrywers`: genre filter + Nuwe stemme / Meeste gelees sorts + paginated skrywer cards) rather than building a parallel directory (no duplication — Principle 8; the writer listing is already the single source). The block's genre/sort/page GET vars work unchanged on the ledegids route.
3. The ledegids has an **intro** (heading + short description) using the glossary `ledegids` term (the approved member-directory label — never "members list" / "directory") and authored Afrikaans copy.
4. **Three-layer:** the page is theme presentation (FSE template + pattern) embedding the existing `ink-core` discovery block; no new business logic. Locked chrome per the 1.6 convention; tokens-only styling.
5. The directory surfaces **writers** (the 8.3 block lists writers with a published work), consistent with FR-43's "discover writers" intent — not every registered account.

## Tasks / Subtasks

- [x] Task 1: Ledegids pattern (`patterns/ledegids.php`) (AC: #1–#3, #5)
  - [x] Heading (`ink_foundation_term( 'ledegids' )`) + a short authored-Afrikaans intro paragraph; then `<!-- wp:ink/ontdek-skrywers /-->` (the 8.3 writer-discovery block). Token-only; copy via `esc_html__` / `ink_foundation_term`.
- [x] Task 2: Ledegids template (`templates/page-ledegids.html`) (AC: #1, #4)
  - [x] header part → `main` (constrained) → `<!-- wp:pattern {"slug":"ink-foundation/ledegids"} /-->` → footer part; locked chrome (move/remove).
- [x] Task 3: Terms + tests + gates (AC: #3)
  - [x] Add `ledegids` Terms key ("Ledegids") to `I18n\Terms::map()` (glossary line 162).
  - [x] `tests/Unit/Social/LedegidsTemplateTest.php` (structural, mirrors `OntdekTemplateTest`): `page-ledegids.html` embeds the `ink-foundation/ledegids` pattern + header/footer parts; `ledegids.php` embeds `wp:ink/ontdek-skrywers` (the reused 8.3 surface) and uses the `ledegids` term (no raw directory literal). `TermsTest` (extend): the `ledegids` key resolves to "Ledegids".
  - [x] `composer test:unit` green; `composer stan` clean; `composer cs` 0 errors; `composer copy:scan` no new debt (theme pattern text via gettext); `composer deptrac` clean (no code change — block reuse only).

## Dev Notes

- **Reuse, don't rebuild** [Source: Story 8.3 `ink/ontdek-skrywers`; project-context Principle 8 / "don't build a feature a kept surface already covers"]: the writer-discovery listing (genre facet + sorts + skrywer cards) already exists from 8.3 and is the single source. The ledegids is the **member-directory route** onto that surface — embed the block, do not duplicate the `WP_User_Query`. This also keeps the directory and the Ontdek skrywers tab consistent (one listing, two entry points).
- **Custom, not the BuddyPress members screen** [Source: epics.md#Story 8.5 "custom, not default community screens"; project-context BuddyPress scoped]: even though the BP `members` component is scoped ON (Story 9.1, for the directory capability), the *surface* INK shows is its own `ink/ontdek-skrywers` block — not the default BP members-directory template. (BP `members` being active is what makes author archives / the directory capability available; the rendered UI is INK's.)
- **Glossary** [Source: afrikaans-terms.md line 162 "ledegids … Moenie 'members list' of 'directory' gebruik nie"]: use the `ledegids` term via the registry; the intro copy is authored Afrikaans. No AI Afrikaans, no raw literal in the pattern.
- **Theme-only story**: no `ink-core` source change beyond the `ledegids` Terms key — this is presentation wiring (a template + a pattern) onto an existing block, like the auth/utility pages. The structural test is the verification (the theme is covered by structural/E2E, not unit logic).

### Project Structure Notes

- NEW theme: `templates/page-ledegids.html`, `patterns/ledegids.php`.
- MOD ink-core: `src/I18n/Terms.php` (`ledegids` key).
- NEW tests: `tests/Unit/Social/LedegidsTemplateTest.php`; MOD `tests/Unit/I18n/TermsTest.php`.
- deptrac: no change. theme.json: no new styles needed (reuses `.ink-ontdek-skrywers*` from 8.3); add a `.ink-ledegids` intro wrapper style only if required.
- Note (don't build): a separate non-writer members directory; per-member messaging (9.8 deferred); follow-from-directory affordance (the 8.3 cards already link to the Skrywerprofiel where the 9.2 Volg toggle lives).

### References

- [Source: _bmad-output/planning-artifacts/epics.md#Story 9.7]
- [Source: wp-content/plugins/ink-core/src/Discovery/SkrywersTab.php (the reused ink/ontdek-skrywers block, Story 8.3)]
- [Source: wp-content/themes/ink-foundation/templates/page-ontdek.html, patterns/ontdek.php (page+pattern shape)]
- [Source: docs/afrikaans-terms.md line 162 (ledegids); _bmad-output/project-context.md#Principle-8, #three-layer, #Afrikaans-first]

## Dev Agent Record

### Agent Model Used

claude-opus-4-8 (BMAD dev-story loop, Epic 9)

### Debug Log References

- `composer stan` / `composer deptrac` run outside the sandbox. stan OK; deptrac 3 pre-existing (0 new — block reuse only, no code dependency added).

### Completion Notes List

- **Ledegids = the member-directory route onto the existing 8.3 writer discovery** — no parallel directory. `patterns/ledegids.php` embeds `wp:ink/ontdek-skrywers` (the single-source writer listing: genre facet + sorts + skrywer cards) under an authored intro; `templates/page-ledegids.html` hosts it in the chrome. Principle 8 (no duplication) — the ledegids and the Ontdek skrywers tab are one listing, two entry points.
- **Custom surface, not the BP members screen**: the rendered UI is INK's own block; BuddyPress `members` (scoped ON in 9.1) supplies the directory capability/author archives, not the template.
- **Glossary**: `ledegids` Terms key added (glossary line 162 — never "members list"/"directory"); the intro "Ontdek die skrywers van die gemeenskap." is authored Afrikaans via `esc_html__` (copy-debt to ratify). copy:scan clean.
- **Theme-only** beyond the Terms key — verified structurally (`LedegidsTemplateTest`) + the `ledegids` Terms resolution (`TermsTest`).
- Tests 646→649 (+3); cs 0 errors; stan OK; copy:scan no new debt; deptrac 3 pre-existing (0 new).

### File List

- `wp-content/themes/ink-foundation/patterns/ledegids.php` (NEW — directory pattern reusing ink/ontdek-skrywers)
- `wp-content/themes/ink-foundation/templates/page-ledegids.html` (NEW — ledegids page template)
- `wp-content/plugins/ink-core/src/I18n/Terms.php` (MOD — ledegids key)
- `tests/Unit/Social/LedegidsTemplateTest.php` (NEW — structural)
- `tests/Unit/I18n/TermsTest.php` (MOD — social-keys resolution test)
- `_bmad-output/implementation-artifacts/9-7-member-directory-ledegids.md` (NEW — this story)
- `_bmad-output/implementation-artifacts/sprint-status.yaml` (MOD — 9.7 status)

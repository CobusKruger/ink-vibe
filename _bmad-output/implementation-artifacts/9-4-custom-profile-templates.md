---
baseline_commit: fd087fc
---

# Story 9.4: Custom profile templates

Status: done

<!-- Note: Validation is optional. Run validate-create-story for quality check before dev-story. -->

## Story

As a lid,
I want block-theme profiles,
so that public and private profile data are correctly separated. (FR-40)

## Acceptance Criteria

**Given** the profile templates
**When** they render
**Then** **private My Profiel** and **public Skrywerprofiel** exist, showing Gradering, bio, stats, pinned works, accomplishments
**And** private data (read counts, "wins needed" subtext) lives on My Profiel only.

1. A **public Skrywerprofiel** exists as the block-theme author template (`templates/author.html`) and shows, for the *queried* skrywer: display name, avatar, bio, the **Gradering badge** (Story 5.4), the **volgeling count** (Story 9.2), a **Volg / Volg tans** toggle (Story 9.2), a **pinned-works** slot (filled by Story 9.5), and an accomplishments area. **No private data** (no read counts, no "wins needed" subtext).
2. The Skrywerprofiel's per-author dynamic content is a **server-rendered `ink-core` block** (`ink/skrywerprofiel`) that resolves the queried author at render time (`get_queried_object_id()`). This is required because a pattern's PHP runs at registration/`init` — before the main query resolves — so a queried-author profile cannot be rendered from pattern-PHP bridge calls (those only work for the *current user*). The block degrades to nothing for a non-author context.
3. A **private My Profiel** exists as a block-theme page template (`templates/page-my-profiel.html`) for the logged-in member's own dashboard, showing: their Gradering badge, the **"wins needed" subtext** (Story 5.9 — private only), their following-feed (`ink/volg-voer`, Story 9.3), their leeslys (`ink/leeslys`, Story 7.7), a **read-count** slot (filled by Story 9.12 — private only), and the lidmaatskap renewal section (the existing `ink-foundation/lidmaatskap-hernu` pattern, superseding the interim `page-my-profiel-lidmaatskap` host per the Story 4.5 note).
4. My Profiel's current-user content is a **theme pattern** (`patterns/my-profiel.php`) that calls the current-user bridges (`ink_foundation_gradering_badge()`, `ink_foundation_gradering_wins_needed()`) and embeds the existing blocks — the same pattern-PHP-at-init mechanism the `lidmaatskap-hernu` pattern already uses for current-user content (which is valid because auth is established before `init`).
4. **Public/private separation is load-bearing and tested:** the read-count surface and the "wins needed" subtext appear on **My Profiel only**. The public Skrywerprofiel block must NOT render `ink_foundation_gradering_wins_needed` or any read-count surface — asserted by test (the public block file does not reference the wins-needed bridge / read-count block; the private pattern does).
6. **Three-layer & conflation-clean:** the Skrywerprofiel block lives in `ink-core` (`Ink\Social`) and renders its own escaped HTML (the house style — like the Discovery/Engagement blocks); it reads `Tiers\Api::gradingView()` for the badge **display only** (never a gate — the same conflation-clean display read as Discovery→Tiers in 8.5) and `Social\Api` for follow data. The theme templates only embed the block + pattern. Gradering/read-count/wins-needed are display surfaces, never entitlement/permission gates.
7. **Afrikaans-first:** all profile copy (section headings, "Geen werke nie", accomplishments label, stats phrasing) is glossary-backed (`Terms` / `ink_foundation_term`) or authored Afrikaans from `ui-copy-translations.md` (the Skrywerprofiel / My Profiel sections); no AI Afrikaans, no raw literals in patterns.

## Tasks / Subtasks

- [x] Task 1: Public Skrywerprofiel block (`Ink\Social\SkrywerProfiel`) (AC: #1, #2, #6, #7)
  - [x] `BLOCK = 'ink/skrywerprofiel'`; register on `init` (`register_block_type` guard). `render()` — resolve `get_queried_object_id()`; if not a positive author id return ''. Gather: display name, avatar, bio (`description` meta), gradering badge (from `Tiers\Api::gradingView()`, token-only markup, a11y label-as-text), volgeling count label (`Social\Api::volgelingLabel( Api::followerCount() )`), the Volg toggle (`FollowToggle::render(['skrywerId'=>$id])`), a reserved pinned-works slot comment (9.5), accomplishments area.
  - [x] `toHtml( array $profile ): string` (pure) — compose the escaped public card; NO read-count / wins-needed. Pinned-works slot is a documented marker the 9.5 block injects into (e.g. an empty `<div class="ink-skrywerprofiel__vasgespel">` the 9.5 render targets, or a `wp:ink/vasgespelde-werke` embed once 9.5 exists — reserve the slot, don't fabricate pinned data).
- [x] Task 2: Public template `author.html` (AC: #1)
  - [x] `templates/author.html` — header part → `main` (constrained) → `<!-- wp:ink/skrywerprofiel /-->` → footer part; locked chrome (move/remove) per the 1.6 convention.
- [x] Task 3: Private My Profiel pattern + template (AC: #3, #4, #7)
  - [x] `patterns/my-profiel.php` — current-user dashboard: heading; gradering badge (`ink_foundation_gradering_badge()`); the wins-needed subtext (`ink_foundation_gradering_wins_needed()`, rendered only when non-empty — private); read-count slot marker (9.12); `<!-- wp:ink/volg-voer /-->` (following-feed); `<!-- wp:ink/leeslys /-->`; `<!-- wp:pattern {"slug":"ink-foundation/lidmaatskap-hernu"} /-->`. Token-only styles; authored Afrikaans via `ink_foundation_term` / `esc_html__`.
  - [x] `templates/page-my-profiel.html` — header → main → `<!-- wp:pattern {"slug":"ink-foundation/my-profiel"} /-->` → footer.
- [x] Task 4: Styles + glue (AC: #1, #7)
  - [x] `.ink-skrywerprofiel*` + `.ink-my-profiel*` token-only styles in `theme.json`.
  - [x] Register `SkrywerProfiel` in `Social\Module::register()`.
- [x] Task 5: Tests + gates (AC: all, esp. #4 separation)
  - [x] `tests/Unit/Social/SkrywerProfielTest.php`: `render` returns '' for a non-author context (queried id 0); `toHtml` renders name/bio/gradering/volgeling/volg button for a queried author; **public-only**: the rendered public card contains NO read-count and NO wins-needed text (non-vacuous — it DOES contain the gradering badge + volgeling count).
  - [x] `tests/Unit/Social/ProfileTemplatesTest.php` (structural, mirrors `OntdekTemplateTest`): `author.html` embeds `wp:ink/skrywerprofiel` + header/footer parts; `page-my-profiel.html` embeds `ink-foundation/my-profiel`; `my-profiel.php` calls `ink_foundation_gradering_wins_needed` + embeds `wp:ink/volg-voer` + `wp:ink/leeslys` + the `lidmaatskap-hernu` ref; **separation**: the `SkrywerProfiel.php` block source does NOT reference `wins_needed` / a read-count block, while `my-profiel.php` DOES (the wins-needed bridge) — the load-bearing public/private split.
  - [x] `composer test:unit` green; `composer stan` clean; `composer cs` 0 errors; `composer copy:scan` no new debt (authored/glossary copy only, or `[NEEDS HUMAN AFRIKAANS]` placeholders routed properly for any gap); `composer deptrac` clean (declare Social→Tiers display edge if introduced, mirroring Discovery→Tiers).

## Dev Notes

- **Why a server-rendered block for the public profile, not a pattern** [Source: WP pattern-registration timing; reading-pattern precedent (ink/gedig-body)]: a `.php` pattern's code runs when the pattern is registered (on `init`), *before* the main query resolves, so `get_queried_object_id()` is unavailable there. Per-author dynamic content therefore MUST be a server-rendered block whose `render_callback` runs at render time (after the query) — exactly how the reading surfaces embed `ink/gedig-body`. The current-user My Profiel is different: auth is established before `init`, so `get_current_user_id()` is valid in pattern PHP (as `lidmaatskap-hernu` already relies on).
- **Reuse the existing surfaces, don't rebuild** [Source: Stories 5.4, 5.9, 9.2, 9.3, 7.7]: the Gradering badge (theme bridge `ink_foundation_gradering_badge`), wins-needed subtext (`ink_foundation_gradering_wins_needed`), follow toggle (`ink/volg-knoppie` / `FollowToggle`), following-feed (`ink/volg-voer`), leeslys (`ink/leeslys`) and lidmaatskap renewal (`ink-foundation/lidmaatskap-hernu`) all already exist. 9.4 is the HOST that places them with the correct public/private split. The public block reuses `FollowToggle::render()` directly (same module).
- **The interim lidmaatskap host is superseded** [Source: Story 4.5 note "interim page-my-profiel-lidmaatskap … superseded by Epic 9.4"]: My Profiel now embeds the `lidmaatskap-hernu` pattern. Leave the interim `page-my-profiel-lidmaatskap.html` in place (it harmlessly hosts the same pattern; full removal/redirect is a migration concern, Epic 16) but note it is superseded.
- **Gradering on the public profile reads Tiers for DISPLAY only** [Source: 8.5 Discovery→Tiers display edge; project-context THE conflation rule]: the badge shows the writer's gradering; it is never a permission/entitlement gate. The block reads `Tiers\Api::gradingView()` (the typed display view) exactly as the theme bridge does — display, not a gate. This introduces a Social→Tiers deptrac edge (display-only) mirroring the accepted Discovery→Tiers edge; declare it. (Keep the FollowStore/Controller/Toggle/Counts/Feed files Tiers-free — the existing conflation `CodeScan` stays as-is; the profile block is the documented display-read exception, like Discovery.)
- **Pinned works + read counts are reserved slots** [Source: epics.md#Story 9.5, #Story 9.12]: the AC lists "pinned works" (public) and "read counts" (private My Profiel) as profile content, but those blocks are Stories 9.5 / 9.12. 9.4 reserves their slots (documented markers / empty containers the later blocks render into); it must NOT fabricate pinned data or a read-count surface. This is the same host-then-fill sequencing as 8.5→9.4.
- **Copy** [project-context Afrikaans-first; ui-copy-translations.md]: profile headings, stats phrasing, accomplishments label, "Geen werke nie" — use the authored Skrywerprofiel / My Profiel copy in `ui-copy-translations.md` where it exists; route any genuine gap through the `[NEEDS HUMAN AFRIKAANS]` + translation-sheet workflow (no AI Afrikaans). Stats like the volgeling count come from `Social\Api::volgelingLabel()` (already glossary-correct).

### Project Structure Notes

- NEW ink-core: `src/Social/SkrywerProfiel.php` (server-rendered `ink/skrywerprofiel` public-profile block).
- MOD ink-core: `src/Social/Module.php` (register the block).
- NEW theme: `templates/author.html`, `templates/page-my-profiel.html`, `patterns/my-profiel.php`.
- MOD theme: `theme.json` (`.ink-skrywerprofiel` / `.ink-my-profiel` token styles).
- NEW tests: `SkrywerProfielTest`, `ProfileTemplatesTest`.
- deptrac: declare Social→Tiers (display-only, mirror Discovery→Tiers) for the gradering badge read.
- Note (don't build): the pinned-works block (9.5 fills the public slot); the read-count surface (9.12 fills the private slot); a custom My-Profiel-vs-public-author routing nuance beyond the standard author template; reviews display (9.6).

### References

- [Source: _bmad-output/planning-artifacts/epics.md#Story 9.4, #Story 9.5, #Story 9.12]
- [Source: _bmad-output/planning-artifacts/architecture.md#AD-7 (server-rendered, no REST), #AD-1 (facade), #AD-5 (display reads)]
- [Source: wp-content/themes/ink-foundation/functions.php (ink_foundation_gradering_badge / _wins_needed bridges — Stories 5.4/5.9)]
- [Source: wp-content/themes/ink-foundation/patterns/lidmaatskap-hernu.php (current-user pattern-PHP precedent), templates/page-ontdek.html (template shape)]
- [Source: wp-content/plugins/ink-core/src/Social/FollowToggle.php, FollowingFeed.php, Api.php (Stories 9.2/9.3)]
- [Source: _bmad-output/project-context.md#three-layer, #conflation-rule, #Afrikaans-first, #block-locking]

## Dev Agent Record

### Agent Model Used

claude-opus-4-8 (BMAD dev-story loop, Epic 9)

### Debug Log References

- `composer stan` / `composer deptrac` run outside the sandbox. stan OK; deptrac 3 PRE-EXISTING violations, 0 new — the Social→Tiers display edge (gradering badge) was declared in `deptrac.yaml` mirroring the accepted Discovery→Tiers (8.5) edge.
- `my-profiel.php` echoes the `ink_foundation_gradering_badge()` bridge output (already-escaped token markup) inside a `wp:html` block with a documented `phpcs:ignore WordPress.Security.EscapeOutput` (the bridge is the trusted escaper, like other theme bridge consumers).

### Completion Notes List

- **Public Skrywerprofiel = server-rendered block** (`ink/skrywerprofiel`, `Ink\Social\SkrywerProfiel`): resolves the *queried* author at render (`is_author()` + `get_queried_object_id()`) — necessary because pattern PHP runs at `init`, before the query resolves. Renders name/avatar/bio + Gradering badge + volgeling count + Volg toggle + a reserved pinned-works slot (9.5) + accomplishments. PUBLIC data only.
- **Private My Profiel = theme pattern** (`patterns/my-profiel.php` + `templates/page-my-profiel.html`): current-user context, so the `ink_foundation_gradering_badge()` / `ink_foundation_gradering_wins_needed()` bridges resolve in pattern PHP (the `lidmaatskap-hernu` mechanism). Embeds the wins-needed subtext (private), a reserved read-count slot (9.12), the `ink/volg-voer` feed (9.3), `ink/leeslys` (7.7) and the `lidmaatskap-hernu` renewal section (superseding the interim host).
- **FR-40 public/private separation is the load-bearing guarantee, double-tested**: (1) `SkrywerProfielTest` asserts the rendered public card carries the gradering badge + volgeling count but NO wins-needed / read-count markup (non-vacuous); (2) `ProfileTemplatesTest` asserts the public block SOURCE never references `wins_needed`/`winsNeededSubtext`/`leesgetalle`, while `my-profiel.php` DOES carry the wins-needed bridge + the read-count slot.
- **Gradering badge reads Tiers for DISPLAY only** (never a gate) — declared Social→Tiers in `deptrac.yaml`, mirroring the accepted Discovery→Tiers display edge (8.5). The follow-graph files stay Tiers-free (their conflation `CodeScan` is unchanged); the profile block is the documented display-read.
- **Copy is authored/approved** (no AI Afrikaans): "My profiel", "Prestasies" (ui-copy line 689), "Gradering" (Terms registry); the feed/leeslys/renewal blocks carry their own approved copy. copy:scan clean (no new debt; the pattern's text nodes all go through `esc_html__`/`ink_foundation_term`).
- Tests 607→614 (+7); cs 0 errors; stan OK; copy:scan no new debt; deptrac 3 pre-existing (0 new).

### File List

- `wp-content/plugins/ink-core/src/Social/SkrywerProfiel.php` (NEW — ink/skrywerprofiel public-profile block)
- `wp-content/plugins/ink-core/src/Social/Module.php` (MOD — register SkrywerProfiel)
- `wp-content/themes/ink-foundation/templates/author.html` (NEW — public Skrywerprofiel template)
- `wp-content/themes/ink-foundation/templates/page-my-profiel.html` (NEW — private My Profiel template)
- `wp-content/themes/ink-foundation/patterns/my-profiel.php` (NEW — My Profiel dashboard pattern)
- `wp-content/themes/ink-foundation/theme.json` (MOD — .ink-skrywerprofiel / .ink-my-profiel token styles)
- `deptrac.yaml` (MOD — declare Social→Tiers display edge)
- `tests/Unit/Social/SkrywerProfielTest.php` (NEW)
- `tests/Unit/Social/ProfileTemplatesTest.php` (NEW)
- `_bmad-output/implementation-artifacts/9-4-custom-profile-templates.md` (NEW — this story)
- `_bmad-output/implementation-artifacts/sprint-status.yaml` (MOD — 9.4 status)

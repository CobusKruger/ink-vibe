---
baseline_commit: bfa8008
---

# Story 10.5: Winner ↔ challenge linkage

Status: review

<!-- Note: Validation is optional. Run validate-create-story for quality check before dev-story. -->

## Story

As a reader,
I want winners linked to their producing challenge,
so that I can trace a winning piece's context. (FR-53)

## Acceptance Criteria

**Given** a winning entry
**When** it appears in the Biblioteek
**Then** it links back to the producing challenge via `uitdagingsrondte` (or modelled relationship).

1. A `biblioteek_item` (or any post) carrying an `uitdagingsrondte` round term **links back to the producing `uitdaging`** on its single view — the modelled relationship is the existing round-term slug convention `uitdaging-{id}` (Story 6.6 `ChallengeLinking::resolveRoundTerm`).
2. The round-term ↔ uitdaging slug convention is a **single source** consumed by both the writer (Submission, which creates the term) and the reader (Library, which resolves it) — no duplicated literal. Extracted to `Ink\Content\ChallengeRound` (Content owns both the `uitdagingsrondte` taxonomy and the `uitdaging` CPT); `Submission\ChallengeLinking::resolveRoundTerm()` is refactored to use it (same slug value — behaviour unchanged).
3. The link is **fail-safe**: a round term whose slug doesn't parse, or that resolves to a non-existent / non-`uitdaging` / unpublished post, yields **no link** (silently skipped) rather than a broken/exposing link. Multiple round terms → multiple links.
4. The linkage renders via a server-rendered block (`ink/biblioteek-uitdaging-skakel`) embedded in the `reading-biblioteek` single pattern (three-layer-clean — resolution logic in `ink-core`, presentation in the theme). Afrikaans copy ("Uit die uitdaging:") is glossary-consistent authored `__()` source.
5. Conflation rule holds: zero `Ink\Tiers` / `Ink\Entitlement` — the linkage is a content-relationship display, never a gate. `Library` depends only on `Kernel` + `Content`.

## Tasks / Subtasks

- [x] Task 1: Single-source the round-term slug convention (AC: #2)
  - [x] `wp-content/plugins/ink-core/src/Content/ChallengeRound.php` — `SLUG_PREFIX = 'uitdaging-'`; `slugFor()`; `uitdagingIdFromSlug()` (regex `^uitdaging-([1-9][0-9]*)$` → int or null; rejects leading-zero / non-positive / non-matching).
  - [x] Refactored `Submission\ChallengeLinking::resolveRoundTerm()` to use `ChallengeRound::slugFor()` (identical value; ChallengeLinking tests still green).
- [x] Task 2: `Library\WinnerLinkage` resolver + server block (AC: #1, #3, #4, #5)
  - [x] `wp-content/plugins/ink-core/src/Library/WinnerLinkage.php`: `BLOCK = 'ink/biblioteek-uitdaging-skakel'`; `register()`; `linksFor()` (resolve `uitdagingsrondte` terms → published `uitdaging` links, fail-safe skips + de-dupe); pure `toHtml()`; thin `render()`.
  - [x] Wired `( new WinnerLinkage() )->register()` into `Library\Module::register()`.
- [x] Task 3: Theme — embed on the single view (AC: #4)
  - [x] Added `<!-- wp:ink/biblioteek-uitdaging-skakel /-->` to `reading-biblioteek.php` (below the byline; renders empty when no linked challenge).
- [x] Task 4: Tests (AC: all)
  - [x] `tests/Unit/Content/ChallengeRoundTest.php` — 3 tests (round-trip + null-rejection incl. `uitdaging-0`/`uitdaging-01`/`uitdaging-abc`).
  - [x] `tests/Unit/Library/WinnerLinkageTest.php` — 6 tests (`toHtml` empty/escaped/multiple; `linksFor` resolve/skip-unparseable/skip-non-uitdaging/skip-unpublished/de-dupe). Added `tests/stubs/class-wp-term.php` (bootstrap-wired, like WP_User/WP_Error).
  - [x] `tests/Unit/Library/BiblioteekTemplateTest.php` — asserts the reading pattern embeds the linkage block.
- [x] Task 5: Gates (AC: all)
  - [x] `composer test:unit` 704 passed / 1 skipped (+9, incl. ChallengeLinking green); `composer stan` OK (125 files); `composer cs` clean on all changed files; `composer copy:scan` no new debt; `composer deptrac` 3 PRE-EXISTING, 0 new (no new edge — `Library → Content` from 10.1 covers `ChallengeRound`; Submission unchanged).

## Dev Notes

- **The modelled relationship already exists** [Source: wp-content/plugins/ink-core/src/Submission/ChallengeLinking.php:183-202]: a bydrae/biblioteek_item linked to a uitdaging carries an `uitdagingsrondte` term whose slug is `uitdaging-{uitdaging_id}` — a stable, parseable join key back to the producing `uitdaging` post. 10.5 reads that key in the other direction. The `ink_entries` authoritative record is Epic 12/12A; the slug convention is the launch-grade relationship.
- **Single-source the convention** [Source: project-context "single source"; the conflation/DRY discipline]: today `resolveRoundTerm` inlines `'uitdaging-' . $id`. Extract to `Ink\Content\ChallengeRound` (Content owns both `Taxonomies::UITDAGINGSRONDTE` and `PostTypes::UITDAGING`), so the writer (Submission) and the reader (Library) share one definition. Both modules already depend on Content (deptrac) — no new edge, no circular risk. The test overrides `resolveRoundTerm`, and the value is unchanged, so Submission tests stay green.
- **Fail-safe resolution** [Source: WorksArchive defensive-degrade house style]: `uitdagingIdFromSlug` returns null for any non-`uitdaging-<positiveint>` slug; `linksFor` then verifies `get_post_type === UITDAGING` + `get_post_status === 'publish'` before emitting a link. A deleted/unpublished/tampered round term shows nothing (never a broken or draft-exposing link).
- **Single view is the context surface** [Source: AC; reading-biblioteek.php from 10.1]: the link belongs on the single reading view ("trace a winning piece's context"). Card-level linkage would add N per-item term queries to the archive — out of scope; the single view satisfies FR-53. (Note for later: a card badge could reuse `linksFor`.)
- **Server block + theme embed** [Source: 10.1 Archive block + reading-gedig engagement-block embeds]: the resolver is an `ink-core` server block; the theme only embeds it (three-layer). Renders empty markup when the item has no producing challenge, so non-winning library items are unaffected.
- **Afrikaans-first** [Source: project-context]: "Uit die uitdaging:" is glossary-consistent authored `__( …, 'ink-core' )` source (copy-debt to ratify, 8.x precedent) — no AI Afrikaans.
- **Conflation rule** [Source: deptrac.yaml; THE conflation rule]: linkage is content-relationship display only — zero Tiers/Entitlement. "Winner" here is the challenge-context trace, not a tier gate.

### Project Structure Notes

- NEW: `wp-content/plugins/ink-core/src/Content/ChallengeRound.php`, `wp-content/plugins/ink-core/src/Library/WinnerLinkage.php`.
- MOD: `wp-content/plugins/ink-core/src/Submission/ChallengeLinking.php` (use `ChallengeRound::slugFor`), `wp-content/plugins/ink-core/src/Library/Module.php` (register the block), `wp-content/themes/ink-foundation/patterns/reading-biblioteek.php` (embed the block).
- NEW tests: `tests/Unit/Content/ChallengeRoundTest.php`, `tests/Unit/Library/WinnerLinkageTest.php`; MOD `tests/Unit/Library/BiblioteekTemplateTest.php`.
- **Expected deptrac edges (pre-flagged):** none new — `Library → Content` (already declared in 10.1) covers `ChallengeRound`/`PostTypes`/`Taxonomies`; Submission → Content already allowed. No Entitlement/Tiers.
- Note (don't build): card-level challenge badges on the archive; the `ink_entries` authoritative entry model (Epic 12/12A).

### References

- [Source: _bmad-output/planning-artifacts/epics.md#Story 10.5 (FR-53)]
- [Source: wp-content/plugins/ink-core/src/Submission/ChallengeLinking.php:183-202 (the `uitdaging-{id}` slug convention)]
- [Source: wp-content/plugins/ink-core/src/Library/Archive.php (10.1 server-block house style)]
- [Source: wp-content/themes/ink-foundation/patterns/reading-biblioteek.php (10.1 single pattern to embed into)]
- [Source: deptrac.yaml (Library → Content; conflation rule)]

## Dev Agent Record

### Agent Model Used

claude-opus-4-8 (BMAD dev-story loop, Epic 10)

### Debug Log References

- `composer stan` run with the sandbox disabled (PHPStan binds a local TCP analysis socket → EPERM under the command sandbox); result OK / 125 files.

### Completion Notes List

- **Single-sourced the round-term ↔ uitdaging join key** in `Ink\Content\ChallengeRound` (`slugFor`/`uitdagingIdFromSlug`, the `uitdaging-{id}` convention). Content owns both the taxonomy + the CPT, so the writer (`Submission\ChallengeLinking`, refactored to use it) and the reader (`Library\WinnerLinkage`) share one definition — no duplicated literal. Submission tests stay green (value unchanged; the test overrides `resolveRoundTerm`).
- **`ink/biblioteek-uitdaging-skakel` server block** resolves a Biblioteek item's `uitdagingsrondte` terms back to their producing published `uitdaging`(s) and renders "Uit die uitdaging: <link>". Fail-safe: unparseable slug / non-uitdaging / unpublished / deleted → no link (silently skipped); repeated terms de-duped. Embedded in the 10.1 `reading-biblioteek` single pattern; renders empty markup for non-winning items.
- **Conflation-clean:** content-relationship display only — zero Tiers/Entitlement; no new deptrac edge (the 10.1 `Library → Content` covers `ChallengeRound`).
- **Tests:** +9 (3 ChallengeRound + 6 WinnerLinkage) + 1 template assertion; new `WP_Term` stub wired into the bootstrap. Suite 695→704, zero regressions.
- **Deferred (noted):** card-level challenge badges on the archive (reuse `linksFor`); the `ink_entries` authoritative entry model is Epic 12/12A.

### File List

- `wp-content/plugins/ink-core/src/Content/ChallengeRound.php` (NEW — round-term slug single source)
- `wp-content/plugins/ink-core/src/Library/WinnerLinkage.php` (NEW — `ink/biblioteek-uitdaging-skakel` block)
- `wp-content/plugins/ink-core/src/Submission/ChallengeLinking.php` (MOD — use `ChallengeRound::slugFor`)
- `wp-content/plugins/ink-core/src/Library/Module.php` (MOD — register WinnerLinkage)
- `wp-content/themes/ink-foundation/patterns/reading-biblioteek.php` (MOD — embed the linkage block)
- `tests/Unit/Content/ChallengeRoundTest.php` (NEW)
- `tests/Unit/Library/WinnerLinkageTest.php` (NEW)
- `tests/Unit/Library/BiblioteekTemplateTest.php` (MOD — assert the linkage block embed)
- `tests/stubs/class-wp-term.php` (NEW — minimal WP_Term double)
- `tests/bootstrap.php` (MOD — wire the WP_Term stub)
- `_bmad-output/implementation-artifacts/10-5-winner-challenge-linkage.md` (NEW — this story)
- `_bmad-output/implementation-artifacts/sprint-status.yaml` (MOD — 10.5 status)

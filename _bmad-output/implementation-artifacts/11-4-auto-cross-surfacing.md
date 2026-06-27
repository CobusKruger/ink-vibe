---
baseline_commit: (Story 11.3 commit)
---

# Story 11.4: Auto cross-surfacing

Status: done

## Story

As a reader,
I want training to surface beside relevant works,
so that guidance appears in context without staff effort. (FR-55)

## Acceptance Criteria

**Given** shared `genre`/`vaardigheid` terms
**When** a work/challenge renders
**Then** related training surfaces **solely** by shared terms (an item sharing no term surfaces nothing); no per-item manual linking exists.

1. A server-rendered block (`ink/opleiding-verwant`) on a work's reading view surfaces published `opleiding_artikel` that share **at least one** `genre` or `vaardigheid` term with the work — newest-first, bounded (N).
2. Matching is **solely by shared taxonomy terms** — there is **no per-item manual linking** field anywhere (Principle 8 / THE shared-taxonomy-surfacing rule). A work that shares no `genre`/`vaardigheid` term with any training article surfaces **nothing** (the block renders empty — no heading, no shell).
3. The block **excludes nothing but itself** is N/A for works (results are always `opleiding_artikel`); it defensively excludes the current post id so the same block embedded on an `opleiding_artikel` view (or future challenge view) never self-lists.
4. The block is embedded in the four work reading patterns (`reading-gedig`/`reading-storie`/`reading-artikel`/`reading-biblioteek`) after the existing related-works footer — three-layer-clean (theme embeds the ink-core block; no logic in the theme). The challenge single (Epic 12) embeds the same block when built; the block already reads terms generically (a challenge with no shared term surfaces nothing).
5. The heading is **"Verwante leerhulpbronne"** (glossary-consistent with the authored "Leerhulpbronne" term); copy-debt to ratify. Open browsing, conflation-clean (reads only `Ink\Content` taxonomy/CPT slugs), server-rendered (AD-7), fail-safe (unpublished/garbage terms surface nothing).

## Tasks / Subtasks

- [x] Task 1: `Training\RelatedTraining` server block (AC: #1–#3, #5)
  - [x] `wp-content/plugins/ink-core/src/Training/RelatedTraining.php` — `BLOCK = 'ink/opleiding-verwant'`, `LIMIT = 3`. Pure `queryArgs( int $exclude_id, list<int> $genre_ids, list<int> $vaardigheid_ids, int $limit )` building a `tax_query` OR over the non-empty term-id lists (single clause when only one taxonomy has terms; `relation => OR` when both); `post__not_in => [exclude_id]` (omitted when 0); published `opleiding_artikel`, newest-first, `no_found_rows`. Side-effecting `relatedFor()` collects genre + vaardigheid term ids → `[]` when none (no query) → else queries + maps. Pure `toHtml()` ("Verwante leerhulpbronne" heading + list; '' when empty).
  - [x] Registered in `Training\Module::register()` alongside `Hub`.
- [x] Task 2: Theme embeds (AC: #4)
  - [x] `<!-- wp:ink/opleiding-verwant /-->` after `wp:ink/verwante-stukke` in `reading-gedig.php`/`reading-storie.php`/`reading-artikel.php`, and after `post-content` in `reading-biblioteek.php`.
- [x] Task 3: Tests (AC: all)
  - [x] `tests/Unit/Training/RelatedTrainingTest.php` (7 tests) — `queryArgs` OR/single-clause/self-exclude/limit-floor/no-exclude; `toHtml` heading+links and the FR-55 surfaces-nothing invariant (`toHtml([])` is '').
  - [x] `OpleidingTemplateTest` — guardrail asserting the four work reading patterns embed `wp:ink/opleiding-verwant` (non-vacuous: a core block present too).
- [x] Task 4: Gates — `composer test:unit` 747 passed / 1 skipped (+8); `stan` OK; `cs` clean (phpcbf aligned the queryArgs param block); `copy:scan` 6/6 baseline; `deptrac` 3 pre-existing, 0 new (`Training → Content` already covers the taxonomy reads).

## Dev Notes

- **Mirror `Ink\Library\WinnerLinkage`** [Source: Library/WinnerLinkage.php]: a server-rendered block that resolves a relationship off the current post's terms and renders a small aside, fail-safe (nothing resolves → empty string, no shell). The difference: WinnerLinkage walks one `uitdagingsrondte` term → uitdaging; RelatedTraining walks the post's `genre`+`vaardigheid` terms → a bounded `opleiding_artikel` query.
- **Shared-taxonomy surfacing, never manual linking** [Source: project-context "Shared-taxonomy surfacing, not manual linking"; spec line 178]: there is no editorial "related training" picker. The match is purely `tax_query` over shared `genre`/`vaardigheid` term ids. A work with no such term shares nothing → the block renders nothing. This is the load-bearing FR-55 invariant — the test must prove BOTH directions (shares term → surfaces; shares none → empty).
- **Both taxonomies span works + training** [Source: Taxonomies.php:107-129]: `genre` AND `vaardigheid` attach to the bydrae CPTs + `opleiding_artikel` (+ biblioteek_item). So a work can carry either; the OR `tax_query` matches on either. `uitdaging` carries neither (so a challenge surfaces nothing until/unless it shares a term — consistent with the principle).
- **Empty-term-list safety** [Source: WP_Query tax_query]: an empty `terms` array is an invalid clause — build a clause only for a non-empty id list; with both empty, `relatedFor` returns `[]` before querying. With one empty, emit the single clause (no `relation`).
- **No new deptrac edge** — `Training → Content` (10.1/11.1) already covers `PostTypes`/`Taxonomies`. Conflation-clean — zero Entitlement/Tiers.

### Project Structure Notes

- NEW: `wp-content/plugins/ink-core/src/Training/RelatedTraining.php`, `tests/Unit/Training/RelatedTrainingTest.php`.
- MOD: `Training\Module` (register the block); `patterns/reading-gedig.php`, `reading-storie.php`, `reading-artikel.php`, `reading-biblioteek.php` (embed the block); `OpleidingTemplateTest.php` (embed guardrail); `sprint-status.yaml`.
- No new deptrac edges; conflation-clean.

### References

- [Source: _bmad-output/planning-artifacts/epics.md#Story 11.4 (FR-55)]
- [Source: wp-content/plugins/ink-core/src/Library/WinnerLinkage.php (server-block relationship-resolver pattern)]
- [Source: wp-content/plugins/ink-core/src/Content/Taxonomies.php:107-129 (genre/vaardigheid span works + training)]
- [Source: docs/ui-copy-translations.md:243 ("Leerhulpbronne" authored term)]
- [Source: wp-content/themes/ink-foundation/patterns/reading-gedig.php (embed point — after verwante-stukke)]

## Dev Agent Record

### Agent Model Used

claude-opus-4-8 (BMAD dev-story loop, Epic 11)

### Debug Log References

- `composer stan` runs sandbox-disabled (PHPStan TCP socket EPERM); OK.

### Completion Notes List

- **`ink/opleiding-verwant` cross-surfacing block** mirrors `Library\WinnerLinkage`: pure `queryArgs()` + side-effecting `relatedFor()` + pure `toHtml()`. Matches published `opleiding_artikel` sharing ANY of the work's `genre`/`vaardigheid` term ids via a `tax_query` (single clause for one taxonomy, `relation => OR` for both); excludes the current post; bounded to 3, newest-first. **No manual-linking field anywhere** — the match is purely shared taxonomy terms (FR-55 / Principle 8).
- **The surfaces-nothing invariant is enforced two ways:** `relatedFor()` returns `[]` before querying when the post carries no genre/vaardigheid term, and `toHtml([])` returns '' (no heading, no shell). A work sharing no term surfaces nothing; a challenge (which carries neither taxonomy) likewise surfaces nothing — consistent with the principle.
- **Embedded in the four work reading patterns** (gedig/storie/artikel after `verwante-stukke`; biblioteek after `post-content`) — theme embeds the ink-core block, no logic in the theme. The block reads terms generically, so the Epic-12 challenge single can embed the same block unchanged.
- **Heading "Verwante leerhulpbronne"** — glossary-consistent with the authored "Leerhulpbronne" term (the challenge-detail variant "Leerhulpbronne vir hierdie uitdaging"); copy-debt to ratify, not a new placeholder (copy:scan stays 6/6).
- **No new deptrac edge** (`Training → Content` already covers `PostTypes`/`Taxonomies`); conflation-clean.
- **Tests:** +8 (7 RelatedTraining + 1 embed guardrail). Suite 739→747.

### File List

- `wp-content/plugins/ink-core/src/Training/RelatedTraining.php` (NEW — `ink/opleiding-verwant` cross-surfacing block)
- `wp-content/plugins/ink-core/src/Training/Module.php` (MOD — register RelatedTraining)
- `wp-content/themes/ink-foundation/patterns/reading-gedig.php` (MOD — embed block)
- `wp-content/themes/ink-foundation/patterns/reading-storie.php` (MOD — embed block)
- `wp-content/themes/ink-foundation/patterns/reading-artikel.php` (MOD — embed block)
- `wp-content/themes/ink-foundation/patterns/reading-biblioteek.php` (MOD — embed block)
- `tests/Unit/Training/RelatedTrainingTest.php` (NEW)
- `tests/Unit/Training/OpleidingTemplateTest.php` (MOD — embed guardrail)
- `_bmad-output/implementation-artifacts/11-4-auto-cross-surfacing.md` (NEW — this story)
- `_bmad-output/implementation-artifacts/sprint-status.yaml` (MOD — 11.4 status)

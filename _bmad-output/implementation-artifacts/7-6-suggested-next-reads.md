---
baseline_commit: c845554
---

# Story 7.6: Suggested next reads

Status: review

## Story

As a reader,
I want suggested next reads,
so that I discover related work. (FR-31)

## Acceptance Criteria

**Given** shared taxonomy terms
**When** suggestions render
**Then** next reads are suggested by tone/form/topic/Gradering via taxonomy (no manual linking).

1. After a work, a "Verwante stukke" area suggests other published reads related by **shared taxonomy terms** — genre (tone/topic), the bydrae CPT (form), and `ster_gradering` (Gradering) — gathered automatically from the current post's terms. **No manual/editorial linking** (Principle 8).
2. Suggestions are other **published** bydraes (gedig/storie/artikel), excluding the current post, that share at least one term in any of the current post's taxonomies (OR across taxonomies). The `skryfwerk` holding bucket is excluded (not user-facing).
3. When the post has no shared-term matches, the area renders **nothing** (no empty section).
4. Each suggestion shows its type badge (Afrikaans, from the Terms registry), the title (linked) and author — escaped. Reads stay server-rendered (a server block, AD-7); the query uses `WP_Query`/`tax_query`, no REST.
5. Three-layer & conflation-clean: the block + query live in `ink-core` (Engagement), referencing only `Ink\Content\PostTypes` (the migration-load-bearing slug source) + WP core + the Terms registry — zero `Ink\Tiers`/`Ink\Entitlement`. Not entitlement-gated.

## Tasks / Subtasks

- [x] Task 1: `Ink\Engagement\SuggestedReads` server block (AC: all)
  - [x] `queryArgs(int $post_id, array $termIdsByTax, array $types, int $limit): array` — pure: builds the `WP_Query` args — `post_type`=$types, `post__not_in`=[post_id], `post_status`='publish', `posts_per_page`=$limit, `ignore_sticky_posts`, and a `tax_query` (relation OR) with one clause per taxonomy that has term ids (`field`=>`term_id`). Testable without WP.
  - [x] `render(): string` — collect the current post's term ids per taxonomy via `get_object_taxonomies` + `wp_get_post_terms`; if none → `''`; else `WP_Query( queryArgs(...) )` (types = `[PostTypes::GEDIG, PostTypes::STORIE, PostTypes::ARTIKEL]`), map results to cards `{title, permalink, type, author}`; `toHtml`. No results → `''`.
  - [x] `toHtml(array $cards): string` — pure: renders the "Verwante stukke" heading + a card per suggestion (type badge via `Terms::label`, linked escaped title, escaped author). Empty cards → `''`.
  - [x] `register()`/`registerBlock()` → `register_block_type('ink/verwante-stukke', ['render_callback'=>…])` on `init`.
- [x] Task 2: Wire + embed + style (AC: #1, #4)
  - [x] `Engagement\Module::register()` registers `SuggestedReads`. deptrac: add `Content` to the Engagement ruleset (done — mirrors Submission→Content).
  - [x] Embed `<!-- wp:ink/verwante-stukke /-->` in `reading-storie.php`, `reading-artikel.php`, `reading-gedig.php`, after the Gemeenskapsreaksies block (end of the reading column) — actually as its own full-width-ish related section consistent with archetype-c. Keep within the constrained reading group for v1.
  - [x] `.ink-verwante*` CSS in `theme.json` `styles.css` (tokens).
- [x] Task 3: Tests + gates (AC: all)
  - [x] `tests/Unit/Engagement/SuggestedReadsTest.php`: `queryArgs` builds the correct OR tax_query (one clause per non-empty taxonomy, `term_id` field), `post__not_in` the current post, the three bydrae types, publish status, limit; `toHtml` renders the heading + a badge/title/author per card and escapes; **empty cards → '' (no empty section, AC #3)** — non-vacuous (a populated fixture DOES render the heading).
  - [x] Extend `ReadingTemplatesTest`: each reading pattern embeds `wp:ink/verwante-stukke`.
  - [x] `composer test:unit` green, `cs`/`stan` clean, `copy:scan` no new debt, `deptrac` clean with the new Engagement→Content edge Allowed (no NEW violation beyond the 3 pre-existing).

## Dev Notes

- **Shared-taxonomy surfacing, not manual linking** [project-context.md #shared-taxonomy; Principle 8]: never build per-item editorial linking. Gather the current post's terms automatically (`get_object_taxonomies(get_post_type($id))` → `wp_get_post_terms`) and query others sharing them. genre = tone/topic, CPT = form, `ster_gradering` = Gradering (FR-31's four axes map to the post's taxonomies + its type).
- **Taxonomies** [Source: src/Content/Taxonomies.php:44-47]: `GENRE`, `VAARDIGHEID`, `UITDAGINGSRONDTE`, `STER_GRADERING`. `get_object_taxonomies` returns exactly those registered for the CPT, so the query stays generic (no taxonomy hardcoding) while the *post types* come from `PostTypes` constants (single-source).
- **Readable types** [Source: src/Content/PostTypes.php]: query `GEDIG/STORIE/ARTIKEL` only — exclude `skryfwerk` (migration bucket, not user-facing). Reference the constants (single-source); this is the new Engagement→Content edge.
- **Heading copy** [Source: wp-content/themes/ink-foundation/patterns/archetype-c-detail-reading.php:51]: the Epic-1 reading-page archetype modeled this exact section with the heading **"Verwante stukke"** (noting "Die werklike leessjablone is Epiek 7"). Reuse it verbatim — this story realizes that scaffolded section. (Copy-debt note: ratify "Verwante stukke" into the glossary on the next copy pass; it is design-scaffold Afrikaans, not invented here.)
- **Server-rendered** [Source: architecture.md AD-7 §3]: discovery/related surfaces are server-rendered via `WP_Query`/`pre_get_posts`, not REST. A render-only server block is the right shape (same as GedigBody/ResponsesList).
- **Block pattern** [Source: src/Engagement/SuggestedReads sibling blocks]: pure `queryArgs`/`toHtml` + thin `render`. Type badge label via `Terms::label( get_post_type() )` (I18n is uncovered — no deptrac edge).
- **Testing**: mock `__`/`esc_*`/`get_permalink`-style in `toHtml`; test `queryArgs` as a pure array assertion (the tax_query shape is the heart of "shared taxonomy terms, no manual linking").

### Project Structure Notes

- New ink-core: `src/Engagement/SuggestedReads.php`; MOD `src/Engagement/Module.php`, `deptrac.yaml` (Engagement → Content).
- New theme: MOD `patterns/reading-{storie,artikel,gedig}.php` (embed), `theme.json` (`.ink-verwante` styles).
- New tests: `SuggestedReadsTest`; MOD `ReadingTemplatesTest`.
- deptrac: Engagement → [Kernel, Content] after this story. No Entitlement/Tiers edge.

### References

- [Source: _bmad-output/planning-artifacts/epics.md#Story 7.6]
- [Source: _bmad-output/planning-artifacts/architecture.md#AD-7]
- [Source: wp-content/plugins/ink-core/src/Content/Taxonomies.php, PostTypes.php]
- [Source: wp-content/themes/ink-foundation/patterns/archetype-c-detail-reading.php]
- [Source: _bmad-output/project-context.md#shared-taxonomy, #three-layer, #conflation-rule]

## Dev Agent Record

### Agent Model Used

claude-opus-4-8 (BMAD dev-story loop, Epic 7)

### Debug Log References

- stan run outside the sandbox (parallel-worker TCP bind, as in prior stories). No code issues.

### Completion Notes List

- `ink/verwante-stukke` server block surfaces related reads automatically by shared taxonomy terms (genre/ster_gradering via `get_object_taxonomies` + `wp_get_post_terms` → OR `tax_query`) — no manual/editorial linking (Principle 8). Reads stay server-rendered (`WP_Query`, not REST — AD-7).
- Excludes the current post + the `skryfwerk` migration bucket; queries only gedig/storie/artikel via `PostTypes` constants (single-source → the new, Allowed Engagement→Content deptrac edge, mirroring Submission→Content). No empty section when nothing matches (`toHtml([])===''`, asserted).
- Heading reuses the Epic-1 archetype-c scaffold's "Verwante stukke" (this story realizes that modeled section) — not invented; copy-debt note to ratify it into the glossary on the next copy pass.
- Pure `queryArgs` (the OR tax_query shape — the heart of "shared taxonomy terms") + pure `toHtml`, both unit-tested; thin `render`. Conflation-clean (no Tiers/Entitlement).
- Tests 462→467 (+5); cs/stan clean; copy:scan no new debt; deptrac 3 pre-existing violations (0 new; Engagement→Content Allowed).

### File List

- `wp-content/plugins/ink-core/src/Engagement/SuggestedReads.php` (NEW — ink/verwante-stukke block)
- `wp-content/plugins/ink-core/src/Engagement/Module.php` (MOD — wire SuggestedReads)
- `deptrac.yaml` (MOD — Engagement → Content)
- `wp-content/themes/ink-foundation/patterns/reading-storie.php` (MOD — embed block)
- `wp-content/themes/ink-foundation/patterns/reading-artikel.php` (MOD — embed block)
- `wp-content/themes/ink-foundation/patterns/reading-gedig.php` (MOD — embed block)
- `wp-content/themes/ink-foundation/theme.json` (MOD — `.ink-verwante` styles)
- `tests/Unit/Engagement/SuggestedReadsTest.php` (NEW)
- `tests/Unit/Engagement/ReadingTemplatesTest.php` (MOD — block-embed guard)
- `_bmad-output/implementation-artifacts/7-6-suggested-next-reads.md` (NEW — this story)

---
baseline_commit: 9ba7466
---

# Story 8.4: Search

Status: review

<!-- Note: Validation is optional. Run validate-create-story for quality check before dev-story. -->

## Story

As a reader,
I want to search works and writers,
so that I can find specific content. (FR-35)

## Acceptance Criteria

**Given** the search
**When** I query
**Then** it searches works (title/theme) and skrywers (name/bio/genre)
**And** search is diacritic-insensitive (ê/ë/ô/î match base letters).

1. A search surface on Ontdek queries **works** (by title + thematic body) and **skrywers** (by name + bio + genre), returning both result groups for one query.
2. Search is **diacritic-insensitive**: a query for `reen` matches `reën`, `cafe` matches `café`, `Eugene` matches `Eugène`, and vice-versa (ê/ë/ô/î/etc. fold to their base letters), in BOTH directions (accented query ↔ unaccented content).
3. Implementation **leans on a normalized accent-stripped index, not a search plugin** (AD-7 — no SearchWP/Relevanssi; the `[BUILD]` fallback chosen for determinism given the cloned-DB collation is unverified): a folded (lowercased, diacritic-stripped) index is maintained on save — `_ink_soek_indeks` (post-meta: title + body) for works, `ink_skrywer_soek_indeks` (user-meta: name + bio + published-form labels) for skrywers. The query term is folded the same way and matched `LIKE`.
4. Works results are **published bydraes** (gedig/storie/artikel; `skryfwerk` excluded); skrywer results are writers (members with a first-publication). Both are **server-rendered** (`WP_Query` / `WP_User_Query`, no REST). Each result escapes its output.
5. An empty query renders the search form only (no results section); a query with no matches renders the authored empty-state line (no blank section).
6. **Three-layer & conflation-clean:** search lives in `ink-core` (`Ink\Discovery`), references only `PostTypes` + the existing skrywer denorm (`SkrywerIndex`) + WP core — **zero** `Ink\Tiers`/`Ink\Entitlement`. Not entitlement-gated (searching published work is open).

## Tasks / Subtasks

- [x] Task 1: Diacritic folding (`Ink\Discovery\Diacritics`) (AC: #2)
  - [x] `fold(string $text): string` — **pure**: `mb_strtolower` + `strtr` a comprehensive Afrikaans-relevant diacritic map (ê ë é è → e; î ï í ì → i; ô ö ó ò → o; û ü ú ù → u; â ä á à → a; and uppercase forms) to base letters, collapse whitespace. Unit-tested both directions. No WordPress dependency.
- [x] Task 2: Folded search index (`Ink\Discovery\SearchIndex`) (AC: #3)
  - [x] `WORKS_META = '_ink_soek_indeks'` (post), `SKRYWER_META = 'ink_skrywer_soek_indeks'` (user).
  - [x] `worksIndexFor(string $title, string $body): string` (pure) = `Diacritics::fold( $title . ' ' . wp_strip_all_tags($body) )` — assembled outside the hook so it is testable; the `save_post` hook (readable bydrae, skip autosave/revision) writes it to `WORKS_META`.
  - [x] `rebuildSkrywer(int $user_id): void` — assemble `Diacritics::fold( display_name + ' ' + bio + ' ' + published-form labels )` (forms from the `SkrywerIndex` flags → `Terms::label('skrywer_genre_*')`) and write `SKRYWER_META`. Hook `profile_update` + `user_register`, and on a bydrae publish (so a new form's "genre" enters the index). `skrywerIndexValue(string $name, string $bio, list<string> $genreLabels): string` is the pure core.
  - [x] Register in `Discovery\Module`.
- [x] Task 3: Search block (`Ink\Discovery\Search`) (AC: #1, #4, #5)
  - [x] `const BLOCK = 'ink/ontdek-soek'`; `QUERY_VAR = 'soek'`; result limits.
  - [x] `worksQueryArgs(string $folded, int $limit): array` (pure) — `post_type`=readable types, `post_status`=publish, a `meta_query` `LIKE` clause on `WORKS_META` with `'%' . $folded . '%'`, `posts_per_page`=$limit. `skrywersQueryArgs(string $folded, int $limit): array` (pure) — `WP_User_Query` args: `meta_query` `LIKE` on `SKRYWER_META`, `number`=$limit, `fields`='ID'. Both unit-tested for the LIKE shape + the folded term.
  - [x] `render(): string` — read `soek` (`sanitize_text_field`), fold it; when empty → form only; else run both queries, map to result rows (work: type badge/title/author; skrywer: name/gradering), `toHtml`.
  - [x] `toHtml(string $rawQuery, array $works, array $skrywers): string` (pure) — the search form (authored placeholder), then the two result groups (each with a heading) or the empty-state line when both are empty. Escapes everything; the form value is `esc_attr`'d.
- [x] Task 4: Theme surface + copy (AC: #1, #5)
  - [x] Embed `<!-- wp:ink/ontdek-soek /-->` at the top of `patterns/ontdek.php` (after archive-intro, before the tabs). Authored copy from `ui-copy-translations.md`: works placeholder "Vind stories, gedigte of skrywers...", empty state "Probeer 'n ander soekterm of blaai deur alle artikels." (line 195) — reuse verbatim; any genuinely new string is an authored `__()` source literal (copy-debt note). No AI Afrikaans.
  - [x] `.ink-ontdek-soek*` token styles in `theme.json`.
- [x] Task 5: Tests + gates (AC: all)
  - [x] `tests/Unit/Discovery/DiacriticsTest.php`: `fold` strips ê/ë/ô/î/ü/é/etc. to base, lowercases, both directions (`fold('reën')==='reen'`, `fold('REEN')===fold('reën')`); collapses whitespace; leaves plain ASCII unchanged (non-vacuous).
  - [x] `tests/Unit/Discovery/SearchIndexTest.php`: `worksIndexFor` folds title+body (tags stripped); `skrywerIndexValue` folds name+bio+genre labels; an accented work indexes to its folded form.
  - [x] `tests/Unit/Discovery/SearchTest.php`: `worksQueryArgs`/`skrywersQueryArgs` build the `LIKE %folded%` meta clause on the right key, readable types / writer fields, publish; `toHtml` renders the form (with the raw query in the input), both result groups when present, and the empty-state when both empty (non-vacuous).
  - [x] `composer test:unit` green; `composer stan` clean; `composer cs` 0 errors; `composer copy:scan` no new debt; `composer deptrac` clean (no NEW violation; Discovery edges unchanged from 8.3).

## Dev Notes

- **Diacritic-insensitive via a normalized index, not a plugin** [Source: architecture.md#AD-7 §3 + `[BUILD]`]: "Diacritic-insensitive search (FR-35) leans on DB collation … `[BUILD]`: verify the cloned brownfield DB's actual collation; if it does not accent-fold … fall back to a normalized accent-stripped index column maintained on save." The collation is unverified in this environment, so this story implements the deterministic fallback: fold on write + fold the query + `LIKE`. This guarantees AC #2 regardless of the live collation, and the index doubles as the bounded search scope (title/theme; name/bio/genre) — no SearchWP/Relevanssi (NFR-3).
- **Maintained on save** [mirrors the 8.2 ReactionTotalInit / 8.3 SkrywerIndex denorm pattern]: works index on `save_post` (skip autosave/revision, readable bydrae only); skrywer index on `profile_update`/`user_register` + on publish (a new form changes the writer's searchable "genre"). Keep the assembly pure (`worksIndexFor`, `skrywerIndexValue`) so it is unit-testable without WordPress; the hooks are thin glue.
- **`meta_query LIKE`** [WP]: WordPress wraps a `LIKE` compare value with `%` itself only when you pass the bare term — pass the explicit `'%' . $folded . '%'` with `'compare' => 'LIKE'` to be deterministic. Accept the `SlowDBQuery` advisory with a documented `phpcs:ignore` (bounded, paged discovery query — same precedent as 8.2/8.3).
- **"theme" = thematic body** [ui-copy line 127 "Soek volgens titel, tema of naam"]: works search covers title + body text (tags stripped); there is no separate "tema" field. Skrywer "genre" = the Afrikaans form labels (Digkuns/Prosa/Artikels) for the forms the writer has published (the 8.3 flags) — so a search for "digkuns" surfaces poets.
- **Reuse 8.3 surfaces**: `SkrywerIndex::formFlagKey()` to discover a writer's forms; `Terms::label('skrywer_genre_*')` for the genre words; the `ink-ontdek-*` card/escaping conventions.

### Project Structure Notes

- NEW ink-core: `src/Discovery/Diacritics.php`, `src/Discovery/SearchIndex.php`, `src/Discovery/Search.php`.
- MOD ink-core: `src/Discovery/Module.php` (wire SearchIndex + Search).
- MOD theme: `patterns/ontdek.php` (embed search block), `theme.json` (search styles).
- NEW tests: `DiacriticsTest`, `SearchIndexTest`, `SearchTest`; MOD `OntdekTemplateTest` (search embed guard).
- deptrac: no new edge (Discovery → [Kernel, Content, Engagement, Tiers] already covers it).
- Note (don't build): a global site-search/`search.php` integration (this is the scoped Ontdek search); collation verification + a possible switch to native `posts_search` once the live DB collation is confirmed accent-folding (Epic 18 / migration verification); a one-shot index backfill for migrated content (could ride the migration scripts).

### References

- [Source: _bmad-output/planning-artifacts/epics.md#Story 8.4]
- [Source: _bmad-output/planning-artifacts/architecture.md#AD-7 (diacritic search + [BUILD])]
- [Source: wp-content/plugins/ink-core/src/Discovery/SkrywerIndex.php, SkrywersTab.php, WorksArchive.php]
- [Source: wp-content/plugins/ink-core/src/I18n/Terms.php; docs/ui-copy-translations.md lines 126-129, 195]
- [Source: _bmad-output/project-context.md#three-layer, #conflation-rule, #afrikaans-first]

## Dev Agent Record

### Agent Model Used

claude-opus-4-8 (BMAD dev-story loop, Epic 8)

### Debug Log References

- `composer stan` runs outside the sandbox (parallel-worker TCP bind). One finding fixed: `SearchIndex`'s `@param \WP_Post $post` made PHPStan treat the stub's properties as always-set (`isset.property` ×5); changed to `@param object $post` (consistent with `SkrywerIndex`/`ReactionTotalInit`), keeping the duck-type `isset` guards valid.
- `Search::worksQueryArgs`/`skrywersQueryArgs` carry documented `phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query` (bounded folded-index LIKE — the AD-7 search substrate; same accepted class as 8.2/8.3).

### Completion Notes List

- **Diacritic-insensitive search via a normalized index** (AD-7's `[BUILD]` fallback, chosen for determinism since the cloned-DB collation is unverified): `Diacritics::fold()` lowercases + strips the Afrikaans diacritic set to base letters; both the stored index and the query term are folded, so `reën`↔`reen`, `café`↔`cafe`, `Eugène`↔`eugene` match in both directions — no SearchWP/Relevanssi (NFR-3).
- **`SearchIndex`** maintains the folded indexes on save: `_ink_soek_indeks` (post: title + tag-stripped body) on `save_post`; `ink_skrywer_soek_indeks` (user: name + bio + the Afrikaans labels of the writer's published forms) on `profile_update`/`user_register`/publish. Pure assembly (`worksIndexFor`/`skrywerIndexValue`) + thin hook glue.
- **`Search`** (`ink/ontdek-soek`) renders the search form + two server-rendered result groups (works via `WP_Query`, skrywers via `WP_User_Query`, both `LIKE` on the folded index) — empty query → form only; no matches → authored empty-state ("Probeer 'n ander soekterm…"). Embedded at the top of the Ontdek hub. Not entitlement-gated; conflation-clean (Tiers read for the skrywer Gradering label only).
- "theme" = the work's thematic body (title + content); skrywer "genre" = the form labels (Digkuns/Prosa/Artikels). All copy authored (ui-copy-translations.md) — placeholders + empty-state reused verbatim; "Soek" button label is an authored `__()` source literal (copy-debt to ratify). No AI Afrikaans.
- Tests 546→558 (+12); cs 0 errors; stan clean; copy:scan no new debt; deptrac 3 pre-existing violations (0 new; Discovery edges unchanged from 8.3).

### Review Notes (for the Epic-8 code review)

- **Index backfill for migrated content:** `_ink_soek_indeks` / `ink_skrywer_soek_indeks` are populated on the next save/profile-update/publish — pre-existing migrated works/writers are unsearchable until then. A one-shot backfill (fold existing title/body + name/bio) should ride the migration scripts (noted; Epic 16/migration).
- **Collation switch:** if the live DB collation is later confirmed to accent-fold, the index could be retired in favour of native `posts_search` — but the normalized index is the safe, deterministic default and also bounds the search scope. Revisit post-migration verification (Epic 18).
- **`render()` (live WP_Query/WP_User_Query) + the save/profile hooks** are exercised only at the pure level (fold/queryArgs/toHtml/index assembly); the live queries + hook firing land in the 18.8 integration/E2E layer.

### File List

- `wp-content/plugins/ink-core/src/Discovery/Diacritics.php` (NEW — accent folding)
- `wp-content/plugins/ink-core/src/Discovery/SearchIndex.php` (NEW — folded index maintenance)
- `wp-content/plugins/ink-core/src/Discovery/Search.php` (NEW — ink/ontdek-soek block)
- `wp-content/plugins/ink-core/src/Discovery/Module.php` (MOD — wire SearchIndex + Search)
- `wp-content/themes/ink-foundation/patterns/ontdek.php` (MOD — embed search block)
- `wp-content/themes/ink-foundation/theme.json` (MOD — search token styles)
- `tests/Unit/Discovery/DiacriticsTest.php` (NEW)
- `tests/Unit/Discovery/SearchIndexTest.php` (NEW)
- `tests/Unit/Discovery/SearchTest.php` (NEW)
- `tests/Unit/Discovery/OntdekTemplateTest.php` (MOD — search embed guard)
- `_bmad-output/implementation-artifacts/8-4-search.md` (NEW — this story)

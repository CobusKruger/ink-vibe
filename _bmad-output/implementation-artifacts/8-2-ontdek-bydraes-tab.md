---
baseline_commit: 2c8311c
---

# Story 8.2: Ontdek — bydraes tab

Status: review

<!-- Note: Validation is optional. Run validate-create-story for quality check before dev-story. -->

## Story

As a reader,
I want to browse and sort bydraes,
so that I can find work I like. (FR-33)

## Acceptance Criteria

**Given** the bydraes tab
**When** I filter/sort
**Then** I can filter by type (Gedigte/Stories/Artikels) and sort (Nuut / Opspraakwekkend / Mees geliefd).

1. The Ontdek works archive (`ink/ontdek-werke`, Story 8.1) gains a **type filter** — Alles (default) / Gedigte / Stories / Artikels — narrowing the listing to one bydrae CPT (or all three). A garbage/unknown type degrades to Alles.
2. The archive gains a **sort control** with three options: **Nuut** (default — newest first, by date), **Opspraakwekkend** (trending — a stored recomputed score, descending), **Mees geliefd** (most reactions — descending). A garbage/unknown sort degrades to Nuut. Date is the stable tiebreaker for the count sorts.
3. **Sort-driving counts are denormalized into indexed post-meta** (AD-7 — a JOINed COUNT against the reactions table is uncacheable): a published bydrae's total reaction count is maintained in `ink_reaksie_telling` **transactionally on every reaction write** (set/remove), and initialised to `0` when a bydrae is published — so a zero-reaction work still appears in the "Mees geliefd" ordering rather than being dropped by the meta join.
4. **"Opspraakwekkend" is a stored, recomputed score** `ink_trending_score` (AD-7), refreshed by an **Action Scheduler** recurring job (AD-6, group `ink`), ordered via meta — never computed live per request. The score is a pure function of the denormalized reaction total and the work's age (recency-weighted gravity), so a brand-new work with modest engagement can out-rank an old work with stale engagement.
5. The filter + sort are **server-rendered** (extended `WP_Query` driven by query vars, AD-7), reflected in the URL (shareable/bookmarkable), with the active type + sort visually marked. No REST for the listing.
6. **Three-layer & conflation-clean:** the filter/sort live in `ink-core` (`Ink\Discovery`); the reaction-total denormalization lives in `ink-core` (`Ink\Engagement`, which owns reactions). Discovery reads the reaction-total meta-key via the `Ink\Engagement\Api` facade (a real, non-circular Discovery→Engagement edge) and owns the trending meta + job. **Zero** `Ink\Tiers`/`Ink\Entitlement`. The tab is **not** entitlement-gated.

## Tasks / Subtasks

- [x] Task 1: Denormalized reaction total in `ink-core` Engagement (AC: #3, #6)
  - [x] `ReactionStore::TOTAL_META_KEY = 'ink_reaksie_telling'` (the single source for the denormalized per-work reaction total).
  - [x] `ReactionStore::syncTotal(int $post_id): int` — recompute `array_sum( self::countsForPost($post_id) )` and `update_post_meta($post_id, TOTAL_META_KEY, $total)`; return the total. Called at the end of `set()` AND `remove()` (transactional with the reaction write). Recompute-on-write (not incremental) keeps it correct under the UNIQUE-key upsert/toggle.
  - [x] Initialise the meta to `0` when a bydrae is published: an Engagement collaborator hooks `transition_post_status` (or `publish_{cpt}`) for the readable bydrae types and, if the meta is absent, sets it to `0` — so count-sorted `WP_Query` (meta join) includes zero-reaction works. Reuse `Readable::isBydrae()` for the type gate. Register it in `Engagement\Module`.
  - [x] `Engagement\Api::reactionTotalMetaKey(): string` → `ReactionStore::TOTAL_META_KEY` (the cross-module contract surface; Discovery reads this, never reaches into `ReactionStore` directly).
- [x] Task 2: Trending score + Action Scheduler job in `ink-core` Discovery (AC: #4, #6)
  - [x] `Ink\Discovery\TrendingScore`: `META_KEY = 'ink_trending_score'`; `compute(int $reactionTotal, int $ageDays): float` — **pure**, recency-weighted gravity (e.g. `($reactionTotal + 1) / pow($ageDays + 2, 1.5)`), unit-testable: newer + more-reacted ranks higher; monotonic in reactions for a fixed age; decays with age for a fixed reaction count. Clamp `$ageDays` to ≥ 0.
  - [x] `recomputeAll(): void` — iterate published bydraes (`WP_Query`, readable types, `fields=ids`, batched/`posts_per_page`-bounded), read `ink_reaksie_telling` (default 0) + age from `post_date_gmt`, write `META_KEY`. Also ensures the reaction-total meta exists (idempotent backfill safety).
  - [x] Schedule a **daily recurring** Action Scheduler action (`as_schedule_recurring_action`, group `ink`) on `init` when not already scheduled (`as_next_scheduled_action` guard; `function_exists` guards so it is a graceful no-op without Action Scheduler — mirrors `LifecycleEmails`). The action hook callback runs `recomputeAll()`. Unschedule on deactivation is acceptable to defer (note); the guard prevents duplicates.
  - [x] Register `TrendingScore` in `Discovery\Module`.
- [x] Task 3: Extend the works-archive query for type + sort (AC: #1, #2, #5)
  - [x] `WorksArchive`: `TYPE_VAR = 'werke_tipe'`, `SORT_VAR = 'werke_sorteer'`; sort constants `SORT_NUUT`/`SORT_OPSPRAAK`/`SORT_GELIEFD`; `allowedSorts(): list<string>`.
  - [x] Extend `queryArgs(int $paged, int $perPage, ?int $year, ?int $month, ?string $type = null, string $sort = self::SORT_NUUT): array` — **backward-compatible** (new params default, so 8.1 callers/tests are unaffected). `$type`: if it is one of `readableTypes()`, `post_type` = that single type; else all three. `$sort`: `nuut` → date DESC (8.1 behaviour); `mees_geliefd` → `orderby=meta_value_num`, `meta_key`=`Engagement\Api::reactionTotalMetaKey()`, `order=DESC`, secondary date DESC; `opspraakwekkend` → same shape on `TrendingScore::META_KEY`. Unknown sort → `nuut`. Pure + unit-tested.
  - [x] `render()` reads `werke_tipe` (`sanitize_key`) + `werke_sorteer` (`sanitize_key`) from the query var/GET (defensive, like 8.1's `requestInt`), passes them through; the `nav` context carries the active type + sort for the controls.
- [x] Task 4: Filter + sort UI in the block (AC: #1, #2, #5)
  - [x] `WorksArchive::controlsHtml(?string $activeType, string $activeSort): string` — **pure**: type-filter pills (Alles + one per readable type, label via `Terms::label`, `is-style-pill`, active pill marked) and a sort control (the three options, active marked), each a GET link via `add_query_arg`/`remove_query_arg` that preserves the other dimension + resets `werke_bladsy` to 1. Rendered above the list in `toHtml`. All escaped.
  - [x] Sort/type labels from the registry/authored copy: `Nuut`/`Opspraakwekkend`/`Mees geliefd` and the `Alles` filter are authored UI copy (`ui-copy-translations.md` lines ~119-149). Source controlled-vocabulary type labels (`Gedigte`/`Stories`/`Artikels`) via `Terms::label('{type}_plural')`. Any genuinely new string → authored `__('…','ink-core')` source literal + note as copy-debt to ratify (consistent with 8.1's nav-term note); prefer reuse of the ui-copy doc terms. No AI Afrikaans.
- [x] Task 5: Tests + gates (AC: all)
  - [x] `tests/Unit/Engagement/ReactionStoreTotalTest.php` (or extend an existing ReactionStore test): `syncTotal` writes the summed total to `TOTAL_META_KEY` and returns it (mock `countsForPost` rows + `update_post_meta`); `set`/`remove` invoke the sync (assert `update_post_meta` called with the key). Publish-init sets `0` for a bydrae and is a no-op for a non-bydrae / when already set (non-vacuous).
  - [x] `tests/Unit/Discovery/TrendingScoreTest.php`: `compute` is monotonic in reactions (fixed age), decays with age (fixed reactions), clamps negative age, and a newer modestly-reacted work out-ranks an old heavily-stale one at the documented inputs.
  - [x] `tests/Unit/Discovery/WorksArchiveTest.php` (extend): type filter narrows `post_type` to the one CPT (and garbage → all three); `mees_geliefd`/`opspraakwekkend` set `orderby=meta_value_num` + the correct `meta_key` + DESC + date tiebreak; unknown sort → `nuut` (date). `controlsHtml` renders the type pills + the three sort options, marks the active ones, preserves the other dimension in the hrefs, and resets the page (non-vacuous: a populated control set, active state actually marked).
  - [x] `composer test:unit` green; `composer stan` clean; `composer cs` 0 errors; `composer copy:scan` no new debt; `composer deptrac` clean with the new **Discovery→Engagement** edge Allowed (no NEW violation beyond the pre-existing baseline).

## Dev Notes

- **Denormalized sort counts (the core of this story)** [Source: architecture.md#AD-7 §3]: "Sort-driving counts are denormalized from day one into indexed post-meta, updated transactionally on reaction/read write, so 'Mees geliefd' / 'Meeste gelees' are cheap `WP_Query` orderby (a JOINed COUNT against the reactions table is expensive and uncacheable)." This story builds that substrate for reactions. "Opspraakwekkend (trending) is a stored, recomputed score (`ink_trending_score`) refreshed on an Action Scheduler job, ordered via meta — not computed live."
- **Reaction write path** [Source: src/Engagement/ReactionStore.php set()/remove(), countsForPost(); ReactionController.php]: all reaction mutations funnel through `ReactionStore::set()`/`remove()` (the REST controller + tests call these). Hook the denorm there so EVERY path stays consistent (cross-story durability — project-context). `countsForPost()` already aggregates per reaction; `array_sum` is the total.
- **Zero-meta-drop trap** [WP `orderby=meta_value_num` + `meta_key`]: posts WITHOUT the meta key are excluded from a meta-ordered query (implicit join). Initialise `ink_reaksie_telling=0` at publish so zero-reaction works are not silently dropped from "Mees geliefd". The trending `recomputeAll()` also ensures the meta exists (belt-and-braces backfill for migrated content).
- **Action Scheduler pattern** [Source: src/Entitlement/LifecycleEmails.php:365-385,781-786]: `function_exists('as_schedule_recurring_action')`/`as_next_scheduled_action` guards (graceful no-op without WC/Action Scheduler), group constant (`'ink'`), hook constant. Use `as_schedule_recurring_action` for the daily refresh; guard against duplicate scheduling with `as_next_scheduled_action`.
- **Backward-compatible queryArgs** [Source: src/Discovery/WorksArchive.php (8.1)]: 8.1's `queryArgs(int $paged,int $perPage,?int $year,?int $month)` and its tests must keep passing — add the `$type`/`$sort` params with defaults at the end. The `nuut` branch must produce IDENTICAL args to 8.1.
- **Discovery→Engagement coupling** [deptrac]: Discovery reads the reaction-total meta-key through `Engagement\Api::reactionTotalMetaKey()` (facade, single-source — never duplicate the `'ink_reaksie_telling'` literal in Discovery). Add `Engagement` to the Discovery ruleset (non-circular: Engagement does not depend on Discovery). The trending meta key is Discovery-owned (`TrendingScore::META_KEY`).
- **Type labels** [Source: src/I18n/Terms.php]: `gedig_plural`=Gedigte, `storie_plural`=Stories, `artikel_plural`=Artikels (all exist). Sort labels Nuut/Opspraakwekkend/Mees geliefd + Alles are authored UI copy (ui-copy-translations.md).
- **Server-rendered, URL-reflected** [Source: AD-7 §2/§3]: filter/sort via query vars in the URL (shareable). The Interactivity-API tab behaviour is an enhancement, not required for this story — GET-link controls are the server-rendered baseline.

### Project Structure Notes

- MOD ink-core: `src/Engagement/ReactionStore.php` (TOTAL_META_KEY + syncTotal + set/remove hook-in), `src/Engagement/Api.php` (reactionTotalMetaKey), `src/Engagement/Module.php` (publish-init collaborator); `src/Discovery/WorksArchive.php` (type/sort), `src/Discovery/Module.php` (TrendingScore), `deptrac.yaml` (Discovery → Engagement).
- NEW ink-core: `src/Discovery/TrendingScore.php`; the publish-init collaborator (e.g. `src/Engagement/ReactionTotalInit.php`).
- MOD theme: `patterns/ontdek.php` only if controls move there (they live in the block — likely no theme change beyond what 8.1 shipped).
- NEW tests: `TrendingScoreTest`; MOD `WorksArchiveTest`, a ReactionStore total test.
- deptrac after this story: Discovery → [Kernel, Content, Engagement]. No Entitlement/Tiers edge (conflation-clean).
- Note (don't build): read-count denormalization (`_ink_read_count`) for "Meeste gelees" is Story 8.3's skrywers sort; this story builds only the reaction-total + trending substrate.

### References

- [Source: _bmad-output/planning-artifacts/epics.md#Story 8.2]
- [Source: _bmad-output/planning-artifacts/architecture.md#AD-7]
- [Source: wp-content/plugins/ink-core/src/Engagement/ReactionStore.php, ReactionController.php, Api.php]
- [Source: wp-content/plugins/ink-core/src/Discovery/WorksArchive.php (Story 8.1)]
- [Source: wp-content/plugins/ink-core/src/Entitlement/LifecycleEmails.php (Action Scheduler pattern)]
- [Source: wp-content/plugins/ink-core/src/I18n/Terms.php; docs/ui-copy-translations.md]
- [Source: _bmad-output/project-context.md#three-layer, #conflation-rule, #afrikaans-first]

## Dev Agent Record

### Agent Model Used

claude-opus-4-8 (BMAD dev-story loop, Epic 8)

### Debug Log References

- `composer stan` runs outside the sandbox (PHPStan parallel-worker TCP bind blocked under the sandbox), as in prior stories. "No errors" (95 files).
- `WorksArchive` carries two documented `phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key` on the count-sort `meta_key`s — a known false positive (AD-7 denormalizes into INDEXED post-meta precisely so this orderby is cheap; not a live COUNT join). Same tolerated class as the pre-existing `SuggestedReads` tax_query / `ResponseStore` meta_query warnings (which remain, untouched, from the Epic-7 baseline).

### Completion Notes List

- **Denormalized "Mees geliefd" substrate (Engagement):** every reaction write (`ReactionStore::set`/`remove`) now syncs a per-work total into `ink_reaksie_telling` via `syncTotal()` (recompute-on-write — correct under the UNIQUE-key toggle). `ReactionTotalInit` seeds the meta to `0` on a bydrae's publish so zero-reaction works are not dropped by the meta-ordered query's implicit join. The key is exposed cross-module via `Engagement\Api::reactionTotalMetaKey()`.
- **"Opspraakwekkend" trending (Discovery):** `TrendingScore` stores `ink_trending_score`, recomputed by a daily Action Scheduler recurring action (group `ink`, guarded — graceful no-op without Action Scheduler). The score is a pure recency-weighted gravity (`(reactions+1)/(age_days+2)^1.5`): a fresh, modestly-reacted work out-ranks an old, stale one. `recomputeAll()` batches over published bydraes and seeds the reaction-total meta for migrated content.
- **Filter + sort (Discovery):** `WorksArchive::queryArgs` gained backward-compatible `$type`/`$sort` params — type narrows `post_type` (garbage → all three); sorts map to `orderby=meta_value_num` on the denormalized/stored meta (DESC, date tiebreak) or plain date for `nuut` (unknown → nuut). `controlsHtml` renders the type pills (Alles + Gedigte/Stories/Artikels) + the three sort options as GET links that preserve the other dimension and reset the page, marking the active control (`is-active` + `aria-current`). Server-rendered, URL-reflected, shareable (AD-7). Not entitlement-gated.
- Conflation-clean: Discovery reads only the Engagement Api facade key + Content slugs; zero Tiers/Entitlement. New Discovery→Engagement deptrac edge (Allowed, non-circular).
- Copy: Nuut/Opspraakwekkend/Mees geliefd/Alles are authored `__()` source literals (ui-copy-translations.md) — copy-debt to ratify into the glossary on the next pass (consistent with the Epic-7 engagement copy-debt note). No AI Afrikaans.
- Tests 507→526 (+19); cs 0 errors; stan clean; copy:scan no new debt; deptrac 3 pre-existing violations (0 new).

### Review Notes (for the Epic-8 code review)

- **Trending freshness before the first scheduled run:** `ink_trending_score` is empty until the daily job first runs; "Opspraakwekkend" orders zero/missing-score works last until then. "Mees geliefd" works immediately (maintained live on every reaction write + seeded at publish). An on-activation one-shot `recomputeAll()` could prime it — deferred (the daily job + publish-seed cover steady state).
- **`recomputeAll()` is unit-tested only at the pure `compute`/`ageInDays` level** (the WP_Query batch loop + Action Scheduler scheduling are thin WP glue, exercised in the 18.8 integration/E2E layer — consistent with the LifecycleEmails precedent).
- **No deactivation unschedule** of the recurring action (the `as_next_scheduled_action` guard prevents duplicates on reactivation); fold a tidy `as_unschedule_all_actions` into the Discovery deactivation path if/when one is added.

### File List

- `wp-content/plugins/ink-core/src/Engagement/ReactionStore.php` (MOD — TOTAL_META_KEY + syncTotal + set/remove sync)
- `wp-content/plugins/ink-core/src/Engagement/ReactionTotalInit.php` (NEW — seed denorm total at publish)
- `wp-content/plugins/ink-core/src/Engagement/Api.php` (MOD — reactionTotalMetaKey facade)
- `wp-content/plugins/ink-core/src/Engagement/Module.php` (MOD — wire ReactionTotalInit)
- `wp-content/plugins/ink-core/src/Discovery/TrendingScore.php` (NEW — trending meta + Action Scheduler job)
- `wp-content/plugins/ink-core/src/Discovery/Module.php` (MOD — wire TrendingScore)
- `wp-content/plugins/ink-core/src/Discovery/WorksArchive.php` (MOD — type filter + sorts + controls)
- `deptrac.yaml` (MOD — Discovery → Engagement)
- `wp-content/themes/ink-foundation/theme.json` (MOD — control/filter/sort token styles)
- `tests/Unit/Engagement/ReactionStoreTest.php` (MOD — syncTotal + set/remove sync)
- `tests/Unit/Engagement/ReactionTotalInitTest.php` (NEW)
- `tests/Unit/Discovery/TrendingScoreTest.php` (NEW)
- `tests/Unit/Discovery/WorksArchiveTest.php` (MOD — type/sort/controls)
- `_bmad-output/implementation-artifacts/8-2-ontdek-bydraes-tab.md` (NEW — this story)

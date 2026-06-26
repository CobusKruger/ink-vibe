---
baseline_commit: 66e7002
---

# Story 8.3: Ontdek — skrywers tab

Status: done

<!-- Note: Validation is optional. Run validate-create-story for quality check before dev-story. -->

### Review Findings (Epic 8 code review, 2026-06-26)

- [x] [Review][Patch] `SkrywerIndex::onTransition` stored `0` (epoch) when `post_date_gmt` was missing/invalid, mis-ranking the writer forever in recency sorts — now falls back to `time()`. [SkrywerIndex.php]
- [x] [Review][Patch] `ReadCount::maybeCount` counted preview requests — added `is_preview()` to the guard. [ReadCount.php]
- [x] [Review][Defer] Skrywers tab + "Meeste gelees" drop pre-existing/migrated writers lacking the denorm meta — one-shot backfill owned by the scripted migration (Epic 16). See deferred-work.md.
- [x] [Review][Defer] Read-count increment is a non-atomic read-modify-write (lost updates) — owned by the Story-18.x read-count hardening (with bot/dedup). See deferred-work.md.
- [x] [Review][Dismiss] `publish→publish` edit re-writes `laaste_publikasie` to the same `post_date` (idempotent; "recently active = recently published" is the documented definition).

## Story

As a reader,
I want to browse and sort skrywers,
so that I can find writers to follow. (FR-34)

## Acceptance Criteria

**Given** the skrywers tab
**When** I filter/sort
**Then** I can filter by genre (Digkuns/Prosa/Artikels) and sort (Meeste gelees / Nuwe stemme).

1. The Ontdek hub gains a **Skrywers tab** (the 8.1 placeholder tab becomes functional): a **server-rendered** `ink/ontdek-skrywers` block backed by **`WP_User_Query`** (AD-7 — core Query Loop cannot query users), listing the writers (members who have published at least one bydrae).
2. The tab filters by **genre**: **Digkuns** (poets — published a gedig), **Prosa** (prose — published a storie), **Artikels** (published an artikel); plus an unfiltered default. "genre via the writer's published works" (AD-7) — not per-item editorial linking (Principle 8). A garbage/unknown genre degrades to unfiltered.
3. The tab sorts by **Meeste gelees** (most-read — descending) and **Nuwe stemme** (new voices — most-recent first publication, descending). A garbage/unknown sort degrades to a stable default.
4. **The sort/filter substrate is denormalized per writer (AD-7 — `WP_User_Query` orders/filters on user-meta):** on a bydrae's publish, the author's denormalized fields are maintained — a per-form flag (`ink_skrywer_het_gedig`/`_storie`/`_artikel` = `1`), the first-publication timestamp (`ink_skrywer_eerste_publikasie`, set once), and a read-total seed (`ink_skrywer_gelees_telling`). A **read count** is maintained on each single-bydrae view: the post's `_ink_read_count` (reused for discovery sort + analytics, AD-7) and the author's `ink_skrywer_gelees_telling` are incremented.
5. Each skrywer renders as a card: display name (linked), the writer's **Gradering** (tier label, read from the Tiers Api — `tier from user-meta`, AD-7), a short bio, and a follow placeholder (the follow toggle is Epic 9). All escaped.
6. The filter + sort are **server-rendered** (query vars in the URL, shareable), with the active genre + sort marked; no REST for the listing.
7. **Three-layer & conflation-clean:** the tab + denormalization live in `ink-core` (`Ink\Discovery`); it reads the writer Gradering via the `Ink\Tiers\Api` facade (a real Discovery→Tiers edge — display only; **never** gates discovery on tier, and carries zero `Ink\Entitlement`). Not entitlement-gated.
8. **Deferred Epic-2 item closed (term-image consume point):** `Ink\Content\TermImages::imageId()` (surfaced via `Content\Api::termImageId()`) now validates the stored attachment with `wp_attachment_is_image()`, returning `0` for a missing / non-image / deleted ID — so a stale ID from the unchecked 2.5 save can never render a broken `<img>` on a discovery (or training) surface.

## Tasks / Subtasks

- [x] Task 1: Per-writer denormalization on publish (`Ink\Discovery\SkrywerIndex`) (AC: #4)
  - [x] Hook `transition_post_status`; on a readable bydrae reaching `publish` (gate via the bydrae types), for the author: set `ink_skrywer_het_{form}` = `1` for that form; set `ink_skrywer_eerste_publikasie` to the publish GMT unix timestamp **only if absent** (first publication, never moved later); seed `ink_skrywer_gelees_telling` = `0` if absent. Meta-key constants are the single source.
  - [x] `formFlagKey(string $type): string` (pure) maps `gedig`/`storie`/`artikel` → the flag key; `genreToType(string $genre): ?string` maps `digkuns`→gedig, `prosa`→storie, `artikels`→artikel (else null). Register in `Discovery\Module`.
- [x] Task 2: Read-count tracking (`Ink\Discovery\ReadCount`) (AC: #4)
  - [x] Hook `wp` (or `template_redirect`); when the main query `is_singular()` a readable bydrae (not admin, not feed, not REST), increment the post's `_ink_read_count` and the author's `ink_skrywer_gelees_telling`. `incrementPost(int $post_id): void` + `incrementAuthor(int $author_id): void` thin helpers over `get/update_post_meta`/`get/update_user_meta`. Per-request single increment; bot/dedup throttle deferred (note). Register in `Discovery\Module`.
  - [x] `READ_COUNT_META = '_ink_read_count'` (post) — the AD-7 read-count single source.
- [x] Task 3: Skrywers query + block (`Ink\Discovery\SkrywersTab`) (AC: #1, #2, #3, #5, #6, #7)
  - [x] `const BLOCK = 'ink/ontdek-skrywers'`; `PER_PAGE`; `GENRE_VAR='skrywer_genre'`, `SORT_VAR='skrywer_sorteer'`, `PAGED_VAR='skrywer_bladsy'`; sort constants `SORT_GELEES='meeste_gelees'`, `SORT_NUWE='nuwe_stemme'`; `allowedGenres()`/`allowedSorts()`.
  - [x] `queryArgs(?string $genre, string $sort, int $paged, int $perPage): array` — **pure**, builds the `WP_User_Query` args. Always a `meta_query` `writer` clause (`EXISTS` on `ink_skrywer_eerste_publikasie`) so only writers appear. Genre filter → add the form-flag clause (`key`=`SkrywerIndex::formFlagKey(genreToType)`, `value`='1'). Sort: `nuwe_stemme` → named NUMERIC clause on `eerste_publikasie`, `orderby` DESC; `meeste_gelees` → named NUMERIC clause on `gelees_telling`, `orderby` DESC. Unknown sort → `nuwe_stemme`. `number`/`paged` for paging; `fields`='ID'.
  - [x] `render(): string` — read `skrywer_genre`/`skrywer_sorteer`/`skrywer_bladsy` defensively (sanitise + allowlist), run `new \WP_User_Query(...)`, map ids to cards `{name, profile_url, gradering, bio}` (Gradering label via `Tiers\Api::forUser()` → `Terms::label($tier->value)`; bio via `get_the_author_meta('description', $id)`; profile via `get_author_posts_url`), then `toHtml`.
  - [x] `toHtml(array $cards, array $nav): string` + `controlsHtml(?string $activeGenre, string $activeSort): string` — **pure**: genre pills (Almal/Digkuns/Prosa/Artikels) + sort control (Meeste gelees / Nuwe stemme) as state-preserving GET links with the active marked (mirrors `WorksArchive::controlsHtml`); skrywer cards (`is-style-card`); empty-state line; pagination. All escaped.
- [x] Task 4: Genre/sort labels + Skrywers-tab theme surface (AC: #2, #3, #6)
  - [x] Add `Terms` registry keys for the genre-filter labels: `skrywer_genre_digkuns`→"Digkuns", `skrywer_genre_prosa`→"Prosa", `skrywer_genre_artikels`→"Artikels" (authored, ui-copy-translations.md). Sort labels Meeste gelees / Nuwe stemme + the "Almal" default are authored `__()` source copy (copy-debt to ratify; no AI Afrikaans).
  - [x] `patterns/ontdek.php`: make the Skrywers tab pill link to the skrywers section + embed `<!-- wp:ink/ontdek-skrywers /-->` in a `#skrywers` section (the Bydraes section stays from 8.1/8.2). Both tabs visible; the block renders its own genre/sort controls.
- [x] Task 5: Close the deferred 2.5 term-image validation (AC: #8)
  - [x] `Content\TermImages::imageId()`: return the stored id only when `$id > 0 && wp_attachment_is_image( $id )`, else `0`. Update the docblock. (Consume-point validation — protects every `Content\Api::termImageId()` caller.)
- [x] Task 6: Tests + gates (AC: all)
  - [x] `tests/Unit/Discovery/SkrywerIndexTest.php`: publish of each form sets its flag + first-publication (once — a later publish does NOT move it) + seeds the read-total; non-bydrae/non-publish is a no-op; `genreToType`/`formFlagKey` mappings (incl. unknown→null), non-vacuous.
  - [x] `tests/Unit/Discovery/ReadCountTest.php`: `incrementPost`/`incrementAuthor` add 1 to the respective meta (mock get/update); the guard skips non-singular / non-bydrae / admin (drive the real branch so the guard can fail).
  - [x] `tests/Unit/Discovery/SkrywersTabTest.php`: `queryArgs` always carries the writer-EXISTS clause; genre filter adds the correct form-flag clause (garbage genre → no form clause); `meeste_gelees`/`nuwe_stemme` order by the correct NUMERIC meta DESC; unknown sort → nuwe_stemme. `controlsHtml` renders the genre pills + both sorts, marks the active (non-vacuous). `toHtml` renders a card per skrywer (name/gradering/bio) + empty-state; escapes.
  - [x] `tests/Unit/Content/TermImagesTest.php` (extend): `imageId` returns the id for a valid image attachment, `0` for a non-image / missing id (non-vacuous — a valid id DOES return).
  - [x] `composer test:unit` green; `composer stan` clean; `composer cs` 0 errors; `composer copy:scan` no new debt; `composer deptrac` clean with the new **Discovery→Tiers** edge Allowed (no NEW violation beyond the pre-existing baseline).

## Dev Notes

- **Skrywers tab is server-rendered `WP_User_Query`** [Source: architecture.md#AD-7 §3]: "Skrywers tab is server-rendered (custom block backed by `WP_User_Query` — tier from user-meta, genre via the writer's published works), because Query Loop cannot query users." "Nuwe stemme = writers by first-publish recency."
- **The `get_users`/`WP_User_Query` meta pattern** [Source: src/Tiers/Api.php:285-308 usersByGrade]: filter by user-meta with `fields=>'ID'`, accept the `SlowDBQuery` advisory with a documented `phpcs:ignore` (the result set is discovery-scoped + paged). Mirror this house style; the meta-ordered query needs the meta to EXIST, hence the publish-time seeds (Task 1) so writers are never dropped.
- **Denormalization, not live aggregation** [AD-7]: `WP_User_Query` cannot sum a writer's post read-counts at query time, so the per-writer totals/flags/first-publish are maintained on publish + on read into user-meta. `_ink_read_count` is the post-level read count "reused for both discovery sort + analytics."
- **Read the Gradering via the Tiers facade** [Source: src/Tiers/Api.php:48 forUser; project-context 2.3 deferral "read tier via a typed accessor, not raw get_user_meta"]: `Tiers\Api::forUser($id)` returns the default-safe `Tier`; label via `Terms::label($tier->value)`. This is the sanctioned accessor — adds a Discovery→Tiers edge (display-only; conflation-safe — Discovery never gates on tier, and never touches Entitlement).
- **Genre = form** [epics.md#Story 8.3; ui-copy]: Digkuns↔gedig, Prosa↔storie, Artikels↔artikel. The skrywers-tab "genre" is the FORM of the writer's published works, sourced from the publish-time form flags — NOT the `genre` taxonomy terms and NOT per-item editorial linking.
- **`controlsHtml` mirrors 8.2** [Source: src/Discovery/WorksArchive.php controlsHtml/pill]: same GET-link, state-preserving, active-marked pattern; reuse the shape.
- **Term-image validation** [Source: deferred-work.md "Story 2.5"; src/Content/TermImages.php:78 imageId, Content/Api.php:80]: the 2.5 save `absint`s any positive integer; validate at the consume point with `wp_attachment_is_image()` so a stale/non-image id renders nothing instead of a broken image.
- **Read-count throttle** is deliberately minimal for launch (per-request increment on a singular bydrae view, excluding admin/feed/REST); bot-filtering + per-user/session dedup are an analytics-hardening concern (note for Epic 18 / the analytics provider story 18.9).

### Project Structure Notes

- NEW ink-core: `src/Discovery/SkrywerIndex.php`, `src/Discovery/ReadCount.php`, `src/Discovery/SkrywersTab.php`.
- MOD ink-core: `src/Discovery/Module.php` (wire the three), `src/I18n/Terms.php` (genre-filter labels), `src/Content/TermImages.php` (image-validity), `deptrac.yaml` (Discovery → Tiers).
- MOD theme: `patterns/ontdek.php` (activate Skrywers tab + embed the block), `theme.json` (skrywer-card/control styles).
- NEW tests: `SkrywerIndexTest`, `ReadCountTest`, `SkrywersTabTest`; MOD `TermImagesTest`.
- deptrac after this story: Discovery → [Kernel, Content, Engagement, Tiers]. No Entitlement edge (conflation-clean).
- Note (don't build): the follow toggle (Epic 9); the real Skrywerprofiel page (Epic 9) — the card links to the WP author URL for now; bot/dedup read-count throttle (Epic 18).

### References

- [Source: _bmad-output/planning-artifacts/epics.md#Story 8.3]
- [Source: _bmad-output/planning-artifacts/architecture.md#AD-7]
- [Source: wp-content/plugins/ink-core/src/Tiers/Api.php (usersByGrade, forUser)]
- [Source: wp-content/plugins/ink-core/src/Discovery/WorksArchive.php (controls pattern), TrendingScore.php]
- [Source: wp-content/plugins/ink-core/src/Content/TermImages.php, Content/Api.php]
- [Source: _bmad-output/implementation-artifacts/deferred-work.md#Story 2.5]
- [Source: _bmad-output/project-context.md#three-layer, #conflation-rule, #shared-taxonomy, #afrikaans-first]

## Dev Agent Record

### Agent Model Used

claude-opus-4-8 (BMAD dev-story loop, Epic 8)

### Debug Log References

- `composer stan` runs outside the sandbox (parallel-worker TCP bind). One real finding fixed: a redundant `self::PER_PAGE > 0` guard (positive constant — `greater.alwaysTrue`) removed from `SkrywersTab::render()`.
- **Cross-test leak fixed (harness robustness):** `ReadCountTest` is the first test (alphabetically before `Entitlement`) to stub `is_admin`. Brain Monkey cannot un-define a function, so `function_exists('is_admin')` then returns true process-wide, which made `StorefrontSuppression::isStorefrontRequest()`'s `function_exists('is_admin') && is_admin()` guard call an unmocked `is_admin` → 5 `StorefrontSuppressionTest` failures. Hardened that test's `beforeEach` to stub `is_admin` explicitly (false). `StorefrontSuppression` production code unchanged; the fix removes a latent ordering fragility any future test could trigger.
- `SkrywersTab::queryArgs` carries a documented `phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query` (discovery-scoped, paged `WP_User_Query` on indexed denorm meta — AD-7; same accepted class as `Tiers\Api::usersByGrade`).

### Completion Notes List

- **Skrywers tab** (`ink/ontdek-skrywers`): a server-rendered `WP_User_Query` over writers, filterable by genre (Digkuns/Prosa/Artikels = the FORM of their published work) and sortable by Meeste gelees / Nuwe stemme. Mirrors the 8.2 controls (state-preserving GET links, active marked). Cards show name (→ author URL), Gradering (via the `Tiers\Api` facade — display only), bio. Not entitlement-gated.
- **Denormalized per-writer substrate** (`SkrywerIndex`, on publish): per-form "has published" flags (the genre filter), `ink_skrywer_eerste_publikasie` set once (Nuwe stemme), `ink_skrywer_gelees_telling` seeded 0 (so the meta-ordered "Meeste gelees" query keeps zero-read writers). `WP_User_Query` can only filter/order on user-meta — hence the denorm (AD-7).
- **Read-count tracking** (`ReadCount`, on `wp`): increments the post's `_ink_read_count` + the author's read total on each single readable-bydrae front-end view (skips admin/feed/REST/non-singular). Bot/dedup throttle deferred to Epic 18 (noted).
- **Deferred 2.5 closed:** `TermImages::imageId()` now validates with `wp_attachment_is_image()` at the consume point — a stale/non-image id renders nothing instead of a broken `<img>`. Both Epic-2 review deferrals assigned to Epic 8 (the 2.2 slug single-source in 8.1, this 2.5 term-image guard in 8.3) are now closed.
- Conflation-clean: new Discovery→Tiers edge is display-only (never gates discovery on tier); zero Entitlement. Genre/sort labels authored (Digkuns/Prosa/Artikels in the Terms registry; Meeste gelees/Nuwe stemme/Almal as `__()` source) — copy-debt to ratify; no AI Afrikaans.
- Tests 526→546 (+20); cs 0 errors; stan clean; copy:scan no new debt; deptrac 3 pre-existing violations (0 new).

### Review Notes (for the Epic-8 code review)

- **Read-count is per-request, unthrottled** — bots/refreshes inflate it; per-user/session dedup + bot-filtering are an analytics-hardening concern (Epic 18 / 18.9). The skrywers "Meeste gelees" order is therefore directional, not audited, at launch.
- **`render()` (WP_User_Query) + the `wp`/`transition_post_status` hooks are exercised only at the pure level** (queryArgs/toHtml/controls + increment/onTransition helpers); the live query + hook firing land in the 18.8 integration/E2E layer (LifecycleEmails precedent).
- **Migrated writers** have no denorm fields until they next publish; a one-shot backfill (set first-publish/flags from existing posts) could prime them — deferred (note); the trending `recomputeAll` already seeds reaction totals, a parallel skrywer backfill could ride the migration scripts.

### File List

- `wp-content/plugins/ink-core/src/Discovery/SkrywerIndex.php` (NEW — per-writer denorm on publish)
- `wp-content/plugins/ink-core/src/Discovery/ReadCount.php` (NEW — read-count tracking)
- `wp-content/plugins/ink-core/src/Discovery/SkrywersTab.php` (NEW — ink/ontdek-skrywers block)
- `wp-content/plugins/ink-core/src/Discovery/Module.php` (MOD — wire the three)
- `wp-content/plugins/ink-core/src/I18n/Terms.php` (MOD — Digkuns/Prosa/Artikels genre-filter labels)
- `wp-content/plugins/ink-core/src/Content/TermImages.php` (MOD — imageId wp_attachment_is_image guard, closes 2.5)
- `deptrac.yaml` (MOD — Discovery → Tiers)
- `wp-content/themes/ink-foundation/patterns/ontdek.php` (MOD — activate Skrywers tab + embed block)
- `wp-content/themes/ink-foundation/theme.json` (MOD — skrywer-card/control token styles)
- `tests/Unit/Discovery/SkrywerIndexTest.php` (NEW)
- `tests/Unit/Discovery/ReadCountTest.php` (NEW)
- `tests/Unit/Discovery/SkrywersTabTest.php` (NEW)
- `tests/Unit/Discovery/OntdekTemplateTest.php` (MOD — skrywers block embed guard)
- `tests/Unit/Content/TermImagesTest.php` (MOD — imageId validation)
- `tests/Unit/Entitlement/StorefrontSuppressionTest.php` (MOD — harden is_admin stub against cross-test pollution)
- `_bmad-output/implementation-artifacts/8-3-ontdek-skrywers-tab.md` (NEW — this story)

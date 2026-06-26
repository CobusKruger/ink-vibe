---
baseline_commit: 6223d0d
---

# Story 8.5: Discovery surfaces

Status: review

<!-- Note: Validation is optional. Run validate-create-story for quality check before dev-story. -->

## Story

As a reader,
I want personalised discovery surfaces,
so that relevant writers/works surface to me. (FR-36)

## Acceptance Criteria

**Given** custom discovery logic
**When** surfaces render
**Then** "writers like this", new voices, recently active, writers in your Gradering, and unread-by-you appear (custom, not default community screens).

1. A **custom** discovery-surfaces component renders on the Ontdek hub — INK's own logic, NOT a default BuddyPress/community directory screen.
2. **New voices** — writers by most-recent FIRST publication (reuses the 8.3 `ink_skrywer_eerste_publikasie` denorm).
3. **Recently active** — writers by most-recent publication (a new `ink_skrywer_laaste_publikasie` denorm, refreshed on every publish).
4. **Writers like this / like you** — writers sharing a published FORM with a reference writer (the 8.3 form flags), excluding the reference writer. On the hub, the reference is the logged-in writer ("Skrywers soos jy"); the contextual per-profile "writers like this" is realised on the Epic-9 Skrywerprofiel page, which consumes the same builder.
5. **Writers in your Gradering** — for a logged-in member, other writers at the same `Tier` (via `Tiers\Api`), excluding self. Hidden when logged out.
6. The surfaces are **server-rendered** (`WP_User_Query`, no REST), each a small row of skrywer cards (reusing the 8.3 card shape); a surface with no results renders nothing (no empty rows). Output escaped.
7. **Three-layer & conflation-clean:** the surfaces live in `ink-core` (`Ink\Discovery`); "writers in your Gradering" reads `Tiers\Api` for DISPLAY/grouping only and is a *discovery convenience*, never an entitlement/permission gate (THE conflation rule holds — discovery is open; tier here just clusters peers). Zero `Ink\Entitlement`.
8. **"Unread-by-you" is scoped out of this story (documented deferral), not silently dropped:** true "unread" needs a per-user READ history, which does not exist (the 8.3 `_ink_read_count` is an aggregate, and the leeslys is *saves*, not *reads*). Building a per-user read log is its own mechanism; per the epic's "fast-follow" framing it is deferred to the read-history work (Epic 9 activity / Epic 18 analytics). The surfaces component is built so the unread row slots in once that exists. Recorded in `deferred-work.md`.

## Tasks / Subtasks

- [x] Task 1: Add the "last publication" denorm (AC: #3)
  - [x] `SkrywerIndex`: add `LAST_PUBLISH_META = 'ink_skrywer_laaste_publikasie'`; on every readable-bydrae publish, set it to the publish GMT unix timestamp (ALWAYS updated, unlike first-publish which is once). Extend the existing `onTransition` (no new hook).
- [x] Task 2: Discovery-surfaces provider (`Ink\Discovery\DiscoverySurfaces`) (AC: #2–#5, #7)
  - [x] `newVoicesArgs(int $limit): array` (pure) — `WP_User_Query` args: writer-EXISTS clause on `eerste_publikasie`, NUMERIC orderby `eerste_publikasie` DESC, `number`, `fields`='ID'.
  - [x] `recentlyActiveArgs(int $limit): array` (pure) — same shape ordered by `LAST_PUBLISH_META` DESC.
  - [x] `writersLikeArgs(list<string> $forms, int $exclude_id, int $limit): array` (pure) — OR `meta_query` over the form-flag keys for `$forms` (= '1'), `exclude`=[$exclude_id], `number`, `fields`='ID'. Empty `$forms` → returns args that match nothing (no surface).
  - [x] `formsFor(int $user_id): list<string>` — the readable types the writer has a form flag for (reads `SkrywerIndex::formFlagKey`). `inGraderingIds(int $user_id, int $limit): list<int>` — `Tiers\Api::usersByGrade( Tiers\Api::forUser($user_id), [...] )` minus self via the pure `excludeId(list<int>, int): list<int>` helper.
- [x] Task 3: Surfaces render block (`ink/ontdek-vlakke`) (AC: #1, #6)
  - [x] `render(): string` — gather the surfaces: New voices + Recently active always; "Skrywers soos jy" + "Skrywers in jou Gradering" only when `is_user_logged_in()` AND the user is a writer; map each to skrywer cards (name/profile/gradering, reusing the 8.3 card shape), `toHtml`.
  - [x] `toHtml(array<string,list<card>> $surfaces): string` (pure) — one titled row per NON-EMPTY surface (heading + horizontal card list); renders nothing for an empty surface. Escaped. Surface headings are authored `__()` source (New stemme / Onlangs aktief / Skrywers soos jy / Skrywers in jou Gradering) — copy-debt to ratify; no AI Afrikaans.
  - [x] Register in `Discovery\Module`; embed `<!-- wp:ink/ontdek-vlakke /-->` in `patterns/ontdek.php` (e.g. between the search and the tabs); `.ink-ontdek-vlakke*` token styles in `theme.json`.
- [x] Task 4: Tests + gates (AC: all)
  - [x] `tests/Unit/Discovery/DiscoverySurfacesTest.php`: `newVoicesArgs`/`recentlyActiveArgs` order by the correct NUMERIC meta DESC with the writer-EXISTS clause + ID fields; `writersLikeArgs` builds the OR form-flag clause + `exclude` (empty forms → a match-nothing guard); `excludeId` removes self (and is a no-op when absent); `formsFor` returns only the flagged forms (mock `get_user_meta`).
  - [x] `tests/Unit/Discovery/OntdekVlakkeTest.php` (or fold into the above): `toHtml` renders a titled row per non-empty surface, renders NOTHING for an empty surface, and escapes (non-vacuous — a populated surface DOES render its heading + cards).
  - [x] `tests/Unit/Discovery/SkrywerIndexTest.php` (extend): a publish sets `laaste_publikasie` (ALWAYS, even when first-publish already set — so a second publish refreshes last but not first).
  - [x] Extend `OntdekTemplateTest`: the hub embeds `wp:ink/ontdek-vlakke`.
  - [x] `composer test:unit` green; `composer stan` clean; `composer cs` 0 errors; `composer copy:scan` no new debt; `composer deptrac` clean (no NEW edge — Discovery → [Kernel, Content, Engagement, Tiers] already covers it).

## Dev Notes

- **Custom surfaces, not default community screens** [Source: epics.md#Story 8.5; project-context "Reading engagement lives in ink-core" / BuddyPress scoped]: these are INK-owned `WP_User_Query` surfaces, never a BuddyPress members directory. Mirror the 8.3 `SkrywersTab` query + card conventions (writer-EXISTS clause, NUMERIC meta orderby, `fields=ID`, the `ink-ontdek-*` escaped card).
- **No follow graph yet** [Source: src/Social/Module.php (reserved, Epic 9); project-context "Follow is custom in ink-core … Epic 9"]: "writers like this" is built on **shared taxonomy / form** (Principle 8 — auto-surfacing, no manual linking), NOT on a follow/affinity graph (which lands in Epic 9). This is the spec-intended relatedness signal and needs nothing from Epic 9.
- **Tier clusters, never gates** [project-context THE conflation rule; deptrac Discovery→Tiers from 8.3]: "writers in your Gradering" groups peers at the same `Tier` for discovery convenience — it must NOT become a permission/entitlement check. `Tiers\Api::usersByGrade()` already exists (Story 5.5) and returns IDs.
- **Reuse the 8.3 denorm + card** [Source: src/Discovery/SkrywerIndex.php, SkrywersTab.php]: `eerste_publikasie` (new voices), the new `laaste_publikasie` (recently active), the form flags (writers-like). The skrywer card (name → author URL, Gradering via `Tiers\Api::forUser`, escaped) is the same shape — keep one card style.
- **Unread-by-you deferral** [AC #8]: there is no per-user read state (the `_ink_read_count` from 8.3 is an aggregate; leeslys is saves). A per-user read log is real new infrastructure; the epic frames personalised surfaces as fast-follow, so defer it cleanly and record it in `deferred-work.md`. Build the surfaces component so the row slots in later — do NOT ship a misleading "unsaved" proxy labelled "unread".
- **`writersLikeArgs` empty-forms guard**: a writer with no form flags (shouldn't happen for a real writer, but defensive) must produce a query that returns nothing rather than every user — assert it.

### Project Structure Notes

- MOD ink-core: `src/Discovery/SkrywerIndex.php` (laaste_publikasie), `src/Discovery/Module.php` (wire the block).
- NEW ink-core: `src/Discovery/DiscoverySurfaces.php` (provider + `ink/ontdek-vlakke` block — or split block into its own class; keep one if thin).
- MOD theme: `patterns/ontdek.php` (embed), `theme.json` (surface-row styles).
- NEW tests: `DiscoverySurfacesTest` (+ optional `OntdekVlakkeTest`); MOD `SkrywerIndexTest`, `OntdekTemplateTest`.
- MOD `_bmad-output/implementation-artifacts/deferred-work.md` (record the unread-by-you deferral).
- deptrac: no new edge.
- Note (don't build): the per-profile contextual "writers like this" rendering (Epic 9 Skrywerprofiel consumes `writersLikeArgs`); unread-by-you (read-history infra); the follow-affinity refinement of "writers like this" (Epic 9).

### References

- [Source: _bmad-output/planning-artifacts/epics.md#Story 8.5]
- [Source: _bmad-output/planning-artifacts/architecture.md#AD-7]
- [Source: wp-content/plugins/ink-core/src/Discovery/SkrywerIndex.php, SkrywersTab.php]
- [Source: wp-content/plugins/ink-core/src/Tiers/Api.php (usersByGrade, forUser)]
- [Source: _bmad-output/project-context.md#three-layer, #conflation-rule, #shared-taxonomy]

## Dev Agent Record

### Agent Model Used

claude-opus-4-8 (BMAD dev-story loop, Epic 8)

### Debug Log References

- `composer stan` runs outside the sandbox (parallel-worker TCP bind). No code findings.
- `DiscoverySurfaces` carries documented `phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query` on the two `WP_User_Query` builders (bounded, indexed denorm meta — AD-7; same accepted class as 8.2/8.3).

### Completion Notes List

- **Four personalised surfaces** (`ink/ontdek-vlakke`, server-rendered `WP_User_Query`): New voices (first-publish recency), Recently active (new `ink_skrywer_laaste_publikasie` denorm, refreshed every publish), Skrywers soos jy (shared published-FORM with the logged-in writer — shared-taxonomy relatedness, no follow graph), Skrywers in jou Gradering (same-`Tier` peers, logged-in only). Each is a titled row of skrywer cards (8.3 shape); an empty surface renders nothing.
- **Tier clusters, never gates:** "Skrywers in jou Gradering" reads `Tiers\Api` to group peers for discovery convenience — it is NOT an entitlement/permission check (THE conflation rule holds; discovery is open). No new deptrac edge (Discovery→Tiers from 8.3 covers it).
- **"Unread-by-you" deferred (AC #8), not silently dropped:** there is no per-user read history (the 8.3 `_ink_read_count` is an aggregate; the leeslys is *saves*). A per-user read log is its own mechanism; per the epic's fast-follow framing it is deferred to the read-history work (Epic 9 / 18.9) and recorded in `deferred-work.md`. `toHtml` already slots an unread row in once the data exists. A misleading "unsaved-as-unread" proxy was deliberately NOT shipped.
- **Per-profile "writers like this"** (relative to the viewed writer) is wired on the Epic-9 Skrywerprofiel page via the same `writersLikeArgs` builder; the hub ships the "soos jy" (relative to the viewer) variant. Recorded in `deferred-work.md`.
- Copy authored (`__()` source: Nuwe stemme / Onlangs aktief / Skrywers soos jy / Skrywers in jou Gradering) — copy-debt to ratify; no AI Afrikaans.
- Tests 558→566 (+8); cs 0 errors; stan clean; copy:scan no new debt; deptrac 3 pre-existing violations (0 new).

### File List

- `wp-content/plugins/ink-core/src/Discovery/SkrywerIndex.php` (MOD — LAST_PUBLISH_META, always-refreshed on publish)
- `wp-content/plugins/ink-core/src/Discovery/DiscoverySurfaces.php` (NEW — surfaces provider + ink/ontdek-vlakke block)
- `wp-content/plugins/ink-core/src/Discovery/Module.php` (MOD — wire DiscoverySurfaces)
- `wp-content/themes/ink-foundation/patterns/ontdek.php` (MOD — embed surfaces block)
- `wp-content/themes/ink-foundation/theme.json` (MOD — surface-row token styles)
- `tests/Unit/Discovery/DiscoverySurfacesTest.php` (NEW)
- `tests/Unit/Discovery/SkrywerIndexTest.php` (MOD — laaste_publikasie always-refresh)
- `tests/Unit/Discovery/OntdekTemplateTest.php` (MOD — vlakke embed guard)
- `_bmad-output/implementation-artifacts/deferred-work.md` (MOD — unread-by-you + per-profile writers-like deferrals)
- `_bmad-output/implementation-artifacts/8-5-discovery-surfaces.md` (NEW — this story)

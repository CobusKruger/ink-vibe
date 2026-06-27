---
baseline_commit: 1eddde2
---

# Story 9.3: Following-feed

Status: done

<!-- Note: Validation is optional. Run validate-create-story for quality check before dev-story. -->

## Story

As a lid,
I want an activity feed of writers I follow,
so that I see their new work. (FR-39)

## Acceptance Criteria

**Given** the profile "Aktiwiteit" tab
**When** it renders
**Then** it shows **new publications by followed skrywers only**.

1. A server-rendered `ink/volg-voer` block renders the following-feed: **published bydraes authored by the skrywers the current member follows**, newest-first. It reads the followed ids through `Social\Api::followeeIdsFor()` (the 9.2 facade) — never `FollowStore` directly.
2. **Followed skrywers only** — the query is constrained by `author__in` = the followed ids. **Critical guard:** when the member follows nobody, the feed renders the "you follow nobody yet" empty state and does **NOT** run an unconstrained query (an empty `author__in` is silently dropped by `WP_Query`, which would return *every* writer's work — the bug this story must not ship). Same class of empty-set guard as the 8.5 `writersLikeArgs` empty-forms case.
3. **New publications** — only readable bydraes (`gedig`/`storie`/`artikel`; the `skryfwerk` migration bucket is never reader-facing), `post_status = publish`, ordered by date DESC. Reuses the 8.1 `WorksArchive::readableTypes()` set (single source) and the same work-card shape (type / title → permalink / author).
4. **Logged-in only** — the feed is personal; a logged-out visitor sees nothing from this block (it returns '' before any query). The block is placed on the **private My Profiel / profile "Aktiwiteit" tab** by Story 9.4; this story ships the block so 9.4 can embed it.
5. **Two distinct empty states**, both authored Afrikaans (no AI): (a) follows nobody — an invite to discover writers; (b) follows writers but none have published yet — "nog niks geplaas nie". Neither renders an empty `<ul>`.
6. **Server-rendered** (`WP_Query`, no REST — AD-7 discovery/listing house style), mirroring `WorksArchive`/`SuggestedReads`: pure `queryArgs()` + pure `toHtml()` + a thin `render()`. Output escaped.
7. **Three-layer & conflation-clean:** the feed lives in `ink-core` (`Ink\Social`); the theme only embeds the block. It reads `Social\Api` (own module) + `Content\PostTypes` + `Terms` + WP core — zero `Ink\Entitlement` / `Ink\Tiers` (seeing your feed is open to any lid, never entitlement- or tier-gated). No new deptrac edge beyond Social→Content/I18n (already present from 9.2).

## Tasks / Subtasks

- [x] Task 1: Feed provider (`Ink\Social\FollowingFeed`) (AC: #1–#3, #6, #7)
  - [x] `BLOCK = 'ink/volg-voer'`; register on `init` (`register_block_type` guard). `PER_PAGE` const (e.g. 20).
  - [x] `queryArgs( list<int> $author_ids, int $per_page ): array` (pure) — `post_type` = `WorksArchive::readableTypes()`, `post_status` = 'publish', `author__in` = `$author_ids`, `orderby` date DESC, `posts_per_page`, `ignore_sticky_posts`. **Precondition contract:** callers MUST NOT call this with an empty `$author_ids` (the render gate handles the no-followees case) — defensively, an empty list still yields `author__in => array( 0 )` so the query matches nothing rather than everything.
  - [x] `render()` — logged-out → ''. `followeeIdsFor( get_current_user_id() )`; empty → `toHtml( [], 'geen-volg' )` (follows-nobody empty state, no query). Else run `WP_Query`, map posts to cards (title/permalink/type/author), `toHtml( $cards, 'voer' )`.
- [x] Task 2: Render + empty states (`toHtml`) (AC: #3, #5)
  - [x] `toHtml( list<card> $cards, string $state ): string` (pure) — heading (Terms `aktiwiteit`); when `$state === 'geen-volg'` render the discover-writers empty message; when cards empty (followed, none published) render the "nog niks geplaas nie" message; else a `ink-volg-voer__list` of work cards. Escaped, glossary/authored copy.
  - [x] Add Terms key `aktiwiteit` ("Aktiwiteit") — glossary-approved tab label.
- [x] Task 3: Wire + styles (AC: #1, #4)
  - [x] Register `FollowingFeed` in `Social\Module::register()`.
  - [x] `.ink-volg-voer*` token-only styles in `theme.json` (reuse the works-list card pattern).
- [x] Task 4: Tests + gates (AC: all)
  - [x] `tests/Unit/Social/FollowingFeedTest.php`: `queryArgs` constrains by `author__in` to the given ids, readable types only, publish, date DESC; **empty ids → `author__in => [0]` (matches nothing, NOT everything)** — non-vacuous (a populated id list DOES pass the ids through). `toHtml` renders a card list for posts, the follows-nobody message for the `geen-volg` state, and the "nothing published" message for an empty followed-feed; never an empty `<ul>`. `render` returns '' logged-out (mock `is_user_logged_in`).
  - [x] `composer test:unit` green; `composer stan` clean; `composer cs` 0 errors; `composer copy:scan` no new debt; `composer deptrac` clean (no new edge).

## Dev Notes

- **The empty-`author__in` trap is the whole story** [Source: WP_Query semantics; 8.5 `writersLikeArgs` empty-forms guard precedent]: `WP_Query` *silently ignores* an empty `author__in`, returning all posts. A naive feed for a member who follows nobody would therefore show **everyone's** work — the opposite of "followed skrywers only". Guard at the render gate (no followees → empty state, no query) AND defensively in `queryArgs` (empty → `author__in => [0]`). Assert both, non-vacuously.
- **Reuse, don't re-derive** [Source: src/Discovery/WorksArchive.php]: take `readableTypes()` from `WorksArchive` (single source — skryfwerk excluded), and mirror its card shape (type/title/author) + the pure `queryArgs`/`toHtml`/thin-`render` split and the `SuggestedReads` server-render house style. No REST (AD-7).
- **Facade discipline** [AD-1; Story 9.2]: read followed ids ONLY through `Social\Api::followeeIdsFor()`. This is the cross-module contract the 9.2 facade was built for; do not touch `FollowStore`.
- **Placement is 9.4** [Source: epics.md#Story 9.4 "private My Profiel … Aktiwiteit"]: the AC frames this as the profile "Aktiwiteit" tab, but the block-theme profile templates land in 9.4. Ship the block here (like 8.5 shipped surfaces consumed by 9.4); 9.4 embeds `wp:ink/volg-voer` in the profile Aktiwiteit tab. The feed is the member's OWN feed (current user), so it belongs on My Profiel (private), consistent with 9.4's public/private split.
- **Conflation rule** [project-context]: seeing your following-feed is an engagement convenience open to any logged-in lid — never entitlement- or tier-gated. Keep `Ink\Social` free of `Ink\Entitlement`/`Ink\Tiers` (the 9.2 `CodeScan` guardrail already covers the Social files; extend it to `FollowingFeed`).
- **Copy** [project-context Afrikaans-first]: the tab label `Aktiwiteit` and the two empty-state messages are authored Afrikaans. `Aktiwiteit` → Terms registry; the empty-state sentences are `__()` source literals (copy-debt to ratify, like the 8.x surface headings) — no AI Afrikaans, no `[NEEDS HUMAN AFRIKAANS]` unless a phrase has no approved source.

### Project Structure Notes

- NEW ink-core: `src/Social/FollowingFeed.php` (provider + `ink/volg-voer` block).
- MOD ink-core: `src/Social/Module.php` (wire the feed), `src/I18n/Terms.php` (`aktiwiteit` key).
- MOD theme: `theme.json` (`.ink-volg-voer` token styles).
- MOD tests: extend the Social conflation `CodeScan` to include `FollowingFeed.php`; NEW `FollowingFeedTest`.
- deptrac: no new edge (Social→Content from `readableTypes`, Social→I18n from `Terms` — both already allowed/present after 9.2).
- Note (don't build): profile templates + the Aktiwiteit tab host (9.4); the read-history "unread-by-you" surface (deferred, Epic 18); pagination of the feed (out of scope — a bounded recent window is sufficient for v1; note if cut).

### References

- [Source: _bmad-output/planning-artifacts/epics.md#Story 9.3, #Story 9.4]
- [Source: _bmad-output/planning-artifacts/architecture.md#AD-7 (server-rendered discovery, no REST for listings), #AD-1 (facade)]
- [Source: wp-content/plugins/ink-core/src/Discovery/WorksArchive.php (queryArgs/toHtml/render + card shape + readableTypes)]
- [Source: wp-content/plugins/ink-core/src/Social/Api.php (followeeIdsFor — Story 9.2)]
- [Source: _bmad-output/project-context.md#three-layer, #conflation-rule, #Afrikaans-first]

## Dev Agent Record

### Agent Model Used

claude-opus-4-8 (BMAD dev-story loop, Epic 9)

### Debug Log References

- `composer stan` / `composer deptrac` run outside the sandbox (phpstan parallel-worker TCP bind). stan OK.
- deptrac: adding the feed surfaced that **Social had no allowed edge to Content** (the 3 pre-existing violations are Kernel→Content, not Social). Added `Social: [Kernel, Content]` to `deptrac.yaml` with the same rationale as the Discovery→Content (8.1) edge — the feed legitimately lists bydraes. Back to **3 pre-existing violations, 0 new**.

### Completion Notes List

- **Following-feed** (`ink/volg-voer`, server-rendered `WP_Query`): published bydraes authored by the ids from `Social\Api::followeeIdsFor(current_user)`, newest-first, readable types only (skryfwerk excluded), logged-in only. Mirrors the `WorksArchive` pure `queryArgs`/`toHtml`/thin-`render` split and card shape.
- **The empty-`author__in` trap is handled at both layers** (the decisive correctness concern): the render gate returns the follows-nobody empty state WITHOUT querying when `followeeIdsFor` is empty, and `queryArgs` defensively maps an empty id list to `author__in => [0]` (matches nothing, not everything). Both asserted non-vacuously — a naive feed would have shown *every* writer's work to a member who follows nobody.
- **readable-types single source promoted to Content**: rather than couple Social→Discovery, added `Content\PostTypes::readableTypes()` (GEDIG/STORIE/ARTIKEL, skryfwerk excluded) as the shared single source and made `WorksArchive::readableTypes()` delegate to it. The feed reads `PostTypes::readableTypes()` (Social→Content edge). 8.x `WorksArchive::readableTypes()` callers/tests unchanged (same array).
- **Authored copy only** (no AI Afrikaans): heading "Aktiwiteit van wie jy volg", follows-nobody "Volg 'n skrywer om hul nuwe stukke in jou aktiwiteitsvoer te sien." and following-but-empty "Nuwe werk van hierdie skrywers verskyn in jou aktiwiteitsvoer." are taken verbatim from `ui-copy-translations.md` (My Profiel — Volg/Aktiwiteit, lines 751/753/755). No new Terms key was needed in 9.3; the `Aktiwiteit` tab label is deferred to 9.4 where the tab is built.
- **Conflation-clean**: the Social `CodeScan` guardrail now covers `FollowingFeed` too (no Tiers/Entitlement). No REST (AD-7 listing house style).
- Tests 600→607 (+7); cs 0 errors; stan OK; copy:scan no new debt; deptrac 3 pre-existing (0 new).

### File List

- `wp-content/plugins/ink-core/src/Social/FollowingFeed.php` (NEW — ink/volg-voer feed block + author__in guard)
- `wp-content/plugins/ink-core/src/Social/Module.php` (MOD — wire FollowingFeed)
- `wp-content/plugins/ink-core/src/Content/PostTypes.php` (MOD — readableTypes() single source)
- `wp-content/plugins/ink-core/src/Discovery/WorksArchive.php` (MOD — delegate readableTypes() to PostTypes)
- `wp-content/themes/ink-foundation/theme.json` (MOD — .ink-volg-voer token styles)
- `deptrac.yaml` (MOD — allow Social→Content, like Discovery→Content)
- `tests/Unit/Social/FollowingFeedTest.php` (NEW)
- `tests/Unit/Social/FollowControllerTest.php` (MOD — extend the conflation CodeScan to FollowingFeed)
- `_bmad-output/implementation-artifacts/9-3-following-feed.md` (NEW — this story)
- `_bmad-output/implementation-artifacts/sprint-status.yaml` (MOD — 9.3 status)

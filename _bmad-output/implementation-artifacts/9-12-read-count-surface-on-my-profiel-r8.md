---
baseline_commit: d40816c
---

# Story 9.12: Read-count surface on My Profiel (R8)

Status: review

<!-- Note: Validation is optional. Run validate-create-story for quality check before dev-story. -->

## Story

As a skrywer,
I want to see read counts on My Profiel,
so that I know my reach privately. (FR-44b, R8)

## Acceptance Criteria

**Given** the analytics provider (18.9) and `_ink_read_count`
**When** My Profiel renders
**Then** per-bydrae read counts show on **My Profiel only** (private), verb-less with `_n()` plurals — not on the public Skrywerprofiel.

1. A server-rendered `ink/leesgetalle` block lists the **logged-in writer's own published bydraes with their per-bydrae read count** (`_ink_read_count`, the Story 8.x `Discovery\ReadCount` meta), newest-first. Logged-out → renders nothing.
2. **Private — My Profiel ONLY:** the block is embedded in the My Profiel pattern (the 9.4 `data-ink-slot="leesgetalle"` slot) and renders only the *current user's own* works. It does **NOT** appear on the public Skrywerprofiel (the FR-40 separation — already enforced: the `SkrywerProfiel` public block carries no read-count surface).
3. **Verb-less counts with `_n()`:** each count is a verb-less noun phrase (`_n( '%s lesing', '%s lesings', $n )` with `number_format_i18n`), mirroring the 7.8 reaction-count house style — not "12 keer gelees".
4. **Graceful degradation (R8):** with analytics absent / a work never read, `_ink_read_count` is 0/absent → the row shows "0 lesings" (the surface still renders the writer's works; it degrades to zeroes, never errors). The block is correct for whatever counts exist.
5. **Three-layer & conflation-clean:** the surface lives in `ink-core` (`Ink\Discovery` — it owns `_ink_read_count` + `ReadCount`); reads `Content\PostTypes::readableTypes()` (already allowed) + the read-count meta; zero `Ink\Tiers`/`Ink\Entitlement` (a writer seeing their own reach is private, not a gate). Server-rendered (`WP_Query`, no REST — AD-7).
6. **Afrikaans-first:** the heading + count phrasing are glossary-consistent authored Afrikaans (`_n` "lesing"/"lesings"; heading "Leesgetalle"); no AI Afrikaans, no raw literals; copy-debt to ratify (the 8.x label lane).

## Tasks / Subtasks

- [x] Task 1: Read-count surface block (`Ink\Discovery\ReadCountSurface`) (AC: #1–#5)
  - [x] `BLOCK = 'ink/leesgetalle'`; register on `init` (`register_block_type` guard). `render()` — logged-out → ''. Query the current user's own published readable bydraes (`author` = current, `post_type` = `PostTypes::readableTypes()`, newest-first); for each, read `(int) get_post_meta( $id, ReadCount::READ_COUNT_META, true )`; build rows (title + count). `toHtml()` pure.
  - [x] `countLabel( int $n ): string` — verb-less `_n( '%s lesing', '%s lesings', $n, 'ink-core' )` with `number_format_i18n` (the 7.8 verb-less pattern).
- [x] Task 2: Wire + place + styles (AC: #2, #6)
  - [x] Register `ReadCountSurface` in `Discovery\Module::register()`.
  - [x] Embed `<!-- wp:ink/leesgetalle /-->` in `patterns/my-profiel.php` (fill the 9.4 `data-ink-slot="leesgetalle"` slot — the read-count surface goes inside that reserved private group).
  - [x] `.ink-leesgetalle*` token-only styles in `theme.json`.
- [x] Task 3: Tests + gates (AC: all)
  - [x] `tests/Unit/Discovery/ReadCountSurfaceTest.php`: `countLabel` is verb-less singular at 1 / plural at 0 and 2+ (no "gelees" verb); `toHtml` renders a row per own work with its count, and an empty state when the writer has no published works; `render` '' logged-out; a work with no `_ink_read_count` shows 0 (graceful). 
  - [x] `ProfileTemplatesTest` (extend): the My Profiel pattern embeds `wp:ink/leesgetalle`; the public `SkrywerProfiel` block source STILL carries no read-count surface (the FR-40 separation holds with the surface now real).
  - [x] `composer test:unit` green; `composer stan` clean; `composer cs` 0 errors; `composer copy:scan` no new debt; `composer deptrac` clean (no new edge — Discovery→Content already allowed).

## Dev Notes

- **Discovery owns the read count** [Source: src/Discovery/ReadCount.php `READ_COUNT_META = '_ink_read_count'`]: the per-post read count is Discovery's meta (bumped on each published-bydrae view, Story 8.x). The R8 surface therefore belongs in `Discovery` (it reads its own meta) — NOT Social (which would need a new Social→Discovery edge). Discovery→Content is already allowed (8.1). Mirror the `WorksArchive` pure `queryArgs`/`toHtml`/thin-`render` split + the own-works query shape from `PinnedWorksManager`.
- **Private — the FR-40 separation is the whole point** [Source: epics.md#Story 9.12, #Story 9.4]: read counts are the writer's PRIVATE reach — they live on My Profiel only and never on the public Skrywerprofiel. 9.4 reserved the `leesgetalle` slot and the public `SkrywerProfiel` block deliberately renders no read-count surface (asserted by `ProfileTemplatesTest`). 9.12 fills the private slot; re-assert that the public block still has none.
- **Verb-less counts** [Source: Story 7.8 reaction counts; AC #3]: "12 lesings", "1 lesing", "0 lesings" — a noun phrase via `_n()`, not "12 keer gelees" (which carries the verb). `lesing`/`lesings` is the verb-less reading-count noun; copy-debt to ratify (no approved ui-copy row — the 8.x authored-label lane). No AI Afrikaans.
- **Graceful with no analytics (R8)** [Source: epics.md#Story 9.11/9.12 sequence note; the existing `_ink_read_count` from 8.x]: `_ink_read_count` already increments on real views (8.x), so the surface shows whatever has accrued; the 18.9 analytics provider deepens accuracy (bot filtering, atomicity — deferred), but the surface is correct for the counts that exist and degrades to 0 for unread works. Inert-safe.
- **No `ink/ontvangs` emission here** [Source: Story 9.11 owns the R7 trigger; AD-6]: 9.12 is the read-count *surface*. The read-count *milestone* event (`ink/ontvangs`) that drives the R7 receipt is owned by the analytics provider (18.9) / the read-count increment path — out of scope for this display story (9.11 is already inert-safe without it). Note only.

### Project Structure Notes

- NEW ink-core: `src/Discovery/ReadCountSurface.php` (the `ink/leesgetalle` private block).
- MOD ink-core: `src/Discovery/Module.php` (register the block).
- MOD theme: `patterns/my-profiel.php` (embed `wp:ink/leesgetalle` in the reserved leesgetalle slot), `theme.json` (`.ink-leesgetalle` styles).
- NEW tests: `tests/Unit/Discovery/ReadCountSurfaceTest.php`; MOD `tests/Unit/Social/ProfileTemplatesTest.php`.
- deptrac: no new edge (Discovery→Content already allowed). copy:scan: count label is `__()`/`_n()` source in ink-core (not flagged).
- Note (don't build): the `ink/ontvangs` milestone emitter + read-count atomicity/bot-filtering (18.9 analytics, already deferred in Epic 8 review); a public-facing read count (private only, by spec).

### References

- [Source: _bmad-output/planning-artifacts/epics.md#Story 9.12 (FR-44b, R8), #Story 9.4, #Story 18.9]
- [Source: wp-content/plugins/ink-core/src/Discovery/ReadCount.php (READ_COUNT_META = _ink_read_count), WorksArchive.php (block pattern)]
- [Source: wp-content/plugins/ink-core/src/Social/PinnedWorksManager.php (own-works query + toggle-list shape), SkrywerProfiel.php (the public block with NO read count — FR-40)]
- [Source: docs/ui-copy-translations.md (no approved read-count row — copy-debt); _bmad-output/project-context.md#three-layer, #conflation-rule, #Afrikaans-first]

## Dev Agent Record

### Agent Model Used

claude-opus-4-8 (BMAD dev-story loop, Epic 9)

### Debug Log References

- `composer stan` / `composer deptrac` run outside the sandbox. stan OK; deptrac 3 pre-existing (0 new — Discovery→Content already allowed).

### Completion Notes List

- **Read-count surface lives in Discovery** (`ink/leesgetalle`, `Discovery\ReadCountSurface`) — Discovery owns `_ink_read_count` (`ReadCount::READ_COUNT_META`), so reading its own meta needs no new edge. Lists the current user's own published bydraes + each work's read count, newest-first; mirrors the `WorksArchive` pure split + the `PinnedWorksManager` own-works query.
- **Private — My Profiel only** (FR-40): embedded in `my-profiel.php` (the 9.4 `leesgetalle` slot); logged-out → ''; renders only the viewer's own works. The public `SkrywerProfiel` block carries no read-count surface — re-asserted in `ProfileTemplatesTest`.
- **Verb-less `_n()`** count: "12 lesings" / "1 lesing" / "0 lesings" (the 7.8 house style, not "12 keer gelees"); `lesing`/`lesings` is copy-debt to ratify (no approved ui-copy row).
- **Graceful (R8)**: an unread work shows "0 lesings"; the surface is correct for whatever `_ink_read_count` has accrued (8.x increments on real views; the 18.9 analytics deepens accuracy — deferred). The `ink/ontvangs` milestone emitter that drives the R7 receipt is owned by 18.9 (out of scope here; 9.11 is inert-safe without it).
- Tests 673→678 (+5); cs 0 errors; stan OK; copy:scan no new debt; deptrac 3 pre-existing (0 new).

### File List

- `wp-content/plugins/ink-core/src/Discovery/ReadCountSurface.php` (NEW — ink/leesgetalle private block)
- `wp-content/plugins/ink-core/src/Discovery/Module.php` (MOD — register ReadCountSurface)
- `wp-content/themes/ink-foundation/patterns/my-profiel.php` (MOD — embed wp:ink/leesgetalle in the slot)
- `wp-content/themes/ink-foundation/theme.json` (MOD — .ink-leesgetalle styles)
- `tests/Unit/Discovery/ReadCountSurfaceTest.php` (NEW)
- `tests/Unit/Social/ProfileTemplatesTest.php` (MOD — leesgetalle embed + FR-40 re-assert)
- `_bmad-output/implementation-artifacts/9-12-read-count-surface-on-my-profiel-r8.md` (NEW — this story)
- `_bmad-output/implementation-artifacts/sprint-status.yaml` (MOD — 9.12 status)

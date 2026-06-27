---
baseline_commit: 190f042
---

# Story 9.5: Pinned / selected works

Status: done

<!-- Note: Validation is optional. Run validate-create-story for quality check before dev-story. -->

## Story

As a skrywer,
I want to pin selected works,
so that visitors see my best work first. (FR-41)

## Acceptance Criteria

**Given** my profile
**When** I curate
**Then** I can pin/select highlighted pieces shown on my profile.

1. A skrywer can **pin/unpin their own published bydraes** (gedig/storie/artikel). The pinned set is a small **ordered, capped** list stored per writer as user-meta `ink_vasgespelde_werke` (a bounded list — no reverse "who pinned this" query is needed, so user-meta is correct here, unlike the leeslys table). The cap (e.g. 6) is enforced on write.
2. **Ownership + readability are enforced**: a writer may only pin a post that is **their own**, **published**, and a **readable bydrae** (not the `skryfwerk` bucket, not someone else's work, not a draft/page/attachment). A crafted REST call to pin another author's work or a non-bydrae is rejected with an Afrikaans `WP_Error`.
3. **REST write path** `ink/v1/vasgespel` (Afrikaans noun, AD-6): `POST` pins, `DELETE` unpins. Gated by `is_user_logged_in()` + the REST nonce; the ownership/readability check is the authorisation (a writer curates only their own profile). Never entitlement- or tier-gated.
4. **Public display:** the pinned works render on the **public Skrywerprofiel** (Story 9.4), filling the reserved `ink-skrywerprofiel__vasgespel` slot — "visitors see my best work first", in the writer's pin order, newest-pin-last preserved. When the writer has pinned nothing, the slot renders nothing (no empty heading).
5. **Curation UI:** a `ink/vasgespel-bestuur` block on the private My Profiel lists the writer's own published bydraes, each with a **pin / unpin** toggle reflecting the current state; the enqueued client flips it through `ink/v1/vasgespel`. Shown only to the logged-in writer (their own works).
6. **Three-layer & conflation-clean:** all pin logic in `ink-core` (`Ink\Social`); the theme embeds blocks only. The readable+ownership check reads `Content\PostTypes::readableTypes()` (Social→Content, already allowed) + core post fields — zero `Ink\Entitlement` / `Ink\Tiers` (pinning your own work is open to any writing lid; it is not a paid/tier capability). Exposed through `Social\Api`.
7. **Afrikaans-first:** toggle labels + headings are glossary-backed (`Terms`) or authored Afrikaans (ui-copy "Speld vas" / "Vasgespelde werke"); no AI Afrikaans, no raw literals.

## Tasks / Subtasks

- [x] Task 1: Pinned-works store (`Ink\Social\PinnedWorks`) (AC: #1, #2, #6)
  - [x] `META = 'ink_vasgespelde_werke'`; `MAX = 6`. `forUser(int $user_id): list<int>` (read meta → sanitized, deduped, capped int list, pin order preserved). `isPinned(int,int): bool`.
  - [x] `pin(int $user_id, int $post_id): bool` — append the post id if not present, cap at `MAX` (reject the add when already at cap rather than silently dropping an existing pin), persist. `unpin(int,int): bool` (remove, persist; idempotent). Pure list transforms (`addPin`/`removePin`) split out for unit testing without `update_user_meta`.
- [x] Task 2: REST write path (`Ink\Social\PinnedWorksController`) (AC: #2, #3)
  - [x] `register()` → `rest_api_init`; `POST`+`DELETE` on `ink/v1/vasgespel` with a `post_id` arg; `permission()` = `is_user_logged_in()`.
  - [x] `handlePin`/`handleUnpin` resolve the post and compute `$isOwnReadableBydrae` = (post author === current user) && published && type ∈ `PostTypes::readableTypes()`; `validate()` (pure) → `ink_vasgespel_invalid` ("Jy kan net jou eie gepubliseerde werk vasspeld.") when not own/readable. Then call the store. Return `{ post_id, pinned: bool }`.
- [x] Task 3: Public display on the Skrywerprofiel (AC: #4)
  - [x] Extend `SkrywerProfiel`: render the queried author's `PinnedWorks::forUser()` as work cards into the `ink-skrywerprofiel__vasgespel` slot (reuse the card shape; resolve title/permalink/type per pinned id, skipping any now-unpublished id). Heading "Vasgespelde werke" only when there is ≥1 pin; nothing when empty.
- [x] Task 4: Curation block (`Ink\Social\PinnedWorksManager`) (AC: #5)
  - [x] `BLOCK = 'ink/vasgespel-bestuur'`; register on `init`. `render()` — logged-out → ''. Query the current user's own published readable bydraes (`author` = current, `post_type` = readable types); for each, a row with title + a pin/unpin toggle (`isPinned`), `data-ink-post`, `aria-pressed`. `toHtml()` pure. Embed `<!-- wp:ink/vasgespel-bestuur /-->` in `patterns/my-profiel.php`.
  - [x] `.ink-vasgespel*` token styles in `theme.json`; toggle label "Speld vas" / "Vasgespeld" from `Terms`.
- [x] Task 5: Facade + wiring + tests + gates (AC: all)
  - [x] `Social\Api`: `pinnedWorksFor(int): list<int>` (+ any needed accessor) delegating to `PinnedWorks`. Register `PinnedWorksController` + `PinnedWorksManager` in `Social\Module`. Add `vasgespel` / `vasgespeld` Terms keys.
  - [x] `tests/Unit/Social/PinnedWorksTest.php`: `addPin` appends + dedups + respects `MAX` (rejects beyond cap, non-vacuous — a pin under cap DOES add); `removePin` removes + is idempotent; `forUser` sanitizes/caps. `tests/Unit/Social/PinnedWorksControllerTest.php`: `validate` rejects not-own / non-readable (with the Afrikaans code) and passes an own published bydrae; permission. `SkrywerProfielTest` (extend): pinned cards render in the slot; nothing when empty. `PinnedWorksManagerTest`: `toHtml` renders a pin toggle per own work, state-correct; `render` '' logged-out. Extend the Social conflation `CodeScan` to the new files.
  - [x] `composer test:unit` green; `composer stan` clean; `composer cs` 0 errors; `composer copy:scan` no new debt; `composer deptrac` clean (no new edge — Social→Content already allowed).

## Dev Notes

- **User-meta, not a table** [Source: architecture.md#AD-5 (counts/leeslys table rationale)]: pinned works is a *bounded* per-writer ordered list with no reverse-query need (nobody asks "who pinned post X"), so capped user-meta (`ink_vasgespelde_werke`) is the right store — the leeslys needed a table precisely because it has a reverse "who saved this" query; pins do not. Public-ish meta → `ink_` prefix without leading underscore (the pins are shown publicly).
- **Ownership is the authorisation** [Source: AD-6 §2 three-tier permission; project-context conflation rule]: pinning is gated by login + nonce + *ownership* (own published bydrae), NOT by entitlement or tier. Compute the own-readable-bydrae check from core post fields + `PostTypes::readableTypes()` (Social→Content, already allowed from 9.3) — do NOT pull in `Engagement\Readable` (avoids a new Social→Engagement edge; the same rule, locally applied with the ownership clause Readable lacks).
- **Fills the 9.4 slot** [Source: Story 9.4 `ink-skrywerprofiel__vasgespel`]: 9.4 reserved the slot; 9.5 renders the pinned cards there by extending `SkrywerProfiel`. Skip any pinned id that is no longer a published bydrae (deleted/unpublished since pinning) so a stale pin never renders a broken card. Pin order = display order ("best work first").
- **Curation on My Profiel** [Source: AC #5; leeslys toggle precedent]: the writer curates from their own private dashboard — a management list of their works with pin toggles, mirroring the `ink/leeslys-knoppie` server-render-then-client-flip. Reuse the toggle client pattern (a small enqueued flip against `ink/v1/vasgespel`).
- **Cap behaviour** [AC #1]: at `MAX` pins, a new pin is rejected (the writer unpins first) rather than silently evicting an existing pin — predictable curation. Assert the cap non-vacuously.
- **Copy** [project-context Afrikaans-first; ui-copy]: "Speld vas" (pin verb), "Vasgespelde werke" (heading) — use authored copy / glossary; the toggle uses a `Terms` key. No AI Afrikaans.

### Project Structure Notes

- NEW ink-core: `src/Social/PinnedWorks.php` (user-meta store), `src/Social/PinnedWorksController.php` (REST), `src/Social/PinnedWorksManager.php` (curation block).
- MOD ink-core: `src/Social/SkrywerProfiel.php` (render pins in the slot), `src/Social/Api.php` (facade), `src/Social/Module.php` (wire), `src/I18n/Terms.php` (vasgespel keys).
- MOD theme: `patterns/my-profiel.php` (embed curation block), `theme.json` (`.ink-vasgespel` styles).
- NEW tests: `PinnedWorksTest`, `PinnedWorksControllerTest`, `PinnedWorksManagerTest`; MOD `SkrywerProfielTest`, the Social conflation `CodeScan`.
- deptrac: no new edge (Social→Content already allowed).
- Note (don't build): reordering pins via drag (out of scope — pin/unpin order is sufficient for v1; note the cut); pinning non-bydrae content; per-pin captions.

### References

- [Source: _bmad-output/planning-artifacts/epics.md#Story 9.5]
- [Source: _bmad-output/planning-artifacts/architecture.md#AD-5 (storage model), #AD-6 (ink/v1 nouns, permission)]
- [Source: wp-content/plugins/ink-core/src/Social/SkrywerProfiel.php (the 9.4 slot), FollowToggle.php / FollowingFeed.php (toggle + query patterns)]
- [Source: wp-content/plugins/ink-core/src/Engagement/ReadingListToggle.php (server-render-then-flip toggle precedent)]
- [Source: _bmad-output/project-context.md#three-layer, #conflation-rule, #Afrikaans-first]

## Dev Agent Record

### Agent Model Used

claude-opus-4-8 (BMAD dev-story loop, Epic 9)

### Debug Log References

- `composer stan` / `composer deptrac` run outside the sandbox. stan OK; deptrac 3 pre-existing (0 new — Social→Content already allowed from 9.3; no Engagement edge introduced).

### Completion Notes List

- **Pinned works = capped user-meta** (`ink_vasgespelde_werke`, MAX 6, `Ink\Social\PinnedWorks`): an ordered, deduped, bounded per-writer list. The cap/dedup/order rules live in pure transforms (`addPin`/`removePin`/`normalize`) unit-tested without `update_user_meta`. At cap, a new pin is REJECTED (the writer unpins first) — no silent eviction; asserted non-vacuously.
- **Ownership is the authorisation** (`PinnedWorksController`, `ink/v1/vasgespel`): a writer may only pin their OWN published readable bydrae — `post_author === current user` && publish && type ∈ `PostTypes::readableTypes()` (Social→Content, no Engagement edge). A crafted call to pin another's work / a non-bydrae → `ink_vasgespel_invalid`. Login + nonce only; never entitlement/tier.
- **Public display fills the 9.4 slot**: `SkrywerProfiel` now resolves the queried author's pins to cards ("best work first", pin order), skipping any id that is no longer published (no broken stale-pin card). Heading renders only when ≥1 pin.
- **Curation on My Profiel** (`ink/vasgespel-bestuur`, `PinnedWorksManager`): lists the writer's own published works with a state-correct pin/unpin toggle (server-render-then-client-flip, the leeslys pattern), embedded in `my-profiel.php`. Logged-out → ''.
- **Conflation-clean**: all 8 Social follow/pin files pass the extended `CodeScan` guardrail (no Tiers/Entitlement). Exposed via `Social\Api::pinnedWorksFor()`.
- **Copy**: `vasgespel` ("Speld vas", ui-copy 281) + `vasgespeld` ("Vasgespeld", ui-copy 702) Terms keys; "Vasgespelde werke" heading + the curation empty-state are glossary-consistent authored Afrikaans (copy-debt to ratify, the 8.x precedent) — no AI Afrikaans; copy:scan clean.
- Tests 614→629 (+15); cs 0 errors; stan OK; copy:scan no new debt; deptrac 3 pre-existing (0 new).

### File List

- `wp-content/plugins/ink-core/src/Social/PinnedWorks.php` (NEW — user-meta store + pure cap/dedup transforms)
- `wp-content/plugins/ink-core/src/Social/PinnedWorksController.php` (NEW — ink/v1/vasgespel REST + ownership validate)
- `wp-content/plugins/ink-core/src/Social/PinnedWorksManager.php` (NEW — ink/vasgespel-bestuur curation block)
- `wp-content/plugins/ink-core/src/Social/SkrywerProfiel.php` (MOD — render pinned works in the public slot)
- `wp-content/plugins/ink-core/src/Social/Api.php` (MOD — pinnedWorksFor facade)
- `wp-content/plugins/ink-core/src/Social/Module.php` (MOD — wire pin controller + manager)
- `wp-content/plugins/ink-core/src/I18n/Terms.php` (MOD — vasgespel / vasgespeld keys)
- `wp-content/themes/ink-foundation/patterns/my-profiel.php` (MOD — embed ink/vasgespel-bestuur)
- `wp-content/themes/ink-foundation/theme.json` (MOD — .ink-vasgespel / .ink-skrywerprofiel__vasgespel styles)
- `tests/Unit/Social/PinnedWorksTest.php` (NEW)
- `tests/Unit/Social/PinnedWorksControllerTest.php` (NEW)
- `tests/Unit/Social/PinnedWorksManagerTest.php` (NEW)
- `tests/Unit/Social/SkrywerProfielTest.php` (MOD — pinned-works render)
- `tests/Unit/Social/FollowControllerTest.php` (MOD — conflation CodeScan extended to pin files)
- `_bmad-output/implementation-artifacts/9-5-pinned-selected-works.md` (NEW — this story)
- `_bmad-output/implementation-artifacts/sprint-status.yaml` (MOD — 9.5 status)

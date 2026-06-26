---
baseline_commit: b261fd2
---

# Story 7.4: Structured community responses

Status: review

## Story

As a lid,
I want to post a typed Gemeenskapsreaksie,
so that feedback is structured and kind. (FR-27, UJ-3)

## Acceptance Criteria

**Given** a work (WP comments disabled site-wide)
**When** I respond
**Then** I post a Gemeenskapsreaksie of type **lof**, **insig**, or **voorstel**, each carrying its type — the only feedback path.

1. A logged-in lid can post a **Gemeenskapsreaksie** on a work, choosing exactly one type — **lof / insig / voorstel** (the `Ink\Kernel\ResponseType` enum) — plus the response text. The type is persisted with the response.
2. **Storage reuses the WP comment infrastructure** (AD-5a): a single sanctioned custom `comment_type = 'ink_reaksie'`, plus comment-meta `ink_response_type ∈ {lof,insig,voorstel}`. Written **only** programmatically via `wp_insert_comment` (never the public comment form, which stays disabled site-wide by `Comments`, Story 1.8). Flat, not threaded (v1).
3. The write path is the `ink/v1/gemeenskapsreaksie` REST endpoint (AD-6): `is_user_logged_in()` + REST nonce (NOT entitlement-gated, AD-6 §2; conflation-clean). Validation → `WP_Error` with `ink_`-coded **Afrikaans** messages: type ∈ enum, content non-empty after sanitisation, target post readable.
4. **This is the ONLY free-form feedback path** — there is no other public commentary surface (WP comments stay off; line reactions are reactions-only). Each response **must carry a type**: a response with an unknown/missing type is rejected (no untyped Gemeenskapsreaksie can exist).
5. The reading surface renders the existing Gemeenskapsreaksies (server-rendered list: type badge + author + date + text, escaped) and a typed response form, on every work (gedig/storie/artikel). The form submits through the REST endpoint.
6. **Custom-type rows must not inflate the post's displayed comment count** (AD-5a): the response count surfaced is a filtered count of `comment_type='ink_reaksie'` (`ResponseStore::countForPost`), managed independently of WP's default `comment_count`. The display surfaces use that count, not `get_comments_number()`.
7. Three-layer: the store + REST + validation are `ink-core` (Engagement); the list/form rendering is a server block (dynamic, INK-tied, AD-7) with the theme owning presentation. Controlled-vocabulary labels (Lof/Insig/Voorstel, "Gemeenskapsreaksies") come from the `Ink\I18n\Terms` registry (glossary-backed, `docs/afrikaans-terms.md` line 157), never bare literals.

## Tasks / Subtasks

- [x] Task 1: `ResponseType` single-source helper + glossary labels (AC: #1, #7)
  - [x] Add `Ink\Kernel\ResponseType::values(): array` → `['lof','insig','voorstel']` (from `self::cases()`).
  - [x] Add `gemeenskapsreaksie` / `gemeenskapsreaksie_plural` / `lof` / `insig` / `voorstel` to `Ink\I18n\Terms::map()` (Afrikaans, from the glossary), so the badges + heading come from the registry.
- [x] Task 2: `Ink\Engagement\ResponseStore` (WP-comment substrate) (AC: #2, #4, #6)
  - [x] `const COMMENT_TYPE = 'ink_reaksie'`, `const META_TYPE = 'ink_response_type'`.
  - [x] `add(post_id, user_id, ResponseType, content): int` — `wp_insert_comment` (`comment_post_ID`, `user_id`, author name/email from the user, `comment_content`, `comment_type`, `comment_approved=1`); then `add_comment_meta(id, META_TYPE, type->value)`. Returns the comment id (0 on failure).
  - [x] `forPost(post_id): array` — `get_comments(['post_id'=>,'type'=>COMMENT_TYPE,'status'=>'approve','order'=>'ASC'])` → list of view-models `{id, type:ResponseType, content, author, date}` (type via `get_comment_meta` + `ResponseType::tryFrom`; skip rows with an invalid/missing type).
  - [x] `countForPost(post_id): int` — `get_comments(['count'=>true,'type'=>COMMENT_TYPE,'post_id'=>,'status'=>'approve'])` — the filtered count (AD-5a), independent of `comment_count`.
- [x] Task 3: `Ink\Engagement\ResponseController` REST (AC: #3, #4)
  - [x] `register()` → `rest_api_init` → `POST ink/v1/gemeenskapsreaksie`. `permission()` = `is_user_logged_in()`. `args`: `post_id` (int, req), `type` (string, req, `enum`=`ResponseType::values()`), `content` (string, req).
  - [x] Pure `validate(type, content, postReadable): ?WP_Error` (Afrikaans `ink_gemeenskapsreaksie_*` codes: invalid_post / invalid_type / empty_content). Callback: `absint` post, `sanitize_key` type, `sanitize_textarea_field`(`wp_unslash`) content; `get_post_status` readable check; on pass → `ResponseStore::add` → `{ id, type }`. No raw value reaches the store unguarded.
- [x] Task 4: Display block + theme wiring (AC: #5, #6, #7)
  - [x] `Ink\Engagement\ResponsesList` server block `ink/gemeenskapsreaksies` (`render_callback`): renders the heading + count (via `countForPost`, Afrikaans `_n()`-style plural through Terms), the existing responses (type badge from Terms + escaped author/date/content), and the typed form (type radios + textarea + submit). Pure `toHtml(array $responses, int $count): string` for the list portion (unit-testable); the callback pulls `forPost(get_the_ID())`.
  - [x] Append `<!-- wp:ink/gemeenskapsreaksies /-->` to `reading-storie.php`, `reading-artikel.php`, `reading-gedig.php` (after the body).
  - [x] `assets/js/gemeenskapsreaksie.js` — minimal client: POST the form to `ink/v1/gemeenskapsreaksie` with `X-WP-Nonce`; on success prepend/refresh. Enqueue on `is_singular(['gedig','storie','artikel'])` via `ink_foundation_enqueue_gemeenskapsreaksie()`; localise REST root + nonce + post id + Afrikaans labels. No business logic in JS.
- [x] Task 5: Tests + gates (AC: all)
  - [x] `tests/Unit/Kernel/ResponseTypeTest.php`: `values()` exactly `['lof','insig','voorstel']`, matches `cases()`, `tryFrom` rejects a reaction value (e.g. 'hartjie') and ''.
  - [x] `tests/Unit/Engagement/ResponseStoreTest.php` (Brain Monkey): `add` calls `wp_insert_comment` with `comment_type='ink_reaksie'` + `add_comment_meta(META_TYPE,…)`; `forPost` maps comments + meta to typed view-models and **skips an invalid/missing type** (AC #4 — no untyped response); `countForPost` queries `type='ink_reaksie'` with `count=>true` (the AD-5a filtered count — non-vacuous: assert the args).
  - [x] `tests/Unit/Engagement/ResponseControllerTest.php`: permission logged-in; `validate` rejects unreadable post / unknown type / empty content, passes a valid typed response (non-vacuous); **conflation guard** — controller + store source reference no `Ink\Tiers`/`Ink\Entitlement` (CodeScan).
  - [x] (If cheap) extend `ReadingTemplatesTest`: each reading pattern embeds `wp:ink/gemeenskapsreaksies`.
  - [x] `composer test:unit` green, `cs`/`stan` clean, `copy:scan` no new debt, `deptrac` unchanged (Engagement → Kernel; uses Kernel `ResponseType` + `Ink\I18n` via the existing pattern? — keep within current allowed edges; if Terms access needs I18n, note it).

## Dev Notes

- **AD-5a is the spec** [Source: architecture.md:383-429]: `comment_type='ink_reaksie'` + comment-meta `ink_response_type` (the enum); reuse WP's moderation/spam tooling. **Guardrails:** default commenting stays disabled (only programmatic `wp_insert_comment` writes these rows — `Comments` does NOT block programmatic inserts, see its docblock); flat not threaded; the displayed count is the filtered `ink_reaksie` count, NOT WP's `comment_count`.
- **Comments substrate is open for this** [Source: src/Engagement/Comments.php:28-41]: `Comments` deliberately leaves the subsystem registered and does not touch programmatic `wp_insert_comment`. `ink_reaksie` is named there as the sanctioned type. No change to `Comments` needed.
- **ResponseType enum** [Source: src/Kernel/ResponseType.php]: `Lof/Insig/Voorstel` (values `lof/insig/voorstel`). Add only `values()`; validate with `tryFrom`.
- **Glossary** [Source: docs/afrikaans-terms.md:157]: Gemeenskapsreaksie + types are authored terms — add their labels to `Terms::map()` (single-source UI labels), don't inline literals.
- **REST + store pattern** [Source: src/Engagement/ReactionController.php, ReactionStore.php (Story 7.3)]: copy the 7.3 shape — pure `validate()`, thin callback, Afrikaans `WP_Error`, `permission()` logged-in. Here the substrate is WP comments (not a custom table), so `ResponseStore` wraps `wp_insert_comment`/`get_comments`/comment-meta rather than `$wpdb` directly.
- **Reactions-only vs feedback** [Source: epics.md#Story 7.3/7.4]: line reactions (7.3) carry NO text; the Gemeenskapsreaksie (7.4) is THE free-form-but-typed feedback path. Each response MUST carry a type — `forPost` skips a row whose meta type is missing/invalid (no untyped response surfaces), and the write rejects an unknown type. Guard this non-vacuously.
- **No entitlement gate / conflation-clean** [Source: AD-6 §2; project-context]: logged-in + nonce only; zero `Ink\Tiers`/`Ink\Entitlement` (assert via CodeScan like 7.3).
- **Count not inflating** [Source: AD-5a]: surface `countForPost` (filtered query), never `get_comments_number()`. The block uses the filtered count.
- **Three-layer / block** [Source: AD-7]: the Gemeenskapsreaksie form/list is a custom dynamic block; the list render is server-side (reads stay server-rendered), the form posts via REST. Theme owns CSS (theme.json styles.css like `.ink-gedig`).
- **Testing** [Source: tests/Unit/Engagement/ReactionControllerTest.php, ReactionStoreTest.php]: Brain Monkey; mock `wp_insert_comment`/`get_comments`/`get_comment_meta`/`add_comment_meta`; pure `validate`/`toHtml` tested directly; CodeScan conflation guard.

### Project Structure Notes

- New ink-core: `src/Engagement/ResponseStore.php`, `src/Engagement/ResponseController.php`, `src/Engagement/ResponsesList.php`; MOD `src/Kernel/ResponseType.php` (`values()`), `src/I18n/Terms.php` (labels), `src/Engagement/Module.php` (wire controller + block).
- New theme: `assets/js/gemeenskapsreaksie.js`; MOD `functions.php` (enqueue), `theme.json` (`.ink-reaksies*` styles), `patterns/reading-{storie,artikel,gedig}.php` (embed block).
- New tests: `ResponseTypeTest`, `ResponseStoreTest`, `ResponseControllerTest`; MOD `ReadingTemplatesTest`.
- deptrac: Engagement → Kernel (uses `ResponseType`). Terms access is via the theme bridge (presentation), not an ink-core cross-module call — keep no new edge. If the block needs `Ink\I18n\Terms` directly, add the `I18n` edge and note it; prefer reading labels in the theme layer where possible.

### References

- [Source: _bmad-output/planning-artifacts/epics.md#Story 7.4]
- [Source: _bmad-output/planning-artifacts/architecture.md#AD-5a, #AD-6, #AD-7]
- [Source: wp-content/plugins/ink-core/src/Engagement/Comments.php, ReactionController.php, ReactionStore.php]
- [Source: wp-content/plugins/ink-core/src/Kernel/ResponseType.php]
- [Source: docs/afrikaans-terms.md#Gemeenskapsreaksie]
- [Source: _bmad-output/project-context.md#conflation-rule, #escaping, #terminology-registry]

## Dev Agent Record

### Agent Model Used

claude-opus-4-8 (BMAD dev-story loop, Epic 7)

### Debug Log References

- phpcbf realigned the `Terms::map()` array `=>` columns after adding the new engagement keys (the longest key grew the alignment width) — purely cosmetic.
- phpstan ran clean outside the sandbox (parallel-worker TCP bind blocked by the sandbox, as in 7.2/7.3).

### Completion Notes List

- Gemeenskapsreaksies stored on the WP-comment substrate (AD-5a): `comment_type='ink_reaksie'` + comment-meta `ink_response_type` (the enum), written only programmatically via `wp_insert_comment` (public comments stay disabled). No change to `Comments` needed — it already leaves programmatic inserts untouched.
- THE only feedback path & every response carries a type: the write rejects an unknown type, and `forPost` skips any row whose stored type is missing/invalid — so no untyped Gemeenskapsreaksie can be created OR surfaced (guarded non-vacuously both ways).
- Second `ink/v1` endpoint (`ink/v1/gemeenskapsreaksie`) following the 7.3 pattern: pure `validate()`, thin callback, Afrikaans `WP_Error`, logged-in + nonce (not entitlement-gated; conflation-clean — asserted).
- Displayed count is the filtered `ink_reaksie` count (`countForPost`), never WP's `comment_count` (AD-5a) — asserted via the get_comments args.
- `ink/gemeenskapsreaksies` server block renders the list + typed form on all three reading surfaces (embedded in the reading patterns). All labels come from the glossary-backed Terms registry (Gemeenskapsreaksies / Lof / Insig / Voorstel / Plaas) — NO new unauthored copy, so copy:scan stays green and the Afrikaans-source rule holds (I added the glossary-authored labels to the registry).
- Engagement→I18n is allowed (I18n is not a deptrac layer — uncovered, like the Tiers/Entitlement Terms usage), so the block reads `Terms::label()` with no new edge.
- Front-end is a thin enqueued client (reading surfaces only); JS/E2E deferred to 18.8 per the project's layered testing.
- Tests 442→459 (+17); cs/stan clean; copy:scan no new debt; deptrac 0 new violations (3 pre-existing baseline).

### File List

- `wp-content/plugins/ink-core/src/Kernel/ResponseType.php` (MOD — `values()`)
- `wp-content/plugins/ink-core/src/I18n/Terms.php` (MOD — gemeenskapsreaksie/lof/insig/voorstel/plaas labels)
- `wp-content/plugins/ink-core/src/Engagement/ResponseStore.php` (NEW — ink_reaksie comment substrate)
- `wp-content/plugins/ink-core/src/Engagement/ResponseController.php` (NEW — ink/v1/gemeenskapsreaksie REST)
- `wp-content/plugins/ink-core/src/Engagement/ResponsesList.php` (NEW — ink/gemeenskapsreaksies server block)
- `wp-content/plugins/ink-core/src/Engagement/Module.php` (MOD — wire controller + block)
- `wp-content/themes/ink-foundation/patterns/reading-storie.php` (MOD — embed block)
- `wp-content/themes/ink-foundation/patterns/reading-artikel.php` (MOD — embed block)
- `wp-content/themes/ink-foundation/patterns/reading-gedig.php` (MOD — embed block)
- `wp-content/themes/ink-foundation/assets/js/gemeenskapsreaksie.js` (NEW)
- `wp-content/themes/ink-foundation/functions.php` (MOD — enqueue on reading surfaces)
- `wp-content/themes/ink-foundation/theme.json` (MOD — `.ink-reaksies*` styles)
- `tests/Unit/Kernel/ResponseTypeTest.php` (NEW)
- `tests/Unit/Engagement/ResponseStoreTest.php` (NEW)
- `tests/Unit/Engagement/ResponseControllerTest.php` (NEW)
- `tests/Unit/Engagement/ResponsesListTest.php` (NEW)
- `tests/Unit/Engagement/ReadingTemplatesTest.php` (MOD — block-embed guard)
- `_bmad-output/implementation-artifacts/7-4-structured-community-responses.md` (NEW — this story)

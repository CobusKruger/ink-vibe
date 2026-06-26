---
baseline_commit: 79c30e9
---

# Story 9.6: Reader ratings & reviews

Status: review

<!-- Note: Validation is optional. Run validate-create-story for quality check before dev-story. -->

## Story

As a lid,
I want to rate and review a writer,
so that readers can recognise quality. (FR-42)

## Acceptance Criteria

**Given** a Skrywerprofiel
**When** I rate/review
**Then** an aggregate rating + written reviews appear
**And** public reviews are subject to the moderation path (Epic 18) and POPIA public-profile considerations.

1. A lid can submit a **rating (1–5) + optional written review** of a skrywer. Stored in a custom `ink-core` table `{$wpdb->prefix}ink_ratings` (AD-5) with a **moderation status** and a `UNIQUE (user_id, skrywer_id)` (one rating per rater per writer — a re-submit upserts and returns to pending).
2. **Moderation status is a closed enum** `RatingStatus` (hangend / goedgekeur / verwerp — lowercase Afrikaans persisted values, the enum rule). A newly submitted rating is **`hangend` (held)** — never auto-public.
3. **Sequencing / graceful degradation** (cross-epic dependency flagged 2026-06-20): the moderation/report path is Story 18.4, which lands **before** public reviews are first exposed. Until then, reviews are **created and held** — the public Skrywerprofiel shows only **`goedgekeur` (approved)** reviews and an aggregate computed over **approved** ratings only. With no approval path yet, nothing is approved, so the surface degrades to an empty "nog geen oordele" state — never leaking an unmoderated review. (Same inert-until-dependency pattern as R7/R8.)
4. **Public display on the Skrywerprofiel** (Story 9.4): a **Lesergradering** aggregate (average + "[N] leseroordele" count, verb-less `_n()`) and the approved written reviews. Empty/held state when there are no approved ratings.
5. **Submission affordance**: a `ink/leseroordeel-vorm` block on the profile, shown only to a logged-in lid who is **not** the writer (you cannot rate yourself); posts to the REST write path. A logged-out / self viewer sees no form.
6. **REST write path** `ink/v1/leseroordeel` (Afrikaans noun, AD-6): `POST` submits/updates a rating. Gated by `is_user_logged_in()` + nonce; validation (target is a real user, not self, score 1–5) returns an Afrikaans `WP_Error`. Never entitlement- or tier-gated (rating is open to any lid).
7. **Three-layer & conflation-clean:** all rating logic in `ink-core` (`Ink\Social`); reads only `$wpdb` + `Terms` + WP core — zero `Ink\Entitlement` / `Ink\Tiers` (a reader rating is not the writer's Gradering and not an entitlement). Exposed through `Social\Api`.
8. **POPIA / no-leak:** an unapproved review's text is NEVER rendered publicly; the aggregate counts approved ratings only. (The report/takedown path is 18.4.)

## Tasks / Subtasks

- [x] Task 1: Status enum + store (`Ink\Social\RatingStatus`, `Ink\Social\RatingStore`) (AC: #1, #2, #7, #8)
  - [x] `RatingStatus: string` enum — `Hangend='hangend'`, `Goedgekeur='goedgekeur'`, `Verwerp='verwerp'` (closed, lowercase-Afrikaans values; the persisted moderation state).
  - [x] `RatingStore`: `TABLE='ink_ratings'`; `schemaSql()` (id PK, user_id, skrywer_id, score TINYINT, resensie TEXT, status VARCHAR(20), created_at; `UNIQUE KEY user_skrywer (user_id,skrywer_id)`, `KEY skrywer_status (skrywer_id,status)` for the public aggregate/reviews query). `rate(user_id,skrywer_id,score,review): bool` — upsert (`ON DUPLICATE KEY`), status forced to `hangend`. `aggregate(skrywer_id): array{count:int, average:float}` over `goedgekeur` only. `approvedReviews(skrywer_id): list<array{...}>` (`goedgekeur`, non-empty resensie). `hasRated(user_id,skrywer_id): bool`. Bound `$wpdb->prepare`; documented `phpcs:ignore` (the leeslys/follow direct-query class).
- [x] Task 2: REST write path (`Ink\Social\RatingController`) (AC: #5, #6)
  - [x] `register()`→`rest_api_init`; `POST` on `ink/v1/leseroordeel` with `skrywer_id` (int) + `score` (int) + `resensie` (string, sanitized) args; `permission()` = `is_user_logged_in()`. `handle()` validates, sanitizes the review (`sanitize_textarea_field`), calls the store, returns `{ skrywer_id, status:'hangend' }`.
  - [x] `validate( bool $targetIsUser, bool $isSelf, int $score ): ?WP_Error` (pure) — `ink_oordeel_self` (self), `ink_oordeel_invalid_target` (not a user), `ink_oordeel_score` (score outside 1–5). Afrikaans.
- [x] Task 3: Submission form block (`Ink\Social\RatingForm`) (AC: #5)
  - [x] `BLOCK='ink/leseroordeel-vorm'`; register on `init`. `render()` — '' when logged-out, no author context, or the queried author IS the viewer; else a star-select + textarea + submit, nonce, `data-ink-skrywer`. `toHtml()` pure, escaped, glossary copy. Embed `<!-- wp:ink/leseroordeel-vorm /-->` in `author.html`.
- [x] Task 4: Public display on the Skrywerprofiel (AC: #4, #8)
  - [x] Extend `SkrywerProfiel`: render a **Lesergradering** section — the aggregate (average + `Social\Api::leseroordeelLabel(count)`) and approved reviews; an empty "nog geen oordele" state when none approved. Reads `Social\Api::ratingAggregateFor()` / `approvedReviewsFor()`. PUBLIC = approved only.
- [x] Task 5: Facade + wiring + tests + gates (AC: all)
  - [x] `Social\Api`: `ratingAggregateFor()`, `approvedReviewsFor()`, `leseroordeelLabel(int)` (verb-less `_n` "leseroordeel"/"leseroordele"), `hasRated()`. Register the DDL in `ink-core.php`; register `RatingController` + `RatingForm` in `Social\Module`; add `lesergradering` / `leseroordeel` Terms keys.
  - [x] `tests/Unit/Social/RatingStatusTest.php`: the three cases + values. `RatingStoreTest`: schema has the UNIQUE + status index; `rate` upserts with status `hangend` (held); `aggregate`/`approvedReviews` SQL filters `goedgekeur` ONLY (non-vacuous — assert the query binds the approved status, so a pending review can never surface). `RatingControllerTest`: `validate` rejects self / non-user / out-of-range score with the right codes, passes a valid 1–5 third-party. `RatingFormTest`: '' logged-out / self; renders the form for a third-party. `SkrywerProfielTest` (extend): the approved-reviews section renders the aggregate + reviews; empty state when none.
  - [x] `composer test:unit` green; `composer stan` clean; `composer cs` 0 errors; `composer copy:scan` no new debt; `composer deptrac` clean (no new edge).

## Dev Notes

- **Held-for-moderation is the whole sequencing story** [Source: epics.md#Story 9.6 sequence note; architecture.md#AD-5 ratings table "moderation status + aggregation"]: 18.4 (report/moderation) lands before public exposure. 9.6 builds the write + storage + the approved-only read, so a submitted review is created `hangend` and NEVER shown until something approves it. The public aggregate + reviews query filters `goedgekeur` only — assert this non-vacuously (a pending row must not appear). With no approval path yet the surface is simply empty — graceful, not broken (the R7/R8 inert-until-dependency precedent).
- **Custom table, not comments** [Source: AD-5 "Ratings & reviews → custom table — moderation status + aggregation"]: this is its OWN table, distinct from the `ink_reaksie` comment-type engagement (which is per-work, not per-writer). Mirror the leeslys/follow store conventions (dbDelta DDL, `ON DUPLICATE KEY` upsert, bound prepares, documented phpcs:ignore). The `skrywer_status` composite index serves the public "approved ratings for this writer" query.
- **One rating per rater per writer** [AC #1]: `UNIQUE (user_id, skrywer_id)`; a re-submit upserts the score/review and resets status to `hangend` (a changed review must be re-moderated).
- **Rating ≠ Gradering** [project-context conflation rule; glossary line 68/677]: the writer's **Gradering** is their competition tier (5.x); the **Lesergradering** is readers' aggregate opinion — entirely separate. Keep `Ink\Social` ratings free of `Ink\Tiers`/`Ink\Entitlement`; the rating is open to any logged-in lid (not a paid capability).
- **POPIA** [AC #8; epics.md sequence note]: a public profile carries reviews about a person — never expose unmoderated text; the 18.4 report path is the takedown mechanism. 9.6's approved-only read is the pre-18.4 safeguard.
- **Copy** [ui-copy lines 677–678]: "Lesergradering" (aggregate label), "[N] leseroordele" (count — `_n()` leseroordeel/leseroordele). The form labels (star prompt, "Stuur oordeel", textarea placeholder) are authored Afrikaans/glossary; route any genuine gap through `[NEEDS HUMAN AFRIKAANS]`. No AI Afrikaans.

### Project Structure Notes

- NEW ink-core: `src/Social/RatingStatus.php` (enum), `src/Social/RatingStore.php` (table), `src/Social/RatingController.php` (REST), `src/Social/RatingForm.php` (form block).
- MOD ink-core: `src/Social/SkrywerProfiel.php` (Lesergradering display), `src/Social/Api.php` (facade), `src/Social/Module.php` (wire), `src/I18n/Terms.php` (rating keys), `ink-core.php` (Schema::register ink_ratings).
- MOD theme: `templates/author.html` (embed the form block), `theme.json` (`.ink-leseroordeel*` styles).
- NEW tests: `RatingStatusTest`, `RatingStoreTest`, `RatingControllerTest`, `RatingFormTest`; MOD `SkrywerProfielTest`, the Social conflation `CodeScan`.
- deptrac: no new edge (Social→I18n/Content already allowed).
- Note (don't build): the moderation queue / report path / takedown (Story 18.4); review threading/replies; helpful-vote on reviews; e-mail notification of a new review (9.9/9.11 own notifications).

### References

- [Source: _bmad-output/planning-artifacts/epics.md#Story 9.6 (+ the 2026-06-20 sequence note), #Story 18.4]
- [Source: _bmad-output/planning-artifacts/architecture.md#AD-5 (ratings custom table)]
- [Source: wp-content/plugins/ink-core/src/Social/FollowStore.php (store conventions), SkrywerProfiel.php (the 9.4 public block)]
- [Source: wp-content/plugins/ink-core/src/Kernel/Tier.php, Entitlement/MembershipStatus.php (enum convention)]
- [Source: docs/ui-copy-translations.md lines 677–678; _bmad-output/project-context.md#three-layer, #conflation-rule, #Afrikaans-first]

## Dev Agent Record

### Agent Model Used

claude-opus-4-8 (BMAD dev-story loop, Epic 9)

### Debug Log References

- `composer stan` / `composer deptrac` run outside the sandbox. stan OK; deptrac 3 pre-existing (0 new — Social→I18n/Content already allowed).
- Added WP `$wpdb` output-format constants (`ARRAY_A`/`ARRAY_N`/`OBJECT`) to `tests/bootstrap.php` — `RatingStore` passes `ARRAY_A` to `get_row()/get_results()` and WordPress is not loaded for the unit suite (same harness-stub approach as `ABSPATH`/`WP_User`).

### Completion Notes List

- **Held-for-moderation is the load-bearing behaviour**: `RatingStore::rate()` upserts every submission as `RatingStatus::Hangend` (never auto-public); the public reads (`aggregate()`, `approvedReviews()`) filter `goedgekeur` ONLY. Asserted non-vacuously — the queries bind the approved status, so a pending review can never surface (POPIA). With no approval path yet (18.4), the public Lesergradering is simply empty — the inert-until-dependency pattern (R7/R8).
- **`ink_ratings` table** (AD-5): `UNIQUE (user_id, skrywer_id)` = one rating per rater per writer (re-submit upserts + resets to `hangend`); `KEY (skrywer_id, status)` serves the public approved-ratings query. `RatingStatus` closed enum (hangend/goedgekeur/verwerp, lowercase-Afrikaans values).
- **REST `ink/v1/leseroordeel`** (POST): login + nonce only (never entitlement/tier); pure `validate()` rejects self / non-user / score outside 1–5; review sanitized with `sanitize_textarea_field`.
- **Display on the Skrywerprofiel**: a Lesergradering section (average + verb-less `leseroordeelLabel`) + approved reviews; empty "Nog geen oordele nie." state. The `ink/leseroordeel-vorm` form block (logged-in non-self) is embedded in `author.html`; the held-for-moderation note is shown in the form.
- **Rating ≠ Gradering**: reader Lesergradering is entirely separate from the writer's competition Gradering. All 11 Social follow/pin/rating files pass the extended conflation `CodeScan` (no Tiers/Entitlement). Exposed via `Social\Api`.
- **Copy**: "Lesergradering" + `_n()` "leseroordeel/leseroordele" (ui-copy 677–678, approved); the form labels are glossary-consistent authored Afrikaans (copy-debt to ratify, the 8.x precedent) — no AI Afrikaans; copy:scan clean.
- Tests 629→646 (+17); cs 0 errors; stan OK; copy:scan no new debt; deptrac 3 pre-existing (0 new).

### File List

- `wp-content/plugins/ink-core/src/Social/RatingStatus.php` (NEW — moderation status enum)
- `wp-content/plugins/ink-core/src/Social/RatingStore.php` (NEW — ink_ratings table; held-for-moderation reads)
- `wp-content/plugins/ink-core/src/Social/RatingController.php` (NEW — ink/v1/leseroordeel REST + validate)
- `wp-content/plugins/ink-core/src/Social/RatingForm.php` (NEW — ink/leseroordeel-vorm form block)
- `wp-content/plugins/ink-core/src/Social/SkrywerProfiel.php` (MOD — Lesergradering display, approved only)
- `wp-content/plugins/ink-core/src/Social/Api.php` (MOD — rating facade methods)
- `wp-content/plugins/ink-core/src/Social/Module.php` (MOD — wire rating controller + form)
- `wp-content/plugins/ink-core/ink-core.php` (MOD — Schema::register ink_ratings)
- `wp-content/themes/ink-foundation/templates/author.html` (MOD — embed the rating form)
- `wp-content/themes/ink-foundation/theme.json` (MOD — .ink-leseroordeel / lesergradering styles)
- `tests/bootstrap.php` (MOD — ARRAY_A/ARRAY_N/OBJECT constants)
- `tests/Unit/Social/RatingStoreTest.php` (NEW)
- `tests/Unit/Social/RatingControllerTest.php` (NEW)
- `tests/Unit/Social/RatingFormTest.php` (NEW)
- `tests/Unit/Social/SkrywerProfielTest.php` (MOD — Lesergradering render)
- `tests/Unit/Social/FollowControllerTest.php` (MOD — conflation CodeScan extended to rating files)
- `_bmad-output/implementation-artifacts/9-6-reader-ratings-reviews.md` (NEW — this story)
- `_bmad-output/implementation-artifacts/sprint-status.yaml` (MOD — 9.6 status)

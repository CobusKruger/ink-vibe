---
baseline_commit: 3c78a6f8c0a1740c4d6bde664f639d95968e7c13
---

# Story 14.1: borg CPT

Status: review

<!-- Note: Validation is optional. Run validate-create-story for quality check before dev-story. -->

## Story

As a redakteur,
I want a sponsor model,
so that I can manage sponsor details. (FR-58)

## Acceptance Criteria

**Given** `borg`
**When** edited
**Then** fields include name, logo variants, link, `sponsor_tier`, campaign start/end, and placement preferences.

**Deferred from Epic 2 review (see deferred-work.md):** grant `MANAGE_SPONSORS` to the sponsor-admin role and reconcile the REST-vs-meta-box capability divergence on `borg` meta — the 2.4 field `auth_callback` gates on `MANAGE_SPONSORS` (granted to no role yet) while the meta-box save uses `edit_post`, so REST/block-editor writes are blocked until the cap is mapped. (Note: `borg` single pages are intentionally publicly reachable — owner decision 2026-06-21.)

Decomposed:

1. A new `Ink\Sponsors` module is wired into the Kernel: `Sponsors\Module` (the existing reserved stub) is registered in `ink-core.php` as `sponsors` (mirrors the `inkpols`/`challenges` bootstrap). `register()` stays a thin no-op at 14.1 (the model + facade are stateless reads; the homepage strip 14.3 and recognition section 14.4 add render hooks later).
2. The five FR-58 sponsor fields are persisted on the `borg` CPT — **link, sponsor_tier, campaign start date, campaign end date, placement** — via the EXISTING `Content\FieldSets` registration (Story 2.4): `ink_borg_link`, `ink_borg_tier`, `ink_borg_start_date`, `ink_borg_end_date`, `ink_borg_placement`. The story CONSUMES these meta-key constants as the single source — it does **not** re-register the meta or re-type the `ink_borg_*` literals. The sponsor **name** is the post title; the **logo** is the featured image (`borg` supports `thumbnail`, Story 2.1) — there is no separate logo meta field.
3. A `Sponsors\Sponsor` readonly value object is the single read-model for a sponsor: `Sponsor::forPost(int|\WP_Post)` reads the meta off the authoritative post into typed, default-safe properties (`postId:int`, `name:string` [the title], `link:string`, `tier:string`, `startDate:string`, `endDate:string`, `placement:string`). A missing/empty meta degrades to the typed empty default (`''`/`0`) — never a fatal or a malformed value. Scalar coercion via `Kernel\Scalar`.
4. The value object exposes the attachment + URL resolvers the 14.3/14.4 surfaces consume: `logoUrl(string $size = 'medium'): string` (the featured-image URL, `''` when none resolves) and `hasLogo(): bool` (true only when a logo URL resolves). The campaign-window math (`isActive()` / rotation) is **Story 14.2** — 14.1 holds only the raw `startDate`/`endDate` strings; it does NOT compute the window.
5. A `Sponsors\Api` facade is the sole cross-module surface (AD-1): `Api::sponsorFor(int|\WP_Post): ?Sponsor` (null for a non-`borg` / non-existent post) and `Api::metaKeys(): list<string>` (the five keys, delegating to `FieldSets`). Other modules reach Sponsors through the facade, never its internals.
6. **Capability reconciliation (the deferred-from-Epic-2 item) is verified closed and locked with a regression test.** The fix already landed generically with Story 12.3 — `FieldSets::save()` enforces the per-CPT editorial cap (`Capabilities::MANAGE_SPONSORS` for `borg`, per `definitions()`), and `Capabilities::grantToEditor()` (Story 3.3) grants `MANAGE_SPONSORS` to `administrator` + `editor` at activation, so the `auth_callback` (REST) and the meta-box save now gate on the SAME cap. 14.1 adds the **borg-specific** save test (mirroring the existing 12.3 `uitdaging` tests): a user with `edit_post` but WITHOUT `ink_manage_sponsors` cannot write borg meta; a user with BOTH can. No production code change is required for the cap unless the test proves a gap.
7. Conflation-clean: the module reads only `Ink\Content` (the `borg` CPT slug + the `FieldSets` meta-key constants) + `Ink\Kernel` (`Scalar`) + WP core — **zero** `Ink\Tiers` / `Ink\Entitlement`. Managing/viewing a sponsor is editorial, never gated on writer tier or membership. A new deptrac edge `Sponsors -> Content` is added (mirrors `InkPols -> Content` 13.1 / `Challenges -> Content` 12.1); no Tiers/Entitlement edge.

## Tasks / Subtasks

- [x] Task 1: `Sponsors\Sponsor` read-model value object (AC: 3, 4, 7)
  - [x] Subtask 1.1: `final readonly` class with promoted typed props (`postId`, `name`, `link`, `tier`, `startDate`, `endDate`, `placement`).
  - [x] Subtask 1.2: `forPost(int|\WP_Post): self` — resolve the post, read each meta via the `FieldSets::BORG_*` constants, coerce with `Kernel\Scalar` (string), default-safe; `name` = `get_the_title()`.
  - [x] Subtask 1.3: `logoUrl(string $size = 'medium')` — featured-image URL via guarded `get_post_thumbnail_id()` + `wp_get_attachment_image_url()`, `''` when none; `hasLogo(): bool` derived from it.
  - [x] Subtask 1.4: NO window math here — `startDate`/`endDate` are raw strings consumed by 14.2's scheduler.
- [x] Task 2: `Sponsors\Api` facade (AC: 5, 7) — `sponsorFor()` (type-guarded → `Sponsor::forPost` or null for non-positive id / wrong CPT) + `metaKeys()` (delegates `FieldSets`).
- [x] Task 3: `Sponsors\Module` Kernel wiring (AC: 1) — register `addModule( 'sponsors', new Sponsors\Module() )` in `ink-core.php`; keep `register()` a documented no-op until 14.3/14.4.
- [x] Task 4: deptrac edge (AC: 7) — add `Sponsors -> Content` allowed edge (Kernel already allowed); NO Tiers/Entitlement edge.
- [x] Task 5: Capability reconciliation regression test (AC: 6) — in `tests/Unit/Content/FieldSetsTest.php`, added a `borg` pair mirroring the 12.3 `uitdaging` tests + a structural test (auth_callback gates on MANAGE_SPONSORS for every borg field; cap is in the activation grant set). Verified (no production change needed) that `definitions()['borg']['cap'] === Capabilities::MANAGE_SPONSORS` and `Capabilities::all()` includes it.
- [x] Task 6: Tests — `tests/Unit/Sponsors/SponsorTest.php` (forPost meta read + default-safety; logoUrl/hasLogo via stubs, true/false/dangling/non-positive id) + `tests/Unit/Sponsors/ApiTest.php` (sponsorFor type-guard + null path; metaKeys = the five keys).
- [x] Task 7: Gates — `composer test:unit`, `composer cs`, `composer stan`, `composer deptrac`, `composer copy:scan` all green; counts in Completion Notes.

## Dev Notes

- **The CPT + meta already exist — this story is the read-model layer.** `borg` is registered in `Content\PostTypes` (public, `archive => false` — rendered on the "Ons borge" page not its own archive, supports `title`/`editor`/`thumbnail`) and the five sponsor fields are registered + admin-saved in `Content\FieldSets` (Story 2.4). 14.1 does NOT re-register either — it builds the `Ink\Sponsors` module that surfaces the existing data, EXACTLY as Story 13.1 built `Ink\InkPols` on the Epic-2 `inkpols_uitgawe` CPT, and Epic 12 built `Ink\Challenges` on the `uitdaging` CPT. [Source: wp-content/plugins/ink-core/src/Content/PostTypes.php:63,208-216; src/Content/FieldSets.php:56-61,309-349]
- **Meta-key single source:** consume `FieldSets::BORG_LINK` / `BORG_TIER` / `BORG_START_DATE` / `BORG_END_DATE` / `BORG_PLACEMENT` — never inline the `ink_borg_*` literals. `link` is `esc_url_raw`-sanitised; `tier`/`placement` are `sanitize_text_field`; `start_date`/`end_date` are `Y-m-d` via `FieldSets::sanitizeDate`. [Source: src/Content/FieldSets.php:56-61,312-347]
- **`name` = post title, `logo` = featured image.** The AC's "name" is the `borg` post title (`get_the_title`); "logo variants" is the featured image at requested registered sizes — `borg` supports `thumbnail` (Story 2.1). There is NO `ink_borg_logo` meta; do not invent one. `logoUrl()` resolves `get_post_thumbnail_id()` → `wp_get_attachment_image_url()`, guarded `function_exists`, failing to `''`. Mirror `InkPols\Issue::coverUrl()` exactly (the guarded attachment-resolver house style). [Source: src/Content/PostTypes.php:211; src/InkPols/Issue.php:162-170]
- **`sponsor_tier` stays a sanitised string at 14.1 (controlled-vocabulary enum deferred — glossary gate).** The `FieldSets` 2.4 note flagged "the controlled `SponsorTier` value set ... are Epic 14", but introducing a controlled `enum` requires its Afrikaans value labels (borgvlak names) to be authored in `docs/afrikaans-terms.md` FIRST (the glossary-first rule — project-context "A new concept is added to the glossary before it appears in code or UI"). No sponsor-tier vocabulary is authored yet, so 14.1 keeps `tier` a free-text `string` in the VO (the same shape `FieldSets` persists) and does NOT invent goud/silwer/brons sponsor labels (which would also conflate with the writer-Gradering value set). Route the controlled value set through the copy-debt workflow when/if ordering by tier is actually needed (14.3/14.4 sort by recency, not tier). [Source: src/Content/FieldSets.php:32-33; _bmad-output/project-context.md "Afrikaans-first"]
- **Scalar coercion:** use `Kernel\Scalar::asString()` for the string meta reads (the shared helper) — do not hand-roll `(string)` casts on `get_post_meta` returns. [Source: src/Kernel/Scalar.php:70]
- **House style (read-model VO + facade):** mirror `InkPols\Issue` + `InkPols\Api` (13.1) and `Library\Api` — the thin cross-module facade with a `forPost`/`*For` type-guard. Pure derived helpers stay unit-testable; WP-touching reads (`forPost`, `logoUrl`) use guarded core calls so the unit suite mocks them. [Source: src/InkPols/Issue.php; src/InkPols/Api.php; src/InkPols/Module.php]
- **Capability reconciliation is ALREADY closed generically — verify + lock, do not re-fix.** `FieldSets::save()` (lines 211-219) enforces `current_user_can( definitions()[post_type]['cap'] )` for EVERY CPT, and `definitions()['borg']['cap']` is `Capabilities::MANAGE_SPONSORS` (line 310). `Capabilities::all()` includes `MANAGE_SPONSORS` (line 54) and `grantToEditor()` grants all four caps to `administrator`+`editor` at activation (Story 3.3). So the REST `auth_callback` and the meta-box save gate on the SAME cap, granted to real roles — the divergence the deferred note describes is closed. 14.1's job is the **borg-specific regression test** mirroring the 12.3 `uitdaging` tests (which only cover `uitdaging`). Only touch production code if the test exposes a real gap. [Source: src/Content/FieldSets.php:207-219,309-310; src/Kernel/Capabilities.php:54,67-74,111-123; tests/Unit/Content/FieldSetsTest.php:159-208]
- **Conflation rule:** sponsors are editorial content — there is NO tier/entitlement gate anywhere in the model. Keep the module free of `Ink\Tiers`/`Ink\Entitlement` (the 13.1/12.1/10.1 precedent). [Source: _bmad-output/project-context.md "THE conflation rule"]
- **Testing rules (standing, project-context):** Brain-Monkey isolation — if `logoUrl()` is tested via function-absence, beware Brain Monkey leaves a stubbed `wp_*` defined process-wide; prefer exercising the resolver via the stub returning `''`/an id (the 13.1 `displayDate` precedent). Test the OUTCOME (the URL/`bool` we return, the meta we read), not a mock's internal call shape. Guardrail tests must be non-vacuous — the cap-denial test must prove the write WOULD happen with the cap. [Source: _bmad-output/project-context.md "Testing Rules"; src/InkPols/Issue.php:86 (Brain-Monkey note in 13.1)]

### Project Structure Notes

- New: `wp-content/plugins/ink-core/src/Sponsors/Sponsor.php`, `tests/Unit/Sponsors/SponsorTest.php`, `tests/Unit/Sponsors/ApiTest.php`.
- Modified: `wp-content/plugins/ink-core/src/Sponsors/Api.php` (fill the reserved facade), `wp-content/plugins/ink-core/src/Sponsors/Module.php` (doc-update the no-op for 14.x), `wp-content/plugins/ink-core/ink-core.php` (register the `sponsors` module), `deptrac.yaml` (Sponsors->Content edge), `tests/Unit/Content/FieldSetsTest.php` (borg cap pair).
- No theme files in 14.1 (the homepage strip is 14.3, the recognition section is 14.4). No new meta, no new CPT, no new Terms keys, no new enum.

### References

- [Source: _bmad-output/planning-artifacts/epics.md#Story 14.1]
- [Source: _bmad-output/planning-artifacts/prds/prd-ink-vibe-2026-06-14/prd.md#FR-58] (Sponsor model, scheduling & placement)
- [Source: wp-content/plugins/ink-core/src/Content/FieldSets.php] — existing borg meta + cap reconciliation (2.4 / 12.3)
- [Source: wp-content/plugins/ink-core/src/Content/PostTypes.php] — borg CPT (2.1)
- [Source: wp-content/plugins/ink-core/src/Kernel/Capabilities.php] — MANAGE_SPONSORS grant (3.3)
- [Source: wp-content/plugins/ink-core/src/InkPols/Issue.php + Api.php + Module.php] — read-model VO + facade house style (13.1)
- [Source: tests/Unit/Content/FieldSetsTest.php:159-208] — 12.3 cap-reconciliation test pattern to mirror for borg

## Dev Agent Record

### Agent Model Used

claude-opus-4-8

### Debug Log References

### Completion Notes List

- Built the `Ink\Sponsors` module read-model + facade over the EXISTING Epic-2 `borg` CPT (2.1) + its five sponsor fields (2.4) — the same "module over an Epic-2 CPT" shape as InkPols (13.1) and Challenges (12.1). No CPT/meta re-registration: the `Sponsor` VO reads via the `FieldSets::BORG_*` constants (single source) and `Kernel\Scalar::asString` coercion (default-safe).
- `Sponsor` exposes `name` (post title), the five meta fields, and the `logoUrl()`/`hasLogo()` featured-image resolvers (guarded `get_post_thumbnail_id` → `wp_get_attachment_image_url`, fail-safe to `''`/`false`; the `InkPols\Issue::coverUrl` precedent). The campaign-window math is deliberately NOT here (it's 14.2) — the VO holds only the raw `startDate`/`endDate` strings.
- `Api` facade: `sponsorFor()` type-guards (non-positive id → null before `get_post_type`; wrong CPT → null) and `metaKeys()` delegates to `FieldSets`. `Module` registered as `sponsors` in `ink-core.php`; `register()` is a documented no-op at 14.1 (render hooks land in 14.3/14.4).
- **`sponsor_tier` kept as a sanitised string (controlled-vocabulary enum deferred — glossary gate).** Introducing a `SponsorTier` enum needs its Afrikaans borgvlak labels authored in `docs/afrikaans-terms.md` first (project-context "a new concept is added to the glossary before it appears in code or UI"), and would risk conflating with the writer-Gradering brons/silwer/goud value set. Routed to the copy-debt workflow if tier-ordering is ever needed (14.3/14.4 sort by recency, not tier).
- **Capability reconciliation (deferred-from-Epic-2) verified CLOSED + locked, no production change.** The generic 12.3 fix in `FieldSets::save()` already enforces `current_user_can( MANAGE_SPONSORS )` for `borg` (per `definitions()`), and `Capabilities::grantToEditor()` (3.3) grants it to admin+editor at activation — so the REST `auth_callback` and the meta-box save gate on the SAME, granted cap. Added the borg-specific regression pair (deny without the cap / write with it — non-vacuous) + a structural test (every borg field's `auth_callback` gates on MANAGE_SPONSORS; the cap is in `Capabilities::all()`).
- **Pre-existing Epic-13 cs errors cleaned up (out of band, to keep the Epic-14 `composer cs` gate green):** `InkPols\Archive.php` used a disallowed short ternary (`?:`) as a sort tiebreak — rewritten as an explicit `0 !== $by_date ? … : …` block closure (behaviour-identical: strcmp only returns the falsy `0` on equal dates); `InkPols\Migration.php` had an inline comment ending in `)` — reworded to end in a full stop. Both were shipped red at the Epic-13 merge (`3c78a6f`) and are unrelated to 14.1 logic; zero behaviour change.
- Conflation-clean: `Sponsors -> Content` + `Sponsors -> Kernel` only; zero Tiers/Entitlement (deptrac edge added, mirrors `InkPols -> Content` 13.1 / `Challenges -> Content` 12.1).
- **Gates:** `composer test:unit` → 878 passed / 1 skipped (+13: 6 Sponsor VO, 4 Api, 3 borg cap/structural), zero regressions; `composer cs` → **0 errors** (only the 2 documented pre-existing slow-query WARNINGS in `Engagement\ResponseStore`/`SuggestedReads`); `composer stan` → No errors (149 files); `composer deptrac` → 3 violations = the documented PRE-EXISTING `Kernel\Activation -> Content\PostTypes` baseline, **no new edge** (`Sponsors -> Content` allowed; 466 allowed); `composer copy:scan` → no new placeholder debt (6 known gaps unchanged; 14.1 adds no user-facing copy).

### File List

- `wp-content/plugins/ink-core/src/Sponsors/Sponsor.php` (new)
- `wp-content/plugins/ink-core/src/Sponsors/Api.php` (modified — filled the reserved facade)
- `wp-content/plugins/ink-core/src/Sponsors/Module.php` (modified — read-model module doc, no-op register for 14.x)
- `wp-content/plugins/ink-core/ink-core.php` (modified — registered the `sponsors` module)
- `deptrac.yaml` (modified — Sponsors->Content edge)
- `tests/Unit/Sponsors/SponsorTest.php` (new)
- `tests/Unit/Sponsors/ApiTest.php` (new)
- `tests/Unit/Content/FieldSetsTest.php` (modified — borg cap-reconciliation pair + structural cap test)
- `wp-content/plugins/ink-core/src/InkPols/Archive.php` (modified — pre-existing cs fix: short ternary → explicit closure)
- `wp-content/plugins/ink-core/src/InkPols/Migration.php` (modified — pre-existing cs fix: inline-comment end char)

## Change Log

- 2026-06-28: Story 14.1 implemented — the `Ink\Sponsors` module read-model (`Sponsor` VO + `Api` facade) over the existing Epic-2 `borg` CPT/meta; verified + locked the deferred MANAGE_SPONSORS cap reconciliation; cleaned 2 pre-existing Epic-13 cs errors. Status → review.

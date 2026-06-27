# Story 12.3: Challenge metadata

Status: review

## Story

As an ink-core developer,
I want challenge metadata with monthly cadence,
so that rounds are well-formed and time-correct. (FR-47)

## Acceptance Criteria

**Given** challenge meta
**When** stored
**Then** `challenge_theme` and `challenge_deadline` exist with a **monthly** cadence
**And** all times are SAST; the deadline is inclusive through 23:59:59 SAST; after deadline, entries are frozen for judging.

**Deferred from Epic 2 review:** grant `MANAGE_CHALLENGES` to the challenge-admin role and reconcile the REST-vs-meta-box capability divergence on `uitdaging` meta — the 2.4 field `auth_callback` gates on `MANAGE_CHALLENGES` while the meta-box save uses `edit_post`, so the two write paths enforce different capabilities.

Decomposed:

1. The `uitdaging` theme + deadline meta (`ink_uitdaging_theme`, `ink_uitdaging_deadline`) exist and are SAST-typed (already from Epic 2) — this story formalises the **monthly cadence** derivation + the judging freeze and closes the capability divergence.
2. A `Challenges\Cadence` helper derives the monthly round period from a deadline: `periodKey()` → `YYYY-MM`, `periodLabel()` → Afrikaans `"Oktober 2026"` (a single source for the month names that the winner labels — "Oktober Goud-wenner" — consume), and `monthName(1..12)`.
3. `Cadence::entriesFrozen($deadline, $now)` is the named judging-freeze single source: `true` once `now` is past the inclusive end-of-day-SAST deadline (the inverse of `Sast::isThroughEndOfDay`) — "after deadline, entries are frozen for judging".
4. **Capability reconciliation:** `FieldSets::save()` (the `save_post` meta-box path) now also enforces the per-CPT editorial capability (`$def['cap']`, = `MANAGE_CHALLENGES` for uitdaging) in addition to `edit_post`, so the classic/meta-box write path matches the REST `auth_callback` gate. `MANAGE_CHALLENGES` is already granted to admin+editor at activation (Story 3.3 `Capabilities::grantToEditor`), so editors/admins keep write access; lesser roles can no longer bypass the editorial gate via the meta box.
5. Admin label glossary-alignment: the uitdaging deadline field label reads **Sluitingsdatum** (the glossary term, line 124), not the "Sperdatum" drift.

## Tasks / Subtasks

- [x] Task 1: `Challenges\Cadence` — Afrikaans month-name single source; `monthName()`, `periodKey()` (SAST YYYY-MM), `periodLabel()` ("Oktober 2026"), `entriesFrozen()` (delegates to `Sast`). Pure, Kernel-only.
- [x] Task 2: Capability reconciliation in `Content\FieldSets::save()` — now enforces `current_user_can( $def['cap'] )` alongside `edit_post`, matching the REST `auth_callback`.
- [x] Task 3: Glossary-aligned the uitdaging deadline admin label "Sperdatum" → "Sluitingsdatum".
- [x] Task 4: Tests — `CadenceTest.php` (5 cases incl. SAST month-rollover) + `FieldSetsTest.php` (+2 save-cap cases). Added a guarded `WP_Post` unit stub (`tests/stubs/class-wp-post.php` + bootstrap wiring).
- [x] Task 5: Gates — all green; no new deptrac edge (Cadence is Challenges->Kernel only).

## Dev Notes

- The deadline boundary single source is `Kernel\Sast::isThroughEndOfDay` (AD-2/AD-3, inclusive 23:59:59 SAST). `Cadence::entriesFrozen` is its named inverse for the judging context. [Source: src/Kernel/Sast.php:100]
- The REST/meta-box divergence: `register_post_meta` `auth_callback` → `current_user_can($cap)`; `save()` → `current_user_can('edit_post')`. Reconcile by adding the `$cap` check to `save()`. [Source: src/Content/FieldSets.php:98,207]
- `MANAGE_CHALLENGES` granted to admin+editor at activation via `Capabilities::grantToEditor` (called from `Activation::activate`). [Source: src/Kernel/Capabilities.php:111; src/Kernel/Activation.php:72]
- Afrikaans months are standard (not contested INK copy); kept as a documented `Cadence` constant, the single source that feeds the monthly period label + the Tiers winner label period ("Oktober Goud-wenner", `Tiers\Api::winnerLabel`). [Source: src/Tiers/Api.php:321]
- Cadence is in the Challenges layer (deptrac Challenges->Kernel only for this file — no Content edge needed).

### Project Structure Notes

- New: `src/Challenges/Cadence.php`, `tests/Unit/Challenges/CadenceTest.php`.
- Modified: `src/Content/FieldSets.php` (save cap + label), `tests/Unit/Content/FieldSetsTest.php`.

### References

- [Source: _bmad-output/planning-artifacts/epics.md#Story 12.3]
- [Source: docs/afrikaans-terms.md] lines 122-125

## Dev Agent Record

### Agent Model Used

claude-opus-4-8

### Debug Log References

### Completion Notes List

- Formalised the monthly cadence in `Challenges\Cadence`: SAST-correct `periodKey`/`periodLabel` (the Afrikaans month-name single source that the Tiers winner label "Oktober Goud-wenner" consumes) + the named `entriesFrozen` judging-freeze delegating to the `Sast` boundary. The theme+deadline meta itself already existed from Epic 2; this story made the cadence + freeze first-class.
- Closed the Epic-2 capability divergence: `FieldSets::save()` now also requires the per-CPT editorial cap (uitdaging → `MANAGE_CHALLENGES`), matching the REST `auth_callback`. Verified `MANAGE_CHALLENGES` is granted to admin+editor at activation (`Capabilities::grantToEditor` ← `Activation::activate`), so no editorial regression; the divergence is closed (meta box can no longer bypass with bare `edit_post`).
- **Gates:** `composer test` → 784 passed / 2 skipped (+7); `composer cs` → 0 errors; `composer stan` → No errors; `composer deptrac` → 3 pre-existing violations only, no new edge.

### File List

- `wp-content/plugins/ink-core/src/Challenges/Cadence.php` (new)
- `wp-content/plugins/ink-core/src/Content/FieldSets.php` (modified — save() cap reconcile + Sluitingsdatum label)
- `tests/Unit/Challenges/CadenceTest.php` (new)
- `tests/Unit/Content/FieldSetsTest.php` (modified — +2 save-cap cases)
- `tests/stubs/class-wp-post.php` (new — unit WP_Post double)
- `tests/bootstrap.php` (modified — wire the WP_Post stub)

### Change Log

- 2026-06-27: Story 12.3 implemented — monthly cadence helper + judging-freeze + Epic-2 capability reconciliation + label alignment. Status → review.

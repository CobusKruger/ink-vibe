---
baseline_commit: 623951ecdd272482a6240c8569b371423d12136b
---

# Story 12A.6: Winner banner — per-rank / per-tier variants

Status: done

## Story

As a reader,
I want clear winner banners,
so that I can see who placed and at what rank/tier. (FR-50-R2, C9)

## Acceptance Criteria

**Given** the existing base banner design (home "Desember-wenner")
**When** variants render
**Then** **algehele wenner** (1st) vs **wenner** (2nd/3rd) variants exist with Brons/Silwer/Goud colour tokens and Meester = `primary #EA4015`
**And** colour is paired with text/icon (no colour-only encoding — a11y)
**And** the placement flag extends the entry placement record (already `ink_entry_placement`, Story 12.6).

## Tasks / Subtasks

- [x] Task 1: `Challenges\WinnerBanner` (pure presenter) (AC: 1,2)
  - [x] `variant(int $rank): string` — 'algehele' (1) / 'wenner' (2–3) / '' (invalid)
  - [x] `toHtml(int $rank, string $grade, string $label): string` — `<span class="ink-wenner-banier ink-wenner-banier--{variant} ink-gradering--{grade}">` + an `aria-hidden` mark + a TEXT label (colour always paired with text — a11y); '' for an invalid rank
  - [x] `forPost(int $post_id): string` — reads `Placements::placementFor` + the entry-time gradering snapshot (`Entry::GRADERING_META_KEY`), composes the placement label, renders (a winning work shows its banner)
- [x] Task 2: theme bridge `ink_foundation_wenner_banier( int $post_id )` (guarded, mirrors `ink_foundation_gradering_badge`) + `.ink-wenner-banier` structural CSS in `theme.json` (tokens only — reuses the `.ink-gradering--{tier}` colour convention; Meester→`primary`)
- [x] Task 3: Tests — `WinnerBannerTest`: variant boundaries; toHtml emits the variant + tier classes + a TEXT label + an `aria-hidden` mark (non-colour-only); invalid rank → ''; forPost reads placement + gradering and renders
- [x] Task 4: Gates — test:unit / cs / stan / deptrac / copy:scan green. No new deptrac edge (Placements/Entry same module; Terms uncovered; Scalar = Kernel). New Afrikaans label via the existing `Placements::placementLabel` (algehele wenner / wenner) — no new copy.

## Dev Notes

- **Reuse the 5.4 colour convention:** the banner carries `ink-gradering--{tier}` so Meester→`primary #EA4015` works today (theme.json `.ink-gradering--meester{color:primary}`). Brons/Silwer/Goud are class hooks; explicit metallic swatches are a **design-handoff item** (the Lovable tokens for tier colours aren't in `theme.json` yet — adding invented hexes would violate Gate A / the "don't invent design" rule). Flagged for the design sync, like copy-debt. [Source: theme.json `.ink-gradering`; functions.php `ink_foundation_gradering_badge`; src/Tiers/GraderingView.php cssModifier]
- **a11y (no colour-only):** the mark glyph is `aria-hidden`, the placement label is real text — so rank/tier is conveyed by text, not colour alone (mirrors the gradering badge). [Source: functions.php:533]
- **Placement flag already extends the entry record** via `ink_entry_placement` (Story 12.6) — no schema change here. The banner READS it (`Placements::placementFor`) + the entry-time gradering snapshot. [Source: src/Challenges/Placements.php:44; src/Challenges/Entry.php:56]
- Pure presenter + a thin `forPost` impure reader (house style); test the OUTCOME (the markup WE emit). [Source: project-context.md]

### Project Structure Notes

- New: `src/Challenges/WinnerBanner.php`, `tests/Unit/Challenges/WinnerBannerTest.php`.
- Modified: `wp-content/themes/ink-foundation/functions.php` (theme bridge), `theme.json` (`.ink-wenner-banier` CSS).
- No new deptrac edge.

### References

- [Source: _bmad-output/planning-artifacts/epics.md#Story 12A.6]
- [Source: src/Challenges/Placements.php (12.6), Entry.php (12.4); theme functions.php gradering badge (5.4)]

## Dev Agent Record

### Agent Model Used

claude-opus-4-8

### Debug Log References

- None.

### Completion Notes List

- `Challenges\WinnerBanner` (pure presenter): `variant()` (algehele/wenner), `toHtml(rank, grade, label)` emitting `ink-wenner-banier--{variant}` + `ink-gradering--{tier}` classes with an `aria-hidden` mark + a real text label (a11y — never colour-only), and `forPost()` reading `Placements::placementFor` + the entry-time gradering snapshot. Invalid rank → '' (no banner on a non-placed work).
- Theme bridge `ink_foundation_wenner_banier( $post_id )` (guarded, mirrors `ink_foundation_gradering_badge`) + token-only `.ink-wenner-banier` CSS in theme.json (reuses the `.ink-gradering--{tier}` colour convention; Meester→primary works today).
- **Design-handoff gap (flagged):** explicit Brons/Silwer/Goud swatches aren't in theme.json (only Meester is coloured, per the 5.4 precedent); the tier classes are hooks. Inventing metallic hexes would violate Gate A / "don't invent design" — flagged for the design sync, like copy-debt.
- Placement flag already extends the entry record (`ink_entry_placement`, 12.6) — no schema change. Conflation-clean (no new deptrac edge).
- Gates: `composer test:unit` 1171→1178 (+7), 1 skipped; `cs` 0 errors; `stan` OK; `deptrac` 3 pre-existing only; `copy:scan` clean.

### File List

- `wp-content/plugins/ink-core/src/Challenges/WinnerBanner.php` (new)
- `wp-content/themes/ink-foundation/functions.php` (modified — ink_foundation_wenner_banier bridge)
- `wp-content/themes/ink-foundation/theme.json` (modified — .ink-wenner-banier CSS)
- `tests/Unit/Challenges/WinnerBannerTest.php` (new)

### Change Log

- 2026-06-29 — Story 12A.6 implemented: winner-banner per-rank/per-tier variants (pure WinnerBanner presenter + theme bridge + token CSS), a11y text+icon. 7 unit tests. Suite 1171→1178. Brons/Silwer/Goud swatches flagged as a design-handoff item.

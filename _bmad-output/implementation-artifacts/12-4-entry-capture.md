# Story 12.4: Entry capture

Status: review

## Story

As a skrywer,
I want my entry linked to the round,
so that it is judged in the right context. (FR-48, UJ-4)

## Acceptance Criteria

**Given** an open uitdaging
**When** I submit an inskrywing
**Then** it links to the round via `uitdagingsrondte`
**And** at most 3 entries of each content type per uitdaging are allowed; the entry-time Gradering pool governs judging.

**Deferred from Epic 2 review:** confirm + test the deliberate model that `uitdagingsrondte` (and `ster_gradering`) attach to the entry / works, NOT the `uitdaging` CPT (the entry record is authoritative, AD-5).

Decomposed:

1. Round linking already exists (`Submission\ChallengeLinking::link()` writes the `uitdagingsrondte` term for each open ticked uitdaging) — this story adds the per-type cap + the entry-time Gradering snapshot and confirms the AD-5 attachment model.
2. **Per-type cap:** `ChallengeLinking::link()` links at most **3** entries of a given content type, per author, per uitdaging. A 4th entry of that type is not linked (the bydrae still saves; the round link is refused) — `withinCap()` pure rule + an overridable `entryCountFor()` count seam.
3. **Entry-time Gradering pool:** when an entry is linked, the writer's **current Gradering** is snapshotted onto the entry (the authoritative entry record, AD-5) as `ink_entry_gradering` meta — this is the pool that governs judging (Brons-vs-Brons …, Story 12.5). `Challenges\Entry` does this via a runtime hook fired after linking (`ink/uitdaging_entry_linked`), so Submission carries no `Ink\Tiers` reference (THE conflation rule holds — Gradering never gates submission, only records the judging pool after the fact).
4. **AD-5 confirmation:** a `TaxonomiesTest` assertion that `uitdagingsrondte` + `ster_gradering` attach to the works object types (bydraes + biblioteek_item) and NOT to the `uitdaging` CPT.

## Tasks / Subtasks

- [x] Task 1: `ChallengeLinking` per-type cap — `MAX_ENTRIES_PER_TYPE = 3`, pure `withinCap(int $existing): bool`, overridable `entryCountFor(int $post_id, int $uitdaging_id): int` (counts the author's other entries of the same type in this round), enforced in `link()`.
- [x] Task 2: `ChallengeLinking` fires `ink/uitdaging_entry_linked` (literal, INK convention) with `($post_id, $linked_ids)` after a non-empty link — the seam for the Gradering snapshot. No Tiers reference in Submission.
- [x] Task 3: `Challenges\Entry` — `HOOK` const + `GRADERING_META_KEY = 'ink_entry_gradering'`; `register()` subscribes the hook; `onEntryLinked()` snapshots `Tiers\Api::forUser(author)->value` onto the entry (idempotent — first link wins), via an overridable `gradingValueFor()` seam.
- [x] Task 4: Module wiring — `Challenges\Module::register()` registers `Entry`.
- [x] Task 5: deptrac — add `Challenges -> Tiers` edge (reads Gradering for the pool snapshot via the Tiers Api facade; the module charter's documented dependency). Conflation-clean (no Entitlement edge).
- [x] Task 6: Tests — `ChallengeLinkingTest` (cap: 4th of a type skipped; existing link test seam updated) + `Challenges\EntryTest` (snapshots the grading value; idempotent; ignores a non-positive author) + `TaxonomiesTest` AD-5 attachment assertion.
- [x] Task 7: Gates — test/cs/stan/deptrac green; only the documented new `Challenges -> Tiers` edge.

## Dev Notes

- `link()` already dedupes + skips closed/invalid ticks and returns the linked ids — add the cap check before `assign()` and fire the hook after the loop. [Source: src/Submission/ChallengeLinking.php:105]
- Cross-module hook convention: the firer uses the literal (`do_action('ink/tier_promoted', …)` in `Tiers\Api`), the listener defines the `HOOK` constant (`Tiers\PromotionEmails::HOOK`). Mirror it: `ChallengeLinking` fires the literal, `Challenges\Entry::HOOK` is the listener const. No deptrac edge from a runtime hook. [Source: src/Tiers/Api.php:148; src/Tiers/PromotionEmails.php:48]
- Entry-time pool = `Tiers\Api::forUser(int): Tier` (static) → `Tier->value` (brons/silwer/goud/meester). Stored as entry meta so 12.5 can group by pool and 12.6 can record placements per pool. [Source: src/Tiers/Api.php:48; src/Kernel/Tier.php:39]
- AD-5: `uitdagingsrondte` + `ster_gradering` object_types = `$works` (bydraes + biblioteek_item), never `uitdaging`. [Source: src/Content/Taxonomies.php:131-142]
- Conflation: Submission must stay free of `Ink\Tiers` (the SubmissionForm docblock invariant). The snapshot lives in Challenges, decoupled via the hook. [Source: src/Submission/SubmissionForm.php:37]

### Project Structure Notes

- New: `src/Challenges/Entry.php`, `tests/Unit/Challenges/EntryTest.php`.
- Modified: `src/Submission/ChallengeLinking.php`, `src/Challenges/Module.php`, `deptrac.yaml`, `tests/Unit/Submission/ChallengeLinkingTest.php`, `tests/Unit/Content/TaxonomiesTest.php`.

### References

- [Source: _bmad-output/planning-artifacts/epics.md#Story 12.4]
- [Source: docs/afrikaans-terms.md] lines 125-126, 298-300

## Dev Agent Record

### Agent Model Used

claude-opus-4-8

### Debug Log References

### Completion Notes List

- Per-type cap enforced in `ChallengeLinking::link()` (`MAX_ENTRIES_PER_TYPE=3`, pure `withinCap`, overridable `entryCountFor` WP query seam) — a 4th entry of a type is not round-linked while the bydrae still saves.
- Entry-time Gradering snapshot decoupled from Submission via a runtime hook: `link()` fires `ink/uitdaging_entry_linked`; `Challenges\Entry` listens and writes `ink_entry_gradering` (the writer's `Tier->value`) once per entry (idempotent). Submission stays free of `Ink\Tiers` — THE conflation rule holds; the new edge is `Challenges -> Tiers` (read-only, Api facade, charter-documented, mirrors Discovery->Tiers).
- AD-5 deferred item closed: `TaxonomiesTest` now asserts `uitdagingsrondte` + `ster_gradering` attach to works (bydraes + biblioteek_item) and never to the `uitdaging` CPT.
- `Challenges\Entry` is non-final (seam class precedent) for the `gradingValueFor` test seam.
- **Gates:** `composer test` → 791 passed / 2 skipped (+7); `composer cs` → 0 errors; `composer stan` → No errors; `composer deptrac` → 3 pre-existing violations, new `Challenges -> Tiers` edge allowed (no new violation).

### File List

- `wp-content/plugins/ink-core/src/Challenges/Entry.php` (new)
- `wp-content/plugins/ink-core/src/Submission/ChallengeLinking.php` (modified — per-type cap + entry-linked hook)
- `wp-content/plugins/ink-core/src/Challenges/Module.php` (modified — register Entry)
- `deptrac.yaml` (modified — Challenges -> Tiers edge)
- `tests/Unit/Challenges/EntryTest.php` (new)
- `tests/Unit/Submission/ChallengeLinkingTest.php` (modified — cap cases + seam)
- `tests/Unit/Content/TaxonomiesTest.php` (modified — AD-5 attachment assertion)

### Change Log

- 2026-06-27: Story 12.4 implemented — per-type entry cap + entry-time Gradering snapshot (hook-decoupled) + AD-5 attachment confirmation. Status → review.

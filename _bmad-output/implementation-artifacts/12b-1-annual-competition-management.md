---
baseline_commit: d99cd0c080d291261e7268fd1197d64adee76d51
---

# Story 12B.1: Annual competition management

Status: done

<!-- Note: Validation is optional. Run validate-create-story for quality check before dev-story. -->

## Story

As a redakteur,
I want an annual-cadence competition reusing the monthly machinery,
so that the yearly competition is automated too. (R9)

## Acceptance Criteria

**Given** the existing adjudication pipeline (12A.1/12A.2/12A.3, 12A.4/12A.6/12A.7, 5.8)
**When** an annual competition runs
**Then** it reuses EntryID collation, paste-text ingestion + coverage report, winners post/banner/featuring, and auto-promotion on an **annual** cadence
**And** no new core mechanics are introduced (post-launch). *(Source doc mislabels this "R8"; it is R9.)*

Decomposed:

1. The whole adjudication pipeline (collation `12A.2`, ingestion + coverage `12A.3`, winners post `12A.4`, banner `12A.6`, featured feed `12A.7`, moderator feedback `12A.5`, auto-promotion `5.8`/`Placements`) is **already cadence-agnostic** — it keys off a round (a `uitdaging` post), its EntryIDs, its `Gradering × category` pools, and its stored deadline. The ONE place a round's identity is cadence-specific is the **period** derived by `Cadence` (monthly today: key `YYYY-MM`, label "Oktober 2026"). 12B.1 = make the period derivation **cadence-aware** and let a redakteur mark a round **annual**. No pipeline class changes.
2. A `CadenceType` value set (enum): `maandeliks` (monthly — the default, preserving every existing `uitdaging`) and `jaarliks` (annual). This is the persisted meta value + code ID, modelled as an `enum` per the project value-set rule (never an inline literal).
3. `Cadence::periodKey()` / `periodLabel()` take an optional `CadenceType` (default `Maandeliks`, so all current callers/tests are unchanged). Annual ⇒ period key `Y` (e.g. `2026`), period label the **year only** (`"2026"`) — which keeps `Tiers\Api::winnerLabel( Goud, "2026" )` ⇒ `"2026 Goud-wenner"` with **zero new front-end copy**.
4. A per-`uitdaging` cadence configuration: `FieldSets::UITDAGING_CADENCE` meta (`ink_uitdaging_cadence`) + a meta-box **select** so a redakteur picks the cadence; absent/invalid ⇒ `maandeliks`. `Cadence::forUitdaging()` resolves it; `Cadence::periodKeyFor()` / `periodLabelFor()` resolve-then-delegate so a caller needs only the `uitdaging` id + deadline.
5. `entriesFrozen()` is unchanged — the judging-freeze boundary is the stored deadline instant, identical for both cadences (the annual round simply has a year-end-ish deadline). Documented, not re-implemented.

## Tasks / Subtasks

- [x] **Task 1: `CadenceType` enum** (AC: 2) — **placed in `Ink\Kernel` (not `Ink\Challenges`)**: `wp-content/plugins/ink-core/src/Kernel/CadenceType.php`. Deptrac-driven (see Debug Log): `Content\FieldSets` must reference the enum to single-source the options + sanitiser, but `Content` may depend only on `Kernel` — so the value set is Kernel-owned, exactly mirroring `Ink\Kernel\Tier`. Backed string enum: `Maandeliks = 'maandeliks'`, `Jaarliks = 'jaarliks'`. Methods: `default()` (monthly), `fromMeta( mixed ): self` (default `Maandeliks` on `''`/null/non-string/unknown), `values()` (single-source list). **No `label()`** — presentation stays out of the enum (mirrors `Tier`); the Afrikaans option labels are inline admin chrome on the FieldSets field def.
- [x] **Task 2: `Cadence` becomes cadence-aware** (AC: 1, 3, 5) — `wp-content/plugins/ink-core/src/Challenges/Cadence.php`:
  - [x] `periodKey( \DateTimeInterface $deadline, CadenceType $cadence = CadenceType::Maandeliks ): string` — `Jaarliks` ⇒ SAST `Y`; `Maandeliks` ⇒ SAST `Y-m` (unchanged). Default arg keeps the existing 1-arg callers/tests green.
  - [x] `periodLabel( \DateTimeInterface $deadline, CadenceType $cadence = CadenceType::Maandeliks ): string` — `Jaarliks` ⇒ SAST `Y` (the bare year, e.g. `"2026"`); `Maandeliks` ⇒ "Oktober 2026" (unchanged). **No new copy** for the annual label (a numeral).
  - [x] `forUitdaging( int $uitdaging_id ): CadenceType` — read `FieldSets::UITDAGING_CADENCE` via `Scalar::asString` + `CadenceType::fromMeta`. Reads only `Ink\Content\FieldSets` + `Ink\Kernel\Scalar`/`CadenceType` (all already-allowed Challenges deps — deptrac confirmed no new edge).
  - [x] `periodKeyFor( int $uitdaging_id, \DateTimeInterface $deadline ): string` and `periodLabelFor( int $uitdaging_id, \DateTimeInterface $deadline ): string` — resolve cadence then delegate. The single entry point a cadence-agnostic caller uses.
  - [x] Leave `entriesFrozen()` and `monthName()` exactly as-is; extend the class doc to name the annual cadence + why the freeze boundary is cadence-independent.
- [x] **Task 3: per-`uitdaging` cadence config** (AC: 4) — `wp-content/plugins/ink-core/src/Content/FieldSets.php`:
  - [x] `public const UITDAGING_CADENCE = 'ink_uitdaging_cadence';`
  - [x] Add a `uitdaging` field def: `key` = `UITDAGING_CADENCE`, `label` = `__( 'Kadens', 'ink-core' )`, `type` = `'string'`, `input` = `'select'`, `options` = `[CadenceType::Maandeliks->value => __('Maandeliks'), CadenceType::Jaarliks->value => __('Jaarliks')]`, `sanitize` = new `self::sanitizeCadence` (coerce via `CadenceType::fromMeta(...)->value`; default `maandeliks`).
  - [x] Extend `renderBox()` with a `'select' === $field['input']` branch: render `<select>` with each `$field['options']` as `<option value=...>`, `selected()` on the stored value. Every attribute/label escaped at output.
  - [x] `save()` already routes through `$field['sanitize']` — no change beyond the new callback. `register()` registers the meta with `show_in_rest` + the sanitize callback (string default `''` ⇒ `fromMeta('')` ⇒ monthly). `definitions()` docblock extended with the optional `options` key.
- [x] **Task 4: glossary + UI-copy rows** (AC: 2, 4) — record the cadence concept before/with the code (glossary-first rule):
  - [x] `docs/afrikaans-terms.md` — added the **kadens** row in "Uitdagings en projekte": the maandelikse vs jaarlikse distinction, enum + meta key, the no-new-copy year-label rationale; flagged `Kadens`/`Maandeliks`/`Jaarliks` for human ratification.
  - [x] `docs/afrikaans-translation-sheet.md` §7 — added `CADENCE-FIELD-LABEL`/`CADENCE-OPT-MONTHLY`/`CADENCE-OPT-ANNUAL` for human ratification per the standing process. **`docs/ui-copy-translations.md` deliberately NOT touched:** it tracks *front-end* copy; admin meta-box labels (like the existing "Tema"/"Sluitingsdatum") are inline-authored Afrikaans source and are not tracked there — adding "Kadens" would diverge from that pattern. Glossary + translation-sheet are the consistent homes.
- [x] **Task 5: tests** — `tests/Unit/Kernel/CadenceTypeTest.php` (new — enum moved to Kernel) + `tests/Unit/Challenges/CadenceTest.php` (extended) + `tests/Unit/Content/FieldSetsTest.php` (extended):
  - [x] `CadenceType::fromMeta` — `'jaarliks'`⇒Jaarliks, `'maandeliks'`⇒Maandeliks, `''`/`'rubbish'`/`'Jaarliks'`(case)/null/int⇒Maandeliks; `values()` single-source.
  - [x] `periodKey`/`periodLabel` annual ⇒ `Y` / `"2026"` (+ SAST year-rollover near midnight); monthly default-arg path unchanged (existing `Y-m` / "Oktober 2026" + month-rollover assertions stay green).
  - [x] **Non-vacuous cadence proof:** one deadline, monthly≠annual for both key and label.
  - [x] `forUitdaging` / `periodKeyFor` / `periodLabelFor` — `get_post_meta` stubbed; `'jaarliks'`⇒annual, absent⇒monthly. Tests the OUTCOME (the period string WE derive).
  - [x] **Pipeline-reuse assertion:** annual period `"2026"` flows to `Tiers\Api::winnerLabel( Goud, "2026" )` ⇒ `"2026 Goud-wenner"` (the winners machinery reused unchanged).
  - [x] `sanitizeCadence` valid⇒kept, junk/empty⇒`'maandeliks'`; the cadence field registers + renders a `<select>` with both options (stored value preselected); the save path persists a selection through the sanitiser; metaKeys count 12→13.
- [x] **Task 6: gates** — `composer test:unit` 1184→**1199** (+15), 1 skipped; `cs` my files clean (3 pre-existing findings in untouched IngestionPage/ResponseStore/SuggestedReads); `stan` OK; `deptrac` 3 pre-existing violations only (`Kernel\Activation`→`Content`, untouched) — **no new edge**; `copy:scan` no new debt (8 known).

## Dev Notes

### Why this is "configuration, not new mechanics"
The 12A pipeline never branches on cadence. `Collation`, `Ingestion`, `Coverage`, `WinnersPost`, `WinnerBanner`, `FeaturedWinners`, `ModeratorFeedback`, `Placements`/`Pools` (auto-promotion via `5.8`) all operate on a *round* = a `uitdaging` post + its EntryIDs + `Gradering × category` pools + stored deadline. None of them read `Cadence`. The only cadence-specific fact is the **period** (the human-facing "which competition instance" label) — `Cadence` owns it, and today it only knows months. 12B.1 teaches `Cadence` the annual period and adds the per-round switch. That is the whole epic. [Source: src/Challenges/*.php; src/Challenges/Cadence.php:35]

### Files being modified (READ before editing — current state → change → preserve)
- **`src/Challenges/Cadence.php`** (UPDATE). *Today:* monthly-only helper — `periodKey`⇒`Y-m`, `periodLabel`⇒"Oktober 2026", `entriesFrozen` (delegates to `Sast::isThroughEndOfDay`), `monthName`, private `inSast`. Currently consumed only by its own test (it is the reserved single source for the period label that `Tiers\Api::winnerLabel` is designed to take). *Change:* add the optional `CadenceType` param to `periodKey`/`periodLabel` (default `Maandeliks`), add `forUitdaging`/`periodKeyFor`/`periodLabelFor`. *Preserve:* the exact monthly outputs and the `inSast` SAST-month-rollover behaviour; `entriesFrozen` unchanged. [Source: src/Challenges/Cadence.php]
- **`src/Content/FieldSets.php`** (UPDATE). *Today:* declarative per-CPT meta map + meta-box render/save; `renderBox` handles only `textarea` vs `<input type=…>`; `save` routes each field through its `sanitize` callback after nonce + `current_user_can($cap)` (the 12.3 capability-reconciliation gate — `MANAGE_CHALLENGES` for `uitdaging`). *Change:* add `UITDAGING_CADENCE` const + field def (select), a `select` branch in `renderBox`, and a `sanitizeCadence` callback. *Preserve:* the nonce→autosave/revision→`edit_post`→per-CPT-cap save gate; `register_post_meta` shape; every existing field's render/save. [Source: src/Content/FieldSets.php:53,133,186,289]

### Conventions to honour
- **Value set ⇒ enum** (`brons`/`silwer`/`goud`, `lof`/`insig`/`voorstel` precedent). `CadenceType` is the persisted DB value; never inline `'jaarliks'`/`'maandeliks'` elsewhere. [Source: project-context.md §Language Rules; src/Tiers/Tier.php]
- **Glossary-first.** Add the cadence concept to `docs/afrikaans-terms.md` before/with the code; admin labels are Afrikaans-authored source (no English `.mo`) — the established meta-box pattern ("Tema", "Sluitingsdatum"). `Jaarliks`/`Kadens` are standard Afrikaans but flag them in `docs/afrikaans-translation-sheet.md` for human ratification (no-AI-Afrikaans gate, belt-and-braces). [Source: project-context.md §Afrikaans-first; src/Content/FieldSets.php:295]
- **No new front-end copy:** the annual period label is the bare year (a numeral), so `winnerLabel` composes "2026 Goud-wenner" with no authored string. Deliberate — keeps copy-debt flat and avoids inventing a contested "Jaarwenner" term. If the owner later wants distinct annual phrasing ("Jaarwenner 2026"), that is a follow-up copy task, not 12B.1. [Source: src/Tiers/Api.php:356]
- **Test the OUTCOME** (the period string / label WE derive), Brain-Monkey-stub `get_post_meta`, reset no statics (Cadence/CadenceType are pure/stateless). Non-vacuous: assert monthly≠annual for the same deadline. [Source: project-context.md §Testing Rules]
- **Conflation-clean.** `Cadence::forUitdaging` reads `Content\FieldSets` + `Kernel\Scalar` only — both already-allowed Challenges edges. No `Entitlement`/`Tiers` edge introduced. Confirm `composer deptrac` shows no NEW violation (read the Violations count, not the pre-existing Kernel→Content legend note). [Source: ink-stan-sandbox-and-wpcli-phpstan memory]

### Known inherited gap (NOT in 12B.1 scope — flag for the epic review/retro)
The R12A **D1** follow-up is open: `Placements::arrange()`/`forRound()` collapse category winners to one-per-`Gradering` on the READ side (the featured slot inherits it via `WinnersPost::placedEntries`); the committed DATA is correct (rank stored per entry). 12B.1 does not touch the read model — annual rounds inherit the same per-category read-collapse the monthly rounds have. Adding the annual cadence does not worsen it, but it does mean an annual competition with per-category pools shows the same collapsed read view. **Carry D1 as the standing pre-launch follow-up; do not silently expand 12B.1 to fix it.** [Source: epic-12a-retro-2026-06-29.md §D1; epic-12a-carryforward memory]

### Project Structure Notes
- New: `src/Challenges/CadenceType.php`, `tests/Unit/Challenges/CadenceTypeTest.php`.
- Modified: `src/Challenges/Cadence.php`, `src/Content/FieldSets.php`, `tests/Unit/Challenges/CadenceTest.php`, `docs/afrikaans-terms.md`, `docs/ui-copy-translations.md`, `docs/afrikaans-translation-sheet.md`.
- No new deptrac edge expected (Challenges→Content + Challenges→Kernel are established). No new front-end copy ⇒ `copy:scan` baseline unchanged.

### References
- [Source: _bmad-output/planning-artifacts/epics.md#Story 12B.1]
- [Source: src/Challenges/Cadence.php (12.3 monthly cadence), Deadline.php (12.2)]
- [Source: src/Content/FieldSets.php (Epic 2 meta + 12.3 cap reconciliation)]
- [Source: src/Tiers/Api.php:356 winnerLabel( Tier, period )]
- [Source: epic-12a-retro-2026-06-29.md §5 Epic 12B preview + §D1 readiness flag]

## Dev Agent Record

### Agent Model Used

claude-opus-4-8

### Debug Log References

- **Deptrac-driven enum placement (the one design change from the story plan):** the story planned `Ink\Challenges\CadenceType`, but `Content\FieldSets` must reference the enum to single-source the field options + the `sanitizeCadence` coercion. `deptrac.yaml` allows `Content: [Kernel]` only — a `Content → Challenges` edge is forbidden. So `CadenceType` is **Kernel-owned** (`Ink\Kernel\CadenceType`), exactly the rationale that puts `Ink\Kernel\Tier` in Kernel ("so BOTH modules read it without an inter-module edge"). `Cadence` (Challenges) and `FieldSets` (Content) both read it edge-free. Its test moved to `tests/Unit/Kernel/CadenceTypeTest.php` accordingly.
- **Brain Monkey test fix:** initial `forUitdaging`/`periodKeyFor` tests used multiple `Functions\expect('get_post_meta')->with(...)` expectations; Mockery matched them greedily and returned the wrong stub. Switched to a single `Functions\when('get_post_meta')->alias(...)` keyed by post id — deterministic and clearer.

### Completion Notes List

- **What 12B.1 delivers:** the annual competition is *configuration over the existing 12A pipeline*, not new mechanics. The pipeline (collation/ingestion/coverage/winners-post/banner/featured/feedback/auto-promotion) never branches on cadence; the only cadence-specific fact is the round's **period**, owned by `Challenges\Cadence`. 12B.1 (1) adds a `CadenceType` value set, (2) makes `Cadence`'s period key/label cadence-aware (annual ⇒ year), (3) lets a redakteur mark a `uitdaging` annual via a new meta + meta-box select, defaulting to monthly so every existing round is untouched. No pipeline class changed.
- **No new front-end copy:** the annual period label is the bare year ("2026"), so `Tiers\Api::winnerLabel` composes "2026 Goud-wenner" with no authored string (asserted by a test). A distinct annual phrasing ("Jaarwenner") would be a future copy task, deliberately not invented here (no-AI-Afrikaans gate).
- **Backward compatibility:** `CadenceType::fromMeta('')`/absent/junk ⇒ `Maandeliks`; the `register_post_meta` string default is `''`. Every legacy `uitdaging` keeps its exact monthly period derivation — proven by the unchanged monthly assertions in `CadenceTest`.
- **Inherited gap NOT touched (flag for review/retro):** R12A **D1** — `Placements::arrange()`/`forRound()` collapse category winners to one-per-`Gradering` on the READ side (the featured slot inherits it). The committed DATA is correct. Annual rounds inherit the same monthly read-collapse; 12B.1 does not worsen or fix it. Still owed pre-launch / before per-category pools run at scale.
- **Pre-existing cs findings (not 12B.1):** `composer cs` reports 1 error (IngestionPage.php:368 `$_POST['bevestig']`) + 2 warnings (ResponseStore/SuggestedReads slow-query) in files **not in this diff** (byte-identical to HEAD). Flagged for the epic review; out of 12B.1 scope.
- **Gates:** `test:unit` 1199 passed / 1 skipped (+15); `cs` clean on all changed files; `stan` OK; `deptrac` no new edge (3 pre-existing `Kernel\Activation`→`Content` violations only); `copy:scan` no new debt.

### File List

- `wp-content/plugins/ink-core/src/Kernel/CadenceType.php` (NEW — Kernel-owned cadence value set)
- `wp-content/plugins/ink-core/src/Challenges/Cadence.php` (modified — cadence-aware periodKey/periodLabel + forUitdaging/periodKeyFor/periodLabelFor)
- `wp-content/plugins/ink-core/src/Content/FieldSets.php` (modified — UITDAGING_CADENCE meta + select field def + renderBox select branch w/ out-of-set fallback + sanitizeCadence)
- `wp-content/plugins/ink-core/src/Challenges/WinnersPost.php` (modified — R12B: period-aware composeTitle + roundPeriod seam; folds the cadence period into the announcement title)
- `tests/Unit/Kernel/CadenceTypeTest.php` (NEW — CadenceType enum tests)
- `tests/Unit/Challenges/CadenceTest.php` (modified — annual cadence + resolver + pipeline-reuse tests)
- `tests/Unit/Content/FieldSetsTest.php` (modified — cadence field/sanitiser/select-render/save tests; metaKeys count 12→13)
- `docs/afrikaans-terms.md` (modified — kadens glossary row)
- `docs/afrikaans-translation-sheet.md` (modified — §7 cadence labels for human ratification)
- `_bmad-output/implementation-artifacts/sprint-status.yaml` (modified — 12B.1 + epic-12b status)
- `_bmad-output/implementation-artifacts/12b-1-annual-competition-management.md` (this story)

### Change Log

- 2026-06-29 — Story 12B.1 implemented: annual competition cadence as configuration over the 12A pipeline. New Kernel `CadenceType` enum (`maandeliks`/`jaarliks`), cadence-aware `Challenges\Cadence` (annual period ⇒ year), per-`uitdaging` cadence meta + meta-box select in `Content\FieldSets` (default monthly). No new front-end copy. 15 unit tests (suite 1184→1199). Glossary + translation-sheet rows for the cadence labels. R12A D1 read-collapse inherited, not addressed (flagged). cs/stan/deptrac/copy:scan: no new issues.
- 2026-06-29 — R12B code review (3-layer adversarial; `epic-12b-code-review-2026-06-29.md`). Two HIGH findings (raised by both Edge Case Hunter + Acceptance Auditor): the cadence was stranded (no production consumer of the period) and the reuse test was vacuous. **Both FIXED:** `WinnersPost::generate()` now folds the cadence-aware period into the announcement title via a period-aware `composeTitle` (annual ⇒ "Wenners: 2026 — …", monthly ⇒ "Wenners: Desember 2026 — …"); the vacuous `winnerLabel` test was replaced by a production-path `WinnersPostTest` proof. One LOW fixed (select first-option fallback for an out-of-set stored value). Deferred: REST enum-schema discoverability (M1, sanitiser already enforces correctness) + the pre-existing R12A D1 read-collapse. 0 unresolved HIGH/MED. Suite 1199→1201; cs/stan/deptrac/copy:scan still clean (no new edge). Status → done.

### Senior Developer Review (AI)

**Outcome:** Approved after fixes. R12B (2026-06-29), 3-layer adversarial review. Full report: `epic-12b-code-review-2026-06-29.md`.

**Review Follow-ups (AI):**

- [x] [Review][Patch][HIGH] Wire the cadence period into a production surface — annual cadence was stranded (no consumer). `WinnersPost::generate()` → period-aware `composeTitle`. [src/Challenges/WinnersPost.php]
- [x] [Review][Patch][HIGH] Replace the vacuous `winnerLabel` reuse test with a production-path proof. [tests/Unit/Challenges/WinnersPostTest.php, CadenceTest.php]
- [x] [Review][Patch][LOW] `select` render falls back to the first option for an out-of-set stored value. [src/Content/FieldSets.php]
- [ ] [Review][Defer][MED] Advertise the cadence value set in the REST meta schema (`enum`) — sanitiser already enforces correctness; discoverability only. [src/Content/FieldSets.php]
- [x] [Review][Defer][LOW] R12A D1 per-Gradering read-collapse — pre-existing, independent of cadence; remains the standing pre-launch follow-up.

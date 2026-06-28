---
baseline_commit: de2ee63
---

# Story 17.3: Terminology reconciliation (reopened by G1)

Status: review

<!-- Note: Validation is optional. Run validate-create-story for quality check before dev-story. -->

## Story

As a content manager,
I want terminology reconciled across all docs and code,
so that the canonical vocabulary is consistent. (G1)

## Acceptance Criteria

1. **Given** `afrikaans-terms.md` updated first as source of truth **When** corrections propagate **Then** `intekening`/`intekenaar`/`intekenlid` → **lidmaatskap / betaalde lid / gratis lid**; UI tier term → **Gradering** (never "tier"); **Skrywerprofiel = PUBLIC**, **My Profiel = PRIVATE** (fix "Skrywersprofiel" → "Skrywerprofiel"); new terms EntryID, algehele wenner / wenner, Terugvoer van die moderator, Meester **And** corrections propagate to **ALL** docs, not just the active file.
2. **The glossary `afrikaans-terms.md` is verified complete & correct as source of truth** for every G1 item — all retired terms documented, all four new nouns present and scoped, the PUBLIC/PRIVATE convention and the "never 'tier' in UI" rule stated. (Audit confirms it is already complete; record the verification, add an entry only if a gap is found.)
3. **The "Skrywersprofiel" spelling drift is fixed in the remaining live/source docs** — specifically `docs/new-requirements-18-june/administrative-requirements.md` L83 + L110 (both mean the PUBLIC profile → "Skrywerprofiel"). The consolidated-spec occurrences are spelling-correction *notes* ("not 'Skrywersprofiel'") and MUST be left intact.
4. **The PUBLIC/PRIVATE contradiction in the source requirements doc is reconciled** — `administrative-requirements.md` L169 maps the private profile to "the Skrywersprofiel page" (client's pre-G1 naming). Reconcile to canonical: the **private** page is **My Profiel**; the **public** profile is **Skrywerprofiel**. Preserve the client's intent (a public profile shown to others + a private page shown only to the writer); add a "(per G1 / afrikaans-terms.md)" reference so the reconciliation is traceable.
5. **The deprecated "vriend" UI stat is reconciled to the follow model** — `docs/ui-copy-translations.md` L482 "Friends / Vriende" overview stat contradicts L470 ("Wie ek volg" replaced the Friends tab) and the glossary (vriend deprecated → volg/volgeling; BuddyPress Friends OFF). Change it to the follow-model stat ("Following / Wie ek volg"), consistent with L470. (Live code already uses the follow model; this is a doc-only residue — the only code "Vriendskappe" reference is the friendship→follow migration source, which is correct and stays.)
6. **No code IDs change.** `ink_writer_tier` (and other migration-load-bearing IDs/slugs/enum values brons/silwer/goud) are preserved — the corrections are UI/label/doc only.
7. **Gates green:** `composer copy:scan`, `composer test:unit`, and (if any PHP is touched — none expected) `composer cs`/`php -l`/`composer stan`/`composer deptrac` all clean; baseline unchanged.

## Tasks / Subtasks

- [x] Task 1: Verify the glossary as source of truth (AC: #1, #2)
  - [x] Verified `afrikaans-terms.md` is COMPLETE & CORRECT — no change needed. It documents: intekening/intekenaar/intekenlid retired → lidmaatskap/betaalde lid/gratis lid (L43/46/47, +forbidden-terms table L247); Gradering (never "tier") UI term (L68, table L246); Skrywerprofiel=PUBLIC (L33) + My Profiel=PRIVATE + the spelling note (table L248); the four new nouns — EntryID (L126), algehele wenner (L128), Terugvoer van die moderator (L130), Meester (L72).
- [x] Task 2: Fix the Skrywersprofiel spelling drift in source docs (AC: #3)
  - [x] `administrative-requirements.md` L83 "(Skrywersprofiel)" → "public writer profile page (Skrywerprofiel)".
  - [x] `administrative-requirements.md` L110 "(Skrywersprofiel)" → "(Skrywerprofiel)".
  - [x] Left the consolidated-spec occurrences (L227/L399) intact — they are spelling-correction notes.
- [x] Task 3: Reconcile the PUBLIC/PRIVATE contradiction (AC: #4)
  - [x] `administrative-requirements.md` L169: rewrote the mapping — private = "My Profiel" page, public = "Skrywerprofiel"; kept the client's public/private distinction; added the "(Reconciled per G1, 2026-06-20; afrikaans-terms.md is source of truth)" reference.
- [x] Task 4: Reconcile the deprecated "vriend" stat (AC: #5)
  - [x] `ui-copy-translations.md` L482: "Friends / Vriende" stat → "Following / Wie ek volg" with a note that it replaces the legacy friend stat (volg-besluit; vriend afgeskaf → volg/volgeling), consistent with L470.
- [x] Task 5: Sweep for any other live/UI-copy stray old terms + run gates (AC: #1, #6, #7)
  - [x] Re-grepped live code + `ui-copy-translations.md`: ZERO Class A residue — no `intekening/intekenaar/intekenlid` or `Skrywersprofiel` in live code/ui-copy; remaining "Vriende" mentions are the explanatory "replaces the old Vriende…" notes (correct). `ink_writer_tier` left untouched; the `FollowGraphMigration` "Vriendskappe" source term left untouched.
  - [x] `composer copy:scan` ✓ (8/8 unchanged); `composer test:unit` ✓ (1017 passed, 1 skipped — docs-only change, no regression).

## Dev Notes

### Audit result (read first) — reconciliation is ~done; this is the residue
A full classified audit (A = must-fix UI/live; B = preserve code IDs; C = glossary/reconcile docs that document old→new; D = dated historical snapshots) found:
- **Glossary `afrikaans-terms.md` is already COMPLETE and CORRECT** as source of truth for every G1 item (retired terms with G1 ref; all four new nouns; PUBLIC/PRIVATE convention; Gradering-not-tier).
- **Live code (`patterns/*.php`, `ink-core/src/**`) is already aligned** — uses lidmaatskap/betaalde lid/gratis lid, Gradering via the `Ink\I18n\Terms` registry, the correct `Skrywerprofiel` spelling (`Social\SkrywerProfiel`), and the follow model. NO Class A residue in code.
- **Class A residue is doc-only:** `administrative-requirements.md` L83/L110 (spelling), L169 (PUBLIC/PRIVATE contradiction), and `ui-copy-translations.md` L482 (deprecated Vriende stat). That is the entire deliverable.
- **Class B preserved:** `ink_writer_tier` is a code-only meta key, never UI — leave alone. The `FollowGraphMigration` "Vriendskappe" string is the migration SOURCE term — correct, stays.
- **Class C/D left intact:** glossary, reconcile-*.md, PRDs, sprint-change-proposals, completed story files, and the consolidated-spec spelling-correction notes legitimately cite old terms as decision record — do NOT rewrite.

### Architecture compliance (project-context.md)
- **`afrikaans-terms.md` is the glossary source of truth** — a term is settled there first; code IDs and UI labels follow it. This story verifies it (already settled) then propagates the last doc residue.
- **Controlled-vocabulary UI labels come from the `ink-core` terminology registry** (`Ink\I18n\Terms`) — already the case; no inline label changes needed in code.
- **Code IDs are migration-load-bearing** — never rename `ink_writer_tier`, CPT/taxonomy IDs, or enum values to "fix terminology" (label ≠ slug; see the term-corrections-propagate / label-vs-slug rule).
- **Fix the term in ALL docs, not just the active file** — but discriminate: living docs/UI copy get corrected; dated snapshots + glossary/reconcile docs that document the change stay as record.

### Project Structure Notes
- MODIFIED (docs only): `docs/new-requirements-18-june/administrative-requirements.md`, `docs/ui-copy-translations.md`.
- `docs/afrikaans-terms.md`: verify-only (no change expected).
- No code, no test changes expected. `placeholder-baseline.json` unchanged.

### Testing standards
- No code touched → no new tests. Run `composer copy:scan` (baseline unchanged) + `composer test:unit` (no regressions) as the safety net. If any PHP is unexpectedly touched, run `composer cs`/`php -l`/`composer stan`/`composer deptrac`.

### References
- [Source: _bmad-output/planning-artifacts/epics.md#Epic 17 — Story 17.3]
- [Source: docs/afrikaans-terms.md] (source of truth — verified)
- [Source: docs/specs/ink-consolidated-spec.md#227, #399 (Item 20 / G1)] (spelling-correction notes — preserve)
- [Source: docs/new-requirements-18-june/administrative-requirements.md#L83,L110,L169] (Class A fixes)
- [Source: docs/ui-copy-translations.md#L470,L482] (follow-model stat reconcile)
- [Source: project-context.md#Afrikaans-first; afrikaans-terms.md source of truth; migration-load-bearing code IDs]

## Dev Agent Record

### Agent Model Used

claude-opus-4-8

### Debug Log References

- `composer copy:scan` ✓ (8/8 unchanged); `composer test:unit` ✓ (1017 passed, 1 skipped). Docs-only change — no code, no new tests.

### Completion Notes List

- **The G1 reconciliation was already ~complete** across Epics 1–16: the glossary is the correct source of truth and all live code is aligned (lidmaatskap family, Gradering via `Ink\I18n\Terms`, correct `Skrywerprofiel` spelling, follow model). This story closed the doc-only residue a classified audit surfaced.
- **Three doc fixes:** spelling drift "Skrywersprofiel" → "Skrywerprofiel" in `administrative-requirements.md` L83/L110 (both public-profile usages); reconciled the L169 PUBLIC/PRIVATE contradiction (client had mapped the private page to "Skrywersprofiel" — now My Profiel = private, Skrywerprofiel = public, with a G1 traceability note); reconciled the deprecated "Vriende" overview stat to the follow model in `ui-copy-translations.md` L482.
- **Discrimination held:** code IDs (`ink_writer_tier`), enum values (brons/silwer/goud), the `FollowGraphMigration` "Vriendskappe" source term, the glossary, reconcile-docs, dated planning snapshots, and the consolidated-spec spelling-correction notes were all correctly LEFT INTACT — label ≠ slug; historical records preserved.
- **No code, no test changes; baseline unchanged.**

### File List

- `docs/new-requirements-18-june/administrative-requirements.md` (MODIFIED — L83/L110 spelling, L169 PUBLIC/PRIVATE reconcile)
- `docs/ui-copy-translations.md` (MODIFIED — L482 Vriende stat → follow model)
- `_bmad-output/implementation-artifacts/17-3-terminology-reconciliation-reopened-by-g1.md` (story file)
- `_bmad-output/implementation-artifacts/sprint-status.yaml` (status tracking)

## Change Log

| Date | Change |
|---|---|
| 2026-06-28 | Story 17.3 implemented: verified `afrikaans-terms.md` complete as source of truth (no change); fixed the residual "Skrywersprofiel" spelling drift + reconciled the PUBLIC/PRIVATE contradiction in `administrative-requirements.md`; reconciled the deprecated "Vriende" stat to the follow model in `ui-copy-translations.md`. Code IDs + historical records preserved. Docs-only; all gates green (1017 tests). Status → review. |

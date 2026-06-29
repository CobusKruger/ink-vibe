---
baseline_commit: 823ff550f6107cd585c4d28b74429f28e3963a90
---

# Story 12A.3: Paste-text results ingestion + coverage report (R2)

Status: done

<!-- Note: Validation is optional. Run validate-create-story for quality check before dev-story. -->

## Story

As a redakteur,
I want to paste judges' results as plain text and get a coverage report before an irreversible commit,
so that I can safely commit results without a parser dependency. (FR-50-R2, R2)

## Acceptance Criteria

(epics.md 12A.3 admin-flow ACs.)

1. Editor selects the same uitdaging, then **pastes the judges' results as plain text** into a textarea. **No `.docx` upload** (owner decision — removes the PhpWord/XXE/zip-bomb surface).
2. Parser extracts (a) the **winners block** — top-3 per Gradering **and per category**, each by EntryID, **"Geen"** allowed; and (b) **per-entry commentary** — keyed by EntryID (+ title), then the text.
3. Produces a **dekkingsverslag** reconciling the parsed EntryIDs against **all stored EntryIDs**: matched / unmatched / "Geen", EntryIDs in the doc that don't match (and vice versa), whether all winners were identified, whether commentary was resolved for every entry.
4. **Explicit confirm gate:** committing is irreversible (publishes a post, writes comments, promotes Graderings), so it is **blocked until the editor confirms** all categories are accounted for. A hard gap (unknown EntryID, duplicate rank in a pool) blocks regardless.
5. **On confirm, in order:** (1) generate the **wenneraankondiging** post (12A.4 — reserved seam); (2) write **Terugvoer van die moderator** per entry (12A.5 — reserved seam); (3) Biblioteek update **stub** (fire `ink/biblioteek_wen_bywerking`, R4/10.6); (4) record placements `algehele wenner`/`wenner` (12.6 `Placements::record`, rank-unique per pool); (5) featured feed (falls out of the winners post, 12A.7); (6) **trigger Gradering auto-promotion** (`Tiers\Api::awardWins`, 5.8 — a win = any top-3 placement).
6. **States:** parse partial/failure → coverage shows gaps, commit blocked; EntryID mismatch → flagged; **re-run after a successful commit → idempotent** (no double-post/comment/promote).

Readiness flags honoured: **#1 rank-uniqueness enforced at ingestion** (one 1st/2nd/3rd per pool); **#2 placements → `awardWins`** wired here.

**Pool = Gradering × category** (decision): the AC says "top-3 per Gradering **and per category**", and judges rank poems against poems. The pool key is `{grade}|{type}`. NOTE: 12.6 `Placements::arrange()`/`forRound()` group by Gradering only (coarser) — not used by this ingestion's enforcement; reconciling that view is flagged for the code-review/retro.

## Tasks / Subtasks

- [x] Task 1: `Challenges\ResultsParser` (pure) — `parse(string $text): array{winners:list<array{grade:string,type:string,rank:int,entry_id:string}>, commentary:list<array{entry_id:string,title:string,text:string}>}`; section-aware (WENNERS / KOMMENTAAR), pool-header context (grade + category), EntryID token `/(Gedig|Storie|Artikel)\s+\d+/i`, rank tokens (1/2/3, 1ste/2de/3de), "Geen" omitted. Documented format.
- [x] Task 2: `Challenges\Coverage` (pure) — `report(array $winners, array $commentary, array $stored): array` (dekkingsverslag: matched/unmatched/unknown winners + commentary, entries-without-commentary, duplicate (grade,type,rank), allWinnersIdentified, allCommentaryResolved); `blocksCommit(array $report): bool` (true on unknown EntryID or duplicate rank).
- [x] Task 3: `Challenges\Ingestion` — idempotent commit pipeline `commit(int $uitdaging_id, list $winners): array` (winners resolved to `{post_id,rank,author_id}`): guard the per-round commit-done marker; then in order generate-winners-post (reserved seam → 0), write-moderator-feedback (reserved seam → 0), fire biblioteek hook, `Placements::record` per winner, `awardWins` per author (summed top-3 wins, challenge id). Marks committed. Re-run no-ops.
  - [x] reserved protected seams `commitWinnersPost()` / `commitModeratorFeedback()` (documented no-op → filled by 12A.4 / 12A.5, the 10.6 reserved-stub precedent)
  - [x] `awardPromotions(list $winners): int` — group by author, sum wins, `Tiers\Api::awardWins(author, count, uitdaging_id)`
- [x] Task 4: `Challenges\IngestionPage` — admin submenu (MANAGE_CHALLENGES): paste textarea → parse → render dekkingsverslag → confirm checkbox + commit (nonce/cap/sanitize per AdminProfile/FieldSets). Resolves parsed EntryIDs → post ids via the round's stored EntryID map (`entriesFor` seam reused/mirrored from CollationPage). Blocks commit when `Coverage::blocksCommit` or unconfirmed.
- [x] Task 5: Tests — `ResultsParserTest`, `CoverageTest`, `IngestionTest` (commit pipeline via seams): parse winners+commentary+Geen; coverage matched/unmatched/duplicate/missing-commentary + blocksCommit; commit records placements + awards promotions + fires biblioteek hook + is idempotent (second commit no-ops — non-vacuous: first WROTE).
- [x] Task 6: Gates — test:unit / cs / stan / deptrac / copy:scan green. deptrac: Challenges → Content + Tiers (allowed); biblioteek via literal `do_action` (no Library edge); no Notifications edge (winners post is 12A.4). New Afrikaans admin labels as `__()` literals.

## Dev Notes

- **Idempotency-in-the-write (R12 lesson):** a per-round commit-done marker (`ink_uitdaging_commit_done` post meta on the uitdaging) guards the whole pipeline; `Placements::record` + `EntryId` are first-wins; `awardWins` runs once (guarded by the marker). Re-running a committed round writes nothing. [Source: epic-12-retro §3]
- **Rank-uniqueness at ingestion (flag #1):** the authoritative invariant `Placements` trusts. `Coverage` flags a duplicate (grade,type,rank); `blocksCommit` is true until resolved — so two "algehele wenners" in a pool can never be committed. [Source: epic-12-retro §6 action 2]
- **placements → awardWins (flag #2):** a win = any top-3 placement at the writer's current grade; `awardPromotions` sums an author's top-3 placements this round and calls `Tiers\Api::awardWins(author, count, uitdaging_id)` once (PromotionEngine accumulates, fires `ink/tier_promoted` → 5.10 email). [Source: src/Tiers/Api.php:233; epic-12-retro §6 action 3]
- **Biblioteek hook via literal `do_action`** (`ink/biblioteek_wen_bywerking`, the firer-uses-literal convention like `ink/uitdaging_entry_linked`) → no Challenges→Library edge; `Library\AutoUpdate` listens (10.6 stub). [Source: src/Library/AutoUpdate.php:39]
- **Winners post (12A.4) + moderator feedback (12A.5) are reserved seams here** (documented no-op protected methods, the 10.6 precedent) — 12A.4/12A.5 fill them. The commit pipeline ORDER (AC-5) is established now. [Source: src/Library/AutoUpdate.php (reserved-stub precedent)]
- **No `.docx`** — plain-text textarea only (owner decision; removes the parser/XXE surface). The coverage report + confirm gate are the safety net for an imperfect parse. [Source: epics.md#Story 12A.3]
- Admin $_POST pattern + escaping + testing rules (pure parser/coverage; commit via seams; test the OUTCOME; non-vacuous idempotency) as in 12A.2. [Source: project-context.md]

### Project Structure Notes

- New: `src/Challenges/ResultsParser.php`, `Coverage.php`, `Ingestion.php`, `IngestionPage.php`; tests for each.
- Modified: `src/Challenges/Module.php` (register IngestionPage).
- deptrac: Challenges → Content + Tiers (allowed). No new edge.

### References

- [Source: _bmad-output/planning-artifacts/epics.md#Story 12A.3 + #12A.3 admin-flow AC]
- [Source: src/Challenges/Placements.php (12.6), EntryId.php (12A.1), Collation.php (12A.2)]
- [Source: src/Tiers/Api.php (awardWins 5.8), src/Library/AutoUpdate.php (10.6 hook)]
- [Source: epic-12-retro-2026-06-27.md §6 (readiness flags 1 & 2)]

## Dev Agent Record

### Agent Model Used

claude-opus-4-8

### Debug Log References

- deptrac flagged 2 new Challenges→Library violations from referencing `AutoUpdate::HOOK` — switched `fireBiblioteek` to the literal `do_action('ink/biblioteek_wen_bywerking', …)` (true firer-uses-literal, the `ink/uitdaging_entry_linked` precedent). Back to 3 pre-existing only.
- PHPStan `parameterByRef.unusedType` on `flushCommentary` (by-ref `$current` only ever nulled). Refactored to take `$current` by value (append-only); the caller owns the reset. Clean.

### Completion Notes List

- **R2 keystone.** `ResultsParser` (pure) parses pasted plain text into a winners block (pool header grade context + rank token + EntryID, "Geen" dropped) + per-entry commentary; `Coverage` (pure) builds the dekkingsverslag reconciling against stored EntryIDs (matched/unknown winners + commentary, entries-without-commentary, duplicate pool slots) and `blocksCommit` hard-blocks on an unknown EntryID or a duplicate rank; `Ingestion` runs the idempotent AC-5 commit pipeline; `IngestionPage` is the thin admin shell (paste → dekkingsverslag → confirm gate → commit).
- **Readiness flag #1 (rank-uniqueness at ingestion):** `Coverage` flags a duplicate `(grade|type|rank)` pool slot or an entry placed twice; `blocksCommit` → true, so two "algehele wenners" can never be committed.
- **Readiness flag #2 (placements → awardWins):** `Ingestion::awardPromotions` sums an author's top-3 placements this round and calls `Tiers\Api::awardWins(author, count, uitdaging_id)` once (a win = any top-3 placement; PromotionEngine accumulates + fires `ink/tier_promoted` → 5.10).
- **Pool = Gradering × category** (decision): honours the AC's "top-3 per Gradering and per category"; a rank-1 gedig and rank-1 storie in the same grade are distinct slots (test asserts no false collision). NOTE for review/retro: 12.6 `Placements::arrange()`/`forRound()` group by Gradering only (coarser) — not used by this ingestion's enforcement; reconciling that coarser view is flagged.
- **Idempotency-in-the-write:** the per-round `ink_uitdaging_commit_done` marker guards the whole pipeline — a re-run no-ops (no double-post/comment/promote). Non-vacuous test: first commit WROTE, second wrote nothing.
- **Reserved seams (10.6 precedent):** `commitWinnersPost` (→12A.4) and `commitModeratorFeedback` (→12A.5) are documented no-ops; the commit ORDER is established now. Biblioteek hook fired by literal (no Library edge); placements via `Placements::record` (12.6); auto-promotion via `awardWins` (5.8).
- New Afrikaans admin labels as `__()` literals (Uitslae-invoer, Ontleed, Pleeg uitslae, Dekkingsverslag, confirm copy, etc.).
- Gates: `composer test:unit` 1142→1156 (+14), 1 skipped; `cs` 0 errors; `stan` OK; `deptrac` 3 pre-existing only (no new edge — biblioteek via literal); `copy:scan` clean.

### File List

- `wp-content/plugins/ink-core/src/Challenges/ResultsParser.php` (new)
- `wp-content/plugins/ink-core/src/Challenges/Coverage.php` (new)
- `wp-content/plugins/ink-core/src/Challenges/Ingestion.php` (new)
- `wp-content/plugins/ink-core/src/Challenges/IngestionPage.php` (new)
- `wp-content/plugins/ink-core/src/Challenges/Module.php` (modified — register IngestionPage)
- `tests/Unit/Challenges/ResultsParserTest.php` (new)
- `tests/Unit/Challenges/CoverageTest.php` (new)
- `tests/Unit/Challenges/IngestionTest.php` (new)

### Change Log

- 2026-06-29 — Story 12A.3 implemented: paste-text results ingestion + dekkingsverslag + idempotent AC-5 commit pipeline (placements + auto-promotion + biblioteek hook; winners-post/feedback reserved for 12A.4/12A.5). Rank-uniqueness + placements→awardWins readiness flags honoured. 14 unit tests. Suite 1142→1156.

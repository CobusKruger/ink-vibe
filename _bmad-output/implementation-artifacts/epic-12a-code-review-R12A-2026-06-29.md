# Epic 12A Code Review (R12A) — Challenge adjudication automation (2026-06-29)

**Method:** 3-layer adversarial review (Blind Hunter / Edge Case Hunter / Acceptance Auditor), all at the session model, over the full Epic 12A diff (`63fd7d8..d354ddb`, 7 story commits, ~2 770 lines of new `ink-core` Challenges code + FeaturedWinners/Notifications/theme edits). Triaged into patch / defer / dismiss.

## Outcome

- **0 unresolved HIGH/MEDIUM.** 6 patches applied; 6 deferred (documented); 5 dismissed.
- The Acceptance Auditor **verified all 7 stories' load-bearing ACs are met**, the conflation rule held everywhere (zero `Ink\Entitlement`), the security baseline held (nonce + capability + `is_scalar`→`wp_unslash`→sanitise + escaped output on every admin surface), and all new UI/admin strings are Afrikaans `__()` literals.
- Tests **1181 → 1184** (+3 review tests); cs 0 errors; stan OK; deptrac 3 pre-existing Kernel→Content only; copy:scan clean.

## Patches applied (6)

1. **`Collation::stripIdentity` word-boundary removal (was a blunt `str_ireplace`).** A short author display name ("An", "Roos", "Son") was scrubbed as a SUBSTRING from inside the work's own words, corrupting the judged text. Now `preg_replace('/\b…\b/iu', …)` — whole-word/phrase only. (Blind HIGH / Edge MED / Auditor LOW.)
2. **`ModeratorFeedback::insertComment` return handling.** `wp_insert_comment` returns `int|false` (success id can be a numeric string on some installs); the `is_int()` guard risked treating a successful insert as failure (undercount + masked write via the `hasFeedback` idempotency). Now `false === $id ? 0 : (int) $id`. (Blind HIGH.)
3. **`Ingestion::commit` empty-winner guard.** A zero-winner commit passed `blocksCommit` (trivially — no unknowns/dups), published an empty announcement, and marked the round done — permanently locking it (`reeds_gepleeg`) so the real results could never be committed. Now an empty winners set returns `geen_wenners` WITHOUT marking committed; the round stays re-runnable. (Blind MED / Edge HIGH.)
4. **`Ingestion::commit` defensive per-post dedup.** `commit()` trusted its input was rank-unique (the invariant lived only in the UI's Coverage gate). Now `uniqueByPost()` collapses a duplicated `post_id` so a dirty/direct caller can never double-place or double-award. (Edge MED.)
5. **Rank-uniqueness keys on the AUTHORITATIVE stored gradering, not the pasted header (flag #1).** The duplicate gate keyed on the parsed header grade; a typo'd/omitted header could let two real-pool rank-1s slip through. `IngestionPage::analyse` now overrides each matched winner's grade with the entry's stored `ink_entry_gradering` snapshot before reconciliation — so the "one 1st/2nd/3rd per pool" invariant is enforced on authoritative data. (Edge MED / Auditor MED — the gate half of A1.)
6. **`ResultsParser::rankIn` accepts an ordinal without trailing punctuation.** `1ste Gedig 3` (no colon) was silently dropped (lost placement, no signal). Now an ordinal form (`1ste`/`2de`/`3de`) matches without punctuation; a bare `1 Gedig` still does NOT (so prose like "3 gedigte is ingedien" never false-matches). (Blind LOW / Edge LOW.)

## Deferred (6) — see deferred-work.md

- **D1 (MED, the read half of Auditor A1): per-Gradering read-collapse vs per-(Gradering×category) winners.** `Placements::arrange()`/`forRound()` (Story 12.6) group by Gradering ONLY and collapse to one entry per rank per pool — so two legitimately-distinct rank-1 winners (gedig + storie, same grade) feeding `WinnersPost::placedEntries()` + the home featured slot are silently de-duped to one "algehele wenner". The commit GATE is now correct (patch 5); the READ side needs a deliberate category-aware revision (touches 12.6 + WinnersPost + the home slot). This is the divergence flagged in the 12A.3 story. Owner: a follow-up Placements read-model reconciliation.
- **D2 (MED): `ModeratorFeedback::recordForRound` per-post idempotency blocks a corrected re-commit.** Only reachable if the commit-done marker is cleared for a correction; no correction/re-ingestion flow exists yet. Owner: future correction flow.
- **D3 (LOW): AC-5 fires the biblioteek hook (step 3) before placements are recorded (step 4).** A rank-dependent Library listener would see rank 0 — but the listener is a documented no-op stub (10.6) and the payload carries only post ids. Decide ordering when the Library body lands.
- **D4 (LOW): `ResultsParser` hard-codes `Gedig|Storie|Artikel` decoupled from the `Terms` registry.** Works today (labels == capitalised slugs); if a `Terms` label is re-authored the parser silently stops matching. The coverage report + confirm gate are the safety net. Owner: maintainability follow-up.
- **D5 (LOW): no recipient cap / BCC on the judge email.** All recipients are visible to each other; no upper bound. Small editor-controlled judge panel mitigates. Owner: a hardening pass.
- **D6 (LOW): `postedUitdagingId` has no post-type/existence check.** A typo'd id yields a confusing all-unknown coverage report (safe outcome, no write). Owner: a UX nicety.

## Dismissed (5)

- `featured()` missing `uitdaging_id` guard — `Pools::forRound(0)` returns `[]` (verified); safe.
- A moderator can flip a writer's display toggle — by design (`MODERATE` edits other profiles); documented.
- `WinnerBanner` with an empty gradering renders no tier class — graceful degrade (the rank variant still renders).
- `awardPromotions` reports "promoted" not "wins-awarded" — the "bevorder" wording is accurate.
- `computeAssignments` ignores a number on a now-Meester entry — narrow (grade mutates to Meester post-numbering); Meester is excluded from the judge email anyway.

## Verified (Acceptance Auditor) — load-bearing ACs correctly met

12A.1 EntryID idempotent (never renumber/burn, assigned at collation, meta-on-post). 12A.2 sort type→Gradering→EntryID + anonymisation + Meester non-competing + editable preview + ad-hoc `wp_mail` (no Notifications dep). 12A.3 parser + dekkingsverslag + explicit confirm gate + idempotent commit + AC-5 order + biblioteek-via-literal + summed `awardWins`. 12A.4 form-letter-framed post + featured slot + links (new `Challenges→Notifications` edge documented). 12A.5 `ink_moderator_terugvoer` via `wp_insert_comment` (not a re-enabled comment) + author-gated display. 12A.6 algehele/wenner variants + Meester=primary + colour-paired-with-text a11y. 12A.7 algehele wenner first, all winners kept (`orderFeed`). Conflation + security + Afrikaans held throughout.

---

*Saved 2026-06-29. 6 patches applied + tested; 6 deferred to deferred-work.md; epic-12a stories → done.*

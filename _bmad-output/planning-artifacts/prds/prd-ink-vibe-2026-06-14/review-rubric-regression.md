# PRD Regression Re-check — INK (prd-ink-vibe-2026-06-14)

*Focused regression pass against `prd-validation-checklist.md`. Not a fresh full review — confirms the prior HIGH findings are resolved and the edits introduced no new high/critical issues. Reviewed 2026-06-15 against prd.md (719 lines) + addendum.md.*

## Overall verdict

The previously-flagged HIGH findings are **genuinely resolved**. The duplicate FR-58 ID is gone (FR-58 now appears exactly once, as Sponsors), the intent enum / "choose intent" E2E are removed and every residual "intent" mention now correctly asserts the *absence* of a signup gate, FR-24's legibility adjective is demoted to a §6 tone goal, and FR-13/FR-18 are now testable. ID sequences are clean and contiguous. **One new mechanical defect was introduced**: `FR-63` (deferred auto-renew) is cross-referenced twice but never defined as an FR block — a dangling ID. It is medium, not high (the §14.2 anchor it points at exists and fully describes the deferral), but it should be closed before the next gate.

## Resolution of prior HIGH findings

| Prior finding | Status | Evidence |
|---|---|---|
| Duplicate **FR-58** ID | RESOLVED | `grep "FR-58"` → single definition at prd.md:465 (Sponsors). No second FR-58. |
| Dangling auto-renew reference | PARTIALLY — see new finding | Auto-renew now consistently routed to **FR-63 / §14.2 / OQ-9**, but FR-63 itself is undefined (below). |
| Addendum **intent enum** | RESOLVED | addendum.md:16 explicitly: *"No `intent` enum — FR-2 removed the signup intent gate."* |
| "choose intent" E2E | RESOLVED | No such E2E remains; NFR-9 / addendum §G critical journey is *register → buy → submit → publish → read/react → renewal/expiry*. All "intent" mentions (prd.md:121,183,186-188) now assert the gate's absence — consistent with FR-2. |
| **FR-24** legibility adjective | RESOLVED | prd.md:300 now reads *"Afrikaans legibility is a tone goal — §6 — not an acceptance criterion here."* Adjective removed from the testable consequence. |
| **FR-13** testability | RESOLVED | prd.md:252 — *"a subscription-state change has no write path to `ink_writer_tier`… unit tests assert that each known subscription-state transition leaves tier unchanged."* Concrete, verifiable. |
| **FR-18** testability | RESOLVED | prd.md:273 enumerates allowed marks (hard breaks, blank-line/stanza preservation, bold, italic) and explicit exclusions; verbatim line-structure preservation cross-refs FR-25. |

## New-edit integrity check (no new high/critical introduced — one medium)

### Findings
- **medium** Dangling FR ID — `FR-63` referenced but never defined (§4.2 Notes prd.md:234; §13 prd.md:632). The FR list runs contiguously FR-1..FR-62; there is no `#### FR-63:` block. Both references point readers to "FR-63, §14.2; OQ-9", and §14.2 *does* describe the deferred auto-renew fully — but the ID resolves to nothing. *Fix:* either add a one-line FR-63 stub (e.g. under §4.2 or §14.2 marked deferred/P-deferred) so the ID has an anchor, or drop the "FR-63" label and reference "§14.2 / OQ-9" only. Recommend the stub, since §13 deliberately gives deferred items stable IDs.
- *No other issues.* New IDs all resolve cleanly:
  - **SM-8** defined (prd.md:665), validates FR-48/49/50; counter **SM-C4** defined (prd.md:676) and correctly names SM-8. Both consistent with the Brons-excluded / distinct-placers rationale.
  - **FR-50** restated as queryable placement records (1st/2nd/3rd per tier), P0, referenced by SM-8 and §14.1 — consistent.
  - **FR-12** restated as set-tier in any direction with log; auto-promotion correctly deferred to P2 (§14.2 prd.md:651) — no contradiction with FR-51 (optional winner→promotion link).
  - **OQ-16/17/18** defined (prd.md:699-701), each referenced from the body (MR-5→OQ-18, migration risk 2→OQ-16, §14.2 retired-plan→OQ-18, SM-1→OQ-16, §8/§11→OQ-17). Header tally "8 resolved, 9 deferred, 1 open" matches the 18 entries.
  - **FR-63** aside, FR-4's "No auto-renew at launch" (prd.md:201) and the FR-44 expiry-reminder rationale (prd.md:387) are internally consistent with the deferral.

## Mechanical notes
- **ID continuity:** FR-1..62 (62, contiguous), MR-1..11, UJ-1..6, OQ-1..18, SM-1..8 + SM-C1..C4 — no gaps, no duplicates. Only the undefined **FR-63** breaks the otherwise-clean cross-reference graph.
- **THE conflation rule:** still coherently threaded (FR-13, FR-19, FR-49, §12, addendum §B) — the FR-12 set-tier rewrite did not weaken it.
- **Glossary / terminology:** no drift introduced by the edits; FR-50/FR-12/FR-48 use `ster gradering` / `uitdagingsrondte` / `inskrywing` verbatim.
- **NFR-1 allowlist:** prd.md:525 now defines a permitted-token allowlist + member-content-out-of-scope, giving SM-2's gate a deterministic pass/fail — improvement, no regression.

## Verdict for the gate
Clear to proceed. Close the one medium FR-63 dangling-ID defect at convenience; it does not block downstream work because the target section and OQ exist.

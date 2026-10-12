# Stage 1 — Progress

Running status of the Stage 1 audit, one row per inventory row in `agent-brief.md` §2.1.
Updated by the lead (orchestrating) session as each page is committed. Method and rules:
`README.md`. Kickoff instructions: `stage-1-kickoff.md`.

**Stage 1 is complete when every row below is `done`.** Stage 2 does not start before
that (R9).

| Row | Page | Treatment | Status | Commit(s) | Notes |
|---|---|---|---|---|---|
| 00 | tuisblad | A | **done** | `e08fe8d` (P3), `121d503` (P4/4b) | Establishes header/footer + the audit-id convention. 131 ids pair at all 3 viewports. |
| 01 | gemeenskap | A | **done** | `e74ff23` · lovable `3840ee9` | 5/7 sections (stats + spotlight = disclosed deferrals, SCOPE); 63 ids pair at all 3 viewports; no live load-scope bug |
| 02 | my-profiel | A | queued | | INK captured authenticated; header excluded from diff |
| 03 | ontdek | A | in progress — batch 1 | | |
| 04 | skryf | A | in progress — batch 1 | | |
| 05 | uitdaging-enkel | A | in progress — batch 1 | | |
| 06 | skrywer | A | queued | | |
| 07 | lees-storie | A | queued | | |
| 08a | biblioteek | A | queued | | shares Lovable `/library` with 08b |
| 08b | opleiding | A | queued | | |
| 09a | meld-aan | A | queued | | shares Lovable `/auth` with 09b |
| 09b | registreer | A | queued | | |
| 10 | wagwoord-herstel | A | queued | | |
| 11 | wagwoord-terugstel | C | queued | | |
| 12 | vier-nul-vier | C | queued | | |
| 13 | oor-ink | B | queued | | |
| 14 | uitdagings | B | queued | | |
| 15–18 | kontak, lidmaatskap, inkpols, ledegids | C | queued | | one batched subagent (kickoff) |
| 22 | lees-gedig | A | queued | | |

## How the fan-out runs

- **Batches of four.** Each subagent takes one row and runs Phases 0–4b end to end.
- **One Lovable build per subagent.** Phase 3 edits the Lovable source, so a shared
  `dist/` and a shared preview server would let one agent's rebuild change another's
  capture mid-run. Each subagent builds to `tmp/lovable-dist/<page>/` and previews it on
  its own port (`4180 + row number`). The `:4173` server is left for row 00 only.
- **Deploy before capture** (`README.md` Phase 4, precondition 0). Subagents run
  `tools/deploy-to-local.sh --apply` after their Phase 3 edits and confirm with a
  cache-busted `curl`. Other agents' in-progress edits deploy too; each page's
  determinism gate guards its own capture.
- **Subagents do not commit.** The lead commits per page (both repos) after reviewing the
  hand-back, so concurrent agents never race on the git index.
- **Shared register.** Each subagent writes `maps/_primitive-observations.<page>.csv`; the
  lead merges them all into `maps/_primitive-observations.csv` once, at the end of Stage 1
  (`stage-1-kickoff.md`). Until then the merged file holds row 00 only.

## Tooling issues found during the fan-out

Fix these between batches, never while subagents are running.

| # | Found on | Issue | Status |
|---|---|---|---|
| T1 | row 01 | `determinism-check.sh` does `rm -rf tmp/determinism` — parallel agents delete each other's gate runs. Needs a per-page output dir. Row 01 worked around it with a page-private copy. | open |
| T2 | row 01 | `run.sh primitives` misses Lovable `asChild` buttons, class-less eyebrows and INK icon tiles, and includes header/footer nodes. Row 01 built its register with a page-local script using the same property set. Every later row likely needs the same compensation. | open |
| T3 | row 03 | `capture.mjs` waited on every incomplete image, so a lazy `<img>` in a `display:none` tab panel hung the capture forever. Row 03 patched it to wait only on rendered images (against its brief, which forbade harness edits; the fix is sound and leaves computed values untouched). | patched, uncommitted — lands with row 03 |

## Log

- **2026-10-12** — Row 00 completed (Phases 4/4b). Found that `nuwe-ink.local` had been
  serving pre-Phase-3 code; added deploy-before-capture as Phase 4 precondition 0 and
  allowed `deploy-to-local.sh` in the committed settings. Batch 1 (01, 03, 04, 05) started.
- **2026-10-13** — Batch 1 was interrupted by the account usage limit and resumed after
  reset. Row 01 committed. Tooling issues T1–T3 logged above.

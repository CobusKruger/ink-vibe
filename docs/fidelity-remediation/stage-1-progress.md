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
| 03 | ontdek | A | **done** | `d8364f1` · lovable `4a9c7c4` | 70 ids pair; Lovable writers tab is React state (no URL) → 19 `writers-*` ids unpaired; search-results / empty / logged-in states not captured. Discovery renderers restructured to stamp first-item ids; `SkrywersTab` ids also render on row 18 |
| 04 | skryf | A | **done** | `54a9862` · lovable `93339cf` | INK authed; Lovable picker + post-click editor states; success screen not captured. Owner calls: S-2 (one form vs Lovable's two steps rests on an implementer comment, `skryf.css:66-72`, not a ruling). **Live copy bug:** storie/artikel descriptions render a literal backslash ("Skep \’n…", `skryf.php:23-24`) |
| 05 | uitdaging-enkel | A | **done** | `7637a50` · lovable `a5021ae` | Resources = disclosed SCOPE gap; 64 ids pair; F10 ("The Prompt" h2) needs an owner call — MISSING unless the Story 12.1 disclosure covers it; closed (`is-gesluit`) state not captured |
| 06 | skrywer | A | **done** | `86a089e` · lovable `add1aa1` | 44 ids pair. Headline F1: profile sits in the template's 768px `<main>` vs Lovable's 1400px full-bleed sections. Tagline data exists (`Tagline.php`) but is never rendered. Follow buttons + rating form are logged-in only (not captured). Owner calls: F22 (Follow hidden from guests), F3 (no location field), F16 (pinned-only list), F8 |
| 07 | lees-storie | A | in progress — batch 2 | | |
| 08a | biblioteek | A | in progress — batch 2 | | one agent does 08a + 08b: they share Lovable `/library`, so one id set must serve both |
| 08b | opleiding | A | in progress — batch 2 | | |
| 09a | meld-aan | A | queued | | shares Lovable `/auth` with 09b |
| 09b | registreer | A | queued | | |
| 10 | wagwoord-herstel | A | queued | | |
| 11 | wagwoord-terugstel | C | queued | | |
| 12 | vier-nul-vier | C | queued | | |
| 13 | oor-ink | B | queued | | |
| 14 | uitdagings | B | queued | | |
| 15–18 | kontak, lidmaatskap, inkpols, ledegids | C | queued | | one batched subagent (kickoff) |
| 22 | lees-gedig | A | queued — after 07 | | shares Lovable `ReadStory.tsx` with row 07, so it runs after 07 to avoid id collisions |

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
| T1 | row 01 | `determinism-check.sh` does `rm -rf tmp/determinism` — parallel agents delete each other's gate runs. Needs a per-page output dir. Row 01 worked around it with a page-private copy. | **fixed `c423c15`** — each gate runs in `tmp/determinism/<side>-<page>@<vp>[-auth][-<state>]/` and clears only that dir; capture flags pass through after the positionals |
| T2 | rows 01, 05 | `run.sh primitives` misses Lovable `asChild` buttons/links, class-less eyebrows, INK icon tiles and INK `-skakel`/`-tipe` classes, and includes header/footer nodes. Row 01 built its register with a page-local script using the same property set. Every later row likely needs the same compensation. | **fixed `c423c15`** — new `--exclude-chrome` flag. Covers `asChild`/shadcn variants, INK buttons via their wrapper, class-less eyebrows, icon tiles, `-skakel`/`-tipe`, checkbox/choice-row, step-badge, list-bullet and tier-label. Leftovers are listed in README Phase 4b |
| T4 | row 04 | `verify.mjs` and `determinism-check.sh` have no auth (`storageState`) option, and `capture.mjs` cannot click into a state (Lovable `/write` shows its form only after a type-card click). Row 04 gated through page-private patched copies in `tmp/fidelity-skryf/`. Rows 02 and the auth pages will hit the same gap. Also: the `fidelity-capture` session expires — re-run `run.sh login` before authed captures. | **fixed `c423c15`** — `--auth` on capture/verify/determinism, which fails with exit 3 and a `run.sh login` hint if not logged in. `--click <sel>` (repeatable) + `--state <name>` gives `<page>@<vp>[-auth][-<state>].ndjson`, and verify re-drives the clicks |
| T5 | row 06 | `diff.mjs` pairs the **last** element when an audit-id occurs more than once (`diff.mjs:91-92`), silently mis-pairing. Checked 2026-10-13: no capture on disk has a duplicate id, so no committed finding is affected. Fix: make diff (and capture) fail loudly on a duplicate id. | open |
| T6 | row 06 | `extract-primitives` (post-T2) tags anything containing "gradering" as `tier-label`, misses INK `__genre` pills, and writes one row per viewport instead of collapsing identical ones (README §4.1 register spec). | open |
| T3 | row 03 | `capture.mjs` waited on every incomplete image, so a lazy `<img>` in a `display:none` tab panel hung the capture forever. Row 03 patched it to wait only on rendered images (against its brief, which forbade harness edits; the fix is sound and leaves computed values untouched). | **fixed** `9367c86` |

## Log

- **2026-10-12** — Row 00 completed (Phases 4/4b). Found that `nuwe-ink.local` had been
  serving pre-Phase-3 code; added deploy-before-capture as Phase 4 precondition 0 and
  allowed `deploy-to-local.sh` in the committed settings. Batch 1 (01, 03, 04, 05) started.
- **2026-10-13** — Batch 1 was interrupted by the account usage limit and resumed after
  reset. Row 01 committed. Tooling issues T1–T3 logged above.
- **2026-10-13** — Batch 1 complete (01, 03, 04, 05). Every row needed a page-private
  workaround for T1 and T2, and row 04 for T4. Harness fixes for T1/T2/T4 go in before
  batch 2.

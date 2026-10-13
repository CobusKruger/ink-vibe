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
| 02 | my-profiel | A | **done (partial)** | `f770174` · lovable `7416d00` | **Populated states unmeasured:** `fidelity-capture` has no data (46 ids). Owner call: seed that user or approve a second capture user. **Live bugs:** edit-modal overlay clamped to 800×876, backdrop doesn't cover the viewport (F34); `[NEEDS HUMAN AFRIKAANS]` placeholders visible; all 3 renewal plans show R50,00 → same `add-to-cart=926`. Owner calls: avatar editing deferred vs §7.1 (F3/F35); underline vs pill tabs (F7). `FollowToggle`'s fixed id will duplicate on a populated following list (T5) | INK captured authenticated; header excluded from diff |
| 03 | ontdek | A | **done** | `d8364f1` · lovable `4a9c7c4` | 70 ids pair; Lovable writers tab is React state (no URL) → 19 `writers-*` ids unpaired; search-results / empty / logged-in states not captured. Discovery renderers restructured to stamp first-item ids; `SkrywersTab` ids also render on row 18 |
| 04 | skryf | A | **done** | `54a9862` · lovable `93339cf` | INK authed; Lovable picker + post-click editor states; success screen not captured. Owner calls: S-2 (one form vs Lovable's two steps rests on an implementer comment, `skryf.css:66-72`, not a ruling). **Live copy bug:** storie/artikel descriptions render a literal backslash ("Skep \’n…", `skryf.php:23-24`) |
| 05 | uitdaging-enkel | A | **done** | `7637a50` · lovable `a5021ae` | Resources = disclosed SCOPE gap; 64 ids pair; F10 ("The Prompt" h2) needs an owner call — MISSING unless the Story 12.1 disclosure covers it; closed (`is-gesluit`) state not captured |
| 06 | skrywer | A | **done** | `86a089e` · lovable `add1aa1` | 44 ids pair. Headline F1: profile sits in the template's 768px `<main>` vs Lovable's 1400px full-bleed sections. Tagline data exists (`Tagline.php`) but is never rendered. Follow buttons + rating form are logged-in only (not captured). Owner calls: F22 (Follow hidden from guests), F3 (no location field), F16 (pinned-only list), F8 |
| 07 | lees-storie | A | **done** | `767803f` · lovable `58306c5` | All 6 sections present; 82 ids pair; generic `reading-*` ids on shared reading components for row 22. Artikel not captured. Not captured: highlight mark (needs a selection), logged-in follow, drop-cap pseudo. `reading-author-follow` can't pair — the button already carries row 06's `skrywer-follow-btn` (FollowToggle stamps it everywhere). Owner calls: read time vs date (S-5/S-6), share button (S-8), confirm Reply/upvote disclosures (S-15/S-16) |
| 08a | biblioteek | A | **done** | `1b9c83d` · lovable `c4df0b2` · cards: uncommitted | **Cards now measured via `/qa-bloks/`** (owner ruling 2026-10-13; T7 scoped capture, spine §7): 31 ids pair, including all 24 shelf/grid/card ids; gates pass. `findings/08a-biblioteek-drift-cards*.md`. Still unmeasured: cover image (the first card has none), hover. Card drifts: opaque shelf-card fill, heavier resting shadow, uniform 8px inner gap, pill 4/12 vs 2/10. Also: "Soek" button SCOPE?; cover-image divergence rests on an implementer comment; copy — h1 is just "Biblioteek", bylines lack "deur" |
| 08b | opleiding | A | **done** | `1b9c83d` · lovable `c4df0b2` · cards: uncommitted | **Cards now measured via `/qa-bloks/`** (spine §7): 35 ids pair, including all 24 shelf/count/grid/card ids; gates pass. `findings/08b-opleiding-drift-cards*.md`. Fixture articles have no author, so the author line is unmeasured. Shelf h2 is 600 here and 400 on 08a. Search button renders in Arial (no font-family) |
| 09a | meld-aan | A | **done** | see 09b | 12 ids pair. Owner calls: tabs split → SCOPE or STRUCTURE (M4/R4); should auth pages carry header/footer (M1/R1); remember-me rests on an implementer comment (M10); brand lock-up + back link (M2/M13) |
| 09b | registreer | A | **done** | `5dd2136` · lovable `31db630` (09a + 09b) | 8 ids pair. **Owner call:** registreer asks username + e-mail with no password (WP core) vs Lovable's e-mail + password (R9). Not captured: fout/voltooi notices, focus/hover, toast, social section (gated off) |
| 10 | wagwoord-herstel | A | queued | | |
| 11 | wagwoord-terugstel | C | queued | | |
| 12 | vier-nul-vier | C | queued | | |
| 13 | oor-ink | B | queued | | |
| 14 | uitdagings | B | queued | | |
| 15–18 | kontak, lidmaatskap, inkpols, ledegids | C | queued | | one batched subagent (kickoff) |
| 22 | lees-gedig | A | **done** `2a4574b` · lovable `9db3869` | Owner calls: INK pre-fills hearts/highlights for every line any reader reacted to (disclosed `functions.php:583-588`; Lovable shows none at rest); dedication field; poetry-specific response copy. **Live bug:** artikel floating heart is a clickable button but its script only loads on gedig/storie. Hover heart, logged-in and response cards not captured | | shares Lovable `ReadStory.tsx` with row 07, so it runs after 07 to avoid id collisions |

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
| T7 | rows 08a/08b | Owner ruling 2026-10-13: measure the library cards from `/qa-bloks/` (live archives hold only QA fixtures, hidden outside that page). That page embeds both archives, so their shared ids collide (T5). Needs a capture scope option (record only nodes inside a container selector) or equivalent, then supplementary 08a/08b card captures + diffs. | **fixed (T5–T7 commit)** — `capture --scope '<css>' --scope-label <name>` records only the first match's subtree (paths stay body-relative), writes `<page>@<vp>[-auth][-<state>]-scope-<name>.ndjson`, and adds `scope`/`scopeLabel`/`scopePath` to `_meta`. `verify` checks that the scope root still resolves to the same node; the gate passes the flags through. 08a/08b card captures done |
| T5 | row 06 | `diff.mjs` pairs the **last** element when an audit-id occurs more than once (`diff.mjs:91-92`), silently mis-pairing. Checked 2026-10-13: no capture on disk has a duplicate id, so no committed finding is affected. Fix: make diff (and capture) fail loudly on a duplicate id. | **fixed (T5–T7 commit)** — diff exits 2, writes nothing and lists each duplicated id with its node paths; capture warns on stderr. Re-checked: none of the 116 captures on disk has a duplicate id |
| T6 | row 06 | `extract-primitives` (post-T2) tags anything containing "gradering" as `tier-label`, misses INK `__genre` pills, and writes one row per viewport instead of collapsing identical ones (README §4.1 register spec). | **fixed (T5–T7 commit)** — `tier-label` only for `.ink-gradering` or a `…__gradering` element (badge parts and badge wrappers dropped, `__lesergradering*` no longer matched); a pill-shaped `__genre` is a `pill`; identical rows across the captures passed in one run collapse to `390\|768\|1440` (per occurrence; `--no-collapse` opts out) |
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
- **2026-10-13** — Tooling pass for T5/T6/T7 (uncommitted). Unscoped captures are
  byte-identical to before (tuisblad@1440, both sides), and so is diff output
  (`01-gemeenskap-drift.md`). Rows 08a/08b cards are measured from `/qa-bloks/` with
  scoped captures, and both rows are now `done`.

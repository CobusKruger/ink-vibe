# Handover: Lovable design delta (Oct 2026)

**Date:** 2026-10-26. **Read this first.** It is self-contained: it needs no access to earlier Claude sessions or to Claude's memory.

It covers two sessions:
- the main strategy session;
- a forked session that settled InkPols.

**Repo state:** branch `docs/lovable-delta-2026-10`, cut from `main` at `e82af1c` (branch `fix/rehearsal-findings` is merged). It holds this folder, the InkPols CSV and the class-map move. Not merged.

## 1. The documents

| File | What it is | Authority |
|---|---|---|
| `HANDOVER.md` | State, rulings, corrections, next steps | Start here |
| `README.md` | The build strategy: new elements (§1), changes to existing pages (§2), method, sequence and work packages (§3), owner decisions (§4), docs to update (§5), risks (§6) | Authoritative for the method. §4 is up to date. |
| `inkpols.md` | The full WP4 InkPols specification, including final Afrikaans copy | Authoritative for InkPols |
| `wp-content/plugins/ink-core/migration-data/inkpols-uitgawes.csv` | InkPols issue metadata. Prefilled with slug, naam, datum, pdf, voorblad, ou_url; titel, beskrywing and the featured items are blank, for the owner. | Committed, through a `.gitignore` exception |
| `wp-content/plugins/ink-core/migration-data/README.md` | Describes that CSV | |

**Lovable reference:** `ink-lovable` from `71c0847` (the last commit the fidelity rework synced) to `86b69dd`, 22 commits. It adds:
- **New pages:** ChallengeResults, Competitions, Magazine, MagazineIssue.
- **New home sections:** ResultsBanner and MagazineSection.
- **Changed:** the home winners section was rebuilt, the reading page gained an award aside, and the header and footer gained links. Library headings changed.
- **No new design tokens.**

## 2. What the main session did

1. It read the full Lovable delta. The lead read it directly, which was cheaper than a subagent at this size.
2. Two fresh Opus subagents mapped the INK side:
   - **New design elements → existing ink-core capability.** Most of it extends existing machinery: Placements, ModeratorFeedback, WinnersPost, FeaturedWinners, the InkPols CPT. Kompetisies (gala, external directory, categories) is mostly new.
   - **Page↔template linkage and the docs a refresh depends on.** Covered SystemPages, the template inventory, pages still stored in the DB, a doc checklist, and whether the fidelity harness can serve as an acceptance test.
3. The lead spot-checked the key claims in code before writing `README.md`.

**The strategy in one paragraph.** It inverts the fidelity rework: audit artefacts come *before* the build and act as its acceptance test.
- **Before any code:** each page gets a ratified build spec (section spine, `data-audit-id` map, a ratified recipe for every element, a data source per field, copy keys). All recipe gaps are ratified in one batch first.
- **Pages are code, never post_content:** template + pattern + blocks, registered via `SystemPages`, a CPT, or a rewrite endpoint.
- **New editorial data** is migrated or seeded from committed files.
- **Acceptance:** a page is accepted by the capture/diff harness, then must pass the same diff again after `wp ink migrate-all` on a restored prod copy.
- **Build order:** WP1 Uitslae → WP2 reading-page award aside → WP3 Tuis → WP4 InkPols → WP5 Kompetisies → WP6 header. WP4 may go first, because it is fully specified.

## 3. Owner rulings (2026-10-26)

| # | Ruling |
|---|---|
| D1 | The **header gains Kompetisies**; its other items are correct as they are. The header/menu *style* must match Lovable. **InkPols is not in the header.** It is reached only from its home-page section. |
| D2 | `/kompetisies/` is the new design for prod's "Eksterne Kompetisies" Page (ID 59192). SystemPages adopts that page and the legacy body is emptied in code. |
| D3/D4 | The annual competition's **web pages are in scope**: announcements, gala photos, external competition links. Only the **admin side** is deferred, and it is still undefined: how entries reach the judges and how winners come back. Gala ticket prices are **fictional, with no way to pay on the site**. Payments come after lidmaatskap payments. |
| D6 | Judges' citations show **only if the author has opted in**, using the existing `ModeratorFeedback` display toggle (off by default). Otherwise they are not shown anywhere; the placement itself still shows. *The owner wrote "pinned by the author"; this was read as that opt-in toggle and has not been contradicted.* |
| D7 | **Not a decision; settled long ago.** Brons/Silwer/Goud is the author's Gradering and applies **to Gedigte only**. `Challenges/Pools.php:19-40` (Story 12.5) splits *every* category by Gradering. That is a **bug**, fixed at the start of WP1 (README §3.3) before the podium is built. It touches Placements, Coverage, ResultsParser and their tests. Check the migrated result data for any storie/artikel rows that carry a gradering. |
| D8 | Accepted: add a per-round judges' letter (meta on the `uitdaging`), and capture honourable mentions, which the parser currently discards (`ResultsParser.php:147-151`). |
| D9 | InkPols is fully specified in `inkpols.md`. |
| Footer | All footer links are deferred ("a shit show"). Out of scope. |

**Still open.** Recommendations are in README §4.2.
- **D5:** results at `/uitdaging/<slug>/uitslae/`. Does the winners post 301 to it?
- **D10:** exactly two home winner cards (Storie + Gedig), with a lifetime win count derived from placements, because `ink_tier_win_count` resets on promotion.
- **D11:** place labels ("1ste/2de/3de plek" vs the existing "algehele wenner/wenner"), and how a Meester winner is labelled on the Goud podium.
- **D12:** the current round is shown on Tuis only in the hero; the rebuilt winners section drops the current-challenge card.

## 4. Corrections and clarifications

- **The forked session's handover said the rulings had never been written into README §4.** That was wrong; they were in §4.1. README §4 now also carries D9, and N2/N3/N6, WP4 and §3.4 are updated from the InkPols spec.
- **Header (agent finding 1).** The header items on the site are right. Only the style has to match Lovable, plus the Kompetisies item. A separate issue: the `wp_navigation` "Hoofnavigasie" built by `Migration/NavigationRebuilder.php` is not used by the header (`patterns/header-main.php` hardcodes its links). The two lists have drifted apart. Leave this alone unless it causes trouble.
- **Footer (agent finding 2).** Deferred. It also links the non-existent `/geleenthede`.
- **InkPols (agent finding 3).** The problem was migration, not design:
  - Prod keeps each issue as a root Page with an embedded PDF.
  - INK's `migrate-inkpols` step expected a CPT and does nothing, so `/inkpols/` is empty after every refresh.
  - The fix is the CSV-driven migration step in `inkpols.md` §3.
  - `docs/migration-plan.md`'s InkPols section is stale (it assumes a CPT).
- **Handoff changelog (agent finding 4).** `docs/design-handoff/README.md` last records ink-lovable `04217f7` (July). The `lovable-design-sync` skill scopes each run from that recorded hash. Record `71c0847` as the baseline at the next sync, or that sync will re-process everything since July. This is a one-line fix in strategy step C.
- **Other stranded or stale facts the subagents found:**
  - Nothing calls `ModeratorFeedback::feedbackFor()` or the theme's `ink_foundation_wenner_banier()`; WP2 gives them their first consumer.
  - The home winners card renders only from a theme demo filter (`functions.php:1336-1361`); the real `WinnersPost::featured()` payload carries only id/rank/title/url.
  - `SystemPages` adopts an existing prod Page with the same slug and keeps its body.
  - `/qa-bloks/` and several harness fixture URLs in `tools/fidelity/targets.json` do not survive a refresh.

## 5. ⚠ Before switching machines

**Already fixed (2026-10-26, branch `docs/lovable-delta-2026-10`):**
- `migration-data/inkpols-uitgawes.csv` now has a `.gitignore` exception and is committed.
- The Stage 3 class map moved from `tmp/stage3/class-map.md` to `docs/fidelity-remediation/class-map.md`, and the references to it (`primitives.css` header, `stage-1-progress.md`) were updated.

**Still not in git.** These stay on the old machine unless you copy them. Neither git nor a refresh from live brings them back.

| Item | Why it matters | Action |
|---|---|---|
| `migration-data/writer-accounts.csv`, `writer-aliases.csv`, `result-post-authors.csv` | `migrate-all` reads them (tier derivation, result posts). They are gitignored and **were never committed**, even though the runbook says "every human decision is a committed file". | **Copy by hand** (they hold names and usernames), or decide to commit them |
| `tmp/post-roadmap-followup-worklist.md` | The **active** launch-prep worklist with owner decisions (e.g. the Meester-label item behind D11) | Copy, or move into `docs/` |
| `tmp/migration-rework-worklist.md` | Decisions log of the migration rework. Mostly done, and largely captured in `docs/migration-runbook.md` | Copy (reference only) |
| `tmp/rows-c/off-token.py` (plus `tmp/fidelity-password/off-token.py`, `tmp/r1314/offtoken.py`) | The **generator** cited by the committed Treatment C findings (12, 15–18) for the OFF-TOKEN check. Without it those findings cannot be re-run. | Copy, or move into `tools/fidelity/` and update the "Rerun" lines in those findings |
| `tmp/nuwe-ink-prepared.sql.gz` (2026-10-25 prepared prod dump) | Source for the InkPols analysis and for rehearsals | Copy, or take a fresh prod copy |
| `docs/data-cleanup/` | Winners/gradering canonical files (hold emails, so deliberately gitignored). Gradering is now derived by `wp ink derive-tiers`, so they are optional. | Copy if you want the history |
| `_bmad/custom/config.user.toml` | Your local BMAD user config | Copy |
| `tmp/wpcli.sh` | Helper that points wp-cli at Local's MySQL socket; the socket path is machine-specific | Recreate on the new machine (`docs/fidelity-remediation/README.md` §9) |
| The Local `nuwe-ink` site, its DB and its `wp-content/uploads` | The capture target | Rebuild: new Local site, fresh prod copy (DB + uploads), `wp ink migrate-all` |
| Capture tooling: nvm Node 24, Playwright browsers in `tmp/ms-playwright`, `tmp/fidelity-auth/` | The harness needs them | Reinstall (`docs/fidelity-remediation/harness-worklist.md`); `run.sh login` recreates the auth session |
| Claude memory (`~/.claude/projects/...`) | Session notes | Everything relevant to this work is in this folder |

Everything else in `tmp/` is regenerable output: captures, diffs, logs, Lovable builds.

`ink-lovable` is level with `origin/main`, so a clone brings it across. Push it after step B adds the audit-ids.

## 6. Next steps

1. **Owner:**
   - fill in `inkpols-uitgawes.csv` (titel, beskrywing, featured items);
   - rule on D5, D10, D11, D12;
   - decide how the CSVs travel (§5).
2. **Merge** branch `docs/lovable-delta-2026-10` into `main` when ready (it is committed). `git merge` needs the sandbox off.
3. **Strategy step B:** add `data-audit-id`s, Lovable-first, to every new or changed Lovable file: ChallengeResults, Magazine, MagazineIssue, MagazineSection, ResultsBanner, Competitions, the rebuilt ChallengeSection (restore the tuisblad ids where they still pair), and the ReadStory aside. Commit in `ink-lovable`.
4. **Step C:** amend `.claude/skills/lovable-design-sync/SKILL.md` per README §5, then run it for `71c0847..<new SHA>`.
5. **Then README §3.2 steps D–H**, one work package at a time. WP4 InkPols can go first.

## 7. Working rules

- Opus subagents, **at most 3 in parallel**, with lean briefs. The owner is cost-sensitive; the lead does small edits itself and commits per work package in both repos.
- **Fidelity rules R1–R9** in `docs/fidelity-remediation/README.md` apply throughout:
  - no presence/absence claims from grep;
  - no values claimed from source or screenshots, only from captures;
  - no primitives defined outside `primitives.css`.
- **"Lovable wins" is the default** for routine style mismatches; don't ask the owner element by element. Escalate only genuine conflicts and scope questions.
- **Afrikaans is the source of truth.**
  - Never ship the mockup's English, and never AI-translate it.
  - Unknown copy gets a copy-debt marker.
  - Owner-supplied copy (e.g. `inkpols.md` §2) is final.
- **Before any INK capture:**
  - run `tools/deploy-to-local.sh --apply` and confirm with a cache-busted curl;
  - Chromium and the preview server need the sandbox off;
  - so do `composer stan` and `git merge`.
- **Repo conventions:**
  - Temp files go only in repo-local `./tmp/`.
  - Shell writes to `docs/` and `_bmad-output/` are blocked; use Edit/Write.
  - No `cd` prefix on shell commands.
  - No commit trailers.
- **When the owner says "take notes"**, confirm in one line and do nothing else.

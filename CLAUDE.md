# CLAUDE.md

Project instructions for Claude Code. These apply to all sessions in this repo.

## THE FIRST RULE (owner, 2026-11-01)

**THE PRESENCE OF A CSV DOES NOT MEAN WE'RE NOT WRITING CODE FOR THE SITE ANYMORE.** Data is prepped for the migration so we don't need to handle every extreme corner case. **When the site goes live, it runs entirely without being fed new CSV files.**

- `migrate-all` uses CSVs only to import what existed before the cutover.
- Anything that changes after go-live is added and edited on the site, through real features (post types, admin screens), and that code gets built.
- Never propose a CSV, a seed file or a re-run of a migration as how the live site is operated. Never invent a way to avoid doing the work.

## Project at a glance

- New site to replace prod **ink.org.za**. WordPress: theme `wp-content/themes/ink-foundation`, plugin `wp-content/plugins/ink-core`.
- Requirements: 19 BMAD epics (`_bmad-output/`, `sprint-status.yaml`). Epics 1–18 done; 19 (theme fidelity) in progress.
- **Design = `../verse-haven`** (Lovable; called `ink-lovable` in older docs). Match its colours, spacing and layout at very high fidelity.
- **Design ≠ copy.** Its text is English and reflects simplified requirements. Afrikaans copy in this repo and the epics is the source of truth. Never port, or AI-translate, Lovable's English. Unknown copy gets a copy-debt marker.

## Read the code first

- Before stating what the design or the site does, read the code: `../verse-haven` for the design, this repo and the rendered page for the site.
- Handover and planning docs are unverified leads, not facts; `docs/lovable-delta-2026-10/HANDOVER.md` has been wrong about the design. The owner's description outranks any inference.
- **Tuis order** (owner, 2026-10-30, matches `verse-haven/src/pages/Index.tsx`): hero (intro left, active challenge right) → results banner → magazine (InkPols) → winner cards (four, not Lovable's two) → featured writing (recent posts for now) → sponsors (hand-made Borge posts) → CTA → footer. Where the dev page differs, the dev page is wrong.
- The footer is known to be a mess and is deferred.

## Fidelity — do not repeat the rework

- Read `docs/fidelity-remediation/README.md` before **any** visual/styling work. Rules R1–R9 are binding:
  - no presence/absence claims from grep;
  - style values only from rendered captures (`tools/fidelity/run.sh`), never from source or screenshots;
  - join on `data-audit-id`, assigned Lovable-first;
  - primitives (buttons, cards, pills, forms, headings, bands) live only in the shared `primitives.css`. Never page-scoped.
  - deliberate divergences are disclosed in code comments; respect them.
- **The acceptance gate is one command:** `tools/fidelity/accept/gate-all.sh` (Git Bash on Windows, bash on macOS; page/state list in `tools/fidelity/accept/gates.json`; how to run it and what it needs: `docs/fidelity-remediation/README.md` §9). Do not rebuild it in `tmp/`.
- New pages follow the *inverted* method (`docs/lovable-delta-2026-10/README.md`): ratified build spec + audit-ids **before** code; the capture/diff harness is the acceptance test.
- "Lovable wins" for routine style mismatches. Escalate only real conflicts or scope questions.

## Current work (Oct 2026)

- **Design delta** (verse-haven `71c0847..86b69dd`): start at `docs/lovable-delta-2026-10/HANDOVER.md`. Work packages WP1 Uitslae → WP6 header; **WP4 InkPols** is fully specified in `inkpols.md` and may go first.
- **Pages are code**, never `post_content`: template + pattern + blocks, registered via `SystemPages`, CPT or rewrite.
- **Monthly rounds have no separate deadline:** a round runs from the first to the last day of its month (October: 1 to 31 October). Never treat this as missing data or ask for it. The month comes from the title; recent rounds all carry it. The post date verifies it: rounds are created in the first days of the month, normally the 1st, seldom after the 3rd, with a few outliers shortly before month-end. If there is no new challenge yet, the site keeps showing the last one.
- **Post type follows the category map in `docs/migration-plan.md` §"Current state"** (`artikels`, `algemeengorrel`, `rubrieke`, `prosa`, `prosa-lede-bydraes` give `artikel`). Search the docs for a ruling before asking the owner.
- **Handoffs:** `docs/handoffs/homepage-dev-round.md` (done). The Gradering fix, the competition exclusion and the winner importer are built (2026-11-03) and wait to be proved on a real refresh: `docs/handoffs/winner-cleanup.md`.

## Winner data

- **Wins come from the old challenge result posts**, read by `LegacyResultsParser` (`wp ink derive-tiers`). No hand-curated winner list is an input (owner, 2026-11-03).
- **Migration inputs are exactly three files in `../migration-data/`** (`docs/migration-data.md`): `writer-accounts.csv`, `writer-aliases.csv`, `inkpols-uitgawes.csv`. **Every other CSV, spreadsheet or list is banned as a migration input.** Old lists live in `../backup-data/` and are never read by code.
- **Best effort for inactive members:** someone who has posted nothing in a year doesn't get reviewed. A win count slightly off for them is accepted.
- **Compare typed titles and names only through `Ink\Migration\TextKey`.** It handles dropped accents, every spelling of 'n (ŉ, ñ, ń, 'n, ’n, n, N) and grades added to titles.
- **Competitions are not challenges**, and are never counted as challenges.

## Dev site (nuwe-ink) and deployment

- **dev / nuwe-ink** = Local (Flywheel) site `https://nuwe-ink.local`. It does **not** read this repo; changes are visible only after deploying (`node tools/deploy-to-local.mjs`, dry run first, then `--apply`), then cache purge.
- **Refresh from prod:** `docs/migration-runbook.md` + `node tools/rehearsal-prep.mjs`, then `node tools/local.mjs wp ink migrate-all`. Prod is never touched.
- **Tooling is cross-platform (macOS + Windows).** The site is found via Local's `sites.json` (`tools/lib/local-site.mjs`); never hardcode machine paths. Run WP-CLI/MySQL against the site via `node tools/local.mjs wp|mysql|mysqldump …` (plain `wp` can't connect). Windows setup: `tools/setup-windows.ps1`.
- **All migration data lives only in `../migration-data/`** (three files), outside the repo (owner's choice; `docs/migration-data.md`). The site reads it via `INK_MIGRATION_DATA_DIR`, with no fallback. Never copy it into the repo. That folder is the working copy: edit it directly.
- New-machine checklist: `docs/lovable-delta-2026-10/HANDOVER.md` §5.1.
- Table prefix is `wpjj_`. Capture as `fidelity-capture` (recreated after every refresh), never `admin`.

## Transient & temp files

- Write **all** transient/temporary files — scratch scripts, query files, intermediate output, diffs, `git commit -F` message files — to the repo-local `./tmp/` directory. It is gitignored (`/tmp/*` with `!/tmp/.gitkeep`), so nothing there is ever committed. This applies to **every** agent (main session and subagents) working in this repo.
- Do **not** use `$CLAUDE_JOB_DIR/tmp`, `$TMPDIR`, `/tmp`, or any other location for scratch. `./tmp/` is the single supported temp directory. (In this sandboxed repo `/tmp` and `$CLAUDE_JOB_DIR/tmp` shell-writes are blocked anyway — see [[shell-write-deny-artifact-trees]] context.)
- For multi-line commit messages, write the message to `tmp/commit-msg.txt` and run `git commit -F tmp/commit-msg.txt`. Do **not** use heredoc `-m "$(cat <<'EOF' … EOF)"` — the heredoc/angle-bracket text trips the zsh `<N-M>` glob permission heuristic and forces an approval prompt.

## Commit messages

- Do not append `Co-Authored-By` or `Claude-Session` trailers. This is enforced via `attribution.commit: ""` in `.claude/settings.json`; do not reintroduce the trailers manually.

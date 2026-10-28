# CLAUDE.md

Project instructions for Claude Code. These apply to all sessions in this repo.

## Project at a glance

- New site to replace prod **ink.org.za**. WordPress: theme `wp-content/themes/ink-foundation`, plugin `wp-content/plugins/ink-core`.
- Requirements: 19 BMAD epics (`_bmad-output/`, `sprint-status.yaml`). Epics 1–18 done; 19 (theme fidelity) in progress.
- **Design = `../verse-haven`** (Lovable; called `ink-lovable` in older docs). Match its colours, spacing and layout at very high fidelity.
- **Design ≠ copy.** Its text is English and reflects simplified requirements. Afrikaans copy in this repo and the epics is the source of truth. Never port, or AI-translate, Lovable's English. Unknown copy gets a copy-debt marker.

## Fidelity — do not repeat the rework

- Read `docs/fidelity-remediation/README.md` before **any** visual/styling work. Rules R1–R9 are binding:
  - no presence/absence claims from grep;
  - style values only from rendered captures (`tools/fidelity/run.sh`), never from source or screenshots;
  - join on `data-audit-id`, assigned Lovable-first;
  - primitives (buttons, cards, pills, forms, headings, bands) live only in the shared `primitives.css`. Never page-scoped.
  - deliberate divergences are disclosed in code comments; respect them.
- New pages follow the *inverted* method (`docs/lovable-delta-2026-10/README.md`): ratified build spec + audit-ids **before** code; the capture/diff harness is the acceptance test.
- "Lovable wins" for routine style mismatches. Escalate only real conflicts or scope questions.

## Current work (Oct 2026)

- **Design delta** (verse-haven `71c0847..86b69dd`): start at `docs/lovable-delta-2026-10/HANDOVER.md`. Work packages WP1 Uitslae → WP6 header; **WP4 InkPols** is fully specified in `inkpols.md` and may go first.
- **Pages are code**, never `post_content`: template + pattern + blocks, registered via `SystemPages`, CPT or rewrite.

## Dev site (nuwe-ink) and deployment

- **dev / nuwe-ink** = Local (Flywheel) site `https://nuwe-ink.local`. It does **not** read this repo; changes are visible only after deploying (`node tools/deploy-to-local.mjs`, dry run first, then `--apply`), then cache purge.
- **Refresh from prod:** `docs/migration-runbook.md` + `node tools/rehearsal-prep.mjs`, then `node tools/local.mjs wp ink migrate-all`. Prod is never touched.
- **Tooling is cross-platform (macOS + Windows).** The site is found via Local's `sites.json` (`tools/lib/local-site.mjs`); never hardcode machine paths. Run WP-CLI/MySQL against the site via `node tools/local.mjs wp|mysql|mysqldump …` (plain `wp` can't connect). Windows setup: `tools/setup-windows.ps1`.
- The writer/moderator migration CSVs live **outside** the repo in `../migration-data/` (owner's choice); the site reads them via `INK_MIGRATION_DATA_DIR` in `wp-config.php`. Don't move them into the repo.
- New-machine checklist: `docs/lovable-delta-2026-10/HANDOVER.md` §5.1.
- Table prefix is `wpjj_`. Capture as `fidelity-capture` (recreated after every refresh), never `admin`.

## Transient & temp files

- Write **all** transient/temporary files — scratch scripts, query files, intermediate output, diffs, `git commit -F` message files — to the repo-local `./tmp/` directory. It is gitignored (`/tmp/*` with `!/tmp/.gitkeep`), so nothing there is ever committed. This applies to **every** agent (main session and subagents) working in this repo.
- Do **not** use `$CLAUDE_JOB_DIR/tmp`, `$TMPDIR`, `/tmp`, or any other location for scratch. `./tmp/` is the single supported temp directory. (In this sandboxed repo `/tmp` and `$CLAUDE_JOB_DIR/tmp` shell-writes are blocked anyway — see [[shell-write-deny-artifact-trees]] context.)
- For multi-line commit messages, write the message to `tmp/commit-msg.txt` and run `git commit -F tmp/commit-msg.txt`. Do **not** use heredoc `-m "$(cat <<'EOF' … EOF)"` — the heredoc/angle-bracket text trips the zsh `<N-M>` glob permission heuristic and forces an approval prompt.

## Commit messages

- Do not append `Co-Authored-By` or `Claude-Session` trailers. This is enforced via `attribution.commit: ""` in `.claude/settings.json`; do not reintroduce the trailers manually.

# CLAUDE.md

Project instructions for Claude Code. These apply to all sessions in this repo.

## Transient & temp files

- Write **all** transient/temporary files — scratch scripts, query files, intermediate output, diffs, `git commit -F` message files — to the repo-local `./tmp/` directory. It is gitignored (`/tmp/*` with `!/tmp/.gitkeep`), so nothing there is ever committed. This applies to **every** agent (main session and subagents) working in this repo.
- Do **not** use `$CLAUDE_JOB_DIR/tmp`, `$TMPDIR`, `/tmp`, or any other location for scratch. `./tmp/` is the single supported temp directory. (In this sandboxed repo `/tmp` and `$CLAUDE_JOB_DIR/tmp` shell-writes are blocked anyway — see [[shell-write-deny-artifact-trees]] context.)
- For multi-line commit messages, write the message to `tmp/commit-msg.txt` and run `git commit -F tmp/commit-msg.txt`. Do **not** use heredoc `-m "$(cat <<'EOF' … EOF)"` — the heredoc/angle-bracket text trips the zsh `<N-M>` glob permission heuristic and forces an approval prompt.

## Commit messages

- Do not append `Co-Authored-By` or `Claude-Session` trailers. This is enforced via `attribution.commit: ""` in `.claude/settings.json`; do not reintroduce the trailers manually.

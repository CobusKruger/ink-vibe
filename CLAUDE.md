# CLAUDE.md

Project instructions for Claude Code. These apply to all sessions in this repo.

## Transient files & commit messages

- Write transient files that **git must read** — e.g. `git commit -F` message files — to the repo-local `tmp/` directory. It is gitignored (`/tmp/*` with `!/tmp/.gitkeep`), so scratch files there are never committed.
- For multi-line commit messages, write the message to `tmp/commit-msg.txt` and run `git commit -F tmp/commit-msg.txt`. Do **not** use heredoc `-m "$(cat <<'EOF' … EOF)"` — the heredoc/angle-bracket text trips the zsh `<N-M>` glob permission heuristic and forces an approval prompt.
- Purely ephemeral scratch that git never needs may go to the session/job temp dir instead; reserve repo `tmp/` for things git reads or that should be repo-local.

## Commit messages

- Do not append `Co-Authored-By` or `Claude-Session` trailers. This is enforced via `attribution.commit: ""` in `.claude/settings.json`; do not reintroduce the trailers manually.

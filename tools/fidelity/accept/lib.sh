#!/usr/bin/env bash
# Sourced by the accept/*.sh entry points. Goes to the repo root and defines the helpers.
#   Works from Git Bash on Windows and bash on macOS/Linux. tools/fidelity/run.sh finds Node 20+ (PATH, the Windows
#   registry PATH, or nvm) and sets the Playwright env, so every node call goes through `run.sh node`.
ACCEPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$ACCEPT_DIR/../../.." || exit 1
RUN=tools/fidelity/run.sh
# the node runners spawn bash for determinism-check.sh: hand them this shell's own bash as a native path
BASH_EXE="$BASH"; command -v cygpath >/dev/null 2>&1 && BASH_EXE="$(cygpath -m "$BASH")"; export BASH_EXE

# run.sh node <script> [args]
nodex() { "$RUN" node "$@"; }
# WP-CLI against the dev site (Local's DB connection, tools/lib/local-site.mjs)
wpc() { "$RUN" node tools/local.mjs wp "$@"; }
say() { printf '%s\n' "$*"; }
stamp() { date +%Y%m%d-%H%M; }

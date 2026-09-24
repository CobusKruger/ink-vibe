#!/usr/bin/env bash
# Wrapper for the fidelity capture tools.
#
# Three environment facts make the harness work; getting any of them wrong
# produces a different confusing failure, so they live here rather than in
# anyone's shell history. See docs/fidelity-remediation/README.md §9.
#
#   1. Node 20+ — Playwright 1.63 hard-refuses on the machine default (18.17.1).
#      v24.12.0 is already installed under nvm; this does NOT change your default.
#   2. PLAYWRIGHT_BROWSERS_PATH — the sandbox denies ~/Library/Caches/ms-playwright,
#      so browsers live in the repo-local gitignored tmp/.
#   3. NODE_PATH — the tools resolve `playwright` from the repo's node_modules.
#
# NOTE: Chromium cannot launch inside the macOS Seatbelt sandbox (it is denied
# its Mach rendezvous port), and a preview server cannot bind a socket there.
# Every capture run needs the sandbox off.
#
# Usage:  tools/fidelity/run.sh capture --url … --side … --page …
#         tools/fidelity/run.sh diff    --a … --b …

set -euo pipefail

REPO="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
NODE_BIN="$HOME/.nvm/versions/node/v24.12.0/bin"

if [[ ! -x "$NODE_BIN/node" ]]; then
  echo "error: expected Node 20+ at $NODE_BIN" >&2
  echo "       adjust NODE_BIN in this script, or install one: nvm install 24" >&2
  exit 1
fi

export PATH="$NODE_BIN:$PATH"
export PLAYWRIGHT_BROWSERS_PATH="$REPO/tmp/ms-playwright"
export NODE_PATH="$REPO/node_modules"

if [[ ! -d "$PLAYWRIGHT_BROWSERS_PATH" ]]; then
  echo "error: no browsers at $PLAYWRIGHT_BROWSERS_PATH" >&2
  echo "       run: PLAYWRIGHT_BROWSERS_PATH=$PLAYWRIGHT_BROWSERS_PATH \\" >&2
  echo "            $REPO/node_modules/.bin/playwright install chromium" >&2
  exit 1
fi

tool="${1:-}"
shift || true

case "$tool" in
  capture)    exec node "$REPO/tools/fidelity/capture.mjs" "$@" ;;
  diff)       exec node "$REPO/tools/fidelity/diff.mjs" "$@" ;;
  primitives) exec node "$REPO/tools/fidelity/extract-primitives.mjs" "$@" ;;
  verify)     exec node "$REPO/tools/fidelity/verify.mjs" "$@" ;;
  login)      exec node "$REPO/tools/fidelity/login.mjs" "$@" ;;
  *)
    echo "usage: run.sh <capture|diff|primitives|verify|login> [args…]" >&2
    exit 1
    ;;
esac

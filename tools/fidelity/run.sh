#!/usr/bin/env bash
# Wrapper for the fidelity capture tools.
#
# Three environment facts make the harness work; getting any of them wrong
# produces a different confusing failure, so they live here rather than in
# anyone's shell history. See docs/fidelity-remediation/README.md §9.
#
#   1. Node 20+ — Playwright 1.63 hard-refuses anything older. The `node` on PATH
#      is used if it is new enough; otherwise, on Windows, the Machine + User PATH
#      from the registry (Git Bash may start without them); otherwise the newest
#      nvm-installed Node 20+ (macOS nvm or nvm-windows). This does NOT change your default.
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
node_major() { "$1" -p 'process.versions.node.split(".")[0]' 2>/dev/null || echo 0; }
path_node_ok() { command -v node >/dev/null 2>&1 && (( $(node_major node) >= 20 )); }

# Git Bash (MSYS2 / Cygwin): node.exe is a native Windows program, so it gets native
# paths, and MSYS argument mangling stays off. Otherwise a selector such as
# a[href=/skryf] would be silently rewritten into a Windows path.
ON_WINDOWS=0
case "${OSTYPE:-}" in msys*|cygwin*) ON_WINDOWS=1 ;; esac
if (( ON_WINDOWS )); then
  REPO="$(cygpath -m "$REPO")"
  export MSYS2_ARG_CONV_EXCL='*'
fi

# Git Bash can start with neither the Machine nor the User PATH that the Node and PHP
# installers wrote to, so read them (and NVM_HOME) from the registry. Appended, so the
# Unix tools keep priority.
if (( ON_WINDOWS )) && ! path_node_ok && command -v powershell.exe >/dev/null 2>&1; then
  win_env="$(powershell.exe -NoProfile -NonInteractive -Command     "[Environment]::GetEnvironmentVariable('Path','Machine')+';'+[Environment]::GetEnvironmentVariable('Path','User');      [Environment]::GetEnvironmentVariable('NVM_HOME','Machine')+[Environment]::GetEnvironmentVariable('NVM_HOME','User')" | tr -d '')" || win_env=""
  win_path="$(sed -n 1p <<<"$win_env")"
  win_nvm="$(sed -n 2p <<<"$win_env")"
  [[ -n "$win_path" ]] && PATH="$PATH:$(cygpath -u -p "$win_path")"
  [[ -z "${NVM_HOME:-}" && -n "$win_nvm" ]] && NVM_HOME="$win_nvm"
fi

NODE_BIN=""
if path_node_ok; then
  NODE_BIN="$(dirname "$(command -v node)")"
else
  # Pick the highest major; no `sort -V` (not in every macOS sort).
  best=0
  shopt -s nullglob
  for dir in "$HOME"/.nvm/versions/node/v*/bin "${NVM_HOME:-/nonexistent}"/v*; do
    for exe in "$dir/node" "$dir/node.exe"; do
      [[ -x "$exe" ]] || continue
      major="$(node_major "$exe")"
      if (( major >= 20 && major > best )); then best="$major"; NODE_BIN="$dir"; fi
    done
  done
  shopt -u nullglob
fi

if [[ -z "$NODE_BIN" ]]; then
  echo "error: Node 20+ not found on PATH or under nvm" >&2
  echo "       install one: nvm install 24" >&2
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

# Mangling is off, so convert a /c/… path argument here. Selectors and URLs do not match.
if (( ON_WINDOWS )); then
  args=()
  for a in "$@"; do
    if [[ "$a" =~ ^/[a-zA-Z]/ ]]; then a="$(cygpath -m "$a")"; fi
    args+=("$a")
  done
  set -- ${args[@]+"${args[@]}"}
fi

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

#!/usr/bin/env bash
# C5 — the determinism gate.
#
# Captures the same page N times and compares the outputs byte for byte.
# If a capture is not reproducible, nothing built on it is trustworthy, so
# this must pass before any finding is generated from a capture.
#
# Usage: determinism-check.sh <url> <side> <page> [viewport] [runs] [expect-selectors] [capture flags…]
#
# Capture flags (anything from the first `--flag` on, an optional `--` separator is
# skipped) are passed through to every capture run: --auth <file>, --click <sel>
# (repeatable), --state <name>, --mode <all|keyed>. --out and --viewport are owned
# by this script.
#
# Each run writes to its own directory, keyed by what it captures (T1):
#   tmp/determinism/<side>-<page>@<vp>[-auth][-<state>]/run<i>/
# Only that directory is cleared, so parallel gates on different pages, sides,
# viewports or states cannot delete each other's runs.

set -uo pipefail

REPO="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
URL="${1:?url required}"
SIDE="${2:?side required}"
PAGE="${3:?page required}"
shift 3

# Optional positionals, until the first flag.
pos=()
while [[ $# -gt 0 && "$1" != --* ]]; do pos+=("$1"); shift; done
[[ "${1:-}" == "--" ]] && shift
VP="${pos[0]:-1440}"
RUNS="${pos[1]:-3}"
EXPECT="${pos[2]:-}"
EXTRA=("$@")

AUTH="" STATE=""
for ((i = 0; i < ${#EXTRA[@]}; i++)); do
  case "${EXTRA[$i]}" in
    --auth)  AUTH="${EXTRA[$((i + 1))]:-}" ;;
    --state) STATE="${EXTRA[$((i + 1))]:-}" ;;
    --out|--viewport)
      echo "error: ${EXTRA[$i]} is set by determinism-check.sh, do not pass it" >&2
      exit 2 ;;
  esac
done

NAME="$PAGE@$VP${AUTH:+-auth}${STATE:+-$STATE}"
BASE="$REPO/tmp/determinism/$SIDE-$NAME"
rm -rf "$BASE"
mkdir -p "$BASE"

for i in $(seq 1 "$RUNS"); do
  args=(capture --url "$URL" --side "$SIDE" --page "$PAGE" --viewport "$VP" --out "$BASE/run$i")
  [[ -n "$EXPECT" ]] && args+=(--expect "$EXPECT")
  [[ ${#EXTRA[@]} -gt 0 ]] && args+=("${EXTRA[@]}")
  if ! "$REPO/tools/fidelity/run.sh" "${args[@]}" > "$BASE/run$i.log" 2>&1; then
    echo "run $i: FAILED to capture (log: $BASE/run$i.log)"
    tail -n 12 "$BASE/run$i.log"
    exit 1
  fi
  echo "run $i: captured"
done

ref="$BASE/run1/$SIDE/$NAME.ndjson"
status=0

for i in $(seq 2 "$RUNS"); do
  cur="$BASE/run$i/$SIDE/$NAME.ndjson"
  if diff -q "$ref" "$cur" > /dev/null 2>&1; then
    echo "run1 vs run$i: IDENTICAL"
  else
    n=$(diff "$ref" "$cur" | grep -c '^[<>]')
    echo "run1 vs run$i: DIFFERS ($n changed lines)"
    diff "$ref" "$cur" | grep '^[<>]' | head -4
    status=1
  fi
done

if [[ $status -eq 0 ]]; then
  echo "GATE PASSED — capture is reproducible ($SIDE $NAME)"
else
  echo "GATE FAILED — do not generate findings from this capture ($SIDE $NAME)"
fi
exit $status

#!/usr/bin/env bash
# C5 — the determinism gate.
#
# Captures the same page N times and compares the outputs byte for byte.
# If a capture is not reproducible, nothing built on it is trustworthy, so
# this must pass before any finding is generated from a capture.
#
# Usage: determinism-check.sh <url> <side> <page> [viewport] [runs] [expect-selectors]

set -uo pipefail

REPO="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
URL="${1:?url required}"
SIDE="${2:?side required}"
PAGE="${3:?page required}"
VP="${4:-1440}"
RUNS="${5:-3}"
EXPECT="${6:-}"

BASE="$REPO/tmp/determinism"
rm -rf "$BASE"

for i in $(seq 1 "$RUNS"); do
  args=(capture --url "$URL" --side "$SIDE" --page "$PAGE" --viewport "$VP" --out "$BASE/run$i")
  [[ -n "$EXPECT" ]] && args+=(--expect "$EXPECT")
  if ! "$REPO/tools/fidelity/run.sh" "${args[@]}" > /dev/null 2>&1; then
    echo "run $i: FAILED to capture"
    exit 1
  fi
  echo "run $i: captured"
done

ref="$BASE/run1/$SIDE/$PAGE@$VP.ndjson"
status=0

for i in $(seq 2 "$RUNS"); do
  cur="$BASE/run$i/$SIDE/$PAGE@$VP.ndjson"
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
  echo "GATE PASSED — capture is reproducible"
else
  echo "GATE FAILED — do not generate findings from this capture"
fi
exit $status

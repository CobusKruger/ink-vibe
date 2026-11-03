#!/usr/bin/env bash
# Lees determinism: 36 checks (3 types x INK/Lovable x signed in/out x 3 viewports), each 3 byte-identical captures
# plus a verify. Intermittent failures happen (a transient crash, a width
# 0.015-0.3 px when a "ure gelede" label ticks over): re-run the run, a clean run is 36 of 36.
#   lees-determinism.sh [label]
. "$(dirname "${BASH_SOURCE[0]}")/lib.sh"
nodex tools/fidelity/accept/determinism.mjs lees --label "${1:-$(stamp)}"

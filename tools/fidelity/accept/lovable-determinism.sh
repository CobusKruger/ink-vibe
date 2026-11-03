#!/usr/bin/env bash
# Lovable determinism: 3 captures byte-identical + verify for every stored Lovable reference capture (read from its
# _meta). Needs the references: run gate-all.sh (or gates.mjs) first. Known noise on the Lovable side makes some fail
# (see determinism.mjs); the count is the thing to compare, not a pass/fail.
#   lovable-determinism.sh [label]
. "$(dirname "${BASH_SOURCE[0]}")/lib.sh"
nodex tools/fidelity/accept/determinism.mjs lovable --label "${1:-$(stamp)}"

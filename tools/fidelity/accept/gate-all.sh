#!/usr/bin/env bash
# The full acceptance gate, one entry point (docs/fidelity-remediation/README.md §9).
#   tools/fidelity/accept/gate-all.sh [label] [--determinism] [--refresh-refs] [gates.mjs flags]
# Order: every page/state gate at 390/768/1440 (data: gates.json)
# -> with --determinism: the Lees determinism run and the Lovable reference determinism run (slow, ~1 h).
# Needs: the Local site up and deployed (node tools/deploy-to-local.mjs --apply), a Lovable preview of verse-haven
# (LOVABLE_URL or "lovablePreview" in tools/local.config.json, else targets.json bases.lovable), browsers in tmp/ms-playwright.
# Exit 0 only if every gate passed. Never refreshes or restores the database.
. "$(dirname "${BASH_SOURCE[0]}")/lib.sh"
label=""; det=0; pass=()
for a in "$@"; do
  case $a in
    --determinism) det=1;;
    --*) pass+=("$a");;
    *) [ -z "$label" ] && label=$a || pass+=("$a");;
  esac
done
label=${label:-$(stamp)}
rc=0
say "##### page and state gates"
nodex tools/fidelity/accept/gates.mjs --label "$label" "${pass[@]}" || rc=1
if [ $det = 1 ]; then
  say "##### Lees determinism"; tools/fidelity/accept/lees-determinism.sh "$label" || rc=1
  say "##### Lovable determinism"; tools/fidelity/accept/lovable-determinism.sh "$label" || rc=1
fi
say "##### ALL GATES: $([ $rc = 0 ] && echo PASSED || echo FAILED) (label $label)"
exit $rc

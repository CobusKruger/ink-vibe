#!/usr/bin/env bash
# The closed-round hero state: Tuis with NO open round (the newest real round shows, with "Lees meer").
#   closed-round.sh [label]
# Drafts the fixture round(s) (title "Toetsronde", made by tools/fixtures/uitslae-fixtures.php), flushes the cache, gates
# the whole Tuis main and the hero alone at 390/768/1440 (group `closed` in gates.json), and ALWAYS republishes the
# fixture(s) afterwards (also on error or Ctrl-C). Standing state: the hero's height deltas against the design come from
# the longer real round description (evidence: docs/lovable-delta-2026-10/specs/03-tuis.md), not from structure.
. "$(dirname "${BASH_SOURCE[0]}")/lib.sh"
label=${1:-$(stamp)}-closed
ids=$(wpc post list --post_type=uitdaging --post_status=publish --s=Toetsronde --field=ID 2>/dev/null | tr -d '\r')
restore() { for i in $ids; do wpc post update "$i" --post_status=publish >/dev/null 2>&1; done; wpc cache flush >/dev/null 2>&1; say "fixture round(s) republished: ${ids:-none}"; }
trap restore EXIT
[ -z "$ids" ] && say "note: no published Toetsronde fixture found (run fixtures.sh rebuild); the newest real round may already be the open one"
for i in $ids; do wpc post update "$i" --post_status=draft >/dev/null 2>&1; done
wpc cache flush >/dev/null 2>&1
ink=$(nodex -e "const t=require('./tools/fidelity/targets.json');process.stdout.write(process.env.INK_URL||t.bases.ink)")
if curl -sk "$ink/?nocache=$RANDOM" | grep -q 'Toetsronde'; then say "closed state NOT reached: the fixture round is still on the page"; exit 1; fi
say "##### closed round (fixture round drafted)"
nodex tools/fidelity/accept/gates.mjs --group closed --label "$label" --ref-dir tmp/fidelity-captures/accept/ref "${@:2}"

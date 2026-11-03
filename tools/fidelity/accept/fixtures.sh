#!/usr/bin/env bash
# Dev-only test data the gates need (docs/migration-runbook.md "Dev-only data"). A refresh wipes it; never run on live.
#   fixtures.sh rebuild   all fixture scripts in order (idempotent), then re-centre the response times  [default]
#   fixtures.sh recentre  only re-centre the relative-time fixtures (response ages), then flush caches
# The response times render as "6 ure gelede" from time(); fix-dates.php sets them to N hours + 30 minutes so the label
# holds for +/- 30 minutes. Run `recentre` at the start of every capture / verify / determinism session.
. "$(dirname "${BASH_SOURCE[0]}")/lib.sh"
f=tools/fixtures
rebuild() {
  wpc eval-file $f/uitslae-fixtures.php || return 1
  wpc eval-file $f/ui-fixture.php create || return 1
  wpc eval-file $f/kompetisies-toets.php || return 1
  wpc eval-file $f/seed-responses.php || return 1
}
recentre() {
  wpc eval-file $f/fix-dates.php || return 1
  wpc cache flush >/dev/null 2>&1
}
mode=${1:-rebuild}
case $mode in
  rebuild)  rebuild && recentre || exit 1;;
  recentre) recentre || exit 1;;
  *) say "usage: fixtures.sh [rebuild|recentre]"; exit 2;;
esac
say "fixtures: $mode done"

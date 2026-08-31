#!/usr/bin/env bash
#
# deploy-to-local.sh — sync ink-foundation (and ink-core, if present) from this
# repo into the Local-by-Flywheel WordPress install at nuwe-ink.local.
#
# nuwe-ink.local does NOT read its theme/plugin code live from this repo —
# it's a separate WordPress install at:
#   /Users/cobus/Local Sites/nuwe-ink/app/public
# Code changes made here need to be deployed across before they're visible
# on the live site.
#
# Usage:
#   tools/deploy-to-local.sh            # dry run — shows what WOULD change
#   tools/deploy-to-local.sh --apply    # actually performs the sync
#
# The sync mirrors each source directory onto its destination counterpart
# (rsync --delete), so stale files at the destination are removed to match
# this repo exactly. Dry-run is the default specifically because of --delete.
#
set -euo pipefail

REPO_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
DEST_ROOT="/Users/cobus/Local Sites/nuwe-ink/app/public"

APPLY=false
if [[ "${1:-}" == "--apply" ]]; then
	APPLY=true
fi

if [[ "$APPLY" == true ]]; then
	RSYNC_FLAGS=(-av --delete)
	echo "== APPLY mode: syncing for real =="
else
	RSYNC_FLAGS=(-av --delete --dry-run)
	echo "== DRY RUN: showing what would change (pass --apply to sync for real) =="
fi

# Common excludes — only add ones that are actually harmless to skip.
EXCLUDES=(--exclude=.DS_Store)

sync_dir() {
	local rel="$1"
	local src="${REPO_ROOT}/${rel}/"
	local dest="${DEST_ROOT}/${rel}/"

	if [[ ! -d "$src" ]]; then
		echo "-- skip: ${rel} does not exist in this repo, nothing to sync --"
		return
	fi

	mkdir -p "$dest"
	echo "-- syncing ${rel} --"
	rsync "${RSYNC_FLAGS[@]}" "${EXCLUDES[@]}" "$src" "$dest"
}

sync_dir "wp-content/themes/ink-foundation"

# ink-core is referenced throughout the codebase/docs as a real plugin, but
# only sync it if it actually exists as a directory in this repo — don't
# invent a sync path for something that isn't checked in here.
sync_dir "wp-content/plugins/ink-core"

if [[ "$APPLY" == true ]]; then
	echo "== attempting cache purge on the destination install (best-effort) =="
	if command -v wp >/dev/null 2>&1; then
		wp cache flush --path="$DEST_ROOT" || echo "-- 'wp cache flush' failed, continuing --"
		wp litespeed-purge all --path="$DEST_ROOT" || echo "-- 'wp litespeed-purge all' failed or not available, continuing --"
	else
		echo "-- 'wp' (WP-CLI) not found in PATH — purge the cache manually: wp-admin -> LiteSpeed Cache -> Purge All --"
	fi
fi

echo "== done =="

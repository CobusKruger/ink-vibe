#!/usr/bin/env node
/**
 * deploy-to-local.mjs — mirror ink-foundation and ink-core from this repo into the
 * Local (Flywheel) site nuwe-ink. Works on macOS and Windows.
 *
 * nuwe-ink.local does NOT read its theme/plugin code from this repo; it runs its
 * own copy, found through Local's registry (tools/lib/local-site.mjs). Changes
 * made here are visible on the site only after this has run.
 *
 * Usage:
 *   node tools/deploy-to-local.mjs            # dry run — shows what WOULD change
 *   node tools/deploy-to-local.mjs --apply    # performs the sync
 *
 * Each folder is mirrored with deletion (like `rsync --delete`): files at the
 * destination that are not in this repo are removed. That is why the dry run is
 * the default, and why files must never be hand-copied into the site's
 * ink-core/migration-data/ (see INK_MIGRATION_DATA_DIR in docs/migration-runbook.md).
 */

import { existsSync } from 'node:fs';
import path from 'node:path';
import { pathToFileURL } from 'node:url';
import { REPO_ROOT, die, findSite, say, wp } from './lib/local-site.mjs';
import { mirror } from './lib/mirror.mjs';

const DIRS = ['wp-content/themes/ink-foundation', 'wp-content/plugins/ink-core'];

export function deploy(site, { apply }) {
  if (!existsSync(path.join(site.webRoot, 'wp-config.php'))) {
    die(`No WordPress install at ${site.webRoot} (wp-config.php missing).`);
  }

  say(apply ? 'APPLY mode: syncing for real' : 'DRY RUN: showing what would change (pass --apply to sync for real)');
  console.log(`  site: ${site.webRoot}`);

  let changes = 0;
  for (const rel of DIRS) {
    const src = path.join(REPO_ROOT, rel);
    if (!existsSync(src)) {
      console.log(`-- skip: ${rel} does not exist in this repo, nothing to sync --`);
      continue;
    }
    console.log(`-- syncing ${rel} --`);
    const r = mirror(src, path.join(site.webRoot, rel), { deleteExtra: true, dryRun: !apply });
    changes += r.copied.length + r.deleted.length;
  }
  console.log(`-- ${changes} change(s) --`);

  if (apply && changes > 0) {
    say('cache purge (best-effort)');
    wp(site, ['cache', 'flush'], { allowFail: true }) || console.log("-- 'wp cache flush' failed, continuing --");
    wp(site, ['litespeed-purge', 'all'], { allowFail: true })
      || console.log('-- LiteSpeed purge failed or not available: wp-admin -> LiteSpeed Cache -> Purge All --');
  }
  return changes;
}

if (import.meta.url === pathToFileURL(process.argv[1]).href) {
  const args = process.argv.slice(2);
  const unknown = args.filter((a) => a !== '--apply');
  if (unknown.length) die(`Unknown argument: ${unknown[0]}`);
  deploy(findSite(), { apply: args.includes('--apply') });
  say('done');
}

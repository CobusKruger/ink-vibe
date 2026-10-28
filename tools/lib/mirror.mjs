/**
 * Copy a folder tree the way `rsync -a [--delete]` did, without needing rsync.
 *
 * A file is copied when the destination lacks it or differs in size or mtime;
 * the copy keeps the source mtime, so an unchanged file is skipped next time.
 * With `deleteExtra`, destination files and folders absent from the source are
 * removed. With `dryRun`, nothing is written; the changes are only reported.
 */

import {
  chmodSync, copyFileSync, existsSync, lstatSync, mkdirSync, readdirSync, rmSync, statSync, utimesSync,
} from 'node:fs';
import path from 'node:path';

const JUNK = (name) => name === '.DS_Store' || name.startsWith('._');

/**
 * @returns {{copied:string[], deleted:string[]}} paths relative to `dest`.
 */
export function mirror(src, dest, { deleteExtra = false, dryRun = false, log = console.log } = {}) {
  const result = { copied: [], deleted: [] };
  walk(src, dest, '', { deleteExtra, dryRun, log }, result);
  return result;
}

function walk(src, dest, rel, opts, result) {
  const srcDir = path.join(src, rel);
  const destDir = path.join(dest, rel);
  const entries = readdirSync(srcDir, { withFileTypes: true }).filter((e) => !JUNK(e.name));
  const names = new Set(entries.map((e) => e.name));

  if (!existsSync(destDir) && !opts.dryRun) mkdirSync(destDir, { recursive: true });
  // In a dry run a file may still sit where this folder belongs (reported by the caller).
  const destIsDir = existsSync(destDir) && lstatSync(destDir).isDirectory();

  if (opts.deleteExtra && destIsDir) {
    for (const d of readdirSync(destDir, { withFileTypes: true })) {
      // Finder recreates .DS_Store at will; leave junk alone, as rsync's exclude did.
      if (names.has(d.name) || JUNK(d.name)) continue;
      const r = path.join(rel, d.name);
      opts.log(`deleting ${r}${d.isDirectory() ? '/' : ''}`);
      result.deleted.push(r);
      if (!opts.dryRun) rmSync(path.join(dest, r), { recursive: true, force: true });
    }
  }

  for (const e of entries) {
    const r = path.join(rel, e.name);
    const s = path.join(src, r);
    const d = path.join(dest, r);

    if (e.isDirectory()) {
      if (existsSync(d) && !lstatSync(d).isDirectory()) {
        opts.log(`deleting ${r} (file where a folder belongs)`);
        result.deleted.push(r);
        if (!opts.dryRun) rmSync(d, { force: true });
      }
      walk(src, dest, r, opts, result);
      continue;
    }
    if (!e.isFile()) {
      opts.log(`skipping ${r} (not a regular file)`);
      continue;
    }

    const ss = statSync(s);
    let same = false;
    if (existsSync(d) || isLink(d)) {
      const ds = lstatSync(d);
      if (ds.isSymbolicLink()) {
        // Never write through a link into whatever it points at.
        if (!opts.dryRun) rmSync(d, { force: true });
      } else if (ds.isDirectory()) {
        opts.log(`deleting ${r}/ (folder where a file belongs)`);
        result.deleted.push(r);
        if (!opts.dryRun) rmSync(d, { recursive: true, force: true });
      } else {
        // Whole seconds: filesystems store mtimes at different precisions.
        same = ds.size === ss.size && Math.floor(ds.mtimeMs / 1000) === Math.floor(ss.mtimeMs / 1000);
      }
    }
    if (same) continue;

    opts.log(r);
    result.copied.push(r);
    if (!opts.dryRun) {
      try {
        copyFileSync(s, d);
      } catch (err) {
        if (err.code !== 'EACCES' && err.code !== 'EPERM') throw err;
        rmSync(d, { force: true }); // read-only destination: replace it, as rsync does
        copyFileSync(s, d);
      }
      chmodSync(d, ss.mode);
      utimesSync(d, ss.atime, ss.mtime);
    }
  }
}

function isLink(p) {
  try {
    return lstatSync(p).isSymbolicLink();
  } catch {
    return false;
  }
}

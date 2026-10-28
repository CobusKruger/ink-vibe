#!/usr/bin/env node
/**
 * local.mjs — run WP-CLI or Local's mysql client against the nuwe-ink site, with
 * the right database connection, on macOS or Windows.
 *
 *   node tools/local.mjs info                       # what was found in Local's registry
 *   node tools/local.mjs wp <args…>                 # e.g. wp ink migrate-all
 *   node tools/local.mjs mysql [args…] [< file.sql] # e.g. mysql < tools/fidelity/create-capture-user.sql
 *   node tools/local.mjs mysqldump [args…] > out.sql
 */

import { spawnSync } from 'node:child_process';
import { die, findSite, mysqlConnArgs, requireConnection, wp } from './lib/local-site.mjs';

const [cmd, ...rest] = process.argv.slice(2);
const site = findSite();

// The site's database is the default: `-D` for mysql (a later -D wins), the trailing
// name for mysqldump unless --databases/--all-databases says otherwise.
function db(binary, isDump) {
  if (!binary) die(`Local's ${site.db.service} ${site.db.version} client not found under lightning-services.`);
  const explicit = rest.some((a) => a === '--databases' || a === '--all-databases' || a === '-B' || a === '-A');
  const args = isDump
    ? [...rest, ...(explicit ? [] : [site.db.name])]
    : ['-D', site.db.name, ...rest];
  const r = spawnSync(binary, [...mysqlConnArgs(site.db), ...args], {
    stdio: 'inherit',
    env: { ...process.env, MYSQL_PWD: site.db.password },
  });
  process.exit(r.status ?? 1);
}

switch (cmd) {
  case 'info':
    console.log(JSON.stringify({ ...site, db: { ...site.db, password: '***' } }, null, 2));
    break;
  case 'wp':
    requireConnection(site);
    process.exit(wp(site, rest, { allowFail: true }) ? 0 : 1);
    break;
  case 'mysql':
    db(site.db.client, false);
    break;
  case 'mysqldump':
    db(site.db.dump, true);
    break;
  default:
    die('usage: node tools/local.mjs <info|wp|mysql|mysqldump> [args…]');
}

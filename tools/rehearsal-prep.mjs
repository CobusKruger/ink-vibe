#!/usr/bin/env node
/**
 * rehearsal-prep.mjs — put a fresh copy of the prod site onto the Local site
 * nuwe-ink, ready for `wp ink migrate-all`. Works on macOS and Windows.
 *
 * Everything up to the prod export itself is automated; the export (a database
 * dump and, optionally, the uploads and plugins folders) is the only thing you
 * bring. Leave --uploads out to keep the uploads already on the site (media added
 * to prod since then will be missing: `wp ink verify-media` shows it). A new
 * Local site has no plugins: pass --plugins (prod's wp-content/plugins).
 *
 * Usage:
 *   node tools/rehearsal-prep.mjs --sql <prod.sql|prod.sql.gz> [--uploads <dir|.zip|.tar.gz>]
 *        [--wp-content <dir>]  (prod's whole wp-content: fills uploads, plugins, mu-plugins, themes)
 *        [--plugins <dir>] [--mu-plugins <dir>] [--themes <dir>] [--mail-sink sink@nuwe-ink.local] [--old-url https://ink.org.za]
 *        [--migration-data <dir>] [--apply]
 *
 * Without --apply it is a DRY RUN: it checks the inputs and the Local site and
 * prints what it would do. With --apply it DESTROYS the current nuwe-ink
 * database (a test copy, replaced on purpose; nothing is kept).
 *
 * What it does, in order:
 *   1. safety checks (the target really is the Local nuwe-ink site)
 *   2. drops and recreates the database, imports the prod dump, runs `wp core update-db`
 *   3. replaces the prod URL with https://<site>.local using `wp search-replace`
 *      (serialization-safe; editing the SQL text would corrupt serialized data).
 *      Only URLs are replaced, never the bare domain, so addresses such as
 *      moderator@ink.org.za in the data are left alone.
 *   4. copies the uploads and plugins in, deploys ink-core + ink-foundation (deploy-to-local.mjs)
 *   5. activates them, deactivates the retired plugins and FluentSMTP (the prod database
 *      arrives with prod's plugin state), sets WP_ENVIRONMENT_TYPE, INK_MAIL_SINK and
 *      INK_MIGRATION_DATA_DIR, flushes rewrites and caches
 *
 * Mail: Local runs Mailpit for this site. With INK_MAIL_SINK set, ink-core
 * rewrites every outgoing message to that address instead of the real one, and
 * Mailpit catches it (open it from the Local app). With no sink set, ink-core
 * sends nothing at all.
 */

import { spawnSync } from 'node:child_process';
import { copyFileSync, existsSync, mkdirSync, mkdtempSync, readdirSync, statSync } from 'node:fs';
import path from 'node:path';
import { deploy } from './deploy-to-local.mjs';
import {
  REPO_ROOT, die, findSite, mysql, mysqlImport, requireConnection, say, systemTar, wp,
} from './lib/local-site.mjs';
import { mirror } from './lib/mirror.mjs';

// Plugins the new site retires (docs/staging-setup-guide.md 4a), plus FluentSMTP, which would send
// through prod's mail server. The prod database has them all active. Deactivated, never deleted.
// `simple-css` matters most: its stored CSS sets the body text to 14px on every page.
const RETIRED_PLUGINS = [
  'wordpress-seo', 'loginizer', 'js_composer', 'qode-framework', 'youzify', 'youzify-frontend-submission',
  'cbxuseronline', 'classic-widgets', 'joinup-core', 'ultimate-social-media-icons', 'woocommerce-legacy-rest-api',
  'wpcustom-category-image', 'wps-bidouille', 'automatic-translator-addon-for-loco-translate', 'document-emberdder',
  'maintenance', 'pdf-embedder', 'string-locator', 'advanced-cron-manager', 'comments-plus', 'report-content',
  'invite-anyone', 'redirect-redirection', 'simple-css', 'fluent-smtp',
];

// --- Arguments -------------------------------------------------------------
const opts = { sql: '', wpContent: '', uploads: '', plugins: '', muPlugins: '', themes: '', oldUrl: '', mailSink: '', migrationData: '', apply: false };
const argv = process.argv.slice(2);
for (let i = 0; i < argv.length; i++) {
  const a = argv[i];
  const value = () => argv[++i] ?? die(`${a} needs a value.`);
  switch (a) {
    case '--sql': opts.sql = value(); break;
    case '--wp-content': opts.wpContent = value(); break;
    case '--uploads': opts.uploads = value(); break;
    case '--plugins': opts.plugins = value(); break;
    case '--mu-plugins': opts.muPlugins = value(); break;
    case '--themes': opts.themes = value(); break;
    case '--old-url': opts.oldUrl = value(); break;
    case '--mail-sink': opts.mailSink = value(); break;
    case '--migration-data': opts.migrationData = value(); break;
    case '--apply': opts.apply = true; break;
    default: die(`Unknown argument: ${a}`);
  }
}

if (!opts.sql || !existsSync(opts.sql) || !statSync(opts.sql).isFile()) die('--sql <file> is required and must exist.');
// --wp-content is a copy of prod's whole wp-content: its folders fill whatever is not given.
if (opts.wpContent) {
  if (!(existsSync(opts.wpContent) && statSync(opts.wpContent).isDirectory())) die(`--wp-content must be a folder: ${opts.wpContent}`);
  for (const [key, name] of [['uploads', 'uploads'], ['plugins', 'plugins'], ['muPlugins', 'mu-plugins'], ['themes', 'themes']]) {
    const dir = path.join(opts.wpContent, name);
    if (!opts[key] && existsSync(dir)) opts[key] = dir;
  }
}
if (opts.uploads && !existsSync(opts.uploads)) die(`--uploads path does not exist: ${opts.uploads}`);
if (opts.plugins && !(existsSync(opts.plugins) && statSync(opts.plugins).isDirectory())) {
  die(`--plugins must be a folder: ${opts.plugins}`);
}

const site = findSite();
const targetUrl = `https://${site.domain}`;
const mailSink = opts.mailSink || `sink@${site.domain}`;
const tmpDir = path.join(REPO_ROOT, 'tmp');

// All migration data lives outside the repo (docs/migration-data.md). Same list as
// Ink\Kernel\MigrationData::FILES; checked now, before the database is touched.
const migrationData = opts.migrationData ? path.resolve(opts.migrationData) : path.resolve(REPO_ROOT, '..', 'migration-data');
for (const file of ['writer-accounts.csv', 'writer-aliases.csv', 'inkpols-uitgawes.csv']) {
  if (!existsSync(path.join(migrationData, file))) die(`Migration data not found: ${path.join(migrationData, file)}. Pass --migration-data <dir>.`);
}

if (!existsSync(path.join(site.webRoot, 'wp-config.php'))) die(`Local site not found at: ${site.webRoot}`);
if (!site.db.client) die(`Local's mysql client (${site.db.service} ${site.db.version}) not found.`);
requireConnection(site);

// --- Safety: this must be the Local copy, never anything pointing at prod. ----
mysql(site, ['-e', 'SELECT 1'], { allowFail: true }) !== null
  || die('Cannot reach the site database. Start the site in the Local app first.');
// WP-CLI must work before anything is dropped: an empty `home` below would otherwise pass.
wp(site, ['core', 'version'], { bare: true, capture: true, allowFail: true }) !== null
  || die('WP-CLI cannot load this site. Check: node tools/local.mjs wp core version');

const currentHome = wp(site, ['option', 'get', 'home'], { bare: true, capture: true, allowFail: true }) ?? '';
if (currentHome && !currentHome.includes(site.domain)) {
  die(`The current site URL is '${currentHome}', not ${site.domain}. Refusing to continue.`);
}
const env = wp(site, ['config', 'get', 'WP_ENVIRONMENT_TYPE'], { bare: true, capture: true, allowFail: true });
if (env === 'production') die('WP_ENVIRONMENT_TYPE is production.');

const conn = site.db.socket ?? `127.0.0.1:${site.db.port}`;
say('Plan');
console.log(`  site:           ${site.webRoot}`);
console.log(`  database:       ${site.db.name} via ${conn}`);
console.log(`  prod dump:      ${opts.sql}`);
console.log(`  uploads:        ${opts.uploads || '(none given: keeping the uploads already on the site)'}`);
console.log(`  plugins:        ${opts.plugins || '(none given: keeping the plugins already on the site)'}`);
console.log(`  mu-plugins:     ${opts.muPlugins || '(none given: keeping what is on the site)'}`);
console.log(`  themes:         ${opts.themes || '(none given: keeping the themes already on the site)'}`);
console.log(`  old URL:        ${opts.oldUrl || '(read from the imported database)'}`);
console.log(`  new URL:        ${targetUrl}`);
console.log(`  mail sink:      ${mailSink}`);
console.log(`  migration data: ${migrationData}`);

if (!opts.apply) {
  say('DRY RUN: nothing was changed. Re-run with --apply to do it.');
  process.exit(0);
}

mkdirSync(tmpDir, { recursive: true });

// --- 1. Database --------------------------------------------------------------
say('1/5 Resetting the database and importing the prod dump');
const dbName = site.db.name.replace(/`/g, '');
mysql(site, ['-e', `DROP DATABASE IF EXISTS \`${dbName}\`; CREATE DATABASE \`${dbName}\` CHARACTER SET utf8mb4;`]);
await mysqlImport(site, dbName, opts.sql).catch((e) => die(e.message));

// A new Local site's wp-config.php says `wp_`; prod uses its own prefix (`wpjj_`). Take it
// from the dump: the prefix whose options and users tables both exist.
let prefix = wp(site, ['config', 'get', 'table_prefix'], { bare: true, capture: true });
const tables = (mysql(site, ['-N', '-e', `SELECT table_name FROM information_schema.tables WHERE table_schema='${dbName}'`]) ?? '')
  .split(/\r?\n/).filter(Boolean);
if (!tables.includes(`${prefix}options`)) {
  const candidates = tables.filter((t) => t.endsWith('options')).map((t) => t.slice(0, -'options'.length))
    .filter((p) => p && tables.includes(`${p}users`));
  if (candidates.length !== 1) {
    die(`Imported database has no '${prefix}options' table, and the prefix is ambiguous (${candidates.join(', ') || 'none found'}). Set $table_prefix in wp-config.php.`);
  }
  console.log(`  table prefix: ${prefix} -> ${candidates[0]} (from the dump)`);
  prefix = candidates[0];
  wp(site, ['config', 'set', 'table_prefix', prefix, '--type=variable', '--quiet'], { bare: true });
}

// Prod may run an older WordPress than the Local site; bring its schema forward.
wp(site, ['core', 'update-db'], { bare: true });

// --- 2. URLs --------------------------------------------------------------------
say(`2/5 Pointing the site at ${targetUrl}`);
let oldUrl = opts.oldUrl;
if (!oldUrl) {
  oldUrl = (mysql(site, ['-N', '-e', `SELECT option_value FROM \`${dbName}\`.\`${prefix}options\` WHERE option_name='home'`]) ?? '').trim();
}
const oldHost = oldUrl.replace(/^[a-z]+:\/\//i, '').split('/')[0];
if (!oldHost) die('Could not work out the old site URL. Pass --old-url.');
console.log(`  replacing URLs on host: ${oldHost}`);
const bareHost = oldHost.replace(/^www\./, '');
for (const from of [`https://${oldHost}`, `http://${oldHost}`, `https://www.${bareHost}`, `http://www.${bareHost}`]) {
  wp(site, ['search-replace', from, targetUrl, '--all-tables', '--skip-columns=guid', '--quiet'], { bare: true, allowFail: true });
}

// --- 3. Uploads -----------------------------------------------------------------
say('3/5 Copying the uploads and plugins');
const uploadsDest = path.join(site.webRoot, 'wp-content', 'uploads');
mkdirSync(uploadsDest, { recursive: true });
if (!opts.uploads) {
  console.log('  skipped: keeping the existing uploads');
} else {
  let from = opts.uploads;
  if (!statSync(from).isDirectory()) {
    if (!/\.(zip|tar\.gz|tgz)$/i.test(from)) die(`Unsupported uploads archive: ${from}`);
    const extract = mkdtempSync(path.join(tmpDir, 'uploads-'));
    const r = spawnSync(systemTar(), ['-xf', from, '-C', extract], { stdio: 'inherit' });
    if (r.status !== 0) die(`Could not extract ${from}`);
    from = extract;
    // An archive may contain the folder itself ("uploads/") or its content.
    if (existsSync(path.join(from, 'uploads'))) from = path.join(from, 'uploads');
    if (existsSync(path.join(from, 'wp-content', 'uploads'))) from = path.join(from, 'wp-content', 'uploads');
  }
  const r = mirror(from, uploadsDest, { log: () => {} });
  console.log(`  ${r.copied.length} file(s) copied`);
}

// Prod's code folders. ink-core and ink-foundation come from this repo (step 4), so they are
// skipped. Each folder is mirrored; anything already on the site and not in the source stays.
function copyCode(label, srcDir, destName) {
  if (!srcDir) return;
  console.log(`  ${label}:`);
  const dest = path.join(site.webRoot, 'wp-content', destName);
  mkdirSync(dest, { recursive: true });
  for (const e of readdirSync(srcDir, { withFileTypes: true })) {
    if (['ink-core', 'ink-foundation', 'index.php'].includes(e.name) || e.name.startsWith('.')) continue;
    const from = path.join(srcDir, e.name);
    const to = path.join(dest, e.name);
    if (e.isDirectory()) {
      const r = mirror(from, to, { deleteExtra: true, log: () => {} });
      console.log(`    ${e.name}: ${r.copied.length} copied, ${r.deleted.length} removed`);
    } else if (e.isFile() && e.name.endsWith('.php')) {
      copyFileSync(from, to);
      console.log(`    ${e.name}`);
    }
  }
}
copyCode('plugins', opts.plugins, 'plugins');
copyCode('mu-plugins', opts.muPlugins, 'mu-plugins');
copyCode('themes', opts.themes, 'themes');

// --- 4. Code --------------------------------------------------------------------
say("4/5 Deploying this repo's code to the site");
deploy(site, { apply: true });

// --- 5. Activate and configure ----------------------------------------------------
say('5/5 Activating, constants, rewrite rules');
wp(site, ['plugin', 'activate', 'ink-core'], { bare: true });
wp(site, ['theme', 'activate', 'ink-foundation'], { bare: true });
const activeNames = (wp(site, ['plugin', 'list', '--status=active', '--field=name'], { bare: true, capture: true, allowFail: true }) ?? '')
  .split('\n').map((name) => name.trim());
const retired = RETIRED_PLUGINS.filter((name) => activeNames.includes(name));
if (retired.length > 0) {
  wp(site, ['plugin', 'deactivate', ...retired], { bare: true, allowFail: true });
  console.log(`  deactivated ${retired.length} retired plugin(s): ${retired.join(', ')}`);
}
wp(site, ['config', 'set', 'WP_ENVIRONMENT_TYPE', 'local', '--type=constant', '--quiet'], { bare: true });
wp(site, ['config', 'set', 'INK_MAIL_SINK', mailSink, '--type=constant', '--quiet'], { bare: true });
wp(site, ['config', 'set', 'INK_MIGRATION_DATA_DIR', migrationData.split(path.sep).join('/'), '--type=constant', '--quiet'], { bare: true });
wp(site, ['rewrite', 'flush', '--quiet'], { bare: true, allowFail: true });
wp(site, ['cache', 'flush', '--quiet'], { bare: true, allowFail: true });

say('Done. Checks');
const get = (args) => wp(site, args, { bare: true, capture: true, allowFail: true }) ?? '?';
console.log(`  home:        ${get(['option', 'get', 'home'])}`);
console.log(`  environment: ${get(['eval', 'echo wp_get_environment_type();'])}`);
console.log(`  ink-core:    ${get(['plugin', 'get', 'ink-core', '--field=status'])}`);
console.log(`  theme:       ${get(['theme', 'get', 'ink-foundation', '--field=status'])}`);
const stillActive = get(['plugin', 'list', '--status=active', '--field=name']).split('\n').map((name) => name.trim());
console.log(`  retired plugins still active: ${RETIRED_PLUGINS.filter((name) => stillActive.includes(name)).join(', ') || 'none'}`);
console.log(`  posts:       ${get(['eval', 'global $wpdb; echo $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_status=\'publish\'");'])}`);
console.log();
console.log(`Next: log in at ${targetUrl}/wp-admin/, then run: node tools/local.mjs wp ink migrate-all`);

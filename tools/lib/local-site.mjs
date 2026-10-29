/**
 * The Local (Flywheel) site nuwe-ink, found the same way on macOS and Windows.
 *
 * Nothing machine-specific is hardcoded. Local's own registry (`sites.json`) gives
 * the site's folder, database credentials, MySQL port and service version, and
 * Local's bundled `mysql` client sits under `lightning-services/`. WP-CLI runs on
 * the system PHP with the site's port (and, on macOS, its socket) passed in, because
 * wp-config.php says `DB_HOST = 'localhost'` and the default port/socket is wrong.
 *
 * Per-machine overrides (all optional) go in `tools/local.config.json` (gitignored):
 *   { "site": "nuwe-ink", "localHome": "…", "php": "…", "wpCli": "…/wp-cli.phar" }
 */

import { spawn, spawnSync } from 'node:child_process';
import { createReadStream, existsSync, readdirSync, readFileSync, realpathSync, statSync } from 'node:fs';
import os from 'node:os';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import { createGunzip } from 'node:zlib';

export const REPO_ROOT = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..', '..');
export const IS_WINDOWS = process.platform === 'win32';

export function die(msg) {
  console.error(`ERROR: ${msg}`);
  process.exit(1);
}

export function say(msg) {
  console.log(`== ${msg} ==`);
}

export function loadConfig() {
  const file = path.join(REPO_ROOT, 'tools', 'local.config.json');
  if (!existsSync(file)) return {};
  try {
    return JSON.parse(readFileSync(file, 'utf8'));
  } catch (e) {
    die(`${file} is not valid JSON: ${e.message}`);
  }
}

function expandHome(p) {
  return p.startsWith('~') ? path.join(os.homedir(), p.slice(1)) : p;
}

/** Where the Local app keeps its data. */
export function localHome(config = loadConfig()) {
  if (config.localHome) return expandHome(config.localHome);
  if (process.platform === 'darwin') return path.join(os.homedir(), 'Library', 'Application Support', 'Local');
  if (IS_WINDOWS) return path.join(process.env.APPDATA ?? path.join(os.homedir(), 'AppData', 'Roaming'), 'Local');
  return path.join(os.homedir(), '.config', 'Local');
}

/** Find an executable on PATH (no shell). */
export function which(name) {
  const exts = IS_WINDOWS ? (process.env.PATHEXT ?? '.EXE;.BAT;.CMD').split(';').map((e) => e.toLowerCase()) : [''];
  for (const dir of (process.env.PATH ?? '').split(path.delimiter)) {
    if (!dir) continue;
    // Windows: PATHEXT only, so an extensionless Git Bash shim (e.g. ~/bin/wp) is not picked.
    for (const ext of exts) {
      const candidate = path.join(dir, name + ext);
      try {
        if (statSync(candidate).isFile()) return candidate;
      } catch {
        // not here
      }
    }
  }
  return null;
}

/**
 * The site's registry entry and everything derived from it.
 *
 * @returns {{id:string, name:string, domain:string, root:string, webRoot:string,
 *   db:{name:string,user:string,password:string,port:number|null,socket:string|null,
 *   service:string, version:string, client:string|null, dump:string|null}}}
 */
export function findSite(config = loadConfig()) {
  const want = config.site ?? 'nuwe-ink';
  const home = localHome(config);
  const registry = path.join(home, 'sites.json');

  if (!existsSync(registry)) die(`Local's registry not found at ${registry}. Is Local installed?`);

  const sites = JSON.parse(readFileSync(registry, 'utf8'));
  const entry = Object.entries(sites).find(([, s]) => s.name === want || s.domain === `${want}.local`);

  if (!entry) {
    const names = Object.values(sites).map((s) => s.name).join(', ') || '(none)';
    die(`No Local site named "${want}" in ${registry}. Sites: ${names}. Create it in Local, or set "site" in tools/local.config.json.`);
  }

  const [id, s] = entry;
  const root = path.resolve(expandHome(s.path));
  const webRoot = path.join(root, 'app', 'public');

  const serviceName = s.services?.mysql ? 'mysql' : s.services?.mariadb ? 'mariadb' : 'mysql';
  const service = s.services?.[serviceName] ?? {};
  const port = service.ports?.MYSQL?.[0] ?? service.ports?.MARIADB?.[0] ?? null;
  const socket = IS_WINDOWS ? null : path.join(home, 'run', id, 'mysql', 'mysqld.sock');

  const bins = findDbBinaries(home, serviceName, service.version ?? '');

  return {
    id,
    name: s.name,
    domain: s.domain,
    root,
    webRoot,
    db: {
      name: s.mysql?.database ?? 'local',
      user: s.mysql?.user ?? 'root',
      password: s.mysql?.password ?? 'root',
      port: port === null ? null : Number(port),
      socket: socket && existsSync(socket) ? socket : null,
      service: serviceName,
      version: service.version ?? '',
      client: bins.client,
      dump: bins.dump,
    },
  };
}

/** Local's bundled client: lightning-services/<service>-<version>*\/bin/<platform>/bin/mysql[.exe]. */
function findDbBinaries(home, service, version) {
  const base = path.join(home, 'lightning-services');
  const exe = IS_WINDOWS ? '.exe' : '';
  const found = { client: null, dump: null };

  if (!existsSync(base)) return found;

  if (!version) return found;
  // Folders are `<service>-<version>+<build>`; match the version exactly.
  const dirs = readdirSync(base).filter((d) => d === `${service}-${version}` || d.startsWith(`${service}-${version}+`)).sort().reverse();
  for (const d of dirs) {
    const binRoot = path.join(base, d, 'bin');
    if (!existsSync(binRoot)) continue;
    // Prefer this machine's architecture folder; Local ships e.g. darwin, darwin-arm64, win64, linux.
    const platforms = readdirSync(binRoot).sort((a, b) => platformScore(b) - platformScore(a));
    for (const p of platforms) {
      const client = path.join(binRoot, p, 'bin', `mysql${exe}`);
      if (existsSync(client)) {
        found.client = client;
        const dump = path.join(binRoot, p, 'bin', `mysqldump${exe}`);
        found.dump = existsSync(dump) ? dump : null;
        return found;
      }
    }
  }
  return found;
}

function platformScore(dir) {
  const d = dir.toLowerCase();
  if (IS_WINDOWS) return d === 'win64' ? 3 : d.startsWith('win') ? 2 : 0;
  if (process.platform === 'darwin') {
    const arm = process.arch === 'arm64';
    if (d === (arm ? 'darwin-arm64' : 'darwin')) return 3;
    return d.startsWith('darwin') ? 2 : 0;
  }
  return d.startsWith('linux') ? 2 : 0;
}

/**
 * Fail early when PHP cannot reach the database: on macOS/Linux `DB_HOST = 'localhost'`
 * makes mysqli use a socket and ignore the port, so the socket must exist.
 */
export function requireConnection(site) {
  if (!IS_WINDOWS && !site.db.socket) {
    die(`MySQL socket not found at ${path.join(localHome(), 'run', site.id, 'mysql', 'mysqld.sock')}. Start the site in the Local app first.`);
  }
  if (IS_WINDOWS && !site.db.port) die('No MySQL port for this site in Local\'s sites.json. Start the site in the Local app first.');
}

/** Connection flags for Local's mysql client: the socket on macOS, else TCP. */
export function mysqlConnArgs(db) {
  const args = [`-u${db.user}`];
  if (db.socket) args.push(`--socket=${db.socket}`);
  else if (db.port) args.push('--protocol=TCP', '--host=127.0.0.1', `--port=${db.port}`);
  else die('Neither a MySQL socket nor a port is known for this site. Start the site in Local first.');
  return args;
}

function mysqlEnv(db) {
  return { ...process.env, MYSQL_PWD: db.password };
}

/** Run Local's mysql client. Returns stdout. `input` (string) goes to stdin. */
export function mysql(site, args, { input, allowFail = false } = {}) {
  if (!site.db.client) die(`Local's mysql client (${site.db.service} ${site.db.version}) not found under lightning-services.`);
  const r = spawnSync(site.db.client, [...mysqlConnArgs(site.db), ...args], {
    env: mysqlEnv(site.db),
    input,
    encoding: 'utf8',
    maxBuffer: 256 * 1024 * 1024,
  });
  if (r.status !== 0 && !allowFail) die(`mysql failed: ${(r.stderr || r.error?.message || '').trim()}`);
  return r.status === 0 ? r.stdout : null;
}

/** Stream a .sql or .sql.gz file into a database. */
export function mysqlImport(site, database, file) {
  if (!site.db.client) die(`Local's mysql client (${site.db.service} ${site.db.version}) not found under lightning-services.`);
  return new Promise((resolve, reject) => {
    const child = spawn(site.db.client, [...mysqlConnArgs(site.db), database], {
      env: mysqlEnv(site.db),
      stdio: ['pipe', 'inherit', 'inherit'],
    });
    let source = createReadStream(file);
    if (file.endsWith('.gz')) source = source.pipe(createGunzip());
    source.on('error', reject);
    child.stdin.on('error', reject);
    source.pipe(child.stdin);
    child.on('error', reject);
    child.on('close', (code) => (code === 0 ? resolve() : reject(new Error(`mysql import exited ${code}`))));
  });
}

/** The PHP binary and the WP-CLI phar to run it with. */
export function wpCli(config = loadConfig()) {
  const php = config.php ? expandHome(config.php) : which('php');
  if (!php) die('php not found on PATH (or set "php" in tools/local.config.json).');

  let phar = config.wpCli ? expandHome(config.wpCli) : null;
  if (!phar) {
    const wp = which('wp');
    if (!wp) die('wp (WP-CLI) not found on PATH (or set "wpCli" in tools/local.config.json).');
    // macOS/Linux: `wp` is the phar itself. Windows: a wp.bat shim next to wp-cli.phar.
    phar = /\.(bat|cmd)$/i.test(wp) ? path.join(path.dirname(wp), 'wp-cli.phar') : realpathSync(wp);
    if (!existsSync(phar)) die(`Found ${wp} but not the phar it wraps (${phar}). Set "wpCli" in tools/local.config.json.`);
  }
  // A shell wrapper (e.g. Composer's vendor/bin/wp) is not something PHP can run.
  const head = readFileSync(phar).subarray(0, 64).toString('latin1');
  if (head.startsWith('#!') && !/php/.test(head.split('\n')[0])) {
    die(`${phar} is a shell wrapper, not the WP-CLI phar. Set "wpCli" in tools/local.config.json.`);
  }
  return { php, phar };
}

function phpDbArgs(db) {
  const args = [];
  if (db.port) args.push('-d', `mysqli.default_port=${db.port}`, '-d', `pdo_mysql.default_port=${db.port}`);
  if (db.socket) args.push('-d', `mysqli.default_socket=${db.socket}`, '-d', `pdo_mysql.default_socket=${db.socket}`);
  return args;
}

/**
 * Run WP-CLI against the site. `bare` adds --skip-plugins --skip-themes (for work on a
 * database that is mid-import). Returns stdout when `capture`, else inherits stdio.
 */
export function wp(site, args, { bare = false, capture = false, allowFail = false, config = loadConfig() } = {}) {
  const { php, phar } = wpCli(config);
  const argv = [
    '-d', 'error_reporting=0',
    ...phpDbArgs(site.db),
    phar,
    `--path=${site.webRoot}`,
    ...(bare ? ['--skip-plugins', '--skip-themes'] : []),
    ...args,
  ];
  const r = spawnSync(php, argv, {
    stdio: capture ? ['ignore', 'pipe', 'pipe'] : 'inherit',
    encoding: 'utf8',
    maxBuffer: 256 * 1024 * 1024,
  });
  if (r.status !== 0 && !allowFail) {
    die(`wp ${args.join(' ')} failed${capture ? `: ${(r.stderr || '').trim()}` : ''}`);
  }
  if (!capture) return r.status === 0;
  return r.status === 0 ? r.stdout.trim() : null;
}

/** The system `tar` (bsdtar on macOS and Windows 10+, which also reads .zip; GNU tar does not). */
export function systemTar() {
  const t = IS_WINDOWS
    ? path.join(process.env.SystemRoot ?? 'C:\\Windows', 'System32', 'tar.exe')
    : process.platform === 'darwin' ? '/usr/bin/tar' : null;
  if (t && existsSync(t)) return t;
  return which('tar') ?? die('tar not found.');
}

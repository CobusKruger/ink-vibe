/**
 * Shared by gates.mjs and determinism.mjs: configuration, lookups, sessions, process helpers.
 * Cross-platform (macOS + Windows): no machine paths. Run through tools/fidelity/run.sh node ..., which supplies
 * Node 20+, PLAYWRIGHT_BROWSERS_PATH and NODE_PATH (the .sh entry points do that).
 */
import { readFileSync, writeFileSync, existsSync, mkdirSync } from 'node:fs';
import { spawnSync } from 'node:child_process';
import https from 'node:https';
import http from 'node:http';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

export const REPO = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..', '..', '..');
process.chdir(REPO);

const readJson = (f) => JSON.parse(readFileSync(f, 'utf8').replace(/^﻿/, ''));
export const gatesFile = readJson(path.join(REPO, 'tools/fidelity/accept/gates.json'));
const targets = readJson(path.join(REPO, 'tools/fidelity/targets.json'));
const localCfgFile = path.join(REPO, 'tools/local.config.json');
const localCfg = existsSync(localCfgFile) ? readJson(localCfgFile) : {};

const trimSlash = (u) => u.replace(/\/+$/, '');
/** INK base: INK_URL env, else targets.json bases.ink. */
export const INK = trimSlash(process.env.INK_URL || targets.bases.ink);
/** Lovable preview: LOVABLE_URL env, else `lovablePreview` in tools/local.config.json, else targets.json bases.lovable. */
export const LOVABLE = trimSlash(process.env.LOVABLE_URL || localCfg.lovablePreview || targets.bases.lovable);
/** The Lovable repo (awards slug, Supabase project id): `lovableRepo` in tools/local.config.json, else the sibling folder. */
export const LOVABLE_REPO = path.resolve(REPO, localCfg.lovableRepo || '../verse-haven');
export const CAPTURE_LOGIN = 'fidelity-capture';
export const INK_AUTH = 'tmp/fidelity-auth/ink.json';
export const LOVABLE_AUTH = 'tmp/fidelity-auth/lovable.json';

export const log = (...a) => console.log(...a);
export const die = (msg) => { console.error(`error: ${msg}`); process.exit(2); };

export function mapPath(m) {
  const k = m.split('/')[0];
  const base = gatesFile.maps[k];
  return base ? `${base}/${m.slice(k.length + 1)}` : m;
}

export function nodeEnv() {
  return {
    ...process.env,
    PLAYWRIGHT_BROWSERS_PATH: process.env.PLAYWRIGHT_BROWSERS_PATH || path.join(REPO, 'tmp', 'ms-playwright'),
    NODE_PATH: process.env.NODE_PATH || path.join(REPO, 'node_modules'),
  };
}

/** Run a node script (tools/fidelity/*.mjs, tools/local.mjs) with the harness environment. */
export function runNode(script, args, opts = {}) {
  const r = spawnSync(process.execPath, [script, ...args], { cwd: REPO, env: nodeEnv(), encoding: 'utf8', maxBuffer: 256 * 1024 * 1024, ...opts });
  return { status: r.status ?? 1, out: `${r.stdout || ''}${r.stderr || ''}`, stdout: r.stdout || '', stderr: r.stderr || '' };
}

/** `node tools/local.mjs wp ...`: returns trimmed stdout, or throws. */
export function wp(args) {
  const r = runNode('tools/local.mjs', ['wp', ...args]);
  if (r.status !== 0) throw new Error(`wp ${args.join(' ')} failed:\n${r.out.slice(-600)}`);
  return r.stdout.replace(/\r/g, '').trim();
}

export function httpStatus(url) {
  return new Promise((resolve, reject) => {
    const mod = url.startsWith('https') ? https : http;
    const req = mod.get(url, { rejectUnauthorized: false, timeout: 30000 }, (res) => { res.resume(); resolve(res.statusCode); });
    req.on('timeout', () => req.destroy(new Error('timeout')));
    req.on('error', reject);
  });
}

export function httpText(url) {
  return new Promise((resolve, reject) => {
    const mod = url.startsWith('https') ? https : http;
    mod.get(url, { rejectUnauthorized: false, timeout: 30000 }, (res) => {
      let b = ''; res.setEncoding('utf8'); res.on('data', (c) => { b += c; }); res.on('end', () => resolve({ status: res.statusCode, body: b }));
    }).on('error', reject);
  });
}

/** Resolve every lookup named in `needed` (a Set of names) to a slug. Looked up on the running site, never hardcoded. */
export async function resolveLookups(needed) {
  const out = {};
  const defs = gatesFile.lookups;
  let issues = null;
  for (const name of needed) {
    const d = defs[name];
    if (!d) die(`gates.json: unknown lookup {${name}}`);
    if (d.type === 'round') {
      const slugs = wp(['post', 'list', '--post_type=uitdaging', '--post_status=publish', '--orderby=post_date', '--order=DESC', '--posts_per_page=500', '--field=post_name']).split('\n').filter(Boolean);
      const cands = slugs.filter((s) => s.startsWith(d.prefix));
      let found = null;
      for (const s of cands) {
        const code = await httpStatus(`${INK}/uitdaging/${s}/uitslae/`);
        if (code === d.uitslae) { found = s; break; }
      }
      if (!found) die(`lookup {${name}}: no published round starting "${d.prefix}" whose /uitslae/ answers ${d.uitslae} (candidates: ${cands.join(', ') || 'none'})`);
      out[name] = found;
    } else if (d.type === 'issue') {
      issues ??= wp(['post', 'list', '--post_type=inkpols_uitgawe', '--post_status=publish', '--orderby=post_date', '--order=DESC', '--posts_per_page=100', '--field=post_name']).split('\n').filter(Boolean);
      const s = issues.at(d.index);
      if (!s) die(`lookup {${name}}: no InkPols issue at index ${d.index} (${issues.length} published)`);
      out[name] = s;
    } else if (d.type === 'award') {
      const f = path.join(LOVABLE_REPO, 'src/data/awards.ts');
      if (!existsSync(f)) die(`lookup {award}: ${f} not found (set "lovableRepo" in tools/local.config.json)`);
      const m = readFileSync(f, 'utf8').match(/slug:\s*"([^"]+)"/);
      if (!m) die(`lookup {award}: no slug in ${f}`);
      out[name] = m[1];
    } else die(`gates.json: lookup ${name} has unknown type ${d.type}`);
  }
  return out;
}

export const neededLookups = (gates) => {
  const s = new Set();
  for (const g of gates) for (const u of [g.lovable, g.ink]) for (const m of u.matchAll(/\{(\w+)\}/g)) s.add(m[1]);
  return s;
};
export const fill = (p, lk) => p.replace(/\{(\w+)\}/g, (_, n) => lk[n]);

/** One entry per capture/diff unit: gate x auth x state, with its names resolved. */
export function expand(gates, lk) {
  const units = [];
  for (const g of gates) {
    const states = g.states || [{ name: null }];
    const auths = g.auth === 'both' ? [false, true] : [false];
    for (const auth of auths) for (const st of states) {
      const vps = st.viewports || gatesFile.viewports;
      const scope = g.scope || 'main', scopeLabel = g.scopeLabel || 'main';
      const stem = (v) => `${g.page}@${v}${auth ? '-auth' : ''}${st.name ? `-${st.name}` : ''}-scope-${scopeLabel}`;
      const disp = (v) => `${g.display || g.page}${auth ? '-auth' : ''}${st.name ? `-${st.name}` : ''}@${v}`;
      units.push({ g, auth, state: st.name, click: st.click || [], vps, scope, scopeLabel, stem, disp, map: mapPath(g.map), lovableUrl: LOVABLE + fill(g.lovable, lk), inkUrl: INK + fill(g.ink, lk) });
    }
  }
  return units;
}

/** Sign the capture user in (no data change) and write the Lovable fake-session file for the current preview origin. */
export function ensureSessions() {
  let id = '';
  try { id = wp(['user', 'get', CAPTURE_LOGIN, '--field=ID']); } catch { /* handled below */ }
  if (!/^\d+$/.test(id)) die(`the capture user "${CAPTURE_LOGIN}" does not exist: recreate it (docs/fidelity-remediation/README.md §9): node tools/local.mjs mysql -e "source tools/fidelity/create-capture-user.sql", then node tools/local.mjs wp eval-file tools/fidelity/set-capture-password.php`);
  const pw = readFileSync(path.join(REPO, 'tools/fidelity/set-capture-password.php'), 'utf8').match(/\$pass\s*=\s*'([^']+)'/)?.[1];
  if (!pw) die('cannot read the capture password from tools/fidelity/set-capture-password.php');
  mkdirSync(path.join(REPO, 'tmp/fidelity-auth'), { recursive: true });
  const r = runNode('tools/fidelity/login.mjs', ['--side', 'ink', '--url', `${INK}/wp-login.php`, '--user', CAPTURE_LOGIN, '--pass', pw, '--out', INK_AUTH]);
  if (r.status !== 0) die(`capture login failed:\n${r.out.slice(-800)}`);
  // Lovable: a fake Supabase session in localStorage (no backend call), keyed by the project id the build was made with.
  const envFile = path.join(LOVABLE_REPO, '.env');
  const pid = existsSync(envFile) ? readFileSync(envFile, 'utf8').match(/VITE_SUPABASE_PROJECT_ID="?([a-z0-9]+)/)?.[1] : null;
  if (!pid) die(`cannot read VITE_SUPABASE_PROJECT_ID from ${envFile} (set "lovableRepo" in tools/local.config.json)`);
  const session = { access_token: 'x.eyJleHAiOjQxMDI0NDQ4MDAsInN1YiI6IjAwMDAwMDAwLTAwMDAtMDAwMC0wMDAwLTAwMDAwMDAwMDAwMCJ9.x', refresh_token: 'x', token_type: 'bearer', expires_in: 99999999, expires_at: 4102444800, user: { id: '00000000-0000-0000-0000-000000000000', aud: 'authenticated', role: 'authenticated', email: 'fixture@example.test', app_metadata: {}, user_metadata: {}, created_at: '2026-01-01T00:00:00Z' } };
  const origin = new URL(LOVABLE).origin;
  writeFileSync(path.join(REPO, LOVABLE_AUTH), JSON.stringify({ cookies: [], origins: [{ origin, localStorage: [{ name: `sb-${pid}-auth-token`, value: JSON.stringify(session) }] }] }));
}

/** The Lovable preview must be a build that carries the audit-ids the gates use. Returns the bundle name (a build id). */
export async function lovableBuild() {
  let page;
  try { page = await httpText(`${LOVABLE}/`); } catch (e) { die(`the Lovable preview ${LOVABLE} is not reachable (${e.message}). Build and serve it: cd ../verse-haven && npx vite build && npx vite preview --port <port> --strictPort; then set LOVABLE_URL or "lovablePreview" in tools/local.config.json`); }
  const js = page.body.match(/\/assets\/index-[\w-]+\.js/)?.[0];
  if (!js) die(`${LOVABLE}/ does not look like the Lovable build (no /assets/index-*.js)`);
  const bundle = await httpText(`${LOVABLE}${js}`);
  const missing = gatesFile.preflight.lovableBundleMustContain.filter((s) => !bundle.body.includes(s));
  if (missing.length) die(`the Lovable build at ${LOVABLE} lacks audit-ids the gates use (${missing.join(', ')}): rebuild verse-haven at the delta commit (docs/fidelity-remediation/README.md §9)`);
  return js.split('/').pop();
}

/**
 * Shared by capture.mjs, verify.mjs and diff.mjs: argument parsing, the auth-session
 * guard (T4), driving a page into a named state with scripted clicks (T4), the capture
 * scope (T7) and the duplicate-id check (T5).
 */

import { readFileSync, existsSync } from 'node:fs';

const LOGIN_HINT =
  'Refresh the session (no data change):\n' +
  '  tools/fidelity/run.sh login --side ink --url https://nuwe-ink.local/wp-login.php \\\n' +
  '                              --user fidelity-capture --pass <password>';

export class AuthError extends Error {}

/**
 * `--flag value` pairs. Flags listed in `repeatable` collect into arrays (in order);
 * flags listed in `boolean` take no value.
 */
export function parseFlags(argv, { repeatable = [], boolean = [] } = {}) {
  const a = {};
  for (const r of repeatable) a[r] = [];
  for (let i = 2; i < argv.length; i += 1) {
    if (!argv[i].startsWith('--')) throw new Error(`Expected a flag at "${argv[i]}"`);
    const k = argv[i].slice(2);
    if (boolean.includes(k)) { a[k] = true; continue; }
    const v = argv[i + 1];
    if (v === undefined) throw new Error(`Missing value for --${k}`);
    i += 1;
    if (repeatable.includes(k)) a[k].push(v);
    else a[k] = v;
  }
  return a;
}

/** `--state` names go into a filename. */
export function checkStateName(state, clicks) {
  if (clicks.length && !state) {
    throw new Error('--click needs --state <name>, so the clicked capture does not overwrite the default-state file');
  }
  if (state && !/^[a-z0-9][a-z0-9-]*$/.test(state)) {
    throw new Error(`--state "${state}" must be lower-case letters, digits and hyphens`);
  }
}

/**
 * `--scope <css>` records only the first matching element's subtree (T7); its
 * `--scope-label` goes into the filename, so it is required and filename-safe.
 */
export function checkScope(scope, label) {
  if (scope && !label) {
    throw new Error('--scope needs --scope-label <name>, so the scoped capture does not overwrite the whole-page file');
  }
  if (label && !scope) throw new Error('--scope-label given without --scope');
  if (label && !/^[a-z0-9][a-z0-9-]*$/.test(label)) {
    throw new Error(`--scope-label "${label}" must be lower-case letters, digits and hyphens`);
  }
}

/** `<page>@<vp>[-auth][-<state>][-scope-<label>]` — shared by capture and the gate. */
export function captureName({ page, viewport, auth, state, scopeLabel }) {
  return `${page}@${viewport}${auth ? '-auth' : ''}${state ? `-${state}` : ''}${scopeLabel ? `-scope-${scopeLabel}` : ''}`;
}

/**
 * Every audit-id that occurs on more than one record, with the paths it occurs at (T5).
 * diff.mjs joins on audit-id, so a repeated id would silently pair one arbitrary instance.
 */
export function duplicateIds(recs) {
  const seen = new Map();
  for (const r of recs) {
    if (!r.a) continue;
    if (!seen.has(r.a)) seen.set(r.a, []);
    seen.get(r.a).push(r.p);
  }
  return [...seen].filter(([, paths]) => paths.length > 1);
}

/** Fail before launching a browser if the storageState file is missing or its WP session cookie has lapsed. */
export function preflightAuth(authFile) {
  if (!authFile) return;
  if (!existsSync(authFile)) throw new AuthError(`AUTH FILE MISSING — ${authFile}\n${LOGIN_HINT}`);
  const st = JSON.parse(readFileSync(authFile, 'utf8'));
  const wp = (st.cookies ?? []).filter((c) => c.name.startsWith('wordpress_logged_in'));
  const now = Date.now() / 1000;
  if (wp.length && wp.every((c) => c.expires > 0 && c.expires < now)) {
    throw new AuthError(`AUTH EXPIRED — the wordpress_logged_in cookie in ${authFile} has expired.\n${LOGIN_HINT}`);
  }
}

/**
 * After navigation with --auth: prove the page actually rendered logged in. WordPress
 * puts `logged-in` on <body> for an authenticated request — a server-side check that
 * also catches a session revoked before its cookie expiry. The Lovable side has no
 * auth profile (harness-worklist D7), so there is nothing to check there.
 */
export async function assertLoggedIn(page, side, authFile) {
  if (!authFile || side !== 'ink') return;
  const ok = await page.evaluate(() => document.body?.classList.contains('logged-in') ?? false);
  if (!ok) {
    throw new AuthError(
      `AUTH EXPIRED — ${authFile} did not produce a logged-in page at ${page.url()} ` +
      `(no "logged-in" body class). Refusing to capture a logged-out page.\n${LOGIN_HINT}`,
    );
  }
}

/** Apply each click in order, letting the page settle after each. */
export async function driveState(page, clicks) {
  for (const sel of clicks) {
    const loc = page.locator(sel);
    if ((await loc.count()) === 0) throw new Error(`--click selector matched nothing: ${sel}`);
    await loc.first().click();
    await page.waitForLoadState('networkidle').catch(() => {});
    await page.evaluate(() => new Promise((r) => requestAnimationFrame(() => requestAnimationFrame(r))));
    await page.waitForTimeout(200);
  }
}

/** Print an AuthError cleanly and exit non-zero; rethrow anything else. */
export function exitOnAuthError(err) {
  if (err instanceof AuthError) {
    console.error(`\n${err.message}\n`);
    process.exit(3);
  }
  throw err;
}

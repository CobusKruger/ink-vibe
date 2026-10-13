/**
 * E11 — the self-verification sampler.
 *
 * README.md §8.4 forbids shipping a Phase 4 finding unless the generator's own
 * numbers reproduce. This re-navigates to the captured page, re-reads a random
 * sample of nodes live, and compares against what the capture recorded.
 *
 * If this fails, the capture is wrong and every finding derived from it is void.
 *
 * Usage:
 *   verify.mjs --capture <file.ndjson> [--sample 25]
 *              [--auth <storageState.json>] [--click <selector> … --state <name>]
 *
 * A capture taken with --auth must be verified with --auth (it fails otherwise).
 * A capture taken with --click/--state records them in its _meta; verify re-drives
 * that state before its fresh reads. Passing --click/--state here overrides the
 * recorded ones (they must agree with _meta.state).
 * A capture taken with --scope (T7) records the selector and the scope root's path;
 * verify re-resolves the selector live and fails if it no longer lands on that same
 * element, so the sampled paths are known to sit inside the same scope. --scope /
 * --scope-label may be passed but must agree with _meta.
 */

import { readFile } from 'node:fs/promises';
import { chromium } from 'playwright';
import {
  parseFlags, checkStateName, checkScope, preflightAuth, assertLoggedIn, driveState, exitOnAuthError,
} from './state.mjs';

const DETERMINISM_CSS = `
*, *::before, *::after {
  animation-duration: 0s !important;
  animation-delay: 0s !important;
  animation-iteration-count: 1 !important;
  transition-duration: 0s !important;
  transition-delay: 0s !important;
}
html { scroll-behavior: auto !important; }
`;

function parseArgs(argv) {
  const a = parseFlags(argv, { repeatable: ['click'] });
  if (!a.capture) throw new Error('Usage: verify.mjs --capture <file.ndjson> [--sample 25] [--auth <file>] [--click <sel> … --state <name>] [--scope <sel> --scope-label <name>]');
  checkStateName(a.state, a.click);
  checkScope(a.scope, a['scope-label']);
  return {
    capture: a.capture, sample: Number(a.sample ?? 25),
    auth: a.auth ?? null, clicks: a.click, state: a.state ?? null,
    scope: a.scope ?? null, scopeLabel: a['scope-label'] ?? null,
  };
}

const args = parseArgs(process.argv);
const lines = (await readFile(args.capture, 'utf8')).trim().split('\n');
const recs = lines.map((l) => JSON.parse(l));
const meta = recs.shift();
if (!meta?._meta) throw new Error('First line is not a _meta record — is this a capture file?');

// T4: replay the same session and state the capture was taken under.
if (meta.auth && !args.auth) {
  throw new Error(`${args.capture} was captured with --auth (${meta.auth}); pass --auth <storageState.json> to verify it`);
}
if (args.state && args.state !== (meta.state ?? null)) {
  throw new Error(`--state ${args.state} does not match the capture's state (${meta.state ?? 'default'})`);
}
if (args.scope && (args.scope !== (meta.scope ?? null) || args.scopeLabel !== (meta.scopeLabel ?? null))) {
  throw new Error(`--scope ${args.scope} / --scope-label ${args.scopeLabel} do not match the capture's scope (${meta.scope ?? 'none'} / ${meta.scopeLabel ?? 'none'})`);
}
const clicks = args.clicks.length ? args.clicks : (meta.clicks ?? []);
try { preflightAuth(args.auth); } catch (e) { exitOnAuthError(e); }

// Random sample, stable enough to be meaningful without being exhaustive.
const pool = [...recs];
const picked = [];
while (picked.length < Math.min(args.sample, pool.length)) {
  picked.push(...pool.splice(Math.floor(Math.random() * pool.length), 1));
}

const browser = await chromium.launch();
const ctx = await browser.newContext({
  ignoreHTTPSErrors: true,
  viewport: { width: meta.viewport, height: 900 },
  deviceScaleFactor: 1,
  colorScheme: 'light',
  ...(args.auth ? { storageState: args.auth } : {}),
});
const page = await ctx.newPage();
await page.goto(meta.url, { waitUntil: 'networkidle', timeout: 60_000 });
await page.addStyleTag({ content: DETERMINISM_CSS });
try {
  await assertLoggedIn(page, meta.side, args.auth);
  await driveState(page, clicks);
} catch (e) {
  await browser.close();
  exitOnAuthError(e);
}
await page.evaluate(() => document.fonts.ready);
await page.evaluate(async () => {
  const step = window.innerHeight;
  for (let y = 0; y < document.body.scrollHeight; y += step) {
    window.scrollTo(0, y);
    await new Promise((r) => setTimeout(r, 40));
  }
  window.scrollTo(0, 0);
});
await page.waitForTimeout(150);

const live = await page.evaluate(({ nodes, props }) => {
  const resolve = (path) => {
    let el = document.body;
    if (path === '') return el;
    for (const idx of path.split('/').map(Number)) {
      if (!el || !el.children[idx]) return null;
      el = el.children[idx];
    }
    return el;
  };
  return nodes.map(({ p }) => {
    const el = resolve(p);
    if (!el) return null;
    const cs = getComputedStyle(el);
    const out = {};
    for (const k of props) out[k] = cs.getPropertyValue(k);
    return { tag: el.tagName.toLowerCase(), cs: out };
  });
}, { nodes: picked.map((n) => ({ p: n.p })), props: Object.keys(picked[0].cs) });

let checked = 0, mismatches = 0, unresolved = 0;
const problems = [];

// T7: the scope selector must still resolve to the element the capture walked.
let liveScopePath = null;
if (meta.scope) {
  liveScopePath = await page.evaluate((sel) => {
    let el = document.querySelector(sel);
    if (!el) return null;
    const parts = [];
    while (el && el !== document.body && el.parentElement) {
      parts.unshift(Array.prototype.indexOf.call(el.parentElement.children, el));
      el = el.parentElement;
    }
    return parts.join('/');
  }, meta.scope);
  if (liveScopePath !== meta.scopePath) {
    mismatches += 1;
    problems.push({ p: meta.scopePath, why: `scope drift: ${meta.scope} resolves live to ${liveScopePath ?? 'nothing'}, captured at ${meta.scopePath}` });
  }
}

picked.forEach((rec, i) => {
  const l = live[i];
  if (!l) { unresolved += 1; problems.push({ p: rec.p, why: 'node did not resolve' }); return; }
  if (l.tag !== rec.t) {
    problems.push({ p: rec.p, why: `tag drift: captured <${rec.t}>, live <${l.tag}>` });
    mismatches += 1;
    return;
  }
  for (const [prop, val] of Object.entries(rec.cs)) {
    checked += 1;
    if (l.cs[prop] !== val) {
      mismatches += 1;
      problems.push({ p: rec.p, why: `${prop}: captured "${val}", live "${l.cs[prop]}"` });
    }
  }
});

await browser.close();

console.log(`verify ${args.capture}`);
console.log(`  url        ${meta.url} @ ${meta.viewport}px`);
if (args.auth || clicks.length) {
  console.log(`  session    ${args.auth ?? 'anonymous'}${clicks.length ? `; state ${meta.state ?? args.state} via ${clicks.length} click(s)` : ''}`);
}
if (meta.scope) {
  console.log(`  scope      ${meta.scope} (${meta.scopeLabel}) at [${meta.scopePath}]; live [${liveScopePath ?? 'none'}]`);
}
console.log(`  sampled    ${picked.length} of ${recs.length} nodes`);
console.log(`  properties ${checked} compared`);
console.log(`  unresolved ${unresolved}`);
console.log(`  mismatches ${mismatches}`);

if (problems.length) {
  console.log('\nfirst problems:');
  for (const p of problems.slice(0, 10)) console.log(`  [${p.p}] ${p.why}`);
  console.log('\nVERIFY FAILED — the capture does not reproduce. Findings from it are void.');
  process.exit(1);
}

console.log('\nVERIFY PASSED — the capture reproduces against a live read.');

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
 */

import { readFile } from 'node:fs/promises';
import { chromium } from 'playwright';

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
  const a = {};
  for (let i = 2; i < argv.length; i += 2) a[argv[i].slice(2)] = argv[i + 1];
  if (!a.capture) throw new Error('Usage: verify.mjs --capture <file.ndjson> [--sample 25]');
  return { capture: a.capture, sample: Number(a.sample ?? 25) };
}

const args = parseArgs(process.argv);
const lines = (await readFile(args.capture, 'utf8')).trim().split('\n');
const recs = lines.map((l) => JSON.parse(l));
const meta = recs.shift();
if (!meta?._meta) throw new Error('First line is not a _meta record — is this a capture file?');

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
});
const page = await ctx.newPage();
await page.goto(meta.url, { waitUntil: 'networkidle', timeout: 60_000 });
await page.addStyleTag({ content: DETERMINISM_CSS });
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

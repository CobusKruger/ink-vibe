/**
 * Fidelity capture — renders a page and emits one NDJSON record per DOM node,
 * carrying an allowlisted set of computed-style properties.
 *
 * See docs/fidelity-remediation/README.md §4.1 (Phase 4 / 4b).
 *
 * Run it through tools/fidelity/run.sh, which sets the three required env vars.
 * Chromium cannot launch inside the macOS sandbox — see README §9.
 *
 * Usage:
 *   capture.mjs --url <url> --side <ink|lovable> --page <slug>
 *               [--viewport 390,768,1440] [--mode all|keyed]
 *               [--auth <storageState.json>] [--out <dir>]
 *               [--expect <selector>[,<selector>...]]
 *               [--click <selector> [--click <selector> …] --state <name>]
 *               [--scope <css selector> --scope-label <name>]
 *
 *   --auth   Playwright storageState from `run.sh login`. On the ink side the capture
 *            aborts (exit 3) unless the page renders with the WP `logged-in` body class.
 *   --click  drive the page into a state before capture; repeatable, applied in order,
 *            each followed by a settle wait. Requires --state.
 *   --state  label for that state; output is <page>@<vp>[-auth][-<state>].ndjson and the
 *            _meta record gains `state` + `clicks` (verify.mjs re-drives from them).
 *   --scope  record only the FIRST element matching the selector, and its subtree (T7),
 *            e.g. one archive block on a page that embeds several with the same
 *            audit-ids. Paths stay relative to <body>, so verify resolves them as
 *            usual. Requires --scope-label; output is
 *            <page>@<vp>[-auth][-<state>]-scope-<label>.ndjson and _meta gains
 *            `scope`, `scopeLabel` and `scopePath`. Aborts if nothing matches.
 *
 * A repeated audit-id in the written capture is reported on stderr (T5): diff.mjs
 * refuses such a file, so narrow the capture (--scope) or fix the ids first.
 */

import { chromium } from 'playwright';
import { mkdir, writeFile } from 'node:fs/promises';
import path from 'node:path';
import {
  parseFlags, checkStateName, checkScope, captureName, duplicateIds,
  preflightAuth, assertLoggedIn, driveState, exitOnAuthError,
} from './state.mjs';

const TOOL_VERSION = '1.0.0';

// --- Phase 4 §B3: the computed-property allowlist. -------------------------
// Capturing all ~340 computed properties per node is what makes a capture
// unusable. This set covers every property a fidelity finding can be about.
const PROPS = [
  'display', 'position', 'box-sizing', 'z-index', 'overflow',
  'width', 'height', 'max-width', 'min-height',
  'margin-top', 'margin-right', 'margin-bottom', 'margin-left',
  'padding-top', 'padding-right', 'padding-bottom', 'padding-left',
  'border-top-width', 'border-right-width', 'border-bottom-width', 'border-left-width',
  'border-top-style', 'border-top-color',
  'border-top-left-radius', 'border-top-right-radius',
  'border-bottom-right-radius', 'border-bottom-left-radius',
  'color', 'background-color', 'background-image', 'opacity', 'box-shadow',
  'font-family', 'font-size', 'font-weight', 'font-style',
  'line-height', 'letter-spacing', 'text-transform', 'text-align',
  'text-decoration-line', 'white-space',
  'flex-direction', 'flex-wrap', 'justify-content', 'align-items',
  'gap', 'row-gap', 'column-gap',
  'grid-template-columns', 'grid-auto-flow',
  'transform',
];

// Tags whose subtree carries no styling signal worth a record.
const SKIP_TAGS = new Set([
  'SCRIPT', 'STYLE', 'LINK', 'META', 'TITLE', 'HEAD', 'NOSCRIPT', 'TEMPLATE', 'BR',
]);

// --- C1-C4: determinism. ---------------------------------------------------
// Animations are driven to their END state rather than disabled: `animation:none`
// would strip a fade-up's end state and leave the element at its base opacity,
// which for some recipes is 0. Zero duration + fill-mode lands on the final frame.
const DETERMINISM_CSS = `
*, *::before, *::after {
  animation-duration: 0s !important;
  animation-delay: 0s !important;
  animation-iteration-count: 1 !important;
  transition-duration: 0s !important;
  transition-delay: 0s !important;
  caret-color: transparent !important;
}
html { scroll-behavior: auto !important; }
`;

function parseArgs(argv) {
  const a = parseFlags(argv, { repeatable: ['click'] });
  for (const req of ['url', 'side', 'page']) {
    if (!a[req]) throw new Error(`Missing required --${req}`);
  }
  checkStateName(a.state, a.click);
  checkScope(a.scope, a['scope-label']);
  return {
    url: a.url,
    side: a.side,
    page: a.page,
    viewports: (a.viewport ?? '390,768,1440').split(',').map(Number),
    mode: a.mode ?? 'all',
    auth: a.auth ?? null,
    out: a.out ?? 'tmp/fidelity-captures',
    expect: a.expect ? a.expect.split(',') : [],
    clicks: a.click,
    state: a.state ?? null,
    scope: a.scope ?? null,
    scopeLabel: a['scope-label'] ?? null,
  };
}

/** Walk the rendered DOM and serialise each node. Runs in the page.
 *  Playwright passes exactly one argument to an evaluated function. */
function collect({ props, skipTags, mode, scope }) {
  const out = [];
  const root = document.body;
  // T7: walk only the scope's subtree; paths stay relative to <body>.
  const start = scope ? document.querySelector(scope) : root;
  if (!start) return { nodes: null, scopePath: null };

  const pathOf = (el) => {
    const parts = [];
    let cur = el;
    while (cur && cur !== root) {
      const parent = cur.parentElement;
      if (!parent) break;
      parts.unshift(Array.prototype.indexOf.call(parent.children, cur));
      cur = parent;
    }
    return parts.join('/');
  };

  const walk = (el) => {
    if (skipTags.includes(el.tagName)) return;

    const auditId = el.getAttribute('data-audit-id');
    const take = mode === 'all' || auditId !== null;

    if (take) {
      const cs = getComputedStyle(el);
      const styles = {};
      for (const p of props) styles[p] = cs.getPropertyValue(p);

      const r = el.getBoundingClientRect();
      // Own text only — a container would otherwise repeat its whole subtree.
      const ownText = Array.prototype.filter
        .call(el.childNodes, (n) => n.nodeType === 3)
        .map((n) => n.textContent)
        .join(' ')
        .replace(/\s+/g, ' ')
        .trim();

      out.push({
        p: pathOf(el),
        t: el.tagName.toLowerCase(),
        c: el.className && typeof el.className === 'string'
          ? el.className.split(/\s+/).filter(Boolean)
          : [],
        a: auditId,
        x: {
          x: Math.round(r.x), y: Math.round(r.y),
          w: Math.round(r.width), h: Math.round(r.height),
        },
        s: ownText.slice(0, 80),
        cs: styles,
      });
    }

    // SVG internals are decorative and would swamp the record count.
    if (el.tagName === 'SVG' || el.tagName === 'svg') return;
    for (const child of el.children) walk(child);
  };

  walk(start);
  return { nodes: out, scopePath: scope ? pathOf(start) : null };
}

const args = parseArgs(process.argv);
try { preflightAuth(args.auth); } catch (e) { exitOnAuthError(e); }
const browser = await chromium.launch();
const results = [];

try {
for (const width of args.viewports) {
  const ctx = await browser.newContext({
    ignoreHTTPSErrors: true,            // nuwe-ink.local uses a Local-issued cert
    viewport: { width, height: 900 },
    deviceScaleFactor: 1,
    colorScheme: 'light',               // D4: light only for now
    ...(args.auth ? { storageState: args.auth } : {}),
  });

  const page = await ctx.newPage();
  await page.addStyleTag({ content: DETERMINISM_CSS }).catch(() => {});
  await page.goto(args.url, { waitUntil: 'networkidle', timeout: 60_000 });
  await page.addStyleTag({ content: DETERMINISM_CSS });

  // T4: never capture a logged-out page under an auth filename; then drive the state.
  await assertLoggedIn(page, args.side, args.auth);
  await driveState(page, args.clicks);

  // C2: font metrics move every measurement downstream.
  await page.evaluate(() => document.fonts.ready);

  // C4: force lazy images to load, then return to the top so bbox y-values
  // are measured from a known scroll position.
  await page.evaluate(async () => {
    const step = window.innerHeight;
    for (let y = 0; y < document.body.scrollHeight; y += step) {
      window.scrollTo(0, y);
      await new Promise((r) => setTimeout(r, 40));
    }
    window.scrollTo(0, 0);
    // Only wait on RENDERED images: a loading="lazy" <img> inside a display:none
    // subtree (e.g. Ontdek's hidden #skrywers tab panel) is never fetched, so its
    // load/error event never fires and an unfiltered wait hangs forever (row 03).
    await Promise.all(
      Array.from(document.images)
        .filter((i) => !i.complete && i.getClientRects().length > 0)
        .map((i) => new Promise((r) => { i.onload = i.onerror = r; })),
    );
  });
  await page.waitForTimeout(150);

  // A3: assert the page actually rendered its content before writing output.
  // An empty capture otherwise looks like a successful one.
  const missing = [];
  for (const sel of args.expect) {
    if ((await page.locator(sel).count()) === 0) missing.push(sel);
  }
  if (missing.length) {
    throw new Error(
      `Expected selectors absent at ${width}px — capture aborted rather than ` +
      `writing an empty file: ${missing.join(', ')}`,
    );
  }

  const { nodes, scopePath } = await page.evaluate(collect, {
    props: PROPS, skipTags: [...SKIP_TAGS], mode: args.mode, scope: args.scope,
  });
  if (!nodes) {
    throw new Error(`--scope matched nothing at ${width}px: ${args.scope} — capture aborted`);
  }

  const meta = {
    _meta: true,
    tool: TOOL_VERSION,
    side: args.side,
    page: args.page,
    url: args.url,
    viewport: width,
    mode: args.mode,
    auth: args.auth ? path.basename(args.auth) : null,
    // Only present for a driven state, so a default-state capture is byte-identical
    // to one taken before these flags existed.
    ...(args.state ? { state: args.state, clicks: args.clicks } : {}),
    // Likewise only for a scoped capture (T7).
    ...(args.scope ? { scope: args.scope, scopeLabel: args.scopeLabel, scopePath } : {}),
    nodes: nodes.length,
    auditIds: nodes.filter((n) => n.a).length,
    props: PROPS.length,
  };

  const dir = path.join(args.out, args.side);
  await mkdir(dir, { recursive: true });
  const name = `${captureName({
    page: args.page, viewport: width, auth: args.auth, state: args.state, scopeLabel: args.scopeLabel,
  })}.ndjson`;
  const file = path.join(dir, name);
  await writeFile(
    file,
    [meta, ...nodes].map((r) => JSON.stringify(r)).join('\n') + '\n',
  );

  results.push({ file, ...meta });
  console.log(`${file}  ${nodes.length} nodes, ${meta.auditIds} audit-ids`);
  // T5: warn on stderr (stdout is unchanged) — diff.mjs refuses a duplicated id.
  const dups = duplicateIds(nodes);
  if (dups.length) {
    console.error(`WARNING: ${dups.length} audit-id(s) occur more than once in ${file}; diff will refuse it:`);
    for (const [id, paths] of dups) console.error(`  ${id}  at ${paths.join(', ')}`);
  }
  await ctx.close();
}
} catch (e) {
  await browser.close();
  exitOnAuthError(e);
}

await browser.close();

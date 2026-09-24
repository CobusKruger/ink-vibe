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
 */

import { chromium } from 'playwright';
import { mkdir, writeFile } from 'node:fs/promises';
import path from 'node:path';

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
  const a = {};
  for (let i = 2; i < argv.length; i += 2) {
    if (!argv[i].startsWith('--')) throw new Error(`Expected a flag at "${argv[i]}"`);
    a[argv[i].slice(2)] = argv[i + 1];
  }
  for (const req of ['url', 'side', 'page']) {
    if (!a[req]) throw new Error(`Missing required --${req}`);
  }
  return {
    url: a.url,
    side: a.side,
    page: a.page,
    viewports: (a.viewport ?? '390,768,1440').split(',').map(Number),
    mode: a.mode ?? 'all',
    auth: a.auth ?? null,
    out: a.out ?? 'tmp/fidelity-captures',
    expect: a.expect ? a.expect.split(',') : [],
  };
}

/** Walk the rendered DOM and serialise each node. Runs in the page.
 *  Playwright passes exactly one argument to an evaluated function. */
function collect({ props, skipTags, mode }) {
  const out = [];
  const root = document.body;

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

  walk(root);
  return out;
}

const args = parseArgs(process.argv);
const browser = await chromium.launch();
const results = [];

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
    await Promise.all(
      Array.from(document.images)
        .filter((i) => !i.complete)
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

  const nodes = await page.evaluate(collect, {
    props: PROPS, skipTags: [...SKIP_TAGS], mode: args.mode,
  });

  const meta = {
    _meta: true,
    tool: TOOL_VERSION,
    side: args.side,
    page: args.page,
    url: args.url,
    viewport: width,
    mode: args.mode,
    auth: args.auth ? path.basename(args.auth) : null,
    nodes: nodes.length,
    auditIds: nodes.filter((n) => n.a).length,
    props: PROPS.length,
  };

  const dir = path.join(args.out, args.side);
  await mkdir(dir, { recursive: true });
  const name = `${args.page}@${width}${args.auth ? '-auth' : ''}.ndjson`;
  const file = path.join(dir, name);
  await writeFile(
    file,
    [meta, ...nodes].map((r) => JSON.stringify(r)).join('\n') + '\n',
  );

  results.push({ file, ...meta });
  console.log(`${file}  ${nodes.length} nodes, ${meta.auditIds} audit-ids`);
  await ctx.close();
}

await browser.close();

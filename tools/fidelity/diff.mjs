/**
 * Fidelity diff — joins two captures on `data-audit-id` and reports the
 * computed-style deltas, severity-ordered.
 *
 * See docs/fidelity-remediation/README.md §4.1 (Phase 4) and §6 (severity).
 * Output is GENERATED. Do not hand-edit the resulting finding document.
 *
 * Usage:
 *   diff.mjs --a <lovable.ndjson> --b <ink.ndjson> [--out <file.md>]
 */

import { readFile, writeFile } from 'node:fs/promises';

// README §6: box model first, then colour, then type, then micro.
const SEVERITY = [
  ['box', [
    'display', 'position', 'box-sizing', 'overflow',
    'width', 'height', 'max-width', 'min-height',
    'margin-top', 'margin-right', 'margin-bottom', 'margin-left',
    'padding-top', 'padding-right', 'padding-bottom', 'padding-left',
    'flex-direction', 'flex-wrap', 'justify-content', 'align-items',
    'gap', 'row-gap', 'column-gap', 'grid-template-columns', 'grid-auto-flow',
  ]],
  ['colour', [
    'color', 'background-color', 'background-image', 'opacity', 'border-top-color',
  ]],
  ['type', [
    'font-family', 'font-size', 'font-weight', 'font-style',
    'line-height', 'text-align', 'text-transform', 'white-space',
  ]],
  ['micro', [
    'letter-spacing', 'box-shadow', 'transform', 'z-index', 'text-decoration-line',
    'border-top-width', 'border-right-width', 'border-bottom-width', 'border-left-width',
    'border-top-style',
    'border-top-left-radius', 'border-top-right-radius',
    'border-bottom-right-radius', 'border-bottom-left-radius',
  ]],
];

const bucketOf = (prop) => {
  for (const [name, props] of SEVERITY) if (props.includes(prop)) return name;
  return 'micro';
};
const rank = (b) => SEVERITY.findIndex(([n]) => n === b);

function parseArgs(argv) {
  const a = {};
  for (let i = 2; i < argv.length; i += 2) a[argv[i].slice(2)] = argv[i + 1];
  if (!a.a || !a.b) throw new Error('Usage: diff.mjs --a <ref.ndjson> --b <ink.ndjson> [--out <file>]');
  return a;
}

// --- Noise suppression. ----------------------------------------------------
// Two sources of false positives swamp a raw comparison. Both are suppressed
// here rather than left for a human to filter, because an agent reading a noisy
// diff starts discounting the real findings alongside the fake ones.

/** Chromium serialises the same colour as `rgb()` or `color(srgb …)` depending
 *  on how it was authored. Canonicalise both to 0-255 integer form. */
function normaliseColour(v) {
  const srgb = v.match(/^color\(srgb ([\d.]+) ([\d.]+) ([\d.]+)(?:\s*\/\s*([\d.]+))?\)$/);
  if (srgb) {
    const [r, g, b] = srgb.slice(1, 4).map((n) => Math.round(parseFloat(n) * 255));
    const alpha = srgb[4] === undefined ? 1 : parseFloat(srgb[4]);
    return alpha === 1 ? `rgb(${r}, ${g}, ${b})` : `rgba(${r}, ${g}, ${b}, ${alpha})`;
  }
  const rgba = v.match(/^rgba\((\d+), (\d+), (\d+), 1\)$/);
  if (rgba) return `rgb(${rgba[1]}, ${rgba[2]}, ${rgba[3]})`;
  return v;
}

const isColourProp = (p) => p === 'color' || p === 'background-color' || p.endsWith('-color');
const normalise = (prop, v) => (isColourProp(prop) ? normaliseColour(v) : v);

/** A border colour on an edge that draws no border is not a visible difference.
 *  Tailwind's preflight sets a default border-color on every element; WordPress
 *  does not — so without this, every node reports a phantom border-colour delta. */
function borderColourIsMoot(a, b, prop) {
  if (!prop.startsWith('border-') || !prop.endsWith('-color')) return false;
  const edge = prop.slice(0, -'-color'.length);
  const moot = (n) => n.cs[`${edge}-style`] === 'none'
    || n.cs[`${edge}-style`] === 'hidden'
    || n.cs[`${edge}-width`] === '0px';
  return moot(a) && moot(b);
}

async function load(file) {
  const lines = (await readFile(file, 'utf8')).trim().split('\n');
  const recs = lines.map((l) => JSON.parse(l));
  const meta = recs[0]?._meta ? recs.shift() : {};
  const byId = new Map();
  for (const r of recs) if (r.a) byId.set(r.a, r);
  return { meta, recs, byId };
}

const args = parseArgs(process.argv);
const A = await load(args.a);   // reference (Lovable)
const B = await load(args.b);   // INK

const paired = [...A.byId.keys()].filter((id) => B.byId.has(id));
const onlyA = [...A.byId.keys()].filter((id) => !B.byId.has(id));
const onlyB = [...B.byId.keys()].filter((id) => !A.byId.has(id));

const findings = [];
for (const id of paired) {
  const a = A.byId.get(id);
  const b = B.byId.get(id);
  for (const prop of Object.keys(a.cs)) {
    if (borderColourIsMoot(a, b, prop)) continue;
    const av = normalise(prop, a.cs[prop]);
    const bv = normalise(prop, b.cs[prop]);
    if (av !== bv) findings.push({ id, prop, bucket: bucketOf(prop), ref: av, ink: bv });
  }
}

// E9c — collapse systemic differences.
// Some properties differ on *every* paired node because the two stacks are built
// differently: Tailwind's preflight sets `box-sizing: border-box` globally, and
// WordPress's constrained layout imposes a `max-width` the mockup never has.
// Repeating those per element buries the real findings, so a property whose
// delta is (near-)universal is reported once as an architectural note.
const SYSTEMIC_THRESHOLD = 0.8;
const systemic = [];

if (paired.length >= 4) {
  const perProp = new Map();
  for (const f of findings) {
    if (!perProp.has(f.prop)) perProp.set(f.prop, []);
    perProp.get(f.prop).push(f);
  }
  for (const [prop, rows] of perProp) {
    if (rows.length / paired.length < SYSTEMIC_THRESHOLD) continue;
    // Only collapse when the delta really is the same one everywhere.
    const shapes = new Set(rows.map((r) => `${r.ref}→${r.ink}`));
    if (shapes.size !== 1) continue;
    systemic.push({ prop, count: rows.length, ref: rows[0].ref, ink: rows[0].ink });
  }
}

const systemicProps = new Set(systemic.map((s) => s.prop));
const perElement = findings.filter((f) => !systemicProps.has(f.prop));

perElement.sort((x, y) => rank(x.bucket) - rank(y.bucket) || x.id.localeCompare(y.id) || x.prop.localeCompare(y.prop));

const L = [];
L.push(`# Computed-style diff — ${B.meta.page ?? '?'} @ ${B.meta.viewport ?? '?'}px`);
L.push('');
L.push('**GENERATED — do not hand-edit.** Regenerate with `tools/fidelity/run.sh diff`.');
L.push('');
L.push(`- Reference: \`${args.a}\` — ${A.meta.url ?? '?'} (${A.recs.length} nodes)`);
L.push(`- INK: \`${args.b}\` — ${B.meta.url ?? '?'} (${B.recs.length} nodes)`);
L.push(`- Paired on \`data-audit-id\`: **${paired.length}**`);
L.push(`- Reference-only ids: ${onlyA.length}${onlyA.length ? ` (${onlyA.join(', ')})` : ''}`);
L.push(`- INK-only ids: ${onlyB.length}${onlyB.length ? ` (${onlyB.join(', ')})` : ''}`);
L.push('');

if (systemic.length) {
  L.push('## Systemic — architectural, not per-element');
  L.push('');
  L.push('Each of these differs identically on (nearly) every paired node, so it is one');
  L.push('decision rather than many findings. Resolve at the layer, not on the page.');
  L.push('');
  L.push('| property | reference | INK | nodes |');
  L.push('|---|---|---|---|');
  for (const s of systemic) {
    L.push(`| ${s.prop} | \`${s.ref}\` | \`${s.ink}\` | ${s.count}/${paired.length} |`);
  }
  L.push('');
}

if (!paired.length) {
  L.push('> No paired nodes. Install `data-audit-id` on both sides (Phase 3) before diffing.');
} else if (!perElement.length) {
  L.push('No per-element computed-style differences across the paired nodes.');
} else {
  L.push(`## ${perElement.length} per-element deltas across ${new Set(perElement.map((f) => f.id)).size} elements`);
  L.push('');
  for (const [bucket] of SEVERITY) {
    const rows = perElement.filter((f) => f.bucket === bucket);
    if (!rows.length) continue;
    L.push(`### ${bucket} — ${rows.length}`);
    L.push('');
    L.push('| audit-id | property | reference | INK |');
    L.push('|---|---|---|---|');
    for (const f of rows) {
      const clip = (v) => (v.length > 60 ? v.slice(0, 57) + '…' : v);
      L.push(`| \`${f.id}\` | ${f.prop} | \`${clip(f.ref)}\` | \`${clip(f.ink)}\` |`);
    }
    L.push('');
  }
}

const md = L.join('\n') + '\n';
if (args.out) {
  await writeFile(args.out, md);
  console.log(`wrote ${args.out} — ${paired.length} paired, ${systemic.length} systemic, ${perElement.length} per-element deltas`);
} else {
  console.log(md);
}

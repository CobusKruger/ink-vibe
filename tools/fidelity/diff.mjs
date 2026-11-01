/**
 * Fidelity diff — joins two captures on `data-audit-id` and reports the
 * computed-style deltas, severity-ordered.
 *
 * See docs/fidelity-remediation/README.md §4.1 (Phase 4) and §6 (severity).
 * Output is GENERATED. Do not hand-edit the resulting finding document.
 *
 * Usage:
 *   diff.mjs --a <lovable.ndjson> --b <ink.ndjson> [--out <file.md>]
 *            [--map <maps/page.csv>] [--fail-on MISSING,STRUCTURE,DRIFT]
 *
 * Exits 2, writing nothing, if either input repeats a data-audit-id (T5); the
 * duplicated ids and their node paths are listed on stderr.
 *
 * --fail-on makes the diff a gate (classes per README §5, plus EXTRA if asked for):
 *   MISSING    id in the reference but not in INK, or a map `paired` id on one side only
 *   EXTRA      id in INK but not in the reference
 *   STRUCTURE  paired id with a different tag, a different nearest paired ancestor,
 *              or paired siblings in a different order
 *   DRIFT      paired id with a differing computed value, systemic deltas included.
 *              Architectural properties (ARCHITECTURAL below) are reported, never gated
 * Exits 1 if any listed class has an unaccepted finding, or if nothing paired.
 * One `gate …` summary line goes to stdout either way.
 *
 * --map supplies the accepted-divergence allowlist: a row with status `divergence` or
 * `scope` AND a non-empty note (the disclosure, R6) accepts findings on that id. An
 * optional `accept` column narrows it to `;`-separated classes and/or properties
 * (e.g. `DRIFT` or `padding-bottom;font-size`). `missing-ink` records a finding and
 * does not accept it.
 */

import { readFile, writeFile } from 'node:fs/promises';
import { duplicateIds } from './state.mjs';

const GATE_CLASSES = ['MISSING', 'EXTRA', 'STRUCTURE', 'DRIFT'];
const ACCEPTING_STATUS = new Set(['divergence', 'scope']);
// A stack-level delta (Tailwind preflight vs WP constrained layout, worklist E9c) is
// one recorded decision. Any other property differing on every node is real drift.
const ARCHITECTURAL = new Set(['box-sizing', 'max-width']);

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
  // Same preflight: `solid` at 0px vs `none` at 0px draws nothing on either side.
  if (prop === 'border-top-style') return a.cs['border-top-width'] === '0px' && b.cs['border-top-width'] === '0px';
  if (!prop.startsWith('border-') || !prop.endsWith('-color')) return false;
  const edge = prop.slice(0, -'-color'.length);
  const moot = (n) => n.cs[`${edge}-style`] === 'none'
    || n.cs[`${edge}-style`] === 'hidden'
    || n.cs[`${edge}-width`] === '0px';
  return moot(a) && moot(b);
}

/** RFC 4180 rows: map notes contain quoted commas. */
function parseCsv(text) {
  const rows = [];
  let row = [], cell = '', quoted = false;
  for (let i = 0; i < text.length; i += 1) {
    const c = text[i];
    if (quoted) {
      if (c === '"' && text[i + 1] === '"') { cell += '"'; i += 1; }
      else if (c === '"') quoted = false;
      else cell += c;
    } else if (c === '"') quoted = true;
    else if (c === ',') { row.push(cell); cell = ''; }
    else if (c === '\n') { row.push(cell); rows.push(row); row = []; cell = ''; }
    else if (c !== '\r') cell += c;
  }
  if (cell || row.length) { row.push(cell); rows.push(row); }
  return rows;
}

/** audit_id → { status, note, accept: Set|null } from a Phase 3 map. */
async function loadMap(file) {
  const [head, ...rows] = parseCsv((await readFile(file, 'utf8')).replace(/^\uFEFF/, ''));
  const col = (name) => head.indexOf(name);
  if (col('audit_id') < 0 || col('status') < 0) throw new Error(`${file}: no audit_id/status columns`);
  const map = new Map();
  for (const r of rows) {
    if (!r[col('audit_id')]) continue;
    const accept = col('accept') >= 0 ? (r[col('accept')] ?? '').split(';').map((t) => t.trim()).filter(Boolean) : [];
    map.set(r[col('audit_id')], {
      status: r[col('status')], note: (r[col('note')] ?? '').trim(), accept: accept.length ? new Set(accept) : null,
    });
  }
  return map;
}

async function load(file) {
  const lines = (await readFile(file, 'utf8')).trim().split('\n');
  const recs = lines.map((l) => JSON.parse(l));
  const meta = recs[0]?._meta ? recs.shift() : {};
  const byId = new Map();
  for (const r of recs) if (r.a) byId.set(r.a, r);
  return { meta, recs, byId, dups: duplicateIds(recs) };
}

const args = parseArgs(process.argv);
const failOn = (args['fail-on'] ?? '').split(',').map((c) => c.trim()).filter(Boolean);
const badClass = failOn.find((c) => !GATE_CLASSES.includes(c));
if (badClass) {
  console.error(`--fail-on: unknown class ${badClass}; use ${GATE_CLASSES.join(',')}`);
  process.exit(2);
}
const map = args.map ? await loadMap(args.map) : new Map();
const isAccepted = (id, cls, prop) => {
  const row = map.get(id);
  if (!row || !ACCEPTING_STATUS.has(row.status) || !row.note) return false;
  return !row.accept || row.accept.has(cls) || (prop !== undefined && row.accept.has(prop));
};
const A = await load(args.a);   // reference (Lovable)
const B = await load(args.b);   // INK

// T5: the join is one record per audit-id. A repeated id would silently pair an
// arbitrary instance (the last one), so refuse rather than write a wrong finding.
if (A.dups.length || B.dups.length) {
  console.error('DIFF REFUSED — duplicate data-audit-id(s); the join would mis-pair silently.');
  for (const [label, file, dups] of [['reference', args.a, A.dups], ['INK', args.b, B.dups]]) {
    if (!dups.length) continue;
    console.error(`\n${label}: ${file}`);
    for (const [id, paths] of dups) console.error(`  ${id}  ×${paths.length}  at ${paths.join(', ')}`);
  }
  console.error('\nNarrow the capture to one component (capture --scope) or make the ids unique.');
  process.exit(2);
}

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

// --- Gate classes. ----------------------------------------------------------
/** id → nearest ancestor (by path) whose id is paired; null at the top. */
function pairedParents({ recs }, pairedIds) {
  const idAt = new Map(recs.filter((r) => r.a && pairedIds.has(r.a)).map((r) => [r.p, r.a]));
  const out = new Map();
  for (const [p, id] of idAt) {
    let parent = null;
    for (let cut = p.lastIndexOf('/'); cut > 0 && parent === null; cut = p.lastIndexOf('/', cut - 1)) {
      parent = idAt.get(p.slice(0, cut)) ?? null;
    }
    out.set(id, parent);
  }
  return out;
}

function structureFindings() {
  const out = [];
  const pairedIds = new Set(paired);
  const pa = pairedParents(A, pairedIds);
  const pb = pairedParents(B, pairedIds);
  for (const id of paired) {
    const [ta, tb] = [A.byId.get(id).t, B.byId.get(id).t];
    if (ta !== tb) out.push({ id, what: 'tag', ref: ta, ink: tb });
    if (pa.get(id) !== pb.get(id)) out.push({ id, what: 'parent', ref: pa.get(id) ?? '(top)', ink: pb.get(id) ?? '(top)' });
  }
  // Order among siblings that share a parent on both sides. Records are in document order.
  const groups = new Map();
  for (const id of A.recs.filter((r) => pairedIds.has(r.a)).map((r) => r.a)) {
    if (pa.get(id) !== pb.get(id)) continue;
    const key = pa.get(id);
    if (!groups.has(key)) groups.set(key, []);
    groups.get(key).push(id);
  }
  for (const [parent, ids] of groups) {
    const members = new Set(ids);
    const inInk = B.recs.filter((r) => members.has(r.a)).map((r) => r.a);
    if (ids.join() !== inInk.join()) {
      out.push({ id: parent ?? '(top)', what: 'child order', ref: ids.join(' '), ink: inInk.join(' ') });
    }
  }
  return out;
}

const gated = Boolean(args.map || failOn.length);
const raw = !gated ? { MISSING: [], EXTRA: [], STRUCTURE: [], DRIFT: [] } : {
  MISSING: [
    ...onlyA.map((id) => ({ id, what: `not in INK (map: ${map.get(id)?.status ?? 'unmapped'})` })),
    ...onlyB.filter((id) => map.get(id)?.status === 'paired').map((id) => ({ id, what: 'map says paired; not in the reference' })),
  ],
  EXTRA: onlyB.filter((id) => map.get(id)?.status !== 'paired')
    .map((id) => ({ id, what: `not in the reference (map: ${map.get(id)?.status ?? 'unmapped'})` })),
  STRUCTURE: structureFindings(),
  DRIFT: findings.filter((f) => !ARCHITECTURAL.has(f.prop)),
};
const open = {};
const accepted = [];
for (const cls of GATE_CLASSES) {
  open[cls] = raw[cls].filter((f) => {
    if (!isAccepted(f.id, cls, f.prop)) return true;
    accepted.push({ cls, ...f });
    return false;
  });
}
const notCaptured = [...map].filter(([id, r]) => r.status === 'paired' && !A.byId.has(id) && !B.byId.has(id)).map(([id]) => id);
const gateCounts = GATE_CLASSES.map((c) => `${c}=${open[c].length}`).join(' ');
const gateFailed = failOn.length > 0 && (paired.length === 0 || failOn.some((c) => open[c].length > 0));

// The per-element tables hide what the map accepts; the Gate section lists it instead.
for (let i = perElement.length - 1; i >= 0; i -= 1) {
  if (isAccepted(perElement[i].id, 'DRIFT', perElement[i].prop)) perElement.splice(i, 1);
}

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

if (gated) {
  L.push('## Gate');
  L.push('');
  L.push(`- Map: ${args.map ? `\`${args.map}\`` : 'none'}`);
  L.push(`- Open: ${gateCounts}`);
  L.push(`- Accepted by the map: ${accepted.length}`);
  if (notCaptured.length) L.push(`- Map \`paired\`, on neither side of this capture (another state or scope?): ${notCaptured.join(', ')}`);
  L.push('');
  for (const cls of ['MISSING', 'EXTRA', 'STRUCTURE']) {
    if (!open[cls].length) continue;
    L.push(`### ${cls} — ${open[cls].length}`);
    L.push('');
    for (const f of open[cls]) {
      L.push(`- \`${f.id}\`: ${f.what}${f.ref !== undefined ? ` — reference \`${f.ref}\`, INK \`${f.ink}\`` : ''}`);
    }
    L.push('');
  }
  if (accepted.length) {
    L.push(`### Accepted — ${accepted.length}`);
    L.push('');
    for (const f of accepted) L.push(`- ${f.cls} \`${f.id}\`${f.prop ? ` ${f.prop}` : ''}: ${map.get(f.id).note}`);
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

if (gated) {
  console.log(`gate ${failOn.length ? (gateFailed ? 'FAILED' : 'PASSED') : '(report only)'}: ${gateCounts} accepted=${accepted.length} paired=${paired.length}`);
}
if (gateFailed) process.exit(1);

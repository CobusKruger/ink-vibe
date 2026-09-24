/**
 * E10 — Phase 4b automation.
 *
 * Reads capture NDJSON and emits `maps/_primitive-observations.csv` rows: one per
 * primitive instance, with its computed declarations. This is the raw material
 * Stage 2 groups into canonical recipes (README.md §4.7).
 *
 * It records; it does not judge. Two instances that look identical still both get
 * rows — Stage 2 decides whether they truly match.
 *
 * Usage:
 *   extract-primitives.mjs --in <a.ndjson> [--in <b.ndjson> …] [--out <file.csv>] [--append]
 */

import { readFile, writeFile, appendFile } from 'node:fs/promises';

// --- Classification. -------------------------------------------------------
// INK carries semantic class names; Lovable carries Tailwind utilities and has
// no semantic hook at all. So classification is tag-and-role first, with name
// patterns only as a refinement. Order matters — first match wins.
const RULES = [
  { kind: 'button-primary', test: (n) => /is-style-ink-primary|knoppie--primer/.test(n.c.join(' ')) },
  { kind: 'button-outline', test: (n) => /is-style-ink-outline|knoppie--sekonder/.test(n.c.join(' ')) },
  { kind: 'button-ghost', test: (n) => /is-style-ink-ghost/.test(n.c.join(' ')) },
  { kind: 'button-sage', test: (n) => /is-style-ink-sage/.test(n.c.join(' ')) },
  { kind: 'filter-chip', test: (n) => /filter-knoppie|sorteer-knoppie|datum-knoppie/.test(n.c.join(' ')) },
  { kind: 'tab', test: (n) => /tabs__knoppie|role=tab/.test(n.c.join(' ')) },
  { kind: 'search-field', test: (n) => n.t === 'input' && /soek|search/.test(n.c.join(' ')) },
  { kind: 'search-button', test: (n) => /soek-knoppie/.test(n.c.join(' ')) },
  { kind: 'form-field', test: (n) => ['input', 'textarea', 'select'].includes(n.t) },
  { kind: 'form-label', test: (n) => n.t === 'label' },
  { kind: 'pill', test: (n) => /__pil\b|-pil\b|is-style-pill|rounded-full/.test(n.c.join(' ')) },
  { kind: 'badge', test: (n) => /badge|kenteken|__etiket/.test(n.c.join(' ')) },
  { kind: 'card', test: (n) => /is-style-ink-card|is-style-card|__kaart\b|-card\b|-kaart\b/.test(n.c.join(' ')) },
  { kind: 'eyebrow', test: (n) => /__boskrif|eyebrow|uppercase/.test(n.c.join(' ')) },
  { kind: 'avatar', test: (n) => n.t === 'img' && /foto|avatar|rounded-full/.test(n.c.join(' ')) },
  { kind: 'section-band', test: (n) => n.t === 'section' || /-band\b|-strook\b/.test(n.c.join(' ')) },
  // Generic button LAST: the specific recipes above should claim their own first.
  { kind: 'button', test: (n) => n.t === 'button' || /wp-block-button__link/.test(n.c.join(' ')) },
  { kind: 'heading', test: (n) => /^h[1-6]$/.test(n.t) },
];

// Which computed properties matter per family. Recording all 51 for every
// instance makes the register unreadable; these are the ones a recipe is made of.
const PROPS_FOR = {
  button: ['display', 'padding-top', 'padding-right', 'padding-bottom', 'padding-left',
    'background-color', 'color', 'border-top-width', 'border-top-style', 'border-top-color',
    'border-top-left-radius', 'font-family', 'font-size', 'font-weight', 'line-height',
    'letter-spacing', 'text-transform', 'box-shadow'],
  field: ['display', 'width', 'padding-top', 'padding-right', 'padding-bottom', 'padding-left',
    'background-color', 'color', 'border-top-width', 'border-top-style', 'border-top-color',
    'border-top-left-radius', 'font-family', 'font-size', 'line-height'],
  surface: ['display', 'padding-top', 'padding-right', 'padding-bottom', 'padding-left',
    'background-color', 'border-top-width', 'border-top-style', 'border-top-color',
    'border-top-left-radius', 'box-shadow', 'gap'],
  text: ['color', 'font-family', 'font-size', 'font-weight', 'font-style', 'line-height',
    'letter-spacing', 'text-transform'],
};

const FAMILY = {
  'button-primary': 'button', 'button-outline': 'button', 'button-ghost': 'button',
  'button-sage': 'button', 'button': 'button', 'search-button': 'button',
  'filter-chip': 'button', 'tab': 'button',
  'search-field': 'field', 'form-field': 'field',
  'card': 'surface', 'section-band': 'surface', 'pill': 'surface', 'badge': 'surface',
  'avatar': 'surface',
  'heading': 'text', 'eyebrow': 'text', 'form-label': 'text',
};

function classify(node) {
  for (const r of RULES) {
    try { if (r.test(node)) return r.kind; } catch { /* selector rule misfire */ }
  }
  return null;
}

function parseArgs(argv) {
  const ins = [];
  let out = null, append = false;
  for (let i = 2; i < argv.length; i += 1) {
    if (argv[i] === '--in') { ins.push(argv[++i]); }
    else if (argv[i] === '--out') { out = argv[++i]; }
    else if (argv[i] === '--append') { append = true; }
  }
  if (!ins.length) throw new Error('Usage: extract-primitives.mjs --in <capture.ndjson> [--out <file.csv>] [--append]');
  return { ins, out, append };
}

const csv = (v) => {
  const s = String(v ?? '');
  return /[",\n]/.test(s) ? `"${s.replace(/"/g, '""')}"` : s;
};

const args = parseArgs(process.argv);
const rows = [];

for (const file of args.ins) {
  const lines = (await readFile(file, 'utf8')).trim().split('\n');
  const recs = lines.map((l) => JSON.parse(l));
  const meta = recs[0]?._meta ? recs.shift() : {};

  for (const n of recs) {
    const kind = classify(n);
    if (!kind) continue;

    const props = PROPS_FOR[FAMILY[kind] ?? 'text'];
    const decls = props
      .map((p) => `${p}:${n.cs[p]}`)
      .filter((d) => !/:$|:normal$|:none$|:auto$/.test(d))
      .join('; ');

    rows.push({
      page: meta.page ?? '?',
      side: meta.side ?? '?',
      viewport: meta.viewport ?? '?',
      component_kind: kind,
      selector: n.c.length ? '.' + n.c.join('.') : n.t,
      defined_in: '',      // source-side; not knowable from a rendered capture
      load_scope: '',      // source-side
      used_in: '',         // source-side
      declarations: decls,
      audit_id: n.a ?? '',
      note: '',
    });
  }
}

const HEAD = 'page,side,viewport,component_kind,selector,defined_in,load_scope,used_in,declarations,audit_id,note';
const body = rows.map((r) => Object.values(r).map(csv).join(',')).join('\n');

if (args.out) {
  if (args.append) await appendFile(args.out, body + '\n');
  else await writeFile(args.out, HEAD + '\n' + body + '\n');
  console.log(`${args.append ? 'appended' : 'wrote'} ${args.out} — ${rows.length} rows`);
} else {
  console.log(HEAD);
  console.log(body);
}

// Summary so a run is self-describing.
const byKind = rows.reduce((m, r) => (m[r.component_kind] = (m[r.component_kind] ?? 0) + 1, m), {});
console.error('\ninstances by kind:');
for (const [k, v] of Object.entries(byKind).sort((a, b) => b[1] - a[1])) {
  console.error(`  ${k.padEnd(16)} ${v}`);
}

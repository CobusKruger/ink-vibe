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
 *                          [--exclude-chrome]
 *
 *   --exclude-chrome  drop the site header and footer (row 00's scope) from every other
 *                     row: any node whose audit-id is `header`/`header-*`/`footer`/`footer-*`,
 *                     and any <header>/<footer> element outside <main>, with their subtrees.
 */

import { readFile, writeFile, appendFile } from 'node:fs/promises';

// --- Shape helpers. ----------------------------------------------------------
// A capture carries no attributes beyond class and audit-id, so where a class name
// gives no hook (Tailwind, class-less WP blocks) classification falls back on the
// computed box: size, radius, fill, border, own text, children.
const px = (v) => parseFloat(v) || 0;
const TRANSPARENT = /^(rgba\(0, 0, 0, 0\)|transparent|color\(srgb 0 0 0 \/ 0\))$/;
const filled = (n) => !TRANSPARENT.test(n.cs['background-color'] ?? '') ||
  (n.cs['background-image'] ?? 'none') !== 'none';
const bordered = (n) => px(n.cs['border-top-width']) > 0 && n.cs['border-top-style'] !== 'none';
const square = (n) => n.x.w > 0 && Math.abs(n.x.w - n.x.h) <= 1;
const round = (n) => px(n.cs['border-top-left-radius']) >= n.x.w / 2 - 1;
const padded = (n) => px(n.cs['padding-top']) > 0 || px(n.cs['padding-left']) > 0;
const cls = (n) => n.c.join(' ');
const has = (n, c) => n.c.includes(c);

// --- Classification. -------------------------------------------------------
// INK carries semantic class names; Lovable carries Tailwind utilities and has
// no semantic hook at all. So classification is tag-and-role first, with name
// patterns only as a refinement. Order matters — first match wins.
// Each rule returns true/false, or a `[kind, note]` pair when it decides the kind
// itself. Every kind is from the README §4.1 controlled vocabulary.

/** Variant of an INK core/button wrapper (`div.wp-block-button.is-style-…`). */
function inkButtonVariant(c) {
  if (/is-style-ink-primary|knoppie--primer/.test(c)) return 'button-primary';
  if (/is-style-ink-sage-outline/.test(c)) return 'button-sage-outline';
  if (/is-style-ink-outline|knoppie--sekonder/.test(c)) return 'button-outline';
  if (/is-style-ink-ghost/.test(c)) return 'button-ghost';
  if (/is-style-ink-sage/.test(c)) return 'button-sage';
  return null;
}

/** Variant of a Lovable shadcn <Button> — rendered as <button>, or as <a> via asChild. */
function lovableButtonVariant(n) {
  if (!['a', 'button'].includes(n.t)) return null;
  if (!(has(n, 'inline-flex') && has(n, 'items-center') && has(n, 'justify-center'))) return null;
  if (has(n, 'bg-terracotta') || has(n, 'bg-primary')) return 'button-primary';
  if (has(n, 'bg-sage')) return 'button-sage';
  if (has(n, 'border-sage')) return 'button-sage-outline';
  if (has(n, 'border-terracotta') || has(n, 'border-input') || has(n, 'border')) return 'button-outline';
  if (has(n, 'underline-offset-4')) return 'link-underline';
  if (has(n, 'h-10') && has(n, 'w-10') && !n.s) return 'button-icon';
  if (has(n, 'hover:bg-accent')) return 'button-ghost';
  return 'button';
}

const RULES = [
  // Not primitives: hidden form plumbing (nonce/referer inputs) and the zero-height
  // toast live region (<section aria-label="Notifications">). `false` = skip node.
  { kind: null, test: (n) => n.t === 'input' && n.cs.display === 'none' },
  { kind: null, test: (n) => n.t === 'section' && n.x.h === 0 && !n.c.length },

  // Check / radio controls: native inputs (type is not captured, so: a small square
  // input) and Radix <button role=checkbox class="peer h-4 w-4 …">.
  { kind: 'checkbox', test: (n) => (n.t === 'input' && square(n) && n.x.w <= 24 && n.x.w >= 10)
      || (n.t === 'button' && has(n, 'peer') && square(n) && n.x.w <= 24) },

  // INK core/button: the wrapper div carries the variant, the <a> is what renders.
  // Row 00's convention: register the <a>, with the kind taken from the wrapper.
  { kind: null, test: (n, ctx) => n.t === 'div' && has(n, 'wp-block-button')
      && ctx.children(n).some((ch) => has(ch, 'wp-block-button__link')) },
  { kind: 'button', test: (n, ctx) => {
    if (!has(n, 'wp-block-button__link')) return false;
    const parent = ctx.parent(n);
    const pc = parent ? cls(parent) : '';
    const v = inkButtonVariant(pc);
    const notes = [v ? `kind from wrapper .${parent.c.join('.')}` : 'wrapper has no registered variant'];
    if (/\bink-btn-icon\b/.test(pc)) notes.push('ink-btn-icon (button-icon-label modifier)');
    return [v ?? 'button', notes.join('; ')];
  } },

  // Lovable shadcn Button, including <Button asChild> links.
  { kind: 'button', test: (n) => {
    const v = lovableButtonVariant(n);
    if (!v) return false;
    return [v, n.t === 'a' ? 'Lovable <Button asChild> link' : ''];
  } },

  { kind: 'button-primary', test: (n) => /is-style-ink-primary|knoppie--primer/.test(cls(n)) },
  { kind: 'button-sage-outline', test: (n) => /is-style-ink-sage-outline/.test(cls(n)) },
  { kind: 'button-outline', test: (n) => /is-style-ink-outline|knoppie--sekonder/.test(cls(n)) },
  { kind: 'button-ghost', test: (n) => /is-style-ink-ghost/.test(cls(n)) },
  { kind: 'button-sage', test: (n) => /is-style-ink-sage/.test(cls(n)) },
  { kind: 'filter-chip', test: (n) => /filter-knoppie|sorteer-knoppie|datum-knoppie/.test(cls(n)) },
  { kind: 'tab', test: (n) => /tabs__knoppie|role=tab/.test(cls(n)) },
  { kind: 'search-field', test: (n) => n.t === 'input' && /soek|search/.test(cls(n)) },
  { kind: 'search-button', test: (n) => /soek-knoppie|soek__knoppie/.test(cls(n)) },
  { kind: 'form-field', test: (n) => ['input', 'textarea', 'select'].includes(n.t) },
  // A bordered <label> wrapping a check/radio control is a selectable surface, not a
  // label: row-shaped → choice-row (challenge checklist row), tile-shaped → card
  // (type-picker card).
  { kind: 'form-label', test: (n, ctx) => {
    if (n.t !== 'label') return false;
    const wrapsControl = ctx.descendants(n).some((d) => d.t === 'input' || (d.t === 'button' && has(d, 'peer')));
    if (!(wrapsControl && bordered(n) && padded(n))) return true;
    return n.x.w > 2 * n.x.h ? ['choice-row', 'bordered <label> wrapping a control']
      : ['card', 'bordered <label> wrapping a control (picker card)'];
  } },

  // A member's Gradering as bare text (INK `__gradering`).
  { kind: 'tier-label', test: (n) => /gradering/.test(cls(n)) && !filled(n) && !bordered(n) },

  // Small round/square shapes with no semantic class.
  { kind: 'list-bullet', test: (n, ctx) => square(n) && n.x.w <= 12 && round(n) && filled(n)
      && !n.s && !ctx.children(n).length },
  { kind: 'step-badge', test: (n) => square(n) && n.x.w >= 20 && n.x.w <= 48 && round(n)
      && filled(n) && /^\d{1,2}$/.test(n.s) },
  { kind: 'icon-tile', test: (n, ctx) => {
    if (!square(n) || n.x.w < 24 || n.x.w > 64 || !filled(n) || n.s
      || ['img', 'button', 'a'].includes(n.t)) return false;
    if (/(__|-)(icon|ikoon)\b|rang-merk/.test(cls(n))) return true;          // INK
    return ctx.children(n).some((ch) => ch.t === 'svg');                       // Lovable
  } },
  // Rounded-full Lovable buttons: a labelled one is a chip, an unlabelled one an icon button.
  { kind: 'filter-chip', test: (n) => n.t === 'button' && has(n, 'rounded-full') && !!n.s },
  { kind: 'button-icon', test: (n) => n.t === 'button' && has(n, 'rounded-full') && !n.s },
  { kind: 'avatar', test: (n) => (n.t === 'img' && /foto|avatar|rounded-full/.test(cls(n)))
      || (square(n) && n.x.w >= 24 && n.x.w <= 64 && round(n) && filled(n) && /^\p{L}{1,2}$/u.test(n.s)) },

  { kind: 'badge', test: (n) => /badge|kenteken|__etiket/.test(cls(n)) && (filled(n) || bordered(n)) },
  // INK `-skakel` (link) and `-tipe` (type label): their names miss the -card/-pil patterns.
  { kind: 'card', test: (n) => /(-|__)skakel\b/.test(cls(n)) && filled(n) && bordered(n)
      && px(n.cs['border-top-left-radius']) >= 8 && px(n.cs['padding-top']) >= 16 },
  { kind: 'button-outline', test: (n) => /(-|__)skakel\b/.test(cls(n)) && bordered(n) && padded(n) && !!n.s },
  { kind: 'pill', test: (n) => /(-|__)tipe\b/.test(cls(n)) && (filled(n) || bordered(n)) && padded(n) },

  // Bespoke button-shaped links with no class hook (INK `__aksie`, `__cta`, …): caught
  // by shape and left as a generic `button` for the auditor to resolve to a variant.
  { kind: 'button', test: (n, ctx) => n.t === 'a' && (!!n.s || ctx.children(n).length > 0) && padded(n) && (filled(n) || bordered(n))
      && px(n.cs['border-top-left-radius']) >= 2 && px(n.cs['border-top-left-radius']) <= 16
      && n.x.h >= 28 && n.x.h <= 60
      && ['button', 'button-shaped <a> with no class hook; resolve the variant'] },

  // Decorative circles (no text, no children) are not pills.
  { kind: null, test: (n, ctx) => has(n, 'rounded-full') && !n.s && !ctx.children(n).length },
  { kind: 'pill', test: (n) => /__pil\b|-pil\b|is-style-pill|rounded-full/.test(cls(n)) },
  { kind: 'card', test: (n) => /is-style-ink-card|is-style-card|__kaart\b|-card\b|-kaart\b/.test(cls(n)) },
  { kind: 'eyebrow', test: (n) => /__boskrif|eyebrow|uppercase/.test(cls(n)) },
  // Class-less eyebrow: small uppercase own text (INK recipes are inline block attributes).
  // (Not the label inside a filled chip — the chip is the primitive.)
  { kind: 'eyebrow', test: (n, ctx) => !!n.s && n.cs['text-transform'] === 'uppercase'
      && px(n.cs['font-size']) <= 14 && !['a', 'button'].includes(n.t) && !filled(n) && !bordered(n)
      && !(ctx.parent(n) && filled(ctx.parent(n))) },
  { kind: 'section-band', test: (n) => n.t === 'section' || /-band\b|-strook\b/.test(cls(n)) },
  // Generic button LAST: the specific recipes above should claim their own first.
  { kind: 'button', test: (n) => n.t === 'button' },
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
  'button-sage': 'button', 'button-sage-outline': 'button', 'button-icon': 'button',
  'button': 'button', 'search-button': 'button', 'filter-chip': 'button', 'tab': 'button',
  'search-field': 'field', 'form-field': 'field', 'checkbox': 'field',
  'card': 'surface', 'section-band': 'surface', 'pill': 'surface', 'badge': 'surface',
  'avatar': 'surface', 'icon-tile': 'surface', 'step-badge': 'surface', 'list-bullet': 'surface',
  'choice-row': 'surface',
  'heading': 'text', 'eyebrow': 'text', 'form-label': 'text', 'tier-label': 'text',
  'link-underline': 'text',
};

function classify(node, ctx) {
  for (const r of RULES) {
    let hit;
    try { hit = r.test(node, ctx); } catch { continue; /* rule misfire */ }
    if (!hit) continue;
    if (Array.isArray(hit)) return { kind: hit[0], note: hit[1] };
    return r.kind ? { kind: r.kind, note: '' } : null;
  }
  return null;
}

/** Paths of the header/footer subtrees (row 00's scope) for --exclude-chrome. */
function chromeRoots(recs) {
  const mainPaths = recs.filter((r) => r.t === 'main').map((r) => r.p);
  const inMain = (p) => mainPaths.some((m) => p === m || p.startsWith(m + '/'));
  return recs
    .filter((r) => /^(header|footer)(-|$)/.test(r.a ?? '')
      || (['header', 'footer'].includes(r.t) && !inMain(r.p)))
    .map((r) => r.p);
}

function parseArgs(argv) {
  const ins = [];
  let out = null, append = false, excludeChrome = false;
  for (let i = 2; i < argv.length; i += 1) {
    if (argv[i] === '--in') { ins.push(argv[++i]); }
    else if (argv[i] === '--out') { out = argv[++i]; }
    else if (argv[i] === '--append') { append = true; }
    else if (argv[i] === '--exclude-chrome') { excludeChrome = true; }
    else throw new Error(`Unknown argument "${argv[i]}"`);
  }
  if (!ins.length) throw new Error('Usage: extract-primitives.mjs --in <capture.ndjson> [--out <file.csv>] [--append] [--exclude-chrome]');
  return { ins, out, append, excludeChrome };
}

const csv = (v) => {
  const s = String(v ?? '');
  return /[",\n]/.test(s) ? `"${s.replace(/"/g, '""')}"` : s;
};

const args = parseArgs(process.argv);
const rows = [];
let excluded = 0;

for (const file of args.ins) {
  const lines = (await readFile(file, 'utf8')).trim().split('\n');
  const recs = lines.map((l) => JSON.parse(l));
  const meta = recs[0]?._meta ? recs.shift() : {};

  const byPath = new Map(recs.map((r) => [r.p, r]));
  const kids = new Map();
  for (const r of recs) {
    if (r.p === '') continue;
    const pp = r.p.includes('/') ? r.p.slice(0, r.p.lastIndexOf('/')) : '';
    if (!kids.has(pp)) kids.set(pp, []);
    kids.get(pp).push(r);
  }
  const ctx = {
    parent: (n) => (n.p === '' ? null : byPath.get(n.p.includes('/') ? n.p.slice(0, n.p.lastIndexOf('/')) : '')),
    children: (n) => kids.get(n.p) ?? [],
    descendants: (n) => recs.filter((r) => r.p.startsWith(n.p === '' ? '' : n.p + '/') && r !== n),
  };
  const chrome = args.excludeChrome ? chromeRoots(recs) : [];
  const isChrome = (p) => chrome.some((c) => p === c || p.startsWith(c + '/'));

  for (const n of recs) {
    if (chrome.length && isChrome(n.p)) { excluded += 1; continue; }
    const hit = classify(n, ctx);
    if (!hit) continue;
    const { kind, note } = hit;

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
      note: [meta.state ? `state ${meta.state}` : '', note].filter(Boolean).join('; '),
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
  console.error(`  ${k.padEnd(20)} ${v}`);
}
if (args.excludeChrome) console.error(`  (${excluded} header/footer nodes excluded)`);
console.error('  generic `button` rows are not a kind — resolve each to a variant (README §4.1)');

/**
 * The acceptance gate runner: captures INK (fresh) and Lovable (reused reference), diffs with the page's map and
 * `--fail-on MISSING,STRUCTURE,DRIFT`, one line per page/state/auth/viewport.
 *
 * Usage (from the repo root, through the wrapper so Node/PATH/env are right):
 *   tools/fidelity/run.sh node tools/fidelity/accept/gates.mjs [--label L] [--group a,b] [--only substr]
 *                          [--ref-dir D] [--refresh-refs] [--list]
 * Normally run via tools/fidelity/accept/gate-all.sh. Data: gates.json. Exit 0 only if every gate passed.
 *
 * Default groups: everything except `closed` (closed-round.sh runs that one with the fixture round drafted).
 */
import { mkdirSync, rmSync, readFileSync, writeFileSync, existsSync, readdirSync } from 'node:fs';
import path from 'node:path';
import {
  REPO, INK, LOVABLE, INK_AUTH, LOVABLE_AUTH, gatesFile, log, die, runNode,
  neededLookups, resolveLookups, expand, ensureSessions, lovableBuild,
} from './common.mjs';

const argv = process.argv.slice(2);
const opt = (n, d = null) => { const i = argv.indexOf(`--${n}`); return i >= 0 ? argv[i + 1] : d; };
const flag = (n) => argv.includes(`--${n}`);
const label = opt('label', `run-${new Date().toISOString().replace(/[-:T]/g, '').slice(0, 12)}`);
const groups = opt('group') ? opt('group').split(',') : [...new Set(gatesFile.gates.map((g) => g.group))].filter((g) => g !== 'closed');
const only = opt('only');
const refDir = opt('ref-dir', 'tmp/fidelity-captures/accept/ref');
const outDir = `tmp/fidelity-captures/accept/${label}`;

const gates = gatesFile.gates.filter((g) => groups.includes(g.group));
if (!gates.length) die(`no gates in group(s) ${groups.join(',')}`);

const lk = await resolveLookups(neededLookups(gates));
let units = expand(gates, lk);
if (only) units = units.filter((u) => u.disp(u.vps[0]).includes(only));

if (flag('list')) {
  log(`lookups: ${JSON.stringify(lk)}`);
  for (const u of units) log(`${u.disp(u.vps.join('/'))}\n    lovable ${u.lovableUrl}\n    ink     ${u.inkUrl}\n    map     ${u.map}  scope ${u.scope}`);
  log(`${units.reduce((n, u) => n + u.vps.length, 0)} gates`);
  process.exit(0);
}

log(`gate run "${label}": INK ${INK}, Lovable ${LOVABLE}, groups ${groups.join(',')}`);
log(`lookups: ${Object.entries(lk).map(([k, v]) => `${k}=${v}`).join(' ')}`);
const build = await lovableBuild();
ensureSessions();

// A reference is valid for one Lovable build. A new build clears the references before they are reused.
mkdirSync(path.join(REPO, refDir, 'lovable'), { recursive: true });
const marker = path.join(REPO, refDir, '.build');
const prev = existsSync(marker) ? readFileSync(marker, 'utf8').trim() : '';
let refresh = flag('refresh-refs');
if (prev !== `${LOVABLE} ${build}`) {
  for (const f of readdirSync(path.join(REPO, refDir, 'lovable'))) rmSync(path.join(REPO, refDir, 'lovable', f));
  writeFileSync(marker, `${LOVABLE} ${build}\n`);
  log(`Lovable references cleared (build ${build}${prev ? `, was ${prev}` : ''}).`);
}
mkdirSync(path.join(REPO, outDir, 'out'), { recursive: true });

function capture(side, u, out) {
  const url = side === 'ink' ? u.inkUrl : u.lovableUrl;
  const args = ['tools/fidelity/capture.mjs', '--url', url, '--side', side, '--page', u.g.page, '--viewport', u.vps.join(','), '--scope', u.scope, '--scope-label', u.scopeLabel, '--out', out];
  if (u.auth) args.push('--auth', side === 'ink' ? INK_AUTH : LOVABLE_AUTH);
  for (const c of u.click) args.push('--click', c);
  if (u.state) args.push('--state', u.state);
  const r = runNode(args[0], args.slice(1));
  return r;
}

const results = [];
const byGroup = {};
for (const u of units) {
  const first = path.join(REPO, refDir, 'lovable', `${u.stem(u.vps[0])}.ndjson`);
  const missing = u.vps.some((v) => !existsSync(path.join(REPO, refDir, 'lovable', `${u.stem(v)}.ndjson`)));
  let err = null;
  if (refresh || missing) {
    const r = capture('lovable', u, refDir);
    if (r.status !== 0) err = `Lovable capture failed: ${r.out.trim().split('\n').slice(-3).join(' | ')}`;
  }
  if (!err) {
    const r = capture('ink', u, outDir);
    if (r.status !== 0) err = `INK capture failed: ${r.out.trim().split('\n').slice(-3).join(' | ')}`;
  }
  for (const v of u.vps) {
    let line;
    if (err) line = `ERROR ${err}`;
    else {
      const a = `${refDir}/lovable/${u.stem(v)}.ndjson`, b = `${outDir}/ink/${u.stem(v)}.ndjson`;
      const d = runNode('tools/fidelity/diff.mjs', ['--a', a, '--b', b, '--map', u.map, '--fail-on', gatesFile.failOn, '--out', `${outDir}/out/${u.disp(v)}.md`]);
      line = d.out.split('\n').find((l) => /^gate /.test(l)) || `ERROR no gate line: ${d.out.trim().split('\n').slice(-2).join(' | ')}`;
    }
    const pass = /^gate PASSED/.test(line);
    results.push({ name: u.disp(v), group: u.g.group, pass, line });
    (byGroup[u.g.group] ??= { pass: 0, fail: 0 })[pass ? 'pass' : 'fail']++;
    log(`${u.disp(v)}: ${line}`);
  }
}

const pass = results.filter((r) => r.pass).length, fail = results.length - pass;
log('---');
for (const [g, c] of Object.entries(byGroup)) log(`${g}: ${c.pass} of ${c.pass + c.fail}`);
log(`GATES: pass=${pass} fail=${fail} of ${results.length} (label ${label}, Lovable build ${build})`);
writeFileSync(path.join(REPO, outDir, 'summary.json'), JSON.stringify({ label, lovable: LOVABLE, build, lookups: lk, pass, fail, results }, null, 1));
process.exit(fail ? 1 : 0);

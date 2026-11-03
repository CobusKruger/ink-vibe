/**
 * Determinism runners. Each check = 3 captures that must be byte-identical (determinism-check.sh) + a `verify` of the
 * first capture against a live read. Prints one line per failure and a RUN summary. Exit 0 only if all pass.
 *   tools/fidelity/run.sh node tools/fidelity/accept/determinism.mjs lees|lovable [--label L] [--ref-dir D]
 * lees:   every Lees capture, INK and Lovable, signed in and out, 3 viewports (3 types x 2 sides x 2 x 3 = 36).
 *         
 * lovable: every stored Lovable reference capture (parameters read from its _meta) in the ref dir. Lovable-side noise
 *         (animations, hover timing) makes some of these fail on every machine; compare against the previous count.
 */
import { readFileSync, readdirSync, writeFileSync, mkdirSync } from 'node:fs';
import { spawnSync } from 'node:child_process';
import path from 'node:path';
import { REPO, gatesFile, INK_AUTH, LOVABLE_AUTH, log, die, neededLookups, resolveLookups, expand, ensureSessions, lovableBuild } from './common.mjs';

const argv = process.argv.slice(2);
const mode = argv[0];
const opt = (n, d = null) => { const i = argv.indexOf(`--${n}`); return i >= 0 ? argv[i + 1] : d; };
const label = opt('label', 'det');
const refDir = opt('ref-dir', 'tmp/fidelity-captures/accept/ref');
const BASH = process.env.BASH_EXE || 'bash';
const env = { ...process.env };

function sh(args) {
  const r = spawnSync(BASH, args, { cwd: REPO, env, encoding: 'utf8', maxBuffer: 256 * 1024 * 1024 });
  return `${r.stdout || ''}${r.stderr || ''}`.trim().split('\n').slice(-1)[0];
}
const detCmd = (url, side, page, vp, expect, flags) => sh(['tools/fidelity/determinism-check.sh', url, side, page, String(vp), '3', expect, ...flags]);
const verifyCmd = (cap, authFlags) => sh(['tools/fidelity/run.sh', 'verify', '--capture', cap, '--sample', '30', ...authFlags]);

const fails = [];
let pass = 0, fail = 0;
function check(name, d, v) {
  const ok = /GATE PASSED/.test(d) && /VERIFY PASSED/.test(v);
  if (ok) pass += 1; else { fail += 1; fails.push(`FAIL ${name} | ${d} | ${v}`); log(fails.at(-1)); }
}

await lovableBuild();
ensureSessions();

if (mode === 'lees') {
  const gates = gatesFile.gates.filter((g) => g.group === 'lees');
  const units = expand(gates, await resolveLookups(neededLookups(gates)));
  for (const u of units) for (const v of u.vps) for (const side of ['lovable', 'ink']) {
    // lees gates list both auth states as separate units; run each side with its own session file
    const auth = u.auth ? (side === 'ink' ? INK_AUTH : LOVABLE_AUTH) : null;
    const af = auth ? ['--auth', auth] : [];
    const url = side === 'ink' ? u.inkUrl : u.lovableUrl;
    const suf = u.auth ? '-auth' : '';
    const d = detCmd(url, side, u.g.page, v, '[data-audit-id=reading-header]', [...af, '--scope', 'main', '--scope-label', 'main']);
    const cap = `tmp/determinism/${side}-${u.g.page}@${v}${suf}-scope-main/run1/${side}/${u.g.page}@${v}${suf}-scope-main.ndjson`;
    check(`${side} ${u.g.page}@${v}${suf}`, d, verifyCmd(cap, af));
  }
  log(`RUN ${label}: lees pass=${pass} fail=${fail} (of ${pass + fail} checks = determinism + verify)`);
} else if (mode === 'lovable') {
  const dir = path.join(REPO, refDir, 'lovable');
  const files = readdirSync(dir).filter((f) => f.endsWith('.ndjson')).sort();
  if (!files.length) die(`no Lovable reference captures in ${dir}: run gate-all.sh first`);
  for (const f of files) {
    const m = JSON.parse(readFileSync(path.join(dir, f), 'utf8').split('\n')[0]);
    if (m.side !== 'lovable') continue;
    const af = m.auth ? ['--auth', LOVABLE_AUTH] : [];
    const cl = (m.clicks || []).flatMap((c) => ['--click', c]).concat(m.state ? ['--state', m.state] : []);
    const sc = m.scope ? ['--scope', m.scope, '--scope-label', m.scopeLabel] : [];
    const d = detCmd(m.url, 'lovable', m.page, m.viewport, '', [...af, ...cl, ...sc]);
    check(f, d, verifyCmd(`${refDir}/lovable/${f}`, af));
  }
  log(`RUN ${label}: LOVABLE pass=${pass} fail=${fail} of ${pass + fail}`);
} else die('usage: determinism.mjs lees|lovable [--label L] [--ref-dir D]');

mkdirSync(path.join(REPO, 'tmp/fidelity-captures/accept', label), { recursive: true });
writeFileSync(path.join(REPO, 'tmp/fidelity-captures/accept', label, `determinism-${mode}.txt`), `${[...fails, `${mode}: pass=${pass} fail=${fail}`].join('\n')}\n`);
process.exit(fail ? 1 : 0);

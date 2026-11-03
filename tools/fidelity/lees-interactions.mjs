/**
 * Interaction checks for the reading pages' two owner-approved features (2026-11-02):
 * the response upvote and the poem dedication. Run as `fidelity-capture`, never admin.
 *
 *   tools/fidelity/run.sh interactions [--post 68070] [--own-response 17665]
 *
 * What it does, and what it removes afterwards:
 *   - adds one TEMP response by an administrator (so the capture user is not its author),
 *     clicks its upvote signed in (count changes without a reload, survives a refresh,
 *     second click toggles off), checks the REST rules (no nonce, own response, missing
 *     response, one vote per member) and the logged-out disabled button; then deletes it
 *   - sets a dedication through the real admin meta-box save, checks the reading page shows
 *     "— text —" before the stanzas, clears it, checks it is gone; then restores the
 *     poem's previous dedication
 *   - submits a draft gedig on /skryf/ with a dedication (field shown for a gedig, hidden
 *     for a storie), checks the stored meta; then deletes the draft
 *
 * Needs tmp/fidelity-auth/ink.json (`run.sh login`) and the poem to have an own response
 * by the capture user (the seeded test responses; see docs/lovable-delta-2026-10/specs/02-lees-wenner.md).
 */
import { chromium } from 'playwright';
import { execFileSync } from 'node:child_process';
import fs from 'node:fs';

const arg = (name, dflt) => { const i = process.argv.indexOf('--' + name); return i > 0 ? process.argv[i + 1] : dflt; };
const POST = arg('post', '68070');
const OWN = arg('own-response', '17665');
const BASE = 'https://nuwe-ink.local';
const AUTH = arg('auth', 'tmp/fidelity-auth/ink.json');
const state = JSON.parse(fs.readFileSync(AUTH, 'utf8'));
const results = [];
const check = (name, ok, detail = '') => { results.push(ok); console.log((ok ? 'PASS ' : 'FAIL ') + name + (detail ? ' :: ' + detail : '')); };

function data(...a) {
  const out = execFileSync(process.execPath, ['tools/local.mjs', 'wp', 'eval-file', 'tools/fidelity/lees-interactions-data.php', ...a], { encoding: 'utf8', env: process.env });
  const kv = {};
  for (const line of out.split(/\r?\n/)) { const m = line.match(/^(id|type|status|dedication|value|removed|cleared)=(.*)$/); if (m) kv[m[1]] = m[2]; }
  return kv;
}

const browser = await chromium.launch();
let tempId = null;
let draftId = null;
const priorDedication = data('dedication-get', POST).value ?? '';

try {
  const permalink = await (async () => {
    const ctx = await browser.newContext({ ignoreHTTPSErrors: true });
    const page = await ctx.newPage();
    await page.goto(`${BASE}/?p=${POST}`);
    const url = page.url();
    await ctx.close();
    return url;
  })();

  // ---- upvote, signed in
  tempId = data('temp-create', POST).id;
  const btn = (page) => page.locator(`.ink-reaksies__item:has(button[data-ink-opstem="${tempId}"]) button.ink-reaksies__upvote`);
  const read = async (page) => {
    const b = btn(page);
    return { count: (await b.locator('[data-ink-opstem-telling]').textContent()).trim(), pressed: await b.getAttribute('aria-pressed'), disabled: await b.isDisabled() };
  };
  const waitCount = (page, n) => page.waitForFunction(([id, want]) => document.querySelector(`button[data-ink-opstem="${id}"] [data-ink-opstem-telling]`).textContent.trim() === want, [tempId, n], { timeout: 5000 });
  {
    const ctx = await browser.newContext({ storageState: state, ignoreHTTPSErrors: true });
    const page = await ctx.newPage();
    await page.goto(permalink);
    let s = await read(page);
    check('signed in: another member\'s response has an enabled upvote at 0, not pressed', !s.disabled && s.count === '0' && s.pressed === 'false', JSON.stringify(s));
    const own = page.locator('.ink-reaksies__item button.ink-reaksies__upvote:not([data-ink-opstem])');
    check('signed in: your own responses get a disabled upvote', (await own.count()) >= 1 && (await own.first().isDisabled()));

    let navs = 0; page.on('framenavigated', (f) => { if (f === page.mainFrame()) navs++; });
    const before = navs;
    await btn(page).click();
    await waitCount(page, '1');
    s = await read(page);
    check('click: count 0 -> 1, aria-pressed true, no reload', s.count === '1' && s.pressed === 'true' && navs === before, JSON.stringify(s));

    await page.reload();
    s = await read(page);
    check('after a refresh: still 1 and pressed (persisted)', s.count === '1' && s.pressed === 'true', JSON.stringify(s));

    await btn(page).click();
    await waitCount(page, '0');
    s = await read(page);
    check('second click toggles off: 0, not pressed', s.count === '0' && s.pressed === 'false', JSON.stringify(s));

    const rest = await page.evaluate(async ({ id, own: ownId }) => {
      const cfg = window.inkGemeenskapsreaksie;
      const post = (i, nonce) => fetch(cfg.upvoteUrl.replace('/0/opstem', '/' + i + '/opstem'), { method: 'POST', credentials: 'same-origin', headers: nonce ? { 'X-WP-Nonce': nonce } : {} }).then(async (r) => ({ status: r.status, body: await r.json() }));
      return { noNonce: (await post(id, null)).status, own: (await post(ownId, cfg.nonce)).status, missing: (await post(99999999, cfg.nonce)).status, twice: [(await post(id, cfg.nonce)).body.count, (await post(id, cfg.nonce)).body.count] };
    }, { id: tempId, own: OWN });
    check('REST: no nonce is refused', [401, 403].includes(rest.noNonce), String(rest.noNonce));
    check('REST: your own response is 403', rest.own === 403, String(rest.own));
    check('REST: a missing response is 404', rest.missing === 404, String(rest.missing));
    check('REST: two toggles give 1 then 0 (one vote per member)', rest.twice[0] === 1 && rest.twice[1] === 0, JSON.stringify(rest.twice));
    await ctx.close();
  }
  // ---- upvote, logged out
  {
    const ctx = await browser.newContext({ ignoreHTTPSErrors: true });
    const page = await ctx.newPage();
    await page.goto(permalink);
    const b = page.locator('button.ink-reaksies__upvote').first();
    check('logged out: the upvote is a disabled button that shows the count', (await b.isDisabled()) && (await b.getAttribute('data-ink-opstem')) === null);
    const status = await page.evaluate(async (id) => (await fetch(`/wp-json/ink/v1/gemeenskapsreaksie/${id}/opstem`, { method: 'POST' })).status, tempId);
    check('logged out: REST upvote is refused', [401, 403].includes(status), String(status));
    await ctx.close();
  }

  // ---- dedication on the reading page
  {
    const text = 'vir die uur voor dagbreek';
    const set = data('dedication-set', POST, text).value;
    check('admin meta-box save stores the dedication', set === text, set);
    const ctx = await browser.newContext({ storageState: state, ignoreHTTPSErrors: true });
    const page = await ctx.newPage();
    await page.goto(permalink);
    const d = page.locator('[data-audit-id="gedig-dedication"]');
    const shown = (await d.count()) === 1 ? (await d.textContent()).trim() : null;
    const before = await page.evaluate(() => { const e = document.querySelector('[data-audit-id="gedig-dedication"]'); return !!e && !!(e.compareDocumentPosition(document.querySelector('[data-audit-id="gedig-stanzas"]')) & Node.DOCUMENT_POSITION_FOLLOWING); });
    check('reading page shows "— text —" before the stanzas', shown === `— ${text} —` && before, String(shown));
    data('dedication-clear', POST);
    await page.reload();
    check('hidden when empty: no dedication element', (await page.locator('[data-audit-id="gedig-dedication"]').count()) === 0);
    await ctx.close();
  }

  // ---- dedication on /skryf/
  {
    const title = 'ZZ interaksietoets ' + Date.now();
    const ctx = await browser.newContext({ storageState: state, ignoreHTTPSErrors: true });
    const page = await ctx.newPage();
    await page.goto(`${BASE}/skryf/`);
    const field = page.locator('#ink-skryf-toewyding');
    check('skryf: the dedication field shows for a gedig', await field.isVisible());
    await page.locator('.ink-skryf-type--storie').click();
    check('skryf: it hides for a storie', !(await field.isVisible()));
    await page.locator('.ink-skryf-type--gedig').click();
    check('skryf: it shows again for a gedig', await field.isVisible());
    await page.fill('#ink-skryf-title', title);
    await page.fill('#ink-skryf-body', 'reël een\nreël twee');
    await field.fill('vir my ma');
    await page.locator('[data-audit-id="skryf-draft-btn"]').click();
    await page.waitForURL(/ink_skryf=konsep-gestoor/, { timeout: 15000 });
    const found = data('draft-find', title);
    draftId = found.id && found.id !== '0' ? found.id : null;
    check('skryf: the draft gedig is saved with the dedication', found.type === 'gedig' && found.dedication === 'vir my ma', JSON.stringify(found));
    await ctx.close();
  }
} finally {
  if (tempId) console.log('cleanup: temp response removed=' + data('temp-remove', tempId).removed);
  if (draftId) console.log('cleanup: draft removed=' + data('draft-remove', draftId).removed);
  if (priorDedication) data('dedication-set', POST, priorDedication); else data('dedication-clear', POST);
  console.log('cleanup: dedication restored to ' + JSON.stringify(priorDedication));
  await browser.close();
}
const failed = results.filter((ok) => !ok).length;
console.log(`${results.length - failed}/${results.length} passed`);
process.exit(failed ? 1 : 0);

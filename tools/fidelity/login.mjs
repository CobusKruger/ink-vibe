/**
 * D6 — produce a reusable authenticated session for capture.
 *
 * Logs in once through the real form and writes Playwright `storageState`
 * (cookies + localStorage) to disk. `capture.mjs --auth <file>` replays it, so
 * no capture run performs a login.
 *
 * Usage:
 *   login.mjs --side ink --url https://nuwe-ink.local/wp-login.php \
 *             --user fidelity-capture --pass … [--out tmp/fidelity-auth/ink.json]
 */

import { chromium } from 'playwright';
import { mkdir } from 'node:fs/promises';
import path from 'node:path';

function parseArgs(argv) {
  const a = {};
  for (let i = 2; i < argv.length; i += 2) a[argv[i].slice(2)] = argv[i + 1];
  for (const r of ['side', 'url', 'user', 'pass']) {
    if (!a[r]) throw new Error(`Missing --${r}`);
  }
  a.out ??= `tmp/fidelity-auth/${a.side}.json`;
  return a;
}

const args = parseArgs(process.argv);
const browser = await chromium.launch();
const ctx = await browser.newContext({ ignoreHTTPSErrors: true, viewport: { width: 1440, height: 900 } });
const page = await ctx.newPage();

await page.goto(args.url, { waitUntil: 'networkidle', timeout: 60_000 });

if (args.side === 'ink') {
  // The themed login form (`/meld-aan`). `wp-login.php` is intercepted and
  // redirected here by ink-core's Accounts\AuthRedirects, so target this form.
  // It renders wp_login_form(): fields keep the core ids, but the submit is a
  // <button name="wp-submit">, not core's <input id="wp-submit">.
  await page.fill('#user_login', args.user);
  await page.fill('#user_pass', args.pass);
  await Promise.all([
    page.waitForLoadState('networkidle'),
    page.click('[name="wp-submit"]'),
  ]);
} else {
  // Supabase email/password form — field names vary, so target by type.
  await page.fill('input[type="email"]', args.user);
  await page.fill('input[type="password"]', args.pass);
  await page.click('button[type="submit"]');
  await page.waitForLoadState('networkidle');
}

// Prove the session took, rather than trusting the navigation. `document.body`
// can be null here if the response was a redirect still in flight.
await page.waitForLoadState('domcontentloaded').catch(() => {});
const signedIn = await page.evaluate(() => ({
  url: location.href,
  body: document.body ? document.body.innerText.slice(0, 300) : '(no body)',
}));

const cookies = await ctx.cookies();
const wpLoggedIn = cookies.some((c) => c.name.startsWith('wordpress_logged_in'));

if (args.side === 'ink' && !wpLoggedIn) {
  console.error('LOGIN FAILED — no wordpress_logged_in cookie was set.');
  console.error(signedIn.body);
  await browser.close();
  process.exit(1);
}

await mkdir(path.dirname(args.out), { recursive: true });
await ctx.storageState({ path: args.out });

console.log(`wrote ${args.out}`);
console.log(`  cookies: ${cookies.length}${args.side === 'ink' ? ` (wordpress_logged_in: ${wpLoggedIn})` : ''}`);
await browser.close();

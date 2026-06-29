# Test-pyramid plan (Story 18.8, NFR-9 / §14.17)

The buildout of the architecture's test pyramid. The **unit** layer is built (1100+
Pest tests, mocked); this plan covers the **integration**, **E2E**, **CI** and
**live-leak** layers and names the seams each must cover.

> Source of record: `_bmad-output/planning-artifacts/epics.md` Story 18.8;
> `_bmad-output/project-context.md` (test pyramid, PayFast sandbox only).

## Where each layer runs

| Layer | Tool | Runs | Bootstrap |
|---|---|---|---|
| Unit (many) | Pest + Brain Monkey / WP_Mock | anywhere `composer install` works (local + CI) | `tests/bootstrap.php` (WP mocked) |
| Integration (fewer) | Pest + WP test library, inside `wp-env` | CI (and locally with `wp-env`) | `tests/Integration/bootstrap.php` (real WP) via `phpunit.integration.xml` |
| E2E (thin) | Playwright + `@wordpress/e2e-test-utils-playwright` | CI (and locally with `wp-env` + browsers) | `playwright.config.js` |
| Live English-leak (NFR-1 Layer 2) | `tools/leak-scan/scan-rendered.php` + `wp i18n` | CI + cron (needs a running site) | crawl orchestration |

`composer test:unit` is the only layer that runs in the mocked-unit sandbox;
`composer test:integration`, the Playwright E2E and the live crawl need `wp-env` /
browsers / a running site and execute in CI.

## Integration seams (real WP, `wp-env`)

The load-bearing theme↔plugin / plugin↔platform seams the architecture names:

- **membership ⇒ submit / expired ⇒ denied** (`tests/Integration/Entitlement/SubmissionGateTest.php`)
  — `Entitlement\Api::can_submit()`. The deny path (logged-out / no active membership →
  fail-safe `false`) runs in the ink-core-only `wp-env`; the **active-member ⇒ true**
  path needs WooCommerce Memberships + a PayFast-sandbox purchase → covered by the E2E
  journey. Pins THE conflation rule (gate reads membership only, never tier).
- **tier write ⇒ meta + log** (`tests/Integration/Tiers/TierWriteTest.php`) —
  `Tiers\Api::promote()` persists `ink_writer_tier` meta **and** appends a
  `PromotionLog` row to the custom table. Fully runs in the ink-core-only `wp-env`.
- **comments disabled, structured engagement writes** (`tests/Integration/Engagement/CommentInsertionTest.php`)
  — `wp_insert_comment` for the sanctioned `ink_reaksie` type bypasses `comments_open`.

## E2E critical journey (Playwright, PayFast SANDBOX)

`tests/e2e/critical-journey.spec.js` — the one thin journey:
**register → buy lidmaatskap via PayFast sandbox → submit → publish → read/react → renew.**
The smoke legs (reachability, Afrikaans `lang`, registration form present) are active;
the paid legs are wired as the contract and enabled once staging seeds the membership
product + PayFast sandbox creds are in the CI env. **Never the live ZAR gateway.**

## Live English-leak gate, Layer 2 (NFR-1)

The runtime counterpart to the static `copy:scan` ratchet:

- **Detector** (`Ink\Tests\Support\RenderedLeakScanner`, unit-tested) — flags
  suspected-English visible text in rendered HTML against an Afrikaans/brand allowlist.
- **Crawl** (`tools/leak-scan/scan-rendered.php`) — fetches the key front-end pages on a
  running site and runs the detector; pair with `wp i18n make-pot` untranslated counts.
- **Scope:** the §12 leak vectors incl. the 12A.0 form-letter store; **admin stays
  English** (§14.14). Re-runs after ungated core/plugin updates (CI + cron). Triage by a
  human — never auto-translate (AI Afrikaans forbidden). See `docs/i18n-leak-vectors.md`.

## The BuddyPress + client-JS integration tail (recurring carry-forward)

Integration/E2E must reach the surfaces the unit layer **cannot** (they live in plugin
internals or the browser), historically deferred — enumerated here so they are not lost:

- **BuddyPress seams** — profile fields/display, the members directory, the
  notifications surface INK composes (Story 9.9), and the friendship→follow migration
  result (BP Friends are OFF; the custom follow graph). Verify INK's BP integration
  points render Afrikaans and behave, without re-testing BP internals.
- **Real3D Flipbook viewer JS** — the InkPols flipbook controls are plugin JavaScript;
  their Afrikaans comes from the committed `.json` (Story 17.2). E2E asserts the viewer
  loads and the controls are Afrikaans (the §12 plugin-JS leak vector).
- **Interactivity-API / enqueued client JS** — INK's own front-end interactivity
  (reactions, reading-list, follow toggle, search) — E2E asserts the client behaviour +
  that any JS-emitted strings are Afrikaans (script translations, not `.mo`).
- **WooCommerce account / checkout overrides** — the `ink-foundation` template overrides
  for the Woo account + the PayFast checkout return — E2E (part of the paid journey).

## CI wiring (`.github/workflows/ci.yml`)

- `quality` — stan / cs / deptrac / `copy:scan` (Layer-1) / unit (every push + PR).
- `integration` — `wp-env` + the seam tests + the **live leak Layer-2 crawl** (main).
- `e2e` — Playwright smoke journey, PayFast **sandbox** (main).

Risk-based depth (NFR-9): full integration + E2E on `main`; lighter elsewhere.

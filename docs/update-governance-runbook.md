# Update-governance runbook (Story 18.7, NFR-7 / NFR-1)

How core/plugin updates are applied without breaking custom overrides or leaking
English. Updates are the main way a translated, customised WordPress site regresses;
this is the standing process that prevents it.

> Source of record: `_bmad-output/planning-artifacts/epics.md` Story 18.7;
> `_bmad-output/project-context.md` (update governance); `wp-content/languages/README.md`
> (committed-translation home, §14.13).

## What the code does (`Ink\I18n\TranslationAudit`)

- `wp ink audit-translations` checks that the premium-plugin Afrikaans translations
  are present in `wp-content/languages/` (the committed home). Run it **after every
  plugin update** as the post-update recheck.
- Expected set (filterable via `ink_i18n_required_translations`; exact filenames
  confirmed on staging): WooCommerce Memberships, PayFast gateway, Real3D Flipbook —
  the premium/niche plugins with no complete community language pack.
- It only checks **presence**; new-string detection is the leak scan (below). It never
  authors translations (AI Afrikaans is forbidden).

## Update classification (risk-based depth)

| Update kind | Gate | Tests |
|---|---|---|
| **Major** core/plugin version | **Staging first** | Full regression on `ink-foundation` custom templates/patterns + translation refresh + leak scan |
| **Minor / security / host-forced** | Can't always gate | Smoke test + rely on the standing leak scan + Patchstack alerts |

## Major-update procedure (on staging)

1. Apply the update on **staging**.
2. **Regression** the custom overrides: every `ink-foundation` template/pattern that
   overrides plugin output (Woo account/checkout, BuddyPress profile, etc.) still
   renders correctly and stays Afrikaans.
3. **Translation refresh:**
   - Prefer a complete **w.org community language pack** where one exists (core,
     WooCommerce) — let WP auto-update it into `wp-content/languages/plugins/`.
   - For premium plugins (no pack), **re-author** the new strings on staging with Loco,
     **commit** the updated `.po/.mo`/`.json` to `wp-content/languages/`.
4. **Leak scan:** run `composer copy:scan` (static) + the 18.8 live `wp i18n` /
   page-crawl layer — confirm no new untranslated front-end strings (the §12 vectors:
   validation/status/error messages, transactional emails, plugin JS, REST/AJAX).
5. **Recheck committed translations:** `wp ink audit-translations` → must report
   `Alle premie-inprop vertalings is teenwoordig`.
6. **Deploy** to production (committed translations load without Loco). Full cache
   purge (18.5). Re-run `wp ink audit-translations` + `wp ink audit-production` (18.6)
   on production.

## Hard rules

- **Loco never on production** (18.6 enforces). Author on staging, commit, redeploy —
  **never hand-edit translations on production**.
- **No English `ink-core` `.mo`** (the Afrikaans-source admin split, §14.15).
- **No AI Afrikaans** — human native-speaker authoring only.
- New strings from an ungated minor/security update that slips through are caught by the
  standing leak scan, then authored on staging + redeployed — not patched live.

## Cross-references

- Security stack (Cloudflare/Patchstack/staging-gate): `docs/security-stack-runbook.md` (18.3).
- Production hygiene (no Loco/diagnostic tools on prod): `docs/production-hygiene-runbook.md` (18.6).
- Standing leak gate (static + live): `docs/i18n-leak-vectors.md` (17.4) + 18.8 CI/cron.

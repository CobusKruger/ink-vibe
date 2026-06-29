# Production-hygiene runbook (Story 18.6, NFR-7)

Production runs **only** what it needs. Development, diagnostic and migration tools
are staging/authoring-only and must never be active on production. This story makes
that a standing, automated gate.

> Source of record: `_bmad-output/planning-artifacts/epics.md` Story 18.6;
> `_bmad-output/project-context.md` (production-hygiene rule — the named tools).

## What the code does (`Ink\Security\ProductionHygiene`)

- On a **production** environment (`wp_get_environment_type() === 'production'`) it
  surfaces any forbidden active plugin via an **admin notice** to administrators and
  the `wp ink audit-production` CLI.
- Off-production (staging/local) it is inert — these tools belong there.
- The forbidden set is a single source (`FORBIDDEN_PLUGINS`), extensible via the
  `ink_security_forbidden_plugins` filter.

```
wp ink audit-production    # success when clean; warns + lists any forbidden active plugin
```

## Staging/authoring-only plugins (NEVER active on production)

- **Loco Translate** — translation authoring (commit `.po/.mo`; production loads them
  without Loco — see the update-governance runbook, 18.7).
- **Code Snippets** — code authoring (production code is committed to the repo).
- **WP Migrate (Lite/Pro)** — DB/file migration (one-off, staging).
- **String Locator** — source search (authoring/debug).
- **Simple CSS / Simple Custom CSS** — CSS authoring (production CSS is in the theme).
- **Query Monitor / Debug Bar** — diagnostics (never on production).

(Extend via `ink_security_forbidden_plugins` if more staging tools are adopted.)

## Deploy checklist

**Pre-deploy (on staging):**
- Author translations with Loco → commit `.po/.mo` → confirm they load.
- Move any Code Snippets logic into committed `ink-core`/theme code.
- Run `wp ink audit-production` against the staging build with the env temporarily
  set to production-like to confirm the would-be production plugin set is clean.

**Post-deploy (on production):**
- Confirm `wp_get_environment_type()` returns `production`.
- Run `wp ink audit-production` → must report
  `Produksie is skoon`.
- Confirm no admin "production-higiëne" notice appears for any administrator.

**Standing gate:** schedule `wp ink audit-production` (cron) and/or rely on the admin
notice so a later stray activation is caught. Re-run after every deploy and after any
plugin install.

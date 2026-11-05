# INK Staging Setup Guide

**The one definitive, step-by-step runbook for standing up a fully-functioning INK site in a staging environment.**

This document takes you from nothing to a working INK site: a WordPress install, pointed at a fresh copy of production data, with the exact plugin set installed and configured, the database transformed to the new content model, and the first-party `ink-core` plugin + `ink-foundation` theme deployed and active.

Follow the phases **in order**. Ordering matters — several steps depend on earlier ones (notably: `ink-core` must be active before any migration command runs, and redirect generation must run after all content is re-typed).

> **Scope.** This is the environment-standup + migration runbook. It references, but does not replace, the deep-dive docs: [migration-plan.md](./migration-plan.md) (data-domain rationale), [plugin-transition-guide.md](./plugin-transition-guide.md) (plugin survey), and the Epic 18 runbooks ([security-stack-runbook.md](./security-stack-runbook.md), [caching-runbook.md](./caching-runbook.md), [seo-rank-math-runbook.md](./seo-rank-math-runbook.md), [redirect-integrity-runbook.md](./redirect-integrity-runbook.md), [production-hygiene-runbook.md](./production-hygiene-runbook.md), [update-governance-runbook.md](./update-governance-runbook.md), [analytics-provider-decision.md](./analytics-provider-decision.md)). Where the plugin-transition survey (June 2026) and the later runbooks disagree, **the runbooks win** — the deltas are baked into the checklist below.

---

## At a glance — the phase map

| Phase | What happens | Result |
|-------|--------------|--------|
| **0** | Gather inputs | You have every artifact before you start |
| **0.5** | Export the production database + media | You hold a `.sql` file and an `uploads/` folder on your computer |
| **1** | Provision WordPress (Local site, PHP 8.3) | Empty running WP |
| **2** | Load the production database + files | Staging = a faithful copy of live |
| **3** | Deploy & activate `ink-core` + `ink-foundation` | New content model registered; `wp ink` CLI available |
| **4** | Install / prune the third-party plugin set | Correct plugin surface, legacy plugins gone |
| **5** | Run the migration toolkit (15 `wp ink` commands, in order) | Data transformed to new model + redirects built |
| **6** | Configure plugins (Rank Math, cache, analytics, 2FA, Redirection) | Behavioural config in place |
| **7** | Manual data entry (sponsors) and the InkPols CSV check | Editorial content complete |
| **8** | Verify & smoke-test | Signed-off, functioning site |

**Runtime prerequisites (all phases):** PHP **8.3+**, WordPress **7.0** (the version this new site is built for — you do *not* need to match the older production version; see the note in Phase 1), WP-CLI, Composer, and Local by Flywheel (or an equivalent LAMP stack).

---

## Phase 0 — Gather your inputs

Do **not** start until you physically have all of these. Chasing a missing artifact mid-migration is where mistakes happen.

- [ ] **A production database dump** (`.sql`) — a *fresh* export from production. **Don't have one yet? That's what Phase 0.5 below is for** — it walks you through three ways to produce this file, no prior WordPress experience assumed.
- [ ] **The production `wp-content/uploads/` directory** — all media (audio, PDFs, images). Migrates as-is; the database references it. **Phase 0.5 also covers how to download this folder.**
- [ ] **A `wp-content/plugins/` folder** — a new Local site has no plugins, and WordPress silently deactivates any the database lists but cannot find. Prod's folder, or the trimmed set from an earlier rehearsal site (Phase 4b).
- [ ] Keep these three outside the repo, e.g. in `../live-import/` beside it. Never commit them.
- [ ] **Premium plugin installer** (you cannot get this from wp.org):
  - [ ] **WooCommerce Memberships** — from woocommerce.com account
- [ ] **Real3D Flipbook** — the InkPols PDF viewer, carried over from the existing site (copy the plugin folder from your previous staging/production install; INK uses the edition already in place — *not* a separate CodeCanyon premium purchase).
- [ ] **No writer-tier CSV needed.** Gradering is derived from the old result posts by `wp ink derive-tiers`, using the migration-data CSVs (see [migration-runbook.md](./migration-runbook.md)). All migration data is kept outside the repo ([migration-data.md](./migration-data.md)); `INK_MIGRATION_DATA_DIR` in `wp-config.php` names the folder.
- [ ] **Decisions confirmed before you run anything** (each blocks a migration step):
  - [ ] **Post classification** — is the content-type category (`Gedig`/`Verhaal`/`Artikel`) reliable enough to auto-classify posts, or do some need manual bulk-editing first? (Unrecognised/conflicting → `skryfwerk` catch-all.)
  - [ ] **Challenge history scope** — migrate all historical challenges, or start the `uitdaging` CPT clean from launch and leave history as a flat archive? (Recommended: clean from launch.)
  - [ ] **BuddyPress Groups** — on or off? Default is **off** → then do **not** install Invite Anyone.
  - [ ] **Legacy profile meta keys** — `migrate-users` cleans legacy profile meta only if you supply the confirmed keys; its default list is empty (it will not guess).
  - [ ] **Analytics provider** — Burst Statistics **or** Independent Analytics (final pick, see [analytics-provider-decision.md](./analytics-provider-decision.md)).
  - [ ] **Staff 2FA plugin** — pick one (e.g. Two-Factor or WP 2FA) that can expose per-user status to the `ink_security_user_has_2fa` filter.

---

## Phase 0.5 — Export the production database and media

This is the step the checklist above assumes you already know how to do — so here it is in full. Your goal is to end up with **two things saved on your own computer**:

1. **A database file** named something like `production.sql` (a plain-text file containing every row of the live site's database).
2. **A copy of the `uploads` folder** (every image, audio file, and PDF that editors have ever uploaded — physically stored on the live server under `wp-content/uploads/`).

**Why two separate things?** WordPress splits its data in two places. The *database* holds all the text, settings, users, and — crucially — *references* to the media (e.g. "the audio for this poem lives at `.../uploads/2024/05/gedig.mp3`"). The *actual media files* live on disk in `uploads/`, not in the database. You need both, or you get a site full of broken images and missing audio.

> **Answering the direct question: "Is this another plugin, or can WP-CLI do it?"** Either works — pick the method that matches the access you already have to the **production** server. You do **not** have to install anything on production if you have SSH access (Method C) or a hosting control panel (Method B). A plugin (Method A) is simply the friendliest route if all you have is the WordPress admin login. All three produce the same two artifacts.

Pick **one** of the three methods below.

### Method A — A migration plugin (easiest; only needs the WordPress admin login)

Use this if the only access you have to production is the `/wp-admin` dashboard login. You install a plugin **on the production site**, let it package everything up, and download the result.

**Recommended: WP Migrate (Lite)** — the same tool Phase 2 later uses to *import*, so it's worth learning once.

1. Log in to the **production** site's `/wp-admin`.
2. Go to **Plugins → Add New**, search for **"WP Migrate"** (by WP Engine / Delicious Brains), click **Install Now**, then **Activate**.
3. Go to **Tools → WP Migrate**. Choose an **Export** (the Lite version exports a `.sql` file; media is handled separately in Lite — see the note below).
4. Run the export and **download the `.sql` file** it produces. That's artifact #1.
5. For the media, WP Migrate **Lite does not bundle uploads** — download the `uploads` folder using Method B or C's file step, **or** use an all-in-one alternative below.
6. When you're done, **deactivate and delete the plugin from production** (leaving migration plugins active on a live site is a security and performance liability).

**All-in-one alternative: Duplicator or All-in-One WP Migration.** These bundle the database *and* the media into a single archive, so you get everything in one download. The trade-off: the archive is **not** a plain `.sql` file — it's a proprietary package that must be *restored* with the same plugin's installer, which doesn't fit the clean "drop a `.sql` in + copy an `uploads/` folder" flow that Phase 2 describes. If you use one of these, you effectively replace Phase 2's manual DB import with the plugin's own restore wizard, then continue from Phase 3 unchanged. For a first-timer that's actually a reasonable path — just be aware you're swapping *how* Phase 2 happens, not skipping it.

> **Watch the size limit.** Free migration plugins often cap uploads/imports (e.g. All-in-One WP Migration's free tier limits import size). If production is large (lots of audio/PDFs), the database export (Method A) plus a separate file download (Method B/C) is more reliable than a single giant archive.

### Method B — The hosting control panel (no terminal, no plugin)

Use this if you can log in to the production **host's** control panel (cPanel, Plesk, or a managed-host dashboard like SiteGround/Bluehost). This is often the most reliable route for a non-developer.

**Get the database (`.sql`):**

1. Log in to the host control panel and open **phpMyAdmin** (usually under a "Databases" section).
2. In phpMyAdmin's left column, click the site's database name. (Not sure which one? It's the value of `DB_NAME` in the site's `wp-config.php` file — you can view that file in the host's **File Manager** at the site root.)
3. With the database selected, click the **Export** tab at the top.
4. Leave the method on **"Quick"** and the format on **"SQL"**, then click **Export / Go**.
5. Your browser downloads a `.sql` file. That's artifact #1. (For a big database, choose **"Custom"** and tick **"gzip"** compression to get a smaller `.sql.gz` — Phase 2 can import that too.)

**Get the media (`uploads/`):**

1. In the host control panel, open **File Manager**.
2. Navigate to the site root, then into `wp-content/`.
3. Select the **`uploads`** folder, choose **Compress → zip**, and download the resulting `.zip`.
4. Unzip it on your computer — you now have the `uploads/` folder. That's artifact #2.

(No File Manager? Use an **SFTP/FTP client** like [FileZilla](https://filezilla-project.org/) with the host's FTP credentials, browse to `wp-content/uploads/`, and drag it to your computer.)

### Method C — WP-CLI over SSH (most technical; fastest and most robust for large sites)

Use this if you have **SSH (terminal) access** to the production server. This is the developer's route and installs nothing on production.

1. **SSH into the production server** and change into the site's WordPress directory (the folder containing `wp-config.php`):
   ```bash
   ssh youruser@prod-server.example.com
   cd /path/to/production/public_html   # wherever wp-config.php lives
   ```
2. **Export the database** with WP-CLI (already installed on most managed hosts; if `wp` isn't found, the host's docs will say how to enable it):
   ```bash
   wp db export production.sql          # writes production.sql in the current folder
   # For a large DB, compress on the fly:
   wp db export - | gzip > production.sql.gz
   ```
3. **Bundle the uploads** into a single archive so it's one quick download:
   ```bash
   tar -czf uploads.tar.gz wp-content/uploads
   ```
4. **Download both files to your computer.** From a terminal *on your own machine* (not the SSH session):
   ```bash
   scp youruser@prod-server.example.com:/path/to/production/public_html/production.sql .
   scp youruser@prod-server.example.com:/path/to/production/public_html/uploads.tar.gz .
   ```
   Then unpack the media locally: `tar -xzf uploads.tar.gz` gives you the `uploads/` folder.
5. **Tidy up** — delete `production.sql` and `uploads.tar.gz` from the production server afterwards so you don't leave a full database dump sitting in a web-accessible folder:
   ```bash
   rm production.sql uploads.tar.gz   # run this on the production server
   ```

### Managed-host shortcut

If production is on a managed WordPress host (**WP Engine, Kinsta, Flywheel, Pantheon**, etc.), their dashboard usually has a **"Backup" / "Download backup"** button that produces a full archive containing both the database and the files. That single download is a perfectly good source — extract it and you'll find the `.sql` and the `uploads/` folder inside. Check your host's docs for "download a backup".

### Security note (applies to every method)

A production `.sql` dump contains **all user accounts, password hashes, email addresses, and private messages**. Treat it like a password:

- Keep it on your own machine or private storage only — never a public folder, shared link, or the site's own `uploads/` directory.
- Delete any copies you left on the production server (Method C step 5).
- When you later place this dump inside the staging `uploads/`, put it under a **private** subfolder (the guide uses `wp-content/uploads/private/`) that isn't linked from the site.

**Result of Phase 0.5:** you now have `production.sql` (or `.sql.gz`) and an `uploads/` folder saved locally. Return to the Phase 0 checklist, tick those two boxes, then continue to Phase 1.

---

## Phase 1 — Provision the WordPress environment

The project targets **Local by Flywheel** for the dev site **nuwe-ink** (`https://nuwe-ink.local`), on macOS or Windows. WP core, wp-admin, and wp-includes are **not** in the repo — Local provides them. (The repo's `.wp-env.json` is for the automated test harness only; it is not the dev runtime.) The repo's tools find the site through Local's own registry; `node tools/local.mjs info` shows what they found.

1. **Create a fresh Local site** so you start from clean core.
   - Site name: **`nuwe-ink`** (domain `nuwe-ink.local`). The tools look for this name; another name needs `"site"` in `tools/local.config.json`.
   - **PHP 8.3** (required by `ink-core`, `ink-foundation`, and the whole toolchain). Local may default to an older PHP: change it on the site's Overview tab.
   - **HTTPS on:** in Local, SSL → Trust. The capture tooling uses `https://nuwe-ink.local`.
   - **WordPress version:** use **7.0** — the version this new site is built for. You do **not** need to match production's (older) version. WordPress only breaks when you import a *newer* database into an *older* WordPress; the reverse — an older production database into a newer WP 7 — is the normal, supported path: WordPress automatically upgrades the old database schema forward on import (see Phase 2, step 2b). Uploads are plain files and are version-independent. (Aside: the repo declares WP 7.0 in headers/wp-env while the static-analysis stubs pin 6.7 — that discrepancy only affects code analysis, never the running site.)
   - Web server: Local offers nginx or Apache; either is fine (LiteSpeed Cache then runs without its server layer).
2. Start the site in Local.
3. Confirm WP-CLI works against the site, from the repo: `node tools/local.mjs wp core version` should print the version.

> **Where do you type the `wp …` commands in this guide?** From the repo root, as `node tools/local.mjs wp …` (e.g. `node tools/local.mjs wp ink migrate-all`). Plain `wp` cannot reach Local's database (wp-config.php says `DB_HOST = 'localhost'`; the helper passes Local's port or socket). Local's own "Open site shell" also works. Tool install: `tools/setup-windows.ps1` on Windows; see [testing-and-quality-harness.md](./testing-and-quality-harness.md) for macOS.

---

## Phase 2 — Load the production database and files

Goal: the dev site is a faithful copy of live, adjusted only for its new URL. **Phases 2 and 3 are one script**, `tools/rehearsal-prep.mjs`; the full procedure, flags and notes are in [migration-runbook.md](./migration-runbook.md). In short, with the Phase 0 inputs in `../live-import/`:

```bash
node tools/rehearsal-prep.mjs --sql ../live-import/prod.sql --uploads ../live-import/uploads --plugins ../live-import/plugins          # dry run
node tools/rehearsal-prep.mjs --sql ../live-import/prod.sql --uploads ../live-import/uploads --plugins ../live-import/plugins --apply
```

It replaces the site database with the dump, runs `wp core update-db` (prod runs an older WordPress than the dev site; the schema is upgraded forward), rewrites the prod URLs to `https://nuwe-ink.local` with a serialization-safe `wp search-replace` (`guid` untouched), copies uploads and plugins, deploys and activates `ink-core` + `ink-foundation` (Phase 3), and sets `WP_ENVIRONMENT_TYPE`, `INK_MAIL_SINK` and `INK_MIGRATION_DATA_DIR`. Local's generated `wp-config.php` already points at the correct local DB — **do not hand-edit credentials.**

**Sanity check:** load the front page. It shows the new theme over the old data until Phase 5 runs.

> Subscriptions require **no import** — WooCommerce Memberships records, plan IDs, start/expiry dates all ride the DB clone. Users, comments, media attachments, and private messages likewise ride the clone. You verify these later; you do not re-create them.

---

## Phase 3 — Deploy and activate the first-party code

This must happen **before any migration command**, because:
- every migration command is a `wp ink …` subcommand that only exists when `ink-core` is active, and
- the new CPTs and taxonomies (which the content migration writes into) are registered by `ink-core`, and its activation installs the custom database tables (follows, reading engagement, etc.).

**There is no asset build.** `ink-core` is pure PHP; `ink-foundation` is a block theme (raw `theme.json` + `.html` templates + hand-authored JS/CSS/fonts). No `npm`, webpack, or vite step exists for either.

`rehearsal-prep.mjs` (Phase 2) does steps 1, 3 and 4. They are listed for later code changes and for understanding.

1. **Place the code** with `node tools/deploy-to-local.mjs` (dry run) then `--apply`. It mirrors `ink-core` and `ink-foundation` from the repo, deleting files that are not in the repo. Do not symlink: the mirror would follow the link into your working copy. Re-run it after every code change; the site never reads the repo directly.
2. **(Optional) Optimize the autoloader.** From `wp-content/plugins/ink-core/`, run `composer install --no-dev`. This generates the plugin's own `vendor/autoload.php`. It is purely an optimization — the plugin falls back to a hand-rolled PSR-4 loader (`src/autoload.php`) and runs fine without it.
3. **Activate the plugin** (runs `Kernel\Activation`: records `ink_core_db_version`, installs custom-table schema via `dbDelta`, flushes rewrite rules, guards PHP/WP minimums):
   ```
   wp plugin activate ink-core
   ```
4. **Activate the theme:**
   ```
   wp theme activate ink-foundation
   ```
5. **Verify the CLI is live:**
   ```
   wp ink   # should list the ink subcommands (migrate-*, verify-*, audit-*, rebuild-*)
   ```

---

## Phase 4 — Install and prune the third-party plugin set

The definitive list. Where the June survey and the Epic 18 runbooks disagreed, this reflects the runbooks (the two big overrides: **Rank Math replaces Yoast**; **Cloudflare replaces Loginizer**).

### 4a. Remove the retired plugins (and the old theme)

Deactivate and delete every one of these — they conflict with or are superseded by the new model:

```
wp plugin deactivate wordpress-seo loginizer js_composer qode-framework \
  youzify youzify-frontend-submission cbxuseronline classic-widgets joinup-core \
  ultimate-social-media-icons woocommerce-legacy-rest-api wpcustom-category-image \
  wps-bidouille automatic-translator-addon-for-loco-translate document-emberdder \
  maintenance pdf-embedder string-locator \
  advanced-cron-manager comments-plus report-content invite-anyone redirect-redirection simple-css \
  code-snippets wp-migrate-db
wp plugin delete wordpress-seo loginizer js_composer qode-framework ...   # same list
wp theme delete ink-v2   # legacy theme; keep a default theme installed as fallback
```

- **Yoast (`wordpress-seo`)** — retired; Rank Math replaces it. Do **not** reactivate.
- **Loginizer** — retired; Cloudflare edge login rule + origin lock replace it.
- **WPBakery (`js_composer`) / Qode / Youzify / revslider / etc.** — legacy page-builder & profile stack; the block theme + `ink-core` + BuddyPress replace them. Their shortcodes are stripped later by `wp ink clean-shortcodes`.
- **LocoAI addon** — forbidden (no AI Afrikaans translation).
- **Code Snippets, WP Migrate** — retired (owner, 2026-11-05). Prod's snippets must not run beside `ink-core`; the prep script imports the dump, so WP Migrate has no job.
- **Comments Plus / Disable Comments** — `ink-core` already closes comments via filters. Retired (owner, 2026-10-29).
- **Advanced Cron Manager, Report Content, Invite Anyone, Redirect Redirection** — retired (owner, 2026-10-29). Report Content is replaced by the `ink-core` form; Invite Anyone only served BuddyPress Groups (off); `redirect-redirection` is **not** the "Redirection" plugin of 4b.

> **On a dev/rehearsal copy FluentSMTP is also deactivated** (`tools/rehearsal-prep.mjs` does this together with the list above; by hand: `wp plugin deactivate fluent-smtp`). The prod database brings its settings with it: it would send through prod's mail server (`mail.ink.org.za`) with prod's account. `INK_MAIL_SINK` still redirects every recipient, so members are safe, but the mail would leave the machine and bounce on prod's account instead of landing in Local's Mailpit. Keep it on production (4b).

### 4b. Keep / install the production plugin set (active on staging **and** production)

| Plugin | Source | Notes |
|--------|--------|-------|
| **BuddyPress** | wp.org | Community/identity. Component config in Phase 6 |
| **Contact Form 7** | wp.org | Contact form on "Oor INK" |
| **Redirection** | wp.org | 301 layer + 404 logging (owns 404 logging) |
| **WooCommerce** | wp.org | Memberships engine (suppress storefront UI) |
| **WooCommerce Memberships** | woocommerce.com (**premium**) | Time-based access |
| **WooCommerce PayFast Gateway** | wp.org | ZAR payments, fixed-term (no auto-renew at launch) |
| **FluentSMTP** | wp.org (already on prod) | Outgoing mail through `mail.ink.org.za`. Production only: deactivate on dev copies (see 4a). Keep, or replace with an equivalent SMTP plugin (owner, 2026-10-29) |
| **Real3D Flipbook** | carried over from existing site | InkPols PDF viewer (the edition already in use; copy the folder from your prior install) |
| **Rank Math SEO** | wp.org | **Replaces Yoast.** Config in Phase 6 |
| **Patchstack** | wp.org + account | CVE monitoring |
| **[2FA plugin]** | wp.org | Enforce for admin+editor; wire `ink_security_user_has_2fa` |
| **[Analytics]** — Burst Statistics **or** Independent Analytics | wp.org | Cookieless. NOT Google Analytics |

> **Note on `[2FA plugin]` / `[Analytics]`:** the square brackets are **choose-one placeholders**, not literal plugin names — install one 2FA plugin (e.g. Two-Factor or WP 2FA) and one cookieless analytics plugin (Burst Statistics **or** Independent Analytics). See the matching Phase 0 decisions.
>
> **No "Report Content" plugin.** The content-report/moderation path is a **custom `ink-core` form** (the `ink_rapporteer` action), resolved via PRD OQ-4 (2026-06-15) — it ships inside `ink-core` and needs no third-party plugin. If you saw "Report Content" in an older copy of this guide, ignore it; there is nothing to install.

### 4c. Host / feature conditional

| Plugin | Install when |
|--------|--------------|
| **LiteSpeed Cache** | Host runs LiteSpeed. Otherwise use host-level / WP Super Cache / W3TC |
| **Invite Anyone** | Only if BuddyPress **Groups** confirmed on (default: off → skip) |

### 4d. Staging-only (must be removed before production cutover — enforced by `wp ink audit-production`)

| Plugin | Purpose on staging |
|--------|--------------------|
| **Loco Translate** | Author Afrikaans `.po/.mo`, then commit to `wp-content/languages/`. **Never AI-translate.** |
| **WP Migrate (Lite)** | Not needed: `tools/rehearsal-prep.mjs` imports the dump. Retired (4a) |
| **Code Snippets** | Prod's snippets must not run on the new site. Retired (4a); any logic still needed lives in `ink-core` |

### 4e. Not a plugin — edge/DNS

**Cloudflare** provides the WAF, login rate-limit rule, origin lock, edge cache, and Turnstile registration challenge. It replaces Loginizer. Configure at cutover for production; on staging it's optional but useful to rehearse (see [security-stack-runbook.md](./security-stack-runbook.md) and [caching-runbook.md](./caching-runbook.md)).

> **No mu-plugins or drop-ins are required by INK.** LiteSpeed installs an `object-cache.php` drop-in automatically only if you enable object caching on a LiteSpeed host.

---

## Phase 5 — Run the migration

One command transforms the cloned data to the new model. `ink-core` must be active (Phase 3) first. Take a DB snapshot before you start so you can roll back.

```bash
wp ink migrate-all
```

It runs every migration step in the required order (sanitise, pages, users, gradering, subscriptions, content re-typing, challenges, result posts, InkPols, cleanup, navigation, follows, options, redirects, media checks), stops at the first failure, and flags any step that printed nothing or only zeros. Each step's outcome is stored, so a re-run resumes where it stopped; `--from=<step>` restarts from a step and `--reset` clears the stored state. The commands are CLI-only and their output is in Afrikaans.

Gradering is derived from the old result posts (`wp ink derive-tiers`); no tier file is needed.

The step list, the input files, and how to check the result are in [migration-runbook.md](./migration-runbook.md).

**To re-run one completed command**, append `--force`, e.g. `wp ink derive-tiers --force`. The reconciling commands (`migrate-challenges`, `migrate-inkpols`) use source markers so `--force` never creates duplicates.

**Take a fresh DB snapshot** after this phase completes cleanly, before you start configuring plugins.

---

## Phase 6 — Configure the plugins

Behavioural configuration that isn't captured in the migration. See each runbook for full detail.

### BuddyPress
- **On:** Member Profiles (xprofile), Friend Connections, Member Directory, Notifications, Private Messaging.
- **Off:** site-wide Activity Streams, Groups (unless confirmed), Blogs/Site Tracking.

### Rank Math SEO — set up **fresh** (do not import Yoast options wholesale) — see [seo-rank-math-runbook.md](./seo-rank-math-runbook.md)
- Setup wizard: locale `af`, org/logo.
- **Titles & Meta on** for: `gedig`, `storie`, `artikel`, `biblioteek_item`, `opleiding_artikel`, `uitdaging`, `inkpols_uitgawe`. Keep `skryfwerk` **noindex** unless promoted.
- Enable **XML sitemap** (verify public CPTs included; exclude `skryfwerk` if noindex).
- Enable **breadcrumbs**. Leave default Article schema on — `Ink\Seo\SchemaTypes` refines `@type` via the `rank_math/json_ld` filter (gedig/storie → CreativeWork, artikel → Article).
- Run **"Import from Yoast SEO" once** as a safety net for per-post overrides, then confirm InkPols `og:image` renders the issue cover.

### Cache — see [caching-runbook.md](./caching-runbook.md)
- **LiteSpeed Cache** (if on LiteSpeed): enable public + browser cache; object cache if the host offers Redis/Memcached; **exclude logged-in users from page cache**.
- **Do-not-cache list:** `admin-post.php` (`ink_kontak`, `ink_rapporteer`), and personalised INK blocks — `ink/leesgetalle`, My Profiel, `ink/volg-voer`, `ink/ontdek-vlakke`, `ink/vasgespel-bestuur`, plus any per-user REST/AJAX endpoint.
- Add **full cache purge on deploy** to your release routine.

### Redirection
- Enable **"Log 404 errors"**. Redirection owns 404 logging — the 301 map itself is what `generate-redirects`/`verify-redirects` produced. See [redirect-integrity-runbook.md](./redirect-integrity-runbook.md) for the live-crawl verification.

### Analytics — see [analytics-provider-decision.md](./analytics-provider-decision.md)
- Configure the chosen plugin in **cookieless / IP-anonymised** mode (POPIA-fit, no consent banner).
- Wire the `ink-core` seam: `add_filter('ink_analytics_provider_active','__return_true')`, plus the `ink/analytics_record_view` action and `ink_analytics_view_count` filter.

### Staff 2FA — see [security-stack-runbook.md](./security-stack-runbook.md)
- **Require** 2FA for `administrator` + `editor`; wire the plugin's per-user status to the `ink_security_user_has_2fa` filter.
- Confirm: `wp ink audit-2fa` → `Alle redakteurs en administrateurs het 2FA aktief`.

### WooCommerce Memberships
- **Do not conflate** membership status (paid access) with `ink_writer_tier` (Bronze/Silver/Gold/Meester pools) — they are separate concepts.
- Products are already configured (R60/1mo, R300/6mo, R600/12mo; PayFast fixed-term, no auto-renew).

### Patchstack
- Connect the site to your Patchstack account for CVE monitoring.

---

## Phase 7 — Manual data entry

These have **no** migration command — they are editorial:

- [ ] **Sponsors** (`borg`/`sponsor`) — enter manually in wp-admin (very low volume).
- [ ] **InkPols back-catalogue** — not entered by hand: `migrate-inkpols` (a `migrate-all` step) creates one `inkpols_uitgawe` per row of `inkpols-uitgawes.csv` in the migration-data folder ([migration-data.md](./migration-data.md)), links the existing PDF and cover by upload path, and the old Pages 301 to the new issues ([lovable-delta-2026-10/inkpols.md](./lovable-delta-2026-10/inkpols.md) §3 "As built"). An issue is published only when its row is complete (PDF, cover, naam, titel, beskrywing, and a title + writer for the Gedig, Storie and Artikel); otherwise it is a draft and its old Page stays live. Check here: the step's warnings list every missing field (`wp ink migrate-inkpols --force` shows them again). Fill the gaps **in the CSV**, commit it, and re-run (on a site you keep: `migrate-inkpols --force`, then `generate-redirects --force` and `verify-redirects --fix`). Edits made in wp-admin are lost at the next refresh.
- [ ] **Navigation review** — `rebuild-navigation` created the canonical "Hoofnavigasie"; review and adjust labels/order in the Site Editor.
- [ ] **Post-classification cleanup** — review anything that landed in `skryfwerk` and re-file if a human decision is needed.
- [ ] **Profile-field mapping** — reconcile any legacy profile fields you chose to keep.

---

## Phase 8 — Verify and smoke-test

Work through these before declaring staging good (and before any production cutover). See [migration-plan.md](./migration-plan.md) §"Order of operations" steps 12–16.

**Automated audits (`wp ink …`):**
- [ ] `wp ink verify-media` — attachments resolve; no missing files.
- [ ] `wp ink verify-redirects` — no unresolved chains/loops/empty targets.
- [ ] `wp ink verify-subscriptions` — membership counts, plan IDs, expiry coverage look right.
- [ ] `wp ink audit-2fa` — passes.
- [ ] `wp ink audit-translations` — `Alle premie-inprop vertalings is teenwoordig` (premium `.po/.mo` present in `wp-content/languages/`).
- [ ] `wp ink audit-production` — reports the staging-only plugins that must go before production (Loco, WP Migrate, Code Snippets, Simple CSS).

**Manual smoke tests:**
- [ ] **Redirects:** crawl a sample of old production URLs → each returns a single 301 to the live target (see [redirect-integrity-runbook.md](./redirect-integrity-runbook.md); needs Redirection + a running site).
- [ ] **Media:** audio plays, PDFs open in the flipbook, images render.
- [ ] **Membership flow:** a test purchase via PayFast → membership activates → gated content unlocks; expiry behaves.
- [ ] **Community:** profiles load, following works (the transformed follow graph), private messages present, member directory works.
- [ ] **Content model:** each CPT (`gedig`, `storie`, `artikel`, `biblioteek_item`, `opleiding_artikel`, `uitdaging`, `inkpols_uitgawe`) has an archive + single view rendering through the theme.
- [ ] **Writer tiers:** gradering is derived from the result posts (`derive-tiers`). Review its unresolved by-lines and fuzzy matches, and run `docs/migration-tools/validate-gradering.php` (see [migration-runbook.md](./migration-runbook.md)).
- [ ] **Forms:** contact form and content-report submit successfully.
- [ ] **Language:** UI renders in Afrikaans (`WPLANG=af`); check for untranslated strings (copy-debt is tracked separately in [afrikaans-copy-worklist.md](./afrikaans-copy-worklist.md)).

**Quality harness (optional, from the repo — validates the code you deployed):** `composer test`, `composer stan`, `composer cs`, `composer deptrac` (see [testing-and-quality-harness.md](./testing-and-quality-harness.md); `composer stan` needs the sandbox off).

---

## Appendix A — Complete command sequence (copy-ready)

```bash
# Run from the repo root. `wp …` below means `node tools/local.mjs wp …`.

# PHASES 2–3 — DB, URL, uploads, plugins, first-party code (dry run first)
node tools/rehearsal-prep.mjs --sql ../live-import/prod.sql --uploads ../live-import/uploads --plugins ../live-import/plugins --apply
wp ink   # sanity: subcommands listed

# PHASE 4 — prune legacy (see Phase 4a for full list), install keep-set via wp-admin/wp plugin install

# PHASE 5 — migration (all steps, in order; resumable)
wp ink migrate-all

# PHASE 8 — audits
wp ink audit-2fa
wp ink audit-translations
wp ink audit-production
```

Re-run any completed once-off command with `--force`.

## Appendix B — Migration command reference

The steps `wp ink migrate-all` runs, in order. Each can also be run on its own; once-off commands take `--force` to re-run.

| # | Command | Purpose |
|---|---------|---------|
| 1 | `ink migrate-sanitise` | Strip transients + finished Action Scheduler rows |
| 2 | `ink ensure-pages` | Create the system Pages the theme's templates and links need |
| 3 | `ink migrate-users` | Collapse non-staff onto `subscriber`; optional legacy-meta clean |
| 4 | `ink derive-tiers` | Derive each writer's gradering from the old result posts (Gedigte wins) |
| 5 | `ink verify-subscriptions` | Read-only WooCommerce Memberships report |
| 6 | `ink migrate-library-training` | `/biblioteek/`→`biblioteek_item`, `/opleiding/`→`opleiding_artikel` |
| 7 | `ink migrate-posts` | Flat posts → typed CPTs (else `skryfwerk`); keeps moderator result posts as posts |
| 8 | `ink migrate-challenges` | Legacy challenge categories → `uitdaging` + round terms |
| 9 | `ink sync-challenge-entries` | Attach posts filed under a challenge's source category to its round |
| 10 | `ink link-result-posts` | Link each result post to its uitdaging (by month and year) |
| 10b | `ink import-winners` | Every win on the old result posts → a record on its round (all categories, with or without a post or an account); placements and entry-Gradering snapshots on the winning posts found |
| 11 | `ink migrate-inkpols` | `inkpols-uitgawes.csv` → one `inkpols_uitgawe` per row (draft until the row is complete); link existing PDF + cover; record 301 sources; trash the old Pages |
| 11b | `ink migrate-kompetisies` | Old galas and the 2022 competition (found by the tags from `tag-old-competitions.sql`) → `kompetisie` posts; photos linked; old Pages trashed and 301ed. Must run before `clean-shortcodes` |
| 12 | `ink clean-shortcodes` | Strip legacy WPBakery `[vc_*]`, keep inner content |
| 13 | `ink rebuild-navigation` | Build canonical "Hoofnavigasie" (new IA) |
| 14 | `ink migrate-follows` | Confirmed friendships → two directed follows; trim BP activity >2yr |
| 15 | `ink migrate-options` | Carry allowlisted options; force `WPLANG=af` |
| 16 | `term recount …` | Recount category, genre, vaardigheid and uitdagingsrondte terms |
| 17 | `ink generate-redirects` | Build 301 map from recorded source URLs vs current permalinks |
| 18 | `ink verify-redirects --fix` | Audit/flatten 301 chains + loops |
| 19 | `ink verify-media` | Read-only attachment report + missing-file list |

## Appendix C — Common gotchas

- **A `wp ink …` command "does nothing" / says already done** — it's idempotent; it ran before. Add `--force` to re-run.
- **`derive-tiers` warns "Onopgelos: …"** — a by-line on an old result post matched no account, so its wins were not counted. Add a row to `writer-accounts.csv` or `writer-aliases.csv` in the `INK_MIGRATION_DATA_DIR` folder, then `wp ink derive-tiers --force`.
- **Redirects are empty or wrong** — you ran `generate-redirects` before finishing the content re-typing (`migrate-posts`/`migrate-library-training`/`migrate-inkpols`). Re-run those, then `generate-redirects --force`.
- **`wp ink` prints nothing** — `ink-core` isn't active, or you're not running WP-CLI (these commands never run on a web request).
- **Old WPBakery `[vc_row]` junk in content** — run `wp ink clean-shortcodes`.
- **Site still looks like the old site** — you haven't activated `ink-foundation` (Phase 3) or you're on the stale reused Local site with `ink-v2` still active.
- **`composer stan` fails with a TCP/EPERM error** — the static-analysis run needs the command sandbox disabled.

---

*Definitive as of 2026-07-07. When the plugin set, migration toolkit, or content model changes, update this document — it is the single source of truth for standing up INK.*

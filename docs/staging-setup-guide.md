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
| **7** | Manual data entry (sponsors, InkPols back-catalogue) | Editorial content complete |
| **8** | Verify & smoke-test | Signed-off, functioning site |

**Runtime prerequisites (all phases):** PHP **8.3+**, WordPress **7.0** (the version this new site is built for — you do *not* need to match the older production version; see the note in Phase 1), WP-CLI, Composer, and Local by Flywheel (or an equivalent LAMP stack).

---

## Phase 0 — Gather your inputs

Do **not** start until you physically have all of these. Chasing a missing artifact mid-migration is where mistakes happen.

- [ ] **A production database dump** (`.sql`) — a *fresh* export from production is strongly preferred over cloning the current staging site, because the existing `ink-staging` Local site is a snapshot of the *old* site (old plugins, old theme `ink-v2`) and may be stale. **Don't have one yet? That's what Phase 0.5 below is for** — it walks you through three ways to produce this file, no prior WordPress experience assumed.
- [ ] **The production `wp-content/uploads/` directory** — all media (audio, PDFs, images). Migrates as-is; the database references it. **Phase 0.5 also covers how to download this folder.**
- [ ] **Premium plugin installer** (you cannot get this from wp.org):
  - [ ] **WooCommerce Memberships** — from woocommerce.com account
- [ ] **Real3D Flipbook** — the InkPols PDF viewer, carried over from the existing site (copy the plugin folder from your previous staging/production install; INK uses the edition already in place — *not* a separate CodeCanyon premium purchase).
- [ ] **The writer-tier CSV** — one row per writer with an **email** column and a **tier** column (`brons`/`silwer`/`goud`/`meester`). Column headers are auto-detected by fragment (`mail`/`e-pos`/`epos` for email; `tier`/`grad` for tier). Optional promotion history. Save it somewhere WP-CLI can read (e.g. `wp-content/uploads/private/tiers.csv`).
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
- When you later place the writer-tier CSV or this dump inside the staging `uploads/`, put it under a **private** subfolder (the guide uses `wp-content/uploads/private/`) that isn't linked from the site.

**Result of Phase 0.5:** you now have `production.sql` (or `.sql.gz`) and an `uploads/` folder saved locally. Return to the Phase 0 checklist, tick those two boxes, then continue to Phase 1.

---

## Phase 1 — Provision the WordPress environment

The project targets **Local by Flywheel** for staging (the `/Users/cobus/Local Sites/ink-staging` site). WP core, wp-admin, and wp-includes are **not** in the repo — Local provides them. (The repo's `.wp-env.json` is for the automated test harness only; it is not the staging runtime.)

1. **Create a fresh Local site** (recommended over reusing the stale one) so you start from clean core.
   - Site name: e.g. `ink-staging`
   - **PHP 8.3** (required by `ink-core`, `ink-foundation`, and the whole toolchain)
   - **WordPress version:** use **7.0** — the version this new site is built for. You do **not** need to match production's (older) version. WordPress only breaks when you import a *newer* database into an *older* WordPress; the reverse — an older production database into a newer WP 7 — is the normal, supported path: WordPress automatically upgrades the old database schema forward on import (see Phase 2, step 2b). Uploads are plain files and are version-independent. (Aside: the repo declares WP 7.0 in headers/wp-env while the static-analysis stubs pin 6.7 — that discrepancy only affects code analysis, never the running site.)
   - Web server: LiteSpeed if you can (it makes the LiteSpeed Cache layer representative of production); otherwise nginx/Apache is fine for staging.
2. Note the site's local URL (e.g. `https://ink-staging.local`) and its DB credentials (Local shows these; they're wired into the generated `wp-config.php`). You'll need the URL for the search-replace in Phase 2.
3. Confirm WP-CLI works against the site: from the site's `app/public/`, `wp core version` should print the version.

> **New to WP-CLI? Where do you type these `wp …` commands?** WP-CLI is a command-line tool that ships **inside Local** — you don't install it separately. In the Local app, **right-click your site in the left sidebar → "Open site shell"** (some versions call it "Open WP-CLI"). That opens a terminal that is *already* pointed at this site's WordPress install, so `wp core version`, and later every `wp ink …` command in Phase 5, just work. Every command in this guide that starts with `wp` is meant to be typed into that shell. (If you prefer, `cd` into the site's `app/public/` folder in any terminal where `wp` is installed and run them there.)

> **Reusing the existing Local site instead?** You can, but you must first remove the legacy theme (`ink-v2`) and every retired plugin (see Phase 4's remove-list) — otherwise old code (WPBakery, Youzify, Yoast, Loginizer) will fight the new model. A fresh site avoids that cleanup entirely. If you reuse it, treat Phase 2 as "re-import a fresh prod DB over the top".

---

## Phase 2 — Load the production database and files

Goal: staging is a faithful copy of live, adjusted only for its new URL. This phase consumes the two artifacts you produced in Phase 0.5 (the `.sql` file and the `uploads/` folder).

> **Where is the staging site's folder?** In the Local app, **right-click your site → "Reveal in Finder" (or "Show folder")**. Inside you'll find `app/public/` — that folder *is* the staging WordPress install. Its `wp-content/uploads/` is where the media goes, and it's the folder the \"Open site shell\" terminal starts in one level up.

1. **Import the media files.** Copy the production `uploads/` folder you downloaded in Phase 0.5 into the staging site at `app/public/wp-content/uploads/`. You want the staging `uploads/` to end up containing the same year/month subfolders (`2024/`, `2025/`, …) as production. If a staging `uploads/` already exists, merge into it (replace/overwrite when asked). Do this before verifying media later.
2. **Import the database.** Load the production `.sql` from Phase 0.5 into the staging database. The simplest way is the Local site shell ("Open site shell" from Phase 1), then:
   ```bash
   wp db import /full/path/to/production.sql
   ```
   Drag the `.sql` file into the terminal window to paste its full path. (If your file is compressed as `production.sql.gz`, first run `gunzip production.sql.gz` to get the `.sql`.) You can instead use Local's built-in database GUI ("Open Adminer"/"Open Sequel") and use its Import button. Either way, Local's generated `wp-config.php` already points at the correct local DB — **do not hand-edit credentials.** This step **overwrites** the empty staging database with production's content, which is exactly what you want.
2b. **Upgrade the database schema forward (only if production ran an older WordPress).** Because staging is WP 7 and the imported data came from an older version, run WordPress's built-in upgrade once so the schema matches WP 7:
   ```bash
   wp core update-db
   ```
   This is the automatic, supported migration referenced in Phase 1 — it adjusts the old schema in place and is safe to run even when nothing needs upgrading (it simply reports "Database upgraded successfully" or that no upgrade was required). If production happened to already run WP 7, this is a harmless no-op.
3. **Rewrite the site URL** from the production domain to the staging domain (this is the "point it to the right place" step for content, distinct from DB credentials). Replace `https://PROD-DOMAIN` below with the real live domain (e.g. `https://inkskryfburo.co.za`), and use your actual Local URL from Phase 1:
   ```
   wp search-replace 'https://PROD-DOMAIN' 'https://ink-staging.local' --all-tables --precise --skip-columns=guid
   wp option update home 'https://ink-staging.local'
   wp option update siteurl 'https://ink-staging.local'
   ```
   (Leave `guid` untouched — GUIDs are permanent identifiers, not links.)
4. **Fix uploads path** only if the production absolute path differs from the local one (`wp option get upload_path`); normally WordPress derives it and you can leave it blank.
5. **Sanity check:** `wp option get blogname` and load the front page. It will look like the *old* site right now — that's expected. The new model arrives in Phases 3–5.

> Subscriptions require **no import** — WooCommerce Memberships records, plan IDs, start/expiry dates all ride the DB clone. Users, comments, media attachments, and private messages likewise ride the clone. You verify these later; you do not re-create them.

---

## Phase 3 — Deploy and activate the first-party code

This must happen **before any migration command**, because:
- every migration command is a `wp ink …` subcommand that only exists when `ink-core` is active, and
- the new CPTs and taxonomies (which the content migration writes into) are registered by `ink-core`, and its activation installs the custom database tables (follows, reading engagement, etc.).

**There is no asset build.** `ink-core` is pure PHP; `ink-foundation` is a block theme (raw `theme.json` + `.html` templates + hand-authored JS/CSS/fonts). No `npm`, webpack, or vite step exists for either.

1. **Place the code** into the staging `wp-content/` (symlink from the repo, or copy):
   - `wp-content/plugins/ink-core/`  ← from `ink-vibe/wp-content/plugins/ink-core/`
   - `wp-content/themes/ink-foundation/`  ← from `ink-vibe/wp-content/themes/ink-foundation/`
   A symlink to your working copy is convenient on staging so you can pull updates without re-copying.
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
  maintenance pdf-embedder string-locator
wp plugin delete wordpress-seo loginizer js_composer qode-framework ...   # same list
wp theme delete ink-v2   # legacy theme; keep a default theme installed as fallback
```

- **Yoast (`wordpress-seo`)** — retired; Rank Math replaces it. Do **not** reactivate.
- **Loginizer** — retired; Cloudflare edge login rule + origin lock replace it.
- **WPBakery (`js_composer`) / Qode / Youzify / revslider / etc.** — legacy page-builder & profile stack; the block theme + `ink-core` + BuddyPress replace them. Their shortcodes are stripped later by `wp ink clean-shortcodes`.
- **LocoAI addon** — forbidden (no AI Afrikaans translation).
- **Comments Plus / Disable Comments** — optional to keep; `ink-core` already closes comments via filters. Delete once you've confirmed the `ink-core` behaviour.

### 4b. Keep / install the production plugin set (active on staging **and** production)

| Plugin | Source | Notes |
|--------|--------|-------|
| **BuddyPress** | wp.org | Community/identity. Component config in Phase 6 |
| **Contact Form 7** | wp.org | Contact form on "Oor INK" |
| **Redirection** | wp.org | 301 layer + 404 logging (owns 404 logging) |
| **WooCommerce** | wp.org | Memberships engine (suppress storefront UI) |
| **WooCommerce Memberships** | woocommerce.com (**premium**) | Time-based access |
| **WooCommerce PayFast Gateway** | wp.org | ZAR payments, fixed-term (no auto-renew at launch) |
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
| **WP Migrate (Lite)** | The DB import tool from Phase 2; uninstall right after use |
| **Code Snippets** | Holds snippets in transit → migrate into `ink-core`, then retire |
| **Simple CSS** | Holds CSS in transit → migrate into the theme, then retire |

### 4e. Not a plugin — edge/DNS

**Cloudflare** provides the WAF, login rate-limit rule, origin lock, edge cache, and Turnstile registration challenge. It replaces Loginizer. Configure at cutover for production; on staging it's optional but useful to rehearse (see [security-stack-runbook.md](./security-stack-runbook.md) and [caching-runbook.md](./caching-runbook.md)).

> **No mu-plugins or drop-ins are required by INK.** LiteSpeed installs an `object-cache.php` drop-in automatically only if you enable object caching on a LiteSpeed host.

---

## Phase 5 — Run the migration toolkit (in order)

These transform the cloned data to the new model. **Rules that apply to all of them:**

- **CLI-only.** They no-op on a web request. Run from the site root with WP-CLI.
- **No `--dry-run`.** The only flags are `--force` (re-run a completed once-off command) and `--fix` (only on `verify-redirects`). Take a DB snapshot before you start so you can roll back.
- **Idempotent.** Each mutating command writes a completion flag; a second run reports "(oorgeslaan — reeds gedoen)" and does nothing unless you pass `--force`. Verification commands are read-only and always re-runnable.
- **Output is in Afrikaans.**

Run them **in this exact order** (dependencies are noted):

```bash
# --- 1. Sanitise the cloned DB (strip transients + finished Action Scheduler rows) ---
wp ink migrate-sanitise

# --- 2. Accounts, then tiers (tier import joins on email → needs accounts first) ---
wp ink migrate-users
wp ink migrate-tiers wp-content/uploads/private/tiers.csv    # positional path REQUIRED

# --- 3. Verify subscriptions (read-only report; no import) ---
wp ink verify-subscriptions

# --- 4. Content re-typing. Library/training BEFORE general posts. ---
#     Each records the pre-migration permalink in ink_migration_source_url,
#     which the redirect generator reads later — so all of these must run
#     BEFORE generate-redirects.
wp ink migrate-library-training      # /biblioteek/ → biblioteek_item, /opleiding/ → opleiding_artikel
wp ink migrate-posts                 # flat posts → gedig/storie/artikel (else skryfwerk); skips monthly_challenge
wp ink migrate-challenges            # builds the uitdaging records migrate-posts skipped
wp ink migrate-inkpols               # legacy inkpols → inkpols_uitgawe; re-links existing PDFs

# --- 5. Content cleanup: strip legacy WPBakery [vc_*] tags, keep inner content ---
wp ink clean-shortcodes

# --- 6. Fresh navigation (new IA — NOT a copy of the old menu; old deep links survive via redirects) ---
wp ink rebuild-navigation

# --- 7. Community graph + selective options ---
wp ink migrate-follows               # confirmed friendship → two directed follows; trims BP activity >2yr
wp ink migrate-options               # allowlist only: siteurl/home/blogname/blogdescription/WPLANG; forces WPLANG=af

# --- 8. Redirects — MUST come after ALL post-type changes above ---
wp ink generate-redirects            # builds 301 map from source-url vs current permalink (only where path changed)
wp ink verify-redirects --fix        # flatten 301→301 chains/loops; --fix re-stores the cleaned map

# --- 9. Media verification (read-only report of attachments + missing files) ---
wp ink verify-media
```

**Critical ordering dependencies** (why the sequence is not negotiable):

1. `ink-core` **active** (Phase 3) → otherwise none of these commands exist.
2. `migrate-users` **before** `migrate-tiers` — the tier import joins on email and never creates accounts.
3. `migrate-library-training` + `migrate-posts` + `migrate-inkpols` **before** `generate-redirects` — redirects are computed from the source URLs those commands record.
4. `migrate-posts` deliberately **skips** `monthly_challenge` posts; `migrate-challenges` builds them — so challenges run **after** posts.
5. `generate-redirects` **before** `verify-redirects`.

**To re-run a completed command** (e.g. after fixing the tier CSV): append `--force`, e.g. `wp ink migrate-tiers .../tiers.csv --force`. The reconciling commands (`migrate-challenges`, `migrate-inkpols`) use source markers so `--force` never creates duplicates.

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
- [ ] **InkPols back-catalogue** — `migrate-inkpols` handles issues present in the legacy data; enter/curate any remaining issues manually. Confirm each issue's PDF (Real3D Flipbook) opens.
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
- [ ] **Writer tiers:** spot-check that `ink_writer_tier` values match the CSV; review anything flagged by `ink_tier_import_review_flag`.
- [ ] **Forms:** contact form and content-report submit successfully.
- [ ] **Language:** UI renders in Afrikaans (`WPLANG=af`); check for untranslated strings (copy-debt is tracked separately in [afrikaans-copy-worklist.md](./afrikaans-copy-worklist.md)).

**Quality harness (optional, from the repo — validates the code you deployed):** `composer test`, `composer stan`, `composer cs`, `composer deptrac` (see [testing-and-quality-harness.md](./testing-and-quality-harness.md); `composer stan` needs the sandbox off).

---

## Appendix A — Complete command sequence (copy-ready)

```bash
# PHASE 2 — DB + URL
wp db import production.sql
wp search-replace 'https://PROD-DOMAIN' 'https://ink-staging.local' --all-tables --precise --skip-columns=guid
wp option update home 'https://ink-staging.local'
wp option update siteurl 'https://ink-staging.local'

# PHASE 3 — first-party code
(cd wp-content/plugins/ink-core && composer install --no-dev)   # optional
wp plugin activate ink-core
wp theme activate ink-foundation
wp ink   # sanity: subcommands listed

# PHASE 4 — prune legacy (see Phase 4a for full list), install keep-set via wp-admin/wp plugin install

# PHASE 5 — migration toolkit (ORDER MATTERS)
wp ink migrate-sanitise
wp ink migrate-users
wp ink migrate-tiers wp-content/uploads/private/tiers.csv
wp ink verify-subscriptions
wp ink migrate-library-training
wp ink migrate-posts
wp ink migrate-challenges
wp ink migrate-inkpols
wp ink clean-shortcodes
wp ink rebuild-navigation
wp ink migrate-follows
wp ink migrate-options
wp ink generate-redirects
wp ink verify-redirects --fix
wp ink verify-media

# PHASE 8 — audits
wp ink audit-2fa
wp ink audit-translations
wp ink audit-production
```

Re-run any completed once-off command with `--force`.

## Appendix B — Migration command reference

| # | Command | Purpose | Flags | Idempotency flag |
|---|---------|---------|-------|------------------|
| 1 | `migrate-sanitise` | Strip transients + finished Action Scheduler rows | `--force` | `ink_migration_sanitise_done` |
| 2 | `migrate-users` | Collapse non-staff onto `subscriber`; optional legacy-meta clean | `--force` | `ink_migration_users_done` |
| 3 | `migrate-tiers <path>` | Import writer-tier CSV → `ink_writer_tier` (join on email) | `--force` | `ink_migration_tiers_done` |
| 4 | `verify-subscriptions` | Read-only WooCommerce Memberships report | — | (read-only) |
| 5 | `migrate-library-training` | `/biblioteek/`→`biblioteek_item`, `/opleiding/`→`opleiding_artikel` | `--force` | `ink_migration_library_done` |
| 6 | `migrate-posts` | Flat posts → typed CPTs (else `skryfwerk`); skips `monthly_challenge` | `--force` | `ink_migration_posts_done` |
| 7 | `migrate-challenges` | Legacy challenge categories → `uitdaging` + round terms | `--force` | `ink_challenge_migration_done` |
| 8 | `migrate-inkpols` | Legacy InkPols → `inkpols_uitgawe`; re-link existing PDFs | `--force` | `ink_inkpols_migration_done` |
| 9 | `clean-shortcodes` | Strip legacy WPBakery `[vc_*]`, keep inner content | `--force` | `ink_migration_shortcodes_done` |
| 10 | `rebuild-navigation` | Build canonical "Hoofnavigasie" (new IA) | `--force` | `ink_migration_navigation_done` |
| 11 | `migrate-follows` | Confirmed friendships → two directed follows; trim BP activity >2yr | `--force` | `ink_migration_follows_done` |
| 12 | `migrate-options` | Carry allowlisted options; force `WPLANG=af` | `--force` | `ink_migration_options_done` |
| 13 | `generate-redirects` | Build 301 map from recorded source URLs vs current permalinks | `--force` | `ink_migration_redirects_done` |
| 14 | `verify-redirects` | Audit/flatten 301 chains + loops | `--fix` | (read-only unless `--fix`) |
| 15 | `verify-media` | Read-only attachment report + missing-file list | — | (read-only) |

## Appendix C — Common gotchas

- **A `wp ink …` command "does nothing" / says already done** — it's idempotent; it ran before. Add `--force` to re-run.
- **`migrate-tiers` errors "Verskaf die pad na die gradering-CSV"** — the CSV path is a **required positional argument**, not a flag.
- **Redirects are empty or wrong** — you ran `generate-redirects` before finishing the content re-typing (`migrate-posts`/`migrate-library-training`/`migrate-inkpols`). Re-run those, then `generate-redirects --force`.
- **`wp ink` prints nothing** — `ink-core` isn't active, or you're not running WP-CLI (these commands never run on a web request).
- **Old WPBakery `[vc_row]` junk in content** — run `wp ink clean-shortcodes`.
- **Site still looks like the old site** — you haven't activated `ink-foundation` (Phase 3) or you're on the stale reused Local site with `ink-v2` still active.
- **`composer stan` fails with a TCP/EPERM error** — the static-analysis run needs the command sandbox disabled.

---

*Definitive as of 2026-07-07. When the plugin set, migration toolkit, or content model changes, update this document — it is the single source of truth for standing up INK.*

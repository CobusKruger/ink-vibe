---
project_name: 'ink-vibe'
user_name: 'Cobus'
date: '2026-06-14'
sections_completed:
  ['technology_stack', 'language_rules', 'framework_rules', 'testing_rules', 'quality_rules', 'workflow_rules', 'anti_patterns']
status: 'complete'
optimized_for_llm: true
---

# Project Context for AI Agents

_This file contains critical rules and patterns that AI agents must follow when implementing code in this project. Focus on unobvious details that agents might otherwise miss._

---

## Technology Stack & Versions

**Build targets:** WordPress **7.0+** · PHP **8.3+** (theme `Requires PHP: 8.3`). Write PHP 8.3-era code — `enum`, `readonly`, constructor promotion, typed class constants, `#[\Override]`, first-class callables, named args. Do not target PHP 7.x idioms.

**Three artifacts you write (and only these):**

| Artifact | What lives here | Repo path |
|---|---|---|
| `ink-foundation` | Block theme (FSE): `theme.json`, templates, template-parts, patterns, block styles. **Presentation only.** | `wp-content/themes/ink-foundation/` |
| `ink-core` | All INK business logic: CPTs, taxonomies, user meta, tier/challenge/submission/follow/engagement logic, REST, admin tools. | `wp-content/plugins/ink-core/` *(to be created)* |
| Translations | Committed `.po/.mo` for surviving 3rd-party plugins only. | `wp-content/languages/` |

**Platform plugins (commodity capabilities — do not reimplement):** BuddyPress (scoped: profiles, directory, notifications; Friends/Groups/Messaging OFF), WooCommerce + WooCommerce Memberships, WooCommerce PayFast Gateway (ZAR), Real3D Flipbook, Rank Math (SEO — Yoast retired), Redirection, LiteSpeed Cache, Patchstack.

**Brownfield:** existing WordPress DB is cloned and reused. Members, subscriptions, content, and media must survive — never assume a clean install.

**Design source:** Lovable (React + Tailwind + shadcn/ui). It is **design intent, not code** — never ported. Tokens normalised in `docs/design-handoff/tokens/theme-tokens.json` → `theme.json`.

**Testing stack:** Pest/PHPUnit + Brain Monkey/WP_Mock (unit), wp-env / wp-browser (integration), Playwright + `@wordpress/e2e-test-utils-playwright` (E2E).

## Critical Implementation Rules

### Language-Specific Rules (PHP 8.3 / WordPress)

- **Prefix everything** `ink_` / `Ink\` — functions, hooks, options, meta keys, CPT/taxonomy IDs, and the `ink-core` namespace. No unprefixed globals.
- **Model fixed value sets as `enum`s** in `ink-core`: writer tier (`brons`/`silwer`/`goud`), response type (`lof`/`insig`/`voorstel`), reaction (`hartjie`/`duim_op`/`wow`). The string is the persisted DB value; never duplicate these literals across the codebase.
- **All output is escaped at the point of output** (`esc_html`, `esc_attr`, `esc_url`, `wp_kses_post`); **all input is sanitised and capability-checked**; nonces on every state-changing form/AJAX/REST call. No raw `$_POST`/`$_GET`.
- **Never write raw SQL with interpolation** — `$wpdb->prepare()` always. Custom tables (e.g. follow graph, tier-promotion log) declared via `dbDelta()` with the `$wpdb->prefix`.
- **i18n on every user-facing string** with the correct text domain (`ink-core` / `ink-foundation`). Afrikaans is the source text; English is the fallback, not the reverse. See the Afrikaans-first rules below.
- **Hook, don't edit.** Integrate with BuddyPress/WooCommerce via their hooks, filters, and template functions — never modify plugin files or assume internal structure.
- **Enqueue assets** via `wp_enqueue_*` with versioned handles; no inline `<script>`/`<style>` in templates, no hardcoded asset URLs (`get_theme_file_uri()` / plugin URL helpers).
- **WP coding standards**: tabs for indentation, Yoda conditions where the project lints for them, `WPCS`/`PHPStan`-clean. Strict types (`declare(strict_types=1)`) in `ink-core` PHP files.

### Framework & Architecture Rules

- **Three-layer separation is non-negotiable.** Presentation → theme; INK business rules & content models → `ink-core`; commodity capabilities → vetted platform plugins. **No business logic in the theme** (no tier/challenge/submission/follow logic in templates or `functions.php`).
- **THE conflation rule:** *subscription status* (active WooCommerce Membership) controls **submission entitlement**; *writer tier* (`ink_writer_tier`: brons/silwer/goud) controls **competition pools**. These are separate concepts — never gate one on the other. A paid Brons subscriber ≠ a Brons writer with an expired subscription.
- **CPTs & taxonomies are registered in `ink-core`, in Afrikaans.** CPTs: `gedig`, `storie`, `artikel`, `skryfwerk`, `biblioteek_item`, `opleiding_artikel`, `uitdaging`, `inkpols_uitgawe`, `borg`. Taxonomies: `genre`, `vaardigheid`, `uitdagingsrondte`, `ster_gradering`. Use these exact code IDs — they are migration-load-bearing (old `verhaal`→`storie`, `inkpols`→`inkpols_uitgawe`).
- **Shared-taxonomy surfacing, not manual linking.** Training and contributions share `genre`/`vaardigheid` terms so resources surface automatically. Never build a feature that needs per-item manual editorial linking (Principle 8 — it gets ignored under workload).
- **Follow is custom in `ink-core`** (asymmetric, one-way). BuddyPress Friend Connections are OFF. Don't reach for a BuddyPress follow add-on.
- **Reading engagement lives in `ink-core`**, not WP comments — WP comments are disabled site-wide. Structured responses (`Gemeenskapsreaksies`: Lof/Insig/Voorstel), line highlights + reactions, suggested reads, reading list, ratings & reviews, pinned works.
- **Block theme, not classic.** FSE templates/template-parts/patterns/block styles. Lock critical editorial structure with block locking; leave content editable for non-technical staff.
- **Lovable → WordPress translation:** extract design *intent* and re-express in WP primitives. Tailwind classes → `theme.json` tokens + block styles; shadcn primitives → core blocks + style variants; client interactivity → Interactivity API or small enqueued JS (business logic stays in `ink-core`); `react-router` → WP templates/permalinks; mock data/`localStorage` → CPTs/meta/migrated DB. **Never emit JSX, copy Tailwind classes, or treat mock data as the data model.**

### Testing Rules

- **Test your own seams, not the plugins.** Verify `ink-core` logic and the theme↔plugin integration points; never re-test BuddyPress/WooCommerce internals.
- **Concentrate the suite in `ink-core`.** It is highly unit-testable; cover the block theme via E2E/visual checks instead of unit tests.
- **Test pyramid:**
  - *Many unit tests* — `ink-core` rules with WP mocked (Brain Monkey / WP_Mock, via Pest or PHPUnit): tier promotion, submission-entitlement gate, sponsor scheduling, follow graph.
  - *Fewer integration tests* — real WP+DB (wp-env + WP test library, or wp-browser/Codeception) for the seams that matter: *active membership ⇒ can submit*, *expired ⇒ denied*, *tier write ⇒ meta + log*.
  - *Thin E2E layer* — Playwright + `@wordpress/e2e-test-utils-playwright` for critical journeys only: register → buy membership via **PayFast sandbox** → submit → publish → read/react → renewal/expiry.
- **`ink-core` rules ship test-first** — the test-harness scaffold is foundational (Epic 1), not deferred.
- **Cross-story durability pass for module-owned guarantees.** When a module owns an invariant (entitlement gate, "comments are the only feedback path", "reactions only on content lines"), test that the guarantee holds across *every* path that touches it, not just the happy path of the story that introduced it — including paths owned by earlier stories. (This caught the denied-publish path still registering challenge entries in Epic 6.) Promoted from candidate after proving itself in Epics 5–6.
- **Guardrail tests must be non-vacuous.** A test asserting a thing is *blocked/absent/disabled* must first prove the thing *would otherwise be present* — exercise the real surface so the assertion can actually fail, never assert against a stub that never had the behaviour. A guardrail that can't fail is worse than none. Promoted from candidate after proving itself in Epics 5–6.
- **Run the unit suite locally — Pest execution is NO LONGER deferred (2026-06-22).** PHP 8.3 + Composer are installed on the dev machine; `composer install` builds `vendor/`. During the dev-story cycle you MUST **author *and run*** the Pest unit tests with `composer test:unit`, and **the suite must pass before a story is marked done.** Do **not** substitute a python3 "structural scan" for execution, and do **not** defer Pest/`php -l`/PHPStan to the CI buildout — the Epic 1–2 "no PHP binary, defer to Story 18.8" precedent is **retired**; do not carry it into new stories. The unit suite is mocked (Brain Monkey, no WordPress/DB) and runs on PHP ≥ 8.3; the unit bootstrap defines a sentinel `ABSPATH` so guarded `ink-core` source files don't `exit(0)` on autoload. **Story 18.8 still owns CI wiring + the integration/E2E layers** (wp-env, Playwright) — not local unit execution, which is available now.
- **Risk-based depth:** smoke-only for minor/security updates; full regression for major version bumps.
- **English-leak check is an automated test**, not a manual pass: crawl key front-end pages + scan `wp i18n` untranslated counts. It is a *standing* gate (re-runs after ungated core/plugin updates), not a one-time build check. The **static subset already runs today**: `composer copy:scan` (`tools/leak-scan/`, CI `quality` job) is a ratchet gate over unauthored-Afrikaans placeholders — see the unauthored-copy workflow under Afrikaans-first. The full page-crawl + `wp i18n` layer lands in Story 17.4 / Epic 18.
- **PayFast always uses sandbox in tests** — never hit the live ZAR gateway.

### Code Quality & Style Rules

**Afrikaans-first (Quality Gate D — no English leakage to front end):**

- Front end + user-facing transactional emails are **entirely Afrikaans**. No English word reaches a visitor or member.
- **Admin-language split:** WP-core and third-party plugin admin chrome = **English** (by decision). All `ink-core` admin surfaces (CPT/taxonomy labels, tier promotion, sponsor scheduling, challenge/winner admin, reports) = **Afrikaans**. Mechanism: `ink-core` authors admin strings in Afrikaans as the source language and ships **no English `.mo`**, so gettext returns the Afrikaans source even under a staff member's English admin locale.
- Site locale `af`; staff (editor/administrator) forced to English admin language via per-user WP setting in `ink-core` — front-end output stays Afrikaans regardless.
- **`afrikaans-terms.md` is the glossary source of truth.** A new concept is added to the glossary **before** it appears in code or UI. Code IDs and UI labels follow it.
- **Controlled-vocabulary UI labels come from the ink-core terminology registry** (single-source, glossary-backed literal __()), the same way fixed value sets come from enums. Never inline a glossary label as a bare literal outside the registry.
- **Never lift copy from the Lovable mockup** — its text is English placeholder. UI copy comes from `ui-copy-translations.md`; real content from the migrated DB.
- **No AI-generated Afrikaans.** Human-authored translations only.
- **Unauthored-copy workflow (the standing process).** When a story needs Afrikaans that doesn't exist yet, do NOT translate it — leave a placeholder and route it to the human author:
  1. **Mark it in code** with `[NEEDS HUMAN AFRIKAANS]` (UI/template) or `[WAG OP MENSLIKE KOPIE]` (email/notice), wrapped in the correct text domain, and keep any feature/email send-toggle **OFF**.
  2. **Mirror a row** in `ui-copy-translations.md` (the canonical UI-copy doc) carrying the same placeholder + an English sample + notes.
  3. **Collect for authoring** in `docs/afrikaans-translation-sheet.md` — a flat `ID` / `EN:` / `AF:` sheet the human fills in one pass. The ID→destination map lives in `docs/afrikaans-copy-worklist.md` (crosswalk). When the sheet comes back, wire each `AF:` into `ui-copy-translations.md` + `afrikaans-terms.md` + the code `file:line`, then lower the scan baseline.
  4. **The gate:** `composer copy:scan` (`tools/leak-scan/`, runs in CI `quality` job) is a ratchet over `placeholder-baseline.json` — it FAILS on any *new* placeholder in live code (theme patterns/templates + `ink-core/src`; docs/tests excluded) and prompts you to lower the baseline as copy lands. Run `composer copy:scan -- --update-baseline` after authoring. **Launch target: baseline empty.** This is the static subset of the full NFR-1 leak gate (Story 17.4 / Epic 18 add the page-crawl + `wp i18n` layer).
- **Theme-pattern i18n convention (Gate D).** Every user-facing string in `wp-content/themes/ink-foundation/patterns/*.php` (and template-part `.php`) goes through a gettext call with the `ink-foundation` text domain — `<?php esc_html_e( '…', 'ink-foundation' ); ?>` / `__( '…', 'ink-foundation' )`. **No raw literal user-facing text in pattern/template markup.** (The Epic-6 review caught raw strings in `skryf.php`; `auth-login.php` had the same.) The `composer copy:scan` leak-scan is extended to flag bare non-whitespace text nodes in `patterns/*.php` as a cheap guardrail; structural-only patterns (no copy) are exempt.
- Heading casing: **sentence case** ("Begin skryf", not "Begin Skryf").

**Design tokens (Quality Gate A):**

- **No hardcoded colours, spacing, or unnamed type sizes** in templates/patterns/styles — everything maps to `theme.json` tokens. `theme.json` naming is the production source of truth even where Lovable names differ.

**Quality gates per template/pattern:** A design-system compliance (tokens only) · B layout consistency (mock intent or archetype) · C platform fit (stable in Site Editor; CPT/taxonomy integration works) · D language compliance.

### Development Workflow Rules

- **Production hygiene — nothing diagnostic/migration on production.** Loco Translate, Code Snippets, Simple CSS, WP Migrate Lite, String Locator are **staging/authoring-only**; never installed or left active on production.
- **Translation workflow:** author Afrikaans `.po/.mo` for surviving 3rd-party plugins **on staging** with Loco → **commit `.po/.mo` to version control** → production loads them from `wp-content/languages/` **without Loco present**. New strings from ungated updates are caught by the English-leak scan, then authored on staging, committed, redeployed — **never hand-edited on production**. Prefer complete community language packs where they exist.
- **Update governance:** gate *major* plugin/core updates through staging (regression pass on custom overrides + translation refresh). Minor/security/host-forced updates can't always be gated → rely on the standing English-leak detection. The committed `.mo` is the only defence for premium/niche plugins (WooCommerce Memberships, PayFast, Real3D, Report Content) — re-check after their updates.
- **Migration is scripted and ordered** (binding sequence): clean DB clone → define CPTs/taxonomies in `ink-core` → users → tiers (CSV → `ink_writer_tier`, default `brons` + flag if missing) → verify subscriptions (no import script — they ride the DB clone) → classify posts (categories → CPTs; unclassifiable → `skryfwerk`, **do not hand-classify at volume**) → library/training (keep `/biblioteek/` `/opleiding/` URL prefixes) → posts **+ 301 redirects** (record old permalink before reassignment) → InkPols → sponsors → rebuild nav → verify redirects/media/BP → smoke-test → DNS cutover.
- **Redirects are mandatory** — every CPT reassignment that changes a URL emits a 301.
- **Friendships → follow:** migration converts each BuddyPress friendship into **two** mutual follow records.
- **Don't clone `wp_options` wholesale** — carry forward only deliberate values (site URL/name, `af` locale). SEO config is set up fresh in Rank Math.
- **Org placeholders:** use clearly-marked placeholders (`[stigtingsjaar]`, `[regstatus]`); never ship US "501(c)(3)" wording. Real values are a pre-launch content gate.

### Critical Don't-Miss Rules

**Never do:**

- ❌ Put business logic in the theme, or content models in `functions.php`. (→ `ink-core`.)
- ❌ Conflate subscription entitlement with writer tier. (See THE conflation rule.)
- ❌ Port Lovable React/JSX, copy Tailwind classes, or use its mock data/`localStorage` as the data model.
- ❌ Lift English placeholder copy from the mockup into the UI.
- ❌ Hardcode colours/spacing/type — always `theme.json` tokens.
- ❌ Ship English to the front end or in `ink-core` admin labels; ship English `.mo` for `ink-core`.
- ❌ Use WP comments for engagement — they're disabled site-wide.
- ❌ Reactivate retired plugins (Youzify, WPBakery/Qode stack, Yoast, Loginizer, Invite Anyone, PDF Embedder, etc.) or build a feature a kept plugin already covers.
- ❌ Hand-classify the `skryfwerk` migration bucket at volume.
- ❌ Reassign a post's CPT/URL without emitting a 301.
- ❌ Build features needing per-item manual editorial linking (use shared taxonomy).
- ❌ Install Loco / migration / diagnostic tools on production.
- ❌ Hit the live PayFast gateway in tests (sandbox only).

**Edge cases agents must handle:**

- Expired/suspended membership → submission denied, but the account, its tier, and published work persist.
- Missing/ambiguous tier on import → default `brons` + flag (don't guess Silwer/Goud).
- Afrikaans i18n leak vectors beyond templates: error/validation/status messages, plugin-composed sentences (BuddyPress notifications, Woo order phrasing), **transactional emails**, **plugin JavaScript** strings (needs the plugin's JS `.json` translations, separate from `.mo`), and out-of-band outputs (REST/AJAX/feeds).
- Real3D Flipbook viewer controls are plugin JS — translate via its JS translations, not `.mo`.

**Security:** Cloudflare-locked origin (no direct origin traffic); staff 2FA on editor/administrator; escape-on-output + sanitise-on-input + nonces everywhere; PayFast is off-site → keep PCI scope low (never store card data).

---

## Usage Guidelines

**For AI Agents:**

- Read this file before implementing any code.
- Follow ALL rules exactly as documented. When in doubt, prefer the more restrictive option.
- Authoritative companions: `docs/specs/ink-consolidated-spec.md` (full spec), `_bmad-output/planning-artifacts/epics.md` (**epics/stories — source of record**, BMAD-conformant), `docs/afrikaans-terms.md` (glossary), `docs/ui-copy-translations.md` (UI copy), `docs/design-handoff/` (tokens, page-map). This file distills the load-bearing rules; those documents hold the detail. **Note:** `docs/specs/ink-feature-list.md` is a **superseded companion input** (2026-06-20) — do not treat it as the living epics list; epics/stories changes go in `epics.md`.
- **Planning artifacts span two locations** (this tripped a prior session): BMAD-native artifacts — PRD, `architecture.md`, UX (`ux-designs/`), `epics.md`, sprint-change-proposals — live under `_bmad-output/planning-artifacts/`; the spec, glossary, UI copy and design-handoff are living **companions** under `docs/`. Always consult this companion map before inferring an artifact is "missing" from a folder glob.
- Update this file if new binding patterns emerge.

**For Humans:**

- Keep this file lean and focused on agent needs.
- Update when the technology stack, plugin set, or a resolved decision (§14) changes.
- Review periodically; remove rules that become obvious over time.

Last Updated: 2026-06-25

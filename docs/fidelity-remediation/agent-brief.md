# Agent Brief — Fidelity Remediation Pass

**Read `README.md` in this folder first.** It holds the method, the hard rules (R1–R7),
the finding taxonomy and the INK page-resolution chain. This brief does not repeat them.

**Status of this document:** the inventory in §2 is **owner-edited**. Once the owner has
corrected it, its pairings are authoritative — do not re-derive them, and do not "improve"
a pairing the owner has set.

---

## 1. Your objective

Bring `ink-foundation` to visual fidelity with the Lovable reference, page by page, using
the pipeline in `README.md` §4 and producing the artifacts in `README.md` §7.

Bring `ink-foundation` to visual fidelity with the Lovable reference, in the four stages
set out in `README.md` §4.0: **audit every page → synthesise the primitives → consolidate
them into one shared stylesheet → remediate what remains.**

**Your job is Stage 1: audit every page. You change no CSS.**

1. **Ratify the inventory (§2).** Every pairing marked `PROPOSED` or `UNRESOLVED` must be
   confirmed — by reading both sides, or by asking the owner — before that page is
   audited. A wrong pairing corrupts every finding built on it (R7).
2. **Audit each page** through Phases 0–4b (`README.md` §4.1), in the order in §4 below.
   Two outputs per page: a finding document, and its primitive instances appended to
   `maps/_primitive-observations.csv`.
3. **Stop at the end of Stage 1** and hand back. Stages 2–4 begin only when every page is
   audited (R9).

The homepage (`00`) is already done through Phase 2 and serves as the worked example:
`findings/00-tuisblad-spine.md`.

### What you must not do

You will find obvious, small, tempting fixes — an inert class, a stray literal, a button
that is visibly wrong. **Record them. Fix none of them.** Every one of those is a shared
component, and fixing it on the page you happen to be looking at adds another divergent
definition. That is precisely how the current state was built
(`findings/01-primitives-audit.md` §7).

If a defect looks urgent enough to break this rule, say so to the owner and let them
decide. Do not decide it yourself.

---

## 2. Page inventory — OWNER TO RATIFY

**Base URLs**
- Lovable: `https://preview--quill-muse-heart.lovable.app`
- INK: `https://nuwe-ink.local`

### 2.1 Page pairing — open both, confirm they are the same page

This is the only table that needs owner review. Treatments are defined in `README.md` §4.6:
**A** = diff against the Lovable page · **B** = part of the page has a Lovable source ·
**C** = no Lovable source, audit against the design system instead.

| # | Lovable URL | INK URL | Treatment | Confirm |
|---|---|---|---|---|
| 00 | `/` | `/` | A | ✅ verified — both read end to end |
| 01 | `/community` | `/gemeenskap` | A | likely — 12 matching audit-ids already exist |
| 02 | `/profile` | `/my-profiel` | A | ☐ |
| 03 | `/browse` | `/ontdek` | A | ☐ |
| 04 | `/write` | `/skryf` | A | ☐ |
| 05 | `/challenges/silent-protagonist` | `https://nuwe-ink.local/uitdaging/skryf-n-storie-waarin-die-hoofkarakter-nooit-praat-nie/` | A | ☐ |
| 06 | `/writer/w1` | `https://nuwe-ink.local/author/cobus/` | A | ☐ §3.1 |
| 07 | `/read/sample` | `https://nuwe-ink.local/storie/die-wenteltrap-na-die-lig/` | A | ☐ §3.2 — INK has **6** reading page types. This one is for stories and articles. |
| 08 | `/library` | `/biblioteek` **and** `/opleiding` | A | ☐ §3.3 — which one? |
| 09 | `/auth` | `/meld-aan` **and** `/registreer` | A | ☐ §3.4 — INK splits it in two |
| 10 | `/forgot-password` | `/wagwoord-herstel` | A | ☐ |
| 11 | `/reset-password` | — probably WordPress's own `wp-login.php` | C? | ☐ §3.5 |
| 12 | any bogus path (404) | any bogus path | C? | ☐ §3.6 |
| 13 | — none | `/oor-ink` | **B** | ☐ — sponsor strip has a source, rest does not |
| 14 | — none | `/uitdagings` (the list) | **B** | ☐ — challenge card has a source, rest does not |
| 15 | — none | `/kontak` | **C** | ☐ |
| 16 | — none | `/lidmaatskap` | **C** | ☐ |
| 17 | — none | `/inkpols` | **C** | ☐ — list and single |
| 18 | — none | `/ledegids` | **C** | ☐ |
| 19 | — none | onboarding | **C** | ☐ — URL unknown |
| 20 | — none | membership under My Profiel | **C** | ☐ — URL unknown |
| 21 | — none | `/qa-bloks` | — | ☐ — QA scaffold; in scope at all? |
| 22 | `https://preview--quill-muse-heart.lovable.app/read/s2` | `https://nuwe-ink.local/gedig/piet-punte/` | A | ☐ §3.2 — INK has **6** reading page types. This one is for poetry. |

**Rows 00–12** are the thirteen Lovable routes, taken from `ink-lovable/src/App.tsx:30-45`.
That list is definitive: `src/pages/` holds exactly thirteen files and no others. The
`:id` / `:slug` routes are shown with the concrete sample values the mockup links to —
`/read/sample` (`HeroSpotlight.tsx:71`), `/challenges/silent-protagonist`
(`HeroSpotlight.tsx:30`), `/writer/w1` (`Community.tsx:206`).

**Rows 13–21** are INK pages the mockup never had. They are *not* out of scope — see
`README.md` §4.6. They are scheduled after the A-pages because their audit needs the
component recipe catalogue that the A-pages produce.

**INK URL provenance.** `/`, `/ontdek`, `/biblioteek`, `/opleiding`, `/uitdagings`,
`/inkpols`, `/gemeenskap`, `/oor-ink` and `/kontak` are the canonical IA routes from
`ink-core/src/Migration/NavigationRebuilder.php:101-140`. `/skryf`, `/my-profiel`,
`/meld-aan`, `/registreer` and `/lees` are read from the live header and footer patterns.
The rest are derived from the WordPress `page-<slug>.html` template convention and are
**unverified** — the agent confirms them against the running site before use (R1).

### 2.2 Where the code lives — agent reference, keyed by row

No owner review needed. Fill in the blanks as pairings are confirmed.

| # | Lovable file | INK template | INK patterns / dynamic blocks |
|---|---|---|---|
| 00 | `pages/Index.tsx` + `components/home/*` | `front-page.html` | `hero`, `featured-grid`, `borg-strook`, `cta-band`, `header-main`, `footer-main` + `ink/huidige-uitdaging`, `ink/wenner-kollig`, `ink/uitgesoekte-bydraes`, `ink/borg-strook` |
| 01 | `pages/Community.tsx` | `page-gemeenskap.html` | `gemeenskap.php` |
| 02 | `pages/Profile.tsx` | `page-my-profiel.html` | `my-profiel.php`, `profile-summary.php` |
| 03 | `pages/Browse.tsx` | `page-ontdek.html` | `ontdek.php` |
| 04 | `pages/Write.tsx` | `page-skryf.html` | `skryf.php` |
| 05 | `pages/Challenge.tsx` | `single-uitdaging.html` | `reading-uitdaging.php` |
| 06 | `pages/Writer.tsx` | `author.html` | `profile-summary.php`? |
| 07 | `pages/ReadStory.tsx` | `single-storie`, `single-gedig`, `single-artikel`, `single-opleiding_artikel`, `single-biblioteek_item`, `single-inkpols_uitgawe` | the six `reading-*.php` |
| 08 | `pages/Library.tsx` | `archive-biblioteek_item.html`, `archive-opleiding_artikel.html` | `biblioteek.php`, `opleiding.php` |
| 09 | `pages/Auth.tsx` | `page-meld-aan.html`, `page-registreer.html` | `auth-login.php`, `auth-register.php` |
| 10 | `pages/ForgotPassword.tsx` | `page-wagwoord-herstel.html` | `auth-forgot-password.php` |
| 11 | `pages/ResetPassword.tsx` | — | `assets/css/wp-login-brand.css` |
| 12 | `pages/NotFound.tsx` | `index.html` (fallback — no `404.html`) | — |
| 13 | `components/home/SponsorsSection.tsx` (strip only) | `page-oor-ink.html` | `oor-ink.php`, `borg-erkenning.php` |
| 14 | `components/home/ChallengeSection.tsx` (card only) | `archive-uitdaging.html` | `uitdaging.php` |
| 15 | — | `page-kontak.html` | `kontak.php` |
| 16 | — | `page-lidmaatskap.html` | `lidmaatskap.php`, `lidmaatskap-hernu.php` |
| 17 | — | `archive-inkpols_uitgawe.html`, `single-inkpols_uitgawe.html` | `inkpols.php`, `reading-inkpols.php` |
| 18 | — | `page-ledegids.html` | `ledegids.php` |
| 19 | — | `page-onboarding.html` | `onboarding.php` |
| 20 | — | `page-my-profiel-lidmaatskap.html` | — |
| 21 | — | `page-qa-bloks.html` | `qa-bloks.php` |

`src/components/home/HeroSection.tsx` exists but is imported by nothing — dead code.
Exclude it.

---

## 3. Open questions for the owner

Each of these changes what gets compared. Do not resolve them by guessing.

### 3.1 — `/writer/:id` target
`docs/design-handoff/page-map.csv:10` names the target `single-skrywer`. **No template by
that name exists.** The full `templates/` listing (29 files) contains `author.html`, which
is WordPress's author-archive template and the natural fit. Proposed correction:
`Writer.tsx` → `author.html`. Confirm, and correct `page-map.csv`.

### 3.2 — `/read/:id` fans out 1:6
Lovable has a single reading route; INK has six reading templates (storie, gedig, artikel,
opleiding-artikel, biblioteek-item, inkpols-uitgawe), each with its own
`patterns/reading-*.php`. `page-map.csv` already splits `lees-storie` and `lees-gedig` as
separate rows. Question: is `ReadStory.tsx` the reference for **all six**, or only for
storie, with the others deliberately differentiated? This determines whether one finding
set or six are produced.

### 3.3 — `/library` fans out, and the targets in `page-map.csv` do not exist
`page-map.csv:5-6` names `page-opleiding` and `page-biblioteek`. **Neither exists as a
template.** What exists: `archive-opleiding_artikel.html` and `archive-biblioteek_item.html`
as templates, plus `patterns/opleiding.php` and `patterns/biblioteek.php`.

Note the two readings, and do not collapse them without confirmation: either those pages
are the archive templates, or they are WP pages using the generic `page.html` with the
pattern inserted as content. **This must be verified against the running site, not
inferred from filenames** (R1). Also confirm whether `Library.tsx` is the reference for
both, given `page-map.csv` marks opleiding `layout-reference` and biblioteek
`partial-reference`.

### 3.4 — `/auth` fans out 1:2
`Auth.tsx` is one page (`page-map.csv:17` describes tabs); INK splits sign-in and
registration into two templates and two patterns. Confirm the pairing and whether the
tabbed-vs-split difference is a `SCOPE` decision to record or a `STRUCTURE` finding to fix.

### 3.5 — `/reset-password` has no identified INK page
No template matches. `assets/css/wp-login-brand.css` exists, which suggests password reset
runs through WordPress core's `wp-login.php` with branding applied rather than a theme
template. Confirm; if so, record it as out of scope for structural comparison and note
what the branding pass covers instead.

### 3.6 — 404
`NotFound.tsx` exists in Lovable. There is no `404.html` in `templates/`; WordPress falls
back to `index.html`. Confirm whether a 404 page is in scope for this pass.

### 3.7 — `PRIM-4`: the replicated card recipe
`patterns/hero.php:11-16` deliberately replicates the `is-style-ink-card` recipe onto the
challenge block's own markup, so that a collapsing block never renders an empty card. The
constraint is real; the replication is what drifts. Changing it means revisiting a
documented deliberate decision (R6), so it needs the owner's call before implementation.
See `findings/01-primitives-audit.md` §6.

### 3.8 — Page order
§4 proposes a work order. Confirm or reorder — the owner's priorities should drive it, not
the inventory's numbering.

---

## 4. Proposed work order

Ordered by *evidence available* and *blast radius*, not by page importance.

All of these are **Stage 1 audits**. None of them changes CSS.

1. **00 `/` tuisblad** — Phase 2 complete. Resume at Phase 3.
2. **01 `/community`** — 12 audit-id pairs already exist; cheapest way to validate that
   the Phase 3/4 tooling works on a second page.
3. **02 `/profile`** — recent work on this page (`docs/my-profiel-rebuild-strategy.md`,
   several commits on the current branch); highest chance of fresh divergence.
4. **03 `/browse`**, **04 `/write`**, **05 `/challenges/:slug`** — self-contained pages.
5. **06 `/writer/:id`** — after §3.1 is resolved.
6. **07 `/read/:id`** — after §3.2 is resolved. Largest fan-out; do it once the method is
   proven.
7. **08 `/library`** — after §3.3 is resolved.
8. **09–10 auth pages** — after §3.4.
9. **11–12** — only if the owner rules them in scope.

Then, and **only** once the A-pages above are measured and `maps/_recipes.csv` exists
(`README.md` §4.6):

10. **13–14 hybrid pages** — `/oor-ink`, `/uitdagings`. Diff the regions that have a
    Lovable source; conformance-audit the rest.
11. **15–20 INK-only pages** — `/kontak`, `/lidmaatskap`, `/inkpols`, `/ledegids`,
    onboarding, membership. Conformance audit against the token set + recipe catalogue.
12. **21** — QA scaffold, if in scope at all.

C-pages are audited here alongside the rest — their primitives belong in the register like
everyone else's. Only the `OFF-RECIPE` half of their assessment waits for Stage 2
(`README.md` §4.6).

### When Stage 1 is complete

Hand back with:

- a finding document for every inventory row
- `maps/_primitive-observations.csv` covering every page
- the `component_kind` vocabulary as you extended it
- a short list of anything you deliberately left unfixed that worries you

Stage 2 (synthesis) and Stage 3 (consolidation into `assets/css/primitives.css`) are
described in `README.md` §4.7. **Do not start them.** They need the complete register and
an owner ratification step.

Header and footer are shared by every page. Fix them **once**, on 00, and note in each
later finding that they were verified there rather than re-reporting them per page.

---

## 5. Per-page procedure

For each ratified row:

1. **Phase 1 — read.** The Lovable page component plus every component it imports
   (including the `components/ui/*` shadcn primitives it uses — a `<Button variant="…">`
   is not self-describing). The INK template plus its full resolution chain: template →
   parts → patterns → `wp:ink/*` render callbacks → `functions.php` block styles
   (`README.md` §3). **Read them; do not grep them** (R1).
2. **Phase 2 — spine.** *Treatment A/B only; Treatment C has nothing to align against and
   skips to step 3.* Write `findings/NN-<page-slug>-spine.md`. Follow the shape of
   `findings/00-tuisblad-spine.md` exactly: files-read list, section spine table,
   classified findings with file:line on both sides, method notes, conclusion + limits.
3. **Phase 3 — join key.** Add `data-audit-id` to Lovable first, then INK. Convention:
   `<section>-<role>`, kebab-case, as in `patterns/hero.php` and `patterns/gemeenskap.php`.
   Write `maps/<page-slug>.csv`.
4. **Phase 4 — capture + diff.** Rendered DOM + `getComputedStyle` on both sides, keyed by
   audit-id. Generate `findings/NN-<page-slug>-drift.md`. Captures to
   `tmp/fidelity-captures/` — never committed.
5. **Phase 4b — register the primitives.** Append one row per primitive instance to
   `maps/_primitive-observations.csv` (`README.md` §4.1, Phase 4b), including the
   `load_scope` vs `used_in` cross-check that catches inert classes. Record, do not judge.
6. **Phase 5 — remediation — is Stage 4. Do not run it.**

Commit per page, per phase. Do not batch multiple pages into one commit.

---

## 6. Definition of done — per page

Treatment C pages skip the spine and the pairing items; everything else applies to all
three treatments.

- [ ] Inventory row ratified by the owner, with a treatment assigned
- [ ] Every file in the resolution chain read end to end, and listed in the finding
- [ ] *(A/B)* Spine table produced; every section accounted for as present / missing / extra
- [ ] *(C)* Every component on the page matched to a `maps/_recipes.csv` entry, or the gap
      escalated rather than reported as a finding
- [ ] Every finding classified, with file:line on **both** sides
- [ ] Disclosure comments checked before raising any finding (R6)
- [ ] `data-audit-id` coverage on every visually meaningful element, both sides
- [ ] `maps/<page-slug>.csv` committed
- [ ] Phase 4 diff generated and **self-verified** against a fresh `getComputedStyle`
      sample (`README.md` §8.4)
- [ ] Every primitive instance on the page appended to `maps/_primitive-observations.csv`,
      with `load_scope` checked against `used_in`
- [ ] Any new `component_kind` added to the controlled vocabulary, not invented inline
- [ ] **No CSS, block style or pattern class changed** — `git diff` touches only `docs/`
      and `maps/` (plus `data-audit-id` attributes from Phase 3)
- [ ] `SCOPE` and `DIVERGENCE` items escalated to the owner, never silently reverted

---

## 7. When to stop and ask

Stop and ask the owner — do not proceed on an assumption — when:

- A pairing in §2 is `UNRESOLVED`, or reading both sides contradicts a `PROPOSED` pairing.
- A finding would change Afrikaans copy (R2). Copy changes are never part of a fidelity
  fix.
- A difference looks deliberate: check for a disclosure comment first, then ask (R6).
- A `SCOPE` finding would remove INK functionality that the mockup simply does not have.
- The fix for a `STRUCTURE` finding would require changing a dynamic block's output in a
  way that affects other pages.

A question costs one message. A wrong assumption costs a page of invalidated findings.

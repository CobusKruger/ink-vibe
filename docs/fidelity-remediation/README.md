# Fidelity Remediation — Strategy

**Status:** active · **Opened:** 2026-09-23 · **Branch:** `feat/epic-19-theme-fidelity`

This folder holds the method, the page inventory, the per-page maps and the generated
findings for bringing the `ink-foundation` theme to visual fidelity with the Lovable
reference design.

Read this file **before** doing any fidelity work. It is written to be picked up cold by
an agent with no prior context.

---

## 1. Why this exists

Earlier fidelity passes failed in a specific, repeatable way:

- The theme was reimplemented rather than ported, so element-level correspondence with
  the reference was never recorded anywhere.
- Comparison was done by eye — browsing both sites, or reading screenshots. Vision
  resolves "bigger / warmer / missing", not `28px vs 30px` or `#8B5E3C vs #8A5C3A`. The
  differences that make a page read as "only a passing resemblance" sit below that
  threshold.
- Absence was repeatedly inferred from `grep` output instead of from reading files. This
  produced confident, wrong claims (see `findings/00-tuisblad-spine.md` §5).

The correction is not "look harder". It is to convert the problem from perception into
**text diffing against a recorded join key**, and to make every claim traceable to a file
and line that was actually read.

---

## 2. Hard rules

These are non-negotiable. Violating any one of them is how the previous passes failed.

| # | Rule |
|---|---|
| **R1** | **Never assert presence or absence from a `grep`.** Greps may *locate*; they may not *conclude*. Any claim that something is missing, extra, or unchanged must come from a full read of every file that could contain it. If a file is too large to read, parse it — do not sample it. |
| **R2** | **The reference is Lovable's *structure*, not Lovable's *copy*.** Afrikaans copy is the source of truth and must never be replaced with the mockup's English, nor AI-retranslated. Porting structure and replacing copy are two different operations; only the second was ever asked for. |
| **R3** | **Join on `data-audit-id`, assigned Lovable-first.** Class names do not need to match and should not be renamed — `ink-`-prefixed BEM is correct for a WP theme. The audit-id is the only identity contract between the two trees. |
| **R4** | **Source is not rendered output.** No claim about a computed value (size, colour, spacing, weight, radius, shadow) may be made from reading source. WordPress composes pages from templates + parts + patterns + dynamic blocks + block-library CSS + `theme.json`; Lovable composes them from JSX + Tailwind + shadcn variants. Style claims require a rendered DOM capture. |
| **R5** | **No screenshots for fidelity work.** They are permitted only for gross structural triage (a whole section absent, elements in the wrong order). They are never evidence for a value. |
| **R6** | **Deliberate divergences are recorded, not "fixed".** Several exist already and are documented in-place in the pattern PHP (e.g. the CTA band's bottom padding, `patterns/cta-band.php:21`). Before changing anything that looks wrong, check for a disclosure comment. If one exists, the finding is `DIVERGENCE (accepted)` and the work stops there. |
| **R7** | **When the mapping is uncertain, ask — do not guess.** A wrong pairing silently corrupts every finding derived from it. |
| **R8** | **A page-scoped stylesheet may not define or redefine a primitive.** Buttons, cards, pills, badges, form controls, headings and section bands belong in one shared stylesheet loaded on every page. This is the **end state**, reached in Stage 3. During Stage 1 you *record* every violation into the observation register and **fix none of them** — fixing primitives one page at a time is exactly how the current divergence was built (`findings/01-primitives-audit.md` §7). |
| **R9** | **Audit every page before consolidating anything.** The primitive set is a *discovery*, not an assumption: you cannot know what the shared components are, or which variant of each is correct, until every page has been surveyed. Stage 2 does not begin until Stage 1 is complete for all pages. |

---

## 3. How to resolve an INK page to its complete markup

This is the non-obvious part of the INK side and the source of the earlier mistakes. A
WordPress block theme page is assembled through a chain; **all four links must be read**:

```
wp-content/themes/ink-foundation/
  templates/<name>.html          ← 1. the template. Start here.
    <!-- wp:template-part {"slug":"header"} -->
      parts/<slug>.html          ← 2. template parts (usually one-liners that
                                       forward to a pattern)
    <!-- wp:pattern {"slug":"ink-foundation/<x>"} -->
      patterns/<x>.php           ← 3. patterns. These are PHP; they execute
                                       server-side and may branch (e.g. auth state).
    <!-- wp:ink/<block> -->
      wp-content/plugins/ink-core/src/**/<Class>.php
                                 ← 4. dynamic blocks. The render callback emits the
                                       real DOM. This is where most of the markup is.
```

Plus two sources that are **not** in that chain and are easy to miss:

- **`functions.php`** — registers block *styles* (`is-style-ink-header`,
  `is-style-ink-footer`, `is-style-ink-primary`, …). Site-wide treatment lives here, not
  in `home.css`.
- **`theme.json`** + `styles/*.json` — the token layer (palette, font sizes, spacing
  presets, layout widths). Everything `var:preset|…` resolves through it.

**A section built from `wp:ink/*` blocks has no `"slug"` attribute in the pattern form.**
Filtering a template for `"slug":"…"` will not show it. This is exactly the error that
produced the false "the challenge section is missing" claim.

To find the class behind a block name, grep for the block name string across
`wp-content/plugins/` — the renderer declares it as a `BLOCK` constant — then **read the
class**.

### The Lovable side

```
ink-lovable/
  src/App.tsx                    ← the route table. The definitive page list.
  src/pages/<Page>.tsx           ← one file per route
  src/components/home|layout|reading/*.tsx
  src/components/ui/*.tsx        ← shadcn primitives. A <Button variant="literary">
                                    expands here — read it before claiming what it renders.
  tailwind.config.ts             ← the scale (spacing, fontSize, colors)
  src/index.css                  ← the CSS-variable token layer
  DESIGN_TOKENS.md               ← prose summary of the above
```

---

## 4. The pipeline

### 4.0 Four stages, in order

The per-page phases below are **Stage 1 only**. Nothing is remediated until every page has
been audited.

| Stage | What | Gate to leave it |
|---|---|---|
| **1 · Audit** | Run Phases 0–4 on every page in the inventory. Produce findings and record every primitive instance into the observation register. **Change no CSS.** | Every inventory row has a finding document and its observations registered |
| **2 · Synthesise** | Group the register into the primitive inventory: for each component kind, every distinct definition found. Choose the canonical recipe for each. (§4.7) | `maps/_recipes.csv` complete and ratified by the owner |
| **3 · Consolidate** | Build the single shared stylesheet from the recipes, load it everywhere, migrate pages onto it, retire the bespoke definitions. (§4.7) | No primitive defined outside the shared layer (R8) |
| **4 · Remediate** | Work the remaining per-page findings — the genuinely page-specific drift that survives consolidation. | Findings closed, Phase 4 re-run clean |

Why this order: a primitive fixed during Stage 1 is a primitive fixed *on one page*, which
adds another divergent definition. And the canonical recipe cannot be chosen from a partial
survey — three pages may hold three variants, and the right one is only visible once all
three are on the table (R9).

**Expect Stage 1 to feel slow and produce no visible improvement.** That is the design.

### 4.1 Per-page phases (Stage 1)

Run per page. Each phase has a defined input and a committed output.

### Phase 0 — Pair the page and pick its treatment
Establish the Lovable URL ↔ INK URL pairing from `agent-brief.md`'s inventory.
If the row is marked unverified, verify it by reading both sides before continuing.
**Output:** a confirmed row in the inventory, carrying one of the three treatments in §4.6.

### Phase 1 — Full structural read
Read, end to end: the Lovable page component and every component it imports; the INK
template and its full resolution chain (§3). No greps as evidence.
**Output:** nothing committed yet — this is the evidence base for Phase 2.

### Phase 2 — Spine + structural findings
Produce the section-level spine (an ordered table of Lovable section ↔ INK counterpart),
then the element-level structural deltas. Classify each per §5.
**Output:** `findings/NN-<page-slug>-spine.md`.

*This phase alone catches missing elements, extra invention, and mechanism differences —
and it requires no tooling. On the homepage it surfaced 12 findings.*

### Phase 3 — Install the join key
Add `data-audit-id` to every visually meaningful element in the Lovable page components,
then to the INK counterparts. Naming: `<section>-<role>`, kebab-case — follow the
existing convention in `patterns/hero.php` and `patterns/gemeenskap.php`.
Assign **from the Lovable side first**. An element with no INK counterpart to attach to
is a `MISSING` finding and is recorded, not invented.
**Output:** edits to both repos + `maps/<page-slug>.csv`.

### Phase 4 — Rendered capture + computed diff

**Tooling:** `tools/fidelity/run.sh` — see `harness-worklist.md` for the full contract and
the environment facts. Targets are listed in `tools/fidelity/targets.json`, derived from
`agent-brief.md` §2.1.

```bash
tools/fidelity/run.sh capture --url <url> --side <ink|lovable> --page <slug> \
                              --viewport 390,768,1440 --expect '<selector>,…'
tools/fidelity/run.sh diff --a <lovable.ndjson> --b <ink.ndjson> --out <finding.md>
tools/fidelity/run.sh verify --capture <file.ndjson> --sample 30
tools/fidelity/determinism-check.sh <url> <side> <page> <viewport> 3
```

**Preconditions — both are gates, not suggestions:**

1. `determinism-check.sh` passes for the page. A capture that is not reproducible makes
   every finding derived from it noise.
2. `run.sh verify` passes on the capture. `README.md` §8.4 forbids shipping a Phase 4
   finding whose numbers do not reproduce against a live read.

**Chromium cannot run inside the macOS sandbox**, and a preview server cannot bind a
socket there — see §9.
Capture rendered DOM and `getComputedStyle` for every audit-id'd node on both sides.
Diff numerically per property.
**Output:** `findings/NN-<page-slug>-drift.md` (generated, not hand-written).

Captures are intermediate output: write them to `tmp/fidelity-captures/` (gitignored),
never to `docs/`. They are regenerable and must not be committed.

### Phase 4b — Register every primitive instance
For each component on the page that is a shared kind — button, card, pill, badge, form
field, label, tab, eyebrow, section band, avatar — append one row per instance to
`maps/_primitive-observations.csv`.

**Tooling:** `tools/fidelity/run.sh primitives --in <capture.ndjson> [--in …] --out
maps/_primitive-observations.csv [--append]`. It classifies each node and emits the row.

A rendered capture cannot say *which stylesheet* a rule came from, so `defined_in`,
`load_scope` and `used_in` come out **empty** and must be filled by a source-side pass —
the method is demonstrated in `findings/01-primitives-audit.md`. That attribution is what
turns a list of variants into an actionable finding, so do not skip it. This is the raw material Stage 2 groups; a page is not
audited until its primitives are registered.

**Record, do not judge.** Do not decide here which variant is right, and do not change a
declaration. Two definitions that look identical still both get rows — Stage 2 determines
whether they truly match.

Columns:

```
page,component_kind,selector,defined_in,load_scope,used_in,declarations,audit_id,note
```

| Column | Meaning |
|---|---|
| `page` | inventory row slug (`tuisblad`, `gemeenskap`, …) |
| `component_kind` | from the controlled list below — a new kind must be *added to the list*, not invented inline |
| `selector` | the selector as written |
| `defined_in` | `theme.json` · `functions.php:<line>` · `assets/css/<file>:<line>` |
| `load_scope` | where that definition actually loads — `site-wide`, or the `is_page()`/`is_singular()` condition gating it |
| `used_in` | the pattern / render-callback / template that emits the class |
| `declarations` | the full declaration body, normalised to one line |
| `audit_id` | the Phase 3 join key, where one exists |
| `note` | free text — e.g. "inert here, stylesheet does not load" |

Seed `component_kind` list — extend it as pages are audited, and record additions in this
file so the vocabulary stays shared:

```
button-primary  button-outline  button-ghost  button-sage  button-icon  button-size
card  pill  badge  eyebrow  section-band
form-field  form-label  form-hint  search-field  filter-chip  tab
avatar  meta-row  count-chip  link-underline
```

A `load_scope` that is narrower than the set of pages in `used_in` is a live bug — flag it
in `note`. That single cross-check is what surfaced `PRIM-1`.

### Phase 5 — *(Stage 4 — do not run during the audit)*
Remediation. Held until Stages 2 and 3 are complete. See §4.0.

---

### 4.6 Page treatments — including pages with no Lovable counterpart

Lovable is a **design mockup**, not a sitemap. INK has pages the mockup never had, and
always will. Those pages are not exempt from fidelity — they must speak the same design
language — they simply cannot be checked by diffing against a counterpart. Every page
therefore carries one of three treatments, assigned in Phase 0.

| Treatment | When | Pipeline |
|---|---|---|
| **A · Reference diff** | A Lovable page exists for it | Phases 1–5 as written |
| **B · Hybrid** | Only part of the page has a Lovable source (e.g. Oor INK's sponsor strip comes from `SponsorsSection.tsx`, the rest does not) | Treatment A on the referenced regions; Treatment C on the remainder. Mark the boundary explicitly in the finding. |
| **C · Conformance audit** | No Lovable source at all | Phases 1, 3, 4 run unchanged; Phase 2 produces no spine (there is nothing to align). The diff target is the **design system**, not a counterpart page — see below. |

#### What Treatment C compares against

Two artifacts. The first is available immediately; the second is a **Stage 2 output**, so
the two halves of a C-page audit happen at different times:

- the `OFF-TOKEN` check runs in Stage 1, like any other page — the token set is already
  known;
- the `OFF-RECIPE` check runs in **Stage 4**, after synthesis has produced the canonical
  recipes. Attempting it earlier has nothing to check against and produces noise.

In Stage 1 a C-page still yields its full primitive observation set (Phase 4b) — which is
the main reason C-pages are audited alongside the rest rather than deferred.

**1. The token set.** Every computed value on the page must be a member of the design
system's vocabulary:

- Lovable side: `tailwind.config.ts` + `src/index.css` + `DESIGN_TOKENS.md`
- INK side: `theme.json` + `styles/*.json`
- Already reconciled in: `docs/design-handoff/tokens/theme-tokens.json` and
  `docs/design-handoff/tokens/token-map.md`

A computed colour, font-size, spacing step, radius or shadow that is **not** a token value
is an `OFF-TOKEN` finding. This is fully mechanical: capture computed styles (Phase 4),
test each value for membership in the token set. No reference page needed.

**2. The component recipe catalogue.** Tokens alone are not enough — a card built from
sanctioned tokens in an unsanctioned combination still looks wrong. So the A-pages produce
a catalogue of recurring component recipes, each recorded as the exact token combination
the reference uses:

```
maps/_recipes.csv
recipe,property,value,token,source_page,source_audit_id
card,background,#…,surface-alt,tuisblad,hero-uitdaging-kaart
card,border-radius,12px,radius.md,tuisblad,hero-uitdaging-kaart
card,border,1px solid #…,border,tuisblad,hero-uitdaging-kaart
card,box-shadow,…,shadow.sm,tuisblad,hero-uitdaging-kaart
badge-pill,…
eyebrow,…
section-band,…
```

Seed it from the recipes the theme already names — `is-style-ink-card`,
`is-style-ink-primary`, `is-style-ink-outline`, `is-style-ink-ghost`,
`is-style-ink-header`, `is-style-ink-footer` (all registered in `functions.php`) — and
extend it as each A-page is measured. A component on a C-page whose computed values
diverge from its recipe is an `OFF-RECIPE` finding.

`maps/_recipes.csv` is a **cross-page** artifact. It is the durable output of this whole
exercise: it is what lets a page that never had a mockup still be held to the design
language, and what will let future pages be built correctly the first time.

#### Order

In **Stage 1**, C-pages are audited alongside A- and B-pages — every page must be surveyed
before synthesis begins (R9), and a C-page's primitives are as much a part of the register
as anyone else's. Only the `OFF-RECIPE` half of their assessment is deferred to Stage 4.

---

### 4.7 Stages 2 and 3 — synthesise the primitives, then consolidate them

These begin only when **every** page in the inventory has been audited and registered
(R9). The register is the input; a partial register produces a wrong canonical recipe.

#### Stage 2 · Synthesise

1. **Group the register by `component_kind`.** Every row for `search-field` together,
   every row for `button-primary` together, and so on.
2. **Within each group, collapse identical `declarations` into one variant.** The output
   per kind is *N distinct variants across M instances*. `N = 1` is already consistent.
   `N > 1` is drift. `N = 1, M > 1` with separate definitions is duplication — still a
   defect, because nothing keeps the copies in step.
3. **Diff the variants property by property.** Record what actually differs: often two
   variants share padding, radius and colour and differ only in type or background, which
   is why the drift reads as "nearly right" rather than obviously broken.
4. **Choose the canonical recipe per kind.** Prefer, in order: the variant backed by a
   Lovable reference measurement (Phase 4 on an A-page); then the variant already
   registered as a block style; then the most-used variant. Where a Treatment C page's
   variant is the only one, it still needs a reference-backed sibling or an owner ruling —
   do not canonise an unreferenced variant silently.
5. **Write `maps/_recipes.csv`** — one row per kind per property, citing where the value
   came from. **Owner ratifies before Stage 3.**

Deliverable: a synthesis document, `findings/_primitive-synthesis.md`, showing for each
kind the variant count, the property-level diff, and the chosen canonical recipe with its
justification.

#### Stage 3 · Consolidate

Target architecture — **one shared stylesheet, loaded on every page**:

```
assets/css/primitives.css     ← every primitive recipe, one definition each
                                enqueued unconditionally (no is_page() gate)
```

Then, in order:

1. Build `primitives.css` from the ratified recipes.
2. Enqueue it site-wide, ahead of the page-scoped sheets.
3. Migrate consumers onto it — patterns and `ink-core` render callbacks reference the
   shared class instead of a page-local one.
4. **Delete** the bespoke definitions from `theme.json` global styles and from the
   page-scoped stylesheets. Deletion is the point; leaving them shadowed re-creates the
   problem.
5. Reconcile the block styles in `functions.php`: a registered style's `inline_style`
   must not restate a recipe that now lives in `primitives.css`.
6. Re-run Phase 4 on a sample of pages to confirm the computed values are unchanged where
   they were already correct, and corrected where they were not.

After Stage 3, R8 is enforceable: any primitive definition outside `primitives.css` is a
defect.

What remains for Stage 4 is genuinely page-specific — layout, composition, and the drift
that is not attributable to a shared component.

---

## 5. Finding taxonomy

Every finding carries exactly one class. The class determines the fix.

| Class | Meaning | Fix |
|---|---|---|
| `MISSING` | Present in Lovable, no counterpart in INK | Markup — port the element |
| `EXTRA` | Present in INK, no counterpart in Lovable | Delete, or reclassify as `SCOPE` |
| `STRUCTURE` | Both present, different DOM shape (nesting, tag, wrapper count) | Markup — reshape |
| `MECHANISM` | Same visual intent, different implementation (DOM node vs CSS background; `<ul>` vs flex `<div>`s) | Usually none; verify computed output matches |
| `SCOPE` | INK deliberately carries more or less than the mockup (extra nav items, real-data repeaters) | Owner decision — never silently reverted |
| `DIVERGENCE (accepted)` | A disclosed, deliberate difference (see R6) | None. Cite the disclosure. |
| `DRIFT` | Matched pair, differing computed values | CSS |
| `OFF-TOKEN` | Treatment C only — a computed value that is not a member of the token set (§4.6) | CSS — replace the literal with the nearest token, or escalate if no token fits |
| `OFF-RECIPE` | Treatment C only — sanctioned tokens in a combination that does not match the component's catalogued recipe (§4.6) | CSS — conform to the recipe, or escalate if the component is genuinely new |
| `PRIMITIVE` | A shared component has more than one definition, or its definition sits in a stylesheet that does not load everywhere it is used | **Record it, defer it.** Raised during Stage 1, resolved in Stages 2–3 at the shared layer. Never fixed on a page (R8). A `PRIMITIVE` finding fixed during the audit is a rule violation, however small it looks. |

`DRIFT`, `OFF-TOKEN` and `OFF-RECIPE` may only be produced by Phase 4. Any of them sourced
from reading CSS instead of measuring rendered output is a rule violation (R4).

An `OFF-TOKEN` or `OFF-RECIPE` finding on a component that has **no** catalogue entry is
not a finding — it is a gap in the catalogue. Add the component to `maps/_recipes.csv`
from its reference-backed source, or escalate to the owner if it has none.

---

## 6. Severity order

This ordering applies to **Stage 4**, once consolidation is done. During Stage 1 nothing is
fixed at all; findings are recorded in the order they are found and sorted later.

`PRIMITIVE` findings never enter this queue — they leave the page stream entirely and are
resolved in Stages 2–3.

Within a page, work and report in this order:

1. Missing or extra **section**
2. Missing or extra **element**
3. **Box model** — width, padding, margin, gap, grid/flex configuration
4. **Colour** — background, text, border
5. **Type** — family, size, weight, line-height
6. **Micro** — letter-spacing, radius, shadow, transition

---

## 7. Folder contract

```
docs/fidelity-remediation/
  README.md                       ← this file: method + hard rules
  agent-brief.md                  ← the page inventory + execution instruction.
                                     OWNER-EDITED — treat its mapping as authoritative.
  findings/
    00-tuisblad-spine.md          ← first finding (also the method's worked example)
    01-primitives-audit.md        ← early partial sample; evidence + seed rows (§4.7)
    NN-<page-slug>-spine.md       ← Phase 2 output, hand-written
    NN-<page-slug>-drift.md       ← Phase 4 output, GENERATED — do not hand-edit
    _primitive-synthesis.md       ← STAGE 2 output: variant counts, property diffs,
                                     chosen canonical recipe per kind
  maps/
    <page-slug>.csv               ← Phase 3 output: the audit-id join table
    _primitive-observations.csv   ← STAGE 1, append-only. One row per primitive
                                     instance per page (Phase 4b). The raw material.
    _recipes.csv                  ← STAGE 2 output: the ratified canonical recipe
                                     per component kind. Input to Stage 3 and to
                                     every Treatment C conformance audit (§4.6).
```

The shared stylesheet that Stage 3 builds lives in the theme, not here:
`wp-content/themes/ink-foundation/assets/css/primitives.css`.

`NN` is a two-digit sequence in the order pages are worked, matching the inventory.

**Map CSV columns:**

```
audit_id,lovable_file,lovable_line,ink_file,ink_line,element_role,status,note
```

`status` ∈ `paired` | `missing-ink` | `missing-lovable` | `scope` | `divergence`.

**Scratch:** `tmp/fidelity-captures/` for DOM and computed-style dumps. Gitignored,
regenerable, never committed. All other transient files also go in repo-local `./tmp/`
per `CLAUDE.md`.

---

## 8. Self-check before reporting

A findings document is not ready until:

1. **Every claim cites a file and line on both sides.** A finding without a citation is
   a hypothesis, and hypotheses are not reported as findings.
2. **Every file behind a presence/absence claim was read end to end** (R1). List the
   files read at the top of the document so the claim is auditable.
3. **No `DRIFT` finding exists without a Phase 4 capture** (R4).
4. **Phase 4 output is self-verified**: for a random sample of audit-ids, the values the
   generator reports must reproduce against a fresh `getComputedStyle` read. If they do
   not, the generator is wrong and the document is withheld.
5. **Disclosure comments were checked** before any finding was raised against a
   suspicious value (R6).
6. **Stage 1 only: no CSS was changed.** If the audit touched a stylesheet, a block style
   or a pattern's classes, it exceeded its remit (R8/R9). Revert it and record it as a
   finding instead.
7. **Stage 1 only: the page's primitives are in the register** (Phase 4b), including the
   `load_scope` vs `used_in` cross-check.

---

## 9. Environment gotchas

- **Shell writes to `docs/` and `_bmad-output/` are blocked** at the sandbox FS layer,
  intentionally. Use the Write/Edit tools for files in those trees, never shell
  redirection or `sed -i`.
- **All transient files go to repo-local `./tmp/`** — not `$TMPDIR`, not `/tmp`. For
  multi-line commit messages write `tmp/commit-msg.txt` and use `git commit -F`.
- **`composer stan` needs the sandbox off** (it opens TCP and gets EPERM otherwise).
- **`git merge` in this repo needs the sandbox off** (FS deny on `docs/`).
- **Do not add commit attribution trailers** — enforced via `attribution.commit: ""`.

### The capture harness

Full contract in `harness-worklist.md`. The parts that bite:

- **Chromium cannot launch inside the macOS Seatbelt sandbox** — it is denied its Mach
  rendezvous port. A preview server also cannot bind a socket there (`listen EPERM`).
  Neither is fixable with an allowlist entry; both need the sandbox off. The commands are
  pre-approved in `.claude/settings.local.json`.
- **Node 20+ is required.** Playwright 1.63 refuses the machine default (18.17.1).
  `run.sh` points at the nvm-installed v24.12.0 and does not change your default.
- **Browsers live in `tmp/ms-playwright`** — the sandbox denies the usual
  `~/Library/Caches` location.
- **The WordPress table prefix is `wpjj_`, not `wp_`.** A stale unused `wp_users` table
  also exists; writing to it looks like it worked and WordPress never sees the row.
- **`wp-cli` cannot connect** to the Local site — `wp-config.php` hardcodes
  `DB_HOST = 'localhost'` and `--require` loads too late to override it. Use Local's own
  PHP with `-d mysqli.default_socket=<sock>`, as `tools/fidelity/set-capture-password.php`
  does, or its `mysql` client over the socket.
- **`wp-login.php` is intercepted** by `ink-core`'s `Accounts\AuthRedirects` and sent to
  `/meld-aan`. Log in there; the submit control is `<button name="wp-submit">`, not core's
  `<input id="wp-submit">`.
- **Capture as `fidelity-capture`, not `admin`** — the admin bar shifts the page ~32px and
  would corrupt every bounding box. That user is a subscriber with
  `show_admin_bar_front = false`.

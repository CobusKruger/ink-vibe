# 05 · uitdaging-enkel — spine and structural findings

| | |
|---|---|
| Page slug | `uitdaging-enkel` (inventory row 05) |
| Lovable | `/challenges/silent-protagonist` → `src/pages/Challenge.tsx` (served from an isolated build on `:4185`) |
| INK | `https://nuwe-ink.local/uitdaging/skryf-n-storie-waarin-die-hoofkarakter-nooit-praat-nie/` → `templates/single-uitdaging.html` |
| Date | 2026-10-13 |
| Phase | 2 (spine) — Phases 3/4/4b outputs referenced in §5 and at the end |
| Treatment | **A** — reference diff |

**Phase 0.** The pairing is confirmed by reading both sides. `App.tsx:34` routes `/challenges/:slug` to
`Challenge.tsx`, which ignores the slug (`useParams()` result unused, `Challenge.tsx:121`) and always renders
the same mock challenge. INK resolves the URL to the `uitdaging` CPT single template. Both are "one
challenge, full detail" pages.

**Header and footer are out of scope.** They were verified on row 00 (`maps/tuisblad.csv`), and their ids
are not re-reported here.

---

## 1. Files read end to end

**Lovable**

- `src/App.tsx` (route table)
- `src/pages/Challenge.tsx` (whole page; all data is inline mock constants)
- `src/components/ui/button.tsx` (the `literary` / `literary-outline` variants and the `lg` size; `asChild` → Radix `Slot`, so the `<Link>`/`<a>` child receives the button classes and props)
- *Not read:* `components/layout/Header.tsx`, `Footer.tsx` (row 00 scope)

**INK** — the resolution chain (README §3)

1. `wp-content/themes/ink-foundation/templates/single-uitdaging.html` — header part → `<main>` group → `wp:pattern ink-foundation/reading-uitdaging` → footer part
2. Template parts `header` / `footer` — *not read* (row 00 scope)
3. `wp-content/themes/ink-foundation/patterns/reading-uitdaging.php` (whole file, including the disclosure docblock at lines 2–58)
4. Dynamic block `ink/uitdaging-besonderhede` → `wp-content/plugins/ink-core/src/Challenges/SinglePage.php` (whole file). It is embedded twice: `variant:"kop"` (`reading-uitdaging.php:88`) and `variant:"inskrywings"` (`:120`).
5. Core dynamic blocks `core/post-title` (`:80`) and `core/post-content` (`:110`). The post body is DB content, so it was read from the **rendered** page (cache-busted `curl`, `<main>` extracted). It is two plain `<p>` paragraphs: the brief, then rules + prize as one prose sentence.
6. `wp-content/themes/ink-foundation/functions.php` (whole file; the `render_block_core/*` filters, `ink_foundation_uitdaging_cta_subtitel()` at `:1861`, `ink_foundation_icon()`, every enqueue condition, and the block styles at `:1349–1832`)
7. `theme.json` `styles.elements` / `styles.blocks` (lines 160–242) and the `styles.css` string (`:162`). The string was extracted and split per rule; every `.ink-uitdaging*`, `.ink-lees-tipe` and `.ink-btn-icon` rule was read.
8. `assets/css/home.css` lines 180–230 and 303–307 (button-size and `.ink-btn-icon` comparison only). It does not load on this page (`functions.php:935–952`, `is_front_page() || is_page('qa-bloks')`).

---

## 2. Section spine

| # | Lovable section (`Challenge.tsx`) | INK counterpart | Status |
|---|---|---|---|
| 1 | Hero — back link, badge, h1, tagline, meta row, two CTAs (`:128–188`) | `section.ink-uitdaging__hero` (`reading-uitdaging.php:68–104`) + `ink/uitdaging-besonderhede` `kop` (`SinglePage.php:311–326`) | **present**; element deltas F1–F8 |
| 2 | Prompt + sidebar — "The Prompt" h2, prompt text, literary devices, Submission Rules aside, Prize (`:190–244`) | Section with `.ink-uitdaging__opdrag` → `core/post-content` (`reading-uitdaging.php:106–114`) | **present, reduced** — one authored blob (F9–F12) |
| 3 | Resources — "Sharpen Your Craft", 4 resource cards (`:246–296`) | — | **missing** — SCOPE, disclosed (F13) |
| 4 | Submissions — eyebrow, h2, view-all link, 3-card grid (`:298–364`) | Section → `ink/uitdaging-besonderhede` `inskrywings` (`reading-uitdaging.php:116–124`, `SinglePage.php:344–397, 477–479`) | **present**; element deltas F14–F22 |
| 5 | Closing CTA — icon, h2, text, two buttons (`:366–390`) | `section.ink-uitdaging-cta` (`reading-uitdaging.php:126–158`) | **present**; F23 |

Section order matches (hero → prompt → [resources] → entries → CTA). `reading-uitdaging.php:27–36`
records the earlier fix that moved the entries list below the prompt. No INK section lacks a Lovable
counterpart.

---

## 3. Structural findings

| id | class | finding | Lovable | INK |
|---|---|---|---|---|
| F1 | MECHANISM | Hero texture: a DOM `<div>` (opacity .04, inline SVG data-URI) vs a CSS `::before` layer | `Challenge.tsx:129–134` | `theme.json:162` [`.ink-uitdaging__hero::before`]; `reading-uitdaging.php:69` |
| F2 | STRUCTURE | Hero wrappers: Lovable `section > container(px-4 py-12/20) > max-w-3xl`, with the back link outside the max-w column. INK `section(padding) > group(768)`, with the back link inside the column. One wrapper fewer. | `Challenge.tsx:135–143` | `reading-uitdaging.php:69–73` |
| F3 | STRUCTURE | Back link: bare `<a>` vs `<p>` wrapping an `<a>` | `Challenge.tsx:136–141` | `reading-uitdaging.php:73` |
| F4 | STRUCTURE | Type badge: `<span>` vs `<p class="ink-lees-tipe">`. The label copy ("Weekly Challenge" vs the data-true type) is a disclosed copy divergence, not structure. | `Challenge.tsx:144–147` | `reading-uitdaging.php:76–77` (disclosure `:39–44`) |
| F5 | DIVERGENCE (accepted) | Tagline renders only from a hand-authored excerpt; none exists, so it is absent at render. Content debt, not template. | `Challenge.tsx:153–155` | `reading-uitdaging.php:82–86` (disclosure `:50–55`) |
| F6 | SCOPE | "Editor's pick" meta item absent: no backing data, owner decision pending | `Challenge.tsx:166–169` | `reading-uitdaging.php:44–48` (disclosure); `SinglePage.php:311–326` |
| F7 | SCOPE | INK adds an Oop/Gesluit status pill to the meta row (real round state; the mockup has none) | — | `SinglePage.php:325` (docblock `:286–287`) |
| F8 | STRUCTURE | Deadline item: INK adds a label `<span>` ("Sluitingsdatum: ") and a `<time>` inside the item. Lovable has one text node. | `Challenge.tsx:158–161` | `SinglePage.php:312–316` |
| F9 | DIVERGENCE (accepted) | Prompt / Rules / Prize zones collapsed into one authored post body. The 2/3 + 1/3 grid, the Submission Rules aside card (h3, bulleted list) and the Prize block have no counterpart; rules and prize survive only as inline prose in paragraph 2. | `Challenge.tsx:192–243` | `reading-uitdaging.php:106–114` (disclosure `:14–20`, "Story 12.1 decision") |
| F10 | MISSING | "The Prompt" h2 heading. Not authored in the post body and not emitted by the pattern. Arguably inside F9's disclosure (zone chrome) — **escalate**. | `Challenge.tsx:195–197` | — (`reading-uitdaging.php:108–111`) |
| F11 | DIVERGENCE (accepted) | "Literary devices" h3 and sage chip row: listed in the disclosure as part of the authored brief; the live post body contains none | `Challenge.tsx:203–217` | disclosure `reading-uitdaging.php:15` |
| F12 | MECHANISM | Prompt text: one `whitespace-pre-line` `<div>` vs `<p>` paragraphs from `core/post-content` | `Challenge.tsx:198–200` | `reading-uitdaging.php:110` |
| F13 | SCOPE | Whole Resources section absent: "needs a new resource-link content model … flagged for a product-owner decision" | `Challenge.tsx:246–296` | disclosure `reading-uitdaging.php:20–25` |
| F14 | STRUCTURE | Entries nesting: INK `section > group(1120) > section#inskrywings > div.__inskrywings-blok > h2 + ul`. That is an extra nested `<section>` and block `<div>`, and the anchor sits on the inner section. Lovable `section#submissions > container > head row + grid`. | `Challenge.tsx:299–301` | `reading-uitdaging.php:117–120`; `SinglePage.php:355, 478` |
| F15 | MISSING | Entries head row and "Already Submitted" eyebrow (INK has only the h2) | `Challenge.tsx:301–305` | `SinglePage.php:345` |
| F16 | MISSING | "View all entries" link (md+) | `Challenge.tsx:310–315` | — (`SinglePage.php:344–397`) |
| F17 | MECHANISM | Card grid: `<div>` grid of `<Link>` cards vs `<ul>/<li>` with an `<a>` card | `Challenge.tsx:318–323` | `SinglePage.php:355, 363–364` |
| F18 | STRUCTURE | Card meta row (type pill + read time) vs pill as a direct child of the card | `Challenge.tsx:325–333` | `SinglePage.php:366–368` |
| F19 | MISSING | Read-time meta item (clock + "N min") | `Challenge.tsx:329–332` | — (`SinglePage.php:357–394`) |
| F20 | STRUCTURE | Entry title `<h3>` vs `<span>` | `Challenge.tsx:334–336` | `SinglePage.php:370` |
| F21 | STRUCTURE | Card footer: Lovable bordered footer `div` holding an author group and a stats group. INK has one `span.__inskrywing-outeur`, which is both the bordered footer and the author group. | `Challenge.tsx:340–359` | `SinglePage.php:376–391` |
| F22 | MISSING | Likes / comments stats in the card footer | `Challenge.tsx:349–358` | — (`SinglePage.php:376–391`) |
| F23 | STRUCTURE | CTA icon: `<svg>` directly vs a `<span>` wrapping the `<svg>` | `Challenge.tsx:369` | `reading-uitdaging.php:131` |

**Counts:** STRUCTURE 9 (F2, F3, F4, F8, F14, F18, F20, F21, F23) · MISSING 5 (F10, F15, F16, F19, F22) ·
MECHANISM 3 (F1, F12, F17) · SCOPE 3 (F6, F7, F13) · DIVERGENCE (accepted) 3 (F5, F9, F11). No EXTRA.

### Classification notes

- **R6 disclosures were checked first.** `reading-uitdaging.php:7–55` discloses five things: the single-blob brief, the absent resources, the badge copy, the absent Editor's pick, and the excerpt-only tagline.
  - The distinction used here: a disclosure recording a *made* decision (Story 12.1 content model; excerpt copy debt) → `DIVERGENCE (accepted)`. A disclosure explicitly deferring to the owner ("flagged for a product-owner decision") → `SCOPE`.
- **F7** is classed `SCOPE`, not `EXTRA`. The pill carries real round state (Oop/Gesluit), and `SinglePage.php:286–287` calls it "WP-only, deliberate". Removing it would remove INK functionality (agent-brief §7).
- **F10** is the one judgement call. The disclosure names "zones", not the heading. If the owner reads the disclosure as covering zone chrome, F10 becomes `DIVERGENCE (accepted)`.
- **The CTA subtitle** renders only for a round with more than 0 entries (`SinglePage.php:423–427`, disclosed). It is present at capture (1 entry), so it is not a finding. On a fresh round it would vanish.
- **Data dependence.** The live round has exactly **one** entry, so INK's entry grid holds a single card. The `auto-fit, minmax(260px,1fr)` track behaviour seen in the drift (`768px 0px` at 1440) depends on that count. Lovable always shows 3.

---

## 4. (none) — no EXTRA sections

---

## 5. Join-key coverage

`maps/uitdaging-enkel.csv`: 74 rows — **34 paired**, 36 `missing-ink`, 3 `missing-lovable`, 1 `scope`.

- All ids use the `uitdaging-` prefix. Hero elements are `uitdaging-<role>`, extending the pre-existing INK id `uitdaging-tagline` (kept, not renamed). Other sections are `uitdaging-<section>-<role>`.
- The row-00 `hero-*` / `challenge-card-*` ids in `CurrentChallenge.php` were not touched.
- Repeaters are tagged on the first instance only.
- **Paired in capture:** 33 page ids at every viewport (plus 31 header/footer ids from row 00 = the diff's 64). `uitdaging-tagline` is paired in the map but absent from the INK render (F5).
- **How the INK ids are placed:**
  - inline attributes in `reading-uitdaging.php` (static markup);
  - emitted from `SinglePage.php` (dynamic block; first entry card only);
  - `render_block_core/post-title` and `render_block_core/post-content` filters for `uitdaging-title` / `uitdaging-prompt-body` (`functions.php`, `ink_foundation_audit_id_uitdaging`, following the reading-page filter precedent).

## 6. Conclusion and limits

The page's sections are in the right order and all present except **Resources**. The divergence is concentrated in two places:

1. **The prompt section is a single authored text blob.** It replaces Lovable's structured prompt / devices / rules / prize layout. This is a disclosed content-model decision.
2. **The entries card is a thinner variant.** It has no meta row, no read time, no stats and a `<span>` title, and it sits inside two extra wrappers.

Smaller deltas are wrapper and tag shape (F2–F4, F8, F23).

Most of the remaining visible difference is not structural. It is in the generated drift reports
(`05-uitdaging-enkel-drift*.md`) and the primitive register (`05-uitdaging-enkel-primitives.md`).

**This document makes no claim about any computed value** (size, colour, spacing, weight) — those come
only from the Phase 4 captures (R4). It cannot speak to:

- header and footer (row 00);
- other `uitdaging` posts whose body, excerpt or entry count differ (data-dependent elements: F5, the CTA subtitle, the grid track count);
- the closed-round (`is-gesluit`) state, which was not rendered.

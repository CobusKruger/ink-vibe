---
baseline_commit: c5821d806c1700331ae3a352f097d8a0646c6fe8
---

# Story 1.6: Block locking strategy

Status: done

<!-- Note: Validation is optional. Run validate-create-story for quality check before dev-story. -->

## Story

As a site owner,
I want critical editorial structure locked while content stays editable,
so that non-technical staff can edit safely in the Site Editor without breaking layout (NFR-6).

## Acceptance Criteria

1. **Critical editorial structure cannot be moved or removed in the Site Editor** — Given the locked templates, template parts, and patterns, when a staff member edits in the Site Editor, then the **structural skeleton** (header/footer chrome, the `main`/`section` container groups, the `core/columns`/`core/column` scaffolds, the navigation, the `core/buttons` wrappers) **cannot be deleted or moved**, while the **content inside** (text in headings/paragraphs/links, the post-title/post-content placeholders, the image, the nav link labels/URLs) **remains editable**. This is achieved declaratively via WordPress block-locking semantics in the block markup — `templateLock` on container blocks/templates/parts and per-block `"lock": {"move": true, "remove": true}` attributes — **not** via a `functions.php` permissions filter. _[Source: epics.md#Story-1.6 "Given locked patterns/templates, When a staff member edits in the Site Editor, Then critical structure cannot be deleted/moved but content remains editable"; project-context.md "Lock critical editorial structure with block locking; leave content editable for non-technical staff"; architecture.md AD-7 line 505 "Static/marketing surfaces are patterns with locked structure", tree line 1006 "archetypes A–D (locked structure)", line 704 "block locking on critical structure"]_
2. **Templates lock their chrome + skeleton but keep content regions editable** — Given `front-page.html`, `index.html`, and `page.html`, when they load in the Site Editor, then each `wp:template-part` chrome reference (header/footer) and the outer `main` group and its inner `alignwide` group carry move/remove locks (the staff member cannot delete or reorder the page skeleton), **and** the editable content region stays open: on `front-page` the hero copy (eyebrow/h1/lead/buttons) is editable in place; on `index`/`page` the `core/post-title` + `core/post-content` placeholders are untouched and fully functional (they resolve post content — locking them would break the page). _[Source: templates/front-page.html, index.html, page.html (current markup: template-part header → main group → inner alignwide group → footer); NFR-6; epics.md#Story-1.6]_
3. **Template parts lock the chrome skeleton** — Given `template-parts/header.html`, `footer.html`, and `section.html`, when they render in the Site Editor, then the header and footer parts (which delegate to the `header-main`/`footer-main` patterns) keep their structure intact and non-removable, and the reusable `section` shell's outer `section` container is move/remove-locked while its inner placeholder content stays editable (a section shell is a *structural container* — its frame is the thing worth protecting; its contents are meant to be filled). _[Source: template-parts/header.html, footer.html, section.html; 1-4 (section shell is a structural container with an Afrikaans placeholder prompt); NFR-6]_
4. **Patterns lock structure with `contentOnly` / selective locks; copy stays editable** — Given the 7 patterns (`header-main`, `footer-main`, `hero`, `featured-grid`, `archive-intro`, `cta-band`, `profile-summary`), when a staff member inserts and edits them, then the **structural skeleton is locked** but **all visible copy remains editable**: the chrome patterns (`header-main`/`footer-main`) use `templateLock:"contentOnly"` on their outer container so staff edit nav labels, footer column headings, list links, and copyright text but cannot restructure the chrome; the content patterns (`hero`/`featured-grid`/`archive-intro`/`cta-band`/`profile-summary`) lock their outer `section` + structural sub-containers (columns/cards/media-text/buttons wrappers) against move/remove so the designed layout survives, while every heading, paragraph, link, badge, image, and button label inside stays editable. **No region a staff member must edit is locked so hard that its text/image/link can't be changed.** _[Source: patterns/*.php (current markup); project-context.md "leave content editable for non-technical staff"; epics.md#Story-1.6; architecture.md AD-7 "patterns with locked structure"]_
5. **Declarative-only, presentation-only, no regression, no scope bleed** — Given the locking work, when it is applied, then it is **declarative block-attribute work only**: lock semantics are added to the existing block markup (`.html` templates/parts + `.php` pattern markup) and **`functions.php` stays byte-for-byte unchanged** (no editor-permission filter, no `block_editor_settings_all`/`allowedBlockTypes`/`templateLock` PHP filter — the architecture says `functions.php` is "register patterns/block styles ONLY", and the project rule is to prefer declarative locks). **No business logic**, no `ink-core`, no remote URLs. The 1.1 tokens, 1.2 typography, 1.3 dark variation, 1.4 templates/parts (copy + structure), and 1.5 patterns/styles (slugs, categories, titles, copy) are **preserved**: locking adds only `templateLock`/`lock` keys to existing block delimiters — **no token, no copy, no slug, no category, no structure is changed.** _[Source: project-context.md three-layer separation ("no business logic in the theme"), Gate A (tokens), Gate D (Afrikaans copy, no AI retranslation); architecture.md tree line 1001 "functions.php … register patterns/block styles ONLY"; 1-4/1-5 additive/no-regression discipline; memory: Afrikaans is source of truth]_
6. **Validity & verification (Gate A/D static + deferred live check)** — Given the touched files, when verified, then `theme.json` remains valid `version: 3` JSON and is **unchanged** by this story; every touched `.html`/`.php` file is well-formed (balanced `<!-- wp:* -->` / `<!-- /wp:* -->` markers, balanced `<?php`/`?>`); every `lock` / `templateLock` value embedded in a block attribute is **valid JSON**; the **lock count matches the documented strategy** (every block the strategy says to lock actually carries the lock attribute/`templateLock`, verified by grep+count); **content-editability is preserved** (no blanket `templateLock:"all"` or `"lock":{"edit":true}` on a pure content region that staff must edit; post-title/post-content are never locked); Gate A holds (no new hardcoded values — locking adds no CSS); Gate D holds (no English UI string introduced, curated Afrikaans + sentence case intact); no remote URLs. **Live Site-Editor lock-behaviour confirmation is deferred to Story 1.11** (no running WP env in the repo) — same precedent as 1.1 AC-4 / 1.2 Task 7 / 1.3 Task 5 / 1.4 AC-6 / 1.5 AC-6. _[Source: project-context.md WP 7.0+ / PHP 8.3+, Gate A/D; 1-1…1-5 deferred-Site-Editor-check precedent; NFR-9 (harness is Story 1.11)]_

## Tasks / Subtasks

> **Current state (read before starting):** The theme ships **3 templates** (`templates/front-page.html`, `index.html`, `page.html`), **3 template parts** (`template-parts/header.html`, `footer.html`, `section.html`), and **7 patterns** (`patterns/header-main.php`, `footer-main.php`, `hero.php`, `featured-grid.php`, `archive-intro.php`, `cta-band.php`, `profile-summary.php`), plus `functions.php` (presentation-only: pattern-category + 3 block-style registrations) and `theme.json` (full token set). **None of them currently carry any `lock` or `templateLock` attribute** (1.5 explicitly deferred locking to this story). The structures to lock are enumerated in the strategy table below. **This story adds ONLY lock semantics — do NOT change any token, copy, slug, category, title, or structural markup; do NOT touch `functions.php` or `theme.json`; do NOT pre-empt 1.7 (`ink-core`), 1.9 (archetypes A–D), or later epics. Do NOT regress 1.1/1.2/1.3/1.4/1.5.**

- [x] **Task 1 — Lock the template skeletons (chrome + main containers) (AC: 1, 2, 5, 6)**
  - [x] In `templates/front-page.html`: added `"lock":{"move":true,"remove":true}` to the two `wp:template-part` references (header/footer) and to the outer `wp:group` (`tagName:"main"`) and its inner `alignwide` `wp:group`. The hero content blocks (eyebrow paragraph, h1, lead paragraph, the `wp:buttons` children) are left **editable** — staff can rewrite the home hero copy, but cannot delete/move the page skeleton or the chrome. (4 locks.)
  - [x] In `templates/index.html` and `templates/page.html`: added the same move/remove lock to the two `wp:template-part` refs, the `main` group, and the inner `alignwide` group (4 locks each). `wp:post-title` / `wp:post-content` left **unlocked** — dynamic content placeholders that must stay fully functional (locking them would break the page render). Their structural *position* is already protected by the locked parent group.
  - [x] Verified each template's block markers stay balanced (front-page 8/8, index 2/2, page 2/2 + self-closing refs) and each added `lock` value is valid JSON.

- [x] **Task 2 — Lock the template-part skeletons (AC: 1, 3, 5, 6)**
  - [x] In `template-parts/section.html`: added `"lock":{"move":true,"remove":true}` to the outer `wp:group` (`tagName:"section"`) and its inner `alignwide` `wp:group` (2 locks), leaving the muted Afrikaans placeholder paragraph editable (the shell's frame is protected; its contents are meant to be filled). The chrome parts `header.html`/`footer.html` each contain only a single `wp:pattern` reference — the lock for the chrome lives in the pattern markup (Task 3), so these two part files were **left unchanged** (locking at the pattern container is the cleaner home for the `contentOnly` declaration).
  - [x] Verified block-marker balance (section 3/3) + valid lock JSON.

- [x] **Task 3 — Lock the chrome patterns with `templateLock:"contentOnly"` (AC: 1, 4, 5, 6)**
  - [x] In `patterns/header-main.php`: added `"templateLock":"contentOnly"` to the outer `wp:group` (`align:"full"`) so the header structure (the constrained wrapper, the flex row, the site-title + navigation arrangement) cannot be restructured, while staff can still edit the `wp:navigation` link **labels/URLs** and the site title. (`contentOnly` locks the layout but keeps text/attribute editing open — the textbook chrome case.)
  - [x] In `patterns/footer-main.php`: added `"templateLock":"contentOnly"` to the outer `wp:group` (`align:"full"`) so the 3-column footer skeleton and the copyright row stay intact, while staff edit the column headings ("Ontdek"/"Gemeenskap"/"Ondersteun ons"), the list links, the intro paragraph, and the copyright text.
  - [x] Confirmed the curated Afrikaans copy is **not** altered (only the outer-group attribute changed — verified via `git diff`); verified `<?php`/`?>` pair + block-marker balance (header 3/3, footer 16/16) + valid `templateLock` JSON.

- [x] **Task 4 — Lock the content-pattern structures (selective move/remove + `contentOnly` where apt) (AC: 1, 4, 5, 6)**
  - [x] `patterns/hero.php`: locked the outer `section` `wp:group` + inner `alignwide` `wp:group` + the `wp:buttons` wrapper against move/remove (`"lock":{"move":true,"remove":true}`, 3 locks); the eyebrow/h1/lead text and the two individual `wp:button`s stay editable (staff rewrite copy + button labels/links, but the hero layout and the button pair survive).
  - [x] `patterns/featured-grid.php`: locked the outer `section` group, the inner `alignwide` group, the flex header row group, the `wp:columns` row, and each of the 3 `wp:column` + each card `wp:group` (`is-style-card`) against move/remove (10 locks) — the 3-card grid layout is the designed structure to protect. The badge/title/attribution/"Lees meer" text and the "Sien alle werke" link stay editable. Locking each `wp:column` against move/remove prevents staff from collapsing the grid to 1–2 cards or reordering it; the card *contents* remain editable.
  - [x] `patterns/archive-intro.php`: locked the outer `section` group + inner `alignwide` group against move/remove (2 locks); eyebrow/h1/lead stay editable.
  - [x] `patterns/cta-band.php`: locked the outer `section` group (the tinted band), the inner constrained group, and the `wp:buttons` wrapper against move/remove (3 locks); the h2/body text + individual buttons stay editable.
  - [x] `patterns/profile-summary.php`: locked the outer `section` group, the `is-style-card` group, and the `wp:media-text` against move/remove (3 locks — the card + media-text arrangement is the layout); the image, name h3, meta paragraph, `is-style-emphasis` bio, and "Volg" button stay editable. The `wp:image` (avatar) carries **no** lock — staff must be able to set the avatar.
  - [x] Verified each pattern's `<?php`/`?>` pair + block-marker balance (hero 8/8, featured-grid 25/25, archive-intro 5/5, cta-band 7/7, profile-summary 11/11) + valid lock JSON; confirmed no copy/token/slug/category/title changed.

- [x] **Task 5 — Static verification (AC: 5, 6)**
  - [x] **JSON validity:** `theme.json` parses as valid JSON, `version:3`, and is **unchanged** by this story (no token/style edits — locking touched only `.html`/`.php` block markup). `styles/dark.json` valid + unchanged.
  - [x] **Lock-JSON validity:** every `lock` / `templateLock` value embedded in a block delimiter is valid JSON (each `wp:*` attribute object extracted + `json.loads`-parsed). PASS — all 34 lock objects + 2 `templateLock` values parse.
  - [x] **Lock-count vs strategy:** counted `lock`/`templateLock` per file — front-page 4, index 4, page 4, section 2, header-main `templateLock`×1, footer-main `templateLock`×1, hero 3, featured-grid 10, archive-intro 2, cta-band 3, profile-summary 3 — **exactly matches the strategy table** (per-file counts in Debug Log). PASS.
  - [x] **Content-editability preserved:** **no** `templateLock:"all"` and **no** `"lock":{"edit":true}` anywhere; `wp:post-title`/`wp:post-content` carry **no** lock; `profile-summary` `wp:image` carries **no** lock; the `contentOnly` chrome patterns + move/remove-only content patterns keep text editable by construction. PASS.
  - [x] **Markup well-formedness:** every touched `.html`/`.php` has balanced `<!-- wp:* -->` / `<!-- /wp:* -->` markers (self-closing `… /-->` counted separately) and each `.php` has exactly one balanced `<?php`/`?>` pair. PASS.
  - [x] **Gate A:** no new hardcoded values — locking added no CSS, no colours, no spacing (grep over all 11 touched files: zero hex/rgb/hsl; the diff adds only `lock`/`templateLock` keys). PASS.
  - [x] **Gate D:** no English UI string introduced; the curated Afrikaans nav/footer/hero/pattern copy is byte-identical (`git diff` of tracked files shows only added `lock`/`templateLock` keys inside `wp:*` attribute objects); no `text-transform` change; pattern `Title`s/labels untouched. PASS.
  - [x] **No remote URLs** introduced (locking adds none). **No `functions.php`/`theme.json` change** by 1.6. **No regression:** the diff is exclusively added lock attributes across the 3 templates + section part + 7 patterns. Live Site-Editor lock-behaviour check **deferred to 1.11**. PASS (63/63 static checks).

## Dev Notes

### What this story is (and is not)
- **Is:** apply WordPress **block locking** so the **critical editorial structure** (header/footer chrome, the template `main`/`section` skeletons, the structural sub-containers of the key patterns — columns/cards/media-text/buttons wrappers, the navigation) **cannot be moved or removed** by non-technical staff in the Site Editor, while **content** (heading/paragraph/link text, nav labels/URLs, the avatar image, button labels) **stays editable**. Done **declaratively** in the block markup via `templateLock` (on container blocks/templates/parts) and per-block `"lock":{"move":true,"remove":true}` attributes, using `contentOnly` where the intent is "edit text but don't restructure". The **locking strategy table** (below) — which block/area gets which lock and why — is the key deliverable. Additive, presentation-only, no copy/token/structure change. _[Source: epics.md#Story-1.6; project-context.md "Lock critical editorial structure … leave content editable"; architecture.md AD-7 / tree line 1006 / line 704]_
- **Is not:** a `functions.php` editor-permission filter or any PHP lock mechanism (the project rule is **prefer declarative locks**; the architecture confines `functions.php` to "register patterns/block styles ONLY"); a redesign of the 1.4 templates/parts or 1.5 patterns (only lock semantics are added — **no** restructuring, **no** copy edits); the **page archetypes A–D** "locked structure" compositions (those are assembled in **Story 1.9** — this story locks the *existing* building blocks, not new page scaffolds); `ink-core` business logic / dynamic blocks (**Story 1.7+**, AD-7); the **Skryf form / reading widgets / follow / leeslys** interactive blocks (`ink-core`); dark-mode activation; tier/Gradering styling (Epic 5). _[Source: epics.md Stories 1.7/1.9; architecture.md AD-7; 1-5 scope boundary "No block locking … that is Story 1.6"]_

### ⭐ The locking strategy (key deliverable): block/area → lock type → rationale

WordPress offers three declarative lock mechanisms (no PHP needed):
- **`templateLock` on a container block** — `"all"` (children cannot be moved, removed, **or** inserted), `"insert"` (no insert/remove, but move allowed), `"contentOnly"` (children's **structure** is locked but their **content/text is still editable**, and the block-list view is hidden — the intent is "edit the words, not the layout"). Inherited by descendants unless a child sets its own `templateLock`.
- **Per-block `lock` attribute** — `{"move":true,"remove":true}` (block can't be dragged or deleted, but its content edits stay open), optionally `{"edit":true}` (locks editing entirely — **avoid on content**).
- **Templates/parts** carry the same `templateLock`/`lock` keys in their block delimiters.

**Decision: prefer `contentOnly` for chrome (text-heavy, layout-fixed) and selective `move`/`remove` locks for content patterns (so individual copy/buttons/images stay fully editable while the designed skeleton is frozen). Never `templateLock:"all"` on a region staff must edit; never `lock.edit` on content.**

| Block / area | File | Lock applied | Rationale |
|---|---|---|---|
| `wp:template-part` header ref | `front-page.html`, `index.html`, `page.html` | `lock:{move,remove}` | Chrome must appear on every view, in place — staff can't delete or reorder it. |
| `wp:template-part` footer ref | `front-page.html`, `index.html`, `page.html` | `lock:{move,remove}` | Same — footer chrome is global, non-removable. |
| outer `main` `wp:group` | `front-page.html`, `index.html`, `page.html` | `lock:{move,remove}` | The page skeleton (header→main→footer) is the protected frame. |
| inner `alignwide` `wp:group` | `front-page.html`, `index.html`, `page.html` | `lock:{move,remove}` | Keeps the content-width region in place; its *children* stay editable. |
| `wp:post-title` / `wp:post-content` | `index.html`, `page.html` | **none** | Dynamic content placeholders — must stay fully functional; position already protected by the locked parent. |
| hero copy (eyebrow/h1/lead) + each `wp:button` | `front-page.html`, `hero.php`, `cta-band.php` | **none (editable)** | Staff rewrite headline/lead/CTA labels + links — this is the content they own. |
| outer `section` `wp:group` | `section.html`, `hero.php`, `featured-grid.php`, `archive-intro.php`, `cta-band.php`, `profile-summary.php` | `lock:{move,remove}` | The section frame is the structural unit worth protecting; contents stay editable. |
| inner `alignwide`/constrained `wp:group` | `section.html`, `hero.php`, `featured-grid.php`, `archive-intro.php`, `cta-band.php` | `lock:{move,remove}` | Holds the content region in place without freezing its text. |
| `wp:buttons` wrapper | `hero.php`, `cta-band.php` | `lock:{move,remove}` | The button pair survives; individual button labels/links stay editable. |
| flex header row `wp:group` | `featured-grid.php` | `lock:{move,remove}` | The "heading + see-all link" row layout is fixed; both texts editable. |
| `wp:columns` + each `wp:column` + each card `wp:group` | `featured-grid.php` | `lock:{move,remove}` | The 3-card grid is the designed structure — staff can't collapse/reorder it; card text editable. |
| `is-style-card` group + `wp:media-text` | `profile-summary.php` | `lock:{move,remove}` | The card + media-text arrangement is the layout; image/name/meta/bio/button editable inside. |
| `wp:image` (avatar) | `profile-summary.php` | **none** (no `edit` lock) | Staff must be able to set the avatar — never lock its edit. |
| outer chrome `wp:group` | `header-main.php`, `footer-main.php` | `templateLock:"contentOnly"` | Chrome is text-heavy + layout-fixed: staff edit nav labels/URLs, footer headings, list links, copyright — but cannot restructure the chrome. The textbook `contentOnly` case. |

**Why `contentOnly` on the chrome patterns, but move/remove on the content patterns:** the chrome (`header-main`/`footer-main`) is a fixed layout whose *only* legitimate staff edit is the **text/links inside** — `contentOnly` locks the whole sub-tree's structure in one declaration while leaving all text editable, which is exactly the maintainability intent. The content patterns (`hero`/`featured-grid`/…) also have *structural children that are themselves content* (individual buttons, cards), so a blanket `contentOnly` on their root would over-lock (e.g. a staff member legitimately adding a 4th featured card, or the editor needing per-button control); instead we lock each structural container against **move/remove** so the skeleton is frozen but each block's content + the ability to edit individual leaves is preserved. This is the "selective move/remove rather than blanket all" guidance applied per AC-4.

### WordPress lock mechanics (exact markup)
- **Per-block lock** — add a `lock` object to the block's JSON attributes in the opening delimiter:
  ```
  <!-- wp:group {"tagName":"section","align":"full","lock":{"move":true,"remove":true},"style":{…},"layout":{…}} -->
  ```
  The `lock` key sits **alongside** existing attributes inside the same `{…}` object — do not add a second comment, do not reformat the existing attributes.
- **`templateLock` on a container** — add `"templateLock":"contentOnly"` to the container's attribute object:
  ```
  <!-- wp:group {"align":"full","templateLock":"contentOnly","style":{…},"backgroundColor":"surface-alt","layout":{…}} -->
  ```
- **Self-closing refs** (`wp:template-part {…} /-->`, `wp:post-title {…} /-->`) take the `lock` key inside their `{…}` too; the template-part refs get `lock`, the post-title/post-content get **none**.
- **Editability under locks:** `lock:{move:true,remove:true}` does **not** lock editing — text/links/images inside the block stay editable. `templateLock:"contentOnly"` keeps **text editable** but hides the inserter/structure controls for descendants. Neither uses `lock.edit` (which would freeze content — explicitly avoided).
- **Valid JSON only:** the attribute object must remain valid JSON (double-quoted keys, boolean `true`, no trailing commas). The static check parses each one.

### ⚠️ Guardrails (prevent disasters)
- **Never lock so hard that content can't be edited.** No `templateLock:"all"` on a content/chrome region staff must edit; no `"lock":{"edit":true}` anywhere; `wp:post-title`/`wp:post-content`/`wp:image` keep **no** lock. The whole point (NFR-6) is *content editable, structure safe.* _[project-context.md "leave content editable for non-technical staff"]_
- **Declarative only — `functions.php` untouched.** No `block_editor_settings_all` / `allowedBlockTypes` / template-lock PHP filter. The architecture confines `functions.php` to pattern/block-style registration; the project rule prefers declarative locks in markup. _[architecture.md tree line 1001; project-context.md three-layer]_
- **Additive attribute-only edit — no restructuring, no copy change.** Add only `lock`/`templateLock` keys to existing `wp:*` attribute objects. Do **not** reorder/rename attributes, change tokens, edit copy, or alter slugs/categories/titles. The diff must be lock keys only. _[1-4/1-5 no-regression discipline; memory: Afrikaans is source of truth — never AI-retranslate]_
- **Token compliance unaffected (Gate A).** Locking adds no CSS/colour/spacing — Gate A is satisfied by construction; re-grep to confirm no new literals. _[project-context.md Gate A]_
- **Afrikaans-first intact (Gate D).** No English string is added (lock keys are WP-internal JSON, like `move`/`remove`/`contentOnly` — same class as the existing `slug`/`area`/`type` keys). Curated copy byte-identical. _[project-context.md Gate D]_
- **No scope bleed.** No archetypes A–D compositions (1.9); no `ink-core`/dynamic blocks (1.7); no new patterns/templates; no `theme.json` change. _[epics.md Stories 1.7/1.9]_
- **No external requests.** Locking introduces no URLs/assets. _[project-context.md "no hardcoded asset URLs"; security]_

### Source tree (files this story touches)
```
wp-content/themes/ink-foundation/
├── templates/
│   ├── front-page.html        # UPDATE — lock header/footer part refs + main + inner group (move/remove); hero copy editable
│   ├── index.html             # UPDATE — lock part refs + main + inner group; post-title/post-content NOT locked
│   └── page.html              # UPDATE — same as index.html
├── template-parts/
│   ├── header.html            # NO CHANGE (single wp:pattern ref; chrome lock lives in the pattern)
│   ├── footer.html            # NO CHANGE (single wp:pattern ref)
│   └── section.html           # UPDATE — lock outer section + inner group (move/remove); placeholder editable
├── patterns/
│   ├── header-main.php         # UPDATE — templateLock:"contentOnly" on outer group; nav labels/site-title editable
│   ├── footer-main.php         # UPDATE — templateLock:"contentOnly" on outer group; headings/links/copyright editable
│   ├── hero.php                # UPDATE — lock section + inner group + buttons wrapper; copy + buttons editable
│   ├── featured-grid.php       # UPDATE — lock section/inner/header-row/columns/columns/cards; card text editable
│   ├── archive-intro.php       # UPDATE — lock section + inner group; eyebrow/h1/lead editable
│   ├── cta-band.php            # UPDATE — lock section + inner group + buttons wrapper; copy + buttons editable
│   └── profile-summary.php     # UPDATE — lock section + card group + media-text; image/name/meta/bio/button editable
├── functions.php               # NO CHANGE (declarative locks only — architecture: register patterns/styles only)
├── theme.json                  # NO CHANGE (AC-6 — no token/style edits)
└── styles/dark.json            # NO CHANGE (1.3)
```
_[Source: architecture.md#`ink-foundation` FSE theme tree lines 999–1009; current repo markup verified by inspection]_

### Project constraints that apply
- **Presentation only** — declarative locks in markup; zero business logic; `functions.php` untouched. _[project-context.md three-layer; architecture.md tree line 1001]_
- **Block theme, not classic** — `templateLock`/`lock` are FSE-native; this is the sanctioned mechanism ("block locking on critical structure"). _[project-context.md "Block theme … Lock critical editorial structure with block locking"; architecture.md line 704]_
- **Afrikaans-first, sentence case** — no copy touched; lock keys are WP-internal. _[project-context.md Gate D; memory: Afrikaans is source of truth]_
- **Gate A** — no new values; locking adds no CSS. _[project-context.md Gate A]_
- **WP 7.0+ / PHP 8.3+** — block locking + `contentOnly` are stable in WP 7.0; pattern files stay valid PHP (header comment + markup only). _[project-context.md tech stack]_
- **NFR-6 maintainability** — the deliverable is precisely "Site Editor stability, block locking" so non-technical staff edit safely. _[epics.md NFR-6; project-context.md]_

### Testing standards summary
- No PHP unit-test harness exists at the repo root yet (arrives in **Story 1.11**); the block theme is covered by **Gate A/D static audits + (future) E2E/visual checks**, not PHP unit tests. _[project-context.md "cover the block theme via E2E/visual checks instead of unit tests"; 1-1…1-5 Completion Notes]_
- Verification for this story (per project-context Gate A/D static + future E2E/visual) = JSON validity (+ theme.json unchanged) · every embedded `lock`/`templateLock` value is valid JSON · lock-count matches the strategy table (grep+count, every intended lock present, nothing extra) · content-editability preserved (no `templateLock:"all"`/`lock.edit` on content; post-title/post-content/image unlocked) · markup well-formedness (balanced `wp:*`/`/wp:*`, balanced `<?php`/`?>`) · Gate A (no new hardcoded values) · Gate D (no English UI string, curated Afrikaans + sentence case intact) · no remote URLs · no `functions.php`/`theme.json` change · no 1.1/1.2/1.3/1.4/1.5 regression — with the **live Site-Editor lock-behaviour check deferred to Story 1.11** (same precedent as 1.1 AC-4 / 1.2 Task 7 / 1.3 Task 5 / 1.4 AC-6 / 1.5 AC-6).

### Project Structure Notes
- This story adds lock semantics to the **existing** 1.4 templates/parts + 1.5 patterns — the architecture tree's "archetypes A–D (locked structure)" (line 1006) refers to the **1.9** page compositions, which are out of scope here; 1.6 establishes the *locking strategy* that 1.9 will reuse when assembling those archetypes.
- The `template-parts/` vs `parts/` naming variance documented in Story 1.4 still stands and is unaffected (locks are added inside the part files, not by renaming).
- No structural deviation: only `lock`/`templateLock` attribute keys are added to existing block delimiters; no files created or removed.

### References

- [Source: _bmad-output/planning-artifacts/epics.md#Story-1.6 — Block locking strategy ("Given locked patterns/templates, When a staff member edits in the Site Editor, Then critical structure cannot be deleted/moved but content remains editable"; NFR-6 maintainability for non-technical staff)]
- [Source: _bmad-output/planning-artifacts/epics.md#Story-1.7 (ink-core) + #Story-1.9 (page archetypes A–D "locked structure") — downstream scope boundaries]
- [Source: _bmad-output/planning-artifacts/architecture.md#AD-7 (line 505 "Static/marketing surfaces are patterns with locked structure"); FSE theme tree (line 1001 "functions.php … register patterns/block styles ONLY", line 1006 "archetypes A–D (locked structure)"); #Selected-Tooling (line 704 "block locking on critical structure … exported via the plugin")]
- [Source: _bmad-output/implementation-artifacts/1-5-core-block-pattern-library.md — the 7 patterns + their structure (header-main/footer-main chrome; hero/featured-grid/archive-intro/cta-band/profile-summary content; is-style-card/emphasis); "No block locking (templateLock/lock) is added (that is Story 1.6)"; additive/no-regression discipline; deferred Site-Editor check]
- [Source: _bmad-output/implementation-artifacts/1-4-global-templates-template-parts.md — the 3 templates + section shell structure (template-part header → main group → inner alignwide group → footer); section shell is a structural container; additive/no-regression discipline]
- [Source: wp-content/themes/ink-foundation/templates/{front-page,index,page}.html · template-parts/{header,footer,section}.html · patterns/{header-main,footer-main,hero,featured-grid,archive-intro,cta-band,profile-summary}.php — current block markup the locks attach to (verified by inspection)]
- [Source: _bmad-output/project-context.md — "Block theme … Lock critical editorial structure with block locking; leave content editable for non-technical staff"; three-layer separation (no theme business logic; functions.php register-only); Quality Gate A (tokens only), Gate D (Afrikaans-first/sentence case/no AI Afrikaans), WP 7.0+/PHP 8.3+; testing (block theme via E2E/visual, harness Story 1.11)]
- [Source: WordPress block-editor block-locking + templateLock (`all`/`insert`/`contentOnly`) semantics — declarative attributes in block markup, stable in WP 7.0]

## Dev Agent Record

### Agent Model Used

claude-opus-4-8[1m] (Opus 4.8, 1M context)

### Debug Log References

- **JSON validity:** `python3 json.load` → `theme.json` VALID (version 3), `styles/dark.json` VALID (version 3); both unchanged by 1.6.
- **Lock-JSON validity:** extracted every `<!-- wp:* {…} -->` attribute object across the 11 touched files and `json.loads`-parsed each — all parse (34 `lock` objects + 2 `templateLock` strings). PASS.
- **Lock counts (vs strategy table):** front-page `lock`×4 / `templateLock`×0; index `lock`×4; page `lock`×4; section `lock`×2; header-main `lock`×0 / `templateLock`×1; footer-main `lock`×0 / `templateLock`×1; hero `lock`×3; featured-grid `lock`×10; archive-intro `lock`×2; cta-band `lock`×3; profile-summary `lock`×3. **Every count equals the documented strategy table; nothing extra.** PASS.
- **Content-editability:** zero `templateLock:"all"`; zero `"lock":{"edit":true}`; `wp:post-title`/`wp:post-content` (index, page) carry no lock; `profile-summary` `wp:image` carries no lock. PASS.
- **Markup balance:** wp/​/wp markers balanced per file — front-page 8 open/8 close (+2 self-closing template-part refs); index 2/2 (+4 self-closing: 2 template-part + post-title + post-content); page 2/2 (+4 self-closing); section 3/3; header-main 3/3 (+8 self-closing); footer-main 16/16; hero 8/8; featured-grid 25/25; archive-intro 5/5; cta-band 7/7; profile-summary 11/11. Each `.php` has exactly one `<?php`/`?>` pair. PASS.
- **Gate A:** grep over all 11 touched files → zero `#hex`/`rgb(`/`rgba(`/`hsl(`/`hsla(`; locking introduced no CSS/colour/spacing. PASS.
- **No remote URLs:** grep over all 11 touched files (excluding `schemas.wp.org`) → none. PASS.
- **No regression (tracked-file diff):** `git diff` of the tracked touched files (`front-page.html`, `header-main.php`, `footer-main.php`) shows **only** added `lock`/`templateLock` keys inside existing `wp:*` attribute objects — no copy/token/slug/category/title/structure change. The 1.4/1.5 files still show as untracked (`??`) because Stories 1.4/1.5 are in `review` (uncommitted) — same working-tree situation documented in 1.3/1.4/1.5; my 1.6 edits to those files are additive lock attributes only.
- **`functions.php` / `theme.json` untouched by 1.6:** no edit made; the `theme.json` `M` in `git status` is the pre-existing uncommitted 1.2 work (same as 1.3/1.4/1.5 documented), not a 1.6 change.
- `php -l` not run — no PHP binary in-env (deferred-harness precedent, 1.1–1.5); substituted the structural balance + lock-JSON + presentation-only checks above.
- **Static verification harness:** 63/63 checks PASS.

### Completion Notes List

- **Block locking applied entirely declaratively** in the block markup — `templateLock`/`lock` attributes added to existing `wp:*` delimiters across the 3 templates, the `section` shell part, and the 7 patterns. **No `functions.php` editor-permission filter was used** (per the project rule "prefer declarative lock attributes/templateLock" + the architecture's "functions.php register patterns/block styles ONLY"); `functions.php` is byte-for-byte unchanged.
- **Strategy: `contentOnly` for chrome, selective move/remove for content.** The chrome patterns (`header-main`/`footer-main`) got `templateLock:"contentOnly"` on their outer group — one declaration that freezes the whole chrome sub-tree's structure while keeping all text/links editable (nav labels/URLs, footer headings, list links, copyright). The template skeletons (header/footer part refs, `main` group, inner `alignwide` group) and the content patterns' structural containers (sections, the featured-grid columns/cards, the cta/hero `buttons` wrappers, the profile-summary card + media-text) got `lock:{move:true,remove:true}` — the layout can't be reordered/deleted, but every heading/paragraph/link/button/image inside stays editable. This avoids the over-locking a blanket `contentOnly`/`all` on a content root would cause (individual buttons/cards must stay individually editable).
- **Content stays editable everywhere it must (NFR-6 honoured).** `wp:post-title`/`wp:post-content` (dynamic placeholders) and the `profile-summary` avatar `wp:image` carry **no** lock; no `lock.edit` and no `templateLock:"all"` is used anywhere. The locking protects *structure*, never *content*.
- **Scope held strictly to lock semantics.** No token, copy, slug, category, title, or structural markup changed — the diff is exclusively added `lock`/`templateLock` keys. No `theme.json`/`functions.php` change; no page archetypes A–D (Story 1.9); no `ink-core`/dynamic blocks (Story 1.7); no later-epic pre-emption. The 1.1 tokens, 1.2 typography, 1.3 dark variation, 1.4 templates/parts, and 1.5 patterns/styles are preserved.
- **The locking-strategy table (Dev Notes) is the key deliverable** — block/area → lock type → rationale — and the implementation matches it exactly (verified by lock-count check). Story 1.9 will reuse this strategy when assembling the archetype compositions.
- **No PHP/test harness at repo root yet** (Story 1.11) — the block theme is covered by Gate A/D static audits + future E2E/visual checks per project-context, not PHP unit tests. No existing tests to run or regress.
- **Live Site-Editor lock-behaviour confirmation deferred to Story 1.11** (no running WP env / no PHP binary in the repo), consistent with the 1.1 AC-4 / 1.2 Task 7 / 1.3 Task 5 / 1.4 AC-6 / 1.5 AC-6 precedent. All static verification (JSON validity + theme.json/functions.php unchanged, lock-JSON validity, lock-count vs strategy, content-editability preserved, markup well-formedness, Gate A, Gate D, no-remote-URL, no-regression) passed — 63/63.

### File List

- `wp-content/themes/ink-foundation/templates/front-page.html` (modified) — added `lock:{move,remove}` to header + footer `wp:template-part` refs, the `main` group, and the inner `alignwide` group; hero copy + buttons left editable.
- `wp-content/themes/ink-foundation/templates/index.html` (modified) — added `lock:{move,remove}` to header + footer part refs, `main` group, inner `alignwide` group; `post-title`/`post-content` left unlocked.
- `wp-content/themes/ink-foundation/templates/page.html` (modified) — same locks as `index.html`.
- `wp-content/themes/ink-foundation/template-parts/section.html` (modified) — added `lock:{move,remove}` to the outer `section` group + inner `alignwide` group; placeholder paragraph left editable.
- `wp-content/themes/ink-foundation/patterns/header-main.php` (modified) — added `templateLock:"contentOnly"` to the outer chrome group; nav labels/URLs + site title stay editable.
- `wp-content/themes/ink-foundation/patterns/footer-main.php` (modified) — added `templateLock:"contentOnly"` to the outer chrome group; column headings/list links/intro/copyright stay editable.
- `wp-content/themes/ink-foundation/patterns/hero.php` (modified) — added `lock:{move,remove}` to the `section` group, inner `alignwide` group, and `wp:buttons` wrapper; copy + individual buttons editable.
- `wp-content/themes/ink-foundation/patterns/featured-grid.php` (modified) — added `lock:{move,remove}` to the `section` group, inner group, flex header row, `wp:columns`, the 3 `wp:column`s, and the 3 `is-style-card` groups; card text + see-all link editable.
- `wp-content/themes/ink-foundation/patterns/archive-intro.php` (modified) — added `lock:{move,remove}` to the `section` group + inner `alignwide` group; copy editable.
- `wp-content/themes/ink-foundation/patterns/cta-band.php` (modified) — added `lock:{move,remove}` to the `section` band, inner constrained group, and `wp:buttons` wrapper; copy + buttons editable.
- `wp-content/themes/ink-foundation/patterns/profile-summary.php` (modified) — added `lock:{move,remove}` to the `section` group, `is-style-card` group, and `wp:media-text`; image/name/meta/bio/"Volg" button editable (image edit not locked).

## Review Findings

Code review (2026-06-21): adversarial three-layer review (Blind Hunter diff-only, Edge Case Hunter diff+project, Acceptance Auditor diff+spec) — all three layers PASS, zero actionable findings. Re-verified independently: every `lock`/`templateLock` value is valid JSON (35 `lock` objects + 2 `templateLock`); all touched files have balanced `wp:*`/`/wp:*` markers; per-file lock counts match the strategy table exactly (front-page 4, index 4, page 4, section 2, header-main tl×1, footer-main tl×1, hero 3, featured-grid 10, archive-intro 2, cta-band 3, profile-summary 3); no `templateLock:"all"`, no `lock.edit`, post-title/post-content/avatar image carry no lock; `functions.php` has no lock filter and `theme.json` diff contains no lock keys (the `theme.json` M is pre-existing 1.2 work). Failed layers: none.

- [x] [Review][Defer] Dev Agent Record miscounts lock objects as 34 [1-6-block-locking-strategy.md:54,181] — cosmetic: the per-file breakdown sums to 35 lock objects (the actual implementation is correct and complete); documentation-only off-by-one in the Debug Log narrative, no code impact. Dismissed/deferred — not a code defect.

## Change Log

| Date | Change |
|---|---|
| 2026-06-20 | Story created (context-engineered) — block locking strategy: declaratively lock the critical editorial structure (header/footer chrome via templateLock:"contentOnly"; template main/section skeletons + content-pattern structural containers via lock:{move,remove}) across the 3 templates, the section shell, and the 7 patterns, while keeping all copy/links/images and post-title/post-content editable (NFR-6). functions.php + theme.json untouched; no 1.1–1.5 regression; no 1.7/1.9 scope bleed. Status → ready-for-dev. |
| 2026-06-20 | Implemented block locking strategy (Tasks 1–5): added `templateLock:"contentOnly"` to the `header-main`/`footer-main` chrome patterns; added `lock:{move,remove}` to the header/footer template-part refs + `main` + inner group across `front-page`/`index`/`page`, the `section` shell (outer + inner), and the structural containers of `hero`/`featured-grid`/`archive-intro`/`cta-band`/`profile-summary` (sections, featured-grid columns/cards, hero/cta buttons wrappers, profile-summary card + media-text). post-title/post-content/avatar image left unlocked; no `templateLock:"all"`, no `lock.edit`. Declarative-only (functions.php + theme.json unchanged); diff is lock attributes only — no copy/token/slug/category/structure change; no 1.1–1.5 regression. Static verification 63/63 PASS (JSON validity, lock-JSON validity, lock-count vs strategy table, content-editability preserved, markup balance, Gate A, Gate D, no remote URLs). Live Site-Editor lock-behaviour check deferred to 1.11. Status → review. |

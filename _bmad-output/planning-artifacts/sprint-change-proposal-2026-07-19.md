# Sprint Change Proposal — Theme Visual-Fidelity Rework (Epic 19)

- **Date:** 2026-07-19
- **Author:** Cobus (with Dev agent, Correct-Course workflow)
- **Trigger discovered:** Staging visual review, 2026-07-19 — the live `ink-foundation` theme does not resemble the Lovable design.
- **Status:** **APPROVED 2026-07-19** — **five-story slice** (owner decision: keep the spec's 19.1–19.5 slicing; fold the `ink-core` data-wiring back into 19.3 cards + 19.4 featured). Applied to `epics.md` + `sprint-status.yaml`. Handoff: implement each story in its own subagent.
- **Mode:** Batch
- **Scope classification:** **Moderate** (new epic + backlog reorganisation; PO/DEV handoff). Not Major — no PRD goal, architecture decision, or MVP objective changes; the design intent was always the target.
- **Related:**
  - `docs/theme-fidelity-rework-plan.md` (companion diagnosis — this proposal is its Phase 2 output)
  - `_bmad-output/planning-artifacts/ux-designs/ux-ink-vibe-2026-06-15/theme-fidelity-spec.md` (**`status: final`, owner sign-off 2026-07-19** — the build contract)
  - `.../ux-designs/ux-ink-vibe-2026-06-15/validation-report.md` + `review-{rubric,fidelity,buildability,accessibility}.md` (four-lens reviewer gate)
  - `docs/theme-redevelopment/design-lovable.png` (target) · `design-staging.png` (current)
  - `_bmad-output/planning-artifacts/epics.md`, `.../implementation-artifacts/sprint-status.yaml`, `_bmad-output/project-context.md`

---

## Section 1 — Issue Summary

**Problem.** The `ink-foundation` FSE theme was built to the design **tokens** but never to a concrete **composition contract**. The rendered Tuisblad (and the shared primitives it depends on) is a plain single-column stack instead of the rich editorial layout the INK Lovable design has specified since February. The visual layer is, in effect, barely implemented.

**This is explicitly NOT:**
- **A "theme failed to load" problem** — the CSS loads and applies.
- **A token-drift problem** — `wp-content/themes/ink-foundation/theme.json` is an *exact match* to the normalised handoff (`docs/design-handoff/tokens/theme-tokens.json`): same palette (`primary #EA4015`, `surface #F8F6F2`), same Lora/Inter fonts, same spacing/shadow/radius scale. `ink-lovable` HEAD is `5618f39` — the exact commit the handoff was last synced to (2026-06-20). **The design docs are already truthful to the current design.**

**Conclusion:** the tokens/specs are correct; **the theme implementation does not honor them.** The composition layer (hero, section patterns, button block styles, story cards) was built to a lower fidelity than the design and must be reworked.

**When/how discovered.** A staging visual review on 2026-07-19 (live `nuwe-ink.local` vs the Lovable preview) surfaced the gap. A prior agent then ran the full diagnosis + Phase-1 UX spec + a four-lens reviewer gate before this proposal.

**Evidence (each a concrete acceptance-criterion source in the fidelity spec):**
1. **Buttons** — `theme.json` has **no `styles.elements.button`** rule (only a font-family), so radius/fill/text-colour fall to core defaults → over-rounded shape + wrong colours. Design wants ~6–8px radius, terracotta-fill/white-text primary, terracotta-outline secondary.
2. **Background** — missing the plus-pattern texture layer (Lovable `HeroSpotlight.tsx:48`, opacity 0.03).
3. **Hero heading** — wrong scale; theme caps at `3xl = 2rem`, design needs a fluid 30→48px headline with a two-tone gradient accent.
4. **Hero layout** — should be a two-column split (headline left, challenge card right); `front-page.html` renders patterns stacked full-width.
5. **Cards** — different type scale/colours/layout and **no hover animations**; `is-style-card` has border+shadow but no hover-lift.
6. **No enqueued stylesheet** — `grep` finds zero `wp_enqueue_style`; §0.3–§0.6 all depend on a real `home.css`.
7. **Dynamic sections render empty or placeholder** — `ink/wenner-kollig` renders a flat `<h2>+<ul>/<li>` (no card DOM); the weekly-challenge card is a static teaser; featured bydraes are 3 hardcoded placeholder cards ("Titel van die werk / href=#").

The fidelity lens spot-checked ~70 discrete values against the Lovable source and confirmed the tokens are overwhelmingly correct and every "current gap" claim matched the actual theme code — corroborating that this is a **rebuild, not a re-token**.

---

## Section 2 — Impact Analysis

### Epic Impact
- **Epic 15 (Organisation pages & contact) — `done`, merged (c7e6a40).** Story **15-1 (Tuisblad)** shipped the home-page *assembly* as `done`, but at low visual fidelity. Epic 19 **reworks its presentation output**; it does **not revert** 15.1 (the page/template assembly and content wiring stand). This is a fidelity rework on top of a completed assembly, not a rollback.
- **Epic 12A (Challenge adjudication automation) — `done`, merged (d99cd0c).** §5 (winner card) requires an **`ink/wenner-kollig` markup upgrade** — the block currently emits a flat list the theme cannot style into the designed card. This is a **cross-epic change to 12A's output block**, behind its existing `ink_home_featured_winner` seam (theme supplies token styling only).
- **Epic 1 (Foundation) — `done`.** `theme.json`, patterns, `front-page.html` and block-style registrations authored here are the surfaces Epic 19 rewrites. No revert — an in-place fidelity uplift.
- **New epic required — Epic 19.** The rework is substantial, crosses the theme↔`ink-core` boundary, and needs an auditable acceptance-criteria trail → a governed epic, not an ad-hoc patch.
- **No impact** on Epics 2–14, 16–18, 12B (business logic, migration, SEO/security stand unchanged).

### Story Impact
- **8 NEW stories** under Epic 19 (detail in Section 4). Five are theme presentation (spec IDs 19.1–19.5, preserved so the signed-off spec's references stay valid); three are `ink-core` data prerequisites (19.6–19.8) — split out because the buildability review flagged that treating data-wiring as parallel to presentation is a trap: **§3, §5, §6 have hard `ink-core` blockers that must land ahead of their consuming presentation stories.**
- **No existing story is reopened or amended** except Epic 12A's `ink/wenner-kollig` block, whose markup upgrade is captured as **Story 19.7** (extends 12A rather than editing its closed story files).

### Artifact Conflicts (documents to update)
- **`epics.md`** — insert **Epic 19** (8 stories) after Epic 18. *(Primary edit.)*
- **`sprint-status.yaml`** — add `epic-19: backlog` + the 8 story entries as `backlog`.
- **`theme-fidelity-spec.md`** — already `final`; no change. It is the story-level acceptance-criteria source Epic 19 stories cite.
- **`project-context.md`** — *(optional, retro-owned)* add a note that theme surfaces require a visual-fidelity composition contract, not tokens alone — the root-cause rule. Deferred to the Epic 19 retrospective, not required to start.
- **PRD / `architecture.md`** — **no change.** No requirement, three-layer boundary, or architecture decision is altered; Epic 19 *reasserts* the three-layer rule (all computation in `ink-core`; theme styles only).

### Technical Impact
- **New:** `assets/css/home.css` (first enqueued theme stylesheet; front-page-gated), `styles.elements.button` + `core/button` block styles (`is-style-ink-primary/-outline/-sage/-sage-outline`), fontSize presets `4xl`/`5xl` + fluid `hero`, tier/gold palette tokens (`brons`/`silwer`/`goud`/bright `gold`), an inline-SVG icon convention, `color-mix()` tint convention, plus-pattern data-URI, `fade-up`/`underline-slide` keyframes, and reduced-motion + focus-ring bases.
- **`ink-core`:** a new `ink/huidige-uitdaging` dynamic block; an upgrade to `ink/wenner-kollig` markup; a featured-stream surface with **read-time computed from word count in `ink-core`** (never the theme) + engagement counts.
- **Rewritten:** `front-page.html` + section patterns to `core/group` Grid layout (not `core/columns`, which stacks at ~782px and can't hit the 1024/768 splits) with per-child `columnSpan` for the asymmetric featured grid.
- **Three-layer rule held:** the theme performs *no* computation; every dynamic value comes from an `ink-core` seam and each dynamic section **collapses gracefully** when empty (no placeholder ever reaches production).
- **Accessibility (NFR-5) folded in:** visible focus rings, reduced-motion covering all transforms, alt-text on avatars/featured images, contrast fixes (winner eyebrow off gold-on-gold; badge/CTA-body contrast ≥ floor), and correct heading order.

---

## Section 3 — Recommended Approach

**Selected: Option 1 — Direct Adjustment (add a new governed epic).** Hybrid note: it carries one small cross-epic change (12A's winner block) folded in as Story 19.7.

| Option | Verdict | Why |
|---|---|---|
| **1 — Direct Adjustment (new Epic 19)** | ✅ **Recommended** | The design intent never changed; only the implementation under-delivered. A scoped epic against an already-signed-off spec re-expresses the design in WP primitives with a full acceptance-criteria audit trail. Effort **Medium**, risk **Low** (spec is validated; tokens already correct). |
| **2 — Rollback** | ❌ Not viable | Nothing to revert — the theme *works*, it's just low-fidelity. Reverting 15.1/Epic 1 would destroy correct assembly + token work and buy nothing. |
| **3 — PRD / MVP review** | ❌ Not applicable | The MVP goal is unchanged; the Lovable design was always the target. No scope reduction needed — this closes a build-quality gap, not a scope question. |

**Rationale.** The spec is a *trustworthy, validated build contract* (four-lens gate: "accept with fixes," all fixes applied in v2, promoted to `final` with owner sign-off). Per-story `dev-story` (over a single `quick-dev` pass) keeps the acceptance criteria auditable for a rework this size and lets the hard `ink-core`→theme sequencing be enforced story-by-story. Timeline impact is contained: one epic, sequenced, with the highest-impact foundational fix (button element styles + `home.css`) first.

---

## Section 4 — Detailed Change Proposals

### 4.1 — NEW: Epic 19 in `epics.md` (inserted after Epic 18)

> **APPROVED SLICE (2026-07-19): five stories.** Per owner decision the `ink-core` data-wiring is folded back into 19.3 (cards: `ink/huidige-uitdaging` block + `ink/wenner-kollig` upgrade) and 19.4 (featured: featured-stream + read-time + counts) rather than split into standalone 19.6–19.8. The **canonical Epic 19 text now lives in `epics.md`** (5 stories: 19.1 Foundations, 19.2 Hero, 19.3 Cards, 19.4 Featured bydraes, 19.5 Polish). Each story's AC keeps "build the collapsing `ink-core` seam before its presentation." The 8-story draft below is retained as the impact-analysis record but is **superseded by the 5-story slice in `epics.md`.**

Full text proposed below (BMAD-conformant, matching the epics.md story format). **Build order** (encoded in the epic intro): `19.1` → (`19.6`, `19.7`, `19.8` data prereqs) → `19.2`, `19.3`, `19.4` → `19.5`.

> ## Epic 19: Theme visual-fidelity rework (NEW — 2026-07-19)
>
> Re-express the signed-off **theme visual-fidelity spec** (`ux-designs/ux-ink-vibe-2026-06-15/theme-fidelity-spec.md`) in WordPress primitives. The `ink-foundation` theme was built to the design *tokens* but never to a composition *contract*; the Tuisblad renders as a plain stack instead of the Lovable editorial layout. This epic rebuilds the home page + shared primitives (buttons, cards, badges, background texture, animations) to high fidelity, adds the missing enqueued stylesheet, and wires the three dynamic sections to real `ink-core` data with graceful collapse. **No business logic enters the theme** — §3/§5/§6 dynamic data come from `ink-core` seams. **Build order:** 19.1 foundations first (everything depends on it); then the `ink-core` data prerequisites 19.6/19.7/19.8; then presentation 19.2/19.3/19.4; then 19.5 polish. Reworks Epic 15's Tuisblad (15.1) presentation and extends Epic 12A's `ink/wenner-kollig` block — neither is reverted.
>
> ### Story 19.1: Foundations — button styles, type ramp, tokens, icon & tint conventions, home.css
>
> As a theme developer,
> I want the shared visual primitives and the CSS delivery mechanism in place,
> So that every later home-page story builds on a correct, token-driven base. (§0, NFR-2/NFR-5)
>
> **Acceptance Criteria:**
>
> **Given** the fidelity spec §0
> **When** foundations are built
> **Then** `theme.json` gains `styles.elements.button` (radius ≤ 8px, terracotta fill, `surface-alt` text, Lora) + `core/button` block styles `is-style-ink-primary`/`-outline`/`-sage`/`-sage-outline` (hover + `:focus-visible` ring via `inline_style`); button **size is baked per-instance** in locked patterns (sm/default/lg/xl padding + font-size + min-height), never a block style
> **And** fontSize presets `4xl` (36px) + `5xl` (48px) + a fluid `hero` preset (30→48px) are added; tier/gold palette tokens `brons` #A6754C, `silwer` #9AA3AD, `goud` #C9B88A + a bright `gold` #E8B130 (winner-gradient top stop only) are registered
> **And** an inline-SVG icon convention (16px `currentColor`, `aria-hidden`, accessible labels where meaning-bearing), a `color-mix()` alpha-tint convention (opaque fallback first; no `hsl(var/a)`), the plus-pattern data-URI, `fade-up`/`underline-slide` keyframes, and a `prefers-reduced-motion` base (disables all transforms) are established
> **And** a versioned `assets/css/home.css` is enqueued via `wp_enqueue_style`, gated to the front page, mirroring the existing script-enqueue pattern.
>
> ### Story 19.2: Hero — two-column split, badge, gradient heading, plus-pattern
>
> As a visitor,
> I want the hero to match the designed editorial layout,
> So that the home page reads as intended. (§1, §2)
>
> **Acceptance Criteria:**
>
> **Given** §2 (and §1 header)
> **When** the hero is built
> **Then** it is a two-column `core/group` Grid ≥1024px (content left, challenge card right), single column below; the badge pill (`primary`/10 via `color-mix`, 14px/500, sentence case, contrast ≥ floor) is present; the h1 is fluid 30→48px/600/1.25 with an inline `.ink-text-gradient` accent phrase (`@supports` + solid fallback); two `lg` buttons per §0.1; the plus-pattern texture sits behind the section (`aria-hidden`, never intercepts clicks); a single visible `h1`; and the sticky header renders `Begin skryf` as `is-style-ink-primary` with the terracotta feather glyph, underline-slide nav links + focus ring, collapsing to a hamburger < 768px.
>
> ### Story 19.3: Cards — hero challenge card + Uitdaging & Winner feature cards + hover-lift
>
> As a visitor,
> I want the challenge and winner cards rendered as designed with real data,
> So that the home page surfaces live editorial moments. (§3, §5)
>
> **Acceptance Criteria:** *(depends on 19.6 + 19.7)*
>
> **Given** §3 + §5 and the `ink-core` data seams from 19.6/19.7
> **When** the cards are built
> **Then** the weekly-challenge card renders in the hero right column (12px radius, 1px border, `surface-alt`, `shadow.sm`, hover-lift reduced-motion-safe, decorative `rounded-bl` corner `aria-hidden`, UPPERCASE type badge + deadline meta) fed by the `ink/huidige-uitdaging` block; the Uitdaging feature card (§5, 16px radius, `shadow.md`, Trophy eyebrow, `rounded-bl-full` corner) and the Winner card (§5, `goud`/`gold-muted` gradient, Crown watermark, **eyebrow set in `text`/`muted-text` — never gold-on-gold**, rank label always text+icon, avatar with alt text) render off the upgraded `ink/wenner-kollig` block; per-rank "[Maand] algehele wenner" (1st) vs "[Maand] wenner" (2nd/3rd), algehele-first; **no live data → each section collapses gracefully.**
>
> ### Story 19.4: Featured bydraes — asymmetric grid + real featured stream
>
> As a visitor,
> I want "Die redakteur se keuse" to show real featured works,
> So that the home page reflects live editorial curation. (§6)
>
> **Acceptance Criteria:** *(depends on 19.8)*
>
> **Given** §6 and the `ink-core` featured stream from 19.8
> **When** the section is built
> **Then** it renders a `core/group` Grid with the featured card spanning 2 columns (asymmetric, per-child `columnSpan`), each card `rounded-xl` 12px + hover-lift (reduced-motion-safe), category pill, read-time, avatar (alt) + author, and reaksie counts (Heart/MessageCircle) with `_n()` af plurals + accessible labels; the UPPERCASE terracotta eyebrow + 30/36px title + focusable "Sien alle werke" header; title hover → terracotta; card titles `h3`; **read-time + counts are `ink-core`-owned (no theme-side computation)**; no "Titel van die werk"/`href="#"` reaches output; **empty feed → the section hides entirely** (owner decision).
>
> ### Story 19.5: Polish — CTA gradient, footer 4-col, borg chips, animations
>
> As a visitor,
> I want the remaining home sections finished to fidelity,
> So that the whole page is consistent. (§7, §8, §10, §0.6)
>
> **Acceptance Criteria:**
>
> **Given** §7/§8/§10/§0.6
> **When** polish is applied
> **Then** the CTA band is a terracotta gradient (`rounded-3xl` 24px, 40/64px padding, two faint decorative circles `aria-hidden`, `surface-alt` heading up to 48px, body at full `surface-alt` for contrast, two `xl` buttons with focus rings); the footer is 4-column ≥768px (`secondary`/30 bg, top border, filled-terracotta heart, Afrikaans org placeholders — never US "501(c)(3)"); the borg strip renders per-tier chips + sage eyebrow + "Word 'n borg" sage-outline CTA when ≥1 active `borg` (collapses when none), logos have alt text and `hover:scale-105` is reduced-motion-safe; `fade-up` + `underline-slide` animations fire once on mount and are fully reduced-motion-gated.
>
> ### Story 19.6: `ink/huidige-uitdaging` dynamic block (ink-core data prerequisite for 19.3)
>
> As an ink-core developer,
> I want a dynamic block for the current open challenge,
> So that the hero challenge card shows live data. (§3, §11)
>
> **Acceptance Criteria:**
>
> **Given** the current open `uitdaging`
> **When** the block renders
> **Then** a new `ink/huidige-uitdaging` block (thin `render` + pure `toHtml` + data seam + graceful collapse, house style per `FeaturedWinners`/`HomepageStrip`) emits the current uitdaging's title, prompt excerpt, and deadline; the open-`uitdaging` query lives in `ink-core`; no open challenge → the block collapses (no placeholder); conflation-clean; unit tests cover the query args + `toHtml` output + empty collapse.
>
> ### Story 19.7: `ink/wenner-kollig` markup upgrade (ink-core; extends Epic 12A; prerequisite for 19.3)
>
> As an ink-core developer,
> I want the winner block to emit the designed card DOM,
> So that the theme can style the winner card without inventing structure. (§5, §11)
>
> **Acceptance Criteria:**
>
> **Given** the existing `ink_home_featured_winner` seam (Epic 12A)
> **When** the block is upgraded
> **Then** `ink/wenner-kollig` (`FeaturedWinners::toHtml()`) emits the card DOM — avatar (with alt), `<blockquote>` quote, rank label (text + Crown icon), and `goud`/`gold-muted` token classes for the gradient — behind its existing seam; per-rank algehele/standard variants; no live winner → graceful collapse; theme supplies token styling only; the 12A ingestion/commit path is unchanged (markup-only upgrade); tests assert the emitted DOM + collapse without re-testing 12A internals.
>
> ### Story 19.8: Featured stream + read-time + engagement counts (ink-core data prerequisite for 19.4)
>
> As an ink-core developer,
> I want a featured-works stream with read-time and counts,
> So that "Die redakteur se keuse" shows real curated data. (§6, §11)
>
> **Acceptance Criteria:**
>
> **Given** published `gedig`/`storie`/`artikel` content
> **When** the featured stream is provided
> **Then** an `ink-core` featured-stream surface (block or `core/query` provider) yields real featured works with **read-time computed from word count in `ink-core`** and engagement counts as `ink-core`-owned values; empty stream → the consumer hides entirely (no empty-state copy); conflation-clean; no theme-side computation; unit tests cover read-time math + ordering + empty behaviour.

### 4.2 — NEW entries in `sprint-status.yaml`

```yaml
  # Epic 19: Theme visual-fidelity rework (NEW — 2026-07-19, Correct-Course)
  # Reworks Epic 15 Tuisblad (15.1) presentation + extends Epic 12A ink/wenner-kollig.
  # Build order: 19-1 → (19-6, 19-7, 19-8 data prereqs) → 19-2, 19-3, 19-4 → 19-5.
  epic-19: backlog
  19-1-foundations-button-styles-type-ramp-tokens-home-css: backlog
  19-2-hero-two-column-split-badge-gradient-plus-pattern: backlog
  19-3-cards-challenge-uitdaging-winner-hover-lift: backlog        # depends on 19-6, 19-7
  19-4-featured-bydraes-asymmetric-grid-real-stream: backlog       # depends on 19-8
  19-5-polish-cta-footer-borg-chips-animations: backlog
  19-6-ink-huidige-uitdaging-block: backlog                        # ink-core prereq for 19-3
  19-7-ink-wenner-kollig-markup-upgrade: backlog                   # ink-core prereq for 19-3 (extends 12A)
  19-8-featured-stream-read-time-counts: backlog                   # ink-core prereq for 19-4
```

### 4.3 — Optional (retro-owned, not required to start)
- `project-context.md`: add a rule that theme surfaces need a visual-fidelity composition contract, not tokens alone — deferred to the Epic 19 retrospective (root-cause capture).

---

## Section 5 — Implementation Handoff

**Scope: Moderate → Product Owner / Developer.**

| Recipient | Responsibility |
|---|---|
| **PO / Dev (this workflow, on approval)** | Apply 4.1 (Epic 19 → `epics.md`) + 4.2 (`sprint-status.yaml`). Then run `bmad-create-story`/`create the epics and stories list` to generate the 8 story files, and `bmad-sprint-planning` to reconcile status. |
| **Dev agent (Amelia, `bmad-dev-story`)** | Implement per story in build order — **19.1 first**, then the `ink-core` prereqs (19.6/19.7/19.8), then presentation (19.2/19.3/19.4), then 19.5. Verify each against the live Lovable mockup + the fidelity spec's per-section ACs. |
| **Reviewer (`bmad-code-review`)** | 3-layer adversarial review at epic close → `epic-19-code-review-<date>.md`. |
| **Retro (`bmad-retrospective`)** | Capture *why* the original build drifted (built without a visual-fidelity contract; unit tests read theme files by path and never caught visual regressions) → promote the root-cause rule into `project-context.md`. |

**Success criteria:** the rendered Tuisblad on staging matches `design-lovable.png` to the fidelity spec's ACs; all three dynamic sections render real `ink-core` data or collapse gracefully (zero placeholders in production); NFR-2 (tokens only) + NFR-5 (a11y floor) + the three-layer rule hold; unit suite green (`composer test:unit`) + `copy:scan` no new debt.

**Sequencing dependency (hard):** 19.6/19.7/19.8 (`ink-core` data) MUST land before their consumers (19.3/19.4). A card story run before its provider ships will silently show graceful-collapse (no live data) and pass — masking the gap.

---

## Approval

- [ ] **Approved** — apply 4.1 + 4.2 and hand off to Dev.
- [ ] **Revise** — (note changes below)

_Decisions captured on approval: story count (8 = 5 presentation + 3 ink-core data prereqs) and the 19.6–19.8 IDs for the data-wiring split._

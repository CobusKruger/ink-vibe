# Sprint Change Proposal — Administrative Burden-Reduction Scope Increase

**Project:** ink-vibe · **Date:** 2026-06-20 · **Author:** Cobus (via Correct Course workflow)
**Trigger source:** `docs/new-requirements-18-june/administrative-requirements.md` (R1–R8, R9, G1)
**Change scope classification:** **MAJOR** (new launch pillar + MVP re-scope) — see §5.

---

## Section 1 — Issue Summary

A meeting with the site owner on **18 June 2026** surfaced a set of new requirements that were moved **into scope**. The existing plan — strong on structure, design, and member-facing functionality — leaves the owner's **day-to-day administrative burden unchanged**, which is unacceptable to her. Most of the new requirements automate work the owner currently does **manually** on the legacy site.

**Issue type:** New stakeholder requirement + deliberate scope increase. **Not** a defect, misunderstanding, or failed approach.

**Discovery context:** Requirements captured post-meeting and documented with two real supporting artifacts:
- `INK Mei projek inskrywings.eml` — a real anonymized judge-collation email (the manual output of R1 today).
- `INK SIA MEI 2026 KOMMENTAAR EN UUTSLAE ES-1.docx` — a real judges' results document (the input to R2).

**Project state:** The repository is at **BMAD planning stage** — no `ink-core` / theme code is implemented yet. This is therefore a **planning-artifact scope change**, not a rework of in-flight sprint code. **Rollback (a standard Correct-Course option) is Not Applicable** — there is nothing built to revert.

**The requirements (with confirmed priorities):**

| # | Requirement | Priority |
|---|---|---|
| R1 | Auto-collate challenge entries into an anonymized judge email | **P0 (MVP)** |
| R2 | Ingest judges' results & commentary → winners post, feedback, banners, featuring, triggers tier calc | **P0 (MVP)** |
| R3 | Automatic Gradering (tier) calculation & promotion; new Meester tier | **P0 (MVP)** |
| R4 | Automatic Biblioteek entry update | **P0 — stub only** (deferred body) |
| R5 | Membership lifecycle automation (emails, per-term config) | **P1 (launch)**; recurring + discount = **post-launch** |
| R6 | Account approval & spam detection | **P1 (launch)** + research spike |
| R7 | Automatic post-receipt notification | **P1 (launch)** |
| R8 | Analytics provider + private-profile read counts | **P1 (launch)** |
| R9 | Annual competition management *(doc mislabels this "R8")* | **P2 (post-launch)** |
| G1 | Terminology refinements | Cross-cutting, P0 |

---

## Section 2 — Impact Analysis

### 2.1 Owner decisions taken during this session

These four conflicts were genuine business calls; the owner/Cobus resolved them as follows, and the proposal below reflects them:

1. **Recurring (auto-renew) billing — KEEP DEFERRED post-launch.** Reverses nothing; honors spec §14.8 / OQ-9. At launch, R5 ships auto-activation + lifecycle emails + per-term config only.
2. **Recurring-renewal discount — ALLOWED** (reverses spec §14.5 "no discount model", 2026-06-14). Because it is tied to recurring signup, it is effective **post-launch** when recurring ships. A new dated decision row supersedes §14.5 for the recurring case.
3. **Membership terms — KEEP 1 / 6 / 12 month** (spec §10.1 authoritative). R5's "3-month" wording is dropped; per-term email config targets 1mo / 6mo / 12mo.
4. **R1 + R2 — BOTH REMAIN P0**, with two simplifications:
   - **No `.docx` parser.** Results are **pasted as plain text** by the user. Removes the PhpWord/PhpOffice Composer dependency and the entire untrusted-ZIP / XXE / zip-bomb security surface.
   - **Templates are simple form-letter text** with a **name-merge in the greeting line** (e.g. "Beste {skrywer}, …"), not a configurable rich-template engine. Applies to the winners post, congratulation email, lifecycle emails, and receipt messages.

### 2.2 Epic impact

| Epic | Impact | Detail |
|---|---|---|
| **NEW: Challenge adjudication automation** (or major Epic 12 extension) | **ADD** | R1 collation, R2 paste-ingestion + coverage report, winners-post generation, moderator-feedback comment type, winner banner, featured-feed ordering. The largest net-new build. |
| **Epic 5 — Writer tiers** | **MODIFY (heavy)** | R3: extend `Tier` enum with `Meester`; add win-count meta + reset-on-promotion; **automatic promotion engine** (5/15 thresholds, multi-win counting); private-profile "wins needed" subtext; promotion congratulation email. |
| **Epic 4 — Membership & payment** | **MODIFY + reversals** | R5: confirm auto-activate → thank-you email; lifecycle emails (1mo + 1wk warnings, thank-you) + per-term on/off config. Recurring + discount = post-launch. |
| **Epic 3 — Accounts / auth** | **MODIFY + spike** | R6: social login; optional manual-approval backstop; anti-spam research spike. |
| **Epic 9 — Profiles / engagement** | **MODIFY** | R7 receipt notification (new trigger, randomized message); R8 read-count surface on My Profiel. |
| **Epic 10 — Biblioteek** | **NOTE only** | R4 update *hook* noted; body deferred with the rest of the biblioteek analysis. |
| **Epic 15 — Home / discovery** | **MODIFY** | R2 winners-post featured slot + featured-feed ordering ("algehele wenner" first). |
| **Epic 17 — Terminology** | **REOPEN** | G1 reopens the "Done" terminology story; corrections must propagate to **all** docs. |
| **Epic 18 — SEO/security/perf** | **ADD** | R8 analytics provider (absent everywhere today); R6 registration anti-spam extends the security stack. |
| **NEW (P2): Annual competition** | **ADD (deferred)** | R9 — reuses the R1/R2/R3 machinery on an annual cadence. |
| **NEW (foundation, early): Configurable form-letter + notification capability** | **ADD** | Lightweight: stored form-letter text + name-merge, per-event on/off toggles, randomized message list (R7). Consumed by R2, R3, R5, R7. |

**Hard dependency chain (build order, even though all P0):** **R1 → R2 → R3.** R1 assigns & stores the per-type `EntryID`; R2 matches pasted results against stored `EntryID`s; R3 auto-promotion is triggered as R2's final step and consumes R2's placement records. The **EntryID data-model change is the linchpin** and must land first.

### 2.3 Artifact-conflict summary (9 conflicts)

| # | Conflict | Resolution in this proposal |
|---|---|---|
| C1 | Tier auto-promotion deferred to P2 (§14.2) vs R3 P0 | **Accept P0** — R3 supplies the promotion rules the deferral was waiting on. |
| C2 | Recurring billing deferred (§14.8) vs R5 at launch | **Keep deferred** (owner decision). |
| C3 | No-discount ruling (§14.5) vs R5 recurring discount | **Allow** for recurring, post-launch (owner decision; new dated decision row). |
| C4 | Membership terms 1/6/12 vs R5 "3-month" | **Keep 1/6/12** (owner decision). |
| C5 | Comments disabled site-wide vs R2 moderator-feedback comment | Implement as **custom structured `comment_type = ink_moderator_terugvoer`** via `wp_insert_comment` + writer display-toggle. **No native WP comments re-enabled.** |
| C6 | Skrywersprofiel = public (R3, spec, UX) vs private (G1) | **Skrywerprofiel = public; My Profiel = private.** Rewrite G1's wording. Propagate to all docs. |
| C7 | `intekenaar`/`intekening` vs `lid`/`lidmaatskap`/`betaalde lid` (G1) | **Adopt G1's lid family** as canonical: `lidmaatskap` (membership), `lid`/`lede` (members), `gratis lid` (free) vs `betaalde lid` (paid). Update `afrikaans-terms.md` first, then propagate. |
| C8 | R6 manual approval vs frictionless signup (UJ-1) | **Layered:** anti-spam + social login on; manual approval an **optional, off-by-default backstop**. |
| C9 | Winner banner + tier colours need to be pinned down in tokens | Banner **already designed** (home page, *The Last Light of Winter* = "December Winner"; copy token `Desember-wenner`, `ui-copy-translations.md:80`). Define the per-rank variants (`algehele wenner` 1st vs `wenner` 2nd/3rd) and Brons/Silwer/Goud colour tokens; **Meester uses the existing brand red-orange `primary: #EA4015`** (not `danger` #EF4444). Pair colour with text/icon (no colour-only rank encoding). |

### 2.4 Technical impact (architecture)

- **Data model (mostly extends existing stores — AD-5):**
  - `EntryID` → new column(s) on the existing `ink_entries` custom table (`entry_type`, `entry_number`), **assigned at collation time** (not entry time), per-type sequence. Linchpin for R2.
  - Win-count → new **user-meta** key (e.g. `ink_tier_win_count`) alongside `ink_writer_tier` / `ink_tier_promoted_at`; reset by `Tiers::promote()`.
  - `Tier::Meester` → trivial Kernel enum extension; manual-only terminal state (no threshold).
  - Placement / winner flag (`algehele wenner` 1st vs `wenner` 2nd–3rd) → extends `ink_entries` placement columns; drives banner + featured ordering.
  - Form-letter templates, send toggles, randomized message list → WP **options** owned by the new Notifications/Templates surface (simple text + name-merge, per §2.1).
- **Services (attach to existing modules; respect the ~8–12 module cap):** judge-email composer + paste-text results parser → **Challenges**; promotion engine → **Tiers**; form-letter/notification dispatch → **Notifications** (expands from BP-only to transactional email); analytics + read-count → **Discovery** (reuses already-denormalized `_ink_read_count`); anti-spam/social-login → vetted **platform plugins** via hooks, not `ink-core`.
- **THE conflation rule holds:** R3 promotion engine lives in `Ink\Tiers` (never reads Entitlement); R5 membership logic lives in `Ink\Entitlement` (never reads Tiers); Notifications is the only shared **downstream event consumer**. The existing Deptrac CI rule (AD-8) enforces this — new code must pass it.
- **Integration points:** WC Memberships lifecycle events → Action Scheduler (already bundled) for the 1mo/1wk expiry sweeps; PayFast recurring **NOT** integrated at launch (deferred); analytics + social-login = new vetted-plugin seams.
- **NFR / security / i18n:**
  - `.docx` parsing risk **eliminated** by the paste-only decision.
  - **Leak-scan gate (NFR-1) must extend to the new template/options store** — admin-authored form-letter text is not covered by the build-time `.mo` + page-crawl scan today. Enforce Afrikaans at the admin boundary or scan the option store.
  - R6 adds a registration anti-abuse surface and a possible "pending approval" account state.
  - R8 analytics + R5 emails sharpen the deferred **POPIA** question (OQ-3) — may need addressing sooner.

### 2.5 PRD / Spec / UX impact highlights

- **PRD MVP framing breaks.** PRD calls PayFast *"the single change that lifts the manual-operations tax"* — adding R1–R4 at P0 makes editorial automation a **second launch pillar** and roughly **doubles** the challenge-admin MVP. §1 and §14 need re-scoping.
- **Spec §14 resolved-decision rows** must get new dated rows: §14.2 (tier auto-promotion → now P0), §14.5 (discount → allowed for recurring, post-launch). §14.8 (recurring) unchanged.
- **Spec vocabulary additions** (§6 / §4 / glossary): `EntryID`, `Meester`, win-count, winner/`algehele wenner` flag, winner-banner, `ink_moderator_terugvoer`, winners-announcement post, gratis/betaalde lid, lifecycle-email set, analytics provider + read-count.
- **Admin surfaces use WP admin chrome — no design-system work.** R1, R2-ingest, R3-promotion UI, R5 email-config, R6 approval-queue all live in **wp-admin** (Settings API / list tables / `@wordpress/components`) and do **not** use the front-end design system (`theme.json` / Lovable tokens). What needs specifying is **interaction flow + states + Afrikaans labels**, not visual design — captured as **acceptance criteria in Epic 12A** (the multi-step R1 preview and R2 parse→coverage→confirm→irreversible-actions flow). No dedicated admin-UX/mockup pass required *(owner decision 2026-06-20)*.
- **UX conflicts:** Gemeenskapsreaksies is documented as "the ONLY feedback path" — the moderator-feedback comment is the sanctioned exception (structured, programmatic). Winner banner is **already designed** (home page, *The Last Light of Winter*; copy `Desember-wenner`). Remaining UX work: per-rank/per-tier banner variants, Brons/Silwer/Goud colour tokens (Meester = brand `primary #EA4015`), and avoiding colour-only rank encoding (a11y).

---

## Section 3 — Recommended Approach

**Selected path: HYBRID — Direct Adjustment + PRD MVP Review.**

- **Direct Adjustment** (add/modify epics & stories) is appropriate because nothing is built yet — there is no rework cost, only planning-artifact updates.
- **PRD MVP Review** is required because the new P0 items change the launch contract from *"replacement-parity + PayFast"* to *"replacement-parity + PayFast + editorial automation pillar."*

**Rationale:**
- **Business value:** directly targets the owner's burden — the explicit purpose of the change; philosophically aligned with the PRD's own counter-metric SM-C3 ("do not raise per-item manual editorial effort").
- **Effort:** **High** — one new epic (challenge adjudication), heavy Epic 5 changes, a foundation capability, plus P1 spread. Materially reduced by the paste-only + simple-form-letter decisions.
- **Risk:** **Medium** — well-specified with real sample artifacts; main risk is **launch-date pressure** from the doubled challenge-admin MVP. Mitigated by the simplifications and by R5-recurring/R9 staying post-launch.
- **Sustainability:** automation replaces manual editorial steps — net positive for long-term operability.

**Alternatives considered & rejected:** Rollback (N/A — nothing built); keeping R1/R2 at P1 (rejected by owner — burden relief is the point of the change).

---

## Section 4 — Detailed Change Proposals

> Format: **OLD → NEW** with rationale. Edits are grouped by artifact. These are *proposals* — applying them to the artifacts is the handoff work in §5.

### 4.A — Terminology / glossary (`docs/afrikaans-terms.md`) — do FIRST (everything else depends on it)

**4.A.1 — Membership term family (C7)**
- **NEW glossary entries:** `lidmaatskap` = membership; `lid` / `lede` = member(s); `gratis lid` = free member (account only: read, react, follow, reading list); `betaalde lid` = paid member / subscriber (additionally: post + all training material).
- **Action:** reconcile/retire `intekening`/`intekenaar` in favour of the `lid` family; sweep all docs (PRD §3, spec §4/§8, UX, FR-4.7 status copy "Jou intekening is aktief…" → "Jou lidmaatskap is aktief…").
- **Rationale:** owner-confirmed canonical vocabulary; the existing "intekening" copy must change. *Per project memory: a term correction propagates to ALL docs, not just one.*

**4.A.2 — Profile naming (C6)**
- **OLD (G1 wording):** "private profile … the Skrywersprofiel page, shown only to the writer."
- **NEW:** **Skrywerprofiel = the public writer profile** (shown to others); **My Profiel = the private profile** (shown only to the writer). Fix the "Skrywer**s**profiel" spelling drift to **Skrywerprofiel**.
- **Rationale:** R3, the spec (§8), and the UX docs all treat Skrywerprofiel as public; G1 is internally contradictory. Resolves where R3's "wins needed" subtext and R8's read counts land (→ My Profiel, private).

**4.A.3 — New domain nouns**
- Add: `Gradering` (= writer tier, synonym of ster gradering), `Meester` (4th tier), `EntryID` (inskrywingsnommer — per-type sequence within a challenge), `algehele wenner` (1st) vs `wenner` (2nd/3rd), `Terugvoer van die moderator` (moderator-feedback response).
- **Rationale:** Principle 4 — a concept enters the glossary **before** code/UI.

### 4.B — Consolidated spec (`docs/specs/ink-consolidated-spec.md`)

**4.B.1 — Tier enum (§3, §4, §6.3, §6.4, §10.1)**
- **OLD:** `ink_writer_tier` … `brons` / `silwer` / `goud`.
- **NEW:** `brons` / `silwer` / `goud` / **`meester`** (manual-only, never auto-promoted; rendered in the **brand red-orange `primary: #EA4015`** — distinct from `danger`). Add new user-meta `ink_tier_win_count` (top-3 wins toward next tier; reset to 0 on promotion).
- **Rationale:** R3.

**4.B.2 — Tier promotion algorithm (NEW subsection under §6.3 / Epic 5)**
- **NEW:** Automatic promotion engine. A *win* = any top-3 placement in any entry type at the writer's current gradering; multiple placements (incl. multiple in one category) each count. **Brons → Silwer at 5 wins; Silwer → Goud at 15 wins.** On promotion the win-count resets to 0. Manual promotion/demotion by staff remains (incl. manual-only Meester). Promotion fires a templated congratulation email.
- **Rationale:** R3 — net-new behavioural contract.

**4.B.3 — §14 decision rows (NEW dated entries, 2026-06-20)**
- §14.2: tier auto-promotion **moved P2 → P0** (rules now defined — see 4.B.2).
- §14.5: no-discount ruling **amended** — a genuine recurring-renewal discount is **permitted** (no vanity "%-off" framing); effective when recurring ships post-launch.
- §14.8: recurring billing — **unchanged** (remains deferred post-launch).
- **Rationale:** owner decisions; resolved rows cannot be silently overwritten.

**4.B.4 — Comments exception (§7)**
- **OLD:** "WordPress comments are disabled site-wide."
- **NEW:** add: "…with one programmatic exception — moderator feedback is stored as a custom structured response (`comment_type = ink_moderator_terugvoer`), never as an open WP comment; it is visible on a work only when the writer enables it on My Profiel."
- **Rationale:** C5 / R2.2.

**4.B.5 — Challenge adjudication (NEW subsection §6.5 / Epic 12)**
- **NEW:** `EntryID` stored on entries (per-type sequence, assigned at collation). Judge-email collation tool (R1). Paste-text results ingestion + coverage report (R2). Winners-announcement post (simple form-letter template, featured home slot, entry index with links). Winner banner + featured-feed ordering.
- **Rationale:** R1/R2.

**4.B.6 — Membership lifecycle (§10.1 / Epic 4)**
- **NEW:** auto-activation on PayFast triggers a thank-you email; lifecycle emails — 1-month-prior (longer terms) + 1-week-prior expiry warnings, thank-you on every activation; per-term (1/6/12) on/off + form-letter config. Recurring payment, the renewal-warning variant, and recurring discount = **post-launch**.
- **Rationale:** R5 + owner decisions.

**4.B.7 — Analytics & read count (§10 / §8)**
- **NEW:** select an analytics provider (currently none); surface read counts on **My Profiel** (verb-less count + `_n()` plurals), reusing `_ink_read_count`.
- **Rationale:** R8.

**4.B.8 — R4 stub (§6.2 / §9.4)** — one-line forward reference: "winning entries update the writer's Biblioteek; detail deferred with the broader biblioteek analysis (R4)."

### 4.C — PRD (`prds/prd-ink-vibe-2026-06-14/prd.md`)

**4.C.1 — §1 Vision / "Why now"**
- **OLD:** automation scoped to "front-end PayFast payment and tier tracking"; PayFast described as *"the single change that lifts the manual-operations tax."*
- **NEW:** add **editorial-automation as a co-equal launch pillar** (challenge adjudication, automatic tier promotion, lifecycle/receipt notifications); soften "single change" language.
- **Rationale:** the P0 additions change the launch contract.

**4.C.2 — FR-11/FR-12/FR-14 + §14.2 (tier)** — mirror 4.B.1/4.B.2: add Meester, win-count, auto-promotion at P0, private-profile subtext.

**4.C.3 — FR-4 / FR-63 / §13 (membership)** — recurring stays deferred; add lifecycle emails at launch; record the recurring-discount carve-out (post-launch).

**4.C.4 — New FRs** — challenge collation (R1), results ingestion (R2), winners post + banner (R2), receipt notification (R7), analytics + read count (R8), account approval/social-login (R6). Glossary edits per §4.A.

### 4.D — Epics / feature list (`docs/specs/ink-feature-list.md`)

- **ADD new epic** (or Epic 12 sub-epic) *Challenge adjudication automation* with stories: EntryID data model · judge-email collation tool (R1) · paste-text results ingestion + coverage report (R2) · winners-post generation · moderator-feedback comment type + display toggle · winner banner (depends Lovable sync) · featured-feed ordering.
- **ADD foundation story** (early): configurable form-letter text + name-merge + send toggles + randomized message list.
- **Epic 5:** add stories — Meester enum/red token · win-count meta · auto-promotion engine · private-profile subtext · congratulation email. Modify 5.4 (Meester display) and confirm 5.2 covers manual Meester.
- **Epic 4:** add lifecycle-email story; mark recurring + discount stories **P2/post-launch**.
- **Epic 3 / 18:** R6 anti-spam **research spike** → then social-login + optional approval backstop; R8 **analytics-provider** story (new).
- **Epic 9:** R7 receipt-notification trigger; R8 read-count surface.
- **Epic 10:** R4 note. **Epic 17:** reopen terminology (G1). **Epic 15:** featured ordering.
- **ADD P2:** R9 annual competition management.

### 4.E — Architecture (`_bmad-output/planning-artifacts/architecture.md`)

- Update **AD-5** (EntryID columns + collation-time lifecycle; win-count meta), **Kernel enum** (`Tier::Meester`), **AD-5a** (new `ink_moderator_terugvoer` comment type + display toggle), **AD-3 decision 6** (auto-promotion now in scope; `Tiers::promote()` stays sole write path).
- **New ADR:** lightweight form-letter/notification template store (options-based, name-merge only — *not* a rich engine), owned by Notifications; extend the **NFR-1 leak-scan to cover it**.
- **Remove** the `.docx`/PhpWord dependency and its XXE/zip security NFR from scope (paste-only).
- Note Action-Scheduler lifecycle sweeps (R5); PayFast recurring stays a documented **seam, not built**.

### 4.F — UX (`ux-designs/ux-ink-vibe-2026-06-15/`) + design handoff

- **Admin surfaces: no design-system work** (wp-admin chrome). Reword the UX admin note accordingly; capture R1/R2 interaction flow + states as **Epic 12A acceptance criteria** (done 2026-06-20). R5 email-config, R6 approval queue, R3 manual-promotion (extend UJ-5 for Meester) follow the same WP-admin pattern with Afrikaans `ink-core` labels.
- **Front-end mods:** My Profiel (wins-needed subtext, read count), Skrywerprofiel (Meester tier), home featured ordering, Kennisgewings (R7 trigger), auth (social-login buttons + optional pending state), lidmaatskap (terminology, post-launch recurring opt-in).
- **Winner banner already exists** (home page, *The Last Light of Winter* = "December Winner" / `Desember-wenner`, `ui-copy-translations.md:80`). Define the per-rank variants (`algehele wenner` vs `wenner`) and Brons/Silwer/Goud colour tokens; **Meester = brand `primary #EA4015`** (red-orange, already used on buttons). Pair colour with text/icon (no colour-only rank encoding).

---

## Section 5 — Implementation Handoff

**Scope classification: MAJOR.** This adds a launch pillar and re-scopes the MVP — it is a fundamental replan, not a backlog tweak.

| Recipient (BMAD agent) | Responsibility |
|---|---|
| **Tech Writer (Paige)** | **FIRST:** apply §4.A glossary edits to `afrikaans-terms.md`, then propagate the term changes across all docs (memory rule). Unblocks everything else. |
| **Product Manager (John)** | Apply §4.C PRD edits (MVP re-scope, new FRs, §14 rows) and confirm the launch contract. |
| **Architect (Winston)** | Apply §4.E architecture edits (EntryID model, enum, comment type, form-letter store, leak-scan extension, remove `.docx` dep). |
| **PM/PO + Dev** | Apply §4.D epic/story edits; resequence per the R1→R2→R3 chain; run `bmad-sprint-planning` to regenerate sprint status with the new/changed epics. |
| **UX Designer (Sally)** | Front-end only: winner-banner per-rank variants + Brons/Silwer/Goud colour tokens (Meester = brand `primary #EA4015`). **No admin-UX pass** — admin screens are WP chrome; their flow lives in Epic 12A ACs. |
| **Dev (Amelia)** | Implementation, after artifacts are updated — starting with the EntryID data model (linchpin) and the form-letter foundation. |

**Sequencing:** Terminology (4.A) → Spec/PRD/Arch (4.B/4.C/4.E) in parallel → Epics (4.D) → UX (4.F) → sprint re-plan → build (EntryID + foundation first, then R1 → R2 → R3).

**Success criteria:**
- All five planning artifacts reflect R1–R9 + G1 with the decisions in §2.1.
- The R1→R2→R3 dependency is explicit in the epic/story sequence.
- Glossary is the single source of truth and propagated everywhere; Skrywerprofiel/My Profiel public/private resolved.
- §14 decision rows updated with dated entries; no silent overwrite.
- Conflation rule (Entitlement ⟂ Tiers) preserved; Deptrac rule still passes.
- Afrikaans leak-scan extended to the new template store.

**Open items to confirm before/at build:**
- Winner-banner base design exists; only the per-rank/per-tier variants + Brons/Silwer/Goud colour tokens need defining (Meester = brand `primary #EA4015`).
- R6 anti-spam approach is a research spike (owner: "I know nothing about this").
- POPIA posture (OQ-3) may need addressing sooner given R8 analytics + R5 emails.

---

## Appendix — Checklist Status

| § | Item | Status |
|---|---|---|
| 1.1–1.3 | Trigger, problem, evidence | ✅ Done |
| 2.1–2.5 | Epic impact | ✅ Done |
| 3.1–3.4 | PRD / Architecture / UX / other artifacts | ✅ Done |
| 4.1 | Option 1 Direct Adjustment | ✅ Viable (chosen, hybrid) |
| 4.2 | Option 2 Rollback | N/A (nothing built) |
| 4.3 | Option 3 MVP Review | ✅ Viable (chosen, hybrid) |
| 4.4 | Recommended path | ✅ Hybrid: Direct Adjustment + MVP Review |
| 5.1–5.5 | Proposal components | ✅ Done |
| 6.1–6.2 | Final review & accuracy | ✅ Done |
| 6.3 | Explicit user approval | ✅ Approved 2026-06-20 |
| 6.4 | Update sprint-status.yaml | N/A — no sprint-status.yaml exists yet; epic edits applied to `ink-feature-list.md`; a future `bmad-sprint-planning` run generates sprint status from it |
| 6.5 | Confirm handoff | ✅ Edits applied directly (see "Edits applied" below) |

### Edits applied 2026-06-20 (per owner instruction "apply the artifact edits")
All §4 change proposals have been **applied directly** to the living artifacts:
- ✅ `docs/afrikaans-terms.md` (4.A — glossary, source of truth; done first)
- ✅ `docs/specs/ink-consolidated-spec.md` (4.B)
- ✅ `prds/prd-ink-vibe-2026-06-14/prd.md` (4.C)
- ✅ `docs/specs/ink-feature-list.md` (4.D — added Epic 12A *Challenge adjudication automation*, Epic 12B *Annual competition* (R9, P2); modified Epics 3/4/5/9/10/15/17/18)
- ✅ `architecture.md` (4.E — incl. new **AD-9** form-letter/notification store; `.docx` parser removed from scope)
- ✅ UX `EXPERIENCE.md` + `DESIGN.md` (4.F — incl. new **Admin surfaces** section flagged needs-design)
- ✅ `docs/ui-copy-translations.md` (terminology + new copy rows)

**Verified:** no stray retired terms (`intekening`/`intekenaar`/`intekenlid`, `Skrywersprofiel`) remain as live usage — only deliberate retirement/decision notes. Conflation rule (lidmaatskap ⟂ Gradering) preserved across all files.

**Open content items (human Afrikaans required — not AI-generated):**
- `ui-copy-translations.md`: membership lifecycle email copy (thank-you, 1-month, 1-week) and R7 randomized receipt messages marked **[NEEDS HUMAN AFRIKAANS]**.
- `afrikaans-terms.md`: the action-verb **"Inteken"** flagged for human confirmation (the noun *intekening* was retired; the button label needs an owner ruling).
- Historical PRD process logs (`extract-*`, `reconcile-*`, `review-*`) intentionally left unchanged (audit records).

## Appendix — Decisions Log (2026-06-20)
1. Recurring billing — **deferred post-launch** (unchanged).
2. Recurring-renewal discount — **allowed** (post-launch); supersedes §14.5 for recurring.
3. Membership terms — **1 / 6 / 12 month** (unchanged); R5 "3-month" dropped.
4. R1 + R2 — **both P0**; **no `.docx` parser** (paste-only); **simple form-letter templates** (name-merge only).
5. Tier auto-promotion — **accepted at P0** (R3 supplies the rules).
6. Moderator feedback — **custom structured comment type**, not WP comments.
7. Skrywerprofiel = public; My Profiel = private; G1 rewritten.
8. `lid` family adopted as canonical membership vocabulary.
9. R6 manual approval — **optional, off-by-default backstop**.
10. Annual competition — **R9, P2**.
11. Winner banner — **already designed** (home page, *The Last Light of Winter* = "December Winner" / `Desember-wenner`); only per-rank/per-tier variants + colour tokens remain.
12. Meester colour — **brand red-orange `primary #EA4015`** (the existing button colour), not the `danger` red.

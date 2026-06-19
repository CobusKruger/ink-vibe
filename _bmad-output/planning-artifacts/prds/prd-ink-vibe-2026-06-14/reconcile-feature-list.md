# PRD ↔ Feature-List Reconciliation

**Inputs:**
- PRD: `_bmad-output/planning-artifacts/prds/prd-ink-vibe-2026-06-14/prd.md`
- Addendum: `_bmad-output/planning-artifacts/prds/prd-ink-vibe-2026-06-14/addendum.md`
- Feature list (source): `docs/specs/ink-feature-list.md`

Date: 2026-06-15

---

## 1. `(FL x.y)` reference resolution — SYSTEMIC FAILURE

Almost **every** `(FL x.y)` traceability reference in the PRD points to the **wrong epic**. There is a wholesale **off-by-one-epic** drift: from FR-4 onward, each FR cites the epic number that is exactly **one lower** than the correct feature-list epic. This is consistent with the feature list having been re-numbered/re-ordered (an epic inserted, or epics renumbered into build/dependency order) **after** the PRD's FL references were written, with the references never updated.

The story numbers (`.y`) are almost all correct — only the epic prefix is off by one. So the fix is mechanical: bump the epic number by +1 for the whole block (with the special FR-1/2/3 case below).

### 1a. Auth FRs point at a non-existent story (worst case)

| FR | PRD says | Reality | Correct ref |
|---|---|---|---|
| FR-1 Register/login/reset | FL 14.5 | Epic 14 is **Sponsors** (14.1–14.4); **FL 14.5 does not exist** | **FL 3.1** (Authentication pages) |
| FR-2 No signup intent gate | FL 14.5 | non-existent | **FL 3.2** (intent capture — *removed*) and/or FL 3.3 |
| FR-3 First social-action prompt | FL 14.5 | non-existent | **FL 3.3** (Registration lifecycle / onboarding) |

FR-1/2/3 do **not** follow the +1 rule — they cite a phantom `14.5`. These are the only references that resolve to nothing at all.

### 1b. The off-by-one block (FR-4 → FR-62)

Every reference below resolves to the *wrong* real story (an existing entry, but the wrong feature). Representative mappings:

| PRD FR (and its FL ref) | Resolves to (wrong) | Should be |
|---|---|---|
| FR-4 Membership products (FL 3.1) | Authentication pages | **FL 4.1** |
| FR-5 PayFast purchase (FL 3.2) | intent capture (removed) | **FL 4.2** |
| FR-6 Access enforcement (FL 3.3) | registration lifecycle | **FL 4.3** |
| FR-7 Lidmaatskap page (FL 3.4) | *nonexistent (Epic 3 has no 3.4)* | **FL 4.4** |
| FR-8 Renewal (FL 3.5) | nonexistent | **FL 4.5** |
| FR-9 Status messaging (FL 3.7) | nonexistent | **FL 4.7** |
| FR-10 Store suppression (FL 3.6) | nonexistent | **FL 4.6** |
| FR-11 Tier model (FL 4.1) | Membership products | **FL 5.1** |
| FR-12 Set/adjust tier (FL 4.2, 4.3) | PayFast / Access enforcement | **FL 5.2, 5.3** |
| FR-13 Tier≠sub guardrail (FL 4.6) | Store suppression | **FL 5.6** |
| FR-14 Tier display (FL 4.4) | Lidmaatskap page | **FL 5.4** |
| FR-15 Tier in discovery (FL 4.5) | Renewal | **FL 5.5** |
| FR-16–23 Submission (FL 5.x) | Writer tiers | **FL 6.x** (5.1→6.1, 5.2→6.2, 5.3→6.3, 5.4→6.4, 5.5→6.5, 5.6→6.6, 5.7→6.7, 5.8→6.8) |
| FR-24–31 Reading/engagement (FL 6.x) | Submission | **FL 7.x** (6.1→7.1, 6.2→7.2, 6.3→7.3, 6.4→7.4, 6.5→7.5, 6.6→7.6, 6.7→7.7, 6.8→7.8) |
| FR-32–36 Discovery (FL 7.x) | Reading/engagement | **FL 8.x** |
| FR-37–44 Community (FL 8.x) | Discovery | **FL 9.x** (8.1→9.1, 8.2→9.2, 8.3→9.3, 8.4→9.4, 8.5→9.5, 8.6→9.6, 8.7→9.7, 8.9→9.9) |
| FR-45–51 Challenges (FL 11.x) | Training | **FL 12.x** (11.1→12.1 … 11.7→12.7) |
| FR-52–53 Library (FL 9.x) | Community | **FL 10.x** (9.1→10.1, 9.5→10.5) |
| FR-54–56 Training (FL 10.x) | Library | **FL 11.x** (10.1→11.1, 10.2→11.2, 10.4→11.4, 10.3→11.3, 10.5→11.5) |
| FR-57 InkPols (FL 12.1–12.3) | Challenges | **FL 13.1–13.3** |
| FR-58 Sponsors (FL 13.1–13.4) | InkPols | **FL 14.1–14.4** |
| FR-59–62 Org pages (FL 14.x) | Sponsors | **FL 15.x** (14.1→15.1, 14.2→15.2, 14.3→15.3, 14.4→15.4, 14.6→15.5) |

Note FR-62 (footer) cites `FL 14.6`; the org-pages epic's footer story is **FL 15.5** (there is no 15.6). So FR-62's `.y` is *also* wrong, not just the epic.

**Severity: CRITICAL.** Traceability is effectively non-functional across the entire FR set. Any downstream agent following an FL link lands on the wrong feature (or nothing).

---

## 2. Omissions / contradictions

The PRD's recent edits (FR-50 rename, FR-12 retitle, FR-63 add) are internally consistent and align with the feature list's intent. But several feature-list stories are silently omitted, and one priority claim contradicts the source.

### 2a. FR-50 placement records vs FL 12.6 — title/scope drift (LOW, content OK)
- FR-50 "Queryable placement records" (P0) now records **1st/2nd/3rd per tier** and powers SM-8. FL **12.6** is titled "Structured winner records" with priority "**P0 (for admin)**" and acceptance "Queryable winner data per tier."
- The PRD has *broadened* FL 12.6 from single-winner to top-3 placements. The feature list still says "winner records" (singular winner). Not a contradiction (PRD is the superset and explicitly notes admin-recording is launch-critical), but the **feature list lags** the PRD on the top-3 placement model. Recommend updating FL 12.6 wording to "placement records (1st–3rd per tier)" to match FR-50 + SM-8.

### 2b. FR-12 set/adjust tier — FL 5.2 still says "promotion" only (LOW)
- FR-12 was retitled to "set/adjust tier in **any direction** (promotion or corrective demotion)". FL **5.2** is still "Staff **promotion** admin UI" / "View tier, **promote**, record reason". The feature list has no notion of demotion. PRD is broader; FL lags. Recommend FL 5.2 reword to "set/adjust tier (promote or demote)".
- Also: FR-12 maps to **two** FL stories (5.2 UI + 5.3 log). FL 5.3 (promotion log) is **P1** in the feature list, but FR-12 folds the auditable log into a single **P0** requirement. See priority note 3a.

### 2c. SM-8 (craft progression) — no contradiction, fully supported
- SM-8 validates FR-48/49/50, which map to FL 12.4/12.5/12.6. All three exist. SM-8 is a PRD-level metric with no feature-list counterpart needed. **No gap.** (Confirms the FR-50 top-3 model is the data source SM-8 relies on — reinforces 2a.)

### 2d. Feature-list stories the PRD omits entirely (MEDIUM)
These FL entries have **no FR** and are not listed in §14 scope or §13 non-goals:
- **FL 1.8 Comment-disable filters** (P1, `ink-core`) — PRD asserts "WP comments disabled site-wide" (FR-24, FR-27, §13) but never carries the *implementing* story as an FR/NFR. Implied, not traced.
- **FL 6.9 Remove legacy edit-link filter** (P1) — drop the old `/plaas-nuwe-publikasie` override when Youzify retired. No FR; only obliquely in addendum §F (Youzify removal). Migration/cleanup step with no PRD home.
- **FL 12.8 Historical challenge migration** (P1) — building `uitdaging` records from challenge-round categories. PRD covers this only in OQ-7 (resolved) and addendum §E, **not** as an MR-x migration requirement. Gap in §10's MR list.
- **FL 9.8 Private messaging** and **FL 9.10 / 9.x removed items** — correctly handled (PRD §14.2 defers messaging; §13/§14.2 note removed widget). **No gap.**

### 2e. FR-63 (deferred auto-renew) ↔ FL 4.8 (MEDIUM — broken/missing cross-ref)
- The PRD repeatedly cites **FR-63** for deferred auto-renew (§4.2 Notes, §13, §14.2, OQ-9), and the feature list has the matching story **FL 4.8 Auto-renew (recurring), P2, deferred**.
- **But FR-63 has no definition in the PRD body.** §4 ends at FR-62; there is no `#### FR-63` heading anywhere. Every reference to FR-63 is dangling. The task description says FR-63 was "added," but it was added only as *cross-references*, not as an actual numbered requirement. Either add the FR-63 block (mapping to **FL 4.8**) or the references are broken.

**Severity: MEDIUM** — FR-63 is cited 4× but undefined; FL 4.8 is its intended source.

---

## 3. Priority (P0/P1/P2) mismatches

| Item | PRD | Feature list | Verdict |
|---|---|---|---|
| Tier promotion **log/history** | FR-12 folds log into **P0** | FL 5.3 (promotion log) = **P1** | **MISMATCH (MEDIUM).** PRD elevates the auditable log to launch-critical; FL has it as at-launch P1. The FR-12 UI half (FL 5.2) is P0 in both. Reconcile: either bump FL 5.3 → P0 or scope FR-12's P0 to the set-tier action with the log at P1. |
| Placement / winner records | FR-50 = **P0** | FL 12.6 = **P0 (for admin)** | Consistent (both P0; PRD clarifies admin-facing recording is the launch-critical part). |
| FR-54 Training hub | **P1** (cites FL 10.1, 10.2) | FL 11.1, 11.2 both **P1** | Consistent once epic ref fixed. |
| FR-56 Editor's shelf + community guides | **P2** (FL 10.3, 10.5) | FL 11.3, 11.5 both **P2** | Consistent. |
| All other mapped FRs | — | — | Priorities match the *correct* (epic+1) FL story in every spot-check. The drift is in the epic number, not the priority. |

No other P0/P1/P2 conflicts found beyond the FL 5.3 log-priority case.

---

## 4. Recommended fixes (priority order)

1. **CRITICAL** — Repair all `(FL x.y)` epic prefixes: FR-1/2/3 → FL 3.1/3.2/3.3; FR-4 onward = current ref **+1 on the epic number** (see §1b table); fix FR-62's `.y` (14.6 → 15.5).
2. **MEDIUM** — Define an actual `#### FR-63` block (deferred auto-renew, P2, FL 4.8), or remove the 4 dangling FR-63 references.
3. **MEDIUM** — Add MR-x / FR coverage (or explicit pointer) for FL 1.8 (comment-disable), FL 6.9 (legacy edit-link removal), FL 12.8 (historical challenge migration).
4. **MEDIUM** — Reconcile FL 5.3 (promotion log) priority P1 vs FR-12's P0 folding.
5. **LOW** — Update feature-list wording: FL 12.6 → top-3 placement records (match FR-50/SM-8); FL 5.2 → set/adjust (promote *or* demote) tier (match retitled FR-12).

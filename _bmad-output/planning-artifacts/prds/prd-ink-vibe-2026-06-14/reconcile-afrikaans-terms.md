# INK PRD — Afrikaans Terminology Reconciliation

*Reconciles `prd.md` + `addendum.md` against the source of truth `docs/afrikaans-terms.md`.*
*Date: 2026-06-15. Scope: recent PRD edits — FR-12 set/adjust tier (corrective demotion), dynamic-count plural rules, payment-failed/cancelled + expiry-reminder messages, "Skryf in" challenge CTA.*

---

## Method

Read all three files in full. Checked: (1) every domain noun in the PRD against the glossary/source of truth; (2) new Afrikaans strings in the PRD that are absent from `afrikaans-terms.md` and should be added; (3) banned-term / synonym drift in both directions.

---

## 1. Domain-term conformance (PRD vs source of truth)

Spot-checked every glossary term used in the PRD. **No banned-term usages and no synonym drift found in the PRD prose.** The PRD is disciplined:

- `bydrae/bydraes`, `gedig`, `storie` (canonical, not legacy `verhaal`), `artikel`, `skryfwerk` (holding bucket, correctly flagged non-user-facing), `biblioteekitem`, `hulpbronartikel`, `uitdaging`, `uitgawe`, `borg/borge` — all match.
- `lid/lede`, `besoeker`, `skrywer`, `leser`, `redakteur`, `lidmaatskap`, `intekening`, `aansluitingsopsie` — all match. The PRD correctly keeps `lidmaatskap` ≠ `intekening` distinct (matches source note).
- `ster gradering` (label) + `ster_gradering` (slug) + `ink_writer_tier` (meta key) — all match the OQ-13/OQ-14 resolutions baked into the source.
- `bevorder/bevordering`, `graderingsgeskiedenis` — match.
- `volg / volg tans`, `volgeling/volgelinge` (never "volger"), `leeslys`, `aktiwiteitsvoer`, `reaksie` (hartjie/duim op/wow), `hooglignering`, `kennisgewing/kennisgewings`, `ledegids`, `Ontdek` — all match.
- `genre`, `vaardigheid`, `uitdagingsrondte` (not `uitdagingsronde`), `inskrywing` — match.

**One naming observation (not drift):** PRD §3 glossary uses **`Gemeenskapsreaksies`** with response types **lof / insig / voorstel**. This concept and its three types are **NOT in `afrikaans-terms.md` at all** — see Gap 1. The source only has the generic `reaksie` and (banned) `kommentaar`. This is a real glossary gap, since `Gemeenskapsreaksie` is the platform's *only* feedback path and is used heavily across FR-27, §6, §13, SM-6, SM-C2.

---

## 2. New Afrikaans strings in the PRD missing from `afrikaans-terms.md`

The source of truth's own maintenance rule (line 257): *a new concept is added here BEFORE it appears in code or UI.* The following strings now live in the PRD but have no home in the guide.

### Gap 1 (HIGH) — `Gemeenskapsreaksie` + lof / insig / voorstel are entirely absent from the guide
The structured-response system is a first-class INK concept (FR-27, the sole feedback path) but does not appear anywhere in `afrikaans-terms.md`. The guide's Deel 1 "Gemeenskap en sosiale interaksie" table only lists `reaksie`, `kommentaar` (banned), `boodskap`, `kennisgewing`. Add:
- **Gemeenskapsreaksie** (`community_response`?) — structured community response; the only feedback path (WP comments disabled).
- **lof** (praise), **insig** (insight), **voorstel** (suggestion) — the three response types.

### Gap 2 (HIGH) — No term for corrective demotion; guide has "bevorder" (promote) only
FR-12 now lets a redakteur **set a tier in any direction** — "promotion (**bevorder**) or **corrective demotion**." The guide's Skrywersvlakke table has only **bevorder** ("Bevorder na 'n hoër vlak"). There is **no Afrikaans term for lowering a tier**, and the PRD uses the bare English phrase "corrective demotion" rather than an approved term. Per the discipline rule this concept must be named in the guide first. Suggested options for the founder/copywriter to pick:
- **verlaag / verlaging** ("na Brons verlaag") — neutral, parallels bevorder/bevordering, recommended.
- **terugstel / regstel** ("regstel" reads as *correct* rather than *demote*, fitting the "corrective" framing).
- A neutral umbrella verb for the bidirectional action itself (FR-12's "set/adjust") — e.g. **stel … vlak** / **pas … aan** / **aanpas** — since the FR is about *setting*, not only demoting.

### Gap 3 (MEDIUM) — Two new status messages not in Deel 3 (Stelsel- en statusboodskappe)
FR-9 and the FR-44 notification table introduce two member-facing strings that are not in the guide's status-message table (which currently covers only active/expired/denied):
- **"Jou betaling het misluk of is gekanselleer…"** — payment-failed/cancelled state (FR-9). No payment-failure message exists in the guide.
- **"Jou intekening verval binnekort"** — expiry reminder notification (FR-44). The guide has expiry-*verval* ("Jou intekening het verval…") but no *pending-expiry* reminder.

Both are load-bearing (the expiry reminder matters specifically because there is no auto-renew at launch). Add to Deel 3.

### Gap 4 (LOW / consistency) — Plural pairs used in PRD; guide lists some but not all
FR-28 mandates locale-correct `_n()` plurals and gives examples: **hartjie/hartjies**, **volgeling/volgelinge**, **inskrywing/inskrywings**.
- `volgeling → volgelinge`: already in the guide (line 131). ✓
- `inskrywing → inskrywings`: noun is in the guide (line 108) but **no plural is recorded**; PRD uses `inskrywings`.
- `hartjie → hartjies`: **the reaction sub-type names (hartjie / duim op / wow) and their plurals are not individually listed**; the guide only has the umbrella `reaksie`. The PRD displays counts as e.g. "342 hartjies". Worth recording the plural for the reaction tokens used in count strings.

Recommend the guide either add a plural column to the affected rows or a short "dynamic counts use `_n()`; n=1 singular / n≠1 plural" note, mirroring FR-28.

---

## 3. "Skryf in" challenge CTA — CONSISTENT (no action needed)

The challenge CTA **"Skryf in"** is **already in the source of truth** — Deel 2 UI-aksietale, line 163: *"inskryf vir 'n uitdaging → Skryf in"*. PRD OQ-10 (line 693) documents the decision and aligns the noun (`inskrywing`) and confirmation ("Jou inskrywing is ontvang", guide line 185). No drift. This one is reconciled.

---

## 4. Reverse check — banned terms / avoid-list

No PRD usage of any Deel 4 avoid-list term in user-facing sense: no "post/story/submit/tier/feed/like/vriend/volger/user/library/training/challenge/issue/sponsor/browse/indien" as a primary UI term. Note "indien" appears only in OQ-10 explicitly flagging that it is *on the avoid-list* (correct). "tier" appears only in English meta/architecture discussion (`ink_writer_tier`, "writer tier") — acceptable per guide (code/English meta key is load-bearing, OQ-14), never as a UI label.

---

## Summary of recommended additions to `docs/afrikaans-terms.md`

| # | Add to guide | Section |
|---|---|---|
| 1 | `Gemeenskapsreaksie` + types **lof / insig / voorstel** | Deel 1 — Gemeenskap |
| 2 | A demotion term (rec. **verlaag/verlaging**) + a bidirectional "set tier" verb | Deel 1 — Skrywersvlakke |
| 3 | **"Jou betaling het misluk of is gekanselleer…"** and **"Jou intekening verval binnekort"** | Deel 3 — Statusboodskappe |
| 4 | Plurals for `inskrywing → inskrywings` and reaction tokens (`hartjie → hartjies`); optional `_n()` note | Deel 1 / Deel 3 |

All four are *additions to the source of truth*, not corrections to the PRD — the PRD prose itself is term-clean. Gaps 1 and 2 are the priority: both are first-class concepts (sole feedback path; bidirectional tier control) with no approved Afrikaans term in the guide, violating the "add to guide before code/UI" rule.

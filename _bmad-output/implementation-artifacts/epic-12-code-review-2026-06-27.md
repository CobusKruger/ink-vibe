# Epic 12 — Code Review (Challenges / Uitdagings & winners)

**Date:** 2026-06-27
**Scope:** `main..epic-12-uitdagings` — all 8 stories (12.1–12.8). New `Ink\Challenges` module (SinglePage, Archive, Deadline, Cadence, Entry, Pools, Placements, PromotionHistory, Migration) + `Submission\ChallengeLinking`, `Content\FieldSets`, `Tiers\Api`, theme templates/patterns, `I18n\Terms`, deptrac.
**Method:** 3-layer adversarial review — Blind Hunter (diff-only), Edge Case Hunter (diff + project read), Acceptance Auditor (diff vs epic ACs).

## Outcome

Acceptance Auditor: **all 8 stories' core ACs met**, plus both deferred-from-Epic-2 items closed (12.3 `MANAGE_CHALLENGES` REST/meta-box reconciliation; 12.4 AD-5 taxonomy-attachment confirmation). Conflation rule (Gradering ⟂ lidmaatskap) preserved throughout — the only new cross-module edge is read-only `Challenges → Tiers` (Api facade, mirrors Discovery→Tiers).

5 patches applied (R12); 5 items deferred; remainder dismissed as by-design/noise.

## Patches applied (R12)

- **P1 (High) — `Migration --force` duplicated every round.** `createUitdaging` was a blind `wp_insert_post`; a `--force` re-run inserted a second uitdaging (+ new slug/term), orphaning the first. Fixed: get-or-create via a `ink_uitdaging_source_category` marker (`ensureUitdaging`/`findUitdagingForCategory`) so `--force` reconciles. Also skip empty-name categories (would create an untitled published uitdaging).
- **P2 (Medium) — `Migration::legacyCategories()` default migrated EVERY category.** A naive run would convert ordinary blog categories (Nuus/Uncategorized) into published uitdagings and mis-tag their posts. Changed the default to an empty list (explicit site override required); an un-overridden run is now a deliberate no-op.
- **P3 (Medium) — single-page entries query was unbounded (`posts_per_page => -1`).** Request-rendered, so a busy round loaded every entry per view. Bounded to `SinglePage::MAX_ENTRIES = 500` (covers a real round; `Pools::forRound` inherits the bound).
- **P4 (Low) — `Placements::arrange` tie order undefined + duplicate ranks surfaced.** Now sorts by rank then entry id (deterministic) and defensively collapses to one entry per rank per pool (lowest id wins) — dirty ingestion can't show two "algehele wenners".
- **P5 (Low) — `PromotionHistory` only on `edit_user_profile`.** Added `show_user_profile` so a `MANAGE_TIERS` holder also sees the audit trail on their own profile (gate keeps it invisible to writers).

## Deferred (see deferred-work.md → "code review of Epic 12")

- Placement rank-uniqueness + win→auto-promotion = 12A.3 ingestion / 5.8 responsibility (consumers not yet built; read-side guarded by P4).
- Entry-cap concurrency race (non-atomic count→write; low-probability; needs lock/reconcile).
- Meester entries silently excluded from pools (by design; needs a submission-time UX signal).
- Deadline time-of-day discarded by the inclusive end-of-day-SAST boundary (product decision: `date`-only field vs honour the time).
- `Deadline::parse` accepts rolling/invalid + far-future dates (UI-guarded; harden only if non-UI writes appear).

## Dismissed (by design / not defects)

- Cap counts drafts/pending toward the per-type limit — intended (the cap is per content type, any status).
- Submission carries no `Ink\Tiers` reference — verified intentional (the conflation invariant; the snapshot is hook-decoupled into Challenges).
- Output escaping, nonce/capability gating, SQL-injection, date/timezone boundary math — all clean (the SAST math was scrutinised hardest and is correct; `Africa/Johannesburg` has no DST).
- 12.6 "feeds R2/R3/SM-8" is a documented forward dependency — the queryable store exists; consumers are 12A.3/5.8.

## Gates (post-patch)

`composer test` → 812 passed / 2 skipped; `composer cs` → 0 errors (pre-existing slow-query warnings only); `composer stan` → No errors; `composer deptrac` → 3 PRE-EXISTING `Kernel\Activation → Content\PostTypes` violations only, no new edge.

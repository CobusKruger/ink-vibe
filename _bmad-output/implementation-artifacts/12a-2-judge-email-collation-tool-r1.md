---
baseline_commit: 9e4489bcb97f95aa5020c51e69692a6eb77b5e82
---

# Story 12A.2: Judge-email collation tool (R1)

Status: done

<!-- Note: Validation is optional. Run validate-create-story for quality check before dev-story. -->

## Story

As a redakteur,
I want to auto-collate an uitdaging round into an anonymized judge email,
so that I stop assembling it by hand. (FR-50-R1, R1)

## Acceptance Criteria

(epics.md 12A.2 admin-flow ACs — wp-admin chrome, Afrikaans ink-core labels.)

1. Lives under the **Uitdagings** admin menu; the editor selects an uitdaging from a list in **descending date order**.
2. On collate, the system gathers all linked entries, **assigns the per-type EntryID** (each type from 1, via 12A.1 `EntryId::assign`), sorted by **entry type → Gradering (Brons, Silwer, Goud) → EntryID**, and **persists** the EntryID.
3. Generates an **editable preview**: the **full challenge body** first, then entries ordered by type → Gradering → EntryID; each entry shows **EntryID + title (one heading line)**, a blank line, then the **full entry text**; the **writer's name and any copyright notice are stripped**.
4. The editor can **edit the preview inline** before sending (a textarea seeded with the generated body).
5. The editor enters one or more **recipient email addresses** and **sends** (validated, via `wp_mail`).
6. **States:** *no entries linked* → empty state, no send; *re-collation of an already-numbered round* → **idempotent** (never renumber/burn EntryIDs, continue the per-type sequence for new entries only); *send success/failure* → clear status.

Cross-cutting (Epic-12 retro readiness flag #4): **Meester entries are non-competing** — Meester is manual-only/terminal and forms no pool (`Pools::competingTiers` excludes it). Collation **excludes Meester entries from the numbered judge pools** and reports them separately as nie-mededingend, realizing the "a Meester entry won't be pool-judged" signal at the adjudication layer. (The submission-time signal touches the Submission module and is noted as a follow-up.)

## Tasks / Subtasks

- [x] Task 1: `Challenges\Collation` — pure adjudication logic (AC: 2,3,5,6)
  - [x] `typeOrder()` = `PostTypes::readableTypes()` (gedig, storie, artikel); `gradingOrder()` = `Pools::competingTiers()` values (brons, silwer, goud)
  - [x] `sortForAssignment(array $entries): list` — stable sort by (type index → gradering index → id); excludes non-competing (Meester / empty) entries
  - [x] `computeAssignments(array $sorted, array $existing): array<int,int>` — per-type sequential numbers from 1, **continuing past the per-type max existing number** so already-numbered entries keep their number (idempotent re-collation); pure
  - [x] `stripIdentity(string $content, string $author_name): string` — remove the author's name occurrences + any copyright-notice line (©/(c)/copyright/kopiereg); pure
  - [x] `buildPreviewBody(string $challengeBody, list $entries): string` — challenge body, then per entry `"{EntryID}: {title}\n\n{stripped text}"`; pure
  - [x] `parseRecipients(string $raw): list<string>` — split on comma/newline, `sanitize_email` + `is_email`, dedupe; pure-ish (WP fns mockable)
- [x] Task 2: `Challenges\Collation::assignRound(array $assignments, array $types): int` — impure: call `EntryId::assign(id, type, number)` per entry; returns count newly assigned (first-wins, so re-collation writes 0)
- [x] Task 3: `Challenges\CollationPage` — admin submenu shell (AC: 1,4,5,6)
  - [x] `add_submenu_page('edit.php?post_type=uitdaging', …)` gated on `Capabilities::MANAGE_CHALLENGES`; Afrikaans labels via `Terms`/literals
  - [x] uitdaging dropdown (published, `orderby=date DESC`); collate + send actions, each nonce-verified + capability-gated + `is_scalar`/`wp_unslash`/sanitize per the `AdminProfile`/`FieldSets` pattern
  - [x] collate seam `entriesFor(int $uitdaging_id): list` (protected, overridable) returns `{id,type,gradering,title,content,author_name}`; render the editable preview textarea + recipient field; empty state when no entries
  - [x] send: validate recipients, `wp_mail` the editor-edited body, surface success/failure
  - [x] wired in `Module::register()`
- [x] Task 4: Tests — `tests/Unit/Challenges/CollationTest.php`
  - [x] sortForAssignment orders by type→gradering→id, drops Meester/empty (non-vacuous: include a Meester entry and assert it's absent)
  - [x] computeAssignments numbers per type from 1; re-collation keeps existing numbers and continues the sequence (idempotent)
  - [x] stripIdentity removes the author name + copyright line, keeps the work (non-vacuous: prove the name WAS present)
  - [x] buildPreviewBody composes body + per-entry blocks in order
  - [x] parseRecipients keeps valid, drops invalid, dedupes
  - [x] assignRound calls EntryId::assign per entry (outcome: the meta written)
- [x] Task 5: Gates — test:unit / cs / stan / deptrac / copy:scan green; deptrac edges: Challenges → Content (PostTypes, already allowed) + Tiers (Pools→Tier via competingTiers, already allowed); **no Notifications edge** (judge email is editor-composed ad-hoc `wp_mail`, not a member form-letter template). New Afrikaans admin labels routed via `Terms`/placeholders per the unauthored-copy workflow if not yet authored.

## Dev Notes

- **EntryID assignment is deferred to collation** (12A.1 AC): `EntryId::assign` is first-wins, so re-collation never renumbers/burns. `computeAssignments` continues each type's sequence past the existing max — so a late entry added before deadline gets the next number, existing entries keep theirs. [Source: src/Challenges/EntryId.php; epics.md#12A.2 AC-6]
- **Sort order is type → Gradering → EntryID.** Type order = `PostTypes::readableTypes()` (gedig, storie, artikel); Gradering order = `Pools::competingTiers()` (brons, silwer, goud). The EntryID is assigned IN this order, so "→ EntryID" is the within-(type,grade) id tiebreak. [Source: src/Content/PostTypes.php:105; src/Challenges/Pools.php:43]
- **Meester is non-competing** (readiness flag #4). `Pools::competingTiers()` already excludes the manual-only Meester; collation drops Meester/empty-snapshot entries from the numbered pools and surfaces them separately. This is the adjudication-layer realization of "a Meester entry won't be pool-judged". [Source: src/Challenges/Pools.php:43; epic-12-retro §5 readiness flag 4]
- **Anonymisation:** judges must not see the writer's identity (FR-50-R1). `stripIdentity` removes the author's display-name occurrences and copyright-notice lines (©, "(c)", "copyright", "kopiereg") from both the heading and body positions. Heuristic + conservative (it removes known identity tokens, never rewrites the work). [Source: epics.md#12A.2 AC-3]
- **The judge email is editor-composed, ad-hoc `wp_mail`** — NOT a member-facing Notifications form-letter (those are the templated transactional emails: 4.8/5.10/9.11/12A.4). So 12A.2 takes **no Notifications dependency**; 12A.4's winners post is where the `Challenges → Notifications` template edge lands. [Source: src/Notifications/Template.php class docblock]
- **Admin pattern (sanctioned $_POST):** nonce → `current_user_can(MANAGE_CHALLENGES)` → `is_scalar` guard → `wp_unslash` + sanitize. Mirror `Tiers\AdminProfile::save()` / `Content\FieldSets::save()`. Output escaped (`esc_html`/`esc_attr`/`esc_textarea`). [Source: src/Tiers/AdminProfile.php:165; src/Content/FieldSets.php:186]
- **Testability:** all real logic is in pure `Collation` statics; the admin shell stays thin with a protected `entriesFor()` seam (the Brain-Monkey protected-seam isolation rule). Test the OUTCOME (the ordering/numbering WE compute, the meta `assignRound` writes), never a mock-of-a-mock. [Source: project-context.md Testing Rules]

### Project Structure Notes

- New: `src/Challenges/Collation.php`, `src/Challenges/CollationPage.php`, `tests/Unit/Challenges/CollationTest.php`.
- Modified: `src/Challenges/Module.php` (register `CollationPage`).
- deptrac: Challenges → Content + Tiers (both already allowed). No new edge.

### References

- [Source: _bmad-output/planning-artifacts/epics.md#Story 12A.2 + #12A.2 admin-flow AC]
- [Source: src/Challenges/EntryId.php (12A.1), Pools.php (12.5), Content/PostTypes.php]
- [Source: src/Tiers/AdminProfile.php, src/Content/FieldSets.php (admin $_POST pattern)]
- [Source: epic-12-retro §5 (Meester non-competing readiness flag)]

## Dev Agent Record

### Agent Model Used

claude-opus-4-8

### Debug Log References

- cs first failed on docblock `@param` alignment (auto-fixable) — `composer cs:fix` resolved; 0 errors after (the 2 remaining warnings are pre-existing in untouched Engagement files).

### Completion Notes List

- `Challenges\Collation` (pure) holds all the adjudication logic: `sortForAssignment` (type→Gradering→id, drops non-competing Meester/empty), `computeAssignments` (per-type from 1, idempotent re-collation continuing the sequence), `stripIdentity` (author name + ©/copyright/kopiereg lines), `buildPreviewBody`, `parseRecipients`, and the `collate()` aggregator. `assignRound()` is the only side effect (delegates to `EntryId::assign`, first-wins).
- `Challenges\CollationPage` is the thin admin submenu under the Uitdagings menu (MANAGE_CHALLENGES-gated, nonce + is_scalar→wp_unslash→sanitize per AdminProfile/FieldSets, escaped output). `collateRound()` is the testable integration point over the `entriesFor()`/`challengeBodyFor()`/`uitdagings()` protected seams. The judge email is sent via ad-hoc `wp_mail` (editor-composed) — **no Notifications dependency**.
- **Meester non-competing (readiness flag #4) realized at the adjudication layer:** Meester (and empty-snapshot) entries are excluded from the numbered judge pools; an empty round shows the "geen mededingende inskrywings" state. The submission-time Meester signal (Submission module) remains a noted follow-up.
- New Afrikaans admin labels authored as `__()` literals (ink-core admin-chrome convention — no English `.mo`): "Beoordelaar-e-pos", "Stel saam", "Stuur", "Voorskou (redigeerbaar)", "Kies", recipient/notice strings. Standard Afrikaans, glossary-consistent; copy:scan clean.
- Gates: `composer test:unit` 1133→1142 (+9), 1 skipped; `cs` 0 errors; `stan` OK; `deptrac` 3 pre-existing Kernel→Content only (Challenges→Content + Challenges→Tiers already allowed; no new edge — Ink\I18n uncovered, no Notifications/Library edge); `copy:scan` clean.

### File List

- `wp-content/plugins/ink-core/src/Challenges/Collation.php` (new)
- `wp-content/plugins/ink-core/src/Challenges/CollationPage.php` (new)
- `wp-content/plugins/ink-core/src/Challenges/Module.php` (modified — register CollationPage)
- `tests/Unit/Challenges/CollationTest.php` (new)

### Change Log

- 2026-06-29 — Story 12A.2 implemented: judge-email collation tool (pure Collation logic + thin CollationPage admin shell). Per-type EntryID assignment at collation, anonymized editable preview, ad-hoc wp_mail send. 9 unit tests. Suite 1133→1142.

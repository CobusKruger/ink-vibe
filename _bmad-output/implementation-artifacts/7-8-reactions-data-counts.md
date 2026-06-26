---
baseline_commit: 94103df
---

# Story 7.8: Reactions data + counts

Status: done

### Review Findings

- [x] [Review][Defer] `duim op` (invariant) / `wow`→`wows` count plurals are natural forms of the authored reaction terms but only `hartjie`/`hartjies` is glossary-explicit — copy-debt to ratify the forms. See deferred-work.md.

## Story

As a reader,
I want to see reaction counts,
so that resonance is visible without vanity framing. (FR-28)

## Acceptance Criteria

**Given** reaction data
**When** counts render
**Then** they show verb-less (e.g. "342 hartjies") with locale-correct `_n()` plurals (1 hartjie / 342 hartjies; n=0 handled) on every count surface.

1. A work's reaction totals are aggregated from the 7.3 line-reaction store (across all its lines), per reaction type.
2. Counts render **verb-less** ("342 hartjies", not "342 mense het gehou"), via WordPress `_n()` so the **singular/plural** is locale-correct: "1 hartjie" / "342 hartjies"; **n=0 is handled** (renders the plural form, "0 hartjies"). The hartjie singular/plural is the glossary form (afrikaans-terms.md line 156; ui-copy 681/724).
3. The count formatting is **single-source** (`Ink\Engagement\ReactionCounts`) so every count surface formats identically — no inlined count strings.
4. A count surface renders on the reading page (the work's resonance totals). Three-layer: data + formatting in `ink-core` (Engagement); a server block (AD-7) for the surface; theme owns presentation. Not entitlement-gated; conflation-clean.

## Tasks / Subtasks

- [x] Task 1: `ReactionStore::countsForPost` (AC: #1)
  - [x] `countsForPost(int $post_id): array<string,int>` — `SELECT reaction, COUNT(*) … GROUP BY reaction` (prepared); normalise to all three `Reaction::values()` (0 default). Mirror the existing `$wpdb` phpcs/caching conventions.
- [x] Task 2: `Ink\Engagement\ReactionCounts` single-source formatter (AC: #2, #3)
  - [x] `label(Reaction $r, int $n): string` — verb-less, `_n()` per reaction: Hartjie → `_n('%d hartjie','%d hartjies',$n,'ink-core')` (glossary form); DuimOp → `_n('%d duim op','%d duim op',$n,'ink-core')` (invariant count phrase); Wow → `_n('%d wow','%d wows',$n,'ink-core')`. `sprintf` the `$n`. n=0 → plural form. (Copy-debt: only the hartjie form is glossary-explicit; ratify the duim op / wow count forms on the next glossary pass.)
- [x] Task 3: `Ink\Engagement\ReactionTotals` server block (AC: #4)
  - [x] `ink/reaksie-tellers` block: `render()` → `countsForPost(get_the_ID())` → `toHtml`. Pure `toHtml(array<string,int> $counts): string` — a verb-less totals line using `ReactionCounts::label` for each reaction (icon + count), no vanity verb.
  - [x] Embed `<!-- wp:ink/reaksie-tellers /-->` in the three reading patterns (header area, near the byline/save toggle).
  - [x] `Engagement\Module::register()` registers the block. `.ink-reaksie-tellers*` CSS in `theme.json` (tokens).
- [x] Task 4: Tests + gates (AC: all)
  - [x] Extend `ReactionStoreTest`: `countsForPost` GROUP-BY query + normalisation to all three reactions (a reaction with no rows → 0) — non-vacuous (assert the prepared SQL + the normalised map).
  - [x] `tests/Unit/Engagement/ReactionCountsTest.php` (mock `_n`/`sprintf`): asserts the singular form requested at n=1, the plural form at n=342 AND **at n=0** (n=0 uses plural — "0 hartjies"), verb-less (no verb token in the format strings), per reaction.
  - [x] `tests/Unit/Engagement/ReactionTotalsTest.php`: `toHtml` renders a count for each reaction via the single-source formatter; non-vacuous.
  - [x] Extend `ReadingTemplatesTest`: each reading pattern embeds `wp:ink/reaksie-tellers`.
  - [x] `composer test:unit` green, `cs`/`stan` clean, `copy:scan` no new debt, `deptrac` clean (Engagement → [Kernel, Content]).

## Dev Notes

- **Builds on 7.3** [Source: src/Engagement/ReactionStore.php]: add `countsForPost` next to `forPost`/`userReaction`. Aggregate ALL line reactions for the work (the resonance metric is per-work, ui-copy 681: "hartjies" next to ♥).
- **Verb-less + `_n()`** [Source: afrikaans-terms.md:156; ui-copy-translations.md:681,724; project-context.md i18n]: "Telwoord gebruik enkel-/meervoud: '1 hartjie' / '342 hartjies'", "geen werkwoord benodig". `_n()` is the correct WP plural mechanism (precedent: ui-copy 553 renewal-button `_n()`). The hartjie forms are glossary-authored; the duim op (invariant) / wow forms are the natural count forms of the authored reaction terms — flagged as copy-debt to ratify, NOT invented marketing prose.
- **Single-source format** [project-context.md #never duplicate]: all count surfaces call `ReactionCounts::label` — never an inlined `_n()` per surface. This is the FR-28 "every count surface" guarantee.
- **n=0** [AC]: handled by `_n()` returning the plural ("0 hartjies"); assert it.
- **Block pattern** [Source: prior Engagement blocks]: pure `toHtml` + thin `render`; Engagement → Kernel (Reaction enum) + own store; no new deptrac edge.
- **Testing**: mock `_n` to echo its args so the test can assert which singular/plural pair + count were used; `sprintf` real or mocked. countsForPost via Mockery `$wpdb` (GROUP BY → rows → normalised map).

### Project Structure Notes

- New ink-core: `src/Engagement/ReactionCounts.php`, `src/Engagement/ReactionTotals.php`; MOD `src/Engagement/ReactionStore.php` (countsForPost), `src/Engagement/Module.php`.
- New theme: MOD `patterns/reading-{storie,artikel,gedig}.php` (embed), `theme.json` (styles).
- New tests: `ReactionCountsTest`, `ReactionTotalsTest`; MOD `ReactionStoreTest`, `ReadingTemplatesTest`.
- deptrac: Engagement → [Kernel, Content] (already); no Entitlement/Tiers edge.

### References

- [Source: _bmad-output/planning-artifacts/epics.md#Story 7.8]
- [Source: docs/afrikaans-terms.md:156; docs/ui-copy-translations.md:681,724,553]
- [Source: wp-content/plugins/ink-core/src/Engagement/ReactionStore.php; src/Kernel/Reaction.php]
- [Source: _bmad-output/project-context.md#afrikaans-first, #never-duplicate, #conflation-rule]

## Dev Agent Record

### Agent Model Used

claude-opus-4-8 (BMAD dev-story loop, Epic 7)

### Debug Log References

- stan run outside the sandbox (parallel-worker TCP bind). No code issues.

### Completion Notes List

- `ReactionStore::countsForPost` aggregates a work's line reactions per type (GROUP BY), normalised to all three reactions (a reaction with no rows → 0) so count surfaces always have a complete ordered map.
- `Ink\Engagement\ReactionCounts::label` is the SINGLE-SOURCE verb-less, `_n()`-correct formatter ("1 hartjie" / "342 hartjies"; n=0 → plural "0 hartjies") — every count surface formats identically (FR-28). hartjie/hartjies is the glossary form (afrikaans-terms 156); duim op is the invariant count phrase, wow→wows the natural plural — flagged as copy-debt to ratify (not invented marketing prose).
- `ink/reaksie-tellers` server block renders the work's verb-less resonance totals (icon does the verb — no vanity framing), embedded in all three reading headers.
- Tests assert the actual rendered strings (with `_n` aliased to the real plural rule), incl. the n=0→plural case and a verb-less guard (no "het"/"gehou"/"stem"/"like").
- Engagement → [Kernel, Content] unchanged (uses Reaction enum + own store); conflation-clean.
- Tests 483→492 (+9); cs/stan clean; copy:scan no new debt; deptrac 0 new violations.

### File List

- `wp-content/plugins/ink-core/src/Engagement/ReactionStore.php` (MOD — countsForPost)
- `wp-content/plugins/ink-core/src/Engagement/ReactionCounts.php` (NEW — single-source `_n()` formatter)
- `wp-content/plugins/ink-core/src/Engagement/ReactionTotals.php` (NEW — ink/reaksie-tellers block)
- `wp-content/plugins/ink-core/src/Engagement/Module.php` (MOD — wire ReactionTotals)
- `wp-content/themes/ink-foundation/patterns/reading-storie.php` (MOD — embed totals)
- `wp-content/themes/ink-foundation/patterns/reading-artikel.php` (MOD — embed totals)
- `wp-content/themes/ink-foundation/patterns/reading-gedig.php` (MOD — embed totals)
- `wp-content/themes/ink-foundation/theme.json` (MOD — `.ink-reaksie-tellers` styles)
- `tests/Unit/Engagement/ReactionCountsTest.php` (NEW)
- `tests/Unit/Engagement/ReactionTotalsTest.php` (NEW)
- `tests/Unit/Engagement/ReactionStoreTest.php` (MOD — countsForPost)
- `tests/Unit/Engagement/ReadingTemplatesTest.php` (MOD — totals embed guard)
- `_bmad-output/implementation-artifacts/7-8-reactions-data-counts.md` (NEW — this story)

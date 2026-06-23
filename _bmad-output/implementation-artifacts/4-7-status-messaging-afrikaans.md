---
baseline_commit: 28ccaeff7e4c112cb2b7e0fcb8807d1f064bd4d7
---

# Story 4.7: Status messaging (Afrikaans)

Status: review

<!-- Note: Validation is optional. Run validate-create-story for quality check before dev-story. -->

## Story

As a lid,
I want Afrikaans status messages for my lidmaatskap,
so that I always understand my access state. (FR-9)

## Acceptance Criteria

1. **The four lidmaatskap access states each resolve to their lid-family Afrikaans status message via the single-source terminology registry — never an inline bare literal.** Given the four states **active / expired / access-denied / payment-failed**, when a message is requested for a state, then it returns the human-authored lid-family Afrikaans copy from the `ink-core` terminology registry (`Ink\I18n\Terms`, AD-10 / Story 2.0) — e.g. active → "Jou lidmaatskap is aktief. Jy kan nou werk plaas.", expired → "Jou lidmaatskap het verval. Hernu om werk te plaas.", payment-failed → "Jou betaling het misluk of is gekanselleer." — and no consumer (template, gate, or class) inlines the message text. _[Source: epics.md#Story-4.7 AC "Given active / expired / access-denied / payment-failed states … it uses the lid-family Afrikaans copy ('Jou lidmaatskap is aktief…', 'Jou lidmaatskap het verval…', 'Jou betaling het misluk of is gekanselleer…')"; afrikaans-terms.md Deel 3 lines 209/210/212/215 (the curated status messages); project-context.md "Controlled-vocabulary UI labels come from the ink-core terminology registry … never inline a glossary label as a bare literal"; architecture.md AD-10 (terminology label registry, glossary-backed `__()`, never inline literal)]_

2. **The status copy is human-authored / approved — sourced from the glossary, never AI-translated.** Given the curated status copy ALREADY exists in `docs/afrikaans-terms.md` Deel 3 ("Stelsel- en statusboodskappe") and is mirrored in `docs/ui-copy-translations.md`, when the registry is seeded, then the registry literals are copied verbatim from that approved source (the AC's example strings ARE these glossary strings — approved seeds). No new Afrikaans wording is invented or AI-translated; where any state's exact final copy were NOT yet authored, the key would be registered with a clearly-marked `[NEEDS HUMAN AFRIKAANS]` placeholder and flagged in `ui-copy-translations.md` — but for 4.7 all four states map to already-approved glossary copy, so there are **no** placeholders. _[Source: project-context.md "No AI-generated Afrikaans — human-authored only", "afrikaans-terms.md is the glossary source of truth — a new concept is added to the glossary BEFORE it appears in code/UI"; afrikaans-terms.md Deel 3 (curated copy present); ui-copy-translations.md line ~544 ([NEEDS HUMAN AFRIKAANS] / "moenie KI-vertaal nie" precedent); 3-6-...-r6.md (the `[NEEDS HUMAN AFRIKAANS]` / `[WAG OP MENSLIKE KOPIE]` discipline)]_

3. **A state→message resolver maps each membership state to its registered Afrikaans message, modelled as a closed `enum` (the fixed-value-set rule), conflation-clean.** Given the four states, when the resolver is asked for a state's message, then a closed `Ink\Entitlement\MembershipStatus` enum (active / expired / access-denied / payment-failed) maps each case to its terminology-registry **message key**, and a resolver returns the Afrikaans message via `Terms::label()` — active→the aktief message, expired→the verval message, access-denied→the denial message, payment-failed→the betaling-misluk message. The enum + resolver carry **zero** reference to `Ink\Tiers` / `ink_writer_tier` / writer Gradering (THE conflation rule); status messaging is a lidmaatskap-state concept only. _[Source: epics.md#Story-4.7; project-context.md "Model fixed value sets as enums in ink-core … never duplicate these literals", THE conflation rule (`Ink\Tiers` ⟂ `Ink\Entitlement`); epics.md line 24 (conflation rule); architecture.md AD-1 (Entitlement ⟂ Tiers, Deptrac-enforced); LidmaatskapTerm.php (the in-module enum→registry-key precedent)]_

4. **The status messages + resolution are `ink-core` business logic exposed through the Entitlement `Api` facade; rendering is theme/later-story — 4.7 wires only what genuinely needs it now.** Given three-layer separation, when 4.7 lands, then the status-message strings live in `Ink\I18n\Terms`, the state→message resolution lives in `Ink\Entitlement` (a `StatusMessages` resolver + the `MembershipStatus` enum), and they are reachable through the existing `Ink\Entitlement\Api` facade (the sole cross-module surface, AD-1) via a clean `Api::statusMessage( MembershipStatus )` method (plus `Api::statusMessageFor( string )` keyed by the WC status string where useful). No new consumer is wired into a render path now: the entitlement-DENIED enforcement point is **Story 6.8** (`Ink\Submission`, which does not exist yet) and the lidmaatskap/My-Profiel status SURFACE is **Story 9.4** — both consume this API later; 4.7 documents that boundary and adds no template/`functions.php` logic. _[Source: project-context.md three-layer ("No business logic in the theme"); architecture.md AD-1 (module = dir + bootstrap + facade; Api is the only cross-module surface); epics.md#Story-6.8 (FR-19 publish gate — the denial-copy consumer), #Story-9.4 (My Profiel / Skrywerprofiel — the status-surface consumer); SubmissionGate.php docblock ("The full Afrikaans 'jou lidmaatskap het verval' status copy is Story 4.7"); PlanPresenter.php / Api.php (the read-model + facade precedent)]_

5. **Every new `.php` follows the house rules; authored AND passing Pest tests; zero regressions.** Given the project conventions, when 4.7 is built, then each new `ink-core` file is `<?php` + one `declare(strict_types=1)` + the correct `Ink\…` namespace + `defined('ABSPATH')||exit;` + no closing `?>`, PascalCase/camelCase, `ink_`-prefixed single-source consts where applicable, no raw superglobals, escape-on-output at any render point (none here — rendering is deferred). Pest unit tests are authored under `tests/Unit/` and **run with `composer test:unit`, and the full suite passes before the story is marked done** (baseline 235 passed / 1 skipped → zero regressions). `composer cs` (touched files), `composer stan`, and `composer deptrac` (confirming no `Entitlement → Tiers` edge) are run and recorded. _[Source: project-context.md language rules (strict types, `Ink\` namespace, `ABSPATH` guard, `ink_` prefix, escape-on-output, no raw superglobals), testing rule 2026-06-22 (author *and run* Pest; suite passes before done); architecture.md AD-1/AD-8 (Deptrac conflation enforcement); deptrac.yaml (Entitlement allowlist = Kernel + Notifications, no Tiers)]_

## Tasks / Subtasks

> **Current state (read before starting):**
> - **The terminology registry is LIVE** (`Ink\I18n\Terms`, Story 2.0): a `private static function map()` of `key => __( '<Afrikaans>', 'ink-core' )` literals, with `label( $key )` (fail-safe: returns the key + a `WP_DEBUG` notice for an unknown key), `has( $key )`, and `all()` (the leak-scan inspection surface). **Add the four status-message keys to `map()`** — this is the single-source surface; do NOT create a parallel registry (the prompt allows a sibling, but `Terms` already governs lid-family controlled vocabulary, so extend it — fewer moving parts, the leak scan already inspects `Terms::all()`). Keys are extracted by `wp i18n make-pot` ONLY because they are literal `__()` calls — never wrap `__()` around a variable.
> - **The glossary ALREADY HOLDS the approved copy.** `docs/afrikaans-terms.md` **Deel 3** ("Stelsel- en statusboodskappe", lines ~201-215) is the human source of truth and already contains: line 209 "Jou lidmaatskap is aktief. Jy kan nou werk plaas." (active), line 210 "Jou lidmaatskap het verval. Hernu om werk te plaas." (expired), line 212 "Jou betaling het misluk of is gekanselleer." (payment-failed), line 215 "Slegs betaalde lede kan werk plaas. Sien aansluitingsopsies." (access-denied — betaalde-lidmaatskap-needed). **Glossary-first is already satisfied** — these are NOT new concepts. The story PROJECTS them into the registry verbatim; it does not author Afrikaans. (There is also line 214 "Jy moet aangemeld wees om te reageer." — the NOT-logged-in denial — and line 211 the expiry-soon reminder; those are NOT among the four 4.7 states (211 is the 4.8/9.9 reminder; 214 is an engagement-not-logged-in case), so 4.7 registers the four 4.7-scoped messages and may register the not-logged-in denial only if the resolver needs it — keep scope tight.)
> - **The Entitlement module exists and is wired** (`Ink\Entitlement`, Epic 4): `Module.php` (bootstrap → wires `PurchaseActivation` + `StorefrontSuppression`), `Api.php` (the sole cross-module facade — `can_submit()`, `planRows()`, `renewalRows()`, plan registry), `SubmissionGate.php` (4.3 `canSubmit()` — returns a bool; its docblock explicitly DEFERS "The full Afrikaans 'jou lidmaatskap het verval' status copy is Story 4.7"), `PurchaseActivation.php` (4.2 — its docblock defers "the Afrikaans status-messaging surface (4.7)"), `LidmaatskapTerm.php` (**the in-module `enum` → `Terms::label( $this->termKey() )` precedent — mirror it for `MembershipStatus`**), `PlanPresenter.php` / `MembershipPlans.php`. **Add a `StatusMessages` resolver + a `MembershipStatus` enum**; expose via `Api`. No new bootstrap/`addModule` edit (the resolver is a pure on-demand read, like the gate — it registers NO hook).
> - **Deptrac:** `Entitlement`'s allowlist is `Kernel` + `Notifications` (4.2). The status work uses `Ink\I18n\Terms` — note: `Terms` is in the `Ink\I18n\*` namespace, which is **not a declared Deptrac layer** (only `Ink\Kernel`, `Ink\Content`, `Ink\Entitlement`, … are layers). An un-layered class is uncollected, so a dependency on it raises no rule violation (the existing `LidmaatskapTerm` already depends on `Terms` with deptrac green). Confirm `deptrac analyse` stays green and that **no `Entitlement → Tiers` edge appears** — that prohibition is permanent.
> - **Pest runs now** (`composer test:unit`; `tests/bootstrap.php` + Brain Monkey; `__()` stubbed as identity passthrough so the Afrikaans SOURCE literal is what `label()` returns). Baseline **235 passed / 1 skipped**. Author tests under `tests/Unit/I18n/` (registry keys) and `tests/Unit/Entitlement/` (the enum + resolver) and **run them**.
>
> **Scope is the FOUR lid-family status MESSAGES + the state→message resolver API ONLY.** Do **NOT** build: the publish-flow enforcement WIRING that surfaces the denial (Story 6.8, `Ink\Submission` — does not exist yet); the My Profiel / Skrywerprofiel status SURFACE that renders "aktief"/"verval" (Story 9.4); the lifecycle EMAIL copy + expiry warnings (Story 4.8 — those route through the Notifications form-letter store, not this registry); any theme template / pattern / `functions.php` render logic; any `Ink\Tiers` / Gradering coupling; any change to `SubmissionGate::canSubmit()`'s bool contract (it stays a pure bool; the MESSAGE is resolved separately by the consumer at the enforcement point).

- [x] **Task 1 — Glossary-first gate (AC: 1, 2)**
  - [x] Confirm the four status messages already exist in `docs/afrikaans-terms.md` Deel 3 (they do — lines 209/210/212/215). **No new Afrikaans is authored.** Add a short note in Deel 3 (or a projection sub-note) recording that these four situations are projected into the `ink-core` terminology registry under stable concept keys (`status_active` / `status_expired` / `status_access_denied` / `status_payment_failed`) and consumed via the registry — keeping the glossary↔registry relationship documented (the glossary stays the human source of truth; the registry is its machine projection).
  - [x] Add the four status-message rows to `docs/ui-copy-translations.md` under the Lidmaatskap section, marked as **GEFINALISEERDE / curated** copy (verbatim from Deel 3), with the registry concept keys and a note that they are consumed by 6.8 (denial) and 9.4 (status surface) — NOT `[NEEDS HUMAN AFRIKAANS]` (the copy is approved). Confirm there are zero placeholders for 4.7.

- [x] **Task 2 — Project the four messages into the terminology registry (AC: 1, 2)**
  - [x] Edit `wp-content/plugins/ink-core/src/I18n/Terms.php`: add four keys to `map()` — `status_active` → `__( 'Jou lidmaatskap is aktief. Jy kan nou werk plaas.', 'ink-core' )`, `status_expired` → `__( 'Jou lidmaatskap het verval. Hernu om werk te plaas.', 'ink-core' )`, `status_access_denied` → `__( 'Slegs betaalde lede kan werk plaas. Sien aansluitingsopsies.', 'ink-core' )`, `status_payment_failed` → `__( 'Jou betaling het misluk of is gekanselleer.', 'ink-core' )`. Literals verbatim from Deel 3; documented as the lid-family status-message projection. No `__( $var )`.

- [x] **Task 3 — The `MembershipStatus` enum (the fixed value set) (AC: 3)**
  - [x] Create `wp-content/plugins/ink-core/src/Entitlement/MembershipStatus.php` — `namespace Ink\Entitlement; enum MembershipStatus: string` with cases `Active = 'active'`, `Expired = 'expired'`, `AccessDenied = 'access-denied'`, `PaymentFailed = 'payment-failed'` (the four AC states; backing string is the stable state id). Add a `messageKey(): string` `match` mapping each case to its registry key (mirroring `LidmaatskapTerm::termKey()`), and a `message(): string` returning `Terms::label( $this->messageKey() )`. Strict types + `ABSPATH` guard + no closing `?>`. **Zero** `use Ink\Tiers` (conflation).

- [x] **Task 4 — The `StatusMessages` resolver (AC: 3, 4)**
  - [x] Create `wp-content/plugins/ink-core/src/Entitlement/StatusMessages.php` — `namespace Ink\Entitlement; final class StatusMessages`. A thin resolver (read-model shape, like `PlanPresenter`): `messageFor( MembershipStatus $status ): string` → `$status->message()`; and a `fromWcStatus( string $wc_status ): MembershipStatus` helper that maps the relevant WooCommerce Memberships status strings to the closed enum (`active`/`complimentary`/`free`/`free_trial` → Active; `expired` → Expired; `cancelled`/`paused`/`pending`/`pending_cancellation` → AccessDenied) for the consumer that has only a WC status string in hand (6.8/9.4). Document that `payment-failed` is NOT a WC membership status (it is a PayFast return/cancel state) so `fromWcStatus` never returns it — `PaymentFailed` is resolved directly from the payment-return context by its consumer. Introduces no business RULE (the gate/`canSubmit` remains the entitlement authority); this only maps a state to its message. Zero `Ink\Tiers`.

- [x] **Task 5 — Expose via the `Api` facade (AC: 4)**
  - [x] Edit `wp-content/plugins/ink-core/src/Entitlement/Api.php`: add `statusMessage( MembershipStatus $status ): string` (delegates to a lazily-built shared `StatusMessages`) and `statusMessageFor( string $wc_status ): string` (resolves the enum from a WC status then returns its message) — the clean cross-module API the 6.8 enforcement point and 9.4 status surface consume. Update the facade docblock to record the 4.7 surface. No new hook.
  - [x] Update `wp-content/plugins/ink-core/src/Entitlement/Module.php` doc-comment: move "the full Afrikaans status-messaging copy (Story 4.7)" out of the RESERVED/NOT-built list into the owned-behaviour prose (the resolver is a pure on-demand read — registers NO hook, like the gate; `register()` is unchanged).

- [x] **Task 6 — Author AND RUN the Pest unit tests (AC: 1–5)**
  - [x] Extend `tests/Unit/I18n/TermsTest.php` (or add focused assertions): each of the four `status_*` keys resolves to its exact approved Afrikaans message; `has()` reports them registered; assert key Afrikaans tokens present ("lidmaatskap", "aktief", "verval", "betaling", "misluk", "betaalde lede") and **no English leakage** (assert absence of obvious English words — "membership", "expired", "payment", "active", "failed", "denied").
  - [x] Create `tests/Unit/Entitlement/MembershipStatusTest.php` + `tests/Unit/Entitlement/StatusMessagesTest.php` (Brain Monkey, `__()` identity stub): each enum case → its registry message (active→aktief, expired→verval, access-denied→denial, payment-failed→betaling-misluk); the resolver maps correctly; `fromWcStatus()` maps WC strings to the right enum and never returns `PaymentFailed`; conflation — assert no `Ink\Tiers` symbol is referenced (e.g. source-scan / no `ink_writer_tier`). Use real autoloaded constants/enums (no duplicated literals).
  - [x] **Run `composer test:unit`** — the whole suite passes (zero regressions). Record the command + green result in the Dev Agent Record. Run `composer cs` (touched files) + `composer stan` + `composer deptrac` (confirm no `Entitlement → Tiers`). Note the phpstan sandbox caveat if it appears.

- [x] **Task 7 — Verification & guardrail sweep (AC: 1–5)**
  - [x] Confirm: every new `.php` has `<?php` + one `declare(strict_types=1)` + correct `Ink\…` namespace + `ABSPATH` guard + no closing `?>`; PascalCase/camelCase; the four messages exist ONLY in `Terms::map()` (single-source — grep that no consumer inlines the literal); no raw superglobals; no `use Ink\Tiers` / `ink_writer_tier` anywhere in the new files; the resolver/enum register no hook; the `Api` facade is the only cross-module surface touched.
  - [x] Confirm scope discipline: no 6.8 publish wiring, no 9.4 surface, no 4.8 email copy, no theme/`functions.php` render logic, no Gradering coupling, no change to `canSubmit()`'s bool contract; zero `[NEEDS HUMAN AFRIKAANS]` placeholders introduced (all four states map to approved glossary copy).

## Review Findings

_Code review 2026-06-23 (2-layer adversarial review: Blind Hunter, Edge+Acceptance Auditor). Test suite independently re-run: **248 passed / 1 skipped** — Dev Agent Record claim confirmed._

_**Blind Hunter: clean, no findings.** Confirmed the four status messages are byte-for-byte verbatim from the human-authored glossary `afrikaans-terms.md` Deel 3 (no AI-invented Afrikaans), single-source in the `Ink\I18n\Terms` registry (no consumer inlines a literal), the resolver mapping (`MembershipStatus` → registry key, WC status → enum) is correct, and the work is conflation-clean (zero `Ink\Tiers` / `ink_writer_tier`)._

_**Edge+Acceptance Auditor: all 5 ACs PASS, all gates PASS.** The no-consumer-wired deferral (render consumers = the Story 6.8 publish-denial enforcement point + the Story 9.4 My Profiel / Skrywerprofiel status surface) is ruled LEGITIMATE and FR-mapped (FR-9 → 4.7 supplies messages + resolver only). Tests are genuine (assert against the real autoloaded registry values, not hollow). 248 passed / 1 skipped independently confirmed._

- [x] [Review][Patch] **`fromWcStatus()` matched case/whitespace-sensitively** — an arbitrary WC status `'Active'` / `' active '` / `'EXPIRED'` would fail-safe-deny an entitled member (a *false denial*). Normalised the input with `strtolower( trim( … ) )` before matching, mirroring the fail-safe-deny hardening of `SubmissionGate` / `StorefrontSuppression`; the fail-safe DIRECTION (unknown → `AccessDenied`) is unchanged. Added a test asserting `'Active'` / `' active '` / `'EXPIRED'` normalise to the correct enum case. [StatusMessages.php:108-114] (LOW)
- [x] [Review][Doc] **"deptrac green" wording imprecise** — `composer deptrac` exits non-zero with 3 PRE-EXISTING `Ink\Kernel\Activation → Ink\Content\PostTypes` violations unrelated to 4.7. Reworded the Completion Notes to the accurate Epic-4 form: "introduces no new edge and no `Entitlement → Tiers` edge; the 3 reported violations are the pre-existing `Kernel\Activation → Content\PostTypes` baseline, unchanged by this story." [Completion Notes] (LOW — doc)
- [Review][By-design] **`payment-failed` is reachable only via the typed `Api::statusMessage( MembershipStatus::PaymentFailed )` path** — NOT via `fromWcStatus()`, because payment-failed is a PayFast-return/cancel state, not a WooCommerce Memberships status. `fromWcStatus()` never returns it (asserted by test); the 4.2 purchase-return consumer resolves `PaymentFailed` directly. Correct by design.
- [Review][By-design] **The closed-enum `match` in `MembershipStatus::messageKey()` has no `default`** — a future un-handled case fails LOUD (`\UnhandledMatchError`) rather than silently falling through to a wrong/empty message. Correct by design (the fixed-value-set rule).
- [Review][By-design] **No render consumer is wired now** — the publish-denial enforcement point is Story 6.8 (`Ink\Submission`, not yet built) and the My Profiel / Skrywerprofiel status surface is Story 9.4; both consume `Api::statusMessage()` / `Api::statusMessageFor()` later. 4.7 supplies the messages + resolver + facade only and documents the boundary. Correct by design (three-layer; FR-9 → 4.7).

## Dev Notes

### What this story is (and is NOT)

- **IS:** the **four lid-family Afrikaans status messages** (active / expired / access-denied / payment-failed) projected as single-source, glossary-backed `__()` literals into the `ink-core` terminology registry (`Ink\I18n\Terms`, AD-10), PLUS a closed `Ink\Entitlement\MembershipStatus` enum + a `StatusMessages` resolver that maps a membership state → the right registry message, exposed through the existing `Ink\Entitlement\Api` facade (`statusMessage()` / `statusMessageFor()`); PLUS authored AND passing Pest tests. _[epics.md#Story-4.7; afrikaans-terms.md Deel 3; architecture.md AD-1/AD-10]_
- **IS NOT:** the publish-flow enforcement WIRING that surfaces the denial (**Story 6.8**, `Ink\Submission` — does not exist yet); the My Profiel / Skrywerprofiel status SURFACE (**Story 9.4**); the lifecycle EMAIL copy + expiry warnings (**Story 4.8** — Notifications form-letter store, not this registry); any theme template / pattern / `functions.php` render logic; any `Ink\Tiers` / Gradering coupling; any change to `SubmissionGate::canSubmit()`'s pure-bool contract.

### ⭐ The decided approach (key deliverable)

The status MESSAGES are controlled-vocabulary lid-family copy → they belong in the **same single-source registry** (`Ink\I18n\Terms`) that already governs the lid family (`membership`, `betaalde_lid`, the term labels, the approval-backstop labels). The prompt permits a sibling status-message registry, but `Terms` already IS the lid-family controlled-vocabulary projection and the NFR-1 leak scan already inspects `Terms::all()` — a sibling would be redundant moving parts. So: **register the four keys in `Terms`**.

The state→message RESOLUTION is the modelling decision. Following the project's "model fixed value sets as enums" rule and the in-module `LidmaatskapTerm` precedent (an `enum` whose `label()` defers to `Terms::label( $this->termKey() )`), 4.7 adds:

- `MembershipStatus` — a closed `enum: string` of the four AC states; `message()` → `Terms::label( $this->messageKey() )`.
- `StatusMessages` — a thin resolver (`messageFor( MembershipStatus )`, `fromWcStatus( string )`) so a consumer holding only a WooCommerce status string can resolve the right message without re-deriving the mapping.
- `Api::statusMessage()` / `Api::statusMessageFor()` — the clean cross-module surface (the sole public seam, AD-1) the later consumers call.

**Why no consumer is wired now:** the two genuine consumers do not exist yet — the entitlement-DENIED enforcement point is Story 6.8 (`Ink\Submission`), and the lidmaatskap/My-Profiel status SURFACE is Story 9.4. 4.3 (`SubmissionGate`) deliberately deferred the denial COPY to 4.7 (its docblock says so) and returns a bool; 4.2 (`PurchaseActivation`) deferred the status-messaging SURFACE to 4.7. 4.7 supplies the messages + the resolver API and documents that 6.8/9.4 consume them — this keeps 4.7 a clean, render-free, business-logic-only story (three-layer: strings + resolution in `ink-core`; rendering is theme/later-story).

### ⚠️ Guardrails (prevent disasters)

- **No AI-generated Afrikaans.** All four messages are verbatim from `afrikaans-terms.md` Deel 3 (human-authored, approved). The AC's example strings ARE these glossary strings. Zero `[NEEDS HUMAN AFRIKAANS]` placeholders are needed for 4.7. _[project-context.md "No AI-generated Afrikaans"; afrikaans-terms.md Deel 3]_
- **Single-source — never an inline literal.** The four messages live ONLY in `Terms::map()`. Consumers resolve by KEY via `Api::statusMessage()`. _[project-context.md "never inline a glossary label as a bare literal"; AD-10]_
- **THE conflation rule.** Status messaging is a lidmaatskap-state concept — zero `Ink\Tiers` / `ink_writer_tier` reference. The enum + resolver live in `Ink\Entitlement`; Deptrac's permanent `Entitlement ⟂ Tiers` prohibition stays green. _[AD-1; epics.md line 24; deptrac.yaml]_
- **Glossary-first.** The four situations are already in the glossary (Deel 3) — the standing rule is satisfied; the story projects, it does not author. _[project-context.md "a new concept is added to the glossary BEFORE it appears in code/UI"]_
- **Scope: messages + resolver only.** No 6.8 wiring, no 9.4 surface, no 4.8 email copy, no theme render logic. _[epics.md FR coverage: FR-9 → 4.7 only]_

### Source tree (new / touched)

- `wp-content/plugins/ink-core/src/I18n/Terms.php` — **edit** (add four `status_*` keys to `map()`).
- `wp-content/plugins/ink-core/src/Entitlement/MembershipStatus.php` — **new** (the closed state enum).
- `wp-content/plugins/ink-core/src/Entitlement/StatusMessages.php` — **new** (the state→message resolver).
- `wp-content/plugins/ink-core/src/Entitlement/Api.php` — **edit** (add `statusMessage()` / `statusMessageFor()`).
- `wp-content/plugins/ink-core/src/Entitlement/Module.php` — **edit** (doc-comment: 4.7 now owned, not reserved).
- `tests/Unit/I18n/TermsTest.php` — **edit** (the four status-message assertions + leak check).
- `tests/Unit/Entitlement/MembershipStatusTest.php` — **new**.
- `tests/Unit/Entitlement/StatusMessagesTest.php` — **new**.
- `docs/afrikaans-terms.md` — **edit** (Deel 3 projection note).
- `docs/ui-copy-translations.md` — **edit** (curated status-message rows + registry-key + consumer note).

## Dev Agent Record

### Agent Model Used

Opus 4.8 (1M context)

### Debug Log References

- `composer test:unit` — full Unit suite.
- `composer cs` (phpcs, touched files) · `composer stan` (phpstan) · `composer deptrac` (conflation graph).
- **Review round (2026-06-23):** `composer test:unit` → **249 passed / 1 skipped** (was 248/1; +1 from the new casing/whitespace normalisation test, zero regressions). `composer cs wp-content/plugins/ink-core/src/Entitlement/StatusMessages.php tests/Unit/Entitlement/StatusMessagesTest.php` → clean. `composer stan` → **No errors** (run with the sandbox disabled to clear the documented TCP-listen `EPERM` caveat). `composer deptrac` → no new edge, no `Entitlement → Tiers`; the 3 reported violations are the pre-existing `Kernel\Activation → Content\PostTypes` baseline.

### Completion Notes

- **Glossary-first satisfied without authoring any Afrikaans.** The four lid-family status messages already existed, human-authored and approved, in `docs/afrikaans-terms.md` Deel 3 (lines 209/210/212/215). 4.7 PROJECTED them verbatim into `Ink\I18n\Terms::map()` under four stable concept keys (`status_active`, `status_expired`, `status_access_denied`, `status_payment_failed`). **Zero `[NEEDS HUMAN AFRIKAANS]` placeholders** — all four states map to approved copy. Added a projection note to Deel 3 and curated (non-placeholder) rows to `docs/ui-copy-translations.md`.
- **State→message resolution.** Added `Ink\Entitlement\MembershipStatus` (closed `enum: string` of the four AC states; `message()` → `Terms::label( messageKey() )`, mirroring the `LidmaatskapTerm` precedent) and `Ink\Entitlement\StatusMessages` (a thin resolver: `messageFor( MembershipStatus )` + `fromWcStatus( string )`, which never returns `PaymentFailed` because payment-failed is a PayFast-return state, not a WC membership status). Exposed through `Ink\Entitlement\Api::statusMessage()` / `Api::statusMessageFor()` — the sole cross-module surface.
- **No consumer wired now (documented boundary).** The denial-copy consumer is the publish enforcement point (Story 6.8, `Ink\Submission`, not yet built); the status SURFACE consumer is My Profiel / Skrywerprofiel (Story 9.4). 4.3's `SubmissionGate` (bool) and 4.2's `PurchaseActivation` both explicitly deferred their copy/surface to 4.7; their docblocks and `Module.php`'s reserved-list were updated to reflect that 4.7 now owns the messages. `canSubmit()`'s pure-bool contract is unchanged. The resolver/enum register NO hook (pure on-demand read, like the gate).
- **Conflation-clean.** No `Ink\Tiers` / `ink_writer_tier` reference in any new file; Deptrac stays green with no `Entitlement → Tiers` edge.
- **Tests authored AND run.** Extended `tests/Unit/I18n/TermsTest.php` (four status keys + Afrikaans-token / no-English-leak assertions); added `tests/Unit/Entitlement/MembershipStatusTest.php` + `StatusMessagesTest.php`. `composer test:unit`: **248 passed / 1 skipped** (was 235/1 — +13, zero regressions). `composer cs` clean on touched files; `composer stan` clean; `composer deptrac` introduces no new edge and no `Entitlement → Tiers` edge; the 3 reported violations are the pre-existing `Kernel\Activation → Content\PostTypes` baseline, unchanged by this story.

### File List

- `wp-content/plugins/ink-core/src/I18n/Terms.php` (edit)
- `wp-content/plugins/ink-core/src/Entitlement/MembershipStatus.php` (new)
- `wp-content/plugins/ink-core/src/Entitlement/StatusMessages.php` (new)
- `wp-content/plugins/ink-core/src/Entitlement/Api.php` (edit)
- `wp-content/plugins/ink-core/src/Entitlement/Module.php` (edit)
- `tests/Unit/I18n/TermsTest.php` (edit)
- `tests/Unit/Entitlement/MembershipStatusTest.php` (new)
- `tests/Unit/Entitlement/StatusMessagesTest.php` (new)
- `docs/afrikaans-terms.md` (edit)
- `docs/ui-copy-translations.md` (edit)

### Change Log

| Date | Version | Description | Author |
|---|---|---|---|
| 2026-06-23 | 0.1 | create-story: drafted 4.7 (status messaging, FR-9) — ready-for-dev | Opus 4.8 (1M context) |
| 2026-06-23 | 1.0 | dev-story: projected four glossary status messages into `Terms`; added `MembershipStatus` enum + `StatusMessages` resolver + `Api` surface; tests 248/1, cs/stan/deptrac clean; status → review | Opus 4.8 (1M context) |
| 2026-06-23 | 1.1 | code-review patches: normalise casing/whitespace in `fromWcStatus()` (false-denial hardening) + test; corrected "deptrac green" wording; appended Review Findings (2-layer adversarial review). tests 249/1, cs/stan/deptrac clean | Opus 4.8 (1M context) |

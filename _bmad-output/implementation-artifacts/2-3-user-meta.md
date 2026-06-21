---
baseline_commit: 628a611
---

# Story 2.3: User meta

Status: review

<!-- Note: Validation is optional. Run validate-create-story for quality check before dev-story. -->

## Story

As an ink-core developer,
I want the writer-tier user meta defined,
so that Gradering state has a stable store independent of membership.

## Acceptance Criteria

1. **`ink_writer_tier` and `ink_tier_promoted_at` user meta are registered, with `ink_writer_tier` defaulting to `brons`.** Given a user, when the tier meta is read, then `ink_writer_tier` and `ink_tier_promoted_at` exist as registered user meta and an unset `ink_writer_tier` reads as `brons` (the default). The default value comes from `Ink\Kernel\Tier::Brons->value` — never a re-typed `'brons'` literal. `ink_tier_win_count` is explicitly NOT registered here — it is added in Story 5.7. _[Source: epics.md#Story-2.3 AC "`ink_writer_tier` and `ink_tier_promoted_at` exist (default `brons`); `ink_tier_win_count` is added in 5.7"; architecture.md AD-5 storage table line 370 (`ink_writer_tier`, `ink_tier_promoted_at`, `ink_tier_win_count` → user-meta, OQ-14); project-context.md line 42 (writer tier enum brons/silwer/goud), "Model fixed value sets as enums … never duplicate these literals"]_

2. **The meta is registered as the data substrate in `ink-core`/Content, independent of lidmaatskap entitlement (THE conflation rule).** Given the AD-1 modular monolith, when the meta registers, then it registers inside `Ink\Content` (the Epic-2 module that owns CPTs/taxonomies/**meta** per the architecture module tree), dispatched on `init` via `Content\Module::register()` (after CPTs/taxonomies). The registration introduces NO dependency on `Ink\Entitlement` — *writer tier* (Gradering, competition pools) is kept strictly separate from *subscription status* (submission entitlement). This story registers only the storage substrate; the SOLE write path `Tiers::promote()` and the promotion engine remain Epic 5 (the Tiers module), and are NOT implemented here. _[Source: architecture.md module tree (`Content/ # Epic 2 — CPTs, taxonomies, meta`; `Tiers/ # Epic 5 … Tiers::promote() (sole write path) ⟂ Entitlement`), AD-1; project-context.md "THE conflation rule … subscription status controls submission entitlement; writer tier controls competition pools. These are separate concepts — never gate one on the other"; src/Kernel/Tier.php (Kernel-owned value type, read without an inter-module edge)]_

3. **The tier is a controlled value set, REST-aware, and staff-write-gated.** Given each `register_meta` call, when it runs, then `ink_writer_tier` is `single => true`, `type => 'string'`, `show_in_rest => true` (profile read surface, AD-6), with a `sanitize_callback` that coerces any non-`Tier` value back to the `brons` default via `Ink\Kernel\Tier::tryFrom()` (so an invalid write can never persist a junk grade), and an `auth_callback` that gates writes on `current_user_can( Ink\Kernel\Capabilities::MANAGE_TIERS )` — tier is staff-set, never self-set by the member. `ink_tier_promoted_at` is likewise `single`/`show_in_rest`, sanitized as a text datetime, default `''`. _[Source: architecture.md AD-6 (`show_in_rest` / three-tier capability check `current_user_can('ink_{cap}')`); src/Kernel/Capabilities.php (`MANAGE_TIERS = 'ink_manage_tiers'`); src/Kernel/Tier.php (`tryFrom` validation); project-context.md "All input is sanitised and capability-checked", "Model fixed value sets as enums"]_

4. **Registration is strict, prefixed, single-sourced, statically verifiable.** Given the new code, when it is written, then the meta keys are class constants (single-source, `ink_`-prefixed), the Content facade (`Content\Api`) exposes them as the cross-module surface (AD-1), every new `.php` is `<?php` + `declare(strict_types=1)` + `Ink\Content` namespace + `defined('ABSPATH')||exit;`; classes PascalCase, methods camelCase, no raw superglobals. A ready-to-run Pest test asserts the registration (keys, default, sanitize coercion, REST/single flags, facade); `php -l`/Pest deferred to the CI buildout (18.8) per the Epic-1 precedent; a python3 structural scan substitutes. _[Source: architecture.md AD-1 (facade discipline), Implementation Patterns (naming/structure, tests `tests/Unit/{Module}`); project-context.md "Prefix everything ink_/Ink\", "declare(strict_types=1)", "no raw $_POST/$_GET"; Stories 2.1/2.2 (registrar + python3-scan precedent)]_

## Tasks / Subtasks

> **Current state (read before starting):** Story 2.1/2.2 (review) built `Ink\Content` with `PostTypes` (9 CPTs) and `Taxonomies` (4 taxonomies); `Content\Module::register()` calls `( new PostTypes() )->register(); ( new Taxonomies() )->register();` on `init`; `Content\Api` exposes `all()`/`bydraeTypes()`/`taxonomies()`. The `content` module is wired in `ink-core.php` (2.1). The Kernel already owns `Ink\Kernel\Tier` (enum: Brons/Silwer/Goud/Meester, backing values `brons`/…) and `Ink\Kernel\Capabilities` (`MANAGE_TIERS = 'ink_manage_tiers'`). The Tiers module (`src/Tiers/`) is reserved for Epic 5 and is a no-op — do NOT implement promotion logic, the engine, or `Tiers::promote()` here. No PHP binary / built `vendor/` — verification is the python3 scan + ready-to-run Pest (Epic-1 precedent). Scope is USER-META registration ONLY — per-CPT admin field sets are 2.4, term images are 2.5, `ink_tier_win_count` is 5.7, promotion behaviour is Epic 5.

- [x] **Task 1 — Add the user-meta key single-source + the Content registrar (AC: 1, 4)**
  - [x] Create `src/Content/UserMeta.php` — `namespace Ink\Content; final class UserMeta` (strict types + `ABSPATH` guard, mirroring `PostTypes`/`Taxonomies`). Declare a class constant per meta key (`WRITER_TIER = 'ink_writer_tier'`, `TIER_PROMOTED_AT = 'ink_tier_promoted_at'`) — these constants are the single source for the keys. Do NOT add a `win_count` constant (5.7).
  - [x] `public static function keys(): array` — the two meta keys (the facade surface).
  - [x] `public function register(): void` — call `register_meta( 'user', self::WRITER_TIER, $args )` and `register_meta( 'user', self::TIER_PROMOTED_AT, $args )`. Runs on `init` (invoked from `Module::register()` after CPTs/taxonomies — `register_meta` is an `init`-time call).

- [x] **Task 2 — Configure `ink_writer_tier` as a controlled, gated value (AC: 1, 3)**
  - [x] `ink_writer_tier` args: `single => true`, `type => 'string'`, `show_in_rest => true`, `default => Ink\Kernel\Tier::Brons->value` (never a `'brons'` literal), `sanitize_callback` → a private static method that returns `( Tier::tryFrom( (string) $value ) ?? Tier::Brons )->value` (coerce any junk back to the default), `auth_callback` → `static fn (): bool => current_user_can( Capabilities::MANAGE_TIERS )` (staff-write-gated).
  - [x] `use Ink\Kernel\Tier;` and `use Ink\Kernel\Capabilities;` at the top.

- [x] **Task 3 — Configure `ink_tier_promoted_at` (AC: 1, 3)**
  - [x] `ink_tier_promoted_at` args: `single => true`, `type => 'string'`, `show_in_rest => true`, `default => ''`, `sanitize_callback => 'sanitize_text_field'`, `auth_callback` → the same `MANAGE_TIERS` gate. (A convenience "last promotion" timestamp; the authoritative promotion history is the Epic-5 custom table — do NOT build it here.)

- [x] **Task 4 — Register through the module seam + expose the facade (AC: 2, 4)**
  - [x] Edit `src/Content/Module.php`: `register()` calls `( new UserMeta() )->register();` AFTER the CPT/taxonomy registrars. Update the doc-comment (2.3 now live; reserved note now covers only field sets 2.4 / term images 2.5). MUST NOT add any `Ink\Entitlement` reference (THE conflation rule).
  - [x] Edit `src/Content/Api.php`: add `Api::userMetaKeys(): array` delegating to `UserMeta::keys()`. (Behavioural tier read/write is the Epic-5 `Tiers\Api`; this facade exposes only the registered key surface.)
  - [x] No `ink-core.php` change — the `content` module is already wired.

- [x] **Task 5 — Author the ready-to-run Pest unit test (AC: 1, 3, 4)**
  - [x] Create `tests/Unit/Content/UserMetaTest.php` (Brain Monkey, mirroring the 2.1/2.2 tests): alias `register_meta` to capture `(object_type, key, args)`; assert both keys register against object type `'user'`; `ink_writer_tier` default is `'brons'` and comes through as `single`/`show_in_rest` true; invoking the captured `ink_writer_tier` `sanitize_callback` with `'goud'` returns `'goud'` and with `'rubbish'` returns `'brons'` (coercion); `ink_tier_promoted_at` default is `''`; assert `ink_tier_win_count` is NOT among the registered keys (scope guard); `Api::userMetaKeys()` returns the two keys. (For the `sanitize_callback` test the real `Ink\Kernel\Tier` enum is autoloaded; no WP needed.)

- [x] **Task 6 — Static verification (no PHP binary — Epic-1 precedent) (AC: 1–4)**
  - [x] python3 scan: structure (`<?php`/one `declare`/`ABSPATH`/balanced braces/no `?>`) on all new/edited `ink-core` files; 2 meta-key constants present with exact IDs; `register_meta( 'user', …)` called for both; default sourced from `Tier::Brons->value` (no `'brons'` literal); `auth_callback` uses `Capabilities::MANAGE_TIERS`; `sanitize_callback` present; `show_in_rest`/`single` true; `Content\Module::register` calls `UserMeta`; `Api::userMetaKeys` present; no `ink_tier_win_count` (out of scope); no `Ink\Entitlement` reference (conflation rule); no `register_post_type`/`register_taxonomy`/`add_meta_box`; no raw superglobals; theme untouched. Record `php -l`/Pest deferral.

## Dev Notes

### What this story is (and is NOT)

- **IS:** registering the **writer-tier user-meta substrate** (`ink_writer_tier` default `brons`, `ink_tier_promoted_at`) in `Ink\Content`, controlled by the Kernel `Tier` enum, REST-aware, staff-write-gated, with the facade exposing the keys. _[epics.md#Story-2.3; AD-1/AD-5/AD-6]_
- **IS NOT:** the promotion engine / win-count threshold logic / `Tiers::promote()` (Epic 5), `ink_tier_win_count` (Story 5.7), the promotion-log custom table (Epic 5), per-CPT admin field sets / meta boxes (2.4), term images (2.5), tier→role capability mapping (later), or any tier UI (Epic 5). No `Ink\Entitlement` coupling. No theme changes.

### ⭐ The mechanism (key deliverable)

- **Tier state ⟂ membership:** the meta is the stable store for Gradering state that survives independent of subscription status (THE conflation rule). Registering it in Content with zero `Entitlement` reference is the structural guarantee.
- **Controlled default via the enum:** the default and the sanitize coercion both go through `Ink\Kernel\Tier` — `default => Tier::Brons->value`, `sanitize_callback` → `( Tier::tryFrom($v) ?? Tier::Brons )->value`. A junk write (typo, malicious REST payload) can never persist an invalid grade; it falls back to `brons`. No `'brons'` literal is re-typed.
- **Staff-write-gated:** `auth_callback` → `current_user_can( Capabilities::MANAGE_TIERS )`. Tier is editorial state, never self-set. (The cap is granted to roles in a later epic; the gate is wired now.)
- **Key single-source:** the two meta keys are `UserMeta` class constants — the Epic-5 Tiers module reads them via `Content\Api::userMetaKeys()` (a permitted Tiers→Content facade edge; only Tiers⟂Entitlement is forbidden).

### ⚠️ Guardrails

- **Default & validation from the `Tier` enum** — `Tier::Brons->value` / `Tier::tryFrom()`; never a `'brons'` string literal. _[project-context.md enums]_
- **No `ink_tier_win_count`** — that is Story 5.7. _[epics.md#Story-2.3]_
- **No promotion behaviour** — `Tiers::promote()`, the engine and the log are Epic 5. This story is storage only. _[architecture module tree]_
- **No `Ink\Entitlement` reference** — THE conflation rule; the CI dep rule (1.11/AD-8) forbids the Tiers⟷Entitlement edge, and the spirit applies to keeping tier storage entitlement-free. _[project-context.md]_
- **Capability-checked + sanitised** writes (`auth_callback` + `sanitize_callback`). _[project-context.md]_
- **Meta keys live in `ink-core`/Content**, registered via the already-wired module — never the theme. _[three-layer; AD-1]_
- **strict types + `ABSPATH` guard + `Ink\Content` namespace + no raw superglobals** in every new file. _[project-context.md]_

### Project Structure Notes

- New `src/Content/UserMeta.php` (the registrar/collaborator, mirroring `PostTypes`/`Taxonomies`); edits to `src/Content/Module.php` (delegate to `UserMeta` after the CPT/taxonomy registrars), `src/Content/Api.php` (facade exposes the meta keys). Test at `tests/Unit/Content/UserMetaTest.php` mirrors the 2.1/2.2 tests.
- No `ink-core.php` change — the `content` module bootstrap is already wired (2.1). The `Module → collaborator` split now spans `PostTypes` + `Taxonomies` + `UserMeta`, preserving the house style.

### Previous story intelligence (Stories 2.1 / 2.2)

- The registrar pattern to mirror: a `final class` in `Ink\Content` with `public const` single-source IDs, a `public static keys()/all()` facade surface, a `public function register()` that loops/calls the WP registrar, and private static helpers (`definitions()`/`args()`/`labels()`). For 2.3 the WP registrar is `register_meta` (signature: object_type, meta_key, args).
- The test pattern: alias the WP registrar via `Functions\when(...)->alias(...)` to capture args into a map, then `expect()` on the captured map. Reuse the capture-helper shape; for the sanitize coercion test, call the captured `sanitize_callback` directly (the real `Tier` enum is autoloaded by `tests/bootstrap.php`).
- The python3 scan (2.1: 53/53, 2.2: 52/52) is the verification template — extend it for the meta specifics; use regex (not fixed-space substrings) for aligned `=>` array entries.
- `Content\Module::register()` already orders CPTs → taxonomies; append UserMeta last.

### Testing standards summary

No PHP binary / built `vendor/` (CI harness at 18.8). `php -l`/Pest/PHPStan deferred (Epic-1 precedent); python3 structural scan substitutes; the authored `UserMetaTest.php` runs once the runner is wired. Brain Monkey stubs `register_meta` (and `current_user_can` only if an auth_callback path is exercised — not needed for the default/sanitize assertions). Tests live at the repo-root `tests/` tree.

### References

- [Source: _bmad-output/planning-artifacts/epics.md#Story-2.3 — User meta; AC: `ink_writer_tier` and `ink_tier_promoted_at` exist (default `brons`); `ink_tier_win_count` is added in 5.7]
- [Source: _bmad-output/planning-artifacts/architecture.md — module tree (`Content/ # Epic 2 — CPTs, taxonomies, meta`; `Tiers/ # Epic 5 … Tiers::promote() sole write path ⟂ Entitlement`); AD-5 storage table line 370 (writer-tier keys → user-meta, OQ-14; win_count reset by `Tiers::promote()` — R3, Epic 5); AD-6 (`show_in_rest`, three-tier capability check); AD-1 (facade discipline)]
- [Source: _bmad-output/project-context.md — THE conflation rule (subscription status ⟂ writer tier); "Model fixed value sets as enums … never duplicate these literals"; "All input is sanitised and capability-checked"; "Prefix everything ink_/Ink\"; "declare(strict_types=1)"; three-layer (content models in ink-core, never the theme)]
- [Source: wp-content/plugins/ink-core/src/Kernel/Tier.php (enum + backing values; `Tier::Brons->value`, `Tier::tryFrom()`); src/Kernel/Capabilities.php (`MANAGE_TIERS = 'ink_manage_tiers'`); src/Content/Module.php + Api.php (2.1/2.2 — wired module + facade to extend); src/Content/PostTypes.php + Taxonomies.php (registrar pattern to mirror)]
- [Source: tests/Unit/Content/PostTypesTest.php + TaxonomiesTest.php (the capture-and-assert test pattern to mirror)]

## Dev Agent Record

### Agent Model Used

claude-opus-4-8[1m] (Opus 4.8, 1M context)

### Debug Log References

- python3 structural scan (`scan_2_3.py`, run from the job tmp dir): **45/45 checks passed** — structure, 2 exact `ink_`-prefixed meta-key constants, `register_meta('user', …)` called twice, default from `Tier::Brons->value` (no `'brons'` literal in code), sanitize via `Tier::tryFrom`, `auth_callback` on `Capabilities::MANAGE_TIERS`, `single`/`show_in_rest` true, `Module::register` calls `UserMeta` last, `Api::userMetaKeys()` present, no `ink_tier_win_count` / no `Entitlement` reference / no `register_post_type`/`register_taxonomy`/`add_meta_box` in code, no raw superglobals, theme untouched. (Absence checks run against a comment-stripped copy so doc references to the deferred concepts don't false-positive.)

### Completion Notes List

- **Registrar (`UserMeta`)** mirrors `PostTypes`/`Taxonomies`: 2 `ink_`-prefixed meta keys as `public const` single-source (`WRITER_TIER`, `TIER_PROMOTED_AT`); `register()` calls `register_meta( 'user', … )` for each. `ink_tier_win_count` is intentionally NOT registered (Story 5.7).
- **Controlled default + coercion (AC-1/AC-3):** `ink_writer_tier` `default => Tier::Brons->value`; `sanitize_callback` → `UserMeta::sanitizeTier()` returns `( Tier::tryFrom( (string) $value ) ?? Tier::Brons )->value`, so a junk/malicious write can never persist an invalid grade. No `'brons'` literal is re-typed — both the default and the fallback flow through the Kernel `Tier` enum.
- **Staff-write-gated (AC-3):** `auth_callback` → `current_user_can( Capabilities::MANAGE_TIERS )` for both keys; tier is editorial state, never self-set. Both keys are `single`, `show_in_rest`, `type => 'string'`. `ink_tier_promoted_at` default `''`, sanitized with `sanitize_text_field`.
- **Conflation rule (AC-2):** the registrar carries ZERO reference to `Ink\Entitlement` — writer-tier storage is kept structurally independent of subscription entitlement. Promotion behaviour (`Tiers::promote()`, the engine, the log) remains Epic 5 and is not touched here.
- **Wiring (AC-2/AC-4):** `Content\Module::register()` calls `( new UserMeta() )->register();` after the CPT/taxonomy registrars (all on `init`); `Content\Api::userMetaKeys()` exposes the keys as the cross-module surface. No `ink-core.php` change (the `content` module was wired in 2.1). No registration leaks into the theme.
- **Scope discipline:** user-meta substrate only — no admin field sets (2.4), term images (2.5), win-count (5.7) or promotion engine/log (Epic 5).
- **Verification:** `php -l` / Pest / PHPStan deferred to the CI buildout (Story 18.8) per the Epic-1 precedent. The ready-to-run `UserMetaTest.php` (Brain Monkey captures `register_meta`; calls the captured `sanitize_callback` against the real autoloaded `Tier` enum to prove `goud`→`goud`, `rubbish`→`brons`) runs once the runner is wired; the python3 scan substitutes now (45/45).

### File List

- `wp-content/plugins/ink-core/src/Content/UserMeta.php` (new) — the writer-tier user-meta registrar + key single-source.
- `wp-content/plugins/ink-core/src/Content/Module.php` (modified) — `register()` delegates to `UserMeta` after the CPT/taxonomy registrars.
- `wp-content/plugins/ink-core/src/Content/Api.php` (modified) — facade exposes `userMetaKeys()`.
- `tests/Unit/Content/UserMetaTest.php` (new) — ready-to-run Pest unit test.

## Change Log

| Date | Change |
|---|---|
| 2026-06-21 | Story created (context-engineered) — register the writer-tier user-meta substrate (`ink_writer_tier` default `brons` via the Kernel `Tier` enum, `ink_tier_promoted_at`) in `Ink\Content`, REST-aware, sanitize-coerced to a valid grade, staff-write-gated on `MANAGE_TIERS`, independent of `Entitlement` (conflation rule); facade exposes the keys. `ink_tier_win_count` + promotion behaviour deferred to Epic 5. Status → ready-for-dev. |
| 2026-06-21 | Story implemented — `UserMeta` registrar + 2 meta-key constants, `register_meta` for both keys, enum-sourced default + sanitize coercion, `MANAGE_TIERS` auth gate, Module/Api wiring, ready-to-run Pest test. python3 scan 45/45 (`php -l`/Pest deferred to 18.8). Status → review. |

# Testing & quality harness — installation and configuration guide

This is the operational guide for the `ink-vibe` automated test + quality harness:
how to install the toolchain, run every gate locally, what each configuration
file does, and how the harness is exposed to agents working in this repository.

The harness itself was scaffolded in **Story 1.11** (Epic 1) and is the AD-8 / NFR-9
"test-first" foundation: `ink-core` business rules ship with their tests against a
runner that already exists. This document is the human/agent-facing companion to
that scaffold.

- **Architecture:** AD-8 (CI pipeline), AD-1 (module dependency graph + the
  `Ink\Entitlement ⟂ Ink\Tiers` conflation rule), NFR-9 (risk-based test depth).
- **Source of truth for config:** the repo-root files listed under
  [Configuration reference](#configuration-reference). This guide explains them; it
  does not replace them.

---

## 1. Current status (at a glance)

| Gate | Command | Status | Needs |
|------|---------|--------|-------|
| Unit (Pest + Brain Monkey) | `composer test:unit` | ✅ green — 66 pass, 1 skip | PHP only |
| Static analysis (PHPStan) | `composer stan` | ✅ green | PHP; **not** sandbox-compatible (see §7) |
| Coding standards (PHPCS/WPCS) | `composer cs` | ✅ green | PHP |
| Architecture rules (Deptrac) | `composer deptrac` | ✅ green — 0 violations | PHP |
| Integration (wp-env + WP) | `composer test:integration` | ⏸ deferred (Story 18.8) | **Docker** + Node 20 |
| E2E (Playwright) | — | ⏸ deferred (Story 18.8) | placeholder CI job only |

The four PHP gates run today. Integration and E2E are intentionally deferred to
**Story 18.8** (the full-pyramid buildout) and additionally need Docker, which is
not yet installed locally.

---

## 2. Prerequisites

| Tool | Version | Used for | Required for |
|------|---------|----------|--------------|
| PHP | **8.3+** (8.3 is the project floor; CI runs 8.3) | every PHP gate | unit, stan, cs, deptrac |
| Composer | 2.x | dependency install + script runner | all gates |
| Node.js | **20** (CI); 18 works for most tasks | `@wordpress/env` | integration (wp-env) |
| Docker | any recent | the WordPress + DB container | integration only |

> **PHP version note.** The project pins **8.3** (`composer.json` `"php": ">=8.3"`,
> `phpcs.xml` `testVersion 8.3-`, `.wp-env.json` `phpVersion 8.3`, and PHPStan's
> `phpVersion` range — see §6). The unit suite is verified green on both 8.3 and
> 8.5. If your local PHP is newer than 8.3 (e.g. 8.5), that is fine — analysis is
> still performed *as if* on 8.3 so it matches CI. Do not "fix" code for a newer
> PHP than the floor.

---

## 3. Installing the toolchain

### macOS (Homebrew)

```bash
brew install php@8.3 composer        # PHP 8.3 + Composer
# (Node + Docker only needed later, for integration tests)
brew install node                    # Node 20 for wp-env
# Docker Desktop: install from docker.com or `brew install --cask docker`
```

If you already have a newer PHP (e.g. via `brew install php`), that works too —
see the PHP version note above.

### Linux (Debian/Ubuntu)

```bash
sudo apt-get install php8.3-cli php8.3-xml php8.3-mbstring php8.3-curl
# Composer: https://getcomposer.org/download/
```

### Verify

```bash
php --version        # expect 8.3+ (NTS CLI)
composer --version   # expect 2.x
```

### Install project dependencies

From the repository root:

```bash
composer install
```

This builds `vendor/` with the dev toolchain (Pest, Brain Monkey, PHPStan +
phpstan-wordpress, PHP_CodeSniffer + WPCS, Deptrac). `vendor/` and `composer.lock`
are git-ignored / build artifacts per **AD-4** (only first-party code is committed);
`composer.lock` is committed to pin the toolchain.

> **No network in a restricted shell?** `composer install` fetches from Packagist
> and GitHub. If you are running inside a sandbox that blocks those hosts, run the
> install with networking enabled (or outside the sandbox) once; afterwards the
> built `vendor/` lets the unit suite run offline.

---

## 4. Running the suites

```bash
composer test:unit          # Pest unit suite — WordPress fully mocked (Brain Monkey), no DB
composer test               # alias for the default Pest run
composer stan               # PHPStan static analysis (level 5, ink-core/src)
composer cs                 # PHPCS / WPCS coding standards
composer cs:fix             # PHPCBF — auto-fix the fixable coding-standard issues
composer deptrac            # AD-1 module graph + the Entitlement ⟂ Tiers conflation rule
```

Integration (needs the Docker stack up first):

```bash
npx @wordpress/env start    # boot WordPress 7.0 / PHP 8.3 in Docker
composer test:integration   # Pest integration suite against real WP + DB
npx @wordpress/env stop
```

To run everything the way CI does, in order:

```bash
composer install && composer stan && composer cs && composer deptrac && composer test:unit
```

---

## 5. The test pyramid & layout

Tests live at the **repo root** (`tests/`), mirroring `wp-content/plugins/ink-core/src/`
(architecture lines 851, 937–967) — not co-located in the plugin.

```
tests/
├── bootstrap.php              # Unit bootstrap: Composer autoload + Brain Monkey + WP_User double + sentinel ABSPATH
├── Pest.php                   # Pest lifecycle bindings (minimal)
├── stubs/class-wp-user.php    # Minimal WP_User double for the mocked unit suite
├── Unit/{Module}/             # Pest + Brain Monkey — WordPress fully mocked, no DB
└── Integration/
    ├── bootstrap.php          # wp-env WP test-library seam (buildout: Story 18.8)
    └── {Module}/              # real WP + DB via wp-env
```

- **Unit** (`tests/Unit/{Module}/`): mock WordPress with Brain Monkey; no DB;
  namespace `Ink\Tests\Unit\{Module}`.
- **Integration** (`tests/Integration/{Module}/`): real WP via wp-env; namespace
  `Ink\Tests\Integration\{Module}`.
- **E2E** (`tests/e2e/`, Playwright): built in Story 18.8.

P0-rule homes (established by the scaffold, filled by later epics): tier promotion
(5.x) → `tests/{Unit,Integration}/Tiers/`; submission gate (6.8) →
`tests/{Unit,Integration}/Submission/` (+ the `Entitlement` seam); follow graph
(9.2) → `tests/{Unit,Integration}/Social/`.

> **Why the unit bootstrap defines `ABSPATH`.** Every `ink-core` source file opens
> with `defined( 'ABSPATH' ) || exit;`. The unit suite does not load WordPress, so
> without a sentinel `ABSPATH` the first autoloaded class would `exit(0)` and abort
> the run. `tests/bootstrap.php` defines a sentinel constant to satisfy that guard
> — it does **not** load WordPress.

---

## 6. Configuration reference

All harness configuration lives at the repo root.

| File | Purpose |
|------|---------|
| `composer.json` | Dev-tooling `require-dev`, PSR-4 autoload (`Ink\` → src/, `Ink\Tests\` → tests/), the `scripts` every gate is invoked through, AD-4 installer-paths. |
| `composer.lock` | Pins the exact toolchain versions (committed). |
| `phpunit.xml` | Pest/PHPUnit config; `Unit` + `Integration` testsuites; `bootstrap=tests/bootstrap.php`; `failOnWarning`/`failOnRisky` strictness. |
| `phpstan.neon` | PHPStan level 5 over `ink-core/src`; see notes below. |
| `phpstan-bootstrap.php` | **Analysis-only.** Declares the plugin constants (`INK_CORE_PATH/FILE/VERSION/URL`) that are defined at runtime in `ink-core.php` — which is outside the analysed `src/` path. Never loaded at runtime. |
| `phpcs.xml` | WPCS 3.3 ruleset; the project-convention exemptions (see below). |
| `deptrac.yaml` | The AD-1 module dependency graph + the build-failing `Ink\Entitlement ⟂ Ink\Tiers` conflation rule (FR-13). |
| `.wp-env.json` | `@wordpress/env` config — WP 7.0 / PHP 8.3, mounts `ink-core` + `ink-foundation`. |
| `.github/workflows/ci.yml` | The AD-8 pipeline (see §8). |

### PHPStan notes (`phpstan.neon` + `composer.json`)

- **`phpVersion: { min: 80300, max: 80399 }`** — analysis targets the supported PHP
  floor (8.3), independent of the host PHP. This keeps results deterministic and
  matched to CI even on a newer local PHP.
- **`treatPhpDocTypesAsCertain: false`** — generic PHPDoc (`array<string, T>`) is
  treated as a hint, not a runtime guarantee, so PHPStan does not infer false
  "never null" / "always true" certainty from it.
- **`bootstrapFiles: [phpstan-bootstrap.php]`** — resolves the plugin constants.
- **`excludePaths: [.../src/autoload.php]`** — the hand-rolled PSR-4 autoload bridge
  resolves its `require` paths at runtime (guarded by `is_readable()`); the
  plugin-local `vendor/` is not committed, so static require-path checking misfires.
- The `composer stan` script passes **`--memory-limit=1G`** (the default 128M is too
  low for the analysis + WordPress stubs).

### PHPCS / WPCS conventions (`phpcs.xml`)

The project deliberately adopts **PSR-4 + typed PHP 8.3** conventions. The ruleset
keeps **all security and i18n sniffs active** and exempts only the rules that
genuinely conflict with those conventions (each is commented in the file):

- **PSR-4 file naming** (`WordPress.Files.FileName.*`) — `ink-core` is
  Composer-autoloaded, which *requires* `TemplateStore.php` for class `TemplateStore`.
  WPCS's `class-*.php` convention is mutually exclusive with PSR-4.
- **camelCase methods + members** (`ValidFunctionName.MethodNameInvalid`,
  `ValidVariableName.*`) — value objects expose camelCase API (e.g. `$defaultSubject`);
  `snake_case` is still enforced on the procedural/global WP surface.
- **Redundant-with-types doc sniffs** (`FunctionComment.MissingParamTag` /
  `MissingParamComment` / `IncorrectTypeHint` / `ParamNameNoMatch`,
  `VariableComment.MissingVar`) — every param/property is PHP-typed and
  PHPStan-verified; `IncorrectTypeHint` additionally cannot parse the PHPStan
  generics (`list<string>`) the code relies on. Description/structure sniffs
  (`MissingShort`, undocumented functions, file `@package`) stay **active**.
- **Block-pattern headers** — `Squiz.Commenting.FileComment.*` is excluded **only**
  for `patterns/*.php`, whose header is WordPress pattern-registration metadata
  (Title/Slug/Categories), not a PHP file docblock.

---

## 7. Running the harness as an agent in this folder

Agents working in this repository can run the **unit suite without a permission
prompt** — it is the fast, deterministic, no-network, no-Docker path and is
allow-listed in `.claude/settings.local.json`:

```bash
composer test:unit          # allow-listed
composer test               # allow-listed
vendor/bin/pest --testsuite=Unit
```

**Sandbox caveats** (important for agents):

- The **unit suite runs inside the Claude sandbox** with no extra permission —
  it does no networking and opens no sockets.
- **PHPStan does NOT run inside the sandbox.** Its parallel worker pool opens a
  local TCP server, which the sandbox blocks (`EPERM`). Run `composer stan` with
  the sandbox disabled, or rely on CI. (`composer cs` and `composer deptrac` run
  fine sandboxed.)
- `composer install` needs network access to Packagist + GitHub; run it once
  outside the sandbox (or with networking enabled). After `vendor/` exists the unit
  suite is fully offline.

> The analysis gates (`stan` / `cs` / `deptrac`) are also present in the allow-list
> from harness setup. If you want strict unit-only agent access, trim those entries
> from `.claude/settings.local.json`.

---

## 8. CI pipeline (AD-8)

`.github/workflows/ci.yml` runs on every PR and on push to `main`, PHP 8.3:

```
composer install
  → PHPStan          (composer stan)
  → PHPCS / WPCS     (composer cs)
  → Deptrac          (composer deptrac — incl. the Entitlement ⟂ Tiers conflation rule)
  → Pest unit        (composer test:unit)
  → wp-env integration  (composer test:integration; full on PRs to main, per NFR-9 risk-based depth)
  → Playwright E2E   (placeholder — built in Story 18.8)
```

The stage order is load-bearing: lint/analyse/architecture rules gate **before**
tests. The conflation rule is a build-**failing** Deptrac invariant. **PayFast uses
the sandbox only** — the live ZAR gateway is never hit in tests.

---

## 9. Integration & E2E (deferred to Story 18.8)

The integration harness is **scaffolded but not built out**:

- `.wp-env.json` and `tests/Integration/` exist; one placeholder
  (`CommentInsertionTest`) is `->skip()`-marked.
- Running it needs **Docker** (not yet installed locally) and **Node 20**.
- The full integration pyramid, the Playwright E2E suite + critical journeys
  (register → PayFast sandbox → submit → publish → react → renew), coverage gates,
  and the `tools/leak-scan` English-leak gate (NFR-1, Story 17.4 / Epic 18) all land
  in **Story 18.8** — not before.

---

## 10. Troubleshooting

| Symptom | Cause / fix |
|---------|-------------|
| `Failed to listen on "tcp://127.0.0.1:0": Operation not permitted` (PHPStan) | The sandbox blocks PHPStan's parallel worker TCP server. Run `composer stan` with the sandbox disabled, or rely on CI. |
| `PHPStan process crashed because it reached configured PHP memory limit: 128M` | The `composer stan` script passes `--memory-limit=1G`; if invoking `phpstan` directly, add it yourself. |
| `INK_CORE_PATH ... constant.notFound` in PHPStan | `phpstan-bootstrap.php` (via `bootstrapFiles`) must be present and referenced — it declares the plugin constants. |
| Unit run aborts silently with no failures | A source file's `defined( 'ABSPATH' ) || exit;` guard fired. Ensure `tests/bootstrap.php` defines the sentinel `ABSPATH`. |
| `qossmic/deptrac-shim` fails to install / is deprecated | Swap to the Deptrac PHAR or `phparkitect/phparkitect`; the `deptrac.yaml` ruleset is runner-agnostic. |
| `npx @wordpress/env start` fails | Needs Docker running and Node 20. Integration is deferred to Story 18.8 — not required for the four PHP gates. |
| `composer install` network errors | Needs Packagist + GitHub access; run outside a restrictive sandbox once. |

---

## 11. Related documents

- `tests/README.md` — the in-tree layout + conventions summary.
- `_bmad-output/implementation-artifacts/1-11-test-harness-scaffold.md` — the story
  that built the scaffold.
- `_bmad-output/planning-artifacts/architecture.md` — AD-1 (module graph), AD-4
  (build topology), AD-8 (CI pipeline), AD-9 (notifications), test-stack section.

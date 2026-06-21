# Tests — ink-vibe harness (NFR-9)

The test + quality harness is **repo-root** (architecture.md lines 851, 937–967), not plugin-local. Stories 1.8 and 1.10 authored their unit tests ready-to-run and parked them under `wp-content/plugins/ink-core/tests/` as a placeholder *while no harness existed*; **Story 1.11 stood up the harness and relocated them here.** That plugin-local location is superseded — all tests live under this repo-root `tests/` tree.

## Layout (mirrors `wp-content/plugins/ink-core/src/`)

```
tests/
├── bootstrap.php              # Unit bootstrap: Composer autoload + Brain Monkey + WP_User double
├── stubs/class-wp-user.php    # Minimal WP_User double for the mocked unit suite
├── Unit/{Module}/             # Pest + Brain Monkey / WP_Mock — WP fully mocked
└── Integration/
    ├── bootstrap.php          # wp-env WP test-library seam (buildout: Story 18.8)
    └── {Module}/              # real WP + DB via wp-env
```

E2E (`tests/e2e/`, Playwright) is built in **Story 18.8** with the full pyramid.

## Conventions for later stories

- **Unit** (`tests/Unit/{Module}/`): mock WordPress with Brain Monkey; no DB; namespace `Ink\Tests\Unit\{Module}`.
- **Integration** (`tests/Integration/{Module}/`): real WP via wp-env; namespace `Ink\Tests\Integration\{Module}`.
- P0-rule homes: tier promotion (5.x) → `tests/{Unit,Integration}/Tiers/`; submission gate (6.8) → `tests/{Unit,Integration}/Submission/` (+ the `Entitlement` seam); follow graph (9.2) → `tests/{Unit,Integration}/Social/`.
- PSR-4: `Ink\Tests\` → `tests/` (repo-root `composer.json` `autoload-dev`).

## Running

```bash
composer install            # builds vendor/ (dev tooling: Pest, Brain Monkey, PHPStan, WPCS, Deptrac)
composer test:unit          # Pest unit suite (WP mocked)
composer stan               # PHPStan
composer cs                 # PHPCS / WPCS
composer deptrac            # AD-1 module graph + Entitlement ⟂ Tiers (conflation rule)

npx @wordpress/env start    # boot WordPress 7.0 / PHP 8.3
composer test:integration   # Pest integration suite (real WP)
```

CI (`.github/workflows/ci.yml`, AD-8) runs these in order: `composer install` → PHPStan → PHPCS → Deptrac → Pest unit → wp-env integration; Playwright E2E is a deferred placeholder (Story 18.8). PayFast uses the **sandbox** only.

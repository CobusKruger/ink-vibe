# Capture Harness — Worklist

The capture harness is the tooling behind Phase 4 / 4b (`README.md` §4.1). This file is
the authoritative source for the item IDs (`A1`, `E10`, `F15`, …) used elsewhere.

**Status:** the harness captures, diffs and passes its determinism gate on both sides.
Four items remain before the Stage 1 audit can start at scale.

---

## Decisions (settled)

| | Question | Ruling |
|---|---|---|
| D1 | INK target | `nuwe-ink.local`. **Never `ink-staging`** — that is a copy of the site being replaced. |
| D2 | Lovable source | Local. Production build via `vite preview`, **not** `vite dev` — `componentTagger()` injects dev-only DOM attributes. |
| D3 | Breakpoints | 390 / 768 / 1440 |
| D4 | Colour scheme | Light only. Dark (`styles/dark.json`) possibly later. |
| D5 | Auth captures | Required. |

---

## A · Environment

| | Item | Status |
|---|---|---|
| A1 | Install Playwright + chromium | ✅ done |
| A2 | Both targets serving | ✅ done — INK 200, Lovable preview 200 |
| A3 | Content-state assertion | ✅ done — `--expect` aborts rather than writing an empty capture |
| A4 | Deterministic clock | ⬜ not needed so far; revisit if a deadline string shifts layout |

## B · Capture core

| | Item | Status |
|---|---|---|
| B1 | `tools/fidelity/capture.mjs` | ✅ done |
| B2 | Node record schema | ✅ done — path, tag, classes, audit-id, bbox, own-text, computed subset |
| B3 | Computed-property allowlist | ✅ done — 51 properties |
| B4 | `--mode all` / `--mode keyed` | ✅ done |
| B5 | Targets file | ✅ done — `tools/fidelity/targets.json`, 22 rows from `agent-brief.md` §2.1 |

## C · Determinism

| | Item | Status |
|---|---|---|
| C1 | Animations driven to end state | ✅ done — zero duration, **not** `animation: none`, which would strip a fade-up's final frame |
| C2 | `document.fonts.ready` | ✅ done |
| C3 | Fixed viewport, DPR 1, explicit colour scheme | ✅ done |
| C4 | Force lazy images, return to scroll origin | ✅ done |
| C5 | **Determinism gate** | ✅ **PASSED** — 3 consecutive captures byte-identical, both sides. `tools/fidelity/determinism-check.sh` |

## D · Auth profiles

| | Item | Status |
|---|---|---|
| D6 | INK `storageState` for a logged-in user | ✅ done — `fidelity-capture` (subscriber, admin bar off), `run.sh login`, `/my-profiel` captured authenticated |
| D7 | Lovable Supabase session `storageState` | ⏸️ **owner** — needs an account on the hosted Supabase project. Low value: only the header's two buttons change. |
| D8 | Capture matrix | ✅ settled — anon everywhere; row 02 INK-authed with the header excluded from that diff |

Auth is not confined to the account pages: the header branches on auth state on **every**
page (`Header.tsx:60-102`, `header-main.php:83-95`), so every capture is affected.

## E · Consumers

| | Item | Status |
|---|---|---|
| E9 | `tools/fidelity/diff.mjs` | ✅ done — joins on audit-id, severity-ordered Markdown |
| E10 | `tools/fidelity/extract-primitives.mjs` | ✅ done — 110 instances / 11 kinds from the homepage alone. Emits `page,side,viewport,component_kind,selector,declarations,audit_id`. |
| E10b | Source attribution — `defined_in` / `load_scope` / `used_in` | ⬜ **open** — a rendered capture cannot say which stylesheet a rule came from. Needs CDP `CSS.getMatchedStylesForNode`, or a source-side pass. Columns are emitted empty for now. |
| E11 | Self-verification sampler | ✅ done — `run.sh verify`. Both sides: 30 nodes, 1560 properties, **0 mismatches**. |
| E12 | Output conventions | ✅ done — `tmp/fidelity-captures/<side>/<page>@<vp>[-auth].ndjson` |

### Noise suppression in `diff.mjs`

A noisy diff teaches an agent to discount real findings, so false positives are suppressed
in the tool rather than left to a human.

| | Source | Status |
|---|---|---|
| E9a | Colour notation — Chromium emits `rgba(…)` or `color(srgb …)` for the same colour | ✅ canonicalised |
| E9b | Phantom borders — Tailwind preflight sets a border colour on every element, WordPress does not | ✅ suppressed when neither side draws a border |
| E9c | Systemic properties — `box-sizing`, `max-width` differ on *every* node (Tailwind preflight vs WP constrained layout) | ✅ done — a delta identical on ≥80% of paired nodes collapses to one architectural note |

## F · Docs wiring

| | Item | Status |
|---|---|---|
| F13 | README Phase 4/4b points at the tools and names the two gates | ✅ done |
| F14 | agent-brief points at the harness and its gates | ✅ done |
| F15 | Tool contract — flags, env, sandbox | ✅ done — this file plus `README.md` §9 |

---

## Environment facts

Three facts make the harness work. Each produces a different confusing failure if missed;
`tools/fidelity/run.sh` sets all three.

1. **Node 20+.** Playwright 1.63 hard-refuses on the machine default (18.17.1, via `n`).
   `v24.12.0` is already installed under nvm. This does **not** change the default.
2. **`PLAYWRIGHT_BROWSERS_PATH`.** The sandbox denies `~/Library/Caches/ms-playwright`;
   browsers live in repo-local gitignored `tmp/ms-playwright`.
3. **`NODE_PATH`** — the tools resolve `playwright` from the repo's `node_modules`.

**Sandbox.** Two operations cannot run inside the macOS Seatbelt sandbox, and neither is
fixable with an allowlist entry:

- Chromium is denied its Mach rendezvous port (`bootstrap_check_in … Permission denied`)
- a preview server cannot bind a socket (`listen EPERM`)

`/sandbox` is unavailable in this environment. The commands are instead pre-approved as
permission rules in `.claude/settings.local.json`.

**Architecture.** Verified genuine `x86_64` with an `x64` node and a `mac-x64` Chromium —
no Rosetta translation, so no font-metric drift from an arch mismatch. Re-check this if the
harness is ever run on Apple Silicon.

---

## Commands

```bash
# capture one page
tools/fidelity/run.sh capture \
  --url https://nuwe-ink.local/ --side ink --page tuisblad \
  --viewport 1440 --expect '[data-audit-id="hero-h1"],.ink-uitgesoekte-bydraes'

# diff reference against INK
tools/fidelity/run.sh diff \
  --a tmp/fidelity-captures/lovable/tuisblad@1440.ndjson \
  --b tmp/fidelity-captures/ink/tuisblad@1440.ndjson \
  --out docs/fidelity-remediation/findings/00-tuisblad-drift.md

# the determinism gate — run before trusting any capture
tools/fidelity/determinism-check.sh https://nuwe-ink.local/ ink tuisblad 1440 3

# start the Lovable reference server (needs sandbox off; binds :4173)
npm --prefix ../ink-lovable run build
npm --prefix ../ink-lovable run preview -- --port 4173 --strictPort
```

---

## Blocking the Stage 1 audit

**Nothing.** The harness is complete and both gates pass. Stage 1 can begin.

Two items remain open but block nothing:

- **`D7`** — a Lovable logged-in session. Needs an account on the hosted Supabase project,
  which is an external service, so it is an owner decision rather than something to create
  unilaterally. No Lovable page gates on auth, so the only difference is the header's two
  buttons.
- **`E10b`** — source attribution for the primitive register (below).

**`D6`–`D8` are not blockers.** `useAuth` is consumed in only four Lovable files —
`App.tsx`, `Header.tsx`, `useAuth.tsx`, `Auth.tsx` — so **no Lovable page gates on auth**;
`Profile.tsx` renders from mock data. Only the header's two buttons change. On the INK
side only `/my-profiel` (row 02) needs a real session. Plan: capture both sides anonymous
everywhere, and for row 02 capture INK authenticated while excluding the header from that
page's diff, since the header is already covered by row 00.

**`E10b` is not a blocker either**, but it limits what Stage 2 can do unaided: the register
will carry *what* each instance computes to, but not *where* it is defined. Stage 2 can
still group and diff variants; attributing each to its stylesheet needs either E10b or the
source-side method already demonstrated in `findings/01-primitives-audit.md`.

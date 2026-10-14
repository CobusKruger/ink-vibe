# 12 · vier-nul-vier (404) — primitive register (Phase 4b)

**Page:** `https://nuwe-ink.local/storie/pannekoek/` (HTTP 404) · **Treatment:** C · **Date:** 2026-10-13 · **Stage 1**
**Register:** `maps/_primitive-observations.vier-nul-vier.csv`, **header only: zero rows.**
**Captures:** `tmp/fidelity-captures/ink/vier-nul-vier@{390,768,1440}.ndjson` — determinism gate 3/3 identical and
`verify --sample 30` 0 mismatches at every width. No duplicate audit-ids.

## Files read end to end

- `templates/index.html` (the fallback: there is no `404.html`), `parts/header.html`, `parts/footer.html`
- `functions.php` (all of it). No filter targets the 404 / `index.html` output.

## Result

`run.sh primitives --exclude-chrome` returns **no rows**. Outside the header and footer (row 00), the page renders
only `<main>` (`vier-nul-vier-main`, padding s-64 / s-24) and an empty `alignwide` group (`vier-nul-vier-inner`,
0px tall). `core/post-title` and `core/post-content` emit nothing on a 404. There is no heading, button, link or
card to register, so no source attribution or load-scope check applies.

The Lovable `NotFound.tsx` reference (captured for orientation, not diffed) would contribute a `heading` (h1 "404",
36px / 700) and a `link-underline` ("Return to Home"). It was **not** added to the register: Treatment C has no
reference side (README §4.6), so `lovable` rows would have no counterpart to be compared with in Stage 2. The missing elements are noted in `findings/12-vier-nul-vier-conformance.md` → *structural notes*.

# 12 · vier-nul-vier — Stage 4 (Treatment C)

INK `/storie/pannekoek/` (any unknown URL) · Lovable `NotFound.tsx` (reference) · 2026-10-23

| Finding | Status | Fix (file:line) |
|---|---|---|
| Live bug 7: 404 falls back to `index.html`, empty `<main>`, no heading/message/home link | fixed (structure) | `themes/ink-foundation/templates/404.html:1-9`; `patterns/vier-nul-vier.php:25-39` |
| Frame: Lovable `min-h-screen` centred band on `bg-muted` | fixed (100vh, `muted`, flex column centred, 48/16 padding) | `vier-nul-vier.php:25-26` |
| Heading "404": Lora 36/40 700, `mb-4` | fixed (numeric, no copy) | `vier-nul-vier.php:27-29` |
| Message: 20/28 `muted-text`, `mb-4` | fixed (structure); copy = placeholder | `vier-nul-vier.php:31-33` |
| Home link: 16/24 primary, underlined | fixed (structure); copy = placeholder | `vier-nul-vier.php:35-37` |
| Message + link copy (no authored Afrikaans) | owner (copy debt `404-BOODSKAP`, `404-TUIS-SKAKEL`) | `docs/afrikaans-translation-sheet.md` §13; `docs/afrikaans-copy-worklist.md`; baseline +2 in `tools/leak-scan/placeholder-baseline.json` |
| Header/footer kept (Lovable 404 has none) | owner (same call as auth chrome) | — |
| English `<title>` "Page not found - INK" (core string) | owner (copy / language pack) | — |
| Heading recipe has no primitive (36/40 700 at every width) | accepted (block typography attributes; primitive request to lead) | — |
| OFF-RECIPE / OFF-TOKEN | conforms: every value is a token (`xxxxl`, `xl`, `md`, `muted`, `muted-text`, `primary`, `s-16`, `s-48`) | — |

## Phase 4 re-run (INK capture vs Lovable NotFound values, deployed 2026-10-23)

- 1440: clean — section 900 tall `#F3F0ED`; h1 36/40 700 Lora; message 20/28 muted; link primary underlined; 16px gaps.
- 768: clean, same values.
- 390: clean, same values (placeholder copy wraps to 3 lines).

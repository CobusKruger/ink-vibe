# 15 · kontak — Stage 4 (Treatment C, OFF-RECIPE)

INK `/kontak/` checked against its recipes. The form controls use the `/auth` field-and-button recipe (stage-2-rulings "Forms without a Lovable equivalent"). Method: the kontak capture was remapped onto the `/auth` ids (`kontak-form→auth-form`, `-field→auth-email-field`, `-label→auth-email-label`, `-input→auth-email-input`, `-submit→auth-submit`) and diffed with `run.sh diff` against a fresh Lovable `/auth` capture from :4173. Scratch: `tmp/stage4/forms/` (`remap.py`, `diff3/kontak-offrecipe@*.md`).

| Finding | Status | Fix (file:line) |
|---|---|---|
| Field / input / label / submit vs the /auth recipe (40px, 14/20, 8/12 padding, r6, border, serif submit) | conforms | no change; Stage 3 migration holds |
| Label line-height 20 vs /auth 14 (field 68 vs 72px) | accepted | LBL-1 (20px) |
| Submit box-shadow vs none on Lovable `/auth` | accepted | `maps/_recipes.csv:559` (button-primary shadow.md, cites `meld-aan:auth-submit`); lead check: the 4173 `/auth` submit renders no shadow |
| OFF-RECIPE: left-aligned eyebrow used the centred `--tracked` variant (12/16, 0.2em) | fixed | `patterns/kontak.php:31-32` → base `ink-eyebrow` (14/20, 0.05em), per EB-1 |
| OFF-TOKEN (Stage 1) | clean | 0 instances (`15-kontak-conformance.md`) |
| Result notice (`?ink_kontak=…`) not captured | accepted | `ink-notice` primitive (NR-2); a capture needs a form round-trip |
| Field/textarea/submit width = 640 column vs /auth 382 card | accepted | page layout, not a recipe value |

**Phase 4 re-run** (verify 30/30 at 390/768/1440; determinism gate 390 passed).
- **390:** clean apart from LBL-1, the recipe shadow and mechanism (margin on label vs input).
- **768:** as 390.
- **1440:** as 390.

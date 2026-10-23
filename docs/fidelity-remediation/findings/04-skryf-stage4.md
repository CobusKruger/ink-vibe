# 04 · skryf — Stage 4 (Treatment A)

INK `/skryf/` (authed as `fidelity-capture`) diffed against Lovable `/write` on :4173: the picker (initial render) and the editor (`--click skryf-type-card --state editor`). Header/footer ids are unkeyed before diffing. Scratch: `tmp/stage4/forms/`.

| Finding | Status | Fix (file:line) |
|---|---|---|
| Live bug: literal backslash "Skep \’n…" / "Deel \’n…" (`\&#8217;` in a single-quoted string) | fixed | `patterns/skryf.php:23-24` (escaping only, words unchanged). Same bug in `patterns/onboarding.php:76,91` (not this page) — listed for the lead |
| Page shell: section py 64 / px 24, content 896 vs `main py-8 md:py-12` + `container px-4 max-w-4xl` (864) | fixed | `skryf.php:57-58` (px 16, contentSize 864); `assets/css/skryf.css:19-29` (py 32 → 48) |
| Lede 24px under h1 vs `mb-2` (8) | fixed | `skryf.php:124-125`; `skryf.css:36` |
| Challenges panel → title gap 16 vs `mb-6` (24) | fixed | `skryf.css:182` |
| Challenges title→hint 4px; hint not in the title column | fixed | `skryf.css:186-191`, `:200` (legend mb 0, hint indented 32) |
| Title input 18/28 at ≥768 vs 14/20 (shadcn `md:text-sm` beats `text-lg`) | fixed | `skryf.css:143-154` |
| Textarea 453px / lh 1.7 vs `min-h-[400px] leading-relaxed` (400 / 26px) | fixed | `skryf.css:172-176` |
| Success view: card capped at 640 vs full `max-w-5xl` container; lead 480 vs `max-w-xl` | fixed | `skryf.css:31`, `:257`, `:276` |
| Success: icon→h1 24 vs 16; h1→lead 24 vs 8 | fixed | `skryf.css:267-274` |
| Success: engage text 16/24 vs `text-sm mt-1`; h2 24 vs `mt-2` | fixed | `skryf.php:97-102`; `skryf.css:309-316` |
| Success: CTAs not full-width stacked below 640 (`flex-col sm:flex-row`) | fixed | `skryf.css:288` |
| S-2 one form vs two-step picker → editor (type indicator, "Change type", selected type-card state) | owner | Q-S2 |
| S-4 success "Lift another writer today" 3-card grid (NOT BUILT) | owner | disclosed `skryf.php:105-117`; needs a selection rule |
| S-9 per-challenge deadline line (row 46 vs 64px) | owner | needs `Submission\Api::formModel()` data + copy |
| S-11 per-type body label | owner | needs Afrikaans copy (R2) |
| S-13 / S-16 button icons (Save, Send, Feather) | owner | Lovable `Write.tsx:409,419,171` |
| S-1 login gate; S-12 file pickers; S-5 "Kies 'n tipe" legend | accepted | SCOPE (spine) |
| S-3/S-7/S-10/S-14 wrapper shape; padding on section not `main`; field margins vs flex gap | accepted | mechanism; computed gaps now match |
| Draft button serif vs Lovable Inter | accepted | stage-2-rulings "Button font" |
| Publish button shadow vs none on Lovable `/write` | accepted | `maps/_recipes.csv:559` (button-primary shadow.md); lead check: Lovable default-variant submits (`/write`, `/auth`) render no shadow |
| Checkbox checked tick drawn as background-image vs Radix svg child | accepted | `ink-checkbox` primitive; checked row border + primary/5 fill match (captured `--click skryf-checkbox-input --state checked`) |
| Carried: success card / denial notice / checked row unreachable | verified | checked row: captured (above). Denial: captured via `?ink_skryf=geen-toegang` — matches the notice recipe (NR-2, 12/16, r6, 14/21). Success: forced by injecting the `skryf.php:65-103` markup into the live page (`tmp/stage4/forms/success.mjs`) at 390/1440; values above now match `Write.tsx:155-193` |

**Phase 4 re-run** (ink verify 30/30 at 390/768/1440; determinism gate 390 passed).
- **390:** editor: only mechanism (margin placement), the S-2/S-7/S-9/S-13 structure deltas, and content height from INK-only fields. Picker: S-2 selected state + legend only.
- **768:** as 390; title input now 14/20.
- **1440:** as 768; container 864, form/textarea widths and heights match.

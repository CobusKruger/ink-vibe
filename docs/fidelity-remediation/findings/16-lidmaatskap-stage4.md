# 16 · lidmaatskap — Stage 4 (Treatment C, OFF-RECIPE)

INK `/lidmaatskap/` checked against the recipes for its components: index intro (`section-band,intro`, `heading,h1-index`, as on `/ontdek` and `/ledegids`), card base, button-primary lg, h2-section (HR-1), cream band (SB-3). Scratch: `tmp/stage4/forms/cap3/`.

| Finding | Status | Fix (file:line) |
|---|---|---|
| Live bug: literal backslash "Volle toegang vir \’n maand/jaar" | fixed | `patterns/lidmaatskap.php:38,40` (escaping only, words unchanged) |
| Live bug: all three plans R50,00 → same `add-to-cart=926` | owner (data) | Not code. Option `ink_membership_plan_products` = `{1:926, 6:926, 12:926}`, and the only lidmaatskap product is #926 "Ink Publishing Lidmaatskap" (R50). `MembershipPlans::productIdFor()` reads the map as designed. Fix = create the 1/6/12-month WooCommerce products and remap the option (Q-L1). Also affects row 02's renewal plans |
| OFF-RECIPE intro: no cream band or bottom rule, py 64/24, lede mt 12 and lh 1.5, h1 centred in a 768 column | fixed | `lidmaatskap.php:69-79` (`ink-band--intro --flush`, left-aligned); theme.json `.ink-lidmaatskap-hero*` (inner py 56 → 80, lede mt 24, lh 1.625, max-w 768) |
| 24px blockGap between sections | fixed | `templates/page-lidmaatskap.html:3` (blockGap 0) |
| Section bands: ad-hoc py 24/48, 48/48, 24/64 and px 24 | fixed | `lidmaatskap.php:85-86, 157-158, 185-186` → `ink-band ink-band--sm` (48), px 16 |
| Benefits/FAQ h2 = h2-block (24) for section headings | fixed | `lidmaatskap.php:161-162, 189-190` → `ink-heading-h2-section` (HR-1, 30/36 → 36/40) |
| Benefits/FAQ centred in 768 while the intro and plan grid sit at the wide left edge | fixed | `lidmaatskap.php:159-160, 187-188` (no `layout`); theme.json `.ink-lidmaatskap-kolom>*` max-w 768 |
| CTA band solid `secondary` vs SB-3 `secondary/30` | fixed | `lidmaatskap.php:221-222` → `ink-band ink-band--cream` |
| Plan card (r8, 1px border, shadow-sm, p24), plan button (lg 44, serif), FAQ card | conforms | — |
| Plan price 24px bold (no recipe) | accepted | page-specific (`theme.json` `.ink-lidmaatskap-plans p.has-xxl-font-size`) |
| OFF-TOKEN (Stage 1) | clean | 0 instances (`16-lidmaatskap-conformance.md`) |
| FAQ open state, disabled-plan branch | not captured | unchanged markup; primitives only |

**Phase 4 re-run** (verify 30/30 at 390/768/1440; determinism gate 390 passed).
- **390:** clean against the recipes above.
- **768:** as 390.
- **1440:** as 390; the intro matches `/ledegids` (inner py 80, lede 18/29.25, mt 24).

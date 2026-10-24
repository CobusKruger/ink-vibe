# Production menu → ink-vibe mapping

Source: [ink-prod-menu.md](ink-prod-menu.md) (dump of the live ink.org.za menu). Decisions below were made by the product owner on 2026-10-24.

## Target top-level navigation

Tuis · Ontdek · **Biblioteek** · Opleiding · Uitdagings · Gemeenskap · My profiel

Biblioteek is new in the header nav, placed next to Opleiding. The header pattern is `wp-content/themes/ink-foundation/patterns/header-main.php`. Oor INK, Lidmaatskap and Kontak are reached from the footer and calls to action, not the header.

## Mapping

| Production | ink-vibe target | Notes |
|---|---|---|
| Tuis | `front-page` | Exists. |
| Lede | `page-ledegids` + Ontdek **Skrywers** tab | Exists. |
| Projekte & Kompetisies (+ Oktober 2026 projek) | Uitdagings: `archive-uitdaging` / `single-uitdaging` | Exists. |
| Eksterne Kompetisies | Part of the competition design | Pending the Lovable design. |
| Lede Bydraes (Gedigte, Verhale, Prosa) | Ontdek **Bydraes** tab (`gedig`, `storie`, `artikel`) | See "Lede Bydraes". |
| Lees meer oor INK | `page-oor-ink` | Exists. |
| INK se Gala-Aande (10 galas + Fotos) | New gala pages | Pending the Lovable design. |
| Biblioteek | `archive-biblioteek_item` | Exists; becomes a top-level nav entry. |
| Biblioteek › Projek wenners | **Ontdek**, new third tab (dynamic query) | See "Projek wenners". |
| Biblioteek › Woordeboeke | Biblioteek shelf | Moves from Opleiding. |
| Opleiding | `archive-opleiding_artikel` | Exists. |
| Skenkings & Donasies | Not decided | Open. |
| Boekwinkel | Skipped | The production page is broken; revisit later. |

## Lede Bydraes → Ontdek

- The production Verhale sub-categories (Geskiedenis, Jeug/Kinders, Kortverhale, Liefde, Liegstories) are **not** carried over as genres. Nobody browses them and nobody tags a genre today.
- Migration: map all old `/kategorie/lede-bydraes/...` URLs to the Ontdek **Bydraes** tab (301). Content type (Gedig / Verhaal / Artikel) stays the source of truth for CPT mapping, as in `migration-plan.md`.
- Prosa folds into `storie` / `artikel` (to confirm during migration).

## Projek wenners → Ontdek (third tab)

- Ontdek gets a third tab next to Bydraes and Skrywers.
- It is a **dynamic query**, not a Biblioteek collection, so the winners are not curated or duplicated by hand.
- It is removed from the Biblioteek shelves.
- The query source (the challenge winners / `ster_gradering` / `uitdagingsrondte` data) is settled at implementation.
- Redirects: `/biblioteek/projek-wenners/...` (including the `[tier]/[slug]` pattern in `migration-plan.md`) must point to the new tab.

## Biblioteek

Biblioteek holds curated reference and collection material, not member contributions. The archive's top-level **shelves** (filter strip or section headings at the top of the page):

1. **Gedigte**: poems that do not come from site contributors. Stored as `biblioteek_item`, **not** the `gedig` CPT, so they never appear in Ontdek or on author profiles.
2. **Woordeboeke**: moved from Opleiding. Stored as `biblioteek_item`.
3. **Collections / series**:
   - Liegstories
   - Oom Pine se jagstories
   - Flipvis se verhale
   - Gert Rossouw se briewe aan Celeste
   - FAK elektroniese sangbundel & Kitaardrukke
   - Vergete Helde uit die geskiedenis
   - Vrystaatstories (Henning van Aswegen)
   - Kinderliedjies (nested under FAK in production; decide its parent)
   - Kinderrympies / Vingerversies

### Woordeboeke

- Items: Woordeboek – WAT, Drietalige idoom woordeboek (pdf), E-woordeboeke, Letterkundige terme Woordeboek, Dignet Woordeboek.
- Production lists Letterkundige terme Woordeboek twice (under Digkuns and under Woordeboeke). Keep it only under Biblioteek and drop the Digkuns copy.
- Old `/opleiding/woordeboek-wat/`, `/opleiding/gedigte/letterkundige-terme-woordeboek/` and similar URLs get 301s to the new Biblioteek URLs.
- Some of these are probably link-outs rather than articles. Confirm per item.

## Open items

- Skenkings & Donasies: new page, or part of Oor INK / Lidmaatskap?
- Gala-Aande and the competition pages: wait for the Lovable design (then run the lovable-design-sync skill and add to `design-handoff/page-map.csv`).
- Kinderliedjies parentage.
- Boekwinkel: revisit once the production page is fixed.

## Follow-up work

1. Add Biblioteek to `header-main.php`.
2. Add the third Ontdek tab (Projek wenners) in `patterns/ontdek.php`, with a server-rendered block in `ink-core/Discovery`.
3. Add the shelf filter (Gedigte, Woordeboeke, collections) to the Biblioteek archive block in `ink-core/Library`.
4. Update the redirect map for the moved URLs (Migration module).
5. Update `design-handoff/page-map.csv` and `migration-plan.md`.

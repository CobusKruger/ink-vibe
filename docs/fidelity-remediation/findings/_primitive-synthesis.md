# Primitive synthesis — Stage 2

**Status:** Ratified 2026-10-14. Owner rulings: [`../stage-2-rulings.md`](../stage-2-rulings.md).

The input is the merged register `maps/_primitive-observations.csv`: 1,896 rows, 38 kinds,
22 pages. Three agents each synthesised one group of kinds. Their detailed reports hold,
per kind, the variant count, a property-level diff and the justification for the chosen
recipe:

| Group | Kinds | Report | Recipe rows (all canonical / of which owner-ruling) |
|---|---|---|---|
| type-layout | heading, section-band, eyebrow | [_primitive-synthesis.type-layout.md](_primitive-synthesis.type-layout.md) | 155 (155 / 45) |
| surfaces | card, pill, meta-item, avatar, tier-chip, icon-tile, badge, tier-label, meta-row, accent-block, step-badge, count-chip, status-dot, notice, list-bullet, divider | [_primitive-synthesis.surfaces.md](_primitive-synthesis.surfaces.md) | 360 (360 / 30) |
| controls | button-* (8 kinds), search-button, filter-chip, tab, nav-link, link-underline, form-field, form-label, form-hint, search-field, checkbox, choice-row | [_primitive-synthesis.controls.md](_primitive-synthesis.controls.md) | 230 (230 / 41) |

The merged recipe table is [`maps/_recipes.csv`](../maps/_recipes.csv), with 745 rows (0
`needs-ruling`) and this header:
`component_kind,variant,property,viewport,value,source,evidence,status,note`.
The per-group CSVs `maps/_recipes.<group>.csv` are its sources. Edit those, then re-merge.

## Headline

- **Nearly every canonical recipe is backed by a Lovable measurement.** Only `tier-label`
  and `notice` have no Lovable reference.
- **Drift is systemic, not per-page.** A few INK-wide causes account for most variants:
  1. **Line-height 1.5 on small text.** Lovable sets fixed leading on xs/sm/lg text
     (16/20/28px), while INK uses 1.5. Small text, controls and chips render 1–2px taller.
  2. **Missing font weights.** `theme.json` sets no heading weight, so headings render at
     700. Lovable uses 600 (400 on some pages; HR-1 rules 600). Registered button styles set
     no weight either, so those buttons render at 400 instead of 500.
  3. **Fluid size presets.** The xxl/xxxl/hero sizes produce in-between values Lovable
     never uses, and one element can be sized by up to four layers.
  4. **No shared button base.** All Lovable buttons share one base: inline-flex, gap 8,
     weight 500, radius 6, and four sizes. INK restates button styles per consumer.
     Font: serif on every button (BTN-FONT), overriding Lovable's Inter base.
  5. **content-box sizing.** Bordered avatars render 4–8px too large.
  6. **Shadows at rest.** INK puts `shadow.sm` on every card; Lovable cards have none at
     rest.
  7. **Token drift.** Several INK palette values differ from Lovable's (TOK-1: adopt Lovable's).
- **Container gutter:** Lovable uses 16px, INK 24px. This is canonical (Lovable), not a
  ruling.

## Owner rulings (applied)

Full text: [`../stage-2-rulings.md`](../stage-2-rulings.md). Rows decided by a ruling carry
`source=owner-ruling`, `evidence=stage-2-rulings.md <ID>`. LEG-1/2, BTN-FONT and FORMS are
labels for that doc's unnumbered sections.

| ID | Ruling |
|---|---|
| TOK-1, BTN-2, BTN-3 | General rule: Lovable wins on colour, spacing, size, weight, leading. Lovable palette + shadow-sm/-lg; no pill buttons; neutral outline hovers to sage. |
| HR-1 | Section headings 36px, weight 600; `*-regular` variants collapse to 600. |
| HR-2 | Work-card titles weight 400 (20 / 27.5). |
| HR-3 | Home hero and community hero are distinct; keep both. |
| HR-4 | CTA-band heading 30 / 36 / 48; white on brand, cream on ink. |
| SB-1 | Lovable's per-section padding; reading pages tighter (48). |
| SB-3 | Cream bands `bg-secondary/30` = `rgba(240, 235, 230, 0.3)` everywhere. |
| EB-1 | Keep both eyebrows (centred tracked, left-aligned base). |
| EB-2 | Stat labels 12px uppercase. |
| BTN-1 | CTA buttons serif 18px, 48px high. |
| LEG-1 | "Explore stories": transparent, 2px white border, white text; hover white fill, terracotta text. Fixed in Lovable. |
| LEG-2 | "Read Full Story": `border-gold/40`, filled hover `bg-gold text-ink border-gold`, serif. Fixed in Lovable. |
| BTN-FONT | Every button is serif; not chips, tabs, the Reply text button or counted icon buttons. |
| LBL-1 | Form labels line-height 20px. |
| NR-1 | Tier labels: brons / silwer / goud tokens, 10% fill, 20% border (sage-chip pattern). |
| NR-2 | Keep INK's notice styling. |
| NR-3 | Sage tints 10%. |
| NR-4 | Small meta-item icon gap 6px. |
| SRCH-1 | Keep "Soek" as a brand-primary button; input and button both 40px. |
| CHIP-1 | Ontdek date chip = sage chip (`Challenge.tsx:214`). |
| FORMS | Contact form and any field without a Lovable sibling use the `/auth` field and button recipe. |

### Owed by Stage 3/4

| Item | Note |
|---|---|
| NR-2 script | A small script that sets the Afrikaans required-field validity message "Vul hierdie veld in" (`setCustomValidity`) on every form with `required` fields. No CSS rows. |
| Proposed values | Flagged `PROPOSED` in notes: tier-label chip padding (2px 10px), date-chip active state (solid sage). Silwer/goud tier text fails AA at 12px (flagged). |
| `border-bottom` | Still not in the capture property set. `background-image` always was (capture.mjs since 86ce11e); the gradient band is now measured. |

## Vocabulary changes proposed

Not applied; decided at Stage 3 kickoff, when `primitives.css` class names are chosen.
The register and recipes still use the Stage-1 kinds.

- **heading:** add a role dimension (h1-index, h2-section, …). The recipes already use the
  role as the `variant`.
- **section-band:** split it into the band surface and a new `container` kind. Re-file
  about 51 rows that are not bands: header, footer, tabs, the cta panel, the auth wrapper
  and shells.
- **eyebrow:** split `stat-label` out. The card eyebrow may be `meta-item`, and the caps
  form label is closer to an eyebrow than to a form-label.
- **button-size:** rename it to `button-base`. Split `button-outline` into a 2px primary
  outline and a 1px neutral outline. The bare "Reply" text button is not a ghost button.
  `button-icon` holds at least four components. `button-icon-label` mixes a layout
  modifier with the engagement counters.
- **link-underline:** misnamed, because the reference links are not underlined at rest.
  `filter-chip` holds four sub-recipes.
- **Surfaces:**
  - `pill`, `badge` and `count-chip` are one component.
  - Drop `sheet`, `dialog` and `empty-state` as separate card kinds.
  - The engagement bar filed as a pill is a toolbar.
  - `.ink-gradering` is its own kind, separate from tier-label.
  - The circle kinds (avatar, status-dot, step-badge) share one shape.

## Regenerating

The group scripts live in `tmp/stage2/<group>/` (gitignored, re-runnable). The merge step
checks the 9-column header and concatenates the groups in the order type-layout, surfaces,
controls. The rulings were applied by `tmp/stage2/close/apply_rulings.py` (`--write`,
sandbox off), which re-merges the same way; its captures are in `tmp/stage2/close/captures/`.

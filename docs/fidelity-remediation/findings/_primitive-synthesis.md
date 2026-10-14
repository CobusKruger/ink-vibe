# Primitive synthesis — Stage 2

**Status:** awaiting owner ratification (README §4.7). Stage 3 does not start until the
rulings below are made and this document is marked ratified.

The input is the merged register `maps/_primitive-observations.csv`: 1,896 rows, 38 kinds,
22 pages. Three agents each synthesised one group of kinds. Their detailed reports hold,
per kind, the variant count, a property-level diff and the justification for the chosen
recipe:

| Group | Kinds | Report | Recipe rows (canonical / needs-ruling) |
|---|---|---|---|
| type-layout | heading, section-band, eyebrow | [_primitive-synthesis.type-layout.md](_primitive-synthesis.type-layout.md) | 153 (109 / 44) |
| surfaces | card, pill, meta-item, avatar, tier-chip, icon-tile, badge, tier-label, meta-row, accent-block, step-badge, count-chip, status-dot, notice, list-bullet, divider | [_primitive-synthesis.surfaces.md](_primitive-synthesis.surfaces.md) | 347 (331 / 16) |
| controls | button-* (8 kinds), search-button, filter-chip, tab, nav-link, link-underline, form-field, form-label, form-hint, search-field, checkbox, choice-row | [_primitive-synthesis.controls.md](_primitive-synthesis.controls.md) | 202 (195 / 7) |

The merged recipe table is [`maps/_recipes.csv`](../maps/_recipes.csv), with 702 rows and
this header:
`component_kind,variant,property,viewport,value,source,evidence,status,note`.
The per-group CSVs `maps/_recipes.<group>.csv` are its sources. Edit those, then re-merge.

## Headline

- **Nearly every canonical recipe is backed by a Lovable measurement.** Only `tier-label`
  and `notice` have no Lovable reference.
- **Drift is systemic, not per-page.** A few INK-wide causes account for most variants:
  1. **Line-height 1.5 on small text.** Lovable sets fixed leading on xs/sm/lg text
     (16/20/28px), while INK uses 1.5. Small text, controls and chips render 1–2px taller.
  2. **Missing font weights.** `theme.json` sets no heading weight, so headings render at
     700. Lovable uses 600, or 400 on some pages (see HR-1). Registered button styles set
     no weight either, so those buttons render at 400 instead of 500.
  3. **Fluid size presets.** The xxl/xxxl/hero sizes produce in-between values Lovable
     never uses, and one element can be sized by up to four layers.
  4. **No shared button base.** All Lovable buttons share one base: inline-flex, gap 8,
     weight 500, radius 6, and four sizes. INK restates button styles per consumer. The
     `ink-ghost` and `ink-sage-outline` block styles also use Lora where Lovable uses
     Inter.
  5. **content-box sizing.** Bordered avatars render 4–8px too large.
  6. **Shadows at rest.** INK puts `shadow.sm` on every card; Lovable cards have none at
     rest.
  7. **Token drift.** Several INK palette values differ from Lovable's (TOK-1 below).
- **Container gutter:** Lovable uses 16px, INK 24px. This is canonical (Lovable), not a
  ruling.

## Owner rulings needed

The IDs refer to the group reports, where each item lists its options and evidence.

### Cross-cutting (decide first; these change many recipes)

| ID | Question |
|---|---|
| TOK-1 (controls TOKEN-1 = surfaces NR-5, NR-6) | Adopt Lovable's palette values where INK's tokens drift: card/#FFF vs surface-alt, foreground, background, border, muted-foreground, secondary and gold-muted (the last differs visibly). INK has no token matching Tailwind's shadow-sm or shadow-lg; should they be added? |
| HR-1 | Heading weight: Lovable uses 400 on my-profiel, skrywer and biblioteek but 600 elsewhere, at the same sizes. One weight site-wide, or per page family? |
| SB-1 | Section vertical padding: three steps (48/64/80), a flat 64, or responsive? |
| NR-3 | The sage tint is 15% in some places and 10% in others. |

### Per component

| ID | Kind | Question |
|---|---|---|
| HR-2 | heading | Work-card title: 20/28 at weight 600, or 20/27.5 at 400? |
| HR-3 | heading | Home hero and gemeenskap hero use two different h1 scales. Keep both? |
| HR-4 | heading | CTA heading on a dark or brand band: two size scales, and white or cream? |
| SB-3 | section-band | Cream tint: 0.4 (eight bands) or 0.3 (skrywer-werk)? |
| EB-1 | eyebrow | Section eyebrow: sm with 0.05em tracking, or xs with 0.2em? |
| EB-2 | eyebrow | Two stat labels deviate from the rest. |
| BTN-1 | button-outline | The two Lovable on-dark outline buttons contradict each other. |
| BTN-2 | button-primary | The lidmaatskap pill button contradicts the "never a pill" rule in `functions.php`. |
| BTN-3 | button-outline | The neutral outline has no registered style, and its hover was never measured. |
| SRCH-1 | search-button | INK-only "Soek" button (the pending row 08a ruling). |
| CHIP-1 | filter-chip | INK-only ontdek date chip. |
| LBL-1 | form-label | Lovable labels use two line-heights, 14px and 20px. |
| NR-1 | tier-label | No Lovable reference, because the writers tab was never paired. The most-used INK variant is proposed. |
| NR-2 | notice | No Lovable reference. The most-used INK variant is proposed. |
| NR-4 | meta-item | Small-icon gap: 6px (7 instances) or 4px (2)? |

### Capture gaps (not rulings; fix in tooling before Stage 3 verification)

- The capture does not record `border-bottom` or `background-image`. As a result, the
  gradient band and some border details are unmeasured.
- Lovable's padding on the inner container of cream bands was not captured.

## Vocabulary changes proposed

None of these has been applied, so the register and recipes still use the Stage-1 kinds.
If ratified, they should be applied in Stage 3, when `primitives.css` class names are
chosen.

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
controls.
